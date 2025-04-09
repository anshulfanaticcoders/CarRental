<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import bgImage from '../../../assets/resetpasswordbgimage.jpg'
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

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

    <div class="bg-cover min-h-[100vh] flex items-center"
        :style="{ backgroundImage: `url(${bgImage})` }">

        <div class="w-[30rem] mx-auto rounded-lg glow-animation max-[768px]:w-full max-[768px]:mx-[1rem]">

            <form @submit.prevent="submit" class="w-full p-5">
            <Link href="/" class="py-4 inline-block">
                <ApplicationLogo logoColor="#FFFFFF" />
            </Link>
            <div>
                <InputLabel for="email" value="Email" class="text-white"/>

                <TextInput id="email" type="email" class="mt-1 block w-full p-2 border" v-model="form.email" required autofocus
                    autocomplete="username" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" class="text-white"/>

                <TextInput id="password" type="password" class="mt-1 block w-full p-2 border" v-model="form.password" required
                    autocomplete="new-password" />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" class="text-white"/>

                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full p-2 border"
                    v-model="form.password_confirmation" required autocomplete="new-password" />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Reset Password
                </PrimaryButton>
            </div>
        </form>
    </div>
</div>
</template>

<style scoped>
@keyframes glow {
  0%, 100% {
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
