<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import {
  Building,
  CreditCard,
  TrendingUp,
  TrendingDown,
  Calendar,
  DollarSign,
  Users,
  Filter,
  Search,
  Download,
  CheckCircle,
  XCircle,
  AlertCircle,
  MoreVertical,
  Edit,
  Trash2,
  Eye,
  Clock,
  ArrowUpCircle,
  ArrowDownCircle,
  BarChart3,
  PieChart
} from 'lucide-vue-next'
import { Line, Bar, Doughnut, Pie } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  Filler
} from 'chart.js'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  Filler
)

// Reactive data
const loading = ref(true)
const commissions = ref([])
const statistics = ref({
  overview: {},
  charts: {}
})
const dateRange = ref('30d')
const statusFilter = ref('all')
const searchQuery = ref('')
const selectedCommission = ref(null)
const showDetailsModal = ref(false)

// Chart data
const commissionTrendChart = ref(null)
const commissionStatusChart = ref(null)
const topAffiliatesChart = ref(null)

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
    tooltip: {
      mode: 'index',
      intersect: false,
    },
  },
}

// Commission trend chart data
const commissionTrendChartData = computed(() => ({
  labels: commissionTrendChart.value?.labels || [],
  datasets: [
    {
      label: 'Commission Amount',
      data: commissionTrendChart.value?.amounts || [],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
      fill: true,
    },
    {
      label: 'Number of Commissions',
      data: commissionTrendChart.value?.counts || [],
      borderColor: 'rgb(16, 185, 129)',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.4,
      fill: true,
      yAxisID: 'y1',
    }
  ]
}))

const commissionTrendChartOptions = {
  ...chartOptions,
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Date'
      }
    },
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      title: {
        display: true,
        text: 'Amount (€)'
      },
      beginAtZero: true
    },
    y1: {
      type: 'linear',
      display: true,
      position: 'right',
      title: {
        display: true,
        text: 'Count'
      },
      beginAtZero: true,
      grid: {
        drawOnChartArea: false,
      },
    }
  }
}

// Commission status chart data
const commissionStatusChartData = computed(() => {
  console.log('🔍 DEBUG: commissionStatusChartData computed called')
  console.log('🔍 DEBUG: commissionStatusChart.value', commissionStatusChart.value)

  const data = {
    labels: commissionStatusChart.value?.labels || ['Pending', 'Approved', 'Paid', 'Rejected', 'Cancelled', 'Disputed'],
    datasets: [
      {
        data: commissionStatusChart.value?.data || [30, 45, 100, 15, 5, 8],
        backgroundColor: [
          'rgba(245, 158, 11, 0.8)',   // yellow for pending
          'rgba(59, 130, 246, 0.8)',    // blue for approved
          'rgba(16, 185, 129, 0.8)',    // green for paid
          'rgba(239, 68, 68, 0.8)',     // red for rejected
          'rgba(107, 114, 128, 0.8)',   // gray for cancelled
          'rgba(255, 159, 64, 0.8)'     // orange for disputed
        ],
        borderColor: [
          'rgb(245, 158, 11)',
          'rgb(59, 130, 246)',
          'rgb(16, 185, 129)',
          'rgb(239, 68, 68)',
          'rgb(107, 114, 128)',
          'rgb(255, 159, 64)'
        ],
        borderWidth: 1
      }
    ]
  }

  console.log('🔍 DEBUG: final chart data', data)
  return data
})

const commissionStatusChartOptions = {
  ...chartOptions,
  plugins: {
    ...chartOptions.plugins,
    legend: {
      display: false
    }
  }
}

// Top affiliates chart data
const topAffiliatesChartData = computed(() => ({
  labels: topAffiliatesChart.value?.labels || [],
  datasets: [
    {
      label: 'Commission Earned',
      data: topAffiliatesChart.value?.commissions || [],
      backgroundColor: 'rgba(139, 92, 246, 0.8)',
      borderColor: 'rgb(139, 92, 246)',
      borderWidth: 1
    }
  ]
}))

