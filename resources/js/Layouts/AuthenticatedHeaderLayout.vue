<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import axios from "axios";
import { useCurrency } from '@/composables/useCurrency';
import { hideTawk, showTawk } from '@/lib/tawk';
import bellIcon from '../../assets/bell.svg'
import whatsappIcon from '../../assets/whatsapp.svg';
import callIcon from '../../assets/call.svg';
import flagEn from '../../assets/flag-en.svg';
import flagFr from '../../assets/flag-fr.svg';
import flagNl from '../../assets/flag-nl.svg';
import flagEs from '../../assets/flag-es.svg';
import flagAr from '../../assets/flag-ar.svg';
import moneyExchangeSymbol from '../../assets/money-exchange-symbol.svg';

import FloatingSocialIcons from '@/Components/FloatingSocialIcons.vue';

// Get page properties
const page = usePage();
const { url, props } = page;
const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();
const showingNavigationDropdown = ref(false);
const showingNotificationDropdown = ref(false);
const animatedFlagUrl = ref(null); // New ref for animated flag URL
const showingAccountDropdown = ref(false);

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
onMounted(() => {
  if (page.props.auth?.user) {
    fetchNotifications();
  }
  fetchContactInfo();

  document.addEventListener('click', closeNotificationDropdownOnOutsideClick);
});

