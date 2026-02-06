<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import axios from "axios";

import Dropdown from "@/Components/Dropdown.vue";
import { useCurrency } from '@/composables/useCurrency';
import { hideTawk, showTawk } from '@/lib/tawk';
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
// hamburgerIcon is not used from assets anymore, using SVG directly

const page = usePage();
const { url } = page; // for watch

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

const middleNavItems = [
    { label: 'How it works', id: 'how-it-works' },
    { label: 'Blogs', id: 'blogs' },
    { label: 'Testimonials', id: 'testimonials' },
    { label: 'FAQ', id: 'faq' },
    { label: 'eSIM', id: 'esim' },
];

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

const changeLanguage = (newLocale) => {
    const currentUrl = new URL(window.location.href);
    const pathParts = currentUrl.pathname.split('/');
    pathParts[1] = newLocale;
    const newPath = pathParts.join('/');

    router.visit(newPath + currentUrl.search, {
        onSuccess: () => {
            router.post(route('language.change'), {
                locale: newLocale,
                _method: 'POST'
            }, {
                onSuccess: () => {
                    window.location.reload();
                }
            });
        }
    });
};

// Currency names mapping for better display
const currencyNames = {
    'USD': 'United States Dollar',
    'EUR': 'Euro',
    'GBP': 'British Pound Sterling',
    'JPY': 'Japanese Yen',
    'AUD': 'Australian Dollar',
    'CAD': 'Canadian Dollar',
    'CHF': 'Swiss Franc',
    'CNH': 'Chinese Yuan',
    'HKD': 'Hong Kong Dollar',
    'SGD': 'Singapore Dollar',
    'SEK': 'Swedish Krona',
    'KRW': 'South Korean Won',
    'NOK': 'Norwegian Krone',
    'NZD': 'New Zealand Dollar',
    'INR': 'Indian Rupee',
    'MXN': 'Mexican Peso',
    'BRL': 'Brazilian Real',
    'RUB': 'Russian Ruble',
    'ZAR': 'South African Rand',
    'AED': 'United Arab Emirates Dirham',
    'MAD': 'Moroccan Dirham',
    'TRY': 'Turkish Lira',
    'JOD': 'Jordanian Dinar',
    'ISK': 'Iceland Krona',
    'AZN': 'Azerbaijanian Manat',
    'MYR': 'Malaysian Ringgit',
    'OMR': 'Rial Omani',
    'UGX': 'Uganda Shilling',
    'NIO': 'Nicaragua Cordoba Oro'
};

// Currency symbols mapping
const currencySymbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'AUD': 'A$',
    'CAD': 'C$',
    'CHF': 'Fr',
    'CNH': '¥',
    'HKD': 'HK$',
    'SGD': 'S$',
    'SEK': 'kr',
    'KRW': '₩',
    'NOK': 'kr',
    'NZD': 'NZ$',
    'INR': '₹',
    'MXN': '$',
    'BRL': 'R$',
    'RUB': '₽',
    'ZAR': 'R',
    'AED': 'د.إ',
    'MAD': 'د.م.‏',
    'TRY': '₺',
    'JOD': 'د.ا.‏',
    'ISK': 'kr.',
    'AZN': '₼',
    'MYR': 'RM',
    'OMR': '﷼',
    'UGX': 'USh',
    'NIO': 'C$'
};

// Function to format currency display
const formatCurrencyDisplay = (currency) => {
    const name = currencyNames[currency] || currency;
    const symbol = currencySymbols[currency] || '';
    return `${currency}(${name})${symbol}`;
};

// Function to format currency display for the trigger
const formatCurrencyTriggerDisplay = (currency) => {
    const symbol = currencySymbols[currency] || '';
    return `${currency}(${symbol})`;
};

const isWelcomeRoute = computed(() => {
    if (typeof window === 'undefined') return false;
    const path = window.location.pathname;
    return path === `/${currentLocale.value}` || path === `/${currentLocale.value}/` || path === '/';
});

