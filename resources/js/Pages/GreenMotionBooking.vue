<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useToast } from 'vue-toastification';

const props = defineProps({
    vehicle: Object,
    location: Object,
    optionalExtras: Array,
    filters: Object,
    locale: String,
    error: String,
});

const page = usePage();
const toast = useToast();

const currencySymbols = ref({});

onMounted(async () => {
    try {
        const response = await fetch('/currency.json');
        const data = await response.json();
        currencySymbols.value = data.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
    } catch (error) {
        console.error("Error loading currency symbols:", error);
    }
});

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$';
};

const selectedPackage = ref(null);
const selectedOptionalExtras = ref([]);

const form = ref({
    location_id: props.filters.location_id || props.location?.id || 61627,
    start_date: props.filters.start_date || '2032-01-06',
    start_time: props.filters.start_time || '09:00',
    end_date: props.filters.end_date || '2032-01-08',
    end_time: props.filters.end_time || '09:00',
    age: props.filters.age || 35,
    rentalCode: null, // This will be set by selectedPackage
    customer: {
        firstname: '',
        surname: '',
        email: '',
        phone: '',
        address1: '',
        address2: '',
        address3: '',
        town: '',
        postcode: '',
        country: '',
        driver_licence_number: '',
        flight_number: '',
        comments: '',
        title: '',
        bplace: '',
        bdate: '',
        idno: '',
        idplace: '',
        idissue: '',
        idexp: '',
        licissue: '',
        licplace: '',
        licexp: '',
        idurl: '',
        id_rear_url: '',
        licurl: '',
        lic_rear_url: '',
        verification_response: '',
        custimage: '',
        dvlacheckcode: '',
    },
    extras: [], // Selected optional extras for API
    vehicle_id: props.vehicle?.id,
    vehicle_total: 0, // Calculated based on selected package
    currency: props.vehicle?.products?.[0]?.currency || '$',
    grand_total: 0, // Calculated total
    paymentHandlerRef: null,
    quoteid: props.vehicle?.products?.[0]?.quoteid || 'dummy_quote_id', // Assuming quoteid is available or a dummy
    payment_type: 'POA', // Default to Pay on Arrival
    remarks: null,
});

