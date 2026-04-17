<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import axios from "axios";

import Dropdown from "@/Components/Dropdown.vue";
import { useCurrency } from '@/composables/useCurrency';
import { setScrollLock } from '@/lib/scrollLock';
import whatsappIcon from '../../assets/whatsapp.svg';
import callIcon from '../../assets/call.svg';
import flagEn from '../../assets/flag-en.svg';
import flagFr from '../../assets/flag-fr.svg';
import flagNl from '../../assets/flag-nl.svg';
import flagEs from '../../assets/flag-es.svg';
import flagAr from '../../assets/flag-ar.svg';
import moneyExchangeSymbol from '../../assets/money-exchange-symbol.svg';
import FloatingSocialIcons from '@/Components/FloatingSocialIcons.vue';

const page = usePage();
const { url } = page;

const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();

const showingNavigationDropdown = ref(false);

const isLoginPage = computed(() => page.url.includes('/login'));
const isRegisterPage = computed(() => page.url.includes('/register'));
const contactInfo = ref(null);
const pages = computed(() => page.props.pages);

const fetchContactInfo = async () => {
    try {
        const response = await axios.get('/api/footer-contact-info');
        contactInfo.value = response.data;
    } catch (error) {
        console.error("Error fetching contact info:", error);
    }
};

onMounted(() => {
    fetchContactInfo();
});

// Language switcher logic
const currentLocale = computed(() => page.props.locale || 'en');

const availableLocales = {
    en: { name: 'En', flag: flagEn },
    fr: { name: 'Fr', flag: flagFr },
    nl: { name: 'Nl', flag: flagNl },
    es: { name: 'Es', flag: flagEs },
    ar: { name: 'Ar', flag: flagAr },
};

const getTranslatedSlug = (pageSlug) => {
    let targetPage = null;
    const defaultLocale = 'en';

    if (pages.value) {
        for (const key in pages.value) {
            const pageItem = pages.value[key];
            if (pageItem && pageItem.translations && Array.isArray(pageItem.translations)) {
                const defaultTranslation = pageItem.translations.find(
                    (t) => t.locale === defaultLocale && t.slug === pageSlug
                );
                if (defaultTranslation) {
                    targetPage = pageItem;
                    break;
                }
            }
        }
    }

    if (!targetPage || !targetPage.translations || !Array.isArray(targetPage.translations)) {
        return pageSlug;
    }

    const translation = targetPage.translations.find((t) => t.locale === currentLocale.value);
    return translation ? translation.slug : pageSlug;
};

const whatsappLink = computed(() => {
    const phone = contactInfo.value?.phone_number || '+32493000000';
    const digits = phone.replace(/[^\d]/g, '');
    return digits ? `https://wa.me/${digits}` : null;
});

const callLink = computed(() => {
    const phone = contactInfo.value?.phone_number || '+32493000000';
    const cleaned = phone.replace(/[^\d+]/g, '');
    if (!cleaned) return null;
    return `tel:${cleaned}`;
});

const resolveLanguageTargetUrl = (newLocale) => {
    const currentUrl = new URL(window.location.href);
    const pathParts = currentUrl.pathname.split('/');
    pathParts[1] = newLocale;
    return pathParts.join('/') + currentUrl.search;
};

const changeLanguage = async (newLocale) => {
    const targetUrl = resolveLanguageTargetUrl(newLocale);

    try {
        await axios.post(route('language.change'), { locale: newLocale });
    } catch (error) {
        console.error('Error persisting language preference:', error);
    }

    router.visit(targetUrl);
};

