<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-6 w-[95%] ml-[1.5rem] pb-12">
            <div class="mt-[2rem] rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-gray-400">FAQ Studio</p>
                            <h1 class="text-2xl font-semibold text-gray-900">Bulk FAQ Editor</h1>
                            <p class="text-sm text-gray-500">Create and update multiple FAQs across locales at once.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Input v-model="search" placeholder="Search FAQs..." class="w-[260px]" />
                            <Button variant="outline" @click="addRow">Add Row</Button>
                            <Button :disabled="isSaving || hasIncompleteRows" @click="saveAll">
                                {{ isSaving ? 'Saving...' : 'Save All' }}
                            </Button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Locale View</span>
                        <button
                            v-for="locale in availableLocales"
                            :key="locale"
                            type="button"
                            @click="activeLocaleFilter = locale"
                            :class="[
                                'rounded-full border px-3 py-1 text-xs font-semibold uppercase transition',
                                activeLocaleFilter === locale
                                    ? 'border-[#153B4F] bg-[#153B4F] text-white'
                                    : 'border-gray-200 text-gray-500 hover:border-[#153B4F] hover:text-[#153B4F]'
                            ]"
                        >
                            {{ locale }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div>
                    <div>
                        <div
                            class="grid border-b border-gray-100 bg-[#153B4F0D]"
                            :style="gridTemplate"
                        >
                            <div class="px-4 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500">Row</div>
                            <div
                                v-for="locale in visibleLocales"
                                :key="`head-${locale}`"
                                class="px-4 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500"
                            >
                                {{ locale }}
                            </div>
                        </div>

                        <div
                            v-for="(row, rowIndex) in filteredRows"
                            :key="row.key"
                            class="grid border-b border-gray-100"
                            :style="gridTemplate"
                        >
                            <div class="px-4 py-4 bg-gray-50/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Row {{ rowIndex + 1 }}</p>
                                        <p class="text-xs text-gray-500">{{ row.id ? 'Existing FAQ' : 'New FAQ' }}</p>
                                    </div>
                                    <span
                                        v-if="getMissingLocales(row).length"
                                        class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase text-amber-700"
                                    >
                                        Missing {{ getMissingLocales(row).join(', ') }}
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-semibold uppercase text-emerald-700"
                                    >
                                        Complete
                                    </span>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <Button size="sm" variant="outline" @click="duplicateRow(row)">Duplicate</Button>
                                    <Button size="sm" variant="outline" @click="copyFromLocale(row, 'en')">
                                        Copy from EN
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openDeleteDialog(row)">Delete</Button>
                                </div>
                            </div>

                            <div
                                v-for="locale in visibleLocales"
                                :key="`${row.key}-${locale}`"
                                class="px-4 py-4"
                            >
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase text-gray-400">Question</p>
                                        <span class="text-[10px] font-semibold text-gray-400">{{ locale.toUpperCase() }}</span>
                                    </div>
                                    <Input
                                        v-model="row.translations[locale].question"
                                        :placeholder="`Question in ${locale.toUpperCase()}`"
                                        class="mt-2"
                                    />
                                    <p
                                        v-if="fieldError(rowIndex, locale, 'question')"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldError(rowIndex, locale, 'question') }}
                                    </p>

                                    <div class="mt-4 flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase text-gray-400">Answer</p>
                                    </div>
                                    <Textarea
                                        v-model="row.translations[locale].answer"
                                        :placeholder="`Answer in ${locale.toUpperCase()}`"
                                        class="mt-2 min-h-[120px] max-h-[200px] overflow-y-auto"
                                    />
                                    <p
                                        v-if="fieldError(rowIndex, locale, 'answer')"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldError(rowIndex, locale, 'answer') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredRows.length === 0" class="px-6 py-16 text-center text-gray-500">
                            No FAQs match your search. Try adding a new row.
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-6">
                <div class="rounded-2xl border border-gray-200 bg-white/95 shadow-lg backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-gray-400">Progress</p>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ completedRows }} complete / {{ rowsWithContent }} total
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button variant="outline" @click="addRow">Add Row</Button>
                            <Button :disabled="isSaving || hasIncompleteRows" @click="saveAll">
                                {{ isSaving ? 'Saving...' : 'Save All' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete FAQ row?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will remove the FAQ. If it already exists, it will be deleted from the database.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import { useToast } from "vue-toastification";

const props = defineProps({
    faqs: Array,
    search: String,
    available_locales: Array,
    current_locale: String,
    errors: Object,
});

const toast = useToast();

const availableLocales = computed(() => props.available_locales || []);
const search = ref(props.search || "");
const isSaving = ref(false);
const activeLocaleFilter = ref(props.current_locale || availableLocales.value[0] || "en");
const isDeleteDialogOpen = ref(false);
const rowToDelete = ref(null);

const createRowKey = () => `row_${Date.now()}_${Math.random().toString(16).slice(2)}`;

const createEmptyTranslations = () => {
    const translations = {};
    availableLocales.value.forEach(locale => {
        translations[locale] = { question: "", answer: "" };
    });
    return translations;
};

const mapFaqToRow = (faq) => {
    const translations = createEmptyTranslations();
    (faq?.translations || []).forEach((translation) => {
        if (translation?.locale && translations[translation.locale]) {
            translations[translation.locale] = {
                question: translation.question || "",
                answer: translation.answer || "",
            };
        }
    });

    return {
        key: createRowKey(),
        id: faq?.id || null,
        translations,
    };
};

const faqRows = ref(
    (props.faqs || []).map(mapFaqToRow)
);

if (faqRows.value.length === 0) {
    faqRows.value.push({ key: createRowKey(), id: null, translations: createEmptyTranslations() });
}

const visibleLocales = computed(() => {
    return availableLocales.value.includes(activeLocaleFilter.value)
        ? [activeLocaleFilter.value]
        : availableLocales.value.slice(0, 1);
});

const gridTemplate = computed(() => {
    const columns = visibleLocales.value.length || 1;
    return `grid-template-columns: 220px repeat(${columns}, minmax(0, 1fr));`;
});

const rowHasContent = (row) => {
    return availableLocales.value.some(locale => {
        const entry = row.translations[locale];
        return entry?.question?.trim() || entry?.answer?.trim();
    });
};

const getMissingLocales = (row) => {
    return availableLocales.value
        .filter(locale => {
            const entry = row.translations[locale];
            return !entry?.question?.trim() || !entry?.answer?.trim();
        })
        .map(locale => locale.toUpperCase());
};

const isRowComplete = (row) => rowHasContent(row) && getMissingLocales(row).length === 0;

const filteredRows = computed(() => {
    if (!search.value.trim()) {
        return faqRows.value;
    }

    const term = search.value.toLowerCase();
    return faqRows.value.filter(row => {
        return availableLocales.value.some(locale => {
            const entry = row.translations[locale];
            return (
                entry.question?.toLowerCase().includes(term) ||
                entry.answer?.toLowerCase().includes(term)
            );
        });
    });
});

const rowsWithContent = computed(() => faqRows.value.filter(rowHasContent).length);
const completedRows = computed(() => faqRows.value.filter(isRowComplete).length);
const hasIncompleteRows = computed(() => {
    return faqRows.value.some(row => rowHasContent(row) && !isRowComplete(row));
});

const addRow = () => {
    faqRows.value.push({ key: createRowKey(), id: null, translations: createEmptyTranslations() });
};

const duplicateRow = (row) => {
    const clonedTranslations = JSON.parse(JSON.stringify(row.translations));
    faqRows.value.push({ key: createRowKey(), id: null, translations: clonedTranslations });
};

const copyFromLocale = (row, sourceLocale) => {
    if (!row.translations[sourceLocale]) return;
    const source = row.translations[sourceLocale];
    availableLocales.value.forEach(locale => {
        if (locale === sourceLocale) return;
        row.translations[locale] = {
            question: source.question,
            answer: source.answer,
        };
    });
};

const openDeleteDialog = (row) => {
    rowToDelete.value = row;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    const row = rowToDelete.value;
    if (!row) return;

    if (row.id) {
        router.delete(route("admin.settings.faq.destroy", row.id), {
            onSuccess: (page) => {
                if (page.props.flash?.success) {
                    toast.success(page.props.flash.success);
                }
                faqRows.value = faqRows.value.filter(item => item.key !== row.key);
                if (faqRows.value.length === 0) {
                    addRow();
                }
                isDeleteDialogOpen.value = false;
            },
            onError: () => {
                toast.error("Failed to delete FAQ.");
                isDeleteDialogOpen.value = false;
            },
        });
    } else {
        faqRows.value = faqRows.value.filter(item => item.key !== row.key);
        if (faqRows.value.length === 0) {
            addRow();
        }
        isDeleteDialogOpen.value = false;
    }
};

const fieldError = (rowIndex, locale, field) => {
    if (!props.errors) return null;
    return props.errors[`rows.${rowIndex}.translations.${locale}.${field}`] || null;
};

const saveAll = () => {
    const rowsToSave = faqRows.value
        .filter(rowHasContent)
        .map(row => ({ id: row.id, translations: row.translations }));

    if (!rowsToSave.length) {
        toast.warning("Add at least one FAQ before saving.");
        return;
    }

    isSaving.value = true;

    router.post(route("admin.settings.faq.bulk"), { rows: rowsToSave }, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                toast.success(page.props.flash.success);
            }
            router.reload({ only: ["faqs"] });
        },
        onError: (formErrors) => {
            Object.values(formErrors).forEach(error => toast.error(error));
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

watch(() => props.faqs, (newFaqs) => {
    faqRows.value = (newFaqs || []).map(mapFaqToRow);
    if (faqRows.value.length === 0) {
        addRow();
    }
}, { deep: true });
</script>

<style scoped>
</style>
