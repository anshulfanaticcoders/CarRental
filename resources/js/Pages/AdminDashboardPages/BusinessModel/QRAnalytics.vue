<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import {
  QrCode,
  TrendingUp,
  TrendingDown,
  Calendar,
  BarChart3,
  PieChart,
  Eye,
  Download,
  Search,
  Filter,
  MapPin,
  Users,
  Activity,
  Clock,
  CheckCircle,
  AlertCircle,
  ArrowUpCircle,
  ArrowDownCircle,
  Globe,
  Smartphone,
  Monitor,
  Tablet
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
const qrData = ref({
  overview: {},
  top_performers: [],
  device_stats: {},
  location_stats: [],
  qr_codes: [],
  scan_trends_data: {},
  device_trends_data: {},
  location_trends_data: {},
  conversion_trends_data: {}
})
const dateRange = ref('30d')
const selectedBusiness = ref('all')
const searchQuery = ref('')
const qrCodes = ref([])
const analyticsChart = ref(null)

// Chart data
const scanTrendChart = ref(null)
const deviceUsageChart = ref(null)
const locationPerformanceChart = ref(null)
const conversionRateChart = ref(null)

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

// Scan trend chart data
const scanTrendChartData = computed(() => ({
  labels: scanTrendChart.value?.labels || [],
  datasets: [
    {
      label: 'Total Scans',
      data: scanTrendChart.value?.total_scans || [],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
      fill: true,
    },
    {
      label: 'Unique Scans',
      data: scanTrendChart.value?.unique_scans || [],
      borderColor: 'rgb(16, 185, 129)',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.4,
      fill: true,
    },
    {
      label: 'Conversions',
      data: scanTrendChart.value?.conversions || [],
      borderColor: 'rgb(139, 92, 246)',
      backgroundColor: 'rgba(139, 92, 246, 0.1)',
      tension: 0.4,
      fill: true,
    }
  ]
}))

const scanTrendChartOptions = {
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
      display: true,
      title: {
        display: true,
        text: 'Count'
      },
      beginAtZero: true
    }
  }
}

// Device usage chart data
const deviceUsageChartData = computed(() => {
  const chartData = deviceUsageChart.value?.data || [0, 0, 0, 0]
  const chartLabels = deviceUsageChart.value?.labels || ['Mobile', 'Desktop', 'Tablet', 'Other']

  console.log('🔵 Device Usage Chart data:', chartData)
  console.log('🏷️ Device Usage Chart labels:', chartLabels)
  console.log('📊 Device Usage Chart total:', chartData.reduce((a, b) => a + b, 0))

  // Filter out zero values to avoid Chart.js rendering issues
  const filteredData = []
  const filteredLabels = []
  const filteredColors = []
  const filteredBorders = []

  const colors = [
    'rgba(59, 130, 246, 0.8)', // Mobile - blue
    'rgba(16, 185, 129, 0.8)', // Desktop - green
    'rgba(139, 92, 246, 0.8)', // Tablet - purple
    'rgba(156, 163, 175, 0.8)'  // Other - gray
  ]

  const borders = [
    'rgb(59, 130, 246)',   // Mobile - blue
    'rgb(16, 185, 129)',   // Desktop - green
    'rgb(139, 92, 246)',   // Tablet - purple
    'rgb(156, 163, 175)'    // Other - gray
  ]

  // Only include devices with non-zero values
  chartData.forEach((value, index) => {
    if (value > 0) {
      filteredData.push(value)
      filteredLabels.push(chartLabels[index])
      filteredColors.push(colors[index])
      filteredBorders.push(borders[index])
    }
  })

  // If all values are zero, show a fallback
  if (filteredData.length === 0) {
    console.log('⚠️ All device values are zero, showing fallback chart')
    return {
      labels: ['No Data'],
      datasets: [
        {
          data: [1],
          backgroundColor: ['rgba(156, 163, 175, 0.8)'],
          borderColor: ['rgb(156, 163, 175)'],
          borderWidth: 1
        }
      ]
    }
  }

  console.log('✅ Filtered Device Usage Chart data:', filteredData)
  console.log('✅ Filtered Device Usage Chart labels:', filteredLabels)

  return {
    labels: filteredLabels,
    datasets: [
      {
        data: filteredData,
        backgroundColor: filteredColors,
        borderColor: filteredBorders,
        borderWidth: 1
      }
    ]
  }
})

