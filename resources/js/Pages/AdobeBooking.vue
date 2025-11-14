<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useToast } from 'vue-toastification';
import { useCurrency } from '@/composables/useCurrency';
import { Vue3Lottie } from 'vue3-lottie';
import universalLoader from '../../../public/animations/universal-loader.json';

const props = defineProps({
    vehicle: Object,
    searchParams: Object,
    locale: String,
});

const page = usePage();
const toast = useToast();

// UI State
const currentStep = ref(1);
const isFormValid = ref(false);
const showMobileBookingSummary = ref(false);
const isSubmitting = ref(false);
const selectedProtections = ref({});
const selectedExtras = ref({});

const { selectedCurrency, supportedCurrencies, changeCurrency } = useCurrency();

// Form data
const formData = ref({
    // Customer Information
    customer: {
        name: '',
        last_name: '',
        email: '',
        phone: '',
        country: '',
    },
    // Additional Information
    customer_comment: '',
    reference: '',
    flight_number: '',
    language: 'en',
    // Adobe-specific pricing
    tdr_total: props.vehicle.tdr || 0,
    pli_total: props.vehicle.pli || 0,
    ldw_total: 0,
    spp_total: 0,
    dro_total: 0,
    base_rate: props.vehicle.base_rate || props.vehicle.tdr || 0,
    vehicle_total: props.vehicle.price_per_day || 0,
    grand_total: 0,
});

// Computed pricing
const pricingBreakdown = computed(() => {
    const items = [
        {
            label: 'Time & Distance Rate',
            amount: formData.value.tdr_total,
            description: 'Base rental charge',
            required: true
        },
        {
            label: 'Liability Protection (PLI)',
            amount: formData.value.pli_total,
            description: 'Third-party liability insurance',
            required: true
        }
    ];

    if (formData.value.ldw_total > 0) {
        items.push({
            label: 'Loss Damage Waiver (LDW)',
            amount: formData.value.ldw_total,
            description: 'Vehicle damage protection',
            required: false
        });
    }

    if (formData.value.spp_total > 0) {
        items.push({
            label: 'Super Protection (SPP)',
            amount: formData.value.spp_total,
            description: 'Comprehensive coverage',
            required: false
        });
    }

    if (formData.value.dro_total > 0) {
        items.push({
            label: 'Drop-off Fee (DRO)',
            amount: formData.value.dro_total,
            description: 'Different location drop-off',
            required: false
        });
    }

    // Add selected protections
    Object.values(selectedProtections.value).forEach(protection => {
        if (protection.selected) {
            items.push({
                label: protection.name || protection.code,
                amount: protection.total || 0,
                description: protection.description || '',
                required: protection.required || false
            });
        }
    });

    // Add selected extras
    Object.values(selectedExtras.value).forEach(extra => {
        if (extra.selected && extra.quantity > 0) {
            items.push({
                label: extra.name || extra.code,
                amount: (extra.total || 0) * extra.quantity,
                description: extra.description || '',
                required: false
            });
        }
    });

    return items;
});

const calculateTotal = computed(() => {
    return pricingBreakdown.value.reduce((total, item) => total + item.amount, 0);
});

// Watch for protection/extras changes
watch([selectedProtections, selectedExtras], () => {
    formData.value.grand_total = calculateTotal.value;
    formData.value.vehicle_total = calculateTotal.value;
}, { deep: true });

// Initialize protections and extras
onMounted(() => {
    // Initialize protections
    if (props.vehicle.protections) {
        props.vehicle.protections.forEach(protection => {
            if (protection.type === 'Proteccion') {
                selectedProtections.value[protection.code] = {
                    ...protection,
                    selected: protection.required || false,
                    quantity: 1
                };

                // Auto-select required protections
                if (protection.required) {
                    formData.value[`${protection.code.toLowerCase()}_total`] = protection.total || 0;
                }
            }
        });
    }

    // Initialize extras
    if (props.vehicle.extras) {
        props.vehicle.extras.forEach(extra => {
            if (extra.type !== 'Proteccion') {
                selectedExtras.value[extra.code] = {
                    ...extra,
                    selected: false,
                    quantity: 0
                };
            }
        });
    }

    // Calculate initial total
    formData.value.grand_total = calculateTotal.value;
    formData.value.vehicle_total = calculateTotal.value;
});

