<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import {
  Building,
  User,
  MapPin,
  Mail,
  Phone,
  Globe,
  CreditCard,
  Calendar,
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  QrCode,
  Eye,
  Edit,
  Download,
  ExternalLink,
  TrendingUp,
  TrendingDown,
  BarChart3,
  Users,
  Activity,
  FileText,
  Shield,
  Settings,
  RefreshCw,
  Camera,
  DollarSign
} from 'lucide-vue-next'

// Props
const props = defineProps({
  businessId: {
    type: String,
    required: true
  }
})

// Reactive data
const loading = ref(true)
const business = ref(null)
const statistics = ref({})
const locations = ref([])
const qrCodes = ref([])
const commissions = ref([])
const recentActivity = ref([])
const editingBusiness = ref(false)
const businessForm = ref({})
const showQrModal = ref(false)
const selectedQrCode = ref(null)

// Computed properties
const isVerified = computed(() => business.value?.verification_status === 'verified')
const isActive = computed(() => business.value?.status === 'active')
const totalRevenue = computed(() => commissions.value.reduce((sum, c) => sum + parseFloat(c.booking_amount || 0), 0))
const totalCommissions = computed(() => commissions.value.reduce((sum, c) => sum + parseFloat(c.net_commission || 0), 0))
const avgCommissionRate = computed(() => {
  if (commissions.value.length === 0) return 0
  const totalRate = commissions.value.reduce((sum, c) => sum + parseFloat(c.commission_rate || 0), 0)
  return (totalRate / commissions.value.length).toFixed(2)
})

// Load business details
const loadBusinessDetails = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/admin/affiliate/businesses/${props.businessId}`, {
      params: {
        include: 'businessModel,locations,qrCodes,commissions,customerScans'
      }
    })

    business.value = response.data.business
    locations.value = response.data.locations || []
    qrCodes.value = response.data.qr_codes || []
    commissions.value = response.data.commissions || []
    statistics.value = response.data.statistics || {}
    recentActivity.value = response.data.recent_activity || []

    // Initialize form
    businessForm.value = { ...business.value }
  } catch (error) {
    console.error('Error loading business details:', error)
    showNotification('Error loading business details', 'error')
  } finally {
    loading.value = false
  }
}

// Update business
const updateBusiness = async () => {
  try {
    await axios.put(`/admin/affiliate/businesses/${props.businessId}`, businessForm.value)
    business.value = { ...business.value, ...businessForm.value }
    editingBusiness.value = false
    showNotification('Business updated successfully', 'success')
  } catch (error) {
    console.error('Error updating business:', error)
    showNotification('Error updating business', 'error')
  }
}

// Verify business
const verifyBusiness = async (approve = true) => {
  try {
    await axios.post(`/admin/affiliate/businesses/${props.businessId}/verify`, {
      action: approve ? 'approve' : 'reject'
    })

    if (approve) {
      business.value.verification_status = 'verified'
      business.value.verified_at = new Date().toISOString()
      business.value.status = 'active'
    } else {
      business.value.verification_status = 'rejected'
    }

    showNotification(`Business ${approve ? 'verified' : 'rejected'} successfully`, 'success')
  } catch (error) {
    console.error(`Error ${approve ? 'verifying' : 'rejecting'} business:`, error)
    showNotification(`Error ${approve ? 'verifying' : 'rejecting'} business`, 'error')
  }
}

// Toggle business status
const toggleBusinessStatus = async () => {
  try {
    const newStatus = business.value.status === 'active' ? 'inactive' : 'active'
    await axios.put(`/admin/affiliate/businesses/${props.businessId}`, {
      status: newStatus
    })
    business.value.status = newStatus
    showNotification(`Business ${newStatus}d`, 'success')
  } catch (error) {
    console.error('Error toggling business status:', error)
    showNotification('Error updating business status', 'error')
  }
}

// Get status icon
const getStatusIcon = (status) => {
  switch (status) {
    case 'verified':
    case 'active':
      return CheckCircle
    case 'pending':
      return Clock
    case 'rejected':
    case 'inactive':
      return XCircle
    default:
      return AlertCircle
  }
}

// Get status color
const getStatusColor = (status) => {
  switch (status) {
    case 'verified':
    case 'active':
      return 'text-green-600 bg-green-100'
    case 'pending':
      return 'text-yellow-600 bg-yellow-100'
    case 'rejected':
    case 'inactive':
      return 'text-red-600 bg-red-100'
    default:
      return 'text-gray-600 bg-gray-100'
  }
}

// Get activity icon
const getActivityIcon = (type) => {
  switch (type) {
    case 'success':
      return CheckCircle
    case 'info':
      return FileText
    case 'warning':
      return AlertCircle
    case 'error':
      return XCircle
    default:
      return FileText
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

// QR Modal functions
const viewQrCode = (qrCode) => {
  selectedQrCode.value = qrCode
  showQrModal.value = true
}

const closeQrModal = () => {
  showQrModal.value = false
  selectedQrCode.value = null
}

const getQrCodeImageUrl = (qrCode) => {
  // Just construct full URL from path
  const imagePath = qrCode.qr_image_path
  if (!imagePath) return null

  return `https://my-public-bucket.4tcl8.upcloudobjects.com/${imagePath}`
}

