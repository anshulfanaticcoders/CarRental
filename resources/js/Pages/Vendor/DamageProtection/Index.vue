<template>
    <MyProfileLayout>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold">Damage Protection for Booking number :- </h1>
                <p class="bg-customPrimaryColor text-white px-3 rounded-[99px] text-[1.1rem]">{{ booking.booking_number }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex justify-between">

                <!-- Before Images Section -->
                <div class="w-[40%]">
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
                            <p v-if="beforeImageError" class="text-red-500 text-sm">{{ beforeImageError }}</p>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="!beforeImages.length"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:bg-gray-300"
                        >
                            Upload Before Images
                        </button>
                    </form>

                    <!-- Display Existing Before Images -->
                    <div v-if="damageProtection?.before_images?.length" class="grid grid-cols-3 gap-4">
                        <div 
                            v-for="(image, index) in damageProtection.before_images" 
                            :key="index" 
                            class="relative"
                        >
                            <img 
                                :src="'/storage/' + image" 
                                class="w-full h-[100px] hover:scale-105 cursor-pointer object-cover rounded"
                                @click="openImageModal(damageProtection.before_images, index)"
                            />
                        </div>
                    </div>
                    <p v-else class="text-gray-500">No before images uploaded yet.</p>
                    <button 
                        v-if="damageProtection?.before_images?.length"
                        @click="deleteBeforeImages"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-4"
                    >
                        Delete All Before Images
                    </button>
                </div>

                <!-- After Images Section -->
                <div class="w-[40%]">
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
                            <p v-if="afterImageError" class="text-red-500 text-sm">{{ afterImageError }}</p>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="!afterImages.length"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:bg-gray-300"
                        >
                            Upload After Images
                        </button>
                    </form>

                    <!-- Display Existing After Images -->
                    <div v-if="damageProtection?.after_images?.length" class="grid grid-cols-3 gap-4">
                        <div 
                            v-for="(image, index) in damageProtection.after_images" 
                            :key="index" 
                            class="relative"
                        >
                            <img 
                                :src="'/storage/' + image" 
                                class="w-full h-[100px] hover:scale-105 cursor-pointer object-cover rounded"
                                @click="openImageModal(damageProtection.after_images, index)"
                            />
                        </div>
                    </div>
                    <p v-else class="text-gray-500">No after images uploaded yet.</p>
                    <button 
                        v-if="damageProtection?.after_images?.length"
                        @click="deleteAfterImages"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-4"
                    >
                        Delete All After Images
                    </button>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal with Carousel -->
        <Dialog v-model:open="isImageModalOpen">
            <DialogContent class="max-w-[40rem]">
                <DialogHeader>
                    <DialogTitle>Image Viewer</DialogTitle>
                </DialogHeader>
                
                <Carousel class="relative w-full">
                    <CarouselContent>
                        <CarouselItem v-for="(image, index) in selectedImages" :key="index">
                            <div class="p-1">
                                <Card>
                                    <CardContent class="flex aspect-square items-center justify-center p-2">
                                        <img :src="'/storage/' + image" class="w-full h-auto rounded-lg object-cover" />
                                    </CardContent>
                                </Card>
                            </div>
                        </CarouselItem>
                    </CarouselContent>
                    <CarouselPrevious />
                    <CarouselNext />
                </Carousel>
                
                <DialogFooter>
                    <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" @click="isImageModalOpen = false">
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
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import { Card, CardContent } from '@/Components/ui/card';

const beforeImages = ref([]);
const afterImages = ref([]);
const beforeImageInput = ref(null);
const afterImageInput = ref(null);
const { booking, damageProtection: initialDamageProtection } = usePage().props;
const damageProtection = ref(initialDamageProtection);
const beforeImageError = ref("");
const afterImageError = ref("");
// Image Viewer Modal
const isImageModalOpen = ref(false);
const selectedImages = ref([]);
const currentIndex = ref(0);

const openImageModal = (images, index) => {
    selectedImages.value = images
    currentIndex.value = index
    isImageModalOpen.value = true
}

const handleBeforeFileUpload = (event) => {
    const files = Array.from(event.target.files)
    if (files.length > 5) {
        beforeImageError.value = "You can upload a maximum of 5 images at a time."
        beforeImages.value = []
        return
    }
    beforeImageError.value = ""
    beforeImages.value = files
}

const handleAfterFileUpload = (event) => {
    const files = Array.from(event.target.files)
    if (files.length > 5) {
        afterImageError.value = "You can upload a maximum of 5 images at a time."
        afterImages.value = []
        return
    }
    afterImageError.value = ""
    afterImages.value = files
}

const submitBeforeImages = () => {
    const form = new FormData()
    beforeImages.value.forEach(file => form.append('images[]', file))

    router.post(route('vendor.damage-protection.upload-before', { booking: booking.id }), form, {
        onSuccess: (page) => {
            damageProtection.value = page.props.damageProtection
            beforeImages.value = []
            beforeImageInput.value.value = null
        }
    })
}

const submitAfterImages = () => {
    const form = new FormData()
    afterImages.value.forEach(file => form.append('images[]', file))

    router.post(route('vendor.damage-protection.upload-after', { booking: booking.id }), form, {
        onSuccess: (page) => {
            damageProtection.value = page.props.damageProtection
            afterImages.value = []
            afterImageInput.value.value = null
        }
    })
}

const deleteBeforeImages = () => {
    router.delete(route('vendor.damage-protection.delete-before-images', { booking: booking.id }), { preserveScroll: true })
}

const deleteAfterImages = () => {
    router.delete(route('vendor.damage-protection.delete-after-images', { booking: booking.id }), { preserveScroll: true })
}
</script>
