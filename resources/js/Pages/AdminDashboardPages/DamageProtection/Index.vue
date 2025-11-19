<template>
  <AdminDashboardLayout>
    <div class="w-[95%] ml-[1.5rem] p-6 space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">Damage Protection Records</h1>
        <!-- Placeholder for search or other actions if needed in future -->
      </div>

      <div v-if="damageRecords.data.length > 0" class="rounded-lg border bg-card shadow-sm overflow-hidden">
        <div class="overflow-x-auto max-w-full">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="whitespace-nowrap">ID</TableHead>
                <TableHead class="whitespace-nowrap">Booking Number</TableHead>
                <TableHead class="whitespace-nowrap">Customer</TableHead>
                <TableHead class="whitespace-nowrap">Email</TableHead>
                <TableHead class="whitespace-nowrap">Vendor</TableHead>
                <TableHead class="whitespace-nowrap">Vehicle Brand</TableHead>
                <TableHead class="whitespace-nowrap">Pickup Location</TableHead>
                <TableHead class="whitespace-nowrap">Date</TableHead>
                <TableHead class="whitespace-nowrap">Before Images</TableHead>
                <TableHead class="whitespace-nowrap">After Images</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(item, index) in damageRecords.data" :key="item.id">
                <TableCell class="whitespace-nowrap">{{ (damageRecords.current_page - 1) * damageRecords.per_page + index + 1 }}</TableCell>
                <TableCell class="whitespace-nowrap">{{ item.booking_number }}</TableCell>
                <TableCell class="whitespace-nowrap">
                <div>{{ item.customer_name }}</div>
                <div v-if="item.customer_id" class="text-xs text-gray-500">(ID: {{ item.customer_id }})</div>
              </TableCell>
                <TableCell class="whitespace-nowrap">{{ item.customer_email }}</TableCell>
                <TableCell class="whitespace-nowrap">
                <div>{{ item.vendor_name }}</div>
                <div v-if="item.vendor_id" class="text-xs text-gray-500">(ID: {{ item.vendor_id }})</div>
              </TableCell>
                <TableCell class="whitespace-nowrap">{{ item.vehicle_brand }}</TableCell>
                <TableCell class="whitespace-nowrap">{{ item.pickup_location }}</TableCell>
                <TableCell class="whitespace-nowrap">{{ formatDate(item.created_at) }}</TableCell>
                    <TableCell class="whitespace-nowrap">
                  <Badge v-if="item.before_images && item.before_images.length > 0"
                         variant="secondary"
                         class="cursor-pointer"
                         @click="showImages('before', item.before_images)">
                    View ({{ item.before_images.length }})
                  </Badge>
                  <span v-else class="text-gray-500">No Images</span>
                </TableCell>
                <TableCell class="whitespace-nowrap">
                  <Badge v-if="item.after_images && item.after_images.length > 0"
                         variant="secondary"
                         class="cursor-pointer"
                         @click="showImages('after', item.after_images)">
                    View ({{ item.after_images.length }})
                  </Badge>
                  <span v-else class="text-gray-500">No Images</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
        <div class="flex justify-end pt-4">
          <Pagination :currentPage="damageRecords.current_page"
                      :totalPages="damageRecords.last_page"
                      @page-change="handlePageChange" />
        </div>
      </div>
      <div v-else class="rounded-lg border bg-card p-8 text-center">
        No damage protection records found.
      </div>

      <!-- Modal for Image Carousel using Dialog component -->
      <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-[80%] md:max-w-[60%] lg:max-w-[50%] xl:max-w-[40%]">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold capitalize">{{ currentImageType }} Images</DialogTitle>
          </DialogHeader>
          
          <div v-if="currentImages.length > 0" class="py-4">
            <div class="relative">
              <img :src="currentImages[currentIndex]" alt="Damage Image" class="w-full h-auto max-h-[70vh] object-contain rounded mb-2"/>
              <Button v-if="currentImages.length > 1" @click="prevImage"
                      variant="outline" size="icon"
                      class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white">
                &#10094;
              </Button>
              <Button v-if="currentImages.length > 1" @click="nextImage"
                      variant="outline" size="icon"
                      class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white">
                &#10095;
              </Button>
            </div>
            <p class="text-center mt-2 text-sm text-gray-600">{{ currentIndex + 1 }} / {{ currentImages.length }}</p>
          </div>
          <div v-else class="py-4">
            <p class="text-center text-gray-500">No images to display for this category.</p>
          </div>

          <DialogFooter>
            <Button variant="outline" @click="closeModal">Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import { ref, defineProps } from 'vue';
import { router } from "@inertiajs/vue3";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

// Data will be passed by Inertia from the Laravel controller
const props = defineProps({
  damageRecords: Object, // Expecting a paginator object
});

const isModalOpen = ref(false);
const currentImages = ref([]);
const currentIndex = ref(0);
const currentImageType = ref(''); // 'before' or 'after'

const showImages = (type, images) => {
  if (images && images.length > 0) {
    currentImageType.value = type;
    currentImages.value = images;
    currentIndex.value = 0;
    isModalOpen.value = true;
  }
};

const closeModal = () => {
  isModalOpen.value = false;
};

const nextImage = () => {
  if (currentImages.value.length > 0) {
    currentIndex.value = (currentIndex.value + 1) % currentImages.value.length;
  }
};

const prevImage = () => {
  if (currentImages.value.length > 0) {
    currentIndex.value = (currentIndex.value - 1 + currentImages.value.length) % currentImages.value.length;
  }
};

const handlePageChange = (page) => {
  router.get(route('admin.damage-protection.index', { page: page }), {}, {
    preserveState: true,
    replace: true,
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};
</script>
