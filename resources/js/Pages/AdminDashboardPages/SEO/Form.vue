<template>
  <AdminDashboardLayout :title="formTitle">
    <div class="py-12">
      <div class="mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">{{ formTitle }}</h2>

            <form @submit.prevent="submitForm">
              <div class="grid grid-cols-1 gap-6">
                <!-- The main URL Slug is removed and is now part of the localized content -->

                <!-- SEO Title (Main/Default for SeoMeta model) -->
                <div>
                  <label for="seo_title" class="block text-sm font-medium text-gray-700">Default SEO Title
                    (Required)</label>
                  <input type="text" id="seo_title" v-model="form.seo_title"
                    class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    placeholder="Default SEO title for the page" />
                  <p v-if="form.errors.seo_title" class="mt-1 text-xs text-red-500">{{ form.errors.seo_title }}</p>
                </div>

                <!-- Meta Description (Main/Default for SeoMeta model) -->
                <div>
                  <label for="meta_description" class="block text-sm font-medium text-gray-700">Default Meta
                    Description</label>
                  <textarea id="meta_description" v-model="form.meta_description" rows="3"
                    class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    placeholder="Default short and compelling description"></textarea>
                  <p v-if="form.errors.meta_description" class="mt-1 text-xs text-red-500">{{
                    form.errors.meta_description }}</p>
                </div>

                <!-- Keywords (Main/Default for SeoMeta model) -->
                <div>
                  <label for="keywords" class="block text-sm font-medium text-gray-700">Default Keywords
                    (comma-separated)</label>
                  <input type="text" id="keywords" v-model="form.keywords"
                    class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    placeholder="default_keyword1, default_keyword2" />
                  <p v-if="form.errors.keywords" class="mt-1 text-xs text-red-500">{{ form.errors.keywords }}</p>
                </div>

                <!-- Canonical URL -->
                <div>
                  <label for="canonical_url" class="block text-sm font-medium text-gray-700">Canonical URL</label>
                  <input type="url" id="canonical_url" v-model="form.canonical_url"
                    class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    placeholder="https://yourdomain.com/preferred-url" />
                  <p v-if="form.errors.canonical_url" class="mt-1 text-xs text-red-500">{{ form.errors.canonical_url }}
                  </p>
                  <p v-else class="mt-1 text-xs text-gray-500">The preferred version of a URL if duplicate content
                    exists.</p>
                </div>

                <!-- SEO Image URL -->
                <div>
                  <label for="seo_image_url" class="block text-sm font-medium text-gray-700">SEO Image URL</label>
                  <input type="url" id="seo_image_url" v-model="form.seo_image_url"
                    class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    placeholder="https://yourdomain.com/path/to/image.jpg" />
                  <p v-if="form.errors.seo_image_url" class="mt-1 text-xs text-red-500">{{ form.errors.seo_image_url }}
                  </p>
                  <p v-else class="mt-1 text-xs text-gray-500">Image for social sharing (Open Graph image).</p>
                </div>

                <!-- Language Tabs Navigation -->
                <div class="mt-6 mb-4 border-b border-gray-200">
                  <h3 class="text-lg font-medium text-gray-900 mb-2">Localized Content</h3>
                  <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button v-for="locale in locales" :key="locale" type="button" @click="currentLocaleTab = locale"
                      :class="[
                        currentLocaleTab === locale
                          ? 'border-indigo-500 text-indigo-600'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none'
                      ]">
                      {{ locale.toUpperCase() }}
                    </button>
                  </nav>
                </div>

                <!-- Translatable Fields based on selected tab -->
                <template v-for="locale in locales" :key="locale">
                  <div v-if="currentLocaleTab === locale">
                    <!-- Translated SEO Title -->
                    <div class="mb-4">
                      <label :for="'trans_seo_title_' + locale" class="block text-sm font-medium text-gray-700">SEO
                        Title [{{ locale.toUpperCase() }}]</label>
                      <input type="text" :id="'trans_seo_title_' + locale" v-model="form.translations[locale].seo_title"
                        class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        :placeholder="'SEO title in ' + locale" />
                      <p v-if="form.errors[`translations.${locale}.seo_title`]" class="mt-1 text-xs text-red-500">{{
                        form.errors[`translations.${locale}.seo_title`] }}</p>
                    </div>

                    <!-- Translated Meta Description -->
                    <div class="mb-4">
                      <label :for="'trans_meta_description_' + locale"
                        class="block text-sm font-medium text-gray-700">Meta Description [{{ locale.toUpperCase()
                        }}]</label>
                      <textarea :id="'trans_meta_description_' + locale"
                        v-model="form.translations[locale].meta_description" rows="3"
                        class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        :placeholder="'Meta description in ' + locale"></textarea>
                      <p v-if="form.errors[`translations.${locale}.meta_description`]"
                        class="mt-1 text-xs text-red-500">{{ form.errors[`translations.${locale}.meta_description`] }}
                      </p>
                    </div>

                    <!-- Translated Keywords -->
                    <div class="mb-4">
                      <label :for="'trans_keywords_' + locale" class="block text-sm font-medium text-gray-700">Keywords
                        [{{ locale.toUpperCase() }}]</label>
                      <input type="text" :id="'trans_keywords_' + locale" v-model="form.translations[locale].keywords"
                        class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        :placeholder="'Keywords in ' + locale + ', comma-separated'" />
                      <p v-if="form.errors[`translations.${locale}.keywords`]" class="mt-1 text-xs text-red-500">{{
                        form.errors[`translations.${locale}.keywords`] }}</p>
                    </div>

                    <!-- Translated URL Slug -->
                    <div>
                      <label :for="'trans_url_slug_' + locale" class="block text-sm font-medium text-gray-700">URL Slug
                        [{{ locale.toUpperCase() }}]</label>
                      <input type="text" :id="'trans_url_slug_' + locale" v-model="form.translations[locale].url_slug"
                        class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        :placeholder="'url-slug-in-' + locale" />
                      <p v-if="form.errors[`translations.${locale}.url_slug`]" class="mt-1 text-xs text-red-500">{{
                        form.errors[`translations.${locale}.url_slug`] }}</p>
                    </div>
                  </div>
                </template>
              </div>

              <div class="mt-8 flex justify-end space-x-3">
                <Link :href="route('admin.seo-meta.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                Cancel
                </Link>
                <button type="submit" :disabled="form.processing"
                  class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                  {{ isEditing ? 'Update' : 'Create' }} SEO Meta
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { defineProps, computed, ref, watch } from 'vue'; // Added ref, watch

