<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Headphones, ShieldCheck } from 'lucide-vue-next';
import bookingStatusIllustration from '../../../assets/bookingstatusIcon.svg';

const props = defineProps({
    eyebrow: { type: String, default: 'Booking status' },
    title: { type: String, required: true },
    message: { type: String, required: true },
    tone: { type: String, default: 'neutral' },
    icon: { type: [Object, Function], default: null },
    illustration: { type: String, default: null },
    primaryLabel: { type: String, default: 'Return to search' },
    primaryHref: { type: String, default: '/' },
    primaryAction: { type: Function, default: null },
    secondaryLabel: { type: String, default: 'Back to home' },
    secondaryHref: { type: String, default: '/' },
    secondaryAction: { type: Function, default: null },
    booking: { type: Object, default: null },
    sessionId: { type: String, default: null },
});

const toneClass = computed(() => `tone-${['success', 'warning', 'danger'].includes(props.tone) ? props.tone : 'neutral'}`);

const toneLabel = computed(() => ({
    success: 'Confirmed',
    warning: 'In progress',
    danger: 'Needs attention',
    neutral: 'Update',
}[props.tone] || 'Update'));

const hasValue = (v) => v !== null && v !== undefined && v !== '';
const formatStatus = (v) => hasValue(v) ? String(v).replaceAll('_', ' ') : '';

const amountPaid = computed(() => {
    if (!hasValue(props.booking?.amount_paid)) return '';
    const currency = props.booking?.booking_currency || '';
    const amount = Number(props.booking.amount_paid);
    const formatted = Number.isFinite(amount) ? amount.toFixed(2) : props.booking.amount_paid;
    return `${currency} ${formatted}`.trim();
});

const detailItems = computed(() => {
    if (!props.booking) return [];
    return [
        { label: 'Booking', value: props.booking.booking_number },
        { label: 'Status', value: formatStatus(props.booking.booking_status) },
        { label: 'Payment', value: formatStatus(props.booking.payment_status) },
        { label: 'Paid', value: amountPaid.value },
    ].filter((item) => hasValue(item.value));
});

const hasReferenceBlock = computed(() => detailItems.value.length > 0 || hasValue(props.sessionId));
const resolvedIllustration = computed(() => props.illustration || bookingStatusIllustration);
</script>

