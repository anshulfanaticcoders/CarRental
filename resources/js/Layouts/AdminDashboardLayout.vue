<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const unreadCount = ref(0);
const notifications = ref([]);
const showDropdown = ref(false);

// Define route to title mapping
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
  '/customer-bookings': 'All Bookings',
  '/customer-bookings/pending': 'Pending Bookings',
  '/customer-bookings/confirmed': 'Active Bookings',
  '/customer-bookings/completed': 'Completed Bookings',
  '/customer-bookings/cancelled': 'Cancelled Bookings',
  '/pages': 'All Pages',
  '/admin/contact-us': 'Contact Us',
  '/blogs': 'All Blogs',
  '/admin/payments': 'All Payments',
  '/users-report': 'Users Report',
  '/vendors-report': 'Vendors Report',
  '/business-report': 'Business Report',
  '/contact-us-mails': 'Contact Mails',
  '/activity-logs': 'All Activities',
  '/admin/settings/footer': 'Footer Location',
  '/admin/settings/footer-categories': 'Footer Category',
  '/admin/settings/faq': 'FAQ',
}


// Get current title based on route
const currentPageTitle = computed(() => {
  const path = window.location.pathname;
  return routeTitles[path] || 'Dashboard'; // Default to Dashboard if no match
});

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/notifications/unread');
        unreadCount.value = response.data.unread_count;
        notifications.value = response.data.notifications;
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
};

const markNotificationAsRead = async (id) => {
    try {
        await axios.post(`/notifications/mark-as-read/${id}`);
        fetchNotifications(); // Refresh notifications
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

// Mark all notifications as read when opening dropdown
const markAllAsRead = async () => {
    notifications.value.forEach(n => markNotificationAsRead(n.id));
    unreadCount.value = 0; // Reset badge count
};

onMounted(fetchNotifications);
</script>

<template>
    <Head title="Dashboard"/>
    <main class="">
        <div class="flex">
        <AdminSiderBar/>
        <!-- Content  -->
          <div class="column w-full py-[1rem] flex flex-col" >
            <div class="mt-[0.65rem] ml-[1.5rem] flex justify-between border-b">
                <p>{{ currentPageTitle }}</p>
                 <!-- Notification Bell -->
                 <div class="relative pr-[2rem]">
                        <button @click="showDropdown = !showDropdown" class="relative">
                            <Bell class="w-6 h-6 text-gray-700" />
                            <span v-if="unreadCount > 0"
                                class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                {{ unreadCount }}
                            </span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div v-if="showDropdown" class="absolute right-0 mt-2 w-64 bg-white border rounded-lg shadow-lg">
                            <div class="p-3 font-semibold border-b">Notifications</div>
                            <ul v-if="notifications.length">
                                <li v-for="notification in notifications" :key="notification.id" class="p-3 border-b hover:bg-gray-100 cursor-pointer" @click="markNotificationAsRead(notification.id)">
                                    <p class="text-sm">{{ notification.data.message }}</p>
                                    <span class="text-xs text-gray-500">{{ notification.created_at }}</span>
                                </li>
                            </ul>
                            <p v-else class="p-3 text-gray-500">No new notifications</p>
                            <button v-if="notifications.length" @click="markAllAsRead" class="w-full text-center p-2 bg-blue-600 text-white hover:bg-blue-700">
                                Mark all as read
                            </button>
                        </div>
                    </div>
                    <!-- End Notification Bell -->
            </div>
              <slot/>
         </div>
        <!-- Content  -->
    </div>
</main>
</template>



<style>

</style>