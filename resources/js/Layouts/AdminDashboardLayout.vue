<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head } from '@inertiajs/vue3';
// import {
//   Breadcrumb,
//   BreadcrumbItem,
//   BreadcrumbLink,
//   BreadcrumbList,
// //   BreadcrumbSeparator,
// } from '@/components/ui/breadcrumb'
// // import { ChevronRightSquareIcon } from 'lucide-vue-next'
</script>

<template>
    <Head title="Dashboard"/>
    <main class="">
        <div class="flex">
        <AdminSiderBar/>
        <!-- Content  -->
          <div class="column w-full py-[1rem] flex flex-col" >
            <div class="mt-[0.75rem] ml-[1.5rem]">
                <p>Dashboard</p>
            </div>
              <slot/>
         </div>
        <!-- Content  -->
    </div>
</main>
</template>



<style>

</style>