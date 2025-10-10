<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import { useCurrency } from '@/composables/useCurrency';
import globeIcon from '../../assets/globe.svg';
import flagEn from '../../assets/flag-en.svg';
import flagFr from '../../assets/flag-fr.svg';
import flagNl from '../../assets/flag-nl.svg';
import flagEs from '../../assets/flag-es.svg';
import flagAr from '../../assets/flag-ar.svg';
import moneyExchangeSymbol from '../../assets/money-exchange-symbol.svg';
// hamburgerIcon is not used from assets anymore, using SVG directly
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/Components/ui/sheet";

const page = usePage();
const { url } = page; // for watch

const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();

const showingNavigationDropdown = ref(false);

const isLoginPage = computed(() => page.url.includes('/login'));
const isRegisterPage = computed(() => page.url.includes('/register'));

// Language switcher logic
const currentLocale = computed(() => page.props.locale || 'en');

const availableLocales = {
  en: { name: 'En', flag: flagEn },
  fr: { name: 'Fr', flag: flagFr },
  nl: { name: 'Nl', flag: flagNl },
  es: { name: 'Es', flag: flagEs },
  ar: { name: 'Ar', flag: flagAr },
};

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

const toggleMobileNav = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

// Watch for route changes to close mobile menu
watch(() => url.value, () => {
    showingNavigationDropdown.value = false;
});
</script>

<template>
    <header class="border-b border-gray-200 shadow-sm bg-white">
        <div class="full-w-container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <!-- Logo Section -->
                <div class="flex-shrink-0">
                    <Link href="/" class="block w-32 md:w-40 transition-transform hover:opacity-80">
                    <ApplicationLogo class="w-full h-auto" />
                    </Link>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex md:items-center md:space-x-6">
                    <Link v-if="isRegisterPage" :href="route('login')"
                        class="button-primary py-2 px-4 text-sm font-medium rounded-md transition-all duration-200 hover:shadow-md">
                    {{ _t('header', 'log_in') }}
                    </Link>
                    <Link v-if="isLoginPage" :href="route('register')"
                        class="button-secondary py-2 px-4 text-sm font-medium rounded-md transition-all duration-200 hover:shadow-md">
                    {{ _t('header', 'create_account') }}
                    </Link>

                    <!-- Currency Switcher -->
                    <div class="relative bg-[#efefef] hover:bg-[#d6d6d6] focus:bg-[#d6d6d6] rounded-full">
                        <Dropdown align="right" width="max">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition duration-150 ease-in-out"
                                    :disabled="currencyLoading"
                                >
                                    <img :src="moneyExchangeSymbol" alt="Currency" class="w-6 h-6 mr-2">
                                    <span>{{ formatCurrencyTriggerDisplay(selectedCurrency) }}</span>
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </template>
                        <template #content>
                            <div class="max-h-64 overflow-y-auto currency-scrollbar">
                                <div
                                    v-for="currency in supportedCurrencies"
                                    :key="currency"
                                    @click="changeCurrency(currency)"
                                    class="flex items-center min-w-max px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                                    :class="{ 'bg-white !text-[#153B4F]': selectedCurrency === currency }"
                                >
                                    <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                    {{ formatCurrencyDisplay(currency) }}
                                 </div>
                            </div>
                        </template>
                        </Dropdown>
                    </div>

                    <!-- Language Switcher for Guests (Desktop) -->
                    <Dropdown align="right" width="48" class="bg-[#efefef] hover:bg-[#d6d6d6] focus:bg-[#d6d6d6] rounded-full">
                        <template #trigger>
                            <button 
                            type="button" 
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition duration-150 ease-in-out"
                            >
                                <img :src="availableLocales[currentLocale].flag" :alt="availableLocales[currentLocale].name + ' Flag'" class="w-6 h-6 mr-2 rounded-full">
                                <span>{{ availableLocales[currentLocale].name }}</span>
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <div 
                                v-for="(language, code) in availableLocales" 
                                :key="code"
                                @click="changeLanguage(code)"
                                class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                                :class="{ 'bg-gray-500': currentLocale === code }"
                            >
                                <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                                {{ language.name }}
                            </div>
                        </template>
                    </Dropdown>
                </div>

                <!-- Mobile menu button -->
                <div class="flex md:hidden">
                    <button @click="toggleMobileNav" type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition duration-150 ease-in-out"
                        aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">{{ showingNavigationDropdown ? 'Close menu' : 'Open menu' }}</span>
                        <svg class="h-6 w-6"
                            :class="{ 'hidden': showingNavigationDropdown, 'block': !showingNavigationDropdown }"
                            stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="h-6 w-6"
                            :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
                            stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu, show/hide based on mobile menu state -->
        <div id="mobile-menu" :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
            class="md:hidden">
            <div class="pt-2 pb-4 space-y-1 border-t border-gray-200 bg-gray-50">
                <div class="px-4 py-3 space-y-3">
                    <Link v-if="isRegisterPage" :href="route('login')"
                        class="block w-full py-2 px-4 text-center font-medium text-white bg-customPrimaryColor hover:bg-[#153b4fef] rounded-md transition duration-150 ease-in-out">
                    {{ _t('header', 'log_in') }}
                    </Link>
                    <Link v-if="isLoginPage" :href="route('register')"
                        class="block w-full py-2 px-4 text-center font-medium text-blue-600 bg-white border border-customPrimaryColor hover:bg-blue-50 rounded-md transition duration-150 ease-in-out">
                    {{ _t('header', 'create_account') }}
                    </Link>

                    <!-- Currency Options -->
                    <div class="mt-3 px-4 py-2 border-t border-gray-200">
                        <div class="text-sm font-medium text-gray-600 mb-2">Currency</div>
                        <select
                            v-model="selectedCurrency"
                            @change="changeCurrency($event.target.value)"
                            class="block w-full px-2 py-1 text-sm rounded-md border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                            <option v-for="currency in supportedCurrencies" :key="currency" :value="currency">
                                {{ formatCurrencyDisplay(currency) }}
                            </option>
                        </select>
                    </div>

                    <!-- Language Options -->
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-sm font-medium text-gray-600 mb-2">Language</div>
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                v-for="(language, code) in availableLocales" 
                                :key="code"
                                @click="changeLanguage(code)"
                                class="flex items-center justify-center px-2 py-1 text-sm rounded-md transition-all duration-200 hover:bg-gray-200"
                                :class="currentLocale === code ? 'bg-gray-200 font-medium' : 'bg-gray-100'"
                            >
                                <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-1 rounded-full">
                                {{ language.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full-screen loader for currency change -->
        <div v-if="currencyLoading" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
            <img :src="moneyExchangeSymbol" alt="Loading..." class="w-16 h-16 animate-spin" />
        </div>
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
</style>
