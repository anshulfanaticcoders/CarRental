<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import loginBg from '../../../assets/loginpageImage.jpg'
import GuestHeader from '@/Layouts/GuestHeader.vue';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Toaster } from "@/Components/ui/sonner";
import { watch, onMounted } from 'vue';

const page = usePage();

const _t = (group, key) => {
    return page.props.translations[group][key] || key;
};

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);
const isLoggingIn = ref(false);
const oauthError = computed(() => page.props.errors?.oauth || null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    isLoggingIn.value = true;

    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            isLoggingIn.value = false;
        },
        onError: () => {
            isLoggingIn.value = false;
        }
    });
};

const handleStatusToast = (status) => {
    if (status) {
        toast.success(status);
    }
};

onMounted(() => {
    setTimeout(() => {
        handleStatusToast(props.status);
    }, 100);
});

watch(() => props.status, (newStatus) => {
    handleStatusToast(newStatus);
});
</script>

<template>
    <main class="login-page">

        <Head>
            <meta name="robots" content="noindex, nofollow">
            <title inertia>{{ _t('login', 'log_in') }}</title>
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

        <GuestHeader />

        <Toaster position="bottom-right" :toastOptions="{
            style: { background: 'black', color: 'white', border: '1px solid #333' }
        }" />

        <div class="ml-[10%] flex justify-between items-center gap-16 h-[91vh] sign_in
        max-[768px]:flex-col max-[768px]:ml-0 max-[768px]:px-[1.5rem] max-[768px]:justify-center relative
        ">            <div class="column w-[40%] max-[768px]:w-full form-column">
                <div class="form-panel">
                <div class="text-center mb-[4rem] text-[#111111] form-header">
                    <h3 class="font-medium text-[3rem] max-[768px]:text-[1.5rem] max-[768px]:text-white form-title">{{ _t('login',
                        'sign_in') }}</h3>
                    <p
                        class='text-customLightGrayColor max-[768px]:text-white max-[768px]:text-[1rem] max-[768px]:mt-2 form-subtitle'>
                        {{ _t('login', 'login_description') }}</p>
                </div>
                <div v-if="oauthError" class="oauth-error">
                    {{ oauthError }}
                </div>
                <form @submit.prevent="submit" class="form-body">
                    <div class="form-field">
                        <InputLabel for="email" :value="_t('login', 'email_address')" class="max-[768px]:!text-white form-label" />

                        <TextInput id="email" type="email" class="mt-1 block w-full form-input" v-model="form.email" required
                            autofocus autocomplete="username" />

                        <InputError class="mt-2 input-error" :message="form.errors.email" />
                    </div>

                    <div class="mt-4 relative password-field form-field">
                        <InputLabel for="password" :value="_t('login', 'password')" class="max-[768px]:!text-white form-label" />

                        <TextInput :type="showPassword ? 'text' : 'password'" id="password"
                            class="mt-1 block w-full pr-12 form-input" v-model="form.password" required
                            autocomplete="current-password" />

                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 font-medium text-customDarkBlackColor text-sm password-toggle">
                            {{ showPassword ? _t('registerUser', 'hide_password') : _t('registerUser', 'show_password')
                            }}
                        </button>

                        <InputError class="mt-2 input-error" :message="form.errors.password" />
                    </div>


                    <div class="flex mt-4 justify-between items-center remember-section form-field">
                        <label class="flex items-center remember-group" for="remember">
                            <Checkbox name="remember" id="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-lg text-gray-600 max-[768px]:text-white max-[768px]:text-[1rem] remember-label">{{
                                _t('login', 'remember_me') }}</span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="underline max-[768px]:text-[1rem] max-[768px]:text-white text-lg text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 forgot-link">
                            {{ _t('login', 'forgot_password') }}
                        </Link>

                    </div>

                    <div class="flex flex-col gap-4 justify-end mt-4">
                        <button
                            class="button-primary w-full p-4 text-[1.15rem] max-[768px]:text-[1rem] max-[768px]:mt-5"
                            :class="{ 'opacity-25': form.processing || isLoggingIn, 'is-loading': isLoggingIn }"
                            :disabled="form.processing || isLoggingIn">
                            <span v-if="isLoggingIn" class="flex items-center justify-center gap-2">
                                <span class="button-spinner"></span>
                                Signing in...
                            </span>
                            <span v-else>{{ _t('login', 'sign_in_button') }}</span>
                        </button>
                    </div>

                    <div class="social-divider">
                        <span>{{ _t('login', 'social_divider') }}</span>
                    </div>

                    <div class="social-buttons">
                        <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'google' })"
                            class="social-button">
                            <span class="social-icon" aria-hidden="true">
                                <svg viewBox="0 0 48 48" class="social-svg">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.25 3.6l6.9-6.9C35.7 2.57 30.23 0 24 0 14.62 0 6.53 5.38 2.55 13.22l8.06 6.26C12.5 13.04 17.8 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.64-.15-3.22-.43-4.75H24v9.02h12.98c-.56 3.02-2.25 5.58-4.77 7.3l7.32 5.68c4.28-3.95 6.45-9.77 6.45-17.25z"/>
                                    <path fill="#FBBC05" d="M10.61 28.74c-.48-1.45-.76-2.99-.76-4.74 0-1.75.27-3.29.76-4.74l-8.06-6.26C.92 16.24 0 19.9 0 24c0 4.1.92 7.76 2.55 11l8.06-6.26z"/>
                                    <path fill="#34A853" d="M24 48c6.23 0 11.45-2.06 15.27-5.6l-7.32-5.68c-2.02 1.36-4.6 2.16-7.95 2.16-6.2 0-11.5-3.54-13.39-8.29l-8.06 6.26C6.53 42.62 14.62 48 24 48z"/>
                                    <path fill="none" d="M0 0h48v48H0z"/>
                                </svg>
                            </span>
                            Continue with Google
                        </a>
                        <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'facebook' })"
                            class="social-button">
                            <span class="social-icon" aria-hidden="true">
                                <svg viewBox="0 0 48 48" class="social-svg">
                                    <path fill="#1877F2" d="M48 24c0 13.26-10.74 24-24 24S0 37.26 0 24 10.74 0 24 0s24 10.74 24 24z"/>
                                    <path fill="#fff" d="M26.67 24.98h5.15l.81-5.3h-5.96v-3.44c0-1.53.75-3.02 3.17-3.02h2.45V8.7s-2.22-.38-4.35-.38c-4.44 0-7.34 2.69-7.34 7.56v3.8h-4.94v5.3h4.94V40h6.07V24.98z"/>
                                </svg>
                            </span>
                            Continue with Facebook
                        </a>
                    </div>
                </form>
                </div>
            </div>

            <div
                class="column image-column overflow-hidden h-full w-[50%] max-[768px]:w-full max-[768px]:absolute max-[768px]:top-0 max-[768px]:-z-10">
                <img :src=loginBg alt=""
                    class="w-full h-full brightness-90 object-cover repeat-0 max-[768px]:brightness-50">
            </div>
        </div>

        <!-- Loader Overlay -->
        <div v-if="isLoggingIn" class="loader-overlay">
            <div class="loader-content">
                <div class="loader-animation"></div>
                <p>Signing you in...</p>
            </div>
        </div>

    </main>

