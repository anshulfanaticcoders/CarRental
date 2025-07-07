<template>
    <AdminDashboardLayout>
        <div class="flex-col md:flex">
            <div class="flex-1 space-y-4 p-8 pt-6">
                <div class="flex items-center justify-between space-y-2">
                    <h2 class="text-[1.5rem] font-semibold tracking-tight">Dashboard</h2>
                </div>
                <Tabs :model-value="activeTab" @update:modelValue="onTabChange" class="space-y-4">
                    <TabsList>
                        <TabsTrigger value="revenue">Overview</TabsTrigger>
                        <TabsTrigger value="vehicles">Vehicles</TabsTrigger>
                        <TabsTrigger value="users">Users</TabsTrigger>
                        <TabsTrigger value="vendors">Vendors</TabsTrigger>
                    </TabsList>
                    <TabsContent value="revenue" class="space-y-4">
                        <div class="flex items-center justify-between space-y-2">
                            <div class="flex items-center space-x-2">
                                <Select v-model="revenuePeriod" @update:modelValue="onRevenuePeriodChange">
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
                                            <span>{{ revenueDateRange.start }} - {{ revenueDateRange.end }}</span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0" align="start">
                                        <VDatePicker v-model="revenueDate" is-range />
                                    </PopoverContent>
                                </Popover>
                                <Button @click="applyRevenueDateRange">Apply</Button>
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
                                    <DropdownMenuItem @click="exportData('xml')">XML</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalCustomers }}</div>
                                    <p class="text-xs text-muted-foreground"> {{ customerGrowthPercentage >= 0 ?
                                        `+${customerGrowthPercentage}%` : `${customerGrowthPercentage}%` }} from last
                                        period</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Vendors</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                                    <p class="text-xs text-muted-foreground"> {{ vendorGrowthPercentage >= 0 ?
                                        `+${vendorGrowthPercentage}%` : `${vendorGrowthPercentage}%` }} from last period
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Bookings</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                        <path d="M9 16l2 2 4-4"></path>
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalBookings }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Active Bookings</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
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
                                        `+${bookingGrowthPercentage}%` : `${bookingGrowthPercentage}%` }} from last
                                        period</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Pending Bookings</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                        <path d="M9 16l2 2 4-4"></path>
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ pendingBookings }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Completed Bookings</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                        <path d="M9 16l2 2 4-4"></path>
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ completedBookings }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Cancelled Bookings</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                        <path d="M9 16l2 2 4-4"></path>
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ cancelledBookings }}</div>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                            <Card class="col-span-4">
                                <CardHeader>
                                    <CardTitle>Overview</CardTitle>
                                </CardHeader>
                                <CardContent class="pl-2">
                                    <BarChart :data="bookingOverview" :categories="['completed', 'confirmed', 'pending', 'cancelled']" index="name" :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']" :stacked="true" :rounded-corners="4" />
                                </CardContent>
                            </Card>
                            <Card class="col-span-3 max-h-[550px] overflow-auto">
                                <CardHeader>
                                    <CardTitle>Recent Sales</CardTitle>
                                    <CardDescription>
                                        You made {{ currentMonthSales }} sales in this period.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div v-for="sale in recentSales" :key="sale.booking_number" class="mb-4 grid grid-cols-[25px_1fr] items-start pb-4 last:mb-0 last:pb-0">
                                        <span class="flex h-2 w-2 translate-y-1 rounded-full bg-sky-500" />
                                        <div class="space-y-1">
                                            <p class="text-sm font-medium leading-none">
                                                {{ sale.customer_name }}
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ sale.vehicle }}
                                            </p>
                                        </div>
                                        <div class="ml-auto font-medium">${{ sale.total_amount }}</div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle>Bookings</CardTitle>
                                <CardDescription>
                                    A list of all bookings in the selected period.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead v-for="column in columns" :key="column.accessorKey">
                                                {{ column.header }}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="row in tableData.data" :key="row.booking_id">
                                            <TableCell v-for="column in columns" :key="column.accessorKey">
                                                {{ row[column.accessorKey] }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <Pagination :currentPage="tableData.current_page" :totalPages="tableData.last_page" @page-change="onRevenuePageChange" />
                            </CardContent>
                        </Card>
                    </TabsContent>
                    <TabsContent value="vehicles" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Vehicles</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                                        <path d="M3 13h18l-2-6H5l-2 6Z" />
                                        <circle cx="7.5" cy="17.5" r="2.5" />
                                        <circle cx="16.5" cy="17.5" r="2.5" />
                                        <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVehicles }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Vehicles Rented</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                                        <path d="M3 13h18l-2-6H5l-2 6Z" />
                                        <circle cx="7.5" cy="17.5" r="2.5" />
                                        <circle cx="16.5" cy="17.5" r="2.5" />
                                        <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ rentedVehicles }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Vehicles Under Maintenance</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                                        <path d="M3 13h18l-2-6H5l-2 6Z" />
                                        <circle cx="7.5" cy="17.5" r="2.5" />
                                        <circle cx="16.5" cy="17.5" r="2.5" />
                                        <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ maintenanceVehicles }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Available Vehicles</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-6 w-6 text-muted-foreground">
                                        <path d="M3 13h18l-2-6H5l-2 6Z" />
                                        <circle cx="7.5" cy="17.5" r="2.5" />
                                        <circle cx="16.5" cy="17.5" r="2.5" />
                                        <path d="M5 13V6h14v7M9 6V3M15 6V3" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ activeVehicles }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Vendors</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" strokeLinecap="round" strokeLinejoin="round"
                                        strokeWidth="2" class="h-4 w-4 text-muted-foreground">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                            <Card class="col-span-4">
                                <CardHeader>
                                    <CardTitle>Vehicle Status Overview</CardTitle>
                                </CardHeader>
                                <CardContent class="pl-2">
                                    <BarChart :data="vehicleStatusOverview" :categories="['available', 'rented', 'maintenance']" index="name" :colors="['#10B981', '#153B4F', '#FFC633']" :stacked="true" :rounded-corners="4" />
                                </CardContent>
                            </Card>
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicles</CardTitle>
                                <CardDescription>
                                    A list of all vehicles.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead v-for="column in vehicleColumns" :key="column.accessorKey">
                                                {{ column.header }}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="row in vehicleTableData.data" :key="row.id">
                                            <TableCell v-for="column in vehicleColumns" :key="column.accessorKey">
                                                {{ row[column.accessorKey] }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <div class="flex items-center gap-2" v-if="vehicleTableData.last_page > 1">
                                    <Button
                                        variant="outline"
                                        :disabled="vehicleTableData.current_page === 1"
                                        @click="onVehiclePageChange(vehicleTableData.current_page - 1)"
                                    >
                                        Previous
                                    </Button>

                                    <template v-for="pageNumber in visibleVehiclePageNumbers" :key="pageNumber">
                                        <Button
                                            v-if="typeof pageNumber === 'number'"
                                            variant="outline"
                                            :class="{ 'bg-primary text-primary-foreground': vehicleTableData.current_page === pageNumber }"
                                            @click="onVehiclePageChange(pageNumber)"
                                        >
                                            {{ pageNumber }}
                                        </Button>
                                        <span v-else class="text-sm px-2">
                                            {{ pageNumber }}
                                        </span>
                                    </template>

                                    <Button
                                        variant="outline"
                                        :disabled="vehicleTableData.current_page === vehicleTableData.last_page"
                                        @click="onVehiclePageChange(vehicleTableData.current_page + 1)"
                                    >
                                        Next
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                    <TabsContent value="users" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Customers</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalCustomers }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Active Customers</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ activeCustomers }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Inactive Customers</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ inactiveCustomers }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Suspended Customers</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ suspendedCustomers }}</div>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                            <Card class="col-span-4">
                                <CardHeader>
                                    <CardTitle>User Status Overview</CardTitle>
                                </CardHeader>
                                <CardContent class="pl-2">
                                    <BarChart :data="userStatusOverview" :categories="['active', 'inactive', 'suspended']" index="name" :colors="['#10B981', '#FBBF24', '#EF4444']" :stacked="true" :rounded-corners="4" />
                                </CardContent>
                            </Card>
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle>Users</CardTitle>
                                <CardDescription>
                                    A list of all customers.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead v-for="column in userColumns" :key="column.accessorKey">
                                                {{ column.header }}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="row in userTableData.data" :key="row.id">
                                            <TableCell v-for="column in userColumns" :key="column.accessorKey">
                                                {{ row[column.accessorKey] }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <div class="flex items-center gap-2" v-if="userTableData.last_page > 1">
                                    <Button
                                        variant="outline"
                                        :disabled="userTableData.current_page === 1"
                                        @click="onUserPageChange(userTableData.current_page - 1)"
                                    >
                                        Previous
                                    </Button>

                                    <template v-for="pageNumber in visibleUserPageNumbers" :key="pageNumber">
                                        <Button
                                            v-if="typeof pageNumber === 'number'"
                                            variant="outline"
                                            :class="{ 'bg-primary text-primary-foreground': userTableData.current_page === pageNumber }"
                                            @click="onUserPageChange(pageNumber)"
                                        >
                                            {{ pageNumber }}
                                        </Button>
                                        <span v-else class="text-sm px-2">
                                            {{ pageNumber }}
                                        </span>
                                    </template>

                                    <Button
                                        variant="outline"
                                        :disabled="userTableData.current_page === userTableData.last_page"
                                        @click="onUserPageChange(userTableData.current_page + 1)"
                                    >
                                        Next
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                    <TabsContent value="vendors" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Total Vendors</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Active Vendors</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ activeVendors }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Inactive Vendors</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ inactiveVendors }}</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">Suspended Vendors</CardTitle>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ suspendedVendors }}</div>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                            <Card class="col-span-4">
                                <CardHeader>
                                    <CardTitle>Vendor Status Overview</CardTitle>
                                </CardHeader>
                                <CardContent class="pl-2">
                                    <BarChart :data="vendorStatusOverview" :categories="['active', 'inactive', 'suspended']" index="name" :colors="['#10B981', '#FBBF24', '#EF4444']" :stacked="true" :rounded-corners="4" />
                                </CardContent>
                            </Card>
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle>Vendors</CardTitle>
                                <CardDescription>
                                    A list of all vendors.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead v-for="column in vendorColumns" :key="column.accessorKey">
                                                {{ column.header }}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="row in vendorTableData.data" :key="row.id">
                                            <TableCell v-for="column in vendorColumns" :key="column.accessorKey">
                                                {{ row[column.accessorKey] }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <div class="flex items-center gap-2" v-if="vendorTableData.last_page > 1">
                                    <Button
                                        variant="outline"
                                        :disabled="vendorTableData.current_page === 1"
                                        @click="onVendorPageChange(vendorTableData.current_page - 1)"
                                    >
                                        Previous
                                    </Button>
                                    <template v-for="pageNumber in visibleVendorPageNumbers" :key="pageNumber">
                                        <Button
                                            v-if="typeof pageNumber === 'number'"
                                            variant="outline"
                                            :class="{ 'bg-primary text-primary-foreground': vendorTableData.current_page === pageNumber }"
                                            @click="onVendorPageChange(pageNumber)"
                                        >
                                            {{ pageNumber }}
                                        </Button>
                                        <span v-else class="text-sm px-2">
                                            {{ pageNumber }}
                                        </span>
                                    </template>
                                    <Button
                                        variant="outline"
                                        :disabled="vendorTableData.current_page === vendorTableData.last_page"
                                        @click="onVendorPageChange(vendorTableData.current_page + 1)"
                                    >
                                        Next
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/Components/ui/tabs";
import { BarChart } from "@/Components/ui/chart-bar";
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { DatePicker as VDatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import jsPDF from 'jspdf';
import 'jspdf-autotable';
import { utils, writeFile } from 'xlsx';
import { unparse } from 'papaparse';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const props = defineProps([
    'totalCustomers',
    'activeCustomers',
    'inactiveCustomers',
    'suspendedCustomers',
    'userStatusOverview',
    'customerGrowthPercentage',
    'totalVendors',
    'activeVendors',
    'inactiveVendors',
    'suspendedVendors',
    'vendorStatusOverview',
    'vendorGrowthPercentage',
    'totalVehicles',
    'vehicleGrowthPercentage',
    'activeVehicles',
    'rentedVehicles',
    'maintenanceVehicles',
    'vehicleStatusOverview',
    'totalBookings',
    'activeBookings',
    'bookingGrowthPercentage',
    'pendingBookings',
    'completedBookings',
    'cancelledBookings',
    'totalRevenue',
    'revenueGrowth',
    'bookingOverview',
    'revenueData',
    'recentSales',
    'currentMonthSales',
    'paymentOverview',
    'totalCompletedPayments',
    'totalCancelledPayments',
    'paymentGrowthPercentage',
    'tableData',
    'vehicleTableData',
    'userTableData',
    'vendorTableData',
    'dateRange'
]);

const revenuePeriod = ref('year');
const activeTab = ref('revenue');

const revenueDate = ref({
    start: new Date(props.dateRange.start),
    end: new Date(props.dateRange.end),
});

const revenueDateRange = computed(() => ({
    start: revenueDate.value.start.toLocaleDateString(),
    end: revenueDate.value.end.toLocaleDateString(),
}));

const onTabChange = (value) => {
    activeTab.value = value;
};

const applyRevenueDateRange = () => {
    Inertia.get('/admin-dashboard', {
        start_date: revenueDate.value.start.toISOString().split('T')[0],
        end_date: revenueDate.value.end.toISOString().split('T')[0],
        tab: 'revenue',
    }, {
        preserveState: true,
        replace: true,
    });
};

const onRevenuePeriodChange = (value) => {
    revenuePeriod.value = value;
    Inertia.get('/admin-dashboard', {
        period: value,
        tab: 'revenue',
    }, {
        preserveState: true,
        replace: true,
    });
};

const onRevenuePageChange = (page) => {
    Inertia.get('/admin-dashboard', {
        page: page,
        start_date: revenueDate.value.start.toISOString().split('T')[0],
        end_date: revenueDate.value.end.toISOString().split('T')[0],
        tab: 'revenue',
    }, {
        preserveState: true,
        replace: true,
    });
};

const onVehiclePageChange = (page) => {
    Inertia.get('/admin-dashboard', {
        vehicle_page: page,
        tab: 'vehicles',
    }, {
        preserveState: true,
        replace: true,
    });
};

const onUserPageChange = (page) => {
    Inertia.get('/admin-dashboard', {
        user_page: page,
        tab: 'users',
    }, {
        preserveState: true,
        replace: true,
    });
};

const onVendorPageChange = (page) => {
    Inertia.get('/admin-dashboard', {
        vendor_page: page,
        tab: 'vendors',
    }, {
        preserveState: true,
        replace: true,
    });
};

const exportData = (format) => {
    const data = activeTab.value === 'revenue' ? props.tableData.data : props.vehicleTableData.data;
    const columns = activeTab.value === 'revenue' ? [
        { header: 'Booking ID', dataKey: 'booking_id' },
        { header: 'Customer', dataKey: 'customer_name' },
        { header: 'Vendor', dataKey: 'vendor_name' },
        { header: 'Vehicle', dataKey: 'vehicle' },
        { header: 'Start Date', dataKey: 'start_date' },
        { header: 'End Date', dataKey: 'end_date' },
        { header: 'Amount', dataKey: 'total_amount' },
        { header: 'Status', dataKey: 'status' },
    ] : [
        { header: 'ID', dataKey: 'id' },
        { header: 'Vendor', dataKey: 'vendor_name' },
        { header: 'Location', dataKey: 'location' },
        { header: 'Country', dataKey: 'country' },
        { header: 'Company', dataKey: 'company_name' },
        { header: 'Status', dataKey: 'status' },
    ];

    if (format === 'pdf') {
        const doc = new jsPDF();
        doc.autoTable({
            head: [columns.map(c => c.header)],
            body: data.map(row => columns.map(c => row[c.dataKey])),
        });
        doc.save('export.pdf');
    } else if (format === 'excel') {
        const worksheet = utils.json_to_sheet(data);
        const workbook = utils.book_new();
        utils.book_append_sheet(workbook, worksheet, 'Export');
        writeFile(workbook, 'export.xlsx');
    } else if (format === 'xml') {
        let xml = '<?xml version="1.0" encoding="UTF-8"?><data>';
        data.forEach(item => {
            xml += '<item>';
            for (const key in item) {
                xml += `<${key}>${item[key]}</${key}>`;
            }
            xml += '</item>';
        });
        xml += '</data>';
        const blob = new Blob([xml], { type: 'application/xml;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'export.xml';
        link.click();
        URL.revokeObjectURL(link.href);
    }
};

const columns = [
    { accessorKey: 'booking_id', header: 'Booking ID' },
    { accessorKey: 'customer_name', header: 'Customer' },
    { accessorKey: 'vendor_name', header: 'Vendor' },
    { accessorKey: 'vehicle', header: 'Vehicle' },
    { accessorKey: 'start_date', header: 'Start Date' },
    { accessorKey: 'end_date', header: 'End Date' },
    { accessorKey: 'total_amount', header: 'Amount' },
    { accessorKey: 'status', header: 'Status' },
];

const vehicleColumns = [
    { accessorKey: 'id', header: 'ID' },
    { accessorKey: 'vendor_name', header: 'Vendor' },
    { accessorKey: 'location', header: 'Location' },
    { accessorKey: 'country', header: 'Country' },
    { accessorKey: 'company_name', header: 'Company' },
    { accessorKey: 'status', header: 'Status' },
];

const userColumns = [
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'phone', header: 'Phone' },
    { accessorKey: 'location', header: 'Location' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'joined_at', header: 'Joined' },
];

const vendorColumns = [
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'company_name', header: 'Company' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'joined_at', header: 'Joined' },
];

const visibleVehiclePageNumbers = computed(() => {
    const max = 5;
    const current = props.vehicleTableData.current_page;
    const total = props.vehicleTableData.last_page;
    const half = Math.floor(max / 2);

    if (total <= max) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let startPage = Math.max(1, current - half);
    let endPage = Math.min(total, current + half - (max % 2 === 0 ? 1 : 0));

    if (current <= half) {
        endPage = max;
        startPage = 1;
    } else if (current + half > total) {
        startPage = total - max + 1;
        endPage = total;
    }

    const pages = [];

    if (startPage > 1) {
        pages.push(1);
        if (startPage > 2) {
            pages.push('...');
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }

    if (endPage < total) {
        if (endPage < total - 1) {
            pages.push('...');
        }
        pages.push(total);
    }

    return pages;
});

const visibleUserPageNumbers = computed(() => {
    const max = 5;
    const current = props.userTableData.current_page;
    const total = props.userTableData.last_page;
    const half = Math.floor(max / 2);

    if (total <= max) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let startPage = Math.max(1, current - half);
    let endPage = Math.min(total, current + half - (max % 2 === 0 ? 1 : 0));

    if (current <= half) {
        endPage = max;
        startPage = 1;
    } else if (current + half > total) {
        startPage = total - max + 1;
        endPage = total;
    }

    const pages = [];

    if (startPage > 1) {
        pages.push(1);
        if (startPage > 2) {
            pages.push('...');
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }

    if (endPage < total) {
        if (endPage < total - 1) {
            pages.push('...');
        }
        pages.push(total);
    }

    return pages;
});

const visibleVendorPageNumbers = computed(() => {
    const max = 5;
    const current = props.vendorTableData.current_page;
    const total = props.vendorTableData.last_page;
    const half = Math.floor(max / 2);

    if (total <= max) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let startPage = Math.max(1, current - half);
    let endPage = Math.min(total, current + half - (max % 2 === 0 ? 1 : 0));

    if (current <= half) {
        endPage = max;
        startPage = 1;
    } else if (current + half > total) {
        startPage = total - max + 1;
        endPage = total;
    }

    const pages = [];

    if (startPage > 1) {
        pages.push(1);
        if (startPage > 2) {
            pages.push('...');
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }

    if (endPage < total) {
        if (endPage < total - 1) {
            pages.push('...');
        }
        pages.push(total);
    }

    return pages;
});

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab) {
    activeTab.value = tab;
  }
});

watch(() => props.dateRange, (newDateRange) => {
  revenueDate.value = {
    start: new Date(newDateRange.start),
    end: new Date(newDateRange.end),
  };
});
</script>