// Currency names mapping for better display
const currencyNames = {
    'USD': 'United States Dollar', 'EUR': 'Euro', 'GBP': 'British Pound Sterling',
    'JPY': 'Japanese Yen', 'AUD': 'Australian Dollar', 'CAD': 'Canadian Dollar',
    'CHF': 'Swiss Franc', 'CNH': 'Chinese Yuan', 'HKD': 'Hong Kong Dollar',
    'SGD': 'Singapore Dollar', 'SEK': 'Swedish Krona', 'KRW': 'South Korean Won',
    'NOK': 'Norwegian Krone', 'NZD': 'New Zealand Dollar', 'INR': 'Indian Rupee',
    'MXN': 'Mexican Peso', 'BRL': 'Brazilian Real', 'RUB': 'Russian Ruble',
    'ZAR': 'South African Rand', 'AED': 'United Arab Emirates Dirham',
    'MAD': 'Moroccan Dirham', 'TRY': 'Turkish Lira', 'JOD': 'Jordanian Dinar',
    'ISK': 'Iceland Krona', 'AZN': 'Azerbaijanian Manat', 'MYR': 'Malaysian Ringgit',
    'OMR': 'Rial Omani', 'UGX': 'Uganda Shilling', 'NIO': 'Nicaragua Cordoba Oro'
};

const currencySymbols = {
    'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥', 'AUD': 'A$', 'CAD': 'C$',
    'CHF': 'Fr', 'CNH': '¥', 'HKD': 'HK$', 'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩',
    'NOK': 'kr', 'NZD': 'NZ$', 'INR': '₹', 'MXN': '$', 'BRL': 'R$', 'RUB': '₽',
    'ZAR': 'R', 'AED': 'د.إ', 'MAD': 'د.م.‏', 'TRY': '₺', 'JOD': 'د.ا.‏',
    'ISK': 'kr.', 'AZN': '₼', 'MYR': 'RM', 'OMR': '﷼', 'UGX': 'USh', 'NIO': 'C$'
};

const formatCurrencyDisplay = (currency) => {
    const name = currencyNames[currency] || currency;
    const symbol = currencySymbols[currency] || '';
    return `${currency}(${name})${symbol}`;
};

const formatCurrencyTriggerDisplay = (currency) => {
    const symbol = currencySymbols[currency] || '';
    return `${currency}(${symbol})`;
};

const toggleMobileNav = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

watch(() => url.value, () => {
    showingNavigationDropdown.value = false;
});

watch(() => showingNavigationDropdown.value, (isOpen) => {
    setScrollLock(isOpen);
});

onUnmounted(() => {
    setScrollLock(false);
});
</script>

