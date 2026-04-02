<template>
    <SeoHead :seo="props.seo" />
    <GuestHeader />
        <!-- Dark Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#0a1d28] to-[#153b4f]">
            <div class="absolute top-[5%] left-[-3%] w-[220px] h-[220px] rounded-full bg-cyan-500 opacity-20 blur-[80px] pointer-events-none animate-float"></div>
            <div class="absolute bottom-[5%] right-[-2%] w-[160px] h-[160px] rounded-full bg-cyan-500 opacity-[0.12] blur-[80px] pointer-events-none animate-float-delayed"></div>

            <div class="relative z-10 max-w-[min(92%,1200px)] mx-auto py-8 md:py-12">
                <span class="inline-block text-[0.76rem] font-bold tracking-[0.12em] uppercase text-cyan-400 mb-2">Partner Program</span>
                <h1 class="text-2xl md:text-[2rem] font-[900] text-white mb-1">
                    Become a Vrooem <span class="text-cyan-300">Partner.</span>
                </h1>
                <p class="text-[0.9rem] text-slate-400 leading-relaxed mb-6 max-w-[520px]">
                    Earn commissions on every car rental booking your guests make through your QR codes. Set up takes just 3 minutes.
                </p>

                <!-- Perk Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div v-for="perk in perks" :key="perk.title"
                        class="bg-[rgba(21,59,79,0.28)] backdrop-blur-[16px] border border-[rgba(6,182,212,0.08)] rounded-2xl p-4 text-center transition-all duration-400 hover:border-[rgba(6,182,212,0.18)] hover:shadow-[0_10px_30px_rgba(0,0,0,0.12)]">
                        <div class="w-[42px] h-[42px] rounded-xl flex items-center justify-center text-lg bg-[rgba(6,182,212,0.12)] border border-[rgba(6,182,212,0.1)] mx-auto mb-2.5">
                            <component :is="perkIconMap[perk.icon]" class="w-5 h-5 text-cyan-400" />
                        </div>
                        <h4 class="font-bold text-[0.82rem] text-white mb-0.5">{{ perk.title }}</h4>
                        <p class="text-[0.72rem] text-white/45 leading-snug">{{ perk.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form Section -->
        <section class="bg-gradient-to-b from-slate-50 to-white py-8 md:py-14">
            <div class="max-w-[min(92%,1200px)] mx-auto">
                <div class="af-register-shell">
                    <!-- Left: Form Card -->
                    <div class="af-register-left">
                        <div class="relative rounded-[24px] overflow-hidden bg-white shadow-[0_1px_2px_rgba(21,59,79,0.04),0_4px_16px_rgba(21,59,79,0.06),0_16px_48px_rgba(21,59,79,0.08)] border border-[rgba(15,23,42,0.06)]">
                            <!-- Top gradient bar -->
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#153b4f] via-cyan-500 to-[#2ea7ad]"></div>

                            <div class="p-6 md:p-10">
                                <h2 class="text-xl md:text-[1.4rem] font-[800] text-slate-900 mb-1">
                                    Create your <span class="bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] bg-clip-text text-transparent">partner account.</span>
                                </h2>
                                <p class="text-[0.88rem] text-slate-500 mb-6">Fill in your details to get started. <a :href="route('login', { locale })" class="text-[#2ea7ad] font-semibold no-underline hover:underline">Already a partner? Sign in</a></p>

                                <!-- Step Indicator -->
                                <div class="flex items-center gap-0 mb-7 py-3.5 px-5 bg-slate-50 border border-slate-200 rounded-2xl">
                                    <template v-for="(s, i) in steps" :key="s.label">
                                        <div class="flex items-center gap-2">
                                            <div :class="[
                                                'w-8 h-8 rounded-full flex items-center justify-center font-bold text-[0.78rem] shrink-0 transition-all duration-300',
                                                currentStep > i + 1 ? 'bg-emerald-500 text-white' :
                                                currentStep === i + 1 ? 'bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] text-white shadow-[0_2px_8px_rgba(21,59,79,0.2)]' :
                                                'bg-white text-slate-400 border-[1.5px] border-slate-200'
                                            ]">
                                                <template v-if="currentStep > i + 1">
                                                    <Check class="w-3.5 h-3.5" :stroke-width="3" />
                                                </template>
                                                <template v-else>{{ i + 1 }}</template>
                                            </div>
                                            <span :class="['font-semibold text-[0.76rem]', currentStep >= i + 1 ? 'text-[#153b4f]' : 'text-slate-400']">
                                                {{ s.label }}
                                            </span>
                                        </div>
                                        <div v-if="i < steps.length - 1"
                                            :class="['flex-1 h-0.5 mx-2 rounded-full', currentStep > i + 1 ? 'bg-emerald-500' : 'bg-slate-200']">
                                        </div>
                                    </template>
                                </div>

                                <form @submit.prevent="submitForm">
                                    <!-- Step 1: Account -->
                                    <div v-show="currentStep === 1">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="af-label">First Name</label>
                                                <div class="relative">
                                                    <span class="af-icon"><User class="w-4 h-4" /></span>
                                                    <input v-model="form.first_name" type="text" class="af-input" placeholder="John" />
                                                </div>
                                                <p v-if="stepErrors.first_name || form.errors.first_name" class="text-red-500 text-xs mt-1">{{ stepErrors.first_name || form.errors.first_name }}</p>
                                            </div>
                                            <div>
                                                <label class="af-label">Last Name</label>
                                                <div class="relative">
                                                    <span class="af-icon"><User class="w-4 h-4" /></span>
                                                    <input v-model="form.last_name" type="text" class="af-input" placeholder="Smith" />
                                                </div>
                                                <p v-if="stepErrors.last_name || form.errors.last_name" class="text-red-500 text-xs mt-1">{{ stepErrors.last_name || form.errors.last_name }}</p>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="af-label">Email Address</label>
                                            <div class="relative">
                                                <span class="af-icon"><Mail class="w-4 h-4" /></span>
                                                <input v-model="form.email" type="email" class="af-input" placeholder="you@business.com" />
                                            </div>
                                            <p v-if="stepErrors.email || form.errors.email" class="text-red-500 text-xs mt-1">{{ stepErrors.email || form.errors.email }}</p>
                                        </div>
                                        <div class="mb-4">
                                            <label class="af-label">Password</label>
                                            <div class="relative">
                                                <span class="af-icon"><Lock class="w-4 h-4" /></span>
                                                <input v-model="form.password" type="password" class="af-input" placeholder="Minimum 8 characters" />
                                            </div>
                                            <!-- Password Strength Meter -->
                                            <div class="flex gap-[3px] mt-2">
                                                <div v-for="n in 4" :key="n" :class="['h-1 flex-1 rounded-full', n <= passwordStrength ? strengthColors[passwordStrength] : 'bg-slate-200']"></div>
                                            </div>
                                            <span class="text-[0.75rem] text-slate-400 mt-1 block">{{ passwordStrengthLabel }}</span>
                                            <p v-if="stepErrors.password || form.errors.password" class="text-red-500 text-xs mt-1">{{ stepErrors.password || form.errors.password }}</p>
                                        </div>
                                        <div class="mb-4">
                                            <label class="af-label">Confirm Password</label>
                                            <div class="relative">
                                                <span class="af-icon"><Lock class="w-4 h-4" /></span>
                                                <input v-model="form.password_confirmation" type="password" class="af-input" placeholder="Repeat your password" />
                                            </div>
                                            <p v-if="stepErrors.password_confirmation" class="text-red-500 text-xs mt-1">{{ stepErrors.password_confirmation }}</p>
                                        </div>
                                        <div class="flex justify-end mt-6 pt-4 border-t border-slate-100">
                                            <button type="button" @click="nextStep" :disabled="isValidating" class="af-btn-primary">
                                                <span v-if="isValidating">Validating...</span>
                                                <span v-else>Continue</span>
                                                <ChevronRight v-if="!isValidating" class="w-4 h-4" :stroke-width="2.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Step 2: Business -->
                                    <div v-show="currentStep === 2">
                                        <div class="mb-4">
                                            <label class="af-label">Business Name</label>
                                            <div class="relative">
                                                <span class="af-icon"><Building2 class="w-4 h-4" /></span>
                                                <input v-model="form.business_name" type="text" class="af-input" placeholder="Hotel Playa del Sol" />
                                            </div>
                                            <p v-if="stepErrors.business_name || form.errors.business_name" class="text-red-500 text-xs mt-1">{{ stepErrors.business_name || form.errors.business_name }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="af-label">Business Type</label>
                                                <select v-model="form.business_type" class="af-select">
                                                    <option value="">Select type</option>
                                                    <option value="hotel">Hotel / Resort</option>
                                                    <option value="travel_agency">Travel Agency</option>
                                                    <option value="car_dealership">Car Dealership</option>
                                                    <option value="tour_operator">Tour Operator</option>
                                                    <option value="restaurant">Restaurant</option>
                                                    <option value="retail">Retail Shop</option>
                                                    <option value="other">Other</option>
                                                </select>
                                                <p v-if="stepErrors.business_type || form.errors.business_type" class="text-red-500 text-xs mt-1">{{ stepErrors.business_type || form.errors.business_type }}</p>
                                            </div>
                                            <div>
                                                <label class="af-label">Phone Number</label>
                                                <div class="relative">
                                                    <span class="af-icon"><Phone class="w-4 h-4" /></span>
                                                    <input v-model="form.contact_phone" type="tel" class="af-input" placeholder="+34 612 345 678" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="af-label">City</label>
                                                <div class="relative">
                                                    <span class="af-icon"><MapPin class="w-4 h-4" /></span>
                                                    <input v-model="form.city" type="text" class="af-input" placeholder="Malaga" />
                                                </div>
                                                <p v-if="stepErrors.city" class="text-red-500 text-xs mt-1">{{ stepErrors.city }}</p>
                                            </div>
                                            <div>
                                                <label class="af-label">Country</label>
                                                <input v-model="form.country" type="text" class="af-input" placeholder="Spain" />
                                                <p v-if="stepErrors.country" class="text-red-500 text-xs mt-1">{{ stepErrors.country }}</p>
                                            </div>
                                        </div>
                                        <div class="flex justify-between mt-6 pt-4 border-t border-slate-100">
                                            <button type="button" @click="prevStep" class="af-btn-outline">
                                                <ChevronLeft class="w-4 h-4" :stroke-width="2.5" />
                                                Back
                                            </button>
                                            <button type="button" @click="nextStep" class="af-btn-primary">
                                                Continue
                                                <ChevronRight class="w-4 h-4" :stroke-width="2.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Step 3: Bank Info -->
                                    <div v-show="currentStep === 3">
                                        <div class="mb-4">
                                            <label class="af-label">Bank Name</label>
                                            <div class="relative">
                                                <span class="af-icon"><Landmark class="w-4 h-4" /></span>
                                                <input v-model="form.bank_name" type="text" class="af-input" placeholder="Banco Santander" />
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="af-label">IBAN</label>
                                            <div class="relative">
                                                <span class="af-icon"><CreditCard class="w-4 h-4" /></span>
                                                <input v-model="form.bank_iban" type="text" class="af-input" placeholder="ES91 2100 0418 4502 0005 1332" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="af-label">BIC / SWIFT</label>
                                                <div class="relative">
                                                    <span class="af-icon"><Globe class="w-4 h-4" /></span>
                                                    <input v-model="form.bank_bic" type="text" class="af-input" placeholder="BSCHESMMXXX" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="af-label">Account Holder Name</label>
                                                <div class="relative">
                                                    <span class="af-icon"><User class="w-4 h-4" /></span>
                                                    <input v-model="form.bank_account_name" type="text" class="af-input" placeholder="Hotel Playa del Sol S.L." />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="af-label">Payout Currency</label>
                                            <select v-model="form.currency" class="af-select">
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
                                        <p class="text-[0.75rem] text-slate-400 mt-1">Bank details are used for commission payouts only. Select the currency your bank account uses.</p>
                                        <div class="flex justify-between mt-6 pt-4 border-t border-slate-100">
                                            <button type="button" @click="prevStep" class="af-btn-outline">
                                                <ChevronLeft class="w-4 h-4" :stroke-width="2.5" />
                                                Back
                                            </button>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="nextStep" class="text-sm text-slate-400 hover:text-[#2ea7ad] transition-colors font-medium">Skip this step</button>
                                                <button type="button" @click="nextStep" class="af-btn-primary">
                                                    Continue
                                                    <ChevronRight class="w-4 h-4" :stroke-width="2.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 4: Terms -->
                                    <div v-show="currentStep === 4">
                                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 max-h-[160px] overflow-y-auto text-[0.82rem] text-slate-600 leading-relaxed mb-5">
                                            <strong>Vrooem Partner Agreement</strong><br /><br />
                                            By registering as a Vrooem Partner, you agree to the following terms:<br /><br />
                                            <strong>1. Commission Structure</strong><br />
                                            You will receive a 3% commission on the base price of each eligible booking made through your unique QR codes.<br /><br />
                                            <strong>2. QR Code Usage</strong><br />
                                            QR codes must be displayed only at registered locations. Codes are geo-restricted to ensure fair usage.<br /><br />
                                            <strong>3. Payouts</strong><br />
                                            Commissions are paid monthly via bank transfer, subject to a minimum payout of &euro;50.<br /><br />
                                            <strong>4. Termination</strong><br />
                                            Either party may terminate with 30 days notice.
                                        </div>
                                        <div class="flex items-start gap-2.5 mb-3">
                                            <input v-model="agreeTerms" type="checkbox" class="w-4 h-4 accent-[#153b4f] mt-0.5 shrink-0 cursor-pointer" />
                                            <label class="text-[0.85rem] text-slate-700 cursor-pointer">I agree to the <a href="#" class="text-[#2ea7ad] font-semibold no-underline hover:underline">Partner Terms &amp; Conditions</a></label>
                                        </div>
                                        <div class="flex items-start gap-2.5 mb-3">
                                            <input v-model="agreePrivacy" type="checkbox" class="w-4 h-4 accent-[#153b4f] mt-0.5 shrink-0 cursor-pointer" />
                                            <label class="text-[0.85rem] text-slate-700 cursor-pointer">I agree to the <a href="#" class="text-[#2ea7ad] font-semibold no-underline hover:underline">Privacy Policy</a></label>
                                        </div>
                                        <div class="flex justify-between mt-6 pt-4 border-t border-slate-100">
                                            <button type="button" @click="prevStep" class="af-btn-outline">
                                                <ChevronLeft class="w-4 h-4" :stroke-width="2.5" />
                                                Back
                                            </button>
                                            <button type="submit" :disabled="form.processing || !agreeTerms || !agreePrivacy"
                                                class="af-btn-primary flex-1 ml-4 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                                <span v-if="form.processing" class="flex items-center gap-2">
                                                    <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                                    Creating account...
                                                </span>
                                                <span v-else>Create Partner Account</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Info Panel -->
                    <div class="af-register-right">
                        <transition name="panel-fade" mode="out-in">
                            <!-- Step 1: Why Partner With Us -->
                            <div v-if="currentStep === 1" key="step1" class="af-info-panel">
                                <span class="af-info-eyebrow">Partner Program</span>
                                <h3 class="af-info-title">Why Partner With Vrooem?</h3>
                                <p class="af-info-text">Turn your foot traffic into a revenue stream. Every guest who scans your QR code and books a car earns you a commission.</p>
                                <ul class="af-info-list">
                                    <li>3% commission on every completed booking</li>
                                    <li>No upfront costs or monthly fees</li>
                                    <li>Real-time tracking of scans and earnings</li>
                                    <li>Dedicated partner dashboard</li>
                                </ul>
                                <div class="af-info-stat">
                                    <div class="af-info-stat-value">500+</div>
                                    <div class="af-info-stat-label">Active partners across Europe</div>
                                </div>
                            </div>

                            <!-- Step 2: How It Works -->
                            <div v-else-if="currentStep === 2" key="step2" class="af-info-panel">
                                <span class="af-info-eyebrow">How It Works</span>
                                <h3 class="af-info-title">Simple. Smart. Scalable.</h3>
                                <p class="af-info-text">Set up your business profile and start generating QR codes for your locations in minutes.</p>
                                <div class="af-info-steps">
                                    <div class="af-info-step">
                                        <div class="af-info-step-num">1</div>
                                        <div>
                                            <strong>Register your business</strong>
                                            <p>Add your business details and locations</p>
                                        </div>
                                    </div>
                                    <div class="af-info-step">
                                        <div class="af-info-step-num">2</div>
                                        <div>
                                            <strong>Generate QR codes</strong>
                                            <p>Create geo-targeted codes for each location</p>
                                        </div>
                                    </div>
                                    <div class="af-info-step">
                                        <div class="af-info-step-num">3</div>
                                        <div>
                                            <strong>Earn commissions</strong>
                                            <p>Get paid for every booking from your QR codes</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Secure Payouts -->
                            <div v-else-if="currentStep === 3" key="step3" class="af-info-panel">
                                <span class="af-info-eyebrow">Payouts</span>
                                <h3 class="af-info-title">Secure & Reliable Payouts</h3>
                                <p class="af-info-text">Your bank details are encrypted and used exclusively for commission transfers. We never share your financial information.</p>
                                <ul class="af-info-list">
                                    <li>Monthly automatic bank transfers</li>
                                    <li>Minimum payout threshold: &euro;50</li>
                                    <li>Detailed payout reports in your dashboard</li>
                                    <li>You can update bank details anytime</li>
                                </ul>
                                <div class="af-info-note">
                                    <Info class="w-4 h-4 shrink-0 mt-0.5" />
                                    <span>You can skip this step now and add bank details later from your dashboard settings.</span>
                                </div>
                            </div>

                            <!-- Step 4: What Happens Next -->
                            <div v-else key="step4" class="af-info-panel">
                                <span class="af-info-eyebrow">Almost There</span>
                                <h3 class="af-info-title">What Happens Next?</h3>
                                <p class="af-info-text">After you submit, our team reviews your application. Here's the timeline:</p>
                                <div class="af-info-timeline">
                                    <div class="af-info-timeline-item">
                                        <div class="af-info-timeline-dot af-info-timeline-dot--active"></div>
                                        <div>
                                            <strong>Submit application</strong>
                                            <p>You're here now</p>
                                        </div>
                                    </div>
                                    <div class="af-info-timeline-item">
                                        <div class="af-info-timeline-dot"></div>
                                        <div>
                                            <strong>Review (1-2 business days)</strong>
                                            <p>Our team verifies your details</p>
                                        </div>
                                    </div>
                                    <div class="af-info-timeline-item">
                                        <div class="af-info-timeline-dot"></div>
                                        <div>
                                            <strong>Approval email</strong>
                                            <p>You'll be notified when approved</p>
                                        </div>
                                    </div>
                                    <div class="af-info-timeline-item">
                                        <div class="af-info-timeline-dot"></div>
                                        <div>
                                            <strong>Start earning</strong>
                                            <p>Create QR codes and earn commissions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <p class="text-center mt-5 text-[0.85rem] text-slate-500">
                    Already have an account? <a :href="route('login', { locale })" class="text-[#2ea7ad] font-semibold no-underline hover:underline">Sign in</a>
                </p>
            </div>
        </section>

        <Footer />
        <Toaster position="bottom-right" />
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/Components/ui/sonner';
import SeoHead from '@/Components/SeoHead.vue';
import axios from 'axios';
import GuestHeader from '@/Layouts/GuestHeader.vue';
import Footer from '@/Components/Footer.vue';
import { Check, User, Mail, Lock, ChevronRight, ChevronLeft, Building2, Phone, MapPin, Landmark, CreditCard, Globe, Info, DollarSign, Smartphone, BarChart3 } from 'lucide-vue-next';

const props = defineProps({
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'en');

const perks = [
    { icon: 'DollarSign', title: 'Earn Commissions', desc: 'Get paid for every booking from your location.' },
    { icon: 'Smartphone', title: 'Smart QR Codes', desc: 'Geo-targeted with real-time analytics.' },
    { icon: 'BarChart3', title: 'Live Dashboard', desc: 'Track scans, bookings & revenue.' },
    { icon: 'Landmark', title: 'Bank Payouts', desc: 'Direct monthly bank transfers.' },
];

const perkIconMap = { DollarSign, Smartphone, BarChart3, Landmark };

const steps = [
    { label: 'Account' },
    { label: 'Business' },
    { label: 'Bank Info' },
    { label: 'Terms' },
];

const currentStep = ref(1);
const agreeTerms = ref(false);
const agreePrivacy = ref(false);
const isValidating = ref(false);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    business_name: '',
    business_type: '',
    contact_phone: '',
    city: '',
    country: '',
    bank_name: '',
    bank_iban: '',
    bank_bic: '',
    bank_account_name: '',
    currency: 'EUR',
});

const strengthColors = {
    1: 'bg-red-400',
    2: 'bg-amber-400',
    3: 'bg-cyan-400',
    4: 'bg-emerald-500',
};

const passwordStrength = computed(() => {
    const pw = form.password;
    if (!pw) return 0;
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return score;
});

const passwordStrengthLabel = computed(() => {
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    return labels[passwordStrength.value] || '';
});

const stepErrors = ref({});

const validateStep = (step) => {
    const errors = {};

    if (step === 1) {
        if (!form.first_name.trim()) errors.first_name = 'First name is required.';
        if (!form.last_name.trim()) errors.last_name = 'Last name is required.';
        if (!form.email.trim()) {
            errors.email = 'Email is required.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            errors.email = 'Enter a valid email address.';
        }
        if (!form.password) {
            errors.password = 'Password is required.';
        } else if (form.password.length < 8) {
            errors.password = 'Password must be at least 8 characters.';
        }
        if (form.password !== form.password_confirmation) {
            errors.password_confirmation = 'Passwords do not match.';
        }
    }

    if (step === 2) {
        if (!form.business_name.trim()) errors.business_name = 'Business name is required.';
        if (!form.business_type) errors.business_type = 'Select a business type.';
        if (!form.city.trim()) errors.city = 'City is required.';
        if (!form.country.trim()) errors.country = 'Country is required.';
    }

    stepErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const nextStep = async () => {
    if (!validateStep(currentStep.value)) return;

    // Server-side email uniqueness check at Step 1
    if (currentStep.value === 1) {
        isValidating.value = true;
        try {
            await axios.post(route('validate-email'), { email: form.email });
            // Email is available, proceed
            isValidating.value = false;
            currentStep.value++;
        } catch (error) {
            isValidating.value = false;
            if (error.response?.data?.errors?.email) {
                stepErrors.value = { ...stepErrors.value, email: error.response.data.errors.email[0] };
            } else {
                stepErrors.value = { ...stepErrors.value, email: 'This email is already taken.' };
            }
            return;
        }
    } else {
        if (currentStep.value < 4) currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        stepErrors.value = {};
        currentStep.value--;
    }
};

const submitForm = () => {
    form.post(route('affiliate.register.store', { locale: locale.value }), {
        onSuccess: () => {
            toast.success('Account created! Your application is pending approval.');
        },
        onError: (errors) => {
            const errorFields = Object.keys(errors);
            if (errorFields.some(f => ['first_name', 'last_name', 'email', 'password', 'password_confirmation'].includes(f))) {
                currentStep.value = 1;
            } else if (errorFields.some(f => ['business_name', 'business_type', 'city', 'country'].includes(f))) {
                currentStep.value = 2;
            } else if (errorFields.some(f => ['bank_name', 'bank_iban', 'bank_bic', 'bank_account_name', 'currency'].includes(f))) {
                currentStep.value = 3;
            }
            toast.error('Please fix the errors and try again.');
        },
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

/* Two-column layout */
.af-register-shell {
    display: flex;
    gap: 2rem;
    align-items: stretch;
}

.af-register-left {
    flex: 1;
    min-width: 0;
}

.af-register-right {
    flex: 0 0 34%;
    border-radius: 24px;
    background: linear-gradient(160deg, #0f2936, #153b4f 55%, #1c4d66);
    color: #fff;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 2rem;
    box-shadow: 0 24px 48px rgba(15, 41, 54, 0.25);
}

.af-register-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 20%, rgba(6, 182, 212, 0.3), transparent 55%);
    opacity: 0.6;
}

/* Info panel content */
.af-info-panel {
    position: relative;
    z-index: 1;
    text-align: left;
}

.af-info-eyebrow {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-weight: 600;
    color: rgba(6, 182, 212, 0.9);
}

.af-info-title {
    margin-top: 0.75rem;
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.25;
}

.af-info-text {
    margin-top: 1rem;
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.9rem;
    line-height: 1.65;
}

.af-info-list {
    margin-top: 1.5rem;
    display: grid;
    gap: 0.65rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.85rem;
    list-style: none;
    padding: 0;
}

.af-info-list li {
    position: relative;
    padding-left: 1.25rem;
    line-height: 1.5;
}

.af-info-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.45rem;
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: rgba(6, 182, 212, 0.8);
}

/* Stat highlight */
.af-info-stat {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.af-info-stat-value {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #06b6d4, #2dd4bf);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.af-info-stat-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 0.15rem;
}

/* How it works steps */
.af-info-steps {
    margin-top: 1.5rem;
    display: grid;
    gap: 1.25rem;
}

.af-info-step {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
}

.af-info-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(6, 182, 212, 0.15);
    border: 1px solid rgba(6, 182, 212, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: #06b6d4;
    flex-shrink: 0;
}

.af-info-step strong {
    display: block;
    font-size: 0.88rem;
    color: #fff;
}

.af-info-step p {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.55);
    margin-top: 0.15rem;
}

/* Info note */
.af-info-note {
    margin-top: 1.75rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.85rem;
    border-radius: 12px;
    background: rgba(6, 182, 212, 0.08);
    border: 1px solid rgba(6, 182, 212, 0.15);
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.78rem;
    line-height: 1.5;
}

/* Timeline */
.af-info-timeline {
    margin-top: 1.5rem;
    display: grid;
    gap: 0;
}

.af-info-timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding-bottom: 1.25rem;
    position: relative;
}

.af-info-timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 16px;
    bottom: 0;
    width: 1px;
    background: rgba(6, 182, 212, 0.2);
}

.af-info-timeline-item:last-child {
    padding-bottom: 0;
}

.af-info-timeline-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    border: 2px solid rgba(6, 182, 212, 0.4);
    background: transparent;
    flex-shrink: 0;
    margin-top: 3px;
}