const handleNavClick = (event, targetId) => {
    if (!isWelcomeRoute.value) return;
    event.preventDefault();
    const section = document.getElementById(targetId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const toggleMobileNav = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

// Watch for route changes to close mobile menu
watch(() => url.value, () => {
    showingNavigationDropdown.value = false;
});

watch(() => showingNavigationDropdown.value, (isOpen) => {
    if (isOpen) {
        hideTawk();
    } else {
        showTawk();
    }
    setScrollLock(isOpen);
});

onUnmounted(() => {
    setScrollLock(false);
});
</script>

<template>
    <header class="border-b border-gray-200 shadow-sm bg-white relative z-40">
        <div class="full-w-container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20 gap-4">
                <div class="flex-shrink-0">
                    <Link :href="route('welcome', { locale: page.props.locale })"
                        class="block w-32 md:w-40 transition-transform hover:opacity-80">
                        <ApplicationLogo class="w-full h-auto" />
                    </Link>
                </div>

                <nav v-if="isWelcomeRoute" class="hidden lg:flex items-center gap-8 font-medium text-gray-700">
                    <button v-for="item in middleNavItems" :key="item.id" type="button" class="header-nav-link"
                        @click="handleNavClick($event, item.id)">
                        {{ item.label }}
                    </button>
                </nav>

                <div class="flex items-center gap-3">
                    <div class="hidden lg:flex items-center gap-2">
                        <Dropdown align="right" width="max">
                            <template #trigger>
                                <button type="button" class="header-icon-trigger is-labeled" :disabled="currencyLoading"
                                    aria-label="Change currency">
                                    <img :src="moneyExchangeSymbol" alt="" class="w-5 h-5"
                                        :class="{ 'opacity-60': currencyLoading }">
                                    <span class="header-trigger-label" :class="{ 'opacity-60': currencyLoading }">
                                        {{ formatCurrencyTriggerDisplay(selectedCurrency) }}
                                    </span>
                                </button>
                            </template>
                            <template #content>
                                <div class="max-h-64 overflow-y-auto currency-scrollbar">
                                    <div v-for="currency in supportedCurrencies" :key="currency"
                                        @click="changeCurrency(currency)"
                                        class="flex items-center min-w-max px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                                        :class="{ 'bg-white !text-[#153B4F]': selectedCurrency === currency }">
                                        <span v-if="selectedCurrency === currency"
                                            class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        {{ formatCurrencyDisplay(currency) }}
                                    </div>
                                </div>
                            </template>
                        </Dropdown>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="header-icon-trigger is-labeled" aria-label="Change language">
                                    <img :src="availableLocales[currentLocale].flag" alt=""
                                        class="w-5 h-5 rounded-full">
                                    <span class="header-trigger-label">{{ availableLocales[currentLocale].name }}</span>
                                </button>
                            </template>
                            <template #content>
                                <div v-for="(language, code) in availableLocales" :key="code"
                                    @click="changeLanguage(code)"
                                    class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                                    :class="{ 'bg-gray-500': currentLocale === code }">
                                    <img :src="language.flag" :alt="language.name + ' Flag'"
                                        class="w-5 h-5 mr-2 rounded-full">
                                    {{ language.name }}
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <button @click="toggleMobileNav" type="button" class="menu-toggle"
                        :class="{ 'is-open': showingNavigationDropdown }" aria-controls="offcanvas-menu"
                        aria-expanded="false">
                        <span class="sr-only">{{ showingNavigationDropdown ? 'Close menu' : 'Open menu' }}</span>
                        <span class="menu-bar bar-top"></span>
                        <span class="menu-bar bar-mid"></span>
                        <span class="menu-bar bar-bottom"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="offcanvas-overlay" :class="{ 'is-open': showingNavigationDropdown }"
            @click="showingNavigationDropdown = false" aria-hidden="true"></div>
        <aside class="offcanvas-panel" :class="{ 'is-open': showingNavigationDropdown }" role="dialog"
            aria-modal="true" :aria-hidden="!showingNavigationDropdown">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between px-6 py-5 border-b">
                    <Link :href="route('welcome', { locale: page.props.locale })" class="w-28">
                        <ApplicationLogo class="w-full h-auto" />
                    </Link>
                    <button type="button" class="offcanvas-close" @click="showingNavigationDropdown = false">
                        <span class="sr-only">Close menu</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div class="flex min-h-full flex-col gap-6">
                    <div class="space-y-3">
                        <div class="text-xs uppercase tracking-widest text-gray-400">Account</div>
                        <div class="flex flex-wrap gap-2">
                            <Link v-if="!isLoginPage" :href="route('login', { locale: page.props.locale })"
                                class="flex-1 min-w-[140px] flex items-center justify-between rounded-lg border border-customPrimaryColor bg-customPrimaryColor px-4 py-3 text-sm font-medium text-white hover:bg-[#153b4fef]">
                                Log in
                            </Link>
                            <Link v-if="!isRegisterPage" :href="route('register', { locale: page.props.locale })"
                                class="flex-1 min-w-[140px] flex items-center justify-between rounded-lg border border-customPrimaryColor px-4 py-3 text-sm font-medium text-customPrimaryColor hover:bg-blue-50">
                                Create Account
                            </Link>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="text-xs uppercase tracking-widest text-gray-400">Currency</div>
                        <Dropdown align="right" width="max">
                            <template #trigger>
                                <button type="button"
                                    class="inline-flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 hover:border-customPrimaryColor"
                                    :disabled="currencyLoading">
                                    <span class="flex items-center">
                                        <img :src="moneyExchangeSymbol" alt="Currency" class="w-5 h-5 mr-2">
                                        {{ formatCurrencyTriggerDisplay(selectedCurrency) }}
                                    </span>
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="max-h-64 overflow-y-auto currency-scrollbar">
                                    <div v-for="currency in supportedCurrencies" :key="currency"
                                        @click="changeCurrency(currency)"
                                        class="flex items-center min-w-max px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                                        :class="{ 'bg-white !text-[#153B4F]': selectedCurrency === currency }">
                                        <span v-if="selectedCurrency === currency"
                                            class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        {{ formatCurrencyDisplay(currency) }}
                                    </div>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="space-y-3">
                        <div class="text-xs uppercase tracking-widest text-gray-400">Language</div>
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button"
                                    class="inline-flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 hover:border-customPrimaryColor">
                                    <span class="flex items-center">
                                        <img :src="availableLocales[currentLocale].flag"
                                            :alt="availableLocales[currentLocale].name + ' Flag'"
                                            class="w-5 h-5 mr-2 rounded-full">
                                        {{ availableLocales[currentLocale].name }}
                                    </span>
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div v-for="(language, code) in availableLocales" :key="code"
                                    @click="changeLanguage(code)"
                                    class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                                    :class="{ 'bg-gray-500': currentLocale === code }">
                                    <img :src="language.flag" :alt="language.name + ' Flag'"
                                        class="w-5 h-5 mr-2 rounded-full">
                                    {{ language.name }}
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="space-y-3">
                        <div class="text-xs uppercase tracking-widest text-gray-400">Pages</div>
                        <div class="space-y-2">
                            <Link
                                :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('about-us') })"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                                About Us
                            </Link>
                            <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                                Blogs
                            </Link>
                            <Link :href="route('faq.show', { locale: page.props.locale })"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                                FAQ
                            </Link>
                            <a href="https://vrooem.esimqr.link/" target="_blank" rel="noopener noreferrer"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                                eSIM
                            </a>
                            <Link :href="route('contact-us', { locale: page.props.locale })"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                                Contact Us
                            </Link>
                            <Link :href="`/${page.props.locale}/business/register`"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                                Business
                            </Link>
                        </div>
                    </div>

                    <div v-if="whatsappLink || callLink" class="mt-auto space-y-3 border-t border-gray-200 pt-6">
                        <div class="text-xs uppercase tracking-widest text-gray-400">Contact</div>
                        <div class="flex flex-wrap gap-2">
                            <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                                class="flex-1 min-w-[160px] flex items-center gap-3 rounded-lg border border-emerald-500 bg-emerald-500 px-4 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:border-emerald-600 hover:bg-emerald-600">
                                <img :src="whatsappIcon" alt="WhatsApp" class="w-5 h-5 filter brightness-0 invert">
                                Chat on WhatsApp
                            </a>
                            <a v-if="callLink" :href="callLink"
                                class="flex-1 min-w-[160px] flex items-center gap-3 rounded-lg border border-customPrimaryColor bg-customPrimaryColor px-4 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:border-[#153b4fef] hover:bg-[#153b4fef]">
                                <img :src="callIcon" alt="Call" class="w-5 h-5 filter brightness-0 invert">
                                Call Now
                            </a>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </aside>

        <div v-if="currencyLoading"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
            <img :src="moneyExchangeSymbol" alt="Loading..." class="w-16 h-16 animate-spin" />
        </div>
        <FloatingSocialIcons />
    </header>
