<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    page: Object,
    meta: Object,
    sections: Array,
    seo: Object,
    locale: String,
    pages: Object,
});

// --- Section helper ---
const getSection = (type) => props.sections?.find(s => s.type === type);

const heroSection = computed(() => getSection('hero'));
const contentSection = computed(() => getSection('content'));

// --- Contact form (copied from ContactUs.vue) ---
const form = useForm({
    name: '',
    email: '',
    message: '',
    admin_email: import.meta.env.VITE_ADMIN_EMAIL,
});

const inertiaPage = usePage();
const flashSuccess = computed(() => inertiaPage.props.flash?.success || null);

const submitForm = () => {
    form.post(route('contact.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

watch(() => form.processing, (newVal) => {
    if (newVal) {
        document.body.classList.add('loading');
    } else {
        document.body.classList.remove('loading');
    }
});

// --- Contact points ---
const contactPoints = computed(() => {
    if (!props.meta?.contact_points) return [];
    if (typeof props.meta.contact_points === 'string') {
        try { return JSON.parse(props.meta.contact_points); } catch { return []; }
    }
    return props.meta.contact_points;
});

// --- Scroll-reveal ---
let observer = null;

onMounted(() => {
    const observerOptions = {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px',
    };

    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// --- SVG Icons ---
const PhoneIcon = `<svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>`;
const EmailIcon = `<svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>`;
const LocationIcon = `<svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>`;
</script>

<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <div class="contact-page text-gray-800">
        <!-- Hero Section -->
        <section class="hero-section relative overflow-hidden text-white">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1d28] via-customPrimaryColor to-[#1a4d66]"></div>

            <!-- Hero background image from meta -->
            <div v-if="meta?.hero_image_url"
                 class="absolute inset-0 bg-cover bg-center opacity-[0.12] transition-opacity duration-600"
                 :style="{ backgroundImage: `url('${meta.hero_image_url}')` }">
            </div>

            <!-- Decorative glows -->
            <div class="absolute -top-1/2 -right-[20%] w-[700px] h-[700px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.12)_0%,transparent_70%)] animate-glow"></div>
            <div class="absolute -bottom-[30%] -left-[10%] w-[500px] h-[500px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.08)_0%,transparent_70%)] animate-glow-delayed"></div>

            <!-- Grain texture -->
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-repeat"
                 style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E&quot;)">
            </div>

            <div class="container mx-auto px-6 py-24 md:py-32 relative z-10 text-center max-w-[720px]">
                <h1 class="text-4xl md:text-5xl lg:text-[56px] font-extrabold mb-5 leading-[1.12] tracking-[-1.5px] animate-hero-fade">
                    {{ page.title }}
                </h1>
                <p v-if="heroSection?.content"
                   class="text-base md:text-lg opacity-0 leading-relaxed font-light animate-hero-fade-delay"
                   v-html="heroSection.content">
                </p>
            </div>

            <!-- Wave -->
            <div class="absolute bottom-[-1px] left-0 w-full leading-[0]">
                <svg viewBox="0 0 1440 48" fill="none" preserveAspectRatio="none" class="w-full h-12">
                    <path d="M0 48h1440V20c-120 15-360 28-720 28S120 35 0 20v28z" fill="white"/>
                </svg>
            </div>
        </section>

        <!-- Main Content Section -->
        <section class="py-16 md:py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="grid md:grid-cols-2 gap-14 items-start">

                    <!-- Left Column: Intro + Contact Details -->
                    <div class="space-y-10">
                        <!-- Intro text from content section -->
                        <div v-if="contentSection?.content" class="reveal">
                            <h2 class="text-2xl md:text-[26px] font-bold text-customPrimaryColor mb-4 relative inline-block after:content-[''] after:absolute after:bottom-[-6px] after:left-0 after:w-10 after:h-[3px] after:bg-cyan-500 after:rounded">
                                {{ contentSection.title || _t('contactus', 'about_vrooem') }}
                            </h2>
                            <div class="text-slate-500 text-[15px] leading-[1.85] mt-4" v-html="contentSection.content"></div>
                        </div>

                        <!-- Contact Details -->
                        <div class="reveal">
                            <h2 class="text-2xl md:text-[26px] font-bold text-customPrimaryColor mb-5 relative inline-block after:content-[''] after:absolute after:bottom-[-6px] after:left-0 after:w-10 after:h-[3px] after:bg-cyan-500 after:rounded">
                                {{ _t('contactus', 'get_in_touch') }}
                            </h2>
                            <div class="space-y-4 mt-5">
                                <!-- Phone -->
                                <div v-if="meta?.phone_number" class="contact-item flex items-center gap-4 p-3.5 px-4 rounded-xl transition-all duration-250 hover:bg-customLightPrimaryColor hover:translate-x-1">
                                    <div class="contact-icon flex-shrink-0 w-12 h-12 bg-customLightPrimaryColor rounded-xl flex items-center justify-center transition-all duration-300">
                                        <span v-html="PhoneIcon" class="text-customPrimaryColor"></span>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">Phone</div>
                                        <a :href="`tel:${meta.phone_number}`" class="text-[15px] font-medium text-slate-800 hover:text-cyan-500 transition-colors">{{ meta.phone_number }}</a>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div v-if="meta?.email" class="contact-item flex items-center gap-4 p-3.5 px-4 rounded-xl transition-all duration-250 hover:bg-customLightPrimaryColor hover:translate-x-1">
                                    <div class="contact-icon flex-shrink-0 w-12 h-12 bg-customLightPrimaryColor rounded-xl flex items-center justify-center transition-all duration-300">
                                        <span v-html="EmailIcon" class="text-customPrimaryColor"></span>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">Email</div>
                                        <a :href="`mailto:${meta.email}`" class="text-[15px] font-medium text-slate-800 hover:text-cyan-500 transition-colors">{{ meta.email }}</a>
                                    </div>
                                </div>

                                <!-- Address -->
                                <div v-if="meta?.address" class="contact-item flex items-center gap-4 p-3.5 px-4 rounded-xl transition-all duration-250 hover:bg-customLightPrimaryColor hover:translate-x-1">
                                    <div class="contact-icon flex-shrink-0 w-12 h-12 bg-customLightPrimaryColor rounded-xl flex items-center justify-center transition-all duration-300">
                                        <span v-html="LocationIcon" class="text-customPrimaryColor"></span>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">Address</div>
                                        <span class="text-[15px] font-medium text-slate-800">{{ meta.address }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Contact Form -->
                    <div class="form-card bg-slate-50 border border-slate-200 rounded-[20px] p-10 shadow-md relative overflow-hidden reveal">
                        <!-- Top accent bar -->
                        <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-customPrimaryColor to-cyan-500"></div>

                        <h2 class="text-2xl font-bold text-customPrimaryColor text-center mb-1.5">
                            {{ _t('contactus', 'send_message') }}
                        </h2>
                        <p class="text-center text-slate-400 text-sm mb-6">&nbsp;</p>

                        <!-- Success flash -->
                        <div v-if="flashSuccess" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                            <p>{{ flashSuccess }}</p>
                        </div>

                        <!-- Error messages -->
                        <div v-if="Object.keys(form.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                            <ul class="list-disc list-inside">
                                <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-5">
                            <div>
                                <label class="block text-[13px] font-semibold text-slate-800 mb-1.5">{{ _t('contactus', 'name') }}</label>
                                <input type="text"
                                       v-model="form.name"
                                       class="w-full px-4 py-3 bg-white border-[1.5px] border-slate-200 rounded-[10px] text-sm text-slate-800 outline-none transition-all duration-250 focus:border-cyan-500 focus:shadow-[0_0_0_3px_rgba(6,182,212,0.12)] focus:-translate-y-px"
                                       :placeholder="_t('contactus', 'your_name')"
                                       required />
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-slate-800 mb-1.5">{{ _t('contactus', 'email') }}</label>
                                <input type="email"
                                       v-model="form.email"
                                       class="w-full px-4 py-3 bg-white border-[1.5px] border-slate-200 rounded-[10px] text-sm text-slate-800 outline-none transition-all duration-250 focus:border-cyan-500 focus:shadow-[0_0_0_3px_rgba(6,182,212,0.12)] focus:-translate-y-px"
                                       :placeholder="_t('contactus', 'your_email')"
                                       required />
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-slate-800 mb-1.5">{{ _t('contactus', 'message') }}</label>
                                <textarea v-model="form.message"
                                          class="w-full px-4 py-3 bg-white border-[1.5px] border-slate-200 rounded-[10px] text-sm text-slate-800 outline-none transition-all duration-250 focus:border-cyan-500 focus:shadow-[0_0_0_3px_rgba(6,182,212,0.12)] focus:-translate-y-px min-h-[120px] resize-y"
                                          rows="4"
                                          :placeholder="_t('contactus', 'your_message')"
                                          required></textarea>
                            </div>
                            <div class="pt-2">
                                <button type="submit"
                                        :disabled="form.processing"
                                        class="submit-btn w-full py-3.5 bg-customPrimaryColor text-white font-semibold text-[15px] rounded-[10px] transition-all duration-250 hover:bg-[#0f2d3d] hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(21,59,79,0.3)] disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden">
                                    {{ form.processing ? _t('contactus', 'sending_message_button') : _t('contactus', 'send_message_button') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section v-if="meta?.map_link" class="pb-16 md:pb-20 bg-white">
            <div class="container mx-auto px-6 reveal">
                <div class="rounded-[20px] overflow-hidden shadow-lg h-[380px] md:h-[380px] max-md:h-[280px] max-sm:h-[220px]">
                    <iframe :src="meta.map_link"
                            class="w-full h-full border-0"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </section>

        <!-- Contact Points Section -->
        <section v-if="contactPoints.length" class="py-20 md:py-[88px] bg-slate-50 relative">
            <!-- Top accent line -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[120px] h-[3px] bg-gradient-to-r from-cyan-500 to-customPrimaryColor rounded"></div>

            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-extrabold text-customPrimaryColor text-center mb-12 tracking-tight reveal">
                    {{ _t('contactus', 'why_choose_vrooem') }}
                </h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-7">
                    <div v-for="(point, index) in contactPoints"
                         :key="index"
                         class="point-card bg-white rounded-[20px] p-10 pb-9 text-center shadow-sm border border-slate-200 transition-all duration-400 hover:-translate-y-2 hover:shadow-xl relative overflow-hidden reveal"
                         :class="{ 'reveal-delay-1': index % 3 === 0, 'reveal-delay-2': index % 3 === 1, 'reveal-delay-3': index % 3 === 2 }">
                        <!-- Top bar on hover -->
                        <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-customPrimaryColor to-cyan-500 scale-x-0 origin-left transition-transform duration-400 point-card-bar"></div>

                        <div class="point-icon w-[88px] h-[88px] bg-gradient-to-br from-customPrimaryColor to-[#1a5570] rounded-3xl mx-auto mb-6 flex items-center justify-center transition-all duration-400">
                            <img v-if="point.icon && (point.icon.startsWith('http') || point.icon.startsWith('/'))"
                                 :src="point.icon"
                                 :alt="point.title"
                                 class="h-16 w-16 rounded-2xl object-cover" />
                            <div v-else-if="point.icon"
                                 v-html="point.icon"
                                 class="w-[38px] h-[38px] text-white [&>svg]:w-full [&>svg]:h-full [&>svg]:stroke-white [&>svg]:fill-none">
                            </div>
                            <span v-else class="text-3xl text-white">&#9733;</span>
                        </div>
                        <h3 class="text-lg font-bold text-customPrimaryColor mb-2">{{ point.title }}</h3>
                        <p v-if="point.description" class="text-sm text-slate-500 leading-relaxed">{{ point.description }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <Footer />

    <!-- Fullscreen Loader Overlay -->
    <div v-if="form.processing" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white bg-opacity-80">
        <div class="flex flex-col items-center space-y-4 p-8 bg-white rounded-lg shadow-2xl">
            <svg class="animate-spin h-12 w-12 text-customPrimaryColor" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V1a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V4a1 1 0 00-1-1H4z" />
            </svg>
            <p class="text-lg text-customPrimaryColor font-semibold">{{ _t('contactus', 'sending_message_button') }}</p>
        </div>
    </div>
</template>

<style scoped>
.hero-section {
    min-height: 50vh;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

/* Hero animations */
@keyframes heroFadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 0.88; transform: translateY(0); }
}

.animate-hero-fade {
    opacity: 0;
    animation: heroFadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-hero-fade-delay {
    animation: heroFadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.15s forwards;
}

/* Glow animation */
@keyframes pulseGlow {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.7; }
}

.animate-glow {
    animation: pulseGlow 6s ease-in-out infinite;
}

.animate-glow-delayed {
    animation: pulseGlow 8s ease-in-out infinite 2s;
}

/* Scroll reveal */
.reveal {
    opacity: 0;
    transform: translateY(32px);
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.reveal.visible {
    opacity: 1;
    transform: translateY(0);
}

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }

/* Contact item icon hover */
.contact-item:hover .contact-icon {
    background-color: #153b4f;
    transform: scale(1.08);
    box-shadow: 0 4px 16px rgba(21, 59, 79, 0.3);
}

.contact-item:hover .contact-icon :deep(svg) {
    stroke: white;
}

/* Point card hover effects */
.point-card:hover .point-card-bar {
    transform: scaleX(1);
}

.point-card:hover .point-icon {
    transform: scale(1.08) rotate(4deg);
    border-radius: 20px;
}

/* Submit button shimmer */
.submit-btn::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    transform: translateX(-100%);
    transition: transform 0.5s;
}

.submit-btn:hover::after {
    transform: translateX(100%);
}

/* Loading state */
body.loading {
    overflow: hidden;
}

/* Focus accessibility */
input:focus-visible,
textarea:focus-visible,
button:focus-visible {
    outline: 2px solid #153b4f;
    outline-offset: 2px;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section {
        min-height: auto;
    }

    .form-card {
        padding: 28px 24px;
    }
}

@media (max-width: 480px) {
    .form-card {
        padding: 24px 18px;
    }
}
</style>
