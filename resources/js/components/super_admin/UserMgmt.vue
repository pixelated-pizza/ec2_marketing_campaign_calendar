<template>
  <div class="p-4 card">
    <h2 class="text-2xl font-bold mb-4">User Management</h2>

    <!-- Error banner -->
    <div
      v-if="authStore.error"
      class="mb-4 p-3 bg-red-100 text-red-700 rounded"
    >
      {{ authStore.error }}
    </div>

    <!-- Search and Add -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
      <InputText
        v-model="globalFilter"
        placeholder="Search users..."
        class="p-inputtext-sm w-full sm:w-64"
      />
      <Button
        label="Add User"
        icon="pi pi-plus"
        class="p-button-sm p-button-primary"
        @click="openAddDialog"
      />
    </div>

    <!-- Users Table -->
    <DataTable
      :value="users"
      :paginator="true"
      :rows="10"
      :globalFilterFields="['name', 'email', 'role_name']"
      :filters="filters"
      responsiveLayout="scroll"
      class="p-datatable-sm"
      :loading="authStore.loading"
    >
      <Column field="name"      header="Name"  sortable />
      <Column field="email"     header="Email" sortable />
      <Column field="role_name" header="Role"  sortable />
      <Column header="Actions">
        <template #body="slotProps">
          <Button
            icon="pi pi-pencil"
            class="p-button-sm p-button-text p-button-info mr-2"
            @click="openEditDialog(slotProps.data)"
          />
          <Button
            icon="pi pi-trash"
            class="p-button-sm p-button-text p-button-danger"
            @click="confirmDelete(slotProps.data)"
          />
        </template>
      </Column>
    </DataTable>

    <!-- Add / Edit Dialog -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? 'Edit User' : 'Add User'"
      modal
      class="w-full max-w-md"
    >
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
          <Dropdown
            v-model="form.role_id"
            :options="roles"
            optionLabel="role_name"
            optionValue="role_id"
            placeholder="Select a role"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" class="p-button-text" @click="closeDialog" />
        <Button
          :label="isEditing ? 'Save' : 'Create'"
          :loading="saving"
          @click="submitForm"
        />
      </template>
    </Dialog>

    <!-- Delete Confirm Dialog -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';

import DataTable    from 'primevue/datatable';
import Column       from 'primevue/column';
import Button       from 'primevue/button';
import InputText    from 'primevue/inputtext';
import Dialog       from 'primevue/dialog';
import Password     from 'primevue/password';
import Dropdown     from 'primevue/dropdown';
import ConfirmDialog from 'primevue/confirmdialog';

import { useAuthStore } from '@/js/stores/useAuthStore';
import { fetchUsers, createUser, updateUser, deleteUser } from '@/js/api/user_mgmt_api';

const authStore = useAuthStore();
const confirm   = useConfirm();
const toast     = useToast();

const users        = ref([]);
const globalFilter = ref('');
const filters      = reactive({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

watch(globalFilter, (val) => {
    filters.global.value = val;
});

const dialogVisible = ref(false);
const isEditing     = ref(false);
const saving        = ref(false);

const emptyForm = () => ({ id: null, name: '', email: '', password: '', role_id: null });
const form      = reactive(emptyForm());

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
        id:       row.id,
        name:     row.name,
        email:    row.email,
        password: '',
        role_id:  row.role_id ?? null,
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
            summary:  'Error',
            detail:   err?.response?.data?.message ?? 'Operation failed.',
            life:     4000,
        });
    } finally {
        saving.value = false;
    }
}

function confirmDelete(row) {
    confirm.require({
        message:     `Delete user "${row.name}"? This cannot be undone.`,
        header:      'Confirm Delete',
        icon:        'pi pi-exclamation-triangle',
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
</script>

<style scoped>
</style>