<template>
  <AdminDashboardLayout>
    <div class="hidden flex-col md:flex">
      <div class="flex-1 space-y-4 p-8 pt-6">
        <div class="flex items-center justify-between space-y-2">
          <h2 class="text-[1.5rem] font-semibold tracking-tight">Business Reports</h2>
        </div>
        <Tabs default-value="overview" class="space-y-4">
          <TabsList class="flex gap-2 justify-start">
            <div>
              <select v-model="selectedReport" class="p-2 border rounded">
                <option value="monthly">Monthly Performance</option>
                <option value="weekly">Weekly Performance</option>
                <option value="daily">Daily Performance</option>
                <option value="location">Revenue by Location</option>
              </select>
            </div>
            <div class="flex flex-col items-end">
              <div>
                <select v-model="exportFormat" class="p-2 border rounded">
                  <option value="pdf">Export as PDF</option>
                  <option value="excel">Export as Excel</option>
                  <option value="csv">Export as CSV</option>
                </select>
                <button @click="exportData" class="ml-2 p-2 bg-blue-500 text-white rounded">Export</button>
              </div>
              <div v-if="error" class="text-sm text-red-500 mt-2">
                {{ error }}
              </div>
            </div>
          </TabsList>
          <TabsContent value="overview" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <!-- Total Revenue -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">${{ totalRevenue.toLocaleString() }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ revenueGrowth >= 0 ? `↑ ${revenueGrowth}%` : `↓ ${Math.abs(revenueGrowth)}%` }} vs last
                  </p>
                </CardContent>
              </Card>

              <!-- Active Bookings -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Active Bookings</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ activeBookings }}</div>
                  <p class="text-xs text-muted-foreground">
                    +{{ bookingsChange }} from yesterday
                  </p>
                </CardContent>
              </Card>

              <!-- Fleet Utilization -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Fleet Utilization</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path
                      d="M19 17h2l.64-2.54c.24-.959.24-1.962 0-2.92l-1.07-4.27A3 3 0 0 0 17.66 5H4.34a3 3 0 0 0-2.91 2.27L.36 11.54a7.971 7.971 0 0 0 0 2.92L1 17h2" />
                    <circle cx="12" cy="17" r="2" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ fleetUtilization }}%</div>
                  <p class="text-xs text-muted-foreground">
                    {{ utilizationGrowth >= 0 ? `↑ ${utilizationGrowth}%` : `↓ ${Math.abs(utilizationGrowth)}%` }} this
                    week
                  </p>
                </CardContent>
              </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
              <Card class="col-span-4">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold">Revenue Overview</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">
                  <!-- Conditionally render bar charts -->
                  <BarChart v-if="selectedReport === 'monthly'" :data="monthlyData"
                    :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4"
                    :colors="['#10B981', '#153B4F', '#FFC633']" />

                  <BarChart v-if="selectedReport === 'weekly'" :data="weeklyData"
                    :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4"
                    :colors="['#10B981', '#153B4F', '#FFC633']" />
                  <BarChart v-if="selectedReport === 'daily'" :data="dailyData"
                    :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4"
                    :colors="['#10B981', '#153B4F', '#FFC633']" />
                  <BarChart v-if="selectedReport === 'location'" :data="locationData"
                    :categories="['revenue', 'bookings', 'fleetUtilization']" :index="'name'" :rounded-corners="4"
                    :colors="['#10B981', '#153B4F', '#FFC633']" />
                </CardContent>
              </Card>
              <Card class="col-span-3">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold">Booking Trends</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">
                  <LineChart :data="monthlyData" index="name" :categories="['bookings']" :colors="['blue']"
                    :show-x-axis="true" :show-y-axis="true" :show-grid-lines="true" class="h-96 w-full" />
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
import LineChart from "@/Components/ui/chart-line/LineChart.vue";
import Tabs from "@/Components/ui/tabs/Tabs.vue";
import TabsContent from "@/Components/ui/tabs/TabsContent.vue";
import TabsList from "@/Components/ui/tabs/TabsList.vue";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import jsPDF from 'jspdf';
import 'jspdf-autotable';
import { utils, writeFile } from 'xlsx';
import { unparse } from 'papaparse';
import { ref } from "vue";
const selectedReport = ref("monthly");
const exportFormat = ref('pdf');
const error = ref('');

const props = defineProps([
  'totalRevenue',
  'revenueGrowth',
  'activeBookings',
  'bookingsChange',
  'fleetUtilization',
  'utilizationGrowth',
  'monthlyData',
  'weeklyData',
  'dailyData',
  'locationData'
]);

const prepareExportData = () => {
  try {
    let data;
    
    // Select the correct dataset based on the user's selection
    if (selectedReport.value === "monthly") {
      data = props.monthlyData;
    } else if (selectedReport.value === "weekly") {
      data = props.weeklyData;
    } else if (selectedReport.value === "daily") {
      data = props.dailyData;
    }else if (selectedReport.value === "location") {
      data = props.locationData;
    }

    // Transform data to match export format
    return data.map(item => ({
      Date: item.name, // "Month", "Week", or "Day" dynamically
      Revenue: item.revenue,
      "Active Bookings": item.bookings,
      "Fleet Utilization (%)": item.fleetUtilization
    }));
  } catch (err) {
    error.value = "Error preparing data for export";
    throw err;
  }
};


// Export functions remain the same as your user report
const exportToPDF = async () => {
  try {
    const doc = new jsPDF();
    const data = prepareExportData();

    doc.setFontSize(16);
    doc.text('Business Analytics Report', 15, 15);

    doc.setFontSize(10);
    doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 15, 25);

    let yPosition = 40;
    data.forEach(row => {
      Object.entries(row).forEach(([key, value]) => {
        doc.text(`${key}: ${value}`, 15, yPosition);
        yPosition += 10;
      });
      yPosition += 5;
    });

    doc.save('business_analytics.pdf');
    error.value = '';
  } catch (err) {
    error.value = 'Failed to export PDF';
    console.error('PDF export error:', err);
  }
};

const exportToExcel = async () => {
  try {
    const data = prepareExportData();
    const ws = utils.json_to_sheet(data);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Business Analytics');
    writeFile(wb, 'business_analytics.xlsx');
    error.value = '';
  } catch (err) {
    error.value = 'Failed to export Excel file';
    console.error('Excel export error:', err);
  }
};

const exportToCSV = async () => {
  try {
    const data = prepareExportData();
    const csv = unparse(data);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'business_analytics.csv';
    link.click();
    URL.revokeObjectURL(link.href);
    error.value = '';
  } catch (err) {
    error.value = 'Failed to export CSV file';
    console.error('CSV export error:', err);
  }
};

const exportData = async () => {
  try {
    if (exportFormat.value === 'pdf') {
      await exportToPDF();
    } else if (exportFormat.value === 'excel') {
      await exportToExcel();
    } else if (exportFormat.value === 'csv') {
      await exportToCSV();
    }
  } catch (err) {
    error.value = `Failed to export data: ${err.message}`;
    console.error('Export error:', err);
  }
};
</script>