<script setup>
import { Link } from "@inertiajs/vue3";
import Heart from "../../assets/Heart.svg";
import carIcon from "../../assets/carIcon.svg";
import walkIcon from "../../assets/walking.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import check from "../../assets/Check.svg";
import goIcon from "../../assets/goIcon.svg";
import { ref, onMounted } from "vue";
import axios from "axios";

// Fetching all vehicles
const vehicles = ref([]);

const fetchVehicles = async () => {
    try {
        const response = await axios.get("/api/vehicles");
        vehicles.value = response.data;
    } catch (error) {
        console.error("Error fetching vehicles:", error);
    }
};

onMounted(() => {
    fetchVehicles();
});

</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <div
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            class=" p-[2rem] rounded-[12px] border-[1px] border-[#E7E7E7]"
        >
            <div class="column flex justify-end">
                <Link href=""><img :src="Heart" alt="" class="w-full mb-[1rem]" /></Link>
            </div>
            <Link :href="`single-car/vehicle/${vehicle.id}`">
                <div class="column flex flex-col gap-5 items-start h-[20rem]">
                    <img
                            v-if="vehicle?.images"
                            :src="`/storage/${
                                vehicle.images.find(
                                    (image) =>
                                        image.image_type === 'primary'
                                )?.image_path
                            }`"
                            alt="Primary Image"
                            class="w-full h-[16rem] object-cover rounded-lg"
                        />
                    <span
                        class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                    >
                        {{ vehicle.model }}
                    </span>
                </div>

                <div class="column mt-[2rem]">
                    <h5 class="font-medium text-[1.5rem] text-customPrimaryColor">
                        {{ vehicle.brand }}
                    </h5>
                    <div class="car_short_info mt-[1rem] flex gap-3">
                        <img :src="carIcon" alt="" />
                        <div class="features">
                            <span class="capitalize text-[1.15rem]"
                                >{{ vehicle.transmission }} . {{ vehicle.fuel }} . {{ vehicle.seating_capacity }}
                                Seats</span
                            >
                        </div>
                    </div>
                    <div class="extra_details flex gap-5 mt-[1rem]">
                        <div class="col flex gap-3">
                            <img :src="walkIcon" alt="" /><span class="text-[1.15rem]">9.3 KM Away</span>
                        </div>
                        <div class="col flex gap-3">
                            <img :src="mileageIcon" alt="" /><span class="text-[1.15rem]">{{ vehicle.mileage }}km/d</span>
                        </div>
                    </div>

                    <div class="benefits mt-[2rem] grid grid-cols-2 gap-3">
                        <span class="flex gap-3 items-center text-[12px]">
                            <img :src="check" alt="" />Free Cancellation
                        </span>
                        <span class="flex gap-3 items-center text-[12px]">
                            <img :src="check" alt="" />Unlimited mileage
                        </span>
                        <span class="flex gap-3 items-center text-[12px]">
                            <img :src="check" alt="" />Unlimited kilometers
                        </span>
                    </div>

                    <div class="mt-[2rem] flex justify-between items-center">
                        <div>
                            <span
                                class="text-customPrimaryColor text-[1.875rem] font-medium"
                                >€{{ vehicle.price_per_day }}</span
                            ><span>/day</span>
                        </div>
                        <img :src="goIcon" alt="" />
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>


<style>


</style>