</template>


<style scoped>
.login-page {
    background: radial-gradient(circle at 15% 20%, rgba(34, 211, 238, 0.025), transparent 60%),
        radial-gradient(circle at 85% 10%, rgba(124, 179, 204, 0.05), transparent 70%),
        linear-gradient(160deg, #f9fcfe, #ffffff 70%);
}

.sign_in {
    position: relative;
}

.sign_in::before {
    content: '';
    position: absolute;
    width: 420px;
    height: 420px;
    left: -140px;
    top: 60px;
    background: radial-gradient(circle, rgba(34, 211, 238, 0.25), transparent 70%);
    filter: blur(10px);
    z-index: 0;
}

.sign_in::after {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    right: 10%;
    bottom: 40px;
    background: radial-gradient(circle, rgba(36, 95, 125, 0.2), transparent 65%);
    z-index: 0;
}

.form-panel {
    position: relative;
    z-index: 2;
    padding: 3.5rem 3rem;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid rgba(176, 212, 230, 0.6);
    box-shadow: 0 12px 32px rgba(21, 59, 79, 0.12);
    backdrop-filter: blur(12px);
    overflow: hidden;
}

.form-column {
    width: 40%;
}

.form-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(140deg, rgba(255, 255, 255, 0.5), transparent 65%);
    opacity: 0.6;
    pointer-events: none;
}

.form-header {
    margin-bottom: 3rem;
    position: relative;
    z-index: 1;
}

.form-title {
    font-family: "IBM Plex Sans", sans-serif;
    color: #0f2936;
    font-weight: 600;
}

.form-subtitle {
    color: #475569;
}

.oauth-error {
    margin-bottom: 1.5rem;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #b91c1c;
    font-weight: 600;
    text-align: center;
}