// Clean up event listeners on unmount
onUnmounted(() => {
  document.removeEventListener('click', closeNotificationDropdownOnOutsideClick);
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
    case 'VendorVehicleCreateNotification':
      return route('current-vendor-vehicles.index', { locale });
    case 'BookingCreatedVendorNotification':
      return route('bookings.index', { locale });
    case 'BookingCreatedCustomerNotification':
      return route('profile.bookings.confirmed', { locale });
    case 'VendorStatusUpdatedNotification':
      return route('vendor.status', { locale });
    case 'NewMessageNotification':
      return isVendor.value ? route('messages.vendor.index', { locale }) : route('messages.index', { locale });
    case 'VehicleCreatedNotification':
      return route('current-vendor-vehicles.index', { locale });
    case 'VehicleCreatedNotification':
      return route('current-vendor-vehicles.index', { locale });
    case 'ReviewSubmittedVendorNotification':
      return route('vendor.reviews', { locale });
    case 'BookingCancelledNotification':
      return route('profile.bookings.cancelled', { locale });
    case 'BulkVehicleUploadNotification':
      return route('current-vendor-vehicles.index', { locale });
    case 'PendingBookingReminderNotification':
      return route('bookings.index', { locale });
    case 'BookingStatusUpdatedCustomerNotification':
      const bookingStatus = notification.data.status;
      if (bookingStatus === 'pending') {
        return route('profile.bookings.pending', { locale });
      } else if (bookingStatus === 'confirmed') {
        return route('profile.bookings.confirmed', { locale });
      } else if (bookingStatus === 'completed') {
        return route('profile.bookings.completed', { locale });
      }
      return '#'; // Fallback if status is not recognized
    default:
      return '#'; // Fallback for unknown notification types
  }
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

const changeLanguage = (newLocale) => {
  // Trigger flag animation
  animatedFlagUrl.value = availableLocales[newLocale].flag;

  const animationDuration = 1500; // 1.5 seconds for animation and delay

  setTimeout(() => {
    animatedFlagUrl.value = null; // Hide flag after animation

    const currentUrl = new URL(window.location.href);
    const pathParts = currentUrl.pathname.split('/');

    // Handle page translations
    if (pathParts.length > 2 && pathParts[2] === 'page') {
      const currentSlug = pathParts[3];
      const pages = page.props.pages;
      const currentPage = Object.values(pages).find(p => {
        return p.translations.some(t => t.slug === currentSlug);
      });

      if (currentPage) {
        const newTranslation = currentPage.translations.find(t => t.locale === newLocale);
        if (newTranslation) {
          router.visit(route('pages.show', { locale: newLocale, slug: newTranslation.slug }));
          return;
        }
      }
    }

    // Handle blog post translations
    if (pathParts.length > 3 && pathParts[3] === 'blog' && page.props.blog) {
      const blog = page.props.blog;
      const newTranslation = blog.translations.find(t => t.locale === newLocale);
      if (newTranslation && newTranslation.slug) {
        router.visit(route('blog.show', { locale: newLocale, country: page.props.country, blog: newTranslation.slug }));
        return;
      } else {
        // If no translation, redirect to the blog index page for the new locale
        router.visit(route('blog', { locale: newLocale, country: page.props.country }));
        return;
      }
    }

    // Handle contact page translations
    if (page.props.contactPage) {
      const seoMeta = page.props.seoMeta;
      if (seoMeta && seoMeta.translations) {
        const newTranslation = seoMeta.translations.find(t => t.locale === newLocale);
        if (newTranslation && newTranslation.url_slug) {
          if (newTranslation.url_slug === 'contact-us') {
            router.visit(route('contact-us', { locale: newLocale }));
          } else {
            router.visit(route('contact-us', { locale: newLocale, slug: newTranslation.url_slug }));
          }
          return;
        }
      }
    }

    // Fallback for other pages or if translation not found
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
  }, animationDuration); // Delay navigation until animation is complete
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

watch(() => showingNavigationDropdown.value, (isOpen) => {
  if (!isOpen) showingAccountDropdown.value = false;
  if (isOpen) {
    hideTawk();
  } else {
    showTawk();
  }
});
</script>

<template>
  <header class="border-b border-gray-200 shadow-sm bg-white relative z-40">
    <div class="full-w-container mx-auto">
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
          <div v-if="isAuthenticated" class="relative">
            <button ref="bellIconRef"
              @click="showingNotificationDropdown = !showingNotificationDropdown; markAllAsRead()"
              class="bell-minimal relative"
              :class="{ 'ripple-effect': unreadCount > 0 }">
              <img :src="bellIcon" alt="Notifications" class="w-5 h-5">
              <span v-if="unreadCount > 0"
                class="bell-badge absolute inline-flex items-center justify-center text-xs font-bold leading-none text-white bg-red-600 rounded-full">{{
                  unreadCount }}</span>
            </button>
            <div ref="notificationDropdownRef" v-if="showingNotificationDropdown"
              class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-[99999] top-[3rem]">
              <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-medium">Notifications</h3>
              </div>
              <div class="overflow-y-auto" style="max-height: 400px;">
                <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                  No notifications yet.
                </div>
                <div v-else>
                  <div v-for="notification in notifications" :key="notification.id"
                    @click="handleNotificationClick(notification)" class="p-4 border-b hover:bg-gray-50 cursor-pointer"
                    :class="{ 'bg-gray-100': !notification.read_at }">
                    <div class="flex ">
                      <div class="font-semibold">{{ notification.data.title }}</div>
                      <div class="text-xs text-gray-500">{{ notification.type.split('\\').pop() }}</div>
                    </div>
                    <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
                    <div class="text-xs text-customPrimaryColor mt-1 text-right">{{ new
                      Date(notification.created_at).toLocaleString() }}</div>
                  </div>
                </div>
              </div>
              <div class="p-2 border-t text-center">
                <button @click="clearAllNotifications" class="text-sm text-red-500 hover:underline">Clear all
                  notifications</button>
              </div>
            </div>
          </div>

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
                  <div v-for="currency in supportedCurrencies" :key="currency" @click="changeCurrency(currency)"
                    class="flex min-w-max items-center px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                    :class="{ 'bg-white !text-[#153B4F] font-bold': selectedCurrency === currency }">
                    <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    {{ formatCurrencyDisplay(currency) }}
                  </div>
                </div>
              </template>
            </Dropdown>

            <Dropdown align="right" width="48">
              <template #trigger>
                <button type="button" class="header-icon-trigger is-labeled" aria-label="Change language">
                  <img :src="availableLocales[currentLocale].flag" alt="" class="w-5 h-5 rounded-full">
                  <span class="header-trigger-label">{{ availableLocales[currentLocale].name }}</span>
                </button>
              </template>
              <template #content>
                <div v-for="(language, code) in availableLocales" :key="code" @click="changeLanguage(code)"
                  class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                  :class="{ 'bg-gray-500': currentLocale === code }">
                  <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                  {{ language.name }}
                </div>
              </template>
            </Dropdown>
          </div>

          <button @click="toggleMobileNav" type="button" class="menu-toggle"
            :class="{ 'is-open': showingNavigationDropdown }" aria-controls="offcanvas-menu" aria-expanded="false">
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
          <div v-if="isAuthenticated" class="relative">
            <button type="button" class="account-trigger" @click="showingAccountDropdown = !showingAccountDropdown">
              <div class="account-avatar">
                <img v-if="avatarUrl" :src="avatarUrl" alt="User avatar" />
                <span v-else>{{ userInitials }}</span>
              </div>
              <div class="account-meta">
                <span class="account-name">{{ displayName }}</span>
                <span class="account-role">{{ roleLabel }}</span>
              </div>
              <svg class="account-chevron" :class="{ 'is-open': showingAccountDropdown }" viewBox="0 0 24 24"
                fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 15l6-6 6 6" />
              </svg>
            </button>
            <div v-if="showingAccountDropdown" class="account-menu">
              <Link v-if="!isAdmin" :href="route('profile.edit', { locale: props.locale })"
                class="account-menu-item">Profile</Link>
              <Link :href="route('logout', { locale: props.locale })" method="post" as="button"
                class="account-menu-item">Log Out</Link>
            </div>
          </div>
          <Link v-else :href="route('welcome', { locale: page.props.locale })" class="w-28">
            <ApplicationLogo class="w-full h-auto" />
          </Link>
          <button type="button" class="offcanvas-close" @click="showingNavigationDropdown = false">
            <span class="sr-only">Close menu</span>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5">
          <div class="flex min-h-full flex-col gap-6">
            <div class="space-y-3">
              <div class="text-xs uppercase tracking-widest text-gray-400">Account</div>
              <div class="space-y-2">
                <Link v-if="isAdmin" :href="route('admin.dashboard')"
                  class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                  Admin Dashboard
                </Link>


                <Link v-if="isVendor"
                  :href="vendorStatus === 'approved' ? route('vehicles.create', { locale: props.locale }) : route('vendor.status', { locale: props.locale })"
                  class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                  <span v-if="vendorStatus === 'approved'">Create Listing</span>
                  <span v-else>Complete Verification</span>
                </Link>

                <Link v-if="isCustomer" :href="route('vendor.register', { locale: props.locale })"
                  class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-800 hover:border-customPrimaryColor hover:text-customPrimaryColor">
                  Register as Vendor
                </Link>

                <div v-if="!isAuthenticated" class="flex flex-nowrap gap-2">
                  <Link :href="route('login', { locale: props.locale })"
                    class="flex-1 min-w-[140px] flex items-center justify-between rounded-lg border border-customPrimaryColor bg-customPrimaryColor px-4 py-3 text-sm font-medium text-white hover:bg-[#153b4fef]">
                    Log in
                  </Link>

                  <Link :href="route('register', { locale: props.locale })"
                    class="flex-1 min-w-[140px] flex items-center justify-between rounded-lg border border-customPrimaryColor px-4 py-3 text-sm font-medium text-customPrimaryColor hover:bg-blue-50">
                    Create Account
                  </Link>
                </div>

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
                      <img :src="moneyExchangeSymbol" alt="Currency" class="w-5 h-5 mr-2"
                        :class="{ 'opacity-60': currencyLoading }">
                      {{ formatCurrencyTriggerDisplay(selectedCurrency) }}
                    </span>
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                </template>
                <template #content>
                  <div class="max-h-64 overflow-y-auto currency-scrollbar">
                    <div v-for="currency in supportedCurrencies" :key="currency" @click="changeCurrency(currency)"
                      class="flex min-w-max items-center px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                      :class="{ 'bg-white !text-[#153B4F] font-bold': selectedCurrency === currency }">
                      <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
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
                        :alt="availableLocales[currentLocale].name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                      {{ availableLocales[currentLocale].name }}
                    </span>
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                </template>
                <template #content>
                  <div v-for="(language, code) in availableLocales" :key="code" @click="changeLanguage(code)"
                    class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                    :class="{ 'bg-gray-500': currentLocale === code }">
                    <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                    {{ language.name }}
                  </div>
                </template>
              </Dropdown>
            </div>

            <div class="space-y-3">
              <div class="text-xs uppercase tracking-widest text-gray-400">Pages</div>
              <div class="space-y-2">
                <Link :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('about-us') })"
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

          </div>
        </div>
        <div v-if="whatsappLink || callLink" class="offcanvas-footer">
          <div class="text-xs uppercase tracking-widest text-gray-400">Contact</div>
          <div class="flex flex-nowrap gap-2">
            <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
              class="flex-1 min-w-0 flex items-center gap-3 rounded-lg border border-emerald-500 bg-emerald-500 px-3 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:border-emerald-600 hover:bg-emerald-600">
              <img :src="whatsappIcon" alt="WhatsApp" class="w-5 h-5 filter brightness-0 invert">
              Chat on WhatsApp
            </a>
            <a v-if="callLink" :href="callLink"
              class="flex-1 min-w-0 flex items-center gap-3 rounded-lg border border-customPrimaryColor bg-customPrimaryColor px-3 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:border-[#153b4fef] hover:bg-[#153b4fef]">
              <img :src="callIcon" alt="Call" class="w-5 h-5 filter brightness-0 invert">
              Call Now
            </a>
          </div>
        </div>
      </div>
    </aside>

    <div v-if="animatedFlagUrl" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
      <img :src="animatedFlagUrl" alt="Flag" class="animated-flag" />
    </div>

    <div v-if="currencyLoading" class="currency-overlay">
      <div class="currency-loader">
        <span class="dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
      </div>
    </div>

    <FloatingSocialIcons />
  </header>
