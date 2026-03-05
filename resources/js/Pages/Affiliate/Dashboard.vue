<template>
    <AffiliateHeader currentPage="dashboard" />
        <!-- Pending Approval Banner -->
        <div v-if="!isVerified" class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-[min(92%,1200px)] mx-auto py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                    </svg>
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
                        <h1 class="text-xl md:text-[1.75rem] font-[800] text-white mb-0.5">Dashboard</h1>
                        <p class="text-[0.88rem] text-slate-400">Welcome back, {{ business.name }}.</p>
                    </div>
                    <div class="flex gap-2 items-center">
                        <div class="w-9 h-9 rounded-[10px] border border-white/10 bg-white/5 flex items-center justify-center cursor-pointer text-base relative">
                            &#x1F514;
                            <div class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-red-500 border-[1.5px] border-[#0a1d28]"></div>
                        </div>
                        <a :href="route('affiliate.qr-codes', { locale })"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-[0.8rem] font-bold text-white rounded-[10px] bg-gradient-to-br from-cyan-500 to-cyan-600 shadow-[0_4px_14px_rgba(6,182,212,0.25)] transition-all duration-250 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(6,182,212,0.35)]">
                            + Create QR
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 mt-5">
                    <div v-for="stat in statCards" :key="stat.label"
                        class="bg-[rgba(21,59,79,0.28)] backdrop-blur-[16px] border border-[rgba(6,182,212,0.08)] rounded-2xl p-4 text-center transition-all duration-400 hover:border-[rgba(6,182,212,0.18)] hover:shadow-[0_10px_30px_rgba(0,0,0,0.12)]">
                        <div class="w-9 h-9 rounded-[10px] flex items-center justify-center text-base bg-[rgba(6,182,212,0.12)] border border-[rgba(6,182,212,0.08)] mx-auto mb-2.5">
                            {{ stat.icon }}
                        </div>
                        <div class="text-2xl font-[800] bg-gradient-to-br from-cyan-300 to-cyan-500 bg-clip-text text-transparent leading-tight mb-0.5">
                            {{ stat.value }}
                        </div>
                        <div class="text-[0.75rem] text-slate-500">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Revenue Chart -->
        <section class="bg-gradient-to-b from-slate-50 to-white py-4 md:py-6">
            <div class="max-w-[min(92%,1200px)] mx-auto">
                <div class="bg-white border border-[rgba(15,23,42,0.07)] rounded-2xl p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <span class="text-[0.76rem] font-bold tracking-[0.12em] uppercase text-cyan-500">Revenue</span>
                            <div class="font-bold text-[0.92rem] text-[#153b4f]">Last 30 Days</div>
                        </div>
                    </div>
                    <div class="flex items-end gap-2 h-[130px]">
                        <div v-for="(bar, i) in chartBars" :key="i"
                            class="flex-1 rounded-t bg-gradient-to-t from-[#153b4f] to-[#2ea7ad] min-h-[6px] relative cursor-pointer transition-opacity duration-300 hover:opacity-80"
                            :style="{ height: bar.height }">
                            <span class="absolute -bottom-4 left-1/2 -translate-x-1/2 text-[0.62rem] text-slate-400">{{ bar.label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two Column Grid -->
        <section class="bg-white pb-4 md:pb-6">
            <div class="max-w-[min(92%,1200px)] mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Recent Commissions -->
                    <div class="bg-white border border-[rgba(15,23,42,0.07)] rounded-[20px] shadow-[0_1px_2px_rgba(21,59,79,0.03),0_8px_24px_rgba(21,59,79,0.06)]">
                        <div class="flex justify-between items-center py-3.5 px-4 border-b border-[rgba(15,23,42,0.06)]">
                            <h3 class="text-[0.9rem] font-bold text-[#153b4f]">Recent Commissions</h3>
                            <a :href="route('affiliate.commissions', { locale })"
                                class="text-[0.78rem] font-semibold text-[#2ea7ad] hover:bg-[rgba(46,167,173,0.06)] px-3 py-1.5 rounded-lg transition-colors">
                                View All
                            </a>
                        </div>
                        <div class="px-4 pb-4 pt-1">
                            <div v-for="c in recentCommissions" :key="c.id"
                                class="flex justify-between items-center py-2.5 border-b border-[rgba(15,23,42,0.04)] last:border-b-0">
                                <div>
                                    <div class="font-semibold text-[0.85rem] text-[#153b4f]">{{ c.booking?.booking_number || '-' }}</div>
                                    <div class="text-[0.7rem] text-slate-400 mt-0.5">{{ timeAgo(c.created_at) }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[0.85rem] text-[#153b4f]">&euro;{{ parseFloat(c.commission_amount).toFixed(2) }}</span>
                                    <span :class="getStatusBadgeClass(c.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.65rem] font-semibold uppercase tracking-wide">
                                        {{ c.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!recentCommissions.length" class="py-6 text-center text-slate-400 text-sm">
                                No commissions yet. Share your QR codes to start earning!
                            </div>
                        </div>
                    </div>

                    <!-- Active QR Codes -->
                    <div class="bg-white border border-[rgba(15,23,42,0.07)] rounded-[20px] shadow-[0_1px_2px_rgba(21,59,79,0.03),0_8px_24px_rgba(21,59,79,0.06)]">
                        <div class="flex justify-between items-center py-3.5 px-4 border-b border-[rgba(15,23,42,0.06)]">
                            <h3 class="text-[0.9rem] font-bold text-[#153b4f]">Active QR Codes</h3>
                            <a :href="route('affiliate.qr-codes', { locale })"
                                class="text-[0.78rem] font-semibold text-[#2ea7ad] hover:bg-[rgba(46,167,173,0.06)] px-3 py-1.5 rounded-lg transition-colors">
                                Manage
                            </a>
                        </div>
                        <div class="px-4 pb-4 pt-1">
                            <div v-for="qr in qrCodes" :key="qr.id"
                                class="flex items-center gap-3 py-2.5 border-b border-[rgba(15,23,42,0.04)] last:border-b-0">
                                <div class="w-9 h-9 rounded-[10px] bg-gradient-to-br from-[rgba(6,182,212,0.12)] to-[rgba(21,59,79,0.05)] border border-[rgba(6,182,212,0.1)] flex items-center justify-center text-base shrink-0">
                                    &#x1F4F1;
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[0.85rem] text-[#153b4f] truncate">{{ qr.label || qr.short_code }}</div>
                                    <div class="text-[0.72rem] text-slate-400">{{ qr.customer_scans_count || 0 }} scans</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.65rem] font-semibold uppercase tracking-wide bg-emerald-100 text-emerald-800">
                                    {{ qr.status || 'Active' }}
                                </span>
                            </div>
                            <div v-if="!qrCodes.length" class="py-6 text-center text-slate-400 text-sm">
                                No QR codes yet. Create your first QR code to start tracking scans.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <Toaster position="bottom-right" />
</template>

<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/Components/ui/sonner';
import AffiliateHeader from '@/Layouts/AffiliateHeader.vue';


const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isVerified = computed(() => page.props.affiliateVerificationStatus === 'verified');

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}, { immediate: true });

const props = defineProps({
    business: Object,
    stats: Object,
    recentCommissions: Array,
    qrCodes: Array,
});

const statCards = computed(() => [
    { icon: '\u{1F4B6}', label: 'Total Revenue', value: '\u20AC' + formatCurrency(props.stats.total_commissions) },
    { icon: '\u23F3', label: 'Pending', value: '\u20AC' + formatCurrency(props.stats.pending_commissions) },
    { icon: '\u{1F4F7}', label: 'Total Scans', value: formatWholeNumber(props.stats.total_scans) },
    { icon: '\u{1F3AF}', label: 'Conversion', value: props.stats.conversion_rate + '%' },
]);

const chartBars = computed(() => {
    const bars = [];
    const commissions = props.recentCommissions || [];
    for (let i = 0; i < 8; i++) {
        const c = commissions[i];
        const amount = c ? parseFloat(c.commission_amount) : 0;
        const maxAmount = Math.max(...commissions.map(x => parseFloat(x.commission_amount || 0)), 1);
        const pct = Math.max(5, (amount / maxAmount) * 100);
        bars.push({ height: pct + '%', label: 'W' + (i + 1) });
    }
    return bars;
});

function formatCurrency(val) {
    const num = parseFloat(val || 0);
    return num >= 1000 ? (num / 1000).toFixed(1) + 'k' : num.toFixed(2);
}

function formatWholeNumber(val) {
    const num = parseInt(val || 0);
    return num >= 1000 ? (num / 1000).toFixed(1) + 'k' : num.toLocaleString();
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return mins + ' min ago';
    const hours = Math.floor(mins / 60);
    if (hours < 24) return hours + ' hr ago';
    const days = Math.floor(hours / 24);
    return days + ' day' + (days > 1 ? 's' : '') + ' ago';
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
</style>
