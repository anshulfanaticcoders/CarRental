<template>
    <nav class="vr-pagination" aria-label="Vehicle list pagination">
        <div class="vr-pagination__summary" aria-live="polite">
            <span>Page</span>
            <strong>{{ safeCurrentPage }}</strong>
            <span>of</span>
            <strong>{{ safeTotalPages }}</strong>
        </div>

        <div class="vr-pagination__controls">
            <button
                type="button"
                class="vr-pagination__button vr-pagination__button--edge"
                :disabled="!canGoPrevious"
                aria-label="First page"
                @click="goToPage(1)"
            >
                <ChevronsLeft class="vr-pagination__icon" aria-hidden="true" />
            </button>

            <button
                type="button"
                class="vr-pagination__button vr-pagination__button--nav"
                :disabled="!canGoPrevious"
                aria-label="Previous page"
                @click="goToPage(safeCurrentPage - 1)"
            >
                <ChevronLeft class="vr-pagination__icon" aria-hidden="true" />
                <span>Previous</span>
            </button>

            <div class="vr-pagination__pages" role="list" aria-label="Pages">
                <template v-for="item in pageItems" :key="item.key">
                    <button
                        v-if="item.type === 'page'"
                        type="button"
                        class="vr-pagination__button vr-pagination__button--page"
                        :class="{
                            'is-active': item.page === safeCurrentPage,
                            'is-mobile-extra': isMobileExtraPage(item.page),
                        }"
                        :aria-current="item.page === safeCurrentPage ? 'page' : undefined"
                        :aria-label="`Go to page ${item.page}`"
                        @click="goToPage(item.page)"
                    >
                        {{ item.page }}
                    </button>

                    <span v-else class="vr-pagination__ellipsis" role="listitem" aria-hidden="true">
                        <MoreHorizontal class="vr-pagination__icon" />
                    </span>
                </template>
            </div>

            <button
                type="button"
                class="vr-pagination__button vr-pagination__button--nav"
                :disabled="!canGoNext"
                aria-label="Next page"
                @click="goToPage(safeCurrentPage + 1)"
            >
                <span>Next</span>
                <ChevronRight class="vr-pagination__icon" aria-hidden="true" />
            </button>

            <button
                type="button"
                class="vr-pagination__button vr-pagination__button--edge"
                :disabled="!canGoNext"
                aria-label="Last page"
                @click="goToPage(safeTotalPages)"
            >
                <ChevronsRight class="vr-pagination__icon" aria-hidden="true" />
            </button>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    MoreHorizontal,
} from 'lucide-vue-next';

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

const emit = defineEmits(['page-change']);

const safeTotalPages = computed(() => Math.max(1, Number(props.totalPages) || 1));

const safeCurrentPage = computed(() => {
    const current = Number(props.currentPage) || 1;

    return Math.min(Math.max(current, 1), safeTotalPages.value);
});

const canGoPrevious = computed(() => safeCurrentPage.value > 1);
const canGoNext = computed(() => safeCurrentPage.value < safeTotalPages.value);

const pageItems = computed(() => {
    const current = safeCurrentPage.value;
    const total = safeTotalPages.value;

    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => createPageItem(index + 1));
    }

    const visiblePages = new Set([1, total, current, current - 1, current + 1]);

    if (current <= 4) {
        [2, 3, 4, 5].forEach((page) => visiblePages.add(page));
    }

    if (current >= total - 3) {
        [total - 4, total - 3, total - 2, total - 1].forEach((page) => visiblePages.add(page));
    }

    const pages = Array.from(visiblePages)
        .filter((page) => page >= 1 && page <= total)
        .sort((a, b) => a - b);

    return pages.reduce((items, page, index) => {
        const previousPage = pages[index - 1];

        if (previousPage && page - previousPage > 1) {
            items.push({
                type: 'ellipsis',
                key: `ellipsis-${previousPage}-${page}`,
            });
        }

        items.push(createPageItem(page));

        return items;
    }, []);
});

function createPageItem(page) {
    return {
        type: 'page',
        page,
        key: `page-${page}`,
    };
}

function goToPage(page) {
    const targetPage = Number(page);

    if (
        !Number.isInteger(targetPage)
        || targetPage < 1
        || targetPage > safeTotalPages.value
        || targetPage === safeCurrentPage.value
    ) {
        return;
    }

    emit('page-change', targetPage);
}

function isMobileExtraPage(page) {
    return safeTotalPages.value > 5
        && page !== 1
        && page !== safeCurrentPage.value
        && page !== safeTotalPages.value;
}
</script>