<template>
    <main class="bo-page" :class="toneClass">
        <div class="bo-orbit" aria-hidden="true">
            <span class="bo-orbit-dot bo-orbit-dot--a"></span>
            <span class="bo-orbit-dot bo-orbit-dot--b"></span>
            <span class="bo-orbit-dot bo-orbit-dot--c"></span>
        </div>

        <div class="full-w-container bo-wrap">
            <article class="bo-card" :class="toneClass">
                <span class="bo-accent-stripe" aria-hidden="true"></span>

                <section class="bo-hero">
                    <div class="bo-hero-bg" aria-hidden="true">
                        <span class="bo-glow bo-glow--lg"></span>
                        <span class="bo-glow bo-glow--sm"></span>
                        <svg class="bo-grid" aria-hidden="true">
                            <defs>
                                <pattern id="bo-grid-pattern" width="32" height="32" patternUnits="userSpaceOnUse">
                                    <path d="M 32 0 L 0 0 0 32" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#bo-grid-pattern)" />
                        </svg>
                    </div>

                    <div class="bo-hero-inner">
                        <div class="bo-hero-top">
                            <span class="bo-eyebrow stagger" style="--d:0ms">
                                <span class="bo-eyebrow-dot"></span>
                                {{ eyebrow }}
                            </span>
                            <span class="bo-status-pill stagger" :class="toneClass" style="--d:60ms">
                                <span class="bo-status-pulse"></span>
                                {{ toneLabel }}
                            </span>
                        </div>

                        <div class="bo-hero-art stagger" style="--d:120ms">
                            <span class="bo-art-ring bo-art-ring--outer"></span>
                            <span class="bo-art-ring bo-art-ring--inner"></span>
                            <div class="bo-art-frame">
                                <img :src="resolvedIllustration" alt="" loading="lazy" />
                            </div>
                            <span v-if="icon" class="bo-art-badge" :class="toneClass">
                                <component :is="icon" class="h-4 w-4" aria-hidden="true" />
                            </span>
                        </div>

                        <h1 class="bo-title stagger" style="--d:180ms">{{ title }}</h1>
                        <p class="bo-message stagger" style="--d:240ms">{{ message }}</p>
                    </div>
                </section>

                <section class="bo-body">
                    <div v-if="hasReferenceBlock" class="bo-reference stagger" style="--d:300ms">
                        <header class="bo-reference-head">
                            <span class="bo-reference-icon"><ShieldCheck class="h-3.5 w-3.5" aria-hidden="true" /></span>
                            <span class="bo-reference-label">Reference</span>
                        </header>

                        <dl v-if="detailItems.length" class="bo-reference-list">
                            <div v-for="(item, i) in detailItems" :key="item.label" class="bo-reference-row" :style="`--d:${340 + i * 40}ms`">
                                <dt>{{ item.label }}</dt>
                                <dd>{{ item.value }}</dd>
                            </div>
                        </dl>

                        <div v-if="sessionId" class="bo-payment-ref stagger" :style="`--d:${340 + detailItems.length * 40}ms`">
                            <span>Payment reference</span>
                            <strong>{{ sessionId }}</strong>
                        </div>
                    </div>

                    <div class="bo-support stagger" style="--d:480ms">
                        <span class="bo-support-icon"><Headphones class="h-4 w-4" aria-hidden="true" /></span>
                        <p>
                            <strong>Need help?</strong>
                            Share the reference above with support — we can trace this safely without exposing card details.
                        </p>
                    </div>

                    <div class="bo-actions stagger" style="--d:540ms">
                        <button v-if="primaryAction" type="button" class="bo-btn bo-btn--primary" @click="primaryAction">
                            <span>{{ primaryLabel }}</span>
                            <ArrowRight class="h-4 w-4 bo-btn-arrow" aria-hidden="true" />
                        </button>
                        <Link v-else :href="primaryHref" class="bo-btn bo-btn--primary">
                            <span>{{ primaryLabel }}</span>
                            <ArrowRight class="h-4 w-4 bo-btn-arrow" aria-hidden="true" />
                        </Link>

                        <button v-if="secondaryAction" type="button" class="bo-btn bo-btn--ghost" @click="secondaryAction">
                            {{ secondaryLabel }}
                        </button>
                        <Link v-else :href="secondaryHref" class="bo-btn bo-btn--ghost">
                            {{ secondaryLabel }}
                        </Link>
                    </div>
                </section>
            </article>
        </div>
    </main>
</template>

<style scoped>
.bo-page {
    --ease: cubic-bezier(0.22, 1, 0.36, 1);
    --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
    --tone-color: var(--accent-400);
    --tone-bright: #67e8f9;
    --tone-soft: rgba(34, 211, 238, 0.22);
    --tone-shadow: rgba(34, 211, 238, 0.28);
    position: relative;
    padding-top: clamp(1.5rem, 3.5vw, 2.5rem);
    padding-bottom: clamp(1.5rem, 3vw, 2.25rem);
    background:
        radial-gradient(circle at 12% 88%, rgba(34, 211, 238, 0.05), transparent 50%),
        radial-gradient(circle at 88% 12%, rgba(21, 59, 79, 0.06), transparent 55%),
        linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
    color: var(--gray-800);
    font-family: "IBM Plex Sans", serif;
    overflow: hidden;
}

