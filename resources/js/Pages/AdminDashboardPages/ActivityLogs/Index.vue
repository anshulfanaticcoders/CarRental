<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Activity Logs</span>
                <div class="flex items-center gap-4">
                    <Input
                        v-model="search"
                        placeholder="Search logs..."
                        class="w-[300px]"
                        @input="handleSearch"
                    />
                </div>
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Activity Type</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>IP Address</TableHead>
                            <TableHead>Date & Time</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(log, index) in logs.data" :key="log.id">
                            <TableCell>{{ (logs.current_page - 1) * logs.per_page + index + 1 }}</TableCell>
                            <TableCell>
                                {{ log.user ? `${log.user.first_name} ${log.user.last_name}` : 'System' }}
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getActivityTypeBadgeVariant(log.activity_type)">
                                    {{ log.activity_type }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ log.activity_description }}</TableCell>
                            <TableCell>{{ log.ip_address || 'N/A' }}</TableCell>
                            <TableCell>{{ formatDate(log.created_at) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <!-- Pagination -->
                <div class="mt-4 flex justify-end">
                    <Pagination 
                        :current-page="logs.current_page" 
                        :total-pages="logs.last_page"
                        @page-change="handlePageChange" 
                    />
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Input } from "@/Components/ui/input";
import Pagination from "@/Pages/AdminDashboardPages/Users/Pagination.vue";

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get('/activity-logs', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const handlePageChange = (page) => {
    router.get(`/activity-logs?page=${page}`);
};

const getActivityTypeBadgeVariant = (type) => {
    switch (type.toLowerCase()) {
        case 'create': return 'default';
        case 'update': return 'secondary';
        case 'delete': return 'destructive';
        default: return 'outline';
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};
</script>