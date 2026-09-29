<script setup lang="ts">
import { computed } from 'vue'
import { ArrowRight, ShieldCheck } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

const props = defineProps<{
  total: string
  payNow: string | null
  payLater: string | null
  paymentPercentage: number | null
  durationText: string
  packageLabel: string
  deposit: string
  disabled: boolean
  expired: boolean
}>()

const emit = defineEmits<{ (e: 'book'): void }>()

const { t } = useOfferTranslations()
const hasSplit = computed(() => props.paymentPercentage !== null && props.payNow !== null && props.payLater !== null)
const payNowWidth = computed(() => `${Math.min(100, Math.max(4, props.paymentPercentage ?? 0))}%`)
</script>

<template>
  <aside class="ofsm" :aria-label="t('checkout_summary', 'Checkout summary')">
    <p class="ofsm-label">{{ t('checkout_summary', 'Checkout summary') }}</p>
    <p class="ofsm-package">{{ packageLabel }}</p>

    <p class="ofsm-total-label">{{ t('total_rental_price', 'Total rental price') }}</p>
    <p class="ofsm-total">{{ total }}</p>
    <p class="ofsm-duration">{{ durationText }}</p>

    <dl class="ofsm-facts">
      <div>
        <dt>{{ t('security_deposit', 'Security deposit') }}</dt>
        <dd>{{ deposit }}</dd>
      </div>
    </dl>

    <div v-if="hasSplit" class="ofsm-split">
      <div class="ofsm-bar" role="presentation"><i :style="{ width: payNowWidth }"></i></div>
      <div class="ofsm-split-rows">
        <div class="ofsm-split-row is-now">
          <span>{{ t('pay_now_percent', 'Pay now :percent%', { percent: paymentPercentage ?? 0 }) }}</span>
          <strong>{{ payNow }}</strong>
        </div>
        <div class="ofsm-split-row">
          <span>{{ t('pay_on_arrival', 'Pay on arrival') }}</span>
          <strong>{{ payLater }}</strong>
        </div>
      </div>
    </div>
    <p v-else class="ofsm-split-missing">{{ t('payment_split_unavailable', 'The deposit split is unavailable for this offer. You will see the exact amount before you pay.') }}</p>

    <button type="button" class="ofsm-cta" :disabled="disabled" @click="emit('book')">
      <span>{{ expired ? t('offer_expired', 'Offer expired') : t('choose_extras_and_continue', 'Choose extras and continue') }}</span>
      <ArrowRight v-if="!expired" class="ofsm-cta-arrow" :size="17" :stroke-width="2.2" />
    </button>

    <p class="ofsm-note">
      <ShieldCheck :size="15" :stroke-width="1.9" />
      <span>{{ t('secure_authorization_note', 'Secure card authorization · Charged only after the supplier confirms') }}</span>
    </p>
  </aside>
</template>

<style scoped>
.ofsm {
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px;
  box-shadow: 0 4px 12px rgba(21, 59, 79, 0.08), 0 2px 4px rgba(21, 59, 79, 0.04);
}
.ofsm-label { margin: 0; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; color: #94a3b8; }
.ofsm-package { margin: 6px 0 18px; font-size: 0.86rem; font-weight: 600; color: #334155; overflow-wrap: anywhere; }
.ofsm-total-label { margin: 0; font-size: 0.76rem; color: #64748b; }
.ofsm-total { margin: 2px 0 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(1.9rem, 4vw, 2.3rem); font-weight: 800; color: #153b4f; line-height: 1.05; letter-spacing: -0.02em; overflow-wrap: anywhere; }
.ofsm-duration { margin: 4px 0 0; font-size: 0.78rem; color: #94a3b8; }
.ofsm-facts { margin: 14px 0 0; padding: 12px 0; border-block: 1px solid #e2e8f0; }
.ofsm-facts div { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
.ofsm-facts dt { font-size: 0.78rem; color: #64748b; }
.ofsm-facts dd { margin: 0; font-size: 0.84rem; font-weight: 700; color: #153b4f; text-align: end; overflow-wrap: anywhere; }

.ofsm-split { margin: 18px 0 20px; }
.ofsm-bar { height: 6px; border-radius: 999px; background: #dceef6; overflow: hidden; }
.ofsm-bar i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #153b4f, #2d7294); transition: width 0.5s var(--ease); }
.ofsm-split-rows { margin-top: 12px; display: grid; gap: 8px; }
.ofsm-split-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; font-size: 0.84rem; color: #64748b; }
.ofsm-split-row strong { font-weight: 700; color: #334155; overflow-wrap: anywhere; text-align: end; }
.ofsm-split-row.is-now { color: #153b4f; font-weight: 600; }
.ofsm-split-row.is-now strong { color: #153b4f; }
.ofsm-split-missing { margin: 18px 0; padding: 11px 13px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.78rem; line-height: 1.55; color: #64748b; }

.ofsm-cta {
  width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 9px;
  padding: 14px 20px; border: 0; border-radius: 999px; cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.92rem; font-weight: 700; color: #fff;
  background: linear-gradient(135deg, #153b4f, #1c4d66);
  box-shadow: 0 4px 12px rgba(21, 59, 79, 0.24);
  transition: transform 0.3s var(--ease), box-shadow 0.3s var(--ease), opacity 0.3s var(--ease);
}
.ofsm-cta-arrow { flex: none; transition: transform 0.3s var(--ease); }
.ofsm-cta:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(21, 59, 79, 0.26); }
.ofsm-cta:hover:not(:disabled) .ofsm-cta-arrow { transform: translateX(3px); }
.ofsm-cta:focus-visible { outline: 2px solid #22d3ee; outline-offset: 3px; }
.ofsm-cta:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; }

.ofsm-note { display: flex; gap: 8px; margin: 14px 0 0; font-size: 0.75rem; line-height: 1.55; color: #64748b; }
.ofsm-note svg { flex: none; margin-top: 1px; color: #2d7294; }

[dir='rtl'] .ofsm-cta-arrow { rotate: 180deg; }
[dir='rtl'] .ofsm-cta:hover:not(:disabled) .ofsm-cta-arrow { transform: translateX(-3px); }

@media (prefers-reduced-motion: reduce) {
  .ofsm-cta, .ofsm-cta-arrow, .ofsm-bar i { transition: none; }
  .ofsm-cta:hover:not(:disabled) { transform: none; }
  .ofsm-cta:hover:not(:disabled) .ofsm-cta-arrow { transform: none; }
}
</style>
