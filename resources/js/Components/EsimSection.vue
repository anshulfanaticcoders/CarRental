<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
// Import the background image
import abstractWaveBg from '../../assets/abstract-wave-min.png'

const page = usePage()
const currentLocale = computed(() => page.props.locale || 'en')

// Reactive data
const countries = ref([])
const plans = ref([])
const selectedCountry = ref('')
const selectedPlan = ref('')
const isLoadingCountries = ref(false)
const isLoadingPlans = ref(false)
const isProcessingOrder = ref(false)
const customerName = ref('')
const customerEmail = ref('')

// Form validation
const errors = ref({
    country: '',
    plan: '',
    email: '',
    name: ''
})

// Fetch countries on component mount
onMounted(async () => {
    await fetchCountries()
})

const fetchCountries = async () => {
    isLoadingCountries.value = true
    try {
        const response = await axios.get(`/${currentLocale.value}/api/esim/countries`)
        if (response.data.success) {
            countries.value = response.data.data
        } else {
            console.error('Failed to fetch countries:', response.data.message)
        }
    } catch (error) {
        console.error('Error fetching countries:', error)
    } finally {
        isLoadingCountries.value = false
    }
}

const fetchPlans = async (countryCode) => {
    if (!countryCode) {
        plans.value = []
        return
    }

    isLoadingPlans.value = true
    selectedPlan.value = ''
    errors.value.plan = ''

    try {
        const response = await axios.get(`/${currentLocale.value}/api/esim/plans/${countryCode}`)
        if (response.data.success) {
            plans.value = response.data.data
        } else {
            console.error('Failed to fetch plans:', response.data.message)
            plans.value = []
        }
    } catch (error) {
        console.error('Error fetching plans:', error)
        plans.value = []
    } finally {
        isLoadingPlans.value = false
    }
}

const handleCountryChange = (countryCode) => {
    selectedCountry.value = countryCode
    errors.value.country = ''
    fetchPlans(countryCode)
}

const handlePlanChange = (planId) => {
    selectedPlan.value = planId
    errors.value.plan = ''
}

const validateForm = () => {
    let isValid = true

    // Reset errors
    errors.value = { country: '', plan: '', email: '', name: '' }

    if (!selectedCountry.value) {
        errors.value.country = 'Please select a country'
        isValid = false
    }

    if (!selectedPlan.value) {
        errors.value.plan = 'Please select a plan'
        isValid = false
    }

    if (!customerName.value.trim()) {
        errors.value.name = 'Please enter your name'
        isValid = false
    }

    if (!customerEmail.value.trim()) {
        errors.value.email = 'Please enter your email'
        isValid = false
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail.value)) {
        errors.value.email = 'Please enter a valid email address'
        isValid = false
    }

    return isValid
}

const submitOrder = async () => {
    if (!validateForm()) {
        return
    }

    isProcessingOrder.value = true

    try {
        // Get the selected plan details to ensure we have the correct slug
        const selectedPlanDetails = getSelectedPlanDetails()
        const planId = selectedPlanDetails?.slug || selectedPlan.value

        const orderData = {
            country_code: selectedCountry.value,
            plan_id: planId,
            customer_name: customerName.value,
            email: customerEmail.value
        }

        const response = await axios.post(`/${currentLocale.value}/api/esim/order`, orderData)

        if (response.data.success) {
            // Redirect to Stripe checkout
            window.location.href = response.data.checkout_url
        } else {
            console.error('Order creation failed:', response.data.message)
            alert('Failed to process order. Please try again.')
        }
    } catch (error) {
        console.error('Error creating order:', error)
        alert('An error occurred while processing your order. Please try again.')
    } finally {
        isProcessingOrder.value = false
    }
}

const getSelectedPlanDetails = () => {
    return plans.value.find(plan => plan.slug === selectedPlan.value || plan.packageCode === selectedPlan.value || plan.code === selectedPlan.value)
}

const formatData = (bytes) => {
    if (!bytes) return 'N/A'

    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
    if (bytes === 0) return '0 Bytes'

    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}


// Translation helper
const _t = (key) => {
    const { props } = usePage()
    if (props.translations && props.translations.esim && props.translations.esim[key]) {
        return props.translations.esim[key]
    }
    return key
}
</script>

