<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import loginBg from '../../../assets/loginpageImage.jpg'
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
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
    <Head>
        <meta name="robots" content="noindex, nofollow">
        <title inertia>{{ _t('login', 'log_in') }}</title>
    </Head>

    <Toaster position="bottom-right" :toastOptions="{ style: { background: 'black', color: 'white', border: '1px solid #333' } }" />

    <main class="login-page">
        <!-- Form Side -->
        <div class="login-form-side">
            <div class="logo-bar">
                <Link :href="route('welcome', { locale: page.props.locale })" class="logo-link">
                    <ApplicationLogo class="logo" />
                </Link>
            </div>

            <div class="form-center">
                <div class="form-card">
                    <h1 class="form-title">{{ _t('login', 'sign_in') }}</h1>
                    <p class="form-sub">{{ _t('login', 'login_description') }}</p>

                    <div v-if="oauthError" class="oauth-error">{{ oauthError }}</div>

                    <form @submit.prevent="submit">
                        <!-- Email -->
                        <div class="field">
                            <InputLabel for="email" :value="_t('login', 'email_address')" class="field-label" />
                            <TextInput id="email" type="email" class="field-input" v-model="form.email" required autofocus autocomplete="username" placeholder="name@example.com" />
                            <InputError class="field-error" :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div class="field">
                            <InputLabel for="password" :value="_t('login', 'password')" class="field-label" />
                            <div class="pw-wrap">
                                <TextInput :type="showPassword ? 'text' : 'password'" id="password" class="field-input" v-model="form.password" required autocomplete="current-password" placeholder="Enter your password" />
                                <button type="button" @click="showPassword = !showPassword" class="pw-toggle">
                                    {{ showPassword ? _t('registerUser', 'hide_password') : _t('registerUser', 'show_password') }}
                                </button>
                            </div>
                            <InputError class="field-error" :message="form.errors.password" />
                        </div>

                        <!-- Remember + Forgot -->
                        <div class="field-row">
                            <label class="remember" for="remember">
                                <Checkbox name="remember" id="remember" v-model:checked="form.remember" />
                                <span>{{ _t('login', 'remember_me') }}</span>
                            </label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="forgot">{{ _t('login', 'forgot_password') }}</Link>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="submit-btn" :class="{ 'is-loading': isLoggingIn }" :disabled="form.processing || isLoggingIn">
                            <span v-if="isLoggingIn" class="btn-loading"><span class="spinner"></span> Signing in...</span>
                            <span v-else>{{ _t('login', 'sign_in_button') }}</span>
                        </button>
                    </form>

                    <!-- Social -->
                    <div class="divider"><span>{{ _t('login', 'social_divider') }}</span></div>
                    <div class="social-btns">
                        <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'google' })" class="social-btn">
                            <svg viewBox="0 0 48 48" width="22" height="22"><path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.25 3.6l6.9-6.9C35.7 2.57 30.23 0 24 0 14.62 0 6.53 5.38 2.55 13.22l8.06 6.26C12.5 13.04 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.64-.15-3.22-.43-4.75H24v9.02h12.98c-.56 3.02-2.25 5.58-4.77 7.3l7.32 5.68c4.28-3.95 6.45-9.77 6.45-17.25z"/><path fill="#FBBC05" d="M10.61 28.74c-.48-1.45-.76-2.99-.76-4.74 0-1.75.27-3.29.76-4.74l-8.06-6.26C.92 16.24 0 19.9 0 24c0 4.1.92 7.76 2.55 11l8.06-6.26z"/><path fill="#34A853" d="M24 48c6.23 0 11.45-2.06 15.27-5.6l-7.32-5.68c-2.02 1.36-4.6 2.16-7.95 2.16-6.2 0-11.5-3.54-13.39-8.29l-8.06 6.26C6.53 42.62 14.62 48 24 48z"/></svg>
                            Google
                        </a>
                        <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'facebook' })" class="social-btn">
                            <svg viewBox="0 0 48 48" width="22" height="22"><path fill="#1877F2" d="M48 24c0 13.26-10.74 24-24 24S0 37.26 0 24 10.74 0 24 0s24 10.74 24 24z"/><path fill="#fff" d="M26.67 24.98h5.15l.81-5.3h-5.96v-3.44c0-1.53.75-3.02 3.17-3.02h2.45V8.7s-2.22-.38-4.35-.38c-4.44 0-7.34 2.69-7.34 7.56v3.8h-4.94v5.3h4.94V40h6.07V24.98z"/></svg>
                            Facebook
                        </a>
                    </div>

                    <!-- Create Account -->
                    <p class="create-link">{{ _t('login', 'no_account') }} <Link :href="route('register', { locale: page.props.locale })" class="create-link-a">{{ _t('login', 'create_account') }}</Link></p>
                </div>
            </div>
        </div>

        <!-- Image Side -->
        <div class="login-image-side">
            <img :src="loginBg" alt="Car rental" />
            <div class="image-overlay"></div>
            <div class="image-content">
                <h2>Your journey starts with the perfect car.</h2>
                <p>Compare prices from 200+ trusted providers across Europe. Best prices, free cancellation, 24/7 support.</p>
                <div class="image-trust">
                    <span><i class="trust-dot"></i> Free cancellation</span>
                    <span><i class="trust-dot"></i> Best price guarantee</span>
                    <span><i class="trust-dot"></i> 24/7 support</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Loader -->
    <div v-if="isLoggingIn" class="loader-overlay">
        <div class="loader-content"><div class="loader-spinner"></div><p>Signing you in...</p></div>
    </div>
