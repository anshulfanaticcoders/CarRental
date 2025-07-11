<script setup>
import { ref, computed, onMounted, watch, inject, getCurrentInstance } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import chevronIcon from "../../assets/chaveronDown.svg";
import profileIcon from "../../assets/userDashIcon.svg";
import bookingsIcon from "../../assets/bookingIcon.svg";
import inboxIcon from "../../assets/inboxIcon.svg";
import favoritesIcon from "../../assets/favouriteIcon.svg";
import reviewsIcon from "../../assets/myreviewIcon.svg";
import dashboardIcon from "../../assets/vendorDashboarIcon.svg";
import vehiclesIcon from "../../assets/vehicletypeIcon.svg";
import clockIcon from "../../assets/clockIcon.svg";
import dateblockingIcon from "../../assets/dateblockingIcon.svg";
import logoutIcon from '../../assets/logoutIcon.svg';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog'

// Get the states from parent component
const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const isCollapsed = inject('isSidebarCollapsed', ref(false));
const isMobileMenuOpen = inject('isMobileMenuOpen', ref(false));
const isMobile = inject('isMobile', ref(false));

const toggleSidebar = inject('toggleSidebar', () => {
  isCollapsed.value = !isCollapsed.value;
  // Emit the event for the parent layout
  emit('toggle-sidebar');
});

const toggleMobileMenu = inject('toggleMobileMenu', () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
  // Emit the event for the parent layout
  emit('toggle-mobile-menu');
});

// Define emits
const emit = defineEmits(['toggle-sidebar', 'toggle-mobile-menu']);

// Existing logic
const user = ref(null);
const booking = ref(null);
const payment = ref(null);
const vehicle = ref(null);
const error = ref(null);
const userId = usePage().props.auth.user.id;

onMounted(async () => {
  const paymentIntentId = usePage().props.payment_intent;

  if (paymentIntentId) {
    try {
      const response = await axios.get(
        `/api/booking-success/details?payment_intent=${paymentIntentId}`
      );

      booking.value = response.data.booking;
      payment.value = response.data.payment;
      vehicle.value = response.data.vehicle;
    } catch (err) {
      error.value =
        "There was an error fetching the booking details. Please try again later.";
      console.error("Error fetching booking details:", err);
    }
  } else {
    error.value = "Payment Intent ID is missing from the URL.";
  }
});

const activeMenu = ref(null);
const activeSubmenu = ref(null);
const activeLink = ref(null);

// Determine user role from props
const userRole = usePage().props.auth.user.role;

