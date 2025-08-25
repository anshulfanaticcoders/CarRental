<template>
    <Head>
        <meta name="robots" content="index, follow" />
        <title>{{ seoTitle }}</title>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">{{ page.title }}</h1>
                <p class="hero-subtitle">{{ _t('aboutus', 'hero_subtitle') }}</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content py-[5rem]">
        <div class="container">
            <!-- Mission Section -->
            <section class="section mb-[3rem]">
               
                    <div class="mission-content fade-in-up">
                        <div v-html="page.content"></div>
                    </div>
                
            </section>

            <!-- Excellence Section -->
            <section class="section mb-[3rem]">
                <div class="section-header">
                <h2 class="section-title">{{ _t('aboutus', 'commitment_to_excellence_title') }}</h2>
                <p class="section-description">
                    {{ _t('aboutus', 'commitment_to_excellence_description') }}
                </p>
                </div>
            </section>

            <!-- Why Choose Us Features -->
            <section class="section mb-[5rem]">
                <div class="section-header">
                <h2 class="section-title">{{ _t('aboutus', 'why_choose_vrooem_title') }}</h2>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card fade-in-up">
                        <span class="feature-icon">🚙</span>
                        <h3 class="feature-title">{{ _t('aboutus', 'diverse_fleet_title') }}</h3>
                        <p class="feature-description">{{ _t('aboutus', 'diverse_fleet_description') }}</p>
                    </div>

                    <div class="feature-card fade-in-up">
                        <span class="feature-icon">💰</span>
                        <h3 class="feature-title">{{ _t('aboutus', 'transparent_pricing_title') }}</h3>
                        <p class="feature-description">{{ _t('aboutus', 'transparent_pricing_description') }}</p>
                    </div>

                    <div class="feature-card fade-in-up">
                        <span class="feature-icon">⏰</span>
                        <h3 class="feature-title">{{ _t('aboutus', 'flexible_rental_options_title') }}</h3>
                        <p class="feature-description">{{ _t('aboutus', 'flexible_rental_options_description') }}</p>
                    </div>

                    <div class="feature-card fade-in-up">
                        <span class="feature-icon">📍</span>
                        <h3 class="feature-title">{{ _t('aboutus', 'convenient_locations_title') }}</h3>
                        <p class="feature-description">{{ _t('aboutus', 'convenient_locations_description') }}</p>
                    </div>

                    <div class="feature-card fade-in-up">
                        <span class="feature-icon">🔧</span>
                        <h3 class="feature-title">{{ _t('aboutus', 'well_maintained_vehicles_title') }}</h3>
                        <p class="feature-description">{{ _t('aboutus', 'well_maintained_vehicles_description') }}</p>
                    </div>

                    <div class="feature-card fade-in-up">
                        <span class="feature-icon">🤝</span>
                        <h3 class="feature-title">{{ _t('aboutus', 'excellent_customer_support_title') }}</h3>
                        <p class="feature-description">{{ _t('aboutus', 'excellent_customer_support_description') }}</p>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="stats-section rounded-[20px]">
                <div class="container">
                    <h2 style="font-size: 2.5em;">{{ _t('aboutus', 'trusted_by_thousands_title') }}</h2>
                    <p style="font-size: 1.2em; opacity: 0.9; margin-bottom: 40px;">{{ _t('aboutus', 'trusted_by_thousands_description') }}</p>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number">{{ _t('aboutus', 'happy_customers_number') }}</span>
                            <span class="stat-label">{{ _t('aboutus', 'happy_customers_label') }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ _t('aboutus', 'vehicles_available_number') }}</span>
                            <span class="stat-label">{{ _t('aboutus', 'vehicles_available_label') }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ _t('aboutus', 'customer_satisfaction_number') }}</span>
                            <span class="stat-label">{{ _t('aboutus', 'customer_satisfaction_label') }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ _t('aboutus', 'customer_support_number') }}</span>
                            <span class="stat-label">{{ _t('aboutus', 'customer_support_label') }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Hassle-Free Experience -->
            <section class="section mt-[4rem]">
                <div class="section-header">
                <h2 class="section-title">{{ _t('aboutus', 'hassle_free_experience_title') }}</h2>
                </div>
                
                <div class="mission-grid">
                    <div class="mission-content fade-in-up">
                        <h3 style="font-size: 1.8em; color: #2c3e50; margin-bottom: 20px;">{{ _t('aboutus', 'simple_online_booking_title') }}</h3>
                        <p style="font-size: 1.1em; line-height: 1.8; margin-bottom: 20px;">
                            {{ _t('aboutus', 'simple_online_booking_description_1') }}
                        </p>
                        <p style="font-size: 1.1em; line-height: 1.8; color: #666;">
                            {{ _t('aboutus', 'simple_online_booking_description_2') }}
                        </p>
                    </div>
                    <div class="mission-image">
                        <img :src="onlineBooking" alt="">
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">{{ _t('aboutus', 'drive_with_confidence_title') }}</h2>
                <p class="cta-description">{{ _t('aboutus', 'drive_with_confidence_description') }}</p>
                <a href="/" class="cta-button">{{ _t('aboutus', 'book_your_ride_today_button') }}</a>
            </div>
        </div>
    </section>

    <Footer/>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import onlineBooking from '../../../assets/onlineBooking.svg';

const props = defineProps({
    page: Object,
    seoMeta: Object,
    locale: String,
});

const page_ = usePage();
const currentLocale = computed(() => props.locale || 'en');
const currentUrl = computed(() => window.location.href);

const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === currentLocale.value) || {};
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || props.page.title;
});

