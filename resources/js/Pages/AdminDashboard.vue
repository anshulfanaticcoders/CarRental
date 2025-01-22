<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";

// State
const userCount = ref<number>(0);
const users = ref<Array<Record<string, any>>>([]);
const loading = ref<boolean>(true);

// Fetch user count
const fetchUserCount = async () => {
  try {
    const response = await axios.get<{ count: number }>("/api/user-count");
    userCount.value = response.data.count; // Update the user count
  } catch (error) {
    console.error("Error fetching user count:", error);
  }
};

// Fetch users
const fetchUsers = async () => {
  try {
    const response = await axios.get<Array<Record<string, any>>>("/api/users");
    users.value = response.data;
  } catch (error) {
    console.error("Error fetching users:", error);
  }
};

// Lifecycle hook
onMounted(async () => {
  loading.value = true;
  await Promise.all([fetchUserCount(), fetchUsers()]); // Fetch data in parallel
  loading.value = false;
});
</script>

<template>
  <AdminDashboardLayout>
    <div>
    <div v-if="loading" class="text-gray-500">Loading...</div>

    <div v-else>
      <h1 class="text-lg font-bold">User Count: {{ userCount }}</h1>
      <ul>
        <li v-for="user in users" :key="user.id" class="text-gray-700">
          {{ user.name }}
        </li>
      </ul>
    </div>
  </div>
  </AdminDashboardLayout>
</template>
