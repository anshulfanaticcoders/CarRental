<script setup lang="ts">
import { computed } from 'vue'
import { Check, SlidersHorizontal, CreditCard } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

const props = defineProps<{ step: 'extras' | 'checkout' }>()
const emit = defineEmits<{ (e: 'back-to-offer'): void, (e: 'back-to-extras'): void }>()

const { t } = useOfferTranslations()
const onCheckout = computed(() => props.step === 'checkout')
</script>

<template>
  <nav class="ofs" :aria-label="t('booking_progress', 'Booking progress')">
    <div class="full-w-container ofs-track">
      <button type="button" class="ofs-node is-done" @click="emit('back-to-offer')">
        <span class="ofs-dot"><Check :size="14" :stroke-width="3" /></span>
        <span class="ofs-copy">
          <b>{{ t('step_offer', 'Offer') }}</b>
          <small>{{ t('step_selected_deal', 'Selected deal') }}</small>
        </span>
      </button>

      <span class="ofs-rail" aria-hidden="true"><i style="width:100%"></i></span>

      <component
        :is="onCheckout ? 'button' : 'div'"
        class="ofs-node"
        :class="onCheckout ? 'is-done' : 'is-active'"
        :type="onCheckout ? 'button' : undefined"
        :aria-current="!onCheckout ? 'step' : undefined"
        @click="onCheckout ? emit('back-to-extras') : undefined"
      >
        <span class="ofs-dot">
          <Check v-if="onCheckout" :size="14" :stroke-width="3" />
          <SlidersHorizontal v-else :size="14" :stroke-width="2.2" />
        </span>
        <span class="ofs-copy">
          <b>{{ t('step_customize', 'Customize') }}</b>
          <small>{{ t('step_extras_options', 'Extras & options') }}</small>
        </span>
      </component>

      <span class="ofs-rail" aria-hidden="true"><i :style="{ width: onCheckout ? '100%' : '0%' }"></i></span>

      <div class="ofs-node" :class="onCheckout ? 'is-active' : 'is-idle'" :aria-current="onCheckout ? 'step' : undefined">
        <span class="ofs-dot"><CreditCard :size="14" :stroke-width="2.2" /></span>
        <span class="ofs-copy">
          <b>{{ t('step_checkout', 'Checkout') }}</b>
          <small>{{ t('step_secure_payment', 'Secure payment') }}</small>
        </span>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.ofs { --ease: cubic-bezier(0.22, 1, 0.36, 1); background: #fff; border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.ofs-track { display: flex; align-items: center; gap: 8px; padding: 12px 0; }
.ofs-node { display: flex; align-items: center; gap: 9px; background: none; border: 0; padding: 0; text-align: start; color: inherit; min-width: 0; }
button.ofs-node { cursor: pointer; }
button.ofs-node:hover .ofs-copy b { color: #1c4d66; }
.ofs-node:focus-visible { outline: 2px solid #22d3ee; outline-offset: 4px; border-radius: 10px; }
.ofs-dot { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; flex: none; background: #e2e8f0; color: #94a3b8; transition: background 0.3s var(--ease), color 0.3s var(--ease), box-shadow 0.3s var(--ease); }
.is-done .ofs-dot { background: #153b4f; color: #fff; }
.is-active .ofs-dot { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #22d3ee; box-shadow: 0 4px 12px rgba(21, 59, 79, 0.28); }
.ofs-copy { display: none; min-width: 0; }
.ofs-copy b { display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.8rem; font-weight: 700; color: #153b4f; line-height: 1.25; transition: color 0.3s var(--ease); }
.is-idle .ofs-copy b { color: #94a3b8; }
.ofs-copy small { display: block; font-size: 0.68rem; color: #94a3b8; line-height: 1.3; }
.ofs-rail { flex: 1; height: 2px; background: #e2e8f0; border-radius: 999px; overflow: hidden; min-width: 16px; }
.ofs-rail i { display: block; height: 100%; background: #153b4f; border-radius: 999px; transition: width 0.6s var(--ease); }

@media (min-width: 640px) { .ofs-copy { display: block; } .ofs-track { gap: 14px; } }
@media (prefers-reduced-motion: reduce) { .ofs-rail i { transition: none; } }
</style>
