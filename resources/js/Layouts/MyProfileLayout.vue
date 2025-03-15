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
  isMobile.value = window.innerWidth <= 480;
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
  <AuthenticatedHeaderLayout/>
  <main class="">
    <!-- Mobile menu button - only visible on mobile -->
    <button 
      @click="toggleMobileMenu" 
      class="hidden max-[480px]:flex items-center justify-center p-3 m-4 bg-[#153b4f] text-white rounded-md top-16 left-2 z-50"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
      <span class="ml-2">Show Menu</span>
    </button>
    
    <div class="py-customVerticalSpacing flex gap-10 max-[480px]:pt-2">
      <!-- Sidebar container -->
      <div 
        class="column sidebar sticky top-[3rem] sidebar-container pl-2"
        :class="{ 
          'w-[20%]': !isSidebarCollapsed && !isMobile, 
          'w-[80px]': isSidebarCollapsed && !isMobile,
          'fixed left-0 top-0 h-full w-[80%] z-50': isMobileMenuOpen,
          'hidden max-[480px]:hidden': !isMobileMenuOpen && isMobile
        }"
      >
        <SiderBar 
          @toggle-sidebar="toggleSidebar" 
          @toggle-mobile-menu="toggleMobileMenu"
        />
      </div>
      
      <!-- Overlay for mobile menu -->
      <div 
        v-if="isMobileMenuOpen" 
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
        @click="toggleMobileMenu"
      ></div>
      
      <!-- Content  -->
      <div 
        class="column" 
        :class="{ 
          'w-[75%]': !isSidebarCollapsed && !isMobile, 
          'w-[calc(100%-120px)]': isSidebarCollapsed && !isMobile,
          'w-full px-4': isMobile
        }"
      >
        <slot/>
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
  background-color: white;
  border-radius: 12px;
  /* z-index: 40; */
  transition: all 0.3s ease; /* Smooth transition for width and position change */
}

/* Mobile sidebar styling */
@media (max-width: 480px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    border-radius: 0;
    padding-top: 60px;
  }
}
</style>