<template>
    <div class="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="text-sm text-gray-600">
            <span class="font-medium">{{ currentPage }}</span>
            <span class="text-gray-400 mx-1">of</span>
            <span class="font-medium">{{ totalPages }}</span>
            <span class="text-gray-500 ml-1">pages</span>
        </div>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage === 1"
                @click="$emit('page-change', currentPage - 1)"
                class="flex items-center gap-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
                <ChevronLeft class="w-4 h-4" />
                Previous
            </Button>

            <div class="flex items-center gap-1">
                <Button
                    v-for="page in visiblePages"
                    :key="page"
                    :variant="page === currentPage ? 'default' : 'outline'"
                    size="sm"
                    @click="$emit('page-change', page)"
                    :class="[
                        'w-8 h-8 p-0 font-medium transition-all duration-200',
                        page === currentPage
                            ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm'
                            : 'bg-white border-gray-300 hover:bg-gray-50 text-gray-700'
                    ]"
                >
                    {{ page }}
                </Button>
            </div>

            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage === totalPages"
                @click="$emit('page-change', currentPage + 1)"
                class="flex items-center gap-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
                Next
                <ChevronRight class="w-4 h-4" />
            </Button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Button } from "@/Components/ui/button";
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
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

// Compute visible page numbers
const visiblePages = computed(() => {
    const current = props.currentPage;
    const total = props.totalPages;
    const delta = 2; // Number of pages to show on each side

    const range = [];
    const rangeWithDots = [];
    let l;

    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
            range.push(i);
        }
    }

    range.forEach((i) => {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1);
            } else if (i - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        l = i;
    });

    return rangeWithDots.filter(page => page !== '...');
});
</script>