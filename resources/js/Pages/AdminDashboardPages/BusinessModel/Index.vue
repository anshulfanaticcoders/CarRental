<script setup>
import { ref, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import { Percent, DollarSign, Settings, Building, Save, RotateCcw } from 'lucide-vue-next'

const page = usePage()

// Use Inertia props when available, fall back to defaults
const rawGlobal = page.props.globalSettings
const rawBusinesses = page.props.businesses

const globalSettings = ref({
  discount_type: rawGlobal?.global_discount_type || rawGlobal?.discount_type || 'percentage',
  discount_value: rawGlobal?.global_discount_value ?? rawGlobal?.discount_value ?? 0,
  min_booking_amount: rawGlobal?.global_min_booking_amount ?? rawGlobal?.min_booking_amount ?? null,
  max_discount_amount: rawGlobal?.global_max_discount_amount ?? rawGlobal?.max_discount_amount ?? null,
  commission_rate: rawGlobal?.global_commission_rate ?? rawGlobal?.commission_rate ?? 3,
  commission_type: rawGlobal?.global_commission_type || rawGlobal?.commission_type || 'percentage',
  payout_threshold: rawGlobal?.global_payout_threshold ?? rawGlobal?.payout_threshold ?? 100,
  max_qr_codes_per_business: rawGlobal?.max_qr_codes_per_business ?? 100,
  qr_code_validity_days: rawGlobal?.qr_code_validity_days ?? 365,
  session_tracking_hours: rawGlobal?.session_tracking_hours ?? 24,
  allow_business_override: rawGlobal?.allow_business_override ?? true,
  require_business_verification: rawGlobal?.require_business_verification ?? true,
  auto_approve_commissions: rawGlobal?.auto_approve_commissions ?? true,
})

const businesses = ref(Array.isArray(rawBusinesses) ? rawBusinesses : (rawBusinesses?.data || []))
const businessModels = ref({})

// Initialize business models
businesses.value.forEach(business => {
  businessModels.value[business.id] = business.business_model || {
    discount_value: null,
    commission_rate: null,
    commission_type: 'percentage',
  }
})

const saving = ref(false)
const savingBusiness = ref(null)
const notification = ref({ show: false, message: '', type: 'success' })

const saveGlobalSettings = async () => {
  saving.value = true
  try {
    await axios.post('/admin/affiliate/global-settings', globalSettings.value)
    showNotification('Global settings saved successfully!', 'success')
  } catch (error) {
    console.error('Error saving global settings:', error)
    showNotification('Error saving settings. Please try again.', 'error')
  } finally {
    saving.value = false
  }
}

const saveBusinessModel = async (businessId) => {
  savingBusiness.value = businessId
  try {
    await axios.post(`/admin/affiliate/businesses/${businessId}/model`, businessModels.value[businessId])
    showNotification('Business override updated!', 'success')
  } catch (error) {
    console.error('Error saving business model:', error)
    showNotification('Error updating business override.', 'error')
  } finally {
    savingBusiness.value = null
  }
}

const resetBusinessOverride = (businessId) => {
  businessModels.value[businessId] = {
    discount_value: null,
    commission_rate: null,
    commission_type: 'percentage',
  }
  saveBusinessModel(businessId)
}

const getEffectiveCommission = (business) => {
  const model = businessModels.value[business.id]
  return model?.commission_rate !== null && model?.commission_rate !== '' ? model.commission_rate : globalSettings.value.commission_rate
}

const getEffectiveDiscount = (business) => {
  const model = businessModels.value[business.id]
  return model?.discount_value !== null && model?.discount_value !== '' ? model.discount_value : globalSettings.value.discount_value
}

const hasOverride = (business) => {
  const model = businessModels.value[business.id]
  return (model?.commission_rate !== null && model?.commission_rate !== '') ||
         (model?.discount_value !== null && model?.discount_value !== '')
}

const showNotification = (message, type) => {
  notification.value = { show: true, message, type }
  setTimeout(() => { notification.value.show = false }, 3000)
}

const verifiedBusinesses = computed(() => businesses.value.filter(b => b.verification_status === 'verified'))
</script>

<template>
  <Head title="Commission & Discount Settings" />
  <AdminDashboardLayout>
    <div class="settings-page">
      <!-- Notification -->
      <Transition name="slide">
        <div v-if="notification.show"
          :class="['notification', notification.type === 'success' ? 'notification-success' : 'notification-error']">
          {{ notification.message }}
        </div>
      </Transition>

      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Commission & Discount Settings</h1>
          <p>Set the universal affiliate commission rate and customer discount rules.</p>
        </div>
      </div>

      <!-- Commission Rate Hero Card -->
      <div class="hero-card">
        <div class="hero-card-inner">
          <div class="hero-icon-wrap">
            <Percent :size="28" />
          </div>
          <div class="hero-content">
            <div class="hero-label">Universal Affiliate Commission Rate</div>
            <div class="hero-rate-row">
              <input
                type="number"
                v-model="globalSettings.commission_rate"
                step="0.1"
                min="0"
                max="100"
                class="hero-rate-input"
              />
              <span class="hero-rate-suffix">%</span>
              <span class="hero-rate-desc">of every booking made via affiliate QR codes</span>
            </div>
            <div class="hero-note">
              This rate applies to <strong>all affiliates</strong> unless overridden below.
              When a customer books through an affiliate QR code, the affiliate earns this percentage as commission.
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Grid -->
      <div class="settings-grid">
        <!-- Customer Discount Settings -->
        <div class="settings-card">
          <div class="card-header">
            <DollarSign :size="18" class="card-icon" />
            <h2>Customer Discount</h2>
          </div>
          <p class="card-desc">Discount given to customers who scan an affiliate QR code.</p>
          <div class="form-grid">
            <div class="form-field">
              <label>Discount Type</label>
              <select v-model="globalSettings.discount_type">
                <option value="percentage">Percentage (%)</option>
                <option value="fixed_amount">Fixed Amount (€)</option>
              </select>
            </div>
            <div class="form-field">
              <label>Discount Value</label>
              <div class="input-with-suffix">
                <input type="number" v-model="globalSettings.discount_value" step="0.01" min="0" />
                <span>{{ globalSettings.discount_type === 'percentage' ? '%' : '€' }}</span>
              </div>
            </div>
            <div class="form-field">
              <label>Min. Booking Amount</label>
              <div class="input-with-suffix">
                <input type="number" v-model="globalSettings.min_booking_amount" step="0.01" min="0" placeholder="No minimum" />
                <span>€</span>
              </div>
            </div>
            <div class="form-field">
              <label>Max. Discount Cap</label>
              <div class="input-with-suffix">
                <input type="number" v-model="globalSettings.max_discount_amount" step="0.01" min="0" placeholder="No cap" />
                <span>€</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Operational Settings -->
        <div class="settings-card">
          <div class="card-header">
            <Settings :size="18" class="card-icon" />
            <h2>Operational Settings</h2>
          </div>
          <p class="card-desc">Payout thresholds and QR code limits.</p>
          <div class="form-grid">
            <div class="form-field">
              <label>Payout Threshold</label>
              <div class="input-with-suffix">
                <input type="number" v-model="globalSettings.payout_threshold" step="0.01" min="0" />
                <span>€</span>
              </div>
              <small>Minimum amount before payout is triggered.</small>
            </div>
            <div class="form-field">
              <label>Max QR Codes / Business</label>
              <input type="number" v-model="globalSettings.max_qr_codes_per_business" min="1" />
            </div>
            <div class="form-field">
              <label>QR Code Validity</label>
              <div class="input-with-suffix">
                <input type="number" v-model="globalSettings.qr_code_validity_days" min="1" />
                <span>days</span>
              </div>
            </div>
            <div class="form-field">
              <label>Session Tracking</label>
              <div class="input-with-suffix">
                <input type="number" v-model="globalSettings.session_tracking_hours" min="1" max="168" />
                <span>hours</span>
              </div>
              <small>How long a QR scan cookie stays active.</small>
            </div>
          </div>

          <!-- Toggles -->
          <div class="toggle-group">
            <label class="toggle-row">
              <input type="checkbox" v-model="globalSettings.require_business_verification" />
              <div>
                <strong>Require Business Verification</strong>
                <small>New affiliates must be approved before earning commissions.</small>
              </div>
            </label>
            <label class="toggle-row">
              <input type="checkbox" v-model="globalSettings.auto_approve_commissions" />
              <div>
                <strong>Auto-Approve Commissions</strong>
                <small>Commissions are automatically approved after booking confirmation.</small>
              </div>
            </label>
            <label class="toggle-row">
              <input type="checkbox" v-model="globalSettings.allow_business_override" />
              <div>
                <strong>Allow Per-Business Overrides</strong>
                <small>Enable custom commission rates for specific businesses.</small>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="save-row">
        <button @click="saveGlobalSettings" class="btn-save" :disabled="saving">
          <Save :size="16" />
          {{ saving ? 'Saving...' : 'Save All Settings' }}
        </button>
      </div>

      <!-- Business-Specific Overrides -->
      <div v-if="globalSettings.allow_business_override && verifiedBusinesses.length > 0" class="overrides-section">
        <div class="overrides-header">
          <Building :size="18" class="card-icon" />
          <div>
            <h2>Per-Business Overrides</h2>
            <p>Set custom rates for specific affiliates. Leave blank to use global settings.</p>
          </div>
        </div>

        <div class="overrides-table-wrap">
          <table class="overrides-table">
            <thead>
              <tr>
                <th>Business</th>
                <th>Type</th>
                <th class="text-center">Effective Commission</th>
                <th>Override Commission</th>
                <th>Override Discount</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="business in verifiedBusinesses" :key="business.id" :class="{ 'row-override': hasOverride(business) }">
                <td>
                  <div class="business-name">{{ business.name }}</div>
                  <div class="business-email">{{ business.contact_email }}</div>
                </td>
                <td>
                  <span class="type-badge">{{ business.business_type || 'N/A' }}</span>
                </td>
                <td class="text-center">
                  <span class="effective-rate" :class="{ 'rate-custom': hasOverride(business) }">
                    {{ getEffectiveCommission(business) }}%
                  </span>
                </td>
                <td>
                  <div class="input-with-suffix input-sm">
                    <input
                      type="number"
                      v-model="businessModels[business.id].commission_rate"
                      step="0.1"
                      min="0"
                      max="100"
                      :placeholder="globalSettings.commission_rate + '% (global)'"
                    />
                    <span>%</span>
                  </div>
                </td>
                <td>
                  <div class="input-with-suffix input-sm">
                    <input
                      type="number"
                      v-model="businessModels[business.id].discount_value"
                      step="0.01"
                      min="0"
                      :placeholder="globalSettings.discount_value + (globalSettings.discount_type === 'percentage' ? '%' : '€') + ' (global)'"
                    />
                    <span>{{ globalSettings.discount_type === 'percentage' ? '%' : '€' }}</span>
                  </div>
                </td>
                <td class="text-center">
                  <div class="action-btns">
                    <button
                      @click="saveBusinessModel(business.id)"
                      class="btn-action btn-action-save"
                      :disabled="savingBusiness === business.id"
                    >
                      {{ savingBusiness === business.id ? '...' : 'Save' }}
                    </button>
                    <button
                      v-if="hasOverride(business)"
                      @click="resetBusinessOverride(business.id)"
                      class="btn-action btn-action-reset"
                      title="Reset to global"
                    >
                      <RotateCcw :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else-if="verifiedBusinesses.length === 0 && globalSettings.allow_business_override" class="empty-state">
        <Building :size="32" />
        <p>No verified businesses yet. Overrides will appear here once businesses are approved.</p>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style scoped>
.settings-page {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

/* Notification */
.notification {
  position: fixed;
  top: 16px;
  right: 16px;
  padding: 12px 20px;
  border-radius: 8px;
  color: #fff;
  font-size: 0.875rem;
  font-weight: 500;
  z-index: 9999;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.notification-success { background: #10b981; }
.notification-error { background: #ef4444; }
.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-10px); }

/* Header */
.page-header {
  margin-bottom: 1.5rem;
}
.page-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}
.page-header p {
  color: #6b7280;
  font-size: 0.9rem;
}

/* Hero Card */
.hero-card {
  background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
  border: 2px solid #3b82f6;
  border-radius: 14px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}
.hero-card-inner {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}
.hero-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #3b82f6;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.hero-label {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #3b82f6;
  margin-bottom: 0.5rem;
}
.hero-rate-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.hero-rate-input {
  width: 80px;
  padding: 0.5rem 0.75rem;
  border: 2px solid #3b82f6;
  border-radius: 8px;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  text-align: center;
  background: #fff;
}
.hero-rate-input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
.hero-rate-suffix {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}
.hero-rate-desc {
  font-size: 0.875rem;
  color: #6b7280;
}
.hero-note {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 0.75rem;
  line-height: 1.5;
}
.hero-note strong {
  color: #374151;
}

/* Settings Grid */
.settings-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

/* Settings Card */
.settings-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}
.card-header h2 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}
.card-icon {
  color: #3b82f6;
}
.card-desc {
  font-size: 0.8rem;
  color: #9ca3af;
  margin-bottom: 1rem;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.form-field {
  display: flex;
  flex-direction: column;
}
.form-field label {
  font-size: 0.78rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.3rem;
}
.form-field input,
.form-field select {
  padding: 0.5rem 0.65rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.85rem;
  color: #1f2937;
  background: #fff;
}
.form-field input:focus,
.form-field select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.1);
}
.form-field small {
  font-size: 0.7rem;
  color: #9ca3af;
  margin-top: 0.2rem;
}

