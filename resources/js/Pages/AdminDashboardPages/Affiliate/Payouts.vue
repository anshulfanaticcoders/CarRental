<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Scout Payouts</h1>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ $page.props.flash.error }}
            </div>

            <!-- Pending Commissions Summary -->
            <div v-if="pendingSummary.length" class="bg-white rounded-xl border overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Approved Commissions Ready for Payout</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Business</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Commissions</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Total Amount</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Bank</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">IBAN</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in pendingSummary" :key="item.business_id" class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ item.business?.name || '-' }}</td>
                                <td class="px-4 py-3">{{ item.count }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-600">
                                    &euro;{{ parseFloat(item.total).toFixed(2) }}
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ item.business?.bank_name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ item.business?.bank_iban || 'Not provided' }}</td>
                                <td class="px-4 py-3">
                                    <button @click="openCreatePayoutModal(item)"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                        Create Payout
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="bg-gray-50 border rounded-xl p-8 text-center text-gray-400">
                No approved commissions awaiting payout.
            </div>

            <!-- Payouts History -->
            <div class="bg-white rounded-xl border overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Payout History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Business</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Amount</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Period</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Bank Ref</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Paid By</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payout in payouts.data" :key="payout.id" class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ payout.business?.name || '-' }}</td>
                                <td class="px-4 py-3 font-semibold">
                                    &euro;{{ parseFloat(payout.total_amount).toFixed(2) }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ new Date(payout.period_start).toLocaleDateString() }}
                                    &ndash;
                                    {{ new Date(payout.period_end).toLocaleDateString() }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="{
                                        'bg-amber-100 text-amber-700': payout.status === 'pending',
                                        'bg-blue-100 text-blue-700': payout.status === 'processing',
                                        'bg-emerald-100 text-emerald-700': payout.status === 'paid',
                                        'bg-red-100 text-red-700': payout.status === 'failed',
                                    }" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ payout.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                                    {{ payout.bank_transfer_reference || '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    <template v-if="payout.paid_by_user">
                                        {{ payout.paid_by_user.first_name }} {{ payout.paid_by_user.last_name }}
                                    </template>
                                    <template v-else>-</template>
                                </td>
                                <td class="px-4 py-3">
                                    <button v-if="payout.status === 'pending'"
                                        @click="openMarkPaidModal(payout)"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                        Mark Paid
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!payouts.data.length">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">No payouts yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payouts.last_page > 1" class="px-4 py-3 border-t flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Showing {{ payouts.from }} to {{ payouts.to }} of {{ payouts.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in payouts.links" :key="link.label">
                            <a v-if="link.url" :href="link.url"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50'
                                ]"
                                v-html="link.label">
                            </a>
                            <span v-else class="px-3 py-1 rounded text-sm text-gray-300" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Create Payout Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCreateModal = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <h3 class="text-lg font-semibold">Create Payout</h3>
                    <p class="text-sm text-gray-500">
                        {{ createForm.count }} approved commissions totaling
                        <strong>&euro;{{ parseFloat(createForm.total).toFixed(2) }}</strong>
                        for <strong>{{ createForm.business_name }}</strong>
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period Start</label>
                        <input v-model="createForm.period_start" type="date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period End</label>
                        <input v-model="createForm.period_end" type="date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (optional)</label>
                        <textarea v-model="createForm.admin_notes" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showCreateModal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                        <button @click="submitCreatePayout" :disabled="createForm.processing"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50">
                            {{ createForm.processing ? 'Creating...' : 'Create Payout' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mark Paid Modal -->
            <div v-if="showMarkPaidModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showMarkPaidModal = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <h3 class="text-lg font-semibold">Mark Payout as Paid</h3>
                    <p class="text-sm text-gray-500">
                        Payout of <strong>&euro;{{ parseFloat(markPaidForm.amount).toFixed(2) }}</strong>
                        for <strong>{{ markPaidForm.business_name }}</strong>
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Transfer Reference</label>
                        <input v-model="markPaidForm.bank_transfer_reference" type="text" required
                            placeholder="e.g. TXN-2026-03-001"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (optional)</label>
                        <textarea v-model="markPaidForm.admin_notes" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showMarkPaidModal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                        <button @click="submitMarkPaid" :disabled="markPaidForm.processing"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50">
                            {{ markPaidForm.processing ? 'Processing...' : 'Confirm Payment' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

defineProps({
    payouts: Object,
    pendingSummary: Array,
});

const showCreateModal = ref(false);
const showMarkPaidModal = ref(false);

const createForm = useForm({
    business_id: null,
    business_name: '',
    total: 0,
    count: 0,
    period_start: '',
    period_end: '',
    admin_notes: '',
});

const markPaidForm = useForm({
    payout_id: null,
    business_name: '',
    amount: 0,
    bank_transfer_reference: '',
    admin_notes: '',
});

const openCreatePayoutModal = (item) => {
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
    showCreateModal.value = true;
};

const submitCreatePayout = () => {
    createForm.post(route('admin.affiliate.payouts.create'), {
        onSuccess: () => {
            showCreateModal.value = false;
        },
    });
};

const openMarkPaidModal = (payout) => {
    markPaidForm.payout_id = payout.id;
    markPaidForm.business_name = payout.business?.name || '';
    markPaidForm.amount = payout.total_amount;
    markPaidForm.bank_transfer_reference = '';
    markPaidForm.admin_notes = '';
    showMarkPaidModal.value = true;
};

const submitMarkPaid = () => {
    markPaidForm.post(route('admin.affiliate.payouts.mark-paid', { payout: markPaidForm.payout_id }), {
        onSuccess: () => {
            showMarkPaidModal.value = false;
        },
    });
};
</script>
