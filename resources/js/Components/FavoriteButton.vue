<!-- FavoriteButton.vue -->
<script setup>
import { ref, onMounted, watch } from 'vue';
import lottie from 'lottie-web';

const props = defineProps({
  isFavorite: {
    type: Boolean,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['toggle']);

const animationContainer = ref(null);
const animation = ref(null);

onMounted(async () => {
  // Import the Lottie animation JSON
  const animationData = await import('../../assets/heart-animation.json');
  
  animation.value = lottie.loadAnimation({
    container: animationContainer.value,
    renderer: 'svg',
    loop: false,
    autoplay: false,
    animationData: animationData.default,
  });

  // Set initial animation state
  if (props.isFavorite) {
    animation.value.goToAndStop(animation.value.totalFrames - 1, true);
  }
});

watch(() => props.isFavorite, (newValue) => {
  if (!animation.value) return;

  if (newValue) {
    animation.value.playSegments([0, animation.value.totalFrames], true);
  } else {
    animation.value.playSegments([animation.value.totalFrames, 0], true);
  }
});

const handleClick = () => {
  if (!props.loading) {
    emit('toggle');
  }
};
</script>

<template>
  <button 
    class="block w-[2rem] h-[2rem] relative"
    @click.prevent="handleClick"
    :disabled="loading"
  >
    <div 
      ref="animationContainer" 
      class="w-full h-full"
      :class="{ 'opacity-50': loading }"
    ></div>
  </button>
</template>