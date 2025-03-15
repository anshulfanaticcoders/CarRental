<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import loginBg from '../../../assets/loginpageImage.jpg'
import GuestHeader from '@/Layouts/GuestHeader.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <main class="">
        <Head title="Log in" />
        <GuestHeader/>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <div class="ml-[10%] flex justify-between items-center gap-16 h-[100vh] sign_in
        max-[480px]:flex-col max-[480px]:ml-0 max-[480px]:px-[1.5rem] max-[480px]:justify-center
        ">
         <div class="column w-[40%] max-[480px]:w-full">
            <div class="text-center mb-[4rem] text-[#111111]">
                <h3 class="font-medium text-[3rem] max-[480px]:text-[1.5rem] max-[480px]:text-white">Sign In</h3>
                <p class='text-customLightGrayColor max-[480px]:text-white max-[480px]:text-[1rem] max-[480px]:mt-2'>Login for free to access to in any of our products</p>
            </div>
            <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email address" class="max-[480px]:!text-white"/>

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" class="max-[480px]:!text-white"/>

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex mt-4 justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-lg text-gray-600 max-[480px]:text-white max-[480px]:text-[1rem]">Remember me</span>
                </label>
                    <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline max-[480px]:text-[1rem] max-[480px]:text-white text-lg text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Forgot your password?
                </Link>

            </div>

            <div class="flex flex-col gap-4 justify-end mt-4">
                <button class="ms-4 button-primary w-full p-4 text-[1.15rem] max-[480px]:text-[1rem] max-[480px]:mt-5" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Sign in
                </button>
            </div>
        </form>
         </div>

         <div class="column overflow-hidden h-full w-[40%] max-[480px]:w-full max-[480px]:absolute max-[480px]:-z-10">
            <img :src = loginBg alt="" class="w-full h-full object-cover repeat-0 max-[480px]:brightness-50">
         </div>
        </div>

</main>
</template>


<style>
.sign_in input{
    border: 1px solid #2B2B2B80;
    border-radius: 12px;
    padding: 1rem;
}
.sign_in label{
    color:#2B2B2BBF;
}
    
</style>
