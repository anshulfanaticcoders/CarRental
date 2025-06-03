<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Create New Page</span>
                <Link :href="route('admin.pages.index')"
                    class="px-4 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]">
                Back to Pages
                </Link>
            </div>

            <!-- Create Page Form -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Tabs -->
                        <div class="flex border-b border-gray-200">
                            <button
                                v-for="locale in locales"
                                :key="locale"
                                @click="activeLocale = locale"
                                :class="[
                                    'py-2 px-4 font-semibold',
                                    activeLocale === locale ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700'
                                ]"
                            >
                                {{ locale }}
                            </button>
                        </div>

                        <!-- Title Field -->
                        <div class="space-y-2">
                            <label for="title" class="text-sm font-medium">Title ({{ activeLocale }})</label>
                            <Input id="title" v-model="form.title" type="text" class="w-full" required />
                            <p v-if="form.errors.title" class="text-red-500 text-sm">{{ form.errors.title }}</p>
                        </div>

                        <!-- Content Field -->
                        <div class="space-y-2">
                            <label for="content" class="text-sm font-medium">Content ({{ activeLocale }})</label>
                            <editor v-model="form.content" api-key="l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1" :init="{ height: 500, menubar: false }" />
                            <p v-if="form.errors.content" class="text-red-500 text-sm">{{ form.errors.content }}</p>
                        </div>

                        <!-- SEO Meta Fields -->
                        <div class="col-span-1 mt-6 pt-6 border-t border-gray-300">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">SEO Meta Information</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <!-- SEO Title -->
                                <div class="space-y-2">
                                    <label for="seo_title" class="text-sm font-medium">SEO Title (Max 60 chars)</label>
                                    <Input id="seo_title" v-model="form.seo_title" type="text" class="w-full" maxlength="60" />
                                    <p v-if="form.errors.seo_title" class="text-red-500 text-sm">{{ form.errors.seo_title }}</p>
                                </div>

                                <!-- Meta Description -->
                                <div class="space-y-2">
                                    <label for="meta_description" class="text-sm font-medium">Meta Description (Max 160 chars)</label>
                                    <textarea id="meta_description" v-model="form.meta_description" maxlength="160" rows="3" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    <p v-if="form.errors.meta_description" class="text-red-500 text-sm">{{ form.errors.meta_description }}</p>
                                </div>

                                <!-- Keywords -->
                                <div class="space-y-2">
                                    <label for="keywords" class="text-sm font-medium">Keywords (comma-separated)</label>
                                    <Input id="keywords" v-model="form.keywords" type="text" class="w-full" />
                                    <p v-if="form.errors.keywords" class="text-red-500 text-sm">{{ form.errors.keywords }}</p>
                                </div>

                                <!-- Canonical URL -->
                                <div class="space-y-2">
                                    <label for="canonical_url" class="text-sm font-medium">Canonical URL</label>
                                    <Input id="canonical_url" v-model="form.canonical_url" type="url" class="w-full" placeholder="https://yourdomain.com/preferred-url" />
                                    <p v-if="form.errors.canonical_url" class="text-red-500 text-sm">{{ form.errors.canonical_url }}</p>
                                </div>

                                <!-- SEO Image URL -->
                                <div class="space-y-2">
                                    <label for="seo_image_url" class="text-sm font-medium">SEO Image URL (Open Graph Image)</label>
                                    <Input id="seo_image_url" v-model="form.seo_image_url" type="url" class="w-full" placeholder="https://yourdomain.com/path/to/image.jpg" />
                                    <p v-if="form.errors.seo_image_url" class="text-red-500 text-sm">{{ form.errors.seo_image_url }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <Button type="submit" class="px-4 py-2" :disabled="form.processing">
                                Create Page
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Editor from '@tinymce/tinymce-vue';
import { ref } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

const locales = ['en', 'fr', 'nl'];
const activeLocale = ref('en');

const form = useForm({
    locale: 'en',
    title: '',
    content: '',
    // SEO Fields
    seo_title: '',
    meta_description: '',
    keywords: '',
    canonical_url: '',
    seo_image_url: '',
});

const submit = () => {
    form.post(route('admin.pages.store'), {
        onSuccess: () => {
            toast.success('Page created successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
        onError: (errors) => {
            console.error('Error creating page:', errors);
            let errorMessages = Object.values(errors).join(' ');
            toast.error('Error creating page: ' + errorMessages, { // Display backend validation errors
                position: 'top-right',
                timeout: 7000, // Longer timeout for errors
            });
        }
    });
};
</script>
