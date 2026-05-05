<template>
    <AdminDashboardLayout title="SEO Meta Management">
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">SEO Meta</h1>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                        Total: {{ seoMetas?.total || 0 }}
                    </span>
                    <Link :href="route('admin.seo-meta.create')">
                        <Button>
                            <Plus class="w-4 h-4 mr-1" />
                            New SEO Meta
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by route, title, description"
                        class="pl-10 pr-4 h-12 text-base"
                    />
                </div>
            </div>

            <div v-if="seoMetas?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap px-4 py-3 w-[72px]">Sr No.</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Route</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">SEO Title</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Last Updated</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(meta, index) in seoMetas.data" :key="meta.id">
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    {{ (seoMetas.from || 1) + index }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium text-slate-900">{{ meta.route_name || '—' }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-md">
                                    <div class="text-sm truncate" :title="meta.seo_title">{{ meta.seo_title || '—' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(meta.updated_at) }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('admin.seo-meta.edit', meta.id)">
                                            <Button size="sm" variant="outline" class="flex items-center gap-1">
                                                <Edit class="w-3 h-3" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(meta)" class="flex items-center gap-1">
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
                        :current-page="seoMetas.current_page"
                        :total-pages="seoMetas.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>

            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Globe class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No SEO meta tags found</h3>
                        <p class="text-muted-foreground">No entries match your filters yet.</p>
                    </div>
                </div>
            </div>

            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete SEO meta?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <template v-if="deleteTarget">
                                This will permanently delete the SEO entry for
                                <span class="font-medium text-foreground">{{ deleteTarget.route_name }}</span>.
                                This action cannot be undone.
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
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Search, Plus, Edit, Trash2, Globe } from 'lucide-vue-next';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    seoMetas: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.seo-meta.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

const handlePageChange = (p) => {
    router.get(route('admin.seo-meta.index'), { page: p, search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const isDeleteDialogOpen = ref(false);
const deleteTarget = ref(null);
const isDeleting = ref(false);

const openDeleteDialog = (meta) => {
    deleteTarget.value = meta;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(route('admin.seo-meta.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Deleted SEO meta for ${deleteTarget.value.route_name}`);
            isDeleteDialogOpen.value = false;
            deleteTarget.value = null;
        },
        onError: () => toast.error('Failed to delete SEO meta'),
        onFinish: () => { isDeleting.value = false; },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

</script>
