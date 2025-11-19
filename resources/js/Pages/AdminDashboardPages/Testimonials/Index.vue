<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Testimonials Management</h1>
                <div class="flex items-center gap-4">
                    <Dialog v-model:open="isDialogOpen">
                        <DialogTrigger as-child>
                            <Button @click="openCreateDialog" class="bg-blue-600 hover:bg-blue-700">
                                <Plus class="w-4 h-4 mr-2" />
                                Add New Testimonial
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ isEditing ? 'Edit Testimonial' : 'Create Testimonial' }}</DialogTitle>
                                <DialogDescription>Fill in the details to {{ isEditing ? 'update' : 'create' }} a testimonial.</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="saveTestimonial">
                                <div class="space-y-4">
                                    <!-- Avatar Upload -->
                                    <div>
                                        <label class="block text-sm font-medium">Avatar</label>
                                        <input type="file" ref="avatarInput" @change="handleAvatarChange" accept="image/*" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                        <img v-if="form.avatarPreview" :src="form.avatarPreview" alt="Avatar Preview" class="mt-2 h-20 w-20 object-cover rounded-full" />
                                    </div>
                                    <!-- Name -->
                                    <div>
                                        <label class="block text-sm font-medium">Name</label>
                                        <Input v-model="form.name" required />
                                    </div>
                                    <!-- Review -->
                                    <div>
                                        <label class="block text-sm font-medium">Review</label>
                                        <Textarea v-model="form.review" required />
                                    </div>
                                    <!-- Ratings -->
                                    <div>
                                        <label class="block text-sm font-medium">Ratings (1-5)</label>
                                        <Input v-model.number="form.ratings" type="number" min="1" max="5" required />
                                    </div>
                                    <!-- Designation -->
                                    <div>
                                        <label class="block text-sm font-medium">Designation</label>
                                        <Input v-model="form.designation" required />
                                    </div>
                                </div>
                                <DialogFooter class="mt-6">
                                    <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                                    <Button type="submit" :disabled="processing">
                                        <span v-if="processing" class="flex items-center gap-2">
                                            <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                            {{ isEditing ? 'Updating...' : 'Creating...' }}
                                        </span>
                                        <span v-else>{{ isEditing ? 'Update' : 'Create' }}</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ flash.error }}
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Testimonials Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <MessageSquare class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Testimonials</p>
                    </div>
                </div>

                <!-- 5 Star Testimonials Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <Star class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            5 Star
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.five_star || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">5 Star Reviews</p>
                    </div>
                </div>

                <!-- 4 Star Testimonials Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <TrendingUp class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            4 Star
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ statusCounts?.four_star || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">4 Star Reviews</p>
                    </div>
                </div>

                <!-- 3 Star Testimonials Card -->
                <div class="relative bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-orange-500 bg-opacity-20 rounded-lg">
                            <MessageSquare class="w-6 h-6 text-orange-600" />
                        </div>
                        <Badge variant="secondary" class="bg-orange-500 text-white">
                            3 Star
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-orange-900">{{ statusCounts?.three_star || 0 }}</p>
                        <p class="text-sm text-orange-700 mt-1">3 Star Reviews</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search & Filter -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-center">
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search testimonials by name, review, designation..."
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="ratingFilter">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Ratings" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Ratings</SelectItem>
                            <SelectItem value="5">5 Star</SelectItem>
                            <SelectItem value="4">4 Star</SelectItem>
                            <SelectItem value="3">3 Star</SelectItem>
                            <SelectItem value="2">2 Star</SelectItem>
                            <SelectItem value="1">1 Star</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Enhanced Testimonials Table -->
            <div v-if="testimonials.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Avatar</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Name</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Review</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Rating</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Designation</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(testimonial, index) in testimonials.data" :key="testimonial.id" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (testimonials.current_page - 1) * testimonials.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="relative">
                                        <img v-if="testimonial.avatar" :src="testimonial.avatar" alt="Avatar" class="h-12 w-12 object-cover rounded-full border border-gray-200" />
                                        <div v-else class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <MessageSquare class="w-6 h-6 text-gray-400" />
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ testimonial.name }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-xs">
                                    <div class="text-sm line-clamp-2">{{ testimonial.review }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="getRatingBadgeVariant(testimonial.ratings)" class="capitalize">
                                            {{ getRatingStars(testimonial.ratings) }} {{ testimonial.ratings }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm text-muted-foreground">{{ testimonial.designation }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="openEditDialog(testimonial)">Edit</Button>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(testimonial.id)">Delete</Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="testimonials.current_page" :total-pages="testimonials.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <MessageSquare class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No testimonials found</h3>
                        <p class="text-muted-foreground">No testimonials match your current search criteria.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirm Deletion</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this testimonial? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-6">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, defineProps, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Plus,
    Star,
    MessageSquare,
    TrendingUp,
    Search
} from 'lucide-vue-next';
import {Table, TableHeader, TableRow, TableHead, TableBody, TableCell} from "@/Components/ui/table";
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const toast = useToast();

// Props from Inertia
const props = defineProps({
    testimonials: Object,
    statusCounts: Object,
    filters: Object,
    flash: Object,
});

// State
const isDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isEditing = ref(false);
const currentTestimonialId = ref(null);
const testimonialToDelete = ref(null);
const processing = ref(false);

const form = reactive({
    avatar: null,
    avatarPreview: null,
    name: '',
    review: '',
    ratings: 1,
    designation: '',
});

// Refs
const avatarInput = ref(null);

// Search and filter state
const search = ref(props.filters?.search || '');
const ratingFilter = ref(props.filters?.rating || 'all');

// Watch for changes in search and rating filter
watch([search, ratingFilter], () => {
    handleFilterChange();
});

const handleFilterChange = () => {
    router.get(route('testimonials.index'), {
        search: search.value,
        rating: ratingFilter.value
    }, {
        preserveState: true,
        replace: true,
    });
};

// CSRF Token Setup
const getCsrfToken = async () => {
    await axios.get('/sanctum/csrf-cookie');
};

// Methods
const openCreateDialog = () => {
    isEditing.value = false;
    isDialogOpen.value = true;
    resetForm();
};

const openEditDialog = async (testimonial) => {
    isEditing.value = true;
    currentTestimonialId.value = testimonial.id;
    isDialogOpen.value = true;

    form.name = testimonial.name;
    form.review = testimonial.review;
    form.ratings = testimonial.ratings;
    form.designation = testimonial.designation;
    form.avatarPreview = testimonial.avatar || null;
    form.avatar = null;
};

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        form.avatarPreview = URL.createObjectURL(file);
    }
};

