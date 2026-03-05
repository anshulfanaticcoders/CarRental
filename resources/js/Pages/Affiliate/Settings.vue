<template>
    <AffiliateHeader currentPage="settings" />
        <!-- Pending Approval Banner -->
        <div v-if="!isVerified" class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-[min(92%,1200px)] mx-auto py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-bold text-amber-800">Account Pending Approval</span>
                    <span class="text-xs text-amber-600 ml-1">— Your partner account is being reviewed. You'll be notified by email once approved. Some features are limited.</span>
                </div>
            </div>
        </div>

        <!-- Section 1: Dark Hero Header -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#0a1d28] to-[#153b4f]">
            <!-- Decorative orbs -->
            <div class="absolute top-[5%] left-[-3%] w-[220px] h-[220px] rounded-full bg-cyan-500 opacity-20 blur-[80px] pointer-events-none animate-float"></div>
            <div class="absolute bottom-[5%] right-[-2%] w-[160px] h-[160px] rounded-full bg-cyan-500 opacity-[0.12] blur-[80px] pointer-events-none animate-float-delayed"></div>

            <div class="relative z-10 max-w-[min(92%,1200px)] mx-auto py-4 md:py-6">
                <h1 class="text-2xl md:text-3xl font-extrabold text-white">Settings</h1>
                <p class="text-slate-400 text-sm mt-1">Manage your profile, bank details, and account.</p>
            </div>
        </section>

        <!-- Section 2: Settings Content -->
        <section class="bg-gradient-to-b from-slate-50 to-white min-h-[40vh]">
            <div class="max-w-[min(92%,1200px)] mx-auto py-4 md:py-6">

                <!-- Flash Success Message -->
                <div v-if="$page.props.flash?.success"
                    class="mb-5 flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $page.props.flash.success }}
                </div>

                <!-- Tab Navigation -->
                <div class="flex border-b border-slate-200 mb-5 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-all duration-200',
                            activeTab === tab.key
                                ? 'text-[#153b4f] border-[#2ea7ad]'
                                : 'text-slate-400 border-transparent hover:text-slate-600'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Account Status Banner -->
                <div v-if="business.verification_status === 'verified'" class="flex items-center gap-3.5 px-4 py-3.5 bg-emerald-50 border border-emerald-200 rounded-xl mb-5">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-emerald-800">Account Verified & Active</h4>
                        <p class="text-xs text-emerald-600">
                            Verified on {{ business.verified_at ? new Date(business.verified_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A' }}. Earning commissions.
                        </p>
                    </div>
                </div>
                <div v-else class="flex items-center gap-3.5 px-4 py-3.5 bg-amber-50 border border-amber-200 rounded-xl mb-5">
                    <div class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-amber-800">Verification Pending</h4>
                        <p class="text-xs text-amber-600">Your account is under review. Commissions will be activated once verified.</p>
                    </div>
                </div>

                <!-- Tab 1: Business Info -->
                <div v-if="activeTab === 'business'" class="bg-white border border-slate-100 rounded-2xl shadow-sm shadow-[rgba(21,59,79,0.06)] p-5 md:p-6">
                    <h3 class="text-[0.95rem] font-bold text-[#153b4f]">Business Information</h3>
                    <p class="text-[0.78rem] text-slate-500 mb-5">Update your business details.</p>

                    <form @submit.prevent="submitBusiness">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Business Name</label>
                                <input v-model="businessForm.business_name" type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                <p v-if="businessForm.errors.business_name" class="text-red-500 text-xs mt-1">{{ businessForm.errors.business_name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Business Type</label>
                                <select v-model="businessForm.business_type"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white">
                                    <option value="">Select type</option>
                                    <option value="hotel">Hotel / Resort</option>
                                    <option value="travel_agency">Travel Agency</option>
                                    <option value="car_dealership">Car Dealership</option>
                                    <option value="tour_operator">Tour Operator</option>
                                    <option value="restaurant">Restaurant</option>
                                    <option value="retail">Retail Shop</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact Email</label>
                                <input v-model="businessForm.contact_email" type="email"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Phone</label>
                                <input v-model="businessForm.contact_phone" type="tel"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                            <input v-model="businessForm.legal_address" type="text"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">City</label>
                                <input v-model="businessForm.city" type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Country</label>
                                <input v-model="businessForm.country" type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 mt-5 pt-4 border-t border-slate-100">
                            <button type="button" @click="resetBusinessForm"
                                class="px-5 py-2.5 text-sm font-bold text-[#153b4f] bg-transparent border border-slate-200 rounded-xl transition-all duration-200 hover:border-[#153b4f]">
                                Cancel
                            </button>
                            <button type="submit" :disabled="businessForm.processing"
                                class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-all duration-200 hover:-translate-y-0.5 disabled:opacity-50"
                                style="background: linear-gradient(135deg, #153b4f, #2ea7ad); box-shadow: 0 4px 14px rgba(21,59,79,0.18);">
                                <span v-if="businessForm.processing">Saving...</span>
                                <span v-else>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Bank Details -->
                <div v-if="activeTab === 'bank'" class="bg-white border border-slate-100 rounded-2xl shadow-sm shadow-[rgba(21,59,79,0.06)] p-5 md:p-6">
                    <h3 class="text-[0.95rem] font-bold text-[#153b4f]">Bank Details</h3>
                    <p class="text-[0.78rem] text-slate-500 mb-5">For commission payouts.</p>

                    <form @submit.prevent="submitBank">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Bank Name</label>
                                <input v-model="bankForm.bank_name" type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Account Holder</label>
                                <input v-model="bankForm.bank_account_name" type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">IBAN</label>
                            <input v-model="bankForm.bank_iban" type="text"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">BIC / SWIFT</label>
                                <input v-model="bankForm.bank_bic" type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payout Currency</label>
                                <select v-model="bankForm.payout_currency"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white">
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="CHF">CHF - Swiss Franc</option>
                                    <option value="SEK">SEK - Swedish Krona</option>
                                    <option value="NOK">NOK - Norwegian Krone</option>
                                    <option value="DKK">DKK - Danish Krone</option>
                                    <option value="PLN">PLN - Polish Zloty</option>
                                    <option value="CZK">CZK - Czech Koruna</option>
                                    <option value="HUF">HUF - Hungarian Forint</option>
                                    <option value="TRY">TRY - Turkish Lira</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 mt-5 pt-4 border-t border-slate-100">
                            <button type="button" @click="resetBankForm"
                                class="px-5 py-2.5 text-sm font-bold text-[#153b4f] bg-transparent border border-slate-200 rounded-xl transition-all duration-200 hover:border-[#153b4f]">
                                Cancel
                            </button>
                            <button type="submit" :disabled="bankForm.processing"
                                class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-all duration-200 hover:-translate-y-0.5 disabled:opacity-50"
                                style="background: linear-gradient(135deg, #153b4f, #2ea7ad); box-shadow: 0 4px 14px rgba(21,59,79,0.18);">
                                <span v-if="bankForm.processing">Updating...</span>
                                <span v-else>Update</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 3: Password -->
                <div v-if="activeTab === 'password'" class="bg-white border border-slate-100 rounded-2xl shadow-sm shadow-[rgba(21,59,79,0.06)] p-5 md:p-6">
                    <h3 class="text-[0.95rem] font-bold text-[#153b4f]">Change Password</h3>
                    <p class="text-[0.78rem] text-slate-500 mb-5">Keep your account secure.</p>

                    <form @submit.prevent="submitPassword">
                        <div class="max-w-[380px]">
                            <div class="mb-4">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Current Password</label>
                                <input v-model="passwordForm.current_password" type="password"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white"
                                    placeholder="Enter current password" />
                                <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">New Password</label>
                                <input v-model="passwordForm.password" type="password"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white"
                                    placeholder="Minimum 8 characters" />
                                <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Confirm Password</label>
                                <input v-model="passwordForm.password_confirmation" type="password"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all duration-200 hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white"
                                    placeholder="Repeat new password" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 mt-5 pt-4 border-t border-slate-100">
                            <button type="submit" :disabled="passwordForm.processing"
                                class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-all duration-200 hover:-translate-y-0.5 disabled:opacity-50"
                                style="background: linear-gradient(135deg, #153b4f, #2ea7ad); box-shadow: 0 4px 14px rgba(21,59,79,0.18);">
                                <span v-if="passwordForm.processing">Updating...</span>
                                <span v-else>Update Password</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 4: Commission Terms (Read-Only) -->
                <div v-if="activeTab === 'terms'" class="bg-white border border-slate-100 rounded-2xl shadow-sm shadow-[rgba(21,59,79,0.06)] p-5 md:p-6">
                    <h3 class="text-[0.95rem] font-bold text-[#153b4f]">Commission Terms</h3>
                    <p class="text-[0.78rem] text-slate-500 mb-5">Your current rates and terms.</p>

                    <div class="bg-sky-50 border border-sky-100 rounded-xl p-4">
                        <div class="divide-y divide-sky-100">
                            <div class="flex items-center justify-between py-3 first:pt-0">
                                <span class="text-sm text-slate-600">Commission Rate</span>
                                <span class="text-sm font-bold text-[#153b4f]">{{ commissionTerms?.commission_rate ?? 3 }}%</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-slate-600">Payout Method</span>
                                <span class="text-sm font-bold text-[#153b4f]">Bank Transfer</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-slate-600">Min Payout</span>
                                <span class="text-sm font-bold text-[#153b4f]">&euro;{{ commissionTerms?.payout_threshold ?? 50 }}</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-slate-600">Schedule</span>
                                <span class="text-sm font-bold text-[#153b4f]">Monthly</span>
                            </div>
                            <div class="flex items-center justify-between py-3 last:pb-0">
                                <span class="text-sm text-slate-600">Tracking Window</span>
                                <span class="text-sm font-bold text-[#153b4f]">30 days</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <Toaster position="bottom-right" />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/Components/ui/sonner';
import AffiliateHeader from '@/Layouts/AffiliateHeader.vue';


const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isVerified = computed(() => page.props.affiliateVerificationStatus === 'verified');

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}, { immediate: true });

const props = defineProps({
    business: Object,
    user: Object,
    commissionTerms: Object,
});

const activeTab = ref('business');

const tabs = [
    { key: 'business', label: 'Business Info' },
    { key: 'bank', label: 'Bank Details' },
    { key: 'password', label: 'Password' },
    { key: 'terms', label: 'Commission Terms' },
];

const businessForm = useForm({
    business_name: props.business.name || '',
    business_type: props.business.business_type || '',
    contact_email: props.business.contact_email || '',
    contact_phone: props.business.contact_phone || '',
    city: props.business.city || '',
    country: props.business.country || '',
    legal_address: props.business.legal_address || '',
});

const submitBusiness = () => {
    businessForm.put(route('affiliate.settings.update', { locale: locale.value }), { preserveScroll: true });
};

const resetBusinessForm = () => {
    businessForm.business_name = props.business.name || '';
    businessForm.business_type = props.business.business_type || '';
    businessForm.contact_email = props.business.contact_email || '';
    businessForm.contact_phone = props.business.contact_phone || '';
    businessForm.city = props.business.city || '';
    businessForm.country = props.business.country || '';
    businessForm.legal_address = props.business.legal_address || '';
    businessForm.clearErrors();
};

const bankForm = useForm({
    bank_name: props.business.bank_name || '',
    bank_iban: props.business.bank_iban || '',
    bank_bic: props.business.bank_bic || '',
    bank_account_name: props.business.bank_account_name || '',
    payout_currency: props.business.currency || 'EUR',
});

const submitBank = () => {
    bankForm.put(route('affiliate.bank-details.update', { locale: locale.value }), { preserveScroll: true });
};

const resetBankForm = () => {
    bankForm.bank_name = props.business.bank_name || '';
    bankForm.bank_iban = props.business.bank_iban || '';
    bankForm.bank_bic = props.business.bank_bic || '';
    bankForm.bank_account_name = props.business.bank_account_name || '';
    bankForm.payout_currency = props.business.currency || 'EUR';
    bankForm.clearErrors();
};

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put(route('affiliate.password.update', { locale: locale.value }), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-14px) scale(1.04); }
}
.animate-float { animation: float 14s ease-in-out infinite; }
.animate-float-delayed { animation: float 14s ease-in-out infinite; animation-delay: -7s; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
