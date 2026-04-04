<template>
    <AdminDashboardLayout>
        <div class="p-6 lg:p-8 overflow-x-hidden">
            <!-- Header -->
            <div class="mb-6 space-y-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">API Analytics</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Monitor API usage, performance, and revenue across all consumers</p>
                </div>
                <!-- Period Tabs + Custom Toggle -->
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex items-center rounded-lg border border-gray-200 bg-white p-0.5">
                        <button v-for="tab in quickTabs" :key="tab.value" @click="setPeriod(tab.value)"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                            :class="activePeriod === tab.value ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-900'">
                            {{ tab.label }}
                        </button>
                    </div>
                    <button @click="showCustomRange = !showCustomRange"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border transition-colors flex-shrink-0"
                        :class="activePeriod === 'custom' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-500 border-gray-200 hover:text-gray-900 hover:border-gray-300'">
                        <CalendarRange class="w-3.5 h-3.5" />
                    </button>
                    <!-- Custom Range (inline, appears next to toggle) -->
                    <template v-if="showCustomRange">
                        <input type="date" v-model="customFrom" class="h-8 w-[130px] rounded-md border border-gray-200 px-2 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-300" />
                        <span class="text-xs text-gray-400">to</span>
                        <input type="date" v-model="customTo" class="h-8 w-[130px] rounded-md border border-gray-200 px-2 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-300" />
                        <Button size="sm" class="h-8 text-xs flex-shrink-0" @click="applyCustomRange" :disabled="!customFrom || !customTo">Apply</Button>
                    </template>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                <div v-for="k in kpiCards" :key="k.key" class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ k.label }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatKpiValue(kpis?.[k.key]?.value, k.format) }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="k.bg">
                            <component :is="k.icon" class="w-4.5 h-4.5" :class="k.ic" />
                        </div>
                    </div>
                    <div v-if="kpis?.[k.key]?.delta != null" class="mt-2 flex items-center gap-1">
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold px-1.5 py-0.5 rounded-full"
                            :class="deltaClass(kpis[k.key].delta, k.inverted)">
                            <TrendingUp v-if="kpis[k.key].delta >= 0" class="w-3 h-3" />
                            <TrendingDown v-else class="w-3 h-3" />
                            {{ Math.abs(kpis[k.key].delta).toFixed(1) }}%
                        </span>
                        <span class="text-[11px] text-gray-400">vs prev</span>
                    </div>
                </div>
            </div>

            <!-- API Traffic (Full-width) -->
            <div class="bg-white rounded-xl border border-gray-200 mb-6">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-[15px] font-bold text-gray-900">API Traffic</h2>
                </div>
                <div class="p-4">
                    <apexchart v-if="trafficSeries?.length" type="area" height="280" :options="trafficChartOptions" :series="trafficChartSeries" />
                    <EmptyState v-else message="No traffic data for this period" />
                </div>
            </div>

            <!-- Grid Row 1: Bookings by Status + Revenue -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-[15px] font-bold text-gray-900">Bookings by Status</h2>
                    </div>
                    <div class="p-4">
                        <apexchart v-if="bookingsSeries?.length" type="bar" height="260" :options="bookingsChartOptions" :series="bookingsSeries" />
                        <EmptyState v-else message="No booking data for this period" />
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-[15px] font-bold text-gray-900">Revenue</h2>
                    </div>
                    <div class="p-4">
                        <apexchart v-if="revenueSeries?.length" type="area" height="260" :options="revenueChartOptions" :series="revenueChartSeries" />
                        <EmptyState v-else message="No revenue data for this period" />
                    </div>
                </div>
            </div>

            <!-- Grid Row 2: Endpoint Popularity + Error Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-[15px] font-bold text-gray-900">Endpoint Popularity</h2>
                    </div>
                    <div class="p-4">
                        <apexchart v-if="endpointPopularity?.length" type="bar" height="260" :options="endpointChartOptions" :series="endpointChartSeries" />
                        <EmptyState v-else message="No endpoint data for this period" />
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-[15px] font-bold text-gray-900">Error Breakdown</h2>
                    </div>
                    <div class="p-4">
                        <apexchart v-if="errorBreakdown?.length" type="bar" height="260" :options="errorChartOptions" :series="errorChartSeries" />
                        <EmptyState v-else message="No errors in this period" />
                    </div>
                </div>
            </div>

            <!-- Grid Row 3: Response Time + Plan Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-[15px] font-bold text-gray-900">Response Time by Endpoint</h2>
                    </div>
                    <div class="p-4">
                        <apexchart v-if="responseTimeByEndpoint?.length" type="bar" height="260" :options="responseTimeChartOptions" :series="responseTimeChartSeries" />
                        <EmptyState v-else message="No latency data for this period" />
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-[15px] font-bold text-gray-900">Plan Distribution</h2>
                    </div>
                    <div class="p-4">
                        <apexchart v-if="planDistribution?.length" type="donut" height="260" :options="planDonutOptions" :series="planDonutSeries" />
                        <EmptyState v-else message="No consumer data available" />
                    </div>
                </div>
            </div>

            <!-- Top Consumers Table (Full-width) -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-[15px] font-bold text-gray-900">Top Consumers</h2>
                </div>
                <Table v-if="topConsumers?.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Consumer</TableHead>
                            <TableHead class="w-24">Plan</TableHead>
                            <TableHead class="w-24">Mode</TableHead>
                            <TableHead class="w-28 text-right">Requests</TableHead>
                            <TableHead class="w-24 text-right">Bookings</TableHead>
                            <TableHead class="w-28 text-right">Revenue</TableHead>
                            <TableHead class="w-24 text-right">Errors</TableHead>
                            <TableHead class="w-24 text-right hidden lg:table-cell">Latency</TableHead>
                            <TableHead class="w-32 hidden lg:table-cell">Last Active</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="c in topConsumers" :key="c.id">
                            <TableCell class="font-medium">
                                <Link :href="route('admin.api-consumers.show', c.id)" class="text-sm text-gray-900 hover:text-blue-600 transition-colors">{{ c.name }}</Link>
                            </TableCell>
                            <TableCell><Badge :class="planBadge(c.plan)">{{ c.plan }}</Badge></TableCell>
                            <TableCell><Badge :class="modeBadge(c.mode)">{{ c.mode }}</Badge></TableCell>
                            <TableCell class="text-right text-sm tabular-nums">{{ (c.requests || 0).toLocaleString() }}</TableCell>
                            <TableCell class="text-right text-sm tabular-nums">{{ (c.bookings || 0).toLocaleString() }}</TableCell>
                            <TableCell class="text-right text-sm tabular-nums">&euro;{{ (c.revenue || 0).toFixed(2) }}</TableCell>
                            <TableCell class="text-right">
                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded" :class="errorRateCls(c.error_rate)">{{ (c.error_rate || 0).toFixed(1) }}%</span>
                            </TableCell>
                            <TableCell class="text-right text-sm text-gray-500 hidden lg:table-cell">{{ c.avg_latency ? c.avg_latency + 'ms' : '—' }}</TableCell>
                            <TableCell class="text-sm text-gray-500 hidden lg:table-cell">{{ fmtRel(c.last_active) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState v-else message="No consumer data for this period" />
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { BarChart3, TrendingUp, TrendingDown, DollarSign, AlertTriangle, Clock, Activity, CalendarRange } from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';

const apexchart = VueApexCharts;

// Inline EmptyState
const EmptyState = { props: ['message'], template: '<div class="px-5 py-10 text-center text-gray-400 text-sm">{{ message }}</div>' };

const props = defineProps({
    period: String,
    from: String,
    to: String,
    kpis: Object,
    trafficSeries: Array,
    bookingsSeries: Array,
    revenueSeries: Array,
    endpointPopularity: Array,
    errorBreakdown: Array,
    responseTimeByEndpoint: Array,
    planDistribution: Array,
    topConsumers: Array,
});

// --- Period controls ---
const quickTabs = [
    { label: 'Today', value: 'today' },
    { label: '7 days', value: '7d' },
    { label: '30 days', value: '30d' },
    { label: '90 days', value: '90d' },
];
const activePeriod = ref(props.period || '30d');
const customFrom = ref(props.from || '');
const customTo = ref(props.to || '');
const showCustomRange = ref(props.period === 'custom');

const setPeriod = (p) => {
    activePeriod.value = p;
    showCustomRange.value = false;
    router.get(route('admin.api-analytics'), { period: p }, { preserveState: true, replace: true });
};
const applyCustomRange = () => {
    if (customFrom.value && customTo.value) {
        router.get(route('admin.api-analytics'), { period: 'custom', from: customFrom.value, to: customTo.value }, { preserveState: true, replace: true });
    }
};

// --- Helpers ---
const shortDate = (d) => {
    if (!d) return '';
    const s = String(d);
    if (s.includes('T') || s.includes(' ')) {
        const dt = new Date(s);
        return dt.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    }
    const dt = new Date(s);
    return dt.toLocaleDateString('en-GB', { month: 'short', day: 'numeric' });
};
const shortenEndpoint = (ep) => ep ? ep.replace(/^\/v1/, '') : ep;
const fmtRel = (d) => {
    if (!d) return 'Never';
    const ms = Date.now() - new Date(d).getTime();
    const m = Math.floor(ms / 60000);
    if (m < 1) return 'just now';
    if (m < 60) return m + 'm ago';
    const h = Math.floor(m / 60);
    if (h < 24) return h + 'h ago';
    return Math.floor(h / 24) + 'd ago';
};
const formatKpiValue = (v, format) => {
    if (v == null) return '—';
    if (format === 'currency') return '\u20AC' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (format === 'percent') return Number(v).toFixed(1) + '%';
    if (format === 'ms') return Number(v).toLocaleString() + 'ms';
    return Number(v).toLocaleString();
};

// --- KPI card definitions ---
const kpiCards = [
    { key: 'total_requests', label: 'Total Requests', format: 'number', icon: BarChart3, bg: 'bg-blue-50', ic: 'text-blue-600', inverted: false },
    { key: 'total_bookings', label: 'Bookings', format: 'number', icon: Activity, bg: 'bg-emerald-50', ic: 'text-emerald-600', inverted: false },
    { key: 'total_revenue', label: 'Revenue', format: 'currency', icon: DollarSign, bg: 'bg-violet-50', ic: 'text-violet-600', inverted: false },
    { key: 'error_rate', label: 'Error Rate', format: 'percent', icon: AlertTriangle, bg: 'bg-amber-50', ic: 'text-amber-600', inverted: true },
    { key: 'avg_latency', label: 'Avg Latency', format: 'ms', icon: Clock, bg: 'bg-rose-50', ic: 'text-rose-600', inverted: true },
];

const deltaClass = (delta, inverted) => {
    const positive = inverted ? delta <= 0 : delta >= 0;
    return positive ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700';
};

// --- Badge helpers ---
const planBadge = (p) => ({ 'bg-gray-100 text-gray-700': p === 'basic', 'bg-blue-100 text-blue-700': p === 'premium', 'bg-purple-100 text-purple-700': p === 'enterprise' });
const modeBadge = (m) => ({ 'bg-amber-100 text-amber-800': m === 'sandbox', 'bg-emerald-100 text-emerald-800': m === 'live' });
const errorRateCls = (r) => {
    if (r == null || r < 1) return 'bg-emerald-50 text-emerald-700';
    if (r < 5) return 'bg-amber-50 text-amber-700';
    return 'bg-red-50 text-red-700';
};

// --- Chart shared config ---
const fontFamily = '"Plus Jakarta Sans", "IBM Plex Sans", sans-serif';
const gridConfig = { borderColor: 'rgba(226, 232, 240, 0.6)', strokeDashArray: 3 };
const tooltipTheme = { theme: 'dark', style: { fontSize: '12px', fontFamily } };
const animationConfig = { enabled: true, speed: 800, easing: 'easeinout' };

// --- Traffic Area Chart ---
const trafficChartSeries = computed(() => [{
    name: 'Requests',
    data: (props.trafficSeries || []).map(p => ({ x: p.x, y: p.y })),
}]);
const trafficChartOptions = computed(() => ({
    chart: { type: 'area', fontFamily, toolbar: { show: false }, zoom: { enabled: true }, animations: animationConfig },
    stroke: { curve: 'smooth', width: 2.5, colors: ['#22d3ee'] },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100], colorStops: [{ offset: 0, color: '#153b4f', opacity: 0.4 }, { offset: 100, color: '#153b4f', opacity: 0.05 }] } },
    colors: ['#22d3ee'],
    markers: { size: 4, colors: ['#22d3ee'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
    grid: gridConfig,
    dataLabels: { enabled: false },
    tooltip: { ...tooltipTheme, x: { formatter: (v) => shortDate(v) } },
    xaxis: { type: 'category', labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' }, formatter: (v) => shortDate(v) }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' } } },
}));

