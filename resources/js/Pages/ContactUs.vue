<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  contactPage: {
    type: Object,
    default: () => ({
      hero_title: 'Contact Vrooem',
      hero_description: 'Get in touch with us',
      hero_image_url: '',
      contact_points: [],
      intro_text: '',
      phone_number: '',
      email: '',
      address: ''
    })
  }
});
</script>

<template>
    <AuthenticatedHeaderLayout/>
 
    <Head title="Contact Us - Vrooem" />

    <div class="contact-us-container">
      <!-- Hero Section -->
      <section class="hero-section relative">
        <div 
          v-if="contactPage.hero_image_url" 
          class="absolute inset-0 bg-cover bg-center opacity-50"
          :style="{ backgroundImage: `url('${contactPage.hero_image_url}')` }"
        ></div>
        
        <div class="container mx-auto px-4 py-16 relative z-10 text-center">
          <h1 class="text-4xl font-bold text-gray-900 mb-4">
            {{ contactPage.hero_title }}
          </h1>
          <p class="text-xl text-gray-700 max-w-2xl mx-auto">
            {{ contactPage.hero_description }}
          </p>
        </div>
      </section>

      <!-- Contact Information Section -->
      <section class="contact-info py-16 bg-gray-100">
        <div class="container mx-auto px-4">
          <div class="grid md:grid-cols-2 gap-8">
            <!-- Company Intro -->
            <div>
              <h2 class="text-3xl font-semibold mb-6">About Vrooem</h2>
              <p class="text-gray-700 leading-relaxed">
                {{ contactPage.intro_text }}
              </p>
            </div>

            <!-- Contact Details -->
            <div>
              <h2 class="text-3xl font-semibold mb-6">Get in Touch</h2>
              <div class="space-y-4">
                <div v-if="contactPage.phone_number" class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h3m-3-9a9 9 0 01-9 9m9-9a9 9 0 00-9 9m0 0c0 1.657 1.5 3 3 3h13a3 3 0 003-3m-6-9v6" />
                  </svg>
                  <span>{{ contactPage.phone_number }}</span>
                </div>

                <div v-if="contactPage.email" class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span>{{ contactPage.email }}</span>
                </div>

                <div v-if="contactPage.address" class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>{{ contactPage.address }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Contact Points Section -->
      <section v-if="contactPage.contact_points && contactPage.contact_points.length" class="contact-points py-16">
        <div class="container mx-auto px-4">
          <h2 class="text-3xl font-semibold text-center mb-12">Why Choose Vrooem</h2>
          <div class="grid md:grid-cols-3 gap-8">
            <div 
              v-for="(point, index) in contactPage.contact_points" 
              :key="index" 
              class="text-center p-6 bg-white shadow-md rounded-lg"
            >
              <div class="mb-4 flex justify-center">
                <img 
                  v-if="point.icon" 
                  :src="point.icon" 
                  :alt="point.title" 
                  class="h-16 w-16"
                />
                <div v-else class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-3xl">
                    {{ 
                      index === 0 ? '📞' : 
                      index === 1 ? '📍' : 
                      '💬' 
                    }}
                  </span>
                </div>
              </div>
              <h3 class="text-xl font-semibold mb-2">{{ point.title }}</h3>
            </div>
          </div>
        </div>
      </section>

      <!-- Contact Form (Optional) -->
      <section class="contact-form py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-2xl">
          <h2 class="text-3xl font-semibold text-center mb-8">Send us a Message</h2>
          <form class="bg-white shadow-md rounded-lg p-8">
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
              <input 
                type="text" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                placeholder="Your Name"
              />
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
              <input 
                type="email" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                placeholder="Your Email"
              />
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">Message</label>
              <textarea 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                rows="4"
                placeholder="Your Message"
              ></textarea>
            </div>
            <div class="text-center">
              <button 
                type="submit" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Send Message
              </button>
            </div>
          </form>
        </div>
      </section>
    </div>

    <Footer/>
</template>

<style scoped>
.hero-section {
  min-height: 50vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>