<template>
    <MyProfileLayout>
        <div class="">
            <p
                class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]">
                Booking Details</p>

            <div class="mb-4">
                <input type="text" v-model="searchQuery" placeholder="Search bookings..."
                    class="px-4 py-2 border border-gray-300 rounded-md w-full" />
            </div>

            <div v-if="filteredBookings.length" class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Booking ID</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Customer Name</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Vehicle</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Booking Date</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Return Date</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Payment</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Payment Status</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Booking Status</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(booking, index) in filteredBookings" :key="booking.id"
                            class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) *
                                pagination.per_page + index + 1 }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ booking.booking_number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                {{ booking.customer?.first_name }} {{ booking.customer?.last_name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                {{ booking.vehicle?.brand }} <span
                                    class="bg-customLightPrimaryColor ml-2 p-1 rounded-[12px]">{{ booking.vehicle?.model
                                    }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ formatDate(booking.pickup_date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ formatDate(booking.return_date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ booking.vendor_profile?.currency }} {{ booking.payments[0]?.amount || 'N/A' }}</td>
                            <td class="px-4 py-2 text-sm capitalize">
                                <span :class="{
                                    'text-green-600 font-semibold': booking.payments[0]?.payment_status === 'succeeded',
                                    'text-yellow-500 font-semibold': booking.payments[0]?.payment_status === 'pending',
                                    'text-red-500 font-semibold': booking.payments[0]?.payment_status === 'failed',
                                    'text-gray-500 font-semibold': !booking.payments.length
                                }">
                                    {{ booking.payments[0]?.payment_status || 'No Payment' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm whitespace-nowrap">
                                <select v-model="booking.booking_status" @change="updateStatus(booking)"
                                    class="w-full py-2 border rounded" :class="{
                                        'text-green-600 font-medium': booking.booking_status === 'completed' || booking.booking_status === 'confirmed',
                                        'text-yellow-500 font-medium': booking.booking_status === 'pending',
                                        'text-red-500 font-medium': booking.booking_status === 'cancelled'
                                    }">
                                    <option value="pending" class="text-yellow-500 font-medium">Pending</option>
                                    <option value="confirmed" class="text-green-600 font-medium">Confirmed</option>
                                    <option value="completed" class="text-green-600 font-medium">Completed</option>
                                    <option value="cancelled" class="text-red-500 font-medium">Cancelled</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-sm whitespace-nowrap">
                                <button v-if="booking.booking_status !== 'cancelled'"
                                    class="text-red-600 font-semibold hover:underline"
                                    @click="cancelBooking(booking.id)">
                                    Cancel
                                </button>
                                <button @click="goToDamageProtection(booking.id)"
                                    class="text-blue-600 font-semibold hover:underline ml-4">
                                    Damage Protection
                                </button>
                                <button @click="openCustomerDocumentsDialog(booking.customer.id)"
                                    class="text-blue-600 font-semibold hover:underline ml-4">
                                    View Documents
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6">
                <span class="text-gray-500">No bookings found.</span>
            </div>
            <div class="mt-[1rem] flex justify-end">
                <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                    @page-change="handlePageChange" />
            </div>
        </div>

        <Dialog v-model:open="isCustomerDocumentsDialogOpen">
            <DialogContent class="max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>Customer Documents</DialogTitle>
                </DialogHeader>
                <div v-if="customerDocuments && customerDocuments.length" class="flex justify-between">
                    <div v-for="document in customerDocuments" :key="document.id"
                        class="mb-4 flex flex-col gap-2 items-center">
                        <p class="font-semibold">{{ formatDocumentType(document.document_type) }}</p>
                        <img :src="document.document_file" alt="Document Image"
                            class="h-20 w-[150px] object-cover mb-2 cursor-pointer"
                            @click="openImageModal(document.document_file)" />
                        <p class="text-sm capitalize" :class="{
                            'text-yellow-600': document.verification_status === 'pending',
                            'text-green-600': document.verification_status === 'verified',
                            'text-red-600': document.verification_status === 'rejected'
                        }">
                            Status: {{ document.verification_status }}
                        </p>

                        <p class="text-sm text-gray-600">Uploaded on: {{ formatDate(document.created_at) }}</p>
                    </div>
                </div>
                <div v-else class="text-center py-6">
                    <span class="text-gray-500">No documents available.</span>
                </div>
                <DialogFooter>
                    <Button @click="isCustomerDocumentsDialogOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Image Modal -->
        <Dialog v-model:open="isImageModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <img :src="selectedImage" alt="Document Image" class="w-full h-auto" />
                <DialogFooter>
                    <Button @click="isImageModalOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Pagination from './Pagination.vue';
import { useToast } from 'vue-toastification';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const toast = useToast();
const isCustomerDocumentsDialogOpen = ref(false);
const isImageModalOpen = ref(false);
const customerDocuments = ref([]);
const selectedImage = ref(null);
const searchQuery = ref('');

const goToDamageProtection = (bookingId) => {
    router.get(route('vendor.damage-protection.index', { booking: bookingId }));
};

const openCustomerDocumentsDialog = async (customerId) => {
    try {
        const response = await axios.get(route('vendor.customer-documents.index', { customer: customerId }));
        console.log(response.data); // Log the response
        customerDocuments.value = response.data.documents || [];
        isCustomerDocumentsDialogOpen.value = true;
    } catch (error) {
        console.error("Error fetching customer documents:", error);
        toast.error("Failed to fetch customer documents. Please try again.");
    }
};

const openImageModal = (imageUrl) => {
    selectedImage.value = imageUrl;
    isImageModalOpen.value = true;
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
    router.get(route('bookings.index'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

const updateStatus = async (booking) => {
    try {
        await axios.put(`/bookings/${booking.id}`, {
            booking_status: booking.booking_status
        });

        const statusMessages = {
            pending: 'Status changed to Pending!',
            confirmed: 'Status changed to Confirmed!',
            completed: 'Status changed to Completed!',
            cancelled: 'Status changed to Cancelled!'
        };

        toast.success(statusMessages[booking.booking_status], {
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
    }
};

const cancelBooking = async (bookingId) => {
    if (confirm('Are you sure you want to cancel this booking?')) {
        try {
            await axios.post(`/api/bookings/${bookingId}/cancel`);
            router.reload();
        } catch (err) {
            console.error("Error canceling booking:", err);
            alert("Failed to cancel booking. Please try again.");
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
        route('bookings.index'),
        { search: newQuery },
        { preserveState: true, preserveScroll: true }
    );
});
</script>

<style scoped>
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