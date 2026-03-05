<script setup>
import { ref, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Percent, DollarSign, Settings, Building, Save, RotateCcw } from 'lucide-vue-next'

const page = usePage()

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
    const response = await fetch('/admin/affiliate/global-settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content },
      body: JSON.stringify(globalSettings.value),
    })
    if (response.ok) showNotification('Global settings saved successfully!', 'success')
    else showNotification('Error saving settings. Please try again.', 'error')
  } catch {
    showNotification('Error saving settings. Please try again.', 'error')
  } finally {
    saving.value = false
  }
}

const saveBusinessModel = async (businessId) => {
  savingBusiness.value = businessId
  try {
    const response = await fetch(`/admin/affiliate/businesses/${businessId}/model`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content },
      body: JSON.stringify(businessModels.value[businessId]),
    })
    if (response.ok) showNotification('Business override updated!', 'success')
    else showNotification('Error updating business override.', 'error')
  } catch {
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
  <Head title="Affiliate Settings" />
  <AdminDashboardLayout>
    <div class="flex flex-col gap-6 p-6">
      <!-- Notification -->
      <Transition name="slide">
        <div v-if="notification.show"
          :class="[
            'fixed top-4 right-4 z-50 px-5 py-3 rounded-lg text-white text-sm font-medium shadow-lg',
            notification.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'
          ]">
          {{ notification.message }}
        </div>
      </Transition>

      <div>
        <h1 class="text-2xl font-bold tracking-tight">Affiliate Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Set the universal affiliate commission rate and customer discount rules.</p>
      </div>

      <!-- Commission Rate Hero -->
      <div class="rounded-xl border-2 border-blue-500 bg-gradient-to-br from-blue-50 to-green-50 p-6">
        <div class="flex gap-4 items-start">
          <div class="w-12 h-12 rounded-xl bg-blue-500 text-white flex items-center justify-center shrink-0">
            <Percent :size="24" />
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 mb-2">Universal Affiliate Commission Rate</p>
            <div class="flex items-center gap-2 flex-wrap">
              <input
                type="number"
                v-model="globalSettings.commission_rate"
                step="0.1" min="0" max="100"
                class="w-20 px-3 py-2 border-2 border-blue-500 rounded-lg text-2xl font-bold text-center bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
              />
              <span class="text-2xl font-bold">%</span>
              <span class="text-sm text-gray-500">of every booking made via affiliate QR codes</span>
            </div>
            <p class="text-xs text-gray-500 mt-3 leading-relaxed">
              This rate applies to <strong class="text-gray-700">all affiliates</strong> unless overridden below.
              When a customer books through an affiliate QR code, the affiliate earns this percentage as commission.
            </p>
          </div>
        </div>
      </div>

      <!-- Settings Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Customer Discount -->
        <Card>
          <CardHeader class="pb-3">
            <div class="flex items-center gap-2">
              <DollarSign class="w-4.5 h-4.5 text-blue-500" />
              <CardTitle class="text-base font-semibold">Customer Discount</CardTitle>
            </div>
            <p class="text-xs text-gray-400">Discount given to customers who scan an affiliate QR code.</p>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Discount Type</label>
                <select v-model="globalSettings.discount_type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                  <option value="percentage">Percentage (%)</option>
                  <option value="fixed_amount">Fixed Amount (&euro;)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Discount Value</label>
                <div class="flex">
                  <input type="number" v-model="globalSettings.discount_value" step="0.01" min="0"
                    class="flex-1 min-w-0 px-3 py-2 border border-r-0 border-gray-300 rounded-l-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                  <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">
                    {{ globalSettings.discount_type === 'percentage' ? '%' : '\u20AC' }}
                  </span>
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Min. Booking Amount</label>
                <div class="flex">
                  <input type="number" v-model="globalSettings.min_booking_amount" step="0.01" min="0" placeholder="No minimum"
                    class="flex-1 min-w-0 px-3 py-2 border border-r-0 border-gray-300 rounded-l-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                  <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">&euro;</span>
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Max. Discount Cap</label>
                <div class="flex">
                  <input type="number" v-model="globalSettings.max_discount_amount" step="0.01" min="0" placeholder="No cap"
                    class="flex-1 min-w-0 px-3 py-2 border border-r-0 border-gray-300 rounded-l-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                  <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">&euro;</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Operational Settings -->
        <Card>
          <CardHeader class="pb-3">
            <div class="flex items-center gap-2">
              <Settings class="w-4.5 h-4.5 text-blue-500" />
              <CardTitle class="text-base font-semibold">Operational Settings</CardTitle>
            </div>
            <p class="text-xs text-gray-400">Payout thresholds and QR code limits.</p>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Payout Threshold</label>
                <div class="flex">
                  <input type="number" v-model="globalSettings.payout_threshold" step="0.01" min="0"
                    class="flex-1 min-w-0 px-3 py-2 border border-r-0 border-gray-300 rounded-l-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                  <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">&euro;</span>
                </div>
                <p class="text-[10px] text-gray-400 mt-0.5">Minimum amount before payout is triggered.</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Max QR Codes / Business</label>
                <input type="number" v-model="globalSettings.max_qr_codes_per_business" min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">QR Code Validity</label>
                <div class="flex">
                  <input type="number" v-model="globalSettings.qr_code_validity_days" min="1"
                    class="flex-1 min-w-0 px-3 py-2 border border-r-0 border-gray-300 rounded-l-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                  <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">days</span>
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Session Tracking</label>
                <div class="flex">
                  <input type="number" v-model="globalSettings.session_tracking_hours" min="1" max="168"
                    class="flex-1 min-w-0 px-3 py-2 border border-r-0 border-gray-300 rounded-l-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                  <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">hours</span>
                </div>
                <p class="text-[10px] text-gray-400 mt-0.5">How long a QR scan cookie stays active.</p>
              </div>
            </div>

            <!-- Toggles -->
            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col gap-3">
              <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" v-model="globalSettings.require_business_verification"
                  class="mt-0.5 w-4 h-4 accent-blue-500 shrink-0" />
                <div>
                  <p class="text-xs font-semibold text-gray-700">Require Business Verification</p>
                  <p class="text-[10px] text-gray-400">New affiliates must be approved before earning commissions.</p>
                </div>
              </label>
              <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" v-model="globalSettings.auto_approve_commissions"
                  class="mt-0.5 w-4 h-4 accent-blue-500 shrink-0" />
                <div>
                  <p class="text-xs font-semibold text-gray-700">Auto-Approve Commissions</p>
                  <p class="text-[10px] text-gray-400">Commissions are automatically approved after booking confirmation.</p>
                </div>
              </label>
              <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" v-model="globalSettings.allow_business_override"
                  class="mt-0.5 w-4 h-4 accent-blue-500 shrink-0" />
                <div>
                  <p class="text-xs font-semibold text-gray-700">Allow Per-Business Overrides</p>
                  <p class="text-[10px] text-gray-400">Enable custom commission rates for specific businesses.</p>
                </div>
              </label>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <Button class="bg-blue-500 hover:bg-blue-600 text-white gap-2" :disabled="saving" @click="saveGlobalSettings">
          <Save class="w-4 h-4" />
          {{ saving ? 'Saving...' : 'Save All Settings' }}
        </Button>
      </div>

      <!-- Per-Business Overrides -->
      <Card v-if="globalSettings.allow_business_override && verifiedBusinesses.length > 0">
        <CardHeader class="pb-3">
          <div class="flex items-center gap-2">
            <Building class="w-4.5 h-4.5 text-blue-500" />
            <CardTitle class="text-base font-semibold">Per-Business Overrides</CardTitle>
          </div>
          <p class="text-xs text-gray-400">Set custom rates for specific affiliates. Leave blank to use global settings.</p>
        </CardHeader>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Business</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Effective</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Override Commission</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Override Discount</th>
                  <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="business in verifiedBusinesses" :key="business.id"
                  :class="['border-b border-gray-100 hover:bg-gray-50', hasOverride(business) ? 'bg-blue-50/50' : '']">
                  <td class="px-4 py-3">
                    <div class="font-semibold text-gray-900 text-sm">{{ business.name }}</div>
                    <div class="text-xs text-gray-400">{{ business.contact_email }}</div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-medium">
                      {{ business.business_type || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span :class="['font-bold text-sm', hasOverride(business) ? 'text-blue-600' : 'text-gray-700']">
                      {{ getEffectiveCommission(business) }}%
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex max-w-[140px]">
                      <input type="number" v-model="businessModels[business.id].commission_rate"
                        step="0.1" min="0" max="100"
                        :placeholder="globalSettings.commission_rate + '% (global)'"
                        class="flex-1 min-w-0 px-2 py-1.5 border border-r-0 border-gray-300 rounded-l-md text-xs focus:outline-none focus:border-blue-500" />
                      <span class="px-2 py-1.5 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">%</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex max-w-[140px]">
                      <input type="number" v-model="businessModels[business.id].discount_value"
                        step="0.01" min="0"
                        :placeholder="globalSettings.discount_value + (globalSettings.discount_type === 'percentage' ? '%' : '\u20AC') + ' (global)'"
                        class="flex-1 min-w-0 px-2 py-1.5 border border-r-0 border-gray-300 rounded-l-md text-xs focus:outline-none focus:border-blue-500" />
                      <span class="px-2 py-1.5 bg-gray-100 border border-gray-300 rounded-r-md text-xs text-gray-500">
                        {{ globalSettings.discount_type === 'percentage' ? '%' : '\u20AC' }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <Button size="sm" class="h-7 px-2.5 text-xs bg-blue-500 hover:bg-blue-600 text-white"
                        :disabled="savingBusiness === business.id" @click="saveBusinessModel(business.id)">
                        {{ savingBusiness === business.id ? '...' : 'Save' }}
                      </Button>
                      <Button v-if="hasOverride(business)" variant="outline" size="sm" class="h-7 w-7 p-0"
                        @click="resetBusinessOverride(business.id)" title="Reset to global">
                        <RotateCcw class="w-3 h-3" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <div v-else-if="verifiedBusinesses.length === 0 && globalSettings.allow_business_override"
        class="text-center py-8 text-gray-400 bg-white border rounded-xl">
        <Building class="w-8 h-8 mx-auto mb-2" />
        <p class="text-sm">No verified businesses yet. Overrides will appear here once businesses are approved.</p>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
