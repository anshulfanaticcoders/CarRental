<template>
    <DialogContent class="max-w-2xl p-0">
        <DialogHeader class="border-b px-6 pt-6">
            <DialogTitle>Create New Category</DialogTitle>
            <DialogDescription>
                Add a new vehicle category to your system
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4 max-h-[calc(90vh-140px)] overflow-y-auto">
            <!-- Image Upload Area -->
            <div>
                <label class="text-sm font-medium text-gray-700 mb-2 block">Category Image *</label>
                <div
                    class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-gray-400 transition-colors cursor-pointer"
                    :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                    @drop="handleDrop"
                    @dragover.prevent="handleDragOver"
                    @dragleave.prevent="handleDragLeave"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        @change="handleFileUpload"
                        accept="image/jpeg,image/png,image/jpg,image/gif"
                        required
                    >

                    <!-- Image Preview -->
                    <div v-if="imagePreview" class="space-y-4">
                        <div class="relative">
                            <img :src="imagePreview" alt="Preview" class="mx-auto h-48 w-full object-cover rounded-lg">
                            <button
                                type="button"
                                @click.stop="removeImage"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="text-center text-sm text-gray-600">
                            <p class="font-medium">{{ selectedFileName }}</p>
                            <p>Click or drag to replace image</p>
                        </div>
                    </div>

                    <!-- Upload Instructions -->
                    <div v-else class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">Drag and drop an image here, or click to select</p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="text-sm font-medium text-gray-700 mb-1 block">Category Name *</label>
                    <Input id="name" v-model="form.name" placeholder="Enter category name" required />
                </div>
                <div>
                    <label for="slug" class="text-sm font-medium text-gray-700 mb-1 block">Slug</label>
                    <Input id="slug" v-model="form.slug" placeholder="URL-friendly name" />
                </div>
            </div>

            <div>
                <label for="description" class="text-sm font-medium text-gray-700 mb-1 block">Description</label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    placeholder="Describe this category..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
            </div>

            <div>
                <label for="alt_text" class="text-sm font-medium text-gray-700 mb-1 block">Image Alt Text</label>
                <Input id="alt_text" v-model="form.alt_text" placeholder="Describe the image for SEO and accessibility" />
                <p class="text-xs text-gray-500 mt-1">Important for SEO and screen readers</p>
            </div>

            <DialogFooter class="border-t px-6 py-4">
                <Button type="button" variant="outline" @click="$emit('close')">Cancel</Button>
                <Button type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? 'Creating...' : 'Create Category' }}
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";
const emit = defineEmits(['close']);

// Reactive state
const form = ref({
    name: '',
    slug: '',
    description: '',
    alt_text: '',
    image: null,
});

const isSubmitting = ref(false);
const isDragging = ref(false);
const imagePreview = ref(null);
const selectedFileName = ref('');

// Watch name to auto-generate slug
watch(() => form.value.name, (newName) => {
    if (newName) {
        form.value.slug = newName.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
});

// Drag and drop handlers
const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
        handleImageFile(files[0]);
    }
};

// File upload handler
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        handleImageFile(file);
    }
};

// Handle image file
const handleImageFile = (file) => {
    if (file.size > 10 * 1024 * 1024) { // 10MB limit
        toast.error('Image size should not exceed 10MB');
        return;
    }

    if (!file.type.startsWith('image/')) {
        toast.error('Please select a valid image file');
        return;
    }

    form.value.image = file;
    selectedFileName.value = file.name;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

// Remove image
const removeImage = () => {
    form.value.image = null;
    imagePreview.value = null;
    selectedFileName.value = '';
    // Reset file input
    if (document.querySelector('input[type="file"]')) {
        document.querySelector('input[type="file"]').value = '';
    }
};

// Submit form
const submitForm = () => {
    isSubmitting.value = true;

    // Create FormData for file upload
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('slug', form.value.slug);
    formData.append('description', form.value.description);
    formData.append('alt_text', form.value.alt_text);
    if (form.value.image) {
        formData.append('image', form.value.image);
    }

    router.post("/vehicles-categories", formData, {
        onSuccess: () => {
            toast.success('Vehicle Category created successfully');
            // Reset form
            form.value = {
                name: '',
                slug: '',
                description: '',
                alt_text: '',
                image: null,
            };
            imagePreview.value = null;
            selectedFileName.value = '';
        },
        onError: (errors) => {
            toast.error('Failed to create category. Please try again.');
        },
        onFinish: () => {
            isSubmitting.value = false;
            emit('close');
        },
    });
};
</script>