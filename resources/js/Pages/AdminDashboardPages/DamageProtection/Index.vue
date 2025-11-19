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

      <!-- Gallery View Modal -->
      <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-[90vw] md:max-w-[80vw] lg:max-w-[70vw] xl:max-w-[60vw] max-h-[80vh] overflow-auto">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold capitalize">{{ currentImageType }} Images</DialogTitle>
          </DialogHeader>

          <div v-if="currentImages.length > 0" class="py-4">
            <div class="mb-4">
              <p class="text-sm text-gray-600">{{ currentImages.length }} image(s) found</p>
            </div>

            <!-- Image Grid Gallery -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div
                v-for="(image, index) in currentImages"
                :key="index"
                class="relative group cursor-pointer"
                @click="openMagnifier(index)"
              >
                <img
                  :src="image"
                  :alt="`${currentImageType} image ${index + 1}`"
                  class="w-full h-40 object-cover rounded-lg border border-gray-200 hover:border-blue-400 transition-colors"
                />
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all flex items-center justify-center">
                  <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <Badge variant="secondary" class="text-xs">
                      {{ index + 1 }}
                    </Badge>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="py-4">
            <p class="text-center text-gray-500">No images to display for this category.</p>
          </div>

          <DialogFooter>
            <Button variant="outline" @click="closeModal">Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Magnifier Modal -->
      <Dialog v-model:open="isMagnifierModalOpen">
        <DialogContent class="sm:max-w-[80vw] md:max-w-[70vw] lg:max-w-[60vw] xl:max-w-[50vw] max-h-[80vh] overflow-hidden">
          <DialogHeader>
            <DialogTitle class="text-xl font-semibold">Examine Damage - {{ currentImageType }} ({{ currentMagnifierIndex + 1 }}/{{ currentImages.length }})</DialogTitle>
          </DialogHeader>

          <div class="py-4">
            <!-- Magnifier Controls -->
            <div class="flex justify-between items-center mb-4">
              <div class="flex items-center gap-4">
                <label class="text-sm text-gray-600">Zoom Level:</label>
                <div class="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    @click="decreaseZoom"
                    :disabled="zoomLevel <= 1.5">
                    -
                  </Button>
                  <span class="text-sm font-medium w-12 text-center">{{ zoomLevel }}x</span>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="increaseZoom"
                    :disabled="zoomLevel >= 5">
                    +
                  </Button>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Magnifier:</label>
                <Button
                  :variant="magnifierEnabled ? 'default' : 'outline'"
                  size="sm"
                  @click="magnifierEnabled = !magnifierEnabled">
                  {{ magnifierEnabled ? 'ON' : 'OFF' }}
                </Button>
              </div>
            </div>

            <!-- Image Container with Magnifier -->
            <div class="relative inline-block" @mouseleave="hideMagnifier">
              <img
                ref="magnifierImage"
                :src="currentImages[currentMagnifierIndex]"
                alt="Damage Image"
                class="w-full h-auto max-h-[40vh] object-contain rounded cursor-crosshair"
                @mousemove="handleMagnifierMouseMove"
                @mouseenter="showMagnifier"
              />

              <!-- Magnifier Glass -->
              <div
                v-show="magnifierEnabled && isMagnifierVisible"
                :style="magnifierStyle"
                class="absolute border-2 border-blue-500 rounded-full pointer-events-none z-50 shadow-lg"
              >
                <div
                  :style="magnifierBackgroundStyle"
                  class="w-full h-full rounded-full overflow-hidden"
                ></div>
              </div>

              <!-- Navigation Buttons -->
              <Button v-if="currentImages.length > 1" @click="prevMagnifierImage"
                      variant="outline" size="icon"
                      class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white z-40">
                &#10094;
              </Button>
              <Button v-if="currentImages.length > 1" @click="nextMagnifierImage"
                      variant="outline" size="icon"
                      class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white z-40">
                &#10095;
              </Button>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" @click="closeMagnifierModal">Back to Gallery</Button>
            <Button @click="closeMagnifierModal">Close</Button>
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
const isMagnifierModalOpen = ref(false);
const currentImages = ref([]);
const currentImageType = ref(''); // 'before' or 'after'
const currentMagnifierIndex = ref(0);

// Magnifier state
const magnifierEnabled = ref(true);
const isMagnifierVisible = ref(false);
const magnifierStyle = ref({});
const magnifierBackgroundStyle = ref({});
const magnifierImage = ref(null);
const magnifierSize = 150; // Size of magnifier glass
const zoomLevel = ref(2.5); // Zoom level

const showImages = (type, images) => {
  if (images && images.length > 0) {
    currentImageType.value = type;
    currentImages.value = images;
    currentMagnifierIndex.value = 0;
    isModalOpen.value = true;
  }
};

const closeModal = () => {
  isModalOpen.value = false;
};

// Magnifier functions
const openMagnifier = (index) => {
  currentMagnifierIndex.value = index;
  isModalOpen.value = false; // Close gallery
  isMagnifierModalOpen.value = true; // Open magnifier
};

const closeMagnifierModal = () => {
  isMagnifierModalOpen.value = false;
  isModalOpen.value = true; // Return to gallery
};

const nextMagnifierImage = () => {
  if (currentImages.value.length > 0) {
    currentMagnifierIndex.value = (currentMagnifierIndex.value + 1) % currentImages.value.length;
  }
};

const prevMagnifierImage = () => {
  if (currentImages.value.length > 0) {
    currentMagnifierIndex.value = (currentMagnifierIndex.value - 1 + currentImages.value.length) % currentImages.value.length;
  }
};

// Zoom level controls
const increaseZoom = () => {
  if (zoomLevel.value < 5) {
    zoomLevel.value += 0.5;
  }
};

const decreaseZoom = () => {
  if (zoomLevel.value > 1.5) {
    zoomLevel.value -= 0.5;
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

// Magnifier functions
const showMagnifier = () => {
  if (magnifierEnabled.value) {
    isMagnifierVisible.value = true;
  }
};

const hideMagnifier = () => {
  isMagnifierVisible.value = false;
};

const handleMagnifierMouseMove = (event) => {
  if (!magnifierEnabled.value || !magnifierImage.value) return;

  const img = magnifierImage.value;
  const rect = img.getBoundingClientRect();

  // Get mouse position relative to image
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  // Calculate position for magnifier (centered on mouse)
  const magnifierX = x - magnifierSize / 2;
  const magnifierY = y - magnifierSize / 2;

  // Keep magnifier within image bounds
  const boundedX = Math.max(0, Math.min(magnifierX, rect.width - magnifierSize));
  const boundedY = Math.max(0, Math.min(magnifierY, rect.height - magnifierSize));

  // Calculate background position for zoomed image
  const bgX = -(x * zoomLevel.value - magnifierSize / 2);
  const bgY = -(y * zoomLevel.value - magnifierSize / 2);

  // Update magnifier styles
  magnifierStyle.value = {
    left: `${boundedX}px`,
    top: `${boundedY}px`,
    width: `${magnifierSize}px`,
    height: `${magnifierSize}px`,
  };

  magnifierBackgroundStyle.value = {
    backgroundImage: `url(${img.src})`,
    backgroundPosition: `${bgX}px ${bgY}px`,
    backgroundSize: `${rect.width * zoomLevel.value}px ${rect.height * zoomLevel.value}px`,
    backgroundRepeat: 'no-repeat',
  };
};
</script>
