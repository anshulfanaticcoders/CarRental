<template>
    <MyProfileLayout>
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
            <div class="loader h-12 w-12 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
        </div>
        <div class="">
            <p
                class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]">
                {{ _t('vendorprofilepages', 'booking_details_header') }}</p>

            <div class="mb-4">
                <input type="text" v-model="searchQuery"
                    :placeholder="_t('vendorprofilepages', 'search_bookings_placeholder')"
                    class="px-4 py-2 border border-gray-300 rounded-md w-full" />
            </div>

            <div v-if="filteredBookings.length" class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'table_id_header') }}</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_booking_id_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">
                                {{ _t('vendorprofilepages', 'table_customer_name_header') }}</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_vehicle_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_booking_date_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_return_date_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_booking_location_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_total_payment_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_amount_paid_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_amount_pending_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_payment_status_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'table_booking_status_header') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">
                                {{ _t('vendorprofilepages', 'table_cancellation_reason_header') }}</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{
                                _t('vendorprofilepages', 'actions_table_header') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(booking, index) in filteredBookings" :key="booking.id"
                            class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) *
                                pagination.per_page + index + 1 }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ booking.booking_number }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                {{ booking.customer?.first_name }} {{ booking.customer?.last_name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                {{ booking.vehicle?.brand }} <span
                                    class="bg-customLightPrimaryColor ml-2 p-1 rounded-[12px]">{{ booking.vehicle?.model
                                    }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{
                                formatDate(booking.pickup_date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{
                                formatDate(booking.return_date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{
                                booking.pickup_location }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{
                                booking.vendor_profile?.currency }} {{ booking.total_amount || _t('vendorprofilepages',
                                'not_applicable_text') }}</td>
                            <td class="px-4 py-2 text-sm text-green-600 whitespace-nowrap font-medium">{{
                                booking.payments[0]?.payment_status === 'pending' ? 'Nil' :
                                    `${booking.vendor_profile?.currency} ${booking.amount_paid || _t('vendorprofilepages',
                                'not_applicable_text')}`
                                }}</td>
                            <td class="px-4 py-2 text-sm text-yellow-600 whitespace-nowrap font-medium">{{
                                booking.payments[0]?.payment_status === 'pending' ? 'Nil' :
                                    `${booking.vendor_profile?.currency} ${booking.pending_amount ||
                                _t('vendorprofilepages', 'not_applicable_text')}`
                                }}</td>
                            <td class="px-4 py-2 text-sm capitalize">
                                <span :class="{
                                    'text-green-600 font-semibold': booking.payments[0]?.payment_status === 'succeeded',
                                    'text-yellow-500 font-semibold': booking.payments[0]?.payment_status === 'pending',
                                    'text-red-500 font-semibold': booking.payments[0]?.payment_status === 'failed',
                                    'text-gray-500 font-semibold': !booking.payments.length
                                }">
                                    {{ booking.payments[0]?.payment_status || _t('vendorprofilepages',
                                    'text_no_payment') }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm whitespace-nowrap">
                                <select v-model="booking.booking_status" @change="updateStatus(booking)"
                                    class="w-full py-2 border rounded" :class="{
                                        'text-green-600 font-medium': booking.booking_status === 'completed' || booking.booking_status === 'confirmed',
                                        'text-yellow-500 font-medium': booking.booking_status === 'pending',
                                        'text-red-500 font-medium': booking.booking_status === 'cancelled'
                                    }">
                                    <option value="pending" class="text-yellow-500 font-medium">{{
                                        _t('vendorprofilepages', 'booking_status_pending') }}</option>
                                    <option value="confirmed" class="text-green-600 font-medium">{{
                                        _t('vendorprofilepages', 'booking_status_confirmed') }}</option>
                                    <option value="completed" class="text-green-600 font-medium">{{
                                        _t('vendorprofilepages', 'booking_status_completed') }}</option>
                                    <option value="cancelled" class="text-red-500 font-medium">{{
                                        _t('vendorprofilepages', 'booking_status_cancelled') }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span v-if="booking.cancellation_reason">
                                    {{ booking.cancellation_reason }}
                                </span>
                                <span v-else class="text-gray-400 italic">
                                    {{ _t('vendorprofilepages', 'text_not_provided') }}
                                </span>
                            </td>

                            <td class="px-4 py-2 text-sm whitespace-nowrap">
                                <button v-if="booking.booking_status !== 'cancelled'"
                                    class="text-red-600 font-semibold hover:underline"
                                    @click="cancelBooking(booking.id)">
                                    {{ _t('vendorprofilepages', 'cancel_button') }}
                                </button>
                                <button @click="goToDamageProtection(booking.id)"
                                    class="text-blue-600 font-semibold hover:underline ml-4">
                                    {{ _t('vendorprofilepages', 'button_damage_protection') }}
                                </button>
                                <button @click="openCustomerDocumentsDialog(booking.customer.id)"
                                    class="text-blue-600 font-semibold hover:underline ml-4">
                                    {{ _t('vendorprofilepages', 'button_view_documents') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6">
                <span class="text-gray-500">{{ _t('vendorprofilepages', 'no_bookings_found_text') }}</span>
            </div>
            <div class="mt-[1rem] flex justify-end">
                <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                    @page-change="handlePageChange" />
            </div>
        </div>

        <!-- Customer Documents Dialog -->
        <Dialog v-model:open="isCustomerDocumentsDialogOpen">
            <DialogContent class="max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>{{ _t('vendorprofilepages', 'customer_documents_dialog_title') }}</DialogTitle>
                </DialogHeader>
                <div v-if="customerDocument" class="grid grid-cols-2 gap-4">
                    <div v-for="field in documentFields" :key="field.key" class="mb-4 flex flex-col gap-2 items-center">
                        <p class="font-semibold">{{ field.label }}</p>
                        <img v-if="customerDocument[field.key]" :src="customerDocument[field.key]" :alt="field.label"
                            class="h-20 w-[150px] object-cover mb-2 cursor-pointer"
                            @click="openImageModal(customerDocument[field.key])" />
                        <span v-else class="text-gray-500">{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                        <p class="text-sm capitalize" :class="{
                            'text-yellow-600': customerDocument.verification_status === 'pending',
                            'text-green-600': customerDocument.verification_status === 'verified',
                            'text-red-600': customerDocument.verification_status === 'rejected',
                        }">
                            {{ _t('vendorprofilepages', 'status_table_header') }}: {{
                            customerDocument.verification_status }}
                        </p>
                        <p class="text-sm text-gray-600">{{ _t('vendorprofilepages', 'doc_uploaded_on_prefix') }} {{
                            formatDate(customerDocument.created_at) }}</p>
                    </div>
                </div>
                <div v-else class="text-center py-6">
                    <span class="text-gray-500">{{ _t('vendorprofilepages', 'no_documents_available_text') }}</span>
                </div>
                <DialogFooter>
                    <Button @click="isCustomerDocumentsDialogOpen = false">{{ _t('vendorprofilepages', 'button_close')
                        }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Image Modal -->
        <Dialog v-model:open="isImageModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <img :src="selectedImage" :alt="_t('vendorprofilepages', 'document_preview_dialog_title')"
                    class="w-full h-auto" />
                <DialogFooter>
                    <Button @click="isImageModalOpen = false">{{ _t('vendorprofilepages', 'button_close') }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { useToast } from 'vue-toastification';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const toast = useToast();
const isCustomerDocumentsDialogOpen = ref(false);
const isImageModalOpen = ref(false);
const customerDocument = ref(null);
const selectedImage = ref('');
const searchQuery = ref('');
const isLoading = ref(false);

const documentFields = computed(() => [
    { key: 'driving_license_front', label: _t('vendorprofilepages', 'doc_label_driving_license_front') },
    { key: 'driving_license_back', label: _t('vendorprofilepages', 'doc_label_driving_license_back') },
    { key: 'passport_front', label: _t('vendorprofilepages', 'passport_front_table_header') },
    { key: 'passport_back', label: _t('vendorprofilepages', 'passport_back_table_header') },
]);


const goToDamageProtection = (bookingId) => {
    router.get(route('vendor.damage-protection.index', { locale: usePage().props.locale, booking: bookingId }));
};

const openCustomerDocumentsDialog = async (customerId) => {
    try {
        isLoading.value = true;
        const response = await axios.get(route('vendor.customer-documents.index', { locale: usePage().props.locale, customer: customerId }));
        console.log(response.data); // Log the response for debugging
        customerDocument.value = response.data.document || null;
        isCustomerDocumentsDialogOpen.value = true;
    } catch (error) {
        console.error('Error fetching customer documents:', error);
        toast.error('Failed to fetch customer documents. Please try again.');
    } finally {
        isLoading.value = false;
    }
};

const openImageModal = (imageUrl) => {
    if (imageUrl) {
        selectedImage.value = imageUrl;
        isImageModalOpen.value = true;
    }
};

const props = defineProps({
    bookings: {
        type: Array,
        filters: Object,
        required: true
    },
    pagination: {
        type: Object,
        required: true
    }
});

const handlePageChange = (page) => {
    router.get(route('bookings.index', { locale: usePage().props.locale }), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

const updateStatus = async (booking) => {
    isLoading.value = true;
    try {
        await axios.put(route('bookings.update', { locale: usePage().props.locale, booking: booking.id }), {
            booking_status: booking.booking_status
        });

        const statusMessages = {
            pending: _t('vendorprofilepages', 'booking_status_pending'),
            confirmed: _t('vendorprofilepages', 'booking_status_confirmed'),
            completed: _t('vendorprofilepages', 'booking_status_completed'),
            cancelled: _t('vendorprofilepages', 'booking_status_cancelled')
        };

        toast.success(`${_t('vendorprofilepages', 'status_table_header')} changed to ${statusMessages[booking.booking_status]}!`, {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
        });

        if (booking.booking_status === 'confirmed') {
            await axios.put(`/vehicles/${booking.vehicle_id}`, {
                status: 'rented'
            });
        }
        router.reload();
    } catch (error) {
        console.error("Error updating status:", error);
        router.reload();
    } finally {
        isLoading.value = false;
    }
};

const cancelBooking = async (bookingId) => {
    if (confirm(_t('vendorprofilepages', 'confirm_cancel_booking_message'))) {
        try {
            await axios.post(route('bookings.cancel', { locale: usePage().props.locale, booking: bookingId }));
            router.reload();
        } catch (err) {
            console.error("Error canceling booking:", err);
            alert(_t('vendorprofilepages', 'alert_failed_to_cancel_booking'));
        }
    }
};

const filteredBookings = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return props.bookings.filter(booking => {
        return (
            booking.booking_number.toLowerCase().includes(query) ||
            (booking.customer?.first_name.toLowerCase().includes(query) ||
                booking.customer?.last_name.toLowerCase().includes(query)) ||
            booking.vehicle?.brand.toLowerCase().includes(query) ||
            booking.vehicle?.model.toLowerCase().includes(query) ||
            booking.booking_status.toLowerCase().includes(query) ||
            (booking.payments[0]?.payment_status.toLowerCase().includes(query) || 'No Payment'.toLowerCase().includes(query)) ||
            formatDate(booking.pickup_date).toLowerCase().includes(query) ||
            formatDate(booking.return_date).toLowerCase().includes(query)
        );
    });
});

const formatDocumentType = (documentType) => {
    // Add your logic to format the document type here
    return documentType.charAt(0).toUpperCase() + documentType.slice(1).toLowerCase();
};

watch(searchQuery, (newQuery) => {
    router.get(
        route('bookings.index', { locale: usePage().props.locale }),
        { search: newQuery },
        { preserveState: true, preserveScroll: true }
    );
});
</script>

<style scoped>
.loader {
    border-top-color: #3490dc;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media screen and (max-width:768px) {
    th {
        font-size: 0.75rem;
    }

    td {
        font-size: 0.75rem;
        text-wrap-mode: nowrap;
    }

    table select {
        width: 100px;
    }
}
</style>
