<template>
    <!-- Mobile Pagination (Stacked Layout) -->
    <div class="lg:hidden w-full">
        <div class="flex flex-col gap-4 p-3 sm:p-4 bg-white rounded-lg border border-gray-200 shadow-sm w-full">
            <!-- Page Info -->
            <div class="text-center text-xs sm:text-sm text-gray-600">
                <span class="font-medium">{{ currentPage }}</span>
                <span class="text-gray-400 mx-1">of</span>
                <span class="font-medium">{{ totalPages }}</span>
                <span class="text-gray-500 ml-1">pages</span>
            </div>

            <!-- Page Numbers -->
            <div class="flex items-center justify-center gap-1 flex-wrap">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="currentPage === 1"
                    @click="$emit('page-change', currentPage - 1)"
                    class="flex items-center justify-center gap-1 p-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                >
                    <ChevronLeft class="w-3 h-3 sm:w-4 sm:h-4" />
                </Button>

                <Button
                    v-for="page in visiblePages"
                    :key="page"
                    :variant="page === currentPage ? 'default' : 'outline'"
                    size="sm"
                    @click="$emit('page-change', page)"
                    :class="[
                        'w-7 h-7 sm:w-8 sm:h-8 p-0 font-medium transition-all duration-200 text-xs sm:text-sm flex-shrink-0',
                        page === currentPage
                            ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm'
                            : 'bg-white border-gray-300 hover:bg-gray-50 text-gray-700'
                    ]"
                >
                    {{ page }}
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="currentPage === totalPages"
                    @click="$emit('page-change', currentPage + 1)"
                    class="flex items-center justify-center gap-1 p-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                >
                    <ChevronRight class="w-3 h-3 sm:w-4 sm:h-4" />
                </Button>
            </div>

            <!-- Navigation Labels (Optional on Mobile) -->
            <div class="flex justify-between text-xs text-gray-500">
                <span>Page {{ currentPage }} of {{ totalPages }}</span>
            </div>
        </div>
    </div>

    <!-- Desktop/Tablet Pagination (Horizontal Layout) -->
    <div class="hidden lg:flex sm:flex items-center justify-between p-3 sm:p-4 bg-white rounded-lg border border-gray-200 shadow-sm w-full">
        <div class="text-sm text-gray-600">
            <span class="font-medium">{{ currentPage }}</span>
            <span class="text-gray-400 mx-1">of</span>
            <span class="font-medium">{{ totalPages }}</span>
            <span class="text-gray-500 ml-1">pages</span>
        </div>

        <div class="flex items-center gap-1 sm:gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage === 1"
                @click="$emit('page-change', currentPage - 1)"
                class="hidden sm:flex items-center gap-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
                <ChevronLeft class="w-4 h-4" />
                <span class="hidden sm:inline">Previous</span>
            </Button>

            <!-- Mobile Previous Button (Icon Only) -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage === 1"
                @click="$emit('page-change', currentPage - 1)"
                class="sm:hidden flex items-center justify-center p-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
                <ChevronLeft class="w-4 h-4" />
            </Button>

            <div class="flex items-center gap-0.5 sm:gap-1">
                <Button
                    v-for="page in visiblePages"
                    :key="page"
                    :variant="page === currentPage ? 'default' : 'outline'"
                    size="sm"
                    @click="$emit('page-change', page)"
                    :class="[
                        'w-8 h-8 p-0 font-medium transition-all duration-200 text-sm flex-shrink-0',
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
                class="hidden sm:flex items-center gap-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
                <span class="hidden sm:inline">Next</span>
                <ChevronRight class="w-4 h-4" />
            </Button>

            <!-- Mobile Next Button (Icon Only) -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage === totalPages"
                @click="$emit('page-change', currentPage + 1)"
                class="sm:hidden flex items-center justify-center p-2 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
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

<style scoped>
/* Mobile pagination improvements */
@media (max-width: 640px) {
    .pagination-container {
        padding: 0.75rem;
    }

    .pagination-page-btn {
        min-width: 2rem;
        min-height: 2rem;
        touch-action: manipulation;
    }

    .pagination-nav-btn {
        min-width: 2.5rem;
        min-height: 2.5rem;
        touch-action: manipulation;
    }

    .pagination-info {
        font-size: 0.75rem;
        line-height: 1.25;
    }
}

/* Tablet pagination improvements */
@media (min-width: 641px) and (max-width: 1023px) {
    .pagination-page-btn {
        min-width: 2.25rem;
        min-height: 2.25rem;
    }

    .pagination-nav-btn {
        min-width: 2.75rem;
        min-height: 2.75rem;
    }
}

/* Desktop pagination improvements */
@media (min-width: 1024px) {
    .pagination-page-btn {
        min-width: 2.5rem;
        min-height: 2.5rem;
    }

    .pagination-nav-btn {
        min-width: 3rem;
        min-height: 2.5rem;
    }
}

/* Touch-friendly pagination */
@media (hover: none) and (pointer: coarse) {
    .pagination-page-btn:active {
        transform: scale(0.95);
    }

    .pagination-nav-btn:active {
        transform: scale(0.95);
    }
}

/* Ensure buttons don't overflow on small screens */
.pagination-btn-group {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    max-width: 100%;
}

/* Responsive text for pagination */
.pagination-text {
    font-size: clamp(0.75rem, 2vw, 0.875rem);
}

/* Active page styling */
.pagination-active {
    background-color: rgb(59, 130, 246) !important;
    color: white !important;
    border-color: rgb(59, 130, 246) !important;
}

/* Disabled state improvements */
.pagination-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    touch-action: none;
}

/* Focus states for accessibility */
.pagination-btn:focus {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}

/* Smooth transitions */
.pagination-btn {
    transition: all 0.15s ease-in-out;
}

/* Container responsive adjustments */
.pagination-wrapper {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

/* Ensure pagination containers are full width */
.pagination-mobile,
.pagination-desktop {
    width: 100%;
}

@media (max-width: 640px) {
    .pagination-wrapper {
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .pagination-mobile {
        width: 100%;
    }
}

/* Full width container styles */
.pagination-full-width {
    width: 100%;
    margin: 0;
    padding: 0;
}

/* Ensure proper width for all pagination elements */
.pagination-container-full {
    width: 100%;
    max-width: none;
}
</style>