<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ResetPassword from '../../../assets/ResetPassword.svg'
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';

import { ref, computed } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isResetting = ref(false);

const passwordsMatch = computed(() => {
    return form.password && form.password_confirmation && form.password === form.password_confirmation;
});

const validationMessage = computed(() => {
    if (!form.password_confirmation) return null;

    if (form.password !== form.password_confirmation) {
        return { text: "Passwords do not match", color: "text-red-600" };
    }
    return { text: "Passwords match", color: "text-green-600" };
});

const submit = () => {
    if (!passwordsMatch.value) return;

    isResetting.value = true;
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            isResetting.value = false;
        },
        onError: () => {
            isResetting.value = false;
        }
    });
};
</script>

<template>

    <Head title="Reset Password" />
    <AuthenticatedHeaderLayout />

    <div class=" min-h-[90vh] flex items-center justify-center px-4 py-10">
        <div
            class="flex flex-col md:flex-row items-center bg-white rounded-xl shadow-lg overflow-hidden max-w-5xl w-full">

            <!-- Left Image -->
            <div class="hidden md:block md:w-1/2 p-6 bg-blue-100">
                <img :src="ResetPassword" alt="Reset Password" class="w-full h-auto object-contain">
            </div>

            <!-- Right Form -->
            <div class="w-full md:w-1/2 p-8">
                <Link href="/" class="block mb-6 text-center md:text-left">
                    <ApplicationLogo class="mx-auto md:mx-0 w-32" />
                </Link>

                <h2 class="text-2xl font-semibold text-gray-700 mb-6 text-center md:text-left">{{ _t('resetpassword',
                    'reset_password_title') }}</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <InputLabel for="email" :value="_t('resetpassword', 'email')" />
                        <TextInput id="email" type="email" class="mt-1 block w-full p-3 border rounded-md"
                            v-model="form.email" required autofocus autocomplete="username" />
                        <InputError class="mt-1 text-sm text-red-500" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="relative">
                        <InputLabel for="password" :value="_t('resetpassword', 'new_password')" />
                        <div class="relative">
                            <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                class="mt-1 block w-full p-3 border rounded-md pr-10" v-model="form.password" required
                                autocomplete="new-password" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1 text-sm text-red-500" :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="relative">
                        <InputLabel for="password_confirmation" :value="_t('resetpassword', 'confirm_password')" />
                        <div class="relative">
                            <TextInput id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                                class="mt-1 block w-full p-3 border rounded-md pr-10"
                                v-model="form.password_confirmation" required autocomplete="new-password" />
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1 text-sm text-red-500" :message="form.errors.password_confirmation" />

                        <!-- Real-time matching validation -->
                        <div v-if="validationMessage" class="mt-2 text-sm font-medium" :class="validationMessage.color">
                            {{ validationMessage.text }}
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <PrimaryButton
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing || isResetting || !passwordsMatch }"
                            :disabled="form.processing || isResetting || !passwordsMatch"
                            class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-2">
                            <div v-if="isResetting"
                                class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin">
                            </div>
                            <span v-if="isResetting">Resetting...</span>
                            <span v-else>{{ _t('resetpassword', 'reset_password_button') }}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>


<style scoped>
@keyframes glow {

    0%,
    100% {
        box-shadow: 0 0 30px rgba(255, 255, 255, 0.15);
    }

    50% {
        box-shadow: 0 0 40px rgba(255, 255, 255, 0.3);
    }
}

.glow-animation {
    animation: glow 2s ease-in-out infinite;
}
</style>
