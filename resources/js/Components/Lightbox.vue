<template>
    <div>
      <!-- Trigger elements remain unchanged in your original code -->
      
      <!-- Lightbox overlay -->
      <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90"
           @click.self="closeLightbox">
        <div class="relative w-full h-full flex flex-col">
          <!-- Close button -->
          <button @click="closeLightbox" class="absolute top-4 right-4 text-white text-2xl z-10 p-2">
            &times;
          </button>
          
          <!-- Navigation arrows -->
          <button @click="prevImage" class="absolute left-4 top-1/2 text-white text-4xl z-10">
            &#10094;
          </button>
          <button @click="nextImage" class="absolute right-4 top-1/2 text-white text-4xl z-10">
            &#10095;
          </button>
          
          <!-- Image container -->
          <div class="flex items-center justify-center h-full">
            <img :src="currentImage" alt="Lightbox Image" class="max-h-[90vh] max-w-[90vw] object-contain" />
          </div>
          
          <!-- Image counter -->
          <div class="absolute bottom-4 left-0 right-0 text-center text-white">
            {{ currentIndex + 1 }} / {{ images.length }}
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, onUnmounted } from 'vue';
  
  // Props
  const props = defineProps({
    images: {
      type: Array,
      required: true
    }
  });
  
  // State
  const isOpen = ref(false);
  const currentIndex = ref(0);
  
  // Computed
  const currentImage = computed(() => {
    if (props.images.length === 0) return '';
    return props.images[currentIndex.value].image_url || props.images[currentIndex.value];
  });
  
  // Methods
  const openLightbox = (index = 0) => {
    currentIndex.value = index;
    isOpen.value = true;
    document.body.style.overflow = 'hidden'; // Prevent scrolling when lightbox is open
  };
  
  const closeLightbox = () => {
    isOpen.value = false;
    document.body.style.overflow = ''; // Restore scrolling
  };
  
  const nextImage = () => {
    if (currentIndex.value < props.images.length - 1) {
      currentIndex.value++;
    } else {
      currentIndex.value = 0; // Loop back to the first image
    }
  };
  
  const prevImage = () => {
    if (currentIndex.value > 0) {
      currentIndex.value--;
    } else {
      currentIndex.value = props.images.length - 1; // Loop to the last image
    }
  };
  
  // Keyboard navigation
  const handleKeydown = (e) => {
    if (!isOpen.value) return;
    
    if (e.key === 'Escape') closeLightbox();
    else if (e.key === 'ArrowLeft') prevImage();
    else if (e.key === 'ArrowRight') nextImage();
  };
  
  // Add keyboard event listeners
  onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
  });
  
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });
  
  // Expose methods to parent component
  defineExpose({
    openLightbox
  });
  </script>