const deviceUsageChartOptions = {
  ...chartOptions,
  plugins: {
    ...chartOptions.plugins,
    legend: {
      display: false
    }
  }
}

// Location performance chart data
const locationPerformanceChartData = computed(() => ({
  labels: locationPerformanceChart.value?.labels || [],
  datasets: [
    {
      label: 'Scans',
      data: locationPerformanceChart.value?.scans || [],
      backgroundColor: 'rgba(245, 158, 11, 0.8)',
      borderColor: 'rgb(245, 158, 11)',
      borderWidth: 1
    },
    {
      label: 'Conversions',
      data: locationPerformanceChart.value?.conversions || [],
      backgroundColor: 'rgba(239, 68, 68, 0.8)',
      borderColor: 'rgb(239, 68, 68)',
      borderWidth: 1
    }
  ]
}))

const locationPerformanceChartOptions = {
  ...chartOptions,
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Location'
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: 'Count'
      },
      beginAtZero: true
    }
  }
}

// Conversion rate chart data
const conversionRateChartData = computed(() => ({
  labels: conversionRateChart.value?.labels || [],
  datasets: [
    {
      label: 'Conversion Rate (%)',
      data: conversionRateChart.value?.rates || [],
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      tension: 0.4,
      fill: true,
    }
  ]
}))

const conversionRateChartOptions = {
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
      display: true,
      title: {
        display: true,
        text: 'Conversion Rate (%)'
      },
      beginAtZero: true,
      max: 100
    }
  }
}

// Computed properties
const formattedStats = computed(() => {
  const stats = qrData.value.overview || {}
  return {
    totalQrCodes: stats.total_qr_codes || 0,
    totalScans: stats.total_scans || 0,
    uniqueScans: stats.unique_scans || 0,
    conversionRate: stats.conversion_rate || 0,
    topPerformingQr: stats.top_performing_qr || null,
    recentGrowth: stats.recent_growth || {},
    avgScansPerQr: stats.avg_scans_per_qr || 0,
    activeQrCodes: stats.active_qr_codes || 0
  }
})

const deviceBreakdown = computed(() => {
  const stats = qrData.value.device_stats || {}
  return {
    mobile: stats.mobile || 0,
    desktop: stats.desktop || 0,
    tablet: stats.tablet || 0,
    other: stats.other || 0
  }
})

const topQrCodes = computed(() => {
  return qrData.value.top_performers || []
})

const locationAnalytics = computed(() => {
  return qrData.value.location_stats || []
})

// Load QR analytics data
const loadAnalytics = async () => {
  loading.value = true
  try {
    const response = await axios.get('/admin/affiliate/qr-analytics-data', {
      params: {
        date_range: dateRange.value,
        business_id: selectedBusiness.value !== 'all' ? selectedBusiness.value : null,
        search: searchQuery.value
      }
    })

    qrData.value = response.data
    qrCodes.value = response.data.qr_codes || []

    // Load chart data (mock data for now)
    loadChartData()
  } catch (error) {
    console.error('Error loading QR analytics:', error)
    showNotification('Error loading QR analytics', 'error')
  } finally {
    loading.value = false
  }
}

