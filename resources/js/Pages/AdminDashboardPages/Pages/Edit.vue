<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Editor from '@tinymce/tinymce-vue';
import { ref, computed, watch } from 'vue';
import { useToast } from 'vue-toastification';
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
import { Toaster } from '@/Components/ui/toast';
import loaderVariant from '../../../../assets/loader-variant.svg';

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
    const existing = props.page.translations[locale] || {};
    initialPageTranslations[locale] = {
        title: existing.title || '',
        slug: existing.slug || '',
        content: existing.content || '',
    };
});

// Initialize seo_translations for SEO content
const initialSeoTranslations = {};
locales.forEach(locale => {
    const existing = props.seoTranslations[locale] || {};
    initialSeoTranslations[locale] = {
        seo_title: existing.seo_title || '',
        meta_description: existing.meta_description || '',
        keywords: existing.keywords || '',
    };
});

const form = useForm({
    _method: 'PUT',
    translations: JSON.parse(JSON.stringify(initialPageTranslations)),
    seo_title: props.seoMeta?.seo_title || (initialPageTranslations.en?.title || ''),
    canonical_url: props.seoMeta?.canonical_url || '',
    seo_image_url: props.seoMeta?.seo_image_url || '',
    seo_translations: JSON.parse(JSON.stringify(initialSeoTranslations)),
});

const setActiveLocale = (locale, event) => {
    if (event) event.preventDefault();
    activeLocale.value = locale;
};

// Slugify function
const slugify = (text) => {
    return text
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-');
};

// Watch for changes in the active locale's title and update the slug
watch(() => form.translations[activeLocale.value]?.title, (newTitle) => {
    if (newTitle) {
        form.translations[activeLocale.value].slug = slugify(newTitle);
    } else {
        form.translations[activeLocale.value].slug = '';
    }
}, { deep: true });