.bo-page.tone-success { --tone-color: #34d399; --tone-bright: #6ee7b7; --tone-soft: rgba(52, 211, 153, 0.22); --tone-shadow: rgba(52, 211, 153, 0.3); }
.bo-page.tone-warning { --tone-color: #fbbf24; --tone-bright: #fde68a; --tone-soft: rgba(251, 191, 36, 0.22); --tone-shadow: rgba(251, 191, 36, 0.3); }
.bo-page.tone-danger { --tone-color: #fb7185; --tone-bright: #fda4af; --tone-soft: rgba(251, 113, 133, 0.22); --tone-shadow: rgba(251, 113, 133, 0.3); }

.bo-orbit {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.bo-orbit-dot {
    position: absolute;
    border-radius: 999px;
    filter: blur(60px);
    opacity: 0.6;
}

.bo-orbit-dot--a { top: 8%; left: -4%; width: 240px; height: 240px; background: var(--tone-soft); animation: bo-drift 18s var(--ease) infinite alternate; }
.bo-orbit-dot--b { bottom: 6%; right: -6%; width: 280px; height: 280px; background: rgba(21, 59, 79, 0.08); animation: bo-drift 22s var(--ease) infinite alternate-reverse; }
.bo-orbit-dot--c { top: 50%; left: 30%; width: 180px; height: 180px; background: rgba(34, 211, 238, 0.06); animation: bo-drift 26s var(--ease) infinite alternate; }

@keyframes bo-drift {
    0% { transform: translate3d(0, 0, 0) scale(1); }
    50% { transform: translate3d(40px, -30px, 0) scale(1.08); }
    100% { transform: translate3d(-30px, 40px, 0) scale(0.95); }
}

.bo-wrap { position: relative; z-index: 1; display: flex; justify-content: center; }

.bo-card {
    width: 100%;
    max-width: 60rem;
    position: relative;
    background: var(--white);
    border: 1px solid rgba(21, 59, 79, 0.06);
    border-radius: 24px;
    box-shadow:
        0 1px 1px rgba(21, 59, 79, 0.02),
        0 30px 70px -25px rgba(10, 29, 40, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.8) inset;
    overflow: hidden;
    animation: bo-card-in 700ms var(--ease-expo) both;
}

@keyframes bo-card-in {
    from { opacity: 0; transform: translateY(16px) scale(0.985); }
    to { opacity: 1; transform: none; }
}

.bo-accent-stripe {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent 0%, var(--tone-color) 30%, var(--tone-bright) 50%, var(--tone-color) 70%, transparent 100%);
    box-shadow: 0 0 18px var(--tone-shadow);
    z-index: 3;
    animation: bo-stripe-in 900ms var(--ease-expo) 200ms both;
    transform-origin: center;
}

@keyframes bo-stripe-in {
    from { transform: scaleX(0); opacity: 0; }
    to { transform: scaleX(1); opacity: 1; }
}

.bo-hero {
    position: relative;
    color: var(--white);
    padding: clamp(1.75rem, 3.5vw, 2.5rem);
    background: linear-gradient(135deg, #0a1d28 0%, #143548 48%, #0b2230 100%);
    overflow: hidden;
}

.bo-hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.bo-glow {
    position: absolute;
    border-radius: 999px;
    filter: blur(50px);
}

.bo-glow--lg {
    top: -30%;
    right: -10%;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle, var(--tone-soft), transparent 70%);
    animation: bo-glow-pulse 8s var(--ease) infinite alternate;
}

.bo-glow--sm {
    bottom: -20%;
    left: -10%;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(34, 211, 238, 0.12), transparent 70%);
    animation: bo-glow-pulse 11s var(--ease) infinite alternate-reverse;
}

@keyframes bo-glow-pulse {
    from { transform: translate3d(0, 0, 0) scale(1); opacity: 0.7; }
    to { transform: translate3d(20px, -10px, 0) scale(1.15); opacity: 1; }
}

.bo-grid {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0.55;
    mask-image: radial-gradient(circle at 50% 50%, black 30%, transparent 75%);
}

.bo-hero-inner { position: relative; z-index: 1; }

.bo-hero-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.bo-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.32rem 0.75rem 0.32rem 0.5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.78);
    font-family: var(--jakarta-font-family);
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.bo-eyebrow-dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: var(--tone-color);
    box-shadow: 0 0 0 3px var(--tone-soft);
}

.bo-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.32rem 0.75rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: var(--tone-bright);
    font-family: var(--jakarta-font-family);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.bo-status-pulse {
    position: relative;
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: var(--tone-color);
}

.bo-status-pulse::after {
    content: "";
    position: absolute;
    inset: -3px;
    border-radius: 999px;
    border: 1px solid var(--tone-color);
    opacity: 0;
    animation: bo-pulse 2.4s var(--ease) infinite;
}

@keyframes bo-pulse {
    0% { transform: scale(0.8); opacity: 0.9; }
    100% { transform: scale(2); opacity: 0; }
}

