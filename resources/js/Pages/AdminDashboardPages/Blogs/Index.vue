<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Blogs</h1>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                        Total: {{ blogs?.total || 0 }}
                    </span>
                    <Link :href="route('admin.blogs.create')">
                        <Button>
                            <Plus class="w-4 h-4 mr-1" />
                            New Blog
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by title or slug"
                        class="pl-10 pr-4 h-12 text-base"
                    />
                </div>
            </div>

            <div v-if="blogs?.data?.length" class="flex flex-wrap items-center gap-3 rounded-xl border bg-white px-4 py-3">
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

            <div v-if="blogs?.data?.length" class="rounded-xl border bg-white">
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
                                <TableHead class="whitespace-nowrap px-4 py-3">Title</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Slug</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Created</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(blog, index) in blogs.data" :key="blog.id">
                                <TableCell class="px-4 py-3">
                                    <Checkbox
                                        :checked="selectedIds.includes(blog.id)"
                                        @update:checked="(checked) => toggleRow(blog.id, checked)"
                                        :aria-label="`Select ${blog.title}`"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    {{ (blogs.from || 1) + index }}
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-md">
                                    <div class="text-sm font-medium text-slate-900 truncate" :title="blog.title">{{ blog.title }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-xs">
                                    <div class="text-sm text-muted-foreground truncate" :title="blog.slug">{{ blog.slug }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <button
                                        type="button"
                                        @click="togglePublishStatus(blog)"
                                        class="focus:outline-none"
                                        :title="blog.is_published ? 'Click to unpublish' : 'Click to publish'"
                                    >
                                        <Badge :variant="blog.is_published ? 'default' : 'secondary'" class="capitalize cursor-pointer">
                                            <component :is="blog.is_published ? Eye : EyeOff" class="w-3 h-3 mr-1" />
                                            {{ blog.is_published ? 'Published' : 'Draft' }}
                                        </Badge>
                                    </button>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(blog.created_at) }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('admin.blogs.edit', blog.id)">
                                            <Button size="sm" variant="outline" class="flex items-center gap-1">
                                                <Edit class="w-3 h-3" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(blog)" class="flex items-center gap-1">
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
                    <Pagination
                        :current-page="blogs.current_page"
                        :total-pages="blogs.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>

            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <FileText class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No blogs found</h3>
                        <p class="text-muted-foreground">No posts match your filters yet.</p>
                    </div>
                    <Link :href="route('admin.blogs.create')">
                        <Button>
                            <Plus class="w-4 h-4 mr-1" />
                            Create your first blog
                        </Button>
                    </Link>
                </div>
            </div>

            <AdminConfirmDialog
                v-model:open="isDeleteDialogOpen"
                title="Delete blog?"
                :description="deleteTarget ? `This will permanently delete ${deleteTarget.title}. This action cannot be undone.` : ''"
                confirm-label="Delete blog"
                :processing="isDeleting"
                @confirm="confirmDelete"
            />

            <AdminConfirmDialog
                v-model:open="isBulkDeleteDialogOpen"
                title="Delete selected blogs?"
                :description="`This will permanently delete ${selectedIds.length} blog${selectedIds.length > 1 ? 's' : ''}. Existing bulk delete behavior is unchanged.`"
                :confirm-label="`Delete ${selectedIds.length} Blog${selectedIds.length > 1 ? 's' : ''}`"
                :processing="isBulkDeleting"
                @confirm="confirmBulkDelete"
            />
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Search, Plus, Edit, Trash2, FileText, Eye, EyeOff } from 'lucide-vue-next';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import AdminConfirmDialog from '@/Pages/AdminDashboardPages/Shared/AdminConfirmDialog.vue';

const props = defineProps({
    blogs: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.blogs.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

const handlePageChange = (p) => {
    router.get(route('admin.blogs.index'), { page: p, search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const isDeleteDialogOpen = ref(false);
const deleteTarget = ref(null);
const isDeleting = ref(false);

const openDeleteDialog = (blog) => {
    deleteTarget.value = blog;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(route('admin.blogs.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${deleteTarget.value.title}`);
            isDeleteDialogOpen.value = false;
            deleteTarget.value = null;
        },
        onError: () => toast.error('Failed to delete blog'),
        onFinish: () => { isDeleting.value = false; },
    });
};

const togglePublishStatus = (blogItem) => {
    router.patch(route('admin.blogs.togglePublish', blogItem.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success(blogItem.is_published ? 'Blog unpublished' : 'Blog published'),
        onError: () => toast.error('Failed to update publish status'),
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

// Bulk selection
const selectedIds = ref([]);
const bulkAction = ref('');
const isBulkDeleteDialogOpen = ref(false);
const isBulkDeleting = ref(false);

const visibleIds = computed(() => (props.blogs?.data || []).map((b) => b.id));
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
        toast.error('Select at least one blog first');
        return;
    }
    if (bulkAction.value === 'delete') isBulkDeleteDialogOpen.value = true;
};

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;
    const count = selectedIds.value.length;
    isBulkDeleting.value = true;
    router.delete(route('admin.blogs.bulk-destroy'), {
        data: { ids: [...selectedIds.value] },
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted ${count} blog${count > 1 ? 's' : ''}`);
            selectedIds.value = [];
            bulkAction.value = '';
            isBulkDeleteDialogOpen.value = false;
        },
        onError: () => toast.error('Failed to delete selected blogs'),
        onFinish: () => { isBulkDeleting.value = false; },
    });
};

watch(() => props.blogs?.current_page, () => { selectedIds.value = []; });
</script>
