<template>
    <MyProfileLayout>
        <div class="container mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success"
                class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4 text-green-800 text-sm sm:text-base">
                <div class="flex items-center justify-between">
                    <span>{{ $page.props.flash.success }}</span>
                    <button @click="clearFlashManually" class="ml-4 text-green-600 hover:text-green-800">
                        <X class="w-4 h-4 sm:w-5 sm:h-5" />
                    </button>
                </div>
            </div>

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ _t('vendorprofilepages', 'my_vehicles_header') }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
                            Manage your vehicle fleet and inventory
                        </p>
                    </div>

                    <!-- Add Vehicle Button Only in Header -->
                    <Link :href="route('vehicles.create', { locale: usePage().props.locale })" class="w-full sm:w-auto">
                        <Button size="sm" class="flex items-center justify-center gap-2 w-full sm:w-auto">
                            <Plus class="w-3 h-3 sm:w-4 sm:h-4" />
                            <span class="hidden sm:inline">{{ _t('vendorprofilepages', 'add_new_vehicle_button')
                            }}</span>
                            <span class="sm:hidden">Add Vehicle</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Category Statistics Cards -->
            <div class="space-y-4 sm:space-y-6 md:grid md:grid-cols-2 lg:grid-cols-4 md:gap-6">
                <!-- Total Vehicles Card (always first) -->
                <div
                    class="relative rounded-xl shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] border unified-category-card total-vehicles-card">
                    <div class="category-card-header">
                        <div class="category-icon total-icon">
                            <Car class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <Badge class="bg-white text-slate-800 font-bold text-xs px-2 py-1">
                            {{ totalVehicles }}
                        </Badge>
                    </div>
                    <div class="category-card-content">
                        <p class="category-card-title">{{ totalVehicles }}</p>
                        <p class="category-card-subtitle">Total Vehicles</p>
                        <p class="category-card-description">{{ totalVehicles === 1 ? 'Vehicle' : 'Vehicles' }}</p>
                    </div>
                </div>

                <!-- Category Cards -->
                <div v-for="(category, index) in categoryStatsData" :key="category.name" :class="[
                    'relative rounded-xl shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] border unified-category-card',
                    getCategoryCardClass(index)
                ]">
                    <div class="category-card-header">
                        <div :class="[getCategoryIconClass(index), 'category-card-icon']">
                            <Car class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <Badge :variant="getCategoryBadgeVariant(index)" class="text-white text-xs px-2 py-1">
                            {{ category.count }}
                        </Badge>
                    </div>
                    <div class="category-card-content">
                        <p :class="[getCategoryCountClass(index), 'category-card-title']">{{ category.count }}</p>
                        <p :class="[getCategoryNameClass(index), 'category-card-subtitle']">{{ category.name }}</p>
                        <p :class="[getCategoryLabelClass(index), 'category-card-description']">{{ category.count === 1
                            ? 'Vehicle' : 'Vehicles' }}</p>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Bar (Visible when vehicles are selected) -->
            <div v-if="selectedVehicleIds.length > 0" class="bulk-actions-bar animate-in">
                <div
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bulk-actions-content">
                    <div class="flex items-center gap-2 selection-counter">
                        <CheckSquare class="w-5 h-5 flex-shrink-0" />
                        <span class="font-medium">
                            {{ selectedVehicleIds.length }} vehicle{{ selectedVehicleIds.length !== 1 ? 's' : '' }}
                            selected
                        </span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto bulk-actions-buttons">
                        <Button @click="toggleSelectAll" variant="outline" size="sm"
                            class="flex items-center justify-center gap-2 bg-white hover:bg-orange-50 border-orange-300 text-orange-700 w-full sm:w-auto bulk-action-btn bulk-action-btn-outline">
                            <CheckSquare class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ isAllSelected ? 'Deselect All' : 'Select All' }}</span>
                            <span class="sm:hidden">{{ isAllSelected ? 'Deselect' : 'Select All' }}</span>
                        </Button>
                        <Button @click="confirmBulkDeletion" variant="destructive" size="sm"
                            class="flex items-center justify-center gap-2 w-full sm:w-auto bulk-action-btn">
                            <Trash2 class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ _t('vendorprofilepages', 'delete_selected_button')
                            }}</span>
                            <span class="sm:hidden">Delete</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search -->
            <div class="relative w-full max-w-md sm:max-w-lg lg:max-w-xl">
                <Search
                    class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 sm:h-5 sm:w-5 text-muted-foreground" />
                <Input v-model="searchQuery" :placeholder="_t('vendorprofilepages', 'search_vehicles_placeholder')"
                    class="pl-10 sm:pl-12 pr-4 h-10 sm:h-12 text-sm sm:text-base w-full" />
            </div>
            <!-- Enhanced Vehicles Section - Mobile Cards / Desktop Table -->
            <div v-if="filteredVehicles.length" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto max-w-full table-container">
                    <Table class="vehicles-table">
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">
                                    <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                </TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Sr. No</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Image</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_brand_model_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_transmission_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_fuel_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_location_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_limited_km_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_cancellation_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'table_price_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages',
                                    'status_table_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Created At</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">{{
                                    _t('vendorprofilepages', 'actions_table_header') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id"
                                class="hover:bg-muted/25 transition-colors" :class="{
                                    'bg-blue-50': selectedVehicleIds.includes(vehicle.id),
                                    'selected-row': selectedVehicleIds.includes(vehicle.id)
                                }">
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <input type="checkbox" :value="vehicle.id" v-model="selectedVehicleIds"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="outline">#{{ vehicle.id }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="relative group car-image-container">
                                        <img :src="getPrimaryImage(vehicle)"
                                            :alt="_t('vendorprofilepages', 'alt_no_image')"
                                            class="car-image-desktop h-12 w-20 object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105" />
                                        <div
                                            class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                            <Eye
                                                class="w-4 h-4 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ vehicle.brand }} {{ vehicle.model }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="secondary" class="capitalize">{{ vehicle.transmission }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="secondary" class="capitalize">{{ vehicle.fuel }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 max-w-xs">
                                    <div class="flex items-center gap-1 truncate" :title="vehicle.full_vehicle_address">
                                        <MapPin class="w-3 h-3 text-muted-foreground flex-shrink-0" />
                                        <span class="text-sm">{{ vehicle.full_vehicle_address }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <template
                                        v-if="vehicle.benefits && (vehicle.benefits.limited_km_per_day_range || vehicle.benefits.limited_km_per_week_range || vehicle.benefits.limited_km_per_month_range)">
                                        <div class="text-xs space-y-1">
                                            <span v-if="vehicle.benefits.limited_km_per_day_range > 0" class="block">
                                                {{ vehicle.benefits.limited_km_per_day_range }} {{
                                                    _t('vendorprofilepages', 'unit_km_day') }}
                                            </span>
                                            <span v-if="vehicle.benefits.limited_km_per_week_range > 0" class="block">
                                                {{ vehicle.benefits.limited_km_per_week_range }} {{
                                                    _t('vendorprofilepages', 'unit_km_week') }}
                                            </span>
                                            <span v-if="vehicle.benefits.limited_km_per_month_range > 0" class="block">
                                                {{ vehicle.benefits.limited_km_per_month_range }} {{
                                                    _t('vendorprofilepages', 'unit_km_month') }}
                                            </span>
                                        </div>
                                    </template>
                                    <span v-else class="text-xs text-muted-foreground">{{ _t('vendorprofilepages',
                                        'unlimited_km_text') }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <template
                                        v-if="vehicle.benefits && (vehicle.benefits.cancellation_available_per_day || vehicle.benefits.cancellation_available_per_week || vehicle.benefits.cancellation_available_per_month)">
                                        <div class="text-xs space-y-1">
                                            <span v-if="vehicle.benefits.cancellation_available_per_day"
                                                class="block">{{ _t('vendorprofilepages', 'cancellation_day') }}</span>
                                            <span v-if="vehicle.benefits.cancellation_available_per_week"
                                                class="block">{{ _t('vendorprofilepages', 'cancellation_week') }}</span>
                                            <span v-if="vehicle.benefits.cancellation_available_per_month"
                                                class="block">{{ _t('vendorprofilepages', 'cancellation_month')
                                                }}</span>
                                        </div>
                                    </template>
                                    <span v-else class="text-xs text-muted-foreground">{{ _t('vendorprofilepages',
                                        'not_available_text') }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <span class="font-bold text-primary">{{ formatPricing(vehicle) }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeVariant(vehicle.status)" class="capitalize">
                                        {{ vehicle.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(vehicle.created_at)
                                }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 action-cell">
                                    <div class="flex justify-end gap-2 min-w-[120px] action-buttons-fixed">
                                        <Link
                                            :href="route('current-vendor-vehicles.edit', { locale: usePage().props.locale, 'current_vendor_vehicle': vehicle.id })">
                                            <Button size="sm" variant="outline"
                                                class="flex items-center gap-1 whitespace-nowrap">
                                                <Edit class="w-3 h-3" />
                                                {{ _t('vendorprofilepages', 'edit_button') }}
                                            </Button>
                                        </Link>
                                        <Button size="sm" variant="destructive" @click="confirmDeletion(vehicle)"
                                            class="flex items-center gap-1 whitespace-nowrap">
                                            <Trash2 class="w-3 h-3" />
                                            {{ _t('vendorprofilepages', 'delete_button_general') }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden p-4 sm:p-6 space-y-4">

                    <div v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id"
                        class="border rounded-lg p-4 bg-card shadow-sm transition-all duration-200 hover:shadow-md"
                        :class="{ 'bg-blue-50 border-blue-200': selectedVehicleIds.includes(vehicle.id) }">
                        <!-- Card Header with Checkbox and Basic Info -->
                        <div class="flex items-start gap-3 mb-4">
                            <input type="checkbox" :value="vehicle.id" v-model="selectedVehicleIds"
                                class="rounded border-gray-300 text-primary focus:ring-primary w-5 h-5 mt-1 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                    <Badge variant="outline" class="text-xs whitespace-nowrap flex-shrink-0">#{{
                                        vehicle.id }}</Badge>
                                    <span class="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">
                                        {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                                    </span>
                                    <Badge :variant="getStatusBadgeVariant(vehicle.status)"
                                        class="capitalize text-xs whitespace-nowrap flex-shrink-0">
                                        {{ vehicle.status }}
                                    </Badge>
                                </div>
                                <h3
                                    class="font-semibold text-base sm:text-lg text-gray-900 dark:text-gray-100 truncate">
                                    {{ vehicle.brand }} {{ vehicle.model }}
                                </h3>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="relative group car-image-container">
                                    <img :src="getPrimaryImage(vehicle)" :alt="_t('vendorprofilepages', 'alt_no_image')"
                                        class="car-image-mobile h-16 w-24 sm:h-20 sm:w-28 object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105" />
                                    <div
                                        class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                        <Eye
                                            class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Details Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="space-y-1">
                                <span class="text-xs font-medium text-muted-foreground block">Transmission</span>
                                <div class="flex items-center">
                                    <Badge variant="secondary" class="text-xs truncate max-w-full">{{
                                        vehicle.transmission }}</Badge>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-medium text-muted-foreground block">Fuel</span>
                                <div class="flex items-center">
                                    <Badge variant="secondary" class="text-xs truncate max-w-full">{{ vehicle.fuel }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="mb-4">
                            <span class="text-xs font-medium text-muted-foreground block mb-1">Location</span>
                            <div class="flex items-center gap-1">
                                <MapPin class="w-3 h-3 text-muted-foreground flex-shrink-0" />
                                <span class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{
                                    vehicle.full_vehicle_address
                                }}</span>
                            </div>
                        </div>

                        <!-- Benefits Section -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            <div class="space-y-1">
                                <span class="text-xs font-medium text-muted-foreground block">Kilometer Limits</span>
                                <div v-if="vehicle.benefits && (vehicle.benefits.limited_km_per_day_range || vehicle.benefits.limited_km_per_week_range || vehicle.benefits.limited_km_per_month_range)"
                                    class="text-xs space-y-1">
                                    <span v-if="vehicle.benefits.limited_km_per_day_range > 0"
                                        class="block text-green-600">
                                        {{ vehicle.benefits.limited_km_per_day_range }}/day
                                    </span>
                                    <span v-if="vehicle.benefits.limited_km_per_week_range > 0"
                                        class="block text-green-600">
                                        {{ vehicle.benefits.limited_km_per_week_range }}/week
                                    </span>
                                    <span v-if="vehicle.benefits.limited_km_per_month_range > 0"
                                        class="block text-green-600">
                                        {{ vehicle.benefits.limited_km_per_month_range }}/month
                                    </span>
                                </div>
                                <span v-else class="text-xs text-green-600">{{ _t('vendorprofilepages',
                                    'unlimited_km_text') }}</span>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-medium text-muted-foreground block">Cancellation</span>
                                <div v-if="vehicle.benefits && (vehicle.benefits.cancellation_available_per_day || vehicle.benefits.cancellation_available_per_week || vehicle.benefits.cancellation_available_per_month)"
                                    class="text-xs space-y-1">
                                    <span v-if="vehicle.benefits.cancellation_available_per_day"
                                        class="block text-blue-600">{{
                                            _t('vendorprofilepages', 'cancellation_day') }}</span>
                                    <span v-if="vehicle.benefits.cancellation_available_per_week"
                                        class="block text-blue-600">{{
                                            _t('vendorprofilepages', 'cancellation_week') }}</span>
                                    <span v-if="vehicle.benefits.cancellation_available_per_month"
                                        class="block text-blue-600">{{
                                            _t('vendorprofilepages', 'cancellation_month') }}</span>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">{{ _t('vendorprofilepages',
                                    'not_available_text')
                                }}</span>
                            </div>
                        </div>

                        <!-- Price and Actions -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t">
                            <div class="space-y-1">
                                <span class="text-xs font-medium text-muted-foreground">Price</span>
                                <p class="font-bold text-primary text-sm sm:text-base">{{ formatPricing(vehicle) }}</p>
                            </div>
                            <div class="flex flex-row gap-2">
                                <Link
                                    :href="route('current-vendor-vehicles.edit', { locale: usePage().props.locale, 'current_vendor_vehicle': vehicle.id })"
                                    class="flex-1">
                                    <Button size="sm" variant="outline"
                                        class="w-full flex items-center justify-center gap-1 text-xs">
                                        <Edit class="w-3 h-3" />
                                        {{ _t('vendorprofilepages', 'edit_button') }}
                                    </Button>
                                </Link>
                                <Button size="sm" variant="destructive" @click="confirmDeletion(vehicle)"
                                    class="flex-1 flex items-center justify-center gap-1 text-xs">
                                    <Trash2 class="w-3 h-3" />
                                    {{ _t('vendorprofilepages', 'delete_button_general') }}
                                </Button>
                            </div>
                        </div>

                        <!-- Created Date -->
                        <div class="mt-3 pt-3 border-t">
                            <span class="text-xs text-muted-foreground">Created: {{ formatDate(vehicle.created_at)
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-6 sm:p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Car class="w-12 h-12 sm:w-16 sm:h-16 text-muted-foreground" />
                    <div class="space-y-2 max-w-md">
                        <h3 class="text-lg sm:text-xl font-semibold text-foreground">{{ _t('vendorprofilepages',
                            'no_vehicles_found_text') }}</h3>
                        <p class="text-sm sm:text-base text-muted-foreground">Get started by adding your first vehicle
                            to the fleet.
                        </p>
                    </div>
                    <Link :href="route('vehicles.create', { locale: usePage().props.locale })"
                        class="w-full sm:w-auto max-w-xs">
                        <Button class="flex items-center justify-center gap-2 w-full">
                            <Plus class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'add_new_vehicle_button') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Delete Confirmation Alert Dialog -->
            <AlertDialog v-model:open="showDeleteModal">
                <AlertDialogContent class="w-[95vw] sm:max-w-md mx-auto max-h-[95vh] overflow-auto">
                    <AlertDialogHeader class="pb-4">
                        <AlertDialogTitle class="flex items-center gap-2 text-base sm:text-lg">
                            <AlertTriangle class="w-4 h-4 sm:w-5 sm:h-5 text-red-500 flex-shrink-0" />
                            <span class="truncate">
                                {{ vehicleToDelete ? _t('vendorprofilepages', 'delete_vehicle_modal_title_single') :
                                    _t('vendorprofilepages', 'delete_vehicle_modal_title_bulk') }}
                            </span>
                        </AlertDialogTitle>
                        <AlertDialogDescription class="text-sm sm:text-base leading-relaxed">
                            {{ vehicleToDelete ? _t('vendorprofilepages', 'delete_vehicle_modal_confirm_single') :
                                _t('vendorprofilepages', 'delete_vehicle_modal_confirm_bulk', {
                                    count:
                                        selectedVehicleIds.length
                                }) }}
                            {{ _t('vendorprofilepages', 'action_cannot_be_undone_text') }}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex flex-col sm:flex-row gap-2 sm:gap-3 pt-4">
                        <AlertDialogCancel @click="showDeleteModal = false; vehicleToDelete = null;"
                            class="flex items-center justify-center gap-2 w-full sm:w-auto">
                            <X class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'cancel_button') }}
                        </AlertDialogCancel>
                        <AlertDialogAction @click="vehicleToDelete ? deleteVehicle() : deleteSelectedVehicles()"
                            class="flex items-center justify-center gap-2 w-full sm:w-auto">
                            <Trash2 class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'delete_button_general') }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