const rentalDuration = computed(() => {
    if (!form.value.start_date || !form.value.end_date) return 0;
    const startDate = new Date(form.value.start_date);
    const endDate = new Date(form.value.end_date);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const availablePackages = computed(() => {
    return props.vehicle?.products || [];
});

const calculateTotals = () => {
    let packagePrice = 0;
    if (selectedPackage.value) {
        packagePrice = parseFloat(selectedPackage.value.total);
        form.value.rentalCode = selectedPackage.value.type;
    }

    let extrasTotal = 0;
    selectedOptionalExtras.value.forEach(extra => {
        // Assuming extra.Total_for_this_booking is the total for the booking duration
        extrasTotal += parseFloat(extra.Total_for_this_booking || 0);
    });

    form.value.vehicle_total = packagePrice;
    form.value.grand_total = packagePrice + extrasTotal;

    // Populate form.extras for API submission
    form.value.extras = selectedOptionalExtras.value.map(extra => ({
        id: extra.optionID,
        option_qty: 1, // Assuming quantity is 1 for now, adjust if UI allows multiple
        option_total: parseFloat(extra.Total_for_this_booking || extra.Daily_rate * rentalDuration.value || 0),
        pre_pay: extra.Prepay_available === 'yes' ? 'Yes' : 'No',
    }));
};

watch(selectedPackage, calculateTotals, { deep: true });
watch(selectedOptionalExtras, calculateTotals, { deep: true });

onMounted(() => {
    // Set initial selected package if available
    if (availablePackages.value.length > 0) {
        selectedPackage.value = availablePackages.value[0]; // Default to first package
    }
    calculateTotals();
});

const submitBooking = async () => {
    if (!selectedPackage.value) {
        toast.error("Please select a rental package.");
        return;
    }

    // Basic validation for customer info
    if (!form.value.customer.firstname || !form.value.customer.surname || !form.value.customer.email || !form.value.customer.phone) {
        toast.error("Please fill in all required customer details.");
        return;
    }

    try {
        const response = await router.post(route('green-motion-booking', { locale: props.locale }), form.value, {
            onSuccess: (page) => {
                toast.success("Booking successful! Reference: " + page.props.booking_reference);
                // Redirect to a success page or show confirmation
                router.visit(route('booking-success.details', { locale: props.locale, booking_reference: page.props.booking_reference }));
            },
            onError: (errors) => {
                console.error("Booking submission errors:", errors);
                toast.error("Booking failed. Please check your details and try again.");
                // Display specific errors to the user
                Object.values(errors).forEach(error => toast.error(error));
            }
        });
    } catch (e) {
        console.error("Network or unexpected error during booking:", e);
        toast.error("An unexpected error occurred. Please try again.");
    }
};

</script>

<template>
    <Head>
        <title>GreenMotion Booking</title>
    </Head>
    <AuthenticatedHeaderLayout />

    <section class="bg-customPrimaryColor py-customVerticalSpacing">
        <div class="full-w-container flex flex-col items-center justify-center py-8">
            <h1 class="text-white text-3xl font-bold mb-4">Book Your GreenMotion Vehicle</h1>
            <p class="text-white text-lg">Complete your reservation for {{ vehicle?.name }}</p>
        </div>
    </section>

    <div class="full-w-container mx-auto mt-8 mb-[4rem]">
        <div v-if="error" class="text-center text-red-500 text-xl py-8">
            {{ error }}
            <Link :href="route('green-motion-cars', { locale: locale, ...filters })" class="text-blue-500 hover:underline block mt-4">
                Back to Search Results
            </Link>
        </div>
        <div v-else-if="vehicle">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Booking Details for {{ vehicle?.name }}</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Vehicle, Packages, Extras -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Vehicle Summary -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold mb-4">Your Selected Vehicle</h3>
                        <div class="flex items-center gap-4">
                            <img :src="vehicle.image || '/default-car-image.jpg'" alt="Vehicle Image" class="w-32 h-24 object-cover rounded-md" />
                            <div>
                                <p class="font-bold text-lg">{{ vehicle.name }}</p>
                                <p class="text-gray-600">{{ vehicle.groupName }}</p>
                                <p class="text-gray-500 text-sm">{{ rentalDuration }} days rental</p>
                            </div>
                        </div>
                    </div>

                    <!-- Package Selection -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold mb-4">Choose Your Rental Package</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="pkg in availablePackages" :key="pkg.type"
                                class="border rounded-lg p-4 cursor-pointer"
                                :class="{ 'border-customPrimaryColor ring-2 ring-customPrimaryColor': selectedPackage?.type === pkg.type }"
                                @click="selectedPackage = pkg">
                                <h4 class="font-bold text-lg mb-2">{{ pkg.type }} Package</h4>
                                <p class="text-gray-700 text-sm">Total: {{ getCurrencySymbol(pkg.currency) }}{{ pkg.total }}</p>
                                <p class="text-gray-700 text-sm">Deposit: {{ getCurrencySymbol(pkg.currency) }}{{ pkg.deposit }}</p>
                                <p class="text-gray-700 text-sm">Excess: {{ getCurrencySymbol(pkg.currency) }}{{ pkg.excess }}</p>
                                <p class="text-gray-700 text-sm">Fuel Policy: {{ pkg.fuelpolicy }}</p>
                                <!-- Add more package details as needed -->
                            </div>
                        </div>
                    </div>

                    <!-- Optional Extras Selection -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold mb-4">Optional Extras</h3>
                        <div v-if="optionalExtras.length > 0" class="space-y-3">
                            <div v-for="extra in optionalExtras" :key="extra.optionID || extra.Name"
                                class="flex items-center justify-between border p-3 rounded-md">
                                <div>
                                    <p class="font-medium">{{ extra.Name }}</p>
                                    <p class="text-gray-600 text-sm">{{ extra.Description }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">{{ getCurrencySymbol(extra.Daily_rate_currency) }}{{ extra.Daily_rate }}/day</span>
                                    <input type="checkbox" :value="extra" v-model="selectedOptionalExtras" class="form-checkbox h-5 w-5 text-customPrimaryColor" />
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500">No additional optional extras available.</p>
                    </div>

                    <!-- Customer Information Form -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold mb-4">Your Details</h3>
                        <form @submit.prevent="submitBooking" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="firstname" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" id="firstname" v-model="form.customer.firstname" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="surname" class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" id="surname" v-model="form.customer.surname" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" v-model="form.customer.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="tel" id="phone" v-model="form.customer.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="address1" class="block text-sm font-medium text-gray-700">Address Line 1</label>
                                <input type="text" id="address1" v-model="form.customer.address1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="town" class="block text-sm font-medium text-gray-700">Town/City</label>
                                <input type="text" id="town" v-model="form.customer.town" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="postcode" class="block text-sm font-medium text-gray-700">Postcode</label>
                                <input type="text" id="postcode" v-model="form.customer.postcode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                                <input type="text" id="country" v-model="form.customer.country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="driver_licence_number" class="block text-sm font-medium text-gray-700">Driver's Licence Number</label>
                                <input type="text" id="driver_licence_number" v-model="form.customer.driver_licence_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <div>
                                <label for="age" class="block text-sm font-medium text-gray-700">Age</label>
                                <input type="number" id="age" v-model="form.age" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                            <!-- Add more fields as necessary based on GreenMotion API requirements -->
                        </form>
                    </div>
                </div>

                <!-- Right Column: Booking Summary & Total -->
                <div class="lg:col-span-1">
                    <div class="sticky top-4 bg-white rounded-lg shadow p-6 space-y-4">
                        <h3 class="text-xl font-semibold mb-4">Booking Summary</h3>
                        <div>
                            <p class="text-gray-700">Vehicle: <span class="font-medium">{{ vehicle?.name }}</span></p>
                            <p class="text-gray-700">Pickup: <span class="font-medium">{{ location?.name }} on {{ form.start_date }} at {{ form.start_time }}</span></p>
                            <p class="text-gray-700">Return: <span class="font-medium">{{ location?.name }} on {{ form.end_date }} at {{ form.end_time }}</span></p>
                            <p class="text-gray-700">Duration: <span class="font-medium">{{ rentalDuration }} days</span></p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-lg font-semibold">Selected Package:</p>
                            <p v-if="selectedPackage" class="text-gray-700">{{ selectedPackage.type }} - {{ getCurrencySymbol(selectedPackage.currency) }}{{ selectedPackage.total }}</p>
                            <p v-else class="text-gray-500">No package selected</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-lg font-semibold">Selected Extras:</p>
                            <ul v-if="selectedOptionalExtras.length > 0" class="list-disc list-inside text-gray-700">
                                <li v-for="extra in selectedOptionalExtras" :key="extra.optionID">{{ extra.Name }} - {{ getCurrencySymbol(extra.Daily_rate_currency) }}{{ extra.Daily_rate }}/day</li>
                            </ul>
                            <p v-else class="text-gray-500">No optional extras selected.</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-2xl font-bold text-customPrimaryColor">Grand Total: {{ getCurrencySymbol(form.currency) }}{{ form.grand_total.toFixed(2) }}</p>
                        </div>

                        <button @click="submitBooking" class="w-full bg-customPrimaryColor text-white py-3 rounded-md hover:bg-customPrimaryColor-dark transition-colors">
                            Confirm Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
/* Add any specific styles for GreenMotionBooking.vue here */
.bg-customPrimaryColor {
    background-color: #153b4f;
}

.text-customPrimaryColor {
    color: #153b4f;
}
</style>