// Menus for "customer" (existing logic)
const menus = [
  {
    title: _t('customerprofile','my_profile'),
    key: "profile",
    icon: profileIcon,
    items: [
      { name: _t('customerprofile','profile'), path: route('profile.edit', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','travel_documents'), path: route('user.documents.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','issued_payments'), path: route('profile.payments', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','register_as_vendor'), path: route('vendor.register', { locale: usePage().props.locale }) },
    ],
  },
  {
    title: _t('customerprofile','my_bookings'),
    key: "bookings",
    icon: bookingsIcon,
    items: [
      { name: _t('customerprofile','confirmed'), path: route('profile.bookings.confirmed', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','pending'), path: route('profile.bookings.pending', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','completed'), path: route('profile.bookings.completed', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','cancelled'), path: route('profile.bookings.cancelled', { locale: usePage().props.locale }) },
    ],
  },
];

const otherLinks = [
  { name: _t('customerprofile','inbox'), path: route('messages.index', { locale: usePage().props.locale }), icon: inboxIcon },
  { name: _t('customerprofile','favorites'), path: route('profile.favourites', { locale: usePage().props.locale }), icon: favoritesIcon },
  { name: _t('customerprofile','my_reviews'), path: route('profile.reviews', { locale: usePage().props.locale }), icon: reviewsIcon },
];

// Additional menus for "vendor"
const vendorMenus = [
  {
    title: _t('customerprofile','dashboard'),
    key: "dashboard",
    icon: dashboardIcon,
    items: [
      { name: _t('customerprofile','profile'), path: route('profile.edit', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','overview'), path: route('vendor.overview', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','documents'), path: route('vendor.documents.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','verification_status'), path: route('vendor.status', { locale: usePage().props.locale }) },
    ],
  },
  {
    title: _t('customerprofile','vehicles'),
    key: "vehicles",
    icon: vehiclesIcon,
    items: [
      { name: _t('customerprofile','all_vehicles'), path: route('current-vendor-vehicles.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','add_new_vehicle'), path: route('vehicles.create', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','create_bulk_listing'), path: route('vehicles.bulk-upload.create', { locale: usePage().props.locale }) },
      { name: _t('customerprofile','manage_plans'), path: route('VendorPlanIndex', { locale: usePage().props.locale }) },
    ],
  },
];

const vendorOtherLinks = [
  { name: _t('customerprofile','payment_history'), path: route('vendor.payments', { locale: usePage().props.locale }), icon: clockIcon },
  { name: _t('customerprofile','bookings'), path: route('bookings.index', { locale: usePage().props.locale }), icon: vehiclesIcon },
  { name: _t('customerprofile','date_blocking'), path: route('vendor.blocking-dates.index', { locale: usePage().props.locale }), icon: dateblockingIcon },
  { name: _t('customerprofile','inbox'), path: route('messages.vendor.index', { locale: usePage().props.locale }), icon: inboxIcon },
  { name: _t('customerprofile','customer_reviews'), path: route('vendor.reviews', { locale: usePage().props.locale }), icon: reviewsIcon },
];

// Active menus based on role
const activeMenus = userRole === "vendor" ? vendorMenus : menus;
const activeOtherLinks = userRole === "vendor" ? vendorOtherLinks : otherLinks;

const toggleMenu = (menuKey) => {
  activeMenu.value = activeMenu.value === menuKey ? null : menuKey;
};

const setActiveSubmenu = (submenu) => {
  activeSubmenu.value = submenu;
};

const greetingMessage = computed(() => {
  const hours = new Date().getHours();
  if (hours < 12) return _t('customerprofile','greeting_morning');
  if (hours < 18) return _t('customerprofile','greeting_afternoon');
  return _t('customerprofile','greeting_evening');
});

const setActiveLinkFromRoute = () => {
  const currentPath = usePage().url.split("?")[0];
  const foundLink = activeOtherLinks.find((link) => {
    try {
      return new URL(link.path).pathname === currentPath;
    } catch (e) {
      return link.path === currentPath;
    }
  });

  if (foundLink) {
    activeLink.value = foundLink.name;
  } else {
    activeLink.value = null;
  }
};

const setActiveSubmenuFromRoute = () => {
  const currentPath = usePage().url.split("?")[0];
  let wasFound = false;
  activeMenus.forEach((menu) => {
    const foundItem = menu.items.find((item) => {
      try {
        return new URL(item.path).pathname === currentPath;
      } catch (e) {
        return item.path === currentPath;
      }
    });
    if (foundItem) {
      activeMenu.value = menu.key;
      activeSubmenu.value = foundItem.name;
      wasFound = true;
    }
  });
  if (!wasFound) {
    activeSubmenu.value = null;
  }
};

onMounted(() => {
  setActiveLinkFromRoute();
  setActiveSubmenuFromRoute();
});

watch(() => usePage().url, () => {
    setActiveLinkFromRoute();
    setActiveSubmenuFromRoute();
});

import axios from "axios";

const fetchUserProfile = async () => {
  try {
    // Make the request to fetch the current user's profile
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

onMounted(fetchUserProfile);

// Function to close mobile menu when clicking a link (for mobile view)
const handleLinkClick = (name) => {
  if (isMobile.value) {
    toggleMobileMenu();
  }
  activeLink.value = name;
};

// Function to handle submenu item click
const handleSubmenuClick = (name) => {
  if (isMobile.value) {
    toggleMobileMenu();
  }
  setActiveSubmenu(name);
};


const profileCompletion = ref(0);

const fetchProfileCompletion = async () => {
    try {
        const response = await fetch(route('profile.completion', { locale: usePage().props.locale }));
        const data = await response.json();
        profileCompletion.value = data.percentage;
    } catch (error) {
        console.error('Error fetching profile completion:', error);
    }
};

onMounted(fetchProfileCompletion);
const showProfileAlert = computed(() => profileCompletion.value < 90);
</script>

<template>
  <div class="sidebar-inner">
    <!-- Mobile close button -->
    <div v-if="isMobile && isMobileMenuOpen" class="mobile-close-btn flex justify-end py-4 px-4 absolute top-0 right-0">
      <button @click="toggleMobileMenu" class="bg-[#153b4f] text-white p-2 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    
    <!-- Collapse toggle button (desktop only) -->
    <div v-if="!isMobile" class="flex justify-end py-4 pr-4">
      <button @click="toggleSidebar" class="collapse-toggle" :class="{ 'toggle-collapsed': isCollapsed }"
        title="collapse menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6" v-if="!isCollapsed"></polyline>
          <polyline points="9 18 15 12 9 6" v-else></polyline>
        </svg>
      </button>
    </div>

    <div :class="{ 'collapsed': isCollapsed && !isMobile }">
      <div class="user-info p-4 w-full" v-if="!isCollapsed || isMobile">
        <div class="flex items-center space-x-3">
          <img :src="user?.profile.avatar || '/storage/avatars/default-avatar.svg'" alt="User Avatar"
            class="w-10 h-10 rounded-full object-cover" />
          <div>
            <p class="text-sm text-gray-500">
              Hello, {{ greetingMessage }}
            </p>
            <p class="text-lg font-semibold text-gray-800">
              {{ user?.first_name }} {{ user?.last_name }}
            </p>
            <p class="capitalize text-md text-[#009900]">
              {{ user?.role }}
            </p>
          </div>
        </div>
      </div>

      <!-- User avatar only when collapsed (desktop) -->
      <div class="collapsed-avatar mt-4 flex justify-center" v-if="isCollapsed && !isMobile">
        <img :src="user?.profile.avatar || '/storage/avatars/default-avatar.svg'" alt="User Avatar"
          class="w-10 h-10 rounded-full object-cover" />
      </div>

      <!-- Dynamic Menus -->
      <div v-for="menu in activeMenus" :key="menu.key" class="menu-item flex flex-col gap-2">
        <button class="menu-header" 
          :class="{ 
            active: activeMenu === menu.key, 
            'collapsed-menu': isCollapsed && !isMobile,
            'justify-between': !isCollapsed || isMobile
          }"
          @click="toggleMenu(menu.key)">
          <div class="flex gap-2 items-center">
            <img :src="menu.icon" alt="" class="icon-button active"
              :class="{ 'brightness-active': activeMenu === menu.key }" />
            <span v-if="!isCollapsed || isMobile">{{ menu.title }}</span>
          </div>
          <img v-if="(!isCollapsed || isMobile)" class="chevron" :class="{ rotated: activeMenu === menu.key }" :src="chevronIcon" alt="" />
        </button>
        <ul v-if="activeMenu === menu.key && (!isCollapsed || isMobile)" class="submenu">
          <li v-for="item in menu.items" :key="item.name" :class="{ 'submenu-active': activeSubmenu === item.name }">
            <Link :href="item.path" class="submenu-link flex items-center gap-2" @click="handleSubmenuClick(item.name)">
              {{ item.name }}
            </Link>
          </li>
        </ul>
      </div>

      <!-- Other Links -->
      <div v-for="link in activeOtherLinks" :key="link.name" class="menu-item">
        <Link :href="link.path" class="menu-link flex items-center gap-2"
          :class="{ 
            active: activeLink === link.name, 
            'collapsed-menu': isCollapsed && !isMobile,
            'justify-center': isCollapsed && !isMobile
          }" 
          @click="handleLinkClick(link.name)">
          <img :src="link.icon" alt="" class="icon w-[24px] h-[24px]"
            :class="{ 'brightness-active': activeLink === link.name }" />
          <span v-if="!isCollapsed || isMobile">{{ link.name }}</span>
        </Link>
      </div>

      <!-- Logout Button -->
      <Link :href="route('logout')" method="post" as="button" 
        class="text-[#EE1D52] flex items-center gap-1 mt-[4rem] w-full pb-[2rem]"
        :class="{ 
          'justify-center': isCollapsed && !isMobile, 
          'ml-[1rem]': !isCollapsed || isMobile, 
          'ml-0': isCollapsed && !isMobile 
        }">
        <img :src="logoutIcon" alt="">
        <span v-if="!isCollapsed || isMobile">{{ _t('customerprofile','log_out') }}</span>
      </Link>
    </div>
  </div>
</template>

<style scoped>
.sidebar-inner {
  position: relative;
  height: 100%;
  width: 100%;
}

.collapse-toggle {
  width: 30px;
  height: 30px;
  background-color: #153b4f;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s;
  border: none;
}

.collapsed {
  width: 100%;
  overflow-x: hidden;
}

a {
  display: flex !important;
}

.user-info {
  margin-bottom: 20px;
}

.menu-header {
  padding-top: 1rem;
  padding-bottom: 1rem;
  padding-left: 1rem;
  padding-right: 1rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: background-color 0.3s;
  font-size: 1rem;
  color: #153b4f;
  font-weight: 500;
}

.menu-header.collapsed-menu {
  padding: 1rem;
  justify-content: center;
}

.menu-header.active {
  background-color: #153b4f;
  color: white;
}

.chevron {
  transition: transform 0.3s;
}

.chevron.rotated {
  transform: rotate(180deg);
  filter: brightness(10);
}

.submenu {
  list-style: none;
  margin: 0;
  padding: 0 20px;
  border-radius: 12px;
}

.submenu li {
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.submenu li.submenu-active {
  background-color: #153b4f1a;
  color: #153b4f;
  border-radius: 12px;
}

.menu-link {
  padding-top: 1rem;
  padding-bottom: 1rem;
  padding-left: 1rem;
  padding-right: 1rem;
  display: block;
  transition: color 0.3s;
  color: #153b4f;
  font-weight: 500;
}

.menu-link.collapsed-menu {
  padding: 1rem;
  justify-content: center;
}

.menu-link.active {
  color: white;
  background-color: #153b4f;
  border-radius: 12px;
}

.brightness-active {
  filter: brightness(15);
}

.collapsed-avatar {
  margin-bottom: 20px;
}
</style>
