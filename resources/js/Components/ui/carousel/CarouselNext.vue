<script setup>
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { ChevronRightIcon } from '@radix-icons/vue'
import { useCarousel } from './useCarousel';

const props = defineProps({
  class: { type: null, required: false },
});

const { orientation, canScrollNext, scrollNext } = useCarousel();
</script>

<template>
  <Button
    :disabled="!canScrollNext"
    :class="cn(
      'touch-manipulation absolute h-12 w-12 rounded-full p-0 bg-customPrimaryColor next-btn',
      orientation === 'horizontal'
        ? '-right-6 top-1/2 -translate-y-1/2'
        : '-bottom-12 left-1/2 -translate-x-1/2 rotate-90',
      props.class,
    )"
    variant="outline"
    @click="scrollNext"
  >
    <slot>
      <ChevronRightIcon class="h-6 w-6 text-white" />
      <span class="sr-only">Next Slide</span>
    </slot>
  </Button>
</template>
