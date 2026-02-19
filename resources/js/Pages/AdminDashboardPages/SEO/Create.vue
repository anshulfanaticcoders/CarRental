<template>
  <SeoForm :routeTargets="routeTargets" :available_locales="available_locales" />
</template>

<script setup>
import SeoForm from './Form.vue';
import { defineProps } from 'vue';

defineProps({
  routeTargets: Array,
  available_locales: Array,
});
</script>
