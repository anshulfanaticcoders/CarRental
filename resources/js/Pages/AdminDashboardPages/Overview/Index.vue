<template>
  <AdminDashboardLayout>
    <div class="md:hidden">
      <VPImage alt="Dashboard" width="1280" height="1214" class="block" :image="{
        dark: '/examples/dashboard-dark.png',
        light: '/examples/dashboard-light.png',
      }" />
    </div>

    <div class="hidden flex-col md:flex">
      <div class="flex-1 space-y-4 p-8 pt-6">
        <div class="flex items-center justify-between space-y-2">
          <h2 class="text-[1.5rem] font-semibold tracking-tight">Dashboard</h2>
        </div>
        <Tabs default-value="overview" class="space-y-4">
          <TabsList>
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="bookings">Bookings</TabsTrigger>
            <TabsTrigger value="payments">Payments</TabsTrigger>
            <TabsTrigger value="revenue">Revenue</TabsTrigger>
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
                  <p class="text-xs text-muted-foreground"> {{ customerGrowthPercentage >= 0 ?
                    `+${customerGrowthPercentage}%` : `${customerGrowthPercentage}%` }} from last month</p>
                </CardContent>
              </Card>

              <!-- Total Users -->
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
                  <p class="text-xs text-muted-foreground"> {{ vendorGrowthPercentage >= 0 ?
                    `+${vendorGrowthPercentage}%` : `${vendorGrowthPercentage}%` }} from last month</p>
                </CardContent>
              </Card>
              <!-- Total Vehicles -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Total Vehicles</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                    <path d="M3 13h18l-2-6H5l-2 6Z" />
                    <circle cx="7.5" cy="17.5" r="2.5" />
                    <circle cx="16.5" cy="17.5" r="2.5" />
                    <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ totalVehicles }}</div>
                  <p class="text-xs text-muted-foreground"> {{ vehicleGrowthPercentage >= 0 ?
                    `+${vehicleGrowthPercentage}%` : `${vehicleGrowthPercentage}%` }} from last month</p>
                </CardContent>
              </Card>

              <!-- Active Bookings -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Active Bookings</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                    <path d="M16 2v4"></path>
                    <path d="M8 2v4"></path>
                    <path d="M3 10h18"></path>
                    <path d="M9 16l2 2 4-4"></path>
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ activeBookings }}</div>
                  <p class="text-xs text-muted-foreground"> {{ bookingGrowthPercentage >= 0 ?
                    `+${bookingGrowthPercentage}%` : `${bookingGrowthPercentage}%` }} from last month</p>
                </CardContent>
              </Card>

              <!-- Revenue -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Revenue</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">${{ totalRevenue }}</div>
                  <p class="text-xs text-muted-foreground"> {{ revenueGrowth >= 0 ? `+${revenueGrowth}` :
                    `${revenueGrowth}` }} since last hour</p>
                </CardContent>
              </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
              <Card class="col-span-3">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold ">Bookings Overview</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">

                  <BarChart :data="props.bookingOverview"
                    :categories="['completed', 'confirmed', 'pending', 'cancelled']" index="name"
                    :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']" :stacked="true" :rounded-corners="4" />
                </CardContent>
              </Card>
              <Card class="col-span-2">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold ">Revenue</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">
                  <LineChart :data="revenueDataAsNumbers" index="name" :categories="['total']" :colors="['#3B82F6']"
                    :show-x-axis="true" :show-y-axis="true" :show-grid-lines="true"
                    :y-formatter="(tick) => tick ? `$ ${new Intl.NumberFormat('en-US').format(tick)}` : ''"
                    class="h-96 w-full" />
                </CardContent>
              </Card>
              <Card class="col-span-2">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold ">Recent Sales</CardTitle>
                  <CardDescription>You made {{ currentMonthSales }} sales this month.</CardDescription>
                </CardHeader>
                <CardContent>
                  <table>
                    <thead>
                      <tr>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="sale in recentSales" :key="sale.booking_number">
                        <td>{{ sale.customer_name }}</td>
                        <td>${{ sale.total_amount }}</td>
                        <td>{{ sale.created_at }}</td>
                      </tr>
                    </tbody>
                  </table>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
          <TabsContent value="bookings" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <!-- Active Bookings -->
              <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium">Active Bookings</CardTitle>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                    <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                    <path d="M16 2v4"></path>
                    <path d="M8 2v4"></path>
                    <path d="M3 10h18"></path>
                    <path d="M9 16l2 2 4-4"></path>
                  </svg>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-bold">{{ activeBookings }}</div>
                  <p class="text-xs text-muted-foreground">
                    {{ bookingGrowthPercentage >= 0 ? `+${bookingGrowthPercentage}%` : `${bookingGrowthPercentage}%` }}
                    from last month
                  </p>
                </CardContent>
              </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
              <!-- Bookings Overview -->
              <Card class="col-span-3">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold">Bookings Overview</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">
                  <BarChart :data="props.bookingOverview"
                    :categories="['completed', 'confirmed', 'pending', 'cancelled']" index="name"
                    :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']" :stacked="true" :rounded-corners="4" />
                </CardContent>
              </Card>

              <!-- Recent Sales -->
              <Card class="col-span-4">
                <CardHeader>
                  <CardTitle class="text-[1.5rem] font-semibold">Recent Sales</CardTitle>
                  <CardDescription>You made {{ currentMonthSales }} sales this month.</CardDescription>
                </CardHeader>
                <CardContent>
                  <table>
                    <thead>
                      <tr>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="sale in recentSales" :key="sale.booking_number">
                        <td>{{ sale.customer_name }}</td>
                        <td>${{ sale.total_amount }}</td>
                        <td>{{ sale.created_at }}</td>
                      </tr>
                    </tbody>
                  </table>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
          <TabsContent value="revenue" class="space-y-4">
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <!-- Total Payments -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">Total Payments</CardTitle>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" class="h-4 w-4 text-muted-foreground">
          <path d="M12 1v22M19 5H5v14h14z" />
        </svg>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">${{ totalRevenue }}</div>
        <p class="text-xs text-muted-foreground">
          {{ paymentGrowthPercentage >= 0 ? `+${paymentGrowthPercentage}%` : `${paymentGrowthPercentage}%` }}
          from last month
        </p>
      </CardContent>
    </Card>

    <!-- Completed Payments -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">Completed Payments</CardTitle>
        <CheckCircleIcon class="h-4 w-4 text-green-500" />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">${{ totalCompletedPayments }}</div>
      </CardContent>
    </Card>

    <!-- Cancelled Payments -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">Cancelled Payments</CardTitle>
        <XCircleIcon class="h-4 w-4 text-red-500" />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">${{ totalCancelledPayments }}</div>
      </CardContent>
    </Card>
  </div>

  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
    <!-- Payment Overview -->
    <Card class="">
      <CardHeader>
        <CardTitle class="text-[1.5rem] font-semibold">Payment Overview</CardTitle>
      </CardHeader>
      <CardContent class="pl-2">
        <BarChart :data="paymentOverview" :categories="['completed', 'pending', 'failed']" index="name"
          :colors="['#10B981', '#FFC633', '#EA3C3C']" :stacked="true" :rounded-corners="4" />
      </CardContent>
    </Card>

    <!-- Recent Payments -->
    <Card class="">
      <CardHeader>
        <CardTitle class="text-[1.5rem] font-semibold ">Recent Sales</CardTitle>
        <CardDescription>You made {{ currentMonthSales }} sales this month.</CardDescription>
      </CardHeader>
      <CardContent>
        <table>
          <thead>
            <tr>
              <th>Customer Name</th>
              <th>Total Amount</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sale in recentSales" :key="sale.booking_number">
              <td>{{ sale.customer_name }}</td>
              <td>${{ sale.total_amount }}</td>
              <td>{{ sale.created_at }}</td>
            </tr>
          </tbody>
        </table>
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
import { computed } from "vue";


