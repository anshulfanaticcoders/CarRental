<script setup>
import { defineProps } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  schema: {
    type: [Object, Array],
    required: true
  }
});
</script>

<template>
  <Head>
    <component :is="'script'" type="application/ld+json">
      {{ JSON.stringify(schema, null, 2) }}
    </component>
  </Head>
</template>
