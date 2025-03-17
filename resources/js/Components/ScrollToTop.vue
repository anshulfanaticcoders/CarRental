<template>
    <button 
      v-if="isVisible" 
      @click="scrollToTop" 
      class="fixed max-[768px]:hidden bottom-4 right-4 bg-customPrimaryColor rounded-xl p-4 shadow-lg hover:bg-gray-100 hover:text-customPrimaryColor transition-colors"
    >
      <ChevronUp class='text-white hover:text-customPrimaryColor' />
    </button>
  </template>
  
  <script setup>
  import { ref, onMounted, onUnmounted } from 'vue'
  import { ChevronUp } from 'lucide-vue-next'
  
  const isVisible = ref(false)
  
  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    })
  }
  
  const toggleVisibility = () => {
    isVisible.value = window.scrollY > 300
  }
  
  onMounted(() => {
    window.addEventListener('scroll', toggleVisibility)
  })
  
  onUnmounted(() => {
    window.removeEventListener('scroll', toggleVisibility)
  })
  </script>