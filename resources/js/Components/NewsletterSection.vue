<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const email = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

const subscribe = async () => {
    if (loading.value) return;
    error.value = '';
    success.value = '';

    if (!email.value) {
        error.value = 'Please enter your email.';
        return;
    }

    loading.value = true;

    try {
        await axios.post('/api/newsletter/subscriptions', {
            email: email.value,
            source: 'homepage_cta',
            locale: page.props.locale,
        });
        success.value = 'Check your inbox to confirm your subscription.';
        email.value = '';
    } catch (err) {
        if (err?.response?.status === 409) {
            error.value = err.response?.data?.message || 'This email is already subscribed.';
        } else if (err?.response?.status === 422) {
            const message = err.response?.data?.errors?.email?.[0];
            error.value = message || 'Please enter a valid email.';
        } else {
            error.value = 'Unable to subscribe right now. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <section class="nl-section">
        <div class="nl-orb nl-orb--1"></div>
        <div class="nl-orb nl-orb--2"></div>
        <div class="nl-orb nl-orb--3"></div>

        <div class="full-w-container">
            <div class="nl-card">
                <div class="nl-layout">

                    <!-- Left: Copy -->
                    <div class="nl-content">
                        <div class="nl-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Stay in the Loop
                        </div>
                        <h2 class="nl-headline">Never miss <span>a deal.</span></h2>
                        <p class="nl-subtext">
                            Subscribe for exclusive offers, travel tips, and early access to our best rental deals — delivered straight to your inbox.
                        </p>

                        <div class="nl-trust">
                            <div class="nl-trust-item">
                                <div class="nl-trust-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <div>
                                    <div class="nl-trust-value">12,000+</div>
                                    Subscribers
                                </div>
                            </div>
                            <div class="nl-trust-item">
                                <div class="nl-trust-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </div>
                                <div>
                                    <div class="nl-trust-value">Weekly</div>
                                    Curated deals
                                </div>
                            </div>
                            <div class="nl-trust-item">
                                <div class="nl-trust-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                </div>
                                <div>
                                    <div class="nl-trust-value">No spam</div>
                                    Unsubscribe anytime
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Form Card -->
                    <div class="nl-form-col">
                        <div class="nl-form-card">
                            <div class="nl-form-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3zm-8.27 4a2 2 0 0 1-3.46 0"/></svg>
                                Get notified first
                            </div>
                            <form @submit.prevent="subscribe">
                                <div class="nl-input-group">
                                    <input
                                        v-model="email"
                                        type="email"
                                        placeholder="Your email address"
                                        class="nl-input"
                                        autocomplete="email"
                                        :disabled="loading"
                                    />
                                    <button type="submit" class="nl-btn" :disabled="loading">
                                        {{ loading ? 'Sending...' : 'Subscribe' }}
                                        <svg v-if="!loading" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </div>
                                <p v-if="error" class="nl-feedback nl-feedback--error">{{ error }}</p>
                                <p v-if="success" class="nl-feedback nl-feedback--success">{{ success }}</p>
                            </form>
                            <p class="nl-privacy">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                We respect your privacy. Unsubscribe anytime.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</template>

<style>
/* ══════════════════════════════════════════
   NEWSLETTER SECTION — LIGHT THEME
   ══════════════════════════════════════════ */
.nl-section {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 45%, #f1f5f9 100%);
    color: #0f172a;
}

.nl-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 600px 450px at 10% 20%, rgba(21, 59, 79, 0.04), transparent),
        radial-gradient(ellipse 500px 400px at 90% 75%, rgba(46, 167, 173, 0.06), transparent),
        radial-gradient(ellipse 800px 300px at 50% 50%, rgba(21, 59, 79, 0.02), transparent);
    pointer-events: none;
    z-index: 0;
}

.nl-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    pointer-events: none;
    z-index: 0;
    animation: nlOrbFloat 16s ease-in-out infinite;
}
.nl-orb--1 { width: 320px; height: 320px; background: rgba(46, 167, 173, 0.12); top: -10%; left: -5%; animation-delay: 0s; }
.nl-orb--2 { width: 240px; height: 240px; background: rgba(21, 59, 79, 0.06); bottom: -8%; right: -4%; animation-delay: -6s; }
.nl-orb--3 { width: 180px; height: 180px; background: rgba(46, 167, 173, 0.08); top: 55%; right: 18%; animation-delay: -10s; }

@keyframes nlOrbFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-18px) scale(1.04); }
}

/* ── Card ── */
.nl-card {
    position: relative;
    z-index: 1;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.07);
    border-radius: 24px;
    padding: clamp(2.5rem, 5vw, 3.5rem) clamp(2rem, 5vw, 4rem);
    overflow: hidden;
    box-shadow:
        0 1px 2px rgba(21, 59, 79, 0.03),
        0 8px 24px rgba(21, 59, 79, 0.06),
        0 24px 48px rgba(21, 59, 79, 0.04);
    transition: box-shadow 0.5s cubic-bezier(0.22, 1, 0.36, 1),
                border-color 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.nl-card:hover {
    box-shadow:
        0 1px 2px rgba(21, 59, 79, 0.03),
        0 12px 32px rgba(21, 59, 79, 0.08),
        0 32px 64px rgba(21, 59, 79, 0.06);
    border-color: rgba(15, 23, 42, 0.1);
}

