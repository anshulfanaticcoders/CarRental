<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Customer Reviews Management</span>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-500">Total Reviews</h3>
                    <p class="text-2xl font-bold">{{ statistics.total_reviews }}</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-500">Average Rating</h3>
                    <p class="text-2xl font-bold flex items-center">
                        {{ statistics.average_rating }}
                        <span class="text-yellow-500 ml-1">★</span>
                    </p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-500">Pending</h3>
                    <p class="text-2xl font-bold">{{ statistics.pending_reviews }}</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-500">Approved</h3>
                    <p class="text-2xl font-bold">{{ statistics.approved_reviews }}</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-500">Rejected</h3>
                    <p class="text-2xl font-bold">{{ statistics.rejected_reviews }}</p>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="flex flex-col md:flex-row gap-4 mt-4">
                <div class="flex-1">
                    <Input 
                        v-model="searchQuery" 
                        placeholder="Search by review text, customer name, or vehicle details..." 
                        @input="debounceSearch"
                    />
                </div>
                <div>
                    <select 
                        v-model="statusFilter"
                        @change="filterByStatus"
                        class="w-full h-10 px-3 rounded-md border border-input"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Reviews Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Customer</TableHead>
                            <TableHead>Vehicle</TableHead>
                            <TableHead>Rating</TableHead>
                            <TableHead>Review</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(review, index) in reviews.data" :key="review.id">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell>
                                {{ review.user?.first_name }} {{ review.user?.last_name }}
                            </TableCell>
                            <TableCell>
                                {{ review.vehicle?.brand }} {{ review.vehicle?.model }}
                            </TableCell>
                            <TableCell>
                                <div class="flex">
                                    <span v-for="i in 5" :key="i" class="text-lg">
                                        <span :class="i <= review.rating ? 'text-yellow-500' : 'text-gray-300'">★</span>
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>{{ truncateText(review.review_text, 50) }}</TableCell>
                            <TableCell>
                                <Badge 
                                    :variant="getStatusVariant(review.status)"
                                >
                                    {{ capitalize(review.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ formatDate(review.created_at) }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openReviewDetails(review)">
                                        View
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="destructive" 
                                        @click="confirmDeleteReview(review)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="reviews.data.length === 0">
                            <TableCell colspan="8" class="text-center py-4">
                                No reviews found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="mt-4 flex justify-end">
                    <Pagination 
                        :current-page="reviews.current_page" 
                        :total-pages="reviews.last_page" 
                        @page-change="handlePageChange" 
                    />
                </div>
            </div>
        </div>

        <!-- Review Details Dialog -->
        <Dialog :open="reviewDialogOpen" @update:open="reviewDialogOpen = $event">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Review Details</DialogTitle>
                </DialogHeader>
                
                <div v-if="selectedReview" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-semibold">Customer</span>
                            <p>{{ selectedReview.user?.first_name }} {{ selectedReview.user?.last_name }}</p>
                        </div>
                        <div>
                            <span class="font-semibold">Vehicle</span>
                            <p>{{ selectedReview.vehicle?.brand }} {{ selectedReview.vehicle?.model }}</p>
                        </div>
                        <div>
                            <span class="font-semibold">Date</span>
                            <p>{{ formatDate(selectedReview.created_at) }}</p>
                        </div>
                    </div>

                    <div>
                        <span class="font-semibold">Rating</span>
                        <div class="flex">
                            <span v-for="i in 5" :key="i" class="text-2xl">
                                <span :class="i <= selectedReview.rating ? 'text-yellow-500' : 'text-gray-300'">★</span>
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="font-semibold">Review</span>
                        <p class="whitespace-pre-wrap">{{ selectedReview.review_text }}</p>
                    </div>

                    <div>
                        <span class="font-semibold">Status</span>
                        <div class="flex gap-2 mt-2">
                            <Button 
                                :variant="selectedReview.status === 'approved' ? 'default' : 'outline'"
                                size="sm"
                                :disabled="isUpdating"
                                @click="updateReviewStatus(selectedReview.id, 'approved')"
                            >
                                Approve
                            </Button>
                            <Button 
                                :variant="selectedReview.status === 'rejected' ? 'default' : 'outline'"
                                size="sm"
                                :disabled="isUpdating"
                                @click="updateReviewStatus(selectedReview.id, 'rejected')"
                            >
                                Reject
                            </Button>
                            <Button 
                                :variant="selectedReview.status === 'pending' ? 'default' : 'outline'"
                                size="sm"
                                :disabled="isUpdating"
                                @click="updateReviewStatus(selectedReview.id, 'pending')"
                            >
                                Mark as Pending
                            </Button>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button 
                        variant="destructive" 
                        @click="confirmDeleteReview(selectedReview)"
                    >
                        Delete Review
                    </Button>
                    <Button variant="outline" @click="reviewDialogOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirm Deletion</DialogTitle>
                </DialogHeader>
                <p>Are you sure you want to delete this review? This action cannot be undone.</p>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteReview">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

// Import UI components
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import Input from "@/Components/ui/input/Input.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { useToast } from 'vue-toastification';
import Pagination from '../Users/Pagination.vue';

const toast = useToast();

const props = defineProps({
    reviews: Object,
    statistics: Object,
    filters: Object
});

// Search and filter
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// Dialog states
const reviewDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedReview = ref(null);
const reviewToDelete = ref(null);
const isUpdating = ref(false); // Track update state to disable buttons during request

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Truncate text
const truncateText = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

// Capitalize first letter
const capitalize = (text) => {
    if (!text) return '';
    return text.charAt(0).toUpperCase() + text.slice(1);
};

// Get status variant for badge
const getStatusVariant = (status) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'rejected':
            return 'destructive';
        case 'pending':
        default:
            return 'outline';
    }
};

