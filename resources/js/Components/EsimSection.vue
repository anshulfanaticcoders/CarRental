<script setup>
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Card, CardContent } from '@/Components/ui/card'

// Translation helper
const _t = (key, fallback = '') => {
    const { props } = usePage();
    if (props.translations && props.translations.esim && props.translations.esim[key]) {
        return props.translations.esim[key];
    }
    return fallback || key;
}

// Redirect to partner portal
const redirectToPartnerPortal = () => {
    window.open('https://vrooem.esimqr.link/', '_blank')
}
</script>

<template>
    <section class="home-section home-section--light relative esim-section">
        <div class="full-w-container relative z-10">
            <div class="esim-grid">
                <div class="esim-content">
                    <span class="text-[1.1rem] text-customPrimaryColor tracking-[0.2em] uppercase">{{ _t('tagline') }}</span>
                    <h2 class="esim-title">
                        {{ _t('title') }}
                    </h2>
                    <p class="esim-subtitle">
                        {{ _t('subtitle') }}
                    </p>

                    <div class="esim-cta-row">
                        <Button @click="redirectToPartnerPortal" size="lg"
                            class="esim-cta-button">
                            {{ _t('get_esim') }}
                        </Button>
                        <div class="esim-support">
                            <span class="esim-support-label">Support</span>
                            <span class="esim-support-text">{{ _t('support_info') }}</span>
                        </div>
                    </div>
                </div>

                <div class="esim-visual">
                    <div class="esim-card-visual">
                        <div class="esim-chip">
                            <span class="chip-line"></span>
                            <span class="chip-line"></span>
                            <span class="chip-line"></span>
                        </div>
                        <div class="esim-signal">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="esim-mark">eSIM</div>
                        <div class="esim-brand">VROOEM</div>
                    </div>

                    <div class="esim-badge badge-top">
                        <span class="badge-icon">∞</span>
                        <div>
                            <p>{{ _t('unlimited_data') }}</p>
                            <span>Always-on connectivity</span>
                        </div>
                    </div>
                    <div class="esim-badge badge-right">
                        <span class="badge-icon">🌍</span>
                        <div>
                            <p>{{ _t('destinations') }}</p>
                            <span>150+ countries</span>
                        </div>
                    </div>
                    <div class="esim-badge badge-bottom">
                        <span class="badge-icon">⚡</span>
                        <div>
                            <p>{{ _t('instant_setup') }}</p>
                            <span>Activate in minutes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.esim-section::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 14% 18%, rgba(46, 167, 173, 0.12), transparent 55%),
        radial-gradient(circle at 86% 6%, rgba(15, 23, 42, 0.08), transparent 60%);
    opacity: 0.7;
    pointer-events: none;
}

.esim-grid {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 3rem;
}

.esim-content {
    flex: 1 1 55%;
    display: flex;
    flex-direction: column;
    gap: 1.6rem;
}

.esim-title {
    font-size: clamp(2.3rem, 4.4vw, 3.6rem);
    line-height: 1.05;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}

.esim-subtitle {
    font-size: 1.1rem;
    color: #64748b;
    max-width: 32rem;
    line-height: 1.7;
    margin: 0;
}

.esim-cta-row {
    display: flex;
    align-items: center;
    gap: 1.8rem;
    flex-wrap: wrap;
}