</template>

<style scoped>
/* Smooth transitions for all interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Responsive adjustments */
@media (max-width: 640px) {

  .button-primary,
  .button-secondary {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }
}

/* Ripple effect animation */
@keyframes ripple {
  0% {
    box-shadow: 0 0 0 0 rgba(46, 167, 173, 0.2);
  }

  70% {
    box-shadow: 0 0 0 18px rgba(46, 167, 173, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(46, 167, 173, 0);
  }
}

.ripple-effect {
  animation: ripple 1.5s infinite;
}

.bell-minimal {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: transparent;
  border: 1px solid transparent;
  color: #334155;
  transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
}

.bell-minimal:hover {
  background: rgba(15, 23, 42, 0.04);
  border-color: rgba(148, 163, 184, 0.6);
  color: #0f172a;
}

.bell-minimal:focus-visible {
  outline: 2px solid rgba(46, 167, 173, 0.5);
  outline-offset: 2px;
}

.bell-badge {
  top: 6px;
  right: 6px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border: 2px solid #ffffff;
  font-size: 0.65rem;
  box-shadow: 0 6px 12px rgba(239, 68, 68, 0.2);
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


/* Flag animation styles */
.animated-flag {
  animation: zoom-fade 1.5s forwards;
  /* Set animation duration to 1.5 seconds */
  width: 100px;
  /* Initial size */
  height: 100px;
  border-radius: 50%;
  /* Make it circular */
  object-fit: cover;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
}

@keyframes zoom-fade {
  0% {
    transform: scale(0.5);
    opacity: 0;
  }

  50% {
    transform: scale(1.5);
    opacity: 1;
  }

  100% {
    transform: scale(2);
    opacity: 0;
  }
}

.currency-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.32);
  backdrop-filter: blur(6px);
}

