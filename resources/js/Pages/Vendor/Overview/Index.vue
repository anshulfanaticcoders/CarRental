<template>
    <MyProfileLayout>
        <div class="">
            <p class="text-[1.75rem] font-semibold mb-6 bg-customLightPrimaryColor p-4 rounded-[12px] max-[768px]:text-[1.2rem]">{{ _t('vendorprofilepages', 'vendor_overview_header') }}</p>

            <div class="grid gap-4 mb-[2rem]"
     style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">

                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'total_vehicles_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ totalVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'vehicles_available_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ activeVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'vehicles_rented_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ rentedVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'maintenance_vehicles_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ maintenanceVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'total_bookings_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ totalBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] text-yellow-600 max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'active_bookings_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ activeBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] text-green-500 max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'completed_bookings_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ completedBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] text-red-500 max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'cancelled_bookings_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ cancelledBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'total_revenue_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">
                        {{ formatNumber(totalRevenue) }}
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <Card>
                    <CardHeader><CardTitle>{{ _t('vendorprofilepages', 'booking_overview_chart_title') }}</CardTitle></CardHeader>
                    <CardContent>
                        <BarChart class="vendor-overview-barchart" :data="bookingOverview" :categories="['completed', 'confirmed', 'pending', 'cancelled']"
                            index="name" :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']" stacked rounded-corners="4" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{{ _t('vendorprofilepages', 'revenue_last_12_months_chart_title') }}</CardTitle></CardHeader>
                    <CardContent>
                        <LineChart :data="revenueDataAsNumbers" index="name" :categories="['total']" :colors="['#3B82F6']"
                            show-x-axis show-y-axis show-grid-lines
                            :y-formatter="(tick) => tick ? `$ ${new Intl.NumberFormat('en-US').format(tick)}` : ''"
                            class="h-96 w-full" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, getCurrentInstance, onMounted } from "vue";
import { router } from '@inertiajs/vue3';
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
import LineChart from "@/Components/ui/chart-line/LineChart.vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";

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

</script>


<style scoped>

</style>
