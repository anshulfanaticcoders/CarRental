<template>
    <AdminDashboardLayout>
      <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
        <div class="flex items-center justify-between mt-[2rem]">
          <span class="text-[1.5rem] font-semibold">Vendor Profiles & Documents</span>
        </div>
  
        <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User Name</TableHead>
                <TableHead>Company Name</TableHead>
                <TableHead>Company Email</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Driving License</TableHead>
                <TableHead>Passport</TableHead>
                <TableHead>Passport Photo</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in users" :key="user.id">
                <TableCell>{{ user.first_name }} {{ user.last_name }}</TableCell>
                <TableCell>{{ user.vendor_profile?.company_name }}</TableCell>
                <TableCell>{{ user.vendor_profile?.company_email }}</TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(user.vendor_profile?.status)">
                    {{ user.vendor_profile?.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <img
                    @click="openImageModal(`/storage/${user.vendor_document?.driving_license}`)"
                    :src="`/storage/${user.vendor_document?.driving_license}`"
                    alt="Driving License"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                  />
                </TableCell>
                <TableCell>
                  <img
                    @click="openImageModal(`/storage/${user.vendor_document?.passport}`)"
                    :src="`/storage/${user.vendor_document?.passport}`"
                    alt="Passport"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                  />
                </TableCell>
                <TableCell>
                  <img
                    @click="openImageModal(`/storage/${user.vendor_document?.passport_photo}`)"
                    :src="`/storage/${user.vendor_document?.passport_photo}`"
                    alt="Passport Photo"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                  />
                </TableCell>
                <TableCell class="text-right">
                  <Select
                    v-model="user.vendor_profile.status"
                    @update:modelValue="(newStatus) => updateStatus(user, newStatus)"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Update Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="pending">Pending</SelectItem>
                      <SelectItem value="approved">Approved</SelectItem>
                      <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                  </Select>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
  
        <Dialog v-model:open="isImageModalOpen">
          <DialogContent class="max-w-4xl">
            <DialogHeader>
              <DialogTitle>Document Image</DialogTitle>
            </DialogHeader>
            <div class="flex justify-center">
              <img :src="selectedImageUrl" alt="Document" class="max-w-full max-h-[70vh] object-contain" />
            </div>
          </DialogContent>
        </Dialog>
      </div>
    </AdminDashboardLayout>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { router } from '@inertiajs/vue3';
  import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
  import Table from "@/Components/ui/table/Table.vue";
  import TableHeader from "@/Components/ui/table/TableHeader.vue";
  import TableRow from "@/Components/ui/table/TableRow.vue";
  import TableHead from "@/Components/ui/table/TableHead.vue";
  import TableBody from "@/Components/ui/table/TableBody.vue";
  import TableCell from "@/Components/ui/table/TableCell.vue";
  import Badge from "@/Components/ui/badge/Badge.vue";
  import Select from "@/Components/ui/select/Select.vue";
  import SelectContent from "@/Components/ui/select/SelectContent.vue";
  import SelectItem from "@/Components/ui/select/SelectItem.vue";
  import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
  import SelectValue from "@/Components/ui/select/SelectValue.vue";
  import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
  } from "@/Components/ui/dialog";
  
  const props = defineProps({
    users: Array,
  });
  
  const isImageModalOpen = ref(false);
  const selectedImageUrl = ref('');
  
  const openImageModal = (imageUrl) => {
    selectedImageUrl.value = imageUrl;
    isImageModalOpen.value = true;
  };
  
  const updateStatus = (user, newStatus) => {
    router.put(`/vendors/${user.vendor_profile.id}/status`, {
      status: newStatus,
    });
  };
  
  const getStatusBadgeVariant = (status) => {
    switch (status) {
      case 'approved':
        return 'default';
      case 'pending':
        return 'secondary';
      case 'rejected':
        return 'destructive';
      default:
        return 'default';
    }
  };
  </script>
  