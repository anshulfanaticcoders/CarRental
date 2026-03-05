<script setup>
import { Link } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { LineChart } from '@/Components/ui/chart-line';
import { BarChart } from '@/Components/ui/chart-bar';
import { Users, UserCheck, ShieldAlert, Euro, Wallet } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    revenueTrend: Array,
    topAffiliates: Array,
    recentCommissions: Array,
});

const statCards = [
    { label: 'Total Partners', value: props.stats.totalPartners, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Active Partners', value: props.stats.activePartners, icon: UserCheck, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { label: 'Pending Verification', value: props.stats.pendingVerification, icon: ShieldAlert, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Total Revenue', value: `\u20AC${props.stats.totalRevenue.toFixed(2)}`, icon: Euro, color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: 'Pending Payouts', value: `\u20AC${props.stats.pendingPayouts.toFixed(2)}`, icon: Wallet, color: 'text-rose-600', bg: 'bg-rose-50' },
];

const statusColor = (status) => {
    const map = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-blue-100 text-blue-700', paid: 'bg-emerald-100 text-emerald-700', rejected: 'bg-red-100 text-red-700' };
    return map[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Affiliate Overview</h1>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <Card v-for="card in statCards" :key="card.label">
                    <CardContent class="flex items-center gap-3 p-4">
                        <div :class="[card.bg, 'rounded-lg p-2.5']">
                            <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ card.label }}</p>
                            <p class="text-lg font-bold">{{ card.value }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue Trend -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-semibold">Revenue Trend (30 days)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <LineChart
                            v-if="revenueTrend.length"
                            :data="revenueTrend"
                            index="date"
                            :categories="['revenue']"
                            :colors="['#6366f1']"
                            :show-legend="false"
                            class="h-[280px]"
                        />
                        <p v-else class="text-center text-gray-400 py-12">No revenue data yet.</p>
                    </CardContent>
                </Card>

                <!-- Top Affiliates -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-semibold">Top 5 Affiliates by Revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            v-if="topAffiliates.length"
                            :data="topAffiliates"
                            index="name"
                            :categories="['revenue']"
                            :colors="['#10b981']"
                            :show-legend="false"
                            class="h-[280px]"
                        />
                        <p v-else class="text-center text-gray-400 py-12">No affiliate data yet.</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Commissions -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Recent Commissions</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">Date</TableHead>
                                <TableHead class="px-4">Partner</TableHead>
                                <TableHead class="px-4">Amount</TableHead>
                                <TableHead class="px-4">Rate</TableHead>
                                <TableHead class="px-4">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="c in recentCommissions" :key="c.id">
                                <TableCell class="px-4 text-gray-500 text-xs">
                                    {{ new Date(c.created_at).toLocaleDateString() }}
                                </TableCell>
                                <TableCell class="px-4 font-medium">{{ c.business?.name || '-' }}</TableCell>
                                <TableCell class="px-4 font-semibold">&euro;{{ parseFloat(c.commission_amount).toFixed(2) }}</TableCell>
                                <TableCell class="px-4">{{ c.commission_rate }}%</TableCell>
                                <TableCell class="px-4">
                                    <span :class="[statusColor(c.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ c.status }}
                                    </span>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!recentCommissions.length">
                                <TableCell colspan="5" class="px-4 text-center text-gray-400 py-8">No commissions yet.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminDashboardLayout>
</template>
