<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  size: {
    type: Number,
    default: 180,
  },
  innerRatio: {
    type: Number,
    default: 0.62,
  },
});

const palette = ['#153B4F', '#10B981', '#F59E0B', '#EF4444', '#6366F1'];

const normalized = computed(() => {
  return props.data.map((item, index) => ({
    label: item.label,
    value: Number(item.value) || 0,
    color: item.color || palette[index % palette.length],
  }));
});

const total = computed(() => normalized.value.reduce((sum, item) => sum + item.value, 0));

const gradient = computed(() => {
  if (total.value === 0) {
    return '#E5E7EB 0% 100%';
  }

  let offset = 0;
  const segments = normalized.value
    .filter(item => item.value > 0)
    .map((item) => {
      const percent = (item.value / total.value) * 100;
      const start = offset;
      const end = offset + percent;
      offset = end;
      return `${item.color} ${start}% ${end}%`;
    });

  return segments.join(', ');
});

const centerSize = computed(() => Math.round(props.size * props.innerRatio));
</script>

<template>
  <div class="pie-wrapper" :style="{ width: `${size}px` }">
    <div
      class="pie"
      :style="{
        width: `${size}px`,
        height: `${size}px`,
        background: `conic-gradient(${gradient})`,
      }"
    >
      <div
        class="pie-center"
        :style="{
          width: `${centerSize}px`,
          height: `${centerSize}px`,
        }"
      >
        <div class="pie-total">{{ total }}</div>
        <div class="pie-label">Total</div>
      </div>
    </div>
    <div class="pie-legend">
      <div v-for="slice in normalized" :key="slice.label" class="pie-legend-item">
        <span class="pie-dot" :style="{ backgroundColor: slice.color }"></span>
        <span class="pie-text">{{ slice.label }}</span>
        <span class="pie-value">{{ slice.value }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pie-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.pie {
  border-radius: 999px;
  display: grid;
  place-items: center;
  position: relative;
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
}

.pie-center {
  border-radius: 999px;
  background: #fff;
  display: grid;
  place-items: center;
  box-shadow: inset 0 0 20px rgba(15, 23, 42, 0.06);
}

.pie-total {
  font-size: 1.4rem;
  font-weight: 700;
  color: #153b4f;
}

.pie-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.pie-legend {
  width: 100%;
  display: grid;
  gap: 8px;
}

.pie-legend-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 0.85rem;
  color: #4b5563;
}

.pie-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  flex-shrink: 0;
}

.pie-text {
  flex: 1;
}

.pie-value {
  font-weight: 600;
  color: #111827;
}
</style>
