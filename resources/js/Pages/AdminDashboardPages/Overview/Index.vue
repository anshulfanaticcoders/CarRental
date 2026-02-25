<template>
    <AdminDashboardLayout>
        <div class="flex-col md:flex">
            <div class="flex-1 space-y-4 p-4 sm:p-6 lg:p-8 pt-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h2>
                </div>
                <Tabs :model-value="activeTab" @update:modelValue="onTabChange" class="space-y-4">
                    <TabsList class="bg-muted/60 p-1">
                        <TabsTrigger value="revenue" class="data-[state=active]:bg-white data-[state=active]:shadow-sm">Overview</TabsTrigger>
                        <TabsTrigger value="vehicles" class="data-[state=active]:bg-white data-[state=active]:shadow-sm">Vehicles</TabsTrigger>
                        <TabsTrigger value="users" class="data-[state=active]:bg-white data-[state=active]:shadow-sm">Users</TabsTrigger>
                        <TabsTrigger value="vendors" class="data-[state=active]:bg-white data-[state=active]:shadow-sm">Vendors</TabsTrigger>
                    </TabsList>

                    <!-- ========== OVERVIEW TAB ========== -->
                    <TabsContent value="revenue" class="space-y-4">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-2">
                                <Select v-model="revenuePeriod" @update:modelValue="onRevenuePeriodChange">
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
                                            <span>{{ revenueDateRange.start }} - {{ revenueDateRange.end }}</span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0" align="start">
                                        <VDatePicker v-model="revenueDate" is-range />
                                    </PopoverContent>
                                </Popover>
                                <Button @click="applyRevenueDateRange" size="sm">Apply</Button>
                            </div>
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
                                    <DropdownMenuItem @click="exportData('xml')">XML</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Total Users -->
                            <Card class="border-l-4 border-l-blue-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Users</CardTitle>
                                    <Users class="h-4 w-4 text-blue-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalCustomers }}</div>
                                    <p class="text-xs mt-1" :class="customerGrowthPercentage >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                        {{ customerGrowthPercentage >= 0 ? '+' : '' }}{{ customerGrowthPercentage }}% from last period
                                    </p>
                                </CardContent>
                            </Card>
                            <!-- Total Vendors -->
                            <Card class="border-l-4 border-l-violet-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Vendors</CardTitle>
                                    <Store class="h-4 w-4 text-violet-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                                    <p class="text-xs mt-1" :class="vendorGrowthPercentage >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                        {{ vendorGrowthPercentage >= 0 ? '+' : '' }}{{ vendorGrowthPercentage }}% from last period
                                    </p>
                                </CardContent>
                            </Card>
                            <!-- Total Bookings -->
                            <Card class="border-l-4 border-l-amber-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Bookings</CardTitle>
                                    <CalendarCheck class="h-4 w-4 text-amber-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalBookings }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center text-[11px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">{{ activeBookings }} active</span>
                                        <span class="inline-flex items-center text-[11px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">{{ pendingBookings }} pending</span>
                                    </div>
                                </CardContent>
                            </Card>
                            <!-- Total Revenue -->
                            <Card class="border-l-4 border-l-emerald-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                                    <Banknote class="h-4 w-4 text-emerald-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ adminCurrency || 'EUR' }} {{ formatNumber(totalRevenue) }}</div>
                                    <p class="text-xs mt-1" :class="revenueGrowth >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                        {{ revenueGrowth >= 0 ? '+' : '' }}{{ revenueGrowth }}% from last period
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                            <Card class="lg:col-span-4">
                                <CardHeader>
                                    <CardTitle class="text-base">Booking Overview</CardTitle>
                                    <CardDescription>Monthly booking status breakdown</CardDescription>
                                </CardHeader>
                                <CardContent class="pl-2">
                                    <BarChart class="h-72" :data="bookingOverview" :categories="['completed', 'confirmed', 'pending', 'cancelled']" index="name" :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']" :stacked="true" :rounded-corners="4" />
                                </CardContent>
                            </Card>
                            <Card class="lg:col-span-3 max-h-[440px] overflow-auto">
                                <CardHeader>
                                    <CardTitle class="text-base">Recent Sales</CardTitle>
                                    <CardDescription>{{ currentMonthSales }} bookings this period</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div v-for="sale in recentSales" :key="sale.booking_number" class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-bold flex-shrink-0">
                                            {{ getInitials(sale.customer_name) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium leading-none truncate">{{ sale.customer_name }}</p>
                                            <p class="text-xs text-muted-foreground truncate mt-0.5">{{ sale.vehicle }}</p>
                                        </div>
                                        <div class="text-sm font-semibold text-right whitespace-nowrap">
                                            {{ sale.currency || adminCurrency || 'EUR' }} {{ parseFloat(sale.total_amount || 0).toFixed(2) }}
                                        </div>
                                    </div>
                                    <div v-if="!recentSales || recentSales.length === 0" class="text-center py-8 text-muted-foreground text-sm">
                                        No sales in this period
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <!-- Bookings Table -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Bookings</CardTitle>
                                <CardDescription>All bookings in the selected period</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-muted/50">
                                                <TableHead class="font-semibold">Booking ID</TableHead>
                                                <TableHead class="font-semibold">Customer</TableHead>
                                                <TableHead class="font-semibold">Vendor</TableHead>
                                                <TableHead class="font-semibold">Vehicle</TableHead>
                                                <TableHead class="font-semibold">Dates</TableHead>
                                                <TableHead class="font-semibold text-right">Amount</TableHead>
                                                <TableHead class="font-semibold text-center">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="row in formattedTableData" :key="row.booking_id" class="hover:bg-muted/30">
                                                <TableCell class="font-medium text-primary">#{{ row.booking_id }}</TableCell>
                                                <TableCell>{{ row.customer_name }}</TableCell>
                                                <TableCell>{{ row.vendor_name }}</TableCell>
                                                <TableCell class="max-w-[160px] truncate">{{ row.vehicle }}</TableCell>
                                                <TableCell class="text-sm whitespace-nowrap">
                                                    <div>{{ row.start_date }}</div>
                                                    <div class="text-muted-foreground text-xs">to {{ row.end_date }}</div>
                                                </TableCell>
                                                <TableCell class="text-right font-medium">{{ row.total_amount }}</TableCell>
                                                <TableCell class="text-center">
                                                    <span :class="statusBadgeClass(row.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                                        {{ row.status }}
                                                    </span>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="!formattedTableData.length">
                                                <TableCell colspan="7" class="text-center py-8 text-muted-foreground">No bookings found</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                <Pagination v-if="tableData.last_page > 1" :currentPage="tableData.current_page" :totalPages="tableData.last_page" @page-change="onRevenuePageChange" class="mt-4" />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- ========== VEHICLES TAB ========== -->
                    <TabsContent value="vehicles" class="space-y-4">
                        <div class="grid gap-4 grid-cols-2 lg:grid-cols-5">
                            <Card class="border-l-4 border-l-blue-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Vehicles</CardTitle>
                                    <Car class="h-4 w-4 text-blue-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVehicles }}</div>
                                    <p class="text-xs mt-1" :class="vehicleGrowthPercentage >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                        {{ vehicleGrowthPercentage >= 0 ? '+' : '' }}{{ vehicleGrowthPercentage }}% growth
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-emerald-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Available</CardTitle>
                                    <CircleCheck class="h-4 w-4 text-emerald-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-emerald-600">{{ activeVehicles }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">Ready to rent</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-orange-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Rented</CardTitle>
                                    <CarFront class="h-4 w-4 text-orange-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-orange-600">{{ rentedVehicles }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">Currently on road</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-red-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Maintenance</CardTitle>
                                    <Wrench class="h-4 w-4 text-red-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-red-600">{{ maintenanceVehicles }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">Under service</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-violet-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Vendors</CardTitle>
                                    <Store class="h-4 w-4 text-violet-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">Total providers</p>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Vehicle Status Distribution</CardTitle>
                                    <CardDescription>Current fleet status breakdown</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <BarChart class="h-64" :data="vehicleStatusOverview" :categories="['available', 'rented', 'maintenance']" index="name" :colors="['#10B981', '#F59E0B', '#EF4444']" :rounded-corners="6" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Fleet Composition</CardTitle>
                                    <CardDescription>Percentage of fleet by status</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex flex-col items-center justify-center h-64 gap-4">
                                        <div class="grid grid-cols-3 gap-6 w-full">
                                            <div class="text-center p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                                                <div class="text-3xl font-bold text-emerald-600">{{ vehiclePct('available') }}%</div>
                                                <div class="text-xs text-emerald-700 font-medium mt-1">Available</div>
                                                <div class="text-lg font-semibold text-emerald-800 mt-0.5">{{ activeVehicles }}</div>
                                            </div>
                                            <div class="text-center p-4 rounded-xl bg-amber-50 border border-amber-200">
                                                <div class="text-3xl font-bold text-amber-600">{{ vehiclePct('rented') }}%</div>
                                                <div class="text-xs text-amber-700 font-medium mt-1">Rented</div>
                                                <div class="text-lg font-semibold text-amber-800 mt-0.5">{{ rentedVehicles }}</div>
                                            </div>
                                            <div class="text-center p-4 rounded-xl bg-red-50 border border-red-200">
                                                <div class="text-3xl font-bold text-red-600">{{ vehiclePct('maintenance') }}%</div>
                                                <div class="text-xs text-red-700 font-medium mt-1">Maintenance</div>
                                                <div class="text-lg font-semibold text-red-800 mt-0.5">{{ maintenanceVehicles }}</div>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex">
                                            <div class="bg-emerald-500 h-full transition-all" :style="{ width: vehiclePct('available') + '%' }"></div>
                                            <div class="bg-amber-500 h-full transition-all" :style="{ width: vehiclePct('rented') + '%' }"></div>
                                            <div class="bg-red-500 h-full transition-all" :style="{ width: vehiclePct('maintenance') + '%' }"></div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <!-- Vehicles Table -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">All Vehicles</CardTitle>
                                <CardDescription>Complete vehicle inventory</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-muted/50">
                                                <TableHead class="font-semibold">ID</TableHead>
                                                <TableHead class="font-semibold">Vendor</TableHead>
                                                <TableHead class="font-semibold">Location</TableHead>
                                                <TableHead class="font-semibold">Country</TableHead>
                                                <TableHead class="font-semibold">Company</TableHead>
                                                <TableHead class="font-semibold text-center">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="row in vehicleTableData.data" :key="row.id" class="hover:bg-muted/30">
                                                <TableCell class="font-medium text-primary">#{{ row.id }}</TableCell>
                                                <TableCell>{{ row.vendor_name }}</TableCell>
                                                <TableCell class="max-w-[180px] truncate">{{ row.location || '-' }}</TableCell>
                                                <TableCell>{{ row.country || '-' }}</TableCell>
                                                <TableCell>{{ row.company_name || '-' }}</TableCell>
                                                <TableCell class="text-center">
                                                    <span :class="vehicleStatusClass(row.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                                        {{ row.status }}
                                                    </span>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="!vehicleTableData.data?.length">
                                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">No vehicles found</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                <div v-if="vehicleTableData.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                                    <p class="text-sm text-muted-foreground">Page {{ vehicleTableData.current_page }} of {{ vehicleTableData.last_page }}</p>
                                    <div class="flex items-center gap-1">
                                        <Button variant="outline" size="sm" :disabled="vehicleTableData.current_page === 1" @click="onVehiclePageChange(vehicleTableData.current_page - 1)">
                                            <ChevronLeft class="w-4 h-4" />
                                        </Button>
                                        <template v-for="pageNumber in visibleVehiclePageNumbers" :key="pageNumber">
                                            <Button v-if="typeof pageNumber === 'number'" :variant="vehicleTableData.current_page === pageNumber ? 'default' : 'outline'" size="sm" @click="onVehiclePageChange(pageNumber)" class="min-w-[32px]">
                                                {{ pageNumber }}
                                            </Button>
                                            <span v-else class="text-sm px-1 text-muted-foreground">...</span>
                                        </template>
                                        <Button variant="outline" size="sm" :disabled="vehicleTableData.current_page === vehicleTableData.last_page" @click="onVehiclePageChange(vehicleTableData.current_page + 1)">
                                            <ChevronRight class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- ========== USERS TAB ========== -->
                    <TabsContent value="users" class="space-y-4">
                        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                            <Card class="border-l-4 border-l-blue-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Customers</CardTitle>
                                    <Users class="h-4 w-4 text-blue-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalCustomers }}</div>
                                    <p class="text-xs mt-1" :class="customerGrowthPercentage >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                        {{ customerGrowthPercentage >= 0 ? '+' : '' }}{{ customerGrowthPercentage }}% growth
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-emerald-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Active</CardTitle>
                                    <UserCheck class="h-4 w-4 text-emerald-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-emerald-600">{{ activeCustomers }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ totalCustomers > 0 ? Math.round((activeCustomers / totalCustomers) * 100) : 0 }}% of total</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-amber-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Inactive</CardTitle>
                                    <UserX class="h-4 w-4 text-amber-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-amber-600">{{ inactiveCustomers }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ totalCustomers > 0 ? Math.round((inactiveCustomers / totalCustomers) * 100) : 0 }}% of total</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-red-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Suspended</CardTitle>
                                    <ShieldAlert class="h-4 w-4 text-red-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-red-600">{{ suspendedCustomers }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ totalCustomers > 0 ? Math.round((suspendedCustomers / totalCustomers) * 100) : 0 }}% of total</p>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">User Status Distribution</CardTitle>
                                    <CardDescription>Breakdown by account status</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <BarChart class="h-64" :data="userStatusOverview" :categories="['active', 'inactive', 'suspended']" index="name" :colors="['#10B981', '#FBBF24', '#EF4444']" :rounded-corners="6" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">User Health</CardTitle>
                                    <CardDescription>Account status composition</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex flex-col items-center justify-center h-64 gap-4">
                                        <div class="grid grid-cols-3 gap-6 w-full">
                                            <div class="text-center p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                                                <div class="text-3xl font-bold text-emerald-600">{{ userPct('active') }}%</div>
                                                <div class="text-xs text-emerald-700 font-medium mt-1">Active</div>
                                                <div class="text-lg font-semibold text-emerald-800 mt-0.5">{{ activeCustomers }}</div>
                                            </div>
                                            <div class="text-center p-4 rounded-xl bg-amber-50 border border-amber-200">
                                                <div class="text-3xl font-bold text-amber-600">{{ userPct('inactive') }}%</div>
                                                <div class="text-xs text-amber-700 font-medium mt-1">Inactive</div>
                                                <div class="text-lg font-semibold text-amber-800 mt-0.5">{{ inactiveCustomers }}</div>
                                            </div>
                                            <div class="text-center p-4 rounded-xl bg-red-50 border border-red-200">
                                                <div class="text-3xl font-bold text-red-600">{{ userPct('suspended') }}%</div>
                                                <div class="text-xs text-red-700 font-medium mt-1">Suspended</div>
                                                <div class="text-lg font-semibold text-red-800 mt-0.5">{{ suspendedCustomers }}</div>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex">
                                            <div class="bg-emerald-500 h-full transition-all" :style="{ width: userPct('active') + '%' }"></div>
                                            <div class="bg-amber-500 h-full transition-all" :style="{ width: userPct('inactive') + '%' }"></div>
                                            <div class="bg-red-500 h-full transition-all" :style="{ width: userPct('suspended') + '%' }"></div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <!-- Users Table -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">All Customers</CardTitle>
                                <CardDescription>Complete customer directory</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-muted/50">
                                                <TableHead class="font-semibold">Name</TableHead>
                                                <TableHead class="font-semibold">Email</TableHead>
                                                <TableHead class="font-semibold">Phone</TableHead>
                                                <TableHead class="font-semibold">Location</TableHead>
                                                <TableHead class="font-semibold text-center">Status</TableHead>
                                                <TableHead class="font-semibold">Joined</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="row in userTableData.data" :key="row.id" class="hover:bg-muted/30">
                                                <TableCell class="font-medium">{{ row.name }}</TableCell>
                                                <TableCell class="text-sm text-muted-foreground">{{ row.email }}</TableCell>
                                                <TableCell class="text-sm">{{ row.phone || '-' }}</TableCell>
                                                <TableCell class="text-sm">{{ row.location || '-' }}</TableCell>
                                                <TableCell class="text-center">
                                                    <span :class="userStatusClass(row.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                                        {{ row.status }}
                                                    </span>
                                                </TableCell>
                                                <TableCell class="text-sm text-muted-foreground whitespace-nowrap">{{ formatDate(row.joined_at) }}</TableCell>
                                            </TableRow>
                                            <TableRow v-if="!userTableData.data?.length">
                                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">No customers found</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                <div v-if="userTableData.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                                    <p class="text-sm text-muted-foreground">Page {{ userTableData.current_page }} of {{ userTableData.last_page }}</p>
                                    <div class="flex items-center gap-1">
                                        <Button variant="outline" size="sm" :disabled="userTableData.current_page === 1" @click="onUserPageChange(userTableData.current_page - 1)">
                                            <ChevronLeft class="w-4 h-4" />
                                        </Button>
                                        <template v-for="pageNumber in visibleUserPageNumbers" :key="pageNumber">
                                            <Button v-if="typeof pageNumber === 'number'" :variant="userTableData.current_page === pageNumber ? 'default' : 'outline'" size="sm" @click="onUserPageChange(pageNumber)" class="min-w-[32px]">
                                                {{ pageNumber }}
                                            </Button>
                                            <span v-else class="text-sm px-1 text-muted-foreground">...</span>
                                        </template>
                                        <Button variant="outline" size="sm" :disabled="userTableData.current_page === userTableData.last_page" @click="onUserPageChange(userTableData.current_page + 1)">
                                            <ChevronRight class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- ========== VENDORS TAB ========== -->
                    <TabsContent value="vendors" class="space-y-4">
                        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                            <Card class="border-l-4 border-l-violet-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Vendors</CardTitle>
                                    <Store class="h-4 w-4 text-violet-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ totalVendors }}</div>
                                    <p class="text-xs mt-1" :class="vendorGrowthPercentage >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                        {{ vendorGrowthPercentage >= 0 ? '+' : '' }}{{ vendorGrowthPercentage }}% growth
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-emerald-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Active</CardTitle>
                                    <UserCheck class="h-4 w-4 text-emerald-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-emerald-600">{{ activeVendors }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ totalVendors > 0 ? Math.round((activeVendors / totalVendors) * 100) : 0 }}% of total</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-amber-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Inactive</CardTitle>
                                    <UserX class="h-4 w-4 text-amber-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-amber-600">{{ inactiveVendors }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ totalVendors > 0 ? Math.round((inactiveVendors / totalVendors) * 100) : 0 }}% of total</p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-red-500">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium text-muted-foreground">Suspended</CardTitle>
                                    <ShieldAlert class="h-4 w-4 text-red-500" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-red-600">{{ suspendedVendors }}</div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ totalVendors > 0 ? Math.round((suspendedVendors / totalVendors) * 100) : 0 }}% of total</p>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Vendor Status Distribution</CardTitle>
                                    <CardDescription>Breakdown by account status</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <BarChart class="h-64" :data="vendorStatusOverview" :categories="['active', 'inactive', 'suspended']" index="name" :colors="['#10B981', '#FBBF24', '#EF4444']" :rounded-corners="6" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Vendor Health</CardTitle>
                                    <CardDescription>Account status composition</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex flex-col items-center justify-center h-64 gap-4">
                                        <div class="grid grid-cols-3 gap-6 w-full">
                                            <div class="text-center p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                                                <div class="text-3xl font-bold text-emerald-600">{{ vendorPct('active') }}%</div>
                                                <div class="text-xs text-emerald-700 font-medium mt-1">Active</div>
                                                <div class="text-lg font-semibold text-emerald-800 mt-0.5">{{ activeVendors }}</div>
                                            </div>
                                            <div class="text-center p-4 rounded-xl bg-amber-50 border border-amber-200">
                                                <div class="text-3xl font-bold text-amber-600">{{ vendorPct('inactive') }}%</div>
                                                <div class="text-xs text-amber-700 font-medium mt-1">Inactive</div>
                                                <div class="text-lg font-semibold text-amber-800 mt-0.5">{{ inactiveVendors }}</div>
                                            </div>
                                            <div class="text-center p-4 rounded-xl bg-red-50 border border-red-200">
                                                <div class="text-3xl font-bold text-red-600">{{ vendorPct('suspended') }}%</div>
                                                <div class="text-xs text-red-700 font-medium mt-1">Suspended</div>
                                                <div class="text-lg font-semibold text-red-800 mt-0.5">{{ suspendedVendors }}</div>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex">
                                            <div class="bg-emerald-500 h-full transition-all" :style="{ width: vendorPct('active') + '%' }"></div>
                                            <div class="bg-amber-500 h-full transition-all" :style="{ width: vendorPct('inactive') + '%' }"></div>
                                            <div class="bg-red-500 h-full transition-all" :style="{ width: vendorPct('suspended') + '%' }"></div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <!-- Vendors Table -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">All Vendors</CardTitle>
                                <CardDescription>Complete vendor directory</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-muted/50">
                                                <TableHead class="font-semibold">Name</TableHead>
                                                <TableHead class="font-semibold">Email</TableHead>
                                                <TableHead class="font-semibold">Company</TableHead>
                                                <TableHead class="font-semibold text-center">Status</TableHead>
                                                <TableHead class="font-semibold">Joined</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="row in vendorTableData.data" :key="row.id" class="hover:bg-muted/30">
                                                <TableCell class="font-medium">{{ row.name }}</TableCell>
                                                <TableCell class="text-sm text-muted-foreground">{{ row.email }}</TableCell>
                                                <TableCell class="text-sm">{{ row.company_name || '-' }}</TableCell>
                                                <TableCell class="text-center">
                                                    <span :class="userStatusClass(row.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                                        {{ row.status }}
                                                    </span>
                                                </TableCell>
                                                <TableCell class="text-sm text-muted-foreground whitespace-nowrap">{{ formatDate(row.joined_at) }}</TableCell>
                                            </TableRow>
                                            <TableRow v-if="!vendorTableData.data?.length">
                                                <TableCell colspan="5" class="text-center py-8 text-muted-foreground">No vendors found</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                <div v-if="vendorTableData.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                                    <p class="text-sm text-muted-foreground">Page {{ vendorTableData.current_page }} of {{ vendorTableData.last_page }}</p>
                                    <div class="flex items-center gap-1">
                                        <Button variant="outline" size="sm" :disabled="vendorTableData.current_page === 1" @click="onVendorPageChange(vendorTableData.current_page - 1)">
                                            <ChevronLeft class="w-4 h-4" />
                                        </Button>
                                        <template v-for="pageNumber in visibleVendorPageNumbers" :key="pageNumber">
                                            <Button v-if="typeof pageNumber === 'number'" :variant="vendorTableData.current_page === pageNumber ? 'default' : 'outline'" size="sm" @click="onVendorPageChange(pageNumber)" class="min-w-[32px]">
                                                {{ pageNumber }}
                                            </Button>
                                            <span v-else class="text-sm px-1 text-muted-foreground">...</span>
                                        </template>
                                        <Button variant="outline" size="sm" :disabled="vendorTableData.current_page === vendorTableData.last_page" @click="onVendorPageChange(vendorTableData.current_page + 1)">
                                            <ChevronRight class="w-4 h-4" />
                                        </Button>
                                    </div>
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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
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
import {
    Users, Store, CalendarCheck, Banknote, Car, CarFront, Wrench, CircleCheck,
    UserCheck, UserX, ShieldAlert, ChevronLeft, ChevronRight, Calendar as CalendarIcon, Download
} from 'lucide-vue-next';

