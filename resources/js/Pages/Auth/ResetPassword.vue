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

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />
    <AuthenticatedHeaderLayout />

    <div class=" min-h-[90vh] flex items-center justify-center px-4 py-10">
        <div class="flex flex-col md:flex-row items-center bg-white rounded-xl shadow-lg overflow-hidden max-w-5xl w-full">
            
            <!-- Left Image -->
            <div class="hidden md:block md:w-1/2 p-6 bg-blue-100">
                <img :src="ResetPassword" alt="Reset Password" class="w-full h-auto object-contain">
            </div>

            <!-- Right Form -->
            <div class="w-full md:w-1/2 p-8">
                <Link href="/" class="block mb-6 text-center md:text-left">
                    <ApplicationLogo class="mx-auto md:mx-0 w-32" />
                </Link>

                <h2 class="text-2xl font-semibold text-gray-700 mb-6 text-center md:text-left">Reset Your Password</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full p-3 border rounded-md"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-1 text-sm text-red-500" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <InputLabel for="password" value="New Password" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full p-3 border rounded-md"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-1 text-sm text-red-500" :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full p-3 border rounded-md"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-1 text-sm text-red-500" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <PrimaryButton
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                            class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition"
                        >
                            Reset Password
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