.currency-loader {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 22px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
}

.currency-loader .dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #f8fafc;
  animation: currencyDots 1.1s ease-in-out infinite;
}

.currency-loader .dot:nth-child(2) {
  animation-delay: 0.2s;
}

.currency-loader .dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes currencyDots {

  0%,
  100% {
    transform: translateY(0);
    opacity: 0.5;
  }

  50% {
    transform: translateY(-6px);
    opacity: 1;
  }
}

.offcanvas-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 240ms ease;
    z-index: 100000;
    height: 100vh;
    height: 100dvh;
    width: 100vw;
    padding-bottom: env(safe-area-inset-bottom);
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
    height: 100dvh;
    width: 80vw;
    max-width: 420px;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
    transform: translateX(100%);
    transition: transform 320ms ease;
    z-index: 100001;
    padding-bottom: env(safe-area-inset-bottom);
}

.offcanvas-footer {
  border-top: 1px solid rgba(148, 163, 184, 0.4);
  padding: 1.5rem 1.5rem calc(1.5rem + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  background: #ffffff;
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

.account-trigger {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  padding: 6px 10px 6px 6px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: #ffffff;
  transition: border-color 160ms ease, box-shadow 160ms ease;
}

.account-trigger:hover {
  border-color: rgba(46, 167, 173, 0.35);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.account-avatar {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  overflow: hidden;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.08);
  color: #1f2937;
  font-weight: 600;
  font-size: 0.95rem;
}

.account-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.account-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.account-name {
  font-weight: 600;
  color: #111827;
  font-size: 0.95rem;
  max-width: 160px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.account-role {
  font-size: 0.78rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.account-chevron {
  width: 18px;
  height: 18px;
  color: #64748b;
  margin-left: 4px;
  transition: transform 160ms ease;
}

.account-chevron.is-open {
  transform: rotate(180deg);
}

.account-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  min-width: 180px;
  padding: 8px;
  border-radius: 12px;
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.3);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
  z-index: 10;
}

.account-menu-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 0.92rem;
  color: #111827;
  transition: background 150ms ease, color 150ms ease;
}

.account-menu-item:hover {
  background: rgba(15, 23, 42, 0.06);
  color: #0f172a;
}

@media (min-width: 640px) {
  .offcanvas-panel {
    width: 380px;
  }
}
</style>
