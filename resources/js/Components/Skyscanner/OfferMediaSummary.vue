<script setup lang="ts">
import { computed } from 'vue'
import { BadgeCheck, Fuel, MapPin, ShieldCheck } from 'lucide-vue-next'

import { buildOfferMediaSummary } from '@/features/skyscanner/utils/offerMediaSummary'

const props = defineProps<{
  supplierName?: string | null
  pickupOffice?: string | null
  transmission?: string | null
  fuelType?: string | null
  seats?: number | null
  mileagePolicy?: string | null
  cancellation?: {
    available?: boolean | null
    daysBeforePickup?: number | null
  } | null
}>()

const items = computed(() => buildOfferMediaSummary({
  supplierName: props.supplierName,
  pickupOffice: props.pickupOffice,
  transmission: props.transmission,
  fuelType: props.fuelType,
  seats: props.seats,
  mileagePolicy: props.mileagePolicy,
  cancellation: {
    available: props.cancellation?.available ?? false,
    daysBeforePickup: props.cancellation?.daysBeforePickup ?? null,
  },
}))

const iconMap = {
  badge: BadgeCheck,
  pin: MapPin,
  gauge: Fuel,
  road: Fuel,
  shield: ShieldCheck,
} as const
</script>

<template>
  <aside class="or-media-summary">
    <p class="or-media-summary-eyebrow">At a glance</p>
    <div class="or-media-summary-stack">
      <div v-for="item in items" :key="item.label" class="or-media-summary-item">
        <div class="or-media-summary-icon">
          <component :is="iconMap[item.icon]" class="or-media-summary-icon-svg" />
        </div>
        <div class="or-media-summary-copy">
          <span class="or-media-summary-label">{{ item.label }}</span>
          <strong class="or-media-summary-value">{{ item.value }}</strong>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.or-media-summary {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 14px;
  min-width: 0;
  width: 100%;
  padding: 20px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(248, 250, 252, 0.96) 100%);
  border: 1px solid rgba(148, 163, 184, 0.22);
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
}

.or-media-summary-eyebrow {
  margin: 0;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #94a3b8;
}

.or-media-summary-stack {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.or-media-summary-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(226, 232, 240, 0.9);
}

.or-media-summary-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border-radius: 12px;
  background: linear-gradient(135deg, #dbeafe 0%, #cffafe 100%);
  color: #153b4f;
  display: flex;
  align-items: center;
  justify-content: center;
}

.or-media-summary-icon-svg {
  width: 18px;
  height: 18px;
}

.or-media-summary-copy {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.or-media-summary-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #94a3b8;
}

.or-media-summary-value {
  font-size: 14px;
  line-height: 1.4;
  color: #153b4f;
  word-break: break-word;
}
</style>