const topAffiliatesChartOptions = {
  ...chartOptions,
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Affiliate'
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: 'Commission (€)'
      },
      beginAtZero: true
    }
  }
}

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

// Computed properties
const filteredCommissions = computed(() => {
  let filtered = commissions.value

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(commission => commission.status === statusFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(commission =>
      commission.business_name.toLowerCase().includes(query) ||
      commission.affiliate_name.toLowerCase().includes(query) ||
      commission.qr_code_id.toLowerCase().includes(query)
    )
  }

  return filtered
})

const formattedStats = computed(() => {
  const stats = statistics.value.overview || {}
  return {
    totalCommissions: stats.total_commissions || 0,
    pendingCommissions: stats.pending_commissions || 0,
    approvedCommissions: stats.approved_commissions || 0,
    paidCommissions: stats.paid_commissions || 0,
    totalAmount: stats.total_amount || 0,
    averageCommission: stats.average_commission || 0,
    monthlyGrowth: statistics.value.monthly_growth || 0,
    topAffiliates: statistics.value.top_affiliates || []
  }
})

// Load commissions data
const loadCommissions = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/admin/affiliate/commissions-data', {
      params: {
        page,
        per_page: pagination.value.per_page,
        date_range: dateRange.value,
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
        search: searchQuery.value
      }
    })

    commissions.value = response.data.data
    pagination.value = response.data.pagination
  } catch (error) {
    console.error('Error loading commissions:', error)
    showNotification('Error loading commissions', 'error')
  } finally {
    loading.value = false
  }
}

// Load statistics
const loadStatistics = async () => {
  try {
    const response = await axios.get('/admin/affiliate/commission-statistics', {
      params: { date_range: dateRange.value }
    })
    statistics.value = response.data

    // Load chart data
    loadChartData()
  } catch (error) {
    console.error('Error loading statistics:', error)
    showNotification('Error loading statistics', 'error')
  }
}

// Load chart data
const loadChartData = () => {
  // Use real data from statistics instead of mock data
  if (statistics.value.overview) {
    const stats = statistics.value.overview
    const statusDistribution = statistics.value.status_distribution || {}

    // Commission status chart data - use real status distribution from controller
    commissionStatusChart.value = {
      labels: ['Pending', 'Approved', 'Paid', 'Rejected', 'Cancelled', 'Disputed'],
      data: [
        statusDistribution.pending || stats.pending_commissions || 0,
        statusDistribution.approved || stats.approved_commissions || 0,
        statusDistribution.paid || stats.paid_commissions || 0,
        statusDistribution.rejected || stats.rejected_commissions || 0,
        statusDistribution.cancelled || 0,
        statusDistribution.disputed || stats.disputed_commissions || 0
      ]
    }

    // Top affiliates chart data - use real top affiliates data from controller
    if (statistics.value.top_affiliates && statistics.value.top_affiliates.length > 0) {
      const topAffiliates = statistics.value.top_affiliates.slice(0, 10)
      topAffiliatesChart.value = {
        labels: topAffiliates.map(affiliate => affiliate.name || 'N/A'),
        commissions: topAffiliates.map(affiliate => affiliate.total_commissions || 0)
      }
    }

    const labels = statistics.value.chart_labels || []
    const commissionData = statistics.value.commission_data || []
    const commissionCountData = statistics.value.commission_count_data || []
    const revenueData = statistics.value.revenue_data || []

    commissionTrendChart.value = {
      labels: labels,
      amounts: revenueData,
      counts: commissionCountData
    }
  }
}

// Update commission status
const updateCommissionStatus = async (commissionId, newStatus) => {
  try {
    await axios.patch(`/admin/affiliate/commissions/${commissionId}/status`, {
      status: newStatus
    })

    showNotification('Commission status updated successfully', 'success')
    loadCommissions(pagination.value.current_page)
    loadStatistics()
  } catch (error) {
    console.error('Error updating commission status:', error)
    showNotification('Error updating commission status', 'error')
  }
}