const seoDescription = computed(() => {
    return seoTranslation.value.meta_description || props.seoMeta?.meta_description || '';
});

const seoKeywords = computed(() => {
    return seoTranslation.value.keywords || props.seoMeta?.keywords || '';
});

const canonicalUrl = computed(() => {
    return props.seoMeta?.canonical_url || window.location.href;
});

const seoImageUrl = computed(() => {
    return props.seoMeta?.seo_image_url || '';
});

onMounted(() => {
    // Add scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);

    // Observe all feature cards
    document.querySelectorAll('.feature-card, .mission-content').forEach(card => {
        observer.observe(card);
    });

    // Counter animation for stats
    const animateCounters = () => {
        const counters = document.querySelectorAll('.stat-number');
        counters.forEach(counter => {
            const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                if (counter.textContent.includes('+')) {
                    counter.textContent = Math.floor(current).toLocaleString() + '+';
                } else if (counter.textContent.includes('%')) {
                    counter.textContent = Math.floor(current * 10) / 10 + '%';
                } else if (counter.textContent.includes('/')) {
                    counter.textContent = '24/7';
                }
            }, 16);
        });
    };

    // Trigger counter animation when stats section is visible
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        statsObserver.observe(statsSection);
    }
});
</script>

<style scoped>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 100px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 300"><path d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,208C1248,224,1344,192,1392,176L1440,160L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z" fill="rgba(255,255,255,0.1)"/></svg>') no-repeat center center;
    background-size: cover;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.5em;
    font-weight: bold;
    margin-bottom: 20px;
    animation: fadeInUp 1s ease;
}

.hero-subtitle {
    font-size: 1.3em;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
    animation: fadeInUp 1s ease 0.3s both;
}

.section-header {
    text-align: center;
    margin-bottom: 60px;
}

.section-title {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 20px;
    position: relative;
}

.section-title::after {
    content: '';
    width: 60px;
    height: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
}

.section-description {
    font-size: 1.2em;
    color: #666;
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

/* Mission Section */
.mission-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.mission-content {
    padding: 40px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    transform: translateY(0);
    transition: transform 0.3s ease;
}

.mission-content:hover {
    transform: translateY(-10px);
}

.mission-image {
    text-align: center;
    font-size: 200px;
    color: #667eea;
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 40px;
    margin-top: 60px;
}

.feature-card {
    background: white;
    padding: 40px 30px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.feature-card:hover::before {
    left: 100%;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.15);
}

.feature-icon {
    font-size: 60px;
    margin-bottom: 20px;
    display: block;
}

.feature-title {
    font-size: 1.5em;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: bold;
}

.feature-description {
    color: #666;
    line-height: 1.6;
}

/* Stats Section */
.stats-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
    margin-top: 40px;
}

