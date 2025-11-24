<template>
  <MyProfileLayout>
    <div class="p-0 md:p-0 lg:p-6 space-y-6">
      <!-- Enhanced Header -->
      <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-100 rounded-xl p-6 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
            <CreditCard class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ _t('vendorprofilepages', 'payment_history_header') }}</h1>
            <p class="text-sm text-gray-600 mt-1">{{ _t('vendorprofilepages', 'payment_history_subheader') }}</p>
          </div>
        </div>
      </div>

      <!-- Search and Filter Section -->
      <div>
        <div class="rounded-xl border bg-card shadow-sm p-6">
          <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="relative flex-1 max-w-md">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
              <Input
                v-model="searchQuery"
                type="text"
                :placeholder="_t('vendorprofilepages', 'search_payments_placeholder')"
                class="pl-10 w-full"
              />
            </div>
            <div class="flex items-center gap-2">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="outline" class="flex items-center gap-2">
                    <Filter class="w-4 h-4" />
                    Status
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-48">
                  <DropdownMenuItem @click="statusFilter = 'all'">
                    <span :class="statusFilter === 'all' ? 'text-blue-600 font-medium' : ''">All Statuses</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="statusFilter = 'succeeded'">
                    <span :class="statusFilter === 'succeeded' ? 'text-green-600 font-medium' : ''">Succeeded</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="statusFilter = 'pending'">
                    <span :class="statusFilter === 'pending' ? 'text-yellow-600 font-medium' : ''">Pending</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="statusFilter = 'failed'">
                    <span :class="statusFilter === 'failed' ? 'text-red-600 font-medium' : ''">Failed</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="outline" class="flex items-center gap-2">
                    <DollarSign class="w-4 h-4" />
                    {{ selectedCurrency === 'all' ? 'All Currencies' : selectedCurrency }}
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-48">
                  <DropdownMenuItem @click="selectedCurrency = 'all'">
                    <span :class="selectedCurrency === 'all' ? 'text-blue-600 font-medium' : ''">All Currencies</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem v-for="currency in availableCurrencies" :key="currency" @click="selectedCurrency = currency">
                    <span :class="selectedCurrency === currency ? 'text-blue-600 font-medium' : ''">
                      {{ getCurrencySymbol(currency) }} {{ currency }}
                    </span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Statistics Cards -->
      <div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Total Revenue Card -->
          <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-green-100 text-sm font-medium">Total Revenue</p>
                <p class="text-2xl font-bold mt-1">
                  <span v-if="selectedCurrency === 'all'">
                    {{ getCurrencySymbol('USD') }}{{ formatNumber(totalRevenueAllCurrencies) }}
                  </span>
                  <span v-else>
                    {{ getCurrencySymbol(selectedCurrency) }}{{ formatNumber(totalRevenueByCurrency) }}
                  </span>
                </p>
                <p class="text-xs text-green-100 mt-1">
                  {{ selectedCurrency === 'all' ? 'All Currencies' : selectedCurrency }}
                </p>
              </div>
              <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                <TrendingUp class="w-6 h-6" />
              </div>
            </div>
          </div>

          <!-- Pending Amount Card -->
          <div class="bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-yellow-100 text-sm font-medium">Pending Amount</p>
                <p class="text-2xl font-bold mt-1">
                  <span v-if="selectedCurrency === 'all'">
                    {{ getCurrencySymbol('USD') }}{{ formatNumber(pendingAmountAllCurrencies) }}
                  </span>
                  <span v-else>
                    {{ getCurrencySymbol(selectedCurrency) }}{{ formatNumber(pendingAmountByCurrency) }}
                  </span>
                </p>
                <p class="text-xs text-yellow-100 mt-1">
                  {{ selectedCurrency === 'all' ? 'All Currencies' : selectedCurrency }}
                </p>
              </div>
              <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                <Clock class="w-6 h-6" />
              </div>
            </div>
          </div>

          <!-- Total Transactions Card -->
          <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-blue-100 text-sm font-medium">Total Transactions</p>
                <p class="text-2xl font-bold mt-1">
                  {{ totalTransactionsAll }}
                </p>
                <p class="text-xs text-blue-100 mt-1">
                  {{ selectedCurrency === 'all' ? 'All Currencies' : `Filtered: ${selectedCurrency}` }}
                </p>
              </div>
              <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                <CreditCard class="w-6 h-6" />
              </div>
            </div>
          </div>

          <!-- Successful Transactions Card -->
          <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-purple-100 text-sm font-medium">Successful</p>
                <p class="text-2xl font-bold mt-1">
                  {{ successfulTransactionsAll }}
                </p>
                <p class="text-xs text-purple-100 mt-1">
                  {{ selectedCurrency === 'all' ? 'All Currencies' : `Filtered: ${selectedCurrency}` }}
                </p>
              </div>
              <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                <CheckCircle class="w-6 h-6" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Payments -->
      <div v-if="!filteredPayments.length">
        <div class="rounded-xl border bg-card p-12 text-center">
          <div class="flex flex-col items-center space-y-4">
            <CreditCard class="w-16 h-16 text-muted-foreground" />
            <div class="space-y-2">
              <h3 class="text-xl font-semibold text-foreground">No payments found</h3>
              <p class="text-muted-foreground">{{ _t('vendorprofilepages', 'no_payment_history_found_text') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payments Table -->
      <div v-else>
        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[60px]">#</TableHead>
                <TableHead>Transaction ID</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Vehicle</TableHead>
                <TableHead class="text-right">Amount Paid</TableHead>
                <TableHead class="text-right">Total Amount</TableHead>
                <TableHead class="text-right">Pending</TableHead>
                <TableHead>Method</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(payment, index) in paginatedPayments" :key="payment.id" class="hover:bg-muted/50 transition-colors">
                <TableCell class="font-medium text-muted-foreground">
                  {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </TableCell>
                <TableCell>
                  <div class="font-mono text-sm">
                    {{ payment.transaction_id || 'N/A' }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <Avatar class="w-8 h-8">
                      <AvatarImage :src="payment.booking?.customer?.profile_image" />
                      <AvatarFallback class="text-xs">
                        {{ payment.booking?.customer?.first_name?.[0] || 'N' }}{{ payment.booking?.customer?.last_name?.[0] || 'A' }}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <div class="font-medium text-sm">
                        {{ payment.booking?.customer?.first_name || 'N/A' }} {{ payment.booking?.customer?.last_name || '' }}
                      </div>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="font-medium">
                    {{ payment.booking?.vehicle?.brand || 'N/A' }} {{ payment.booking?.vehicle?.model || '' }}
                  </div>
                </TableCell>
                <TableCell class="text-right">
                  <div class="font-medium text-green-600">
                    {{ getCurrencySymbol(payment.booking?.booking_currency || 'USD') }}{{ formatNumber(payment.booking?.amount_paid || 0) }}
                  </div>
                </TableCell>
                <TableCell class="text-right">
                  <div class="font-medium">
                    {{ getCurrencySymbol(payment.booking?.booking_currency || 'USD') }}{{ formatNumber(payment.booking?.total_amount || 0) }}
                  </div>
                </TableCell>
                <TableCell class="text-right">
                  <div class="font-medium text-yellow-600">
                    {{ getCurrencySymbol(payment.booking?.booking_currency || 'USD') }}{{ formatNumber(payment.booking?.pending_amount || 0) }}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline" class="capitalize">
                    {{ payment.payment_method || 'N/A' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusVariant(payment.payment_status)" class="capitalize">
                    {{ payment.payment_status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="text-sm text-muted-foreground">
                    {{ formatDate(payment.created_at) }}
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div class="flex justify-end pt-4 pr-2">
            <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
              @page-change="handlePageChange" />
          </div>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { defineProps } from 'vue'
import Pagination from '@/Pages/Vendor/Payments/Pagination.vue';
import { Link, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/ui/avatar';
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell
} from "@/Components/ui/table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  CreditCard,
  Search,
  Filter,
  TrendingUp,
  Clock,
  CheckCircle,
  DollarSign,
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
  payments: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  },
  allPayments: {
    type: Array,
    default: () => []
  }
});

const searchQuery = ref('');
const statusFilter = ref('all');
const selectedCurrency = ref('all');

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Get currency symbol
const getCurrencySymbol = (currency) => {
  const symbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'AUD': 'A$',
    'CAD': 'C$',
    'CHF': 'Fr',
    'HKD': 'HK$',
    'SGD': 'S$',
    'SEK': 'kr',
    'KRW': '₩',
    'NOK': 'kr',
    'NZD': 'NZ$',
    'INR': '₹',
    'MXN': 'Mex$',
    'ZAR': 'R',
    'AED': 'AED'
  };
  return symbols[currency] || '$';
};

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number);
};

// Get status badge variant
const getStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'succeeded':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'failed':
      return 'destructive';
    default:
      return 'outline';
  }
};

