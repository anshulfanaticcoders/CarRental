<template>
    <MyProfileLayout>
        <div class="">
            <p class="text-[1.75rem] font-semibold mb-6 bg-customLightPrimaryColor p-4 rounded-[12px] max-[768px]:text-[1.2rem]">Vendor Overview</p>

            <div class="grid gap-4 mb-[2rem]"
     style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">

                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">Total Vehicles</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ totalVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">Vehicles Available</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ activeVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">Vehicles Rented</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ rentedVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">Maintenance</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ maintenanceVehicles }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">Total Bookings</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ totalBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] text-yellow-600 max-[768px]:text-[1rem] text-center">Active Bookings</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ activeBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] text-green-500 max-[768px]:text-[1rem] text-center">Completed Bookings</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ completedBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] text-red-500 max-[768px]:text-[1rem] text-center">Cancelled Bookings</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ cancelledBookings }}</CardContent>
                </Card>
                <Card class="bg-customLightPrimaryColor">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">Total Revenue</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px]">{{ props.currency }}{{ totalRevenue }}</CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <Card>
                    <CardHeader><CardTitle>Booking Overview</CardTitle></CardHeader>
                    <CardContent>
                        <BarChart class="vendor-overview-barchart" :data="bookingOverview" :categories="['completed', 'confirmed', 'pending', 'cancelled']"
                            index="name" :colors="['#10B981', '#153B4F', '#FFC633', '#EA3C3C']" stacked rounded-corners="4" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Revenue (Last 12 Months)</CardTitle></CardHeader>
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
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import BarChart from "@/Components/ui/chart-bar/BarChart.vue";
import LineChart from "@/Components/ui/chart-line/LineChart.vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";

import { computed } from "vue";

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

const revenueDataAsNumbers = computed(() => {
    if (!props.revenueData) return [];

    return props.revenueData.map(item => ({
        ...item,
        total: Number(item.total),
    }));
});

const formatPrice = (price) => {
    const currencySymbol = vehicle.value.vendor_profile.currency;
    return `${currencySymbol}${price}`;
};

</script>


<style scoped>

</style>