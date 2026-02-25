<template>
    <AdminDashboardLayout>
        <div class="flex-col md:flex">
            <div class="flex-1 space-y-4 p-4 sm:p-6 lg:p-8 pt-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Business Reports</h2>
                        <p class="text-sm text-muted-foreground mt-1">Revenue, bookings, and fleet metrics in {{ adminCurrency }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Download class="w-4 h-4 mr-2" />
                                    Export
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem @click="exportData('pdf')">PDF</DropdownMenuItem>
                                <DropdownMenuItem @click="exportData('excel')">Excel</DropdownMenuItem>
                                <DropdownMenuItem @click="exportData('csv')">CSV</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <!-- Filters row -->
                <div class="flex flex-wrap items-center gap-3">
                    <Select v-model="reportPeriod" @update:modelValue="onPeriodChange">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Select Period" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="week">This Week</SelectItem>
                            <SelectItem value="month">This Month</SelectItem>
                            <SelectItem value="year">This Year</SelectItem>
                        </SelectContent>
                    </Select>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" class="text-sm">
                                <CalendarIcon class="w-4 h-4 mr-2 text-muted-foreground" />
                                <span>{{ dateRange.start }} - {{ dateRange.end }}</span>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <VDatePicker v-model="reportDate" is-range />
                        </PopoverContent>
                    </Popover>
                    <Button @click="applyDateRange" size="sm">Apply</Button>

                    <div class="ml-auto">
                        <Select v-model="selectedReport">
                            <SelectTrigger class="w-[130px]">
                                <SelectValue placeholder="Chart View" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="monthly">Monthly</SelectItem>
                                <SelectItem value="weekly">Weekly</SelectItem>
                                <SelectItem value="daily">Daily</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card class="border-l-4 border-l-emerald-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                            <DollarSign class="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ adminCurrency }} {{ formatNumber(totalRevenue) }}</div>
                            <p class="text-xs mt-1" :class="revenueGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ revenueGrowth >= 0 ? '+' : '' }}{{ revenueGrowth }}% from last period
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="border-l-4 border-l-blue-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Active Bookings</CardTitle>
                            <CalendarCheck class="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ activeBookings }}</div>
                            <p class="text-xs mt-1" :class="bookingsGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ bookingsGrowth >= 0 ? '+' : '' }}{{ bookingsGrowth }}% from last period
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="border-l-4 border-l-amber-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Fleet Utilization</CardTitle>
                            <Car class="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ fleetUtilization }}%</div>
                            <p class="text-xs mt-1" :class="fleetUtilizationGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ fleetUtilizationGrowth >= 0 ? '+' : '' }}{{ fleetUtilizationGrowth }}% from last period
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Charts -->
                <div class="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Revenue & Bookings</CardTitle>
                            <CardDescription>{{ selectedReport.charAt(0).toUpperCase() + selectedReport.slice(1) }} breakdown for selected period</CardDescription>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <BarChart
                                :data="currentChartData"
                                :categories="['revenue', 'bookings']"
                                index="name"
                                :rounded-corners="4"
                                :colors="['#10B981', '#153B4F']"
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Fleet Utilization Trend</CardTitle>
                            <CardDescription>Vehicle utilization rate over time</CardDescription>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <LineChart
                                :data="currentChartData"
                                index="name"
                                :categories="['fleetUtilization']"
                                :colors="['#F59E0B']"
                                :show-x-axis="true"
                                :show-y-axis="true"
                                :show-grid-lines="true"
                            />
                        </CardContent>
                    </Card>
                </div>

                <!-- Location breakdown -->
                <Card v-if="locationData && locationData.length > 0">
                    <CardHeader>
                        <CardTitle>Revenue by Location</CardTitle>
                        <CardDescription>Top pickup locations by revenue ({{ adminCurrency }})</CardDescription>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <BarChart
                            :data="locationData"
                            :categories="['revenue', 'bookings']"
                            index="name"
                            :rounded-corners="4"
                            :colors="['#10B981', '#6366F1']"
                        />
                    </CardContent>
                </Card>

                <!-- Table -->
                <Card>
                    <CardHeader>
                        <CardTitle>Booking Details</CardTitle>
                        <CardDescription>All bookings in the selected period with admin-level pricing</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead>Booking #</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Vendor</TableHead>
                                    <TableHead>Vehicle</TableHead>
                                    <TableHead class="text-right">Amount</TableHead>
                                    <TableHead>Payment</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="row in businessReportTableData.data"
                                    :key="row.id"
                                    class="hover:bg-muted/30"
                                >
                                    <TableCell class="font-medium">{{ row.booking_number }}</TableCell>
                                    <TableCell>{{ row.customer_name }}</TableCell>
                                    <TableCell>{{ row.vendor_name }}</TableCell>
                                    <TableCell>{{ row.vehicle }}</TableCell>
                                    <TableCell class="text-right font-medium">{{ row.currency }} {{ formatNumber(row.total_amount) }}</TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="paymentStatusClass(row.payment_status)"
                                        >
                                            {{ row.payment_status }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="bookingStatusClass(row.booking_status)"
                                        >
                                            {{ row.booking_status }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ row.booking_date }}</TableCell>
                                </TableRow>
                                <TableRow v-if="!businessReportTableData.data?.length">
                                    <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                                        No bookings found for this period.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <!-- Pagination -->
                        <div v-if="businessReportTableData.last_page > 1" class="flex items-center justify-between pt-4">
                            <p class="text-sm text-muted-foreground">
                                Page {{ businessReportTableData.current_page }} of {{ businessReportTableData.last_page }}
                                ({{ businessReportTableData.total }} total)
                            </p>
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="businessReportTableData.current_page <= 1"
                                    @click="onPageChange(businessReportTableData.current_page - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <template v-for="page in pageNumbers" :key="page">
                                    <Button
                                        v-if="page !== '...'"
                                        :variant="page === businessReportTableData.current_page ? 'default' : 'outline'"
                                        size="sm"
                                        class="w-8"
                                        @click="onPageChange(page)"
                                    >
                                        {{ page }}
                                    </Button>
                                    <span v-else class="px-1 text-muted-foreground">...</span>
                                </template>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="businessReportTableData.current_page >= businessReportTableData.last_page"
                                    @click="onPageChange(businessReportTableData.current_page + 1)"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
