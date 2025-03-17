<script setup>
import { ref, defineProps, onMounted } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import { useToast } from 'vue-toastification'; // Add this import
const toast = useToast(); // Initialize toast
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import vendorBgimage from "../../../assets/vendorRegisterbgImage.png";
import warningSign from "../../../assets/WhiteWarningCircle.svg";

const props = defineProps(["user", "userProfile"]);

const form = useForm({
    phone: props.user.phone,
    email: props.user.email,
    address: props.userProfile.address_line1,
    driving_license: null,
    passport: null,
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
    driving_license: "",
    passport: "",
});

const filePreviews = ref({
    driving_license: null,
    passport: null,
});

const removeFile = (type) => {
    fileNames.value[type] = "";
    filePreviews.value[type] = null;
};


// Load saved file data on component mount
onMounted(() => {
    const savedFileData = localStorage.getItem("vendorFileData");
    if (savedFileData) {
        const parsedData = JSON.parse(savedFileData);
        fileNames.value = parsedData;
    }
});

const requiredFiles = ["driving_license", "passport", "passport_photo"]; // Define required file fields

const errors = ref({});

const nextStep = () => {
    errors.value = {}; // Clear previous errors

    if (currentStep.value === 2) {
        const allFilesSelected = requiredFiles.every(
            (field) => form[field] !== null
        );

        if (!allFilesSelected) {
            errors.value.files = "Please upload the document to proceed.";
            return;
        }

        localStorage.setItem("vendorFileData", JSON.stringify(fileNames.value));
    }

    currentStep.value++;
};
const prevStep = () => {
    currentStep.value--;
};

