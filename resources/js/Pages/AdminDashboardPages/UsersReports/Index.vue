<template>
  <AdminDashboardLayout>
    <div class="hidden flex-col md:flex">
      <div class="flex-1 space-y-4 p-8 pt-6">
        <div class="flex items-center justify-between space-y-2">
          <h2 class="text-[1.5rem] font-semibold tracking-tight">Dashboard</h2>
        </div>
        <Tabs default-value="overview" class="space-y-4">
          <TabsList>
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="analytics" disabled>Users</TabsTrigger>
            <TabsTrigger value="reports" disabled>Analytics</TabsTrigger>
            <TabsTrigger value="notifications" disabled>Reports</TabsTrigger>
            <div class="flex flex-col items-end">
      <Button @click="downloadReport" :disabled="isDownloading" variant="outline" class="ml-4">
        <template v-if="!isDownloading">
          <DownloadIcon class="mr-2 h-4 w-4" />
          Download XML Report
        </template>
        <template v-else>
          <ReloadIcon class="mr-2 h-4 w-4 animate-spin" />
          Downloading...
        </template>
      </Button>
      <div v-if="error" class="text-sm text-red-500 mt-2">
        {{ error }}
      </div>
    </div>
          </TabsList>
          <TabsContent value="overview" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <!-- Total Users -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ totalCustomers }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ totalCustomersGrowth >= 0 ? `↑ ${totalCustomersGrowth}%` : `↓
                    ${Math.abs(totalCustomersGrowth)}%` }} from last month
                  </p>
                </CardContent>
              </Card>

              <!-- Total Users -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Active Users</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ activeCustomers }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ activeCustomersGrowth >= 0 ? `↑ ${activeCustomersGrowth}%` : `↓
                    ${Math.abs(activeCustomersGrowth)}%` }} from last month
                  </p>

                </CardContent>
              </Card>
              <!-- Total Vehicles -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">New Users</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                    <path d="M3 13h18l-2-6H5l-2 6Z" />
                    <circle cx="7.5" cy="17.5" r="2.5" />
                    <circle cx="16.5" cy="17.5" r="2.5" />
                    <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ newCustomers }}</div>
                  <p class="text-xs text-muted-foreground"> {{ newCustomersGrowth >= 0 ? `↑ ${newCustomersGrowth}%` : `↓
                    ${Math.abs(newCustomersGrowth)}%` }} vs last week</p>
                </CardContent>
              </Card>

            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
              <Card class="col-span-3">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold ">Users Overview</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">

                  <BarChart :data="monthlyData" :categories="['total', 'active']" :index="'name'"
                    :rounded-corners="4" />

                </CardContent>
              </Card>
              <Card class="col-span-2">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold ">Revenue</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">
                  <LineChart :data="monthlyData" index="name" :categories="['total']" :colors="['blue']"
                    :show-x-axis="true" :show-y-axis="true" :show-grid-lines="true" class="h-96 w-full" />



                </CardContent>
              </Card>
              <Card class="col-span-2">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold ">Recent Activities</CardTitle>
                  <CardDescription>Recent user activity.</CardDescription>
                </CardHeader>
                <CardContent>
                  <div class="space-y-8">
                    <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center">
                      <AvatarImage :src="`/avatars/${activity.user.profile_photo_path || 'default.png'}`"
                        alt="Avatar" />
                      <div class="ml-4 space-y-1">
                        <p class="text-sm font-medium leading-none">
                          {{ activity.user.first_name }} {{ activity.user.last_name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                          {{ activity.user.email }}
                        </p>
                      </div>
                      <div class="ml-auto font-medium">
                        {{ activity.activity_description }} </div>
                      <! -- Displaying the specific activity description -->
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

const props = defineProps([
  'totalCustomers',
  'totalCustomersGrowth',
  'activeCustomers',
  'activeCustomersGrowth',
  'newCustomers',
  'newCustomersGrowth',
  'monthlyData',
  'recentActivities'
]);

const getInitials = (firstName, lastName) => {
  return `${firstName?.[0] || ''}${lastName?.[0] || ''}`;
};
import Button from "@/Components/ui/button/Button.vue";
import axios from 'axios';
import { ref } from "vue";

const isDownloading = ref(false);
const error = ref('');

const downloadReport = async () => {
  error.value = ''; // Clear previous errors
  
  try {
    isDownloading.value = true;
    
    const response = await axios.get('/admin/reports/users/download', {
      responseType: 'blob'
    });
    
    // Check if the response is an error message in JSON format
    if (response.data.type === 'application/json') {
      const reader = new FileReader();
      reader.onload = () => {
        const errorResponse = JSON.parse(reader.result);
        error.value = errorResponse.message || 'Error downloading report';
      };
      reader.readAsText(response.data);
      return;
    }
    
    // Create download link for successful response
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `user-report-${new Date().toISOString().split('T')[0]}.xml`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
  } catch (error) {
    console.error('Error downloading report:', error);
    error.value = error.response?.data?.message || 'Error downloading report. Please try again.';
  } finally {
    isDownloading.value = false;
  }
};
</script>