import Pagination from '@/Pages/Vendor/Vehicles/Pagination.vue';
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from '@/Components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Input } from '@/Components/ui/input';
import Badge from '@/Components/ui/badge/Badge.vue';
import { Button } from '@/Components/ui/button';
import {
    Search,
    Plus,
    CheckSquare,
    Trash2,
    Edit,
    Eye,
    MapPin,
    Car,
    AlertTriangle,
    X,
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
    vehicles: {
        type: Array,
        required: true
    },
    pagination: {
        type: Object,
        required: true
    },
    categoryStats: {
        type: Array,
        default: () => []
    },
    totalVehicles: {
        type: Number,
        default: 0
    }
})
const searchQuery = ref('');

// Define filteredVehicles earlier as other computed/watchers depend on it
const filteredVehicles = computed(() => {
    return props.vehicles.filter(vehicle => {
        const query = searchQuery.value.toLowerCase();
        return (
            vehicle.brand.toLowerCase().includes(query) ||
            vehicle.model.toLowerCase().includes(query) ||
            vehicle.transmission.toLowerCase().includes(query) ||
            vehicle.fuel.toLowerCase().includes(query) ||
            vehicle.full_vehicle_address.toLowerCase().includes(query) ||
            vehicle.status.toLowerCase().includes(query)
        );
    });
});

