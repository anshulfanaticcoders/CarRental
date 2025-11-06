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
        const orderData = {
            country_code: selectedCountry.value,
            plan_id: selectedPlan.value,
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
    return plans.value.find(plan => plan.id === selectedPlan.value)
}

const formatPrice = (price, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency.toUpperCase()
    }).format(price)
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
    <section class="py-customVerticalSpacing bg-gray-50">
        <div class="full-w-container">
            <div class="text-center mb-12">
                <span class="text-[1.25rem] text-customPrimaryColor">- Stay Connected -</span>
                <h2 class="text-[3rem] font-bold text-customDarkBlackColor max-[768px]:text-[2rem] mt-2">
                    {{ _t('title') || 'Get Your eSIM Instantly' }}
                </h2>
                <p class="text-lg text-customLightGrayColor mt-4 max-w-2xl mx-auto">
                    {{ _t('subtitle') || 'Travel ready with instant eSIM activation. No physical SIM, no hassle. Just select your destination and stay connected.' }}
                </p>
            </div>

            <Card class="max-w-2xl mx-auto">
                <CardHeader>
                    <CardTitle class="text-center text-xl">
                        {{ _t('form_title') || 'Choose Your eSIM Plan' }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Country Selection -->
                    <div class="space-y-2">
                        <Label for="country">
                            {{ _t('select_country') || 'Select Country' }}
                        </Label>
                        <Select v-model="selectedCountry" @update:model-value="handleCountryChange" :disabled="isLoadingCountries">
                            <SelectTrigger class="w-full" :class="{ 'border-red-500': errors.country }">
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
                        <Label for="plan">
                            {{ _t('select_plan') || 'Select Plan' }}
                        </Label>
                        <Select v-model="selectedPlan" @update:model-value="handlePlanChange" :disabled="isLoadingPlans || !selectedCountry">
                            <SelectTrigger class="w-full" :class="{ 'border-red-500': errors.plan }">
                                <SelectValue :placeholder="_t('plan_placeholder') || 'Choose a data plan...'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="plan in plans" :key="plan.id" :value="plan.id">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ plan.name }}</span>
                                        <span class="text-sm text-gray-500">
                                            {{ plan.data_amount }} - {{ plan.validity }} - {{ formatPrice(plan.price, plan.currency) }}
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

                    <!-- Plan Details -->
                    <div v-if="getSelectedPlanDetails()" class="p-4 bg-blue-50 rounded-lg">
                        <h4 class="font-medium text-blue-900 mb-2">{{ _t('plan_details') || 'Plan Details' }}</h4>
                        <div class="space-y-1 text-sm text-blue-800">
                            <p><strong>{{ _t('data') || 'Data' }}:</strong> {{ getSelectedPlanDetails().data_amount }}</p>
                            <p><strong>{{ _t('validity') || 'Validity' }}:</strong> {{ getSelectedPlanDetails().validity }}</p>
                            <p><strong>{{ _t('price') || 'Price' }}:</strong> {{ formatPrice(getSelectedPlanDetails().price, getSelectedPlanDetails().currency) }}</p>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="name">
                                {{ _t('your_name') || 'Your Name' }}
                            </Label>
                            <Input
                                id="name"
                                v-model="customerName"
                                type="text"
                                :placeholder="_t('name_placeholder') || 'Enter your full name'"
                                :class="{ 'border-red-500': errors.name }"
                            />
                            <p v-if="errors.name" class="text-sm text-red-500">{{ errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email">
                                {{ _t('email_address') || 'Email Address' }}
                            </Label>
                            <Input
                                id="email"
                                v-model="customerEmail"
                                type="email"
                                :placeholder="_t('email_placeholder') || 'Enter your email for eSIM delivery'"
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
                        {{ isProcessingOrder ? (_t('processing') || 'Processing...') : (_t('get_esim') || 'Get eSIM Now') }}
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