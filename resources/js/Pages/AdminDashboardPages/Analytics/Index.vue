<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { LineChart } from '@/Components/ui/chart-line';
import { BarChart } from '@/Components/ui/chart-bar';
import PieChart from '@/Components/ui/chart-pie/PieChart.vue';
import { CalendarDays, TrendingUp, Wallet, BadgeCheck, WalletCards, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
  filters: Object,
  currency: String,
  metrics: Object,
  trends: Object,
  breakdowns: Object,
});

const range = ref(props.filters?.range || 'week');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const trendSeries = computed(() => props.trends?.series || []);

const bookingPieData = computed(() => ([
  { label: 'Pending', value: props.breakdowns?.bookings?.pending || 0, color: '#F59E0B' },
  { label: 'Confirmed', value: props.breakdowns?.bookings?.confirmed || 0, color: '#153B4F' },
  { label: 'Completed', value: props.breakdowns?.bookings?.completed || 0, color: '#10B981' },
  { label: 'Cancelled', value: props.breakdowns?.bookings?.cancelled || 0, color: '#EF4444' },
]));

const paymentPieData = computed(() => ([
  { label: 'Succeeded', value: props.breakdowns?.payments?.succeeded || 0, color: '#10B981' },
  { label: 'Pending', value: props.breakdowns?.payments?.pending || 0, color: '#F59E0B' },
  { label: 'Failed', value: props.breakdowns?.payments?.failed || 0, color: '#EF4444' },
]));

const formatNumber = (value) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0));
};

const getCurrencySymbol = (currency) => {
  const symbols = {
    USD: '$',
    EUR: '€',
    GBP: '£',
    JPY: '¥',
    AUD: 'A$',
    CAD: 'C$',
    CHF: 'Fr',
    HKD: 'HK$',
    SGD: 'S$',
    SEK: 'kr',
    KRW: '₩',
    NOK: 'kr',
    NZD: 'NZ$',
    INR: '₹',
    MXN: 'Mex$',
    ZAR: 'R',
    AED: 'AED',
  };
  return symbols[currency] || '$';
};

const formatCurrency = (value) => `${getCurrencySymbol(props.currency)}${formatNumber(value)}`;

const growthLabel = (value) => `${value >= 0 ? '+' : ''}${value}%`;

const updateFilters = () => {
  const payload = {
    range: range.value,
  };
  if (range.value === 'custom') {
    payload.start_date = startDate.value;
    payload.end_date = endDate.value;
  }
  router.get(route('admin.analytics.index'), payload, { preserveState: true, preserveScroll: true });
};

watch([range, startDate, endDate], () => {
  updateFilters();
});
</script>

<template>
  <AdminDashboardLayout>
    <div class="flex flex-col gap-6 p-8">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-semibold text-slate-900">Analytics Overview</h2>
          <p class="text-sm text-slate-500">Bookings and payments trends using admin currency snapshots.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <Select v-model="range">
            <SelectTrigger class="w-44">
              <SelectValue placeholder="Range" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="day">Today</SelectItem>
              <SelectItem value="week">Last 7 days</SelectItem>
              <SelectItem value="month">Last 30 days</SelectItem>
              <SelectItem value="custom">Custom range</SelectItem>
            </SelectContent>
          </Select>
          <input
            v-if="range === 'custom'"
            v-model="startDate"
            type="date"
            class="h-10 rounded-md border border-slate-200 px-3 text-sm"
          />
          <input
            v-if="range === 'custom'"
            v-model="endDate"
            type="date"
            class="h-10 rounded-md border border-slate-200 px-3 text-sm"
          />
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-medium">Total Bookings</CardTitle>
            <CalendarDays class="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ metrics.total_bookings }}</div>
            <p class="text-xs text-slate-500">{{ growthLabel(metrics.booking_growth) }} vs previous period</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
            <Wallet class="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(metrics.total_revenue) }}</div>
            <p class="text-xs text-slate-500">{{ growthLabel(metrics.revenue_growth) }} vs previous period</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-medium">Paid Revenue</CardTitle>
            <BadgeCheck class="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(metrics.paid_revenue) }}</div>
            <p class="text-xs text-slate-500">Pending {{ formatCurrency(metrics.pending_revenue) }}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-medium">Total Payments</CardTitle>
            <WalletCards class="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ metrics.total_payments }}</div>
            <p class="text-xs text-slate-500">Success rate {{ metrics.success_rate }}%</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-medium">Pending Revenue</CardTitle>
            <AlertTriangle class="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(metrics.pending_revenue) }}</div>
            <p class="text-xs text-slate-500">Awaiting payment</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-medium">Revenue Momentum</CardTitle>
            <TrendingUp class="h-4 w-4 text-slate-400" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ growthLabel(metrics.revenue_growth) }}</div>
            <p class="text-xs text-slate-500">Admin currency {{ currency }}</p>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Bookings & Payments Trend</CardTitle>
            <CardDescription>Daily activity for the selected range.</CardDescription>
          </CardHeader>
          <CardContent class="pl-2">
            <LineChart
              :data="trendSeries"
              :categories="['bookings', 'payments']"
              index="date"
              :colors="['#153B4F', '#10B981']"
            />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Revenue Breakdown</CardTitle>
            <CardDescription>Total vs paid vs pending revenue.</CardDescription>
          </CardHeader>
          <CardContent class="pl-2">
            <BarChart
              :data="trendSeries"
              :categories="['revenue_total', 'revenue_paid', 'revenue_pending']"
              index="date"
              :rounded-corners="4"
              :colors="['#153B4F', '#10B981', '#F59E0B']"
            />
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Booking Status Distribution</CardTitle>
            <CardDescription>All booking statuses in the selected range.</CardDescription>
          </CardHeader>
          <CardContent class="flex justify-center">
            <PieChart :data="bookingPieData" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Payment Status Distribution</CardTitle>
            <CardDescription>All payment statuses in the selected range.</CardDescription>
          </CardHeader>
          <CardContent class="flex justify-center">
            <PieChart :data="paymentPieData" />
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