watch(searchQuery, (newQuery) => {
    router.get(
        route('current-vendor-vehicles.index', { locale: usePage().props.locale }),
        { search: newQuery },
        { preserveState: true, preserveScroll: true }
    );
});

const handlePageChange = (page) => {
    router.get(route('current-vendor-vehicles.index', { locale: usePage().props.locale }), { ...props.filters, page }, { preserveState: true, preserveScroll: true });

};
const showDeleteModal = ref(false)
const vehicleToDelete = ref(null)
const selectedVehicleIds = ref([])

// Computed property to check if all filtered vehicles are selected
const isAllSelected = computed(() => {
    if (!filteredVehicles.value.length) return false;
    return filteredVehicles.value.every(vehicle => selectedVehicleIds.value.includes(vehicle.id));
});

// Use category stats from props if available, otherwise calculate from current vehicles (fallback)
const categoryStatsData = computed(() => {
    // If category stats are passed from backend, use them
    if (props.categoryStats && props.categoryStats.length > 0) {
        return props.categoryStats.slice(0, 7); // Show top 7 categories (plus total vehicles card = 8 total)
    }

    // Fallback: Calculate from current page vehicles (not ideal but better than nothing)
    const categories = {};
    props.vehicles.forEach(vehicle => {
        const categoryName = vehicle.category?.name || 'Uncategorized';
        if (!categories[categoryName]) {
            categories[categoryName] = 0;
        }
        categories[categoryName]++;
    });

    return Object.entries(categories)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 7); // Show top 7 categories (plus total vehicles card = 8 total)
});

