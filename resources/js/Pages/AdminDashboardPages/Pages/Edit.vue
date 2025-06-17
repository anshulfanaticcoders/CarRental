<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Edit Page</span>
                <Link :href="route('admin.pages.index')"
                    class="px-4 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]">
                Back to Pages
                </Link>
            </div>

            <!-- Edit Page Form -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Tabs -->
                        <div class="flex border-b border-gray-200">
                            <button
                                v-for="locale in locales"
                                :key="locale"
                                @click="setActiveLocale(locale, $event)"
                                :class="[
                                    'py-2 px-4 font-semibold',
                                    activeLocale === locale ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700'
                                ]"
                            >
                                {{ locale }}
                            </button>
                        </div>

                        <!-- Translatable Title and Content -->
                        <template v-for="locale in locales" :key="`content-${locale}`">
                            <div v-if="activeLocale === locale">
                                <!-- Title Field -->
                                <div class="space-y-2">
                                    <label :for="`title-${locale}`" class="text-sm font-medium">Title ({{ locale.toUpperCase() }})</label>
                                    <Input :id="`title-${locale}`" v-model="form.translations[locale].title" type="text" class="w-full" required />
                                    <p v-if="form.errors[`translations.${locale}.title`]" class="text-red-500 text-sm">{{ form.errors[`translations.${locale}.title`] }}</p>
                                </div>

                                <!-- Slug Field -->
                                <div class="space-y-2">
                                    <label :for="`slug-${locale}`" class="text-sm font-medium">Slug ({{ locale.toUpperCase() }})</label>
                                    <Input :id="`slug-${locale}`" v-model="form.translations[locale].slug" type="text" class="w-full" required />
                                    <p v-if="form.errors[`translations.${locale}.slug`]" class="text-red-500 text-sm">{{ form.errors[`translations.${locale}.slug`] }}</p>
                                </div>

                                <!-- Content Field -->
                                <div class="space-y-2">
                                    <label :for="`content-${locale}`" class="text-sm font-medium">Content ({{ locale.toUpperCase() }})</label>
                                    <editor v-model="form.translations[locale].content" :id="`content-${locale}`" api-key="l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1" :init="{ height: 500, menubar: false }" />
                                    <p v-if="form.errors[`translations.${locale}.content`]" class="text-red-500 text-sm">{{ form.errors[`translations.${locale}.content`] }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- SEO Meta Fields -->
                        <div class="col-span-1 mt-6 pt-6 border-t border-gray-300">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">SEO Meta Information</h3>
                            <p class="text-sm text-gray-600 mb-4">Page Slug (<code>{{ page.slug }}</code>) is used as the 'URL Slug' for SEO meta (e.g., 'page/{{ page.slug }}').</p>
                            
                            <!-- Non-translatable SEO fields -->
                            <div class="grid grid-cols-1 gap-6 mb-6">
                                <div class="space-y-2">
                                    <label for="seo_title" class="text-sm font-medium">Default SEO Title (Required Fallback)</label>
                                    <Input id="seo_title" v-model="form.seo_title" type="text" class="w-full" maxlength="60" />
                                    <p v-if="form.errors.seo_title" class="text-red-500 text-sm">{{ form.errors.seo_title }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label for="canonical_url" class="text-sm font-medium">Canonical URL</label>
                                    <Input id="canonical_url" v-model="form.canonical_url" type="url" class="w-full" placeholder="https://yourdomain.com/preferred-url" />
                                    <p v-if="form.errors.canonical_url" class="text-red-500 text-sm">{{ form.errors.canonical_url }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label for="seo_image_url" class="text-sm font-medium">SEO Image URL (Open Graph Image)</label>
                                    <Input id="seo_image_url" v-model="form.seo_image_url" type="url" class="w-full" placeholder="https://yourdomain.com/path/to/image.jpg" />
                                    <p v-if="form.errors.seo_image_url" class="text-red-500 text-sm">{{ form.errors.seo_image_url }}</p>
                                </div>
                            </div>

                            <!-- Translatable SEO Fields - Controlled by the main `activeLocale` tab -->
                            <template v-for="locale in locales" :key="`seo-fields-${locale}`">
                                <div v-if="activeLocale === locale" class="grid grid-cols-1 gap-6 mt-4 pt-4 border-t">
                                    <h4 class="text-md font-semibold text-gray-800">Localized SEO Fields ({{ locale.toUpperCase() }})</h4>
                                    <div class="space-y-2">
                                        <label :for="`seo_title_${locale}`" class="text-sm font-medium">SEO Title</label>
                                        <Input :id="`seo_title_${locale}`" v-model="form.seo_translations[locale].seo_title" type="text" class="w-full" maxlength="60" />
                                        <p v-if="form.errors[`seo_translations.${locale}.seo_title`]" class="text-red-500 text-sm">{{ form.errors[`seo_translations.${locale}.seo_title`] }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label :for="`meta_description_${locale}`" class="text-sm font-medium">Meta Description</label>
                                        <textarea :id="`meta_description_${locale}`" v-model="form.seo_translations[locale].meta_description" maxlength="160" rows="3" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                        <p v-if="form.errors[`seo_translations.${locale}.meta_description`]" class="text-red-500 text-sm">{{ form.errors[`seo_translations.${locale}.meta_description`] }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label :for="`keywords_${locale}`" class="text-sm font-medium">Keywords</label>
                                        <Input :id="`keywords_${locale}`" v-model="form.seo_translations[locale].keywords" type="text" class="w-full" placeholder="keyword1, keyword2..." />
                                        <p v-if="form.errors[`seo_translations.${locale}.keywords`]" class="text-red-500 text-sm">{{ form.errors[`seo_translations.${locale}.keywords`] }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <Button type="submit" class="px-4 py-2" :disabled="form.processing">
                                Update Page
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Editor from '@tinymce/tinymce-vue';
import { ref, computed } from 'vue';
import { useToast } from 'vue-toastification';

const props = defineProps({
    page: Object,
    seoMeta: Object,
    seoTranslations: Object,
    available_locales: Array,
    errors: Object,
});

const toast = useToast();
const locales = props.available_locales || ['en', 'fr', 'nl'];
const activeLocale = ref(locales[0]);

// Initialize form.translations for page content
const initialPageTranslations = {};
locales.forEach(locale => {
    const existing = props.page.translations[locale];
    initialPageTranslations[locale] = {
        title: existing ? existing.title : '',
        slug: existing ? existing.slug : '',
        content: existing ? existing.content : '',
    };
});

// Initialize seo_translations for SEO content
const initialSeoTranslations = {};
locales.forEach(locale => {
    const existing = props.seoTranslations[locale];
    initialSeoTranslations[locale] = {
        seo_title: existing?.seo_title || '',
        meta_description: existing?.meta_description || '',
        keywords: existing?.keywords || '',
    };
});

const form = useForm({
    _method: 'PUT',
    translations: initialPageTranslations,
    // SEO Fields
    seo_title: props.seoMeta?.seo_title || (initialPageTranslations.en?.title || ''),
    canonical_url: props.seoMeta?.canonical_url || '',
    seo_image_url: props.seoMeta?.seo_image_url || '',
    // Translated SEO fields
    seo_translations: initialSeoTranslations,
});

const setActiveLocale = (locale, event) => {
    if (event) event.preventDefault();
    activeLocale.value = locale;
};

const submit = () => {
    form.post(route('admin.pages.update', props.page.id), {
        onSuccess: () => {
            toast.success('Page updated successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
        onError: (errors) => {
            console.error('Error updating page:', errors);
            let errorMessages = Object.values(errors).join(' ');
            toast.error('Error updating page: ' + errorMessages, {
                position: 'top-right',
                timeout: 7000,
            });
        }
    });
};
</script>
