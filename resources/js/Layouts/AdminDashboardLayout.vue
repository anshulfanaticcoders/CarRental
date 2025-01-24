<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard"/>
    <main class="">
        <div class="py-customVerticalSpacing flex gap-10">
        <AdminSiderBar/>
        <!-- Content  -->
          <div class="column w-[70%]" >
              <slot/>
         </div>
        <!-- Content  -->
    </div>
</main>
</template>



<style>

</style>