// Helper functions for category card styling
const getCategoryCardClass = (index) => {
    const classes = [
        'bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200',
        'bg-gradient-to-br from-green-50 to-emerald-50 border-green-200',
        'bg-gradient-to-br from-purple-50 to-pink-50 border-purple-200',
        'bg-gradient-to-br from-orange-50 to-amber-50 border-orange-200',
        'bg-gradient-to-br from-red-50 to-rose-50 border-red-200',
        'bg-gradient-to-br from-cyan-50 to-teal-50 border-cyan-200',
        'bg-gradient-to-br from-indigo-50 to-purple-50 border-indigo-200',
        'bg-gradient-to-br from-gray-50 to-slate-50 border-gray-200'
    ];
    return classes[index % classes.length];
};

const getCategoryIconClass = (index) => {
    const classes = [
        'p-3 bg-blue-500 bg-opacity-20 rounded-lg text-blue-600',
        'p-3 bg-green-500 bg-opacity-20 rounded-lg text-green-600',
        'p-3 bg-purple-500 bg-opacity-20 rounded-lg text-purple-600',
        'p-3 bg-orange-500 bg-opacity-20 rounded-lg text-orange-600',
        'p-3 bg-red-500 bg-opacity-20 rounded-lg text-red-600',
        'p-3 bg-cyan-500 bg-opacity-20 rounded-lg text-cyan-600',
        'p-3 bg-indigo-500 bg-opacity-20 rounded-lg text-indigo-600',
        'p-3 bg-gray-500 bg-opacity-20 rounded-lg text-gray-600'
    ];
    return classes[index % classes.length];
};

