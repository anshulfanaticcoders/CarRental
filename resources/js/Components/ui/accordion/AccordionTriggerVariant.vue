<script setup>
import { cn } from '@/lib/utils';
import { PlusIcon,MinusIcon} from '@radix-icons/vue';
import { AccordionHeader, AccordionTrigger } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;
  return delegated;
});
</script>

<template>
   <AccordionHeader class="flex">
    <AccordionTrigger
      v-bind="delegatedProps"
      :class="
        cn(
          'flex flex-1 items-center justify-between py-4 text-sm font-medium transition-all [&[data-state=open]>svg]:rotate-180',
          props.class,
        )
      "
    >
      <slot />
      <slot name="icon">
        <PlusIcon
          class="h-8 w-8 shrink-0 [&[data-state=open] text-muted-foreground transition-transform duration-200 bg-[#153B4F1A] rounded-[8px] p-1"
        />
      </slot>
    </AccordionTrigger>
  </AccordionHeader>
</template>