// Process payment
const processPayment = async (commissionId) => {
  try {
    await axios.post(`/admin/affiliate/commissions/${commissionId}/pay`)

    showNotification('Payment processed successfully', 'success')
    loadCommissions(pagination.value.current_page)
    loadStatistics()
  } catch (error) {
    console.error('Error processing payment:', error)
    showNotification('Error processing payment', 'error')
  }
}

// Export commissions
const exportCommissions = async () => {
  try {
    const response = await axios.get('/admin/affiliate/commissions-export', {
      params: {
        date_range: dateRange.value,
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
        search: searchQuery.value
      },
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `commissions-${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    showNotification('Commissions exported successfully', 'success')
  } catch (error) {
    console.error('Error exporting commissions:', error)
    showNotification('Error exporting commissions', 'error')
  }
}

// Format currency
const formatCurrency = (amount, currency = 'EUR') => {
  // Convert currency symbols to proper currency codes
  const currencyMap = {
    '₹': 'INR',
    '$': 'USD',
    '€': 'EUR',
    '£': 'GBP',
    '¥': 'JPY',
    '₩': 'KRW',
    '₽': 'RUB',
    '₴': 'UAH',
    '₸': 'KZT',
    '₼': 'AZN',
    '₺': 'TRY',
    '₨': 'PKR',
    '৳': 'BDT',
    '₡': 'CRC',
    '¢': 'CLP',
    '₱': 'PHP',
    '₲': 'PYG',
    '₦': 'NGN',
    '₵': 'GHS'
  }

  // If currency is a single character (symbol), convert to code
  let currencyCode = currency
  if (currency && currency.length === 1 && currencyMap[currency]) {
    currencyCode = currencyMap[currency]
  }

  // Fallback to EUR if invalid currency code
  try {
    const formatter = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
    return formatter.format(amount)
  } catch (error) {
    console.warn(`Invalid currency code: ${currencyCode}, falling back to EUR`)
    const fallbackFormatter = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
    return fallbackFormatter.format(amount)
  }
}

// Format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Get status badge class
const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    disputed: 'bg-orange-100 text-orange-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Get status icon
const getStatusIcon = (status) => {
  const icons = {
    pending: Clock,
    approved: CheckCircle,
    paid: CheckCircle,
    rejected: XCircle,
    disputed: AlertCircle
  }
  return icons[status] || AlertCircle
}

// Get trend icon
const getTrendIcon = (trend) => {
  return trend > 0 ? ArrowUpCircle : ArrowDownCircle
}

// Get trend color
const getTrendColor = (trend) => {
  return trend > 0 ? 'text-green-600' : 'text-red-600'
}

// View commission details
const viewCommissionDetails = (commission) => {
  selectedCommission.value = commission
  showDetailsModal.value = true
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
    background: ${type === 'success' ? '#10b981' : '#ef4444'};
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  `
  document.body.appendChild(notification)
  setTimeout(() => notification.remove(), 3000)
}

// Load on mount
onMounted(() => {
  loadCommissions()
  loadStatistics()
})
</script>

<template>
  <Head title="Commission Management" />
  <AdminDashboardLayout>
    <div class="commission-management">
      <!-- Header -->
      <div class="header">
        <div class="header-content">
          <h1>Commission Management</h1>
          <p>Manage affiliate commissions, payments, and tracking</p>
        </div>
        <div class="header-actions">
          <button @click="exportCommissions" class="btn-export">
            <Download />
            Export
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-section">
        <div class="filters-left">
          <div class="search-box">
            <Search />
            <input
              v-model="searchQuery"
              @input="loadCommissions(1)"
              type="text"
              placeholder="Search by business, affiliate, or QR code..."
              class="search-input"
            />
          </div>
          <select v-model="statusFilter" @change="loadCommissions(1)" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
            <option value="rejected">Rejected</option>
            <option value="disputed">Disputed</option>
          </select>
          <select v-model="dateRange" @change="loadCommissions(); loadStatistics()" class="filter-select">
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="1y">Last Year</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading commission data...</p>
      </div>

      <!-- Main Content -->
      <div v-else class="commissions-content">
        <!-- Statistics Cards -->
        <div class="stats-grid">
          <div class="stat-card primary">
            <div class="stat-icon">
              <DollarSign />
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(formattedStats.totalAmount) }}</h3>
              <p>Total Commissions</p>
              <div class="stat-trend" :class="getTrendColor(formattedStats.monthlyGrowth)">
                <component :is="getTrendIcon(formattedStats.monthlyGrowth)" />
                {{ Math.abs(formattedStats.monthlyGrowth).toFixed(1) }}%
              </div>
            </div>
          </div>

          <div class="stat-card success">
            <div class="stat-icon">
              <CheckCircle />
            </div>
            <div class="stat-content">
              <h3>{{ formattedStats.paidCommissions }}</h3>
              <p>Paid Commissions</p>
              <div class="stat-subtext">Successfully processed</div>
            </div>
          </div>

          <div class="stat-card warning">
            <div class="stat-icon">
              <Clock />
            </div>
            <div class="stat-content">
              <h3>{{ formattedStats.pendingCommissions }}</h3>
              <p>Pending Approval</p>
              <div class="stat-subtext">Requires attention</div>
            </div>
          </div>

          <div class="stat-card info">
            <div class="stat-icon">
              <TrendingUp />
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(formattedStats.averageCommission) }}</h3>
              <p>Average Commission</p>
              <div class="stat-subtext">Per transaction</div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
          <div class="chart-container">
            <div class="chart-header">
              <h3>Commission Trends</h3>
              <BarChart3 class="chart-icon" />
            </div>
            <div class="chart-wrapper">
              <Line
                :data="commissionTrendChartData"
                :options="commissionTrendChartOptions"
                v-if="!loading"
              />
            </div>
          </div>

          <div class="chart-container">
            <div class="chart-header">
              <h3>Commission Status Distribution</h3>
              <PieChart class="chart-icon" />
            </div>
            <div class="chart-wrapper">
              <div class="chart-wrapper">
              <Doughnut
                :data="commissionStatusChartData"
                :options="commissionStatusChartOptions"
              />
            </div>
            </div>
          </div>
        </div>

        <!-- Top Affiliates Chart -->
        <div class="chart-section">
          <div class="chart-header">
            <h3>Top Performing Affiliates</h3>
            <TrendingUp class="chart-icon" />
          </div>
          <div class="chart-wrapper">
            <Bar
              :data="topAffiliatesChartData"
              :options="topAffiliatesChartOptions"
              v-if="!loading"
            />
          </div>
        </div>

        <!-- Commissions Table -->
        <div class="commissions-table-section">
          <div class="table-header">
            <h3>Commission Transactions</h3>
            <div class="table-stats">
              <span>{{ pagination.total }} total commissions</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="commissions-table">
              <thead>
                <tr>
                  <th>Commission ID</th>
                  <th>Business</th>
                  <th>Affiliate</th>
                  <th>QR Code</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Created Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="commission in filteredCommissions" :key="commission.id">
                  <td>
                    <span class="commission-id">#{{ commission.id }}</span>
                  </td>
                  <td>
                    <div class="business-info">
                      <strong>{{ commission.business_name }}</strong>
                      <small>{{ commission.business_type }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="affiliate-info">
                      <strong>{{ commission.affiliate_name }}</strong>
                      <small>{{ commission.affiliate_email }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="qr-code-id">{{ commission.qr_code_id }}</span>
                  </td>
                  <td>
                    <span class="amount">{{ formatCurrency(commission.amount, commission.currency) }}</span>
                  </td>
                  <td>
                    <span :class="['status-badge', getStatusBadgeClass(commission.status)]">
                      <component :is="getStatusIcon(commission.status)" />
                      {{ commission.status }}
                    </span>
                  </td>
                  <td>
                    <span class="date">{{ formatDate(commission.created_at) }}</span>
                  </td>
                  <td>
                    <div class="actions-dropdown">
                      <button class="actions-trigger">
                        <MoreVertical />
                      </button>
                      <div class="actions-menu">
                        <button @click="viewCommissionDetails(commission)" class="action-btn">
                          <Eye />
                          View Details
                        </button>
                        <button
                          v-if="commission.status === 'pending'"
                          @click="updateCommissionStatus(commission.id, 'approved')"
                          class="action-btn"
                        >
                          <CheckCircle />
                          Approve
                        </button>
                        <button
                          v-if="commission.status === 'approved'"
                          @click="processPayment(commission.id)"
                          class="action-btn"
                        >
                          <DollarSign />
                          Process Payment
                        </button>
                        <button
                          v-if="commission.status === 'pending'"
                          @click="updateCommissionStatus(commission.id, 'rejected')"
                          class="action-btn danger"
                        >
                          <XCircle />
                          Reject
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredCommissions.length === 0">
                  <td colspan="8" class="no-data">
                    No commissions found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="pagination">
            <button
              v-for="page in pagination.last_page"
              :key="page"
              @click="loadCommissions(page)"
              :class="['page-btn', { active: page === pagination.current_page }]"
            >
              {{ page }}
            </button>
          </div>
        </div>

        <!-- Top Affiliates -->
        <div class="top-affiliates-section">
          <div class="section-header">
            <h3>Top Performing Affiliates</h3>
            <BarChart3 class="section-icon" />
          </div>
          <div class="affiliates-list">
            <div v-for="affiliate in formattedStats.topAffiliates" :key="affiliate.id" class="affiliate-card">
              <div class="affiliate-rank">#{{ affiliate.rank }}</div>
              <div class="affiliate-info">
                <h4>{{ affiliate.name }}</h4>
                <p>{{ affiliate.email }}</p>
              </div>
              <div class="affiliate-stats">
                <div class="stat">
                  <span class="label">Total Commissions:</span>
                  <span class="value">{{ formatCurrency(affiliate.total_commissions) }}</span>
                </div>
                <div class="stat">
                  <span class="label">Transactions:</span>
                  <span class="value">{{ affiliate.total_transactions }}</span>
                </div>
                <div class="stat">
                  <span class="label">Conversion Rate:</span>
                  <span class="value">{{ (affiliate.conversion_rate * 100).toFixed(2) }}%</span>
                </div>
              </div>
            </div>
            <div v-if="formattedStats.topAffiliates.length === 0" class="no-data">
              <p>No affiliate performance data available</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Commission Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="showDetailsModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Commission Details</h3>
          <button @click="showDetailsModal = false" class="modal-close">
            <XCircle />
          </button>
        </div>
        <div v-if="selectedCommission" class="modal-body">
          <div class="detail-row">
            <span class="label">Commission ID:</span>
            <span class="value">#{{ selectedCommission.id }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Amount:</span>
            <span class="value">{{ formatCurrency(selectedCommission.amount, selectedCommission.currency) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Business:</span>
            <span class="value">{{ selectedCommission.business_name }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Affiliate:</span>
            <span class="value">{{ selectedCommission.affiliate_name }}</span>
          </div>
          <div class="detail-row">
            <span class="label">QR Code:</span>
            <span class="value">{{ selectedCommission.qr_code_id }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Status:</span>
            <span :class="['status-badge', getStatusBadgeClass(selectedCommission.status)]">
              <component :is="getStatusIcon(selectedCommission.status)" />
              {{ selectedCommission.status }}
            </span>
          </div>
          <div class="detail-row">
            <span class="label">Created Date:</span>
            <span class="value">{{ formatDate(selectedCommission.created_at) }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showDetailsModal = false" class="btn-secondary">
            Close
          </button>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style scoped>
.commission-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.chart-wrapper {
  height: 300px;
  position: relative;
  width: 100%;
}

.chart-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.chart-section .chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.chart-section .chart-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.chart-section .chart-icon {
  color: #6b7280;
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

.btn-export {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #059669;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-export:hover {
  background: #047857;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
}

.filters-left {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-box svg {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #6b7280;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  font-size: 0.875rem;
  min-width: 150px;
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

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card.primary {
  border-left: 4px solid #3b82f6;
}

.stat-card.success {
  border-left: 4px solid #10b981;
}

.stat-card.warning {
  border-left: 4px solid #f59e0b;
}

.stat-card.info {
  border-left: 4px solid #6366f1;
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
  font-size: 1.875rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.stat-content p {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.stat-trend svg {
  width: 16px;
  height: 16px;
}

.stat-subtext {
  font-size: 0.75rem;
  color: #9ca3af;
}

.commissions-table-section, .top-affiliates-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.table-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.table-stats {
  color: #6b7280;
  font-size: 0.875rem;
}

.table-responsive {
  overflow-x: auto;
}

.commissions-table {
  width: 100%;
  border-collapse: collapse;
}

.commissions-table th {
  text-align: left;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.commissions-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.commissions-table tr:hover {
  background: #f9fafb;
}

.commission-id {
  font-family: monospace;
  font-size: 0.875rem;
  color: #6b7280;
}

.business-info, .affiliate-info {
  display: flex;
  flex-direction: column;
}

.business-info strong, .affiliate-info strong {
  font-size: 0.875rem;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.business-info small, .affiliate-info small {
  font-size: 0.75rem;
  color: #6b7280;
}

.qr-code-id {
  font-family: monospace;
  font-size: 0.875rem;
  color: #3b82f6;
}

.amount {
  font-weight: 600;
  color: #10b981;
  font-size: 0.875rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge svg {
  width: 14px;
  height: 14px;
}

.date {
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
}

.actions-dropdown {
  position: relative;
}

.actions-trigger {
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 4px;
  transition: background 0.2s;
}

.actions-trigger:hover {
  background: #f3f4f6;
}

.actions-menu {
  position: absolute;
  right: 0;
  top: 100%;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 50;
  min-width: 180px;
  display: none;
}

.actions-dropdown:hover .actions-menu {
  display: block;
}

.action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: none;
  background: transparent;
  text-align: left;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  transition: background 0.2s;
}

.action-btn:hover {
  background: #f9fafb;
}

.action-btn.danger {
  color: #dc2626;
}

.action-btn svg {
  width: 16px;
  height: 16px;
}

.no-data {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
  font-style: italic;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.page-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.page-btn:hover {
  background: #f9fafb;
}

.page-btn.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.section-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.section-icon {
  color: #6b7280;
}

.affiliates-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.affiliate-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  transition: border-color 0.2s;
}

.affiliate-card:hover {
  border-color: #d1d5db;
}

.affiliate-rank {
  width: 40px;
  height: 40px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
}

.affiliate-info h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.affiliate-info p {
  color: #6b7280;
  font-size: 0.875rem;
}

.affiliate-stats {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.affiliate-stats .stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.affiliate-stats .label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.affiliate-stats .value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

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
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.modal-close {
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
}

.modal-body {
  padding: 1.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row .label {
  font-size: 0.875rem;
  color: #6b7280;
}

.detail-row .value {
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 600;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #f9fafb;
}

@media (max-width: 768px) {
  .commission-management {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-left {
    flex-direction: column;
  }

  .search-box {
    min-width: auto;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .affiliate-card {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .affiliate-stats {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .commissions-table {
    font-size: 0.75rem;
  }

  .commissions-table th,
  .commissions-table td {
    padding: 0.5rem;
  }

  .actions-menu {
    right: -50px;
  }
}
</style>