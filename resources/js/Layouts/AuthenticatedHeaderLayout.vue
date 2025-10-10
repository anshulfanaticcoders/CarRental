<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import axios from "axios";
import { useCurrency } from '@/composables/useCurrency';
import globeIcon from '../../assets/globe.svg'
import bellIcon from '../../assets/bell.svg'
import flagEn from '../../assets/flag-en.svg';
import flagFr from '../../assets/flag-fr.svg';
import flagNl from '../../assets/flag-nl.svg';
import flagEs from '../../assets/flag-es.svg';
import flagAr from '../../assets/flag-ar.svg';
import moneyExchangeSymbol from '../../assets/money-exchange-symbol.svg';
import loaderVariant from '../../assets/loader-variant.svg'; // Import loader for potential use

// Get page properties
const page = usePage();
const { url, props } = page;
const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();
const showingNavigationDropdown = ref(false);
const showingNotificationDropdown = ref(false);
const mobileNotificationsOpen = ref(false);
const animatedFlagUrl = ref(null); // New ref for animated flag URL

// User data
const user = ref(null);

// Function to toggle mobile notification
const toggleMobileNotification = () => {
  mobileNotificationsOpen.value = !mobileNotificationsOpen.value;
};

// Notifications
const notifications = ref([]);
const unreadCount = ref(0);

// Refs for dropdowns
const navRef = ref(null);
const notificationDropdownRef = ref(null);
const bellIconRef = ref(null);

// Close mobile menu when clicking outside
const closeNavOnOutsideClick = (event) => {
  if (showingNavigationDropdown.value && navRef.value && !navRef.value.contains(event.target)) {
    showingNavigationDropdown.value = false;
  }
};

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
    fetchUserProfile();
    fetchNotifications();
  }
  
  document.addEventListener('click', closeNavOnOutsideClick);
  document.addEventListener('click', closeNotificationDropdownOnOutsideClick);
});

// Clean up event listeners on unmount
onUnmounted(() => {
  document.removeEventListener('click', closeNavOnOutsideClick);
  document.removeEventListener('click', closeNotificationDropdownOnOutsideClick);
});

