<template>
    <div class="flex items-center gap-2" v-if="totalPages > 1">
        <Button
            variant="outline"
            :disabled="currentPage === 1"
            @click="changePage(currentPage - 1)"
        >
            Previous
        </Button>

        <template v-for="pageNumber in visiblePageNumbers" :key="pageNumber">
            <Button
                v-if="typeof pageNumber === 'number'"
                variant="outline"
                :class="{ 'bg-primary text-primary-foreground': currentPage === pageNumber }"
                @click="changePage(pageNumber)"
            >
                {{ pageNumber }}
            </Button>
            <span v-else class="text-sm px-2">
                {{ pageNumber }}
            </span>
        </template>

        <Button
            variant="outline"
            :disabled="currentPage === totalPages"
            @click="changePage(currentPage + 1)"
        >
            Next
        </Button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Button } from "@/Components/ui/button";

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    maxVisibleButtons: {
        type: Number,
        default: 5, // Max number of page buttons to show (excluding prev/next and ellipses)
    },
});

const emit = defineEmits(['page-change']);

const changePage = (page) => {
    if (page >= 1 && page <= props.totalPages) {
        emit('page-change', page);
    }
};

const visiblePageNumbers = computed(() => {
    const max = props.maxVisibleButtons;
    const current = props.currentPage;
    const total = props.totalPages;
    const half = Math.floor(max / 2);

    if (total <= max) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let startPage = Math.max(1, current - half);
    let endPage = Math.min(total, current + half - (max % 2 === 0 ? 1 : 0));

    if (current <= half) {
        endPage = max;
        startPage = 1;
    } else if (current + half > total) {
        startPage = total - max + 1;
        endPage = total;
    }

    const pages = [];

    if (startPage > 1) {
        pages.push(1);
        if (startPage > 2) {
            pages.push('...');
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }

    if (endPage < total) {
        if (endPage < total - 1) {
            pages.push('...');
        }
        pages.push(total);
    }

    return pages;
});

</script>
