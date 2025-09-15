<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 text-center">
    <div class="w-full max-w-md">
      <Vue3Lottie 
        :animation-data="error404" 
        :height="400" 
        :width="400" 
      />
    </div>
    <h1 class="text-4xl font-bold text-gray-800 mt-4">{{ title }}</h1>
    <p class="text-lg text-gray-600 mt-2">{{ description }}</p>
    <a href="/" class="shine-button mt-6 px-4 py-2 bg-customPrimaryColor text-white rounded hover:bg-customPrimaryColor">Go to Homepage</a>
  </div>
</template>

<script>
import { Vue3Lottie } from 'vue3-lottie';
import error404 from '../../../public/animations/Error-404.json';

export default {
  components: {
    Vue3Lottie,
  },
  props: {
    status: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      error404,
    };
  },
  computed: {
    title() {
      return {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Page Not Found',
        403: '403: Forbidden',
      }[this.status]
    },
    description() {
      return {
        503: 'Sorry, we are doing some maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our servers.',
        404: 'Sorry, the page you are looking for could not be found.',
        403: 'Sorry, you are forbidden from accessing this page.',
      }[this.status]
    },
  },
}
</script>

<style scoped>
.shine-button {
  position: relative;
  overflow: hidden;
}

.shine-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    120deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.8) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  transform: skewX(-25deg);
  animation: shine 3s infinite;
}

@keyframes shine {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}
</style>
