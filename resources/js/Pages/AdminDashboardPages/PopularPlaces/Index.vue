<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Popular Places</h1>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                        Total: {{ places?.total || 0 }}
                    </span>
                    <Link :href="route('popular-places.create')">
                        <Button>
                            <Plus class="w-4 h-4 mr-1" />
                            New Place
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by name, city, state, country"
                        class="pl-10 pr-4 h-12 text-base"
                    />
                </div>
            </div>

            <div v-if="places?.data?.length" class="flex flex-wrap items-center gap-3 rounded-xl border bg-white px-4 py-3">
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

            <div v-if="places?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[50px] px-4 py-3">
                                    <Checkbox
                                        :checked="isAllOnPageSelected"
                                        @update:checked="toggleSelectAllOnPage"
                                        aria-label="Select all on page"
                                    />
                                </TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 w-[72px]">Sr No.</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Image</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Place Name</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">City</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">State</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Country</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Coordinates</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Unified ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(place, index) in places.data" :key="place.id">
                                <TableCell class="px-4 py-3">
                                    <Checkbox
                                        :checked="selectedIds.includes(place.id)"
                                        @update:checked="(checked) => toggleRow(place.id, checked)"
                                        :aria-label="`Select ${place.place_name}`"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    {{ (places.from || 1) + index }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <img
                                        :src="place.image || '/placeholder-image.jpg'"
                                        :alt="place.place_name"
                                        class="w-16 h-16 rounded-md object-cover border"
                                        loading="lazy"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ place.place_name }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ place.city || '—' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ place.state || '—' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ place.country || '—' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm text-muted-foreground">
                                    <span v-if="place.latitude && place.longitude">{{ place.latitude }}, {{ place.longitude }}</span>
                                    <span v-else>—</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm text-muted-foreground">{{ place.unified_location_id || '—' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="openEditDialog(place.id)" class="flex items-center gap-1">
                                            <Edit class="w-3 h-3" />
                                            Edit
                                        </Button>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(place)" class="flex items-center gap-1">
                                            <Trash2 class="w-3 h-3" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="places.current_page" :total-pages="places.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <MapPin class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No places found</h3>
                        <p class="text-muted-foreground">No popular places match your filters.</p>
                    </div>
                </div>
            </div>

            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete place?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <template v-if="deleteTarget">
                                This will permanently delete <span class="font-medium text-foreground">{{ deleteTarget.place_name }}</span>. This action cannot be undone.
                            </template>
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isDeleting">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete" :disabled="isDeleting">
                            <span v-if="isDeleting">Deleting...</span>
                            <span v-else>Delete</span>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog v-model:open="isBulkDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete selected places?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete {{ selectedIds.length }} place{{ selectedIds.length > 1 ? 's' : '' }}. This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isBulkDeleting">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmBulkDelete" :disabled="isBulkDeleting">
                            <span v-if="isBulkDeleting">Deleting...</span>
                            <span v-else>Delete {{ selectedIds.length }} Place{{ selectedIds.length > 1 ? 's' : '' }}</span>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Search, Plus, Edit, Trash2, MapPin } from 'lucide-vue-next';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    places: Object,
    filters: Object,
    status: String,
});

const search = ref(props.filters?.search || '');

const isDeleteDialogOpen = ref(false);
const deleteTarget = ref(null);
const isDeleting = ref(false);

const openDeleteDialog = (place) => {
    deleteTarget.value = place;
    isDeleteDialogOpen.value = true;
};

const openEditDialog = (id) => {
    router.visit(route('popular-places.edit', id));
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(route('popular-places.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${deleteTarget.value.place_name}`);
            isDeleteDialogOpen.value = false;
            deleteTarget.value = null;
        },
        onError: () => toast.error('Failed to delete place'),
        onFinish: () => { isDeleting.value = false; },
    });
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('popular-places.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

const handlePageChange = (page) => {
    router.get(route('popular-places.index'), { page, search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

// Bulk selection
const selectedIds = ref([]);
const bulkAction = ref('');
const isBulkDeleteDialogOpen = ref(false);
const isBulkDeleting = ref(false);

const visibleIds = computed(() => (props.places?.data || []).map((p) => p.id));
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

const applyBulkAction = () => {
    if (!bulkAction.value) return;
    if (selectedIds.value.length === 0) {
        toast.error('Select at least one place first');
        return;
    }
    if (bulkAction.value === 'delete') isBulkDeleteDialogOpen.value = true;
};

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;
    const count = selectedIds.value.length;
    isBulkDeleting.value = true;
    router.delete('/popular-places/bulk', {
        data: { ids: [...selectedIds.value] },
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${count} place${count > 1 ? 's' : ''}`);
            selectedIds.value = [];
            bulkAction.value = '';
            isBulkDeleteDialogOpen.value = false;
        },
        onError: () => toast.error('Failed to delete selected places'),
        onFinish: () => { isBulkDeleting.value = false; },
    });
};

watch(() => props.places?.current_page, () => { selectedIds.value = []; });
watch(() => props.status, (msg) => { if (msg) toast.success(msg); }, { immediate: true });
</script>
