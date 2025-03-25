<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">All Plans</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search plan..." class="w-[300px]" @input="handleSearch" />
                </div>
                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button>Create New Plan</Button>
                    </DialogTrigger>
                    <CreateUser @close="isCreateDialogOpen = false" />
                </Dialog>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditUser :plan="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :plan="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <div class="rounded-md border p-5  mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Plan Type</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Plan Value</TableHead>
                            <TableHead>Features</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(plan, index) in plans.data" :key="plan.id">
                            <TableCell>{{ (plans.current_page - 1) * plans.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ plan.plan_type }}</TableCell>
                            <TableCell>
                                <div class="max-w-[300px] truncate">
                                    {{ plan.plan_description || 'No description' }}
                                </div>
                            </TableCell>
                            <TableCell>{{ Number(plan.plan_value).toFixed(2) }}</TableCell>
                            <TableCell>
                                <div v-if="plan.features && plan.features.length">
                                    <ul class="list-disc list-inside">
                                        <li v-for="(feature, index) in plan.features" :key="index">
                                            {{ feature }}
                                        </li>
                                    </ul>
                                </div>
                                <span v-else>No features</span>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openViewDialog(plan)">
                                        View
                                    </Button>
                                    <Button size="sm" variant="outline" @click="openEditDialog(plan)">
                                        Edit
                                        <img :src=editIcon alt="">
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openDeleteDialog(plan.id)">Delete</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <!-- Pagination -->
                <div class="mt-4 flex justify-end">
                    <Pagination :current-page="plans.current_page" :total-pages="plans.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this plan? This action cannot be undone.
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
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import { Input } from "@/Components/ui/input";
import editIcon from "../../../../assets/Pencil.svg";
import { Dialog, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import CreateUser from "@/Pages/AdminDashboardPages/Plans/CreateUser.vue";
import EditUser from "@/Pages/AdminDashboardPages/Plans/EditUser.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Plans/ViewUser.vue";
import Pagination from "@/Pages/AdminDashboardPages/Plans/Pagination.vue";
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
    plans: Object,
    filters: Object,
});
const search = ref(props.filters.search || ''); // Initialize search with the filter value
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const editForm = ref({});
const viewForm = ref({});
const deletePlanId = ref(null);

// Handle search input
const handleSearch = () => {
    router.get('/admin/plans', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const openEditDialog = (plan) => {
    editForm.value = { ...plan };
    isEditDialogOpen.value = true;
};

const openViewDialog = (plan) => {
    viewForm.value = { ...plan };
    isViewDialogOpen.value = true;
};

const openDeleteDialog = (id) => {
    deletePlanId.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    router.delete(`/admin/plans/${deletePlanId.value}`, {
        onSuccess: () => {
            console.log('Plan deleted successfully');
            isDeleteDialogOpen.value = false;
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

const handlePageChange = (page) => {
    router.get(`/plans?page=${page}`);
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