const getQrCodeStatus = (qrCode) => {
  // Check status from database or use default active status
  if (qrCode.status === 'suspended' || qrCode.status === 'revoked') {
    return { text: 'Suspended', class: 'bg-red-100 text-red-800' }
  }
  if (qrCode.status === 'inactive') {
    return { text: 'Inactive', class: 'bg-gray-100 text-gray-800' }
  }
  return { text: 'Active', class: 'bg-green-100 text-green-800' }
}

const getLocationDisplayName = (qrCode) => {
  // The controller loads location relationship, so we can trust accessor
  return qrCode.location_name || qrCode.location?.name || 'General QR Code'
}

const getLocationAddress = (qrCode) => {
  return qrCode.location_address || 'Online'
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

// Export data
const exportData = async () => {
  try {
    const response = await axios.get(`/admin/affiliate/businesses/${props.businessId}/export`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `business-${business.value.name}-${new Date().toISOString().split('T')[0]}.csv`)
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
  loadBusinessDetails()
})
</script>

<template>
  <Head :title="`Business Details - ${business?.name || 'Loading...'}`" />
  <AdminDashboardLayout>
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading business details...</p>
    </div>

    <div v-else-if="business" class="business-details">
      <!-- Header -->
      <div class="header">
        <div class="header-content">
          <div class="business-title">
            <h1>{{ business.name }}</h1>
            <div class="status-badges">
              <div class="status-badge" :class="getStatusColor(business.verification_status)">
                <component :is="getStatusIcon(business.verification_status)" />
                {{ business.verification_status }}
              </div>
              <div class="status-badge" :class="getStatusColor(business.status)">
                <component :is="getStatusIcon(business.status)" />
                {{ business.status }}
              </div>
            </div>
          </div>
          <div class="header-meta">
            <p>{{ business.business_type.replace('_', ' ').toUpperCase() }}</p>
            <p>ID: {{ business.uuid }}</p>
          </div>
        </div>

        <div class="header-actions">
          <button @click="exportData" class="btn-export">
            <Download />
            Export
          </button>
          <a
            :href="`/business/${business.dashboard_access_token}/dashboard`"
            target="_blank"
            class="btn-dashboard"
          >
            <ExternalLink />
            View Dashboard
          </a>
          <button @click="loadBusinessDetails" class="btn-refresh" :disabled="loading">
            <RefreshCw :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>

      <!-- Business Actions -->
      <div class="actions-section">
        <div class="actions-card">
          <h3>Business Actions</h3>
          <div class="action-buttons">
            <button v-if="!isVerified" @click="verifyBusiness(true)" class="btn-verify">
              <CheckCircle />
              Verify Business
            </button>
            <button v-if="!isVerified" @click="verifyBusiness(false)" class="btn-reject">
              <XCircle />
              Reject Business
            </button>
            <button @click="toggleBusinessStatus" class="btn-toggle">
              <Shield />
              {{ isActive ? 'Deactivate' : 'Activate' }}
            </button>
            <button @click="editingBusiness = !editingBusiness" class="btn-edit">
              <Edit />
              {{ editingBusiness ? 'Cancel' : 'Edit' }}
            </button>
          </div>
        </div>

        <!-- Edit Form -->
        <div v-if="editingBusiness" class="edit-form">
          <div class="form-grid">
            <div class="form-group">
              <label>Business Name</label>
              <input v-model="businessForm.name" type="text" class="form-input" />
            </div>
            <div class="form-group">
              <label>Contact Email</label>
              <input v-model="businessForm.contact_email" type="email" class="form-input" />
            </div>
            <div class="form-group">
              <label>Contact Phone</label>
              <input v-model="businessForm.contact_phone" type="tel" class="form-input" />
            </div>
            <div class="form-group">
              <label>Website</label>
              <input v-model="businessForm.website" type="url" class="form-input" />
            </div>
            <div class="form-group full-width">
              <label>Legal Address</label>
              <textarea v-model="businessForm.legal_address" class="form-textarea"></textarea>
            </div>
            <div class="form-group full-width">
              <label>Billing Address</label>
              <textarea v-model="businessForm.billing_address" class="form-textarea"></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button @click="updateBusiness" class="btn-save">Save Changes</button>
            <button @click="editingBusiness = false" class="btn-cancel">Cancel</button>
          </div>
        </div>
      </div>

      <!-- Statistics Overview -->
      <div class="stats-section">
        <div class="stats-grid">
          <div class="stat-card primary">
            <div class="stat-icon">
              <QrCode />
            </div>
            <div class="stat-content">
              <h3>{{ qrCodes.length }}</h3>
              <p>QR Codes</p>
            </div>
          </div>

          <div class="stat-card success">
            <div class="stat-icon">
              <Eye />
            </div>
            <div class="stat-content">
              <h3>{{ statistics.total_scans || 0 }}</h3>
              <p>Total Scans</p>
            </div>
          </div>

          <div class="stat-card info">
            <div class="stat-icon">
              <DollarSign />
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(totalRevenue) }}</h3>
              <p>Total Revenue</p>
            </div>
          </div>

          <div class="stat-card warning">
            <div class="stat-icon">
              <CreditCard />
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(totalCommissions) }}</h3>
              <p>Total Commissions</p>
            </div>
          </div>

          <div class="stat-card success">
            <div class="stat-icon">
              <BarChart3 />
            </div>
            <div class="stat-content">
              <h3>{{ avgCommissionRate }}%</h3>
              <p>Avg Commission Rate</p>
            </div>
          </div>

          <div class="stat-card info">
            <div class="stat-icon">
              <MapPin />
            </div>
            <div class="stat-content">
              <h3>{{ locations.length }}</h3>
              <p>Locations</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Business Information -->
      <div class="info-section">
        <div class="info-card">
          <h3>Business Information</h3>
          <div class="info-grid">
            <div class="info-item">
              <Mail class="icon" />
              <div>
                <label>Contact Email</label>
                <p>{{ business.contact_email }}</p>
              </div>
            </div>

            <div class="info-item">
              <Phone class="icon" />
              <div>
                <label>Contact Phone</label>
                <p>{{ business.contact_phone }}</p>
              </div>
            </div>

            <div class="info-item">
              <Globe class="icon" />
              <div>
                <label>Website</label>
                <a v-if="business.website" :href="business.website" target="_blank" class="link">
                  {{ business.website }}
                  <ExternalLink class="external-icon" />
                </a>
                <p v-else>N/A</p>
              </div>
            </div>

            <div class="info-item">
              <MapPin class="icon" />
              <div>
                <label>Location</label>
                <p>{{ business.city }}, {{ business.country }}</p>
              </div>
            </div>

            <div class="info-item">
              <CreditCard class="icon" />
              <div>
                <label>Currency</label>
                <p>{{ business.currency }}</p>
              </div>
            </div>

            <div class="info-item">
              <Calendar class="icon" />
              <div>
                <label>Registration Date</label>
                <p>{{ formatDate(business.created_at) }}</p>
              </div>
            </div>
          </div>

          <div class="address-section">
            <div class="address-item">
              <h4>Legal Address</h4>
              <p>{{ business.legal_address }}</p>
            </div>

            <div class="address-item" v-if="business.billing_address">
              <h4>Billing Address</h4>
              <p>{{ business.billing_address }}</p>
            </div>

            <div class="address-item" v-if="business.business_registration_number">
              <h4>Registration Number</h4>
              <p>{{ business.business_registration_number }}</p>
            </div>

            <div class="address-item" v-if="business.tax_id">
              <h4>Tax ID</h4>
              <p>{{ business.tax_id }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Locations -->
      <div class="locations-section">
        <div class="section-card">
          <h3>Locations</h3>
          <div class="locations-grid">
            <div v-for="location in locations" :key="location.id" class="location-card">
              <div class="location-header">
                <h4>{{ location.name }}</h4>
                <span class="location-code">{{ location.location_code }}</span>
              </div>
              <div class="location-details">
                <div class="detail-item">
                  <MapPin class="icon" />
                  <span>{{ location.address_line_1 }}</span>
                </div>
                <div class="detail-item">
                  <Globe class="icon" />
                  <span>{{ location.city }}, {{ location.country }}</span>
                </div>
                <div class="detail-item">
                  <Mail class="icon" />
                  <span>{{ location.location_email }}</span>
                </div>
              </div>
              <div class="location-status">
                <div class="status-badge" :class="getStatusColor(location.verification_status)">
                  {{ location.verification_status }}
                </div>
              </div>
            </div>

            <div v-if="locations.length === 0" class="no-data">
              <p>No locations registered</p>
            </div>
          </div>
        </div>
      </div>

      <!-- QR Codes -->
      <div class="qr-section">
        <div class="section-card">
          <h3>QR Codes</h3>
          <div class="qr-grid">
            <div v-for="qrCode in qrCodes" :key="qrCode.id" class="qr-card">
              <div class="qr-header">
                <h4>{{ qrCode.unique_code }}</h4>
                <div class="status-badge" :class="getStatusColor(qrCode.status)">
                  {{ qrCode.status }}
                </div>
              </div>
              <div class="qr-details">
                <div class="detail-item">
                  <MapPin class="icon" />
                  <span>{{ qrCode.location?.name || 'No location' }}</span>
                </div>
                <div class="detail-item">
                  <Eye class="icon" />
                  <span>{{ qrCode.total_scans || 0 }} scans</span>
                </div>
                <div class="detail-item">
                  <DollarSign class="icon" />
                  <span>{{ formatCurrency(qrCode.total_revenue_generated || 0) }}</span>
                </div>
              </div>
              <div class="qr-actions">
                <button @click="viewQrCode(qrCode)" class="btn-view">
                  <Camera />
                  View QR Code
                </button>
                <div v-if="!getQrCodeImageUrl(qrCode)" class="no-qr-code">
                  <p>No QR code image available</p>
                </div>
              </div>
            </div>

            <div v-if="qrCodes.length === 0" class="no-data">
              <p>No QR codes generated</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="activity-section">
        <div class="section-card">
          <h3>Recent Activity</h3>
          <div class="activity-list">
            <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
              <div class="activity-icon" :class="activity.type">
                <component :is="getActivityIcon(activity.type)" />
              </div>
              <div class="activity-content">
                <p>{{ activity.description }}</p>
                <small>{{ formatDate(activity.created_at) }}</small>
              </div>
            </div>

            <div v-if="recentActivity.length === 0" class="no-data">
              <p>No recent activity</p>
            </div>
          </div>
        </div>
      </div>

      <!-- QR Code Modal -->
      <div v-if="showQrModal" class="fixed inset-0 z-50 overflow-y-auto" @click="closeQrModal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Background overlay -->
          <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
          </div>

          <!-- Center modal -->
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

          <!-- Modal panel -->
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" @click.stop>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                  <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    QR Code Preview
                  </h3>

                  <div v-if="selectedQrCode" class="space-y-4">
                    <!-- QR Code Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                      <h4 class="font-medium text-gray-900 mb-2">{{ getLocationDisplayName(selectedQrCode) }}</h4>
                      <p class="text-sm text-gray-500">{{ getLocationAddress(selectedQrCode) }}</p>

                      <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <span class="text-gray-500">Short Code:</span>
                          <span class="ml-2 font-medium">{{ selectedQrCode.short_code }}</span>
                        </div>
                        <div>
                          <span class="text-gray-500">Status:</span>
                          <span :class="getQrCodeStatus(selectedQrCode).class" class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                            {{ getQrCodeStatus(selectedQrCode).text }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- QR Code Image -->
                    <div class="flex justify-center">
                      <div class="bg-white p-6 border-2 border-gray-200 rounded-lg shadow-sm">
                        <img
                          v-if="getQrCodeImageUrl(selectedQrCode)"
                          :src="getQrCodeImageUrl(selectedQrCode)"
                          :alt="'QR Code for ' + (getLocationDisplayName(selectedQrCode) || 'QR Code ' + selectedQrCode.id)"
                          class="w-80 h-80 object-contain"
                          style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; image-rendering: pixelated;"
                        />
                        <div v-else class="w-80 h-80 flex items-center justify-center bg-gray-100">
                          <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">QR Code Image</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- QR Code Details -->
                    <div class="bg-blue-50 rounded-lg p-4">
                      <h5 class="font-medium text-blue-900 mb-2">QR Code Details</h5>
                      <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                          <span class="text-blue-700">Total Scans:</span>
                          <span class="font-medium text-blue-900">{{ selectedQrCode.total_scans || 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-blue-700">Conversions:</span>
                          <span class="font-medium text-blue-900">{{ selectedQrCode.conversion_count || 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-blue-700">Revenue Generated:</span>
                          <span class="font-medium text-blue-900">{{ formatCurrency(selectedQrCode.total_revenue_generated || 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-blue-700">Created:</span>
                          <span class="font-medium text-blue-900">{{ formatDate(selectedQrCode.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-blue-700">Last Scanned:</span>
                          <span class="font-medium text-blue-900">{{ selectedQrCode.last_scanned_at ? formatDate(selectedQrCode.last_scanned_at) : 'Never' }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  v-if="selectedQrCode"
                  @click="closeQrModal"
                  type="button"
                  class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style scoped>
.business-details {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
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
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
}

.business-title h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.status-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge svg {
  width: 16px;
  height: 16px;
}

.header-meta p {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-export, .btn-dashboard, .btn-refresh {
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
  text-decoration: none;
  transition: all 0.2s;
}

.btn-export:hover, .btn-dashboard:hover, .btn-refresh:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-dashboard {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.btn-dashboard:hover {
  background: #2563eb;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.actions-section {
  margin-bottom: 2rem;
}

.actions-card, .edit-form {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.actions-card h3, .edit-form h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-verify, .btn-reject, .btn-toggle, .btn-edit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-verify {
  background: #10b981;
  color: white;
}

.btn-verify:hover {
  background: #059669;
}

.btn-reject {
  background: #ef4444;
  color: white;
}

.btn-reject:hover {
  background: #dc2626;
}

.btn-toggle {
  background: #f59e0b;
  color: white;
}

.btn-toggle:hover {
  background: #d97706;
}

.btn-edit {
  background: #3b82f6;
  color: white;
}

.btn-edit:hover {
  background: #2563eb;
}

.btn-verify svg, .btn-reject svg, .btn-toggle svg, .btn-edit svg {
  width: 18px;
  height: 18px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-input, .form-textarea {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: border-color 0.15s;
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
  min-height: 100px;
  resize: vertical;
}

.form-actions {
  display: flex;
  gap: 1rem;
}

.btn-save, .btn-cancel {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-save {
  background: #10b981;
  color: white;
}

.btn-save:hover {
  background: #059669;
}

.btn-cancel {
  background: #6b7280;
  color: white;
}

.btn-cancel:hover {
  background: #4b5563;
}

.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
  align-items: stretch;
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
  min-height: 120px;
  word-break: break-word;
}

.stat-card.primary {
  border-left: 4px solid #6366f1;
}

.stat-card.success {
  border-left: 4px solid #10b981;
}

.stat-card.info {
  border-left: 4px solid #3b82f6;
}

.stat-card.warning {
  border-left: 4px solid #f59e0b;
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
}

.info-section, .locations-section, .qr-section, .activity-section {
  margin-bottom: 2rem;
}

.info-card, .section-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  word-break: break-word;
  overflow: hidden;
}

.info-card h3, .section-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
  align-items: stretch;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.info-item .icon {
  width: 20px;
  height: 20px;
  color: #6b7280;
  margin-top: 2px;
  flex-shrink: 0;
}

.info-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.info-item p {
  color: #1f2937;
  font-size: 0.875rem;
}

.link {
  color: #3b82f6;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.link:hover {
  color: #2563eb;
}

.external-icon {
  width: 14px;
  height: 14px;
}

.address-section {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  align-items: stretch;
}

.address-item h4 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.address-item p {
  color: #6b7280;
  font-size: 0.875rem;
}

.locations-grid, .qr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  align-items: stretch;
}

.location-card, .qr-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  background: #f9fafb;
  word-break: break-word;
  overflow: hidden;
}

.location-header, .qr-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.location-header h4, .qr-header h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
}

.location-code {
  font-size: 0.75rem;
  color: #6b7280;
  background: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.location-details, .qr-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.detail-item .icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.location-status {
  margin-bottom: 1rem;
}

.qr-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-view {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
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
  gap: 1rem;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
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

.activity-icon.info {
  background: #dbeafe;
  color: #3b82f6;
}

.activity-icon.warning {
  background: #fed7aa;
  color: #f59e0b;
}

.activity-icon.error {
  background: #fee2e2;
  color: #ef4444;
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

.no-qr-code {
  text-align: center;
  padding: 1rem;
  color: #6b7280;
  font-size: 0.875rem;
  font-style: italic;
}

@media (max-width: 768px) {
  .business-details {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .address-section {
    grid-template-columns: 1fr;
  }

  .locations-grid, .qr-grid {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    flex-direction: column;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .address-section {
    grid-template-columns: 1fr;
  }

  .locations-grid, .qr-grid {
    grid-template-columns: 1fr;
  }
}
</style>