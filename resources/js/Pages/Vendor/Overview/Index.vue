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
                <Card class="bg-customLightPrimaryColor cursor-pointer hover:shadow-lg transition-shadow" @click="showRevenueModal = true">
                    <CardHeader><CardTitle class="text-[1.5rem] max-[768px]:text-[1rem] text-center">{{ _t('vendorprofilepages', 'total_revenue_card_title') }}</CardTitle></CardHeader>
                    <CardContent class="text-[1.75rem] max-[768px]:text-[1rem] bg-customPrimaryColor text-white text-center p-0 rounded-bl-[12px] rounded-br-[12px] relative group">
                        {{ formatNumber(totalRevenue) }}
                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-20 text-white text-sm font-medium rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <span class="text-xs">Click for details</span>
                        </div>
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

            <!-- Revenue Details Modal -->
            <Dialog v-model:open="showRevenueModal">
                <DialogContent class="max-w-[800px] max-h-[80vh] overflow-auto">
                    <DialogHeader>
                        <DialogTitle>{{ _t('vendorprofilepages', 'revenue_details_modal_title') }}</DialogTitle>
                    </DialogHeader>

                    <div class="space-y-4">
                        <!-- Revenue Summary -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">{{ _t('vendorprofilepages', 'revenue_summary_title') }}</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">{{ _t('vendorprofilepages', 'total_revenue_label') }}</p>
                                    <p class="text-2xl font-bold">{{ formatNumber(totalRevenue) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">{{ _t('vendorprofilepages', 'total_bookings_label') }}</p>
                                    <p class="text-2xl font-bold">{{ totalBookings }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Details Table -->
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold">{{ _t('vendorprofilepages', 'booking_details_title') }}</h3>

                            <!-- No bookings message -->
                            <div v-if="!isLoading && (!bookingDetails || bookingDetails.length === 0)"
                                 class="bg-gray-50 p-4 rounded-lg text-center">
                                <p class="text-gray-600">No booking details available</p>
                            </div>

                            <!-- Loading state -->
                            <div v-else-if="isLoading" class="bg-gray-50 p-4 rounded-lg text-center">
                                <p class="text-gray-600">Loading booking details...</p>
                            </div>

                            <!-- Bookings table -->
                            <div v-else class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_booking_number_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_customer_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_vehicle_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_booking_currency_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_amount_paid_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_total_amount_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'table_pending_amount_header') }}</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">{{ _t('vendorprofilepages', 'status_table_header') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="booking in bookingDetails" :key="booking.id" class="hover:bg-gray-50">
                                            <td class="border border-gray-300 px-4 py-2">{{ booking.booking_number }}</td>
                                            <td class="border border-gray-300 px-4 py-2">{{ booking.customer?.first_name }} {{ booking.customer?.last_name }}</td>
                                            <td class="border border-px-4 py-2">{{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}</td>
                                            <td class="border border-gray-300 px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="getCurrencyBadgeClass(booking.booking_currency)">
                                                    {{ booking.booking_currency }}
                                                </span>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-2 text-green-600 font-medium">
                                                {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.amount_paid) }}
                                            </td>
                                            <td class="border border-gray-300 px-4 py-2 font-medium">
                                                {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.total_amount) }}
                                            </td>
                                            <td class="border border-gray-300 px-4 py-2 text-yellow-600 font-medium">
                                                {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.pending_amount) }}
                                            </td>
                                            <td class="border border-gray-300 px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="getStatusBadgeClass(booking.booking_status)">
                                                    {{ booking.booking_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Currency Breakdown -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">{{ _t('vendorprofilepages', 'currency_breakdown_title') }}</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="(amount, currency) in currencyBreakdown" :key="currency" class="text-center">
                                    <div class="text-sm text-gray-600">{{ currency }}</div>
                                    <div class="text-lg font-bold">{{ getCurrencySymbol(currency) }}{{ formatNumber(amount) }}</div>
                                    <div class="text-xs text-gray-500">{{ calculatePercentage(amount, totalRevenue) }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button @click="showRevenueModal = false">{{ _t('vendorprofilepages', 'close_button') }}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

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

// Modal state
const showRevenueModal = ref(false);
const bookingDetails = ref([]);
const isLoading = ref(false);
const currencyBreakdown = ref({});

// Fetch booking details for revenue modal
const fetchBookingDetails = async () => {
    isLoading.value = true;
    try {
        const response = await fetch(`/api/vendor/booking-details-with-revenue?locale=${usePage().props.locale}`);
        const data = await response.json();

        console.log('API Response:', data); // Debug log
        console.log('Bookings count:', data.bookings?.length || 0); // Debug log

        bookingDetails.value = data.bookings || [];
        currencyBreakdown.value = data.currencyBreakdown || {};
    } catch (error) {
        console.error('Error fetching booking details:', error);
        bookingDetails.value = [];
        currencyBreakdown.value = {};
    } finally {
        isLoading.value = false;
    }
};

// Open modal and fetch data
const openRevenueModal = () => {
    showRevenueModal.value = true;
    fetchBookingDetails();
};

// Currency symbol function
const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'AUD': 'A$',
        'CAD': 'C$',
        'CHF': 'Fr',
        'HKD': 'HK$',
        'SGD': 'S$',
        'SEK': 'kr',
        'KRW': '₩',
        'NOK': 'kr',
        'NZD': 'NZ$',
        'INR': '₹',
        'MXN': 'Mex$',
        'ZAR': 'R',
        'AED': 'AED'
    };
    return symbols[currency] || '$';
};

// Format number function
const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

// Calculate percentage
const calculatePercentage = (amount, total) => {
    if (total === 0) return 0;
    return ((amount / total) * 100).toFixed(1);
};

// Currency badge class
const getCurrencyBadgeClass = (currency) => {
    const classes = {
        'USD': 'bg-green-100 text-green-800',
        'EUR': 'bg-blue-100 text-blue-800',
        'GBP': 'bg-purple-100 text-purple-800',
        'JPY': 'bg-red-100 text-red-800',
        'AUD': 'bg-yellow-100 text-yellow-800',
        'CAD': 'bg-orange-100 text-orange-800',
        'CHF': 'bg-pink-100 text-pink-800',
        'HKD': 'bg-teal-100 text-teal-800',
        'SGD': 'bg-indigo-100 text-indigo-800',
        'SEK': 'bg-gray-100 text-gray-800',
        'KRW': 'bg-red-200 text-red-900',
        'NOK': 'bg-blue-200 text-blue-900',
        'NZD': 'bg-green-200 text-green-900',
        'INR': 'bg-orange-200 text-orange-900',
        'MXN': 'bg-yellow-200 text-yellow-900',
        'ZAR': 'bg-purple-200 text-purple-900',
        'AED': 'bg-cyan-100 text-cyan-900'
    };
    return classes[currency] || 'bg-gray-100 text-gray-800';
};

// Status badge class
const getStatusBadgeClass = (status) => {
    const classes = {
        'completed': 'bg-green-100 text-green-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
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
