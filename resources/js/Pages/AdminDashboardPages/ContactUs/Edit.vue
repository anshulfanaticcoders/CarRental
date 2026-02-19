<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Editor from '@tinymce/tinymce-vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const toast = useToast();

const props = defineProps({
    contactPage: Object,
    translations: Object,
    available_locales: Array,
    currentLocale: String,
    seoMeta: Object,
    seoTranslations: Object,
    errors: Object,
});

const locales = props.available_locales || ['en', 'fr', 'nl'];
const activeLocale = ref(props.currentLocale || 'en');
const heroImagePreview = ref(props.contactPage.hero_image_url);

// Initialize translations for main content
const initialTranslations = {};
locales.forEach(locale => {
    const t = props.translations[locale] || {};
    initialTranslations[locale] = {
        hero_title: t.hero_title || '',
        hero_description: t.hero_description || '',
        intro_text: t.intro_text || '',
        contact_points: t.contact_points || [],
    };
});

// Initialize translations for SEO content
const initialSeoTranslations = {};
locales.forEach(locale => {
    const t = props.seoTranslations[locale] || {};
    initialSeoTranslations[locale] = {
        seo_title: t.seo_title || '',
        meta_description: t.meta_description || '',
        keywords: t.keywords || '',
    };
});

const form = useForm({
    _method: 'POST', // Will be spoofed to PUT by Inertia form helper
    // Non-translatable fields
    contact_point_icons: props.contactPage.contact_point_icons || [],
    hero_image: null,
    phone_number: props.contactPage.phone_number || '',
    email: props.contactPage.email || '',
    address: props.contactPage.address || '',
    
    // Translatable fields
    translations: initialTranslations,

    // SEO Fields
    seo_title: props.seoMeta?.seo_title || '',
    meta_description: props.seoMeta?.meta_description || '',
    keywords: props.seoMeta?.keywords || '',
    canonical_url: props.seoMeta?.canonical_url || '',
    seo_image_url: props.seoMeta?.seo_image_url || '',
    seo_translations: initialSeoTranslations,
});

const setActiveLocale = (locale) => {
    activeLocale.value = locale;
};

const handleHeroImageUpload = (event) => {
  const file = event.target.files[0];
  form.hero_image = file;
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => { heroImagePreview.value = e.target.result; };
    reader.readAsDataURL(file);
  } else {
    heroImagePreview.value = props.contactPage.hero_image_url;
  }
};

const addContactPoint = () => {
  form.translations[activeLocale.value].contact_points.push({ title: '' });
  form.contact_point_icons.push(''); // Add a corresponding icon slot
};

const removeContactPoint = (index) => {
  form.translations[activeLocale.value].contact_points.splice(index, 1);
  form.contact_point_icons.splice(index, 1); // Remove corresponding icon
};

const submit = () => {
  form.post(route('admin.contact-us.update'), {
    preserveScroll: true,
    onSuccess: () => {
        toast.success('Contact Us page updated successfully!');
    },
    onError: () => {
        toast.error('Failed to update Contact Us page.');
    }
  });
};

const charCount = (value) => `${value || ''}`.length
</script>

