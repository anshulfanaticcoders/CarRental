<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import SidebarInset from "@/Components/ui/sidebar/SidebarInset.vue";

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
    <div class="admin-dashboard">
      <SidebarInset>
      <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
          <div class="aspect-video rounded-xl bg-muted/50" />
          <div class="aspect-video rounded-xl bg-muted/50" />
          <div class="aspect-video rounded-xl bg-muted/50" />
        </div>
        <div class="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min" />
      </div>
    </SidebarInset>
  </div>
  </AdminDashboardLayout>
</template>

<style>
.admin-dashboard .bg-muted\/50 {
  background-color: #2b2b2b1f;
}
</style>