// Use all payments data if provided, otherwise use current page payments
const allPaymentsData = computed(() => {
  return props.allPayments.length > 0 ? props.allPayments : props.payments;
});

// Get all available currencies
const availableCurrencies = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value)) return [];

  const currencies = new Set();
  allPaymentsData.value.forEach(payment => {
    const currency = payment.currency || payment.booking?.booking_currency || 'USD';
    currencies.add(currency);
  });

  return Array.from(currencies).sort();
});

// Total revenue across all currencies (based on total_amount - full booking value)
const totalRevenueAllCurrencies = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value)) return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const totalAmount = payment.total_amount || payment.booking?.total_amount || 0;
    return total + parseFloat(totalAmount || 0);
  }, 0);
});

// Total pending amount across all currencies
const pendingAmountAllCurrencies = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value)) return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const pendingAmount = payment.pending_amount || payment.booking?.pending_amount || 0;
    return total + parseFloat(pendingAmount || 0);
  }, 0);
});

// Revenue for selected currency only (based on total_amount - full booking value)
const totalRevenueByCurrency = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value) || selectedCurrency.value === 'all') return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const totalAmount = payment.total_amount || payment.booking?.total_amount || 0;
    const currency = payment.currency || payment.booking?.booking_currency || 'USD';

    if (currency === selectedCurrency.value) {
      return total + parseFloat(totalAmount || 0);
    }
    return total;
  }, 0);
});