import LineChart from "@/Components/ui/chart-line/LineChart.vue";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { DatePicker as VDatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import jsPDF from 'jspdf';
import 'jspdf-autotable';
import { utils, writeFile } from 'xlsx';
import { unparse } from 'papaparse';
import {
    DollarSign, CalendarCheck, Car, Calendar as CalendarIcon,
    Download, ChevronLeft, ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    totalRevenue: { type: Number, default: 0 },
    revenueGrowth: { type: Number, default: 0 },
    activeBookings: { type: Number, default: 0 },
    bookingsGrowth: { type: Number, default: 0 },
    fleetUtilization: { type: Number, default: 0 },
    fleetUtilizationGrowth: { type: Number, default: 0 },
    monthlyData: { type: Array, default: () => [] },
    weeklyData: { type: Array, default: () => [] },
    dailyData: { type: Array, default: () => [] },
    locationData: { type: Array, default: () => [] },
    businessReportTableData: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1, total: 0 }) },
    dateRange: { type: Object, default: () => ({ start: '', end: '' }) },
    adminCurrency: { type: String, default: 'EUR' },
});

const reportPeriod = ref('year');
const selectedReport = ref('monthly');
const reportDate = ref({
    start: new Date(props.dateRange.start),
    end: new Date(props.dateRange.end),
});

const currentChartData = computed(() => {
    if (selectedReport.value === 'weekly') return props.weeklyData;
    if (selectedReport.value === 'daily') return props.dailyData;
    return props.monthlyData;
});

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
};

const paymentStatusClass = (status) => {
    const map = {
        paid: 'bg-emerald-50 text-emerald-700',
        partial: 'bg-amber-50 text-amber-700',
        pending: 'bg-slate-100 text-slate-600',
        failed: 'bg-red-50 text-red-700',
        refunded: 'bg-purple-50 text-purple-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const bookingStatusClass = (status) => {
    const map = {
        confirmed: 'bg-emerald-50 text-emerald-700',
        pending: 'bg-amber-50 text-amber-700',
        cancelled: 'bg-red-50 text-red-700',
        completed: 'bg-blue-50 text-blue-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const pageNumbers = computed(() => {
    const current = props.businessReportTableData.current_page;
    const last = props.businessReportTableData.last_page;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [];
    pages.push(1);
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) pages.push(i);
    if (current < last - 2) pages.push('...');
    pages.push(last);
    return pages;
});

const onPeriodChange = (value) => {
    reportPeriod.value = value;
    Inertia.get(route('admin.business.reports'), { period: value }, { preserveState: true, replace: true });
};

const applyDateRange = () => {
    Inertia.get(route('admin.business.reports'), {
        start_date: reportDate.value.start.toISOString().split('T')[0],
        end_date: reportDate.value.end.toISOString().split('T')[0],
    }, { preserveState: true, replace: true });
};

const onPageChange = (page) => {
    Inertia.get(route('admin.business.reports'), {
        page,
        start_date: reportDate.value.start.toISOString().split('T')[0],
        end_date: reportDate.value.end.toISOString().split('T')[0],
        period: reportPeriod.value,
    }, { preserveState: true, replace: true });
};

const exportColumns = [
    { accessorKey: 'booking_number', header: 'Booking #' },
    { accessorKey: 'customer_name', header: 'Customer' },
    { accessorKey: 'vendor_name', header: 'Vendor' },
    { accessorKey: 'vehicle', header: 'Vehicle' },
    { accessorKey: 'total_amount', header: 'Amount' },
    { accessorKey: 'payment_status', header: 'Payment Status' },
    { accessorKey: 'booking_status', header: 'Booking Status' },
    { accessorKey: 'booking_date', header: 'Date' },
];

const exportData = (format) => {
    const data = props.businessReportTableData.data;
    const headers = exportColumns.map(c => c.header);
    const body = data.map(row => exportColumns.map(c => row[c.accessorKey]));

    if (format === 'pdf') {
        const doc = new jsPDF();
        doc.autoTable({ head: [headers], body });
        doc.save('business_report.pdf');
    } else if (format === 'excel') {
        const worksheet = utils.json_to_sheet(data.map(row => {
            let newRow = {};
            exportColumns.forEach(c => newRow[c.header] = row[c.accessorKey]);
            return newRow;
        }));
        const workbook = utils.book_new();
        utils.book_append_sheet(workbook, worksheet, 'Business Report');
        writeFile(workbook, 'business_report.xlsx');
    } else if (format === 'csv') {
        const csv = unparse({ fields: headers, data: body });
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'business_report.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }
};

watch(() => props.dateRange, (newDateRange) => {
    reportDate.value = {
        start: new Date(newDateRange.start),
        end: new Date(newDateRange.end),
    };
});
</script>