// Load chart data
const loadChartData = () => {
  console.log('🔄 Loading chart data from API response:', qrData.value)

  // Scan trends chart data - use real time-series data from API
  const scanTrendsData = qrData.value.scan_trends_data || {}
  console.log('📊 Scan trends data:', scanTrendsData)

  scanTrendChart.value = {
    labels: scanTrendsData?.labels || [],
    total_scans: scanTrendsData?.total_scans || [],
    unique_scans: scanTrendsData?.unique_scans || [],
    conversions: scanTrendsData?.conversions || []
  }

  // Device usage chart data - use real device stats from API
  const deviceStats = qrData.value.device_stats || {}
  console.log('📱 Device stats:', deviceStats)

  deviceUsageChart.value = {
    labels: ['Mobile', 'Desktop', 'Tablet', 'Other'],
    data: [
      deviceStats.mobile || 0,
      deviceStats.desktop || 0,
      deviceStats.tablet || 0,
      deviceStats.other || 0,
    ]
  }

  // Location performance chart data - use location_stats for simple location comparison
  const locationStats = qrData.value.location_stats || []
  console.log('📍 Location stats data:', locationStats)

  if (locationStats.length > 0) {
    locationPerformanceChart.value = {
      labels: locationStats.map(loc => loc.location),
      scans: locationStats.map(loc => loc.scans),
      conversions: locationStats.map(loc => loc.conversions || 0)
    }
  } else {
    // Fallback if no location data
    locationPerformanceChart.value = {
      labels: ['No location data'],
      scans: [0],
      conversions: [0]
    }
  }

  // Conversion rate trends data - use real conversion rate trends
  const conversionTrendsData = qrData.value.conversion_trends_data || {}
  console.log('📈 Conversion trends data:', conversionTrendsData)

  conversionRateChart.value = {
    labels: conversionTrendsData?.labels || [],
    rates: conversionTrendsData?.rates || []
  }
}

// Helper function to distribute a total value across chart labels
const distributeValueAcrossLabels = (total, count) => {
  if (total === 0 || count === 0) return Array(count).fill(0)

  const baseValue = Math.floor(total / count)
  const remainder = total % count

  return Array.from({length: count}, (_, i) => {
    return baseValue + (i < remainder ? 1 : 0)
  })
}

