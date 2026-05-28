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
} from 'lucide-vue-next';
import { Toaster } from '@/Components/ui/sonner';
import { SidebarProvider, SidebarTrigger } from '@/Components/ui/sidebar';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
});

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
  '/admin/newsletter-campaigns': 'Newsletter Campaigns',
  '/admin/newsletter-campaigns/create': 'Create Campaign',
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
  '/admin/settings/payable-amount': 'Payable Amount',
  '/admin/settings/homepage': 'Home Page Settings',
  '/admin/offers': 'Offers',
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
    // Vendor management
    case 'VendorRegisteredNotification':
      return '/vendors';
    case 'VendorStatusUpdatedNotification':
      return '/vendors';
    case 'VendorVehicleCreateNotification':
      return '/vendor-vehicles';
    case 'VehicleCreatedNotification':
      return '/vendor-vehicles';
    case 'BulkVehicleUploadAdminNotification':
      return '/vendor-vehicles';
    // User management
    case 'AccountCreatedNotification':
      return '/users';
    // Bookings
    case 'BookingCreatedAdminNotification':
      return '/customer-bookings';
    case 'BookingCancelledNotification':
      return '/customer-bookings/cancelled';
    case 'BookingStatusUpdatedAdminNotification':
    case 'BookingStatusUpdatedCustomerNotification': {
      const s = notification.data?.status;
      if (s === 'pending') return '/customer-bookings/pending';
      if (s === 'confirmed') return '/customer-bookings/confirmed';
      if (s === 'completed') return '/customer-bookings/completed';
      return '/customer-bookings';
    }
    // External API bookings
    case 'ApiBookingCreatedVendorNotification':
    case 'ApiBookingCancelledVendorNotification':
      return '/external-bookings';
    // Payments
    case 'AdminPaymentFailedNotification':
      return '/admin/payments';
    // Reviews
    case 'ReviewSubmittedAdminNotification':
    case 'ReviewSubmittedVendorNotification':
    case 'ReviewSubmittedCompanyNotification':
      return '/admin/reviews';
    // Messages
    case 'NewMessageNotification':
    case 'MessageReminderNotification':
      return '/contact-us-mails';
    // Affiliates
    case 'BusinessRegistrationAdminNotification':
      return '/admin/affiliate/partners';
    // Pending vendor bookings
    case 'PendingBookingReminderNotification':
      return '/customer-bookings/pending';
    // Contact form submissions
    case 'ContactUsNotification':
      return '/contact-us-mails';
    default:
      return '#';
  }
};

