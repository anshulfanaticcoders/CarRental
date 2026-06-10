<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import {
  ArrowRight,
  ChevronsUpDown,
  Bell,
  Command,
  Search,
  User,
  Settings,
  LogOut,
  X,
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
const showAdminSearch = ref(false);
const adminSearchQuery = ref('');
const adminSearchInputRef = ref(null);

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
  '/api-consumers': 'API Consumers',
  '/external-bookings': 'External Bookings',
  '/admin/api-analytics': 'API Analytics',
  '/admin/affiliate/overview': 'Affiliate Overview',
  '/admin/affiliate/partners': 'Affiliate Partners',
  '/admin/affiliate/commissions': 'Affiliate Commissions',
  '/admin/affiliate/payouts': 'Affiliate Payouts',
  '/damage-protection-records': 'Damage Protection Records',
  '/admin/settings/footer-categories': 'Footer Category',
  '/admin/settings/faq': 'FAQ Settings',
};

const normalizeAdminSearchText = (value) => String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();

const adminQuickLinks = computed(() =>
  Object.entries(routeTitles).map(([url, title]) => ({
    url,
    title,
    group: url.startsWith('/admin/affiliate')
      ? 'Affiliate'
      : url.includes('booking')
        ? 'Bookings'
        : url.includes('settings')
          ? 'Settings'
          : 'Admin',
  }))
);

const filteredAdminQuickLinks = computed(() => {
  const query = normalizeAdminSearchText(adminSearchQuery.value);
  const links = adminQuickLinks.value;

  if (!query) {
    return links.slice(0, 10);
  }

  return links
    .filter((item) => {
      const haystack = normalizeAdminSearchText(`${item.title} ${item.url} ${item.group}`);
      return haystack.includes(query);
    })
    .slice(0, 12);
});

const openAdminSearch = async () => {
  showAdminSearch.value = true;
  showingNotificationDropdown.value = false;
  showDropdown.value = false;
  await nextTick();
  adminSearchInputRef.value?.focus();
};

const closeAdminSearch = () => {
  showAdminSearch.value = false;
  adminSearchQuery.value = '';
};

const announceAdminRouteLoading = () => {
  window.dispatchEvent(new CustomEvent('admin-route-loading:start'));
};

const visitAdminRouteWithLoader = (url) => {
  announceAdminRouteLoading();
  window.setTimeout(() => router.visit(url), 50);
};

const visitAdminSearchResult = (item) => {
  closeAdminSearch();

  if (window.location.pathname !== item.url) {
    visitAdminRouteWithLoader(item.url);
  }
};

