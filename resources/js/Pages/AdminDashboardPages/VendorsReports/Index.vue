<template>
    <AdminDashboardLayout>
      <div class="hidden flex-col md:flex">
        <div class="flex-1 space-y-4 p-8 pt-6">
          <div class="flex items-center justify-between space-y-2">
            <h2 class="text-[1.5rem] font-semibold tracking-tight">Vendor Dashboard</h2>
          </div>
          <Tabs default-value="overview" class="space-y-4">
            <TabsList>
              
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
                <!-- Total Vendors -->
                <Card>
                  <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Vendors</CardTitle>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                      <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                  </CardHeader>
                  <CardContent>
                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                    <p class="text-xs text-muted-foreground">
                      {{ totalVendorsGrowth >= 0 ? `↑ ${totalVendorsGrowth}%` : `↓
                      ${Math.abs(totalVendorsGrowth)}%` }} from last month
                    </p>
                  </CardContent>
                </Card>
  
                <!-- Active Vendors -->
                <Card>
                  <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Active Vendors</CardTitle>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                      <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                  </CardHeader>
                  <CardContent>
                    <div class="text-2xl font-bold">{{ activeVendors }}</div>
                    <p class="text-xs text-muted-foreground">
                      {{ activeVendorsGrowth >= 0 ? `↑ ${activeVendorsGrowth}%` : `↓
                      ${Math.abs(activeVendorsGrowth)}%` }} from last month
                    </p>
                  </CardContent>
                </Card>
  <!-- Total Vehicles -->
  <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Total Vehicles</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M3 13h18l-2-6H5l-2 6Z" />
                    <circle cx="7.5" cy="17.5" r="2.5" />
                    <circle cx="16.5" cy="17.5" r="2.5" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ vehicleStatusData.total }}</div>
                  <p class="text-xs text-muted-foreground">Total registered vehicles</p>
                </CardContent>
              </Card>

              <!-- Active Vehicles -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Active Vehicles</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ vehicleStatusData.active.count }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ vehicleStatusData.active.percentage }}% of total fleet
                  </p>
                </CardContent>
              </Card>

              <!-- Rented Vehicles -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Rented Vehicles</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ vehicleStatusData.rented.count }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ vehicleStatusData.rented.percentage }}% of total fleet
                  </p>
                </CardContent>
              </Card>

              <!-- Maintenance Vehicles -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">In Maintenance</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ vehicleStatusData.maintenance.count }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ vehicleStatusData.maintenance.percentage }}% of total fleet
                  </p>
                </CardContent>
              </Card>
                <!-- New Vendors -->
                <Card>
                  <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">New Vendors</CardTitle>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                      <path d="M3 13h18l-2-6H5l-2 6Z" />
                      <circle cx="7.5" cy="17.5" r="2.5" />
                      <circle cx="16.5" cy="17.5" r="2.5" />
                      <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                    </svg>
                  </CardHeader>
                  <CardContent>
                    <div class="text-2xl font-bold">{{ newVendors }}</div>
                    <p class="text-xs text-muted-foreground">
                      {{ newVendorsGrowth >= 0 ? `↑ ${newVendorsGrowth}%` : `↓
                      ${Math.abs(newVendorsGrowth)}%` }} vs last week
                    </p>
                  </CardContent>
                </Card>
              </div>
  
              <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <Card class="col-span-3">
                  <CardHeader>
                    <CardTitle class="text-[1.5rem] font-semibold">Vendors Overview</CardTitle>
                  </CardHeader>
                  <CardContent class="pl-2">
                    <BarChart 
                      :data="monthlyData" 
                      :categories="['total', 'active']" 
                      :index="'name'"
                      :rounded-corners="4" 
                      :colors="['#153B4F','#10B981']"
                    />
                  </CardContent>
                </Card>
  
                <Card class="col-span-2">
                  <CardHeader>
                    <CardTitle class="text-[1.5rem] font-semibold">Vendor Growth</CardTitle>
                  </CardHeader>
                  <CardContent class="pl-2">
                    <LineChart 
                      :data="monthlyData" 
                      index="name" 
                      :categories="['total']" 
                      :colors="['blue']"
                      :show-x-axis="true" 
                      :show-y-axis="true" 
                      :show-grid-lines="true" 
                      class="h-96 w-full" 
                    />
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
                        <AvatarImage 
                          :src="`/avatars/${activity.user.profile_photo_path || 'default.png'}`"
                          alt="Avatar" 
                        />
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            {{ activity.user.first_name }} {{ activity.user.last_name }}
                          </p>
                          <p class="text-sm text-muted-foreground">
                            {{ activity.user.email }}
                          </p>
                        </div>
                        <div class="ml-auto font-medium">
                          {{ activity.activity_description }}
                        </div>
                      </div>
                      <div v-if="recentActivities.length === 0">No recent activity.</div>
                    </div>
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
  import { ref } from "vue";
  import Card from "@/Components/ui/card/Card.vue";
  import CardContent from "@/Components/ui/card/CardContent.vue";
  import CardDescription from "@/Components/ui/card/CardDescription.vue";
  import CardHeader from "@/Components/ui/card/CardHeader.vue";
  import CardTitle from "@/Components/ui/card/CardTitle.vue";
  import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
  import LineChart from "@/Components/ui/chart-line/LineChart.vue";
  import Tabs from "@/Components/ui/tabs/Tabs.vue";
  import TabsContent from "@/Components/ui/tabs/TabsContent.vue";
  import TabsList from "@/Components/ui/tabs/TabsList.vue";
  import TabsTrigger from "@/Components/ui/tabs/TabsTrigger.vue";
  import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
  import jsPDF from 'jspdf';
  import 'jspdf-autotable';
  import { utils, writeFile } from 'xlsx';
  import { unparse } from 'papaparse';
  
  const exportFormat = ref('pdf');
  const error = ref('');
  
  const props = defineProps([
    'totalVendors',
    'totalVendorsGrowth',
    'activeVendors',
    'activeVendorsGrowth',
    'newVendors',
    'newVendorsGrowth',
    'monthlyData',
    'recentActivities',
    'vehicleStatusData'
  ]);
  
  const prepareExportData = () => {
    try {
      return props.monthlyData.map(item => ({
        Month: item.name,
        'Total Vendors': item.total,
        'Active Vendors': item.active,
        'New Vendors': item.new || 0
      }));
    } catch (err) {
      error.value = 'Error preparing data for export';
      throw err;
    }
  };
  
  const exportToPDF = async () => {
    try {
      const doc = new jsPDF();
      const data = prepareExportData();
      
      doc.setFontSize(16);
      doc.text('Vendor Analytics Report', 15, 15);
      
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
      
      doc.save('vendor_analytics.pdf');
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
      utils.book_append_sheet(wb, ws, 'Vendor Analytics');
      writeFile(wb, 'vendor_analytics.xlsx');
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
      link.download = 'vendor_analytics.csv';
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
  
  const getInitials = (firstName, lastName) => {
    return `${firstName?.[0] || ''}${lastName?.[0] || ''}`;
  };
  </script>