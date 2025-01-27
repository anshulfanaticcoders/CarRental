<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
import { Head } from '@inertiajs/vue3';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
//   BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
// import { ChevronRightSquareIcon } from 'lucide-vue-next'
</script>

<template>
    <Head title="Dashboard"/>
    <main class="">
        <div class="flex">
        <AdminSiderBar/>
        <!-- Content  -->
          <div class="column w-full py-[1rem] flex flex-col" >
            <div class="mt-[0.75rem] ml-[1.5rem]">
                <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/">
                            Dashboard
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <!-- <BreadcrumbSeparator>
                        <ChevronRightSquareIcon/>
                    </BreadcrumbSeparator>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/components">
                            User Management
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator>
                        <ChevronRightSquareIcon/>
                    </BreadcrumbSeparator>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/components">
                            Add Users
                        </BreadcrumbLink>
                    </BreadcrumbItem> -->
                </BreadcrumbList>
            </Breadcrumb>
            </div>
              <slot/>
         </div>
        <!-- Content  -->
    </div>
</main>
</template>



<style>

</style>