const props = defineProps([
    'totalCustomers', 'activeCustomers', 'inactiveCustomers', 'suspendedCustomers',
    'userStatusOverview', 'customerGrowthPercentage',
    'totalVendors', 'activeVendors', 'inactiveVendors', 'suspendedVendors',
    'vendorStatusOverview', 'vendorGrowthPercentage',
    'totalVehicles', 'vehicleGrowthPercentage', 'activeVehicles', 'rentedVehicles', 'maintenanceVehicles',
    'vehicleStatusOverview',
    'totalBookings', 'activeBookings', 'bookingGrowthPercentage', 'pendingBookings', 'completedBookings', 'cancelledBookings',
    'totalRevenue', 'revenueGrowth', 'bookingOverview', 'revenueData',
    'recentSales', 'currentMonthSales',
    'paymentOverview', 'totalCompletedPayments', 'totalCancelledPayments', 'paymentGrowthPercentage',
    'tableData', 'vehicleTableData', 'userTableData', 'vendorTableData',
    'dateRange', 'adminCurrency'
]);

const revenuePeriod = ref('year');
const activeTab = ref('revenue');
const revenueDate = ref({
    start: new Date(props.dateRange.start),
    end: new Date(props.dateRange.end),
});

const revenueDateRange = computed(() => ({
    start: revenueDate.value.start.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
    end: revenueDate.value.end.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
}));