.bo-hero-art {
    position: relative;
    width: 152px;
    height: 152px;
    margin-bottom: 1.4rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bo-art-ring {
    position: absolute;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px;
}

.bo-art-ring--outer {
    inset: -14px;
    border-color: rgba(255, 255, 255, 0.07);
    animation: bo-ring 16s linear infinite;
    transform-origin: center;
}

.bo-art-ring--outer::before,
.bo-art-ring--outer::after {
    content: "";
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: var(--tone-color);
    box-shadow: 0 0 12px var(--tone-shadow);
}

.bo-art-ring--outer::before { top: -3px; left: 50%; transform: translateX(-50%); }
.bo-art-ring--outer::after { bottom: -3px; right: -3px; background: var(--accent-400); box-shadow: 0 0 10px rgba(34, 211, 238, 0.45); }

.bo-art-ring--inner {
    inset: -6px;
    border-color: rgba(255, 255, 255, 0.1);
    border-style: dashed;
    border-radius: 22px;
    animation: bo-ring 24s linear infinite reverse;
}

@keyframes bo-ring {
    to { transform: rotate(360deg); }
}

.bo-art-frame {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 20px;
    background: linear-gradient(160deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 20px 38px rgba(0, 0, 0, 0.28),
        0 0 0 1px rgba(255, 255, 255, 0.12),
        inset 0 -8px 16px rgba(21, 59, 79, 0.05);
    overflow: hidden;
}

.bo-art-frame::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 20%, var(--tone-soft), transparent 60%);
    opacity: 0.55;
    pointer-events: none;
}

.bo-art-frame img {
    position: relative;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bo-art-badge {
    position: absolute;
    bottom: -8px;
    right: -8px;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--white);
    color: var(--tone-color);
    box-shadow: 0 8px 18px rgba(10, 29, 40, 0.35);
    border: 2px solid var(--white);
    z-index: 2;
}

.bo-title {
    margin: 0;
    font-family: var(--jakarta-font-family);
    font-size: clamp(1.6rem, 2.6vw, 2rem);
    font-weight: 700;
    line-height: 1.14;
    letter-spacing: -0.018em;
    color: var(--white);
    text-wrap: balance;
}

.bo-message {
    margin: 0.55rem 0 0;
    max-width: 32rem;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.95rem;
    line-height: 1.6;
    text-wrap: pretty;
}

.bo-body {
    padding: clamp(1.25rem, 3vw, 1.75rem);
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.bo-reference {
    border-radius: 16px;
    background: linear-gradient(180deg, var(--gray-50), var(--white));
    border: 1px solid var(--gray-200);
    padding: 0.95rem 1rem;
}

.bo-reference-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.7rem;
}

.bo-reference-icon {
    display: inline-flex;
    width: 1.6rem;
    height: 1.6rem;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--primary-50);
    color: var(--primary-800);
}

.bo-reference-label {
    font-family: var(--jakarta-font-family);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--primary-800);
}

.bo-reference-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0;
    margin: 0;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(21, 59, 79, 0.06);
    background: var(--white);
}

.bo-reference-row {
    min-width: 0;
    padding: 0.7rem 0.85rem;
    position: relative;
    animation: bo-row-in 500ms var(--ease-expo) calc(var(--d, 0ms)) both;
}

.bo-reference-row:nth-child(even) { border-left: 1px solid rgba(21, 59, 79, 0.06); }
.bo-reference-row:nth-child(n+3) { border-top: 1px solid rgba(21, 59, 79, 0.06); }

.bo-reference-row dt {
    margin: 0;
    color: var(--gray-500);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.bo-reference-row dd {
    margin: 0.25rem 0 0;
    color: var(--gray-900);
    font-family: var(--jakarta-font-family);
    font-size: 0.92rem;
    font-weight: 700;
    text-transform: capitalize;
    overflow-wrap: anywhere;
}

.bo-payment-ref {
    margin-top: 0.6rem;
    padding: 0.65rem 0.85rem;
    border-radius: 10px;
    background: var(--primary-50);
    border: 1px dashed var(--primary-200);
}

.bo-payment-ref span {
    display: block;
    color: var(--primary-800);
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    opacity: 0.7;
}

.bo-payment-ref strong {
    display: block;
    margin-top: 0.2rem;
    color: var(--primary-900);
    font-family: ui-monospace, "JetBrains Mono", SFMono-Regular, Menlo, monospace;
    font-size: 0.78rem;
    font-weight: 500;
    overflow-wrap: anywhere;
    word-break: break-all;
}

.bo-support {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    padding: 0.7rem 0.9rem;
    border-radius: 12px;
    background: var(--primary-50);
    border: 1px solid rgba(21, 59, 79, 0.08);
}

.bo-support-icon {
    flex-shrink: 0;
    display: inline-flex;
    width: 1.85rem;
    height: 1.85rem;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--white);
    color: var(--primary-800);
    border: 1px solid rgba(21, 59, 79, 0.08);
}