const props = defineProps([
  'totalCustomers',
  'customerGrowthPercentage',
  'totalVendors',
  'vendorGrowthPercentage',
  'totalVehicles',
  'vehicleGrowthPercentage',
  'activeBookings',
  'bookingGrowthPercentage',
  'totalRevenue',
  'revenueGrowth',
  'bookingOverview',
  'revenueData',
  'recentSales',
  'currentMonthSales',
  'paymentOverview',
  'totalCompletedPayments',
  'totalCancelledPayments', 
  'paymentGrowthPercentage' 
]);
const revenueDataAsNumbers = computed(() => { // Use a computed property for efficiency
  if (!props.revenueData) return []; // Handle cases where data is not yet loaded

  return props.revenueData.map(item => ({
    ...item, // Spread the other properties (name, bookings, etc.)
    total: Number(item.total), // Convert 'total' to a number
  }));
});
const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US').format(Math.round(number));
};

const getTotalRevenue = () => {
  return props.revenueData.reduce((sum, month) => sum + month.total, 0);
};

const getAverageRevenue = () => {
  return getTotalRevenue() / props.revenueData.length;
};
</script>


<style>
table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 8px;
  border: 1px solid #ddd;
  text-align: left;
  font-size: 13px;
}

th {
  background-color: #f4f4f4;
}
</style>