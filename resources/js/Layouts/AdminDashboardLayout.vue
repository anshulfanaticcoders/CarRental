<script setup>
import AdminSiderBar from '@/Components/AdminSiderBar.vue';
</script>

<template>
    <main class="">
    <div class=" py-customVerticalSpacing flex gap-10">
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