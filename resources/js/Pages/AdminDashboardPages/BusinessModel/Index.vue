<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'

const globalSettings = ref({
  discount_type: 'percentage',
  discount_value: 0,
  min_booking_amount: null,
  max_discount_amount: null,
  commission_rate: 0,
  commission_type: 'percentage',
  payout_threshold: 100.00,
  max_qr_codes_per_business: 100,
  qr_code_validity_days: 365,
  session_tracking_hours: 24,
  allow_business_override: true,
  require_business_verification: true,
  auto_approve_commissions: true,
})

const businesses = ref([])
const businessModels = ref({})
const saving = ref(false)
const savingBusiness = ref(null)

onMounted(async () => {
  await loadGlobalSettings()
  await loadBusinesses()
})

const loadGlobalSettings = async () => {
  try {
    const response = await axios.get('/admin/affiliate/global-settings')
    globalSettings.value = response.data
  } catch (error) {
    console.error('Error loading global settings:', error)
  }
}

const loadBusinesses = async () => {
  try {
    const response = await axios.get('/admin/affiliate/businesses')
    businesses.value = response.data.data || []

    // Initialize business models
    businesses.value.forEach(business => {
      businessModels.value[business.id] = business.business_model || {
        discount_value: null,
        commission_rate: null,
        commission_type: 'percentage',
      }
    })
  } catch (error) {
    console.error('Error loading businesses:', error)
  }
}

const saveGlobalSettings = async () => {
  saving.value = true
  try {
    await axios.post('/admin/affiliate/global-settings', globalSettings.value)
    // Show success message
    showNotification('Global settings saved successfully!', 'success')
  } catch (error) {
    console.error('Error saving global settings:', error)
    showNotification('Error saving global settings', 'error')
  } finally {
    saving.value = false
  }
}

const saveBusinessModel = async (businessId) => {
  savingBusiness.value = businessId
  try {
    await axios.post(`/admin/affiliate/businesses/${businessId}/model`, businessModels.value[businessId])
    showNotification('Business model updated successfully!', 'success')
  } catch (error) {
    console.error('Error saving business model:', error)
    showNotification('Error saving business model', 'error')
  } finally {
    savingBusiness.value = null
  }
}

const getEffectiveDiscount = (business) => {
  const businessModel = businessModels.value[business.id]
  return businessModel.discount_value !== null ? businessModel.discount_value : globalSettings.value.discount_value
}

const getEffectiveCommission = (business) => {
  const businessModel = businessModels.value[business.id]
  return businessModel.commission_rate !== null ? businessModel.commission_rate : globalSettings.value.commission_rate
}

const formatValue = (value, type = 'percentage') => {
  if (type === 'percentage') {
    return `${value}%`
  }
  return `€${value}`
}

