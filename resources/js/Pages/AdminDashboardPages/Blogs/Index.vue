<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Blog Management</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search blog..." class="w-[300px] search-box" @input="handleSearch" />
                </div>
                <Link 
                    :href="route('admin.blogs.create')" 
                    class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90"
                >
                    Create New Blog
                </Link>
            </div>

            <!-- Blogs Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Published</TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(blog, index) in blogs.data" :key="blog.id">
                            <TableCell>{{ (blogs.current_page - 1) * blogs.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ blog.title }}</TableCell>
                            <TableCell>{{ blog.slug }}</TableCell>
                            <TableCell>
                                <Button
                                    @click="togglePublishStatus(blog)"
                                    :class="[
                                        'px-3 py-1 text-xs rounded-full transition-colors duration-150 ease-in-out',
                                        blog.is_published ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-red-500 hover:bg-red-600 text-white'
                                    ]"
                                >
                                    {{ blog.is_published ? 'Published' : 'Unpublished' }}
                                </Button>
                            </TableCell>
                            <TableCell>{{ new Date(blog.created_at).toLocaleDateString() }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link 
                                        :href="route('admin.blogs.edit', blog.id)"
                                        class="px-3 py-2 bg-[#0f172a] text-white rounded hover:bg-[#0f172ae6]"
                                    >
                                        Edit
                                    </Link>
                                    <Button 
                                        variant="destructive"
                                        @click="confirmDeleteBlog(blog.id)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                         <TableRow v-if="blogs.data.length === 0">
                            <TableCell colspan="6" class="text-center">No blogs found.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                <!-- Pagination -->
                <div v-if="blogs.data.length > 0" class="mt-4 flex justify-end">
                    <Pagination
                        :currentPage="blogs.current_page"
                        :totalPages="blogs.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3'; // Added Link
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Input } from '@/Components/ui/input'; // Added Input
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    blogs: Object, // Changed from Array to Object for paginated data
    filters: Object,
});

const search = ref(props.filters?.search || '');

const confirmDeleteBlog = (id) => {
  if (confirm('Are you sure you want to delete this blog?')) {
    deleteBlog(id);
  }
};

const deleteBlog = (id) => {
    router.delete(route('admin.blogs.destroy', id), { // Assuming route name is admin.blogs.destroy
        onSuccess: () => {
            toast.success('Blog deleted successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error, { // error is already a string here
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        }
    });
};

// Handle search input
const handleSearch = () => {
    router.get(route('admin.blogs.index'), { search: search.value }, { // Assuming route name is admin.blogs.index
        preserveState: true,
        replace: true,
    });
};

const togglePublishStatus = (blogItem) => {
  router.patch(route('admin.blogs.togglePublish', blogItem.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Blog publish status updated!');
      // The redirect()->back() from controller should refresh props.
    },
    onError: (errors) => {
      toast.error('Failed to update blog status.');
      console.error('Error updating blog status:', errors);
    }
  });
};

const handlePageChange = (page) => { // Changed to accept page number
    router.get(route('admin.blogs.index', { page: page }), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<style scoped>
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