const props = defineProps({
  seoMeta: {
    type: Object,
    default: null, // Null for create, object for edit
  },
  translations: { // Received from controller for edit mode
    type: Object,
    default: () => ({ en: {}, fr: {}, nl: {} }), // Default for create mode
  },
  // errors: Object, // Passed by Inertia on validation failure
});

const isEditing = computed(() => !!props.seoMeta);
const formTitle = computed(() => isEditing.value ? 'Edit SEO Meta' : 'Create New SEO Meta');
const currentLocaleTab = ref('en'); // Default tab
const locales = ['en', 'fr', 'nl'];

// Initialize translations structure for the form
const initialTranslations = {};
locales.forEach(locale => {
  initialTranslations[locale] = {
    seo_title: props.translations[locale]?.seo_title || '',
    meta_description: props.translations[locale]?.meta_description || '',
    keywords: props.translations[locale]?.keywords || '',
    url_slug: props.translations[locale]?.url_slug || '',
  };
});

const form = useForm({
  _method: isEditing.value ? 'PUT' : 'POST',
  // The main seo_title on SeoMeta model is still required by backend validation
  seo_title: props.seoMeta?.seo_title || '',
  // Main meta_description and keywords on SeoMeta can be fallbacks or not used if all translations are filled
  meta_description: props.seoMeta?.meta_description || '',
  keywords: props.seoMeta?.keywords || '',
  canonical_url: props.seoMeta?.canonical_url || '',
  seo_image_url: props.seoMeta?.seo_image_url || '',
  translations: JSON.parse(JSON.stringify(initialTranslations)), // Deep clone to ensure distinct objects
});

// Watch for errors and potentially switch tab if an error occurs in a non-active tab
watch(() => form.errors, (newErrors) => {
  for (const locale of locales) {
    if (
      newErrors[`translations.${locale}.seo_title`] ||
      newErrors[`translations.${locale}.meta_description`] ||
      newErrors[`translations.${locale}.keywords`]
    ) {
      // console.log(`Error in ${locale} tab`);
      // if (currentLocaleTab.value !== locale) {
      //   currentLocaleTab.value = locale; // Optionally switch to tab with error
      // }
      break;
    }
  }
});

const submitForm = () => {
  if (isEditing.value) {
    form.post(route('admin.seo-meta.update', props.seoMeta.id), {
      preserveScroll: true,
      // onSuccess: () => form.reset(), // Or handle success message
    });
  } else {
    form.post(route('admin.seo-meta.store'), {
      preserveScroll: true,
      // onSuccess: () => form.reset(), // Or handle success message
    });
  }
};
</script>

<style scoped>
/* Add any page-specific styles here */
</style>
