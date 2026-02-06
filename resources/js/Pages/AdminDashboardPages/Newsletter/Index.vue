<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Newsletter Subscribers</h1>
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                    Total: {{ statusCounts?.total || 0 }}
                </div>
            </div>

            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ flash.error }}
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
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="subscribed">Subscribed</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="unsubscribed">Unsubscribed</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div v-if="subscriptions?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap px-4 py-3">Email</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Source</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Locale</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Subscribed</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="subscription in subscriptions.data" :key="subscription.id">
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
                                    <Button
                                        v-if="subscription.status !== 'unsubscribed'"
                                        variant="destructive"
                                        size="sm"
                                        @click="cancelSubscription(subscription)"
                                    >
                                        Cancel
                                    </Button>
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
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Search, Mail } from 'lucide-vue-next';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    subscriptions: Object,
    statusCounts: Object,
    filters: Object,
    flash: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const handlePageChange = (page) => {
    router.get('/admin/newsletter-subscribers', {
        page,
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true });
};

const applyFilters = () => {
    router.get('/admin/newsletter-subscribers', {
        search: search.value,
        status: status.value,
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

const cancelSubscription = (subscription) => {
    const confirmed = window.confirm('Cancel this subscription?');
    if (!confirmed) return;

    router.patch(`/admin/newsletter-subscribers/${subscription.id}/cancel`, {}, {
        preserveScroll: true,
    });
};
</script>