const getCategoryBadgeVariant = (index) => {
    const variants = ['default', 'secondary', 'destructive', 'outline'];
    return variants[index % variants.length];
};

const getCategoryCountClass = (index) => {
    const classes = [
        'text-3xl font-bold text-blue-900',
        'text-3xl font-bold text-green-900',
        'text-3xl font-bold text-purple-900',
        'text-3xl font-bold text-orange-900',
        'text-3xl font-bold text-red-900',
        'text-3xl font-bold text-cyan-900',
        'text-3xl font-bold text-indigo-900',
        'text-3xl font-bold text-gray-900'
    ];
    return classes[index % classes.length];
};

const getCategoryNameClass = (index) => {
    const classes = [
        'text-sm font-medium text-blue-700 mt-1',
        'text-sm font-medium text-green-700 mt-1',
        'text-sm font-medium text-purple-700 mt-1',
        'text-sm font-medium text-orange-700 mt-1',
        'text-sm font-medium text-red-700 mt-1',
        'text-sm font-medium text-cyan-700 mt-1',
        'text-sm font-medium text-indigo-700 mt-1',
        'text-sm font-medium text-gray-700 mt-1'
    ];
    return classes[index % classes.length];
};

const getCategoryLabelClass = (index) => {
    const classes = [
        'text-xs text-blue-600 mt-2',
        'text-xs text-green-600 mt-2',
        'text-xs text-purple-600 mt-2',
        'text-xs text-orange-600 mt-2',
        'text-xs text-red-600 mt-2',
        'text-xs text-cyan-600 mt-2',
        'text-xs text-indigo-600 mt-2',
        'text-xs text-gray-600 mt-2'
    ];
    return classes[index % classes.length];
};

// Toggle select all vehicles
const toggleSelectAll = (event) => {
    // Handle both checkbox events and button clicks
    let isChecked;

    if (event?.target?.checked !== undefined) {
        // Checkbox event
        isChecked = event.target.checked;
    } else {
        // Button click - toggle based on current selection state
        isChecked = selectedVehicleIds.value.length < filteredVehicles.value.length;
    }

    if (isChecked && filteredVehicles.value.length > 0) {
        // Select all vehicles
        const allVehicleIds = filteredVehicles.value.map(vehicle => vehicle.id);
        selectedVehicleIds.value = [...allVehicleIds];
    } else {
        // Deselect all vehicles
        selectedVehicleIds.value = [];
    }
};