// --- Bookings Stacked Bar ---
const bookingsChartOptions = computed(() => ({
    chart: { type: 'bar', stacked: true, fontFamily, toolbar: { show: false }, animations: animationConfig },
    colors: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
    plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
    grid: gridConfig,
    dataLabels: { enabled: false },
    tooltip: { ...tooltipTheme, x: { formatter: (v) => shortDate(v) } },
    xaxis: { type: 'category', labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' }, formatter: (v) => shortDate(v) }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' } } },
    legend: { position: 'top', horizontalAlign: 'right', fontFamily, fontSize: '12px', labels: { colors: '#64748b' }, markers: { size: 6, shape: 'circle' } },
}));

// --- Revenue Area Chart ---
const revenueChartSeries = computed(() => [{
    name: 'Revenue',
    data: (props.revenueSeries || []).map(p => ({ x: p.x, y: p.y })),
}]);
const revenueChartOptions = computed(() => ({
    chart: { type: 'area', fontFamily, toolbar: { show: false }, animations: animationConfig },
    stroke: { curve: 'smooth', width: 2.5, colors: ['#10b981'] },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100], colorStops: [{ offset: 0, color: '#10b981', opacity: 0.35 }, { offset: 100, color: '#10b981', opacity: 0.05 }] } },
    colors: ['#10b981'],
    markers: { size: 4, colors: ['#10b981'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
    grid: gridConfig,
    dataLabels: { enabled: false },
    tooltip: { ...tooltipTheme, x: { formatter: (v) => shortDate(v) }, y: { formatter: (v) => '\u20AC' + Number(v).toFixed(2) } },
    xaxis: { type: 'category', labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' }, formatter: (v) => shortDate(v) }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' }, formatter: (v) => '\u20AC' + Number(v).toLocaleString() } },
}));

