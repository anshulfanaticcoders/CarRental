<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import {
  CreditCard,
  DollarSign,
  TrendingUp,
  TrendingDown,
  Calendar,
  CheckCircle,
  Clock,
  XCircle,
  AlertCircle,
  Search,
  Filter,
  Download,
  RefreshCw,
  Eye,
  ExternalLink,
  Building,
  User,
  FileText,
  Activity,
  BarChart3,
  ArrowUpRight,
  ArrowDownRight,
  Wallet,
  X
} from 'lucide-vue-next'

// Reactive data
const loading = ref(true)
const payments = ref([])
const statistics = ref({})
const searchTerm = ref('')
const statusFilter = ref('all')
const dateRange = ref('30d')
const sortBy = ref('created_at')
const sortOrder = ref('desc')
const currentPage = ref(1)
const totalPages = ref(1)
const processing = ref(new Set())

// Modal state
const showDetailsModal = ref(false)
const selectedCommission = ref(null)

// Computed properties
const filteredPayments = computed(() => {
  // Only sort, don't filter on client side - server handles filtering
  let filtered = [...payments.value]

  // Sort
  filtered.sort((a, b) => {
    let aVal = a[sortBy.value]
    let bVal = b[sortBy.value]

    if (sortBy.value === 'created_at' || sortBy.value === 'paid_at' || sortBy.value === 'scheduled_payout_date') {
      aVal = new Date(aVal)
      bVal = new Date(bVal)
    } else if (sortBy.value === 'net_commission' || sortBy.value === 'booking_amount') {
      aVal = parseFloat(aVal)
      bVal = parseFloat(bVal)
    }

    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return filtered
})

const stats = computed(() => {
  // Use real statistics from API
  return {
    total: statistics.value.overview?.total_amount || 0,
    pending: statistics.value.overview?.pending_amount || 0,
    approved: statistics.value.overview?.approved_amount || 0,
    paid: statistics.value.overview?.paid_amount || 0,
    disputed: statistics.value.overview?.disputed_amount || 0,
    count: {
      total: statistics.value.overview?.total_commissions || 0,
      pending: statistics.value.overview?.pending_commissions || 0,
      approved: statistics.value.overview?.approved_commissions || 0,
      paid: statistics.value.overview?.paid_commissions || 0,
      disputed: statistics.value.overview?.disputed_commissions || 0,
    }
  }
})

// Chart data for simple bar chart
const chartData = computed(() => {
  return [
    { label: 'Pending', value: stats.value.pending },
    { label: 'Approved', value: stats.value.approved },
    { label: 'Paid', value: stats.value.paid },
    { label: 'Disputed', value: stats.value.disputed }
  ]
})

// Load payments
const loadPayments = async () => {
  loading.value = true
  try {
    const [paymentsResponse, statsResponse] = await Promise.all([
      axios.get('/admin/affiliate/commissions-data', {
        params: {
          page: currentPage.value,
          date_range: dateRange.value,
          status: statusFilter.value === 'all' ? null : statusFilter.value,
          search: searchTerm.value
        }
      }),
      axios.get('/admin/affiliate/commission-statistics', {
        params: { date_range: dateRange.value }
      })
    ])

    payments.value = paymentsResponse.data.data || []
    statistics.value = statsResponse.data || {}
    totalPages.value = paymentsResponse.data.pagination?.last_page || 1
  } catch (error) {
    console.error('Error loading payments:', error)
    showNotification('Error loading payments', 'error')
  } finally {
    loading.value = false
  }
}

// Watch for search changes and reload
const handleSearch = () => {
  currentPage.value = 1 // Reset to first page when searching
  loadPayments()
}

// Approve payment
const approvePayment = async (paymentId) => {
  processing.value.add(paymentId)
  try {
    await axios.patch(`/admin/affiliate/commissions/${paymentId}/status`, { status: 'approved' })
    showNotification('Payment approved successfully', 'success')
    await loadPayments()
  } catch (error) {
    console.error('Error approving payment:', error)
    showNotification('Error approving payment', 'error')
  } finally {
    processing.value.delete(paymentId)
  }
}

// Mark as paid
const markAsPaid = async (paymentId) => {
  processing.value.add(paymentId)
  try {
    await axios.post(`/admin/affiliate/commissions/${paymentId}/pay`)
    showNotification('Payment marked as paid', 'success')
    await loadPayments()
  } catch (error) {
    console.error('Error marking payment as paid:', error)
    showNotification('Error marking payment as paid', 'error')
  } finally {
    processing.value.delete(paymentId)
  }
}

// Reject payment
const rejectPayment = async (paymentId, reason = 'Payment rejected by admin') => {
  processing.value.add(paymentId)
  try {
    await axios.patch(`/admin/affiliate/commissions/${paymentId}/status`, { status: 'rejected' })
    showNotification('Payment rejected', 'success')
    await loadPayments()
  } catch (error) {
    console.error('Error rejecting payment:', error)
    showNotification('Error rejecting payment', 'error')
  } finally {
    processing.value.delete(paymentId)
  }
}

// View commission details
const viewDetails = (payment) => {
  selectedCommission.value = payment
  showDetailsModal.value = true
}

// Close details modal
const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedCommission.value = null
}

// Get status icon
const getStatusIcon = (status) => {
  switch (status) {
    case 'approved':
      return CheckCircle
    case 'pending':
      return Clock
    case 'paid':
      return Wallet
    case 'disputed':
      return AlertCircle
    case 'rejected':
      return XCircle
    default:
      return AlertCircle
  }
}

// Get status color
const getStatusColor = (status) => {
  switch (status) {
    case 'approved':
      return 'text-blue-600 bg-blue-100'
    case 'pending':
      return 'text-yellow-600 bg-yellow-100'
    case 'paid':
      return 'text-green-600 bg-green-100'
    case 'disputed':
      return 'text-red-600 bg-red-100'
    case 'rejected':
      return 'text-gray-600 bg-gray-100'
    default:
      return 'text-gray-600 bg-gray-100'
  }
}

// Currency symbol to code mapping
const currencySymbolToCode = {
  '₹': 'INR',
  '€': 'EUR',
  '£': 'GBP',
  '$': 'USD',
  '¥': 'JPY',
  '¥': 'CNY', // Chinese Yuan
  '₩': 'KRW', // South Korean Won
  '₽': 'TRY', // Turkish Lira
  '₼': 'RUB', // Russian Ruble
  'R$': 'ZAR', // South African Rand
  'A$': 'AUD', // Australian Dollar
  'C$': 'CAD', // Canadian Dollar
  'CHF': 'CHF', // Swiss Franc
  '₡': 'NOK', // Norwegian Krone
  'kr': 'SEK', // Swedish Krona
  '₪': 'ILS', // Israeli New Shekel
  '₫': 'BGN', // Bulgarian Lev
  '₭': 'RON', // Romanian Leu
  '₺': 'HRK', // Croatian Kuna
  '₸': 'CZK', // Czech Koruna
  'Ft': 'HUF', // Hungarian Forint
  'zł': 'PLN', // Polish Zloty
  ' Kč': 'CZK' // Czech Koruna (alternative)
}

// Format currency with symbol-to-code conversion
const formatCurrency = (amount, currency = 'EUR') => {
  // Convert currency symbol to code if needed
  const currencyCode = currencySymbolToCode[currency] || currency || 'EUR'

  try {
    const formatter = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
    return formatter.format(amount)
  } catch (error) {
    // Fallback to simple formatting if Intl.NumberFormat fails
    const symbol = Object.keys(currencySymbolToCode).find(code => currencySymbolToCode[code] === currencyCode) ? currency : '€'
    return `${symbol}${amount.toFixed(2)}`
  }
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Show notification
const showNotification = (message, type) => {
  const notification = document.createElement('div')
  notification.className = `notification ${type}`
  notification.textContent = message
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    color: white;
    z-index: 9999;
    background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#ef4444'};
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  `
  document.body.appendChild(notification)
  setTimeout(() => notification.remove(), 3000)
}

// Sort
const sort = (field) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortOrder.value = 'desc'
  }
}

// Chart helper method
const getBarHeight = (value) => {
  const maxValue = Math.max(...chartData.value.map(stat => stat.value))
  const percentage = maxValue > 0 ? (value / maxValue) * 100 : 0
  return Math.max(percentage, 2) + '%'
}

// Export data
const exportData = async () => {
  try {
    const response = await axios.get('/admin/affiliate/commissions-export', {
      params: {
        status: statusFilter.value,
        date_range: dateRange.value,
        search: searchTerm.value
      },
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `commissions-${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    showNotification('Data exported successfully', 'success')
  } catch (error) {
    console.error('Error exporting data:', error)
    showNotification('Error exporting data', 'error')
  }
}

// Watch for search and filter changes
watch([searchTerm, statusFilter, dateRange], () => {
  handleSearch()
})

// Watch for pagination changes
watch(currentPage, () => {
  loadPayments()
})

// Load on mount
onMounted(() => {
  loadPayments()
})
</script>

<template>
  <Head title="Payment Status Tracking" />
  <AdminDashboardLayout>
    <div class="payment-tracking">
      <!-- Header -->
      <div class="header">
        <div class="header-content">
          <h1>Payment Status Tracking</h1>
          <p>Monitor and manage affiliate commission payments</p>
        </div>
        <div class="header-actions">
          <button @click="exportData" class="btn-export">
            <Download />
            Export
          </button>
          <button @click="loadPayments" class="btn-refresh" :disabled="loading">
            <RefreshCw :class="{ 'animate-spin': loading }" />
            Refresh
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-icon">
            <DollarSign />
          </div>
          <div class="stat-content">
            <h3>{{ formatCurrency(stats.total) }}</h3>
            <p>Total Commissions</p>
            <div class="stat-subtext">{{ stats.count.total }} transactions</div>
          </div>
        </div>

        <div class="stat-card pending">
          <div class="stat-icon">
            <Clock />
          </div>
          <div class="stat-content">
            <h3>{{ formatCurrency(stats.pending) }}</h3>
            <p>Pending Approval</p>
            <div class="stat-subtext">{{ stats.count.pending }} transactions</div>
          </div>
        </div>

        <div class="stat-card approved">
          <div class="stat-icon">
            <CheckCircle />
          </div>
          <div class="stat-content">
            <h3>{{ formatCurrency(stats.approved) }}</h3>
            <p>Approved</p>
            <div class="stat-subtext">{{ stats.count.approved }} transactions</div>
          </div>
        </div>

        <div class="stat-card paid">
          <div class="stat-icon">
            <Wallet />
          </div>
          <div class="stat-content">
            <h3>{{ formatCurrency(stats.paid) }}</h3>
            <p>Paid</p>
            <div class="stat-subtext">{{ stats.count.paid }} transactions</div>
          </div>
        </div>

        <div class="stat-card disputed">
          <div class="stat-icon">
            <AlertCircle />
          </div>
          <div class="stat-content">
            <h3>{{ formatCurrency(stats.disputed) }}</h3>
            <p>Disputed</p>
            <div class="stat-subtext">{{ stats.count.disputed }} transactions</div>
          </div>
        </div>
      </div>

      <!-- Summary Chart -->
      <div class="summary-chart">
        <div class="chart-header">
          <h3>Payment Overview</h3>
          <BarChart3 class="chart-icon" />
        </div>
        <div class="chart-container">
          <div class="chart-bars">
            <div class="bar-item" v-for="(stat, index) in chartData" :key="index" :style="{ height: getBarHeight(stat.value) }">
              <div class="bar-label">{{ stat.label }}</div>
              <div class="bar-value">{{ formatCurrency(stat.value) }}</div>
              <div class="bar-fill" :style="{ height: getBarHeight(stat.value) }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="filters-section">
        <div class="filters-left">
          <div class="search-box">
            <Search />
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Search payments..."
              class="search-input"
            />
          </div>

          <select v-model="dateRange" @change="loadPayments" class="filter-select">
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="1y">Last Year</option>
          </select>

          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
            <option value="disputed">Disputed</option>
            <option value="rejected">Rejected</option>
          </select>

          <select v-model="sortBy" @change="sort(sortBy)" class="filter-select">
            <option value="created_at">Date Created</option>
            <option value="net_commission">Amount</option>
            <option value="business_name">Business</option>
            <option value="status">Status</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading payments...</p>
      </div>

      <!-- Payments Table -->
      <div v-else class="payments-table">
        <div class="table-header">
          <div class="table-actions">
            <button @click="sort('created_at')" class="sort-btn" :class="{ active: sortBy === 'created_at' }">
              Date Created
              <span v-if="sortBy === 'created_at'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">
            <button @click="sort('business_name')" class="sort-btn" :class="{ active: sortBy === 'business_name' }">
              Business
              <span v-if="sortBy === 'business_name'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-info">Booking</div>
          <div class="table-actions">
            <button @click="sort('affiliate_name')" class="sort-btn" :class="{ active: sortBy === 'affiliate_name' }">
              Customer
              <span v-if="sortBy === 'affiliate_name'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">
            <button @click="sort('net_commission')" class="sort-btn" :class="{ active: sortBy === 'net_commission' }">
              Commission
              <span v-if="sortBy === 'net_commission'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">
            <button @click="sort('status')" class="sort-btn" :class="{ active: sortBy === 'status' }">
              Status
              <span v-if="sortBy === 'status'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-info">Actions</div>
        </div>

        <div class="table-body">
          <div
            v-for="payment in filteredPayments"
            :key="payment.id"
            class="table-row"
          >
            <div class="table-cell date-cell">
              <div class="date-info">
                <Calendar class="icon" />
                <span>{{ formatDate(payment.created_at) }}</span>
              </div>
            </div>

            <div class="table-cell business-cell">
              <div class="business-info">
                <h4>{{ payment.business_name }}</h4>
                <span class="business-type">{{ payment.business_type }}</span>
              </div>
            </div>

            <div class="table-cell booking-cell">
              <div class="booking-info">
                <span class="booking-ref">{{ payment.booking_reference || 'N/A' }}</span>
                <span class="booking-amount">{{ formatCurrency(payment.booking_amount || 0, payment.currency) }}</span>
              </div>
            </div>

            <div class="table-cell customer-cell">
              <div class="customer-info">
                <div class="customer-item">
                  <User class="icon" />
                  <span>{{ payment.affiliate_name }}</span>
                </div>
                <div class="customer-item">
                  <FileText class="icon" />
                  <span>{{ payment.affiliate_email }}</span>
                </div>
              </div>
            </div>

            <div class="table-cell amount-cell">
              <div class="amount-info">
                <span class="amount">{{ formatCurrency(payment.amount, payment.currency) }}</span>
                <span class="rate">Commission</span>
              </div>
            </div>

            <div class="table-cell status-cell">
              <div class="status-badge" :class="getStatusColor(payment.status)">
                <component :is="getStatusIcon(payment.status)" />
                {{ payment.status }}
              </div>
              <div v-if="payment.scheduled_payout_date" class="payout-date">
                <Clock class="icon" />
                {{ formatDate(payment.scheduled_payout_date) }}
              </div>
            </div>

            <div class="table-cell actions-cell">
              <div class="action-buttons">
                <button @click="viewDetails(payment)" class="btn-view">
                  <Eye />
                  Details
                </button>

                <template v-if="payment.status === 'pending'">
                  <button
                    @click="approvePayment(payment.id)"
                    class="btn-approve"
                    :disabled="processing.has(payment.id)"
                  >
                    <CheckCircle />
                    Approve
                  </button>
                </template>

                <template v-if="payment.status === 'approved'">
                  <button
                    @click="markAsPaid(payment.id)"
                    class="btn-paid"
                    :disabled="processing.has(payment.id)"
                  >
                    <Wallet />
                    Mark Paid
                  </button>
                </template>

                <template v-if="['pending', 'approved'].includes(payment.status)">
                  <button
                    @click="rejectPayment(payment.id)"
                    class="btn-reject"
                    :disabled="processing.has(payment.id)"
                  >
                    <XCircle />
                    Reject
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredPayments.length === 0" class="empty-state">
            <CreditCard />
            <h3>No payments found</h3>
            <p>Try adjusting your filters or search criteria</p>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          Previous
        </button>

        <span class="pagination-info">
          Page {{ currentPage }} of {{ totalPages }}
        </span>

        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="pagination-btn"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Commission Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Commission Details</h3>
          <button @click="closeDetailsModal" class="modal-close">
            <X />
          </button>
        </div>

        <div class="modal-body" v-if="selectedCommission">
          <div class="detail-section">
            <h4>Commission Information</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Commission ID:</label>
                <span>{{ selectedCommission.id }}</span>
              </div>
              <div class="detail-item">
                <label>Amount:</label>
                <span>{{ formatCurrency(selectedCommission.amount, selectedCommission.currency) }}</span>
              </div>
              <div class="detail-item">
                <label>Status:</label>
                <span class="status-badge" :class="getStatusColor(selectedCommission.status)">
                  <component :is="getStatusIcon(selectedCommission.status)" />
                  {{ selectedCommission.status }}
                </span>
              </div>
              <div class="detail-item">
                <label>Created Date:</label>
                <span>{{ formatDate(selectedCommission.created_at) }}</span>
              </div>
              <div class="detail-item" v-if="selectedCommission.paid_at">
                <label>Paid Date:</label>
                <span>{{ formatDate(selectedCommission.paid_at) }}</span>
              </div>
              <div class="detail-item" v-if="selectedCommission.scheduled_payout_date">
                <label>Scheduled Payout:</label>
                <span>{{ selectedCommission.scheduled_payout_date }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Business Information</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Business Name:</label>
                <span>{{ selectedCommission.business_name }}</span>
              </div>
              <div class="detail-item">
                <label>Business Type:</label>
                <span>{{ selectedCommission.business_type }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Customer Information</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Customer Name:</label>
                <span>{{ selectedCommission.affiliate_name }}</span>
              </div>
              <div class="detail-item">
                <label>Customer Email:</label>
                <span>{{ selectedCommission.affiliate_email }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Booking Information</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Booking Reference:</label>
                <span>{{ selectedCommission.booking_reference }}</span>
              </div>
              <div class="detail-item">
                <label>QR Code ID:</label>
                <span>{{ selectedCommission.qr_code_id }}</span>
              </div>
              <div class="detail-item">
                <label>Booking Amount:</label>
                <span>{{ formatCurrency(selectedCommission.booking_amount || 0, selectedCommission.currency) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style scoped>
.payment-tracking {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.header-content p {
  color: #6b7280;
  font-size: 1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-export, .btn-refresh {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-export:hover, .btn-refresh:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-card.total {
  border-left: 4px solid #6366f1;
}

.stat-card.pending {
  border-left: 4px solid #f59e0b;
}

.stat-card.approved {
  border-left: 4px solid #3b82f6;
}

.stat-card.paid {
  border-left: 4px solid #10b981;
}

.stat-card.disputed {
  border-left: 4px solid #ef4444;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
}

.stat-icon svg {
  width: 24px;
  height: 24px;
  color: #6b7280;
}

.stat-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.stat-content p {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.stat-subtext {
  font-size: 0.75rem;
  color: #9ca3af;
}

.summary-chart {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.chart-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.chart-icon {
  color: #6b7280;
}

.chart-container {
  height: 200px;
  background: #f9fafb;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
}

.chart-bars {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  width: 100%;
  height: 100%;
}

.bar-item {
  position: relative;
  width: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  height: 100%;
}

.bar-label {
  font-size: 0.75rem;
  color: #374151;
  margin-bottom: 0.5rem;
  text-align: center;
  font-weight: 500;
  position: relative;
  z-index: 2;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 4px;
  border-radius: 4px;
}

.bar-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  text-align: center;
  position: relative;
  z-index: 2;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 4px;
  border-radius: 4px;
}

.bar-fill {
  position: absolute;
  bottom: 0;
  width: 60px;
  background: linear-gradient(to top, #3b82f6, #1d4ed8);
  border-radius: 4px 4px 0 0;
  transition: height 0.3s ease;
  min-height: 4px;
  z-index: 1;
  left: 50%;
  transform: translateX(-50%);
}

.bar-item:nth-child(2) .bar-fill {
  background: linear-gradient(to top, #10b981, #059669);
}

.bar-item:nth-child(3) .bar-fill {
  background: linear-gradient(to top, #f59e0b, #d97706);
}

.bar-item:nth-child(4) .bar-fill {
  background: linear-gradient(to top, #ef4444, #dc2626);
}

.filters-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.filters-left {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box svg {
  position: absolute;
  left: 12px;
  width: 18px;
  height: 18px;
  color: #6b7280;
}

.search-input {
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  min-width: 250px;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  font-size: 0.875rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

.payments-table {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 1.2fr 1.5fr 1fr 1.5fr 1fr 1fr 1.5fr;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  white-space: nowrap;
}

.sort-btn {
  background: none;
  border: none;
  color: inherit;
  font-size: inherit;
  font-weight: inherit;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0;
}

.sort-btn:hover {
  color: #3b82f6;
}

.sort-btn.active {
  color: #3b82f6;
}

.sort-indicator {
  font-size: 0.75rem;
}

.table-body {
  overflow-x: auto;
}

.table-row {
  display: grid;
  grid-template-columns: 1.2fr 1.5fr 1fr 1.5fr 1fr 1fr 1.5fr;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  align-items: center;
  transition: background-color 0.2s;
}

.table-row:hover {
  background: #f9fafb;
}

.table-cell {
  display: flex;
  align-items: center;
  white-space: nowrap;
}

.date-info, .customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  white-space: nowrap;
}

.date-info, .customer-item, .payout-date {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.icon {
  width: 14px;
  height: 14px;
}

.business-info h4 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.business-type {
  font-size: 0.75rem;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
}

.booking-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  white-space: nowrap;
}

.booking-ref {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.booking-amount {
  font-size: 0.75rem;
  color: #6b7280;
}

.amount-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  white-space: nowrap;
}

.amount {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.rate {
  font-size: 0.75rem;
  color: #6b7280;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.status-badge svg {
  width: 14px;
  height: 14px;
}

.payout-date {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-view, .btn-approve, .btn-paid, .btn-reject {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border: none;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view {
  background: #3b82f6;
  color: white;
}

.btn-view:hover {
  background: #2563eb;
}

.btn-approve {
  background: #3b82f6;
  color: white;
}

.btn-approve:hover {
  background: #2563eb;
}

.btn-paid {
  background: #10b981;
  color: white;
}

.btn-paid:hover {
  background: #059669;
}

.btn-reject {
  background: #ef4444;
  color: white;
}

.btn-reject:hover {
  background: #dc2626;
}

.btn-view svg, .btn-approve svg, .btn-paid svg, .btn-reject svg {
  width: 14px;
  height: 14px;
}

.btn-approve:disabled, .btn-paid:disabled, .btn-reject:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #6b7280;
}

.empty-state svg {
  width: 48px;
  height: 48px;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.pagination-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 2rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
}

.detail-item span {
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 500;
}

@media (max-width: 768px) {
  .payment-tracking {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-left {
    flex-direction: column;
  }

  .search-input {
    min-width: 100%;
  }

  .table-header {
    display: none;
  }

  .table-row {
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .action-buttons {
    justify-content: center;
  }
}
</style>