const currentPageTitle = computed(() => {
  const path = window.location.pathname;
  return props.title || routeTitles[path] || 'Dashboard';
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
    <title>{{ currentPageTitle }}</title>
  </Head>

  <main class="font-[var(--jakarta-font-family)]">
    <SidebarProvider class="admin-shell">
      <AdminSiderBar />

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col min-w-0 bg-[linear-gradient(180deg,#f8fafc_0%,#ffffff_45%,#f1f5f9_100%)]">

        <!-- Premium Header -->
        <header class="min-h-16 flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-7 hdr-glass border-b border-[#dceef6]/90 flex-shrink-0 sticky top-0 z-40">

          <!-- Left: Trigger + Breadcrumb + Title -->
          <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger class="!w-9 !h-9 !rounded-lg !border !border-[#b0d4e6] !bg-white/80 !text-[#153b4f] hover:!text-[#0891b2] hover:!border-[#22d3ee] hover:!bg-[#f0f8fc] hover:!shadow-[0_0_0_3px_rgba(34,211,238,0.12)] transition-all duration-150" />
            <div class="w-px h-5 bg-[#dceef6]"></div>
            <div class="hidden sm:flex items-center gap-1.5 text-[13px] font-medium text-slate-400">
              <span>Admin</span>
              <span class="opacity-40">/</span>
            </div>
            <h1 class="truncate !text-[17px] !font-bold tracking-tight text-[#0f2936] !leading-normal">
              {{ currentPageTitle }}
            </h1>
          </div>

          <!-- Right: Actions -->
          <div class="flex items-center gap-2">

            <!-- Search -->
            <div class="hidden md:flex items-center gap-2 px-3.5 py-2 bg-[#f8fafc] border border-[#dceef6] rounded-xl text-slate-400 text-[13px] font-medium cursor-pointer min-w-[220px] transition-all duration-150 hover:border-[#22d3ee] hover:shadow-[0_0_0_3px_rgba(34,211,238,0.12)] hover:bg-white">
              <Search :size="15" :stroke-width="2" class="flex-shrink-0" />
              <span>Search...</span>
              <span class="text-[10px] font-semibold bg-white border border-[#dceef6] rounded px-1.5 py-0.5 ml-auto text-[#2d7294] shadow-[0_1px_0_#dceef6]">Ctrl K</span>
            </div>

            <!-- Divider -->
            <div class="hidden sm:block w-px h-7 bg-[#dceef6] mx-1"></div>

            <!-- Notification Bell -->
            <button
              ref="bellIconRef"
              @click="toggleNotificationDropdown(); markAllAsRead()"
              class="relative w-[38px] h-[38px] flex items-center justify-center border border-[#dceef6] rounded-lg bg-white text-slate-600 transition-all duration-150 hover:bg-[#f0f8fc] hover:text-[#0891b2] hover:border-[#22d3ee] hover:shadow-[0_0_0_3px_rgba(34,211,238,0.12)] hover:-translate-y-px active:translate-y-0 active:scale-95"
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
            <div class="hidden sm:block w-px h-7 bg-[#dceef6] mx-1"></div>

            <!-- Admin Profile -->
            <div class="hdr-profile-area relative" @click="toggleDropdown">
              <div class="flex items-center gap-2.5 px-1.5 sm:px-2 py-1 rounded-xl cursor-pointer transition-all duration-150 border border-transparent hover:bg-[#f0f8fc] hover:border-[#dceef6]">
                <!-- Avatar -->
                <div class="relative">
                  <div v-if="isLoading" class="w-9 h-9 rounded-lg bg-gray-200 animate-pulse"></div>
                  <div v-else class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] flex items-center justify-center text-white text-[13px] font-bold shadow-[0_2px_10px_rgba(21,59,79,0.22)] overflow-hidden">
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
                <div class="hidden sm:flex flex-col leading-tight">
                  <span class="max-w-[160px] truncate text-[13.5px] font-semibold text-[#0f2936]">{{ adminProfile.company_name || 'Loading...' }}</span>
                  <span class="text-[11px] text-slate-400 font-medium">administrator</span>
                </div>

                <ChevronsUpDown :size="16" :stroke-width="2" class="hidden sm:block text-slate-300 ml-1" />
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
                  class="absolute right-0 mt-2 w-[220px] bg-white border border-[#dceef6] rounded-xl shadow-[0_18px_48px_-18px_rgba(21,59,79,0.32),0_8px_18px_rgba(21,59,79,0.08)] z-50 overflow-hidden p-1"
                >
                  <!-- Dropdown header -->
                  <div class="px-3 pt-2.5 pb-3 border-b border-[#dceef6] mb-1 bg-[#f8fafc] rounded-lg">
                    <p class="text-sm font-bold text-[#0f2936] tracking-tight">{{ adminProfile.company_name || 'Admin' }}</p>
                    <p class="text-[11.5px] text-slate-400 mt-0.5">{{ adminProfile.email || '' }}</p>
                  </div>

                  <Link
                    href="/admin/settings/profile"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-slate-600 hover:bg-[#f0f8fc] hover:text-[#153b4f] transition-all duration-100"
                  >
                    <User :size="16" :stroke-width="1.7" class="text-[#2d7294]" />
                    Profile Settings
                  </Link>

                  <Link
                    href="/admin/settings/profile"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-slate-600 hover:bg-[#f0f8fc] hover:text-[#153b4f] transition-all duration-100"
                  >
                    <Settings :size="16" :stroke-width="1.7" class="text-[#2d7294]" />
                    Account Settings
                  </Link>

                  <div class="h-px bg-[#dceef6] mx-2 my-1"></div>

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
              class="absolute right-4 sm:right-[100px] top-[calc(100%+6px)] w-[calc(100vw-2rem)] sm:w-[400px] bg-white border border-[#dceef6] rounded-xl shadow-[0_18px_48px_-18px_rgba(21,59,79,0.32),0_8px_18px_rgba(21,59,79,0.08)] z-50 overflow-hidden"
            >
              <!-- Header -->
              <div class="flex justify-between items-center px-[18px] pt-4 pb-3 border-b border-[#dceef6] bg-[#f8fafc]">
                <h3 class="text-[15px] font-bold text-[#0f2936] tracking-tight">Notifications</h3>
                <button
                  @click="markAllAsRead"
                  class="text-xs font-semibold text-[#0891b2] px-2 py-1 rounded hover:bg-[#ecfeff] transition-colors"
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
                  class="flex gap-3 px-[18px] py-3.5 border-b border-[#f1f5f9] cursor-pointer transition-colors duration-100 hover:bg-[#f8fafc] relative"
                  :class="!notification.read_at ? 'bg-gradient-to-r from-[#f0f8fc] to-[#ecfeff]/50' : ''"
                >
                  <!-- Unread left bar -->
                  <span
                    v-if="!notification.read_at"
                    class="absolute left-0 top-0 bottom-0 w-[3px] bg-gradient-to-b from-[#153b4f] to-[#22d3ee] rounded-r"
                  ></span>

                  <!-- Icon -->
                  <div class="w-[38px] h-[38px] rounded-lg flex items-center justify-center flex-shrink-0 bg-[#f0f8fc] text-[#0891b2]">
                    <Bell :size="17" :stroke-width="1.8" />
                  </div>

                  <!-- Content -->
                  <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-semibold text-[#0f2936] mb-0.5">{{ notification.data.title }}</p>
                    <p class="text-xs text-slate-400 leading-snug truncate">{{ notification.data.message }}</p>
                    <p class="text-[10px] text-slate-300 font-medium mt-1">
                      {{ new Date(notification.created_at).toLocaleString() }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="py-3 text-center border-t border-[#dceef6]">
                <button
                  @click="clearAllNotifications"
                  class="text-[13px] font-semibold text-[#0891b2] px-4 py-1.5 rounded-lg hover:bg-[#ecfeff] transition-colors"
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
