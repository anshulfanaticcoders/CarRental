<template>
    <AffiliateHeader currentPage="commissions" />
        <!-- Pending Approval Banner -->
        <div v-if="!isVerified" class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-[min(92%,1200px)] mx-auto py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0">
                    <AlertCircle class="w-4 h-4" />
                </div>
                <div>
                    <span class="text-sm font-bold text-amber-800">Account Pending Approval</span>
                    <span class="text-xs text-amber-600 ml-1">— Your partner account is being reviewed. You'll be notified by email once approved. Some features are limited.</span>
                </div>
            </div>
        </div>

        <!-- Dark Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#0a1d28] to-[#153b4f]">
            <div class="absolute top-[5%] left-[-3%] w-[220px] h-[220px] rounded-full bg-cyan-500 opacity-20 blur-[80px] pointer-events-none animate-float"></div>
            <div class="absolute bottom-[5%] right-[-2%] w-[160px] h-[160px] rounded-full bg-cyan-500 opacity-[0.12] blur-[80px] pointer-events-none animate-float-delayed"></div>

            <div class="relative z-10 max-w-[min(92%,1200px)] mx-auto py-4 md:py-6">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
                    <div>
                        <h1 class="text-xl md:text-[1.75rem] font-[800] text-white mb-0.5">Commission History</h1>
                        <p class="text-[0.85rem] text-slate-400">Track your earnings from QR code bookings.</p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 mt-4">
                    <div v-for="stat in statCards" :key="stat.label"
                        class="bg-[rgba(21,59,79,0.28)] backdrop-blur-[16px] border border-[rgba(6,182,212,0.08)] rounded-2xl p-3.5 text-center transition-all duration-400 hover:border-[rgba(6,182,212,0.18)] hover:shadow-[0_10px_30px_rgba(0,0,0,0.12)]">
                        <div class="text-[0.68rem] text-slate-500 font-semibold uppercase tracking-wider mb-0.5">{{ stat.label }}</div>
                        <div class="text-xl font-[800] bg-gradient-to-br from-cyan-300 to-cyan-500 bg-clip-text text-transparent">
                            {{ stat.value }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters & Table -->
        <section class="bg-gradient-to-b from-slate-50 to-white py-4 md:py-6">
            <div class="max-w-[min(92%,1200px)] mx-auto">

                <!-- Filter Chips -->
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center mb-4">
                    <div class="flex gap-1.5 items-center flex-wrap">
                        <button v-for="f in filterOptions" :key="f.value"
                            @click="setFilter(f.value)"
                            :class="[
                                'px-3 py-1 rounded-full border text-[0.78rem] font-medium cursor-pointer transition-all duration-200',
                                activeFilter === f.value
                                    ? 'bg-[#153b4f] text-white border-[#153b4f]'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400'
                            ]">
                            {{ f.label }}
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5 sm:ml-auto">
                        <input type="date" v-model="dateFrom" @change="applyDateFilter"
                            class="px-2.5 py-1 border border-slate-200 rounded-lg text-[0.78rem] bg-white text-slate-700 min-w-0 flex-1 sm:flex-initial" />
                        <span class="text-[0.78rem] text-slate-400">to</span>
                        <input type="date" v-model="dateTo" @change="applyDateFilter"
                            class="px-2.5 py-1 border border-slate-200 rounded-lg text-[0.78rem] bg-white text-slate-700 min-w-0 flex-1 sm:flex-initial" />
                    </div>
                </div>

                <!-- Table -->
                <div class="border border-[rgba(15,23,42,0.07)] rounded-2xl overflow-hidden shadow-[0_1px_2px_rgba(21,59,79,0.03),0_4px_12px_rgba(21,59,79,0.04)]">
                  <div class="overflow-x-auto">
                    <table class="w-full border-collapse min-w-[640px]">
                        <thead>
                            <tr>
                                <th class="af-th">Date</th>
                                <th class="af-th">Booking</th>
                                <th class="af-th">Pickup</th>
                                <th class="af-th">Amount</th>
                                <th class="af-th">Rate</th>
                                <th class="af-th">Commission</th>
                                <th class="af-th">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in commissions.data" :key="c.id" class="transition-colors duration-200 hover:bg-[#f0f8fc]">
                                <td class="af-td">{{ formatDate(c.created_at) }}</td>
                                <td class="af-td font-bold text-[#153b4f]">{{ c.booking?.booking_number || '-' }}</td>
                                <td class="af-td text-slate-500">
                                    {{ c.booking?.pickup_date ? formatDate(c.booking.pickup_date) : '-' }}
                                </td>
                                <td class="af-td">&euro;{{ parseFloat(c.booking_amount).toFixed(2) }}</td>
                                <td class="af-td">{{ c.commission_rate }}%</td>
                                <td class="af-td font-bold text-[#153b4f]">&euro;{{ parseFloat(c.commission_amount).toFixed(2) }}</td>
                                <td class="af-td">
                                    <span :class="getStatusBadgeClass(c.status)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.65rem] font-semibold uppercase tracking-wide">
                                        {{ c.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!commissions.data.length">
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400 text-sm">No commissions found.</td>
                            </tr>
                        </tbody>
                    </table>
                  </div>

                    <!-- Pagination -->
                    <div v-if="commissions.last_page > 1"
                        class="flex justify-between items-center px-4 py-3 border-t border-slate-200 bg-white text-[0.78rem] text-slate-500">
                        <span>Showing {{ commissions.from }}&ndash;{{ commissions.to }} of {{ commissions.total }}</span>
                        <div class="flex gap-1">
                            <template v-for="link in commissions.links" :key="link.label">
                                <a v-if="link.url" :href="link.url"
                                    :class="[
                                        'w-7 h-7 flex items-center justify-center border rounded-md text-[0.75rem] font-semibold transition-colors',
                                        link.active
                                            ? 'bg-[#153b4f] text-white border-[#153b4f]'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                    ]"
                                    v-html="link.label">
                                </a>
                                <span v-else
                                    class="w-7 h-7 flex items-center justify-center text-[0.75rem] text-slate-300"
                                    v-html="link.label">
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <Toaster position="bottom-right" />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/Components/ui/sonner';
import AffiliateHeader from '@/Layouts/AffiliateHeader.vue';
import { AlertCircle } from 'lucide-vue-next';


const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isVerified = computed(() => page.props.affiliateVerificationStatus === 'verified');

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}, { immediate: true });

