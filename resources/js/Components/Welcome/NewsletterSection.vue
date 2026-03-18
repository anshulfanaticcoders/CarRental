<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const email = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

const subscribe = async () => {
    if (loading.value) return;
    error.value = ''; success.value = '';
    if (!email.value) { error.value = 'Please enter your email.'; return; }
    loading.value = true;
    try {
        await axios.post('/api/newsletter/subscriptions', { email: email.value, source: 'homepage_cta', locale: page.props.locale });
        success.value = 'Check your inbox to confirm your subscription.';
        email.value = '';
    } catch (err) {
        if (err?.response?.status === 409) error.value = err.response?.data?.message || 'This email is already subscribed.';
        else if (err?.response?.status === 422) error.value = err.response?.data?.errors?.email?.[0] || 'Please enter a valid email.';
        else error.value = 'Unable to subscribe right now. Please try again.';
    } finally { loading.value = false; }
};
</script>

<template>
    <section class="nl-section">
        <div class="nl-glow"></div>
        <div class="full-w-container nl-z">
            <div class="nl-card">
                <span class="nl-label">Stay Updated</span>
                <h2 class="nl-title">Deals. Routes. Inspiration.</h2>
                <p class="nl-text">Join 40,000+ travelers getting exclusive deals and curated road trip guides every week.</p>
                <form class="nl-form" @submit.prevent="subscribe">
                    <input v-model="email" type="email" class="nl-input" placeholder="Enter your email" />
                    <button type="submit" class="nl-btn" :disabled="loading">{{ loading ? '...' : 'Subscribe' }}</button>
                </form>
                <p v-if="error" class="nl-error">{{ error }}</p>
                <p v-if="success" class="nl-success">{{ success }}</p>
                <p class="nl-fine">No spam. Unsubscribe anytime.</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.nl-section {
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(160deg, #0a1d28 0%, #153b4f 45%, #0c2535 100%);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.nl-glow {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(circle at 50% 0%, rgba(34,211,238,0.05), transparent 60%);
}
.nl-z { position: relative; z-index: 1; }

.nl-card { max-width: 640px; margin-inline: auto; text-align: center; }

.nl-label {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: #22d3ee;
}
.nl-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #22d3ee; }

.nl-title { font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; line-height: 1.12; letter-spacing: -0.02em; color: #fff; margin-top: 0.75rem; margin-bottom: 0.75rem; }
.nl-text { color: rgba(255,255,255,0.5); font-size: 0.95rem; margin-bottom: 2rem; }

.nl-form { display: flex; gap: 0.5rem; max-width: 440px; margin-inline: auto; }
.nl-input {
    flex: 1; padding: 0.85rem 1.25rem; border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.06);
    color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9rem;
    outline: none; transition: border-color 0.3s;
}
.nl-input::placeholder { color: rgba(255,255,255,0.35); }
.nl-input:focus { border-color: #22d3ee; }

.nl-btn {
    padding: 0.85rem 1.75rem; border-radius: 14px; border: none;
    background: #06b6d4; color: #0a1d28; font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.92rem;
    cursor: pointer; transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
}
.nl-btn:hover { background: #22d3ee; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(6,182,212,0.3); }

.nl-fine { font-size: 0.76rem; color: rgba(255,255,255,0.3); margin-top: 1rem; }
.nl-error { font-size: 0.85rem; color: #f87171; margin-top: 0.75rem; }
.nl-success { font-size: 0.85rem; color: #34d399; margin-top: 0.75rem; }

@media (max-width: 768px) { .nl-form { flex-direction: column; } }
</style>
