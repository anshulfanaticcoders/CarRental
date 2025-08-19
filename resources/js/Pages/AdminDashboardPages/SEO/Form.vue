<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { defineProps, computed, ref, watch } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import { Toaster } from '@/Components/ui/toast';
import loaderVariant from '../../../../assets/loader-variant.svg';

const props = defineProps({
  seoMeta: {
    type: Object,
    default: null,
  },
  translations: {
    type: Object,
    default: () => ({ en: {}, fr: {}, nl: {} }),
  },
});

const isEditing = computed(() => !!props.seoMeta);
const formTitle = computed(() => isEditing.value ? 'Edit SEO Meta' : 'Create New SEO Meta');
const currentLocaleTab = ref('en');
const locales = ['en', 'fr', 'nl', 'es', 'ar'];

// Initialize translations with fallback to empty strings
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
  url_slug: props.seoMeta?.url_slug || '', // Added for main seo_metas table
  seo_title: props.seoMeta?.seo_title || '',
  meta_description: props.seoMeta?.meta_description || '',
  keywords: props.seoMeta?.keywords || '',
  canonical_url: props.seoMeta?.canonical_url || '',
  seo_image_url: props.seoMeta?.seo_image_url || '',
  translations: JSON.parse(JSON.stringify(initialTranslations)),
});

// Watch for errors and switch tab if needed
watch(() => form.errors, (newErrors) => {
  for (const locale of locales) {
    if (
      newErrors[`translations.${locale}.seo_title`] ||
      newErrors[`translations.${locale}.meta_description`] ||
      newErrors[`translations.${locale}.keywords`] ||
      newErrors[`translations.${locale}.url_slug`]
    ) {
      currentLocaleTab.value = locale; // Switch to tab with error
      break;
    }
  }
});

const submitForm = () => {
  if (isEditing.value) {
    form.post(route('admin.seo-meta.update', props.seoMeta.id), {
      preserveScroll: true,
    });
  } else {
    form.post(route('admin.seo-meta.store'), {
      preserveScroll: true,
    });
  }
};
</script>

