<template>
    <DialogContent class="max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle>Create New User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <Input v-model="form.first_name" required />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <Input v-model="form.last_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email *" />
                <Input v-model="form.email" type="email" required />
            </div>
            <div class="relative">
                <InputLabel for="country" value="Country" class="mb-1" />
                <Select v-model="form.country">
                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                        <SelectValue placeholder="Select Country" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Country</SelectLabel>
                            <SelectItem v-for="country in countries" :key="country.code" :value="country.code">
                                {{ country.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <!-- Dynamic Flag -->
                <img v-if="form.country" :src="getFlagUrl(form.country)" alt="Country Flag"
                    class="absolute right-3 top-1/2 transform translate-x-[0%] translate-y-[0%] w-[2.1rem] h-[1.5rem] rounded" />
            </div>
            <div>
                <InputLabel for="phone" value="Phone *" />
                <div class="flex">
                    <!-- Phone Code Selection -->
                    <div class="w-[8rem]">
                        <Select v-model="form.phone_code">
                            <SelectTrigger class="w-full h-[2.5rem] border-customLightGrayColor bg-customPrimaryColor text-white rounded-[8px] rounded-r-none border-r-0 flex items-center px-3">
                                <div class="flex items-center">
                                    <img v-if="selectedCountryCode" :src="getFlagUrl(selectedCountryCode)"
                                         alt="Country Flag" class="mr-2 w-5 h-3 rounded" />
                                    <span class="text-sm">{{ form.phone_code || '+1' }}</span>
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Phone Code</SelectLabel>
                                    <SelectItem v-for="country in countries" :key="country.phone_code"
                                               :value="country.phone_code" class="flex items-center">
                                        <div class="flex items-center w-full">
                                            <img :src="getFlagUrl(country.code)" alt="Country Flag"
                                                 class="mr-2 w-5 h-3 rounded" />
                                            <span>{{ country.name }} ({{ country.phone_code }})</span>
                                        </div>
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Phone Number Input -->
                    <div class="flex-1">
                        <Input v-model="form.phone" required class="w-full rounded-l-none h-[2.5rem]" placeholder="Enter phone number" />
                    </div>
                </div>
            </div>
            <div>
                <InputLabel for="date_of_birth" value="Date of Birth *" />
                <VueDatePicker
                    v-model="dateOfBirth"
                    :enable-time-picker="false"
                    uid="date-of-birth"
                    auto-apply
                    placeholder="Select date of birth"
                    class="w-full"
                    :max-date="minimumDateOfBirth"
                    :start-date="minimumDateOfBirth"
                />
                <small class="text-gray-500 mt-1 block">User must be 18 years or older</small>
            </div>
            <div>
                <InputLabel for="password" value="Password *" />
                <Input v-model="form.password" type="password" required :class="{
                    'border-red-500': form.password.length > 0 && form.password.length < 8,
                    'border-green-500': form.password.length >= 8
                }" />
                <p v-if="form.password.length > 0 && form.password.length < 8" class="text-red-500 text-sm mt-1">
                    Password must be at least 8 characters long
                </p>
                <p v-else-if="form.password.length >= 8" class="text-green-500 text-sm mt-1">
                    Password length is valid
                </p>
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Confirm Password *" />
                <Input v-model="form.password_confirmation" type="password" required :class="{
                    'border-red-500': form.password_confirmation.length > 0 &&
                        form.password !== form.password_confirmation,
                    'border-green-500': form.password_confirmation.length > 0 &&
                        form.password === form.password_confirmation
                }" />
                <p v-if="form.password_confirmation.length > 0 &&
                    form.password !== form.password_confirmation" class="text-red-500 text-sm mt-1">
                    Passwords do not match
                </p>
                <p v-else-if="form.password_confirmation.length > 0 &&
                    form.password === form.password_confirmation" class="text-green-500 text-sm mt-1">
                    Passwords match
                </p>
            </div>
            <!-- Address Fields Section -->
            <div class="space-y-3">
                <div>
                    <InputLabel for="address" value="Street Address *" />
                    <Input v-model="form.address" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="city" value="City *" />
                        <Input v-model="form.city" required />
                    </div>
                    <div>
                        <InputLabel for="postal_code" value="Postal Code *" />
                        <Input v-model="form.postal_code" required />
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="role" value="Role *" />
                    <Select v-model="form.role" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="customer">Customer</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <InputLabel for="status" value="Status *" />
                    <Select v-model="form.status" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <!-- Error message for form submission attempt -->
            <div v-if="errorMessage" class="p-2 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ errorMessage }}
            </div>
            <DialogFooter>
                <Button
                    type="submit"
                    :disabled="form.password.length < 8 ||
                        form.password !== form.password_confirmation || isSubmitting"
                    class="relative"
                >
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Creating...
                    </span>
                    <span v-else>Create User</span>
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { onMounted, ref, computed, watch } from "vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { router } from "@inertiajs/vue3";
import { DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectContent from "@/Components/ui/select/SelectContent.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
import Select from "@/Components/ui/select/Select.vue";
import SelectValue from "@/Components/ui/select/SelectValue.vue";
import Button from "@/Components/ui/button/Button.vue";

const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    country: '',
    phone: '',
    phone_code: '',
    password: '',
    password_confirmation: '',
    role: 'customer',
    status: 'active',
    // New fields
    date_of_birth: null,
    address: '',
    city: '',
    postal_code: ''
});

