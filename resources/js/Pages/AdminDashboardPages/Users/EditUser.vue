<template>
    <DialogContent class="max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle>Edit User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateUser" class="space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <Input v-model="editForm.first_name" required />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <Input v-model="editForm.last_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email *" />
                <Input v-model="editForm.email" type="email" required readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div class="relative">
                <InputLabel for="country" value="Country" class="mb-1" />
                <Select v-model="editForm.country">
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
                <img v-if="editForm.country" :src="getFlagUrl(editForm.country)" alt="Country Flag"
                    class="absolute right-3 top-1/2 transform translate-x-[0%] translate-y-[0%] w-[2.1rem] h-[1.5rem] rounded" />
            </div>
            <div>
                <InputLabel for="phone" value="Phone *" />
                <div class="flex">
                    <!-- Phone Code Selection -->
                    <div class="w-[8rem]">
                        <Select v-model="editForm.phone_code">
                            <SelectTrigger class="w-full h-[2.5rem] border-customLightGrayColor bg-customPrimaryColor text-white rounded-[8px] rounded-r-none border-r-0 flex items-center px-3">
                                <div class="flex items-center">
                                    <img v-if="selectedCountryCode" :src="getFlagUrl(selectedCountryCode)"
                                         alt="Country Flag" class="mr-2 w-5 h-3 rounded" />
                                    <span class="text-sm">{{ editForm.phone_code || '+1' }}</span>
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
                        <Input v-model="editForm.phone" required class="w-full rounded-l-none h-[2.5rem]" placeholder="Enter phone number" />
                    </div>
                </div>
            </div>
            <!-- <div>
                <InputLabel for="role" value="Role *" />
                <Select v-model="editForm.role" required>
                    <SelectTrigger>
                        <SelectValue placeholder="Select Role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="customer">Customer</SelectItem>
                    </SelectContent>
                </Select>
            </div> -->
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
            <!-- Address Fields Section -->
            <div class="space-y-3">
                <div>
                    <InputLabel for="address" value="Street Address *" />
                    <Input v-model="editForm.address" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="city" value="City *" />
                        <Input v-model="editForm.city" required />
                    </div>
                    <div>
                        <InputLabel for="postal_code" value="Postal Code *" />
                        <Input v-model="editForm.postal_code" required />
                    </div>
                </div>
            </div>
            <div>
                <InputLabel for="status" value="Status *" />
                <Select v-model="editForm.status" required>
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
            <DialogFooter>
                <Button type="submit" :disabled="isSubmitting" class="relative">
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Updating...
                    </span>
                    <span v-else>Update User</span>
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectContent from "@/Components/ui/select/SelectContent.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
import Select from "@/Components/ui/select/Select.vue";
import SelectValue from "@/Components/ui/select/SelectValue.vue";
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";

const props = defineProps({
    user: Object,
});

const emit = defineEmits(['close']); // Define the 'close' event
const editForm = ref({ ...props.user });
const isSubmitting = ref(false);

// New state variables
const countries = ref([]);
const dateOfBirth = ref(null);
const selectedPhoneCode = ref('');

// Watch for changes in props.user (if the user data is updated dynamically)
watch(() => props.user, (newUser) => {
    editForm.value = { ...newUser };

    // Initialize profile fields from user.profile if available
    if (newUser.profile) {
        editForm.value.address = newUser.profile.address_line1 || '';
        editForm.value.city = newUser.profile.city || '';
        editForm.value.postal_code = newUser.profile.postal_code || '';
        editForm.value.country = newUser.profile.country || '';
        editForm.value.date_of_birth = newUser.profile.date_of_birth || '';

        // Initialize date of birth from user profile if available
        if (newUser.profile.date_of_birth) {
            dateOfBirth.value = new Date(newUser.profile.date_of_birth);
        }
    }

    // Initialize phone code if not present
    if (!editForm.value.phone_code && newUser.phone) {
        // Try to extract phone code from phone number if it exists
        const phoneParts = newUser.phone.split(' ');
        if (phoneParts.length > 1) {
            editForm.value.phone_code = phoneParts[0];
            editForm.value.phone = phoneParts.slice(1).join(' ');
        }
    }
}, { immediate: true });

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
    if (!editForm.value.phone_code || !countries.value?.length) return null;
    const country = countries.value.find(c => c.phone_code === editForm.value.phone_code);
    return country ? country.code : null;
});

// Watch dateOfBirth changes
watch(dateOfBirth, (newValue) => {
    if (newValue) {
        editForm.value.date_of_birth = newValue.toISOString().split('T')[0];
    } else {
        editForm.value.date_of_birth = '';
    }
});

// Watch phone code changes
watch(selectedPhoneCode, (newCode) => {
    editForm.value.phone_code = newCode;
});

// Fetch countries data
const fetchCountries = async () => {
    try {
        const response = await fetch('/countries.json');
        countries.value = await response.json();
        selectedPhoneCode.value = countries.value[0]?.phone_code || '';
    } catch (error) {
        console.error("Error loading countries:", error);
    }
};

onMounted(fetchCountries);

const updateUser = () => {
    // Set loading state
    isSubmitting.value = true;

    // Format full phone number for backend
    const formData = {
        ...editForm.value,
        phone: `${editForm.value.phone_code} ${editForm.value.phone}`.trim()
    };

    router.put(`/users/${editForm.value.id}`, formData, {
        onSuccess: () => {
            // Show appropriate toast based on user status
            if (editForm.value.status === 'active') {
                toast.success('User updated and activated successfully');
            } else if (editForm.value.status === 'inactive') {
                toast.warning('User updated and marked as inactive');
            } else if (editForm.value.status === 'suspended') {
                toast.error('User updated and suspended');
            } else {
                toast.success('User updated successfully');
            }
        },
        onError: (errors) => {
            // Show error notification if validation fails
            const errorMessage = Object.values(errors)[0] || 'An error occurred while updating the user';
            toast.error(errorMessage);
        },
        onFinish: () => {
            // Reset loading state regardless of success or error
            isSubmitting.value = false;
            emit('close');
        },
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