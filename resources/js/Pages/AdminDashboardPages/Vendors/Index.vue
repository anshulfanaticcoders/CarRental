<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Vendors Management</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <Store class="w-4 h-4 mr-1" />
                        All Vendors
                    </span>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Vendors Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Store class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Vendors</p>
                    </div>
                </div>

                <!-- Approved Vendors Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <UserCheck class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Approved
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.approved || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Approved Vendors</p>
                    </div>
                </div>

                <!-- Pending Vendors Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Pending
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ statusCounts?.pending || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">Pending Vendors</p>
                    </div>
                </div>

                <!-- Rejected Vendors Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <UserX class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Rejected
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statusCounts?.rejected || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Rejected Vendors</p>
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
                            placeholder="Search vendors by company, email..."
                            class="pl-10 pr-4 h-12 text-base"
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
                            <SelectItem value="approved">Approved</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditUser :user="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <!-- Image Preview Dialog -->
            <Dialog v-model:open="isImageModalOpen">
                <DialogContent class="sm:max-w-[80%]">
                    <DialogHeader>
                        <DialogTitle>Document Preview</DialogTitle>
                    </DialogHeader>
                    <div class="flex justify-center">
                        <img :src="selectedImage" alt="Document preview" class="max-w-full max-h-[80vh] object-contain">
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isImageModalOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <AdminConfirmDialog
                v-model:open="isDeleteDialogOpen"
                title="Delete vendor?"
                description="This vendor will be removed using the existing admin delete route. This action cannot be undone."
                confirm-label="Delete vendor"
                :processing="isDeleting"
                @confirm="confirmDelete"
            />

            <!-- Bulk Actions Bar (WordPress style) -->
            <div v-if="users.data.length > 0" class="flex flex-wrap items-center gap-3 rounded-xl border bg-white px-4 py-3">
                <Select v-model="bulkAction">
                    <SelectTrigger class="w-48 h-10">
                        <SelectValue placeholder="Bulk actions" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="delete">Delete</SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" size="sm" @click="applyBulkAction" :disabled="!bulkAction">Apply</Button>
                <span v-if="selectedIds.length > 0" class="text-sm text-slate-600">
                    {{ selectedIds.length }} selected
                </span>
                <Button v-if="selectedIds.length > 0" variant="ghost" size="sm" @click="selectedIds = []">Clear</Button>
            </div>

            <!-- Enhanced Vendors Table -->
            <div v-if="users.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[50px] px-4 py-3">
                                    <Checkbox
                                        :checked="isAllOnPageSelected"
                                        @update:checked="toggleSelectAllOnPage"
                                        aria-label="Select all on page"
                                    />
                                </TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Vendor Name</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Company</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Country</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="(vendorProfile,index) in users.data" :key="vendorProfile.id">
                            <TableRow class="hover:bg-muted/25 transition-colors">
                                <TableCell class="px-4 py-3">
                                    <Checkbox
                                        :checked="selectedIds.includes(vendorProfile.user_id)"
                                        @update:checked="(checked) => toggleRow(vendorProfile.user_id, checked)"
                                        :aria-label="`Select ${vendorProfile.user?.first_name} ${vendorProfile.user?.last_name}`"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (users.current_page - 1) * users.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ vendorProfile.user?.first_name }} {{ vendorProfile.user?.last_name }}</div>
                                    <div class="text-sm text-muted-foreground">User ID: {{ vendorProfile.user_id }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="font-medium">{{ vendorProfile.company_name || 'N/A' }}</div>
                                    <div class="text-sm text-muted-foreground">{{ vendorProfile.company_email || 'No company email' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div v-if="vendorCountry(vendorProfile).code" class="flex items-center gap-2">
                                        <img
                                            :src="`https://flagcdn.com/w20/${vendorCountry(vendorProfile).code.toLowerCase()}.png`"
                                            :alt="vendorCountry(vendorProfile).name"
                                            width="20"
                                            height="15"
                                            class="rounded-sm border border-slate-200"
                                            loading="lazy"
                                            @error="$event.target.style.visibility = 'hidden'"
                                        />
                                        <span class="text-sm">{{ vendorCountry(vendorProfile).name }}</span>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeVariant(vendorProfile.status)" class="capitalize">
                                        {{ vendorProfile.status || 'N/A' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="toggleVendorDetails(vendorProfile.id)" class="flex items-center gap-1">
                                            <ChevronUp v-if="isVendorDetailsOpen(vendorProfile.id)" class="w-3 h-3" />
                                            <ChevronDown v-else class="w-3 h-3" />
                                            Details
                                        </Button>
                                        <Button v-if="vendorProfile.status === 'pending'" size="sm" class="flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white" @click="quickStatusUpdate(vendorProfile, 'approved')">
                                            <UserCheck class="w-3 h-3" />
                                            Approve
                                        </Button>
                                        <Button v-if="vendorProfile.status === 'pending'" size="sm" variant="destructive" class="flex items-center gap-1" @click="quickStatusUpdate(vendorProfile, 'rejected')">
                                            <UserX class="w-3 h-3" />
                                            Reject
                                        </Button>
                                        <Button size="sm" variant="outline" @click="openViewDialog(vendorProfile)" class="flex items-center gap-1">
                                            <Eye class="w-3 h-3" />
                                            View
                                        </Button>
                                        <Button size="sm" variant="outline" @click="openEditDialog(vendorProfile)" class="flex items-center gap-1">
                                            <Edit class="w-3 h-3" />
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="openDeleteDialog(vendorProfile.id)"
                                            class="flex items-center gap-1"
                                            :disabled="isDeleting && deleteVendorProfileId === vendorProfile.id"
                                        >
                                            <div
                                                v-if="isDeleting && deleteVendorProfileId === vendorProfile.id"
                                                class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
                                            ></div>
                                            <Trash2 v-else class="w-3 h-3" />
                                            {{ isDeleting && deleteVendorProfileId === vendorProfile.id ? 'Deleting...' : 'Delete' }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="isVendorDetailsOpen(vendorProfile.id)" class="bg-muted/20">
                                <TableCell colspan="7" class="px-4 py-4">
                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div class="rounded-lg border bg-background/40 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Record</p>
                                            <dl class="mt-3 space-y-2 text-sm">
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">Vendor profile ID</dt>
                                                    <dd class="font-medium">{{ vendorProfile.id }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">User ID</dt>
                                                    <dd class="font-medium">{{ vendorProfile.user_id }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">Created</dt>
                                                    <dd class="font-medium">{{ formatDate(vendorProfile.created_at) }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                        <div class="rounded-lg border bg-background/40 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Contact</p>
                                            <dl class="mt-3 space-y-2 text-sm">
                                                <div>
                                                    <dt class="text-muted-foreground">Company name</dt>
                                                    <dd class="font-medium">{{ vendorProfile.company_name || 'N/A' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-muted-foreground">Company email</dt>
                                                    <dd class="break-all font-medium">{{ vendorProfile.company_email || 'N/A' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-muted-foreground">Country</dt>
                                                    <dd class="font-medium">{{ vendorCountry(vendorProfile).name || 'N/A' }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                        <div class="rounded-lg border bg-background/40 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Documents</p>
                                            <div class="mt-3 grid grid-cols-2 gap-3">
                                                <button
                                                    v-if="vendorProfile.user?.vendor_document?.passport_front"
                                                    type="button"
                                                    class="group relative overflow-hidden rounded-lg border bg-background text-left"
                                                    @click="openImageModal(vendorProfile.user.vendor_document.passport_front)"
                                                >
                                                    <img
                                                        :src="vendorProfile.user.vendor_document.passport_front"
                                                        alt="Passport Front"
                                                        class="h-24 w-full object-cover transition-opacity group-hover:opacity-80"
                                                    />
                                                    <span class="block px-2 py-1 text-xs font-medium">Passport front</span>
                                                </button>
                                                <div v-else class="flex h-[124px] items-center justify-center rounded-lg border bg-background/60 text-xs text-muted-foreground">
                                                    No front image
                                                </div>
                                                <button
                                                    v-if="vendorProfile.user?.vendor_document?.passport_back"
                                                    type="button"
                                                    class="group relative overflow-hidden rounded-lg border bg-background text-left"
                                                    @click="openImageModal(vendorProfile.user.vendor_document.passport_back)"
                                                >
                                                    <img
                                                        :src="vendorProfile.user.vendor_document.passport_back"
                                                        alt="Passport Back"
                                                        class="h-24 w-full object-cover transition-opacity group-hover:opacity-80"
                                                    />
                                                    <span class="block px-2 py-1 text-xs font-medium">Passport back</span>
                                                </button>
                                                <div v-else class="flex h-[124px] items-center justify-center rounded-lg border bg-background/60 text-xs text-muted-foreground">
                                                    No back image
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Store class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No vendors found</h3>
                        <p class="text-muted-foreground">No vendor applications match your current search criteria.</p>
                    </div>
                </div>
            </div>

            <AdminConfirmDialog
                v-model:open="isBulkDeleteDialogOpen"
                title="Delete selected vendors?"
                :description="`This will permanently delete ${selectedIds.length} vendor${selectedIds.length > 1 ? 's' : ''}. Existing bulk delete behavior is unchanged.`"
                :confirm-label="`Delete ${selectedIds.length} Vendor${selectedIds.length > 1 ? 's' : ''}`"
                :processing="isBulkDeleting"
                @confirm="confirmBulkDelete"
            />

            <AdminConfirmDialog
                v-model:open="isStatusConfirmOpen"
                :title="statusConfirmCopy.title"
                :description="statusConfirmCopy.description"
                :confirm-label="statusConfirmCopy.confirmLabel"
                :variant="statusConfirmCopy.variant"
                :processing="isStatusUpdating"
                @confirm="confirmQuickStatusUpdate"
            />
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Input } from "@/Components/ui/input";
import { Checkbox } from "@/Components/ui/checkbox";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Store,
  UserCheck,
  UserX,
  Clock,
  Search,
  Eye,
  Edit,
  Trash2,
  ChevronDown,
  ChevronUp
} from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import EditUser from "@/Pages/AdminDashboardPages/Vendors/EditUser.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Vendors/ViewUser.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import AdminConfirmDialog from "@/Pages/AdminDashboardPages/Shared/AdminConfirmDialog.vue";

const props = defineProps({
    users: Object,
    statusCounts: Object,
    filters: Object,
});

const search = ref(props.filters.search || ''); // Initialize search with the filter value
const statusFilter = ref(props.filters?.status || 'all'); // Initialize status filter

// Country lookup (name -> ISO code) for flag rendering
const countriesByName = ref({});
const countriesByCode = ref({});
onMounted(async () => {
    try {
        const { data } = await axios.get('/countries.json');
        if (Array.isArray(data)) {
            data.forEach((c) => {
                if (c.name) countriesByName.value[c.name.toLowerCase()] = { code: c.code, name: c.name };
                if (c.code) countriesByCode.value[c.code.toLowerCase()] = { code: c.code, name: c.name };
            });
        }
    } catch (e) { /* fallback handled by vendorCountry */ }
});

const vendorCountry = (vendor) => {
    const raw = (vendor?.user?.profile?.country || '').toString().trim();
    if (!raw) return { code: '', name: '' };
    const lower = raw.toLowerCase();
    if (raw.length === 2 && countriesByCode.value[lower]) return countriesByCode.value[lower];
    if (countriesByName.value[lower]) return countriesByName.value[lower];
    return { code: raw.length === 2 ? raw : '', name: raw };
};

// Bulk selection
const selectedIds = ref([]);
const expandedVendorRows = ref([]);
const bulkAction = ref('');
const isBulkDeleteDialogOpen = ref(false);
const isBulkDeleting = ref(false);

const visibleIds = computed(() => (props.users?.data || []).map((v) => v.user_id).filter(Boolean));
const isAllOnPageSelected = computed(
    () => visibleIds.value.length > 0 && visibleIds.value.every((id) => selectedIds.value.includes(id)),
);

const toggleRow = (id, checked) => {
    if (checked) {
        if (!selectedIds.value.includes(id)) selectedIds.value.push(id);
    } else {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }
};

const toggleSelectAllOnPage = (checked) => {
    if (checked) {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...visibleIds.value]));
    } else {
        selectedIds.value = selectedIds.value.filter((id) => !visibleIds.value.includes(id));
    }
};

const isVendorDetailsOpen = (id) => expandedVendorRows.value.includes(id);

const toggleVendorDetails = (id) => {
    if (isVendorDetailsOpen(id)) {
        expandedVendorRows.value = expandedVendorRows.value.filter((rowId) => rowId !== id);
        return;
    }

    expandedVendorRows.value = [id];
};

const applyBulkAction = () => {
    if (!bulkAction.value) return;
    if (selectedIds.value.length === 0) {
        toast.error('Select at least one vendor first');
        return;
    }
    if (bulkAction.value === 'delete') isBulkDeleteDialogOpen.value = true;
};

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;
    const count = selectedIds.value.length;
    isBulkDeleting.value = true;
    router.delete('/vendors/bulk', {
        data: { ids: [...selectedIds.value] },
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${count} vendor${count > 1 ? 's' : ''}`);
            selectedIds.value = [];
            bulkAction.value = '';
            isBulkDeleteDialogOpen.value = false;
        },
        onError: () => toast.error('Failed to delete selected vendors'),
        onFinish: () => { isBulkDeleting.value = false; },
    });
};

watch(() => props.users?.current_page, () => { selectedIds.value = []; });
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const isImageModalOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const isDeleting = ref(false)
const isStatusConfirmOpen = ref(false)
const isStatusUpdating = ref(false)
const selectedImage = ref('')
const editForm = ref({});
const viewForm = ref({});
const deleteVendorProfileId = ref(null);
const statusPendingVendor = ref(null);
const statusPendingValue = ref('');

const statusConfirmCopy = computed(() => {
    const isApproval = statusPendingValue.value === 'approved';
    const vendorName = statusPendingVendor.value?.company_name || 'this vendor';

    return {
        title: isApproval ? 'Approve vendor?' : 'Reject vendor?',
        description: `This will ${isApproval ? 'approve' : 'reject'} ${vendorName} using the existing vendor status update route.`,
        confirmLabel: isApproval ? 'Approve vendor' : 'Reject vendor',
        variant: isApproval ? 'warning' : 'danger',
    };
});

// Handle search input
const handleSearch = () => {
    const params = {
        search: search.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get('/vendors', params, {
        preserveState: true,
        replace: true,
    });
};

// Filter by status
const filterByStatus = () => {
    const params = {
        search: search.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get('/vendors', params, {
        preserveState: true,
        replace: true,
    });
};

// Watch for search query changes
watch(search, (newValue) => {
    handleSearch();
});

// Watch for status filter changes
watch(statusFilter, (newValue) => {
    filterByStatus();
});

const quickStatusUpdate = (user, status) => {
    statusPendingVendor.value = user;
    statusPendingValue.value = status;
    isStatusConfirmOpen.value = true;
};

const confirmQuickStatusUpdate = () => {
    if (!statusPendingVendor.value || !statusPendingValue.value) return;

    const user = statusPendingVendor.value;
    const status = statusPendingValue.value;
    isStatusUpdating.value = true;

    router.put(`/vendors/${user.id}`, { status }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(status === 'approved' ? 'Vendor approved successfully' : 'Vendor rejected');
        },
        onError: () => {
            toast.error('Failed to update vendor status');
        },
        onFinish: () => {
            isStatusUpdating.value = false;
            isStatusConfirmOpen.value = false;
            statusPendingVendor.value = null;
            statusPendingValue.value = '';
        },
    });
};

const openEditDialog = (user) => {
    editForm.value = { ...user };
    isEditDialogOpen.value = true;
};

const openViewDialog = (user) => {
    viewForm.value = { ...user };
    isViewDialogOpen.value = true;
};

const openImageModal = (imageUrl) => {
    selectedImage.value = imageUrl
    isImageModalOpen.value = true
}

const openDeleteDialog = (vendorProfileId) => {
    deleteVendorProfileId.value = vendorProfileId;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`/vendors/${deleteVendorProfileId.value}`, {
        onSuccess: () => {
            toast.success('Vendor deleted successfully');
        },
        onError: () => {
            toast.error('Failed to delete vendor');
        },
        onFinish: () => {
            isDeleting.value = false;
            deleteVendorProfileId.value = null;
            isDeleteDialogOpen.value = false;
        }
    });
};

const handlePageChange = (page) => {
    const params = {
        page: page,
        search: search.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get('/vendors', params, {
        preserveState: true,
        replace: true,
    });
};
const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'approved':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'rejected':
            return 'destructive';
        default:
            return 'default';
    }
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};
</script>
