<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Activity Logs</h1>
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                    Total: {{ categoryCounts?.all || 0 }}
                </div>
            </div>

            <div class="rounded-xl border bg-white p-2 overflow-x-auto">
                <div class="flex items-center gap-1 min-w-max">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="changeCategory(tab.value)"
                        :class="[
                            'group relative inline-flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-medium transition-all duration-200 whitespace-nowrap',
                            activeCategory === tab.value
                                ? 'bg-[#153b4f] text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <component
                            :is="tab.icon"
                            :class="[
                                'w-4 h-4 transition-colors',
                                activeCategory === tab.value ? 'text-[#22d3ee]' : 'text-slate-400 group-hover:text-slate-600'
                            ]"
                        />
                        <span>{{ tab.label }}</span>
                        <span
                            :class="[
                                'inline-flex items-center justify-center rounded-md text-[11px] font-semibold leading-none px-1.5 py-1 min-w-[22px] tabular-nums transition-colors',
                                activeCategory === tab.value
                                    ? 'bg-[#22d3ee] text-[#0e2a3a]'
                                    : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200'
                            ]"
                        >
                            {{ categoryCounts?.[tab.value] || 0 }}
                        </span>
                    </button>
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
                <Select v-model="typeFilter">
                    <SelectTrigger class="w-56 h-12">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All types</SelectItem>
                        <SelectItem v-for="t in availableTypes" :key="t" :value="t">{{ t }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="logs?.data?.length" class="rounded-xl border bg-white">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap px-4 py-3 w-[72px]">Sr No.</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">User</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Category</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Type</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Description</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">IP</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Date & Time</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 text-right">Details</TableHead>
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
                                    <div v-if="log.user" class="text-xs text-muted-foreground">{{ log.user.email }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="outline" class="capitalize">{{ log.category || '—' }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="badgeVariantForType(log.activity_type)" class="capitalize">
                                        {{ log.activity_type }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3 max-w-md">
                                    <div class="text-sm truncate" :title="log.activity_description">{{ log.activity_description }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ log.ip_address || 'N/A' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(log.created_at) }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <Button
                                        v-if="hasProperties(log)"
                                        size="sm"
                                        variant="outline"
                                        @click="openDetails(log)"
                                        class="flex items-center gap-1 ml-auto"
                                    >
                                        <Eye class="w-3 h-3" />
                                        View
                                    </Button>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </TableCell>
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

            <Sheet v-model:open="isDetailsOpen">
                <SheetContent class="sm:max-w-lg overflow-y-auto">
                    <SheetHeader>
                        <SheetTitle>Activity details</SheetTitle>
                        <SheetDescription>
                            <template v-if="detailsLog">
                                {{ detailsLog.category }} · {{ detailsLog.activity_type }} · {{ formatDate(detailsLog.created_at) }}
                            </template>
                        </SheetDescription>
                    </SheetHeader>
                    <div v-if="detailsLog" class="mt-6 space-y-4 text-sm">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-1">Description</div>
                            <div class="text-slate-900">{{ detailsLog.activity_description }}</div>
                        </div>
                        <div v-if="detailsLog.user">
                            <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-1">By</div>
                            <div>{{ detailsLog.user.first_name }} {{ detailsLog.user.last_name }} <span class="text-muted-foreground">({{ detailsLog.user.email }})</span></div>
                        </div>
                        <div v-if="detailsLog.ip_address">
                            <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-1">IP / User-Agent</div>
                            <div>{{ detailsLog.ip_address }}</div>
                            <div class="text-xs text-muted-foreground break-all">{{ detailsLog.user_agent || '—' }}</div>
                        </div>
                        <div v-if="detailsLog.logable_type">
                            <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-1">Related</div>
                            <div class="text-slate-900">{{ shortClass(detailsLog.logable_type) }} #{{ detailsLog.logable_id }}</div>
                        </div>
                        <div v-if="detailsLog.properties?.changed">
                            <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Changes</div>
                            <div class="rounded-lg border bg-slate-50 divide-y">
                                <div v-for="(diff, field) in detailsLog.properties.changed" :key="field" class="px-3 py-2">
                                    <div class="text-xs font-medium text-slate-700">{{ field }}</div>
                                    <div class="grid grid-cols-2 gap-2 mt-1 text-xs">
                                        <div>
                                            <span class="text-muted-foreground">Before:</span>
                                            <div class="text-rose-700 break-all">{{ formatValue(diff.from) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground">After:</span>
                                            <div class="text-emerald-700 break-all">{{ formatValue(diff.to) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="detailsLog.properties && !detailsLog.properties.changed">
                            <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-1">Properties</div>
                            <pre class="rounded-lg border bg-slate-50 p-3 text-xs overflow-x-auto">{{ JSON.stringify(detailsLog.properties, null, 2) }}</pre>
                        </div>
                    </div>
                </SheetContent>
            </Sheet>
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
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import {
    Search, Activity, Eye, Layers, User, Store, Car, Calendar,
    CreditCard, Users as UsersIcon, ShieldCheck, FileText, Bug,
} from 'lucide-vue-next';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    categoryCounts: Object,
    availableTypes: Array,
});

const tabs = [
    { value: 'all', label: 'All', icon: Layers },
    { value: 'user', label: 'Users', icon: User },
    { value: 'vendor', label: 'Vendors', icon: Store },
    { value: 'vehicle', label: 'Vehicles', icon: Car },
    { value: 'booking', label: 'Bookings', icon: Calendar },
    { value: 'payment', label: 'Payments', icon: CreditCard },
    { value: 'affiliate', label: 'Affiliate', icon: UsersIcon },
    { value: 'auth', label: 'Auth', icon: ShieldCheck },
    { value: 'content', label: 'Content', icon: FileText },
    { value: 'system', label: 'System', icon: Bug },
];

const activeCategory = ref(props.filters?.category || 'all');
const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');

const isDetailsOpen = ref(false);
const detailsLog = ref(null);

const buildQuery = (overrides = {}) => {
    const q = {};
    if (activeCategory.value && activeCategory.value !== 'all') q.category = activeCategory.value;
    if (typeFilter.value && typeFilter.value !== 'all') q.type = typeFilter.value;
    if (search.value) q.search = search.value;
    return { ...q, ...overrides };
};

const navigate = (extra = {}) => {
    router.get('/activity-logs', buildQuery(extra), {
        preserveState: true,
        replace: true,
    });
};

const changeCategory = (value) => {
    activeCategory.value = value;
    typeFilter.value = 'all';
    navigate();
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => navigate(), 400);
});

watch(typeFilter, () => navigate());

const handlePageChange = (page) => navigate({ page });

const badgeVariantForType = (type) => {
    const t = (type || '').toLowerCase();
    if (['created', 'create', 'register', 'login', 'paid', 'approved', 'confirmed', 'restored'].includes(t)) return 'default';
    if (['updated', 'update', 'logout'].includes(t)) return 'secondary';
    if (['deleted', 'delete', 'cancelled', 'rejected', 'refunded', 'login_failed', 'failed', 'error', 'bulk_deleted'].includes(t)) return 'destructive';
    return 'outline';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};

const hasProperties = (log) => log?.properties && Object.keys(log.properties).length > 0;

const openDetails = (log) => {
    detailsLog.value = log;
    isDetailsOpen.value = true;
};

const shortClass = (fqcn) => {
    if (!fqcn) return '';
    const parts = fqcn.split('\\');
    return parts[parts.length - 1];
};

const formatValue = (v) => {
    if (v === null || v === undefined || v === '') return 'null';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
};
</script>