// Methods
const formatCurrency = (amount) => {
    const formattedAmount = selectedCurrency.value ?
        convertCurrency(amount, 'USD', selectedCurrency.value) : amount;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: selectedCurrency.value || 'USD'
    }).format(formattedAmount);
};

const convertCurrency = (amount, fromCurrency, toCurrency) => {
    // This would integrate with your currency conversion service
    // For now, return the original amount
    return amount;
};

const validateStep = (step) => {
    switch (step) {
        case 1: // Dates and basic info
            return true; // Already validated from search params

        case 2: // Customer information
            return formData.value.customer.name.trim() &&
                   formData.value.customer.last_name.trim() &&
                   formData.value.customer.email.trim() &&
                   formData.value.customer.phone.trim() &&
                   formData.value.customer.country.trim();

        case 3: // Protections and extras
            return true; // Protections are handled by logic

        default:
            return false;
    }
};

const nextStep = () => {
    if (validateStep(currentStep.value)) {
        currentStep.value++;
    } else {
        toast.error('Please complete all required fields');
    }
};

const prevStep = () => {
    currentStep.value--;
};

const toggleProtection = (protectionCode) => {
    const protection = selectedProtections.value[protectionCode];
    if (protection) {
        protection.selected = !protection.selected;

        // Update pricing
        if (protection.required) {
            // Required protections are always included
            formData.value[`${protectionCode.toLowerCase()}_total`] = protection.total || 0;
        } else {
            formData.value[`${protectionCode.toLowerCase()}_total`] = protection.selected ? (protection.total || 0) : 0;
        }
    }
};

const updateExtraQuantity = (extraCode, quantity) => {
    const extra = selectedExtras.value[extraCode];
    if (extra) {
        extra.quantity = Math.max(0, parseInt(quantity) || 0);
        extra.selected = extra.quantity > 0;
    }
};

const submitBooking = async () => {
    if (!validateStep(currentStep.value)) {
        toast.error('Please complete all required fields');
        return;
    }

    isSubmitting.value = true;

    try {
        // Prepare booking data
        const bookingData = {
            ...props.searchParams,
            ...formData.value,
            vehicle_id: props.vehicle.id,
            vehicle_category: props.vehicle.category,
            vehicle_model: props.vehicle.model,
            pickup_location_id: props.searchParams.pickup_location_id,
            dropoff_location_id: props.searchParams.dropoff_location_id,
            selected_protections: Object.values(selectedProtections.value).filter(p => p.selected),
            selected_extras: Object.values(selectedExtras.value).filter(e => e.selected && e.quantity > 0),
        };

        // Create booking and get Stripe checkout
        const response = await fetch(route('adobe.booking.charge'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify(bookingData),
        });

        const result = await response.json();

        if (result.success) {
            // Redirect to Stripe checkout
            window.location.href = result.checkout_url;
        } else {
            toast.error(result.error || 'Failed to create booking');
        }
    } catch (error) {
        console.error('Booking error:', error);
        toast.error('An error occurred while processing your booking');
    } finally {
        isSubmitting.value = false;
    }
};

// Step components
const getStepTitle = (step) => {
    const titles = {
        1: 'Rental Details',
        2: 'Customer Information',
        3: 'Protection & Extras',
        4: 'Payment',
    };
    return titles[step] || '';
};

const isStepComplete = (step) => {
    return validateStep(step);
};
</script>

