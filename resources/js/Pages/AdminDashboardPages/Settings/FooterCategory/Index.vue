<template>
    <AdminDashboardLayout>
        <div class="mx-6 my-8 max-w-4xl space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Footer Setup</p>
                        <h1 class="mt-2 text-2xl font-semibold text-gray-900">Footer Categories</h1>
                        <p class="mt-2 max-w-2xl text-sm text-gray-500">
                            Choose which vehicle categories appear in the footer. Changes save automatically as soon as you toggle a category.
                        </p>
                    </div>

                    <div class="grid min-w-[180px] grid-cols-2 gap-3 rounded-2xl bg-[#153B4F0D] p-4">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-widest text-gray-400">Selected</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ selectedCount }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-widest text-gray-400">Total</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ categories.length }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-500">Category Visibility</h2>
                </div>

                <div v-if="categories.length" class="divide-y divide-gray-100">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="flex items-center justify-between gap-4 px-6 py-4"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ category.name }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ isCategorySelected(category.id) ? 'Visible in footer' : 'Hidden from footer' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                v-if="savingCategoryIds.includes(category.id)"
                                class="text-xs font-medium text-amber-600"
                            >
                                Saving...
                            </span>
                            <span
                                v-else
                                class="text-xs font-medium"
                                :class="isCategorySelected(category.id) ? 'text-emerald-600' : 'text-gray-400'"
                            >
                                {{ isCategorySelected(category.id) ? 'On' : 'Off' }}
                            </span>

                            <Switch
                                :checked="isCategorySelected(category.id)"
                                :disabled="savingCategoryIds.includes(category.id)"
                                @update:checked="(checked) => toggleCategory(category.id, checked)"
                            />
                        </div>
                    </div>
                </div>

                <div v-else class="px-6 py-12 text-center text-sm text-gray-500">
                    No categories available.
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Switch } from '@/Components/ui/switch';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    selectedCategories: {
        type: Array,
        default: () => [],
    },
});

const selectedCategoryIds = ref([...(props.selectedCategories || [])]);
const savingCategoryIds = ref([]);

const selectedCount = computed(() => selectedCategoryIds.value.length);

const isCategorySelected = (categoryId) => selectedCategoryIds.value.includes(categoryId);

const buildUpdatedSelection = (categoryId, checked) => {
    const current = new Set(selectedCategoryIds.value);

    if (checked) {
        current.add(categoryId);
    } else {
        current.delete(categoryId);
    }

    return Array.from(current);
};

const toggleCategory = (categoryId, checked) => {
    const previousSelection = [...selectedCategoryIds.value];
    const nextSelection = buildUpdatedSelection(categoryId, checked);

    selectedCategoryIds.value = nextSelection;
    savingCategoryIds.value = [...savingCategoryIds.value, categoryId];

    router.post(route('admin.settings.footer-categories.update'), {
        selected_categories: nextSelection,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            toast.success(`Footer category ${checked ? 'enabled' : 'disabled'} successfully`);
        },
        onError: () => {
            selectedCategoryIds.value = previousSelection;
            toast.error('Failed to update footer categories');
        },
        onFinish: () => {
            savingCategoryIds.value = savingCategoryIds.value.filter((id) => id !== categoryId);
        },
    });
};
</script>