// --- Endpoint Popularity Horizontal Bar ---
const endpointChartSeries = computed(() => [{
    name: 'Requests',
    data: (props.endpointPopularity || []).map(e => e.count),
}]);
const endpointChartOptions = computed(() => ({
    chart: { type: 'bar', fontFamily, toolbar: { show: false }, animations: animationConfig },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
    colors: ['#153b4f'],
    grid: gridConfig,
    dataLabels: { enabled: false },
    tooltip: tooltipTheme,
    xaxis: { categories: (props.endpointPopularity || []).map(e => shortenEndpoint(e.endpoint)), labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontFamily, fontSize: '11px', colors: '#64748b' } } },
}));

// --- Error Breakdown Horizontal Stacked Bar ---
const errorChartSeries = computed(() => [
    { name: '4xx Client', data: (props.errorBreakdown || []).map(e => e.client_errors) },
    { name: '5xx Server', data: (props.errorBreakdown || []).map(e => e.server_errors) },
]);
const errorChartOptions = computed(() => ({
    chart: { type: 'bar', stacked: true, fontFamily, toolbar: { show: false }, animations: animationConfig },
    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '65%' } },
    colors: ['#f59e0b', '#ef4444'],
    grid: gridConfig,
    dataLabels: { enabled: false },
    tooltip: tooltipTheme,
    xaxis: { categories: (props.errorBreakdown || []).map(e => shortenEndpoint(e.endpoint)), labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontFamily, fontSize: '11px', colors: '#64748b' } } },
    legend: { position: 'top', horizontalAlign: 'right', fontFamily, fontSize: '12px', labels: { colors: '#64748b' }, markers: { size: 6, shape: 'circle' } },
}));

