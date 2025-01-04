<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import chevronIcon from '../../assets/chaveronDown.svg';
import profileIcon from '../../assets/userDashIcon.svg';
import bookingsIcon from '../../assets/bookingIcon.svg';
import inboxIcon from '../../assets/inboxIcon.svg';
import favoritesIcon from '../../assets/favouriteIcon.svg';
import reviewsIcon from '../../assets/myreviewIcon.svg';

const activeMenu = ref(null);
const activeSubmenu = ref(null);
const activeLink = ref(null);

const menus = [
  {
    title: 'My Profile',
    key: 'profile',
    icon: profileIcon,
    items: [
      { name: 'Profile', path: '/profile' },
      { name: 'Travel Documents', path: '/travel-documents' },
      { name: 'Issued Payments', path: '/issued-payments' },
    ],
  },
  {
    title: 'My Bookings',
    key: 'bookings',
    icon: bookingsIcon,
    items: [
      { name: 'Confirmed', path: '/confirmed-bookings' },
      { name: 'Pending', path: '/pending-bookings' },
      { name: 'Completed', path: '/completed-bookings' },
    ],
  },
];

const otherLinks = [
  { name: 'Inbox', path: '/inbox', icon: inboxIcon },
  { name: 'Favorites', path: '/favourites', icon: favoritesIcon },
  { name: 'My Reviews', path: '/review', icon: reviewsIcon },
];

const toggleMenu = (menuKey) => {
  activeMenu.value = activeMenu.value === menuKey ? null : menuKey;
};

const setActiveSubmenu = (submenu) => {
  activeSubmenu.value = submenu;
};

const greetingMessage = computed(() => {
  const hours = new Date().getHours();
  if (hours < 12) return 'Good Morning';
  if (hours < 18) return 'Good Afternoon';
  return 'Good Evening';
});

const setActiveLinkFromRoute = () => {
  const currentPath = window.location.pathname;
  const foundLink = otherLinks.find(link => link.path === currentPath);
  if (foundLink) {
    activeLink.value = foundLink.name;
  }
};

const setActiveSubmenuFromRoute = () => {
  const currentPath = window.location.pathname;
  menus.forEach(menu => {
    const foundItem = menu.items.find(item => item.path === currentPath);
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
</script>

<template>
  <div class="sidebar">
    <div class="user-info bg-gray-50 p-4 border-r border-gray-200 w-full">
      <div class="flex items-center space-x-3">
        <img
          src="https://via.placeholder.com/50"
          alt="User"
          class="w-12 h-12 rounded-full object-cover"
        />
        <div>
          <p class="text-sm text-gray-500">Hello, {{ greetingMessage }}</p>
          <p class="text-lg font-semibold text-gray-800">
            {{ $page.props.auth.user.first_name }}
          </p>
          <p class="capitalize text-md text-[#009900]">
            {{ $page.props.auth.user.role }}
          </p>
        </div>
      </div>
    </div>

    <!-- Dynamic Menus -->
    <div
      v-for="menu in menus"
      :key="menu.key"
      class="menu-item flex flex-col gap-2"
    >
      <button
        class="menu-header"
        :class="{ active: activeMenu === menu.key }"
        @click="toggleMenu(menu.key)"
      >
        <div class="flex gap-2">
          <img :src="menu.icon" alt="" class="icon-button active"
          :class="{ 'brightness-active': activeMenu === menu.key }"
           />
        {{ menu.title }}
        </div>
        <img
          class="chevron"
          :class="{ rotated: activeMenu === menu.key }"
          :src="chevronIcon"
          alt=""
        />
      </button>
      <ul v-if="activeMenu === menu.key" class="submenu">
        <li
          v-for="item in menu.items"
          :key="item.name"
          :class="{ 'submenu-active': activeSubmenu === item.name }"
        >
          <Link
            :href="item.path"
            class="submenu-link flex items-center gap-2"
            @click="setActiveSubmenu(item.name)"
          >
            {{ item.name }}
          </Link>
        </li>
      </ul>
    </div>

    <!-- Other Links -->
    <div v-for="link in otherLinks" :key="link.name" class="menu-item">
      <Link
        :href="link.path"
        class="menu-link flex items-center gap-2"
        :class="{ active: activeLink === link.name }"
        @click="activeLink = link.name"
      >
        <img :src="link.icon" alt="" class="icon" 
        :class="{ 'brightness-active': activeLink === link.name }"
        />
        {{ link.name }}
      </Link>
    </div>
  </div>
</template>

<style scoped>
a {
  display: flex !important;
}
.sidebar {
  background-color: #f5f5f5;
  padding: 20px;
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

.menu-link.active {
  color: white;
  background-color: #153b4f;
  border-radius: 12px;
}
.brightness-active {
  filter: brightness(15);
}
</style>
