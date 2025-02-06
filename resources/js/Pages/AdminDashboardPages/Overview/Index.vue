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
              <TabsTrigger value="analytics" disabled>Bookings</TabsTrigger>
              <TabsTrigger value="reports" disabled>Payments</TabsTrigger>
              <TabsTrigger value="notifications" disabled>Revenue</TabsTrigger>
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
  
                    <BarChart :data="data" :categories="['total']" :index="'name'" :rounded-corners="4" />
  
                  </CardContent>
                </Card>
                <Card class="col-span-2">
                  <CardHeader>
                    <CardTitle class="text-[1.5rem] font-semibold ">Revenue</CardTitle>
                  </CardHeader>
                  <CardContent class="pl-2">
                    <LineChart 
                      :data="data" 
                      index="name" 
                      :categories="['total']"
                      :colors="['blue']"
                      :show-x-axis="true"
                      :show-y-axis="true"
                      :show-grid-lines="true"
                      :y-formatter="(tick) => tick ? `$ ${new Intl.NumberFormat('en-US').format(tick)}` : ''"
                      class="h-96 w-full"
                    />
  
  
  
                  </CardContent>
                </Card>
                <Card class="col-span-2">
                  <CardHeader>
                    <CardTitle class="text-[1.5rem] font-semibold ">Recent Sales</CardTitle>
                    <CardDescription>You made 265 sales this month.</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div class="space-y-8">
                      <div class="flex items-center">
                        <Avatar class="h-9 w-9">
                          <AvatarImage src="/avatars/01.png" alt="Avatar" />
                          <AvatarFallback>OM</AvatarFallback>
                        </Avatar>
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            Olivia Martin
                          </p>
                          <p class="text-sm text-muted-foreground">
                            olivia.martin@email.com
                          </p>
                        </div>
                        <div class="ml-auto font-medium">
                          +$1,999.00
                        </div>
                      </div>
                      <div class="flex items-center">
                        <Avatar class="flex h-9 w-9 items-center justify-center space-y-0 border">
                          <AvatarImage src="/avatars/02.png" alt="Avatar" />
                          <AvatarFallback>JL</AvatarFallback>
                        </Avatar>
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            Jackson Lee
                          </p>
                          <p class="text-sm text-muted-foreground">
                            jackson.lee@email.com
                          </p>
                        </div>
                        <div class="ml-auto font-medium">
                          +$39.00
                        </div>
                      </div>
                      <div class="flex items-center">
                        <Avatar class="h-9 w-9">
                          <AvatarImage src="/avatars/03.png" alt="Avatar" />
                          <AvatarFallback>IN</AvatarFallback>
                        </Avatar>
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            Isabella Nguyen
                          </p>
                          <p class="text-sm text-muted-foreground">
                            isabella.nguyen@email.com
                          </p>
                        </div>
                        <div class="ml-auto font-medium">
                          +$299.00
                        </div>
                      </div>
                      <div class="flex items-center">
                        <Avatar class="h-9 w-9">
                          <AvatarImage src="/avatars/04.png" alt="Avatar" />
                          <AvatarFallback>WK</AvatarFallback>
                        </Avatar>
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            William Kim
                          </p>
                          <p class="text-sm text-muted-foreground">
                            will@email.com
                          </p>
                        </div>
                        <div class="ml-auto font-medium">
                          +$99.00
                        </div>
                      </div>
                      <div class="flex items-center">
                        <Avatar class="h-9 w-9">
                          <AvatarImage src="/avatars/05.png" alt="Avatar" />
                          <AvatarFallback>SD</AvatarFallback>
                        </Avatar>
                        <div class="ml-4 space-y-1">
                          <p class="text-sm font-medium leading-none">
                            Sofia Davis
                          </p>
                          <p class="text-sm text-muted-foreground">
                            sofia.davis@email.com
                          </p>
                        </div>
                        <div class="ml-auto font-medium">
                          +$39.00
                        </div>
                      </div>
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
    'totalCustomers', 'customerGrowthPercentage',
    'totalVendors', 'vendorGrowthPercentage',
    'totalVehicles', 'vehicleGrowthPercentage',
    'activeBookings', 'bookingGrowthPercentage',
    'totalRevenue', 'revenueGrowth'
  ]);
  const data = [
    { name: 'Jan', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Feb', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Mar', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Apr', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'May', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Jun', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Jul', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Aug', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Sep', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Oct', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Nov', total: Math.floor(Math.random() * 5000) + 1000 },
    { name: 'Dec', total: Math.floor(Math.random() * 5000) + 1000 },
  ]
  </script>