// --- Response Time Grouped Bar ---
const responseTimeChartSeries = computed(() => [
    { name: 'Avg (ms)', data: (props.responseTimeByEndpoint || []).map(e => e.avg) },
    { name: 'P95 (ms)', data: (props.responseTimeByEndpoint || []).map(e => e.p95) },
]);
const responseTimeChartOptions = computed(() => ({
    chart: { type: 'bar', fontFamily, toolbar: { show: false }, animations: animationConfig },
    plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
    colors: ['#153b4f', '#22d3ee'],
    grid: gridConfig,
    dataLabels: { enabled: false },
    tooltip: { ...tooltipTheme, y: { formatter: (v) => v + 'ms' } },
    xaxis: { categories: (props.responseTimeByEndpoint || []).map(e => shortenEndpoint(e.endpoint)), labels: { style: { fontFamily, fontSize: '11px', colors: '#64748b' }, rotate: -45, trim: true, maxHeight: 80 }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontFamily, fontSize: '11px', colors: '#94a3b8' }, formatter: (v) => v + 'ms' } },
    legend: { position: 'top', horizontalAlign: 'right', fontFamily, fontSize: '12px', labels: { colors: '#64748b' }, markers: { size: 6, shape: 'circle' } },
}));

// --- Plan Distribution Donut ---
const planColorMap = { basic: '#94a3b8', premium: '#3b82f6', enterprise: '#8b5cf6' };
const planDonutSeries = computed(() => (props.planDistribution || []).map(p => p.count));
const planDonutOptions = computed(() => {
    const labels = (props.planDistribution || []).map(p => p.plan);
    const colors = labels.map(l => planColorMap[l] || '#94a3b8');
    const total = (props.planDistribution || []).reduce((s, p) => s + p.count, 0);
    return {
        chart: { type: 'donut', fontFamily, animations: animationConfig },
        colors,
        labels,
        plotOptions: { pie: { donut: { size: '60%', labels: { show: true, name: { show: true, fontFamily, fontSize: '13px', color: '#64748b' }, value: { show: true, fontFamily, fontSize: '22px', fontWeight: 700, color: '#1e293b' }, total: { show: true, label: 'Total', fontFamily, fontSize: '13px', color: '#64748b', formatter: () => total } } } } },
        dataLabels: { enabled: true, style: { fontFamily, fontSize: '12px', fontWeight: 600 }, dropShadow: { enabled: false } },
        tooltip: tooltipTheme,
        legend: { position: 'bottom', fontFamily, fontSize: '12px', labels: { colors: '#64748b' }, markers: { size: 6, shape: 'circle' } },
        stroke: { width: 2, colors: ['#fff'] },
    };
});
</script>