</template>

<style>
.offcanvasList a {
    padding: 1rem;
    background-color: var(--custom-primary);
    color: white;
    border-radius: 12px;
}

/* Custom scrollbar for currency dropdown */
.currency-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.currency-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.currency-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.currency-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.header-nav-link {
    position: relative;
    font-size: 1.05rem;
    color: #334155;
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: color 200ms ease;
}

.header-nav-link::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: 0;
    width: 0;
    height: 2px;
    background: #153b4f;
    transform: translateX(-50%);
    transition: width 220ms ease;
}

.header-nav-link:hover,
.header-nav-link:focus-visible {
    color: #153b4f;
}

.header-nav-link:hover::after,
.header-nav-link:focus-visible::after {
    width: 100%;
}

.header-icon-trigger {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    color: #475569;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease,
        background 160ms ease, color 160ms ease;
}

.header-icon-trigger.is-labeled {
    width: auto;
    padding: 0 12px;
    gap: 8px;
}

.header-trigger-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    line-height: 1;
}

.header-icon-trigger:hover {
    background: #ffffff;
    border-color: rgba(46, 167, 173, 0.45);
    color: #0f172a;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    transform: translateY(-1px);
}

.header-icon-trigger:active {
    transform: translateY(0);
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
}

.header-icon-trigger:focus-visible {
    outline: 2px solid rgba(59, 130, 246, 0.5);
    outline-offset: 2px;
}

