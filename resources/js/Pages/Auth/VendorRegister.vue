<script setup>
import { ref, defineProps, onMounted, computed, watch, getCurrentInstance } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import { useToast } from 'vue-toastification';
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import vendorBgimage from "../../../assets/vendorRegisterbgImage.png";
import warningSign from "../../../assets/WhiteWarningCircle.svg";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

const props = defineProps({
    user: Object,
    userProfile: Object,
    flash: Object,
    errors: Object,
});


const isLoading = ref(false);
const toast = useToast();

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const form = useForm({
    phone: props.user.phone,
    email: props.user.email,
    address: props.userProfile.address_line1,
    driving_license_front: null,
    driving_license_back: null,
    passport_front: null,
    passport_back: null,
    company_name: "",
    company_phone_number: "",
    company_email: "",
    company_address: "",
    company_gst_number: "",
});

const currentStep = ref(0);
const fullName = `${props.user.first_name} ${props.user.last_name}`;

// Add refs to store file names for display
const fileNames = ref({
    driving_license_front: "",
    driving_license_back: "",
    passport_front: "",
    passport_back: "",
});

const filePreviews = ref({
    driving_license_front: null,
    driving_license_back: null,
    passport_front: null,
    passport_back: null,
});

const removeFile = (type) => {
    fileNames.value[type] = "";
    filePreviews.value[type] = null;
    form[type] = null; // Clear the file from the form as well

    // Update local storage to reflect the changes
    localStorage.setItem(
        "vendorFileData",
        JSON.stringify({
            fileNames: fileNames.value,
            filePreviews: filePreviews.value,
        })
    );
};



// Load saved file data on component mount
onMounted(() => {
    const savedFileData = localStorage.getItem("vendorFileData");
    if (savedFileData) {
        const parsedData = JSON.parse(savedFileData);
        fileNames.value = parsedData;
    }
});

const requiredFiles = ["passport_front", "passport_back",];

const errors = ref({});

const nextStep = () => {
    errors.value = {}; // Clear previous errors

    if (currentStep.value === 2) {
        const allFilesSelected = requiredFiles.every(
            (field) => form[field] !== null
        );

        if (!allFilesSelected) {
            errors.value.files = _t('authpages', 'error_all_documents_required');
            return;
        }

        localStorage.setItem("vendorFileData", JSON.stringify(fileNames.value));
    }

    if (currentStep.value === 3) {
        if (!selectedPhoneCode.value) {
            errors.value.company_phone_code = _t('authpages', 'error_company_phone_code_required');
            return;
        }
        if (!phoneNumber.value || phoneNumber.value.length < 7) {
            errors.value.company_phone_number = _t('authpages', 'error_company_phone_number_invalid');
            return;
        }
    }

    currentStep.value++;
};
const prevStep = () => {
    currentStep.value--;
};

