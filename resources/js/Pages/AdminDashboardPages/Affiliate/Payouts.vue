<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Wallet, Clock, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    payouts: Object,
    pendingSummary: Array,
});

const totalPending = computed(() =>
    props.pendingSummary.reduce((sum, item) => sum + parseFloat(item.total), 0)
);
const totalPaid = computed(() =>
    props.payouts.data.filter(p => p.status === 'paid').reduce((sum, p) => sum + parseFloat(p.total_amount), 0)
);
const pendingPayoutsCount = computed(() =>
    props.payouts.data.filter(p => p.status === 'pending').length
);

const statCards = computed(() => [
    { label: 'Ready for Payout', value: `\u20AC${totalPending.value.toFixed(2)}`, sub: `${props.pendingSummary.length} partners`, icon: Wallet, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Pending Payouts', value: pendingPayoutsCount.value, sub: 'awaiting transfer', icon: Clock, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Total Paid', value: `\u20AC${totalPaid.value.toFixed(2)}`, sub: 'all time', icon: CheckCircle, color: 'text-emerald-600', bg: 'bg-emerald-50' },
]);

const statusColor = (val) => {
    const map = { pending: 'bg-amber-100 text-amber-700', processing: 'bg-blue-100 text-blue-700', paid: 'bg-emerald-100 text-emerald-700', failed: 'bg-red-100 text-red-700' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

// Create payout dialog
const showCreateDialog = ref(false);
const createForm = useForm({
    business_id: null,
    business_name: '',
    total: 0,
    count: 0,
    period_start: '',
    period_end: '',
    admin_notes: '',
});

const openCreatePayoutDialog = (item) => {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    createForm.business_id = item.business_id;
    createForm.business_name = item.business?.name || '';
    createForm.total = item.total;
    createForm.count = item.count;
    createForm.period_start = firstDay;
    createForm.period_end = lastDay;
    createForm.admin_notes = '';
    showCreateDialog.value = true;
};

const submitCreatePayout = () => {
    createForm.post(route('admin.affiliate.payouts.create'), {
        onSuccess: () => { showCreateDialog.value = false; },
    });
};

// Mark paid dialog
const showMarkPaidDialog = ref(false);
const markPaidForm = useForm({
    payout_id: null,
    business_name: '',
    amount: 0,
    bank_transfer_reference: '',
    admin_notes: '',
});

const openMarkPaidDialog = (payout) => {
    markPaidForm.payout_id = payout.id;
    markPaidForm.business_name = payout.business?.name || '';
    markPaidForm.amount = payout.total_amount;
    markPaidForm.bank_transfer_reference = '';
    markPaidForm.admin_notes = '';
    showMarkPaidDialog.value = true;
};

const submitMarkPaid = () => {
    markPaidForm.post(route('admin.affiliate.payouts.mark-paid', { payout: markPaidForm.payout_id }), {
        onSuccess: () => { showMarkPaidDialog.value = false; },
    });
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Payouts</h1>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.error }}
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card v-for="card in statCards" :key="card.label">
                    <CardContent class="flex items-center gap-3 p-4">
                        <div :class="[card.bg, 'rounded-lg p-2.5']">
                            <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ card.label }}</p>
                            <p class="text-xl font-bold">{{ card.value }}</p>
                            <p class="text-xs text-gray-400">{{ card.sub }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pending Commissions Ready -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Approved Commissions Ready for Payout</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table v-if="pendingSummary.length">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">Business</TableHead>
                                <TableHead class="px-4">Commissions</TableHead>
                                <TableHead class="px-4">Total Amount</TableHead>
                                <TableHead class="px-4">Bank</TableHead>
                                <TableHead class="px-4">IBAN</TableHead>
                                <TableHead class="px-4 text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in pendingSummary" :key="item.business_id">
                                <TableCell class="px-4 font-medium">{{ item.business?.name || '-' }}</TableCell>
                                <TableCell class="px-4">{{ item.count }}</TableCell>
                                <TableCell class="px-4 font-semibold text-emerald-600">&euro;{{ parseFloat(item.total).toFixed(2) }}</TableCell>
                                <TableCell class="px-4 text-gray-500">{{ item.business?.bank_name || '-' }}</TableCell>
                                <TableCell class="px-4 text-gray-500 font-mono text-xs">{{ item.business?.bank_iban || 'Not provided' }}</TableCell>
                                <TableCell class="px-4 text-right">
                                    <Button size="sm" class="h-8 bg-indigo-600 hover:bg-indigo-700 text-white" @click="openCreatePayoutDialog(item)">
                                        Create Payout
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="p-8 text-center text-gray-400 text-sm">
                        No approved commissions awaiting payout.
                    </div>
                </CardContent>
            </Card>

            <!-- Payout History -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Payout History</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">Business</TableHead>
                                <TableHead class="px-4">Amount</TableHead>
                                <TableHead class="px-4">Period</TableHead>
                                <TableHead class="px-4">Status</TableHead>
                                <TableHead class="px-4">Bank Ref</TableHead>
                                <TableHead class="px-4">Paid By</TableHead>
                                <TableHead class="px-4 text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payout in payouts.data" :key="payout.id">
                                <TableCell class="px-4 font-medium">{{ payout.business?.name || '-' }}</TableCell>
                                <TableCell class="px-4 font-semibold">&euro;{{ parseFloat(payout.total_amount).toFixed(2) }}</TableCell>
                                <TableCell class="px-4 text-gray-500 text-xs">
                                    {{ new Date(payout.period_start).toLocaleDateString() }}
                                    &ndash;
                                    {{ new Date(payout.period_end).toLocaleDateString() }}
                                </TableCell>
                                <TableCell class="px-4">
                                    <span :class="[statusColor(payout.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ payout.status }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4 text-gray-500 font-mono text-xs">
                                    {{ payout.bank_transfer_reference || '-' }}
                                </TableCell>
                                <TableCell class="px-4 text-gray-500">
                                    <template v-if="payout.paid_by_user">
                                        {{ payout.paid_by_user.first_name }} {{ payout.paid_by_user.last_name }}
                                    </template>
                                    <template v-else>-</template>
                                </TableCell>
                                <TableCell class="px-4 text-right">
                                    <Button
                                        v-if="payout.status === 'pending'"
                                        size="sm"
                                        class="h-8 bg-emerald-600 hover:bg-emerald-700 text-white"
                                        @click="openMarkPaidDialog(payout)"
                                    >
                                        Mark Paid
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!payouts.data.length">
                                <TableCell colspan="7" class="px-4 text-center text-gray-400 py-8">No payouts yet.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="payouts.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Showing {{ payouts.from }} to {{ payouts.to }} of {{ payouts.total }}
                </p>
                <div class="flex gap-1">
                    <template v-for="link in payouts.links" :key="link.label">
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

            <!-- Create Payout Dialog -->
            <Dialog v-model:open="showCreateDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Create Payout</DialogTitle>
                        <DialogDescription>
                            {{ createForm.count }} approved commissions totaling
                            <strong>&euro;{{ parseFloat(createForm.total).toFixed(2) }}</strong>
                            for <strong>{{ createForm.business_name }}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Period Start</label>
                            <Input v-model="createForm.period_start" type="date" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Period End</label>
                            <Input v-model="createForm.period_end" type="date" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (optional)</label>
                            <Textarea v-model="createForm.admin_notes" rows="2" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showCreateDialog = false">Cancel</Button>
                        <Button
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                            :disabled="createForm.processing"
                            @click="submitCreatePayout"
                        >
                            {{ createForm.processing ? 'Creating...' : 'Create Payout' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Mark Paid Dialog -->
            <Dialog v-model:open="showMarkPaidDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Mark Payout as Paid</DialogTitle>
                        <DialogDescription>
                            Payout of <strong>&euro;{{ parseFloat(markPaidForm.amount).toFixed(2) }}</strong>
                            for <strong>{{ markPaidForm.business_name }}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Transfer Reference</label>
                            <Input v-model="markPaidForm.bank_transfer_reference" placeholder="e.g. TXN-2026-03-001" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (optional)</label>
                            <Textarea v-model="markPaidForm.admin_notes" rows="2" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showMarkPaidDialog = false">Cancel</Button>
                        <Button
                            class="bg-emerald-600 hover:bg-emerald-700 text-white"
                            :disabled="markPaidForm.processing || !markPaidForm.bank_transfer_reference"
                            @click="submitMarkPaid"
                        >
                            {{ markPaidForm.processing ? 'Processing...' : 'Confirm Payment' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminDashboardLayout>
</template>