<template>
    <header class="hdr is-light z-40 relative">
        <div class="full-w-container mx-auto">
            <div class="hdr-inner">
                <!-- Logo -->
                <Link :href="route('welcome', { locale: page.props.locale })" class="hdr-logo">
                    <ApplicationLogo class="w-full h-auto" />
                </Link>

                <!-- Right Actions -->
                <div class="hdr-actions">
                    <!-- Currency & Language (desktop) -->
                    <div class="hidden lg:flex items-center gap-1.5">
                        <Dropdown align="right" width="max">
                            <template #trigger>
                                <button type="button" class="hdr-trigger" :disabled="currencyLoading" aria-label="Change currency">
                                    <img :src="moneyExchangeSymbol" alt="" class="w-5 h-5" :class="{ 'opacity-60': currencyLoading }">
                                    <span :class="{ 'opacity-60': currencyLoading }">{{ formatCurrencyTriggerDisplay(selectedCurrency) }}</span>
                                </button>
                            </template>
                            <template #content>
                                <div class="max-h-64 overflow-y-auto currency-scrollbar">
                                    <div v-for="currency in supportedCurrencies" :key="currency" @click="changeCurrency(currency)" class="flex min-w-max items-center px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer" :class="{ 'bg-white !text-[#153B4F] font-bold': selectedCurrency === currency }">
                                        <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        {{ formatCurrencyDisplay(currency) }}
                                    </div>
                                </div>
                            </template>
                        </Dropdown>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="hdr-trigger" aria-label="Change language">
                                    <img :src="availableLocales[currentLocale].flag" alt="" class="w-5 h-5 rounded-full">
                                    <span>{{ availableLocales[currentLocale].name }}</span>
                                </button>
                            </template>
                            <template #content>
                                <div v-for="(language, code) in availableLocales" :key="code" @click="changeLanguage(code)" class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer" :class="{ 'bg-gray-500': currentLocale === code }">
                                    <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                                    {{ language.name }}
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Login -->
                    <Link v-if="!isLoginPage" :href="route('login', { locale: page.props.locale })" class="hdr-btn primary">Log in</Link>

                    <!-- Hamburger -->
                    <button @click="toggleMobileNav" type="button" class="hdr-hamburger" :class="{ 'is-open': showingNavigationDropdown }">
                        <span class="sr-only">{{ showingNavigationDropdown ? 'Close menu' : 'Open menu' }}</span>
                        <span class="bar bar-1"></span>
                        <span class="bar bar-2"></span>
                        <span class="bar bar-3"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Offcanvas -->
        <div class="oc-overlay" :class="{ 'is-open': showingNavigationDropdown }" @click="showingNavigationDropdown = false"></div>
        <aside class="oc-panel" :class="{ 'is-open': showingNavigationDropdown }" role="dialog" aria-modal="true" :aria-hidden="!showingNavigationDropdown">
            <div class="flex h-full flex-col">
                <!-- Head -->
                <div class="oc-head">
                    <Link :href="route('welcome', { locale: page.props.locale })" class="w-28">
                        <ApplicationLogo class="w-full h-auto" />
                    </Link>
                    <button type="button" class="oc-close" @click="showingNavigationDropdown = false">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="oc-body">
                    <!-- Account -->
                    <div class="oc-section">
                        <div class="oc-label">Account</div>
                        <div class="oc-auth-btns">
                            <Link v-if="!isLoginPage" :href="route('login', { locale: page.props.locale })" class="oc-btn-login">Log in</Link>
                            <Link v-if="!isRegisterPage" :href="route('register', { locale: page.props.locale })" class="oc-btn-signup">Create Account</Link>
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="oc-section">
                        <div class="oc-label">Settings</div>
                        <Dropdown align="right" width="max">
                            <template #trigger>
                                <button type="button" class="oc-item w-full" :disabled="currencyLoading">
                                    <span class="flex items-center gap-2">
                                        <img :src="moneyExchangeSymbol" alt="Currency" class="w-5 h-5" :class="{ 'opacity-60': currencyLoading }">
                                        {{ formatCurrencyTriggerDisplay(selectedCurrency) }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="max-h-64 overflow-y-auto currency-scrollbar">
                                    <div v-for="currency in supportedCurrencies" :key="currency" @click="changeCurrency(currency)" class="flex min-w-max items-center px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer" :class="{ 'bg-white !text-[#153B4F] font-bold': selectedCurrency === currency }">
                                        <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        {{ formatCurrencyDisplay(currency) }}
                                    </div>
                                </div>
                            </template>
                        </Dropdown>
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="oc-item w-full">
                                    <span class="flex items-center gap-2">
                                        <img :src="availableLocales[currentLocale].flag" :alt="availableLocales[currentLocale].name + ' Flag'" class="w-5 h-5 rounded-full">
                                        {{ availableLocales[currentLocale].name }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            </template>
                            <template #content>
                                <div v-for="(language, code) in availableLocales" :key="code" @click="changeLanguage(code)" class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer" :class="{ 'bg-gray-500': currentLocale === code }">
                                    <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                                    {{ language.name }}
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Pages -->
                    <div class="oc-section">
                        <div class="oc-label">Explore</div>
                        <Link :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('about-us') })" class="oc-item">About Us</Link>
                        <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })" class="oc-item">Blogs</Link>
                        <Link :href="route('faq.show', { locale: page.props.locale })" class="oc-item">FAQ</Link>
                        <a href="https://vrooem.esimqr.link/" target="_blank" rel="noopener noreferrer" class="oc-item">eSIM</a>
                        <Link :href="`/${page.props.locale}/contact-us`" class="oc-item">Contact Us</Link>
                        <Link :href="`/${page.props.locale}/business/register`" class="oc-item">Business</Link>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="whatsappLink || callLink" class="oc-footer">
                    <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer" class="oc-footer-btn wa">
                        <img :src="whatsappIcon" alt="WhatsApp" class="w-5 h-5 brightness-0 invert"> WhatsApp
                    </a>
                    <a v-if="callLink" :href="callLink" class="oc-footer-btn call">
                        <img :src="callIcon" alt="Call" class="w-5 h-5 brightness-0 invert"> Call Now
                    </a>
                </div>
            </div>
        </aside>

        <!-- Currency loading -->
        <div v-if="currencyLoading" class="currency-overlay">
            <div class="currency-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
        </div>

        <FloatingSocialIcons />
    </header>
