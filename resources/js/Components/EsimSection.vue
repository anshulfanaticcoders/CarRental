<script setup>
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { useScrollAnimation } from '@/composables/useScrollAnimation';

const _t = (key, fallback = '') => {
    const { props } = usePage();
    return props.translations?.esim?.[key] || fallback || key;
};

const redirectToPartnerPortal = () => {
    window.open('https://vrooem.esimqr.link/', '_blank');
};

useScrollAnimation('.esim-section', '.esim-left, .esim-right', {
    y: 50,
    duration: 0.95,
    stagger: 0.18,
});
</script>

<template>
    <section class="esim-section">
        <div class="full-w-container">
            <div class="esim-banner">
                <div class="esim-left sr-reveal">
                    <span class="esim-label">{{ _t('tagline', 'Stay Connected') }}</span>
                    <h2 class="esim-title">{{ _t('title', 'Travel eSIM included free.') }}</h2>
                    <p class="esim-text">{{ _t('subtitle', 'No SIM swaps, no roaming fees. Activate in seconds and stay connected from arrival to drop-off.') }}</p>
                    <div class="esim-features">
                        <div class="esim-feat"><span class="esim-dot"></span>200+ Countries</div>
                        <div class="esim-feat"><span class="esim-dot"></span>Instant Activation</div>
                        <div class="esim-feat"><span class="esim-dot"></span>Unlimited Data Plans</div>
                        <div class="esim-feat"><span class="esim-dot"></span>Works on Any eSIM Phone</div>
                    </div>
                    <div class="esim-cta">
                        <Button @click="redirectToPartnerPortal" size="lg" class="esim-btn">
                            {{ _t('get_esim', 'Get Your eSIM') }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </Button>
                        <div class="esim-support">
                            <span class="esim-sup-label">24/7 Support</span>
                            <span class="esim-sup-val">{{ _t('support_info', 'Worldwide Coverage') }}</span>
                        </div>
                    </div>
                </div>
                <div class="esim-right sr-reveal">
                    <div class="esim-ghost"></div>
                    <div class="esim-card">
                        <div class="esim-signal"><span></span><span></span><span></span><span></span></div>
                        <div class="esim-chip"><span></span><span></span><span></span></div>
                        <div class="esim-mark">eSIM</div>
                        <div class="esim-brand">VROOEM</div>
                    </div>
                    <div class="esim-dots"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

.esim-section {
    padding: clamp(3rem, 6vw, 5rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 45%, #f8fafc 100%);
}
.esim-banner {
    display: grid; grid-template-columns: 1fr 1fr;
    background: linear-gradient(135deg, #0a1d28, #153b4f);
    border-radius: 28px; overflow: hidden; position: relative; min-height: 380px;
}
.esim-banner::before {
    content: ""; position: absolute; top: -30%; right: -10%;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(34,211,238,0.1), transparent 65%);
    pointer-events: none;
}
.esim-left {
    padding: clamp(2rem, 4vw, 3.5rem); display: flex; flex-direction: column;
    justify-content: center; position: relative; z-index: 1;
}
.esim-label {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em;
    text-transform: uppercase; color: #22d3ee; margin-bottom: 0.75rem;
}
.esim-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #22d3ee; }
.esim-title { font-size: clamp(1.75rem, 3vw, 2.5rem); font-weight: 800; color: #fff; line-height: 1.1; letter-spacing: -0.02em; margin-bottom: 0.75rem; }
.esim-text { color: rgba(255,255,255,0.55); font-size: 0.92rem; line-height: 1.6; margin-bottom: 1.5rem; max-width: 360px; }
.esim-features { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.75rem; }
.esim-feat { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; font-weight: 500; color: rgba(255,255,255,0.75); }
.esim-dot { width: 6px; height: 6px; border-radius: 50%; background: #22d3ee; flex-shrink: 0; }
.esim-cta { display: flex; align-items: center; gap: 1.25rem; }
.esim-btn { background: #06b6d4 !important; color: #0a1d28 !important; font-weight: 700 !important; border-radius: 14px !important; display: inline-flex !important; align-items: center !important; gap: 0.5rem !important; }
.esim-btn:hover { background: #22d3ee !important; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(6,182,212,0.3); }
.esim-support { display: flex; flex-direction: column; }
.esim-sup-label { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.35); }
.esim-sup-val { font-size: 0.85rem; font-weight: 600; color: rgba(255,255,255,0.8); }

.esim-right { display: flex; align-items: center; justify-content: center; position: relative; padding: 3rem 2rem; overflow: hidden; }
.esim-right::before { content: ""; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 280px; height: 280px; background: radial-gradient(circle, rgba(34,211,238,0.12), transparent 65%); pointer-events: none; }

.esim-card {
    width: 300px; height: 185px; border-radius: 20px;
    background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.03));
    border: 1px solid rgba(255,255,255,0.12); padding: 1.75rem;
    position: relative; z-index: 2; overflow: hidden;
    box-shadow: 0 24px 48px rgba(0,0,0,0.3);
    transform: perspective(600px) rotateY(-6deg) rotateX(2deg);
    transition: transform 0.5s cubic-bezier(0.22,1,0.36,1);
    backdrop-filter: blur(8px);
}
.esim-card:hover { transform: perspective(600px) rotateY(0) rotateX(0); }
.esim-card::before { content: ""; position: absolute; top: -40%; right: -25%; width: 250px; height: 250px; background: radial-gradient(circle, rgba(34,211,238,0.12), transparent 65%); }

.esim-chip { display: flex; gap: 3px; margin-bottom: 2rem; }
.esim-chip span { width: 20px; height: 14px; border-radius: 3px; background: rgba(212,162,86,0.45); border: 1px solid rgba(212,162,86,0.25); }
.esim-mark {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.45);
    position: relative;
    z-index: 2;
}

.esim-brand {
    font-size: 1.35rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    margin-top: 0.15rem;
    position: relative;
    z-index: 2;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
}
.esim-signal { position: absolute; top: 1.75rem; right: 1.75rem; display: flex; align-items: flex-end; gap: 3px; }
.esim-signal span { display: block; width: 4px; border-radius: 2px; background: #22d3ee; opacity: 0.6; }
.esim-signal span:nth-child(1){height:5px} .esim-signal span:nth-child(2){height:9px} .esim-signal span:nth-child(3){height:13px} .esim-signal span:nth-child(4){height:17px;opacity:1}

.esim-ghost { position: absolute; width: 280px; height: 170px; border-radius: 20px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); top: 50%; left: 50%; transform: translate(-40%,-55%) rotate(6deg); z-index: 1; }
.esim-dots { position: absolute; bottom: 1.5rem; left: 50%; transform: translateX(-50%); display: flex; gap: 4px; z-index: 3; }
.esim-dots span { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.15); }
.esim-dots span:nth-child(1),.esim-dots span:nth-child(4),.esim-dots span:nth-child(7) { background: #22d3ee; opacity: 0.6; }

@media (max-width: 768px) {
    .esim-banner { grid-template-columns: 1fr; }
    .esim-right { min-height: 220px; }
    .esim-card { width: 260px; height: 160px; transform: none; }
    .esim-ghost { width: 240px; height: 150px; }
    .esim-features { grid-template-columns: 1fr; }
    .esim-cta { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 480px) {
    .esim-card { width: 100%; max-width: 240px; height: 150px; padding: 1.25rem; }
    .esim-ghost { display: none; }
}
</style>
