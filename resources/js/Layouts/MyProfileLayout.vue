<script setup>
import SiderBar from '@/Components/SiderBar.vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from './AuthenticatedHeaderLayout.vue';
import { ref, provide } from 'vue';

// Create a reactive state for sidebar collapse
const isSidebarCollapsed = ref(false);

// Provide this state to child components (like SiderBar)
provide('isSidebarCollapsed', isSidebarCollapsed);

// Function to toggle sidebar state (can be called from SiderBar component via emit)
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

// Provide the toggle function to child components
provide('toggleSidebar', toggleSidebar);
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedHeaderLayout/>
    <main class="">
    <div class="py-customVerticalSpacing flex gap-10">
        <div class="column sidebar sticky top-[3rem] sidebar-container pl-2"
             :class="{ 'w-[20%]': !isSidebarCollapsed, 'w-[80px]': isSidebarCollapsed }">
            <SiderBar @toggle-sidebar="toggleSidebar"/>
        </div>
        <!-- Content  -->
          <div class="column" 
               :class="{ 'w-[75%]': !isSidebarCollapsed, 'w-[calc(100%-120px)]': isSidebarCollapsed }">
              <slot/>
         </div>
        <!-- Content  -->
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
    z-index: 40;
    transition: width 0.3s ease; /* Smooth transition for width change */
}
</style>