// Open review details
const openReviewDetails = (review) => {
    selectedReview.value = { ...review }; // Create a copy to avoid mutating props
    reviewDialogOpen.value = true;
};

// Confirm delete review
const confirmDeleteReview = (review) => {
    reviewToDelete.value = review;
    deleteDialogOpen.value = true;
};

// Delete review
const deleteReview = () => {
    if (!reviewToDelete.value) return;
    
    router.delete(route('admin.reviews.destroy', reviewToDelete.value.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            reviewDialogOpen.value = false;
            reviewToDelete.value = null;
            toast.success('Review deleted successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error[0], {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        }
    });
};

// Update review status
const updateReviewStatus = (reviewId, status) => {
    if (isUpdating.value) return;
    isUpdating.value = true;

    router.patch(route('admin.reviews.update-status', reviewId), {
        status: status
    }, {
        onSuccess: () => {
            // Update the selectedReview status locally
            if (selectedReview.value && selectedReview.value.id === reviewId) {
                selectedReview.value.status = status;
            }
            // Refresh reviews list to update table and statistics
            router.reload({
                only: ['reviews', 'statistics'],
                preserveState: true,
                onSuccess: () => {
                    toast.success(`Review status updated to ${status}!`, {
                        position: 'top-right',
                        timeout: 3000,
                    });
                }
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error[0], {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        },
        onFinish: () => {
            isUpdating.value = false;
        }
    });
};

// Search with debounce
const debounceSearch = debounce(() => {
    router.get(route('admin.reviews.index'), {
        search: searchQuery.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true
    });
}, 500);

// Filter by status
const filterByStatus = () => {
    router.get(route('admin.reviews.index'), {
        search: searchQuery.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true
    });
};

const handlePageChange = (page) => {
    router.get(route('admin.reviews.index', { page }), {
        preserveState: true,
        replace: true,
    });
};

// Watch for search query changes
watch(searchQuery, debounceSearch);
</script>

<style scoped>
table th {
    font-size: 0.95rem;
}
table td {
    font-size: 0.875rem;
}

/* Custom button styles for clearer active states */
button[variant="default"][data-state="approved"] {
    background-color: #22c55e; /* Green for approved */
    color: white;
}
button[variant="default"][data-state="rejected"] {
    background-color: #ef4444; /* Red for rejected */
    color: white;
}
button[variant="default"][data-state="pending"] {
    background-color: #eab308; /* Yellow for pending */
    color: white;
}
button[variant="outline"] {
    background-color: transparent;
    border: 1px solid #d1d5db; /* Gray border for inactive */
    color: #374151;
}
button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>