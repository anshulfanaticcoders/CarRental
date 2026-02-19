<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import CountrySelector from '@/Components/CountrySelector.vue'
import loaderVariant from '../../../../assets/loader-variant.svg'

const props = defineProps({
  seoMeta: {
    type: Object,
    default: null,
  },
  translations: {
    type: Object,
    default: () => ({}),
  },
  routeTargets: {
    type: Array,
    default: () => [],
  },
  available_locales: {
    type: Array,
    default: () => ['en'],
  },
  targetKey: {
    type: String,
    default: null,
  },
  country: {
    type: String,
    default: null,
  },
})

const locales = computed(() => props.available_locales?.length ? props.available_locales : ['en'])
const isEditing = computed(() => !!props.seoMeta)
const formTitle = computed(() => (isEditing.value ? 'Edit SEO Target' : 'Create SEO Target'))
const currentLocaleTab = ref(locales.value[0])

const initialTranslations = {}
locales.value.forEach((locale) => {
  initialTranslations[locale] = {
    seo_title: props.translations?.[locale]?.seo_title || '',
    meta_description: props.translations?.[locale]?.meta_description || '',
    keywords: props.translations?.[locale]?.keywords || '',
  }
})

const initialTargetKey = props.targetKey || props.routeTargets?.[0]?.key || 'home'
const initialCountry = (props.country || 'us').toLowerCase()
const countrySelection = ref(initialTargetKey === 'blog_listing' ? [initialCountry] : [])

watch(countrySelection, (newVal) => {
  if (!newVal) return
  if (newVal.length <= 1) return
  countrySelection.value = [newVal[newVal.length - 1]]
})

const selectedCountry = computed(() => (countrySelection.value?.[0] || 'us').toLowerCase())

const form = useForm({
  _method: isEditing.value ? 'PUT' : 'POST',
  target_key: initialTargetKey,
  country: initialCountry,
  seo_title: props.seoMeta?.seo_title || '',
  meta_description: props.seoMeta?.meta_description || '',
  keywords: props.seoMeta?.keywords || '',
  canonical_url: props.seoMeta?.canonical_url || '',
  seo_image_url: props.seoMeta?.seo_image_url || '',
  translations: JSON.parse(JSON.stringify(initialTranslations)),
})

watch(
  () => form.target_key,
  (key) => {
    if (key !== 'blog_listing') {
      countrySelection.value = []
    } else if (countrySelection.value.length === 0) {
      countrySelection.value = ['us']
    }
  }
)

watch(
  () => selectedCountry.value,
  (country) => {
    form.country = country
  }
)

watch(
  () => form.errors,
  (newErrors) => {
    for (const locale of locales.value) {
      if (
        newErrors[`translations.${locale}.seo_title`] ||
        newErrors[`translations.${locale}.meta_description`] ||
        newErrors[`translations.${locale}.keywords`]
      ) {
        currentLocaleTab.value = locale
        break
      }
    }
  }
)

const submitForm = () => {
  const url = isEditing.value
    ? route('admin.seo-meta.update', props.seoMeta.id)
    : route('admin.seo-meta.store')

  form.post(url, { preserveScroll: true })
}

const isBlogListing = computed(() => form.target_key === 'blog_listing')

