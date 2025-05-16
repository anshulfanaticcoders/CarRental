<template>
    <div v-if="links.length > 3" class="flex items-center justify-center mt-4 space-x-1">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-3 py-2 text-sm leading-4 text-gray-400 border rounded"
                v-html="link.label"
            />
            <button
                v-else
                class="px-3 py-2 text-sm leading-4 border rounded hover:bg-white focus:border-primary focus:text-primary"
                :class="{ 'bg-primary text-white': link.active }"
                @click.prevent="changePage(link.url)"
                v-html="link.label"
            />
        </template>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
    currentPage: Number, // Not strictly needed if using links array fully, but can be useful
    totalPages: Number, // Same as above
});

const emit = defineEmits(['page-change']);

const changePage = (url) => {
    if (url) {
        emit('page-change', url);
    }
};
</script>
