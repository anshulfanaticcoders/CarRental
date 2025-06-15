<template>
    <MyProfileLayout>
        <div class="w-full">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between bg-customLightPrimaryColor p-3 md:p-4 rounded-[12px] mb-4 md:mb-6">
                <div class="flex flex-col md:flex-row md:gap-2 md:items-center mb-3 md:mb-0">
                    <span class="text-xl md:text-2xl font-bold text-gray-800">Damage Protection for Booking number :- </span>
                    <p class="bg-customPrimaryColor text-white px-3 py-1 rounded-[99px] text-base md:text-lg w-fit mt-1 md:mt-0">{{ booking.booking_number }}</p>
                </div>
                <Button variant="destructive" @click="navigateToBooking" class="w-full md:w-auto">
                    Cancel
                </Button>
            </div>

            <!-- Main Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 md:p-6">
                <div class="flex flex-col lg:flex-row lg:justify-between gap-8">
                    <!-- Before Images Section -->
                    <div class="w-full lg:w-[48%]">
                        <h2 class="text-xl font-semibold mb-4">Before Rental Images</h2>
                        
                        <form @submit.prevent="submitBeforeImages" class="mb-6">
                            <div class="mb-4">
                                <input 
                                    type="file" 
                                    multiple 
                                    accept="image/*" 
                                    @change="handleBeforeFileUpload"
                                    ref="beforeImageInput"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p v-if="beforeImageError" class="text-red-500 text-sm mt-1">{{ beforeImageError }}</p>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="!beforeImages.length"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:bg-gray-300 w-full md:w-auto"
                            >
                                Upload Before Images
                            </button>
                        </form>

                        <!-- Display Existing Before Images -->
                        <div v-if="damageProtection?.before_images?.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div 
                                v-for="(image, index) in damageProtection.before_images" 
                                :key="index" 
                                class="relative"
                            >
                                <img 
                                    :src="image" 
                                    class="w-full h-24 md:h-28 hover:scale-105 transition-transform cursor-pointer object-cover rounded"
                                    @click="openImageModal(damageProtection.before_images, index)"
                                />
                            </div>
                        </div>
                        <p v-else class="text-gray-500">No before images uploaded yet.</p>
                        <button 
                            v-if="damageProtection?.before_images?.length"
                            @click="deleteBeforeImages"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-4 w-full md:w-auto"
                        >
                            Delete All Before Images
                        </button>
                    </div>

                    <!-- Divider for mobile view -->
                    <div class="border-t border-gray-200 my-6 lg:hidden"></div>

                    <!-- After Images Section -->
                    <div class="w-full lg:w-[48%]">
                        <h2 class="text-xl font-semibold mb-4">After Rental Images</h2>
                        
                        <form @submit.prevent="submitAfterImages" class="mb-6">
                            <div class="mb-4">
                                <input 
                                    type="file" 
                                    multiple 
                                    accept="image/*" 
                                    @change="handleAfterFileUpload"
                                    ref="afterImageInput"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p v-if="afterImageError" class="text-red-500 text-sm mt-1">{{ afterImageError }}</p>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="!afterImages.length"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:bg-gray-300 w-full md:w-auto"
                            >
                                Upload After Images
                            </button>
                        </form>

                        <!-- Display Existing After Images -->
                        <div v-if="damageProtection?.after_images?.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div 
                                v-for="(image, index) in damageProtection.after_images" 
                                :key="index" 
                                class="relative"
                            >
                                <img 
                                    :src="image" 
                                    class="w-full h-24 md:h-28 hover:scale-105 transition-transform cursor-pointer object-cover rounded"
                                    @click="openImageModal(damageProtection.after_images, index)"
                                />
                            </div>
                        </div>
                        <p v-else class="text-gray-500">No after images uploaded yet.</p>
                        <button 
                            v-if="damageProtection?.after_images?.length"
                            @click="deleteAfterImages"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-4 w-full md:w-auto"
                        >
                            Delete All After Images
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal with Carousel -->
        <Dialog v-model:open="isImageModalOpen" class="sm:max-w-md">
            <DialogContent class="w-[90vw] max-w-[90vw] sm:max-w-2xl p-0 md:p-2">
                <DialogHeader class="p-4 pb-0">
                    <DialogTitle>Image Viewer</DialogTitle>
                </DialogHeader>
                
                <Carousel class="w-full">
                    <CarouselContent>
                        <CarouselItem v-for="(image, index) in selectedImages" :key="index">
                            <div class="p-1">
                                <Card>
                                    <CardContent class="flex aspect-square items-center justify-center p-2">
                                        <img :src="image" class="w-full h-auto max-h-[70vh] rounded-lg object-contain" />
                                    </CardContent>
                                </Card>
                            </div>
                        </CarouselItem>
                    </CarouselContent>
                    <CarouselPrevious class="hidden sm:flex" />
                    <CarouselNext class="hidden sm:flex" />
                </Carousel>
                
                <DialogFooter class="p-4 flex justify-center sm:justify-end">
                    <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 w-full sm:w-auto" @click="isImageModalOpen = false">
                        Close
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </MyProfileLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