const showNotification = (message, type) => {
  // Simple notification implementation
  const notification = document.createElement('div')
  notification.className = `notification ${type}`
  notification.textContent = message
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 4px;
    color: white;
    z-index: 9999;
    background: ${type === 'success' ? '#28a745' : '#dc3545'};
  `
  document.body.appendChild(notification)
  setTimeout(() => notification.remove(), 3000)
}
</script>

<template>
  <Head title="Affiliate Business Model Configuration" />
<AdminDashboardLayout>
    <div class="business-model-container">
      <div class="header">
        <h1>Affiliate Business Model Configuration</h1>
        <p>Configure global commission and discount rates for all affiliate businesses</p>
      </div>

      <!-- Global Settings Form -->
      <div class="global-settings card">
        <h2>Global Settings</h2>
        <form @submit.prevent="saveGlobalSettings">
          <div class="form-row">
            <div class="form-group">
              <label for="discount_type">Discount Type</label>
              <select id="discount_type" v-model="globalSettings.discount_type" required>
                <option value="percentage">Percentage</option>
                <option value="fixed_amount">Fixed Amount</option>
              </select>
            </div>
            <div class="form-group">
              <label for="discount_value">Discount Value</label>
              <input
                id="discount_value"
                type="number"
                v-model="globalSettings.discount_value"
                step="0.01"
                min="0"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="min_booking_amount">Minimum Booking Amount</label>
              <input
                id="min_booking_amount"
                type="number"
                v-model="globalSettings.min_booking_amount"
                step="0.01"
                min="0"
                placeholder="No minimum"
              />
            </div>
            <div class="form-group">
              <label for="max_discount_amount">Maximum Discount Amount</label>
              <input
                id="max_discount_amount"
                type="number"
                v-model="globalSettings.max_discount_amount"
                step="0.01"
                min="0"
                placeholder="No maximum"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="commission_type">Commission Type</label>
              <select id="commission_type" v-model="globalSettings.commission_type" required>
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
                <option value="tiered">Tiered</option>
              </select>
            </div>
            <div class="form-group">
              <label for="commission_rate">Commission Rate (%)</label>
              <input
                id="commission_rate"
                type="number"
                v-model="globalSettings.commission_rate"
                step="0.01"
                min="0"
                max="100"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="payout_threshold">Payout Threshold</label>
              <input
                id="payout_threshold"
                type="number"
                v-model="globalSettings.payout_threshold"
                step="0.01"
                min="0"
                required
              />
            </div>
            <div class="form-group">
              <label for="max_qr_codes_per_business">Max QR Codes per Business</label>
              <input
                id="max_qr_codes_per_business"
                type="number"
                v-model="globalSettings.max_qr_codes_per_business"
                min="1"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="qr_code_validity_days">QR Code Validity (Days)</label>
              <input
                id="qr_code_validity_days"
                type="number"
                v-model="globalSettings.qr_code_validity_days"
                min="1"
                required
              />
            </div>
            <div class="form-group">
              <label for="session_tracking_hours">Session Tracking (Hours)</label>
              <input
                id="session_tracking_hours"
                type="number"
                v-model="globalSettings.session_tracking_hours"
                min="1"
                max="168"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="globalSettings.allow_business_override"
                />
                Allow Business Override
              </label>
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="globalSettings.require_business_verification"
                />
                Require Business Verification
              </label>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="globalSettings.auto_approve_commissions"
                />
                Auto Approve Commissions
              </label>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Saving...' : 'Save Global Settings' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Business-Specific Overrides -->
      <div class="business-overrides card">
        <h2>Business-Specific Overrides</h2>
        <div class="table-container">
          <table class="business-table">
            <thead>
              <tr>
                <th>Business Name</th>
                <th>Business Type</th>
                <th>Default Discount</th>
                <th>Override Discount</th>
                <th>Default Commission</th>
                <th>Override Commission</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="business in businesses" :key="business.id">
                <td>{{ business.name }}</td>
                <td>{{ business.business_type }}</td>
                <td>{{ formatValue(getEffectiveDiscount(business)) }}</td>
                <td>
                  <input
                    type="number"
                    v-model="businessModels[business.id].discount_value"
                    step="0.01"
                    min="0"
                    placeholder="Override"
                    class="override-input"
                  />
                </td>
                <td>{{ globalSettings.commission_rate }}%</td>
                <td>
                  <input
                    type="number"
                    v-model="businessModels[business.id].commission_rate"
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="Override"
                    class="override-input"
                  />
                </td>
                <td>
                  <button
                    @click="saveBusinessModel(business.id)"
                    class="btn-sm btn-primary"
                    :disabled="savingBusiness === business.id"
                  >
                    {{ savingBusiness === business.id ? 'Saving...' : 'Update' }}
                  </button>
                </td>
              </tr>
              <tr v-if="businesses.length === 0">
                <td colspan="7" class="text-center">No businesses registered yet</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </AdminDashboardLayout>
</template>

<style scoped>
.business-model-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.header p {
  color: #6b7280;
  font-size: 1rem;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
}

.card h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f3f4f6;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: border-color 0.15s ease-in-out;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f3f4f6;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s ease-in-out;
  font-size: 0.875rem;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.table-container {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.business-table {
  width: 100%;
  border-collapse: collapse;
}

.business-table th,
.business-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.business-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.business-table td {
  font-size: 0.875rem;
  color: #1f2937;
}

.override-input {
  width: 120px;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
}

.override-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.checkbox-label input[type="checkbox"] {
  margin-right: 0.5rem;
  width: auto;
}

.text-center {
  text-align: center;
  color: #6b7280;
  font-style: italic;
  padding: 2rem;
}

@media (max-width: 768px) {
  .business-model-container {
    padding: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .card {
    padding: 1.5rem;
  }

  .table-container {
    font-size: 0.75rem;
  }

  .business-table th,
  .business-table td {
    padding: 0.5rem;
  }

  .override-input {
    width: 80px;
  }
}
</style>