<template>
  <AdminDashboardLayout>
    <Head title="Edit Contact Us Page" />
    
    <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem] mt-5">
      <h1 class="text-2xl font-bold mb-6">Edit Contact Us Page</h1>
      
      <form @submit.prevent="submit" class="bg-white shadow-md rounded p-6">
        <!-- Locale Tabs -->
        <div class="flex border-b border-gray-200 mb-6">
            <button
                v-for="locale in locales"
                :key="locale"
                type="button"
                @click="setActiveLocale(locale)"
                :class="[
                    'py-2 px-4 font-semibold',
                    activeLocale === locale ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700'
                ]"
            >
                {{ locale.toUpperCase() }}
            </button>
        </div>

        <!-- Translatable Fields -->
        <template v-for="locale in locales" :key="`content-${locale}`">
            <div v-if="activeLocale === locale">
                <h2 class="text-xl font-semibold mb-3">Content ({{ locale.toUpperCase() }})</h2>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Hero Title</label>
                    <Input v-model="form.translations[locale].hero_title" type="text" class="w-full" />
                    <p v-if="form.errors[`translations.${locale}.hero_title`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${locale}.hero_title`] }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Hero Description</label>
                    <textarea v-model="form.translations[locale].hero_description" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md" rows="4"></textarea>
                    <p v-if="form.errors[`translations.${locale}.hero_description`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${locale}.hero_description`] }}</p>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Company Intro</label>
                    <textarea v-model="form.translations[locale].intro_text" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md" rows="4"></textarea>
                    <p v-if="form.errors[`translations.${locale}.intro_text`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${locale}.intro_text`] }}</p>
                </div>

                <!-- Contact Points Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">Contact Points</h3>
                    <div v-for="(point, index) in form.translations[locale].contact_points" :key="index" class="flex items-end gap-4 mb-4 p-4 border rounded-md bg-gray-50">
                        <div class="flex-grow">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                            <Input v-model="point.title" type="text" class="w-full" :placeholder="`Contact Point ${index + 1} Title`" />
                            <p v-if="form.errors[`translations.${locale}.contact_points.${index}.title`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${locale}.contact_points.${index}.title`] }}</p>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Icon (SVG string or URL)</label>
                            <Input v-model="form.contact_point_icons[index]" type="text" class="w-full" :placeholder="`Contact Point ${index + 1} Icon`" />
                            <p v-if="form.errors[`contact_point_icons.${index}`]" class="text-red-500 text-xs italic">{{ form.errors[`contact_point_icons.${index}`] }}</p>
                        </div>
                        <Button type="button" @click="removeContactPoint(index)" variant="destructive">Remove</Button>
                    </div>
                    <Button type="button" @click="addContactPoint" class="mt-4">Add Contact Point</Button>
                    <p v-if="form.errors[`translations.${locale}.contact_points`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${locale}.contact_points`] }}</p>
                </div>
            </div>
        </template>

        <hr class="my-6">

        <!-- Non-Translatable Fields -->
        <h2 class="text-xl font-semibold mb-3">General Settings (Not Translated)</h2>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Hero Image (Shared across languages)</label>
            <Input type="file" @input="form.hero_image = $event.target.files[0]" class="w-full" />
            <div v-if="heroImagePreview" class="mt-4"><img :src="heroImagePreview" alt="Hero Image Preview" class="w-full h-auto object-cover" style="max-height: 300px;" /></div>
            <p v-if="form.errors.hero_image" class="text-red-500 text-xs italic">{{ form.errors.hero_image }}</p>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
            <Input v-model="form.phone_number" type="text" class="w-full" />
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <Input v-model="form.email" type="email" class="w-full" />
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
            <textarea v-model="form.address" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md" rows="3"></textarea>
        </div>

        <hr class="my-6">

        <!-- SEO Meta Fields -->
        <h2 class="text-xl font-semibold mb-3">SEO Meta Information</h2>
        <p class="text-sm text-gray-600 mb-4">SEO target is fixed to the Contact Us route.</p>
        <div class="grid grid-cols-1 gap-6">
            <!-- Non-translatable SEO fields -->
            <div class="space-y-2">
                <label for="seo_title" class="text-sm font-medium">Default SEO Title (Fallback)</label>
                <Input id="seo_title" v-model="form.seo_title" type="text" class="w-full" maxlength="60" />
                <p v-if="form.errors.seo_title" class="text-red-500 text-sm">{{ form.errors.seo_title }}</p>
                <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                    <span>{{ charCount(form.seo_title) }}/60</span>
                </div>
            </div>
            <div class="space-y-2">
                <label for="meta_description" class="text-sm font-medium">Default Meta Description (Fallback)</label>
                <textarea id="meta_description" v-model="form.meta_description" maxlength="160" rows="3" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                <p v-if="form.errors.meta_description" class="text-red-500 text-sm">{{ form.errors.meta_description }}</p>
                <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                    <span>{{ charCount(form.meta_description) }}/160</span>
                </div>
            </div>
            <div class="space-y-2">
                <label for="keywords" class="text-sm font-medium">Default Keywords (Fallback)</label>
                <Input id="keywords" v-model="form.keywords" type="text" class="w-full" maxlength="255" placeholder="keyword1, keyword2..." />
                <p v-if="form.errors.keywords" class="text-red-500 text-sm">{{ form.errors.keywords }}</p>
                <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                    <span>{{ charCount(form.keywords) }}/255</span>
                </div>
            </div>
            <div class="space-y-2">
                <label for="canonical_url" class="text-sm font-medium">Canonical URL</label>
                <Input id="canonical_url" v-model="form.canonical_url" type="url" class="w-full" maxlength="255" placeholder="https://yourdomain.com/preferred-url" />
                <p v-if="form.errors.canonical_url" class="text-red-500 text-sm">{{ form.errors.canonical_url }}</p>
                <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                    <span>{{ charCount(form.canonical_url) }}/255</span>
                </div>
            </div>
            <div class="space-y-2">
                <label for="seo_image_url" class="text-sm font-medium">SEO Image URL</label>
                <Input id="seo_image_url" v-model="form.seo_image_url" type="url" class="w-full" maxlength="255" placeholder="https://yourdomain.com/path/to/image.jpg" />
                <p v-if="form.errors.seo_image_url" class="text-red-500 text-sm">{{ form.errors.seo_image_url }}</p>
                <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                    <span>{{ charCount(form.seo_image_url) }}/255</span>
                </div>
            </div>

            <!-- Translatable SEO Fields -->
            <template v-for="locale in locales" :key="`seo-fields-${locale}`">
                <div v-if="activeLocale === locale" class="grid grid-cols-1 gap-6 mt-4 pt-4 border-t">
                    <h4 class="text-md font-semibold text-gray-800">Localized SEO Fields ({{ locale.toUpperCase() }})</h4>
                    <div class="space-y-2">
                        <label :for="`seo_title_${locale}`" class="text-sm font-medium">SEO Title</label>
                        <Input :id="`seo_title_${locale}`" v-model="form.seo_translations[locale].seo_title" type="text" class="w-full" maxlength="60" />
                        <p v-if="form.errors[`seo_translations.${locale}.seo_title`]" class="text-red-500 text-sm">{{ form.errors[`seo_translations.${locale}.seo_title`] }}</p>
                        <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                            <span>{{ charCount(form.seo_translations[locale].seo_title) }}/60</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label :for="`meta_description_${locale}`" class="text-sm font-medium">Meta Description</label>
                        <textarea :id="`meta_description_${locale}`" v-model="form.seo_translations[locale].meta_description" maxlength="160" rows="3" class="w-full mt-1 p-2 border-2 shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                        <p v-if="form.errors[`seo_translations.${locale}.meta_description`]" class="text-red-500 text-sm">{{ form.errors[`seo_translations.${locale}.meta_description`] }}</p>
                        <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                            <span>{{ charCount(form.seo_translations[locale].meta_description) }}/160</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label :for="`keywords_${locale}`" class="text-sm font-medium">Keywords</label>
                        <Input :id="`keywords_${locale}`" v-model="form.seo_translations[locale].keywords" type="text" class="w-full" maxlength="255" placeholder="keyword1, keyword2..." />
                        <p v-if="form.errors[`seo_translations.${locale}.keywords`]" class="text-red-500 text-sm">{{ form.errors[`seo_translations.${locale}.keywords`] }}</p>
                        <div class="mt-1 flex items-center justify-end text-xs text-gray-500">
                            <span>{{ charCount(form.seo_translations[locale].keywords) }}/255</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <div class="mt-8 flex items-center justify-between">
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </Button>
          <Link :href="route('admin.contact-us.index')" class="text-gray-600 hover:text-gray-800">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AdminDashboardLayout>
</template>
