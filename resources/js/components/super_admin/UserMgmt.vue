<template>
  <div class="um-wrapper flex flex-col h-[calc(100vh-120px)] min-h-[500px] p-4 card">
    <h2 class="text-2xl font-bold mb-4">User Management</h2>

    <!-- Error banner -->
    <div v-if="authStore.error" class="mb-4 p-3 bg-red-100 text-red-700 rounded">
      {{ authStore.error }}
    </div>

    <!-- Search and Add -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
      <InputText v-model="globalFilter" placeholder="Search users..." class="p-inputtext-sm w-full sm:w-64" />
      <Button label="Add User" icon="pi pi-plus" class="p-button-sm p-button-primary" @click="openAddDialog" />
    </div>

    <!-- Users Grid -->
    <div
      class="relative flex-1 min-h-[300px] border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
      :data-ag-theme-mode="isDark ? 'dark' : 'light'"
    >
      <AgGridVue
        style="position: absolute; width: 100%; height: 100%; left: 0; top: 0"
        :theme="gridTheme"
        :columnDefs="columnDefs"
        :rowData="users"
        :defaultColDef="defaultColDef"
        :getRowId="getRowId"
        :quickFilterText="globalFilter"
        :loading="authStore.loading || loading"
        :pagination="true"
        :paginationPageSize="10"
        :paginationPageSizeSelector="[10, 20, 50]"
        :enableCellTextSelection="true"
        :ensureDomOrder="true"
        :suppressCellFocus="true"
        :localeText="{ noRowsToShow: 'No users found' }"
      />
    </div>

    <!-- Add / Edit Dialog -->
    <Dialog v-model:visible="dialogVisible" :header="isEditing ? 'Edit User' : 'Add User'" modal
      class="w-full max-w-md">
      <div class="flex flex-col gap-3 pt-2">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Name</label>
          <InputText v-model="form.name" placeholder="Full name" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Email</label>
          <InputText v-model="form.email" placeholder="millstrading.user@..." />
        </div>
        <div v-if="!isEditing" class="flex flex-col gap-1">
          <label class="text-sm font-medium">Password</label>
          <Password v-model="form.password" :feedback="false" toggleMask />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Role</label>
          <Dropdown v-model="form.role_id" :options="roles" optionLabel="role_name" optionValue="role_id"
            placeholder="Select a role" />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" class="p-button-text" @click="closeDialog" />
        <Button :label="isEditing ? 'Save' : 'Create'" :loading="saving" @click="submitForm" />
      </template>
    </Dialog>

    <!-- Delete Confirm Dialog -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { AgGridVue } from 'ag-grid-vue3';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import ConfirmDialog from 'primevue/confirmdialog';

import { useAuthStore } from '@/js/stores/useAuthStore';
import { fetchUsers, createUser, updateUser, deleteUser } from '@/js/api/user_mgmt_api';
import { useAgGridTheme } from '@/js/composables/useAgGridTheme.js';
import ExcelSetFilter from '@/js/components/utils/ExcelSetFilter.vue';

const authStore = useAuthStore();
const confirm = useConfirm();
const toast = useToast();

const { isDark, gridTheme } = useAgGridTheme();

const users = ref([]);
const loading = ref(false);
const globalFilter = ref('');

const dialogVisible = ref(false);
const isEditing = ref(false);
const saving = ref(false);

const emptyForm = () => ({ id: null, name: '', email: '', password: '', role_id: null });
const form = reactive(emptyForm());

const roles = ref([
  { role_id: 1, role_name: 'Admin' },
  { role_id: 2, role_name: 'Editor' },
  { role_id: 3, role_name: 'Viewer' },
]);

onMounted(async () => {
  await authStore.fetchUser();
  await loadUsers();
});

async function loadUsers() {
  loading.value = true;
  try {
    const { data } = await fetchUsers();
    users.value = data;
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load users.',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
}

function openAddDialog() {
  isEditing.value = false;
  Object.assign(form, emptyForm());
  dialogVisible.value = true;
}

function openEditDialog(row) {
  isEditing.value = true;
  Object.assign(form, {
    id: row.id,
    name: row.name,
    email: row.email,
    password: '',
    role_id: row.role_id ?? null,
  });
  dialogVisible.value = true;
}

function closeDialog() {
  dialogVisible.value = false;
}

async function submitForm() {
  saving.value = true;
  try {
    if (isEditing.value) {
      await updateUser(form.id, { name: form.name, email: form.email, role_id: form.role_id });
      toast.add({ severity: 'success', summary: 'Updated', detail: 'User updated.', life: 3000 });
    } else {
      await createUser({ name: form.name, email: form.email, password: form.password, role_id: form.role_id });
      toast.add({ severity: 'success', summary: 'Created', detail: 'User created.', life: 3000 });
    }
    closeDialog();
    await loadUsers();
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err?.response?.data?.message ?? 'Operation failed.',
      life: 4000,
    });
  } finally {
    saving.value = false;
  }
}

function confirmDelete(row) {
  confirm.require({
    message: `Delete user "${row.name}"? This cannot be undone.`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await deleteUser(row.id);
        toast.add({ severity: 'success', summary: 'Deleted', detail: 'User removed.', life: 3000 });
        await loadUsers();
      } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Delete failed.', life: 3000 });
      }
    },
  });
}

// ---------------------------------------------------------------------------
// Grid
// ---------------------------------------------------------------------------
const getRowId = (params) => String(params.data.id);

function iconButton(iconClass, title, colorClasses, onClick) {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.title = title;
  btn.setAttribute('aria-label', title);
  btn.className = `inline-flex h-8 w-8 items-center justify-center rounded-full ${colorClasses}`;
  btn.innerHTML = `<i class="pi ${iconClass}"></i>`;
  btn.addEventListener('click', onClick);
  return btn;
}

function actionsRenderer(params) {
  const wrap = document.createElement('div');
  wrap.className = 'flex items-center gap-1';
  wrap.appendChild(
    iconButton('pi-pencil', 'Edit user', 'text-sky-500 hover:bg-sky-500/10', () => openEditDialog(params.data)),
  );
  wrap.appendChild(
    iconButton('pi-trash', 'Delete user', 'text-red-500 hover:bg-red-500/10', () => confirmDelete(params.data)),
  );
  return wrap;
}

const defaultColDef = {
  sortable: true,
  resizable: true,
  filter: ExcelSetFilter,
  floatingFilter: false,
  flex: 1,
  minWidth: 160,
};

const columnDefs = [
  { colId: 'name', headerName: 'Name', field: 'name' },
  { colId: 'email', headerName: 'Email', field: 'email' },
  { colId: 'role_name', headerName: 'Role', field: 'role_name', maxWidth: 220 },
  {
    colId: 'actions',
    headerName: 'Actions',
    cellRenderer: actionsRenderer,
    sortable: false,
    filter: false,
    resizable: false,
    flex: 0,
    width: 120,
    pinned: 'right',
  },
];
</script>

<style>
.um-wrapper .ag-cell {
  display: flex;
  align-items: center;
}
</style>
