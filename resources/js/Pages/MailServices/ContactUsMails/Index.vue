<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

const submissions = ref([]);

onMounted(() => {
    submissions.value = usePage().props.submissions;
});
</script>

<template>
  <AdminDashboardLayout>
  <div class="p-6">
    <h2 class="text-xl font-bold mb-4">Contact Us Submissions</h2>
    <table class="w-full border-collapse border border-gray-300">
      <thead class="bg-gray-50">
        <tr class="bg-gray-200">
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="(submission, index) in submissions" :key="submission.id">
          <td class="px-6 py-4 whitespace-nowrap">{{ index+1}}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ submission.name }}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ submission.email }}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ submission.message }}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ submission.status }}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ submission.created_at }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</AdminDashboardLayout>
</template>