</template>


<style scoped>
/* Layout */
.login-page { display: flex; min-height: 100vh; --ease: cubic-bezier(0.22, 1, 0.36, 1); --dur: 0.3s; }

/* Form Side */
.login-form-side { flex: 1; display: flex; flex-direction: column; background: linear-gradient(160deg, #f9fcfe, #f0f6fa 40%, #f8fafc); position: relative; overflow: hidden; }
.login-form-side::before { content: ''; position: absolute; top: -200px; left: -200px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(34,211,238,0.05), transparent 65%); pointer-events: none; }
.logo-bar { padding: 24px 40px; position: relative; z-index: 1; }
.logo-link { display: inline-block; width: 10rem; transition: opacity 0.3s var(--ease); }
.logo-link:hover { opacity: 0.8; }
.logo { width: 100%; height: auto; }

.login-form-side::after { content: ''; position: absolute; bottom: -150px; right: -100px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(21,59,79,0.03), transparent 65%); pointer-events: none; }

/* Form Card */
.form-center { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px; position: relative; z-index: 1; }
.form-card { width: 75%; background: rgba(255,255,255,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(176,212,230,0.45); border-radius: 28px; padding: 44px 40px; box-shadow: 0 16px 48px rgba(21,59,79,0.1), 0 0 0 1px rgba(255,255,255,0.6) inset; position: relative; overflow: hidden; }
.form-card::before { content: ''; position: absolute; top: -60%; right: -40%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(34,211,238,0.06), transparent 70%); pointer-events: none; }