.esim-cta-button {
    background: linear-gradient(135deg, #153b4f, #2ea7ad);
    color: #ffffff;
    font-weight: 600;
    padding: 1rem 2.5rem;
    border-radius: 999px;
    box-shadow: 0 16px 30px rgba(21, 59, 79, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.esim-cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 22px 40px rgba(21, 59, 79, 0.28);
}

.esim-support {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    color: #64748b;
    font-size: 0.95rem;
}

.esim-support-label {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.75rem;
    color: #94a3b8;
}

.esim-visual {
    flex: 1 1 45%;
    position: relative;
    min-height: 360px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.esim-card-visual {
    width: 320px;
    height: 210px;
    border-radius: 28px;
    background: linear-gradient(140deg, #ffffff 0%, #edf2f7 60%, #dbe7f0 100%);
    border: 1px solid rgba(148, 163, 184, 0.45);
    box-shadow: 0 26px 50px rgba(15, 23, 42, 0.18);
    position: relative;
    overflow: hidden;
    animation: floatCard 6s ease-in-out infinite;
}

.esim-card-visual::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 18% 12%, rgba(46, 167, 173, 0.28), transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(21, 59, 79, 0.18), transparent 55%);
    opacity: 0.8;
}

.esim-chip {
    position: absolute;
    top: 32px;
    left: 28px;
    width: 72px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f8d48a, #d9b56f);
    border: 1px solid rgba(120, 81, 34, 0.2);
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
    animation: chipGlow 4s ease-in-out infinite;
}

.chip-line {
    height: 3px;
    border-radius: 999px;
    background: rgba(120, 81, 34, 0.4);
}

.chip-line:nth-child(2) {
    width: 80%;
}

.chip-line:nth-child(3) {
    width: 65%;
}

.esim-signal {
    position: absolute;
    top: 28px;
    right: 28px;
    display: flex;
    align-items: flex-end;
    gap: 4px;
}

.esim-signal span {
    width: 8px;
    border-radius: 4px 4px 0 0;
    background: rgba(21, 59, 79, 0.4);
}

.esim-signal span:nth-child(1) {
    height: 10px;
}

.esim-signal span:nth-child(2) {
    height: 16px;
}

.esim-signal span:nth-child(3) {
    height: 22px;
}

.esim-signal span:nth-child(4) {
    height: 28px;
    background: rgba(46, 167, 173, 0.7);
}

.esim-mark {
    position: absolute;
    bottom: 40px;
    left: 28px;
    font-size: 1.5rem;
    font-weight: 700;
    color: #153b4f;
    letter-spacing: 0.08em;
}

.esim-brand {
    position: absolute;
    bottom: 22px;
    left: 28px;
    font-size: 0.75rem;
    letter-spacing: 0.3em;
    color: rgba(21, 59, 79, 0.6);
}

.esim-badge {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 16px;
    padding: 0.75rem 1rem;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
    font-size: 0.9rem;
    color: #0f172a;
    animation: floatBadge 5.5s ease-in-out infinite;
}

.esim-badge p {
    margin: 0;
    font-weight: 600;
}

.esim-badge span {
    font-size: 0.75rem;
    color: #64748b;
    display: block;
}

.badge-icon {
    font-size: 1.35rem;
    color: #2ea7ad;
}

.badge-top {
    top: 12%;
    left: 6%;
}

.badge-right {
    top: 48%;
    right: -8%;
    animation-delay: 0.8s;
}

.badge-bottom {
    bottom: 6%;
    left: 18%;
    animation-delay: 1.4s;
}

@keyframes floatCard {
    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }
}

@keyframes floatBadge {
    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(8px);
    }
}

@keyframes chipGlow {
    0%,
    100% {
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4), 0 0 0 rgba(46, 167, 173, 0.2);
    }

    50% {
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4), 0 0 18px rgba(46, 167, 173, 0.35);
    }
}

@media (max-width: 1024px) {
    .esim-grid {
        flex-direction: column;
        text-align: center;
    }

    .esim-content {
        align-items: center;
    }

    .esim-subtitle {
        max-width: 100%;
    }

    .esim-cta-row {
        justify-content: center;
    }

    .esim-visual {
        width: 100%;
        margin-top: 2.5rem;
    }

    .badge-right {
        right: 4%;
    }
}

@media (max-width: 640px) {
    .esim-card-visual {
        width: 260px;
        height: 175px;
    }

    .esim-badge {
        position: static;
        margin-top: 0.8rem;
        width: 100%;
        justify-content: center;
    }

    .esim-visual {
        flex-direction: column;
    }
}
</style>