.header-icon-trigger[disabled] {
    cursor: not-allowed;
    opacity: 0.75;
    box-shadow: none;
}

.menu-toggle {
    width: 46px;
    height: 46px;
    padding: 0 11px;
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    color: #334155;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease,
        background 160ms ease;
}

.menu-toggle:hover {
    border-color: rgba(46, 167, 173, 0.45);
    background: #ffffff;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    transform: translateY(-1px);
}

.menu-toggle:focus-visible {
    outline: 2px solid rgba(59, 130, 246, 0.5);
    outline-offset: 2px;
}

.menu-bar {
    height: 2px;
    background: #334155;
    border-radius: 999px;
    align-self: flex-start;
    transition: transform 200ms ease, opacity 200ms ease, width 200ms ease;
}

.bar-top {
    width: 100%;
}

.bar-mid {
    width: 70%;
}

.bar-bottom {
    width: 85%;
}

.menu-toggle:hover .bar-mid {
    width: 85%;
}

.menu-toggle:hover .bar-bottom {
    width: 100%;
}

.menu-toggle.is-open .bar-top {
    transform: translateY(8px) rotate(45deg);
    width: 100%;
}

.menu-toggle.is-open .bar-mid {
    opacity: 0;
    transform: scaleX(0.2);
}

.menu-toggle.is-open .bar-bottom {
    transform: translateY(-8px) rotate(-45deg);
    width: 100%;
}

.offcanvas-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 240ms ease;
    z-index: 100000;
}

.offcanvas-overlay.is-open {
    opacity: 1;
    pointer-events: auto;
}

.offcanvas-panel {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 320px;
    max-width: 92vw;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
    transform: translateX(100%);
    transition: transform 320ms ease;
    z-index: 100001;
}

.offcanvas-panel.is-open {
    transform: translateX(0);
}

.offcanvas-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    color: #4b5563;
    background: rgba(15, 23, 42, 0.05);
    transition: color 150ms ease, background 150ms ease;
}

.offcanvas-close:hover {
    color: #111827;
    background: rgba(15, 23, 42, 0.12);
}

@media (min-width: 640px) {
    .offcanvas-panel {
        width: 380px;
    }
}
</style>
