<script setup>
import { cn } from '@/lib/utils';
import { ChevronRightIcon } from '@radix-icons/vue';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <li
    role="presentation"
    aria-hidden="true"
    :class="cn('[&>svg]:size-3.5', props.class)"
  >
    <slot>
      <ChevronRightIcon />
    </slot>
  </li>
</template>
