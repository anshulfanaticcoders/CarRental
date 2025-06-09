<template>
    <AdminDashboardLayout>
        <div class="flex-col md:flex">
            <div class="flex-1 space-y-4 p-8 pt-6">
                <div class="flex items-center justify-between space-y-2">
                    <h2 class="text-[1.5rem] font-semibold tracking-tight">Business Report</h2>
                </div>
                <div class="flex items-center justify-between space-y-2">
                    <div class="flex items-center space-x-2">
                        <Select v-model="reportPeriod" @update:modelValue="onPeriodChange">
                            <SelectTrigger>
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
                                <Button variant="outline">
                                    <i class="far fa-calendar-alt mr-2"></i>
                                    <span>{{ dateRange.start }} - {{ dateRange.end }}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <VDatePicker v-model="reportDate" is-range />
                            </PopoverContent>
                        </Popover>
                        <Button @click="applyDateRange">Apply</Button>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                Export
                                <i class="fas fa-chevron-down ml-2"></i>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem @click="exportData('pdf')">PDF</DropdownMenuItem>
                            <DropdownMenuItem @click="exportData('excel')">Excel</DropdownMenuItem>
                            <DropdownMenuItem @click="exportData('csv')">CSV</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <label for="report-type" class="text-sm font-medium">Report Type:</label>
                        <select id="report-type" v-model="selectedReport" class="p-2 border rounded">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ totalRevenue.toLocaleString() }}</div>
                            <p class="text-xs text-muted-foreground">{{ revenueGrowth >= 0 ? `+${revenueGrowth}%` : `${revenueGrowth}%` }} from last period</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Active Bookings</CardTitle>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ activeBookings }}</div>
                            <p class="text-xs text-muted-foreground">{{ bookingsGrowth >= 0 ? `+${bookingsGrowth}%` : `${bookingsGrowth}%` }} from last period</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Fleet Utilization</CardTitle>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M19 17h2l.64-2.54c.24-.959.24-1.962 0-2.92l-1.07-4.27A3 3 0 0 0 17.66 5H4.34a3 3 0 0 0-2.91 2.27L.36 11.54a7.971 7.971 0 0 0 0 2.92L1 17h2" /><circle cx="12" cy="17" r="2" /></svg>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ fleetUtilization }}%</div>
                            <p class="text-xs text-muted-foreground">{{ fleetUtilizationGrowth >= 0 ? `+${fleetUtilizationGrowth}%` : `${fleetUtilizationGrowth}%` }} from last period</p>
                        </CardContent>
                    </Card>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                    <Card class="col-span-7">
                        <CardHeader>
                            <CardTitle class="text-[1.5rem] font-semibold">Business Overview</CardTitle>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <BarChart v-if="selectedReport === 'monthly'" :data="monthlyData" :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4" :colors="['#10B981', '#153B4F', '#FFC633']"/>
                            <BarChart v-if="selectedReport === 'weekly'" :data="weeklyData" :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4" :colors="['#10B981', '#153B4F', '#FFC633']"/>
                            <BarChart v-if="selectedReport === 'daily'" :data="dailyData" :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4" :colors="['#10B981', '#153B4F', '#FFC633']"/>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Business Report</CardTitle>
                        <CardDescription>A list of all bookings in the selected period.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead v-for="column in columns" :key="column.accessorKey">{{ column.header }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in businessReportTableData.data" :key="row.id">
                                    <TableCell v-for="column in columns" :key="column.accessorKey">{{ row[column.accessorKey] }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <Pagination :currentPage="businessReportTableData.current_page" :totalPages="businessReportTableData.last_page" @page-change="onPageChange" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
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
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps([
    'totalRevenue',
    'revenueGrowth',
    'activeBookings',
    'bookingsGrowth',
    'fleetUtilization',
    'fleetUtilizationGrowth',
    'monthlyData',
    'weeklyData',
    'dailyData',
    'locationData',
    'businessReportTableData',
    'dateRange'
]);

const reportPeriod = ref('year');
const selectedReport = ref('monthly');
const reportDate = ref({
    start: new Date(props.dateRange.start),
    end: new Date(props.dateRange.end),
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
        page: page,
        start_date: reportDate.value.start.toISOString().split('T')[0],
        end_date: reportDate.value.end.toISOString().split('T')[0],
        period: reportPeriod.value,
    }, { preserveState: true, replace: true });
};

const columns = [
    { accessorKey: 'booking_number', header: 'Booking #' },
    { accessorKey: 'customer_name', header: 'Customer' },
    { accessorKey: 'vendor_name', header: 'Vendor' },
    { accessorKey: 'vehicle', header: 'Vehicle' },
    { accessorKey: 'total_amount', header: 'Amount' },
    { accessorKey: 'payment_status', header: 'Payment Status' },
    { accessorKey: 'booking_date', header: 'Date' },
];

const exportData = (format) => {
    const data = props.businessReportTableData.data;
    const headers = columns.map(c => c.header);
    const body = data.map(row => columns.map(c => row[c.accessorKey]));

    if (format === 'pdf') {
        const doc = new jsPDF();
        doc.autoTable({ head: [headers], body: body });
        doc.save('business_report.pdf');
    } else if (format === 'excel') {
        const worksheet = utils.json_to_sheet(data.map(row => {
            let newRow = {};
            columns.forEach(c => newRow[c.header] = row[c.accessorKey]);
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
