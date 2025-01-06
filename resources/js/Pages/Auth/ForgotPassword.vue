<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import GuestHeader from "@/Layouts/GuestHeader.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

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
    <GuestHeader />
    <Head title="Forgot Password" />

    <div class="container h-[89vh] flex items-center justify-center">
        <div class="mb-4 text-sm text-gray-600 h-full flex flex-col justify-center gap-[1rem] w-[50%]">
            <ApplicationLogo/>
            <p>Forgot your password? No problem. Just let us know your email
            address and we will email you a password reset link that will allow
            you to choose a new one.</p>
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <form @submit.prevent="submit" class="">
                <div>
                    <InputLabel for="email" value="Email" />

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
</template>
