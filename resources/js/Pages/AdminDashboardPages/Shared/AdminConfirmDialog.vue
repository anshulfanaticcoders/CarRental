<script setup>
import { computed } from 'vue';
import { AlertTriangle, Loader2 } from 'lucide-vue-next';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  confirmLabel: {
    type: String,
    default: 'Confirm',
  },
  cancelLabel: {
    type: String,
    default: 'Cancel',
  },
  processing: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'danger',
  },
});

const emit = defineEmits(['update:open', 'confirm']);

const dialogOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const iconClass = computed(() => (
  props.variant === 'warning'
    ? 'text-amber-300 bg-amber-500/12 border-amber-400/30'
    : 'text-red-300 bg-red-500/12 border-red-400/30'
));

const actionClass = computed(() => (
  props.variant === 'warning'
    ? 'bg-amber-500 text-[#07131c] hover:bg-amber-400 focus-visible:ring-amber-300'
    : 'bg-red-500 text-white hover:bg-red-400 focus-visible:ring-red-300'
));
</script>

<template>
  <AlertDialog v-model:open="dialogOpen">
    <AlertDialogContent class="admin-confirm-dialog sm:max-w-[440px]">
      <AlertDialogHeader>
        <div class="mb-3 flex items-start gap-3">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border"
            :class="iconClass"
          >
            <AlertTriangle class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <AlertDialogTitle class="text-left text-lg font-bold tracking-tight">
              {{ title }}
            </AlertDialogTitle>
            <AlertDialogDescription class="mt-1 text-left text-sm leading-6">
              {{ description }}
            </AlertDialogDescription>
          </div>
        </div>
      </AlertDialogHeader>

      <AlertDialogFooter class="gap-2 sm:justify-end">
        <AlertDialogCancel
          :disabled="processing"
          class="admin-confirm-cancel !border-cyan-400/30 !bg-[#0b2231] !text-[#d8edf7] shadow-none hover:!border-cyan-300/50 hover:!bg-[#123347] hover:!text-white focus-visible:!ring-2 focus-visible:!ring-cyan-300/50 active:!scale-[0.98] disabled:!cursor-not-allowed disabled:!opacity-50"
        >
          {{ cancelLabel }}
        </AlertDialogCancel>
        <AlertDialogAction
          :disabled="processing"
          :class="actionClass"
          @click="emit('confirm')"
        >
          <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ processing ? 'Working...' : confirmLabel }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
