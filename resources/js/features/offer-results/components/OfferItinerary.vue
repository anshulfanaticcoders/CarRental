<script setup lang="ts">
import { Fuel, Route, Wallet } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

defineProps<{
  durationText: string
  pickupWhen: string
  pickupName: string
  pickupAddress: string
  pickupInstructions?: string | null
  pickupContact?: string | null
  returnWhen: string
  returnName: string
  returnAddress: string
  returnInstructions?: string | null
  fuelPolicy: string
  mileage: string
  deposit: string
}>()

const { t } = useOfferTranslations()
</script>

<template>
  <section class="ofi" :aria-label="t('trip_details', 'Trip details')">
    <div class="ofi-head">
      <p class="ofi-label">{{ t('trip_details', 'Trip details') }}</p>
      <h2>{{ t('pickup_return_and_policies', 'Pickup, return, and counter policies') }}</h2>
      <span class="ofi-duration">{{ durationText }}</span>
    </div>

    <ol class="ofi-spine">
      <li class="ofi-stop">
        <span class="ofi-marker" aria-hidden="true"></span>
        <p class="ofi-stop-label">{{ t('pickup_office', 'Pickup office') }}</p>
        <p class="ofi-stop-when">{{ pickupWhen }}</p>
        <p class="ofi-stop-name">{{ pickupName }}</p>
        <p class="ofi-stop-note">{{ pickupAddress }}</p>
        <p v-if="pickupContact" class="ofi-stop-note">{{ pickupContact }}</p>
        <p v-if="pickupInstructions" class="ofi-stop-hint">
          <span class="ofi-stop-hint-label">{{ t('pickup_instructions', 'Pickup instructions') }}</span>
          {{ pickupInstructions }}
        </p>
      </li>
      <li class="ofi-stop">
        <span class="ofi-marker is-end" aria-hidden="true"></span>
        <p class="ofi-stop-label">{{ t('return_office', 'Return office') }}</p>
        <p class="ofi-stop-when">{{ returnWhen }}</p>
        <p class="ofi-stop-name">{{ returnName }}</p>
        <p class="ofi-stop-note">{{ returnAddress }}</p>
        <p v-if="returnInstructions" class="ofi-stop-hint">
          <span class="ofi-stop-hint-label">{{ t('return_instructions', 'Return instructions') }}</span>
          {{ returnInstructions }}
        </p>
      </li>
    </ol>

    <dl class="ofi-policies">
      <div class="ofi-policy">
        <Fuel :size="16" :stroke-width="1.9" />
        <dt>{{ t('fuel_policy', 'Fuel policy') }}</dt>
        <dd>{{ fuelPolicy }}</dd>
      </div>
      <div class="ofi-policy">
        <Route :size="16" :stroke-width="1.9" />
        <dt>{{ t('mileage', 'Mileage') }}</dt>
        <dd>{{ mileage }}</dd>
      </div>
      <div class="ofi-policy">
        <Wallet :size="16" :stroke-width="1.9" />
        <dt>{{ t('security_deposit', 'Security deposit') }}</dt>
        <dd>{{ deposit }}</dd>
      </div>
    </dl>
  </section>
</template>

<style scoped>
.ofi { --ease: cubic-bezier(0.22, 1, 0.36, 1); }
.ofi-head { margin-bottom: 20px; }
.ofi-label { margin: 0 0 6px; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; color: #94a3b8; }
.ofi-head h2 { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 700; color: #153b4f; line-height: 1.25; }
.ofi-duration { display: inline-block; margin-top: 8px; font-size: 0.78rem; font-weight: 600; color: #2d7294; }

.ofi-spine { list-style: none; margin: 0; padding: 0; display: grid; gap: 22px; }
.ofi-stop { position: relative; padding-inline-start: 26px; min-width: 0; }
.ofi-stop::before { content: ''; position: absolute; inset-inline-start: 5px; top: 16px; bottom: -22px; width: 1px; background: linear-gradient(180deg, #b0d4e6, rgba(176, 212, 230, 0)); }
.ofi-stop:last-child::before { display: none; }
.ofi-marker { position: absolute; inset-inline-start: 0; top: 5px; width: 11px; height: 11px; border-radius: 50%; background: #fff; border: 3px solid #153b4f; }
.ofi-marker.is-end { border-color: #22d3ee; }
.ofi-stop-label { margin: 0; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #94a3b8; }
.ofi-stop-when { margin: 4px 0 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 700; color: #153b4f; }
.ofi-stop-name { margin: 4px 0 0; font-size: 0.88rem; font-weight: 600; color: #334155; overflow-wrap: anywhere; }
.ofi-stop-note { margin: 2px 0 0; font-size: 0.8rem; color: #64748b; line-height: 1.5; overflow-wrap: anywhere; }

.ofi-stop-hint { margin: 8px 0 0; padding: 9px 11px; border-radius: 10px; background: #f0f8fc; border: 1px solid #dceef6; font-size: 0.78rem; line-height: 1.55; color: #334155; overflow-wrap: anywhere; }
.ofi-stop-hint-label { display: block; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #2d7294; margin-bottom: 2px; }

.ofi-policies { margin: 24px 0 0; display: grid; gap: 1px; background: #e2e8f0; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
.ofi-policy { display: flex; align-items: baseline; gap: 9px; padding: 12px 14px; background: #f8fafc; min-width: 0; }
.ofi-policy svg { color: #2d7294; flex: none; align-self: center; }
.ofi-policy dt { font-size: 0.74rem; color: #64748b; white-space: nowrap; }
.ofi-policy dd { margin: 0; margin-inline-start: auto; font-size: 0.82rem; font-weight: 600; color: #153b4f; text-align: end; overflow-wrap: anywhere; }

@media (min-width: 768px) {
  .ofi-head { display: grid; grid-template-columns: 1fr auto; align-items: end; column-gap: 16px; }
  .ofi-label { grid-column: 1; }
  .ofi-head h2 { grid-column: 1; }
  .ofi-duration { grid-column: 2; grid-row: 1 / span 2; margin-top: 0; }
  .ofi-spine { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 28px; }
  .ofi-stop::before { top: 5px; bottom: auto; inset-inline-start: 16px; inset-inline-end: -28px; width: auto; height: 1px; background: #dceef6; }
  .ofi-policies { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
</style>