// Fetch user profile data
const fetchUserProfile = async () => {
  try {
    const response = await axios.get(route('user.profile'));
    if (response.data.status === "success") {
      user.value = response.data.data;
    } else {
      console.error("Failed to fetch user:", response.data.message);
    }
  } catch (error) {
    console.error("Error fetching user:", error);
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
        if (pathParts.length > 2 && pathParts[2] === 'blog' && page.props.blog) {
            const blog = page.props.blog;
            const newTranslation = blog.translations.find(t => t.locale === newLocale);
            if (newTranslation && newTranslation.slug) {
                router.visit(route('blog.show', { locale: newLocale, blog: newTranslation.slug }));
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

// Function to toggle mobile navigation
const toggleMobileNav = () => {
  showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

// Watch for route changes to close mobile menu
watch(() => url.value, () => {
  showingNavigationDropdown.value = false;
});
</script>

<template>
  <header class="border-b border-gray-200 shadow-sm bg-white" ref="navRef">
    <div class="full-w-container mx-auto">
      <div class="flex justify-between items-center h-16 md:h-20">
        <!-- Logo Section -->
        <div class="flex-shrink-0">
          <Link :href="route('welcome', { locale: props.locale })" class="block w-32 md:w-40 transition-transform hover:opacity-80">
            <ApplicationLogo class="w-full h-auto" />
          </Link>
        </div>

        <!-- Desktop Navigation (only for authenticated users) -->
        <div v-if="isAuthenticated" class="hidden md:flex md:items-center md:space-x-6">
          <!-- Vendor/Customer Action Button -->
          <div v-if="isVendor">
            <Link 
              :href="vendorStatus === 'approved' ? route('vehicles.create', { locale: props.locale }) : route('vendor.status', { locale: props.locale })" 
              class="button-secondary inline-flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 hover:shadow-md"
            >
              <span v-if="vendorStatus === 'approved'">{{ _t('header', 'create_listing') }}</span>
              <span v-else>{{ _t('header', 'complete_verification') }}</span>
            </Link>
          </div>

          <div v-else-if="isCustomer">
            <Link 
              :href="route('vendor.register', { locale: props.locale })" 
              class="button-secondary inline-flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 hover:shadow-md"
            >
              {{ _t('header', 'register_as_vendor') }}
            </Link>
          </div>

          <!-- Currency Switcher -->
          <div class="relative bg-[#efefef] hover:bg-[#d6d6d6] focus:bg-[#d6d6d6] rounded-full">
            <div v-if="currencyLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-50">
                <img :src="loaderVariant" alt="Loading..." class="w-6 h-6" />
            </div>
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
              <div class="max-h-80 overflow-y-auto currency-scrollbar">
                <div
                  v-for="currency in supportedCurrencies"
                  :key="currency"
                  @click="changeCurrency(currency)"
                  class="flex min-w-max items-center px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                  :class="{ 'bg-white !text-[#153B4F] font-bold': selectedCurrency === currency }"
                >
                  <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                  {{ formatCurrencyDisplay(currency) }}
                </div>
              </div>
            </template>
            </Dropdown>
          </div>

          <!-- Language Switcher -->
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

          <!-- Notification Bell -->
            <div class="relative">
                <button ref="bellIconRef" @click="showingNotificationDropdown = !showingNotificationDropdown; markAllAsRead()" class="relative p-2 rounded-[99px] bg-[#efefef] focus:bg-[#d6d6d6]" :class="{ 'ripple-effect': unreadCount > 0 }">
                    <img :src="bellIcon" alt="Notifications" class="w-6 h-6 ml-[3px]">
                    <span v-if="unreadCount > 0" class="absolute w-[18px] h-[18px] border-2 border-white top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ unreadCount }}</span>
                </button>
                <div ref="notificationDropdownRef" v-if="showingNotificationDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-20 top-[3rem]">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h3 class="text-lg font-medium">Notifications</h3>
                    </div>
                    <div class="overflow-y-auto" style="max-height: 400px;">
                        <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                            No notifications yet.
                        </div>
                        <div v-else>
                            <div v-for="notification in notifications" :key="notification.id" @click="handleNotificationClick(notification)"
                                class="p-4 border-b hover:bg-gray-50 cursor-pointer"
                                :class="{ 'bg-gray-100': !notification.read_at }">
                                <div class="flex ">
                                    <div class="font-semibold">{{ notification.data.title }}</div>
                                    <div class="text-xs text-gray-500">{{ notification.type.split('\\').pop() }}</div>
                                </div>
                                <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
                                <div class="text-xs text-customPrimaryColor mt-1 text-right">{{ new Date(notification.created_at).toLocaleString() }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 border-t text-center">
                        <button @click="clearAllNotifications" class="text-sm text-red-500 hover:underline">Clear all notifications</button>
                    </div>
                </div>
            </div>
          
          <!-- User Profile Dropdown -->
          <div class="relative ml-3">
            <Dropdown align="right" width="48">
              <template #trigger>
                <button 
                  type="button"
                  class="inline-flex items-center gap-2 py-2 border border-transparent text-sm font-medium rounded-full bg-[#efefef] hover:bg-[#d6d6d6] focus:bg-[#d6d6d6] p-4 transition duration-150 ease-in-out group"
                >
                  <div v-if="user?.profile?.avatar" class="flex-shrink-0 relative">
                    <img 
                      :src="user.profile.avatar || '/storage/avatars/default-avatar.svg'"
                      alt="User Avatar" 
                      class="w-8 h-8 rounded-full object-cover ring-2 ring-white"
                    />
                  </div>
                  <div v-else class="px-3 py-1">
                    {{ page.props.auth.user.first_name }}
                  </div>
                  <svg class="h-4 w-4 text-gray-400 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
              </template>

              <template #content>
                <DropdownLink v-if="isAdmin" :href="route('admin.dashboard')" class="flex items-center">
                  <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                  </svg>
                  {{ _t('header', 'dashboard') }}
                </DropdownLink>
                
                <DropdownLink v-else :href="route('profile.edit', { locale: props.locale })" class="flex items-center">
                  <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  {{ _t('header', 'profile') }}
                </DropdownLink>
                
                <DropdownLink :href="route('logout', { locale: props.locale })" method="post" as="button" class="flex items-center w-full text-left">
                  <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                  </svg>
                  {{ _t('header', 'log_out') }}
                </DropdownLink>
              </template>
            </Dropdown>
          </div>
        </div>

        <!-- Guest Navigation (Desktop) -->
        <div v-else class="hidden md:flex md:items-center md:space-x-6">
          <Link 
            :href="route('login', { locale: props.locale })" 
            class="button-primary py-2 px-4 text-sm font-medium rounded-md transition-all duration-200 hover:shadow-md"
          >
            {{ _t('header', 'log_in') }}
          </Link>
          
          <Link 
            :href="route('register', { locale: props.locale })" 
            class="button-secondary py-2 px-4 text-sm font-medium rounded-md transition-all duration-200 hover:shadow-md"
          >
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
                  class="flex min-w-max items-center px-4 py-2 text-left text-sm leading-5 text-white hover:text-white hover:bg-gray-600 transition duration-150 ease-in-out cursor-pointer"
                  :class="{ 'bg-white !text-[#153B4F] font-bold': selectedCurrency === currency }"
                >
                  <span v-if="selectedCurrency === currency" class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                  {{ formatCurrencyDisplay(currency) }}
                </div>
              </div>
            </template>
            </Dropdown>
          </div>
          
          <!-- Language Switcher for Guests -->
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
          <button 
            @click="toggleMobileNav" 
            type="button" 
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition duration-150 ease-in-out"
            aria-controls="mobile-menu" 
            aria-expanded="false"
          >
            <span class="sr-only">{{ showingNavigationDropdown ? 'Close menu' : 'Open menu' }}</span>
            <svg 
              class="h-6 w-6" 
              :class="{ 'hidden': showingNavigationDropdown, 'block': !showingNavigationDropdown }"
              stroke="currentColor" 
              fill="none" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg 
              class="h-6 w-6" 
              :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
              stroke="currentColor" 
              fill="none" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu, show/hide based on mobile menu state -->
    <div 
      id="mobile-menu" 
      :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
      class="md:hidden"
    >
      <div class="pt-2 pb-4 space-y-1 border-t border-gray-200 bg-gray-50">
        <!-- Authenticated User Mobile Menu -->
        <div v-if="isAuthenticated" class="px-4 py-3">
          <div class="flex items-center">
            <div v-if="user?.profile?.avatar" class="flex-shrink-0">
              <img 
                :src="user.profile.avatar || '/storage/avatars/default-avatar.svg'"
                alt="User Avatar" 
                class="h-10 w-10 rounded-full object-cover"
              />
            </div>
            <div class="ml-3">
              <div class="text-base font-medium text-gray-800">{{ page.props.auth.user.first_name }}</div>
              <div class="text-sm font-medium text-gray-500">{{ page.props.auth.user.email }}</div>
            </div>
            <div class="ml-auto">
              <button @click="toggleMobileNotification(); markAllAsRead();" class="relative p-2 rounded-[99px] focus:bg-[#efefef]" :class="{ 'ripple-effect': unreadCount > 0 }">
                <img :src="bellIcon" alt="Notifications" class="w-6 h-6">
                <span v-if="unreadCount > 0" class="absolute w-[18px] h-[18px] border-2 border-white top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ unreadCount }}</span>
              </button>
            </div>
          </div>
          
          <div class="mt-4 space-y-2">
            <!-- Admin Dashboard Link -->
            <ResponsiveNavLink v-if="isAdmin" :href="route('admin.dashboard')" class="flex items-center">
              <svg class="mr-3 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              Dashboard
            </ResponsiveNavLink>
            
            <!-- Profile Link -->
            <ResponsiveNavLink v-else :href="route('profile.edit', { locale: props.locale })" class="flex items-center">
              <svg class="mr-3 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              Profile
            </ResponsiveNavLink>
            
            <!-- Vendor-specific actions -->
            <ResponsiveNavLink 
              v-if="isVendor"
              :href="vendorStatus === 'approved' ? route('vehicles.create', { locale: props.locale }) : route('vendor.status', { locale: props.locale })" 
              class="flex items-center"
            >
              <svg class="mr-3 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              {{ vendorStatus === 'approved' ? 'Create a Listing' : 'Complete Verification' }}
            </ResponsiveNavLink>
            
            <!-- Customer-specific actions -->
            <ResponsiveNavLink 
              v-if="isCustomer"
              :href="route('vendor.register', { locale: props.locale })" 
              class="flex items-center"
            >
              <svg class="mr-3 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 21H5v-6l2.257-2.257A6 6 0 0119 9z"></path>
              </svg>
              Register as Vendor
            </ResponsiveNavLink>
            
            <!-- Language Options -->
            <div class="mt-3 px-4 py-2 border-t border-gray-200">
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
            
            <!-- Logout Button -->
     
              <ResponsiveNavLink :href="route('logout', { locale: props.locale })" method="post" as="button"  class="flex items-center w-full">
                <svg class="mr-3 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Log Out
              </ResponsiveNavLink>
        
          </div>
        </div>
        
        <!-- Guest User Mobile Menu -->
        <div v-else class="px-4 py-3 space-y-3">
          <Link 
            :href="route('login', { locale: props.locale })" 
            class="block w-full py-2 px-4 text-center font-medium text-white bg-customPrimaryColor hover:bg-[#153b4fef] rounded-md transition duration-150 ease-in-out"
          >
            Log in
          </Link>
          
          <Link 
            :href="route('register', { locale: props.locale })" 
            class="block w-full py-2 px-4 text-center font-medium text-blue-600 bg-white border border-customPrimaryColor hover:bg-blue-50 rounded-md transition duration-150 ease-in-out"
          >
            Create an Account
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
    
    <!-- Animated Flag Overlay -->
    <div v-if="animatedFlagUrl" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
      <img :src="animatedFlagUrl" alt="Flag" class="animated-flag" />
    </div>

    <!-- Full-screen loader for currency change -->
    <div v-if="currencyLoading" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
        <img :src="moneyExchangeSymbol" alt="Loading..." class="w-16 h-16 animate-spin" />
    </div>

    <!-- Full-screen notification component -->
    <div v-if="mobileNotificationsOpen" class="fixed top-0 left-0 w-full h-full bg-white z-50">
      <div class="flex justify-end p-4">
        <button @click="toggleMobileNotification" class="text-gray-500 hover:text-gray-700">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div class="p-4 border-b flex justify-between items-center">
        <h3 class="text-lg font-medium">Notifications</h3>
      </div>
      <div class="overflow-y-auto" style="max-height: calc(100vh - 100px);">
        <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
          No notifications yet.
        </div>
        <div v-else>
          <div v-for="notification in notifications" :key="notification.id" @click="handleNotificationClick(notification)"
            class="p-4 border-b hover:bg-gray-50 cursor-pointer"
            :class="{ 'bg-gray-100': !notification.read_at }">
            <div class="flex ">
              <div class="font-semibold">{{ notification.data.title }}</div>
              <div class="text-xs text-gray-500">{{ notification.type.split('\\').pop() }}</div>
            </div>
            <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
            <div class="text-xs text-customPrimaryColor mt-1 text-right">{{ new Date(notification.created_at).toLocaleString() }}</div>
          </div>
        </div>
      </div>
      <div class="p-2 border-t text-center">
        <button @click="clearAllNotifications" class="text-sm text-red-500 hover:underline">Clear all notifications</button>
      </div>
    </div>
  </header>
</template>

<style scoped>
/* Smooth transitions for all interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Mobile menu transition */
#mobile-menu {
  transition: max-height 0.3s ease-in-out;
  overflow: hidden;
}

/* Ensure header stays on top */
header {
  z-index: 50;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .button-primary, .button-secondary {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }
}

/* Mobile notification bell */
.md\\:hidden .ml-auto {
  display: block !important;
}

/* Full-screen notification styles */
.fixed.top-0.left-0.w-full.h-full.bg-white.z-50 {
  display: flex;
  flex-direction: column;
}

/* Ripple effect animation */
@keyframes ripple {
  0% {
    box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.4); /* Darker ripple, slightly less opaque */
  }
  70% {
    box-shadow: 0 0 0 20px rgba(0, 0, 0, 0); /* Increased spread */
  }
  100% {
    box-shadow: 0 0 0 0 rgba(0, 0, 0, 0);
  }
}

.ripple-effect {
  animation: ripple 1.5s infinite;
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
  animation: zoom-fade 1.5s forwards; /* Set animation duration to 1.5 seconds */
  width: 100px; /* Initial size */
  height: 100px;
  border-radius: 50%; /* Make it circular */
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
</style>
