<template>
    <AdminDashboardLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">External Bookings</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage all bookings received through the Provider API</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-[160px]">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="confirmed">Confirmed</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="consumerFilter">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="All Consumers" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Consumers</SelectItem>
                                <SelectItem v-for="c in consumers" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Input v-model="search" placeholder="Search bookings..." class="w-72" @input="handleSearch" />
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Booking #</TableHead>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Driver</TableHead>
                                <TableHead>Consumer</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right w-44">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="booking in bookings.data" :key="booking.id">
                            <TableRow>
                                <TableCell>
                                    <span class="font-mono text-xs font-semibold text-indigo-700">{{ booking.booking_number }}</span>
                                    <span class="block text-[11px] text-gray-400 mt-0.5">{{ formatDate(booking.created_at) }}</span>
                                </TableCell>
                                <TableCell class="max-w-[180px]">
                                    <span class="font-medium text-gray-800 truncate block">{{ booking.vehicle_name || (booking.vehicle?.brand + ' ' + booking.vehicle?.model) || 'N/A' }}</span>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm font-medium text-gray-800">{{ booking.driver_first_name }} {{ booking.driver_last_name }}</div>
                                    <div class="text-xs text-gray-500">{{ booking.driver_email }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge class="bg-blue-50 text-blue-700 border-blue-200">{{ booking.consumer?.name || 'N/A' }}</Badge>
                                </TableCell>
                                <TableCell class="text-right whitespace-nowrap">
                                    <span class="font-semibold text-gray-900">{{ getCurrencySymbol(booking.currency) }}{{ formatNumber(booking.total_amount) }}</span>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="statusBadgeClass(booking.status)">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 inline-block" :class="statusDotClass(booking.status)"></span>
                                        {{ booking.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="toggleBookingDetails(booking.id)" class="inline-flex items-center gap-1">
                                            <ChevronUp v-if="isBookingDetailsOpen(booking.id)" class="w-3 h-3" />
                                            <ChevronDown v-else class="w-3 h-3" />
                                            Details
                                        </Button>
                                        <Link :href="route('admin.external-bookings.show', booking.id)">
                                            <Button size="sm" variant="outline"><Eye class="w-4 h-4" /></Button>
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="isBookingDetailsOpen(booking.id)" class="bg-muted/20">
                                <TableCell colspan="7" class="px-4 py-4">
                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div class="rounded-lg border bg-background/40 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Trip</p>
                                            <dl class="mt-3 space-y-2 text-sm">
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">Pickup</dt>
                                                    <dd class="font-medium">{{ formatDateCompact(booking.pickup_date) }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">Return</dt>
                                                    <dd class="font-medium">{{ formatDateCompact(booking.return_date) }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">Duration</dt>
                                                    <dd class="font-medium">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                        <div class="rounded-lg border bg-background/40 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Vendor</p>
                                            <dl class="mt-3 space-y-2 text-sm">
                                                <div>
                                                    <dt class="text-muted-foreground">Company</dt>
                                                    <dd class="font-medium">{{ booking.vehicle?.vendor?.vendor_profile?.company_name || booking.vehicle?.vendor?.first_name || 'N/A' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-muted-foreground">Vehicle</dt>
                                                    <dd class="font-medium">{{ booking.vehicle_name || (booking.vehicle?.brand + ' ' + booking.vehicle?.model) || 'N/A' }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                        <div class="rounded-lg border bg-background/40 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Record</p>
                                            <dl class="mt-3 space-y-2 text-sm">
                                                <div>
                                                    <dt class="text-muted-foreground">Consumer</dt>
                                                    <dd class="font-medium">{{ booking.consumer?.name || 'N/A' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-muted-foreground">Driver email</dt>
                                                    <dd class="break-all font-medium">{{ booking.driver_email || 'N/A' }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <dt class="text-muted-foreground">Created</dt>
                                                    <dd class="font-medium">{{ formatDate(booking.created_at) }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                            </template>
                            <TableRow v-if="bookings.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8 text-gray-500">No external bookings found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="bookings.data.length > 0" class="p-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ bookings.total }} booking{{ bookings.total !== 1 ? 's' : '' }} total</span>
                        <Pagination
                            :currentPage="bookings.current_page"
                            :totalPages="bookings.last_page"
                            @page-change="handlePageChange"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { ChevronDown, ChevronUp, Eye } from 'lucide-vue-next';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';

const props = defineProps({
    bookings: Object,
    consumers: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const consumerFilter = ref(props.filters?.api_consumer_id ? String(props.filters.api_consumer_id) : 'all');
const expandedBookingRows = ref([]);

const isBookingDetailsOpen = (id) => expandedBookingRows.value.includes(id);

const toggleBookingDetails = (id) => {
    if (isBookingDetailsOpen(id)) {
        expandedBookingRows.value = expandedBookingRows.value.filter((rowId) => rowId !== id);
        return;
    }

    expandedBookingRows.value = [id];
};

const applyFilters = (params = {}) => {
    router.get(route('admin.external-bookings.index'), {
        search: search.value || undefined,
        status: statusFilter.value === 'all' ? undefined : statusFilter.value,
        api_consumer_id: consumerFilter.value === 'all' ? undefined : consumerFilter.value,
        ...params,
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
};

watch(statusFilter, () => applyFilters());
watch(consumerFilter, () => applyFilters());

const handlePageChange = (page) => {
    applyFilters({ page });
};

const statusBadgeClass = (status) => ({
    'bg-amber-50 text-amber-700 border-amber-200': status === 'pending',
    'bg-blue-50 text-blue-700 border-blue-200': status === 'confirmed',
    'bg-green-50 text-green-700 border-green-200': status === 'completed',
    'bg-red-50 text-red-600 border-red-200': status === 'cancelled',
});

const statusDotClass = (status) => ({
    'bg-amber-500': status === 'pending',
    'bg-blue-500': status === 'confirmed',
    'bg-green-500': status === 'completed',
    'bg-red-500': status === 'cancelled',
}[status] || 'bg-gray-400');

const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

const formatNumber = (number) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateCompact = (dateStr) => {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return `${String(d.getDate()).padStart(2, '0')} ${d.toLocaleString('en-GB', { month: 'short' })}`;
};
</script>
