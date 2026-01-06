<script setup>
import { ref, computed, onMounted, inject } from 'vue';

const props = defineProps({
  modelValue: [String, Number],
  options: {
    type: Array,
    default: () => []
  },
  placeholder: String,
  leftIcon: String,
  rightIcon: String,
  uniqueId: String
});

const emit = defineEmits(['update:modelValue']);

const activeDropdown = inject('activeDropdown');
const setActiveDropdown = inject('setActiveDropdown');

const selectedLabel = computed(() => {
  if (!props.options) return props.placeholder;
  const selectedOption = props.options.find((option) => option.value === props.modelValue);
  return selectedOption ? selectedOption.label : props.placeholder;
});

const selectOption = (value) => {
  emit('update:modelValue', value);
  setActiveDropdown(null);
};

onMounted(() => {
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.custom-dropdown')) {
      setActiveDropdown(null);
    }
  });
});
</script>

<template>
  <div class="custom-dropdown relative w-full md:w-auto">
    <!-- Dropdown Trigger -->
    <button
      type="button"
      @click.stop="setActiveDropdown(uniqueId)"
      class="flex gap-2 items-center justify-between w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-sm shadow-sm hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-black"
    >
      <!-- Left Icon -->
      <img
        v-if="leftIcon"
        :src="leftIcon"
        :alt="placeholder + ' Icon'"
        class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none"
      />
      <!-- Selected Value or Placeholder -->
      <span class="text-gray-700 font-medium truncate">{{ selectedLabel }}</span>
      <!-- Right Icon (Caret) -->
      <img
        v-if="rightIcon"
        :src="rightIcon"
        alt="Caret Down"
        class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
        :class="{ 'rotate-180': activeDropdown === uniqueId }"
      />
    </button>

    <!-- Dropdown Menu -->
    <div
      v-if="activeDropdown === uniqueId"
      class="absolute z-50 mt-2 w-[15rem] max-[768px]:w-full bg-white shadow-xl rounded-lg border border-gray-100 animate-fade-in max-h-60 overflow-y-auto"
    >
      <ul class="py-1">
        <!-- Placeholder Option -->
        <li
          v-if="placeholder"
          class="px-4 py-2 text-gray-500 hover:bg-gray-100 cursor-pointer"
          @click="selectOption('')"
        >
          {{ placeholder }}
        </li>
        <!-- Options -->
        <li
          v-for="option in options"
          :key="option.value"
          class="px-4 py-2 text-gray-700 hover:bg-gray-100 cursor-pointer"
          :class="{ 'bg-gray-50 font-semibold': option.value === modelValue }"
          @click="selectOption(option.value)"
        >
          {{ option.label }}
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>