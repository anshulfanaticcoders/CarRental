<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

defineProps<{
  total: string
  payNow: string | null
  paymentPercentage: number | null
  disabled: boolean
  expired: boolean
}>()

const emit = defineEmits<{ (e: 'book'): void }>()

const { t } = useOfferTranslations()
</script>

<template>
  <div class="ofab">
    <div class="ofab-inner">
      <p class="ofab-figures">
        <strong>{{ total }}</strong>
        <small v-if="payNow !== null && paymentPercentage !== null">
          {{ t('pay_now_amount', 'Pay now :percent% · :amount', { percent: paymentPercentage, amount: payNow }) }}
        </small>
        <small v-else>{{ t('payment_split_unavailable_short', 'Deposit shown before you pay') }}</small>
      </p>
      <button type="button" class="ofab-cta" :disabled="disabled" @click="emit('book')">
        <span>{{ expired ? t('offer_expired', 'Offer expired') : t('choose_extras_and_continue', 'Choose extras and continue') }}</span>
        <ArrowRight v-if="!expired" class="ofab-arrow" :size="16" :stroke-width="2.2" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.ofab {
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  position: fixed; inset-inline: 0; bottom: 0; z-index: 40;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(12px) saturate(1.3);
  -webkit-backdrop-filter: blur(12px) saturate(1.3);
  border-top: 1px solid #e2e8f0;
  box-shadow: 0 -8px 24px rgba(21, 59, 79, 0.1);
  padding-bottom: env(safe-area-inset-bottom);
}
.ofab-inner { width: min(92%, 1440px); margin-inline: auto; display: grid; gap: 8px; padding: 10px 0 12px; }
.ofab-figures { margin: 0; min-width: 0; display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; }
.ofab-figures strong { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.05rem; font-weight: 800; color: #153b4f; line-height: 1.15; overflow-wrap: anywhere; }
.ofab-figures small { font-size: 0.72rem; color: #64748b; overflow-wrap: anywhere; }
.ofab-cta {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 12px 20px; border: 0; border-radius: 999px; cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 700; color: #fff;
  background: linear-gradient(135deg, #153b4f, #1c4d66);
  box-shadow: 0 4px 12px rgba(21, 59, 79, 0.24);
  transition: transform 0.3s var(--ease), box-shadow 0.3s var(--ease), opacity 0.3s var(--ease);
}
.ofab-arrow { flex: none; }
.ofab-cta:hover:not(:disabled) { transform: translateY(-1px); }
.ofab-cta:focus-visible { outline: 2px solid #22d3ee; outline-offset: 3px; }
.ofab-cta:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; }

[dir='rtl'] .ofab-arrow { rotate: 180deg; }

@media (min-width: 480px) {
  .ofab-inner { grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 14px; }
}
@media (min-width: 1024px) { .ofab { display: none; } }
@media (prefers-reduced-motion: reduce) { .ofab-cta { transition: none; } .ofab-cta:hover:not(:disabled) { transform: none; } }
</style>
