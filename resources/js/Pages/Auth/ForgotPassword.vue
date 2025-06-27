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
    <Head>
        <meta name="robots" content="index, follow">
        <title inertia>{{ _t('forgetpassword', 'forgot_password_title') }}</title>
        <meta name="description" :content="seoDescription" />
            <meta name="keywords" :content="seoKeywords" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:title" :content="seoTitle" />
            <meta property="og:description" :content="seoDescription" />
            <meta property="og:image" :content="seoImageUrl" />
            <meta property="og:url" :content="currentUrl" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="seoTitle" />
            <meta name="twitter:description" :content="seoDescription" />
            <meta name="twitter:image" :content="seoImageUrl" />
    </Head>
    <AuthenticatedHeaderLayout />
    <!-- <Head :title="_t('forgetpassword', 'forgot_password_title')" /> -->

    <div class="w-[60rem] mx-auto h-[89vh] flex items-center justify-center max-[768px]:h-auto max-[768px]:py-[2rem] relative
    max-[768px]:flex-col max-[768px]:w-full max-[768px]:px-[1.5rem] max-[768px]:gap-10">
        <img :src=passwordillustrateIcon alt="" class="w-[50%] animate-float">
        <div
            class="mb-4 text-sm text-gray-600 h-full flex flex-col justify-center gap-[1rem] w-full max-[768px]:w-full">
            <Link>
            <ApplicationLogo />
            </Link>
            <p>{{ _t('forgetpassword', 'forgot_password_description') }}</p>
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <form @submit.prevent="submit" class="">
                <div>
                    <InputLabel for="email" :value="_t('forgetpassword', 'email')" />

                    <Input id="email" type="email" class="mt-1 block w-full p-5" v-model="form.email" required autofocus
                        autocomplete="username" />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ _t('forgetpassword', 'email_password_reset_link') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
@keyframes float {

    0%,
    100% {
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
