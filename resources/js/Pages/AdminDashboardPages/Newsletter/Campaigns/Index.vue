<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Newsletter Campaigns</h1>
                <div class="flex gap-2">
                    <a :href="'/admin/newsletter-subscribers/export'" target="_blank">
                        <Button variant="outline" size="sm">
                            <Download class="w-4 h-4 mr-2" />
                            Export Subscribers
                        </Button>
                    </a>
                    <Button @click="router.visit('/admin/newsletter-campaigns/create')">
                        <Plus class="w-4 h-4 mr-2" />
                        New Campaign
                    </Button>
                </div>
            </div>

            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ flash.error }}
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Draft</div>
                    <div class="text-2xl font-semibold text-slate-900">{{ statusCounts?.draft || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Scheduled</div>
                    <div class="text-2xl font-semibold text-blue-600">{{ statusCounts?.scheduled || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Sending</div>
                    <div class="text-2xl font-semibold text-amber-600">{{ statusCounts?.sending || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Sent</div>
                    <div class="text-2xl font-semibold text-green-600">{{ statusCounts?.sent || 0 }}</div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Active Subscribers</div>
                    <div class="text-2xl font-semibold text-slate-900">{{ statusCounts?.activeSubscribers || 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search by subject"
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="status">
                        <SelectTrigger class="w-44 h-12">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="scheduled">Scheduled</SelectItem>
                            <SelectItem value="sending">Sending</SelectItem>
                            <SelectItem value="sent">Sent</SelectItem>
                            <SelectItem value="failed">Failed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Table -->
            <div v-if="campaigns?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap px-4 py-3">Subject</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Recipients</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Open Rate</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Click Rate</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Created</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="campaign in campaigns.data" :key="campaign.id">
                                <TableCell class="px-4 py-3">
                                    <div class="text-sm font-medium text-slate-900 max-w-xs truncate">{{ campaign.subject }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="statusBadgeVariant(campaign.status)" class="capitalize">
                                        {{ campaign.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ campaign.total_recipients }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ campaign.open_rate }}%</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ campaign.click_rate }}%</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ formatDate(campaign.created_at) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex gap-2">
                                        <Button variant="outline" size="sm" @click="router.visit(`/admin/newsletter-campaigns/${campaign.id}`)">
                                            <Eye class="w-4 h-4" />
                                        </Button>
                                        <Button v-if="campaign.status === 'draft' || campaign.status === 'scheduled'" variant="outline" size="sm" @click="router.visit(`/admin/newsletter-campaigns/${campaign.id}/edit`)">
                                            <Pencil class="w-4 h-4" />
                                        </Button>
                                        <Button v-if="campaign.status === 'draft'" variant="destructive" size="sm" @click="deleteCampaign(campaign)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="campaigns.current_page" :total-pages="campaigns.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Send class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No campaigns found</h3>
                        <p class="text-muted-foreground">Create your first newsletter campaign to get started.</p>
                    </div>
                    <Button @click="router.visit('/admin/newsletter-campaigns/create')">
                        <Plus class="w-4 h-4 mr-2" />
                        New Campaign
                    </Button>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Search, Plus, Eye, Pencil, Trash2, Send, Download } from 'lucide-vue-next';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    campaigns: Object,
    statusCounts: Object,
    filters: Object,
    flash: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const handlePageChange = (page) => {
    router.get('/admin/newsletter-campaigns', {
        page,
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true });
};

const applyFilters = () => {
    router.get('/admin/newsletter-campaigns', {
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true });
};

let filterTimeout;
watch([search, status], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};

const statusBadgeVariant = (statusValue) => {
    const map = {
        draft: 'secondary',
        scheduled: 'outline',
        sending: 'default',
        sent: 'default',
        failed: 'destructive',
        cancelled: 'destructive',
    };
    return map[statusValue] || 'secondary';
};

const deleteCampaign = (campaign) => {
    if (!window.confirm(`Delete campaign "${campaign.subject}"?`)) return;
    router.delete(`/admin/newsletter-campaigns/${campaign.id}`, {
        preserveScroll: true,
    });
};
</script>