const handleAdminShortcut = (event) => {
  const key = event.key?.toLowerCase();

  if ((event.ctrlKey || event.metaKey) && key === 'k') {
    event.preventDefault();
    openAdminSearch();
    return;
  }

  if (event.key === 'Escape' && showAdminSearch.value) {
    closeAdminSearch();
  }
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
    visitAdminRouteWithLoader(link);
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

let adminTableObserver = null;
let tableEnhanceFrame = 0;

const normalizeTableLabel = (value) => value.replace(/\s+/g, ' ').trim();

const adminBadgeTones = [
  {
    tone: 'danger',
    terms: ['cancelled', 'canceled', 'failed', 'rejected', 'suspended', 'deleted', 'declined', 'unsubscribed', 'expired', 'overdue'],
  },
  {
    tone: 'warning',
    terms: ['pending', 'draft', 'inactive', 'processing', 'waiting', 'review', 'maintenance', 'unpaid', 'partial'],
  },
  {
    tone: 'success',
    terms: ['active', 'approved', 'available', 'completed', 'confirmed', 'paid', 'published', 'subscribed', 'verified', 'sent', 'success'],
  },
  {
    tone: 'info',
    terms: ['admin', 'vendor', 'customer', 'external', 'api', 'new', 'open', 'all', 'total'],
  },
];

const actionGlyphs = {
  view: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>',
  details: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>',
  edit: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
  delete: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>',
  add: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
  save: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
  close: '<svg class="admin-action-glyph" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
};

const resolveBadgeTone = (element, text) => {
  const value = normalizeAdminSearchText(`${text} ${element.className || ''}`);
  const match = adminBadgeTones.find(({ terms }) => terms.some((term) => value.includes(term)));
  return match?.tone || 'neutral';
};

const classifyAdminAction = (element) => {
  const text = normalizeAdminSearchText(`${element.getAttribute('aria-label') || ''} ${element.getAttribute('title') || ''} ${element.textContent || ''}`);

  if (!text || text.length > 80) return null;
  if (text.includes('delete') || text.includes('remove') || text.includes('reject')) return { action: 'delete', tone: 'danger' };
  if (text !== 'cancel' && text.includes('cancel')) return { action: 'delete', tone: 'danger' };
  if (text.includes('view') || text.includes('preview') || text.includes('show')) return { action: 'view', tone: 'info' };
  if (text.includes('detail') || text.includes('expand') || text.includes('hide')) return { action: 'details', tone: 'info' };
  if (text.includes('edit') || text.includes('update')) return { action: 'edit', tone: 'edit' };
  if (text.includes('create') || text.includes('add') || text.includes('new')) return { action: 'add', tone: 'success' };
  if (text.includes('save') || text.includes('apply') || text.includes('approve') || text.includes('confirm') || text.includes('complete')) return { action: 'save', tone: 'success' };
  if (text === 'close' || text === 'cancel') return { action: 'close', tone: 'neutral' };

  return null;
};

const enhanceAdminBadges = () => {
  const badgeSelector = [
    '.admin-content-surface div.inline-flex',
    '.admin-content-surface span.inline-flex',
    '.admin-content-surface span[class*="rounded-full"]',
    '.admin-content-surface div[class*="rounded-full"]',
    'body.admin-dark-active [role="dialog"] div.inline-flex',
    'body.admin-dark-active [role="dialog"] span.inline-flex',
  ].join(', ');

  document.querySelectorAll(badgeSelector).forEach((element) => {
    if (element.closest('button, a, [role="button"]')) return;

    const text = normalizeTableLabel(element.textContent || '');
    if (!text || text.length > 40) return;

    const isBadgeLike =
      element.classList.contains('rounded-full') ||
      element.classList.contains('rounded-md') ||
      element.className.includes('rounded-full') ||
      element.className.includes('rounded-md');

    if (!isBadgeLike) return;

    element.classList.add('admin-status-badge');
    element.classList.remove('admin-status-success', 'admin-status-warning', 'admin-status-danger', 'admin-status-info', 'admin-status-neutral');
    element.classList.add(`admin-status-${resolveBadgeTone(element, text)}`);
  });
};

const enhanceAdminActions = () => {
  const actionSelector = [
    '.admin-content-surface button',
    '.admin-content-surface a',
    '.admin-content-surface [role="button"]',
    'body.admin-dark-active [role="dialog"] button',
    'body.admin-dark-active [role="dialog"] a',
    'body.admin-dark-active [role="alertdialog"] button',
  ].join(', ');

  document.querySelectorAll(actionSelector).forEach((element) => {
    const action = classifyAdminAction(element);
    if (!action) return;

    element.classList.remove(
      'admin-action-info',
      'admin-action-edit',
      'admin-action-success',
      'admin-action-danger',
      'admin-action-neutral',
    );
    element.classList.add('admin-action-btn', `admin-action-${action.tone}`);
    element.setAttribute('data-admin-action', action.action);

    const hasIcon = Boolean(element.querySelector('svg, .admin-action-glyph'));
    element.classList.toggle('admin-action-has-icon', hasIcon);

    if (!hasIcon && actionGlyphs[action.action]) {
      element.insertAdjacentHTML('afterbegin', actionGlyphs[action.action]);
      element.classList.add('admin-action-has-icon');
    }
  });
};

const enhanceAdminTables = () => {
  if (typeof window === 'undefined') return;

  if (tableEnhanceFrame) {
    window.cancelAnimationFrame(tableEnhanceFrame);
  }

  tableEnhanceFrame = window.requestAnimationFrame(() => {
    document.querySelectorAll('.admin-content-surface table').forEach((table) => {
      const headers = Array.from(table.querySelectorAll('thead th')).map((header) =>
        normalizeTableLabel(header.textContent || '')
      );

      table.classList.add('admin-data-table');
      table.classList.toggle('is-wide', headers.length >= 8);
      table.classList.toggle('is-ultra-wide', headers.length >= 10);

      const frame = table.closest('.overflow-x-auto, .overflow-auto');
      if (frame) {
        frame.classList.add('admin-table-frame');
      }

      if (table.parentElement) {
        table.parentElement.classList.add('admin-table-scroll');
      }

      table.querySelectorAll('tbody tr').forEach((row) => {
        row.classList.add('admin-data-row');
        Array.from(row.children).forEach((cell, index) => {
          if (cell.tagName !== 'TD') return;
          cell.classList.add('admin-data-cell');
          if (headers[index]) {
            cell.setAttribute('data-admin-label', headers[index]);
          }
        });
      });
    });

    enhanceAdminBadges();
    enhanceAdminActions();
  });
};

const setupAdminTableObserver = () => {
  enhanceAdminTables();

  const content = document.querySelector('.admin-content-surface');
  if (!content || typeof MutationObserver === 'undefined') return;

  adminTableObserver = new MutationObserver(enhanceAdminTables);
  adminTableObserver.observe(content, { childList: true, subtree: true });
};

onMounted(() => {
  document.body.classList.add('admin-dark-active');
  fetchAdminProfile();
  fetchNotifications();
  setupAdminTableObserver();
  document.addEventListener('click', closeDropdownsOnOutsideClick);
  document.addEventListener('keydown', handleAdminShortcut);
});

onUnmounted(() => {
  document.body.classList.remove('admin-dark-active');
  if (tableEnhanceFrame) {
    window.cancelAnimationFrame(tableEnhanceFrame);
  }
  adminTableObserver?.disconnect();
  adminTableObserver = null;
  document.removeEventListener('click', closeDropdownsOnOutsideClick);
  document.removeEventListener('keydown', handleAdminShortcut);
});
</script>

<template>
  <Head>
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ currentPageTitle }}</title>
  </Head>

  <main class="admin-dark-root font-[var(--jakarta-font-family)]">
    <SidebarProvider class="admin-shell admin-shell-dark">
      <AdminSiderBar />

      <!-- Main Content Area -->
      <div class="admin-main-surface flex-1 flex flex-col min-w-0">

        <!-- Premium Header -->
        <header class="admin-topbar min-h-16 flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-7 hdr-glass border-b flex-shrink-0 sticky top-0 z-40">

          <!-- Left: Trigger + Breadcrumb + Title -->
          <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger class="!w-10 !h-10 !rounded-[13px] !border !border-[rgba(176,212,230,0.14)] !bg-[rgba(13,39,54,0.76)] !text-[#c7d8e2] hover:!text-white hover:!border-[rgba(34,211,238,0.32)] hover:!bg-[rgba(21,59,79,0.9)] hover:!shadow-[0_0_0_4px_rgba(34,211,238,0.08)] hover:!-translate-y-px active:!translate-y-0 active:!scale-[0.98] transition-[color,background-color,border-color,box-shadow,transform] duration-200 admin-ease-product" />
            <div class="w-px h-5 bg-[rgba(176,212,230,0.16)]"></div>
            <div class="hidden sm:flex items-center gap-1.5 text-[13px] font-medium text-[#6f8798]">
              <span>Admin</span>
              <span class="opacity-40">/</span>
            </div>
            <h1 class="truncate !text-[17px] !font-bold tracking-tight text-[#f4fbff] !leading-normal">
              {{ currentPageTitle }}
            </h1>
          </div>

          <!-- Right: Actions -->
          <div class="flex items-center gap-2">

            <!-- Search -->
            <button
              type="button"
              @click="openAdminSearch"
              class="hidden md:flex items-center gap-2 px-3.5 py-2 bg-[rgba(8,24,34,0.7)] border border-[rgba(176,212,230,0.14)] rounded-xl text-[#8da3b4] text-[13px] font-medium cursor-pointer min-w-[220px] transition-[color,background-color,border-color,box-shadow,transform] duration-200 hover:text-[#d8edf7] hover:border-[rgba(34,211,238,0.34)] hover:shadow-[0_0_0_4px_rgba(34,211,238,0.08)] hover:bg-[rgba(10,35,49,0.9)] focus-visible:text-[#d8edf7] focus-visible:border-[rgba(34,211,238,0.48)] active:scale-[0.98]"
              aria-label="Search admin pages"
            >
              <Search :size="15" :stroke-width="2" class="flex-shrink-0" />
              <span>Search...</span>
              <span class="text-[10px] font-semibold bg-[rgba(34,211,238,0.08)] border border-[rgba(34,211,238,0.18)] rounded px-1.5 py-0.5 ml-auto text-[#67e8f9] shadow-[0_1px_0_rgba(255,255,255,0.06)]">Ctrl K</span>
            </button>

            <!-- Divider -->
            <div class="hidden sm:block w-px h-7 bg-[rgba(176,212,230,0.14)] mx-1"></div>

            <!-- Notification Bell -->
            <button
              ref="bellIconRef"
              @click="toggleNotificationDropdown(); markAllAsRead()"
              class="relative w-[40px] h-[40px] flex items-center justify-center border border-[rgba(176,212,230,0.14)] rounded-[13px] bg-[rgba(13,39,54,0.76)] text-[#b8c7d2] transition-[color,background-color,border-color,box-shadow,transform] duration-200 hover:bg-[rgba(21,59,79,0.9)] hover:text-white hover:border-[rgba(34,211,238,0.34)] hover:shadow-[0_0_0_4px_rgba(34,211,238,0.08)] hover:-translate-y-px active:translate-y-0 active:scale-95"
            >
              <Bell :size="18" :stroke-width="1.7" />
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 bg-gradient-to-br from-red-500 to-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 border-[2.5px] border-[#07131c] shadow-[0_2px_12px_rgba(239,68,68,0.42)] sb-badge-bounce"
              >
                {{ unreadCount }}
              </span>
            </button>

            <!-- Divider -->
            <div class="hidden sm:block w-px h-7 bg-[rgba(176,212,230,0.14)] mx-1"></div>

            <!-- Admin Profile -->
            <div class="hdr-profile-area relative" @click="toggleDropdown">
              <div class="flex items-center gap-2.5 px-1.5 sm:px-2 py-1 rounded-xl cursor-pointer transition-[background-color,border-color,box-shadow,transform] duration-200 border border-transparent hover:bg-[rgba(21,59,79,0.46)] hover:border-[rgba(176,212,230,0.14)] hover:shadow-[0_0_0_4px_rgba(34,211,238,0.06)] hover:-translate-y-px active:translate-y-0">
                <!-- Avatar -->
                <div class="relative">
                  <div v-if="isLoading" class="w-9 h-9 rounded-lg bg-[rgba(176,212,230,0.12)] animate-pulse"></div>
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
                  <span class="absolute -bottom-0.5 -right-0.5 w-[11px] h-[11px] bg-emerald-500 border-[2.5px] border-[#07131c] rounded-full shadow-[0_0_8px_rgba(16,185,129,0.45)]"></span>
                </div>

                <!-- Info -->
                <div class="hidden sm:flex flex-col leading-tight">
                  <span class="max-w-[160px] truncate text-[13.5px] font-semibold text-[#f4fbff]">{{ adminProfile.company_name || 'Loading...' }}</span>
                  <span class="text-[11px] text-[#6f8798] font-medium">administrator</span>
                </div>

                <ChevronsUpDown :size="16" :stroke-width="2" class="hidden sm:block text-[#6f8798] ml-1" />
              </div>

              <!-- Profile Dropdown -->
              <Transition
                enter-active-class="transition duration-200 admin-ease-spring"
                enter-from-class="opacity-0 -translate-y-1.5 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-1.5 scale-[0.98]"
              >
                <div
                  v-if="showDropdown"
                  class="absolute right-0 mt-2 w-[220px] bg-[rgba(10,29,40,0.98)] border border-[rgba(176,212,230,0.16)] rounded-xl shadow-[0_24px_70px_rgba(0,0,0,0.42),0_0_0_1px_rgba(255,255,255,0.03)] z-50 overflow-hidden p-1"
                >
                  <!-- Dropdown header -->
                  <div class="px-3 pt-2.5 pb-3 border-b border-[rgba(176,212,230,0.13)] mb-1 bg-[rgba(21,59,79,0.36)] rounded-lg">
                    <p class="text-sm font-bold text-[#f4fbff] tracking-tight">{{ adminProfile.company_name || 'Admin' }}</p>
                    <p class="text-[11.5px] text-[#8da3b4] mt-0.5">{{ adminProfile.email || '' }}</p>
                  </div>

                  <Link
                    href="/admin/settings/profile"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-[#b8c7d2] hover:bg-[rgba(34,211,238,0.08)] hover:text-white transition-[color,background-color,transform] duration-150 active:scale-[0.98]"
                  >
                    <User :size="16" :stroke-width="1.7" class="text-[#67e8f9]" />
                    Profile Settings
                  </Link>

                  <Link
                    href="/admin/settings/profile"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-[#b8c7d2] hover:bg-[rgba(34,211,238,0.08)] hover:text-white transition-[color,background-color,transform] duration-150 active:scale-[0.98]"
                  >
                    <Settings :size="16" :stroke-width="1.7" class="text-[#67e8f9]" />
                    Account Settings
                  </Link>

                  <div class="h-px bg-[rgba(176,212,230,0.13)] mx-2 my-1"></div>

                  <Link
                    :href="route('admin.logout')"
                    method="post"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-red-300 hover:bg-[rgba(239,68,68,0.1)] hover:text-red-200 transition-[color,background-color,transform] duration-150 active:scale-[0.98]"
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
            enter-active-class="transition duration-250 admin-ease-spring"
            enter-from-class="opacity-0 -translate-y-1.5 scale-[0.98]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-1.5 scale-[0.98]"
          >
            <div
              v-if="showingNotificationDropdown"
              ref="notificationDropdownRef"
              class="absolute right-4 sm:right-[100px] top-[calc(100%+6px)] w-[calc(100vw-2rem)] sm:w-[400px] bg-[rgba(10,29,40,0.98)] border border-[rgba(176,212,230,0.16)] rounded-xl shadow-[0_24px_70px_rgba(0,0,0,0.42),0_0_0_1px_rgba(255,255,255,0.03)] z-50 overflow-hidden"
            >
              <!-- Header -->
              <div class="flex justify-between items-center px-[18px] pt-4 pb-3 border-b border-[rgba(176,212,230,0.13)] bg-[rgba(21,59,79,0.36)]">
                <h3 class="text-[15px] font-bold text-[#f4fbff] tracking-tight">Notifications</h3>
                <button
                  @click="markAllAsRead"
                  class="text-xs font-semibold text-[#67e8f9] px-2 py-1 rounded hover:bg-[rgba(34,211,238,0.08)] transition-colors"
                >
                  Mark all read
                </button>
              </div>

              <!-- List -->
              <div class="overflow-y-auto max-h-[360px] sb-scroll-hide">
                <div v-if="notifications.length === 0" class="py-10 text-center text-[#8da3b4] text-sm">
                  No notifications yet.
                </div>
                <div
                  v-for="notification in notifications"
                  :key="notification.id"
                  @click="handleNotificationClick(notification)"
                  class="flex gap-3 px-[18px] py-3.5 border-b border-[rgba(176,212,230,0.1)] cursor-pointer transition-colors duration-150 hover:bg-[rgba(21,59,79,0.34)] relative"
                  :class="!notification.read_at ? 'bg-gradient-to-r from-[rgba(21,59,79,0.46)] to-[rgba(34,211,238,0.08)]' : ''"
                >
                  <!-- Unread left bar -->
                  <span
                    v-if="!notification.read_at"
                    class="absolute left-0 top-0 bottom-0 w-[3px] bg-gradient-to-b from-[#153b4f] to-[#22d3ee] rounded-r"
                  ></span>

                  <!-- Icon -->
                  <div class="w-[38px] h-[38px] rounded-lg flex items-center justify-center flex-shrink-0 bg-[rgba(34,211,238,0.1)] text-[#67e8f9]">
                    <Bell :size="17" :stroke-width="1.8" />
                  </div>

                  <!-- Content -->
                  <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-semibold text-[#f4fbff] mb-0.5">{{ notification.data.title }}</p>
                    <p class="text-xs text-[#8da3b4] leading-snug truncate">{{ notification.data.message }}</p>
                    <p class="text-[10px] text-[#5f7484] font-medium mt-1">
                      {{ new Date(notification.created_at).toLocaleString() }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="py-3 text-center border-t border-[rgba(176,212,230,0.13)]">
                <button
                  @click="clearAllNotifications"
                  class="text-[13px] font-semibold text-[#67e8f9] px-4 py-1.5 rounded-lg hover:bg-[rgba(34,211,238,0.08)] transition-colors"
                >
                  Clear all notifications
                </button>
              </div>
            </div>
          </Transition>
        </header>

        <Transition
          enter-active-class="transition duration-200 admin-ease-product"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="showAdminSearch"
            class="admin-command-backdrop"
            role="presentation"
            @click.self="closeAdminSearch"
          >
            <div class="admin-command-panel" role="dialog" aria-modal="true" aria-label="Search admin pages">
              <div class="admin-command-input-wrap">
                <Search :size="19" :stroke-width="1.8" />
                <input
                  ref="adminSearchInputRef"
                  v-model="adminSearchQuery"
                  type="search"
                  class="admin-command-input"
                  placeholder="Search pages, bookings, vendors, reports..."
                  @keydown.enter.prevent="filteredAdminQuickLinks[0] && visitAdminSearchResult(filteredAdminQuickLinks[0])"
                />
                <button type="button" class="admin-command-close" aria-label="Close admin search" @click="closeAdminSearch">
                  <X :size="18" :stroke-width="1.9" />
                </button>
              </div>

              <div class="admin-command-results">
                <button
                  v-for="item in filteredAdminQuickLinks"
                  :key="item.url"
                  type="button"
                  class="admin-command-item"
                  @click="visitAdminSearchResult(item)"
                >
                  <span class="admin-command-icon">
                    <Command :size="15" :stroke-width="1.9" />
                  </span>
                  <span class="min-w-0">
                    <span class="admin-command-title">{{ item.title }}</span>
                    <span class="admin-command-meta">{{ item.group }} / {{ item.url }}</span>
                  </span>
                  <ArrowRight :size="16" :stroke-width="1.8" class="admin-command-arrow" />
                </button>

                <div v-if="filteredAdminQuickLinks.length === 0" class="admin-command-empty">
                  No admin page found for "{{ adminSearchQuery }}".
                </div>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Page Content -->
        <div class="admin-content-surface flex-1 overflow-y-auto [&_.container]:!max-w-full">
          <slot />
        </div>
      </div>
    </SidebarProvider>
  </main>

  <Toaster class="pointer-events-auto" />
</template>
