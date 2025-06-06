<script setup>
import { defineProps, onMounted, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  schema: {
    type: Object,
    required: true
  }
});

// Using a ref to hold the script element might not be necessary
// as Inertia's Head component handles updates.
// However, if direct manipulation or specific lifecycle hooks were needed,
// this is how you might approach it. For now, we'll rely on Head's reactivity.

// Note: The guide suggested a <head> tag directly in the template.
// However, with Inertia.js, the recommended way to manage head elements
// is through its own <Head> component for better integration and SSR compatibility.
</script>

<template>
  <Head>
    <component :is="'script'" type="application/ld+json">
      {{ JSON.stringify(schema, null, 2) }}
    </component>
  </Head>
</template>