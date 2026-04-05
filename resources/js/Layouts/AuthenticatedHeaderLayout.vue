<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import axios from "axios";
import { useCurrency } from '@/composables/useCurrency';
// import { hideTawk, showTawk } from '@/lib/tawk';
import { setScrollLock } from '@/lib/scrollLock';
import bellIcon from '../../assets/bell.svg'
import whatsappIcon from '../../assets/whatsapp.svg';
import callIcon from '../../assets/call.svg';
import flagEn from '../../assets/flag-en.svg';
import flagFr from '../../assets/flag-fr.svg';
import flagNl from '../../assets/flag-nl.svg';
import flagEs from '../../assets/flag-es.svg';
import flagAr from '../../assets/flag-ar.svg';
import moneyExchangeSymbol from '../../assets/money-exchange-symbol.svg';
import { getGeoPreferredLocale, toCountryCodeSet } from '@/utils/geoLanguage';

import FloatingSocialIcons from '@/Components/FloatingSocialIcons.vue';

// Get page properties
const page = usePage();
const { url, props } = page;
const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();
const showingNavigationDropdown = ref(false);
const showingNotificationDropdown = ref(false);
const animatedFlagUrl = ref(null); // New ref for animated flag URL
const showingAccountDropdown = ref(false);
const showingDesktopDropdown = ref(false);
const desktopDropdownRef = ref(null);

const closeDesktopDropdown = (e) => {
  if (desktopDropdownRef.value && !desktopDropdownRef.value.contains(e.target)) {
    showingDesktopDropdown.value = false;
  }
};
onMounted(() => document.addEventListener('click', closeDesktopDropdown));
onUnmounted(() => document.removeEventListener('click', closeDesktopDropdown));

// Notifications
const notifications = ref([]);
const unreadCount = ref(0);
const contactInfo = ref(null);



// Refs for dropdowns
const notificationDropdownRef = ref(null);
const bellIconRef = ref(null);

// Close notification dropdown when clicking outside
const closeNotificationDropdownOnOutsideClick = (event) => {
  if (showingNotificationDropdown.value && notificationDropdownRef.value && bellIconRef.value) {
    if (!notificationDropdownRef.value.contains(event.target) && !bellIconRef.value.contains(event.target)) {
      showingNotificationDropdown.value = false;
    }
  }
};

// Add click event listeners on mount
onMounted(async () => {
  if (page.props.auth?.user) {
    fetchNotifications();
  }
  fetchContactInfo();
  await loadCountryCodeSet();
  syncLanguageWithCountry();

  document.addEventListener('click', closeNotificationDropdownOnOutsideClick);
});

// Clean up event listeners on unmount
onUnmounted(() => {
  document.removeEventListener('click', closeNotificationDropdownOnOutsideClick);
  setScrollLock(false);
});

// Fetch contact info
const fetchContactInfo = async () => {
  try {
    const response = await axios.get('/api/footer-contact-info');
    contactInfo.value = response.data;
  } catch (error) {
    console.error("Error fetching contact info:", error);
  }
};

// Fetch notifications
const fetchNotifications = async () => {
  try {
    const response = await axios.get(route('notifications.index', { locale: currentLocale.value }));
    notifications.value = response.data.notifications.data;
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error("Error fetching notifications:", error);
  }
};

// Mark a notification as read
const markAsRead = async (notification) => {
  if (notification.read_at) return;
  try {
    await axios.post(route('notifications.mark-read', { locale: currentLocale.value, id: notification.id }));
    notification.read_at = new Date().toISOString();
    unreadCount.value--;
  } catch (error) {
    console.error("Error marking notification as read:", error);
  }
};

// Mark all notifications as read
const markAllAsRead = async () => {
  try {
    await axios.post(route('notifications.mark-all-read', { locale: currentLocale.value }));
    notifications.value.forEach(n => n.read_at = new Date().toISOString());
    unreadCount.value = 0;
  } catch (error) {
    console.error("Error marking all notifications as read:", error);
  }
};

// Clear all notifications
const clearAllNotifications = async () => {
  try {
    await axios.delete(route('notifications.clear-all', { locale: currentLocale.value }));
    notifications.value = [];
    unreadCount.value = 0;
    showingNotificationDropdown.value = false;
  } catch (error) {
    console.error("Error clearing notifications:", error);
  }
};

