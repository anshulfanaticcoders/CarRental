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
            <div class="vr-phead">
                <div>
                    <span class="vr-eyebrow"><Store /> {{ tt('vendorprofilepages', 'vendor_dashboard_eyebrow', 'Vendor dashboard') }}</span>
                    <h2>{{ tt('vendorprofilepages', 'vendor_overview_header', 'Overview') }}</h2>
                    <p class="vr-sub">{{ tt('vendorprofilepages', 'vendor_overview_subtitle', "Welcome back! Here's your business overview.") }}</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="vr-stat-grid c4">
                <div v-for="(s, i) in statCards" :key="i" class="vr-stat">
                    <div class="vr-ic" :class="`vr-ic-${s.tone}`">
                        <component :is="s.icon" />
                    </div>
                    <div class="vr-v">{{ s.value }}</div>
                    <div class="vr-l">{{ s.label }}</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="chart-grid">
                <div class="vr-panel">
                    <div class="vr-panel-head">
                        <h3><BarChart3 /> {{ tt('vendorprofilepages', 'booking_overview_chart_title', 'Booking Overview') }}</h3>
                    </div>
                    <div class="vr-panel-body">
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
                    </div>
                </div>

                <div class="vr-panel">
                    <div class="vr-panel-head">
                        <h3><TrendingUp /> {{ tt('vendorprofilepages', 'revenue_last_12_months_chart_title', 'Revenue · Last 12 Months') }}</h3>
                    </div>
                    <div class="vr-panel-body">
                        <div class="w-full overflow-x-auto">
                            <LineChart
                                :data="revenueDataAsNumbers"
                                index="name"
                                :categories="['total']"
                                :colors="['#153B4F']"
                                show-x-axis
                                show-y-axis
                                show-grid-lines
                                :y-formatter="(tick) => tick ? `${currency} ${new Intl.NumberFormat('en-US').format(tick)}` : ''"
                                class="h-64 sm:h-72 lg:h-80 min-w-[300px] sm:min-w-full"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { computed, getCurrentInstance, markRaw } from "vue";
import { router, usePage } from '@inertiajs/vue3';
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
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};

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

const statCards = computed(() => [
    { icon: markRaw(Car), tone: 'teal', value: props.totalVehicles, label: tt('vendorprofilepages', 'total_vehicles_card_title', 'Total Vehicles') },
    { icon: markRaw(CheckCircle), tone: 'green', value: props.activeVehicles, label: tt('vendorprofilepages', 'vehicles_available_card_title', 'Available') },
    { icon: markRaw(CarFront), tone: 'amber', value: props.rentedVehicles, label: tt('vendorprofilepages', 'vehicles_rented_card_title', 'Rented') },
    { icon: markRaw(Wrench), tone: 'rose', value: props.maintenanceVehicles, label: tt('vendorprofilepages', 'maintenance_vehicles_card_title', 'Maintenance') },
    { icon: markRaw(Calendar), tone: 'violet', value: props.totalBookings, label: tt('vendorprofilepages', 'total_bookings_card_title', 'Total Bookings') },
    { icon: markRaw(Clock), tone: 'amber', value: props.activeBookings, label: tt('vendorprofilepages', 'active_bookings_card_title', 'Active Bookings') },
    { icon: markRaw(CheckCircle), tone: 'green', value: props.completedBookings, label: tt('vendorprofilepages', 'completed_bookings_card_title', 'Completed') },
    { icon: markRaw(XCircle), tone: 'rose', value: props.cancelledBookings, label: tt('vendorprofilepages', 'cancelled_bookings_card_title', 'Cancelled') },
    { icon: markRaw(DollarSign), tone: 'blue', value: `${props.currency} ${formatNumber(props.totalRevenue)}`, label: tt('vendorprofilepages', 'total_revenue_card_title', 'Total Revenue') },
]);

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

.chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 1024px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
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
