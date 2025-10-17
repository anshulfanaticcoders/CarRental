<script setup>
import { ref, onMounted, computed } from 'vue'
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
  Wallet
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

// Computed properties
const filteredPayments = computed(() => {
  let filtered = payments.value

  // Filter by status
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(p => p.status === statusFilter.value)
  }

  // Filter by search term
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(p =>
      p.business_name.toLowerCase().includes(term) ||
      p.booking_reference.toLowerCase().includes(term) ||
      p.customer_email.toLowerCase().includes(term) ||
      p.currency.toLowerCase().includes(term)
    )
  }

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
  const total = payments.value.reduce((sum, p) => sum + parseFloat(p.net_commission || 0), 0)
  const pending = payments.value.filter(p => p.status === 'pending').reduce((sum, p) => sum + parseFloat(p.net_commission || 0), 0)
  const approved = payments.value.filter(p => p.status === 'approved').reduce((sum, p) => sum + parseFloat(p.net_commission || 0), 0)
  const paid = payments.value.filter(p => p.status === 'paid').reduce((sum, p) => sum + parseFloat(p.net_commission || 0), 0)
  const disputed = payments.value.filter(p => p.status === 'disputed').reduce((sum, p) => sum + parseFloat(p.net_commission || 0), 0)

  return {
    total,
    pending,
    approved,
    paid,
    disputed,
    count: {
      total: payments.value.length,
      pending: payments.value.filter(p => p.status === 'pending').length,
      approved: payments.value.filter(p => p.status === 'approved').length,
      paid: payments.value.filter(p => p.status === 'paid').length,
      disputed: payments.value.filter(p => p.status === 'disputed').length,
    }
  }
})

// Load payments
const loadPayments = async () => {
  loading.value = true
  try {
    const [paymentsResponse, statsResponse] = await Promise.all([
      axios.get('/admin/affiliate/commissions', {
        params: {
          page: currentPage.value,
          date_range: dateRange.value,
          include: 'business,booking,customer'
        }
      }),
      axios.get('/admin/affiliate/payment-statistics', {
        params: { date_range: dateRange.value }
      })
    ])

    payments.value = paymentsResponse.data.data || []
    statistics.value = statsResponse.data || {}
    totalPages.value = paymentsResponse.data.last_page || 1
  } catch (error) {
    console.error('Error loading payments:', error)
    showNotification('Error loading payments', 'error')
  } finally {
    loading.value = false
  }
}

// Approve payment
const approvePayment = async (paymentId) => {
  processing.value.add(paymentId)
  try {
    await axios.post(`/admin/affiliate/commissions/${paymentId}/approve`)
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
    await axios.post(`/admin/affiliate/commissions/${paymentId}/mark-paid`)
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
    await axios.post(`/admin/affiliate/commissions/${paymentId}/reject`, { reason })
    showNotification('Payment rejected', 'success')
    await loadPayments()
  } catch (error) {
    console.error('Error rejecting payment:', error)
    showNotification('Error rejecting payment', 'error')
  } finally {
    processing.value.delete(paymentId)
  }
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

// Format currency
const formatCurrency = (amount, currency = 'EUR') => {
  const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
  return formatter.format(amount)
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

// Export data
const exportData = async () => {
  try {
    const response = await axios.get('/admin/affiliate/commissions/export', {
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
        <div class="chart-placeholder">
          <p>Payment analytics chart will be displayed here</p>
          <small>Integration with charting library needed</small>
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
              Date
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
          <div class="table-info">Customer</div>
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
                <span class="booking-ref">#{{ payment.booking_reference }}</span>
                <span class="booking-amount">{{ formatCurrency(payment.booking_amount, payment.currency) }}</span>
              </div>
            </div>

            <div class="table-cell customer-cell">
              <div class="customer-info">
                <div class="customer-item">
                  <User class="icon" />
                  <span>{{ payment.customer_name }}</span>
                </div>
                <div class="customer-item">
                  <FileText class="icon" />
                  <span>{{ payment.customer_email }}</span>
                </div>
              </div>
            </div>

            <div class="table-cell amount-cell">
              <div class="amount-info">
                <span class="amount">{{ formatCurrency(payment.net_commission, payment.currency) }}</span>
                <span class="rate">{{ payment.commission_rate }}%{{ payment.commission_type === 'fixed' ? ' fixed' : '' }}</span>
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
                <button class="btn-view">
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

.chart-placeholder {
  height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  border-radius: 8px;
  color: #6b7280;
}

.chart-placeholder small {
  margin-top: 0.5rem;
  color: #9ca3af;
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
  max-height: 600px;
  overflow-y: auto;
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
}

.date-info, .customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
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