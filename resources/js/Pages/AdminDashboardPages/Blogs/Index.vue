<template>
    <AdminDashboardLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Blog Management</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage all blog posts and content</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <Input v-model="search" placeholder="Search blogs..." class="w-80" @input="handleSearch" />
                        <Link :href="route('admin.blogs.create')">
                            <Button>Create New Blog</Button>
                        </Link>
                    </div>
                </div>

            <!-- Blogs Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12">ID</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead class="w-32">Status</TableHead>
                            <TableHead class="w-32">Created</TableHead>
                            <TableHead class="text-right w-32">Actions</TableHead>
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
                                    size="sm"
                                    :variant="blog.is_published ? 'default' : 'destructive'"
                                >
                                    {{ blog.is_published ? 'Published' : 'Unpublished' }}
                                </Button>
                            </TableCell>
                            <TableCell>{{ formatDate(blog.created_at) }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('admin.blogs.edit', blog.id)">
                                        <Button size="sm" variant="outline">Edit</Button>
                                    </Link>
                                    <Button
                                        size="sm"
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

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};
</script>