.form-label {
    color: #1c4d66;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.form-body {
    position: relative;
    z-index: 1;
}

.sign_in input.form-input {
    border: 1px solid rgba(21, 59, 79, 0.18);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    background: rgba(255, 255, 255, 0.9);
    transition: all 150ms ease;
}

.sign_in input.form-input:focus {
    outline: none;
    border-color: #06b6d4;
    box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.2);
    background: #ffffff;
}

.password-toggle {
    background: rgba(6, 182, 212, 0.08);
    border: 1px solid rgba(6, 182, 212, 0.2);
    color: #1c4d66;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35rem 0.6rem;
    border-radius: 999px;
    transition: all 150ms ease;
    top: calc(50% + 0.6rem);
}

.password-toggle:hover {
    color: #0f2936;
    background: rgba(6, 182, 212, 0.18);
}

.remember-section {
    gap: 1rem;
}

.remember-group {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    line-height: 1;
}

.remember-label {
    color: #1c4d66;
}

.forgot-link {
    color: #1c4d66 !important;
    text-decoration: none !important;
}

.forgot-link:hover {
    color: #0f2936 !important;
}

.button-primary {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #153b4f, #245f7d);
    border: none;
    border-radius: 14px;
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 150ms ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 24px rgba(21, 59, 79, 0.2);
}

.button-primary::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(255, 255, 255, 0.3), transparent 60%);
    opacity: 0;
    transition: opacity 150ms ease;
}

.button-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 18px 32px rgba(21, 59, 79, 0.25);
}

.button-primary:hover:not(:disabled)::after {
    opacity: 1;
}

.button-primary.is-loading {
    opacity: 0.85;
    cursor: wait;
}

.image-column {
    position: relative;
}

.image-column::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(140deg, rgba(15, 41, 54, 0.18), rgba(6, 182, 212, 0.16));
}

.form-field {
    opacity: 0;
    transform: translateY(18px);
    animation: slideInUp 0.6s ease-out forwards;
}

.form-body .form-field:nth-child(1) {
    animation-delay: 0.1s;
}

.form-body .form-field:nth-child(2) {
    animation-delay: 0.2s;
}

.form-body .form-field:nth-child(3) {
    animation-delay: 0.3s;
}

.form-body .form-field:nth-child(4) {
    animation-delay: 0.4s;
}

.input-error {
    color: #ef4444;
    font-size: 0.75rem;
    font-weight: 500;
}

.social-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 2rem 0 1.25rem;
    color: #64748b;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.social-divider::before,
.social-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(148, 163, 184, 0.4);
}

.social-buttons {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}

.social-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: #ffffff;
    font-weight: 600;
    color: #0f172a;
    transition: transform 150ms ease, box-shadow 150ms ease, border-color 150ms ease;
}

.social-button:hover {
    transform: translateY(-1px);
    border-color: rgba(15, 23, 42, 0.3);
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
}

.social-icon {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.social-svg {
    width: 18px;
    height: 18px;
    display: block;
}

@keyframes slideInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.button-primary .button-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loader-content {
    text-align: center;
    color: #ffffff;
}

.loader-animation {
    width: 48px;
    height: 48px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@media (max-width: 1240px) {
    .login-page {
        background: #0f2936;
    }

    .sign_in {
        min-height: calc(100vh - 72px);
        justify-content: center;
        margin-left: 0;
        padding: 0 2rem;
    }

    .form-column {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .form-panel {
        width: min(640px, 100%);
        margin: 0 auto;
        padding: 3rem 3rem;
        background: rgba(15, 41, 54, 0.75);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .form-title {
        color: #ffffff;
        font-size: 2.4rem;
        line-height: 1.2;
    }

    .form-subtitle {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.05rem;
    }

    .form-label {
        color: rgba(255, 255, 255, 0.8);
    }

    .sign_in input.form-input {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
        color: #ffffff;
    }

    .remember-label {
        color: #ffffff;
    }

    .forgot-link {
        color: #ffffff !important;
    }

    .image-column {
        display: none;
    }
}

@media (max-width: 768px) {
    .sign_in {
        height: auto !important;
        min-height: calc(100vh - 72px);
        align-items: flex-start;
        padding: 2rem 1.5rem 2.5rem;
        gap: 2rem;
    }

    .form-panel {
        width: 100%;
        padding: 2rem 1.5rem;
    }

    .form-title {
        font-size: 1.8rem;
    }

    .form-header {
        margin-bottom: 2rem;
    }

    .remember-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .social-buttons {
        grid-template-columns: 1fr;
    }
}
</style>
