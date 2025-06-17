<template>
  <SeoForm :seoMeta="seoMeta" :translations="translations" />
</template>

<script setup>
import SeoForm from './Form.vue';
import { defineProps } from 'vue';

const props = defineProps({
  seoMeta: Object, // Passed from the controller
  translations: Object,
});
</script>
