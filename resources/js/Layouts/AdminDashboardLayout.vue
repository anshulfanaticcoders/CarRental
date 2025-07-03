<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';
import { ChevronsUpDown } from 'lucide-vue-next';

const showDropdown = ref(false);
const isLoading = ref(true); // Add loading state for skeleton

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
};

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

const adminProfile = ref({
    avatar: null,
    company_name: null,
    email: null,
});

onMounted(async () => {
    try {
        const response = await axios.get('/api/admin/profile');
        adminProfile.value = response.data;
    } catch (error) {
        console.error('Error fetching admin profile:', error);
    } finally {
        isLoading.value = false; // Set loading to false after fetch
    }
});

// Get current title based on route
const currentPageTitle = computed(() => {
  const path = window.location.pathname;
  return routeTitles[path] || 'Dashboard'; // Default to Dashboard if no match
});
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow">
        <title>Dashboard</title>
    </Head>
    <main class="">
        <div class="flex">
            <AdminSiderBar />
            <!-- Content -->
            <div class="column w-full flex flex-col">
                <div class="py-5 px-5 text-white flex justify-between border-b bg-customDarkBlackColor">
                    <p class="leading-8">{{ currentPageTitle }}</p>
                    <div class="relative" @click="toggleDropdown">
                        <div class="flex items-center cursor-pointer">
                            <!-- Skeleton loader for avatar -->
                            <div v-if="isLoading" class="w-8 h-8 rounded-full bg-gray-300 animate-pulse mr-2"></div>
                            <img v-else :src="adminProfile.avatar" alt="Admin Avatar" class="w-8 h-8 rounded-full mr-2">
                            <div>
                                <p class="text-sm">{{ adminProfile.company_name || 'Loading...' }}</p>
                                <p class="text-xs">{{ adminProfile.email || 'Loading...' }}</p>
                            </div>
                            <div class="ml-2">
                                <ChevronsUpDown />
                            </div>
                        </div>
                        <div v-if="showDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                            <Link href="/admin/settings/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Setting</Link>
                            <Link :href="route('admin.logout')" method="post" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</Link>
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
</style>