// Function to get the appropriate link for a notification
const getNotificationLink = (notification) => {
  const type = notification.type.split('\\').pop();
  const locale = currentLocale.value;

  switch (type) {
    // Vendor vehicle management
    case 'VendorVehicleCreateNotification':
    case 'VendorVehicleCreateCompanyNotification':
    case 'VehicleCreatedNotification':
    case 'BulkVehicleUploadNotification':
      return route('current-vendor-vehicles.index', { locale });
    // Vendor status
    case 'VendorStatusUpdatedNotification':
      return route('vendor.status', { locale });
    // Vendor bookings
    case 'BookingCreatedVendorNotification':
    case 'BookingCreatedCompanyNotification':
    case 'PendingBookingReminderNotification':
      return route('bookings.index', { locale });
    // Customer bookings
    case 'BookingCreatedCustomerNotification':
      return route('profile.bookings.confirmed', { locale });
    case 'BookingCancelledNotification':
    case 'BookingCancelledCustomerNotification':
      return isVendor.value ? route('bookings.index', { locale }) : route('profile.bookings.cancelled', { locale });
    case 'BookingStatusUpdatedCustomerNotification': {
      const s = notification.data?.status;
      if (s === 'pending') return route('profile.bookings.pending', { locale });
      if (s === 'confirmed') return route('profile.bookings.confirmed', { locale });
      if (s === 'completed') return route('profile.bookings.completed', { locale });
      return route('profile.bookings', { locale });
    }
    // External API bookings (vendor)
    case 'ApiBookingCreatedVendorNotification':
    case 'ApiBookingCancelledVendorNotification':
      return route('vendor.external-bookings.index', { locale });
    // Messages
    case 'NewMessageNotification':
    case 'MessageReminderNotification':
      return isVendor.value ? route('messages.vendor.index', { locale }) : route('messages.index', { locale });
    // Reviews
    case 'ReviewSubmittedVendorNotification':
    case 'ReviewSubmittedCompanyNotification':
      return route('vendor.reviews', { locale });
    // Payments
    case 'CustomerPaymentFailedNotification':
      return route('profile.payments', { locale });
    // Guest booking
    case 'GuestBookingCreatedNotification':
      return route('login', { locale });
    default:
      return '#';
  }
};

// Format notification type for display (removes "Notification" suffix and adds spaces)
const formatNotificationType = (type) => {
  if (!type) return '';
  const className = type.split('\\').pop().replace(/Notification$/, '');
  // Add spaces before capitals: "BookingCreatedCustomer" -> "Booking Created Customer"
  return className.replace(/([A-Z])/g, ' $1').trim();
};

