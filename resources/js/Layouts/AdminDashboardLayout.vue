<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { ChevronsUpDown } from 'lucide-vue-next';
import bellIcon from '../../assets/belliconwhite.svg';

// State for admin profile
const showDropdown = ref(false);
const isLoading = ref(true);
const adminProfile = ref({
  avatar: null,
  company_name: null,
  email: null,
});

// State for notifications
const notifications = ref([]);
const unreadCount = ref(0);
const showingNotificationDropdown = ref(false);
const notificationDropdownRef = ref(null);
const bellIconRef = ref(null);

// Toggle profile dropdown
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
};

// Toggle notification dropdown
const toggleNotificationDropdown = () => {
  showingNotificationDropdown.value = !showingNotificationDropdown.value;
};

// Close notification dropdown when clicking outside
const closeNotificationDropdownOnOutsideClick = (event) => {
  if (
    showingNotificationDropdown.value &&
    notificationDropdownRef.value &&
    bellIconRef.value &&
    !notificationDropdownRef.value.contains(event.target) &&
    !bellIconRef.value.contains(event.target)
  ) {
    showingNotificationDropdown.value = false;
  }
};

// Route to title mapping
const routeTitles = {
  '/admin-dashboard': 'Dashboard',
  '/users': 'All Users',
  '/admin/user-documents': 'Users Documents',
  '/vendors': 'All Vendors',
  '/vendor-vehicles': 'All Cars',
  '/vehicles-categories': 'Categories',
  '/booking-addons': 'Addons',
  '/admin/plans': 'Plans',
  '/popular-places': 'All Locations',
  '/popular-places/create': 'Create Popular Place',
  '/customer-bookings': 'All Bookings',
  '/customer-bookings/pending': 'Pending Bookings',
  '/customer-bookings/confirmed': 'Active Bookings',
  '/customer-bookings/completed': 'Completed Bookings',
  '/customer-bookings/cancelled': 'Cancelled Bookings',
  '/pages': 'All Pages',
  '/pages/create': 'Create a Page',
  '/admin/contact-us': 'Contact Us',
  '/admin/contact-us/edit': 'Edit Contact Us',
  '/blogs': 'All Blogs',
  '/blogs/create': 'Create a Blog',
  '/admin/payments': 'All Payments',
  '/users-report': 'Users Report',
  '/vendors-report': 'Vendors Report',
  '/business-report': 'Business Report',
  '/contact-us-mails': 'Contact Mails',
  '/activity-logs': 'All Activities',
  '/admin/settings/footer': 'Footer Location',
  '/admin/settings/footer-categories': 'Footer Category',
  '/admin/settings/faq': 'FAQ',
  '/media': 'Media',
  '/admin/seo-meta': 'SEO Management',
  '/admin/seo-meta/create': 'Create SEO Metas',
  '/radiuses': 'Radius Management',
  '/admin/header-footer-scripts': 'Header and Footer Scripts',
  '/admin/settings/profile': 'Profile Setting',
  '/features': 'Features',
};

// Fetch admin profile
const fetchAdminProfile = async () => {
  try {
    const response = await axios.get('/api/admin/profile');
    adminProfile.value = response.data;
  } catch (error) {
    console.error('Error fetching admin profile:', error);
  } finally {
    isLoading.value = false;
  }
};

// Fetch notifications
const fetchNotifications = async () => {
  try {
    const response = await axios.get(route('notifications.index', { locale: 'en' })); // Assuming admin uses 'en' locale
    notifications.value = response.data.notifications.data;
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error('Error fetching notifications:', error);
  }
};

// Mark a notification as read
const markAsRead = async (notification) => {
  if (notification.read_at) return;
  try {
    await axios.post(route('notifications.mark-read', { locale: 'en', id: notification.id }));
    notification.read_at = new Date().toISOString();
    unreadCount.value--;
  } catch (error) {
    console.error('Error marking notification as read:', error);
  }
};

// Mark all notifications as read
const markAllAsRead = async () => {
  try {
    await axios.post(route('notifications.mark-all-read', { locale: 'en' }));
    notifications.value.forEach((n) => (n.read_at = new Date().toISOString()));
    unreadCount.value = 0;
  } catch (error) {
    console.error('Error marking all notifications as read:', error);
  }
};

// Clear all notifications
const clearAllNotifications = async () => {
  try {
    await axios.delete(route('notifications.clear-all', { locale: 'en' }));
    notifications.value = [];
    unreadCount.value = 0;
    showingNotificationDropdown.value = false;
  } catch (error) {
    console.error('Error clearing notifications:', error);
  }
};

// Handle notification click: mark as read and redirect
const handleNotificationClick = async (notification) => {
  await markAsRead(notification);
  const link = getNotificationLink(notification);
  if (link && link !== '#') {
    router.visit(link);
  }
  showingNotificationDropdown.value = false;
};

