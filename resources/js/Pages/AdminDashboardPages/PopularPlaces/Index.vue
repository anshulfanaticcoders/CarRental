<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Popular Places</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search plan..." class="w-[300px] search-box" @input="handleSearch" />
                </div>
                <Link 
                    :href="route('popular-places.create')" 
                    class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90"
                >
                    Create New Place
                </Link>
            </div>

            <!-- Success Message -->
            <!-- Places Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
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
                                    :src="place.image ? `${place.image}` : '/placeholder.jpg'" 
                                    class="w-16 h-16 object-cover rounded"
                                    :alt="place.place_name"
                                />
                            </TableCell>
                            <TableCell>{{ place.place_name }}</TableCell>
                            <TableCell>{{ place.city }}</TableCell>
                            <TableCell>{{ place.state }}</TableCell>
                            <TableCell>{{ place.country }}</TableCell>
                            <TableCell>{{ place.latitude }}, {{ place.longitude }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link 
                                        :href="route('popular-places.edit', place.id)"
                                        class="px-3 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]"
                                    >
                                        Edit
                                    </Link>
                                    <Button 
                                        variant="destructive"
                                        @click="openDeleteDialog(place.id)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                <!-- Pagination -->
                <div class="mt-4 flex justify-end">
                    <Pagination 
                        :current-page="places.current_page" 
                        :total-pages="places.last_page" 
                        @page-change="handlePageChange" 
                    />
                </div>
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
                        <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
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

const openDeleteDialog = (id) => {
    deletePlaceId.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    router.delete(route('popular-places.destroy', deletePlaceId.value)).then(() => {
        isDeleteDialogOpen.value = false;
    });
};

// Handle search input
const handleSearch = () => {
    router.get('/popular-places', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const handlePageChange = (page) => {
    router.get(route('popular-places.index', { page }), {
        preserveState: true,
        replace: true,
    });
};
</script>

<style>
.search-box {
    width: 300px;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    outline: none;
}

.search-box:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

table th{
    font-size: 0.95rem;
}
table td{
    font-size: 0.875rem;
}
</style>