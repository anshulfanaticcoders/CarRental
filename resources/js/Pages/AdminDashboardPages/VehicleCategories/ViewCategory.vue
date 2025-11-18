<template>
    <DialogContent class="max-w-2xl p-0">
        <DialogHeader class="border-b px-6 pt-6">
            <DialogTitle>Category Details</DialogTitle>
            <DialogDescription>
                View all information about this vehicle category
            </DialogDescription>
        </DialogHeader>
        <div class="px-6 py-4 max-h-[calc(90vh-140px)] overflow-y-auto">
            <!-- Image Preview -->
            <div v-if="user.image" class="flex justify-center">
                <img
                    :src="user.image"
                    :alt="user.name"
                    class="w-48 h-32 rounded-lg object-cover border-2 border-gray-200"
                >
            </div>
            <div v-else class="flex justify-center">
                <div class="w-48 h-32 rounded-lg bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center">
                    <span class="text-gray-500">No Image</span>
                </div>
            </div>

            <!-- Category Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Category Name</label>
                    <Input v-model="user.name" readonly class="bg-gray-50 mt-1" />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Slug</label>
                    <Input v-model="user.slug" readonly class="bg-gray-50 mt-1" />
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Description</label>
                <textarea
                    v-model="user.description"
                    readonly
                    rows="3"
                    class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm"
                ></textarea>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Image Alt Text</label>
                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                    {{ user.alt_text || 'No alt text provided' }}
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Status</label>
                <div class="mt-1">
                    <Badge :variant="user.status ? 'default' : 'secondary'">
                        {{ user.status ? "Active" : "Inactive" }}
                    </Badge>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Created At</label>
                    <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                        {{ formatDateTime(user.created_at) }}
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Updated At</label>
                    <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                        {{ formatDateTime(user.updated_at) }}
                    </div>
                </div>
            </div>
        </div>
        <DialogFooter class="border-t px-6 py-4">
            <Button @click="$emit('close')">Close</Button>
        </DialogFooter>
    </DialogContent>
</template>

<script setup>
import { computed } from 'vue';
import { DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import Badge from "@/Components/ui/badge/Badge.vue";

const props = defineProps({
    user: Object,
});

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return 'N/A';

    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}`;
};

// Computed properties for reactive date formatting
const createdAtFormatted = computed(() => formatDateTime(props.user.created_at));
const updatedAtFormatted = computed(() => formatDateTime(props.user.updated_at));
</script>