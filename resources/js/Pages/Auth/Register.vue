<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import GuestHeader from '@/Layouts/GuestHeader.vue';

const steps = [
    { title: 'Personal Information', fields: ['first_name', 'last_name', 'date_of_birth'] },
    { title: 'Contact Details', fields: ['phone', 'email'] },
    { title: 'Address', fields: ['address', 'postcode', 'city', 'country'] },
    { title: 'Create Password', fields: ['password', 'password_confirmation'] }
];

const form = useForm({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    phone: '',
    email: '',
    address: '',
    postcode: '',
    city: '',
    country: '',
    password: '',
    password_confirmation: '',
});

let currentStep = ref(0);

const isStepValid = () => {
    const currentFields = steps[currentStep.value].fields;
    return currentFields.every(field => form[field] !== '');
};

const nextStep = () => {
    if (isStepValid()) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
        onError: (errors) => {
            Object.keys(errors).forEach(field => {
                form.errors[field] = errors[field];
            });
        }
    });
};
</script>

<template>
    <GuestHeader>
        <Head title="Register" />
         <div class="min-h-[80vh] flex justify-center items-center">
        <form @submit.prevent="currentStep === steps.length - 1 ? submit() : nextStep()" class="w-[40%]">

            <div v-if="currentStep === 0">
                <span class="text-[3rem] text-center block font-medium text-customMediumBlackColor">Personal Information</span>
                <p class="text-center mb-[3rem] text-customLightGrayColor font-medium">To get started, tell us about yourself.</p>
             <div class="grid grid-cols-2 gap-5">
                 <div class="column w-full">
                   <InputLabel for="first_name" value="First Name" class=""/>
                   <TextInput id="first_name" type="text" v-model="form.first_name" required autofocus autocomplete="given-name" class="w-full"/>
                   <InputError class="mt-2" :message="form.errors.first_name" />
                 </div>

                <div class="column w-full">
                   <InputLabel for="last_name" value="Last Name" />
                   <TextInput id="last_name" type="text" v-model="form.last_name" required autocomplete="family-name" class="w-full"/>
                   <InputError class="mt-2" :message="form.errors.last_name" />
                </div>

                <div class="column w-full col-span-2">
                   <InputLabel for="date_of_birth" value="Date of Birth" />
                   <TextInput id="date_of_birth" type="date" v-model="form.date_of_birth" required class="w-full"/>
                   <InputError class="mt-2" :message="form.errors.date_of_birth" />
                </div>
              </div>
            </div>

            <div v-else-if="currentStep === 1">

                <span class="text-[3rem] text-center block font-medium text-customMediumBlackColor">Contact Details</span>
                <p class="text-center mb-[3rem] text-customLightGrayColor font-medium">We'll send you relevant info about your bookings.</p>

                <div class="grid grid-cols-1 gap-5">
                <div class="column w-full">
                  <InputLabel for="phone" value="Phone Number" />
                  <TextInput id="phone" type="tel" v-model="form.phone" required class="w-full"/>
                  <InputError class="mt-2" :message="form.errors.phone" />
               </div>

               <div class="column w-full">
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" v-model="form.email" required class="w-full"/>
                <InputError class="mt-2" :message="form.errors.email" />
               </div>
            </div>
            </div>

            <div v-else-if="currentStep === 2">

                <span class="text-[3rem] text-center block font-medium text-customMediumBlackColor">Address</span>
                <p class="text-center mb-[3rem] text-customLightGrayColor font-medium">We'll use it for billing purposes</p>
                <div class="grid grid-cols-2 gap-5">
                <div class="column w-full col-span-2">
                   <InputLabel for="address" value="Address" />
                   <TextInput id="address" type="text" v-model="form.address" required class="w-full"/>
                   <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <div class="column w-full">
                   <InputLabel for="postcode" value="Postcode" />
                   <TextInput id="postcode" type="text" v-model="form.postcode" required class="w-full"/>
                   <InputError class="mt-2" :message="form.errors.postcode" />
                </div>

                <div class="column w-full">
                  <InputLabel for="city" value="City" />
                  <TextInput id="city" type="text" v-model="form.city" required class="w-full"/>
                  <InputError class="mt-2" :message="form.errors.city" />
                </div>

                <div class="column w-full col-span-2">
                   <InputLabel for="country" value="Country" />
                   <TextInput id="country" type="text" v-model="form.country" required class="w-full"/>
                   <InputError class="mt-2" :message="form.errors.country" />
                </div>
              </div>
            </div>

            <div v-else-if="currentStep === 3">

                <span class="text-[3rem] text-center block font-medium text-customMediumBlackColor">Create Password</span>
                <p class="text-center mb-[3rem] text-customLightGrayColor font-medium">We'll use it for billing purposes</p>
                <div class="grid grid-cols-1 gap-5">
                <div class="column w-full">
                  <InputLabel for="password" value="Password" />
                  <TextInput id="password" type="password" v-model="form.password" required autocomplete="new-password" class="w-full"/>
                  <InputError class="mt-2" :message="form.errors.password" />
                </div> 

                <div class="column w-full">
                  <InputLabel for="password_confirmation" value="Confirm Password" />
                  <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password" class="w-full"/>
                  <InputError class="mt-2" :message="form.errors.password_confirmation" />
               </div>
             </div>
            </div>

            <div class="flex items-center justify-between mt-[3rem]">
                <button class="button-secondary w-[30%]" type="button" @click="prevStep" v-if="currentStep > 0">Back</button>
                <PrimaryButton :disabled="!isStepValid()" class="w-[30%]">
                    {{ currentStep === steps.length - 1 ? 'Register' : 'Continue' }}
                </PrimaryButton>
            </div>
        </form>
    </div>
    </GuestHeader>
</template>

<style>
label{
    margin-bottom: 0.5rem;
}
</style>