// Export QR analytics
const exportAnalytics = async () => {
  try {
    const response = await axios.get('/admin/affiliate/qr-analytics-export', {
      params: {
        date_range: dateRange.value,
        business_id: selectedBusiness.value !== 'all' ? selectedBusiness.value : null
      },
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `qr-analytics-${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    showNotification('QR analytics exported successfully', 'success')
  } catch (error) {
    console.error('Error exporting analytics:', error)
    showNotification('Error exporting analytics', 'error')
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

// Format number
const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num)
}

// Format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Get trend icon
const getTrendIcon = (trend) => {
  return trend > 0 ? ArrowUpCircle : ArrowDownCircle
}

// Get trend color
const getTrendColor = (trend) => {
  return trend > 0 ? 'text-green-600' : 'text-red-600'
}

// Get device icon
const getDeviceIcon = (device) => {
  const icons = {
    mobile: Smartphone,
    desktop: Monitor,
    tablet: Tablet,
    other: Globe
  }
  return icons[device] || Globe
}

// Get device color
const getDeviceColor = (device) => {
  const colors = {
    mobile: 'bg-blue-500',
    desktop: 'bg-green-500',
    tablet: 'bg-purple-500',
    other: 'bg-gray-500'
  }
  return colors[device] || 'bg-gray-500'
}

// Get performance badge class
const getPerformanceBadgeClass = (performance) => {
  const classes = {
    excellent: 'bg-green-100 text-green-800',
    good: 'bg-blue-100 text-blue-800',
    average: 'bg-yellow-100 text-yellow-800',
    poor: 'bg-red-100 text-red-800'
  }
  return classes[performance] || 'bg-gray-100 text-gray-800'
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
  loadAnalytics()
})
</script>

<template>
  <Head title="QR Code Analytics" />
  <AdminDashboardLayout>
    <div class="qr-analytics">
      <!-- Header -->
      <div class="header">
        <div class="header-content">
          <h1>QR Code Analytics</h1>
          <p>Comprehensive QR code performance tracking and analytics</p>
        </div>
        <div class="header-actions">
          <button @click="exportAnalytics" class="btn-export">
            <Download />
            Export Analytics
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
              @input="loadAnalytics"
              type="text"
              placeholder="Search QR codes or businesses..."
              class="search-input"
            />
          </div>
          <select v-model="dateRange" @change="loadAnalytics" class="filter-select">
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="1y">Last Year</option>
          </select>
          <select v-model="selectedBusiness" @change="loadAnalytics" class="filter-select">
            <option value="all">All Businesses</option>
            <!-- Will be populated dynamically -->
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading QR analytics...</p>
      </div>

      <!-- Main Content -->
      <div v-else class="analytics-content">
        <!-- Key Metrics Cards -->
        <div class="metrics-grid">
          <div class="metric-card primary">
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

          <div class="metric-card success">
            <div class="metric-icon">
              <Eye />
            </div>
            <div class="metric-content">
              <h3>{{ formatNumber(formattedStats.totalScans) }}</h3>
              <p>Total Scans</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.scans || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.scans || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.scans || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card info">
            <div class="metric-icon">
              <Users />
            </div>
            <div class="metric-content">
              <h3>{{ formatNumber(formattedStats.uniqueScans) }}</h3>
              <p>Unique Scans</p>
              <div class="metric-subtext">Different users</div>
            </div>
          </div>

          <div class="metric-card warning">
            <div class="metric-icon">
              <TrendingUp />
            </div>
            <div class="metric-content">
              <h3>{{ formatPercentage(formattedStats.conversionRate) }}</h3>
              <p>Conversion Rate</p>
              <div class="metric-subtext">Scans to bookings</div>
            </div>
          </div>

          <div class="metric-card primary">
            <div class="metric-icon">
              <Activity />
            </div>
            <div class="metric-content">
              <h3>{{ formatNumber(formattedStats.avgScansPerQr) }}</h3>
              <p>Avg Scans per QR</p>
              <div class="metric-trend" :class="getTrendColor(formattedStats.recentGrowth.avg_scans || 0)">
                <component :is="getTrendIcon(formattedStats.recentGrowth.avg_scans || 0)" />
                {{ formatPercentage(formattedStats.recentGrowth.avg_scans || 0) }}
              </div>
            </div>
          </div>

          <div class="metric-card success">
            <div class="metric-icon">
              <CheckCircle />
            </div>
            <div class="metric-content">
              <h3>{{ formattedStats.activeQrCodes }}</h3>
              <p>Active QR Codes</p>
              <div class="metric-subtext">Currently in use</div>
            </div>
          </div>
        </div>

        <!-- Device Analytics -->
        <div class="analytics-grid">
          <div class="analytics-card">
            <div class="card-header">
              <h3>Device Analytics</h3>
              <Smartphone class="card-icon" />
            </div>
            <div class="device-stats">
              <div v-for="(count, device) in deviceBreakdown" :key="device" class="device-item">
                <div class="device-icon" :class="getDeviceColor(device)">
                  <component :is="getDeviceIcon(device)" />
                </div>
                <div class="device-info">
                  <span class="device-name">{{ device.charAt(0).toUpperCase() + device.slice(1) }}</span>
                  <span class="device-count">{{ formatNumber(count) }} scans</span>
                </div>
                <div class="device-percentage">
                  {{ formatPercentage(count / formattedStats.totalScans) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Top Performing QR Codes -->
          <div class="analytics-card">
            <div class="card-header">
              <h3>Top Performing QR Codes</h3>
              <BarChart3 class="card-icon" />
            </div>
            <div class="qr-list">
              <div v-for="qr in topQrCodes.slice(0, 5)" :key="qr.id" class="qr-item">
                <div class="qr-info">
                  <span class="qr-id">{{ qr.qr_code_id }}</span>
                  <span class="qr-business">{{ qr.business_name }}</span>
                </div>
                <div class="qr-stats">
                  <span class="qr-scans">{{ formatNumber(qr.total_scans) }} scans</span>
                  <span class="qr-conversions">{{ formatNumber(qr.conversions) }} conv.</span>
                </div>
                <div class="qr-performance">
                  <span :class="['performance-badge', getPerformanceBadgeClass(qr.performance)]">
                    {{ qr.performance }}
                  </span>
                </div>
              </div>
              <div v-if="topQrCodes.length === 0" class="no-data">
                <p>No QR code performance data available</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Location Analytics -->
        <div class="location-analytics">
          <div class="card-header">
            <h3>Location Analytics</h3>
            <MapPin class="card-icon" />
          </div>
          <div class="location-grid">
            <div v-for="location in locationAnalytics" :key="location.location" class="location-card">
              <div class="location-header">
                <MapPin class="location-icon" />
                <div class="location-info">
                  <h4>{{ location.location }}</h4>
                  <span class="location-country">{{ location.country }}</span>
                </div>
              </div>
              <div class="location-stats">
                <div class="stat">
                  <span class="label">Scans:</span>
                  <span class="value">{{ formatNumber(location.scans) }}</span>
                </div>
                <div class="stat">
                  <span class="label">Unique:</span>
                  <span class="value">{{ formatNumber(location.unique_scans) }}</span>
                </div>
                <div class="stat">
                  <span class="label">Conversions:</span>
                  <span class="value">{{ formatNumber(location.conversions) }}</span>
                </div>
              </div>
            </div>
            <div v-if="locationAnalytics.length === 0" class="no-data">
              <p>No location data available</p>
            </div>
          </div>
        </div>

        <!-- QR Codes Table -->
        <div class="qr-table-section">
          <div class="table-header">
            <h3>All QR Codes</h3>
            <div class="table-stats">
              <span>{{ qrCodes.length }} QR codes</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="qr-table">
              <thead>
                <tr>
                  <th>QR Code ID</th>
                  <th>Business</th>
                  <th>Total Scans</th>
                  <th>Unique Scans</th>
                  <th>Conversions</th>
                  <th>Conversion Rate</th>
                  <th>Performance</th>
                  <th>Created Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="qr in qrCodes" :key="qr.id">
                  <td>
                    <span class="qr-id">{{ qr.qr_code_id }}</span>
                  </td>
                  <td>
                    <div class="business-info">
                      <strong>{{ qr.business_name }}</strong>
                      <small>{{ qr.business_type }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="scans-count">{{ formatNumber(qr.total_scans) }}</span>
                  </td>
                  <td>
                    <span class="unique-scans">{{ formatNumber(qr.unique_scans) }}</span>
                  </td>
                  <td>
                    <span class="conversions">{{ formatNumber(qr.conversions) }}</span>
                  </td>
                  <td>
                    <span class="conversion-rate">{{ formatPercentage(qr.conversion_rate) }}</span>
                  </td>
                  <td>
                    <span :class="['performance-badge', getPerformanceBadgeClass(qr.performance)]">
                      {{ qr.performance }}
                    </span>
                  </td>
                  <td>
                    <span class="date">{{ formatDate(qr.created_at) }}</span>
                  </td>
                </tr>
                <tr v-if="qrCodes.length === 0">
                  <td colspan="8" class="no-data">
                    No QR codes found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Scan Trends Over Time -->
        <div class="chart-section">
          <div class="chart-header">
            <h3>Scan Trends Over Time</h3>
            <PieChart class="chart-icon" />
          </div>
          <div class="chart-wrapper">
            <Line
              :data="scanTrendChartData"
              :options="scanTrendChartOptions"
              v-if="!loading"
            />
          </div>
        </div>

        <!-- Device Analytics and Conversion Charts -->
        <div class="analytics-grid">
          <div class="analytics-card">
            <div class="card-header">
              <h3>Device Usage Distribution</h3>
              <Smartphone class="card-icon" />
            </div>
            <div class="chart-wrapper">
              <Doughnut
                :data="deviceUsageChartData"
                :options="deviceUsageChartOptions"
                v-if="!loading"
              />
            </div>
          </div>

          <div class="analytics-card">
            <div class="card-header">
              <h3>Conversion Rate Trends</h3>
              <TrendingUp class="card-icon" />
            </div>
            <div class="chart-wrapper">
              <Line
                :data="conversionRateChartData"
                :options="conversionRateChartOptions"
                v-if="!loading"
              />
            </div>
          </div>
        </div>

        <!-- Location Performance Chart -->
        <div class="chart-section">
          <div class="chart-header">
            <h3>Location Performance</h3>
            <MapPin class="chart-icon" />
          </div>
          <div class="chart-wrapper">
            <Bar
              :data="locationPerformanceChartData"
              :options="locationPerformanceChartOptions"
              v-if="!loading"
            />
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style scoped>
.qr-analytics {
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
  width: 16px;
  height: 16px;
}

.metric-subtext {
  font-size: 0.75rem;
  color: #9ca3af;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.analytics-card, .location-analytics, .qr-table-section, .chart-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.card-header, .table-header, .chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.card-header h3, .table-header h3, .chart-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.card-icon, .chart-icon {
  color: #6b7280;
}

.table-stats {
  color: #6b7280;
  font-size: 0.875rem;
}

.device-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.device-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.device-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.device-icon svg {
  width: 20px;
  height: 20px;
}

.device-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.device-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.device-count {
  font-size: 0.875rem;
  color: #6b7280;
}

.device-percentage {
  font-weight: 600;
  color: #3b82f6;
  font-size: 0.875rem;
}

.qr-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.qr-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  transition: border-color 0.2s;
}

.qr-item:hover {
  border-color: #d1d5db;
}

.qr-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.qr-id {
  font-family: monospace;
  font-weight: 600;
  color: #3b82f6;
  margin-bottom: 0.25rem;
}

.qr-business {
  font-size: 0.875rem;
  color: #6b7280;
}

.qr-stats {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.qr-scans, .qr-conversions {
  font-size: 0.875rem;
  color: #374151;
}

.qr-performance {
  display: flex;
  align-items: center;
}

.performance-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.location-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.location-card {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.location-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.location-icon {
  color: #3b82f6;
  width: 24px;
  height: 24px;
}

.location-info h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.location-country {
  font-size: 0.875rem;
  color: #6b7280;
}

.location-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.location-stats .stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.location-stats .label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.location-stats .value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.table-responsive {
  overflow-x: auto;
}

.qr-table {
  width: 100%;
  border-collapse: collapse;
}

.qr-table th {
  text-align: left;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.qr-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.qr-table tr:hover {
  background: #f9fafb;
}

.qr-id {
  font-family: monospace;
  font-size: 0.875rem;
  color: #3b82f6;
}

.business-info {
  display: flex;
  flex-direction: column;
}

.business-info strong {
  font-size: 0.875rem;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.business-info small {
  font-size: 0.75rem;
  color: #6b7280;
}

.scans-count, .unique-scans, .conversions {
  font-weight: 600;
  color: #10b981;
  font-size: 0.875rem;
}

.conversion-rate {
  font-weight: 600;
  color: #3b82f6;
  font-size: 0.875rem;
}

.date {
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
}

.chart-placeholder {
  height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  border-radius: 8px;
  color: #6b7280;
}

.chart-wrapper {
  height: 300px;
  position: relative;
  width: 100%;
}

.chart-placeholder small {
  margin-top: 0.5rem;
  color: #9ca3af;
}

.no-data {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
  font-style: italic;
}

@media (max-width: 768px) {
  .qr-analytics {
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

  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }

  .location-grid {
    grid-template-columns: 1fr;
  }

  .qr-table {
    font-size: 0.75rem;
  }

  .qr-table th,
  .qr-table td {
    padding: 0.5rem;
  }

  .qr-item {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .location-stats {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
}
</style>