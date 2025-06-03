<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

const toast = useToast();

const props = defineProps({
    contactPage: { // This is the main ContactUsPage model data (non-translatable mostly)
        type: Object,
        default: () => ({
            id: null, // Important for updates
            hero_image_url: '',
            phone_number: '',
            email: '',
            address: '',
            contact_point_icons: [], // New: Array of icon URLs/strings
        })
    },
    translations: { // Keyed by locale: { en: { hero_title: ..., contact_points: [{title: 'text1'},...] }, fr: { ... } }
        type: Object,
        default: () => ({})
    },
    currentLocale: { // Default locale from backend
        type: String,
        default: 'en'
    },
    seoMeta: { // Accept seoMeta prop
        type: Object,
        default: null,
    }
});

const locales = ['en', 'fr', 'nl'];
const activeLocale = ref(props.currentLocale);

// Store for all translations to allow editing across tabs before a single save
const allTranslationsData = ref({});

const form = useForm({
    // Non-translatable fields
    contact_point_icons: JSON.parse(JSON.stringify(props.contactPage.contact_point_icons || [])),
    hero_image: null,
    phone_number: props.contactPage.phone_number || '',
    email: props.contactPage.email || '',
    address: props.contactPage.address || '',
    
    // Translatable fields will be sent as a nested object
    translations: {}, // This will be populated in onMounted and sent on submit

    // SEO Fields
    seo_title: props.seoMeta?.seo_title || '',
    meta_description: props.seoMeta?.meta_description || '',
    keywords: props.seoMeta?.keywords || '',
    canonical_url: props.seoMeta?.canonical_url || '',
    seo_image_url: props.seoMeta?.seo_image_url || '',
});

// Temporary form state for the currently active locale's translatable fields
// This is what the input fields will bind to.
const currentLocaleForm = ref({
    hero_title: '',
    hero_description: '',
    intro_text: '',
    contact_points: [], // textual parts for the current locale
});

const heroImagePreview = ref(props.contactPage.hero_image_url);

// Function to load translatable data for a given locale into currentLocaleForm
const loadLocaleData = (locale) => {
    const translation = allTranslationsData.value[locale] || {};
    currentLocaleForm.value.hero_title = translation.hero_title || '';
    currentLocaleForm.value.hero_description = translation.hero_description || '';
    currentLocaleForm.value.intro_text = translation.intro_text || '';
    currentLocaleForm.value.contact_points = JSON.parse(JSON.stringify(translation.contact_points || []));
};

// Function to save data from currentLocaleForm back to allTranslationsData
const saveCurrentLocaleData = (locale) => {
    if (!allTranslationsData.value[locale]) {
        allTranslationsData.value[locale] = {};
    }
    allTranslationsData.value[locale].hero_title = currentLocaleForm.value.hero_title;
    allTranslationsData.value[locale].hero_description = currentLocaleForm.value.hero_description;
    allTranslationsData.value[locale].intro_text = currentLocaleForm.value.intro_text;
    allTranslationsData.value[locale].contact_points = JSON.parse(JSON.stringify(currentLocaleForm.value.contact_points));
};

const setActiveLocale = (newLocale, event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    // Save current locale's data from currentLocaleForm before switching
    saveCurrentLocaleData(activeLocale.value);
    
    activeLocale.value = newLocale;
    
    // Load new locale's data into currentLocaleForm
    loadLocaleData(newLocale);
};

onMounted(() => {
    // Initialize allTranslationsData from props
    locales.forEach(locale => {
        allTranslationsData.value[locale] = JSON.parse(JSON.stringify(props.translations[locale] || {
            hero_title: '',
            hero_description: '',
            intro_text: '',
            contact_points: [],
        }));
    });

    // Set initial active locale and load its data
    activeLocale.value = props.currentLocale || 'en';
    loadLocaleData(activeLocale.value);

    // Non-translatable fields are already initialized in useForm
    heroImagePreview.value = props.contactPage.hero_image_url;

    // Initialize SEO fields if seoMeta is passed (also done in useForm for initial load)
    if (props.seoMeta) {
        form.seo_title = props.seoMeta.seo_title || '';
        form.meta_description = props.seoMeta.meta_description || '';
        form.keywords = props.seoMeta.keywords || '';
        form.canonical_url = props.seoMeta.canonical_url || '';
        form.seo_image_url = props.seoMeta.seo_image_url || '';
    }
});

