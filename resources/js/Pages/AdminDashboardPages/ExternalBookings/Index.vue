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
                                <TableHead>Vendor</TableHead>
                                <TableHead>Dates</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right w-24">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="booking in bookings.data" :key="booking.id">
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
                                <TableCell>
                                    <span class="text-sm text-gray-700">{{ booking.vehicle?.vendor?.name || 'N/A' }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <span class="text-sm text-gray-700">{{ formatDateCompact(booking.pickup_date) }}</span>
                                    <span class="text-gray-300 mx-1">&rarr;</span>
                                    <span class="text-sm text-gray-700">{{ formatDateCompact(booking.return_date) }}</span>
                                    <span class="block text-[11px] text-gray-400 mt-0.5">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</span>
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
                                    <Link :href="route('admin.external-bookings.show', booking.id)">
                                        <Button size="sm" variant="outline"><Eye class="w-4 h-4" /></Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="bookings.data.length === 0">
                                <TableCell colspan="9" class="text-center py-8 text-gray-500">No external bookings found.</TableCell>
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
import { Eye } from 'lucide-vue-next';

const props = defineProps({
    bookings: Object,
    consumers: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const consumerFilter = ref(props.filters?.api_consumer_id ? String(props.filters.api_consumer_id) : 'all');

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

const getCurrencySymbol = (currency) => {
    const symbols = { 'USD': '$', 'EUR': '\u20ac', 'GBP': '\u00a3', 'CHF': 'Fr', 'AED': 'AED', 'MAD': 'MAD' };
    return symbols[currency] || currency || '$';
};

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
