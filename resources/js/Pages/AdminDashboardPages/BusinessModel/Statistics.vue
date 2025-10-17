<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import {
  Building,
  Users,
  CreditCard,
  TrendingUp,
  TrendingDown,
  MapPin,
  QrCode,
  Calendar,
  DollarSign,
  Activity,
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  BarChart3,
  PieChart,
  Eye
} from 'lucide-vue-next'

// Reactive data
const loading = ref(true)
const statistics = ref({
  overview: {},
  charts: {},
  recent: []
})
const dateRange = ref('7d')
const selectedBusiness = ref('all')

// Computed properties
const formattedStats = computed(() => {
  const stats = statistics.value.overview || {}
  return {
    totalBusinesses: stats.total_businesses || 0,
    activeBusinesses: stats.active_businesses || 0,
    pendingVerification: stats.pending_verification || 0,
    totalQrCodes: stats.total_qr_codes || 0,
    totalScans: stats.total_scans || 0,
    totalRevenue: stats.total_revenue || 0,
    totalCommissions: stats.total_commissions || 0,
    pendingPayouts: stats.pending_payouts || 0,
    avgConversionRate: stats.avg_conversion_rate || 0,
    topPerformers: stats.top_performers || [],
    recentGrowth: stats.recent_growth || {}
  }
})

