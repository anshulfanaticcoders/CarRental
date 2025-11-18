<template>
    <AdminDashboardLayout>
        <div class="mx-auto py-6 w-[95%]">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">All Popular Places</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage popular destination places</p>
                </div>
                <div class="flex items-center gap-4">
                    <Input
                        v-model="search"
                        placeholder="Search places..."
                        class="w-80"
                        @input="handleSearch"
                    />
                    <Link :href="route('popular-places.create')">
                        <Button>Create New Place</Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Image</TableHead>
                            <TableHead>Place Name</TableHead>
                            <TableHead>City</TableHead>
                            <TableHead>State</TableHead>
                            <TableHead>Country</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="place in places.data" :key="place.id">
                            <TableCell>
                                <img
                                    :src="place.image || '/placeholder-image.jpg'"
                                    :alt="place.place_name"
                                    class="w-16 h-16 rounded-md object-cover border"
                                >
                            </TableCell>
                            <TableCell class="font-medium">{{ place.place_name }}</TableCell>
                            <TableCell>{{ place.city }}</TableCell>
                            <TableCell>{{ place.state }}</TableCell>
                            <TableCell>{{ place.country }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ place.latitude }}, {{ place.longitude }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(place.id)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openDeleteDialog(place.id)" :disabled="isDeleting">
                                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-end">
                <Pagination :current-page="places.current_page" :total-pages="places.last_page"
                    @page-change="handlePageChange" />
            </div>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this place? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete" :disabled="isDeleting">
                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                    </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { ref } from 'vue';
import { Input } from '@/Components/ui/input';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog'

const props = defineProps({
    places: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const isDeleteDialogOpen = ref(false);
const deletePlaceId = ref(null);
const isDeleting = ref(false);

const openDeleteDialog = (id) => {
    deletePlaceId.value = id;
    isDeleteDialogOpen.value = true;
};

const openEditDialog = (id) => {
    router.visit(route('popular-places.edit', id));
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(route('popular-places.destroy', deletePlaceId.value), {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

// Handle search input
const handleSearch = () => {
    router.get(route('popular-places.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const handlePageChange = (page) => {
    router.get(route('popular-places.index', { page }));
};
</script>