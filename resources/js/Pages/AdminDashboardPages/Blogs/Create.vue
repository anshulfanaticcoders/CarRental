<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Create New Blog Post</span>
                <Link :href="route('admin.blogs.index')"
                    class="px-4 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]">
                Back to Blogs
                </Link>
            </div>

            <!-- Create Blog Form -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Tabs for Locales -->
                        <div class="flex border-b border-gray-200">
                            <button
                                v-for="lang in available_locales"
                                :key="lang"
                                type="button"
                                @click="setActiveLocale(lang)"
                                :class="[
                                    'py-2 px-4 font-semibold',
                                    activeLocale === lang ? 'border-b-2 border-primary text-primary' : 'text-gray-500 hover:text-gray-700'
                                ]"
                            >
                                {{ lang.toUpperCase() }}
                            </button>
                        </div>

                        <!-- Title Field -->
                        <div class="space-y-2">
                            <label :for="'title-' + activeLocale" class="text-sm font-medium">Title ({{ activeLocale.toUpperCase() }})</label>
                            <Input :id="'title-' + activeLocale" v-model="form.translations[activeLocale].title" type="text" class="w-full" required />
                            <p v-if="form.errors[`translations.${activeLocale}.title`]" class="text-red-500 text-sm">{{ form.errors[`translations.${activeLocale}.title`] }}</p>
                        </div>

                        <!-- Content Field -->
                        <div class="space-y-2">
                            <label :for="'content-' + activeLocale" class="text-sm font-medium">Content ({{ activeLocale.toUpperCase() }})</label>
                            <Editor
                                :id="'content-' + activeLocale"
                                v-model="form.translations[activeLocale].content"
                                api-key="l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1"
                                :init="{
                                    height: 500,
                                    menubar: false,
                                    plugins: [
                                        'advlist autolink lists link image charmap print preview anchor',
                                        'searchreplace visualblocks code fullscreen',
                                        'insertdatetime media table paste code help wordcount'
                                    ],
                                    toolbar:
                                        'undo redo | formatselect | bold italic backcolor | \
                                        alignleft aligncenter alignright alignjustify | \
                                        bullist numlist outdent indent | removeformat | help'
                                }"
                            />
                            <p v-if="form.errors.content" class="text-red-500 text-sm">{{ form.errors.content }}</p>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="space-y-2">
                            <label for="image" class="text-sm font-medium">Blog Image</label>
                            <Input id="image" type="file" @input="form.image = $event.target.files[0]" accept="image/*" class="w-full" />
                            <p v-if="form.errors.image" class="text-red-500 text-sm">{{ form.errors.image }}</p>
                        </div>

                        <!-- Is Published Toggle -->
                        <div class="flex items-center space-x-2">
                             <label for="is_published" class="text-sm font-medium">Publish Post</label>
                            <Switch id="is_published" v-model:checked="form.is_published" />
                            <p v-if="form.errors.is_published" class="text-red-500 text-sm">{{ form.errors.is_published }}</p>
                        </div>


                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <Button type="submit" class="px-4 py-2" :disabled="form.processing">
                                Create Blog Post
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
import { Switch } from '@/Components/ui/switch'; // Assuming you have a Switch component
import Editor from '@tinymce/tinymce-vue';
import { ref, watch } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    available_locales: Array,
    current_locale: String,
    errors: Object,
});

const activeLocale = ref(props.current_locale || props.available_locales[0]);

const initialTranslations = {};
props.available_locales.forEach(locale => {
    initialTranslations[locale] = { title: '', content: '' };
});

const form = useForm({
    translations: initialTranslations,
    image: null,
    is_published: true,
});

const setActiveLocale = (locale) => {
    activeLocale.value = locale;
};

const submitForm = () => {
    // The form.translations object already holds all the data
    form.post(route('admin.blogs.store'), {
        onSuccess: () => {
            toast.success('Blog post created successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
            form.reset(); // Reset form fields including translations
            // Re-initialize translations part of the form after reset
            const newInitialTranslations = {};
            props.available_locales.forEach(locale => {
                newInitialTranslations[locale] = { title: '', content: '' };
            });
            form.translations = newInitialTranslations;
            setActiveLocale(props.current_locale || props.available_locales[0]);
        },
        onError: (formErrors) => {
            // Display all translation errors
            for (const key in formErrors) {
                if (key.startsWith('translations.')) {
                    toast.error(formErrors[key], { position: 'top-right', timeout: 3000 });
                }
            }
            if (formErrors.image) {
                toast.error(formErrors.image, { position: 'top-right', timeout: 3000 });
            }
        }
    });
};

</script>
