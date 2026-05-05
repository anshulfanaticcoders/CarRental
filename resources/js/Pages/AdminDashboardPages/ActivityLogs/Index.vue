<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Activity Logs</h1>
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                    Total: {{ logs?.total || 0 }}
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search logs..."
                        class="pl-10 pr-4 h-12 text-base"
                    />
                </div>
            </div>

            <div v-if="logs?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap px-4 py-3 w-[72px]">Sr No.</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">User</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Activity Type</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Description</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">IP Address</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Date & Time</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(log, index) in logs.data" :key="log.id">
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                                    {{ (logs.from || 1) + index }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ log.user ? `${log.user.first_name} ${log.user.last_name}` : 'System' }}
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getActivityTypeBadgeVariant(log.activity_type)" class="capitalize">
                                        {{ log.activity_type }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-md">
                                    <div class="text-sm truncate" :title="log.activity_description">{{ log.activity_description }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ log.ip_address || 'N/A' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(log.created_at) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination
                        :current-page="logs.current_page"
                        :total-pages="logs.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>

            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Activity class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No activity logs found</h3>
                        <p class="text-muted-foreground">No log entries match your filters.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Input } from "@/Components/ui/input";
import { Search, Activity } from 'lucide-vue-next';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/activity-logs', { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

const handlePageChange = (page) => {
    router.get('/activity-logs', { page, search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const getActivityTypeBadgeVariant = (type) => {
    switch ((type || '').toLowerCase()) {
        case 'create': return 'default';
        case 'update': return 'secondary';
        case 'delete': return 'destructive';
        default: return 'outline';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};
</script>
