<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    id: String,
    class: String,
    modelValue: String,
    placeholder: String,
});
</script>
<template>
    <textarea
        :id="id"
        :class="class"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
    ></textarea>
</template>



<style scoped>
/* Add any styles here */
</style>