const charCount = (value) => `${value || ''}`.length
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
              <div
                class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center"
              >
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  ></path>
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ formTitle }}</h1>
                <p class="text-gray-600">Manage SEO for public targets (routes)</p>
              </div>
            </div>

            <form @submit.prevent>
              <div class="mb-10">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Target</h3>

                <div class="grid grid-cols-1 gap-6">
                  <div>
                    <label for="target_key" class="block text-sm font-medium text-gray-700 mb-2">
                      SEO Target <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="target_key"
                      v-model="form.target_key"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                    >
                      <option v-for="t in routeTargets" :key="t.key" :value="t.key">{{ t.label }}</option>
                    </select>
                    <p v-if="form.errors.target_key" class="mt-2 text-sm text-red-600">{{ form.errors.target_key }}</p>
                  </div>

                  <div v-if="isBlogListing">
                    <CountrySelector v-model="countrySelection" />
                    <p v-if="form.errors.country" class="mt-2 text-sm text-red-600">{{ form.errors.country }}</p>
                    <p class="mt-2 text-sm text-gray-500">
                      This SEO target applies to the blog listing route for the selected country.
                    </p>
                  </div>
                </div>
              </div>

              <div class="mb-10">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Default SEO</h3>
                <div class="grid grid-cols-1 gap-6">
                  <div>
                    <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-2">
                      Default SEO Title <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="seo_title"
                      v-model="form.seo_title"
                      type="text"
                      maxlength="60"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                      placeholder="Title used when translation is missing"
                    />
                    <p v-if="form.errors.seo_title" class="mt-2 text-sm text-red-600">{{ form.errors.seo_title }}</p>
                    <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                      <span>{{ charCount(form.seo_title) }}/60</span>
                    </div>
                  </div>

                  <div>
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Default Meta Description</label>
                    <textarea
                      id="meta_description"
                      v-model="form.meta_description"
                      rows="3"
                      maxlength="160"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                    ></textarea>
                    <p v-if="form.errors.meta_description" class="mt-2 text-sm text-red-600">{{ form.errors.meta_description }}</p>
                    <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                      <span>{{ charCount(form.meta_description) }}/160</span>
                    </div>
                  </div>

                  <div>
                    <label for="keywords" class="block text-sm font-medium text-gray-700 mb-2">Default Keywords</label>
                    <input
                      id="keywords"
                      v-model="form.keywords"
                      type="text"
                      maxlength="255"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                      placeholder="keyword1, keyword2..."
                    />
                    <p v-if="form.errors.keywords" class="mt-2 text-sm text-red-600">{{ form.errors.keywords }}</p>
                    <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                      <span>{{ charCount(form.keywords) }}/255</span>
                    </div>
                  </div>

                  <div>
                    <label for="canonical_url" class="block text-sm font-medium text-gray-700 mb-2">Canonical URL Override</label>
                    <input
                      id="canonical_url"
                      v-model="form.canonical_url"
                      type="url"
                      maxlength="255"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                      placeholder="https://example.com/preferred-url"
                    />
                    <p v-if="form.errors.canonical_url" class="mt-2 text-sm text-red-600">{{ form.errors.canonical_url }}</p>
                    <p v-else class="mt-2 text-sm text-gray-500">Use only when you intentionally want a different canonical.</p>
                    <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                      <span>{{ charCount(form.canonical_url) }}/255</span>
                    </div>
                  </div>

                  <div>
                    <label for="seo_image_url" class="block text-sm font-medium text-gray-700 mb-2">SEO Image URL</label>
                    <input
                      id="seo_image_url"
                      v-model="form.seo_image_url"
                      type="url"
                      maxlength="255"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                      placeholder="https://example.com/image.jpg"
                    />
                    <p v-if="form.errors.seo_image_url" class="mt-2 text-sm text-red-600">{{ form.errors.seo_image_url }}</p>
                    <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                      <span>{{ charCount(form.seo_image_url) }}/255</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-10">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Localized SEO</h3>
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
                    <div v-if="currentLocaleTab === locale" class="space-y-6">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SEO Title [{{ locale.toUpperCase() }}]</label>
                        <input
                          v-model="form.translations[locale].seo_title"
                          type="text"
                          maxlength="60"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                        />
                        <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                          <span>{{ charCount(form.translations[locale].seo_title) }}/60</span>
                        </div>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description [{{ locale.toUpperCase() }}]</label>
                        <textarea
                          v-model="form.translations[locale].meta_description"
                          rows="3"
                          maxlength="160"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                        ></textarea>
                        <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                          <span>{{ charCount(form.translations[locale].meta_description) }}/160</span>
                        </div>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Keywords [{{ locale.toUpperCase() }}]</label>
                        <input
                          v-model="form.translations[locale].keywords"
                          type="text"
                          maxlength="255"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                          placeholder="keyword1, keyword2..."
                        />
                        <div class="mt-2 flex items-center justify-end text-sm text-gray-500">
                          <span>{{ charCount(form.translations[locale].keywords) }}/255</span>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <button
                  type="button"
                  @click="submitForm"
                  class="inline-flex items-center px-6 py-3 bg-customPrimaryColor text-white rounded-lg font-semibold hover:bg-customPrimaryColor/90 transition"
                >
                  {{ isEditing ? 'Update' : 'Create' }}
                </button>

                <a
                  :href="route('admin.seo-meta.index')"
                  class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition"
                >
                  Cancel
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
