<template>
    <MyProfileLayout>
        <div class="space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4 text-green-800 text-sm sm:text-base">
                <div class="flex items-center justify-between">
                    <span>{{ $page.props.flash.success }}</span>
                    <button @click="clearFlashManually" class="ml-4 text-green-600 hover:text-green-800">
                        <X class="w-4 h-4 sm:w-5 sm:h-5" />
                    </button>
                </div>
            </div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {{ _t('vendorprofilepages', 'vendor_overview_header') }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
                        Welcome back! Here's your business overview
                    </p>
                </div>
                <div class="flex items-center gap-2 sm:gap-4">
                    <span class="inline-flex items-center px-2 py-1 sm:px-2.5 sm:py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs sm:text-sm font-medium">
                        <Store class="w-3 h-3 sm:w-4 sm:h-4 mr-1" />
                        <span class="hidden sm:inline">Vendor Dashboard</span>
                        <span class="sm:hidden">Dashboard</span>
                    </span>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="space-y-4 sm:space-y-0 sm:grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 sm:gap-4">
                <!-- Total Vehicles Card -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Car class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white text-xs px-2 py-1">
                            <span class="hidden sm:inline">Total</span>
                            <span class="sm:hidden">All</span>
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-900">{{ totalVehicles }}</p>
                        <p class="text-xs sm:text-sm text-blue-700 mt-1">{{ _t('vendorprofilepages', 'total_vehicles_card_title') }}</p>
                    </div>
                </div>

                <!-- Available Vehicles Card -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white text-xs px-2 py-1">
                            <span class="hidden sm:inline">Available</span>
                            <span class="sm:hidden">Free</span>
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-green-900">{{ activeVehicles }}</p>
                        <p class="text-xs sm:text-sm text-green-700 mt-1">{{ _t('vendorprofilepages', 'vehicles_available_card_title') }}</p>
                    </div>
                </div>

                <!-- Rented Vehicles Card -->
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-orange-500 bg-opacity-20 rounded-lg">
                            <CarFront class="w-5 h-5 sm:w-6 sm:h-6 text-orange-600" />
                        </div>
                        <Badge variant="secondary" class="bg-orange-500 text-white text-xs px-2 py-1">
                            Rented
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-orange-900">{{ rentedVehicles }}</p>
                        <p class="text-xs sm:text-sm text-orange-700 mt-1">{{ _t('vendorprofilepages', 'vehicles_rented_card_title') }}</p>
                    </div>
                </div>

                <!-- Maintenance Vehicles Card -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <Wrench class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white text-xs px-2 py-1">
                            <span class="hidden sm:inline">Maintenance</span>
                            <span class="sm:hidden">Repair</span>
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-red-900">{{ maintenanceVehicles }}</p>
                        <p class="text-xs sm:text-sm text-red-700 mt-1">{{ _t('vendorprofilepages', 'maintenance_vehicles_card_title') }}</p>
                    </div>
                </div>

                <!-- Total Bookings Card -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-purple-500 bg-opacity-20 rounded-lg">
                            <Calendar class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" />
                        </div>
                        <Badge variant="secondary" class="bg-purple-500 text-white text-xs px-2 py-1">
                            <span class="hidden sm:inline">Total</span>
                            <span class="sm:hidden">All</span>
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-purple-900">{{ totalBookings }}</p>
                        <p class="text-xs sm:text-sm text-purple-700 mt-1">{{ _t('vendorprofilepages', 'total_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Active Bookings Card -->
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white text-xs px-2 py-1">
                            Active
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-yellow-900">{{ activeBookings }}</p>
                        <p class="text-xs sm:text-sm text-yellow-700 mt-1">{{ _t('vendorprofilepages', 'active_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Completed Bookings Card -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white text-xs px-2 py-1">
                            <span class="hidden sm:inline">Completed</span>
                            <span class="sm:hidden">Done</span>
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-green-900">{{ completedBookings }}</p>
                        <p class="text-xs sm:text-sm text-green-700 mt-1">{{ _t('vendorprofilepages', 'completed_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Cancelled Bookings Card -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white text-xs px-2 py-1">
                            <span class="hidden sm:inline">Cancelled</span>
                            <span class="sm:hidden">Cancel</span>
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-red-900">{{ cancelledBookings }}</p>
                        <p class="text-xs sm:text-sm text-red-700 mt-1">{{ _t('vendorprofilepages', 'cancelled_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Total Revenue Card -->
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-2 bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-indigo-500 bg-opacity-20 rounded-lg">
                            <DollarSign class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" />
                        </div>
                        <Badge variant="secondary" class="bg-indigo-500 text-white text-xs px-2 py-1">
                            Revenue
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold text-indigo-900">{{ currency }} {{ formatNumber(totalRevenue) }}</p>
                        <p class="text-xs sm:text-sm text-indigo-700 mt-1">{{ _t('vendorprofilepages', 'total_revenue_card_title') }}</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Charts Section -->
            <div class="space-y-4 sm:space-y-6 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0">
                <!-- Booking Overview Chart -->
                <Card class="shadow-lg hover:shadow-xl transition-all duration-200 overflow-hidden">
                    <CardHeader class="bg-muted/50 px-4 py-3 sm:px-6 sm:py-4">
                        <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
                            <BarChart3 class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" />
                            <span class="truncate">{{ _t('vendorprofilepages', 'booking_overview_chart_title') }}</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6">
                        <div class="w-full overflow-x-auto">
                            <BarChart
                                class="vendor-overview-barchart h-64 sm:h-72 lg:h-80 min-w-[300px] sm:min-w-full"
                                :data="bookingOverview"
                                :categories="['completed', 'confirmed', 'pending', 'cancelled']"
                                index="name"
                                :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']"
                                stacked
                                rounded-corners="4"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Revenue Chart -->
                <Card class="shadow-lg hover:shadow-xl transition-all duration-200 overflow-hidden">
                    <CardHeader class="bg-muted/50 px-4 py-3 sm:px-6 sm:py-4">
                        <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
                            <TrendingUp class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" />
                            <span class="truncate">{{ _t('vendorprofilepages', 'revenue_last_12_months_chart_title') }}</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6">
                        <div class="w-full overflow-x-auto">
                            <LineChart
                                :data="revenueDataAsNumbers"
                                index="name"
                                :categories="['total']"
                                :colors="['#3B82F6']"
                                show-x-axis
                                show-y-axis
                                show-grid-lines
                                :y-formatter="(tick) => tick ? `${currency} ${new Intl.NumberFormat('en-US').format(tick)}` : ''"
                                class="h-64 sm:h-72 lg:h-80 min-w-[300px] sm:min-w-full"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, getCurrentInstance, onMounted } from "vue";
import { router, usePage } from '@inertiajs/vue3';
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
import LineChart from "@/Components/ui/chart-line/LineChart.vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import {
    Store,
    Car,
    CarFront,
    CheckCircle,
    Clock,
    Calendar,
    XCircle,
    DollarSign,
    Wrench,
    BarChart3,
    TrendingUp,
    X,
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps([
    'totalVehicles',
    'totalBookings',
    'activeBookings',
    'completedBookings',
    'cancelledBookings',
    'totalRevenue',
    'bookingOverview',
    'revenueData',
    'activeVehicles',
    'rentedVehicles',
    'maintenanceVehicles',
    'currency'
]);

// Format number function
const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

const revenueDataAsNumbers = computed(() => {
    if (!props.revenueData) return [];

    return props.revenueData.map(item => ({
        ...item,
        total: Number(item.total),
    }));
});

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

</script>

<style scoped>
/* Custom scrollbar styles */
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Ensure charts are responsive */
.vendor-overview-barchart {
    width: 100%;
    max-width: 100%;
}

/* Mobile-specific adjustments */
@media (max-width: 640px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

/* Tablet-specific adjustments */
@media (min-width: 641px) and (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Large screen optimizations */
@media (min-width: 1280px) {
    .stats-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}
</style>
