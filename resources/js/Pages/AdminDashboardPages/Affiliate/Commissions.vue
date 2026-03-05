<script setup>
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { Hash, Clock, CheckCircle, Wallet, Search } from 'lucide-vue-next';

const props = defineProps({
    commissions: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const buildParams = () => ({
    search: search.value || undefined,
    status: status.value === 'all' ? undefined : status.value,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
});

let debounceTimer = null;
watch([search], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.affiliate.commissions'), buildParams(), { preserveState: true, replace: true });
    }, 300);
});

watch([status, dateFrom, dateTo], () => {
    router.get(route('admin.affiliate.commissions'), buildParams(), { preserveState: true, replace: true });
});

const statCards = [
    { label: 'Total', count: props.stats.total, amount: props.stats.totalAmount, icon: Hash, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Pending', count: props.stats.pending, amount: props.stats.pendingAmount, icon: Clock, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Approved', count: props.stats.approved, amount: props.stats.approvedAmount, icon: CheckCircle, color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: 'Paid', count: props.stats.paid, amount: props.stats.paidAmount, icon: Wallet, color: 'text-emerald-600', bg: 'bg-emerald-50' },
];

const statusColor = (val) => {
    const map = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-blue-100 text-blue-700', paid: 'bg-emerald-100 text-emerald-700', rejected: 'bg-red-100 text-red-700', disputed: 'bg-orange-100 text-orange-700' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

// Approve action
const approveCommission = (id) => {
    router.patch(route('admin.affiliate.commissions.status.update', { id }), {
        action: 'approve',
    }, { preserveScroll: true });
};

// Reject dialog
const showRejectDialog = ref(false);
const rejectForm = useForm({ action: 'reject', reason: '' });
const rejectTargetId = ref(null);

const openRejectDialog = (id) => {
    rejectTargetId.value = id;
    rejectForm.reason = '';
    showRejectDialog.value = true;
};

const submitReject = () => {
    rejectForm.patch(route('admin.affiliate.commissions.status.update', { id: rejectTargetId.value }), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectDialog.value = false;
        },
    });
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Commissions</h1>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card v-for="card in statCards" :key="card.label">
                    <CardContent class="flex items-center gap-3 p-4">
                        <div :class="[card.bg, 'rounded-lg p-2.5']">
                            <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ card.label }}</p>
                            <p class="text-xl font-bold">{{ card.count }}</p>
                            <p class="text-xs text-gray-400">&euro;{{ card.amount.toFixed(2) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input v-model="search" placeholder="Search partner or booking #..." class="pl-9" />
                </div>
                <Select v-model="status">
                    <SelectTrigger class="w-[150px]">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                </Select>
                <Input v-model="dateFrom" type="date" class="w-[160px]" placeholder="From" />
                <Input v-model="dateTo" type="date" class="w-[160px]" placeholder="To" />
            </div>

            <!-- Table -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">Date</TableHead>
                                <TableHead class="px-4">Partner</TableHead>
                                <TableHead class="px-4">Booking #</TableHead>
                                <TableHead class="px-4">Booking Amount</TableHead>
                                <TableHead class="px-4">Rate</TableHead>
                                <TableHead class="px-4">Commission</TableHead>
                                <TableHead class="px-4">Status</TableHead>
                                <TableHead class="px-4 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="c in commissions.data" :key="c.id">
                                <TableCell class="px-4 text-gray-500 text-xs">
                                    {{ new Date(c.created_at).toLocaleDateString() }}
                                </TableCell>
                                <TableCell class="px-4 font-medium">{{ c.business?.name || '-' }}</TableCell>
                                <TableCell class="px-4 font-mono text-xs">{{ c.booking?.booking_number || '-' }}</TableCell>
                                <TableCell class="px-4">&euro;{{ parseFloat(c.booking_amount || 0).toFixed(2) }}</TableCell>
                                <TableCell class="px-4">{{ c.commission_rate }}%</TableCell>
                                <TableCell class="px-4 font-semibold">&euro;{{ parseFloat(c.commission_amount || 0).toFixed(2) }}</TableCell>
                                <TableCell class="px-4">
                                    <span :class="[statusColor(c.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ c.status }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4 text-right">
                                    <div v-if="c.status === 'pending'" class="flex items-center justify-end gap-1.5">
                                        <Button
                                            size="sm"
                                            class="h-7 px-2.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white"
                                            @click="approveCommission(c.id)"
                                        >
                                            Approve
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-7 px-2.5 text-xs text-red-600 border-red-200 hover:bg-red-50"
                                            @click="openRejectDialog(c.id)"
                                        >
                                            Reject
                                        </Button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!commissions.data.length">
                                <TableCell colspan="8" class="px-4 text-center text-gray-400 py-8">No commissions found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="commissions.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Showing {{ commissions.from }} to {{ commissions.to }} of {{ commissions.total }}
                </p>
                <div class="flex gap-1">
                    <template v-for="link in commissions.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 rounded-md text-sm transition-colors',
                                link.active ? 'bg-indigo-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50'
                            ]"
                            v-html="link.label"
                            preserve-state
                        />
                        <span v-else class="px-3 py-1.5 rounded-md text-sm text-gray-300" v-html="link.label" />
                    </template>
                </div>
            </div>

            <!-- Reject Dialog -->
            <Dialog v-model:open="showRejectDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Reject Commission</DialogTitle>
                        <DialogDescription>Please provide a reason for rejecting this commission.</DialogDescription>
                    </DialogHeader>
                    <Textarea v-model="rejectForm.reason" placeholder="Reason for rejection..." rows="3" />
                    <DialogFooter>
                        <Button variant="outline" @click="showRejectDialog = false">Cancel</Button>
                        <Button
                            class="bg-red-600 hover:bg-red-700 text-white"
                            :disabled="rejectForm.processing || !rejectForm.reason"
                            @click="submitReject"
                        >
                            {{ rejectForm.processing ? 'Rejecting...' : 'Reject' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminDashboardLayout>
</template>
