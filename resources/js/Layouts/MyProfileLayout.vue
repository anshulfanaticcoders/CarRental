<script setup>
import SiderBar from '@/Components/SiderBar.vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from './AuthenticatedHeaderLayout.vue';
import { ref, provide, onMounted, onBeforeUnmount } from 'vue';

// Create a reactive state for sidebar collapse
const isSidebarCollapsed = ref(false);

// Add mobile menu state
const isMobileMenuOpen = ref(false);
const isMobile = ref(false);

// Provide this state to child components (like SiderBar)
provide('isSidebarCollapsed', isSidebarCollapsed);
provide('isMobileMenuOpen', isMobileMenuOpen);
provide('isMobile', isMobile);

// Function to toggle sidebar state (can be called from SiderBar component via emit)
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

// Function to toggle mobile menu
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// Function to check if mobile view
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768;
  // Auto-collapse sidebar on mobile
  if (isMobile.value) {
    isSidebarCollapsed.value = true;
  }
};

// Provide the toggle functions to child components
provide('toggleSidebar', toggleSidebar);
provide('toggleMobileMenu', toggleMobileMenu);

onMounted(() => {
  // Check mobile on mount
  checkMobile();

  // Add resize listener
  window.addEventListener('resize', checkMobile);
});

onBeforeUnmount(() => {
  // Clean up event listener
  window.removeEventListener('resize', checkMobile);
});
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedHeaderLayout />
  <main class="">
    <div class="hidden max-[768px]:block max-[768px]:absolute max-[768px]:top-0 max-[768px]:right-0">
      <!-- Mobile menu button - only visible on mobile -->
      <button @click="toggleMobileMenu"
        class="hidden max-[768px]:flex items-center justify-center px-3 py-2 m-4 bg-[#153b4f] text-white rounded-md top-16 left-2 z-50">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>

    <div class="py-customVerticalSpacing flex gap-10 max-[768px]:pt-2">
      <!-- Sidebar container with improved transition classes -->
      <div class="column sidebar sticky top-[3rem] sidebar-container px-2 max-[768px]:!pt-1" 
        :class="{
          'w-[20%]': !isSidebarCollapsed && !isMobile,
          'w-[80px]': isSidebarCollapsed && !isMobile,
          'mobile-sidebar-open': isMobileMenuOpen,
          'mobile-sidebar-closed': !isMobileMenuOpen && isMobile
        }">
        <SiderBar @toggle-sidebar="toggleSidebar" @toggle-mobile-menu="toggleMobileMenu" />
      </div>

      <!-- Overlay for mobile menu with fade transition -->
      <div v-if="isMobileMenuOpen" 
           class="fixed inset-0 bg-black bg-opacity-50 z-40 overlay-transition" 
           @click="toggleMobileMenu"></div>

      <!-- Content -->
      <div class="column transition-all duration-300 ease-in-out" 
        :class="{
          'w-[75%]': !isSidebarCollapsed && !isMobile,
          'w-[calc(100%-120px)]': isSidebarCollapsed && !isMobile,
          'w-full px-4': isMobile
        }">
        <slot />
      </div>
    </div>
  </main>
</template>

<style>
.sidebar {
  position: sticky;
  top: 2rem;
  height: 100vh;
  overflow-y: auto;
  background-color: #154D6A0D;
  border-radius: 12px;
  transition: all 0.3s ease;
  overflow-x: hidden;
}

/* Mobile sidebar styling with improved animations */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    border-radius: 0;
    padding-top: 60px;
    background-color: white;
    height: 100vh;
    z-index: 50;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  
  .mobile-sidebar-open {
    transform: translateX(0);
    width: 80%;
    transition: transform 0.3s ease-out;
  }
  
  .mobile-sidebar-closed {
    transform: translateX(-100%);
    width: 80%;
    transition: transform 0.3s ease-in;
  }
  
  .overlay-transition {
    animation: fadeIn 0.3s ease-out;
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>