const handleFileChange = (field, event) => {
    const file = event.target.files[0];
    const toast = useToast();

    if (file) {
        // Check file extension
        const validExtensions = ['jpg', 'jpeg', 'png'];
        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (!validExtensions.includes(fileExtension)) {
            toast.error(_t('authpages', 'toast_invalid_file_format'), {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            // Reset the input to clear the invalid file
            event.target.value = '';
            return;
        }

        form[field] = file;
        fileNames.value[field] = file.name;

        // Create image preview
        const reader = new FileReader();
        reader.onload = () => {
            filePreviews.value[field] = reader.result;

            // Save to localStorage (file names & previews)
            localStorage.setItem(
                'vendorFileData',
                JSON.stringify({
                    fileNames: fileNames.value,
                    filePreviews: filePreviews.value,
                })
            );
        };
        reader.readAsDataURL(file);
    }
};


const submit = () => {
    isLoading.value = true;
    form.post(route("vendor.store"), {
        onSuccess: () => {
            // const errorMessage = props.flash?.error || props.errors?.error || _t('authpages', 'toast_generic_error'); // Not used directly
            toast.success(_t('authpages', 'toast_vendor_registration_success'), {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            localStorage.removeItem("vendorFileData");
            form.reset();

            isLoading.value = false;
        },
        onError: (errors) => {
            // const errorMessage = props.flash?.error || props.errors?.error || _t('authpages', 'toast_generic_error'); // Not used directly
            toast.error(_t('authpages', 'toast_generic_error'), {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            console.error(errors);
            isLoading.value = false;
        },
    });
};

// Country code and phone number handling
const countries = ref([]);
const selectedPhoneCode = ref("");
const phoneNumber = ref("");
const fullPhone = computed({
    get: () => `${selectedPhoneCode.value}${phoneNumber.value}`,
    set: (value) => { },
});
// Restrict phone number input to digits only
const restrictToNumbers = (event) => {
  const value = event.target.value;
  // Remove non-numeric characters
  phoneNumber.value = value.replace(/[^0-9]/g, "");
};


// Update form.company_phone_number when phone code or number changes
watch([selectedPhoneCode, phoneNumber], ([newCode, newNumber]) => {
    form.company_phone_number = newCode && newNumber ? `${newCode}${newNumber}` : "";
});

const fetchCountries = async () => {
    try {
        const response = await fetch("/countries.json");
        countries.value = await response.json();
        // Set default phone code (optional, e.g., first country)
        if (countries.value.length > 0) {
            selectedPhoneCode.value = countries.value[0].phone_code;
        }
    } catch (error) {
        console.error("Error loading countries:", error);
    }
};

const getFlagUrl = (countryCode) => {
    return `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`;
};

const selectedCountryCode = computed(() => {
    if (!selectedPhoneCode.value || !countries.value || countries.value.length === 0) return null;
    const country = countries.value.find((c) => c.phone_code === selectedPhoneCode.value);
    return country ? country.code : null;
});

// Call fetchCountries on mount
onMounted(() => {
    // ... existing onMounted code ...
    fetchCountries();
});
</script>

<template>

    <Head>
        <meta name="robots" content="noindex, nofollow">
        <title>{{ _t('authpages', 'vendor_register_title') }}</title>
    </Head>
    <AuthenticatedHeaderLayout />
    <div class="max-[768px]:mt-8">
        <div
            class="ml-[5%] flex justify-between min-h-[88vh] max-[768px]:ml-0 max-[768px]:min-h-auto max-[768px]:flex-col max-[768px]:gap-10">
            <div class="column flex items-center w-[40%] max-[768px]:w-full">
                <form @submit.prevent="submit" class="w-full max-[768px]:px-[1.5rem]">
                    <div v-if="currentStep === 0">
                        <div class="flex flex-col gap-5">
                            <span class="text-[3rem] font-medium max-[768px]:text-[1.2rem]">{{ _t('authpages', 'create_vendor_header') }}</span>
                            <p class="text-customLightGrayColor text-[1.15rem] max-[768px]:text-[0.875rem]">
                                {{ _t('authpages', 'create_vendor_intro_text') }}
                            </p>
                            <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem] max-[768px]:text-[0.65rem]"
                                type="button" @click="nextStep">{{ _t('authpages', 'create_a_vendor_button') }}
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-if="currentStep === 1">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="column w-full">
                                <InputLabel for="full_name">{{ _t('authpages', 'full_name_label') }}</InputLabel>
                                <TextInput type="text" v-model="fullName" readonly class="w-full" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="phone">{{ _t('authpages', 'phone_number_label') }}</InputLabel>
                                <TextInput type="tel" v-model="form.phone" class="w-full" readonly />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="email">{{ _t('authpages', 'email_label') }}</InputLabel>
                                <TextInput type="email" v-model="form.email" readonly class="w-full" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="address">{{ _t('authpages', 'address_label') }}</InputLabel>
                                <textarea v-model="form.address" class="w-full" readonly></textarea>
                            </div>

                            <div class="flex justify-between gap-[1.5rem]">
                                <button
                                    class="button-secondary w-[15rem] max-[768px]:w-[10rem] max-[768px]:text-[0.65rem]"
                                    type="button" @click="prevStep" :disabled="currentStep === 0">
                                    {{ _t('authpages', 'back_button') }}
                                </button>
                                <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem] max-[768px]:text-[0.65rem]"
                                    type="button" @click="nextStep">{{ _t('authpages', 'next_button') }}</PrimaryButton>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="currentStep === 2">
                        <div class="grid grid-cols-1 gap-5 document_section">
                           
                            <div class="grid grid-cols-2 gap-5">

                                <!-- Passport Front -->
                                <div class="column w-full">
                                    <InputLabel for="passport_front">{{ _t('authpages', 'passport_front_label') }}</InputLabel>
                                    <div class="flex flex-col gap-2">
                                        <!-- Clickable Upload Area -->
                                        <div @click="$refs.passportFrontInput.click()"
                                            class="document-div cursor-pointer border-[2px] border-customPrimaryColor p-4 rounded-lg text-center border-dotted">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="#153b4f"
                                                class="w-10 h-10 mx-auto text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">{{ _t('authpages', 'click_to_select_image') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ _t('authpages', 'image_format_hint') }}</p>
                                        </div>

                                        <!-- Hidden File Input -->
                                        <input type="file" ref="passportFrontInput" class="hidden"
                                            @change="handleFileChange('passport_front', $event)" />

                                        <!-- Show Image Preview with Remove Button -->
                                        <div v-if="filePreviews.passport_front" class="relative w-[150px]">
                                            <img :src="filePreviews.passport_front" alt="Preview"
                                                class="w-full h-[100px] object-cover rounded-md border shadow-md" />
                                            <button @click.stop="removeFile('passport_front')"
                                                class="absolute top-1 right-1 bg-red-500 text-white w-[20px] h-[20px] rounded-full text-[0.65rem]">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Passport Back -->
                                <div class="column w-full">
                                    <InputLabel for="passport_back">{{ _t('authpages', 'passport_back_label') }}</InputLabel>
                                    <div class="flex flex-col gap-2">
                                        <!-- Clickable Upload Area -->
                                        <div @click="$refs.passportBackInput.click()"
                                            class="document-div cursor-pointer border-[2px] border-customPrimaryColor p-4 rounded-lg text-center border-dotted">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="#153b4f"
                                                class="w-10 h-10 mx-auto text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">{{ _t('authpages', 'click_to_select_image') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ _t('authpages', 'image_format_hint') }}</p>
                                        </div>

                                        <!-- Hidden File Input -->
                                        <input type="file" ref="passportBackInput" class="hidden"
                                            @change="handleFileChange('passport_back', $event)" />

                                        <!-- Show Image Preview with Remove Button -->
                                        <div v-if="filePreviews.passport_back" class="relative w-[150px]">
                                            <img :src="filePreviews.passport_back" alt="Preview"
                                                class="w-full h-[100px] object-cover rounded-md border shadow-md" />
                                            <button @click.stop="removeFile('passport_back')"
                                                class="absolute top-1 right-1 bg-red-500 text-white w-[20px] h-[20px] rounded-full text-[0.65rem]">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="errors.files" class="text-red-500 text-sm text-center">{{ errors.files }}</div>
                        </div>
                        <!-- Buttons -->
                        <div class="flex justify-between mt-[2rem] gap-[1.5rem]">
                            <button class="button-secondary w-[15rem] max-[768px]:w-[10rem] max-[768px]:text-[0.65rem]"
                                type="button" @click="prevStep">{{ _t('authpages', 'back_button') }}</button>
                            <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem] max-[768px]:text-[0.65rem]"
                                type="button" @click="nextStep">{{ _t('authpages', 'next_button') }}</PrimaryButton>
                        </div>


                    </div>

                    <div v-else-if="currentStep === 3">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="column w-full">
                                <InputLabel for="company_name">{{ _t('authpages', 'company_name_label') }}</InputLabel>
                                <TextInput type="text" v-model="form.company_name" class="w-full" required />
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_phone_number">{{ _t('authpages', 'company_phone_number_label') }}</InputLabel>
                                <div class="flex items-end">
                                    <div class="w-[8rem]">
                                        <Select v-model="selectedPhoneCode">
                                            <SelectTrigger
                                                class="w-full p-[1.75rem] border-customLightGrayColor bg-customPrimaryColor text-white rounded-[12px] !rounded-r-none border-r-0">
                                                <div class="flex items-center">
                                                    <img v-if="selectedCountryCode"
                                                        :src="getFlagUrl(selectedCountryCode)" alt="Country Flag"
                                                        class="mr-2 w-6 h-4 rounded" />
                                                    <SelectValue :placeholder="_t('authpages', 'select_code_placeholder')">{{ selectedPhoneCode }}
                                                    </SelectValue>
                                                </div>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('authpages', 'phone_code_label') }}</SelectLabel>
                                                    <SelectItem v-for="country in countries" :key="country.phone_code"
                                                        :value="country.phone_code">
                                                        <div class="flex items-center w-full">
                                                            <img :src="getFlagUrl(country.code)" alt="Country Flag"
                                                                class="mr-2 w-6 h-4 rounded" />
                                                            <span>{{ country.name }} ({{ country.phone_code }})</span>
                                                        </div>
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="w-full">
                                        <TextInput id="company_phone_number" type="tel" v-model="phoneNumber"
                                            class="w-full !rounded-l-none" :placeholder="_t('authpages', 'enter_phone_number_placeholder')" required @input="restrictToNumbers"/>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-gray-500" v-if="selectedPhoneCode && phoneNumber">
                                    {{ _t('authpages', 'full_phone_number_display') }}{{ fullPhone }}
                                </div>
                                <p v-if="errors.company_phone_code" class="text-red-500 text-sm mt-1">{{
                                    errors.company_phone_code }}</p>
                                <p v-if="errors.company_phone_number" class="text-red-500 text-sm mt-1">{{
                                    errors.company_phone_number }}</p>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_email">{{ _t('authpages', 'company_email_label') }}</InputLabel>
                                <TextInput type="email" v-model="form.company_email" class="w-full" required />
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_address">{{ _t('authpages', 'company_address_label') }}</InputLabel>
                                <textarea v-model="form.company_address" class="w-full" required></textarea>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_gst_number">{{ _t('authpages', 'vat_number_label') }}</InputLabel>
                                <TextInput type="text" v-model="form.company_gst_number" class="w-full" required />
                            </div>
                            <div class="flex justify-between max-[768px]:mt-4">
                                <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" type="button"
                                    @click="prevStep">
                                    {{ _t('authpages', 'back_button') }}
                                </button>
                                <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" type="submit">
                                    <!-- Show loader or text based on isLoading -->
                                    <span v-if="isLoading" class="flex items-center justify-center">
                                        <svg class="animate-spin h-5 w-5 mr-2 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ _t('authpages', 'loading_button_text') }}
                                    </span>
                                    <span v-else>{{ _t('authpages', 'submit_button') }}</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="column bg-customPrimaryColor w-[50%] min-h-[80vh] relative 
            max-[768px]:min-h-full max-[768px]:w-full max-[768px]:py-5 max-[768px]:mt-20">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:px-[1.5rem]">
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[40px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('authpages', 'temporary_documents_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('authpages', 'temporary_documents_text') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[40px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('authpages', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('authpages', 'need_help_contact_text') }}</p>
                    </div>
                </div>
                <img :src="vendorBgimage" alt="" class="absolute bottom-0 left-[-4rem] max-[768px]:w-[222px]
                max-[768px]:top-[-5.5rem]" />
            </div>
        </div>
    </div>
</template>

<style scoped>
label {
    margin-bottom: 0.5rem;
}

input,
textarea,
select {
    border-radius: 0.75rem;
    border: 1px solid rgba(43, 43, 43, 0.50) !important;
    padding: 1rem;
}

.document-div svg {
    transition: transform 0.3s ease-in-out;
}

.document-div:hover svg {
    transform: scale(1.1);
}

.animate-spin {
    animation: spin 1s linear infinite;
}

:deep(.border-customLightGrayColor svg) {
    display: none !important;
}


@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

@media screen and (max-width:768px) {

    input,
    textarea {
        font-size: 14px;
    }

    .document_section svg {
        width: 28px;
    }
}
</style>
