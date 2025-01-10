<script setup>
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { ChevronLeftIcon } from '@radix-icons/vue'
import { useCarousel } from './useCarousel';

const props = defineProps({
  class: { type: null, required: false },
});

const { orientation, canScrollPrev, scrollPrev } = useCarousel();
</script>


<template>
  <Button
    :disabled="!canScrollPrev"
    :class="cn(
      'touch-manipulation absolute h-12 w-12 rounded-full p-0 bg-customPrimaryColor prev-btn',
      orientation === 'horizontal'
        ? '-left-12 top-1/2 -translate-y-1/2'
        : '-top-12 left-1/2 -translate-x-1/2 rotate-90',
      props.class,
    )"
    variant="outline"
    @click="scrollPrev"
  >
    <slot>
      <ChevronLeftIcon class="h-6 w-6 text-white" />
      <span class="sr-only">Previous Slide</span>
    </slot>
  </Button>
</template>