<template>
    <Head>
        <title>Complete Your Adobe Car Rental Booking</title>
        <meta name="description" content="Complete your Adobe car rental booking. Select protections, extras, and proceed to secure payment." />
    </Head>

    <AuthenticatedHeaderLayout />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="container mx-auto px-4 max-w-6xl">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-4">
                    <div v-for="step in 4" :key="step" class="flex items-center">
                        <div
                            :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-colors',
                                step <= currentStep ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600',
                                isStepComplete(step) && step < currentStep ? 'bg-green-600' : ''
                            ]"
                        >
                            <span v-if="isStepComplete(step) && step < currentStep">✓</span>
                            <span v-else>{{ step }}</span>
                        </div>
                        <div
                            v-if="step < 4"
                            :class="[
                                'w-16 h-1 mx-2',
                                step < currentStep ? 'bg-blue-600' : 'bg-gray-300'
                            ]"
                        />
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-sm text-gray-600">
                    <div v-for="step in 4" :key="step" class="flex-1 text-center">
                        {{ getStepTitle(step) }}
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <!-- Step 1: Rental Details (Read-only) -->
                        <div v-if="currentStep === 1" class="space-y-6">
                            <h2 class="text-2xl font-bold mb-4">Rental Details</h2>

                            <!-- Vehicle Summary -->
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center space-x-4">
                                    <img
                                        :src="vehicle.image || '/images/adobe-placeholder.jpg'"
                                        :alt="vehicle.model"
                                        class="w-20 h-20 object-cover rounded"
                                        @error="$event.target.src = '/images/adobe-placeholder.jpg'"
                                    />
                                    <div>
                                        <h3 class="font-semibold">{{ vehicle.model }}</h3>
                                        <p class="text-gray-600">Category: {{ vehicle.category?.toUpperCase() }}</p>
                                        <p class="text-blue-600 font-semibold">{{ formatCurrency(vehicle.price_per_day) }}/day</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Rental Dates -->
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="border rounded-lg p-4">
                                    <h4 class="font-semibold mb-2">Pickup</h4>
                                    <p class="text-gray-600">{{ searchParams.date_from }} at {{ searchParams.time_from }}</p>
                                    <p class="text-sm">Location: {{ searchParams.pickup_location_id }}</p>
                                </div>
                                <div class="border rounded-lg p-4">
                                    <h4 class="font-semibold mb-2">Drop-off</h4>
                                    <p class="text-gray-600">{{ searchParams.date_to }} at {{ searchParams.time_to }}</p>
                                    <p class="text-sm">Location: {{ searchParams.dropoff_location_id }}</p>
                                </div>
                            </div>

                            <button
                                @click="nextStep"
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                            >
                                Continue to Customer Information
                            </button>
                        </div>

                        <!-- Step 2: Customer Information -->
                        <div v-if="currentStep === 2" class="space-y-6">
                            <h2 class="text-2xl font-bold mb-4">Customer Information</h2>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input
                                        v-model="formData.customer.name"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="John"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input
                                        v-model="formData.customer.last_name"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Doe"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input
                                    v-model="formData.customer.email"
                                    type="email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="john.doe@example.com"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                <input
                                    v-model="formData.customer.phone"
                                    type="tel"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="+1 (555) 123-4567"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                <input
                                    v-model="formData.customer.country"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="United States"
                                />
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Flight Number</label>
                                    <input
                                        v-model="formData.flight_number"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="AA123"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                                    <input
                                        v-model="formData.reference"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Business trip"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Comments</label>
                                <textarea
                                    v-model="formData.customer_comment"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Any special requests or comments..."
                                ></textarea>
                            </div>

                            <div class="flex space-x-4">
                                <button
                                    @click="prevStep"
                                    class="flex-1 bg-gray-200 text-gray-800 py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                                >
                                    Back
                                </button>
                                <button
                                    @click="nextStep"
                                    class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                                >
                                    Continue to Protection & Extras
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Protections and Extras -->
                        <div v-if="currentStep === 3" class="space-y-6">
                            <h2 class="text-2xl font-bold mb-4">Protection & Extras</h2>

                            <!-- Protections -->
                            <div v-if="Object.keys(selectedProtections).length > 0">
                                <h3 class="text-lg font-semibold mb-3">Protection Options</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="protection in Object.values(selectedProtections)"
                                        :key="protection.code"
                                        class="border rounded-lg p-4"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3">
                                                    <input
                                                        :id="`protection-${protection.code}`"
                                                        type="checkbox"
                                                        :checked="protection.selected"
                                                        :disabled="protection.required"
                                                        @change="toggleProtection(protection.code)"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    />
                                                    <label :for="`protection-${protection.code}`" class="font-medium">
                                                        {{ protection.name || protection.code }}
                                                        <span v-if="protection.required" class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded">
                                                            Required
                                                        </span>
                                                    </label>
                                                </div>
                                                <p class="text-sm text-gray-600 mt-1">{{ protection.description }}</p>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-semibold">{{ formatCurrency(protection.total || 0) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Extras -->
                            <div v-if="Object.keys(selectedExtras).length > 0">
                                <h3 class="text-lg font-semibold mb-3">Additional Extras</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="extra in Object.values(selectedExtras)"
                                        :key="extra.code"
                                        class="border rounded-lg p-4"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3">
                                                    <input
                                                        :id="`extra-${extra.code}`"
                                                        type="checkbox"
                                                        :checked="extra.selected"
                                                        @change="extra.selected = $event.target.checked; updateExtraQuantity(extra.code, extra.selected ? 1 : 0)"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    />
                                                    <label :for="`extra-${extra.code}`" class="font-medium">
                                                        {{ extra.name || extra.code }}
                                                    </label>
                                                </div>
                                                <p class="text-sm text-gray-600 mt-1">{{ extra.description }}</p>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-semibold">{{ formatCurrency(extra.total || 0) }} each</div>
                                                <input
                                                    v-if="extra.selected"
                                                    type="number"
                                                    :value="extra.quantity"
                                                    @input="updateExtraQuantity(extra.code, $event.target.value)"
                                                    min="0"
                                                    max="10"
                                                    class="w-16 px-2 py-1 border border-gray-300 rounded text-sm"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-4">
                                <button
                                    @click="prevStep"
                                    class="flex-1 bg-gray-200 text-gray-800 py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                                >
                                    Back
                                </button>
                                <button
                                    @click="nextStep"
                                    class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                                >
                                    Continue to Payment
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Payment -->
                        <div v-if="currentStep === 4" class="space-y-6">
                            <h2 class="text-2xl font-bold mb-4">Review & Payment</h2>

                            <!-- Order Summary -->
                            <div class="border rounded-lg p-4">
                                <h3 class="font-semibold mb-3">Order Summary</h3>
                                <div class="space-y-2">
                                    <div v-for="item in pricingBreakdown" :key="item.label" class="flex justify-between">
                                        <div>
                                            <div class="font-medium">{{ item.label }}</div>
                                            <div class="text-sm text-gray-600">{{ item.description }}</div>
                                        </div>
                                        <div class="font-semibold">{{ formatCurrency(item.amount) }}</div>
                                    </div>
                                    <div class="border-t pt-2 mt-2">
                                        <div class="flex justify-between text-lg font-bold">
                                            <span>Total</span>
                                            <span>{{ formatCurrency(calculateTotal) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Booking Terms -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-800 mb-2">Booking Terms</h4>
                                        <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• Free cancellation up to 24 hours before pickup</li>
                                        <li>• Driver must be at least 21 years old with valid license</li>
                                        <li>• Credit card required for security deposit</li>
                                        <li>• Full-to-full fuel policy</li>
                                    </ul>
                            </div>

                            <div class="flex space-x-4">
                                <button
                                    @click="prevStep"
                                    class="flex-1 bg-gray-200 text-gray-800 py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                                >
                                    Back
                                </button>
                                <button
                                    @click="submitBooking"
                                    :disabled="isSubmitting"
                                    class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                        <Vue3Lottie :animationData="universalLoader" :height="20" :width="20" />
                                        Processing...
                                    </span>
                                    <span v-else>Complete Booking</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
                        <h3 class="font-semibold mb-4">Booking Summary</h3>

                        <!-- Vehicle Info -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="vehicle.image || '/images/adobe-placeholder.jpg'"
                                    :alt="vehicle.model"
                                    class="w-16 h-16 object-cover rounded"
                                    @error="$event.target.src = '/images/adobe-placeholder.jpg'"
                                />
                                <div>
                                    <div class="font-medium">{{ vehicle.model }}</div>
                                    <div class="text-sm text-gray-600">{{ vehicle.category?.toUpperCase() }}</div>
                                </div>
                            </div>

                            <div class="text-sm space-y-1">
                                <div><strong>Pickup:</strong> {{ searchParams.date_from }} {{ searchParams.time_from }}</div>
                                <div><strong>Drop-off:</strong> {{ searchParams.date_to }} {{ searchParams.time_to }}</div>
                                <div><strong>Location:</strong> {{ searchParams.pickup_location_id }}</div>
                            </div>
                        </div>

                        <!-- Pricing Breakdown -->
                        <div class="border-t pt-4">
                            <div class="space-y-2">
                                <div v-for="item in pricingBreakdown" :key="item.label" class="flex justify-between text-sm">
                                    <span>{{ item.label }}</span>
                                    <span>{{ formatCurrency(item.amount) }}</span>
                                </div>
                                <div class="border-t pt-2">
                                    <div class="flex justify-between font-semibold">
                                        <span>Total</span>
                                        <span>{{ formatCurrency(calculateTotal) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
.loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
</style>