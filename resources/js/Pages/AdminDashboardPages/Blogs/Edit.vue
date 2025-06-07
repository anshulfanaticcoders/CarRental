<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Edit Blog Post</span>
                <Link :href="route('admin.blogs.index')"
                    class="px-4 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]">
                Back to Blogs
                </Link>
            </div>

            <!-- Edit Blog Form -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <form @submit.prevent="submitForm">
                    <!-- Form Header: Locales Tabs and Publish Switch -->
                    <div class="flex justify-between items-center border-b border-gray-200 mb-6 pb-2">
                        <!-- Tabs for Locales -->
                        <div class="flex">
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

                        <!-- Is Published Toggle (Moved to Top) -->
                        <div class="flex items-center space-x-2">
                            <label for="is_published_top" class="text-sm font-medium">Publish Post</label>
                            <Switch id="is_published_top" v-model:checked="form.is_published" />
                            <!-- Error for is_published can be handled globally or near submit if needed -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
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
                                    menubar: true
                                }"
                            />
                            <p v-if="form.errors.content" class="text-red-500 text-sm">{{ form.errors.content }}</p>
                        </div>

                        <!-- Slug Field -->
                        <div class="space-y-2">
                            <label for="slug" class="text-sm font-medium">Slug</label>
                            <Input id="slug" v-model="form.slug" type="text" class="w-full" required />
                            <p v-if="form.errors.slug" class="text-red-500 text-sm">{{ form.errors.slug }}</p>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="space-y-2">
                            <label for="image" class="text-sm font-medium">Blog Image</label>
                            <Input id="image" type="file" @input="form.image = $event.target.files[0]" accept="image/*" class="w-full" />
                            <p v-if="form.errors.image" class="text-red-500 text-sm">{{ form.errors.image }}</p>
                            <img v-if="blog.image && !form.image" :src="blog.image" alt="Current blog image" class="mt-2 h-32 object-cover" />
                        </div>

                        <!-- SEO Meta Fields -->
                        <div class="col-span-1 mt-6 pt-6 border-t border-gray-300">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">SEO Meta Information</h3>
                            <p class="text-sm text-gray-600 mb-4">Blog Slug (<code>{{ form.slug }}</code>) is used as the 'URL Slug' for SEO meta (e.g., 'blog/{{ form.slug }}').</p>
                            
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

                            <!-- Translatable SEO Fields - Now controlled by the main `activeLocale` tab -->
                            <template v-for="locale in available_locales" :key="`seo-fields-${locale}`">
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
                                Update Blog Post
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
import { Switch } from '@/Components/ui/switch';
import Editor from '@tinymce/tinymce-vue';
import { ref, watch, onMounted, computed } from 'vue'; // Added computed
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    blog: Object,
    available_locales: Array,
    current_locale: String,
    errors: Object,
    seoMeta: {
        type: Object,
        default: null,
    },
    seoTranslations: { // Added seoTranslations prop
        type: Object,
        default: () => ({ en: {}, fr: {}, nl: {} }),
    },
});

const activeLocale = ref(props.current_locale || props.available_locales[0]);
// const activeSeoLocaleTab = ref('en'); // No longer needed, using single activeLocale

// Initialize form.translations for blog content
const initialFormTranslations = {};
props.available_locales.forEach(locale => {
    const existing = props.blog.translations[locale];
    initialFormTranslations[locale] = {
        title: existing ? existing.title : '',
        content: existing ? existing.content : '',
    };
});

// Initialize seo_translations for SEO content
const initialSeoTranslations = {};
props.available_locales.forEach(locale => {
    const existing = props.seoTranslations[locale];
    initialSeoTranslations[locale] = {
        seo_title: existing?.seo_title || '',
        meta_description: existing?.meta_description || '',
        keywords: existing?.keywords || '',
    };
});

const form = useForm({
    _method: 'PUT',
    translations: initialFormTranslations,
    slug: props.blog.slug,
    image: null,
    is_published: props.blog.is_published,
    // SEO Fields
    // Main fields on SeoMeta model
    seo_title: props.seoMeta?.seo_title || (initialFormTranslations.en?.title || ''),
    meta_description: props.seoMeta?.meta_description || '',
    keywords: props.seoMeta?.keywords || '',
    canonical_url: props.seoMeta?.canonical_url || '',
    seo_image_url: props.seoMeta?.seo_image_url || '',
    // Translated SEO fields
    seo_translations: initialSeoTranslations,
});

const setActiveLocale = (locale) => {
    activeLocale.value = locale;
    // activeSeoLocaleTab.value = locale; // No longer needed
};

// No need for a watcher if activeLocale only controls the view
// The form.translations object holds all data for all locales simultaneously

onMounted(() => {
    // activeLocale is already set based on current_locale or first available
    // SEO fields are initialized in useForm. If props.seoMeta is null, seo_title defaults to blog's 'en' title.
    // If props.seoMeta exists but seo_title is empty, it also defaults to blog's 'en' title.
    // This ensures that if an SEO record exists but has an empty title, it still gets pre-filled from blog title.
    if (props.seoMeta && props.seoMeta.seo_title === '') {
        form.seo_title = initialFormTranslations.en?.title || '';
    }
    // If no seoMeta record at all, it's already defaulted by useForm.
});

// Watch the 'en' title to auto-fill seo_title if it's empty or was matching the old 'en' title
watch(() => form.translations.en?.title, (newEnTitle, oldEnTitle) => {
    if (newEnTitle) {
        // If seo_title is currently empty OR it was the same as the old blog title, update it.
        // This allows users to set a custom seo_title. If they then change the blog title,
        // their custom seo_title is preserved. If they clear seo_title, it will re-sync.
        if (!form.seo_title || form.seo_title === oldEnTitle) {
            form.seo_title = newEnTitle;
        }
    } else if (form.seo_title === oldEnTitle) { // If new 'en' title is cleared and seo_title was matching
        form.seo_title = ''; // Clear seo_title as well
    }
});


const submitForm = () => {
    // The form.translations object already holds all the data
    form.post(route('admin.blogs.update', props.blog.id), {
        onSuccess: () => {
            toast.success('Blog post updated successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
            form.image = null; // Clear file input on success
        },
        onError: (formErrors) => {
            for (const key in formErrors) {
                if (key.startsWith('translations.')) {
                    toast.error(formErrors[key], { position: 'top-right', timeout: 3000 });
                }
            }
            if (formErrors.slug) toast.error(formErrors.slug, { position: 'top-right', timeout: 3000 });
            if (formErrors.image) toast.error(formErrors.image, { position: 'top-right', timeout: 3000 });
        }
    });
};

</script>
