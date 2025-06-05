<script setup>
import SchemaForm from './Form.vue';
import { defineProps } from 'vue';

const props = defineProps({
  schema: Object, // Passed from the controller's edit method
});
</script>

<template>
  <SchemaForm :schema="props.schema" />
</template>
