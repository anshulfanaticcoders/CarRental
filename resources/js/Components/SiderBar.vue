<script setup>
import { ref, computed, onMounted, watch, getCurrentInstance } from "vue";
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
} from '@/Components/ui/alert-dialog';
import {
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarSeparator,
  useSidebar,
} from '@/Components/ui/sidebar';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';

const page = usePage();
const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const { state, isMobile, setOpen, setOpenMobile } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');

const user = computed(() => page.props.auth?.user || null);
const userInitials = computed(() => {
  const first = user.value?.first_name?.[0] || '';
  const last = user.value?.last_name?.[0] || '';
  return `${first}${last}`.toUpperCase() || 'U';
});

const booking = ref(null);
const payment = ref(null);
const vehicle = ref(null);
const error = ref(null);

onMounted(async () => {
  const paymentIntentId = page.props.payment_intent;

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

const userRole = page.props.auth?.user?.role;

// Menus for "customer" (existing logic)
const menus = [
  {
    title: _t('customerprofile', 'my_profile'),
    key: "profile",
    icon: profileIcon,
    items: [
      { name: _t('customerprofile', 'profile'), path: route('profile.edit', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'travel_documents'), path: route('user.documents.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'issued_payments'), path: route('profile.payments', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'register_as_vendor'), path: route('vendor.register', { locale: usePage().props.locale }) },
    ],
  },
  {
    title: _t('customerprofile', 'my_bookings'),
    key: "bookings",
    icon: bookingsIcon,
    items: [
      { name: _t('customerprofile', 'all_bookings') || 'All Bookings', path: route('profile.bookings.all', { locale: usePage().props.locale }) },
    ],
  },
];

const otherLinks = [
  { name: _t('customerprofile', 'inbox'), path: route('messages.index', { locale: usePage().props.locale }), icon: inboxIcon, isInbox: true },
  { name: _t('customerprofile', 'favorites'), path: route('profile.favourites', { locale: usePage().props.locale }), icon: favoritesIcon },
  { name: _t('customerprofile', 'my_reviews'), path: route('profile.reviews', { locale: usePage().props.locale }), icon: reviewsIcon },
];

// Additional menus for "vendor"
const vendorMenus = [
  {
    title: _t('customerprofile', 'dashboard'),
    key: "dashboard",
    icon: dashboardIcon,
    items: [
      { name: _t('customerprofile', 'profile'), path: route('profile.edit', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'overview'), path: route('vendor.overview', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'documents'), path: route('vendor.documents.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'verification_status'), path: route('vendor.status', { locale: usePage().props.locale }) },
    ],
  },
  {
    title: _t('customerprofile', 'vehicles'),
    key: "vehicles",
    icon: vehiclesIcon,
    items: [
      { name: _t('customerprofile', 'all_vehicles'), path: route('current-vendor-vehicles.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'add_new_vehicle'), path: route('vehicles.create', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'create_bulk_listing'), path: route('vehicles.bulk-upload.create', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'manage_plans'), path: route('VendorPlanIndex', { locale: usePage().props.locale }) },
    ],
  },
];

const vendorOtherLinks = [
  { name: _t('customerprofile', 'payment_history'), path: route('vendor.payments', { locale: usePage().props.locale }), icon: clockIcon },
  { name: _t('customerprofile', 'bookings'), path: route('bookings.index', { locale: usePage().props.locale }), icon: vehiclesIcon },
  { name: _t('customerprofile', 'date_blocking'), path: route('vendor.blocking-dates.index', { locale: usePage().props.locale }), icon: dateblockingIcon },
  { name: _t('customerprofile', 'inbox'), path: route('messages.vendor.index', { locale: usePage().props.locale }), icon: inboxIcon, isInbox: true },
  { name: _t('customerprofile', 'customer_reviews'), path: route('vendor.reviews', { locale: usePage().props.locale }), icon: reviewsIcon },
];

// Active menus based on role
const activeMenus = userRole === "vendor" ? vendorMenus : menus;
const activeOtherLinks = userRole === "vendor" ? vendorOtherLinks : otherLinks;

const toggleMenu = (menuKey) => {
  if (isCollapsed.value && !isMobile.value) {
    setOpen(true);
  }
  activeMenu.value = activeMenu.value === menuKey ? null : menuKey;
};

const setActiveSubmenu = (submenu) => {
  activeSubmenu.value = submenu;
};

const greetingMessage = computed(() => {
  const hours = new Date().getHours();
  if (hours < 12) return _t('customerprofile', 'greeting_morning');
  if (hours < 18) return _t('customerprofile', 'greeting_afternoon');
  return _t('customerprofile', 'greeting_evening');
});

const setActiveLinkFromRoute = () => {
  const currentPath = page.url.split("?")[0];
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
  const currentPath = page.url.split("?")[0];
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

watch(() => page.url, () => {
  setActiveLinkFromRoute();
  setActiveSubmenuFromRoute();
});

import axios from "axios";

const unreadMessageCount = ref(0);

const fetchUnreadMessageCount = async () => {
  try {
  const response = await axios.get(route('messages.unreadCount', { locale: page.props.locale }));
    if (response.data && typeof response.data.unread_count === 'number') {
      unreadMessageCount.value = response.data.unread_count;
    }
  } catch (error) {
    console.error("Error fetching unread message count:", error);
  }
};

onMounted(fetchUnreadMessageCount);
watch(() => usePage().url, fetchUnreadMessageCount); // Re-fetch on route change

// Function to close mobile menu when clicking a link (for mobile view)
const handleLinkClick = (name) => {
  if (isMobile.value) {
    setOpenMobile(false);
  }
  activeLink.value = name;
};

// Function to handle submenu item click
const handleSubmenuClick = (name) => {
  if (isMobile.value) {
    setOpenMobile(false);
  }
  setActiveSubmenu(name);
};


const profileCompletion = ref(0);
const safeCompletion = computed(() => {
  const value = Number(profileCompletion.value) || 0;
  return Math.min(100, Math.max(0, value));
});

const completionColor = computed(() => {
  if (safeCompletion.value >= 90) return 'bg-[#4caf50]';
  if (safeCompletion.value >= 50) return 'bg-amber-400';
  return 'bg-rose-400';
});

const fetchProfileCompletion = async () => {
  try {
    const response = await fetch(route('profile.completion', { locale: page.props.locale }));
    const data = await response.json();
    profileCompletion.value = data.percentage;
  } catch (error) {
    console.error('Error fetching profile completion:', error);
  }
};

onMounted(fetchProfileCompletion);

// Transition Hooks for Accordion Animation
const beforeEnter = (el) => {
  el.style.height = '0';
  el.style.opacity = '0';
};

const enter = (el) => {
  el.style.height = el.scrollHeight + 'px';
  el.style.opacity = '1';
};

const afterEnter = (el) => {
  el.style.height = 'auto'; // Allow height to adjust if content changes
};

const beforeLeave = (el) => {
  el.style.height = el.scrollHeight + 'px';
  el.style.opacity = '1';
};

const leave = (el) => {
  el.style.height = '0';
  el.style.opacity = '0';
};
</script>

<template>
  <div class="sidebar-inner">
    <SidebarHeader>
      <div v-if="isCollapsed" class="profile-collapsed">
        <Avatar class="h-10 w-10">
          <AvatarImage :src="user?.profile?.avatar || '/storage/avatars/default-avatar.svg'" />
          <AvatarFallback>{{ userInitials }}</AvatarFallback>
        </Avatar>
      </div>
      <div v-else class="profile-card">
        <div class="flex items-center gap-3">
          <Avatar class="h-10 w-10">
            <AvatarImage :src="user?.profile?.avatar || '/storage/avatars/default-avatar.svg'" />
            <AvatarFallback>{{ userInitials }}</AvatarFallback>
          </Avatar>
          <div class="min-w-0">
            <p class="text-xs text-white/70">{{ greetingMessage }}</p>
            <p class="text-sm font-semibold text-white truncate">
              {{ user?.first_name }} {{ user?.last_name }}
            </p>
            <p class="text-[0.65rem] uppercase tracking-[0.2em] text-white/80">
              {{ user?.role || 'User' }}
            </p>
          </div>
        </div>
        <div class="mt-3">
          <div class="flex items-center justify-between text-[0.7rem] text-white/70">
            <span>{{ _t('customerprofile', 'profile_completion') || 'Profile completion' }}</span>
            <span>{{ safeCompletion }}%</span>
          </div>
          <div class="mt-1 h-2 rounded-full bg-white/20 overflow-hidden">
            <div class="h-full" :class="completionColor" :style="{ width: `${safeCompletion}%` }"></div>
          </div>
        </div>
      </div>
    </SidebarHeader>

    <SidebarSeparator class="my-2" />

    <SidebarContent>
      <SidebarGroup v-for="menu in activeMenus" :key="menu.key">
        <SidebarGroupLabel>{{ menu.title }}</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              :is-active="activeMenu === menu.key"
              :tooltip="menu.title"
              size="lg"
              :class="['profile-nav-button', isCollapsed ? 'justify-center' : '']"
              @click="toggleMenu(menu.key)"
            >
              <img :src="menu.icon" alt="" class="h-5 w-5 nav-icon" />
              <span v-if="!isCollapsed" class="nav-label">{{ menu.title }}</span>
              <img
                v-if="!isCollapsed"
                :src="chevronIcon"
                alt=""
                class="ml-auto h-3 w-3 transition-transform"
                :class="{ 'rotate-180': activeMenu === menu.key }"
              />
            </SidebarMenuButton>
            <Transition name="accordion" @before-enter="beforeEnter" @enter="enter" @after-enter="afterEnter"
              @before-leave="beforeLeave" @leave="leave">
              <SidebarMenuSub v-if="activeMenu === menu.key && !isCollapsed">
                <SidebarMenuSubItem v-for="item in menu.items" :key="item.name">
                  <SidebarMenuSubButton :is-active="activeSubmenu === item.name" as-child class="profile-sub-button">
                    <Link :href="item.path" @click="handleSubmenuClick(item.name)">
                      <span>{{ item.name }}</span>
                    </Link>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </Transition>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <SidebarSeparator class="my-2" />

      <SidebarGroup>
        <SidebarGroupLabel>{{ _t('customerprofile', 'quick_links') || 'Quick Links' }}</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem v-for="link in activeOtherLinks" :key="link.name">
            <SidebarMenuButton
              :is-active="activeLink === link.name"
              :tooltip="link.name"
              size="lg"
              as-child
              :class="['profile-nav-button', isCollapsed ? 'justify-center' : '']"
            >
              <Link :href="link.path" class="items-center" @click="handleLinkClick(link.name)">
                <img :src="link.icon" alt="" class="h-5 w-5 nav-icon" />
                <span v-if="!isCollapsed" class="nav-label">{{ link.name }}</span>
              </Link>
            </SidebarMenuButton>
            <SidebarMenuBadge
              v-if="link.isInbox && unreadMessageCount > 0"
              class="bg-rose-500 text-white"
            >
              {{ unreadMessageCount }}
            </SidebarMenuBadge>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter>
      <AlertDialog>
        <AlertDialogTrigger as-child>
          <SidebarMenuButton
            variant="outline"
            size="lg"
            class="profile-nav-button bg-rose-600 text-white hover:text-white hover:bg-rose-700 border-rose-600"
            :tooltip="_t('customerprofile', 'log_out')"
          >
            <img :src="logoutIcon" alt="" class="h-5 w-5 nav-icon nav-icon--white" />
            <span v-if="!isCollapsed" class="nav-label text-white">{{ _t('customerprofile', 'log_out') }}</span>
          </SidebarMenuButton>
        </AlertDialogTrigger>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{{ _t('customerprofile', 'log_out') }}</AlertDialogTitle>
            <AlertDialogDescription>
              {{ _t('customerprofile', 'logout_confirmation') || 'Are you sure you want to log out?' }}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>{{ _t('customerprofile', 'button_cancel') || 'Cancel' }}</AlertDialogCancel>
            <AlertDialogAction as-child>
              <Link :href="route('logout')" method="post" as="button">
                {{ _t('customerprofile', 'log_out') }}
              </Link>
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </SidebarFooter>
  </div>
</template>

<style scoped>
.sidebar-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
}

.profile-card {
  border-radius: 16px;
  border: 1px solid rgba(21, 59, 79, 0.35);
  background: #153b4f;
  padding: 0.85rem 0.9rem;
  box-shadow: 0 14px 26px rgba(15, 23, 42, 0.18);
  width: 100%;
  overflow: hidden;
}

.profile-collapsed {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 0.5rem;
}

.accordion-enter-active,
.accordion-leave-active {
  transition: height 0.3s ease, opacity 0.3s ease;
  overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
  height: 0;
  opacity: 0;
}

.profile-nav-button {
  color: #334155;
  border-radius: 12px;
  font-size: 1.2rem;
  transition: background 160ms ease, color 160ms ease, box-shadow 160ms ease;
}

.profile-nav-button:hover {
  background: rgba(148, 163, 184, 0.2);
}

.profile-nav-button[data-active='true'] {
  background: rgba(21, 59, 79, 0.12);
  color: #153b4f;
  box-shadow: inset 3px 0 0 #153b4f;
}

.nav-label {
  font-weight: 600;
}

.nav-icon {
  opacity: 0.9;
}

.nav-icon--white {
  filter: brightness(0) invert(1);
}

.profile-sub-button[data-active='true'] {
  background: rgba(21, 59, 79, 0.12);
  color: #153b4f;
  font-weight: 600;
}
</style>
