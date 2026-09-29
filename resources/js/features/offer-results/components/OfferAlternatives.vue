<script setup lang="ts">
import { Plus } from 'lucide-vue-next'
import OfferAlternativeCard from './OfferAlternativeCard.vue'
import { useOfferTranslations } from '../composables/useOfferTranslations'

export type AlternativeOffer = {
  quoteId: string
  title: string
  imageUrl?: string | null
  specs: string[]
  total: string
  perDay: string
  selection: Record<string, unknown>
  disabled: boolean
}

defineProps<{
  offers: AlternativeOffer[]
  totalCount: number
  canReveal: boolean
}>()

const emit = defineEmits<{
  (e: 'select', quoteId: string, selection: Record<string, unknown>): void
  (e: 'reveal'): void
}>()

const { t } = useOfferTranslations()
</script>

<template>
  <section class="ofa" :aria-label="t('alternatives', 'Alternatives')">
    <div class="ofa-head">
      <div>
        <p class="ofa-label">{{ t('alternatives', 'Alternatives') }}</p>
        <h2>{{ t('you_might_also_like', 'You might also like') }}</h2>
        <p class="ofa-lede">{{ t('alternatives_lede', 'Same dates and same pickup office. Pick a different car if this one is not the right fit.') }}</p>
      </div>
      <span class="ofa-count">{{ t('showing_of_total', ':shown of :total', { shown: offers.length, total: totalCount }) }}</span>
    </div>

    <div class="ofa-grid">
      <OfferAlternativeCard
        v-for="offer in offers"
        :key="offer.quoteId"
        :title="offer.title"
        :image-url="offer.imageUrl"
        :specs="offer.specs"
        :total="offer.total"
        :per-day="offer.perDay"
        :disabled="offer.disabled"
        @select="emit('select', offer.quoteId, offer.selection)"
      />
    </div>

    <div v-if="canReveal" class="ofa-more">
      <button type="button" @click="emit('reveal')">
        <Plus :size="16" :stroke-width="2.2" />
        {{ t('show_more_offers', 'Show more offers') }}
      </button>
    </div>
  </section>
</template>

<style scoped>
.ofa { --ease: cubic-bezier(0.22, 1, 0.36, 1); }
.ofa-head { display: grid; gap: 10px; margin-bottom: 20px; }
.ofa-label { margin: 0 0 6px; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; color: #94a3b8; }
.ofa-head h2 { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 700; color: #153b4f; line-height: 1.25; }
.ofa-lede { margin: 8px 0 0; font-size: 0.85rem; color: #64748b; line-height: 1.6; max-width: 56ch; }
.ofa-count { font-size: 0.74rem; font-weight: 600; color: #94a3b8; white-space: nowrap; }

.ofa-grid { display: grid; gap: 14px; grid-template-columns: minmax(0, 1fr); }

.ofa-more { margin-top: 20px; display: flex; justify-content: center; }
.ofa-more button {
  display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; cursor: pointer;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
  font-size: 0.84rem; font-weight: 600; color: #153b4f;
  transition: background 0.3s var(--ease), border-color 0.3s var(--ease), transform 0.3s var(--ease), box-shadow 0.3s var(--ease);
}
.ofa-more button:hover { background: #f0f8fc; border-color: #153b4f; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(21, 59, 79, 0.08); }
.ofa-more button:focus-visible { outline: 2px solid #22d3ee; outline-offset: 3px; }

@media (min-width: 640px) { .ofa-head { grid-template-columns: minmax(0, 1fr) auto; align-items: end; } }
@media (prefers-reduced-motion: reduce) { .ofa-more button { transition: none; } .ofa-more button:hover { transform: none; } }
</style>