.input-with-suffix {
  display: flex;
  align-items: center;
}
.input-with-suffix input {
  border-radius: 6px 0 0 6px;
  border-right: none;
  flex: 1;
  min-width: 0;
}
.input-with-suffix span {
  padding: 0.5rem 0.6rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 0 6px 6px 0;
  font-size: 0.78rem;
  color: #6b7280;
  white-space: nowrap;
}

/* Toggles */
.toggle-group {
  margin-top: 1rem;
  border-top: 1px solid #f3f4f6;
  padding-top: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.toggle-row {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  cursor: pointer;
  padding: 0.4rem 0;
}
.toggle-row input[type="checkbox"] {
  margin-top: 2px;
  width: 16px;
  height: 16px;
  accent-color: #3b82f6;
  flex-shrink: 0;
}
.toggle-row strong {
  font-size: 0.82rem;
  color: #374151;
  display: block;
}
.toggle-row small {
  font-size: 0.72rem;
  color: #9ca3af;
  display: block;
  margin-top: 1px;
}

/* Save Row */
.save-row {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 2rem;
}
.btn-save {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.5rem;
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-save:hover:not(:disabled) {
  background: #2563eb;
}
.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Overrides Section */
.overrides-section {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.overrides-header {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}
.overrides-header h2 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.1rem;
}
.overrides-header p {
  font-size: 0.8rem;
  color: #9ca3af;
}

.overrides-table-wrap {
  overflow-x: auto;
}
.overrides-table {
  width: 100%;
  border-collapse: collapse;
}
.overrides-table th {
  text-align: left;
  padding: 0.65rem 0.75rem;
  font-size: 0.72rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
  white-space: nowrap;
}
.overrides-table td {
  padding: 0.65rem 0.75rem;
  font-size: 0.85rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}
.overrides-table tr:hover {
  background: #fafbfc;
}
.row-override {
  background: #eff6ff !important;
}
.text-center {
  text-align: center;
}

.business-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.85rem;
}
.business-email {
  font-size: 0.72rem;
  color: #9ca3af;
}
.type-badge {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  background: #f3f4f6;
  color: #6b7280;
  font-size: 0.72rem;
  font-weight: 500;
}
.effective-rate {
  font-weight: 700;
  color: #374151;
  font-size: 0.95rem;
}
.rate-custom {
  color: #3b82f6;
}

.input-sm input {
  padding: 0.35rem 0.5rem;
  font-size: 0.8rem;
}
.input-sm span {
  padding: 0.35rem 0.5rem;
  font-size: 0.72rem;
}

.action-btns {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}
.btn-action {
  padding: 0.3rem 0.65rem;
  border-radius: 5px;
  font-size: 0.75rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  gap: 0.2rem;
}
.btn-action-save {
  background: #3b82f6;
  color: #fff;
}
.btn-action-save:hover:not(:disabled) {
  background: #2563eb;
}
.btn-action-save:disabled {
  opacity: 0.5;
}
.btn-action-reset {
  background: #f3f4f6;
  color: #6b7280;
}
.btn-action-reset:hover {
  background: #e5e7eb;
  color: #374151;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #9ca3af;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}
.empty-state svg {
  margin-bottom: 0.5rem;
}
.empty-state p {
  font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 768px) {
  .settings-page {
    padding: 1rem;
  }
  .settings-grid {
    grid-template-columns: 1fr;
  }
  .form-grid {
    grid-template-columns: 1fr;
  }
  .hero-card-inner {
    flex-direction: column;
  }
  .hero-rate-row {
    flex-direction: column;
    align-items: flex-start;
  }
  .overrides-table th,
  .overrides-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }
  .input-sm input {
    width: 70px;
  }
}
</style>
