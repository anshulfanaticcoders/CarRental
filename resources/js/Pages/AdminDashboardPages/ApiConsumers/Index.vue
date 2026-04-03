<template>
    <AdminDashboardLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">API Consumers</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage external companies using the Vrooem Provider API</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <Input v-model="search" placeholder="Search by name or email..." class="w-80" @input="handleSearch" />
                        <Link :href="route('admin.api-consumers.create')">
                            <Button><Plus class="w-4 h-4 mr-2" />Add Consumer</Button>
                        </Link>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead class="w-28">Plan</TableHead>
                                <TableHead class="w-28">Status</TableHead>
                                <TableHead class="w-28 text-center">Active Keys</TableHead>
                                <TableHead class="w-28 text-right">Requests</TableHead>
                                <TableHead class="w-36">Last Active</TableHead>
                                <TableHead class="text-right w-36">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="consumer in consumers.data" :key="consumer.id">
                                <TableCell class="font-medium">{{ consumer.name }}</TableCell>
                                <TableCell>
                                    <div class="text-sm">{{ consumer.contact_name }}</div>
                                    <div class="text-xs text-gray-500">{{ consumer.contact_email }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="planBadgeClass(consumer.plan)">{{ consumer.plan }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="statusBadgeClass(consumer.status)">{{ consumer.status }}</Badge>
                                </TableCell>
                                <TableCell class="text-center">{{ consumer.active_keys_count }}</TableCell>
                                <TableCell class="text-right">{{ consumer.total_requests.toLocaleString() }}</TableCell>
                                <TableCell>{{ formatRelative(consumer.api_keys_max_last_used_at) }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.api-consumers.show', consumer.id)">
                                            <Button size="sm" variant="outline"><Eye class="w-4 h-4" /></Button>
                                        </Link>
                                        <Link :href="route('admin.api-consumers.edit', consumer.id)">
                                            <Button size="sm" variant="outline"><Pencil class="w-4 h-4" /></Button>
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="consumers.data.length === 0">
                                <TableCell colspan="8" class="text-center py-8 text-gray-500">No API consumers found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="consumers.data.length > 0" class="p-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ consumers.total }} consumer{{ consumers.total !== 1 ? 's' : '' }} total</span>
                        <Pagination
                            :currentPage="consumers.current_page"
                            :totalPages="consumers.last_page"
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
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Plus, Eye, Pencil } from 'lucide-vue-next';

const props = defineProps({
    consumers: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get(route('admin.api-consumers.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const handlePageChange = (page) => {
    router.get(route('admin.api-consumers.index', { page }), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const planBadgeClass = (plan) => ({
    'bg-gray-100 text-gray-700 border-gray-200': plan === 'basic',
    'bg-blue-100 text-blue-700 border-blue-200': plan === 'premium',
    'bg-purple-100 text-purple-700 border-purple-200': plan === 'enterprise',
});

const statusBadgeClass = (status) => ({
    'bg-green-100 text-green-700 border-green-200': status === 'active',
    'bg-red-100 text-red-700 border-red-200': status === 'suspended',
    'bg-gray-100 text-gray-500 border-gray-200': status === 'inactive',
});

const formatRelative = (dateStr) => {
    if (!dateStr) return 'Never';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHrs = Math.floor(diffMin / 60);
    if (diffHrs < 24) return `${diffHrs}h ago`;
    const diffDays = Math.floor(diffHrs / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};
</script>
