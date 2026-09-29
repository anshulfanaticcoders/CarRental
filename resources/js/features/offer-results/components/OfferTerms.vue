<script setup lang="ts">
import { CalendarX2, ChevronDown } from 'lucide-vue-next'
import { useOfferTranslations } from '../composables/useOfferTranslations'

export type TermEntry = { key: string, title: string, body: string }

// Anything longer reads as a wall of text in the rail-width column, so it is
// collapsed behind a native disclosure. The value itself is never dropped.
const LONG_TERM_LENGTH = 220

defineProps<{
  cancellation: string
  entries: TermEntry[]
}>()

const { t } = useOfferTranslations()
</script>

<template>
  <section class="oft" :aria-label="t('rental_conditions', 'Rental conditions')">
    <div class="oft-head">
      <p class="oft-label">{{ t('rental_conditions', 'Rental conditions') }}</p>
      <h2>{{ t('cancellation_and_terms', 'Cancellation and supplier terms') }}</h2>
    </div>

    <div class="oft-cancellation">
      <CalendarX2 :size="17" :stroke-width="1.9" />
      <div>
        <p class="oft-cancellation-label">{{ t('cancellation', 'Cancellation') }}</p>
        <p class="oft-cancellation-value">{{ cancellation }}</p>
      </div>
    </div>

    <div v-for="entry in entries" :key="entry.key" class="oft-entry">
      <details v-if="entry.body.length > LONG_TERM_LENGTH">
        <summary>
          <span>{{ entry.title }}</span>
          <ChevronDown class="oft-chevron" :size="16" :stroke-width="2" />
        </summary>
        <p>{{ entry.body }}</p>
      </details>
      <template v-else>
        <p class="oft-entry-title">{{ entry.title }}</p>
        <p class="oft-entry-body">{{ entry.body }}</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.oft { --ease: cubic-bezier(0.22, 1, 0.36, 1); }
.oft-head { margin-bottom: 18px; }
.oft-label { margin: 0 0 6px; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; color: #94a3b8; }
.oft-head h2 { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 700; color: #153b4f; line-height: 1.25; }

.oft-cancellation { display: flex; gap: 11px; padding: 13px 15px; border-radius: 14px; background: #f0f8fc; border: 1px solid #dceef6; }
.oft-cancellation svg { flex: none; margin-top: 2px; color: #2d7294; }
.oft-cancellation-label { margin: 0; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #2d7294; }
.oft-cancellation-value { margin: 3px 0 0; font-size: 0.85rem; font-weight: 600; color: #153b4f; line-height: 1.5; overflow-wrap: anywhere; }

.oft-entry { border-bottom: 1px solid #eef2f6; padding: 12px 0; }
.oft-entry:first-of-type { margin-top: 6px; border-top: 1px solid #eef2f6; }
.oft-entry-title, .oft-entry summary span { font-size: 0.85rem; font-weight: 600; color: #334155; overflow-wrap: anywhere; }
.oft-entry-title { margin: 0; }
.oft-entry-body, .oft-entry details p { margin: 4px 0 0; font-size: 0.8rem; line-height: 1.6; color: #64748b; overflow-wrap: anywhere; }

.oft-entry summary {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; cursor: pointer;
  list-style: none; color: #334155;
}
.oft-entry summary::-webkit-details-marker { display: none; }
.oft-entry summary:focus-visible { outline: 2px solid #22d3ee; outline-offset: 3px; border-radius: 6px; }
.oft-chevron { flex: none; color: #94a3b8; transition: transform 0.3s var(--ease); }
.oft-entry details[open] .oft-chevron { transform: rotate(180deg); }

@media (prefers-reduced-motion: reduce) { .oft-chevron { transition: none; } }
</style>