// Define notification links for admin (customize as needed)
const getNotificationLink = (notification) => {
  const type = notification.type.split('\\').pop();
  const locale = 'en'; // Assuming admin uses 'en' locale
  switch (type) {
    case 'VendorVehicleCreateNotification':
      return route('vendor-vehicles', { locale });
    case 'BookingCreatedAdminNotification':
      return route('customer-bookings.index', { locale });
    case 'VendorStatusUpdatedNotification':
      return route('vendors', { locale });
    case 'NewMessageNotification':
      return route('admin.messages', { locale }); // Adjust route for admin
    case 'ReviewSubmittedVendorNotification':
      return route('vendors', { locale });
    case 'BookingCancelledNotification':
      return route('customer-bookings.cancelled', { locale });
    case 'AccountCreatedNotification':
      return route('users.index', { locale });
    case 'BulkVehicleUploadAdminNotification':
      return route('admin.vehicles.index', { locale });
    case 'BookingStatusUpdatedCustomerNotification':
      const bookingStatus = notification.data.status;
      if (bookingStatus === 'pending') return route('customer-bookings.pending', { locale });
      if (bookingStatus === 'confirmed') return route('customer-bookings.confirmed', { locale });
      if (bookingStatus === 'completed') return route('customer-bookings.completed', { locale });
      return '#';
    default:
      return '#';
  }
};

// Get current title based on route
const currentPageTitle = computed(() => {
  const path = window.location.pathname;
  return routeTitles[path] || 'Dashboard';
});

// On mount, fetch profile and notifications
onMounted(() => {
  fetchAdminProfile();
  fetchNotifications();
  document.addEventListener('click', closeNotificationDropdownOnOutsideClick);
});

// Clean up event listeners on unmount
onUnmounted(() => {
  document.removeEventListener('click', closeNotificationDropdownOnOutsideClick);
});
</script>

<template>
  <Head>
    <meta name="robots" content="noindex, nofollow" />
    <title>Dashboard</title>
  </Head>
  <main class="">
    <div class="flex">
      <AdminSiderBar />
      <!-- Content -->
      <div class="column w-full flex flex-col">
        <div class="py-5 px-5 text-white flex justify-between items-center shadow-[5px_0px_3px_2px_#8d8d8d] bg-customDarkBlackColor">
          <p class="leading-8">{{ currentPageTitle }}</p>
          <div class="flex items-center space-x-4">
            <!-- Notification Bell (Before Company Details) -->
            <div class="relative">
              <button
                ref="bellIconRef"
                @click="toggleNotificationDropdown(); markAllAsRead()"
                class="relative p-2 rounded-full hover:bg-gray-700 focus:bg-gray-700 bellicon_btn"
                :class="{ 'ripple-effect': unreadCount > 0 }"
              >
                <img :src="bellIcon" alt="Notifications" class="w-6 h-6 ml-[2px]" />
                <span
                  v-if="unreadCount > 0"
                  class="absolute w-[18px] h-[18px] border-2 border-white top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"
                >
                  {{ unreadCount }}
                </span>
              </button>
              <div
                v-if="showingNotificationDropdown"
                ref="notificationDropdownRef"
                class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-20 top-[3rem]"
              >
                <div class="p-4 border-b flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-800">Notifications</h3>
                </div>
                <div class="overflow-y-auto" style="max-height: 400px">
                  <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                    No notifications yet.
                  </div>
                  <div v-else>
                    <div
                      v-for="notification in notifications"
                      :key="notification.id"
                      @click="handleNotificationClick(notification)"
                      class="p-4 border-b hover:bg-gray-50 cursor-pointer"
                      :class="{ 'bg-gray-100': !notification.read_at }"
                    >
                      <div class="flex">
                        <div class="font-semibold">{{ notification.data.title }}</div>
                        <div class="text-xs text-gray-500 ">{{ notification.type.split('\\').pop() }}</div>
                      </div>
                      <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
                      <div class="text-xs text-customPrimaryColor mt-1 text-right">
                        {{ new Date(notification.created_at).toLocaleString() }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="p-2 border-t text-center">
                  <button
                    @click="clearAllNotifications"
                    class="text-sm text-red-500 hover:underline"
                  >
                    Clear all notifications
                  </button>
                </div>
              </div>
            </div>

            <!-- Admin Profile Dropdown -->
            <div class="relative" @click="toggleDropdown">
              <div class="flex items-center cursor-pointer">
                <div v-if="isLoading" class="w-8 h-8 rounded-full bg-gray-300 animate-pulse mr-2"></div>
                <img
                  v-else
                  :src="adminProfile.avatar"
                  alt="Admin Avatar"
                  class="w-8 h-8 rounded-full mr-2"
                />
                <div>
                  <p class="text-sm">{{ adminProfile.company_name || 'Loading...' }}</p>
                  <p class="text-xs">{{ adminProfile.email || 'Loading...' }}</p>
                </div>
                <div class="ml-2">
                  <ChevronsUpDown />
                </div>
              </div>
              <div
                v-if="showDropdown"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10"
              >
                <Link
                  href="/admin/settings/profile"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  Profile Setting
                </Link>
                <Link
                  :href="route('admin.logout')"
                  method="post"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  Logout
                </Link>
              </div>
            </div>
          </div>
        </div>
        <div class="h-[91vh] overflow-y-auto">
          <slot />
        </div>
      </div>
      <!-- Content -->
    </div>
  </main>
</template>

<style scoped>
::-webkit-scrollbar {
  display: none;
}

/* Animation for skeleton loader */
@keyframes pulse {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    opacity: 1;
  }
}

.animate-pulse {
  animation: pulse 1.5s infinite;
}

/* Smooth transitions for dropdowns */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
.bellicon_btn {
  box-shadow: inset 0px 0px 8px 0px white;
}

/* Ripple effect animation */
@keyframes ripple {
  0% {
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7); /* White ripple */
  }
  70% {
    box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); /* Increased spread */
  }
  100% {
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
  }
}

.ripple-effect {
  animation: ripple 1.5s infinite;
}
</style>