.stat-item {
    padding: 20px;
}

.stat-number {
    font-size: 3em;
    font-weight: bold;
    margin-bottom: 10px;
    display: block;
}

.stat-label {
    font-size: 1.1em;
    opacity: 0.9;
}

/* CTA Section */
.cta-section {
    background: #2c3e50;
    color: white;
    padding: 80px 0;
    text-align: center;
}

.cta-content {
    max-width: 600px;
    margin: 0 auto;
}

.cta-title {
    font-size: 2.5em;
    margin-bottom: 20px;
}

.cta-description {
    font-size: 1.2em;
    margin-bottom: 40px;
    opacity: 0.9;
}

.cta-button {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 50px;
    font-size: 1.2em;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }

    .hero-section {
        padding: 80px 0;
    }

    .hero-title {
        font-size: 2em; /* Reduced from 2.5em */
    }

    .hero-subtitle {
        font-size: 1em; /* Reduced from 1.1em */
    }

    .main-content {
        padding: 50px 0; /* Reduced from 60px */
    }

    .section {
        margin-bottom: 50px; /* Reduced from 60px */
    }

    .section-header {
        margin-bottom: 30px; /* Reduced from 40px */
    }

    .section-title {
        font-size: 1.8em; /* Reduced from 2em */
    }

    .section-description {
        font-size: 0.9em; /* Reduced from 1em */
    }

    .mission-grid {
        grid-template-columns: 1fr;
        gap: 20px; /* Reduced from 30px */
    }

    .mission-content {
        padding: 25px; /* Reduced from 30px */
    }

    .mission-content h2 {
        font-size: 1.6em !important; /* Reduced from 1.8em */
        margin-bottom: 10px !important; /* Reduced from 15px */
    }

    .mission-content h3 {
        font-size: 1.3em !important; /* Reduced from 1.5em */
        margin-bottom: 10px !important; /* Reduced from 15px */
    }
    .mission-content h4 {
        font-size: 1.3em !important; /* Reduced from 1.5em */
        margin-bottom: 10px !important; /* Reduced from 15px */
    }

    .mission-content p {
        font-size: 0.9em !important; /* Reduced from 1em */
        line-height: 1.5 !important; /* Adjusted line-height */
    }

    .mission-image {
        font-size: 120px; /* Reduced from 150px */
    }

    .mission-image img {
        max-width: 90%; /* Adjusted max-width */
        height: auto;
    }

    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px; /* Reduced from 30px */
        margin-top: 30px; /* Reduced from 40px */
    }

    .feature-card {
        padding: 25px 15px; /* Reduced from 30px 20px */
    }

    .feature-icon {
        font-size: 40px; /* Reduced from 50px */
        margin-bottom: 10px; /* Reduced from 15px */
    }

    .feature-title {
        font-size: 1.2em; /* Reduced from 1.3em */
    }

    .feature-description {
        font-size: 0.9em; /* Added for consistency */
    }

    .stats-section {
        padding: 50px 0; /* Reduced from 60px */
    }

    .stats-section h2 {
        font-size: 1.8em !important; /* Reduced from 2em */
        margin-bottom: 10px !important; /* Reduced from 15px */
    }

    .stats-section p {
        font-size: 0.9em !important; /* Reduced from 1em */
        margin-bottom: 20px !important; /* Reduced from 30px */
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px; /* Reduced from 20px */
        margin-top: 20px; /* Reduced from 30px */
    }

    .stat-number {
        font-size: 2em; /* Reduced from 2.5em */
    }

    .stat-label {
        font-size: 0.8em; /* Reduced from 0.9em */
    }

    .cta-section {
        padding: 50px 0; /* Reduced from 60px */
    }

    .cta-title {
        font-size: 2em; /* Reduced from 2.5em */
    }

    .cta-description {
        font-size: 0.9em; /* Reduced from 1em */
        margin-bottom: 25px; /* Reduced from 30px */
    }

    .cta-button {
        padding: 10px 25px; /* Reduced from 12px 30px */
        font-size: 0.9em; /* Reduced from 1em */
    }
}
</style>
