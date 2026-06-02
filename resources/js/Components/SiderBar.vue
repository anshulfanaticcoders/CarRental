<script setup>
import { ref, computed, onMounted, watch, getCurrentInstance, markRaw } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
  CalendarCheck,
  Car,
  CalendarX,
  ChevronDown,
  Clock,
  Globe,
  Heart,
  Inbox,
  LayoutDashboard,
  LogOut,
  Star,
  User,
} from "lucide-vue-next";

const profileIcon = markRaw(User);
const bookingsIcon = markRaw(CalendarCheck);
const inboxIcon = markRaw(Inbox);
const favoritesIcon = markRaw(Heart);
const reviewsIcon = markRaw(Star);
const dashboardIcon = markRaw(LayoutDashboard);
const vehiclesIcon = markRaw(Car);
const clockIcon = markRaw(Clock);
const dateblockingIcon = markRaw(CalendarX);
const globeIcon = markRaw(Globe);
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
      { name: 'Manage Vehicle Locations', path: route('vendor.locations.index', { locale: usePage().props.locale }) },
      { name: _t('customerprofile', 'create_bulk_listing'), path: route('vendor.vehicles.bulk-import.index', { locale: usePage().props.locale }) },
    ],
  },
];

const vendorOtherLinks = [
  { name: _t('customerprofile', 'payment_history'), path: route('vendor.payments', { locale: usePage().props.locale }), icon: clockIcon },
  { name: _t('customerprofile', 'bookings'), path: route('bookings.index', { locale: usePage().props.locale }), icon: bookingsIcon },
  { name: 'External Bookings', path: route('vendor.external-bookings.index', { locale: usePage().props.locale }), icon: globeIcon },
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
const isLoggingOut = ref(false);
const isLogoutDialogOpen = ref(false);

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

const handleLogout = () => {
  if (isLoggingOut.value) {
    return;
  }

  isLoggingOut.value = true;
  isLogoutDialogOpen.value = false;

  router.post(route('logout'), {}, {
    onFinish: () => {
      isLoggingOut.value = false;
    },
  });
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
            <span class="role-chip">{{ user?.role || 'User' }}</span>
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

    <SidebarSeparator class="sb-divider" />

    <SidebarContent>
      <SidebarGroup v-for="menu in activeMenus" :key="menu.key" class="sb-group">
        <SidebarGroupLabel class="sb-group-label">{{ menu.title }}</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton :is-active="activeMenu === menu.key" :tooltip="menu.title" size="lg"
              :class="['profile-nav-button profile-group-button', isCollapsed ? 'justify-center' : '']" @click="toggleMenu(menu.key)">
              <component :is="menu.icon" class="h-5 w-5 nav-icon" :stroke-width="1.75" />
              <span v-if="!isCollapsed" class="nav-label">{{ menu.title }}</span>
              <ChevronDown v-if="!isCollapsed" class="ml-auto h-4 w-4 nav-chevron transition-transform"
                :stroke-width="2" :class="{ 'rotate-180': activeMenu === menu.key }" />
            </SidebarMenuButton>
            <Transition name="accordion" @before-enter="beforeEnter" @enter="enter" @after-enter="afterEnter"
              @before-leave="beforeLeave" @leave="leave">
              <SidebarMenuSub v-if="activeMenu === menu.key && !isCollapsed" class="sb-submenu">
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

      <SidebarSeparator class="sb-divider" />

      <SidebarGroup class="sb-group">
        <SidebarGroupLabel class="sb-group-label">{{ _t('customerprofile', 'quick_links') || 'Quick Links' }}</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem v-for="link in activeOtherLinks" :key="link.name">
            <SidebarMenuButton :is-active="activeLink === link.name" :tooltip="link.name" size="lg" as-child
              :class="['profile-nav-button', isCollapsed ? 'justify-center' : '']">
              <Link :href="link.path" class="items-center" @click="handleLinkClick(link.name)">
                <component :is="link.icon" class="h-5 w-5 nav-icon" :stroke-width="1.75" />
                <span v-if="!isCollapsed" class="nav-label">{{ link.name }}</span>
              </Link>
            </SidebarMenuButton>
            <SidebarMenuBadge v-if="link.isInbox && unreadMessageCount > 0" class="bg-rose-500 text-white">
              {{ unreadMessageCount }}
            </SidebarMenuBadge>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter>
      <AlertDialog v-model:open="isLogoutDialogOpen">
        <AlertDialogTrigger as-child>
          <SidebarMenuButton variant="outline" size="lg"
            class="profile-nav-button logout-button bg-rose-600 text-white hover:text-white hover:bg-rose-700 border-rose-600"
            :disabled="isLoggingOut"
            :tooltip="_t('customerprofile', 'log_out')">
            <LogOut class="h-5 w-5 nav-icon" :stroke-width="1.75" />
            <span v-if="!isCollapsed" class="nav-label text-white">
              {{ isLoggingOut ? (_t('customerprofile', 'logging_out') || 'Logging out...') : _t('customerprofile', 'log_out') }}
            </span>
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
            <AlertDialogAction
              :disabled="isLoggingOut"
              @click="handleLogout"
            >
              {{ _t('customerprofile', 'log_out') }}
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
  background-color: #ffffff;
}

.profile-card {
  position: relative;
  border-radius: 18px;
  border: 1px solid rgba(34, 211, 238, 0.18);
  background: linear-gradient(135deg, #153b4f 0%, #1c4d66 100%);
  padding: 1rem;
  box-shadow: 0 14px 26px rgba(15, 23, 42, 0.18);
  width: 100%;
  overflow: hidden;
}

.profile-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 88% -10%, rgba(34, 211, 238, 0.28), transparent 52%);
  pointer-events: none;
}

