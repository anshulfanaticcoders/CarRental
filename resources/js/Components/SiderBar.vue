<script setup>
import { ref, computed, onMounted, watch, inject } from "vue";
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

// Get the isCollapsed state from parent component or set default
const isCollapsed = inject('isSidebarCollapsed', ref(false));
const toggleSidebar = inject('toggleSidebar', () => {
  isCollapsed.value = !isCollapsed.value;
  // Emit the event for the parent layout
  emit('toggle-sidebar');
});

// Define emits
const emit = defineEmits(['toggle-sidebar']);

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
    title: "My Profile",
    key: "profile",
    icon: profileIcon,
    items: [
      { name: "Profile", path: "/profile" },
      { name: "Travel Documents", path: "/user/documents" },
      { name: "Issued Payments", path: "/issued-payments" },
    ],
  },
  {
    title: "My Bookings",
    key: "bookings",
    icon: bookingsIcon,
    items: [
      { name: "Confirmed", path: "/profile/bookings/confirmed" },
      { name: "Pending", path: "/profile/bookings/pending" },
      { name: "Completed", path: "/profile/bookings/completed" },
    ],
  },
];

const otherLinks = [
  { name: "Inbox", path: "/messages", icon: inboxIcon },
  { name: "Favorites", path: "/favourites", icon: favoritesIcon },
  { name: "My Reviews", path: "/profile/reviews", icon: reviewsIcon },
];

// Additional menus for "vendor"
const vendorMenus = [
  {
    title: "Dashboard",
    key: "dashboard",
    icon: dashboardIcon,
    items: [
      { name: "Profile", path: "/profile" },
      { name: "Overview", path: "/overview" },
      { name: "Documents", path: "/vendor/documents" },
      { name: "Reports", path: "/vendor-reports" },
    ],
  },
  {
    title: "Vehicles",
    key: "vehicles",
    icon: vehiclesIcon,
    items: [
      { name: "All Vehicles", path: "/current-vendor-vehicles" },
      { name: "Add New Vehicle", path: "/vehicle-listing" },
      { name: "Add Plan", path: "/plans" },
    ],
  },
];

const vendorOtherLinks = [
  { name: "Payment History", path: "/vendor/payments", icon: clockIcon },
  { name: "Bookings", path: "/bookings", icon: vehiclesIcon },
  { name: "Date Blocking", path: "/blocking-dates", icon: dateblockingIcon },
  { name: "Inbox", path: "/messages/vendor", icon: inboxIcon },
  { name: "Customer Reviews", path: "/customer-reviews", icon: reviewsIcon },
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
  if (hours < 12) return "Good Morning";
  if (hours < 18) return "Good Afternoon";
  return "Good Evening";
});

const setActiveLinkFromRoute = () => {
  const currentPath = window.location.pathname;
  const foundLink = activeOtherLinks.find(
    (link) => link.path === currentPath
  );
  if (foundLink) {
    activeLink.value = foundLink.name;
  }
};

const setActiveSubmenuFromRoute = () => {
  const currentPath = window.location.pathname;
  activeMenus.forEach((menu) => {
    const foundItem = menu.items.find((item) => item.path === currentPath);
    if (foundItem) {
      activeMenu.value = menu.key;
      activeSubmenu.value = foundItem.name;
    }
  });
};

onMounted(() => {
  setActiveLinkFromRoute();
  setActiveSubmenuFromRoute();
});

import axios from "axios";

const fetchUserProfile = async () => {
  try {
    // Make the request to fetch the current user's profile
    const response = await axios.get("/user");

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
</script>

<template>
  <div class="sidebar-inner">
    <!-- Collapse toggle button -->
    <div class="flex justify-end py-4 pr-6">
      <button @click="toggleSidebar" class="collapse-toggle" :class="{ 'toggle-collapsed': isCollapsed }"
        title="collapse menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6" v-if="!isCollapsed"></polyline>
          <polyline points="9 18 15 12 9 6" v-else></polyline>
        </svg>
      </button>
    </div>

    <div :class="{ 'collapsed': isCollapsed }">
      <div class="user-info p-4 w-full" v-if="!isCollapsed">
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

      <!-- User avatar only when collapsed -->
      <div class="collapsed-avatar p-4 flex justify-center" v-if="isCollapsed">
        <img :src="user?.profile.avatar || '/storage/avatars/default-avatar.svg'" alt="User Avatar"
          class="w-10 h-10 rounded-full object-cover" />
      </div>

      <!-- Dynamic Menus -->
      <div v-for="menu in activeMenus" :key="menu.key" class="menu-item flex flex-col gap-2">
        <button class="menu-header" :class="{ active: activeMenu === menu.key, 'collapsed-menu': isCollapsed }"
          @click="toggleMenu(menu.key)">
          <div class="flex gap-2 items-center">
            <img :src="menu.icon" alt="" class="icon-button active"
              :class="{ 'brightness-active': activeMenu === menu.key }" />
            <span v-if="!isCollapsed">{{ menu.title }}</span>
          </div>
          <img v-if="!isCollapsed" class="chevron" :class="{ rotated: activeMenu === menu.key }" :src="chevronIcon"
            alt="" />
        </button>
        <ul v-if="activeMenu === menu.key && !isCollapsed" class="submenu">
          <li v-for="item in menu.items" :key="item.name" :class="{ 'submenu-active': activeSubmenu === item.name }">
            <Link :href="item.path" class="submenu-link flex items-center gap-2" @click="setActiveSubmenu(item.name)">
            {{ item.name }}
            </Link>
          </li>
        </ul>
      </div>

      <!-- Other Links -->
      <div v-for="link in activeOtherLinks" :key="link.name" class="menu-item">
        <Link :href="link.path" class="menu-link flex items-center gap-2"
          :class="{ active: activeLink === link.name, 'collapsed-menu': isCollapsed }" @click="activeLink = link.name">
        <img :src="link.icon" alt="" class="icon w-[24px] h-[24px]"
          :class="{ 'brightness-active': activeLink === link.name }" />
        <span v-if="!isCollapsed">{{ link.name }}</span>
        </Link>
      </div>

      <Link :href="route('logout')" method="post" as="button" class="text-[#EE1D52] flex items-center gap-1 mt-[4rem]"
        :class="{ 'justify-center': isCollapsed, 'ml-[1rem]': !isCollapsed, 'ml-0': isCollapsed }">
      <img :src="logoutIcon" alt="">
      <span v-if="!isCollapsed">Log out</span>
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