// Handle notification click: mark as read and redirect
const handleNotificationClick = async (notification) => {
  await markAsRead(notification);
  const link = getNotificationLink(notification);
  if (link && link !== '#') {
    router.visit(link);
  }
  showingNotificationDropdown.value = false; // Close dropdown after click
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

// Computed properties
const vendorStatus = computed(() => page.props.vendorStatus);
const currentLocale = computed(() => page.props.locale || 'en');
const isAuthenticated = computed(() => !!page.props.auth?.user);
const isVendor = computed(() => page.props.auth?.user?.role === 'vendor');
const isCustomer = computed(() => page.props.auth?.user?.role === 'customer');
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const authUser = computed(() => page.props.auth?.user || null);

const pages = computed(() => page.props.pages);

// Language switcher
const availableLocales = {
  en: { name: 'En', flag: flagEn },
  fr: { name: 'Fr', flag: flagFr },
  nl: { name: 'Nl', flag: flagNl },
  es: { name: 'Es', flag: flagEs },
  ar: { name: 'Ar', flag: flagAr },
};

const AUTO_LANGUAGE_COUNTRY_KEY = 'auto_language_country_code';
const normalizedCountryCode = computed(() => String(page.props.country || '').trim().toUpperCase());
const supportedLocaleCodes = Object.keys(availableLocales);
const countryCodeSet = ref(new Set());
const preferredGeoLocale = computed(() => getGeoPreferredLocale(
  normalizedCountryCode.value,
  supportedLocaleCodes,
  countryCodeSet.value,
));

const loadCountryCodeSet = async () => {
  if (countryCodeSet.value.size > 0 || typeof window === 'undefined') {
    return;
  }

  try {
    const response = await fetch('/countries.json', { cache: 'force-cache' });
    if (!response.ok) {
      return;
    }

    const countries = await response.json();
    countryCodeSet.value = toCountryCodeSet(countries);
  } catch (error) {
    // Keep fallback behavior if countries.json loading fails
  }
};

const performLanguageNavigation = (newLocale) => {
  const currentUrl = new URL(window.location.href);
  const pathParts = currentUrl.pathname.split('/');

  let targetUrl = null;

  // Handle page translations
  if (pathParts.length > 2 && pathParts[2] === 'page') {
    const currentSlug = pathParts[3];
    const pagesData = page.props.pages;
    const currentPage = Object.values(pagesData).find(p => p.translations.some(t => t.slug === currentSlug));
    if (currentPage) {
      const t = currentPage.translations.find(t => t.locale === newLocale);
      if (t) targetUrl = route('pages.show', { locale: newLocale, slug: t.slug });
    }
  }

  // Handle blog post translations
  if (!targetUrl && pathParts.length > 3 && pathParts[3] === 'blog' && page.props.blog) {
    const t = page.props.blog.translations.find(t => t.locale === newLocale);
    targetUrl = t?.slug
      ? route('blog.show', { locale: newLocale, country: page.props.country, blog: t.slug })
      : route('blog', { locale: newLocale, country: page.props.country });
  }

  // Handle contact page
  if (!targetUrl && page.props.contactPage) {
    targetUrl = `/${newLocale}/contact-us`;
  }

  // Fallback: swap locale in current path
  if (!targetUrl) {
    pathParts[1] = newLocale;
    targetUrl = pathParts.join('/') + currentUrl.search;
  }

  // Navigate immediately via Inertia — session is saved by the middleware on the target page
  router.visit(targetUrl);
};

const changeLanguage = (newLocale, options = {}) => {
  const { auto = false } = options;
  if (!availableLocales[newLocale] || currentLocale.value === newLocale) {
    return;
  }

  if (typeof window !== 'undefined' && normalizedCountryCode.value && !auto) {
    sessionStorage.setItem(AUTO_LANGUAGE_COUNTRY_KEY, normalizedCountryCode.value);
  }

  performLanguageNavigation(newLocale);
};

const syncLanguageWithCountry = () => {
  if (typeof window === 'undefined') {
    return;
  }

  const countryCode = normalizedCountryCode.value;
  if (!countryCode) {
    return;
  }

  const targetLocale = preferredGeoLocale.value;
  if (!availableLocales[targetLocale]) {
    return;
  }

  const lastAutoCountry = sessionStorage.getItem(AUTO_LANGUAGE_COUNTRY_KEY);
  if (lastAutoCountry === countryCode) {
    return;
  }

  sessionStorage.setItem(AUTO_LANGUAGE_COUNTRY_KEY, countryCode);

  if (currentLocale.value !== targetLocale) {
    changeLanguage(targetLocale, { auto: true });
  }
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

const displayName = computed(() => {
  const user = authUser.value;
  if (!user) return 'Account';
  const first = user.first_name || '';
  const last = user.last_name || '';
  const full = `${first} ${last}`.trim();
  return full || user.name || user.email || 'Account';
});

const roleLabel = computed(() => {
  if (isAdmin.value) return 'Admin';
  if (isVendor.value) return 'Vendor';
  if (isCustomer.value) return 'User';
  return 'User';
});

const avatarUrl = computed(() => {
  const user = authUser.value;
  return user?.profile?.avatar || user?.avatar || null;
});

const userInitials = computed(() => {
  const user = authUser.value;
  if (!user) return 'A';
  const base = `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || user.email || 'A';
  const parts = base.trim().split(/\s+/);
  if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
  return base[0]?.toUpperCase() || 'A';
});

const isWelcomeRoute = computed(() => {
  if (typeof window === 'undefined') return false;
  const path = window.location.pathname;
  return path === `/${currentLocale.value}` || path === `/${currentLocale.value}/` || path === '/';
});

// Transparent header on Welcome page hero
const heroTransparent = computed(() => isWelcomeRoute.value);

const handleNavClick = (event, targetId) => {
  if (!isWelcomeRoute.value) return;
  event.preventDefault();
  const section = document.getElementById(targetId);
  if (section) {
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

// Function to toggle mobile navigation
const toggleMobileNav = () => {
  showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

// Watch for route changes to close mobile menu
watch(() => url.value, () => {
  showingNavigationDropdown.value = false;
  showingAccountDropdown.value = false;
});

watch(() => page.props.country, async () => {
  await loadCountryCodeSet();
  syncLanguageWithCountry();
});

watch(() => showingNavigationDropdown.value, (isOpen) => {
  if (!isOpen) showingAccountDropdown.value = false;
  // if (isOpen) {
  //     hideTawk();
  // } else {
  //     showTawk();
  // }
  setScrollLock(isOpen);
});
</script>

<template>
  <header class="hdr z-40" :class="isWelcomeRoute ? 'is-hero absolute top-0 left-0 right-0' : 'is-light relative'">
    <div class="full-w-container mx-auto">
      <div class="hdr-inner">
        <!-- Logo -->
        <Link :href="route('welcome', { locale: page.props.locale })" class="hdr-logo">
          <ApplicationLogo class="w-full h-auto" :logo-color="heroTransparent ? '#ffffff' : '#153B4F'" />
        </Link>

        <!-- Center Nav (hero only) -->
        <nav v-if="isWelcomeRoute" class="hdr-nav">
          <button v-for="item in middleNavItems" :key="item.id" type="button" class="hdr-nav-link" @click="handleNavClick($event, item.id)">
            {{ item.label }}
          </button>
        </nav>

        <!-- Right Actions -->
        <div class="hdr-actions">
          <!-- Bell (authenticated) -->
          <div v-if="isAuthenticated" class="relative">
            <button ref="bellIconRef" @click="showingNotificationDropdown = !showingNotificationDropdown; markAllAsRead()" class="hdr-icon" :class="{ 'ripple-effect': unreadCount > 0 }">
              <img :src="bellIcon" alt="Notifications" class="w-5 h-5" :class="{ 'brightness-0 invert': heroTransparent }">
              <span v-if="unreadCount > 0" class="hdr-badge">{{ unreadCount }}</span>
            </button>
            <!-- Notification dropdown -->
            <div ref="notificationDropdownRef" v-if="showingNotificationDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-[99999] top-[3rem] overflow-hidden">
              <div class="p-4 border-b border-gray-100"><h3 class="text-base font-semibold text-gray-900">Notifications</h3></div>
              <div class="overflow-y-auto" style="max-height: 400px;">
                <div v-if="notifications.length === 0" class="p-6 text-center text-gray-400 text-sm">No notifications yet.</div>
                <div v-else>
                  <div v-for="notification in notifications" :key="notification.id" @click="handleNotificationClick(notification)" class="px-4 py-3 border-b border-gray-50 hover:bg-gray-50/80 cursor-pointer transition-colors" :class="{ 'bg-blue-50/40': !notification.read_at }">
                    <div class="flex items-center justify-between gap-2">
                      <span class="font-semibold text-sm text-gray-900">{{ notification.data.title || notification.data.booking_number || 'Notification' }}</span>
                      <span class="text-[10px] text-gray-400 shrink-0">{{ formatNotificationType(notification.type) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">{{ notification.data.message }}</p>
                    <div class="text-xs text-customPrimaryColor mt-1 text-right">{{ new Date(notification.created_at).toLocaleString() }}</div>
                  </div>
                </div>
              </div>
              <div class="p-2 border-t border-gray-100 text-center">
                <button @click="clearAllNotifications" class="text-sm text-red-500 hover:underline">Clear all</button>
              </div>
            </div>
          </div>

          <!-- Currency & Language (desktop) -->
          <div class="hidden lg:flex items-center gap-1.5">
            <Dropdown align="right" width="max">
              <template #trigger>
                <button type="button" class="hdr-trigger" :disabled="currencyLoading" aria-label="Change currency">
                  <img :src="moneyExchangeSymbol" alt="" class="w-5 h-5" :class="[{ 'opacity-60': currencyLoading }, heroTransparent ? 'brightness-0 invert' : '']">
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

          <!-- Login (guest only) -->
          <Link v-if="!isAuthenticated" :href="route('login', { locale: props.locale })" class="hdr-btn primary">Log in</Link>

          <!-- User dropdown (authenticated, desktop only) -->
          <div v-if="isAuthenticated" ref="desktopDropdownRef" class="hdr-user-wrap hidden lg:inline-flex">
            <button type="button" class="hdr-avatar" @click="showingDesktopDropdown = !showingDesktopDropdown">{{ userInitials }}</button>
            <div v-if="showingDesktopDropdown" class="hdr-user-menu">
              <Link v-if="!isAdmin" :href="route('profile.edit', { locale: props.locale })" class="hdr-user-item" @click="showingDesktopDropdown = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profile
              </Link>
              <Link :href="route('logout', { locale: props.locale })" method="post" as="button" class="hdr-user-item" @click="showingDesktopDropdown = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Log Out
              </Link>
            </div>
          </div>

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
        <!-- Offcanvas Head -->
        <div class="oc-head">
          <div v-if="isAuthenticated" class="relative">
            <button type="button" class="oc-account" @click="showingAccountDropdown = !showingAccountDropdown">
              <div class="oc-av">
                <img v-if="avatarUrl" :src="avatarUrl" alt="User avatar" />
                <span v-else>{{ userInitials }}</span>
              </div>
              <div class="oc-av-meta">
                <span class="oc-av-name">{{ displayName }}</span>
                <span class="oc-av-role">{{ roleLabel }}</span>
              </div>
              <svg class="oc-chevron" :class="{ 'is-open': showingAccountDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 15l6-6 6 6" /></svg>
            </button>
            <div v-if="showingAccountDropdown" class="oc-account-menu">
              <Link v-if="!isAdmin" :href="route('profile.edit', { locale: props.locale })" class="oc-menu-item">Profile</Link>
              <Link :href="route('logout', { locale: props.locale })" method="post" as="button" class="oc-menu-item">Log Out</Link>
            </div>
          </div>
          <Link v-else :href="route('welcome', { locale: page.props.locale })" class="w-28">
            <ApplicationLogo class="w-full h-auto" />
          </Link>
          <button type="button" class="oc-close" @click="showingNavigationDropdown = false">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <!-- Offcanvas Body -->
        <div class="oc-body">
          <!-- Account Section -->
          <div class="oc-section">
            <div class="oc-label">Account</div>
            <Link v-if="isAdmin" :href="route('admin.dashboard')" class="oc-item">Admin Dashboard</Link>
            <Link v-if="isVendor" :href="vendorStatus === 'approved' ? route('vehicles.create', { locale: props.locale }) : route('vendor.status', { locale: props.locale })" class="oc-item">
              <span v-if="vendorStatus === 'approved'">Create Listing</span>
              <span v-else>Complete Verification</span>
            </Link>
            <Link v-if="isCustomer" :href="route('vendor.register', { locale: props.locale })" class="oc-item">Register as Vendor</Link>
            <div v-if="!isAuthenticated" class="oc-auth-btns">
              <Link :href="route('login', { locale: props.locale })" class="oc-btn-login">Log in</Link>
              <Link :href="route('register', { locale: props.locale })" class="oc-btn-signup">Create Account</Link>
            </div>
          </div>

          <!-- Settings Section -->
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

          <!-- Pages Section -->
          <div class="oc-section">
            <div class="oc-label">Explore</div>
            <Link :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('about-us') })" class="oc-item">About Us</Link>
            <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })" class="oc-item">Blogs</Link>
            <Link :href="route('faq.show', { locale: page.props.locale })" class="oc-item">FAQ</Link>
            <a href="https://vrooem.esimqr.link/" target="_blank" rel="noopener noreferrer" class="oc-item">eSIM</a>
            <Link :href="`/${page.props.locale}/contact-us`" class="oc-item">Contact Us</Link>
            <Link :href="route('affiliate.register', { locale: page.props.locale })" class="oc-item">Business</Link>
          </div>
        </div>

        <!-- Offcanvas Footer -->
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

    <!-- Flag animation overlay -->
    <div v-if="animatedFlagUrl" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
      <img :src="animatedFlagUrl" alt="Flag" class="animated-flag" />
    </div>

    <!-- Currency loading overlay -->
    <div v-if="currencyLoading" class="currency-overlay">
      <div class="currency-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
    </div>

    <FloatingSocialIcons />
  </header>
</template>

<style scoped>
/* =================== VARIABLES =================== */
.hdr { --ease: cubic-bezier(0.22, 1, 0.36, 1); --duration: 0.3s; }
.oc-overlay, .oc-panel { --ease: cubic-bezier(0.22, 1, 0.36, 1); --duration: 0.3s; }

/* =================== HEADER LAYOUT =================== */
.hdr-inner { display: flex; align-items: center; justify-content: space-between; height: 60px; gap: 12px; }
@media (min-width: 768px) { .hdr-inner { height: 72px; gap: 20px; } }
.hdr-logo { display: block; width: 8rem; flex-shrink: 0; transition: opacity var(--duration) var(--ease); }
.hdr-logo:hover { opacity: 0.8; }
@media (min-width: 768px) { .hdr-logo { width: 10rem; } }

/* =================== NAV =================== */
.hdr-nav { display: none; align-items: center; gap: 6px; }
@media (min-width: 1024px) { .hdr-nav { display: flex; } }
.hdr-nav-link { font-size: 0.88rem; font-weight: 500; padding: 7px 14px; border-radius: 10px; background: transparent; border: none; cursor: pointer; transition: color var(--duration) var(--ease), background var(--duration) var(--ease); }

/* =================== ACTIONS =================== */
.hdr-actions { display: flex; align-items: center; gap: 6px; }
.hdr-icon { width: 42px; height: 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; position: relative; border: 1px solid transparent; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-icon:hover { transform: translateY(-1px); }
.hdr-badge { position: absolute; top: 3px; right: 3px; width: 16px; height: 16px; border-radius: 50%; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-size: 0.58rem; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(10, 22, 32, 0.5); box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4); }
.hdr-trigger { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 12px; font-size: 0.84rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-trigger:hover { transform: translateY(-1px); }
.hdr-trigger[disabled] { cursor: not-allowed; opacity: 0.7; }
.hdr-user-wrap { position: relative; }
.hdr-avatar { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.82rem; cursor: pointer; border: 2px solid transparent; transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-avatar:hover { transform: translateY(-1px); }
.hdr-user-menu { position: absolute; top: calc(100% + 10px); right: 0; min-width: 180px; padding: 8px; border-radius: 14px; background: #fff; border: 1px solid rgba(148, 163, 184, 0.3); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12); z-index: 50; }
.hdr-user-item { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; padding: 10px 12px; border-radius: 10px; font-size: 0.92rem; font-weight: 500; color: #111827; transition: background var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-user-item:hover { background: rgba(15, 23, 42, 0.06); transform: translateX(4px); }
.hdr-user-item svg { width: 18px; height: 18px; color: #64748b; flex-shrink: 0; }
.hdr-btn { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-btn:hover { transform: translateY(-2px); }

/* =================== HAMBURGER =================== */
.hdr-hamburger { width: 40px; height: 40px; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; gap: 5px; padding: 0 11px; border-radius: 12px; cursor: pointer; border: 1px solid transparent; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.hdr-hamburger:hover { transform: translateY(-1px); }
.bar { height: 2px; border-radius: 2px; transition: transform 0.35s var(--ease), opacity 0.35s var(--ease), width 0.35s var(--ease); }
.bar-1 { width: 100%; } .bar-2 { width: 60%; } .bar-3 { width: 80%; }
.hdr-hamburger:hover .bar-2 { width: 80%; } .hdr-hamburger:hover .bar-3 { width: 100%; }
.hdr-hamburger.is-open .bar-1 { transform: translateY(7px) rotate(45deg); width: 100%; }
.hdr-hamburger.is-open .bar-2 { opacity: 0; transform: scaleX(0.2); }
.hdr-hamburger.is-open .bar-3 { transform: translateY(-7px) rotate(-45deg); width: 100%; }

/* =================== HERO MODE (glassmorphism) =================== */
.hdr.is-hero { background: transparent; border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
.hdr.is-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10, 22, 32, 0.4), rgba(10, 22, 32, 0.2)); backdrop-filter: blur(20px) saturate(1.4); -webkit-backdrop-filter: blur(20px) saturate(1.4); z-index: -1; pointer-events: none; }
.hdr.is-hero .hdr-nav-link { color: rgba(255, 255, 255, 0.7); }
.hdr.is-hero .hdr-nav-link:hover { color: #fff; background: rgba(255, 255, 255, 0.08); }
.hdr.is-hero .hdr-icon { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.1); color: rgba(255, 255, 255, 0.65); }
.hdr.is-hero .hdr-icon:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.2); color: #fff; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }
.hdr.is-hero .hdr-trigger { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.1); color: rgba(255, 255, 255, 0.8); }
.hdr.is-hero .hdr-trigger:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.2); color: #fff; }
.hdr.is-hero .hdr-avatar { background: linear-gradient(135deg, rgba(34, 211, 238, 0.15), rgba(34, 211, 238, 0.05)); border-color: rgba(34, 211, 238, 0.25); color: #22d3ee; }
.hdr.is-hero .hdr-avatar:hover { border-color: #22d3ee; box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.1), 0 8px 24px rgba(34, 211, 238, 0.15); }
.hdr.is-hero .hdr-btn.ghost { background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.15); color: rgba(255, 255, 255, 0.9); }
.hdr.is-hero .hdr-btn.ghost:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.3); }
.hdr.is-hero .hdr-btn.primary { background: linear-gradient(135deg, #fff, #f0f8fc); color: #153b4f; border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
.hdr.is-hero .hdr-btn.primary:hover { box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15); }
.hdr.is-hero .hdr-hamburger { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.1); }
.hdr.is-hero .hdr-hamburger:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.2); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }
.hdr.is-hero .bar { background: #fff; }

/* =================== LIGHT MODE (inner pages) =================== */
.hdr.is-light { background: #fff; border-bottom: 1px solid rgba(226, 232, 240, 0.6); box-shadow: 0 1px 3px rgba(21, 59, 79, 0.03), 0 4px 16px rgba(21, 59, 79, 0.02); }
.hdr.is-light .hdr-icon { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
.hdr.is-light .hdr-icon:hover { background: #f0f8fc; border-color: rgba(34, 211, 238, 0.4); color: #153b4f; box-shadow: 0 4px 16px rgba(21, 59, 79, 0.06); }
.hdr.is-light .hdr-trigger { background: #f8fafc; border-color: #e2e8f0; color: #334155; }
.hdr.is-light .hdr-trigger:hover { background: #f0f8fc; border-color: #153b4f; color: #153b4f; }
.hdr.is-light .hdr-avatar { background: linear-gradient(135deg, #f0f8fc, #e2e8f0); border-color: #cbd5e1; color: #153b4f; }
.hdr.is-light .hdr-avatar:hover { border-color: #22d3ee; box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.1); }
.hdr.is-light .hdr-btn.ghost { background: transparent; border: 1px solid #e2e8f0; color: #334155; }
.hdr.is-light .hdr-btn.ghost:hover { border-color: #153b4f; color: #153b4f; background: #f8fafc; }
.hdr.is-light .hdr-btn.primary { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; border: none; box-shadow: 0 2px 8px rgba(21, 59, 79, 0.15); }
.hdr.is-light .hdr-btn.primary:hover { box-shadow: 0 8px 24px rgba(21, 59, 79, 0.2); }
.hdr.is-light .hdr-hamburger { background: #f8fafc; border-color: #e2e8f0; }
.hdr.is-light .hdr-hamburger:hover { background: #f0f8fc; border-color: #153b4f; box-shadow: 0 4px 16px rgba(21, 59, 79, 0.06); }
.hdr.is-light .bar { background: #334155; }

/* =================== RIPPLE =================== */
@keyframes ripple { 0% { box-shadow: 0 0 0 0 rgba(46, 167, 173, 0.2); } 70% { box-shadow: 0 0 0 18px rgba(46, 167, 173, 0); } 100% { box-shadow: 0 0 0 0 rgba(46, 167, 173, 0); } }
.ripple-effect { animation: ripple 1.5s infinite; }

/* =================== OFFCANVAS =================== */
.oc-overlay { position: fixed; inset: 0; z-index: 100000; background: rgba(10, 22, 32, 0.55); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); opacity: 0; pointer-events: none; transition: opacity 0.5s var(--ease); }
.oc-overlay.is-open { opacity: 1; pointer-events: auto; }
.oc-panel { position: fixed; top: 0; right: 0; height: 100vh; height: 100dvh; width: min(400px, 88vw); z-index: 100001; background: linear-gradient(180deg, #fff, #fafbfc); box-shadow: -24px 0 80px rgba(10, 22, 32, 0.12); transform: translateX(100%); transition: transform 0.5s var(--ease); display: flex; flex-direction: column; padding-bottom: env(safe-area-inset-bottom); }
.oc-panel.is-open { transform: translateX(0); }

/* OC Head */
.oc-head { display: flex; align-items: center; justify-content: space-between; padding: 24px 28px; border-bottom: 1px solid rgba(226, 232, 240, 0.5); }
.oc-close { width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease); }
.oc-close:hover { background: #f0f8fc; border-color: #153b4f; color: #153b4f; }

/* OC Account */
.oc-account { display: inline-flex; align-items: center; gap: 14px; text-align: left; padding: 6px 10px 6px 6px; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.35); background: #fff; transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease); }
.oc-account:hover { border-color: rgba(46, 167, 173, 0.35); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08); }
.oc-av { width: 48px; height: 48px; border-radius: 14px; overflow: hidden; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; box-shadow: 0 4px 12px rgba(21, 59, 79, 0.2); }
.oc-av img { width: 100%; height: 100%; object-fit: cover; }
.oc-av-meta { display: flex; flex-direction: column; gap: 2px; }
.oc-av-name { font-weight: 700; font-size: 1rem; color: #0f172a; max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.oc-av-role { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; color: #22d3ee; background: rgba(34, 211, 238, 0.08); padding: 2px 8px; border-radius: 6px; width: fit-content; }
.oc-chevron { color: #64748b; margin-left: 4px; transition: transform var(--duration) var(--ease); }
.oc-chevron.is-open { transform: rotate(180deg); }
.oc-account-menu { position: absolute; top: calc(100% + 8px); left: 0; min-width: 180px; padding: 8px; border-radius: 14px; background: #fff; border: 1px solid rgba(148, 163, 184, 0.3); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12); z-index: 10; }
.oc-menu-item { display: block; width: 100%; text-align: left; padding: 10px 12px; border-radius: 10px; font-size: 0.92rem; color: #111827; transition: background var(--duration) var(--ease); }
.oc-menu-item:hover { background: rgba(15, 23, 42, 0.06); }

/* OC Body */
.oc-body { flex: 1; overflow-y: auto; padding: 8px 28px 28px; }
.oc-section { margin-bottom: 8px; }
.oc-label { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.16em; font-weight: 700; margin-bottom: 10px; padding-top: 20px; color: #94a3b8; }
.oc-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-radius: 14px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; color: #334155; background: #fff; border: 1px solid rgba(226, 232, 240, 0.6); margin-bottom: 6px; box-shadow: 0 1px 2px rgba(21, 59, 79, 0.02); transition: border-color var(--duration) var(--ease), color var(--duration) var(--ease), background var(--duration) var(--ease), transform var(--duration) var(--ease), box-shadow var(--duration) var(--ease); }
.oc-item:hover { border-color: #153b4f; color: #153b4f; background: #f0f8fc; transform: translateX(4px); box-shadow: 0 4px 12px rgba(21, 59, 79, 0.04); }
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

/* =================== UTILITIES =================== */
.currency-scrollbar::-webkit-scrollbar { width: 6px; }
.currency-scrollbar::-webkit-scrollbar-track { background: transparent; }
.currency-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
.currency-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
.animated-flag { animation: zoom-fade 1.5s forwards; width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); }
@keyframes zoom-fade { 0% { transform: scale(0.5); opacity: 0; } 50% { transform: scale(1.5); opacity: 1; } 100% { transform: scale(2); opacity: 0; } }
.currency-overlay { position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.32); backdrop-filter: blur(6px); }
.currency-loader { display: inline-flex; align-items: center; gap: 10px; padding: 14px 22px; border-radius: 999px; background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2); }
.currency-loader .dot { width: 10px; height: 10px; border-radius: 999px; background: #f8fafc; animation: currencyDots 1.1s ease-in-out infinite; }
.currency-loader .dot:nth-child(2) { animation-delay: 0.2s; }
.currency-loader .dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes currencyDots { 0%, 100% { transform: translateY(0); opacity: 0.5; } 50% { transform: translateY(-6px); opacity: 1; } }
</style>