const submit = () => {
    form.post(route('admin.pages.update', props.page.id), {
        preserveScroll: true,
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
        },
    });
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
                <h1 class="text-2xl font-bold text-gray-900">Edit Page</h1>
                <p class="text-gray-600">Update page content and SEO settings</p>
              </div>
            </div>

            <form @submit.prevent>
              <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Page Content</h3>
                <div class="flex border-b border-gray-200 mb-6">
                  <button
                    v-for="locale in locales"
                    :key="locale"
                    type="button"
                    @click="setActiveLocale(locale, $event)"
                    :class="[
                      'py-2 px-4 font-semibold text-sm',
                      activeLocale === locale
                        ? 'border-b-2 border-customPrimaryColor text-customPrimaryColor'
                        : 'text-gray-500 hover:text-gray-700 hover:border-gray-300',
                      'focus:outline-none focus:ring-2 focus:ring-customPrimaryColor'
                    ]"
                  >
                    {{ locale.toUpperCase() }}
                  </button>
                </div>

                <template v-for="locale in locales" :key="`content-${locale}`">
                  <div v-if="activeLocale === locale" class="space-y-6">
                    <!-- Title Field -->
                    <div>
                      <label :for="`title-${locale}`" class="block text-sm font-medium text-gray-700 mb-2">
                        Title ({{ locale.toUpperCase() }}) <span class="text-red-500">*</span>
                      </label>
                      <Input
                        :id="`title-${locale}`"
                        v-model="form.translations[locale].title"
                        type="text"
                        required
                        aria-required="true"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      />
                      <p v-if="form.errors[`translations.${locale}.title`]" class="mt-2 text-sm text-red-600">
                        {{ form.errors[`translations.${locale}.title`] }}
                      </p>
                    </div>

                    <!-- Slug Field -->
                    <div>
                      <label :for="`slug-${locale}`" class="block text-sm font-medium text-gray-700 mb-2">
                        Slug ({{ locale.toUpperCase() }}) <span class="text-red-500">*</span>
                      </label>
                      <Input
                        :id="`slug-${locale}`"
                        v-model="form.translations[locale].slug"
                        type="text"
                        required
                        aria-required="true"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      />
                      <p v-if="form.errors[`translations.${locale}.slug`]" class="mt-2 text-sm text-red-600">
                        {{ form.errors[`translations.${locale}.slug`] }}
                      </p>
                    </div>

                    <!-- Content Field -->
                    <div>
                      <label :for="`content-${locale}`" class="block text-sm font-medium text-gray-700 mb-2">
                        Content ({{ locale.toUpperCase() }})
                      </label>
                      <editor
                        v-model="form.translations[locale].content"
                        :id="`content-${locale}`"
                        api-key="l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1"
                        :init="{
                          height: 500,
                          menubar: false,
                          skin: 'oxide',
                          content_css: 'default',
                          toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
                          plugins: 'lists link image',
                        }"
                        class="border border-gray-300 rounded-lg"
                      />
                      <p v-if="form.errors[`translations.${locale}.content`]" class="mt-2 text-sm text-red-600">
                        {{ form.errors[`translations.${locale}.content`] }}
                      </p>
                    </div>
                  </div>
                </template>
              </div>

              <!-- SEO Meta Fields -->
              <div class="mb-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">SEO Meta Information</h3>
                <p class="text-sm text-gray-600 mb-4">
                  Page Slug (<code>{{ page.slug }}</code>) is used as the 'URL Slug' for SEO meta (e.g., 'page/{{ page.slug }}').
                </p>

                <!-- Non-translatable SEO fields -->
                <div class="grid grid-cols-1 gap-6 mb-6">
                  <div>
                    <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-2">
                      Default SEO Title (Required Fallback)
                    </label>
                    <Input
                      id="seo_title"
                      v-model="form.seo_title"
                      type="text"
                      maxlength="60"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                    />
                    <p v-if="form.errors.seo_title" class="mt-2 text-sm text-red-600">{{ form.errors.seo_title }}</p>
                  </div>
                  <div>
                    <label for="canonical_url" class="block text-sm font-medium text-gray-700 mb-2">
                      Canonical URL
                    </label>
                    <Input
                      id="canonical_url"
                      v-model="form.canonical_url"
                      type="url"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="https://yourdomain.com/preferred-url"
                    />
                    <p v-if="form.errors.canonical_url" class="mt-2 text-sm text-red-600">{{ form.errors.canonical_url }}</p>
                  </div>
                  <div>
                    <label for="seo_image_url" class="block text-sm font-medium text-gray-700 mb-2">
                      SEO Image URL (Open Graph Image)
                    </label>
                    <Input
                      id="seo_image_url"
                      v-model="form.seo_image_url"
                      type="url"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="https://yourdomain.com/path/to/image.jpg"
                    />
                    <p v-if="form.errors.seo_image_url" class="mt-2 text-sm text-red-600">{{ form.errors.seo_image_url }}</p>
                  </div>
                </div>

                <!-- Translatable SEO Fields -->
                <template v-for="locale in locales" :key="`seo-fields-${locale}`">
                  <div v-if="activeLocale === locale" class="grid grid-cols-1 gap-6 mt-4 pt-4 border-t">
                    <h4 class="text-md font-semibold text-gray-800">Localized SEO Fields ({{ locale.toUpperCase() }})</h4>
                    <div>
                      <label :for="`seo_title_${locale}`" class="block text-sm font-medium text-gray-700 mb-2">
                        SEO Title
                      </label>
                      <Input
                        :id="`seo_title_${locale}`"
                        v-model="form.seo_translations[locale].seo_title"
                        type="text"
                        maxlength="60"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      />
                      <p v-if="form.errors[`seo_translations.${locale}.seo_title`]" class="mt-2 text-sm text-red-600">
                        {{ form.errors[`seo_translations.${locale}.seo_title`] }}
                      </p>
                    </div>
                    <div>
                      <label :for="`meta_description_${locale}`" class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Description
                      </label>
                      <textarea
                        :id="`meta_description_${locale}`"
                        v-model="form.seo_translations[locale].meta_description"
                        maxlength="160"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      ></textarea>
                      <p v-if="form.errors[`seo_translations.${locale}.meta_description`]" class="mt-2 text-sm text-red-600">
                        {{ form.errors[`seo_translations.${locale}.meta_description`] }}
                      </p>
                    </div>
                    <div>
                      <label :for="`keywords_${locale}`" class="block text-sm font-medium text-gray-700 mb-2">
                        Keywords
                      </label>
                      <Input
                        :id="`keywords_${locale}`"
                        v-model="form.seo_translations[locale].keywords"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                        placeholder="keyword1, keyword2..."
                      />
                      <p v-if="form.errors[`seo_translations.${locale}.keywords`]" class="mt-2 text-sm text-red-600">
                        {{ form.errors[`seo_translations.${locale}.keywords`] }}
                      </p>
                    </div>
                  </div>
                </template>
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
                        {{ form.processing ? 'Updating...' : 'Update Page' }}
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Update Page?</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to update this page?
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="submit">Continue</AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>

                  <Link
                    :href="route('admin.pages.index')"
                    class="inline-flex items-center px-6 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    Back to Pages
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
                  <span class="text-sm font-medium">Updating page...</span>
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