.nl-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #153b4f, #2ea7ad, #153b4f);
    opacity: 0.85;
}

/* ── Layout ── */
.nl-layout {
    display: flex;
    align-items: center;
    gap: clamp(2rem, 4vw, 4rem);
}

.nl-content {
    flex: 1;
    min-width: 0;
}

.nl-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #153b4f;
    padding: 7px 18px;
    background: rgba(21, 59, 79, 0.06);
    border: 1px solid rgba(21, 59, 79, 0.1);
    border-radius: 100px;
    margin-bottom: 1.25rem;
}

.nl-badge svg {
    width: 14px;
    height: 14px;
    stroke: #2ea7ad;
    fill: none;
}

.nl-headline {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(1.75rem, 3.5vw, 2.5rem);
    font-weight: 800;
    line-height: 1.15;
    color: #0f172a;
    letter-spacing: -0.025em;
    margin-bottom: 0.75rem;
}

.nl-headline span {
    background: linear-gradient(135deg, #153b4f, #2ea7ad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nl-subtext {
    font-size: 1.05rem;
    line-height: 1.65;
    color: #64748b;
    max-width: 32rem;
}

/* ── Trust ── */
.nl-trust {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
}

.nl-trust-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.85rem;
    color: #64748b;
}

.nl-trust-icon {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(21, 59, 79, 0.04);
    border: 1px solid rgba(21, 59, 79, 0.07);
    border-radius: 10px;
    flex-shrink: 0;
}

.nl-trust-icon svg {
    width: 16px;
    height: 16px;
    color: #2ea7ad;
}

.nl-trust-value {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    color: #1e293b;
    font-size: 0.92rem;
}

/* ── Form column ── */
.nl-form-col {
    flex: 0 0 clamp(300px, 40%, 440px);
    min-width: 0;
}

.nl-form-card {
    background: linear-gradient(145deg, #f8fafc, #f1f5f9);
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8),
                0 4px 12px rgba(21, 59, 79, 0.04);
}

.nl-form-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.02rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nl-form-title svg {
    width: 18px;
    height: 18px;
    color: #2ea7ad;
}

.nl-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.nl-input {
    width: 100%;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.1);
    border-radius: 12px;
    color: #0f172a;
    padding: 0.85rem 1rem;
    font-size: 0.92rem;
    font-family: inherit;
    outline: none;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.nl-input:focus {
    border-color: rgba(46, 167, 173, 0.5);
    box-shadow: 0 0 0 3px rgba(46, 167, 173, 0.1);
}

.nl-input::placeholder {
    color: #94a3b8;
}

.nl-input:disabled {
    opacity: 0.6;
    background: #f8fafc;
}

.nl-btn {
    width: 100%;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #153b4f, #2ea7ad);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    padding: 0.85rem 1.6rem;
    font-weight: 700;
    font-size: 0.95rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
    letter-spacing: 0.02em;
    cursor: pointer;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 0 4px 14px rgba(21, 59, 79, 0.18);
}

.nl-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(21, 59, 79, 0.22);
}

.nl-btn:active:not(:disabled) {
    transform: translateY(0);
}

.nl-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.nl-btn svg {
    width: 18px;
    height: 18px;
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.nl-btn:hover:not(:disabled) svg {
    transform: translateX(3px);
}

.nl-btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    animation: nlBtnShimmer 3.5s ease-in-out infinite;
}

@keyframes nlBtnShimmer {
    0%, 70%, 100% { left: -100%; }
    85% { left: 100%; }
}

/* ── Feedback ── */
.nl-feedback {
    margin-top: 0.65rem;
    font-size: 0.84rem;
    line-height: 1.4;
}

.nl-feedback--error { color: #dc2626; }
.nl-feedback--success { color: #059669; }

.nl-privacy {
    margin-top: 1rem;
    font-size: 0.78rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.nl-privacy svg {
    width: 13px;
    height: 13px;
    color: #94a3b8;
    flex-shrink: 0;
}

/* ── Responsive ── */
@media (max-width: 900px) {
    .nl-layout {
        flex-direction: column;
        text-align: center;
    }

    .nl-content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .nl-subtext {
        max-width: 100%;
    }

    .nl-form-col {
        flex: none;
        width: 100%;
        max-width: 440px;
    }

    .nl-trust {
        justify-content: center;
        flex-wrap: wrap;
        gap: 1.25rem;
    }
}

@media (max-width: 600px) {
    .nl-card {
        padding: 2rem 1.5rem;
        border-radius: 18px;
    }

    .nl-form-card {
        padding: 1.5rem;
    }

    .nl-headline {
        font-size: 1.6rem;
    }

    .nl-trust {
        gap: 1rem;
    }

    .nl-trust-item {
        font-size: 0.8rem;
    }
}
</style>
