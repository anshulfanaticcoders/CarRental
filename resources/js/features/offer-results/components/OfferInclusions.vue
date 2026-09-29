<script setup lang="ts">
import { useOfferTranslations } from '../composables/useOfferTranslations'

export type InclusionGroup = {
  key: string
  title: string
  facts: Array<{ key: string, label: string, detail: string }>
}

defineProps<{ groups: InclusionGroup[] }>()

const { t } = useOfferTranslations()
</script>

<template>
  <section class="ofn" :aria-label="t('what_this_offer_includes', 'What this offer includes')">
    <div class="ofn-head">
      <p class="ofn-label">{{ t('offer_details', 'Offer details') }}</p>
      <h2>{{ t('what_this_offer_includes', 'What this offer includes') }}</h2>
      <p class="ofn-lede">{{ t('offer_includes_single_place', 'Package, protection, extras, and charges are grouped here for a quick scan.') }}</p>
    </div>

    <div v-for="group in groups" :key="group.key" class="ofn-group">
      <h3>{{ group.title }}</h3>
      <dl class="ofn-ledger">
        <div v-for="fact in group.facts" :key="fact.key" class="ofn-row">
          <dt>{{ fact.label }}</dt>
          <dd>{{ fact.detail }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<style scoped>
.ofn-head { margin-bottom: 22px; }
.ofn-label { margin: 0 0 6px; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; color: #94a3b8; }
.ofn-head h2 { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 700; color: #153b4f; line-height: 1.25; }
.ofn-lede { margin: 8px 0 0; font-size: 0.85rem; color: #64748b; line-height: 1.6; max-width: 56ch; }

.ofn-group + .ofn-group { margin-top: 26px; }
.ofn-group h3 {
  margin: 0 0 10px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.12em; color: #2d7294;
}
.ofn-ledger { margin: 0; border-top: 1px solid #e2e8f0; }
.ofn-row {
  display: grid; gap: 2px 18px; padding: 11px 0; border-bottom: 1px solid #eef2f6; min-width: 0;
}
.ofn-row dt { font-size: 0.86rem; font-weight: 600; color: #334155; overflow-wrap: anywhere; }
.ofn-row dd { margin: 0; font-size: 0.82rem; color: #64748b; line-height: 1.5; overflow-wrap: anywhere; }

@media (min-width: 640px) {
  .ofn-row { grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); align-items: baseline; }
  .ofn-row dd { text-align: end; }
}
</style>
