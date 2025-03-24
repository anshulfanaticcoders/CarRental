<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Footer Categories Settings</span>
            </div>
            
            <!-- Categories Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <h2 class="text-lg font-medium mb-4">Select Categories to Show in Footer</h2>
                
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[80px]">Select</TableHead>
                            <TableHead>Category Name</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="category in categories" :key="category.id">
                            <TableCell>
                                <input
                                  type="checkbox"
                                  v-model="selectedCategories"
                                  :value="category.id"
                                  class="form-checkbox h-5 w-5 text-blue-600"
                                />
                            </TableCell>
                            <TableCell>{{ category.name }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                <!-- Update Button -->
                <div class="mt-4 flex justify-end">
                    <Button @click="updateFooterSettings" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90">
                        Update Footer
                    </Button>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>
  
<script setup>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { usePage } from '@inertiajs/inertia-vue3';

const props = defineProps({
    categories: Array,
    selectedCategories: Array,
});

// Use ref to make it reactive and initialize with the props value
const selectedCategories = ref(props.selectedCategories || []);
const page = usePage();

const updateFooterSettings = () => {
    Inertia.post(route('admin.settings.footer-categories.update'), {
        selected_categories: selectedCategories.value,
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

.form-checkbox {
    border-radius: 0.25rem;
    cursor: pointer;
}
</style>