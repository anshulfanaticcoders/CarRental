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
          'flex flex-1 items-center justify-between py-4 text-sm font-medium transition-all group',
          props.class,
        )
      "
    >
      <slot />
      <slot name="icon">
        <div class="relative h-8 w-8 ml-2">
          <PlusIcon
          class="h-8 w-8 absolute top-0 right-0 shrink-0 group-data-[state=closed]:opacity-100 group-data-[state=closed]:rotate-0 group-data-[state=open]:opacity-0 group-data-[state=open]:rotate-90 text-muted-foreground transition-transform duration-200 bg-[#153B4F1A] rounded-[8px] p-1"
        />
        <MinusIcon
          class="h-8 w-8 absolute bg-customPrimaryColor text-white top-0 right-0 shrink-0 group-data-[state=closed]:opacity-0 group-data-[state=closed]:rotate-0 group-data-[state=open]:opacity-100 group-data-[state=open]:rotate-180 text-muted-foreground transition-transform duration-200 rounded-[8px] p-1"
        />
        </div>
      </slot>
    </AccordionTrigger>
  </AccordionHeader>
</template>
