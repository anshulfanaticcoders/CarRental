<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Customer Reviews Management</h1>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <MessageCircle class="w-4 h-4 mr-1" />
                            
                        All Reviews
                    </span>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <!-- Total Reviews Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <TrendingUp class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statistics.total_reviews || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Reviews</p>
                    </div>
                </div>

                <!-- Average Rating Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Star class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Average
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900 flex items-center justify-center gap-2">
                            {{ statistics.average_rating || '0.0' }}
                            <span class="text-2xl">★</span>
                        </p>
                        <p class="text-sm text-yellow-700 mt-1">Average Rating</p>
                    </div>
                </div>

                <!-- Pending Reviews Card -->
                <div class="relative bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-orange-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-6 h-6 text-orange-600" />
                        </div>
                        <Badge variant="secondary" class="bg-orange-500 text-white">
                            Pending
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-orange-900">{{ statistics.pending_reviews || 0 }}</p>
                        <p class="text-sm text-orange-700 mt-1">Pending</p>
                    </div>
                </div>

                <!-- Approved Reviews Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Approved
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statistics.approved_reviews || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Approved</p>
                    </div>
                </div>

                <!-- Rejected Reviews Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Rejected
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statistics.rejected_reviews || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Rejected</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search & Filter -->
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1">
                    <div class="relative">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search reviews, customers, or vehicles..."
                            @input="debounceSearch"
                            class="pl-10 pr-4 h-12"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="approved">Approved</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Enhanced Reviews Table -->
            <div v-if="reviews.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Customer</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Vehicle</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Rating</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Review</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Date</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(review, index) in reviews.data"
                                :key="review.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (reviews.current_page - 1) * reviews.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex flex-col space-y-1">
                                        <span class="font-medium">{{ review.user?.first_name || 'N/A' }}</span>
                                        <span class="text-sm text-muted-foreground">{{ review.user?.email || 'N/A' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex flex-col space-y-1">
                                        <span class="font-medium">{{ review.vehicle?.brand || 'N/A' }}</span>
                                        <span class="text-sm text-muted-foreground">{{ review.vehicle?.model || 'N/A' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center space-x-1">
                                        <span v-for="i in 5" :key="i" class="text-lg">
                                            <span :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'">★</span>
                                        </span>
                                        <span class="ml-2 text-sm text-muted-foreground">({{ review.rating }}/5)</span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-xs">
                                    <p class="line-clamp-2 text-sm">{{ truncateText(review.review_text, 60) }}</p>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge
                                        :variant="getStatusVariant(review.status)"
                                        class="capitalize"
                                    >
                                        {{ review.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm text-muted-foreground">
                                    {{ formatDate(review.created_at) }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="openReviewDetails(review)"
                                            class="h-8 px-3"
                                        >
                                            <Eye class="w-4 h-4 mr-1" />
                                            View
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="confirmDeleteReview(review)"
                                            class="h-8 px-3"
                                        >
                                            <Trash2 class="w-4 h-4 mr-1" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4">
                    <Pagination
                        :current-page="reviews.current_page"
                        :total-pages="reviews.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <div class="p-4 bg-gray-100 rounded-full">
                        <Inbox class="w-12 h-12 text-gray-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">No reviews found</h3>
                        <p class="text-sm text-muted-foreground mt-1">
                            Try adjusting your search or filter criteria
                        </p>
                    </div>
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

// Import Lucide icons
import {
  MessageCircle,
  Star,
  TrendingUp,
  ThumbsUp,
  ThumbsDown,
  Clock,
  Search,
  Eye,
  CheckCircle,
  XCircle,
  Trash2,
  Inbox
} from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { useToast } from 'vue-toastification';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';

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
    const params = {
        search: searchQuery.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get(route('admin.reviews.index'), params, {
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

// Watch for status filter changes
watch(statusFilter, (newValue) => {
    filterByStatus();
});
</script>

