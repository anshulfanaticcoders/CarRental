<template>
  <MyProfileLayout>
    <div class="space-y-5">
      <!-- Header -->
      <div class="vr-phead">
        <div>
          <span class="vr-eyebrow"><CreditCard /> {{ tt('vendorprofilepages', 'billing_eyebrow', 'Billing') }}</span>
          <h2>{{ tt('vendorprofilepages', 'payment_history_header', 'Payment History') }}</h2>
          <p class="vr-sub">{{ tt('vendorprofilepages', 'payment_history_subheader', 'Track payouts and transactions across your fleet.') }}</p>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="vr-toolbar">
        <label class="vr-search">
          <Search />
          <input v-model="searchQuery" type="text"
            :placeholder="_t('vendorprofilepages', 'search_payments_placeholder')" />
        </label>
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

      <!-- Stat Cards -->
      <div class="vr-stat-grid c4">
        <div class="vr-stat">
          <div class="vr-ic vr-ic-green"><TrendingUp /></div>
          <div class="vr-v">
            <span v-if="selectedCurrency === 'all'">{{ getCurrencySymbol(defaultVendorCurrency) }}{{ formatNumber(totalRevenueAllCurrencies) }}</span>
            <span v-else>{{ getCurrencySymbol(selectedCurrency) }}{{ formatNumber(totalRevenueByCurrency) }}</span>
          </div>
          <div class="vr-l">Total Revenue · {{ selectedCurrency === 'all' ? 'All Currencies' : selectedCurrency }}</div>
        </div>
        <div class="vr-stat">
          <div class="vr-ic vr-ic-amber"><Clock /></div>
          <div class="vr-v">
            <span v-if="selectedCurrency === 'all'">{{ getCurrencySymbol(defaultVendorCurrency) }}{{ formatNumber(pendingAmountAllCurrencies) }}</span>
            <span v-else>{{ getCurrencySymbol(selectedCurrency) }}{{ formatNumber(pendingAmountByCurrency) }}</span>
          </div>
          <div class="vr-l">Pending Amount · {{ selectedCurrency === 'all' ? 'All Currencies' : selectedCurrency }}</div>
        </div>
        <div class="vr-stat">
          <div class="vr-ic vr-ic-blue"><CreditCard /></div>
          <div class="vr-v">{{ totalTransactionsAll }}</div>
          <div class="vr-l">Total Transactions</div>
        </div>
        <div class="vr-stat">
          <div class="vr-ic vr-ic-violet"><CheckCircle /></div>
          <div class="vr-v">{{ successfulTransactionsAll }}</div>
          <div class="vr-l">Successful</div>
        </div>
      </div>

      <!-- No Payments -->
      <div v-if="!filteredPayments.length" class="vr-panel">
        <div class="vr-empty">
          <div class="e-ic"><CreditCard /></div>
          <h4>{{ tt('vendorprofilepages', 'no_payments_found', 'No payments found') }}</h4>
          <p>{{ tt('vendorprofilepages', 'no_payment_history_found_text', 'Your payment history will appear here.') }}</p>
        </div>
      </div>

      <!-- Payments Table -->
      <div v-else>
        <div class="vr-panel">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[60px]">#</TableHead>
                <TableHead>Transaction ID</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Vehicle</TableHead>
                <TableHead>Amount Paid</TableHead>
                <TableHead>Total Amount</TableHead>
                <TableHead>Pending</TableHead>
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
                <TableCell>
                  <div class="font-medium text-green-600">
                    {{ getCurrencySymbol(getVendorCurrency(payment)) }}{{ formatNumber(getVendorAmount(payment, 'amount_paid')) }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="font-medium">
                    {{ getCurrencySymbol(getVendorCurrency(payment)) }}{{ formatNumber(getVendorAmount(payment, 'total_amount')) }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="font-medium text-yellow-600">
                    {{ getCurrencySymbol(getVendorCurrency(payment)) }}{{ formatNumber(getVendorAmount(payment, 'pending_amount')) }}
                  </div>
                </TableCell>
                <TableCell>
                  <span class="vr-vbadge capitalize">{{ payment.payment_method || 'N/A' }}</span>
                </TableCell>
                <TableCell>
                  <span class="vr-chip capitalize" :class="vrStatus(payment.payment_status)">{{ payment.payment_status }}</span>
                </TableCell>
                <TableCell>
                  <div class="text-sm text-muted-foreground">
                    {{ formatDate(payment.created_at) }}
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div class="vr-pager">
            <span class="info"></span>
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
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/ui/avatar';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';
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
const tt = (group, key, fallback) => {
  const v = _t(group, key);
  return (!v || v === key) ? fallback : v;
};
const vrStatus = (status) => ({ succeeded: 'ok', pending: 'warn', failed: 'bad' }[status?.toLowerCase()] || 'mut');

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
const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

const getVendorCurrency = (payment) => {
  return payment.booking?.amounts?.vendor_currency
    || payment.booking?.vendor_profile?.currency
    || payment.booking?.booking_currency
    || 'EUR';
};

const getVendorAmount = (payment, field) => {
  const vendorFieldMap = {
    amount_paid: 'vendor_paid_amount',
    total_amount: 'vendor_total_amount',
    pending_amount: 'vendor_pending_amount',
  };

  const mappedField = vendorFieldMap[field];
  const amount = mappedField ? payment.booking?.amounts?.[mappedField] : payment.booking?.amounts?.[field];
  if (amount !== undefined && amount !== null) {
    return parseFloat(amount);
  }

  if (field === 'amount_paid') {
    return parseFloat(payment.amount || 0);
  }

  return parseFloat(payment.booking?.[field] || 0);
};

const defaultVendorCurrency = computed(() => {
  const samplePayment = props.payments?.[0] || props.allPayments?.[0] || null;
  return samplePayment ? getVendorCurrency(samplePayment) : 'EUR';
});

if (selectedCurrency.value === 'all' && defaultVendorCurrency.value) {
  selectedCurrency.value = defaultVendorCurrency.value;
}

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number);
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
    const currency = getVendorCurrency(payment);
    currencies.add(currency);
  });

  return Array.from(currencies).sort();
});

// Total revenue across all currencies (based on total_amount - full booking value)
const totalRevenueAllCurrencies = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value)) return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const totalAmount = getVendorAmount(payment, 'total_amount');
    return total + parseFloat(totalAmount || 0);
  }, 0);
});

// Total pending amount across all currencies
const pendingAmountAllCurrencies = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value)) return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const pendingAmount = getVendorAmount(payment, 'pending_amount');
    return total + parseFloat(pendingAmount || 0);
  }, 0);
});

// Revenue for selected currency only (based on total_amount - full booking value)
const totalRevenueByCurrency = computed(() => {
  if (!allPaymentsData.value || !Array.isArray(allPaymentsData.value) || selectedCurrency.value === 'all') return 0;

  return allPaymentsData.value.reduce((total, payment) => {
    const totalAmount = getVendorAmount(payment, 'total_amount');
    const currency = getVendorCurrency(payment);

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
    const pendingAmount = getVendorAmount(payment, 'pending_amount');
    const currency = getVendorCurrency(payment);

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
      const currency = getVendorCurrency(payment);
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
      const currency = getVendorCurrency(payment);
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