.profile-card > * {
  position: relative;
}

.profile-collapsed {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 0.5rem;
}

.role-chip {
  display: inline-block;
  margin-top: 5px;
  font-size: 0.56rem;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #22d3ee;
  background: rgba(34, 211, 238, 0.12);
  border: 1px solid rgba(34, 211, 238, 0.28);
  padding: 3px 9px;
  border-radius: 999px;
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

/* tighten stacked groups: kill the default p-2 vertical padding */
.sb-group {
  padding-top: 0;
  padding-bottom: 0;
}

.sb-divider {
  margin-top: 6px;
  margin-bottom: 6px;
}

/* section group labels */
.sb-group-label {
  height: auto;
  padding: 8px 12px 6px;
  font-size: 0.66rem;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #94a3b8;
}

/* first group label needs no extra top gap (separator already spaces it) */
.sb-group:first-child .sb-group-label {
  padding-top: 2px;
}

/* primary nav buttons: group headers + quick links */
.profile-nav-button {
  height: auto;
  min-height: 44px;
  gap: 12px;
  padding: 10px 12px;
  margin-bottom: 2px;
  color: #334155;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  line-height: 1.3;
  transition: background 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.profile-nav-button:not(.logout-button):hover {
  background: rgba(21, 59, 79, 0.06);
  color: #153b4f;
  transform: translateX(2px);
}

.profile-nav-button:not(.logout-button):hover .nav-icon {
  color: #153b4f;
}

/* logout: keep rose, white icon */
.logout-button .nav-icon {
  color: #ffffff !important;
}

.logout-button:hover {
  transform: translateY(-1px);
}

/* leaf items (quick links): bold filled teal when active */
.profile-nav-button[data-active='true']:not(.profile-group-button) {
  background: linear-gradient(135deg, #153b4f, #1c4d66);
  color: #ffffff;
  box-shadow: 0 8px 18px rgba(21, 59, 79, 0.3);
  transform: none;
}

.profile-nav-button[data-active='true']:not(.profile-group-button) .nav-icon {
  color: #ffffff;
}

.profile-nav-button[data-active='true']:not(.profile-group-button):not(.justify-center)::after {
  content: "";
  width: 7px;
  height: 7px;
  flex: 0 0 7px;
  margin-left: auto;
  border-radius: 999px;
  background: #22d3ee;
  box-shadow: 0 0 8px rgba(34, 211, 238, 0.85);
}

/* parent group containing the active route child: tint + left bar */
.profile-group-button[data-active='true'] {
  background: rgba(21, 59, 79, 0.1);
  color: #153b4f;
  box-shadow: inset 3px 0 0 #153b4f;
  transform: none;
}

.profile-group-button[data-active='true'] .nav-icon,
.profile-group-button[data-active='true'] .nav-chevron {
  color: #153b4f;
}

.nav-label {
  font-weight: 600;
}

.nav-icon {
  width: 20px !important;
  height: 20px !important;
  flex-shrink: 0;
  color: #64748b;
  transition: color 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.nav-chevron {
  width: 16px !important;
  height: 16px !important;
  color: #94a3b8;
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

/* submenu: guide line + breathing room */
.sb-submenu {
  margin-left: 20px;
  padding: 4px 0 4px 12px;
  border-left: 1px solid #e2e8f0;
  gap: 2px;
}

/* submenu leaf links */
.profile-sub-button {
  position: relative;
  height: auto;
  min-height: 36px;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 10px;
  font-size: 0.86rem;
  font-weight: 500;
  line-height: 1.3;
  color: #64748b;
  transition: background 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.profile-sub-button::before {
  content: "";
  width: 5px;
  height: 5px;
  flex: 0 0 5px;
  border-radius: 999px;
  background: #cbd5e1;
  transition: background 0.25s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.profile-sub-button:hover {
  background: rgba(21, 59, 79, 0.06);
  color: #153b4f;
  transform: translateX(2px);
}

.profile-sub-button:hover::before {
  background: #22d3ee;
}

/* active submenu leaf: bold filled teal + cyan dot */
.profile-sub-button[data-active='true'] {
  background: linear-gradient(135deg, #153b4f, #1c4d66);
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 6px 14px rgba(21, 59, 79, 0.26);
  transform: none;
}

.profile-sub-button[data-active='true']::before {
  background: #22d3ee;
  transform: scale(1.5);
  box-shadow: 0 0 8px rgba(34, 211, 238, 0.9);
}
</style>