const props = defineProps({
    business: Object,
    commissions: Object,
    summaryStats: Object,
    filters: Object,
});

const filterOptions = [
    { label: 'All', value: 'all' },
    { label: 'Pending', value: 'pending' },
    { label: 'Approved', value: 'approved' },
    { label: 'Paid', value: 'paid' },
    { label: 'Cancelled', value: 'cancelled' },
];

const activeFilter = ref(props.filters?.status || 'all');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const statCards = computed(() => [
    { label: 'Total Earned', value: '\u20AC' + (props.summaryStats?.total_earned || '0.00') },
    { label: 'Pending', value: '\u20AC' + (props.summaryStats?.pending || '0.00') },
    { label: 'Paid Out', value: '\u20AC' + (props.summaryStats?.paid_out || '0.00') },
    { label: 'This Month', value: '\u20AC' + (props.summaryStats?.this_month || '0.00') },
]);

function setFilter(value) {
    activeFilter.value = value;
    applyFilters();
}

function applyDateFilter() {
    applyFilters();
}

function applyFilters() {
    router.get(route('affiliate.commissions', { locale: locale.value }), {
        status: activeFilter.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
}

function getStatusBadgeClass(status) {
    const map = {
        pending: 'bg-amber-100 text-amber-800',
        approved: 'bg-cyan-100 text-cyan-800',
        paid: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
}
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-14px) scale(1.04); }
}
.animate-float { animation: float 14s ease-in-out infinite; }
.animate-float-delayed { animation: float 14s ease-in-out infinite; animation-delay: -7s; }

.af-th {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.7rem 1rem;
    text-align: left;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.af-td {
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
</style>
