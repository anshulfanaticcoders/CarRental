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
    :aria-busy="loading ? 'true' : 'false'"
  >
    <div 
      ref="animationContainer" 
      class="w-full h-full"
      :class="{ 'opacity-50': loading }"
    ></div>
    <span v-if="loading" class="favorite-spinner" aria-hidden="true"></span>
  </button>
</template>

<style scoped>
.favorite-spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 14px;
  height: 14px;
  margin: -7px 0 0 -7px;
  border-radius: 999px;
  border: 2px solid rgba(0, 0, 0, 0.12);
  border-top-color: rgba(0, 0, 0, 0.55);
  animation: favorite-spin 0.8s linear infinite;
}

@keyframes favorite-spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