// Load statistics data
const loadStatistics = async () => {
  loading.value = true
  try {
    const response = await axios.get('/admin/affiliate/statistics', {
      params: {
        date_range: dateRange.value,
        business_id: selectedBusiness.value !== 'all' ? selectedBusiness.value : null
      }
    })
    statistics.value = response.data
  } catch (error) {
    console.error('Error loading statistics:', error)
    showNotification('Error loading statistics', 'error')
  } finally {
    loading.value = false
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

// Format percentage
const formatPercentage = (value) => {
  return `${(value * 100).toFixed(2)}%`
}

// Get trend icon
const getTrendIcon = (trend) => {
  return trend > 0 ? TrendingUp : TrendingDown
}

// Get trend color
const getTrendColor = (trend) => {
  return trend > 0 ? 'text-green-600' : 'text-red-600'
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
  loadStatistics()
})
</script>

<template>
  <Head title="Business Statistics Dashboard" />
  <AdminDashboardLayout>
    <div class="business-statistics">
      <!-- Header -->
      <div class="header">
        <div class="header-content">
          <h1>Business Statistics Dashboard</h1>
          <p>Comprehensive overview of affiliate business performance and analytics</p>
        </div>
        <div class="header-filters">
          <select v-model="dateRange" @change="loadStatistics" class="filter-select">
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="1y">Last Year</option>
          </select>
          <select v-model="selectedBusiness" @change="loadStatistics" class="filter-select">
            <option value="all">All Businesses</option>
            <!-- Will be populated dynamically -->
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading statistics...</p>
      </div>

      <!-- Main Content -->
      <div v-else class="statistics-content">
        <!-- Key Metrics Cards -->
        <div class="metrics-grid">
          <div class="metric-card primary">
            <div class="metric-icon">
              <Building />
            </div>
            <div class="metric-content">
              <h3>{{ formattedStats.totalBusinesses }}</h3>
              <p>Total Businesses</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.businesses || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.businesses || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.businesses || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card success">
            <div class="metric-icon">
              <CheckCircle />
            </div>
            <div class="metric-content">
              <h3>{{ formattedStats.activeBusinesses }}</h3>
              <p>Active Businesses</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.active || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.active || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.active || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card warning">
            <div class="metric-icon">
              <Clock />
            </div>
            <div class="metric-content">
              <h3>{{ formattedStats.pendingVerification }}</h3>
              <p>Pending Verification</p>
              <div class="metric-subtext">Requires admin attention</div>
            </div>
          </div>

          <div class="metric-card info">
            <div class="metric-icon">
              <QrCode />
            </div>
            <div class="metric-content">
              <h3>{{ formattedStats.totalQrCodes }}</h3>
              <p>Total QR Codes</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.qr_codes || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.qr_codes || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.qr_codes || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card primary">
            <div class="metric-icon">
              <Eye />
            </div>
            <div class="metric-content">
              <h3>{{ formattedStats.totalScans.toLocaleString() }}</h3>
              <p>Total Scans</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.scans || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.scans || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.scans || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card success">
            <div class="metric-icon">
              <DollarSign />
            </div>
            <div class="metric-content">
              <h3>{{ formatCurrency(formattedStats.totalRevenue) }}</h3>
              <p>Total Revenue</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.revenue || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.revenue || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.revenue || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card info">
            <div class="metric-icon">
              <CreditCard />
            </div>
            <div class="metric-content">
              <h3>{{ formatCurrency(formattedStats.totalCommissions) }}</h3>
              <p>Total Commissions</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.commissions || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.commissions || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.commissions || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card warning">
            <div class="metric-icon">
              <AlertCircle />
            </div>
            <div class="metric-content">
              <h3>{{ formatCurrency(formattedStats.pendingPayouts) }}</h3>
              <p>Pending Payouts</p>
              <div class="metric-subtext">Awaiting approval</div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
          <div class="chart-container">
            <div class="chart-header">
              <h3>Revenue Overview</h3>
              <BarChart3 class="chart-icon" />
            </div>
            <div class="chart-placeholder">
              <p>Revenue chart will be displayed here</p>
              <small>Integration with charting library needed</small>
            </div>
          </div>

          <div class="chart-container">
            <div class="chart-header">
              <h3>Conversion Rates</h3>
              <PieChart class="chart-icon" />
            </div>
            <div class="chart-placeholder">
              <p>Conversion analytics will be displayed here</p>
              <small>Average: {{ formatPercentage(formattedStats.avgConversionRate) }}</small>
            </div>
          </div>
        </div>

        <!-- Top Performers -->
        <div class="performers-section">
          <div class="section-header">
            <h3>Top Performing Businesses</h3>
            <TrendingUp class="section-icon" />
          </div>
          <div class="performers-list">
            <div v-for="(business, index) in formattedStats.topPerformers" :key="business.id" class="performer-card">
              <div class="performer-rank">#{{ index + 1 }}</div>
              <div class="performer-info">
                <h4>{{ business.name }}</h4>
                <p>{{ business.business_type }}</p>
              </div>
              <div class="performer-stats">
                <div class="stat">
                  <span class="label">Revenue:</span>
                  <span class="value">{{ formatCurrency(business.total_revenue) }}</span>
                </div>
                <div class="stat">
                  <span class="label">Scans:</span>
                  <span class="value">{{ business.total_scans.toLocaleString() }}</span>
                </div>
                <div class="stat">
                  <span class="label">Conversion:</span>
                  <span class="value">{{ formatPercentage(business.conversion_rate) }}</span>
                </div>
              </div>
              <div class="performer-actions">
                <Link :href="`/admin/affiliate/business-details/${business.id}`" class="btn-view">
                  <Eye />
                  View Details
                </Link>
              </div>
            </div>
            <div v-if="formattedStats.topPerformers.length === 0" class="no-data">
              <p>No performance data available</p>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-section">
          <div class="section-header">
            <h3>Recent Activity</h3>
            <Activity class="section-icon" />
          </div>
          <div class="activity-list">
            <div v-for="activity in statistics.value.recent" :key="activity.id" class="activity-item">
              <div class="activity-icon" :class="activity.type">
                <component :is="getActivityIcon(activity.type)" />
              </div>
              <div class="activity-content">
                <p>{{ activity.description }}</p>
                <small>{{ formatDate(activity.created_at) }}</small>
              </div>
            </div>
            <div v-if="statistics.value.recent.length === 0" class="no-data">
              <p>No recent activity</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style scoped>
.business-statistics {
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

.header-filters {
  display: flex;
  gap: 1rem;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
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

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.metric-card {
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

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.metric-card.primary {
  border-left: 4px solid #3b82f6;
}

.metric-card.success {
  border-left: 4px solid #10b981;
}

.metric-card.warning {
  border-left: 4px solid #f59e0b;
}

.metric-card.info {
  border-left: 4px solid #6366f1;
}

.metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
}

.metric-icon svg {
  width: 24px;
  height: 24px;
  color: #6b7280;
}

.metric-content h3 {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.metric-content p {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.metric-trend {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.metric-trend svg {
  width: 14px;
  height: 14px;
}

.metric-subtext {
  font-size: 0.75rem;
  color: #9ca3af;
}

.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-container {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.chart-header h3 {
  font-size: 1.125rem;
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

.performers-section, .activity-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
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

.performers-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.performer-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  transition: border-color 0.2s;
}

.performer-card:hover {
  border-color: #d1d5db;
}

.performer-rank {
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

.performer-info h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.performer-info p {
  color: #6b7280;
  font-size: 0.875rem;
}

.performer-stats {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat .label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.stat .value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.btn-view {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border-radius: 6px;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-view:hover {
  background: #2563eb;
}

.btn-view svg {
  width: 16px;
  height: 16px;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
}

.activity-icon.success {
  background: #d1fae5;
  color: #10b981;
}

.activity-icon.warning {
  background: #fed7aa;
  color: #f59e0b;
}

.activity-icon.error {
  background: #fee2e2;
  color: #ef4444;
}

.activity-icon.info {
  background: #dbeafe;
  color: #3b82f6;
}

.activity-content p {
  color: #1f2937;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.activity-content small {
  color: #6b7280;
  font-size: 0.75rem;
}

.no-data {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
  font-style: italic;
}

@media (max-width: 768px) {
  .business-statistics {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-filters {
    flex-direction: column;
  }

  .filter-select {
    width: 100%;
  }

  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .charts-section {
    grid-template-columns: 1fr;
  }

  .performer-card {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .performer-stats {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .activity-item {
    flex-direction: column;
    text-align: center;
  }
}
</style>