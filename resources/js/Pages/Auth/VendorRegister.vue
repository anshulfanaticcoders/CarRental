<script setup>
import { ref, defineProps, onMounted } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
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
    passport_photo: null,
    company_name: '',
    company_phone_number: '',
    company_email: '',
    company_address: '',
    company_gst_number: '',
});

const currentStep = ref(0);
const fullName = `${props.user.first_name} ${props.user.last_name}`;

// Add refs to store file names for display
const fileNames = ref({
    driving_license: '',
    passport: '',
    passport_photo: ''
});

// Load saved file data on component mount
onMounted(() => {
    const savedFileData = localStorage.getItem('vendorFileData');
    if (savedFileData) {
        const parsedData = JSON.parse(savedFileData);
        fileNames.value = parsedData;
    }
});

const nextStep = () => {
    if (currentStep.value === 1) {
        // Save file names to localStorage when moving from step 2
        localStorage.setItem('vendorFileData', JSON.stringify(fileNames.value));
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
        
        // Store the updated file names in localStorage
        localStorage.setItem('vendorFileData', JSON.stringify(fileNames.value));
    }
};

const submit = () => {
    form.post(route("vendor.store"), {
        onFinish: () => {
            // Clear localStorage after successful submission
            localStorage.removeItem('vendorFileData');
            form.reset();
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
};
</script>

<template>
    <Head title="Vendor Register" />
    <AuthenticatedHeaderLayout />
    <div class="">
        <div class="container flex justify-between min-h-[88vh]">
            <div class="column flex items-center w-[40%]">
                <form @submit.prevent="submit" class="w-full">
                    <div v-if="currentStep === 0">
                        <div class="flex flex-col gap-5">
                            <span class="text-[3rem] font-medium"
                                >Create Vendor</span
                            >
                            <p class="text-customLightGrayColor text-[1.15rem]">
                                Create your listing in a few minutes to receive
                                rental requests! All you need is a photo, a
                                rate, and an address and our team will contact
                                you and offer you a personalized appointment.
                                Also, make sure you have the vehicle's
                                registration certificate nearby.
                            </p>
                            <PrimaryButton
                                class="w-[30%]"
                                type="button"
                                @click="nextStep"
                                >Create a vendor</PrimaryButton
                            >
                        </div>
                    </div>
                    <div v-if="currentStep === 1">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="column w-full">
                                <InputLabel for="full_name"
                                    >Full Name</InputLabel
                                >
                                <TextInput
                                    type="text"
                                    v-model="fullName"
                                    readonly
                                    class="w-full"
                                />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="phone"
                                    >Phone Number</InputLabel
                                >
                                <TextInput
                                    type="tel"
                                    v-model="form.phone"
                                    class="w-full"
                                    readonly
                                />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="email">Email</InputLabel>
                                <TextInput
                                    type="email"
                                    v-model="form.email"
                                    readonly
                                    class="w-full"
                                />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="address">Address</InputLabel>
                                <textarea
                                    v-model="form.address"
                                    class="w-full"
                                    readonly
                                ></textarea>
                            </div>

                            <div class="flex justify-between">
                                <button
                                    class="button-secondary w-[30%]"
                                    type="button"
                                    @click="prevStep"
                                    :disabled="currentStep === 0"
                                >
                                    Back
                                </button>
                                <PrimaryButton
                                    class="w-[30%]"
                                    type="button"
                                    @click="nextStep"
                                    >Next</PrimaryButton
                                >
                            </div>
                        </div>
                    </div>
                    <div v-else-if="currentStep === 2">
        <div class="grid grid-cols-1 gap-5">
            <div class="column w-full">
                <InputLabel for="driving_license">Driving License</InputLabel>
                <div class="flex flex-col gap-2">
                    <TextInput
                        type="file"
                        @change="handleFileChange('driving_license', $event)"
                        class="w-full"
                    />
                    <span v-if="fileNames.driving_license" class="text-sm text-gray-600">
                        Selected file: {{ fileNames.driving_license }}
                    </span>
                </div>
            </div>

            <div class="column w-full">
                <InputLabel for="passport">Passport</InputLabel>
                <div class="flex flex-col gap-2">
                    <TextInput
                        type="file"
                        @change="handleFileChange('passport', $event)"
                        class="w-full"
                    />
                    <span v-if="fileNames.passport" class="text-sm text-gray-600">
                        Selected file: {{ fileNames.passport }}
                    </span>
                </div>
            </div>

            <div class="column w-full">
                <InputLabel for="passport_photo">Passport Photo</InputLabel>
                <div class="flex flex-col gap-2">
                    <TextInput
                        type="file"
                        @change="handleFileChange('passport_photo', $event)"
                        class="w-full"
                    />
                    <span v-if="fileNames.passport_photo" class="text-sm text-gray-600">
                        Selected file: {{ fileNames.passport_photo }}
                    </span>
                </div>
            </div>

            <div class="flex justify-between">
                <button
                    class="button-secondary w-[30%]"
                    type="button"
                    @click="prevStep"
                >
                    Back
                </button>
                <PrimaryButton
                    class="w-[30%]"
                    type="button"
                    @click="nextStep"
                >Next</PrimaryButton>
            </div>
        </div>
    </div>

                    <div v-else-if="currentStep === 3">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="column w-full">
                                <InputLabel for="company_name">Company Name</InputLabel>
                                <TextInput type="text" v-model="form.company_name" class="w-full" required/>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_phone_number">Company Phone Number</InputLabel>
                                <TextInput type="tel" v-model="form.company_phone_number" class="w-full" required/>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_email">Company Email</InputLabel>
                                <TextInput type="email" v-model="form.company_email" class="w-full" required/>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_address">Company Address</InputLabel>
                                <textarea v-model="form.company_address" class="w-full" required></textarea>
                            </div>
                            <div class="column w-full">
                                <InputLabel for="company_gst_number">Company GST Number</InputLabel>
                                <TextInput type="text" v-model="form.company_gst_number" class="w-full" required/>
                            </div>
                            <div class="flex justify-between">
                                <button class="button-secondary w-[30%]" type="button" @click="prevStep">Back</button>
                                <PrimaryButton class="w-[30%]" type="submit"
                                    >Submit</PrimaryButton
                                >
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div
                class="column bg-customPrimaryColor w-[50%] min-h-[80vh] relative"
            >
                <div
                    class="flex flex-col gap-10 items-center justify-center h-full"
                >
                    <div class="col text-customPrimaryColor-foreground w-[70%]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Temporary documents
                        </h4>
                        <p>
                            You can submit your ad with temporary documents
                            (order form, temporary registration certificate,
                            crossed out vehicle registration document and
                            transfer certificate) while waiting to receive your
                            final vehicle registration document.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img
                    :src="vendorBgimage"
                    alt=""
                    class="absolute bottom-0 left-[-4rem]"
                />
            </div>
        </div>
    </div>
</template>

<style>
label {
    margin-bottom: 0.5rem;
}
</style>
