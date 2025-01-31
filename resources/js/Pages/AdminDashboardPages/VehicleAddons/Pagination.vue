<template>
    <div class="flex items-center gap-2">
        <Button
            variant="outline"
            :disabled="currentPage === 1"
            @click="$emit('page-change', currentPage - 1)"
        >
            Previous
        </Button>
        <span class="text-sm">
            Page {{ currentPage }} of {{ totalPages }}
        </span>
        <Button
            variant="outline"
            :disabled="currentPage === totalPages"
            @click="$emit('page-change', currentPage + 1)"
        >
            Next
        </Button>
    </div>
</template>

<script setup>
import { Button } from "@/Components/ui/button";

defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
});

defineEmits(['page-change']);
</script>