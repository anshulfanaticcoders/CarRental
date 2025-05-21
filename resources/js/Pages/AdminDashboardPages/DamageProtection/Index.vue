<template>
  <AdminDashboardLayout>
    <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
      <div class="flex items-center justify-between mt-[2rem]">
        <span class="text-[1.5rem] font-semibold">Damage Protection Records</span>
        <!-- Placeholder for search or other actions if needed in future -->
      </div>

      <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Booking ID</TableHead>
              <TableHead>Booking No.</TableHead>
              <TableHead>Vendor ID</TableHead>
              <TableHead>Vendor Name</TableHead>
              <TableHead>Customer ID</TableHead>
              <TableHead>Customer Name</TableHead>
              <TableHead>Before Images</TableHead>
              <TableHead>After Images</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="item in damageRecords" :key="item.id" class="hover:bg-gray-100">
              <TableCell>{{ item.id }}</TableCell>
              <TableCell>{{ item.booking_id }}</TableCell>
              <TableCell>{{ item.booking_number }}</TableCell>
              <TableCell>{{ item.vendor_id }}</TableCell>
              <TableCell>{{ item.vendor_name }}</TableCell>
              <TableCell>{{ item.customer_id }}</TableCell>
              <TableCell>{{ item.customer_name }}</TableCell>
              <TableCell>
                <Button v-if="item.before_images && item.before_images.length > 0"
                        size="sm" variant="outline"
                        @click="showImages('before', item.before_images)">
                  View ({{ item.before_images.length }})
                </Button>
                <span v-else>N/A</span>
              </TableCell>
              <TableCell>
                <Button v-if="item.after_images && item.after_images.length > 0"
                        size="sm" variant="outline"
                        @click="showImages('after', item.after_images)">
                  View ({{ item.after_images.length }})
                </Button>
                <span v-else>N/A</span>
              </TableCell>
            </TableRow>
            <TableRow v-if="!damageRecords || damageRecords.length === 0">
              <TableCell colspan="9" class="text-center">No damage protection records found.</TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <!-- TODO: Add Pagination if needed, similar to Vendors/Index.vue -->
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
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';

// Data will be passed by Inertia from the Laravel controller
const props = defineProps({
  damageRecords: Array,
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
</script>

<style scoped>
/* Styles from Vendors/Index.vue for table font sizes */
table th {
  font-size: 0.95rem;
}
table td {
  font-size: 0.875rem;
}

.max-h-\[70vh\] {
  max-height: 70vh;
}
</style>