const countries = ref([]);
const errorMessage = ref('');
const isSubmitting = ref(false);
const emit = defineEmits(['close']);

// New state variables
const dateOfBirth = ref(null);
const selectedPhoneCode = ref('');

const fetchCountries = async () => {
    try {
        const response = await fetch('/countries.json'); // Ensure it's in /public
        countries.value = await response.json();
        selectedPhoneCode.value = countries.value[0]?.phone_code || '';
    } catch (error) {
        console.error("Error loading countries:", error);
    }
};

onMounted(fetchCountries);

// Get flag URL
const getFlagUrl = (countryCode) => {
    return `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`;
};

// Date of birth computed properties
const minimumDateOfBirth = computed(() => {
    const today = new Date();
    const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    return eighteenYearsAgo.toISOString().split('T')[0];
});

// Phone code computed properties
const selectedCountryCode = computed(() => {
    if (!form.value.phone_code || !countries.value?.length) return null;
    const country = countries.value.find(c => c.phone_code === form.value.phone_code);
    return country ? country.code : null;
});

// Watch dateOfBirth changes
watch(dateOfBirth, (newValue) => {
    if (newValue) {
        form.value.date_of_birth = newValue.toISOString().split('T')[0];
    } else {
        form.value.date_of_birth = '';
    }
});

// Watch phone code changes
watch(selectedPhoneCode, (newCode) => {
    form.value.phone_code = newCode;
});

const submitForm = () => {
    // Reset error message
    errorMessage.value = '';

    // Check password length
    if (form.value.password.length < 8) {
        errorMessage.value = 'Password must be at least 8 characters long';
        return;
    }

    // Check password match
    if (form.value.password !== form.value.password_confirmation) {
        errorMessage.value = 'Passwords do not match';
        return;
    }

    if (!form.value.country) {
        errorMessage.value = 'Please select a country';
        return;
    }

    // New validations
    if (!form.value.phone_code) {
        errorMessage.value = 'Please select a phone code';
        return;
    }

    if (!form.value.date_of_birth) {
        errorMessage.value = 'Please select date of birth';
        return;
    }

    if (!form.value.address || !form.value.city || !form.value.postal_code) {
        errorMessage.value = 'Please complete all address fields';
        return;
    }

    // Format full phone number for backend
    const formData = {
        ...form.value,
        phone: `${form.value.phone_code} ${form.value.phone}`.trim()
    };

    // Set loading state
    isSubmitting.value = true;

    router.post("/users", formData, {
        onSuccess: () => {
            form.value = {
                first_name: '',
                last_name: '',
                email: '',
                country: '',
                phone: '',
                phone_code: '',
                password: '',
                password_confirmation: '',
                role: 'customer',
                status: 'active',
                date_of_birth: null,
                address: '',
                city: '',
                postal_code: ''
            };
            dateOfBirth.value = null;
            emit('close');
        },
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] || 'An error occurred';
        },
        onFinish: () => {
            // Reset loading state regardless of success or error
            isSubmitting.value = false;
        },
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<style scoped>
/* Modal optimizations */
:deep([role="dialog"]) {
    max-height: 90vh;
}

:deep(.dialog-content) {
    max-height: 90vh;
    overflow-y: auto;
}

/* Date picker optimizations */
:deep(.dp__input) {
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border-radius: 8px;
    border: 1px solid #2b2b2b99;
    width: 100%;
    font-size: 0.875rem;
}

:deep(.dp__menu) {
    border-radius: 0.5rem;
    padding: 0.5rem;
}

/* Phone input optimizations */
.phone-input-group {
    display: flex;
    align-items: stretch;
}

.phone-input-group .select-trigger {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}

.phone-input-group .input {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}

/* Hide dropdown arrow for phone code select */
.phone-code-select .select-trigger::after {
    display: none !important;
}

/* Ensure consistent height for phone inputs */
.flex .select-trigger {
    height: 2.5rem !important;
    min-height: 2.5rem !important;
    max-height: 2.5rem !important;
}

.flex input {
    height: 2.5rem !important;
}

/* Form field spacing optimizations */
:deep(.space-y-3 > :not([hidden]) ~ :not([hidden])) {
    margin-top: 0.75rem;
}

:deep(.space-y-2 > :not([hidden]) ~ :not([hidden])) {
    margin-top: 0.5rem;
}

/* Input size optimizations */
:deep(input) {
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
}

:deep(.select-trigger) {
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    height: auto;
    min-height: 2.5rem;
}

/* Small screen optimizations */
@media (max-height: 800px) {
    :deep(.dialog-content) {
        max-height: 85vh;
    }

    :deep(.space-y-3 > :not([hidden]) ~ :not([hidden])) {
        margin-top: 0.5rem;
    }
}

@media (max-width: 768px) {
    :deep(.dialog-content) {
        max-width: 95vw;
        margin: 1rem;
    }

    .grid {
        grid-template-columns: 1fr !important;
    }
}
</style>