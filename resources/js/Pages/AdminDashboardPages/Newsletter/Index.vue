<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Newsletter Subscribers</h1>
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                    Total: {{ statusCounts?.total || 0 }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Subscribed</div>
                    <div class="text-2xl font-semibold text-slate-900">{{ statusCounts?.subscribed || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Pending</div>
                    <div class="text-2xl font-semibold text-slate-900">{{ statusCounts?.pending || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Unsubscribed</div>
                    <div class="text-2xl font-semibold text-slate-900">{{ statusCounts?.unsubscribed || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Total</div>
                    <div class="text-2xl font-semibold text-slate-900">{{ statusCounts?.total || 0 }}</div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 items-center justify-center">
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search by email"
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="status">
                        <SelectTrigger class="w-44 h-12">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL_STATUS_VALUE">All Status</SelectItem>
                            <SelectItem value="subscribed">Subscribed</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="unsubscribed">Unsubscribed</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div v-if="subscriptions?.data?.length" class="flex flex-wrap items-center gap-3 rounded-xl border bg-white px-4 py-3">
                <Select v-model="bulkAction">
                    <SelectTrigger class="w-48 h-10">
                        <SelectValue placeholder="Bulk actions" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="delete">Delete</SelectItem>
                        <SelectItem value="cancel">Cancel subscription</SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" size="sm" @click="applyBulkAction" :disabled="!bulkAction">Apply</Button>
                <span v-if="selectedIds.length > 0" class="text-sm text-slate-600">
                    {{ selectedIds.length }} selected
                </span>
                <Button v-if="selectedIds.length > 0" variant="ghost" size="sm" @click="selectedIds = []">Clear</Button>
            </div>

            <div v-if="subscriptions?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[50px] px-4 py-3">
                                    <Checkbox
                                        :checked="isAllOnPageSelected"
                                        @update:checked="toggleSelectAllOnPage"
                                        aria-label="Select all on page"
                                    />
                                </TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 w-[72px]">Sr No.</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Email</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Source</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Locale</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Subscribed</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(subscription, index) in subscriptions.data" :key="subscription.id">
                                <TableCell class="px-4 py-3">
                                    <Checkbox
                                        :checked="selectedIds.includes(subscription.id)"
                                        @update:checked="(checked) => toggleRow(subscription.id, checked)"
                                        :aria-label="`Select ${subscription.email}`"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    {{ (subscriptions.from || 1) + index }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium text-slate-900">{{ subscription.email }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="badgeVariant(subscription.status)" class="capitalize">
                                        {{ subscription.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ subscription.source || '-' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm uppercase">{{ subscription.locale || '-' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ formatDate(subscription.created_at) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            v-if="subscription.status !== 'unsubscribed'"
                                            variant="outline"
                                            size="sm"
                                            @click="openCancelDialog(subscription)"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="openDeleteDialog(subscription)"
                                        >
                                            <Trash2 class="w-3 h-3 mr-1" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="subscriptions.current_page" :total-pages="subscriptions.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Mail class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No subscribers found</h3>
                        <p class="text-muted-foreground">No newsletter subscriptions match your filters.</p>
                    </div>
                </div>
            </div>

            <AdminConfirmDialog
                v-model:open="isBulkDeleteDialogOpen"
                title="Delete selected subscribers?"
                :description="`This will permanently delete ${selectedIds.length} subscriber${selectedIds.length > 1 ? 's' : ''}. Existing bulk delete behavior is unchanged.`"
                :confirm-label="`Delete ${selectedIds.length} Subscriber${selectedIds.length > 1 ? 's' : ''}`"
                :processing="isBulkDeleting"
                @confirm="confirmBulkDelete"
            />

            <AdminConfirmDialog
                v-model:open="isBulkCancelDialogOpen"
                title="Cancel selected subscriptions?"
                :description="`Mark ${selectedIds.length} subscriber${selectedIds.length > 1 ? 's' : ''} as unsubscribed. They will stop receiving newsletter emails.`"
                :confirm-label="`Unsubscribe ${selectedIds.length}`"
                cancel-label="Keep subscribed"
                variant="warning"
                :processing="isBulkCancelling"
                @confirm="confirmBulkCancel"
            />

            <AdminConfirmDialog
                v-model:open="isDeleteDialogOpen"
                title="Delete subscriber?"
                :description="deleteTarget ? `This will permanently delete ${deleteTarget.email}. This action cannot be undone.` : ''"
                confirm-label="Delete subscriber"
                :processing="isDeleting"
                @confirm="confirmDelete"
            />

            <AdminConfirmDialog
                v-model:open="isCancelDialogOpen"
                title="Cancel subscription?"
                :description="cancelTarget ? `Mark ${cancelTarget.email} as unsubscribed? They will stop receiving newsletter emails.` : ''"
                confirm-label="Unsubscribe"
                cancel-label="Keep subscribed"
                variant="warning"
                :processing="isCancelling"
                @confirm="confirmCancel"
            />
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Search, Mail, Trash2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import AdminConfirmDialog from '@/Pages/AdminDashboardPages/Shared/AdminConfirmDialog.vue';

const props = defineProps({
    subscriptions: Object,
    statusCounts: Object,
    filters: Object,
    flash: Object,
});

const ALL_STATUS_VALUE = 'all';
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || ALL_STATUS_VALUE);

const normalizedStatus = () => (
    status.value === ALL_STATUS_VALUE ? '' : status.value
);

const handlePageChange = (page) => {
    router.get('/admin/newsletter-subscribers', {
        page,
        search: search.value,
        status: normalizedStatus(),
    }, { preserveState: true, replace: true });
};

const applyFilters = () => {
    router.get('/admin/newsletter-subscribers', {
        search: search.value,
        status: normalizedStatus(),
    }, { preserveState: true, replace: true });
};

let filterTimeout;
watch([search, status], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};

const badgeVariant = (statusValue) => {
    if (statusValue === 'subscribed') return 'default';
    if (statusValue === 'pending') return 'secondary';
    return 'destructive';
};

const isDeleteDialogOpen = ref(false);
const deleteTarget = ref(null);
const isDeleting = ref(false);

const isCancelDialogOpen = ref(false);
const cancelTarget = ref(null);
const isCancelling = ref(false);

const openDeleteDialog = (subscription) => {
    deleteTarget.value = subscription;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(`/admin/newsletter-subscribers/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${deleteTarget.value.email}`);
            isDeleteDialogOpen.value = false;
            deleteTarget.value = null;
        },
        onError: () => toast.error('Failed to delete subscriber'),
        onFinish: () => { isDeleting.value = false; },
    });
};

const openCancelDialog = (subscription) => {
    cancelTarget.value = subscription;
    isCancelDialogOpen.value = true;
};

const confirmCancel = () => {
    if (!cancelTarget.value) return;
    isCancelling.value = true;
    router.patch(`/admin/newsletter-subscribers/${cancelTarget.value.id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Unsubscribed ${cancelTarget.value.email}`);
            isCancelDialogOpen.value = false;
            cancelTarget.value = null;
        },
        onError: () => toast.error('Failed to cancel subscription'),
        onFinish: () => { isCancelling.value = false; },
    });
};

const selectedIds = ref([]);
const bulkAction = ref('');
const isBulkDeleteDialogOpen = ref(false);
const isBulkDeleting = ref(false);
const isBulkCancelDialogOpen = ref(false);
const isBulkCancelling = ref(false);

const applyBulkAction = () => {
    if (!bulkAction.value) return;
    if (selectedIds.value.length === 0) {
        toast.error('Select at least one subscriber first');
        return;
    }
    if (bulkAction.value === 'delete') isBulkDeleteDialogOpen.value = true;
    else if (bulkAction.value === 'cancel') isBulkCancelDialogOpen.value = true;
};

const confirmBulkCancel = () => {
    if (selectedIds.value.length === 0) return;
    const count = selectedIds.value.length;
    isBulkCancelling.value = true;
    router.patch('/admin/newsletter-subscribers/bulk/cancel', { ids: [...selectedIds.value] }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Unsubscribed ${count} subscriber${count > 1 ? 's' : ''}`);
            selectedIds.value = [];
            bulkAction.value = '';
            isBulkCancelDialogOpen.value = false;
        },
        onError: () => toast.error('Failed to cancel selected subscriptions'),
        onFinish: () => { isBulkCancelling.value = false; },
    });
};

const visibleIds = computed(() => (props.subscriptions?.data || []).map((s) => s.id));
const isAllOnPageSelected = computed(
    () => visibleIds.value.length > 0 && visibleIds.value.every((id) => selectedIds.value.includes(id)),
);

const toggleRow = (id, checked) => {
    if (checked) {
        if (!selectedIds.value.includes(id)) selectedIds.value.push(id);
    } else {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }
};

const toggleSelectAllOnPage = (checked) => {
    if (checked) {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...visibleIds.value]));
    } else {
        selectedIds.value = selectedIds.value.filter((id) => !visibleIds.value.includes(id));
    }
};

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;
    const count = selectedIds.value.length;
    isBulkDeleting.value = true;
    router.delete('/admin/newsletter-subscribers/bulk', {
        data: { ids: [...selectedIds.value] },
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${count} subscriber${count > 1 ? 's' : ''}`);
            selectedIds.value = [];
            bulkAction.value = '';
            isBulkDeleteDialogOpen.value = false;
        },
        onError: () => toast.error('Failed to delete selected subscribers'),
        onFinish: () => { isBulkDeleting.value = false; },
    });
};

watch(() => props.subscriptions?.current_page, () => {
    selectedIds.value = [];
});

watch(() => props.flash?.success, (msg) => { if (msg) toast.success(msg); }, { immediate: true });
watch(() => props.flash?.error, (msg) => { if (msg) toast.error(msg); }, { immediate: true });
</script>
