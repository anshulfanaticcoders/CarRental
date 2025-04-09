<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Input } from "@/Components/ui/input";
import Footer from "@/Components/Footer.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import passwordillustrateIcon from '../../../assets/forgetpassword.svg'

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"));
};
</script>

<template>
    <AuthenticatedHeaderLayout/>
    <Head title="Forgot Password" />

    <div class="w-[60rem] mx-auto h-[89vh] flex items-center justify-center max-[768px]:h-auto max-[768px]:py-[2rem] relative
    max-[768px]:flex-col max-[768px]:w-full max-[768px]:px-[1.5rem] max-[768px]:gap-10">
        <img :src=passwordillustrateIcon alt="" class="w-[50%] animate-float">
        <div class="mb-4 text-sm text-gray-600 h-full flex flex-col justify-center gap-[1rem] w-full max-[768px]:w-full">
            <Link><ApplicationLogo/></Link>
            <p>Forgot your password? No problem. Just let us know your email
            address and we will email you a password reset link that will allow
            you to choose a new one.</p>
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <form @submit.prevent="submit" class="">
                <div>
                    <InputLabel for="email" value="Email" />

                    <Input
                        id="email"
                        type="email"
                        class="mt-1 block w-full p-5"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Email Password Reset Link
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>

    <Footer/>
</template>

<style scoped>
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}
</style>