/* Title */
.form-title { font-size: 2rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; margin-bottom: 6px; position: relative; z-index: 1; }
.form-sub { font-size: 0.95rem; color: #64748b; margin-bottom: 36px; line-height: 1.5; position: relative; z-index: 1; }

/* Fields */
.field { margin-bottom: 20px; position: relative; z-index: 1; }
.field-label { font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; margin-bottom: 8px; }
.field-input { width: 100%; padding: 14px 16px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 0.95rem; font-family: inherit; color: #0f172a; background: #fff; outline: none; transition: border-color var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
.field-input:focus { border-color: #22d3ee; box-shadow: 0 0 0 4px rgba(34,211,238,0.12); }
.field-input::placeholder { color: #94a3b8; }
.field-error { margin-top: 6px; color: #ef4444; font-size: 0.78rem; font-weight: 500; }

/* Password */
.pw-wrap { position: relative; }
.pw-wrap .field-input { padding-right: 80px; }
.pw-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); font-size: 0.75rem; font-weight: 600; color: #153b4f; background: rgba(34,211,238,0.08); border: 1px solid rgba(34,211,238,0.2); padding: 4px 10px; border-radius: 999px; cursor: pointer; transition: background var(--dur) var(--ease); }
.pw-toggle:hover { background: rgba(34,211,238,0.15); }

/* Remember + Forgot */
.field-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative; z-index: 1; }
.remember { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #475569; cursor: pointer; }
.remember input { width: 16px; height: 16px; accent-color: #153b4f; }
.forgot { font-size: 0.88rem; font-weight: 600; color: #153b4f; transition: color var(--dur) var(--ease); text-decoration: none; }
.forgot:hover { color: #22d3ee; }

/* Submit */
.submit-btn { width: 100%; padding: 16px; border: none; border-radius: 14px; font-size: 1rem; font-weight: 700; font-family: inherit; color: #fff; background: linear-gradient(135deg, #153b4f, #1c4d66); cursor: pointer; box-shadow: 0 8px 24px rgba(21,59,79,0.2); position: relative; overflow: hidden; z-index: 1; transition: transform var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
.submit-btn::after { content: ''; position: absolute; inset: 0; background: linear-gradient(120deg, rgba(255,255,255,0.2), transparent 50%); opacity: 0; transition: opacity var(--dur); }
.submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(21,59,79,0.25); }
.submit-btn:hover:not(:disabled)::after { opacity: 1; }
.submit-btn.is-loading { opacity: 0.85; cursor: wait; }
.btn-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
.spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }

/* Divider */
.divider { display: flex; align-items: center; gap: 14px; margin: 28px 0; color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.14em; font-weight: 600; position: relative; z-index: 1; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); }

/* Social */
.social-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; position: relative; z-index: 1; }
.social-btn { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px; border-radius: 14px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 0.88rem; font-weight: 600; color: #0f172a; text-decoration: none; transition: border-color var(--dur) var(--ease), transform var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
.social-btn:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(21,59,79,0.06); }

/* Create Account */
.create-link { text-align: center; margin-top: 28px; font-size: 0.9rem; color: #64748b; position: relative; z-index: 1; }
.create-link-a { color: #153b4f; font-weight: 700; margin-left: 4px; transition: color var(--dur) var(--ease); }
.create-link-a:hover { color: #22d3ee; }

/* OAuth Error */
.oauth-error { margin-bottom: 20px; padding: 12px 16px; border-radius: 12px; background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.15); color: #dc2626; font-size: 0.88rem; font-weight: 600; text-align: center; position: relative; z-index: 1; }

/* Image Side */
.login-image-side { flex: 0 0 50%; position: relative; overflow: hidden; }
.login-image-side img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.image-overlay { position: absolute; inset: 0; background: linear-gradient(160deg, rgba(10,29,40,0.25), rgba(10,29,40,0.6) 60%, rgba(10,29,40,0.85)); }
.image-content { position: absolute; bottom: 48px; left: 48px; right: 48px; z-index: 1; color: #fff; }
.image-content h2 { font-size: 2.2rem; font-weight: 800; line-height: 1.15; margin-bottom: 10px; }
.image-content p { font-size: 0.95rem; color: rgba(255,255,255,0.7); line-height: 1.6; }
.image-trust { display: flex; gap: 20px; margin-top: 24px; }
.image-trust span { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: rgba(255,255,255,0.6); font-weight: 500; }
.trust-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22d3ee; box-shadow: 0 0 8px rgba(34,211,238,0.4); }

/* Loader */
.loader-overlay { position: fixed; inset: 0; background: rgba(10,22,32,0.55); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.loader-content { text-align: center; color: #fff; }
.loader-spinner { width: 48px; height: 48px; margin: 0 auto 1rem; border-radius: 50%; border: 3px solid rgba(255,255,255,0.2); border-top-color: #22d3ee; animation: spin 0.8s linear infinite; }

@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 1024px) {
    .login-image-side { display: none; }
}
@media (max-width: 640px) {
    .form-center { padding: 24px 20px; }
    .form-card { width: 100%; padding: 32px 24px; border-radius: 20px; }
    .form-title { font-size: 1.6rem; }
    .social-btns { grid-template-columns: 1fr; }
    .field-row { flex-direction: column; align-items: flex-start; gap: 12px; }
    .image-trust { flex-wrap: wrap; }
}
</style>