const handleFileChange = (field, event) => {
    const file = event.target.files[0];

    if (file) {
        form[field] = file;
        fileNames.value[field] = file.name;

        // Create image preview
        const reader = new FileReader();
        reader.onload = () => {
            filePreviews.value[field] = reader.result;

            // Save to localStorage (file names & previews)
            localStorage.setItem(
                "vendorFileData",
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
    form.post(route("vendor.store"), {
        onSuccess: () => {
            toast.success('Vendor registration completed successfully! Wait for confimation', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            localStorage.removeItem("vendorFileData");
            form.reset();
        },
        onError: (errors) => {
            toast.error('Something went wrong. Please check your inputs.', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            console.error(errors);
        },
    });
};
</script>

<template>

    <Head title="Vendor Register" />
    <AuthenticatedHeaderLayout />
    <div class="max-[480px]:mt-8">
        <div
            class="ml-[5%] flex justify-between min-h-[88vh] max-[480px]:ml-0 max-[480px]:min-h-auto max-[480px]:flex-col max-[480px]:gap-10">
            <div class="column flex items-center w-[40%] max-[480px]:w-full">
                <form @submit.prevent="submit" class="w-full max-[480px]:px-[1.5rem]">
                    <div v-if="currentStep === 0">
                        <div class="flex flex-col gap-5">
                            <span class="text-[3rem] font-medium max-[480px]:text-[1.2rem]">Create Vendor</span>
                            <p class="text-customLightGrayColor text-[1.15rem] max-[480px]:text-[0.875rem]">
                                Create your listing in a few minutes to receive
                                rental requests! All you need is a photo, a
                                rate, and an address and our team will contact
                                you and offer you a personalized appointment.
                                Also, make sure you have the vehicle's
                                registration certificate nearby.
                            </p>
                            <PrimaryButton class="w-[15rem] max-[480px]:w-[10rem] max-[480px]:text-[0.65rem]"
                                type="button" @click="nextStep">Create a vendor
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-if="currentStep === 1">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="column w-full">
                                <InputLabel for="full_name">Full Name</InputLabel>
                                <TextInput type="text" v-model="fullName" readonly class="w-full" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="phone">Phone Number</InputLabel>
                                <TextInput type="tel" v-model="form.phone" class="w-full" readonly />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="email">Email</InputLabel>
                                <TextInput type="email" v-model="form.email" readonly class="w-full" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="address">Address</InputLabel>
                                <textarea v-model="form.address" class="w-full" readonly></textarea>
                            </div>

                            <div class="flex justify-between">
                                <button class="button-secondary w-[15rem] max-[480px]:w-[10rem]" type="button" @click="prevStep"
                                    :disabled="currentStep === 0">
                                    Back
                                </button>
                                <PrimaryButton class="w-[15rem] max-[480px]:w-[10rem]" type="button" @click="nextStep">Next</PrimaryButton>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="currentStep === 2">
                        <div class="grid grid-cols-1 gap-5">
                            <!-- Driving License -->
                            <div class="column w-full">
                                <InputLabel for="driving_license">Driving License</InputLabel>
                                <div class="flex flex-col gap-2">

                                    <!-- Clickable Upload Area -->
                                    <div @click="$refs.drivingLicenseInput.click()"
                                        class="document-div cursor-pointer border-[2px] border-customPrimaryColor p-4 rounded-lg text-center border-dotted">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="#153b4f" class="w-10 h-10 mx-auto text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">Click to select an image</p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG and JPEG up to 2MB</p>
                                    </div>

                                    <!-- Hidden File Input -->
                                    <input type="file" ref="drivingLicenseInput" class="hidden"
                                        @change="handleFileChange('driving_license', $event)" />


                                    <!-- Show Image Preview with Remove Button -->
                                    <div v-if="filePreviews.driving_license" class="relative w-[150px]">
                                        <img :src="filePreviews.driving_license" alt="Preview"
                                            class="w-full h-[100px] object-cover rounded-md border shadow-md" />

                                        <!-- Remove File Button -->
                                        <button @click.stop="removeFile('driving_license')"
                                            class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full text-xs">
                                            ✕
                                        </button>
                                    </div>

                                    <!-- Show Selected File Name -->
                                    <!-- <span v-if="fileNames.driving_license" class="text-sm text-gray-600">
                                        Selected file: {{ fileNames.driving_license }}
                                    </span> -->
                                    <div v-if="errors.files" class="text-red-500 text-sm">{{ errors.files }}</div> <!-- Error Message -->
                                </div>
                            </div>

                            <!-- Passport/ID -->
                            <div class="column w-full">
                                <InputLabel for="passport">Passport/ID</InputLabel>
                                <div class="flex flex-col gap-2">

                                    <!-- Clickable Upload Area -->
                                    <div @click="$refs.passportInput.click()"
                                        class="document-div cursor-pointer border-[2px] border-customPrimaryColor p-4 rounded-lg text-center border-dotted">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="#153b4f" class="w-10 h-10 mx-auto text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">Click to select an image</p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG and JPEG up to 2MB</p>
                                    </div>

                                    <!-- Hidden File Input -->
                                    <input type="file" ref="passportInput" class="hidden"
                                        @change="handleFileChange('passport', $event)" />

                                    <!-- Show Image Preview with Remove Button -->
                                    <div v-if="filePreviews.passport" class="relative w-[150px]">
                                        <img :src="filePreviews.passport" alt="Preview"
                                            class="w-full h-[100px] object-cover rounded-md border shadow-md" />

                                        <!-- Remove File Button -->
                                        <button @click.stop="removeFile('passport')"
                                            class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full text-xs">
                                            ✕
                                        </button>
                                    </div>

                                    <!-- Show Selected File Name -->
                                    <!-- <span v-if="fileNames.passport" class="text-sm text-gray-600">
                                        Selected file: {{ fileNames.passport }}
                                    </span> -->
                                    <div v-if="errors.files" class="text-red-500 text-sm">{{ errors.files }}</div> <!-- Error Message -->
                                </div>
                            </div>

                        </div>
                        <!-- Buttons -->
                        <div class="flex justify-between mt-[2rem]">
                            <button class="button-secondary w-[15rem] max-[480px]:w-[10rem]" type="button" @click="prevStep">Back</button>
                            <PrimaryButton class="w-[15rem] max-[480px]:w-[10rem]" type="button" @click="nextStep">Next</PrimaryButton>
                        </div>


                    </div>

                    <div v-else-if="currentStep === 3">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="column w-full">
                                <InputLabel for="company_name">Company Name</InputLabel>
                                <TextInput type="text" v-model="form.company_name" class="w-full" required />
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_phone_number">Company Phone Number</InputLabel>
                                <TextInput type="tel" v-model="form.company_phone_number" class="w-full" required />
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_email">Company Email</InputLabel>
                                <TextInput type="email" v-model="form.company_email" class="w-full" required />
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_address">Company Address</InputLabel>
                                <textarea v-model="form.company_address" class="w-full" required></textarea>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_gst_number">VAT Number</InputLabel>
                                <TextInput type="text" v-model="form.company_gst_number" class="w-full" required />
                            </div>
                            <div class="flex justify-between max-[480px]:mt-4">
                                <button class="button-secondary w-[15rem] max-[480px]:w-[10rem]" type="button" @click="prevStep">
                                    Back
                                </button>
                                <PrimaryButton class="w-[15rem] max-[480px]:w-[10rem]" type="submit">Submit</PrimaryButton>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="column bg-customPrimaryColor w-[50%] min-h-[80vh] relative 
            max-[480px]:min-h-full max-[480px]:w-full max-[480px]:py-5 max-[480px]:mt-20">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[480px]:px-[1.5rem]">
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[480px]:w-full">
                        <img :src="warningSign" alt="" class="max-[480px]:w-[40px]"/>
                        <h4 class="text-[1.5rem] font-medium max-[480px]:text-[1.2rem] max-[480px]:py-2">
                            Temporary documents
                        </h4>
                        <p class="max-[480px]:text-[0.875rem]">
                            You can submit your ad with temporary documents
                            (order form, temporary registration certificate,
                            crossed out vehicle registration document and
                            transfer certificate) while waiting to receive your
                            final vehicle registration document.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[480px]:w-full">
                        <img :src="warningSign" alt="" class="max-[480px]:w-[40px]"/>
                        <h4 class="text-[1.5rem] font-medium max-[480px]:text-[1.2rem] max-[480px]:py-2">
                            Need some help?
                        </h4>
                        <p class="max-[480px]:text-[0.875rem]">Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img :src="vendorBgimage" alt="" class="absolute bottom-0 left-[-4rem] max-[480px]:w-[222px]
                max-[480px]:top-[-5.5rem]" />
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

@media screen and (max-width:480px) {
    input,textarea{
        font-size: 14px;
    }
}
</style>
