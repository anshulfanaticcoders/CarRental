<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Users, UserCheck, Clock, ShieldCheck, Search, Eye } from 'lucide-vue-next';

const viewPartner = (id) => {
    router.visit(route('admin.affiliate.partners.show', { id }));
};

const props = defineProps({
    partners: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const verification = ref(props.filters.verification || 'all');

const buildParams = () => ({
    search: search.value || undefined,
    status: status.value === 'all' ? undefined : status.value,
    verification: verification.value === 'all' ? undefined : verification.value,
});

let debounceTimer = null;
watch([search], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.affiliate.partners'), buildParams(), { preserveState: true, replace: true });
    }, 300);
});

watch([status, verification], () => {
    router.get(route('admin.affiliate.partners'), buildParams(), { preserveState: true, replace: true });
});

const statCards = [
    { label: 'Total Partners', value: props.stats.total, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Active', value: props.stats.active, icon: UserCheck, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { label: 'Pending', value: props.stats.pending, icon: Clock, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Verified', value: props.stats.verified, icon: ShieldCheck, color: 'text-indigo-600', bg: 'bg-indigo-50' },
];

const statusColor = (val) => {
    const map = { active: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700', suspended: 'bg-red-100 text-red-700', inactive: 'bg-gray-100 text-gray-600' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

const verificationColor = (val) => {
    const map = { verified: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700', rejected: 'bg-red-100 text-red-700' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

const verifyPartner = (id) => {
    router.post(route('admin.affiliate.businesses.verify', { businessId: id }), {}, { preserveScroll: true });
};

const rejectPartner = (id) => {
    router.post(route('admin.affiliate.businesses.reject', { businessId: id }), {}, { preserveScroll: true });
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Affiliate Partners</h1>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card v-for="card in statCards" :key="card.label">
                    <CardContent class="flex items-center gap-3 p-4">
                        <div :class="[card.bg, 'rounded-lg p-2.5']">
                            <component :is="card.icon" :class="[card.color, 'w-5 h-5']" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ card.label }}</p>
                            <p class="text-xl font-bold">{{ card.value }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input v-model="search" placeholder="Search by name or email..." class="pl-9" />
                </div>
                <Select v-model="status">
                    <SelectTrigger class="w-[160px]">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="suspended">Suspended</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="verification">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="All Verification" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Verification</SelectItem>
                        <SelectItem value="verified">Verified</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">Name</TableHead>
                                <TableHead class="px-4">Type</TableHead>
                                <TableHead class="px-4">Location</TableHead>
                                <TableHead class="px-4 text-center">QR Codes</TableHead>
                                <TableHead class="px-4">Revenue</TableHead>
                                <TableHead class="px-4">Status</TableHead>
                                <TableHead class="px-4">Verification</TableHead>
                                <TableHead class="px-4 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in partners.data" :key="p.id">
                                <TableCell class="px-4 font-medium">
                                    <div>{{ p.name }}</div>
                                    <div class="text-xs text-gray-400">{{ p.contact_email }}</div>
                                </TableCell>
                                <TableCell class="px-4 capitalize text-sm">{{ p.business_type?.replace('_', ' ') || '-' }}</TableCell>
                                <TableCell class="px-4 text-sm">{{ [p.city, p.country].filter(Boolean).join(', ') || '-' }}</TableCell>
                                <TableCell class="px-4 text-center">{{ p.qr_codes_count }}</TableCell>
                                <TableCell class="px-4 font-semibold">&euro;{{ parseFloat(p.commissions_sum_commission_amount || 0).toFixed(2) }}</TableCell>
                                <TableCell class="px-4">
                                    <span :class="[statusColor(p.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ p.status }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4">
                                    <span :class="[verificationColor(p.verification_status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ p.verification_status }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Button variant="outline" size="sm" class="h-8 px-2.5" @click="viewPartner(p.id)">
                                            <Eye class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button
                                            v-if="p.verification_status === 'pending'"
                                            size="sm"
                                            class="h-8 px-2.5 bg-emerald-600 hover:bg-emerald-700 text-white"
                                            @click="verifyPartner(p.id)"
                                        >
                                            Verify
                                        </Button>
                                        <Button
                                            v-if="p.verification_status === 'pending'"
                                            variant="outline"
                                            size="sm"
                                            class="h-8 px-2.5 text-red-600 border-red-200 hover:bg-red-50"
                                            @click="rejectPartner(p.id)"
                                        >
                                            Reject
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!partners.data.length">
                                <TableCell colspan="8" class="px-4 text-center text-gray-400 py-8">No partners found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="partners.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Showing {{ partners.from }} to {{ partners.to }} of {{ partners.total }}
                </p>
                <div class="flex gap-1">
                    <template v-for="link in partners.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 rounded-md text-sm transition-colors',
                                link.active ? 'bg-indigo-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50'
                            ]"
                            v-html="link.label"
                            preserve-state
                        />
                        <span v-else class="px-3 py-1.5 rounded-md text-sm text-gray-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>