const resetForm = () => {
    form.avatar = null;
    form.avatarPreview = null;
    form.name = '';
    form.review = '';
    form.ratings = 1;
    form.designation = '';
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

const saveTestimonial = async () => {
    processing.value = true;
    await getCsrfToken();

    const formData = new FormData();
    if (form.avatar) {
        formData.append('avatar', form.avatar);
    }
    formData.append('name', form.name);
    formData.append('review', form.review);
    formData.append('ratings', form.ratings);
    formData.append('designation', form.designation);

    if (isEditing.value) {
        formData.append('_method', 'POST');
        router.post(route('testimonials.update', currentTestimonialId.value), formData, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
                processing.value = false;
                toast.success('Testimonial updated successfully!');
            },
            onError: (errors) => {
                processing.value = false;
                console.error('Error updating testimonial:', errors);
                toast.error('Error updating testimonial');
            },
        });
    } else {
        router.post(route('testimonials.store'), formData, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
                processing.value = false;
                toast.success('Testimonial created successfully!');
            },
            onError: (errors) => {
                processing.value = false;
                console.error('Error creating testimonial:', errors);
                toast.error('Error creating testimonial');
            },
        });
    }
};

const openDeleteDialog = (id) => {
    testimonialToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
    if (!testimonialToDelete.value) return;

    router.delete(route('testimonials.destroy', testimonialToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            testimonialToDelete.value = null;
            toast.success('Testimonial deleted successfully!');
        },
        onError: (errors) => {
            console.error('Error deleting testimonial:', errors);
            toast.error('Error deleting testimonial');
        }
    });
};

const handlePageChange = (page) => {
    router.get(route('testimonials.index', {
        page: page,
        search: search.value,
        rating: ratingFilter.value
    }), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Flash message handling
onMounted(() => {
    if (props.flash?.success) {
        setTimeout(() => {
            router.clearFlashMessages();
        }, 3000);
    }
});

// Rating display helper
const getRatingStars = (rating) => {
    return '⭐'.repeat(rating);
};

// Rating badge variant
const getRatingBadgeVariant = (rating) => {
    switch (rating) {
        case 5:
            return 'default';
        case 4:
            return 'secondary';
        case 3:
            return 'outline';
        default:
            return 'outline';
    }
};
</script>