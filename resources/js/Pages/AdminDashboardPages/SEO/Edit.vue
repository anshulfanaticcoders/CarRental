<template>
  <SeoForm
    :seoMeta="seoMeta"
    :translations="translations"
    :routeTargets="routeTargets"
    :available_locales="available_locales"
    :targetKey="targetKey"
    :country="country"
  />
</template>

<script setup>
import SeoForm from './Form.vue';
import { defineProps } from 'vue';

const props = defineProps({
  seoMeta: Object, // Passed from the controller
  translations: Object,
  routeTargets: Array,
  available_locales: Array,
  targetKey: String,
  country: String,
});
</script>