<template>
    <section class="py-customVerticalSpacing relative bg-cover bg-center bg-no-repeat" :style="`background-image: url('${abstractWaveBg}');`">
        <!-- Dark overlay for better text visibility -->
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="full-w-container relative z-10">
            <div class="text-center mb-12">
                <span class="text-[1.25rem] text-white">- Stay Connected -</span>
                <h2 class="text-[3rem] font-bold text-white max-[768px]:text-[2rem] mt-2">
                    {{ _t('title') || 'Get Your eSIM Instantly' }}
                </h2>
                <p class="text-lg text-white/90 mt-4 max-w-2xl mx-auto">
                    {{ _t('subtitle') || 'Travel ready with instant eSIM activation. No physical SIM, no hassle. Just select your destination and stay connected.' }}
                </p>
            </div>

            <Card class="max-w-2xl mx-auto shadow-lg bg-white/95 backdrop-blur-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-center text-xl font-bold text-gray-800">
                        {{ _t('form_title') || 'Choose Your eSIM Plan' }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6 pb-6">
                    <!-- Country Selection -->
                    <div class="space-y-2">
                        <Label for="country" class="text-sm font-medium text-gray-700">
                            {{ _t('select_country') || 'Select Country' }}
                        </Label>
                        <Select v-model="selectedCountry" @update:model-value="handleCountryChange" :disabled="isLoadingCountries">
                            <SelectTrigger class="w-full h-12" :class="{ 'border-red-500': errors.country }">
                                <SelectValue :placeholder="_t('country_placeholder') || 'Choose your destination country...'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="country in countries" :key="country.code" :value="country.code">
                                    {{ country.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.country" class="text-sm text-red-500">{{ errors.country }}</p>
                        <div v-if="isLoadingCountries" class="flex items-center gap-2 text-sm text-gray-500">
                            <Loader2 class="w-4 h-4 animate-spin" />
                            {{ _t('loading_countries') || 'Loading countries...' }}
                        </div>
                    </div>

                    <!-- Plan Selection -->
                    <div class="space-y-2" v-if="selectedCountry">
                        <Label for="plan" class="text-sm font-medium text-gray-700">
                            {{ _t('select_plan') || 'Select Plan' }}
                        </Label>
                        <Select v-model="selectedPlan" @update:model-value="handlePlanChange" :disabled="isLoadingPlans || !selectedCountry">
                            <SelectTrigger class="w-full h-12" :class="{ 'border-red-500': errors.plan }">
                                <SelectValue :placeholder="_t('plan_placeholder') || 'Choose a data plan...'" />
                            </SelectTrigger>
                            <SelectContent class="max-h-60">
                                <SelectItem v-for="plan in plans" :key="plan.slug || plan.packageCode || plan.code || plan.id" :value="plan.slug || plan.packageCode || plan.code || plan.id" class="py-3">
                                    <div class="flex flex-col items-start">
                                        <span class="font-medium text-sm leading-tight">{{ plan.name }}</span>
                                        <span class="text-xs text-green-600 font-semibold mt-1">
                                            ${{ (plan.price / 10000).toFixed(2) }}
                                        </span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.plan" class="text-sm text-red-500">{{ errors.plan }}</p>
                        <div v-if="isLoadingPlans" class="flex items-center gap-2 text-sm text-gray-500">
                            <Loader2 class="w-4 h-4 animate-spin" />
                            {{ _t('loading_plans') || 'Loading plans...' }}
                        </div>
                    </div>

                    
                    <!-- Customer Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-sm font-medium text-gray-700">
                                {{ _t('your_name') || 'Your Name' }}
                            </Label>
                            <Input
                                id="name"
                                v-model="customerName"
                                type="text"
                                :placeholder="_t('name_placeholder') || 'Enter your full name'"
                                class="h-12"
                                :class="{ 'border-red-500': errors.name }"
                            />
                            <p v-if="errors.name" class="text-sm text-red-500">{{ errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email" class="text-sm font-medium text-gray-700">
                                {{ _t('email_address') || 'Email Address' }}
                            </Label>
                            <Input
                                id="email"
                                v-model="customerEmail"
                                type="email"
                                :placeholder="_t('email_placeholder') || 'Enter your email for eSIM delivery'"
                                class="h-12"
                                :class="{ 'border-red-500': errors.email }"
                            />
                            <p v-if="errors.email" class="text-sm text-red-500">{{ errors.email }}</p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        @click="submitOrder"
                        class="w-full"
                        size="lg"
                        :disabled="isProcessingOrder || !selectedCountry || !selectedPlan || !customerName || !customerEmail"
                    >
                        <Loader2 v-if="isProcessingOrder" class="w-4 h-4 mr-2 animate-spin" />
                        <span v-if="!isProcessingOrder" class="font-semibold">
                            {{ _t('get_esim') || 'Get eSIM Now' }}
                        </span>
                        <span v-else>
                            {{ _t('processing') || 'Processing...' }}
                        </span>
                    </Button>

                    <!-- Info Message -->
                    <div class="text-center text-sm text-gray-500">
                        <p>{{ _t('delivery_info') || 'Your eSIM will be delivered instantly via email after payment.' }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </section>
</template>