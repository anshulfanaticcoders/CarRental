<template>
    <MyProfileLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">{{ _t('vendorprofilepages', 'vendor_overview_header') }}</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <Store class="w-4 h-4 mr-1" />
                        Vendor Dashboard
                    </span>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                <!-- Total Vehicles Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Car class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ totalVehicles }}</p>
                        <p class="text-sm text-blue-700 mt-1">{{ _t('vendorprofilepages', 'total_vehicles_card_title') }}</p>
                    </div>
                </div>

                <!-- Available Vehicles Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Available
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ activeVehicles }}</p>
                        <p class="text-sm text-green-700 mt-1">{{ _t('vendorprofilepages', 'vehicles_available_card_title') }}</p>
                    </div>
                </div>

                <!-- Rented Vehicles Card -->
                <div class="relative bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-orange-500 bg-opacity-20 rounded-lg">
                            <CarFront class="w-6 h-6 text-orange-600" />
                        </div>
                        <Badge variant="secondary" class="bg-orange-500 text-white">
                            Rented
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-orange-900">{{ rentedVehicles }}</p>
                        <p class="text-sm text-orange-700 mt-1">{{ _t('vendorprofilepages', 'vehicles_rented_card_title') }}</p>
                    </div>
                </div>

                <!-- Maintenance Vehicles Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <Wrench class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Maintenance
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ maintenanceVehicles }}</p>
                        <p class="text-sm text-red-700 mt-1">{{ _t('vendorprofilepages', 'maintenance_vehicles_card_title') }}</p>
                    </div>
                </div>

                <!-- Total Bookings Card -->
                <div class="relative bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-500 bg-opacity-20 rounded-lg">
                            <Calendar class="w-6 h-6 text-purple-600" />
                        </div>
                        <Badge variant="secondary" class="bg-purple-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-purple-900">{{ totalBookings }}</p>
                        <p class="text-sm text-purple-700 mt-1">{{ _t('vendorprofilepages', 'total_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Active Bookings Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Active
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ activeBookings }}</p>
                        <p class="text-sm text-yellow-700 mt-1">{{ _t('vendorprofilepages', 'active_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Completed Bookings Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Completed
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ completedBookings }}</p>
                        <p class="text-sm text-green-700 mt-1">{{ _t('vendorprofilepages', 'completed_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Cancelled Bookings Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Cancelled
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ cancelledBookings }}</p>
                        <p class="text-sm text-red-700 mt-1">{{ _t('vendorprofilepages', 'cancelled_bookings_card_title') }}</p>
                    </div>
                </div>

                <!-- Total Revenue Card -->
                <div class="relative bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] md:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-indigo-500 bg-opacity-20 rounded-lg">
                            <DollarSign class="w-6 h-6 text-indigo-600" />
                        </div>
                        <Badge variant="secondary" class="bg-indigo-500 text-white">
                            Revenue
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-indigo-900">{{ formatNumber(totalRevenue) }}</p>
                        <p class="text-sm text-indigo-700 mt-1">{{ _t('vendorprofilepages', 'total_revenue_card_title') }}</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Booking Overview Chart -->
                <Card class="shadow-lg hover:shadow-xl transition-all duration-200">
                    <CardHeader class="bg-muted/50">
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="w-5 h-5" />
                            {{ _t('vendorprofilepages', 'booking_overview_chart_title') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-6">
                        <BarChart
                            class="vendor-overview-barchart h-80"
                            :data="bookingOverview"
                            :categories="['completed', 'confirmed', 'pending', 'cancelled']"
                            index="name"
                            :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']"
                            stacked
                            rounded-corners="4"
                        />
                    </CardContent>
                </Card>

                <!-- Revenue Chart -->
                <Card class="shadow-lg hover:shadow-xl transition-all duration-200">
                    <CardHeader class="bg-muted/50">
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="w-5 h-5" />
                            {{ _t('vendorprofilepages', 'revenue_last_12_months_chart_title') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-6">
                        <LineChart
                            :data="revenueDataAsNumbers"
                            index="name"
                            :categories="['total']"
                            :colors="['#3B82F6']"
                            show-x-axis
                            show-y-axis
                            show-grid-lines
                            :y-formatter="(tick) => tick ? `$ ${new Intl.NumberFormat('en-US').format(tick)}` : ''"
                            class="h-80 w-full"
                        />
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
