<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Home Page Settings</span>
            </div>

            <Card class="w-full max-w-2xl mt-4">
                <CardHeader>
                    <CardTitle>Hero Section Image</CardTitle>
                    <CardDescription>
                        Upload a high-quality image for the homepage hero section.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitHeroImage" class="space-y-6">
                        
                        <!-- Current Image Display -->
                         <div v-if="settings.hero_image && !previewUrl" class="space-y-2">
                             <Label>Current Image</Label>
                             <div class="relative overflow-hidden rounded-lg border shadow-sm w-full h-64">
                                <img :src="settings.hero_image" alt="Current Hero Image" class="w-full h-full object-cover" />
                             </div>
                         </div>

                        <!-- Drag and Drop Zone -->
                        <div class="space-y-2">
                            <Label for="hero_image">Upload New Image</Label>
                            
                            <div 
                                class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
                                :class="{ 'border-blue-500 bg-blue-50': isDragging, 'border-gray-300 hover:border-gray-400': !isDragging }"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                                @click="triggerFileInput"
                            >
                                <input
                                    ref="fileInputRef"
                                    id="hero_image"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                
                                <div v-if="previewUrl" class="relative w-full h-64 rounded-lg overflow-hidden group">
                                     <img :src="previewUrl" alt="Preview" class="w-full h-full object-cover" />
                                     <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                         <p class="text-white font-medium">Click or Drop to Replace</p>
                                     </div>
                                     <button 
                                        @click.stop="clearSelection" 
                                        type="button"
                                        class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full hover:bg-red-600 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                     </button>
                                </div>

                                <div v-else class="flex flex-col items-center justify-center gap-2 py-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload-cloud text-gray-400"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><path d="m16 16-4-4-4 4"/></svg>
                                    <p class="text-sm font-medium text-gray-600">
                                        <span class="text-blue-600">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        SVG, PNG, JPG or WEBP (max. 1920x1080px, 5MB)
                                    </p>
                                </div>
                            </div>
                            <InputError :message="form.errors.hero_image" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Button type="submit" :disabled="form.processing || !form.hero_image">
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save Changes</span>
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-medium">Saved successfully.</p>
                            </Transition>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import InputError from "@/Components/InputError.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'

const props = defineProps({
    settings: Object,
});

const form = useForm({
    hero_image: null,
});

const isDragging = ref(false);
const previewUrl = ref(null);
const fileInputRef = ref(null);

const triggerFileInput = () => {
    fileInputRef.value.click();
};

const handleFile = (file) => {
    if (file && file.type.startsWith('image/')) {
        form.hero_image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    handleFile(file);
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    handleFile(file);
};

const clearSelection = () => {
    form.hero_image = null;
    previewUrl.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = null;
    }
};

const submitHeroImage = () => {
    form.post(route('admin.settings.homepage.hero-image'), {
        preserveScroll: true,
        onSuccess: () => {
             // Reset form but keep image if it's now current? 
             // Actually, after success, props.settings.hero_image will update.
             // So we should clear our preview and form data to show the "Current Image" from props.
             clearSelection();
        },
    });
};
</script>
