<template>
    <MyProfileLayout>
        <div>
            <h1 class="text-2xl font-bold mb-4">Damage Protection for Booking #{{ booking.booking_number }}</h1>
            <div class="mb-4">
                <h2 class="text-xl font-semibold mb-2">Before Images</h2>
                <div v-if="damageProtection && damageProtection.before_images.length">
                    <div v-for="image in damageProtection.before_images" :key="image" class="mb-2">
                        <img :src="`/storage/${image}`" alt="Before Image" class="w-32 h-32 object-cover">
                    </div>
                </div>
                <div v-else>
                    <input type="file" multiple @change="handleBeforeFileChange" accept="image/*">
                    <button @click="uploadBeforeFiles" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
                </div>
            </div>
            <div>
                <h2 class="text-xl font-semibold mb-2">After Images</h2>
                <div v-if="damageProtection && damageProtection.after_images.length">
                    <div v-for="image in damageProtection.after_images" :key="image" class="mb-2">
                        <img :src="`/storage/${image}`" alt="After Image" class="w-32 h-32 object-cover">
                    </div>
                </div>
                <div v-else>
                    <input type="file" multiple @change="handleAfterFileChange" accept="image/*">
                    <button @click="uploadAfterFiles" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const { props } = usePage();
const booking = props.booking;
const damageProtection = props.damageProtection;

const beforeFiles = ref([]);
const afterFiles = ref([]);

const handleBeforeFileChange = (event) => {
    beforeFiles.value = Array.from(event.target.files);
};

const handleAfterFileChange = (event) => {
    afterFiles.value = Array.from(event.target.files);
};

const uploadBeforeFiles = async () => {
    const formData = new FormData();

    beforeFiles.value.forEach(file => {
        formData.append('before_images[]', file);
    });

    try {
        await axios.post(route('vendor.damage-protection.store-before-images', { booking: booking.id }), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        window.location.reload();
    } catch (error) {
        console.error('Error uploading before images:', error);
    }
};



const uploadAfterFiles = async () => {
    const formData = new FormData();
    afterFiles.value.forEach(file => formData.append('after_images[]', file));

    try {
        await axios.post(`/vendor/bookings/${booking.id}/damage-protection/after-images`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        // Reload the page to show the uploaded images
        window.location.reload();
    } catch (error) {
        console.error('Error uploading after images:', error);
    }
};
</script>