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
    content: ''
});

const submit = () => {
    form.post(route('admin.pages.store'));
    toast.success('Page created successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
};
</script>