.af-info-timeline-dot--active {
    background: #06b6d4;
    border-color: #06b6d4;
    box-shadow: 0 0 8px rgba(6, 182, 212, 0.5);
}

.af-info-timeline-item strong {
    display: block;
    font-size: 0.85rem;
    color: #fff;
}

.af-info-timeline-item p {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 0.1rem;
}

/* Panel transition */
.panel-fade-enter-active,
.panel-fade-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.panel-fade-enter-from,
.panel-fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Form fields */
.af-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.af-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    color: #94a3b8;
    pointer-events: none;
    transition: color 0.25s;
}
.af-input {
    width: 100%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    color: #0f172a;
    padding: 0.8rem 1rem;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
}
.af-input:hover { border-color: #153b4f; }
.af-input:focus { border-color: #153b4f; box-shadow: 0 0 0 3px rgba(21,59,79,0.08); background: #fff; }
.af-input::placeholder { color: #94a3b8; }
.af-icon + .af-input { padding-left: 2.6rem; }

.af-select {
    width: 100%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    color: #0f172a;
    padding: 0.8rem 1rem;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}
.af-select:hover { border-color: #153b4f; }
.af-select:focus { border-color: #153b4f; box-shadow: 0 0 0 3px rgba(21,59,79,0.08); background: #fff; }

.af-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.85rem 1.75rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #153b4f, #245f7d);
    box-shadow: 0 12px 24px rgba(21,59,79,0.2);
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.af-btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 18px 32px rgba(21,59,79,0.25); }
.af-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.af-btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.85rem 1.75rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: #153b4f;
    background: rgba(255,255,255,0.7);
    border: 1px solid rgba(21,59,79,0.3);
    border-radius: 14px;
    cursor: pointer;
    transition: border-color 0.25s, background 0.25s;
}
.af-btn-outline:hover { border-color: #153b4f; background: rgba(21,59,79,0.06); }

/* Responsive: stack on smaller screens */
@media (max-width: 1024px) {
    .af-register-shell {
        flex-direction: column;
    }
    .af-register-right {
        flex: none;
        padding: 2rem 1.5rem;
    }
}

@media (max-width: 640px) {
    .af-register-right {
        display: none;
    }
}
</style>
