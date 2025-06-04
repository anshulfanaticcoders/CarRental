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

</script>

<template>
    <Head title="Dashboard"/>
    <main class="">
        <div class="flex">
        <AdminSiderBar/>
        <!-- Content  -->
          <div class="column w-full flex flex-col" >
            <div class="py-5 px-5 text-white flex justify-between border-b bg-customDarkBlackColor">
                <p>{{ currentPageTitle }}</p>
            </div>
             <div class="h-[92vh] overflow-y-auto">
               <slot/>
             </div>
         </div>
        <!-- Content  -->
    </div>
</main>
</template>



<style scoped>
::-webkit-scrollbar {
    display: none;
}
</style>