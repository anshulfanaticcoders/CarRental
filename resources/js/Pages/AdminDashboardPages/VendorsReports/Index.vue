<template>
    <AdminDashboardLayout>
        <div class="flex-col md:flex">
            <div class="flex-1 space-y-4 p-4 sm:p-6 lg:p-8 pt-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Vendors Report</h2>
                        <p class="text-sm text-muted-foreground mt-1">Vendor registration, activity, and growth analytics</p>
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

                <!-- Filters -->
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
                    <Card class="border-l-4 border-l-violet-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Vendors</CardTitle>
                            <Store class="h-4 w-4 text-violet-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ totalVendors }}</div>
                            <p class="text-xs mt-1" :class="totalVendorsGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ totalVendorsGrowth >= 0 ? '+' : '' }}{{ totalVendorsGrowth }}% from last period
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="border-l-4 border-l-emerald-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Active Vendors</CardTitle>
                            <UserCheck class="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ activeVendors }}</div>
                            <p class="text-xs mt-1" :class="activeVendorsGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ activeVendorsGrowth >= 0 ? '+' : '' }}{{ activeVendorsGrowth }}% from last period
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="border-l-4 border-l-amber-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">New Vendors</CardTitle>
                            <UserPlus class="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ newVendors }}</div>
                            <p class="text-xs mt-1" :class="newVendorsGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ newVendorsGrowth >= 0 ? '+' : '' }}{{ newVendorsGrowth }}% vs last period
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Charts row -->
                <div class="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Vendors Overview</CardTitle>
                            <CardDescription>Active vs new vendors per {{ selectedReport === 'daily' ? 'day' : selectedReport === 'weekly' ? 'week' : 'month' }}</CardDescription>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <BarChart
                                :data="currentChartData"
                                :categories="['active', 'new']"
                                index="name"
                                :rounded-corners="4"
                                :colors="['#10B981', '#F59E0B']"
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Vendor Growth</CardTitle>
                            <CardDescription>Cumulative vendor count over time</CardDescription>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <LineChart
                                :data="currentChartData"
                                index="name"
                                :categories="['total']"
                                :colors="['#8B5CF6']"
                                :show-x-axis="true"
                                :show-y-axis="true"
                                :show-grid-lines="true"
                            />
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Activities -->
                <Card v-if="recentActivities && recentActivities.length > 0">
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>Latest vendor activity in the selected period</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="activity in recentActivities"
                                :key="activity.id"
                                class="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/30"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                                        <Activity class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ activity.user.first_name }} {{ activity.user.last_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ activity.user.email }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm">{{ activity.activity_description }}</p>
                                    <p class="text-xs text-muted-foreground">{{ activity.created_at_formatted }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Table -->
                <Card>
                    <CardHeader>
                        <CardTitle>Vendor Directory</CardTitle>
                        <CardDescription>All vendors registered in the selected period</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead>ID</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Company</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Joined</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="row in vendorReportTableData.data"
                                    :key="row.id"
                                    class="hover:bg-muted/30"
                                >
                                    <TableCell class="font-medium">{{ row.id }}</TableCell>
                                    <TableCell>{{ row.name }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{ row.email }}</TableCell>
                                    <TableCell>{{ row.company_name }}</TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="vendorStatusClass(row.status)"
                                        >
                                            {{ row.status }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ formatDate(row.joined_at) }}</TableCell>
                                </TableRow>
                                <TableRow v-if="!vendorReportTableData.data?.length">
                                    <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                        No vendors found for this period.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <!-- Pagination -->
                        <div v-if="vendorReportTableData.last_page > 1" class="flex items-center justify-between pt-4">
                            <p class="text-sm text-muted-foreground">
                                Page {{ vendorReportTableData.current_page }} of {{ vendorReportTableData.last_page }}
                                ({{ vendorReportTableData.total }} total)
                            </p>
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="vendorReportTableData.current_page <= 1"
                                    @click="onPageChange(vendorReportTableData.current_page - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <template v-for="page in pageNumbers" :key="page">
                                    <Button
                                        v-if="page !== '...'"
                                        :variant="page === vendorReportTableData.current_page ? 'default' : 'outline'"
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
                                    :disabled="vendorReportTableData.current_page >= vendorReportTableData.last_page"
                                    @click="onPageChange(vendorReportTableData.current_page + 1)"
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
    Store, UserCheck, UserPlus, Activity,
    Calendar as CalendarIcon, Download, ChevronLeft, ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    totalVendors: { type: Number, default: 0 },
    totalVendorsGrowth: { type: Number, default: 0 },
    activeVendors: { type: Number, default: 0 },
    activeVendorsGrowth: { type: Number, default: 0 },
    newVendors: { type: Number, default: 0 },
    newVendorsGrowth: { type: Number, default: 0 },
    monthlyData: { type: Array, default: () => [] },
    weeklyData: { type: Array, default: () => [] },
    dailyData: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
    vendorReportTableData: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1, total: 0 }) },
    dateRange: { type: Object, default: () => ({ start: '', end: '' }) },
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

const vendorStatusClass = (status) => {
    const map = {
        active: 'bg-emerald-50 text-emerald-700',
        inactive: 'bg-slate-100 text-slate-600',
        suspended: 'bg-red-50 text-red-700',
        pending: 'bg-amber-50 text-amber-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const pageNumbers = computed(() => {
    const current = props.vendorReportTableData.current_page;
    const last = props.vendorReportTableData.last_page;
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
    Inertia.get(route('admin.vendors.reports'), { period: value }, { preserveState: true, replace: true });
};

const applyDateRange = () => {
    Inertia.get(route('admin.vendors.reports'), {
        start_date: reportDate.value.start.toISOString().split('T')[0],
        end_date: reportDate.value.end.toISOString().split('T')[0],
    }, { preserveState: true, replace: true });
};

const onPageChange = (page) => {
    Inertia.get(route('admin.vendors.reports'), {
        page,
        start_date: reportDate.value.start.toISOString().split('T')[0],
        end_date: reportDate.value.end.toISOString().split('T')[0],
        period: reportPeriod.value,
    }, { preserveState: true, replace: true });
};

const exportColumns = [
    { accessorKey: 'id', header: 'ID' },
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'company_name', header: 'Company' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'joined_at', header: 'Joined' },
];

const exportData = (format) => {
    const data = props.vendorReportTableData.data;
    const headers = exportColumns.map(c => c.header);
    const body = data.map(row => exportColumns.map(c => row[c.accessorKey]));

    if (format === 'pdf') {
        const doc = new jsPDF();
        doc.autoTable({ head: [headers], body });
        doc.save('vendor_report.pdf');
    } else if (format === 'excel') {
        const worksheet = utils.json_to_sheet(data.map(row => {
            let newRow = {};
            exportColumns.forEach(c => newRow[c.header] = row[c.accessorKey]);
            return newRow;
        }));
        const workbook = utils.book_new();
        utils.book_append_sheet(workbook, worksheet, 'Vendor Report');
        writeFile(workbook, 'vendor_report.xlsx');
    } else if (format === 'csv') {
        const csv = unparse({ fields: headers, data: body });
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'vendor_report.csv';
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
