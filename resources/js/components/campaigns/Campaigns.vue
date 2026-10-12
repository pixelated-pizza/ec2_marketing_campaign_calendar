<template>
    <div class="flex flex-col bg-gray-100 dark:bg-gray-900">
        <div class="bg-white dark:bg-gray-800 mb-5 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm text-gray-900 dark:text-white text-center tracking-wide drop-shadow-sm py-3">
                Website Sales, Promotions, Marketplaces Campaigns
            </h4>

            <div class="flex flex-wrap items-center gap-2 ml-5 pb-3">
                <Select v-model="selectedChannel" :options="channels" optionLabel="name" optionValue="channel_id"
                    placeholder="All Channels" class="w-48" showClear />

                <InputText v-model="searchTerm" placeholder="Search Campaigns..." class="w-60" />

                <DatePicker v-model="dateRange" selectionMode="range" dateFormat="yy-mm-dd"
                    placeholder="Filter by Date Range" class="w-64" showIcon />

                <Button label="Reset" icon="pi pi-refresh" class="p-button-secondary" @click="resetFilters" />

                <Button label="Add Campaign" icon="pi pi-plus" severity="success" @click="openAddDialog()" />
            </div>
        </div>

        <div class="relative w-full bg-white dark:bg-gray-900">
            <!-- Univer needs a container with an explicit height -->
            <div ref="univerContainer" class="univer-container w-full h-[calc(100vh-250px)] min-h-[500px]"></div>

            <div v-show="loading"
                class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex flex-col gap-4 justify-center items-center z-10">
                <p class="text-gray-600 dark:text-gray-300 text-lg">
                    Loading calendar...
                </p>

                <Skeleton height="2rem" width="70%" />
                <Skeleton height="2rem" width="50%" />
                <Skeleton height="1rem" width="90%" />
                <Skeleton height="1rem" width="85%" />
                <Skeleton height="1rem" width="95%" />
            </div>
        </div>

        <!-- Add / edit campaign -->
        <Dialog v-model:visible="showDialog" modal :header="editId ? 'Edit Campaign' : 'Add Campaign'"
            :style="{ width: '30rem' }" :breakpoints="{ '640px': '95vw' }">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1">Campaign Name / Promo</label>
                    <Textarea v-model="form.name" rows="3" class="w-full" autoResize />
                    <Message v-if="errors.name" severity="error" size="small" variant="simple">
                        {{ errors.name }}
                    </Message>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1">Channel</label>
                    <Select v-model="form.channel_id" :options="channels" optionLabel="name"
                        :optionValue="(c) => String(c.channel_id)" placeholder="Select a channel" class="w-full" />
                    <Message v-if="errors.channel_id" severity="error" size="small" variant="simple">
                        {{ errors.channel_id }}
                    </Message>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1">Start</label>
                    <DatePicker v-model="form.start_date" showTime hourFormat="12" :stepMinute="1" fluid />
                    <Message v-if="errors.start_date" severity="error" size="small" variant="simple">
                        {{ errors.start_date }}
                    </Message>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1">End</label>
                    <DatePicker v-model="form.end_date" showTime hourFormat="12" :stepMinute="1" fluid />
                    <Message v-if="errors.end_date" severity="error" size="small" variant="simple">
                        {{ errors.end_date }}
                    </Message>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between w-full">
                    <div>
                        <Button v-if="editId" label="Delete" icon="pi pi-trash" severity="danger" text
                            :disabled="saving" @click="confirmDelete" />
                    </div>
                    <div class="flex gap-2">
                        <Button label="Cancel" severity="secondary" @click="closeDialog" />
                        <Button :label="saving ? 'Saving...' : 'Save'" icon="pi pi-check" severity="success"
                            :loading="saving" :disabled="saving" @click="saveCampaign" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Delete confirmation -->
        <Dialog v-model:visible="showDeleteDialog" header="Delete Campaign" modal :closable="false"
            :style="{ width: '400px' }">
            <p>Are you sure you want to delete the campaign "{{ form.name }}"?</p>
            <template #footer>
                <Button label="No" severity="secondary" text @click="showDeleteDialog = false" />
                <Button label="Yes, delete" severity="danger" @click="deleteConfirmed" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Dialog from "primevue/dialog";
import Message from "primevue/message";
import Skeleton from "primevue/skeleton";
import { useCampaignUniver } from "@/js/composables/useCampaignUniver.js";

const {
    loading,
    univerContainer,
    selectedChannel,
    searchTerm,
    dateRange,
    channels,
    resetFilters,
    showDialog,
    showDeleteDialog,
    saving,
    editId,
    form,
    errors,
    openAddDialog,
    closeDialog,
    saveCampaign,
    confirmDelete,
    deleteConfirmed,
} = useCampaignUniver();
</script>

<style>
.univer-container {
    width: 100%;
    min-height: 500px;
}
</style>