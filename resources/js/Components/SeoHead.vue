<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  seo: {
    type: Object,
    required: true,
  },
})

const page = usePage()

// All values reactive: Inertia reuses page components across locale switches.
const title = computed(() => props.seo?.title || 'Vrooem')
const description = computed(() => props.seo?.description || null)
const canonical = computed(() => props.seo?.canonical || null)
const image = computed(() => props.seo?.image || null)
const imageAlt = computed(() => props.seo?.image_alt || title.value)
const robots = computed(() => props.seo?.robots || null)
const ogType = computed(() => props.seo?.og_type || 'website')
const siteName = computed(() => props.seo?.site_name || 'Vrooem')
const twitterSite = computed(() => props.seo?.twitter_site || '@vrooem')
const locale = computed(() => props.seo?.locale || page.props.locale || 'en')
const ogLocale = computed(() => String(locale.value).replace('-', '_'))
const alternates = computed(() => {
  const source = props.seo?.alternates || page.props.alternate_urls || []

  if (Array.isArray(source)) {
    return source
      .map((alternate) => ({
        hreflang: alternate?.hreflang || alternate?.locale,
        href: alternate?.href || alternate?.url,
      }))
      .filter((alternate) => alternate.hreflang && alternate.href)
  }

  return Object.entries(source)
    .map(([hreflang, href]) => ({ hreflang, href }))
    .filter((alternate) => alternate.hreflang && alternate.href)
})
</script>

<template>
  <Head>
    <title>{{ title }}</title>
    <meta v-if="robots" name="robots" :content="robots" head-key="robots" />
    <meta v-if="description" name="description" :content="description" head-key="description" />
    <link v-if="canonical" rel="canonical" :href="canonical" head-key="canonical" />

    <meta property="og:type" :content="ogType" head-key="og:type" />
    <meta property="og:locale" :content="ogLocale" head-key="og:locale" />
    <meta property="og:site_name" :content="siteName" head-key="og:site_name" />
    <meta property="og:title" :content="title" head-key="og:title" />
    <meta v-if="description" property="og:description" :content="description" head-key="og:description" />
    <meta v-if="image" property="og:image" :content="image" head-key="og:image" />
    <meta v-if="image" property="og:image:alt" :content="imageAlt" head-key="og:image:alt" />
    <meta v-if="canonical" property="og:url" :content="canonical" head-key="og:url" />

    <link
      v-for="alternate in alternates"
      :key="alternate.hreflang"
      rel="alternate"
      :hreflang="alternate.hreflang"
      :href="alternate.href"
      :head-key="`alternate:${alternate.hreflang}`"
    />

    <meta name="twitter:card" content="summary_large_image" head-key="twitter:card" />
    <meta name="twitter:site" :content="twitterSite" head-key="twitter:site" />
    <meta name="twitter:title" :content="title" head-key="twitter:title" />
    <meta v-if="description" name="twitter:description" :content="description" head-key="twitter:description" />
    <meta v-if="image" name="twitter:image" :content="image" head-key="twitter:image" />
  </Head>
</template>