// Pending amount for selected currency only
const pendingAmountByCurrency = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value) || selectedCurrency.value === 'all') return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const pendingAmount = payment.pending_amount || payment.booking?.pending_amount || 0;
    const currency = payment.currency || payment.booking?.booking_currency || 'USD';

    if (currency === selectedCurrency.value) {
      return total + parseFloat(pendingAmount || 0);
    }
    return total;
  }, 0);
});

// Total transactions count (all data)
const totalTransactionsAll = computed(() => {
  if (selectedCurrency.value === 'all') {
    return allPaymentsData.value?.length || 0;
  } else {
    return allPaymentsData.value?.filter(payment => {
      const currency = payment.currency || payment.booking?.booking_currency || 'USD';
      return currency === selectedCurrency.value;
    }).length || 0;
  }
});

// Successful transactions count (all data)
const successfulTransactionsAll = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value)) return 0;

  let filtered = allPaymentsData.value;

  if (selectedCurrency.value !== 'all') {
    filtered = filtered.filter(payment => {
      const currency = payment.currency || payment.booking?.booking_currency || 'USD';
      return currency === selectedCurrency.value;
    });
  }

  return filtered.filter(payment =>
    payment.payment_status?.toLowerCase() === 'succeeded'
  ).length;
});

const handlePageChange = (page) => {
  router.get(route('vendor.payments'), {
    search: searchQuery.value,
    status: statusFilter.value !== 'all' ? statusFilter.value : null,
    page
  }, { preserveState: true, preserveScroll: true });
};

// Filter and paginate payments
const filteredPayments = computed(() => {
  let filtered = props.payments;

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(payment => {
      return (
        (payment.transaction_id?.toLowerCase().includes(query)) ||
        (payment.booking?.customer?.first_name?.toLowerCase().includes(query)) ||
        (payment.booking?.customer?.last_name?.toLowerCase().includes(query)) ||
        (payment.booking?.vehicle?.brand?.toLowerCase().includes(query)) ||
        (payment.payment_method?.toLowerCase().includes(query)) ||
        (payment.payment_status?.toLowerCase().includes(query)) ||
        formatDate(payment.created_at).toLowerCase().includes(query)
      );
    });
  }

  // Filter by status
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(payment =>
      payment.payment_status?.toLowerCase() === statusFilter.value
    );
  }

  return filtered;
});

// Paginated payments for current page
const paginatedPayments = computed(() => {
  return filteredPayments.value;
});
</script>

<style scoped>
@media screen and (max-width:768px) {
    
    th{
        font-size: 0.75rem;
    }
    td{
        font-size: 0.75rem;
        text-wrap-mode: nowrap;
    }
}
</style>
