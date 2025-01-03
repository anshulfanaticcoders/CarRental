<script setup>
import SiderBar from '@/Components/SiderBar.vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from './AuthenticatedHeaderLayout.vue';
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedHeaderLayout/>
    <main class="">
    <div class=" py-customVerticalSpacing flex gap-10">
        <div class="column w-[20%] sidebar sticky top-[3rem]">
            <SiderBar/>
        </div>
        <!-- Content  -->
          <div class="column w-[70%]" >
              <slot/>
         </div>
        <!-- Content  -->
    </div>
</main>
</template>
<style>
.sidebar {
    position: sticky; 
    top: 3rem; 
    height: 100vh;
    overflow-y: auto; 
    background-color: white; 
    z-index: 1000; 
}
</style>