watch(filteredVehicles, () => {
    // If filtered vehicles change, we might need to prune selectedVehicleIds
    // or ensure the "select all" state is accurate.
    // For now, we'll clear selection if filters change to avoid complexity,
    // or you could implement more sophisticated logic.
    // selectedVehicleIds.value = []; // Simplest approach
    // A more nuanced approach would be to filter selectedVehicleIds based on new filteredVehicles
    selectedVehicleIds.value = selectedVehicleIds.value.filter(id =>
        filteredVehicles.value.some(vehicle => vehicle.id === id)
    );
});


const getPrimaryImage = (vehicle) => {
    const primaryImage = vehicle.images.find(img => img.image_type === 'primary')
    return primaryImage ? `${primaryImage.image_url}` : '/images/placeholder.jpg'
}

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'available':
            return 'default';
        case 'rented':
            return 'secondary';
        case 'maintenance':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Clear flash message manually
const clearFlashManually = () => {
    router.visit(window.location.pathname, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        data: { flash: null }
    });
};

// Clear flash message after 3 seconds
const clearFlash = () => {
    setTimeout(() => {
        router.visit(window.location.pathname, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            data: { flash: null }
        });
    }, 3000);
};

// Call clearFlash when flash message exists
if (usePage().props.flash?.success) {
    clearFlash();
}

const confirmDeletion = (vehicle) => {
    vehicleToDelete.value = vehicle
    showDeleteModal.value = true
}

const confirmBulkDeletion = () => {
    vehicleToDelete.value = null; // Ensure we are in bulk delete mode
    if (selectedVehicleIds.value.length > 0) {
        showDeleteModal.value = true;
    }
};

const deleteVehicle = () => {
    router.delete(route('current-vendor-vehicles.destroy', { locale: usePage().props.locale, 'current_vendor_vehicle': vehicleToDelete.value.id }), {
        onSuccess: () => {
            showDeleteModal.value = false
            vehicleToDelete.value = null
            // Optionally, remove from selectedVehicleIds if it was there
            const index = selectedVehicleIds.value.indexOf(vehicleToDelete.value.id);
            if (index > -1) {
                selectedVehicleIds.value.splice(index, 1);
            }
        },
        onError: () => {
            showDeleteModal.value = false;
        }
    })
}

const deleteSelectedVehicles = () => {
    if (selectedVehicleIds.value.length === 0) return;
    router.post(route('current-vendor-vehicles.bulk-destroy', { locale: usePage().props.locale }), { ids: selectedVehicleIds.value }, {
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedVehicleIds.value = [];
        },
        onError: (errors) => {
            console.error('Error deleting selected vehicles:', errors);
            showDeleteModal.value = false;
            // Handle error display if necessary
        }
    });
};

