<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import {
  Building,
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  Eye,
  FileText,
  MapPin,
  Mail,
  Phone,
  Calendar,
  Search,
  Filter,
  Download,
  RefreshCw,
  CheckSquare,
  Square,
  ExternalLink,
  User,
  Globe,
  CreditCard,
  Shield
} from 'lucide-vue-next'

// Reactive data
const loading = ref(true)
const businesses = ref([])
const selectedBusinesses = ref(new Set())
const searchTerm = ref('')
const statusFilter = ref('all')
const sortBy = ref('created_at')
const sortOrder = ref('desc')
const currentPage = ref(1)
const totalPages = ref(1)
const processing = ref(new Set())

// Computed properties
const filteredBusinesses = computed(() => {
  // Create a copy to avoid reactivity issues
  let filtered = [...businesses.value]

  // Filter by status
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(b => b.verification_status === statusFilter.value)
  }

  // Filter by search term
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(b => {
      const name = b.name || ''
      const email = b.contact_email || b.email || ''
      const phone = b.contact_phone || b.phone || ''
      const businessType = b.business_type || ''
      const city = b.city || b.location_city || ''
      const country = b.country || b.location_country || ''

      return name.toLowerCase().includes(term) ||
             email.toLowerCase().includes(term) ||
             phone.toLowerCase().includes(term) ||
             businessType.toLowerCase().includes(term) ||
             city.toLowerCase().includes(term) ||
             country.toLowerCase().includes(term)
    })
  }

  // Sort using a non-reactive copy
  const sortField = sortBy.value
  const sortOrderValue = sortOrder.value

  return filtered.sort((a, b) => {
    let aVal = a[sortField]
    let bVal = b[sortField]

    if (sortField === 'created_at' || sortField === 'verified_at') {
      aVal = aVal ? new Date(aVal).getTime() : 0
      bVal = bVal ? new Date(bVal).getTime() : 0
    }

    if (sortOrderValue === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })
})

const stats = computed(() => {
  const total = businesses.value.length
  const pending = businesses.value.filter(b => b.verification_status === 'pending').length
  const verified = businesses.value.filter(b => b.verification_status === 'verified').length
  const rejected = businesses.value.filter(b => b.verification_status === 'rejected').length
  const active = businesses.value.filter(b => b.status === 'active').length

  return { total, pending, verified, rejected, active }
})

// Load businesses
const loadBusinesses = async () => {
  loading.value = true
  try {
    const response = await axios.get('/admin/affiliate/businesses', {
      params: {
        page: currentPage.value,
        include: 'businessModel,locations,qrCodes'
      }
    })
    businesses.value = response.data.data || []
    totalPages.value = response.data.last_page || 1
  } catch (error) {
    console.error('Error loading businesses:', error)
    showNotification('Error loading businesses', 'error')
  } finally {
    loading.value = false
  }
}

// Change business status
const changeBusinessStatus = async (businessId, newStatus) => {
  processing.value.add(businessId)
  try {
    await axios.put(`/admin/affiliate/businesses/${businessId}/status`, {
      status: newStatus
    })

    showNotification(`Business status changed to ${newStatus} successfully`, 'success')
    await loadBusinesses()
  } catch (error) {
    console.error('Error changing business status:', error)
    showNotification('Error changing business status', 'error')
  } finally {
    processing.value.delete(businessId)
  }
}

// Toggle selection
const toggleSelection = (businessId) => {
  if (selectedBusinesses.value.has(businessId)) {
    selectedBusinesses.value.delete(businessId)
  } else {
    selectedBusinesses.value.add(businessId)
  }
}

// Select all
const selectAll = () => {
  if (selectedBusinesses.value.size === filteredBusinesses.value.length) {
    selectedBusinesses.value.clear()
  } else {
    filteredBusinesses.value.forEach(b => selectedBusinesses.value.add(b.id))
  }
}

// Bulk action
const bulkAction = async (action) => {
  if (selectedBusinesses.value.size === 0) {
    showNotification('No businesses selected', 'warning')
    return
  }

  const approve = action === 'approve'

  for (const businessId of selectedBusinesses.value) {
    await verifyBusiness(businessId, approve)
  }

  selectedBusinesses.value.clear()
}

// Get status icon
const getStatusIcon = (status) => {
  switch (status) {
    case 'verified':
      return CheckCircle
    case 'pending':
      return Clock
    case 'rejected':
      return XCircle
    default:
      return AlertCircle
  }
}

