<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

defineProps({
  contactPage: {
    type: Object,
    default: () => ({
      hero_title: 'Contact Vrooem',
      hero_description: 'Get in touch with us',
      hero_image_url: '',
      contact_points: [], // Expects array of { icon: string, title: string }
      intro_text: '',
      phone_number: '',
      email: '',
      address: ''
    })
  }
});

const form = useForm({
  name: '',
  email: '',
  message: '',
  admin_email: import.meta.env.VITE_ADMIN_EMAIL,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || null);

const submitForm = () => {
  form.post(route('contact.submit'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    }
  });
};

watch(() => form.processing, (newVal) => {
  if (newVal) {
    document.body.classList.add('loading');
  } else {
    document.body.classList.remove('loading');
  }
});

// SVG Icons
const PhoneIcon = `
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
  </svg>`;
const EmailIcon = `
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
  </svg>`;
const LocationIcon = `
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
  </svg>`;

</script>

<template>
  <AuthenticatedHeaderLayout />

  <Head :title="_t('contactus','page_title')" />

  <div class="contact-us-page text-gray-800">
    <!-- Hero Section -->
    <section class="hero-section relative bg-customPrimaryColor text-white">
      <div v-if="contactPage.hero_image_url" class="absolute inset-0 opacity-20 bg-cover bg-center"
        :style="{ backgroundImage: `url('${contactPage.hero_image_url}')` }"></div>
      <div class="absolute inset-0 bg-customPrimaryColor opacity-80" v-if="!contactPage.hero_image_url"></div>


      <div class="container mx-auto px-6 py-20 md:py-32 relative z-10 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
          {{ contactPage.hero_title }}
        </h1>
        <p class="text-lg md:text-xl max-w-3xl mx-auto">
          {{ contactPage.hero_description }}
        </p>
      </div>
    </section>

    <!-- Main Content Section -->
    <section class="main-content py-12 md:py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="grid md:grid-cols-2 gap-12 items-start">
          
          <!-- Left Column: Intro & Contact Details -->
          <div class="space-y-10">
            <div v-if="contactPage.intro_text">
              <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-customPrimaryColor">{{ _t('contactus','about_vrooem') }}</h2>
              <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                {{ contactPage.intro_text }}
              </p>
            </div>

            <div>
              <h2 class="text-2xl md:text-3xl font-semibold mb-6 text-customPrimaryColor">{{ _t('contactus','get_in_touch') }}</h2>
              <div class="space-y-6">
                <div v-if="contactPage.phone_number" class="flex items-center group">
                  <span class="flex-shrink-0 w-10 h-10 bg-customLightPrimaryColor rounded-full flex items-center justify-center mr-4 group-hover:bg-customPrimaryColor">
                    <span v-html="PhoneIcon" class="text-customPrimaryColor group-hover:text-white"></span>
                  </span>
                  <a :href="`tel:${contactPage.phone_number}`" class="text-gray-700 hover:text-customPrimaryColor transition-colors">{{ contactPage.phone_number }}</a>
                </div>

                <div v-if="contactPage.email" class="flex items-center group">
                  <span class="flex-shrink-0 w-10 h-10 bg-customLightPrimaryColor rounded-full flex items-center justify-center mr-4 group-hover:bg-customPrimaryColor">
                    <span v-html="EmailIcon" class="text-customPrimaryColor group-hover:text-white"></span>
                  </span>
                  <a :href="`mailto:${contactPage.email}`" class="text-gray-700 hover:text-customPrimaryColor transition-colors">{{ contactPage.email }}</a>
                </div>

                <div v-if="contactPage.address" class="flex items-center group">
                  <span class="flex-shrink-0 w-10 h-10 bg-customLightPrimaryColor rounded-full flex items-center justify-center mr-4 group-hover:bg-customPrimaryColor mt-1">
                    <span v-html="LocationIcon" class="text-customPrimaryColor group-hover:text-white"></span>
                  </span>
                  <span class="text-gray-700">{{ contactPage.address }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Contact Form -->
          <div class="bg-customLightPrimaryColor p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl md:text-3xl font-semibold text-customPrimaryColor text-center mb-8">{{ _t('contactus','send_message') }}</h2>

            <div v-if="flashSuccess" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
              <p>{{ flashSuccess }}</p>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
              <ul class="list-disc list-inside">
                <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
              </ul>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ _t('contactus','name') }}</label>
                <input type="text" v-model="form.name" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-customPrimaryColor focus:border-customPrimaryColor sm:text-sm" :placeholder="_t('contactus','your_name')" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ _t('contactus','email') }}</label>
                <input type="email" v-model="form.email" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-customPrimaryColor focus:border-customPrimaryColor sm:text-sm" :placeholder="_t('contactus','your_email')" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ _t('contactus','message') }}</label>
                <textarea v-model="form.message" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-customPrimaryColor focus:border-customPrimaryColor sm:text-sm" rows="4" :placeholder="_t('contactus','your_message')" required></textarea>
              </div>
              <div class="text-center pt-2">
                <button type="submit" :disabled="form.processing" class="w-full bg-customPrimaryColor hover:bg-opacity-80 text-white font-semibold py-3 px-4 rounded-md shadow-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                  {{ form.processing ? _t('contactus','sending_message_button') : _t('contactus','send_message_button') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Points Section -->
    <section v-if="contactPage.contact_points && contactPage.contact_points.length" class="contact-points py-12 md:py-20 bg-customLightPrimaryColor">
      <div class="container mx-auto px-6">
        <h2 class="text-3xl md:text-4xl font-semibold text-customPrimaryColor text-center mb-12">{{ _t('contactus','why_choose_vrooem') }}</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="(point, index) in contactPage.contact_points" :key="index" class="text-center p-6 bg-white shadow-xl rounded-lg transform hover:scale-105 transition-transform duration-300">
            <div class="mb-5 flex justify-center items-center w-40 h-40 max-[768px]:h-28 max-[768px]:w-28 mx-auto bg-customPrimaryColor rounded-full">
              <!-- Assuming point.icon is an SVG string or an image URL -->
              <img v-if="point.icon && (point.icon.startsWith('http') || point.icon.startsWith('/'))" :src="point.icon" :alt="point.title" class="h-32 w-32 max-[768px]:h-20 max-[768px]:w-20 rounded-[99px] text-white" />
              <div v-else-if="point.icon" v-html="point.icon" class="h-30 w-30 text-white"></div>
              <!-- Fallback if no icon -->
              <span v-else class="text-3xl text-white">💡</span>
            </div>
            <h3 class="text-xl font-semibold text-customPrimaryColor mb-2">{{ point.title }}</h3>
            <!-- Add point.description here if it exists in your data -->
            <!-- <p class="text-gray-600 text-sm">{{ point.description }}</p> -->
          </div>
        </div>
      </div>
    </section>

  </div>

  <Footer />

  <!-- Fullscreen Loader Overlay -->
  <div v-if="form.processing" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white bg-opacity-80">
    <div class="flex flex-col items-center space-y-4 p-8 bg-white rounded-lg shadow-2xl">
      <svg class="animate-spin h-12 w-12 text-customPrimaryColor" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V1a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V4a1 1 0 00-1-1H4z" />
      </svg>
      <p class="text-lg text-customPrimaryColor font-semibold">{{ _t('contactus','sending_message_button') }}</p>
    </div>
  </div>
</template>

<style scoped>
.hero-section {
  min-height: 50vh; /* Adjust as needed */
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden; /* Ensures pseudo-elements don't overflow */
}

/* Removed .hero-section .overlay as opacity is handled directly on the background div */

body.loading {
  overflow: hidden;
}

/* Additional styling for a potentially more polished look */
.contact-us-page {
  font-family: 'Inter', sans-serif; /* Example: Using a common sans-serif font */
}

/* Consider adding focus-visible states for better accessibility */
input:focus-visible, textarea:focus-visible, button:focus-visible {
  outline: 2px solid var(--custom-primary-color, #153b4f); /* Use your primary color variable */
  outline-offset: 2px;
}
</style>
