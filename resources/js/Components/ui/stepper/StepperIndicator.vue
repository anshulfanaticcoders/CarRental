<script setup>
import { cn } from '@/lib/utils';
import { StepperIndicator, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
  stepNumber: { type: Number, required: true },  // Add step number prop
});

const delegatedProps = computed(() => {
  const { class: _, stepNumber: __, ...delegated } = props;
  return delegated;
});

const forwarded = useForwardProps(delegatedProps);

// Check if slot content is provided
const hasSlotContent = computed(() => !!slots.default);
</script>

<template>
  <StepperIndicator
    v-bind="forwarded"
    :class="
      cn(
        'inline-flex items-center justify-center rounded-full text-muted-foreground/50 w-12 h-12 font-medium text-base',
        // Disabled
        'group-data-[disabled]:text-muted-foreground group-data-[disabled]:opacity-50',
        // Active
        'group-data-[state=active]:bg-primary group-data-[state=active]:text-primary-foreground',
        // Completed
        'group-data-[state=completed]:bg-accent group-data-[state=completed]:text-accent-foreground',
        props.class,
      )
    "
  >
    <!-- Show slot content if provided, otherwise show step number -->
    <slot>
      {{ props.stepNumber }}
    </slot>
  </StepperIndicator>
</template>