// --- Helpers ---
const formatNumber = (number) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number || 0);

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').filter(Boolean).map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const statusBadgeClass = (status) => {
    const map = {
        confirmed: 'bg-blue-50 text-blue-700 border border-blue-200',
        completed: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        pending: 'bg-amber-50 text-amber-700 border border-amber-200',
        cancelled: 'bg-red-50 text-red-700 border border-red-200',
    };
    return map[status] || 'bg-gray-50 text-gray-700 border border-gray-200';
};

const vehicleStatusClass = (status) => {
    const map = {
        available: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        rented: 'bg-amber-50 text-amber-700 border border-amber-200',
        maintenance: 'bg-red-50 text-red-700 border border-red-200',
    };
    return map[status] || 'bg-gray-50 text-gray-700 border border-gray-200';
};

const userStatusClass = (status) => {
    const map = {
        active: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        inactive: 'bg-amber-50 text-amber-700 border border-amber-200',
        suspended: 'bg-red-50 text-red-700 border border-red-200',
    };
    return map[status] || 'bg-gray-50 text-gray-700 border border-gray-200';
};

// --- Percentage helpers ---
const vehiclePct = (type) => {
    const total = (props.activeVehicles || 0) + (props.rentedVehicles || 0) + (props.maintenanceVehicles || 0);
    if (!total) return 0;
    const val = type === 'available' ? props.activeVehicles : type === 'rented' ? props.rentedVehicles : props.maintenanceVehicles;
    return Math.round((val / total) * 100);
};

