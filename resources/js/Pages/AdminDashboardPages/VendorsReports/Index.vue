<template>
    <AdminDashboardLayout>
        <div class="flex-col md:flex">
            <div class="flex-1 space-y-4 p-8 pt-6">
                <div class="flex items-center justify-between space-y-2">
                    <h2 class="text-[1.5rem] font-semibold tracking-tight">Vendors Report</h2>
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
                            <CardTitle class="text-sm font-medium">Total Vendors</CardTitle>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ totalVendors }}</div>
                            <p class="text-xs text-muted-foreground">{{ totalVendorsGrowth >= 0 ? `+${totalVendorsGrowth}%` : `${totalVendorsGrowth}%` }} from last period</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Active Vendors</CardTitle>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ activeVendors }}</div>
                            <p class="text-xs text-muted-foreground">{{ activeVendorsGrowth >= 0 ? `+${activeVendorsGrowth}%` : `${activeVendorsGrowth}%` }} from last period</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">New Vendors</CardTitle>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-6 w-6 text-muted-foreground"><path d="M3 13h18l-2-6H5l-2 6Z" /><circle cx="7.5" cy="17.5" r="2.5" /><circle cx="16.5" cy="17.5" r="2.5" /><path d="M5 13V6h14v7M9 6V3M15 6V3" /></svg>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ newVendors }}</div>
                            <p class="text-xs text-muted-foreground">{{ newVendorsGrowth >= 0 ? `+${newVendorsGrowth}%` : `${newVendorsGrowth}%` }} vs last period</p>
                        </CardContent>
                    </Card>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                    <Card class="col-span-3">
                        <CardHeader>
                            <CardTitle class="text-[1.5rem] font-semibold">Vendors Overview</CardTitle>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <BarChart v-if="selectedReport === 'monthly'" :data="monthlyData" :categories="['active', 'new']" :index="'name'" :rounded-corners="4" :colors="['#10B981', '#FFC633']"/>
                            <BarChart v-if="selectedReport === 'weekly'" :data="weeklyData" :categories="['active', 'new']" :index="'name'" :rounded-corners="4" :colors="['#10B981', '#FFC633']"/>
                            <BarChart v-if="selectedReport === 'daily'" :data="dailyData" :categories="['active', 'new']" :index="'name'" :rounded-corners="4" :colors="['#10B981', '#FFC633']"/>
                        </CardContent>
                    </Card>
                    <Card class="col-span-2">
                        <CardHeader>
                            <CardTitle class="text-[1.5rem] font-semibold">Vendor Growth</CardTitle>
                        </CardHeader>
                        <CardContent class="pl-2">
                            <LineChart v-if="selectedReport === 'monthly'" :data="monthlyData" index="name" :categories="['total']" :colors="['blue']" :show-x-axis="true" :show-y-axis="true" :show-grid-lines="true" class="h-96 w-full" />
                            <LineChart v-if="selectedReport === 'weekly'" :data="weeklyData" index="name" :categories="['total']" :colors="['blue']" :show-x-axis="true" :show-y-axis="true" :show-grid-lines="true" class="h-96 w-full" />
                            <LineChart v-if="selectedReport === 'daily'" :data="dailyData" index="name" :categories="['total']" :colors="['blue']" :show-x-axis="true" :show-y-axis="true" :show-grid-lines="true" class="h-96 w-full" />
                        </CardContent>
                    </Card>
                    <Card class="col-span-2">
                        <CardHeader>
                            <CardTitle class="text-[1.5rem] font-semibold">Recent Activities</CardTitle>
                            <CardDescription>Recent vendor activity.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-8">
                                <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center">
                                    <div class="ml-4 space-y-1">
                                        <p class="text-sm font-medium leading-none">{{ activity.user.first_name }} {{ activity.user.last_name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ activity.user.email }}</p>
                                    </div>
                                    <div class="ml-auto text-right">
                                        <p class="text-sm font-medium leading-none">{{ activity.activity_description }}</p>
                                        <p class="text-xs text-muted-foreground">{{ activity.created_at_formatted }}</p>
                                    </div>
                                </div>
                                <div v-if="recentActivities.length === 0">No recent activity.</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Vendor Report</CardTitle>
                        <CardDescription>A list of all vendors in the selected period.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead v-for="column in columns" :key="column.accessorKey">{{ column.header }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in vendorReportTableData.data" :key="row.id">
                                    <TableCell v-for="column in columns" :key="column.accessorKey">{{ row[column.accessorKey] }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <Pagination :currentPage="vendorReportTableData.current_page" :totalPages="vendorReportTableData.last_page" @page-change="onPageChange" />
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
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps([
    'totalVendors',
    'totalVendorsGrowth',
    'activeVendors',
    'activeVendorsGrowth',
    'newVendors',
    'newVendorsGrowth',
    'monthlyData',
    'weeklyData',
    'dailyData',
    'recentActivities',
    'vendorReportTableData',
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
        page: page,
        start_date: reportDate.value.start.toISOString().split('T')[0],
        end_date: reportDate.value.end.toISOString().split('T')[0],
        period: reportPeriod.value,
    }, { preserveState: true, replace: true });
};

const columns = [
    { accessorKey: 'id', header: 'ID' },
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'company_name', header: 'Company' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'joined_at', header: 'Joined' },
];

const exportData = (format) => {
    const data = props.vendorReportTableData.data;
    const headers = columns.map(c => c.header);
    const body = data.map(row => columns.map(c => row[c.accessorKey]));

    if (format === 'pdf') {
        const doc = new jsPDF();
        doc.autoTable({ head: [headers], body: body });
        doc.save('vendor_report.pdf');
    } else if (format === 'excel') {
        const worksheet = utils.json_to_sheet(data.map(row => {
            let newRow = {};
            columns.forEach(c => newRow[c.header] = row[c.accessorKey]);
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
