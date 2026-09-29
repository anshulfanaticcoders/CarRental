<script setup lang="ts">
import { ref, watch } from 'vue'
import { CarFront, ArrowRight } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

const props = defineProps<{
  title: string
  imageUrl?: string | null
  specs: string[]
  total: string
  perDay: string
  disabled: boolean
}>()

const emit = defineEmits<{ (e: 'select'): void }>()

const { t } = useOfferTranslations()
const imageFailed = ref(false)
watch(() => props.imageUrl, () => { imageFailed.value = false })
</script>

<template>
  <article class="ofac">
    <figure class="ofac-media">
      <img v-if="imageUrl && !imageFailed" :src="imageUrl" :alt="title || t('vehicle_image', 'Vehicle image')" loading="lazy" decoding="async" @error="imageFailed = true" />
      <span v-else class="ofac-media-empty">
        <CarFront :size="26" :stroke-width="1.4" />
        <small>{{ t('no_image_available', 'No image available') }}</small>
      </span>
    </figure>

    <div class="ofac-body">
      <h3 class="ofac-title">{{ title }}</h3>
      <ul v-if="specs.length > 0" class="ofac-specs">
        <li v-for="spec in specs" :key="spec">{{ spec }}</li>
      </ul>
    </div>

    <div class="ofac-buy">
      <p class="ofac-price">
        <small>{{ t('total', 'Total') }}</small>
        <strong>{{ total }}</strong>
        <em>{{ t('per_day_amount', ':amount / day', { amount: perDay }) }}</em>
      </p>
      <button type="button" class="ofac-select" :disabled="disabled" @click="emit('select')">
        <span>{{ t('select_this_car', 'Select this car') }}</span>
        <ArrowRight class="ofac-arrow" :size="15" :stroke-width="2.2" />
      </button>
    </div>
  </article>
</template>

<style scoped>
.ofac {
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  display: grid; gap: 14px; padding: 16px; min-width: 0;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
  transition: border-color 0.3s var(--ease), box-shadow 0.3s var(--ease), transform 0.3s var(--ease);
}
.ofac:hover { border-color: #b0d4e6; box-shadow: 0 12px 32px rgba(21, 59, 79, 0.12); transform: translateY(-2px); }

.ofac-media { margin: 0; aspect-ratio: 16 / 10; display: grid; place-items: center; background: #f8fafc; border-radius: 12px; overflow: hidden; }
.ofac-media img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.ofac-media-empty { display: grid; justify-items: center; gap: 6px; color: #94a3b8; font-size: 0.7rem; text-align: center; padding: 8px; }

.ofac-body { min-width: 0; }
.ofac-title { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.98rem; font-weight: 700; color: #153b4f; line-height: 1.3; overflow-wrap: anywhere; }
.ofac-specs { list-style: none; margin: 8px 0 0; padding: 0; display: flex; flex-wrap: wrap; gap: 5px 6px; }
.ofac-specs li {
  padding: 4px 9px; border-radius: 999px; background: #f8fafc; border: 1px solid #e2e8f0;
  font-size: 0.72rem; color: #475569; overflow-wrap: anywhere;
}

.ofac-buy { display: grid; gap: 12px; align-items: end; }
.ofac-price { margin: 0; display: grid; min-width: 0; }
.ofac-price small { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.12em; color: #94a3b8; }
.ofac-price strong { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem; font-weight: 800; color: #153b4f; line-height: 1.15; overflow-wrap: anywhere; }
.ofac-price em { font-style: normal; font-size: 0.74rem; color: #64748b; overflow-wrap: anywhere; }

.ofac-select {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer;
  padding: 10px 18px; border-radius: 999px; border: 1px solid #153b4f; background: #fff;
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.82rem; font-weight: 700; color: #153b4f;
  transition: background 0.3s var(--ease), color 0.3s var(--ease), box-shadow 0.3s var(--ease), opacity 0.3s var(--ease);
}
.ofac-select:hover:not(:disabled) { background: #153b4f; color: #fff; box-shadow: 0 4px 12px rgba(21, 59, 79, 0.2); }
.ofac-select:focus-visible { outline: 2px solid #22d3ee; outline-offset: 3px; }
.ofac-select:disabled { opacity: 0.45; cursor: not-allowed; }
.ofac-arrow { flex: none; }

[dir='rtl'] .ofac-arrow { rotate: 180deg; }

@media (min-width: 560px) {
  .ofac { grid-template-columns: 150px minmax(0, 1fr); grid-template-areas: 'media body' 'media buy'; align-content: start; column-gap: 16px; }
  .ofac-media { grid-area: media; aspect-ratio: 1; align-self: start; }
  .ofac-body { grid-area: body; }
  .ofac-buy { grid-area: buy; grid-template-columns: minmax(0, 1fr) auto; align-items: center; }
}
@media (prefers-reduced-motion: reduce) {
  .ofac, .ofac-select { transition: none; }
  .ofac:hover { transform: none; }
}
</style>