const userPct = (type) => {
    const total = props.totalCustomers || 0;
    if (!total) return 0;
    const val = type === 'active' ? props.activeCustomers : type === 'inactive' ? props.inactiveCustomers : props.suspendedCustomers;
    return Math.round((val / total) * 100);
};

const vendorPct = (type) => {
    const total = props.totalVendors || 0;
    if (!total) return 0;
    const val = type === 'active' ? props.activeVendors : type === 'inactive' ? props.inactiveVendors : props.suspendedVendors;
    return Math.round((val / total) * 100);
};

// --- Tab & navigation ---
const onTabChange = (value) => { activeTab.value = value; };

const applyRevenueDateRange = () => {
    Inertia.get('/admin-dashboard', {
        start_date: revenueDate.value.start.toISOString().split('T')[0],
        end_date: revenueDate.value.end.toISOString().split('T')[0],
        tab: 'revenue',
    }, { preserveState: true, replace: true });
};

const onRevenuePeriodChange = (value) => {
    revenuePeriod.value = value;
    Inertia.get('/admin-dashboard', { period: value, tab: 'revenue' }, { preserveState: true, replace: true });
};

const onRevenuePageChange = (page) => {
    Inertia.get('/admin-dashboard', {
        page, start_date: revenueDate.value.start.toISOString().split('T')[0],
        end_date: revenueDate.value.end.toISOString().split('T')[0], tab: 'revenue',
    }, { preserveState: true, replace: true });
};

