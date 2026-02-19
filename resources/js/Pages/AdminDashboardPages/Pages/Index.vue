<!-- resources/js/Pages/Admin/Pages/Index.vue -->
<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Pages Management</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search page..." class="w-[300px] search-box" @input="handleSearch" />
                </div>
                <Link 
                    :href="route('admin.pages.create')" 
                    class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90"
                >
                    Create New Page
                </Link>
            </div>

            <!-- Pages Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Template</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(page, index) in pages.data" :key="page.id">
                            <TableCell>{{ (pages.current_page - 1) * pages.per_page + index + 1 }}</TableCell>
                            <TableCell class="capitalize">{{ getTitle(page) }}</TableCell>
                            <TableCell>{{ page.slug }}</TableCell>
                            <TableCell>
                                <span :class="templateBadgeClass(page.template)">
                                    {{ page.template || 'default' }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span :class="statusBadgeClass(page.status)">
                                    {{ page.status === 'published' ? 'Published' : 'Draft' }}
                                </span>
                            </TableCell>
                            <TableCell>{{ formatDate(page.created_at) }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link
                                        :href="route('admin.pages.edit', page.id)"
                                        class="px-3 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]"
                                    >
                                        Edit
                                    </Link>
                                    <Button
                                        variant="destructive"
                                        @click="deletePage(page.id)"
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
                        :current-page="pages.current_page" 
                        :total-pages="pages.last_page" 
                        @page-change="handlePageChange" 
                    />
                </div>
            </div>
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

const props = defineProps({
    pages: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const deletePage = (id) => {
    if (confirm('Are you sure you want to delete this page?')) {
        router.delete(route('admin.pages.destroy', id));
    }
};

// Handle search input
const handleSearch = () => {
    router.get(route('admin.pages.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const handlePageChange = (page) => {
    router.get(route('admin.pages.index', { page }), {
        preserveState: true,
        replace: true,
    });
};

const getTitle = (page) => {
    const locale = props.filters.locale || 'en';
    const translation = page.translations.find(t => t.locale === locale);
    return translation ? translation.title : page.slug;
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

const templateBadgeClass = (template) => {
    const base = 'inline-block px-2 py-0.5 text-xs font-medium rounded-full text-white';
    switch (template) {
        case 'contact-us': return `${base} bg-[#1e3a5f]`;
        case 'about-us':   return `${base} bg-teal-600`;
        case 'legal':      return `${base} bg-purple-600`;
        default:           return `${base} bg-gray-500`;
    }
};

const statusBadgeClass = (status) => {
    const base = 'inline-block px-2 py-0.5 text-xs font-medium rounded-full';
    if (status === 'published') {
        return `${base} bg-green-100 text-green-800`;
    }
    return `${base} bg-gray-100 text-gray-800`;
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
