<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  seo: {
    type: Object,
    required: true,
  },
})

// Must be reactive: Inertia can reuse page components between locale switches.
const title = computed(() => props.seo?.title || 'Vrooem')
const description = computed(() => props.seo?.description || null)
const canonical = computed(() => props.seo?.canonical || null)
const image = computed(() => props.seo?.image || null)
const robots = computed(() => props.seo?.robots || null)
</script>

<template>
  <Head>
    <title>{{ title }}</title>
    <meta v-if="robots" name="robots" :content="robots" />
    <meta v-if="description" name="description" :content="description" />
    <link v-if="canonical" rel="canonical" :href="canonical" />

    <meta property="og:title" :content="title" />
    <meta v-if="description" property="og:description" :content="description" />
    <meta v-if="image" property="og:image" :content="image" />
    <meta v-if="canonical" property="og:url" :content="canonical" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" :content="title" />
    <meta v-if="description" name="twitter:description" :content="description" />
    <meta v-if="image" name="twitter:image" :content="image" />
  </Head>
</template>