const handleHeroImageUpload = (event) => {
  const file = event.target.files[0];
  form.hero_image = file; // Stored in the main form object

  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      heroImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    heroImagePreview.value = props.contactPage.hero_image_url;
  }
};

const addContactPointText = () => {
  currentLocaleForm.value.contact_points.push({ title: '' });
};

const removeContactPointText = (index) => {
  currentLocaleForm.value.contact_points.splice(index, 1);
};

const addContactPointIcon = () => {
  form.contact_point_icons.push('');
};

const removeContactPointIcon = (index) => {
  form.contact_point_icons.splice(index, 1);
};

const submit = () => {
  // Save the currently active locale's data from currentLocaleForm to allTranslationsData
  saveCurrentLocaleData(activeLocale.value);
  
  // Assign the collected translations to the main form object for submission
  form.translations = JSON.parse(JSON.stringify(allTranslationsData.value));

  form.post(route('admin.contact-us.update'), {
    preserveScroll: true,
    onSuccess: () => {
        toast.success('Contact Us page updated successfully!', {
            position: 'top-right',
            timeout: 3000,
        });
    },
    onError: (errors) => {
        console.error('Error updating contact page:', errors);
        toast.error('Failed to update Contact Us page.', {
            position: 'top-right',
            timeout: 3000,
        });
    }
  });
};
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
                @click="setActiveLocale(locale, $event)"
                :class="[
                    'py-2 px-4 font-semibold',
                    activeLocale === locale ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700'
                ]"
            >
                {{ locale.toUpperCase() }}
            </button>
        </div>

        <!-- Translatable Fields -->
        <h2 class="text-xl font-semibold mb-3">Content ({{ activeLocale.toUpperCase() }})</h2>
        
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Title ({{ activeLocale.toUpperCase() }})</label>
          <input 
            v-model="currentLocaleForm.hero_title" 
            type="text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          />
           <p v-if="form.errors[`translations.${activeLocale}.hero_title`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${activeLocale}.hero_title`] }}</p>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Description ({{ activeLocale.toUpperCase() }})</label>
          <textarea 
            v-model="currentLocaleForm.hero_description" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            rows="4"
          ></textarea>
          <p v-if="form.errors[`translations.${activeLocale}.hero_description`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${activeLocale}.hero_description`] }}</p>
        </div>

        <!-- Contact Points - Translatable Titles -->
        <div class="mb-4">
          <h3 class="text-lg font-semibold mb-2">Contact Points - Texts ({{ activeLocale.toUpperCase() }})</h3>
          <div 
            v-for="(pointText, index) in currentLocaleForm.contact_points" 
            :key="`text-${index}`" 
            class="flex items-center mb-2 p-2 border rounded"
          >
            <div class="flex-1 mr-2">
                <label class="block text-gray-700 text-sm font-bold mb-1">Title (Point {{index + 1}} Text - {{ activeLocale.toUpperCase() }})</label>
                <input 
                  v-model="pointText.title" 
                  type="text" 
                  placeholder="Point Title Text" 
                  class="mr-2 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                />
                <!-- Add other text fields here if your contact_points structure is {title: '', description: ''} -->
            </div>
            <button 
              type="button" 
              @click="removeContactPointText(index)"
              class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 self-end"
            >
              Remove Text
            </button>
          </div>
          <button 
            type="button" 
            @click="addContactPointText"
            class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
          >
            Add Contact Point Text
          </button>
           <p v-if="form.errors[`translations.${activeLocale}.contact_points`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${activeLocale}.contact_points`] }}</p>
        </div>
        
        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2">Company Intro ({{ activeLocale.toUpperCase() }})</label>
          <textarea 
            v-model="currentLocaleForm.intro_text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            rows="4"
          ></textarea>
          <p v-if="form.errors[`translations.${activeLocale}.intro_text`]" class="text-red-500 text-xs italic">{{ form.errors[`translations.${activeLocale}.intro_text`] }}</p>
        </div>

        <hr class="my-6">

        <!-- Non-Translatable Fields -->
        <h2 class="text-xl font-semibold mb-3">General Settings (Not Translated)</h2>
        
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Image (Shared across languages)</label>
          <input 
            type="file" 
            @change="handleHeroImageUpload"
            accept="image/*"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
          
          <div v-if="heroImagePreview" class="mt-4">
            <img 
              :src="heroImagePreview" 
              alt="Hero Image Preview" 
              class="w-[50rem] h-[30rem] object-cover"
            />
          </div>
           <p v-if="form.errors.hero_image" class="text-red-500 text-xs italic">{{ form.errors.hero_image }}</p>
        </div>

        <!-- Contact Point Icons (Non-Translatable) -->
        <div class="mb-4">
          <h3 class="text-lg font-semibold mb-2">Contact Point Icons (Shared across languages)</h3>
          <p class="text-sm text-gray-600 mb-2">Manage the list of icons here. The translated texts above will be paired with these icons by order.</p>
          <div 
            v-for="(iconUrl, index) in form.contact_point_icons" 
            :key="`icon-${index}`" 
            class="flex items-center mb-2 p-2 border rounded"
          >
            <input 
              v-model="form.contact_point_icons[index]" 
              type="text" 
              :placeholder="`Icon URL/Class for Point ${index + 1}`"
              class="flex-1 mr-2 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            />
            <button 
              type="button" 
              @click="removeContactPointIcon(index)"
              class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 self-end"
            >
              Remove Icon
            </button>
          </div>
          <button 
            type="button" 
            @click="addContactPointIcon"
            class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            Add Icon URL
          </button>
          <p v-if="form.errors.contact_point_icons" class="text-red-500 text-xs italic">{{ form.errors.contact_point_icons }}</p>
        </div>
        
        <!-- Contact Information (Non-Translatable) -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
          <input 
            v-model="form.phone_number" 
            type="text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
          <textarea 
            v-model="form.address" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
            rows="3"
          ></textarea>
        </div>

        <hr class="my-6">

        <!-- SEO Meta Fields -->
        <h2 class="text-xl font-semibold mb-3">SEO Meta Information</h2>
        <p class="text-sm text-gray-600 mb-4">URL Slug for Contact Us page is fixed to: <code>contact-us</code></p>
        <div class="grid grid-cols-1 gap-6">
            <!-- SEO Title -->
            <div class="mb-4">
                <label for="seo_title" class="block text-gray-700 text-sm font-bold mb-2">SEO Title (Max 60 chars)</label>
                <input id="seo_title" v-model="form.seo_title" type="text" maxlength="60" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"/>
                <p v-if="form.errors.seo_title" class="text-red-500 text-xs italic">{{ form.errors.seo_title }}</p>
            </div>

            <!-- Meta Description -->
            <div class="mb-4">
                <label for="meta_description" class="block text-gray-700 text-sm font-bold mb-2">Meta Description (Max 160 chars)</label>
                <textarea id="meta_description" v-model="form.meta_description" maxlength="160" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                <p v-if="form.errors.meta_description" class="text-red-500 text-xs italic">{{ form.errors.meta_description }}</p>
            </div>

            <!-- Keywords -->
            <div class="mb-4">
                <label for="keywords" class="block text-gray-700 text-sm font-bold mb-2">Keywords (comma-separated)</label>
                <input id="keywords" v-model="form.keywords" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="keyword1, keyword2, keyword3..."/>
                <p v-if="form.errors.keywords" class="text-red-500 text-xs italic">{{ form.errors.keywords }}</p>
            </div>

            <!-- Canonical URL -->
            <div class="mb-4">
                <label for="canonical_url" class="block text-gray-700 text-sm font-bold mb-2">Canonical URL</label>
                <input id="canonical_url" v-model="form.canonical_url" type="url" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="https://yourdomain.com/preferred-url"/>
                <p v-if="form.errors.canonical_url" class="text-red-500 text-xs italic">{{ form.errors.canonical_url }}</p>
            </div>

            <!-- SEO Image URL -->
            <div class="mb-4">
                <label for="seo_image_url" class="block text-gray-700 text-sm font-bold mb-2">SEO Image URL (Open Graph Image)</label>
                <input id="seo_image_url" v-model="form.seo_image_url" type="url" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="https://yourdomain.com/path/to/image.jpg"/>
                <p v-if="form.errors.seo_image_url" class="text-red-500 text-xs italic">{{ form.errors.seo_image_url }}</p>
            </div>
        </div>
        
        <div class="mt-8 flex items-center justify-between">
          <button 
            type="submit" 
            :disabled="form.processing"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
          <a 
            :href="route('admin.contact-us.index')"
            class="text-gray-600 hover:text-gray-800"
          >
            Cancel
          </a>
        </div>
      </form>
    </div>
  </AdminDashboardLayout>
</template>
