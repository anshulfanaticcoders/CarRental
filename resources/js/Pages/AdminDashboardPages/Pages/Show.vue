<template>
    <div>
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ page.title }}</h1>
        <Link :href="route('admin.pages.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
          Back to List
        </Link>
      </div>
      
      <div class="bg-white shadow-md rounded p-6">
        <div v-html="page.content"></div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3';
  
  defineProps({
    page: Object
  });
  </script>