// Get status color
const getStatusColor = (status) => {
  switch (status) {
    case 'verified':
      return 'text-green-600 bg-green-100'
    case 'pending':
      return 'text-yellow-600 bg-yellow-100'
    case 'rejected':
      return 'text-red-600 bg-red-100'
    case 'active':
      return 'text-blue-600 bg-blue-100'
    case 'inactive':
      return 'text-gray-600 bg-gray-100'
    default:
      return 'text-gray-600 bg-gray-100'
  }
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'

  try {
    const date = new Date(dateString)

    // Check if date is valid
    if (isNaN(date.getTime())) {
      return 'N/A'
    }

    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    console.warn('Date formatting error:', error)
    return 'N/A'
  }
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
    const response = await axios.get('/admin/affiliate/businesses/export', {
      params: {
        status: statusFilter.value,
        search: searchTerm.value
      },
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `businesses-${new Date().toISOString().split('T')[0]}.csv`)
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
  loadBusinesses()
})
</script>

<template>
  <Head title="Business Verification Management" />
  <AdminDashboardLayout>
    <div class="business-verification">
      <!-- Header -->
      <div class="header">
        <div class="header-content">
          <h1>Business Verification Management</h1>
          <p>Review and verify affiliate business applications</p>
        </div>
        <div class="header-actions">
          <button @click="exportData" class="btn-export">
            <Download />
            Export
          </button>
          <button @click="loadBusinesses" class="btn-refresh" :disabled="loading">
            <RefreshCw :class="{ 'animate-spin': loading }" />
            Refresh
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-icon">
            <Building />
          </div>
          <div class="stat-content">
            <h3>{{ stats.total }}</h3>
            <p>Total Businesses</p>
          </div>
        </div>

        <div class="stat-card pending">
          <div class="stat-icon">
            <Clock />
          </div>
          <div class="stat-content">
            <h3>{{ stats.pending }}</h3>
            <p>Pending Verification</p>
          </div>
        </div>

        <div class="stat-card verified">
          <div class="stat-icon">
            <CheckCircle />
          </div>
          <div class="stat-content">
            <h3>{{ stats.verified }}</h3>
            <p>Verified</p>
          </div>
        </div>

        <div class="stat-card rejected">
          <div class="stat-icon">
            <XCircle />
          </div>
          <div class="stat-content">
            <h3>{{ stats.rejected }}</h3>
            <p>Rejected</p>
          </div>
        </div>

        <div class="stat-card active">
          <div class="stat-icon">
            <Shield />
          </div>
          <div class="stat-content">
            <h3>{{ stats.active }}</h3>
            <p>Active</p>
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
              placeholder="Search businesses..."
              class="search-input"
            />
          </div>

          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="verified">Verified</option>
            <option value="rejected">Rejected</option>
          </select>

          <select v-model="sortBy" @change="sort(sortBy)" class="filter-select">
            <option value="created_at">Date Created</option>
            <option value="name">Business Name</option>
            <option value="business_type">Business Type</option>
            <option value="verified_at">Verified Date</option>
          </select>
        </div>

        <div class="filters-right">
          <div class="bulk-actions" v-if="selectedBusinesses.size > 0">
            <span class="selected-count">{{ selectedBusinesses.size }} selected</span>
            <button @click="bulkAction('approve')" class="btn-approve">
              <CheckSquare />
              Approve Selected
            </button>
            <button @click="bulkAction('reject')" class="btn-reject">
              <Square />
              Reject Selected
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading businesses...</p>
      </div>

      <!-- Businesses Table -->
      <div v-else class="businesses-table">
        <div class="table-header">
          <div class="select-all">
            <input
              type="checkbox"
              :checked="selectedBusinesses.size === filteredBusinesses.length && filteredBusinesses.length > 0"
              @change="selectAll"
            />
          </div>
          <div class="table-actions">
            <button @click="sort('name')" class="sort-btn" :class="{ active: sortBy === 'name' }">
              Business Name
              <span v-if="sortBy === 'name'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">
            <button @click="sort('business_type')" class="sort-btn" :class="{ active: sortBy === 'business_type' }">
              Type
              <span v-if="sortBy === 'business_type'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-info">Contact</div>
          <div class="table-info">Location</div>
          <div class="table-actions">
            <button @click="sort('created_at')" class="sort-btn" :class="{ active: sortBy === 'created_at' }">
              Applied
              <span v-if="sortBy === 'created_at'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">
            <button @click="sort('verified_at')" class="sort-btn" :class="{ active: sortBy === 'verified_at' }">
              Email Verification
              <span v-if="sortBy === 'verified_at'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">
            <button @click="sort('status')" class="sort-btn" :class="{ active: sortBy === 'status' }">
              Business Status
              <span v-if="sortBy === 'status'" class="sort-indicator">
                {{ sortOrder === 'asc' ? '↑' : '↓' }}
              </span>
            </button>
          </div>
          <div class="table-actions">Actions</div>
        </div>

        <div class="table-body">
          <div
            v-for="business in filteredBusinesses"
            :key="business.id"
            class="table-row"
            :class="{ selected: selectedBusinesses.has(business.id) }"
          >
            <div class="table-cell select-cell">
              <input
                type="checkbox"
                :checked="selectedBusinesses.has(business.id)"
                @change="toggleSelection(business.id)"
              />
            </div>

            <div class="table-cell business-cell">
              <div class="business-info">
                <h4>{{ business.name }}</h4>
                <p>{{ business.business_type.replace('_', ' ').toUpperCase() }}</p>
              </div>
            </div>

            <div class="table-cell type-cell">
              <span class="type-badge">{{ business.business_type }}</span>
            </div>

            <div class="table-cell contact-cell">
              <div class="contact-info">
                <div class="contact-item">
                  <Mail class="icon" />
                  <span>{{ business.contact_email || 'N/A' }}</span>
                </div>
                <div class="contact-item">
                  <Phone class="icon" />
                  <span>{{ business.contact_phone || business.phone || 'N/A' }}</span>
                </div>
              </div>
            </div>

            <div class="table-cell location-cell">
              <div class="location-info">
                <div class="location-item">
                  <MapPin class="icon" />
                  <span>{{ business.city || business.location_city || 'N/A' }}, {{ business.country || business.location_country || 'N/A' }}</span>
                </div>
              </div>
            </div>

            <div class="table-cell date-cell">
              <div class="date-info">
                <Calendar class="icon" />
                <span>{{ formatDate(business.applied_at || business.created_at) }}</span>
              </div>
            </div>

            <div class="table-cell status-cell">
              <div class="status-badge" :class="getStatusColor(business.verification_status)">
                <component :is="getStatusIcon(business.verification_status)" />
                {{ business.verification_status }}
              </div>
            </div>

            <div class="table-cell status-cell">
              <div class="status-badge" :class="getStatusColor(business.status)">
                {{ business.status }}
              </div>
            </div>

            <div class="table-cell actions-cell">
              <div class="action-buttons">
                <Link :href="`/admin/affiliate/business-details/${business.id}`" class="btn-view">
                  <Eye />
                  View
                </Link>

                <div class="status-dropdown">
                  <select
                    @change="changeBusinessStatus(business.id, $event.target.value)"
                    :disabled="processing.has(business.id)"
                    :value="business.status"
                    class="status-select"
                  >
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                  </select>
                  <div v-if="processing.has(business.id)" class="status-spinner">
                    <RefreshCw class="animate-spin" />
                  </div>
                </div>

                </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredBusinesses.length === 0" class="empty-state">
            <Building />
            <h3>No businesses found</h3>
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
.business-verification {
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

.stat-card.verified {
  border-left: 4px solid #10b981;
}

.stat-card.rejected {
  border-left: 4px solid #ef4444;
}

.stat-card.active {
  border-left: 4px solid #3b82f6;
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

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.selected-count {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

.btn-approve, .btn-reject {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-approve {
  background: #10b981;
  color: white;
}

.btn-approve:hover {
  background: #059669;
}

.btn-reject {
  background: #ef4444;
  color: white;
}

.btn-reject:hover {
  background: #dc2626;
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

.businesses-table {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 40px 2fr 1fr 2fr 1.5fr 1fr 1fr 1fr 1.5fr;
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
  grid-template-columns: 40px 2fr 1fr 2fr 1.5fr 1fr 1fr 1fr 1.5fr;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  align-items: center;
  transition: background-color 0.2s;
}

.table-row:hover {
  background: #f9fafb;
}

.table-row.selected {
  background: #eff6ff;
}

.table-cell {
  display: flex;
  align-items: center;
}

.business-info h4 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.business-info p {
  font-size: 0.75rem;
  color: #6b7280;
}

.type-badge {
  padding: 0.25rem 0.5rem;
  background: #e5e7eb;
  color: #374151;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.contact-info, .location-info, .date-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.contact-item, .location-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.date-info {
  font-size: 0.75rem;
  color: #6b7280;
}

.contact-item span, .location-item span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.icon {
  width: 14px;
  height: 14px;
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

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-view, .btn-external {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 0.75rem;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-view:hover, .btn-external:hover {
  background: #2563eb;
}

.btn-view svg, .btn-external svg {
  width: 14px;
  height: 14px;
}

.btn-approve, .btn-reject {
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

.btn-approve {
  background: #10b981;
  color: white;
}

.btn-approve:hover {
  background: #059669;
}

.btn-reject {
  background: #ef4444;
  color: white;
}

.btn-reject:hover {
  background: #dc2626;
}

.btn-approve:disabled, .btn-reject:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.status-dropdown {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-select {
  padding: 0.25rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: white;
  color: #374151;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 100px;
}

.status-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

.status-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: #f9fafb;
}

.status-spinner {
  display: flex;
  align-items: center;
  color: #3b82f6;
}

.status-spinner svg {
  width: 12px;
  height: 12px;
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
  .business-verification {
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