<template>
  <HeaderFooterForm :headerFooterScript="headerFooterScript" />
</template>

<script setup>
import HeaderFooterForm from './Form.vue';
import { defineProps } from 'vue';

const props = defineProps({
  headerFooterScript: Object, // Passed from the controller
});
</script>