<template>
  <AdminDashboardLayout>
    <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
      <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
    </div>

    <div class="py-12 px-4 sm:px-6 lg:px-8">
      <div class="mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-8">
            <div class="flex items-center space-x-4 mb-8">
              <div class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ formTitle }}</h1>
                <p class="text-gray-600">Manage SEO settings for your page</p>
              </div>
            </div>

            <form @submit.prevent>
              <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">SEO Settings</h3>
                <div class="grid grid-cols-1 gap-6">
                  <!-- SEO Title -->
                  <div>
                    <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-2">
                      Default SEO Title <span class="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      id="seo_title"
                      v-model="form.seo_title"
                      required
                      aria-required="true"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="Default SEO title for the page"
                    />
                    <p v-if="form.errors.seo_title" class="mt-2 text-sm text-red-600">{{ form.errors.seo_title }}</p>
                  </div>

                  <!-- Meta Description -->
                  <div>
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                      Default Meta Description
                    </label>
                    <textarea
                      id="meta_description"
                      v-model="form.meta_description"
                      rows="3"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="Default short and compelling description"
                    ></textarea>
                    <p v-if="form.errors.meta_description" class="mt-2 text-sm text-red-600">{{ form.errors.meta_description }}</p>
                  </div>

                  <!-- Keywords -->
                  <div>
                    <label for="keywords" class="block text-sm font-medium text-gray-700 mb-2">
                      Default Keywords (comma-separated)
                    </label>
                    <input
                      type="text"
                      id="keywords"
                      v-model="form.keywords"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="default_keyword1, default_keyword2"
                    />
                    <p v-if="form.errors.keywords" class="mt-2 text-sm text-red-600">{{ form.errors.keywords }}</p>
                  </div>

                  <!-- Canonical URL -->
                  <div>
                    <label for="canonical_url" class="block text-sm font-medium text-gray-700 mb-2">
                      Canonical URL
                    </label>
                    <input
                      type="url"
                      id="canonical_url"
                      v-model="form.canonical_url"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="https://yourdomain.com/preferred-url"
                    />
                    <p v-if="form.errors.canonical_url" class="mt-2 text-sm text-red-600">{{ form.errors.canonical_url }}</p>
                    <p v-else class="mt-2 text-sm text-gray-500">The preferred version of a URL if duplicate content exists.</p>
                  </div>

                  <!-- SEO Image URL -->
                  <div>
                    <label for="seo_image_url" class="block text-sm font-medium text-gray-700 mb-2">
                      SEO Image URL
                    </label>
                    <input
                      type="url"
                      id="seo_image_url"
                      v-model="form.seo_image_url"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="https://yourdomain.com/path/to/image.jpg"
                    />
                    <p v-if="form.errors.seo_image_url" class="mt-2 text-sm text-red-600">{{ form.errors.seo_image_url }}</p>
                    <p v-else class="mt-2 text-sm text-gray-500">Image for social sharing (Open Graph image).</p>
                  </div>

                  <!-- Main URL Slug -->
                  <div>
                    <label for="url_slug" class="block text-sm font-medium text-gray-700 mb-2">
                      URL Slug (Main)
                    </label>
                    <input
                      type="text"
                      id="url_slug"
                      v-model="form.url_slug"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="/your-page-slug"
                    />
                    <p v-if="form.errors.url_slug" class="mt-2 text-sm text-red-600">{{ form.errors.url_slug }}</p>
                    <p v-else class="mt-2 text-sm text-gray-500">The unique URL path for this SEO entry (e.g., /about-us, /blog/my-post).</p>
                  </div>
                </div>
              </div>

              <!-- Language Tabs Navigation -->
              <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Localized Content</h3>
                <nav class="flex space-x-4 border-b border-gray-200" aria-label="Tabs">
                  <button
                    v-for="locale in locales"
                    :key="locale"
                    type="button"
                    @click="currentLocaleTab = locale"
                    :class="[
                      currentLocaleTab === locale
                        ? 'border-customPrimaryColor text-customPrimaryColor'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                      'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-customPrimaryColor'
                    ]"
                  >
                    {{ locale.toUpperCase() }}
                  </button>
                </nav>
                <div class="mt-6">
                  <template v-for="locale in locales" :key="locale">
                    <div v-if="currentLocaleTab === locale">
                      <!-- Translated SEO Title -->
                      <div class="mb-4">
                        <label :for="'trans_seo_title_' + locale" class="block text-sm font-medium text-gray-700 mb-2">
                          SEO Title [{{ locale.toUpperCase() }}]
                        </label>
                        <input
                          type="text"
                          :id="'trans_seo_title_' + locale"
                          v-model="form.translations[locale].seo_title"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                          :placeholder="'SEO title in ' + locale"
                        />
                        <p v-if="form.errors[`translations.${locale}.seo_title`]" class="mt-2 text-sm text-red-600">
                          {{ form.errors[`translations.${locale}.seo_title`] }}
                        </p>
                      </div>

                      <!-- Translated Meta Description -->
                      <div class="mb-4">
                        <label :for="'trans_meta_description_' + locale" class="block text-sm font-medium text-gray-700 mb-2">
                          Meta Description [{{ locale.toUpperCase() }}]
                        </label>
                        <textarea
                          :id="'trans_meta_description_' + locale"
                          v-model="form.translations[locale].meta_description"
                          rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                          :placeholder="'Meta description in ' + locale"
                        ></textarea>
                        <p v-if="form.errors[`translations.${locale}.meta_description`]" class="mt-2 text-sm text-red-600">
                          {{ form.errors[`translations.${locale}.meta_description`] }}
                        </p>
                      </div>

                      <!-- Translated Keywords -->
                      <div class="mb-4">
                        <label :for="'trans_keywords_' + locale" class="block text-sm font-medium text-gray-700 mb-2">
                          Keywords [{{ locale.toUpperCase() }}]
                        </label>
                        <input
                          type="text"
                          :id="'trans_keywords_' + locale"
                          v-model="form.translations[locale].keywords"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                          :placeholder="'Keywords in ' + locale + ', comma-separated'"
                        />
                        <p v-if="form.errors[`translations.${locale}.keywords`]" class="mt-2 text-sm text-red-600">
                          {{ form.errors[`translations.${locale}.keywords`] }}
                        </p>
                      </div>

                      <!-- Translated URL Slug -->
                      <div>
                        <label :for="'trans_url_slug_' + locale" class="block text-sm font-medium text-gray-700 mb-2">
                          URL Slug [{{ locale.toUpperCase() }}]
                        </label>
                        <input
                          type="text"
                          :id="'trans_url_slug_' + locale"
                          v-model="form.translations[locale].url_slug"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                          :placeholder="'url-slug-in-' + locale"
                        />
                        <p v-if="form.errors[`translations.${locale}.url_slug`]" class="mt-2 text-sm text-red-600">
                          {{ form.errors[`translations.${locale}.url_slug`] }}
                        </p>
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                  <AlertDialog>
                    <AlertDialogTrigger as-child>
                      <Button
                        :disabled="form.processing"
                        class="inline-flex items-center px-6 py-3 bg-customPrimaryColor text-white rounded-lg hover:bg-customPrimaryColor/90 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium"
                      >
                        <svg
                          v-if="form.processing"
                          class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          ></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ form.processing ? (isEditing ? 'Updating...' : 'Creating...') : (isEditing ? 'Update SEO Meta' : 'Create SEO Meta') }}
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>{{ isEditing ? 'Update' : 'Create' }} SEO Meta?</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to {{ isEditing ? 'update' : 'create' }} these SEO settings?
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="submitForm">Continue</AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>

                  <Link
                    :href="route('admin.seo-meta.index')"
                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    Cancel
                  </Link>
                </div>

                <div v-if="form.processing" class="flex items-center text-customPrimaryColor">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  <span class="text-sm font-medium">{{ isEditing ? 'Updating SEO meta...' : 'Creating SEO meta...' }}</span>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <Toaster />
  </AdminDashboardLayout>
</template>

<style>
.text-customPrimaryColor {
  color: var(--custom-primary);
}

.bg-customPrimaryColor {
  background-color: var(--custom-primary);
}

.border-customPrimaryColor {
  border-color: var(--custom-primary);
}

.ring-customPrimaryColor {
  --tw-ring-color: var(--custom-primary);
}

.focus\:ring-customPrimaryColor:focus {
  --tw-ring-color: var(--custom-primary);
}

.focus\:border-customPrimaryColor:focus {
  --tw-border-opacity: 1;
  border-color: var(--custom-primary);
}

.hover\:bg-customPrimaryColor\/90:hover {
  background-color: color-mix(in srgb, var(--custom-primary) 90%, transparent);
}

.bg-customLightPrimaryColor {
  background-color: var(--custom-light-primary);
}

.hover\:bg-customLightPrimaryColor\/10:hover {
  background-color: color-mix(in srgb, var(--custom-light-primary) 10%, transparent);
}

.ring-customLightPrimaryColor\/20 {
  --tw-ring-color: color-mix(in srgb, var(--custom-light-primary) 20%, transparent);
}
</style>