const onVehiclePageChange = (page) => {
    Inertia.get('/admin-dashboard', { vehicle_page: page, tab: 'vehicles' }, { preserveState: true, replace: true });
};

const onUserPageChange = (page) => {
    Inertia.get('/admin-dashboard', { user_page: page, tab: 'users' }, { preserveState: true, replace: true });
};

const onVendorPageChange = (page) => {
    Inertia.get('/admin-dashboard', { vendor_page: page, tab: 'vendors' }, { preserveState: true, replace: true });
};

// --- Export ---
const exportData = (format) => {
    const data = activeTab.value === 'revenue' ? props.tableData.data : props.vehicleTableData.data;
    const cols = activeTab.value === 'revenue' ? [
        { header: 'Booking ID', dataKey: 'booking_id' }, { header: 'Customer', dataKey: 'customer_name' },
        { header: 'Vendor', dataKey: 'vendor_name' }, { header: 'Vehicle', dataKey: 'vehicle' },
        { header: 'Start Date', dataKey: 'start_date' }, { header: 'End Date', dataKey: 'end_date' },
        { header: 'Amount', dataKey: 'total_amount' }, { header: 'Status', dataKey: 'status' },
    ] : [
        { header: 'ID', dataKey: 'id' }, { header: 'Vendor', dataKey: 'vendor_name' },
        { header: 'Location', dataKey: 'location' }, { header: 'Country', dataKey: 'country' },
        { header: 'Company', dataKey: 'company_name' }, { header: 'Status', dataKey: 'status' },
    ];

    if (format === 'pdf') {
        const doc = new jsPDF();
        doc.autoTable({ head: [cols.map(c => c.header)], body: data.map(row => cols.map(c => row[c.dataKey])) });
        doc.save('export.pdf');
    } else if (format === 'excel') {
        const ws = utils.json_to_sheet(data);
        const wb = utils.book_new();
        utils.book_append_sheet(wb, ws, 'Export');
        writeFile(wb, 'export.xlsx');
    } else if (format === 'xml') {
        let xml = '<?xml version="1.0" encoding="UTF-8"?><data>';
        data.forEach(item => { xml += '<item>'; for (const key in item) { xml += `<${key}>${item[key]}</${key}>`; } xml += '</item>'; });
        xml += '</data>';
        const blob = new Blob([xml], { type: 'application/xml;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'export.xml';
        link.click();
        URL.revokeObjectURL(link.href);
    }
};

// --- Table data ---
const formattedTableData = computed(() => {
    return (props.tableData?.data || []).map(row => ({
        ...row,
        start_date: formatDate(row.start_date),
        end_date: formatDate(row.end_date),
    }));
});

// --- Pagination helpers ---
const buildPageNumbers = (current, total) => {
    const max = 5;
    if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);
    const half = Math.floor(max / 2);
    let start = Math.max(1, current - half);
    let end = Math.min(total, current + half);
    if (current <= half) { end = max; start = 1; }
    else if (current + half > total) { start = total - max + 1; end = total; }
    const pages = [];
    if (start > 1) { pages.push(1); if (start > 2) pages.push('...'); }
    for (let i = start; i <= end; i++) pages.push(i);
    if (end < total) { if (end < total - 1) pages.push('...'); pages.push(total); }
    return pages;
};

const visibleVehiclePageNumbers = computed(() => buildPageNumbers(props.vehicleTableData.current_page, props.vehicleTableData.last_page));
const visibleUserPageNumbers = computed(() => buildPageNumbers(props.userTableData.current_page, props.userTableData.last_page));
const visibleVendorPageNumbers = computed(() => buildPageNumbers(props.vendorTableData.current_page, props.vendorTableData.last_page));

// --- Lifecycle ---
onMounted(() => {
    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab) activeTab.value = tab;
});

watch(() => props.dateRange, (v) => {
    revenueDate.value = { start: new Date(v.start), end: new Date(v.end) };
});
</script>