</template>

<style scoped>
/* Uses same class names as AuthenticatedHeaderLayout for visual consistency */
.hdr { --ease: cubic-bezier(0.22, 1, 0.36, 1); --duration: 0.3s; }
.oc-overlay, .oc-panel { --ease: cubic-bezier(0.22, 1, 0.36, 1); --duration: 0.3s; }

/* Header */
.hdr-inner { display: flex; align-items: center; justify-content: space-between; height: 60px; gap: 12px; }
@media (min-width: 768px) { .hdr-inner { height: 72px; gap: 20px; } }
.hdr-logo { display: block; width: 8rem; flex-shrink: 0; transition: opacity var(--duration) var(--ease); }
.hdr-logo:hover { opacity: 0.8; }
@media (min-width: 768px) { .hdr-logo { width: 10rem; } }
.hdr-actions { display: flex; align-items: center; gap: 6px; }

/* Light mode */
.hdr.is-light { background: #fff; border-bottom: 1px solid rgba(226, 232, 240, 0.6); box-shadow: 0 1px 3px rgba(21, 59, 79, 0.03), 0 4px 16px rgba(21, 59, 79, 0.02); }
.hdr-trigger { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 12px; font-size: 0.84rem; font-weight: 600; cursor: pointer; background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-trigger:hover { background: #f0f8fc; border-color: #153b4f; color: #153b4f; transform: translateY(-1px); }
.hdr-trigger[disabled] { cursor: not-allowed; opacity: 0.7; }
.hdr-btn { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-btn:hover { transform: translateY(-2px); }
.hdr-btn.ghost { background: transparent; border: 1px solid #e2e8f0; color: #334155; }
.hdr-btn.ghost:hover { border-color: #153b4f; color: #153b4f; background: #f8fafc; }
.hdr-btn.primary { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; border: none; box-shadow: 0 2px 8px rgba(21, 59, 79, 0.15); }
.hdr-btn.primary:hover { box-shadow: 0 8px 24px rgba(21, 59, 79, 0.2); }

/* Hamburger */
.hdr-hamburger { width: 40px; height: 40px; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; gap: 5px; padding: 0 11px; border-radius: 12px; cursor: pointer; background: #f8fafc; border: 1px solid #e2e8f0; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-hamburger:hover { background: #f0f8fc; border-color: #153b4f; box-shadow: 0 4px 16px rgba(21, 59, 79, 0.06); transform: translateY(-1px); }
.bar { height: 2px; border-radius: 2px; background: #334155; transition: transform 0.35s var(--ease), opacity 0.35s var(--ease), width 0.35s var(--ease); }
.bar-1 { width: 100%; } .bar-2 { width: 60%; } .bar-3 { width: 80%; }
.hdr-hamburger:hover .bar-2 { width: 80%; } .hdr-hamburger:hover .bar-3 { width: 100%; }
.hdr-hamburger.is-open .bar-1 { transform: translateY(7px) rotate(45deg); width: 100%; }
.hdr-hamburger.is-open .bar-2 { opacity: 0; transform: scaleX(0.2); }
.hdr-hamburger.is-open .bar-3 { transform: translateY(-7px) rotate(-45deg); width: 100%; }

/* Offcanvas */
.oc-overlay { position: fixed; inset: 0; z-index: 100000; background: rgba(10, 22, 32, 0.55); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); opacity: 0; pointer-events: none; transition: opacity 0.5s var(--ease); }
.oc-overlay.is-open { opacity: 1; pointer-events: auto; }
.oc-panel { position: fixed; top: 0; right: 0; height: 100vh; height: 100dvh; width: min(400px, 88vw); z-index: 100001; background: linear-gradient(180deg, #fff, #fafbfc); box-shadow: -24px 0 80px rgba(10, 22, 32, 0.12); transform: translateX(100%); transition: transform 0.5s var(--ease); display: flex; flex-direction: column; padding-bottom: env(safe-area-inset-bottom); }
.oc-panel.is-open { transform: translateX(0); }

/* OC Head */
.oc-head { display: flex; align-items: center; justify-content: space-between; padding: 24px 28px; border-bottom: 1px solid rgba(226, 232, 240, 0.5); }
.oc-close { width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease); }
.oc-close:hover { background: #f0f8fc; border-color: #153b4f; color: #153b4f; }

/* OC Body */
.oc-body { flex: 1; overflow-y: auto; padding: 8px 28px 28px; }
.oc-section { margin-bottom: 8px; }
.oc-label { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.16em; font-weight: 700; margin-bottom: 10px; padding-top: 20px; color: #94a3b8; }
.oc-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-radius: 14px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; color: #334155; background: #fff; border: 1px solid rgba(226, 232, 240, 0.6); margin-bottom: 6px; box-shadow: 0 1px 2px rgba(21, 59, 79, 0.02); transition: border-color var(--duration) var(--ease), color var(--duration) var(--ease), background var(--duration) var(--ease), transform var(--duration) var(--ease), box-shadow var(--duration) var(--ease); }
.oc-item:hover { border-color: #153b4f; color: #153b4f; background: #f0f8fc; transform: translateX(4px); box-shadow: 0 4px 12px rgba(21, 59, 79, 0.04); }

/* OC Auth buttons */
.oc-auth-btns { display: flex; gap: 10px; }
.oc-btn-login, .oc-btn-signup { flex: 1; padding: 14px; border-radius: 14px; text-align: center; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: transform var(--duration) var(--ease), box-shadow var(--duration) var(--ease), background var(--duration) var(--ease); }
.oc-btn-login { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; box-shadow: 0 4px 16px rgba(21, 59, 79, 0.2); }
.oc-btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(21, 59, 79, 0.25); }
.oc-btn-signup { background: #fff; border: 1.5px solid #153b4f; color: #153b4f; }
.oc-btn-signup:hover { background: #f0f8fc; transform: translateY(-2px); }

/* OC Footer */
.oc-footer { padding: 20px 28px calc(20px + env(safe-area-inset-bottom)); border-top: 1px solid rgba(226, 232, 240, 0.5); display: flex; gap: 10px; background: rgba(248, 250, 252, 0.8); }
.oc-footer-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; border-radius: 14px; font-size: 0.85rem; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); transition: transform var(--duration) var(--ease), box-shadow var(--duration) var(--ease); }
.oc-footer-btn.wa { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.oc-footer-btn.wa:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25); }
.oc-footer-btn.call { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; }
.oc-footer-btn.call:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(21, 59, 79, 0.25); }

/* Utilities */
.currency-scrollbar::-webkit-scrollbar { width: 6px; }
.currency-scrollbar::-webkit-scrollbar-track { background: transparent; }
.currency-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
.currency-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
.currency-overlay { position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.32); backdrop-filter: blur(6px); }
.currency-loader { display: inline-flex; align-items: center; gap: 10px; padding: 14px 22px; border-radius: 999px; background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2); }
.currency-loader .dot { width: 10px; height: 10px; border-radius: 999px; background: #f8fafc; animation: currencyDots 1.1s ease-in-out infinite; }
.currency-loader .dot:nth-child(2) { animation-delay: 0.2s; }
.currency-loader .dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes currencyDots { 0%, 100% { transform: translateY(0); opacity: 0.5; } 50% { transform: translateY(-6px); opacity: 1; } }
</style>
