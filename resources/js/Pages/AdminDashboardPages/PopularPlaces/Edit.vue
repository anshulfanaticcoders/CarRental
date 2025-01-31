<!-- resources/js/Pages/AdminDashboardPages/PopularPlaces/Edit.vue -->
<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Edit Popular Place</span>
                <Link 
                    :href="route('popular-places.index')" 
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                >
                    Back to List
                </Link>
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-white">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Place Name -->
                    <div>
                        <InputLabel for="place_name" value="Place Name" />
                        <Input
                            id="place_name"
                            type="text"
                            v-model="form.place_name"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>

                    <!-- City, State, Country -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="city" value="City" />
                            <Input
                                id="city"
                                type="text"
                                v-model="form.city"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="state" value="State" />
                            <Input
                                id="state"
                                type="text"
                                v-model="form.state"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="country" value="Country" />
                            <Input
                                id="country"
                                type="text"
                                v-model="form.country"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Latitude & Longitude -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude" />
                            <Input
                                id="latitude"
                                type="number"
                                step="any"
                                v-model="form.latitude"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude" />
                            <Input
                                id="longitude"
                                type="number"
                                step="any"
                                v-model="form.longitude"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Image -->
                    <div>
                        <InputLabel for="image" value="Place Image" />
                        <div v-if="place.image" class="mt-2 mb-4">
                            <img 
                                :src="`/storage/${place.image}`" 
                                class="w-32 h-32 object-cover rounded"
                                :alt="place.place_name"
                            />
                        </div>
                        <Input
                            id="image"
                            type="file"
                            @input="form.image = $event.target.files[0]"
                            class="mt-1 block w-full"
                            accept="image/*"
                        />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link
                            :href="route('popular-places.index')"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        >
                            Cancel
                        </Link>
                        <Button type="submit" class="bg-primary">
                            Update Place
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    place: {
        type: Object,
        required: true
    }
});

const form = ref({
    place_name: props.place.place_name,
    city: props.place.city,
    state: props.place.state,
    country: props.place.country,
    latitude: props.place.latitude,
    longitude: props.place.longitude,
    image: null,
    _method: 'PUT'
});

const submit = () => {
    router.post(route('popular-places.update', props.place.id), form.value, {
        forceFormData: true
    });
};
</script>