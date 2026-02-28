<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import {
  ChevronsUpDown,
  Bell,
  Search,
  User,
  Settings,
  LogOut,
  PanelLeft,
} from 'lucide-vue-next';
import { Toaster } from '@/Components/ui/sonner';
import { SidebarProvider, SidebarTrigger } from '@/Components/ui/sidebar';

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

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
  showingNotificationDropdown.value = false;
};

const toggleNotificationDropdown = () => {
  showingNotificationDropdown.value = !showingNotificationDropdown.value;
  showDropdown.value = false;
};

const closeDropdownsOnOutsideClick = (event) => {
  if (
    showingNotificationDropdown.value &&
    notificationDropdownRef.value &&
    bellIconRef.value &&
    !notificationDropdownRef.value.contains(event.target) &&
    !bellIconRef.value.contains(event.target)
  ) {
    showingNotificationDropdown.value = false;
  }

  const profileEl = document.querySelector('.hdr-profile-area');
  if (showDropdown.value && profileEl && !profileEl.contains(event.target)) {
    showDropdown.value = false;
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
  '/admin/newsletter-subscribers': 'Newsletter Subscribers',
  '/admin/analytics': 'Analytics',
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
  '/admin/settings/payable-amount': 'Payable Amount',
  '/admin/settings/homepage': 'Home Page Settings',
  '/admin/advertisements': 'Advertisements',
  '/admin/reviews': 'Reviews',
  '/testimonials': 'Testimonials',
  '/damage-protection-records': 'Damage Protection',
  '/admin/affiliate/business-statistics': 'Business Statistics',
  '/admin/affiliate/business-verification': 'Business Verification',
  '/admin/affiliate/payment-tracking': 'Payment Tracking',
  '/admin/affiliate/commission-management': 'Commission Management',
  '/admin/affiliate/qr-analytics': 'QR Code Analytics',
  '/admin/affiliate/business-model': 'Business Model Settings',
  '/admin/affiliate/business-register': 'Register Business',
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
    const response = await axios.get(route('notifications.index', { locale: 'en' }));
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

// Handle notification click
const handleNotificationClick = async (notification) => {
  await markAsRead(notification);
  const link = getNotificationLink(notification);
  if (link && link !== '#') {
    router.visit(link);
  }
  showingNotificationDropdown.value = false;
};

// Define notification links
const getNotificationLink = (notification) => {
  const type = notification.type.split('\\').pop();
  const locale = 'en';
  switch (type) {
    case 'VendorVehicleCreateNotification':
      return route('vendor-vehicles', { locale });
    case 'BookingCreatedAdminNotification':
      return route('customer-bookings.index', { locale });
    case 'VendorStatusUpdatedNotification':
      return route('vendors', { locale });
    case 'NewMessageNotification':
      return route('admin.messages', { locale });
    case 'ReviewSubmittedVendorNotification':
      return route('vendors', { locale });
    case 'BookingCancelledNotification':
      return route('customer-bookings.cancelled', { locale });
    case 'AccountCreatedNotification':
      return route('users.index', { locale });
    case 'BulkVehicleUploadAdminNotification':
      return route('admin.vehicles.index', { locale });
    case 'VendorRegisteredNotification':
      return route('vendors.index', { locale });
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

const currentPageTitle = computed(() => {
  const path = window.location.pathname;
  return routeTitles[path] || 'Dashboard';
});

// Get initials from company name
const profileInitials = computed(() => {
  const name = adminProfile.value.company_name || '';
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || 'VC';
});

onMounted(() => {
  fetchAdminProfile();
  fetchNotifications();
  document.addEventListener('click', closeDropdownsOnOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdownsOnOutsideClick);
});
</script>

<template>
  <Head>
    <meta name="robots" content="noindex, nofollow" />
    <title>Dashboard</title>
  </Head>

  <main class="font-[var(--jakarta-font-family)]">
    <SidebarProvider>
      <AdminSiderBar />

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col min-w-0 bg-[#f5f6fa]">

        <!-- Premium Header -->
        <header class="h-16 flex items-center justify-between px-7 hdr-glass border-b border-gray-200/80 flex-shrink-0 sticky top-0 z-40">

          <!-- Left: Trigger + Breadcrumb + Title -->
          <div class="flex items-center gap-3">
            <SidebarTrigger class="!w-8 !h-8 !rounded-lg !border !border-gray-200 !text-gray-500 hover:!text-indigo-600 hover:!border-indigo-300 hover:!bg-indigo-50/50 transition-all duration-150" />
            <div class="w-px h-5 bg-gray-200"></div>
            <div class="flex items-center gap-1.5 text-[13px] font-medium text-gray-400">
              <span>Admin</span>
              <span class="opacity-40">/</span>
            </div>
            <h1 class="!text-[17px] !font-bold tracking-tight text-gray-900 !leading-normal">
              {{ currentPageTitle }}
            </h1>
          </div>

          <!-- Right: Actions -->
          <div class="flex items-center gap-2">

            <!-- Search -->
            <div class="flex items-center gap-2 px-3.5 py-2 bg-[#f5f6fa] border border-gray-200 rounded-xl text-gray-400 text-[13px] font-medium cursor-pointer min-w-[220px] transition-all duration-150 hover:border-indigo-300 hover:shadow-[0_0_0_3px_rgba(79,70,229,0.08)] hover:bg-white">
              <Search :size="15" :stroke-width="2" class="flex-shrink-0" />
              <span>Search...</span>
              <span class="font-mono text-[10px] font-medium bg-white border border-gray-200 rounded px-1.5 py-0.5 ml-auto text-gray-400 shadow-[0_1px_0_#e2e8f0]">Ctrl K</span>
            </div>

            <!-- Divider -->
            <div class="w-px h-7 bg-gray-200 mx-1"></div>

            <!-- Notification Bell -->
            <button
              ref="bellIconRef"
              @click="toggleNotificationDropdown(); markAllAsRead()"
              class="relative w-[38px] h-[38px] flex items-center justify-center border border-gray-200 rounded-lg bg-white text-gray-600 transition-all duration-150 hover:bg-gray-50 hover:text-indigo-600 hover:border-indigo-300 hover:shadow-[0_0_0_3px_rgba(79,70,229,0.08)] hover:-translate-y-px active:translate-y-0 active:scale-95"
            >
              <Bell :size="18" :stroke-width="1.7" />
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 bg-gradient-to-br from-red-500 to-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 border-[2.5px] border-white shadow-[0_2px_8px_rgba(239,68,68,0.35)] sb-badge-bounce"
              >
                {{ unreadCount }}
              </span>
            </button>

            <!-- Divider -->
            <div class="w-px h-7 bg-gray-200 mx-1"></div>

            <!-- Admin Profile -->
            <div class="hdr-profile-area relative" @click="toggleDropdown">
              <div class="flex items-center gap-2.5 px-2 py-1 rounded-xl cursor-pointer transition-all duration-150 border border-transparent hover:bg-gray-50 hover:border-gray-200">
                <!-- Avatar -->
                <div class="relative">
                  <div v-if="isLoading" class="w-9 h-9 rounded-lg bg-gray-200 animate-pulse"></div>
                  <div v-else class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white text-[13px] font-bold shadow-[0_2px_8px_rgba(79,70,229,0.2)] overflow-hidden">
                    <img
                      v-if="adminProfile.avatar"
                      :src="adminProfile.avatar"
                      alt="Avatar"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>{{ profileInitials }}</span>
                  </div>
                  <!-- Online dot -->
                  <span class="absolute -bottom-0.5 -right-0.5 w-[11px] h-[11px] bg-emerald-500 border-[2.5px] border-white rounded-full shadow-[0_0_4px_rgba(16,185,129,0.4)]"></span>
                </div>

                <!-- Info -->
                <div class="flex flex-col leading-tight">
                  <span class="text-[13.5px] font-semibold text-gray-900">{{ adminProfile.company_name || 'Loading...' }}</span>
                  <span class="text-[11px] text-gray-400 font-mono font-medium">administrator</span>
                </div>

                <ChevronsUpDown :size="16" :stroke-width="2" class="text-gray-300 ml-1" />
              </div>

              <!-- Profile Dropdown -->
              <Transition
                enter-active-class="transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]"
                enter-from-class="opacity-0 -translate-y-1.5 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-1.5 scale-[0.98]"
              >
                <div
                  v-if="showDropdown"
                  class="absolute right-0 mt-2 w-[220px] bg-white border border-gray-200 rounded-xl shadow-[0_12px_40px_-8px_rgba(0,0,0,0.12),0_4px_12px_rgba(0,0,0,0.04)] z-50 overflow-hidden p-1"
                >
                  <!-- Dropdown header -->
                  <div class="px-3 pt-2.5 pb-3 border-b border-gray-100 mb-1">
                    <p class="text-sm font-bold text-gray-900 tracking-tight">{{ adminProfile.company_name || 'Admin' }}</p>
                    <p class="text-[11.5px] text-gray-400 mt-0.5">{{ adminProfile.email || '' }}</p>
                  </div>

                  <Link
                    href="/admin/settings/profile"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-100"
                  >
                    <User :size="16" :stroke-width="1.7" class="text-gray-400" />
                    Profile Settings
                  </Link>

                  <Link
                    href="/admin/settings/profile"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-100"
                  >
                    <Settings :size="16" :stroke-width="1.7" class="text-gray-400" />
                    Account Settings
                  </Link>

                  <div class="h-px bg-gray-100 mx-2 my-1"></div>

                  <Link
                    :href="route('admin.logout')"
                    method="post"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-red-600 hover:bg-red-50 transition-all duration-100"
                  >
                    <LogOut :size="16" :stroke-width="1.7" />
                    Log out
                  </Link>
                </div>
              </Transition>
            </div>
          </div>

          <!-- Notification Dropdown -->
          <Transition
            enter-active-class="transition duration-250 ease-[cubic-bezier(0.16,1,0.3,1)]"
            enter-from-class="opacity-0 -translate-y-1.5 scale-[0.98]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-1.5 scale-[0.98]"
          >
            <div
              v-if="showingNotificationDropdown"
              ref="notificationDropdownRef"
              class="absolute right-[100px] top-[calc(100%+6px)] w-[400px] bg-white border border-gray-200 rounded-xl shadow-[0_12px_40px_-8px_rgba(0,0,0,0.12),0_4px_12px_rgba(0,0,0,0.04)] z-50 overflow-hidden"
            >
              <!-- Header -->
              <div class="flex justify-between items-center px-[18px] pt-4 pb-3 border-b border-gray-100">
                <h3 class="text-[15px] font-bold text-gray-900 tracking-tight">Notifications</h3>
                <button
                  @click="markAllAsRead"
                  class="text-xs font-semibold text-indigo-600 px-2 py-1 rounded hover:bg-indigo-50 transition-colors"
                >
                  Mark all read
                </button>
              </div>

              <!-- List -->
              <div class="overflow-y-auto max-h-[360px] sb-scroll-hide">
                <div v-if="notifications.length === 0" class="py-10 text-center text-gray-400 text-sm">
                  No notifications yet.
                </div>
                <div
                  v-for="notification in notifications"
                  :key="notification.id"
                  @click="handleNotificationClick(notification)"
                  class="flex gap-3 px-[18px] py-3.5 border-b border-gray-50 cursor-pointer transition-colors duration-100 hover:bg-gray-50 relative"
                  :class="!notification.read_at ? 'bg-gradient-to-r from-indigo-50/80 to-indigo-50/20' : ''"
                >
                  <!-- Unread left bar -->
                  <span
                    v-if="!notification.read_at"
                    class="absolute left-0 top-0 bottom-0 w-[3px] bg-gradient-to-b from-indigo-600 to-violet-600 rounded-r"
                  ></span>

                  <!-- Icon -->
                  <div class="w-[38px] h-[38px] rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-50 text-indigo-500">
                    <Bell :size="17" :stroke-width="1.8" />
                  </div>

                  <!-- Content -->
                  <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-semibold text-gray-900 mb-0.5">{{ notification.data.title }}</p>
                    <p class="text-xs text-gray-400 leading-snug truncate">{{ notification.data.message }}</p>
                    <p class="text-[10px] text-gray-300 font-mono font-medium mt-1">
                      {{ new Date(notification.created_at).toLocaleString() }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="py-3 text-center border-t border-gray-100">
                <button
                  @click="clearAllNotifications"
                  class="text-[13px] font-semibold text-indigo-600 px-4 py-1.5 rounded-lg hover:bg-indigo-50 transition-colors"
                >
                  Clear all notifications
                </button>
              </div>
            </div>
          </Transition>
        </header>

        <!-- Page Content -->
        <div class="flex-1 overflow-y-auto [&_.container]:!max-w-full">
          <slot />
        </div>
      </div>
    </SidebarProvider>
  </main>

  <Toaster class="pointer-events-auto" />
</template>