.bo-support p {
    margin: 0;
    color: var(--gray-700);
    font-size: 0.84rem;
    line-height: 1.55;
}

.bo-support strong {
    color: var(--primary-900);
    font-family: var(--jakarta-font-family);
    font-weight: 700;
    margin-right: 0.2rem;
}

.bo-actions {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin-top: 0.25rem;
}

.bo-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 2.85rem;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    font-family: var(--jakarta-font-family);
    font-size: 0.92rem;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    overflow: hidden;
    transition: transform 250ms var(--ease), box-shadow 250ms var(--ease), background-color 250ms var(--ease), border-color 250ms var(--ease), color 250ms var(--ease);
    isolation: isolate;
}

.bo-btn-arrow {
    transition: transform 280ms var(--ease-expo);
}

.bo-btn:hover .bo-btn-arrow { transform: translateX(3px); }

.bo-btn--primary {
    color: var(--white);
    border: 1px solid transparent;
    background: linear-gradient(135deg, #143548 0%, #1c4d66 100%);
    box-shadow: 0 10px 24px -8px rgba(21, 59, 79, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.bo-btn--primary::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--accent-400), var(--primary-800));
    opacity: 0;
    transition: opacity 320ms var(--ease);
    z-index: -1;
}

.bo-btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px -10px rgba(21, 59, 79, 0.55), 0 0 0 1px rgba(34, 211, 238, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

.bo-btn--primary:hover::before { opacity: 0.6; }
.bo-btn--primary:active { transform: translateY(0); }

.bo-btn--primary:focus-visible {
    outline: 2px solid var(--accent-400);
    outline-offset: 3px;
}

.bo-btn--ghost {
    color: var(--primary-800);
    background: var(--white);
    border: 1px solid var(--gray-200);
}

.bo-btn--ghost:hover {
    transform: translateY(-1px);
    background: var(--primary-50);
    border-color: var(--primary-200);
    color: var(--primary-900);
}

.bo-btn--ghost:focus-visible {
    outline: 2px solid var(--primary-800);
    outline-offset: 3px;
}

.stagger {
    animation: bo-fade-up 600ms var(--ease-expo) calc(var(--d, 0ms) + 200ms) both;
}

@keyframes bo-fade-up {
    from { opacity: 0; transform: translate3d(0, 10px, 0); }
    to { opacity: 1; transform: none; }
}

@keyframes bo-row-in {
    from { opacity: 0; transform: translate3d(0, 6px, 0); }
    to { opacity: 1; transform: none; }
}

@media (min-width: 640px) {
    .bo-actions { flex-direction: row; }
    .bo-btn { flex: 1; }
}

@media (min-width: 960px) {
    .bo-card {
        display: grid;
        grid-template-columns: minmax(320px, 0.92fr) minmax(0, 1fr);
        max-width: 60rem;
    }

    .bo-accent-stripe { right: 0; left: 0; }

    .bo-hero {
        padding: clamp(2rem, 3.5vw, 2.75rem);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .bo-body {
        padding: clamp(1.5rem, 2.5vw, 2rem);
        justify-content: center;
        gap: 0.85rem;
        border-left: 1px solid rgba(21, 59, 79, 0.04);
    }

    .bo-hero-art {
        width: 168px;
        height: 168px;
    }
}

@media (max-width: 480px) {
    .bo-reference-list { grid-template-columns: 1fr; }
    .bo-reference-row:nth-child(even) { border-left: none; }
    .bo-reference-row:nth-child(n+2) { border-top: 1px solid rgba(21, 59, 79, 0.06); }
    .bo-hero-art { width: 132px; height: 132px; margin-bottom: 1.1rem; }
}

@media (prefers-reduced-motion: reduce) {
    .bo-card,
    .bo-accent-stripe,
    .stagger,
    .bo-reference-row {
        animation: none !important;
    }
    .bo-orbit-dot,
    .bo-glow,
    .bo-status-pulse::after,
    .bo-art-ring--outer,
    .bo-art-ring--inner {
        animation: none !important;
    }
    .bo-btn,
    .bo-btn-arrow {
        transition: none !important;
    }
}
</style>