const formatPricing = (vehicle) => {
    if (!vehicle || !vehicle.vendor_profile || !vehicle.vendor_profile.currency) {
        return _t('vendorprofilepages', 'not_applicable_text'); // Fallback if data is missing
    }

    const currencySymbol = vehicle.vendor_profile.currency;
    const prices = [];

    if (vehicle.price_per_day) prices.push(`${currencySymbol}${vehicle.price_per_day}${_t('vendorprofilepages', 'price_per_day_suffix')}`);
    if (vehicle.price_per_week) prices.push(`${currencySymbol}${vehicle.price_per_week}${_t('vendorprofilepages', 'price_per_week_suffix')}`);
    if (vehicle.price_per_month) prices.push(`${currencySymbol}${vehicle.price_per_month}${_t('vendorprofilepages', 'price_per_month_suffix')}`);

    return prices.length ? prices.join(' | ') : _t('vendorprofilepages', 'not_applicable_text');
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

// filteredVehicles definition was moved up

</script>

<style scoped>
/* Mobile-specific adjustments */
@media (max-width: 640px) {
    .vehicle-card {
        touch-action: manipulation;
    }

    .vehicle-image {
        touch-action: manipulation;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    /* Ensure buttons are properly sized for touch */
    button {
        min-height: 44px;
        min-width: 44px;
    }

    /* Checkbox styling for mobile */
    input[type="checkbox"] {
        width: 20px;
        height: 20px;
        min-width: 20px;
        min-height: 20px;
    }
}

/* Tablet-specific adjustments */
@media (min-width: 641px) and (max-width: 1024px) {
    .category-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .vehicle-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
}

/* Desktop-specific adjustments */
@media (min-width: 1025px) {
    .category-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Ensure images scale properly on all devices */
.vehicle-image {
    max-width: 100%;
    height: auto;
    object-fit: cover;
    width: 100%;
}

/* Car image container fixes */
.car-image-container {
    position: relative;
    overflow: hidden;
    border-radius: 0.5rem;
    width: 100%;
    max-width: 100%;
}

.car-image-container img {
    transition: transform 0.2s ease;
    width: 100%;
    height: auto;
    object-fit: cover;
}

.car-image-container:hover img {
    transform: scale(1.05);
}

/* Mobile specific car image fixes */
@media (max-width: 640px) {
    .car-image-mobile {
        width: 100%;
        max-width: 150px;
        height: auto;
        object-fit: cover;
    }

    .car-image-small {
        width: 96px;
        height: 64px;
        object-fit: cover;
    }

    .car-image-medium {
        width: 112px;
        height: 80px;
        object-fit: cover;
    }
}

/* Tablet car image fixes */
@media (min-width: 641px) and (max-width: 1024px) {
    .car-image-tablet {
        width: 100%;
        max-width: 180px;
        height: auto;
        object-fit: cover;
    }
}

/* Desktop car image fixes */
@media (min-width: 1025px) {
    .car-image-desktop {
        width: 80px;
        height: 48px;
        object-fit: cover;
    }
}

/* Custom scrollbar for mobile tables */
.vehicle-table {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
}

.vehicle-table::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.vehicle-table::-webkit-scrollbar-track {
    background: transparent;
}

.vehicle-table::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.vehicle-table::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.5);
}

/* Table layout fixes for selected rows */
.vehicles-table {
    table-layout: fixed;
    width: 100%;
}

.vehicles-table th,
.vehicles-table td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Action column specific fixes */
.action-cell {
    min-width: 140px;
    max-width: 200px;
    width: auto;
}

.action-cell .btn-container {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    min-width: 120px;
}

/* Selected row fixes */
.vehicles-table .selected-row {
    position: relative;
    z-index: 1;
}

.vehicles-table .selected-row td {
    position: relative;
    z-index: 1;
}

/* Prevent table layout breaking on selection */
.vehicles-table tbody tr {
    display: table-row;
    vertical-align: middle;
}

/* Fix for table width issues */
.vehicles-table {
    min-width: 100%;
    width: 100%;
    max-width: 100%;
}

.vehicles-table .table-container {
    overflow-x: auto;
    width: 100%;
}

/* Ensure action buttons stay within table cells */
.action-buttons-fixed {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    min-width: 140px;
}

/* Responsive table fixes */
@media (max-width: 1024px) {
    .vehicles-table {
        min-width: 1200px;
        /* Force horizontal scroll on smaller screens */
    }
}

/* Prevent content overflow in table cells */
.vehicles-table td {
    vertical-align: middle;
    position: relative;
}

/* Fix for badge alignment in table cells */
.table-badge-container {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    max-width: 100%;
    overflow: hidden;
}

/* Ensure proper table cell behavior */
.vehicles-table {
    border-collapse: separate;
    border-spacing: 0;
}

.vehicles-table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Responsive text handling */
.text-responsive {
    font-size: clamp(0.875rem, 2.5vw, 1rem);
    line-height: 1.5;
}

/* Mobile card hover effects */
@media (hover: none) and (pointer: coarse) {
    .vehicle-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .vehicle-card:active {
        transform: scale(0.98);
    }
}

/* Dialog responsiveness */
@media (max-width: 640px) {
    .dialog-content {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
    }
}

/* Category cards responsive improvements */
.category-card {
    transition: all 0.2s ease;
    min-height: 120px;
    min-height: 140px;
}

.category-card:hover {
    transform: translateY(-2px);
}

/* Unified category card styles for all cards */
.unified-category-card {
    display: flex;
    flex-direction: column;
    min-height: 140px;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.unified-category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Card header section for consistency */
.category-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

/* Card icon container */
.category-card-icon {
    padding: 0.75rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Card content section */
.category-card-content {
    text-align: center;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Card title styling */
.category-card-title {
    font-size: 1.875rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

/* Card subtitle styling */
.category-card-subtitle {
    font-size: 1.125rem;
    font-weight: 600;
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
    line-height: 1.2;
}

/* Card description styling */
.category-card-description {
    font-size: 0.875rem;
    margin-top: 0.5rem;
    line-height: 1.3;
}

/* Total Vehicles Card Specific Styling */
.total-vehicles-card {
    background: linear-gradient(135deg, rgb(30, 41, 59) 0%, rgb(51, 65, 85) 100%);
    border-color: rgb(71, 85, 105);
}

.total-vehicles-card .category-card-title,
.total-vehicles-card .category-card-subtitle,
.total-vehicles-card .category-card-description {
    color: white;
}

.total-vehicles-card .category-icon {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.total-vehicles-card .category-card-header {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Responsive adjustments for cards */
@media (max-width: 640px) {
    .unified-category-card {
        min-height: 120px;
        padding: 0.75rem;
    }

    .category-card-header {
        margin-bottom: 0.5rem;
    }

    .category-card-icon {
        padding: 0.5rem;
    }

    .category-card-title {
        font-size: 1.125rem;
    }

    .category-card-subtitle {
        font-size: 0.875rem;
    }

    .category-card-description {
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    .unified-category-card {
        min-height: 130px;
        padding: 1.125rem;
    }

    .category-card-header {
        margin-bottom: 0.75rem;
    }

    .category-card-icon {
        padding: 0.625rem;
    }

    .category-card-title {
        font-size: 1.375rem;
    }

    .category-card-subtitle {
        font-size: 0.9375rem;
    }

    .category-card-description {
        font-size: 0.8125rem;
        margin-top: 0.375rem;
    }
}

@media (min-width: 1025px) {
    .unified-category-card {
        min-height: 140px;
        padding: 1.5rem;
    }

    .category-card-header {
        margin-bottom: 1rem;
    }

    .category-card-icon {
        padding: 0.75rem;
    }

    .category-card-title {
        font-size: 1.5rem;
    }

    .category-card-subtitle {
        font-size: 1rem;
    }

    .category-card-description {
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
}

/* Status badge improvements */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
    font-size: 0.75rem;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Badge container fixes */
.badge-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

/* Individual badge fixes */
.badge-item {
    flex-shrink: 0;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Badge grid fixes */
.badge-grid {
    display: grid;
    gap: 0.75rem;
}

.badge-grid>div {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.badge-grid .badge-content {
    display: flex;
    align-items: center;
    min-width: 0;
}

/* Price formatting improvements */
.price-text {
    font-size: clamp(0.875rem, 2vw, 1.125rem);
    font-weight: 600;
    line-height: 1.2;
}

/* Search input improvements */
.search-input {
    transition: all 0.2s ease;
}

.search-input:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Vehicle selection state */
.vehicle-selected {
    background-color: rgba(59, 130, 246, 0.05);
    border-color: rgb(59, 130, 246);
}

/* Responsive grid for vehicle benefits */
.benefits-grid {
    display: grid;
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .benefits-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Image gallery improvements */
.image-gallery {
    position: relative;
    overflow: hidden;
    border-radius: 0.5rem;
}

.image-gallery img {
    transition: transform 0.2s ease;
}

.image-gallery:hover img {
    transform: scale(1.05);
}

/* Pagination improvements */
.pagination-container {
    padding: 1rem;
}

@media (max-width: 640px) {
    .pagination-container {
        padding: 0.75rem;
    }
}

/* Empty state improvements */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state-icon {
    margin: 0 auto 1rem;
}

/* Loading state improvements */
.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
}

/* Color adjustments for dark mode if needed */
@media (prefers-color-scheme: dark) {
    .vehicle-card {
        background-color: rgba(30, 41, 59, 0.5);
        border-color: rgba(71, 85, 105, 0.5);
    }
}

/* Animation improvements */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.vehicle-card {
    animation: slideIn 0.3s ease-out;
}

/* Responsive spacing improvements */
.responsive-spacing {
    padding: clamp(1rem, 4vw, 1.5rem);
}

/* Touch-friendly action buttons */
.action-buttons {
    gap: 0.5rem;
}

@media (max-width: 640px) {
    .action-buttons {
        flex-direction: column;
        gap: 0.75rem;
    }
}

/* Ensure content doesn't overflow on small screens */
.overflow-safe {
    overflow-wrap: break-word;
    word-wrap: break-word;
    hyphens: auto;
}

/* Bulk Actions Bar Styles */
.bulk-actions-bar {
    background: linear-gradient(to right, rgb(254, 240, 138), rgb(251, 207, 232));
    border: 1px solid rgb(251, 146, 60);
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.bulk-actions-bar:hover {
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

/* Animation for bulk actions bar */
@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: slideInFromTop 0.3s ease-out;
}

/* Mobile bulk actions improvements */
@media (max-width: 640px) {
    .bulk-actions-bar {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .bulk-actions-content {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .bulk-actions-buttons {
        flex-direction: column;
        width: 100%;
    }
}

/* Tablet bulk actions improvements */
@media (min-width: 641px) and (max-width: 1024px) {
    .bulk-actions-bar {
        padding: 0.875rem;
    }

    .bulk-actions-content {
        align-items: center;
    }

    .bulk-actions-buttons {
        flex-direction: row;
        width: auto;
    }
}

/* Desktop bulk actions improvements */
@media (min-width: 1025px) {
    .bulk-actions-bar {
        padding: 1rem;
    }

    .bulk-actions-content {
        align-items: center;
    }

    .bulk-actions-buttons {
        flex-direction: row;
        width: auto;
    }
}

/* Bulk action button styling */
.bulk-action-btn {
    transition: all 0.15s ease;
}

.bulk-action-btn:hover {
    transform: translateY(-1px);
}

.bulk-action-btn:active {
    transform: translateY(0);
}

/* Selection counter styling */
.selection-counter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: rgb(154, 52, 18);
}

/* Orange color theme for bulk actions */
.bulk-actions-orange {
    background-color: rgb(255, 251, 235);
    border-color: rgb(251, 146, 60);
}

.bulk-actions-orange .selection-counter {
    color: rgb(154, 52, 18);
}

.bulk-actions-orange .bulk-action-btn-outline {
    background-color: white;
    border-color: rgb(251, 146, 60);
    color: rgb(154, 52, 18);
}

.bulk-actions-orange .bulk-action-btn-outline:hover {
    background-color: rgb(254, 243, 199);
    border-color: rgb(251, 146, 60);
    color: rgb(154, 52, 18);
}
</style>