<style scoped>
.vr-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
    padding: 0.85rem;
    border: 1px solid #dceef6;
    border-radius: 18px;
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98)),
        radial-gradient(circle at 8% 0%, rgba(34, 211, 238, 0.12), transparent 35%);
    box-shadow: 0 8px 24px rgba(21, 59, 79, 0.08);
}

.vr-pagination__summary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-width: max-content;
    min-height: 2.35rem;
    padding: 0.35rem 0.8rem;
    border: 1px solid rgba(176, 212, 230, 0.7);
    border-radius: 999px;
    background: #f0f8fc;
    color: #475569;
    font-size: 0.84rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.vr-pagination__summary strong {
    min-width: 2ch;
    color: #153b4f;
    text-align: center;
}

.vr-pagination__controls {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.45rem;
    min-width: 0;
}

.vr-pagination__pages {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-width: 0;
    padding-inline: 0.15rem;
}

.vr-pagination__button,
.vr-pagination__ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    height: 2.4rem;
    border-radius: 12px;
    font-size: 0.86rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    line-height: 1;
}

.vr-pagination__button {
    border: 1px solid #dceef6;
    background: #ffffff;
    color: #153b4f;
    box-shadow: 0 2px 6px rgba(21, 59, 79, 0.04);
    transition:
        border-color 180ms cubic-bezier(0.22, 1, 0.36, 1),
        background-color 180ms cubic-bezier(0.22, 1, 0.36, 1),
        box-shadow 180ms cubic-bezier(0.22, 1, 0.36, 1),
        color 180ms cubic-bezier(0.22, 1, 0.36, 1);
}

.vr-pagination__button--page,
.vr-pagination__button--edge {
    width: 2.4rem;
}

.vr-pagination__button--nav {
    gap: 0.35rem;
    min-width: 2.6rem;
    padding-inline: 0.8rem;
}

.vr-pagination__button:hover:not(:disabled) {
    border-color: #b0d4e6;
    background: #f0f8fc;
    color: #153b4f;
    box-shadow: 0 6px 16px rgba(21, 59, 79, 0.1);
}

.vr-pagination__button.is-active:hover:not(:disabled) {
    border-color: #153b4f;
    background: linear-gradient(135deg, #153b4f, #1c4d66);
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(21, 59, 79, 0.2);
}

.vr-pagination__button:focus-visible {
    outline: 3px solid rgba(34, 211, 238, 0.35);
    outline-offset: 2px;
}

.vr-pagination__button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
    box-shadow: none;
}

.vr-pagination__button.is-active {
    border-color: #153b4f;
    background: linear-gradient(135deg, #153b4f, #1c4d66);
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(21, 59, 79, 0.2);
}

.vr-pagination__ellipsis {
    width: 1.8rem;
    color: #94a3b8;
}

.vr-pagination__icon {
    width: 1rem;
    height: 1rem;
}

@media (max-width: 1023px) {
    .vr-pagination {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 16px;
    }

    .vr-pagination__summary {
        width: 100%;
        min-height: 2.2rem;
    }

    .vr-pagination__controls {
        justify-content: center;
        width: 100%;
    }

    .vr-pagination__button--nav span {
        display: none;
    }

    .vr-pagination__button--nav {
        width: 2.25rem;
        min-width: 2.25rem;
        padding-inline: 0;
    }
}

@media (max-width: 640px) {
    .vr-pagination {
        box-shadow: 0 10px 22px rgba(21, 59, 79, 0.08);
    }

    .vr-pagination__button,
    .vr-pagination__ellipsis {
        height: 2.15rem;
        border-radius: 11px;
        font-size: 0.78rem;
    }

    .vr-pagination__button--page {
        width: 2.15rem;
    }

    .vr-pagination__button--page.is-mobile-extra {
        display: none;
    }

    .vr-pagination__button--edge {
        display: none;
    }

    .vr-pagination__pages {
        max-width: min(100%, 15.5rem);
        overflow-x: auto;
        scrollbar-width: none;
    }

    .vr-pagination__pages::-webkit-scrollbar {
        display: none;
    }

    .vr-pagination__ellipsis {
        width: 1.4rem;
    }
}

@media (max-width: 360px) {
    .vr-pagination__controls {
        gap: 0.3rem;
    }

    .vr-pagination__pages {
        max-width: 13.5rem;
        gap: 0.25rem;
    }

    .vr-pagination__button,
    .vr-pagination__ellipsis {
        height: 2rem;
    }

    .vr-pagination__button--page,
    .vr-pagination__button--nav {
        width: 2rem;
        min-width: 2rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .vr-pagination__button {
        transition: none;
    }

}
</style>