const beforeImages = ref([]);
const afterImages = ref([]);
const beforeImageInput = ref(null);
const afterImageInput = ref(null);
const { booking, damageProtection: initialDamageProtection } = usePage().props;
const damageProtection = ref(initialDamageProtection);
const beforeImageError = ref("");
const afterImageError = ref("");
const toast = useToast();

// Image Viewer Modal
const isImageModalOpen = ref(false);
const selectedImages = ref([]);
const currentIndex = ref(0);

const openImageModal = (images, index) => {
    selectedImages.value = images;
    currentIndex.value = index;
    isImageModalOpen.value = true;
};

const handleBeforeFileUpload = (event) => {
    const files = Array.from(event.target.files);
    if (files.length > 5) {
        beforeImageError.value = "You can upload a maximum of 5 images at a time.";
        beforeImages.value = [];
        return;
    }
    beforeImageError.value = "";
    beforeImages.value = files;
};

const handleAfterFileUpload = (event) => {
    const files = Array.from(event.target.files);
    if (files.length > 5) {
        afterImageError.value = "You can upload a maximum of 5 images at a time.";
        afterImages.value = [];
        return;
    }
    afterImageError.value = "";
    afterImages.value = files;
};

const submitBeforeImages = () => {
    const form = new FormData();
    beforeImages.value.forEach(file => form.append('images[]', file));

    router.post(route('vendor.damage-protection.upload-before', { booking: booking.id }), form, {
        onSuccess: (page) => {
            damageProtection.value = page.props.damageProtection;
            beforeImages.value = [];
            beforeImageInput.value.value = null;
            toast.success('Before images uploaded successfully!');
        }
    });
};

const submitAfterImages = () => {
    const form = new FormData();
    afterImages.value.forEach(file => form.append('images[]', file));

    router.post(route('vendor.damage-protection.upload-after', { booking: booking.id }), form, {
        onSuccess: (page) => {
            damageProtection.value = page.props.damageProtection;
            afterImages.value = [];
            afterImageInput.value.value = null;
            toast.success('After images uploaded successfully!');
        }
    });
};

const deleteBeforeImages = () => {
    router.delete(route('vendor.damage-protection.delete-before-images', { booking: booking.id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (damageProtection.value) {
                damageProtection.value.before_images = [];
            }
            toast.success('All before images deleted successfully!');
        }
    });
};

const deleteAfterImages = () => {
    router.delete(route('vendor.damage-protection.delete-after-images', { booking: booking.id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (damageProtection.value) {
                damageProtection.value.after_images = [];
            }
            toast.success('All after images deleted successfully!');
        }
    });
};

const navigateToBooking = () => {
  router.visit(route('bookings.index'));
};
</script>
