<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import globeIcon from '../../assets/globe.svg';
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

const showingNavigationDropdown = ref(false);

const isLoginPage = computed(() => page.url.includes('/login'));
const isRegisterPage = computed(() => page.url.includes('/register'));

// Language switcher logic
const currentLocale = computed(() => page.props.locale || 'en');

const availableLocales = {
    en: 'En',
    fr: 'Fr',
    nl: 'Nl'
    // Add more locales if your application supports them
};

const changeLanguage = (locale) => {
    router.post(route('language.change'), { locale }, {
        preserveState: false,
        preserveScroll: true,
    });
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

                    <!-- Language Switcher for Guests (Desktop) -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition duration-150 ease-in-out">
                                <img :src=globeIcon alt="" class="w-8 h-8">
                                <span>{{ availableLocales[currentLocale] }}</span>
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <div v-for="(language, code) in availableLocales" :key="code" @click="changeLanguage(code)"
                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                                :class="{ 'bg-gray-500': currentLocale === code }">
                                {{ language }}
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

                    <!-- Language Options -->
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-sm font-medium text-gray-600 mb-2">{{ _t('header', 'language') }}</div>
                        <div class="grid grid-cols-3 gap-2">
                            <button v-for="(language, code) in availableLocales" :key="code"
                                @click="changeLanguage(code)"
                                class="text-center px-2 py-1 text-sm rounded-md transition-all duration-200 hover:bg-gray-200"
                                :class="currentLocale === code ? 'bg-gray-200 font-medium' : 'bg-gray-100'">
                                {{ language }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
</style>
