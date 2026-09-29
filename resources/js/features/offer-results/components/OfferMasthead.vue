<script setup lang="ts">
import { ref, watch } from 'vue'
import { ShieldCheck, Users, Briefcase, Cog, Fuel, Snowflake, Gauge, Tag, CarFront } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

export type SpecFact = { key: string, value: string }

const props = defineProps<{
  partnerLabel: string
  title: string
  imageUrl?: string | null
  supplierName: string
  availabilityText: string
  specs: SpecFact[]
}>()

const { t } = useOfferTranslations()
const imageFailed = ref(false)
watch(() => props.imageUrl, () => { imageFailed.value = false })

const SPEC_ICONS: Record<string, unknown> = {
  class: Tag,
  seats: Users,
  bags: Briefcase,
  transmission: Cog,
  fuel: Fuel,
  air_conditioning: Snowflake,
  mileage: Gauge,
}

const specLabel = (key: string) => ({
  class: t('vehicle_class', 'Class'),
  seats: t('seats', 'Seats'),
  bags: t('bags', 'Bags'),
  transmission: t('gearbox', 'Gearbox'),
  fuel: t('fuel', 'Fuel'),
  air_conditioning: t('air_conditioning', 'Air conditioning'),
  mileage: t('mileage', 'Mileage'),
}[key] ?? key)
</script>

<template>
  <header class="ofm">
    <div class="ofm-inner">
      <div class="ofm-lede">
        <p class="ofm-source">
          <ShieldCheck :size="15" :stroke-width="2.1" />
          <span class="ofm-source-found">{{ t('found_on_partner', 'Found on :partner', { partner: partnerLabel }) }}</span>
          <i aria-hidden="true">·</i>
          <b class="ofm-source-brand">{{ t('securely_booked_with_vrooem', 'Securely booked with Vrooem') }}</b>
        </p>
        <h1 class="ofm-title">{{ title }}</h1>
        <p class="ofm-meta">
          <span>{{ supplierName }}</span>
          <i aria-hidden="true">·</i>
          <span>{{ availabilityText }}</span>
        </p>
      </div>

      <figure class="ofm-media">
        <img v-if="imageUrl && !imageFailed" :src="imageUrl" :alt="title || t('vehicle_image', 'Vehicle image')" loading="eager" decoding="async" @error="imageFailed = true" />
        <span v-else class="ofm-media-empty">
          <CarFront :size="30" :stroke-width="1.4" />
          <small>{{ t('no_image_available', 'No image available') }}</small>
        </span>
      </figure>
    </div>

    <dl v-if="specs.length > 0" class="ofm-specs">
      <div v-for="spec in specs" :key="spec.key" class="ofm-spec">
        <component :is="SPEC_ICONS[spec.key] ?? Tag" :size="15" :stroke-width="1.9" />
        <dt>{{ specLabel(spec.key) }}</dt>
        <dd>{{ spec.value }}</dd>
      </div>
    </dl>
  </header>
</template>

<style scoped>
.ofm {
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  position: relative; overflow: hidden; border-radius: 20px; color: #fff;
  background: linear-gradient(135deg, #0b2230 0%, #153b4f 46%, #0b1b26 100%);
  box-shadow: 0 12px 32px rgba(21, 59, 79, 0.18);
}
.ofm::before {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(ellipse 70% 90% at 82% 8%, rgba(34, 211, 238, 0.16), transparent 60%);
}
.ofm-inner { position: relative; display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; padding: 22px 20px 0; }
.ofm-lede { min-width: 0; }

.ofm-source { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 8px; margin: 0 0 12px; }
.ofm-source svg { color: #22d3ee; flex: none; align-self: center; }
.ofm-source-brand {
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.86rem; font-weight: 700;
  color: #fff; letter-spacing: -0.005em;
}
.ofm-source i { font-style: normal; color: rgba(255, 255, 255, 0.3); }
.ofm-source-found { font-size: 0.74rem; color: rgba(255, 255, 255, 0.6); }

.ofm-title {
  font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; color: #fff; margin: 0;
  font-size: clamp(1.5rem, 5.2vw, 2.35rem); line-height: 1.12; text-wrap: balance; overflow-wrap: anywhere;
}
.ofm-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; margin: 10px 0 0; font-size: 0.86rem; color: rgba(255, 255, 255, 0.72); }
.ofm-meta i { font-style: normal; opacity: 0.45; }

.ofm-media { margin: 0; width: 100%; min-width: 0; aspect-ratio: 16 / 9; display: grid; place-items: center; }
.ofm-media img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 18px 26px rgba(3, 14, 21, 0.45)); }
.ofm-media-empty { display: grid; justify-items: center; gap: 8px; color: rgba(255, 255, 255, 0.5); font-size: 0.76rem; }

.ofm-specs {
  position: relative; margin: 0; padding: 16px 20px 18px; display: flex; flex-wrap: wrap; gap: 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}
.ofm-spec {
  display: flex; align-items: baseline; gap: 7px; min-width: 0; padding: 7px 12px;
  background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 999px;
  transition: background 0.3s var(--ease), border-color 0.3s var(--ease);
}
.ofm-spec:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.2); }
.ofm-spec svg { color: #22d3ee; flex: none; align-self: center; }
.ofm-spec dt { font-size: 0.7rem; color: rgba(255, 255, 255, 0.55); white-space: nowrap; }
.ofm-spec dd { margin: 0; font-weight: 600; font-size: 0.8rem; color: #fff; overflow-wrap: anywhere; }

@media (min-width: 768px) {
  .ofm-inner { grid-template-columns: minmax(0, 1fr) minmax(0, 0.85fr); align-items: center; gap: 24px; padding: 30px 30px 6px; }
  .ofm-media { aspect-ratio: 4 / 3; }
  .ofm-specs { padding: 18px 30px 22px; }
}
@media (prefers-reduced-motion: reduce) { .ofm-spec { transition: none; } }
</style>
