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
            // Original contact_points structure (e.g., icons) might come from here
            // if we decide to keep non-translatable parts in the main model.
            // For now, we assume contact_points in the form will handle its full structure per locale.
            contact_points: [], 
        })
    },
    translations: { // Keyed by locale: { en: { hero_title: ... }, fr: { ... } }
        type: Object,
        default: () => ({})
    },
    currentLocale: { // Default locale from backend
        type: String,
        default: 'en'
    }
});

const locales = ['en', 'fr', 'nl'];
const activeLocale = ref(props.currentLocale);

const form = useForm({
    locale: props.currentLocale,
    // Translatable fields
    hero_title: '',
    hero_description: '',
    intro_text: '',
    contact_points: [], // Each item: { icon: '', title: '' } - title is translatable

    // Non-translatable fields
    hero_image: null, // For new image upload
    phone_number: props.contactPage.phone_number || '',
    email: props.contactPage.email || '',
    address: props.contactPage.address || '',
});

const heroImagePreview = ref(props.contactPage.hero_image_url);

// Computed property to get the translation data for the active locale
const currentTranslationData = computed(() => {
    return props.translations[activeLocale.value] || {};
});

// Computed property to get base contact points structure (e.g., icons)
// This assumes the main `contactPage.contact_points` holds the non-translatable parts like icons.
// If `contact_points` are fully managed per locale in translations, this might not be needed or adjusted.
const baseContactPoints = computed(() => {
    // If props.contactPage.contact_points contains the base structure (e.g. icons)
    // return props.contactPage.contact_points || [];

    // For now, let's assume the structure is driven by the translation or an empty array
    // This part needs careful consideration based on how contact_points icons are managed.
    // If icons are also part of the translation JSON, then this is simpler.
    // If icons are fixed and only titles are translated, we need a base structure.
    // Let's assume for now the `contact_points` in `currentTranslationData` is the source of truth for the active locale.
    return currentTranslationData.value.contact_points || [];
});


const setActiveLocale = (locale, event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    activeLocale.value = locale;
    form.locale = locale;

    const translation = props.translations[locale] || {};
    form.hero_title = translation.hero_title || '';
    form.hero_description = translation.hero_description || '';
    form.intro_text = translation.intro_text || '';
    
    // For contact_points, we need to merge translated titles with a base structure if icons are separate.
    // If contact_points in translation is self-contained (icon + title per locale):
    form.contact_points = JSON.parse(JSON.stringify(translation.contact_points || [])); 
    // Deep copy to avoid modifying props

    // Non-translatable fields remain as they are (already initialized)
    // form.phone_number = props.contactPage.phone_number || '';
    // form.email = props.contactPage.email || '';
    // form.address = props.contactPage.address || '';
};

onMounted(() => {
    activeLocale.value = props.currentLocale || 'en';
    form.locale = activeLocale.value;
    
    const initialTranslation = props.translations[activeLocale.value] || {};
    form.hero_title = initialTranslation.hero_title || '';
    form.hero_description = initialTranslation.hero_description || '';
    form.intro_text = initialTranslation.intro_text || '';
    form.contact_points = JSON.parse(JSON.stringify(initialTranslation.contact_points || []));

    // Initialize non-translatable fields from contactPage prop
    form.phone_number = props.contactPage.phone_number || '';
    form.email = props.contactPage.email || '';
    form.address = props.contactPage.address || '';
    heroImagePreview.value = props.contactPage.hero_image_url;
});

const handleHeroImageUpload = (event) => {
  const file = event.target.files[0];
  form.hero_image = file;
  
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      heroImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    heroImagePreview.value = props.contactPage.hero_image_url; // Revert to original if no file selected
  }
};

const addContactPoint = () => {
  form.contact_points.push({
    icon: '', // Icon might be non-translatable or also part of this structure
    title: ''  // This title will be for the activeLocale
  });
};

const removeContactPoint = (index) => {
  form.contact_points.splice(index, 1);
};

const submit = () => {
  // Ensure the ID is part of the route if it's an update
  // The controller handles ContactUsPage as a singleton, so ID in route might not be strictly necessary
  // but good practice if routes were resource-based.
  // For now, route('admin.contact-us.update') doesn't take an ID.
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
            v-model="form.hero_title" 
            type="text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          />
           <p v-if="form.errors.hero_title" class="text-red-500 text-xs italic">{{ form.errors.hero_title }}</p>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Description ({{ activeLocale.toUpperCase() }})</label>
          <textarea 
            v-model="form.hero_description" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            rows="4"
          ></textarea>
          <p v-if="form.errors.hero_description" class="text-red-500 text-xs italic">{{ form.errors.hero_description }}</p>
        </div>

        <!-- Contact Points - Translatable Titles -->
        <div class="mb-4">
          <h3 class="text-lg font-semibold mb-2">Contact Points ({{ activeLocale.toUpperCase() }})</h3>
          <div 
            v-for="(point, index) in form.contact_points" 
            :key="index" 
            class="flex items-center mb-2 p-2 border rounded"
          >
            <div class="flex-1 mr-2">
                <label class="block text-gray-700 text-sm font-bold mb-1">Icon URL/SVG (Point {{index + 1}})</label>
                <input 
                  v-model="point.icon" 
                  type="text" 
                  placeholder="Icon URL or SVG code" 
                  class="mr-2 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                />
            </div>
            <div class="flex-1 mr-2">
                <label class="block text-gray-700 text-sm font-bold mb-1">Title (Point {{index + 1}} - {{ activeLocale.toUpperCase() }})</label>
                <input 
                  v-model="point.title" 
                  type="text" 
                  placeholder="Point Title" 
                  class="mr-2 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                />
            </div>
            <button 
              type="button" 
              @click="removeContactPoint(index)"
              class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 self-end"
            >
              Remove
            </button>
          </div>
          <button 
            type="button" 
            @click="addContactPoint"
            class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
          >
            Add Contact Point
          </button>
           <p v-if="form.errors.contact_points" class="text-red-500 text-xs italic">{{ form.errors.contact_points }}</p>
        </div>
        
        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2">Company Intro ({{ activeLocale.toUpperCase() }})</label>
          <textarea 
            v-model="form.intro_text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            rows="4"
          ></textarea>
          <p v-if="form.errors.intro_text" class="text-red-500 text-xs italic">{{ form.errors.intro_text }}</p>
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
        </div>

        <!-- Contact Points -->
        <div class="mb-4">
          <h2 class="text-xl font-semibold mb-2">Contact Points</h2>
          <div 
            v-for="(point, index) in form.contact_points" 
            :key="index" 
            class="flex items-center mb-2"
          >
            <input 
              v-model="point.icon" 
              type="text" 
              placeholder="Icon URL" 
              class="mr-2 shadow appearance-none border rounded py-2 px-3 text-gray-700"
            />
            <input 
              v-model="point.title" 
              type="text" 
              placeholder="Point Title" 
              class="mr-2 shadow appearance-none border rounded py-2 px-3 text-gray-700"
            />
            <button 
              type="button" 
              @click="removeContactPoint(index)"
              class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600"
            >
              Remove
            </button>
          </div>
          <button 
            type="button" 
            @click="addContactPoint"
            class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
          >
            Add Contact Point
          </button>
        </div>

        <!-- Company Intro -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Company Intro</label>
          <textarea 
            v-model="form.intro_text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
            rows="4"
          ></textarea>
        </div>

        <!-- Contact Information -->
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

        <div class="flex items-center justify-between">
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
