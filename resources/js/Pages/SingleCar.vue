<script setup>
// car overview icons import
import doorIcon from "../../assets/door.svg";
import luggageIcon from "../../assets/luggage.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import carbonIcon from "../../assets/carbon-emmision.svg";
import ageIcon from "../../assets/age.svg";
import enginepowerIcon from "../../assets/enginepower.svg";


import ShareIcon from "../../assets/ShareNetwork.svg";
import Heart from "../../assets/Heart.svg";
import carIcon from "../../assets/carIcon.svg";
import walkIcon from "../../assets/walking.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIcon.svg";
import pencilIcon from "../../assets/Pencil.svg";
import partnersIcon from "../../assets/partners.svg";
import infoIcon from "../../assets/WarningCircle.svg";
import { Head, Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";

// Fetching Vehicle Details
const vehicle = ref(null);
const vehicleId = 5;

const fetchVehicleData = async () => {
    try {
        const response = await axios.get(`/api/vehicle/${vehicleId}`);
        vehicle.value = response.data;
    } catch (error) {
        console.error("Error fetching vehicle data:", error);
    }
};

onMounted(() => {
    fetchVehicleData();
});

// Feature-Icon Mapping
const featureIconMap = {
    "Bluetooth": "/storage/icons/bluetooth.svg",
    "Music System": "/storage/icons/music.svg",
    "Toolkit": "/storage/icons/toolkit.svg",
    "USB Charger": "/storage/icons/usb.svg",
    "Key Lock": "/storage/icons/lock.svg",
    "Back Camera": "/storage/icons/camera.svg",
    "Voice Control": "/storage/icons/voiceControl.svg",
    "Navigation": "/storage/icons/navigation.svg",
};
</script>

<template>
    <Head title="Single Car" />
    <AuthenticatedHeaderLayout/>
    <main>
        <section>
            <div class="container py-customVerticalSpacing">
                <div class="flex gap-5 items-center mb-5">
                    <h4 class="font-medium">{{ vehicle?.brand }}</h4>
                    <span
                        class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                        >{{ vehicle?.category.name }}</span
                    >
                </div>
                <div class="flex gap-5 items-center text-[1.25rem]">
                    <div class="car_ratings">5(1)</div>
                    <div class="dot_seperator">
                        <strong>.</strong>
                    </div>
                    <div class="car_location">
                        <span>{{ vehicle?.location }}</span>
                    </div>
                </div>
                <div class="gallery w-full mt-[4rem] flex gap-5">
                    <div class="primary-image mb-4">
                        <img
                            v-if="vehicle?.images"
                            :src="`/storage/${
                                vehicle.images.find(
                                    (image) =>
                                        image.image_type === 'primary'
                                )?.image_path
                            }`"
                            alt="Primary Image"
                            class="w-full h-auto object-cover rounded-lg"
                        />
                    </div>

                    <!-- Display the gallery images -->
                    <div
                        class="gallery w-full grid grid-cols-2  gap-4"
                    >
                        <div
                            v-for="(
                                image, index
                            ) in vehicle?.images.filter(
                                (image) =>
                                    image.image_type === 'gallery'
                            )"
                            :key="image.id"
                            class="gallery-item"
                        >
                            <img
                                :src="`/storage/${image.image_path}`"
                                :alt="`Gallery Image ${index + 1}`"
                                class="w-full h-auto object-cover rounded-lg"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-[4rem]">
                    <div class="column w-[50%]">
                        <div class="column flex flex-col gap-10">
                            <!-- Vehicle Features Section -->
                            <span class="text-[2rem] font-medium">Car Overview</span>
                               <div class="features grid grid-cols-4 gap-x-[2rem] gap-y-[2rem]">
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="peopleIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">People</span>
                                        <span class="font-medium">0{{ vehicle?.seating_capacity }}</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="doorIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Doors</span>
                                        <span class="font-medium">0{{ vehicle?.number_of_doors}}</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="luggageIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Luggage</span>
                                        <span class="font-medium">0{{ vehicle?.luggage_capacity}}</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="transmisionIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Transmission</span>
                                        <span class="font-medium capitalize">{{ vehicle?.transmission}}</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="fuelIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Fuel Type</span>
                                        <span class="font-medium capitalize">{{ vehicle?.fuel}}</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="enginepowerIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Horsepower</span>
                                        <span class="font-medium">{{ vehicle?.horsepower}} hp</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="carbonIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Co2 Emission</span>
                                        <span class="font-medium">{{ vehicle?.co2}} (g/km)</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="mileageIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Mileage</span>
                                        <span class="font-medium">{{ vehicle?.mileage}} km/d</span>
                                       </div>
                                   </div>
                                   <div class="feature-item items-center flex gap-3">
                                       <img :src="ageIcon" alt="">
                                       <div class="flex flex-col">
                                        <span class="text-customLightGrayColor">Minimum Driving Age</span>
                                        <span class="font-medium">21</span>
                                       </div>
                                   </div>
                               </div>
                        </div>

                        <div class="features mt-[3rem]">
                            <span class="text-[2rem] font-medium"
                                >Features</span
                            >
                            <div class="grid grid-cols-4 mt-[2rem] gap-y-[2rem]">
                                <div class="flex items-center gap-3" v-if="vehicle?.features" v-for="(feature, index) in JSON.parse(vehicle.features)" :key="index">
                                    <img :src="featureIconMap[feature]" alt="Feature Icon" class="feature-icon" />
                                  {{ feature }} 
                                 </div>
                                 <div v-else>
                                     <p>No features available.</p>
                                 </div>
                            </div>
                        </div>

                        <div class="features mt-[3rem]">
                            <span class="text-[2rem] font-medium"
                                >Car Location</span
                            >
                            <div class="mt-[2rem] gap-y-[2rem]">
                                {{ vehicle?.location }}
                            </div>
                        </div>

                    </div>
                    <div class="column w-[40%]">
                        <div
                            class="rounded-[12px] border-[1px] border-[#153B4F] p-5 sticky top-[153px]"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <h4>{{ vehicle?.brand }}</h4>
                                <span
                                    class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                                    >{{ vehicle?.category.name }}</span
                                >
                                <div class="icons flex items-center gap-3">
                                    <Link href="" class="w-full"
                                        ><img :src="ShareIcon" alt=""
                                    /></Link>
                                    <Link href="" class="w-full"
                                        ><img :src="Heart" alt=""
                                    /></Link>
                                </div>
                            </div>
                            <div class="">
                                <span>Hosted by
                                    <span class="vendorName uppercase">
                                        {{ vehicle?.user.first_name}}
                                        {{ vehicle?.user.last_name}}
                                    </span>
                                </span>
                            </div>
                            <div class="car_short_info mt-[1rem] flex gap-3">
                                <img :src="carIcon" alt="" />
                                <div class="features">
                                    <span class="text-[1.15rem] capitalize">
                                        {{ vehicle?.transmission}} . {{ vehicle?.fuel}} . {{ vehicle?.seating_capacity}} Seats
                                    </span>
                                </div>
                            </div>
                            <div class="extra_details flex gap-5 mt-[1rem]">
                                <div class="col flex gap-3">
                                    <img :src="walkIcon" alt="" /><span
                                        class="text-[1.15rem]"
                                        >9.3 KM Away</span
                                    >
                                </div>
                                <div class="col flex gap-3">
                                    <img :src="mileageIcon" alt="" /><span
                                        class="text-[1.15rem]"
                                        >{{ vehicle?.mileage}} km/d</span
                                    >
                                </div>
                            </div>

                            <div class="ratings"></div>

                            <div class="location mt-[2rem]">
                                <span
                                    class="text-[1.5rem] font-medium mb-[1rem] inline-block"
                                    >Location</span
                                >
                                <div class="col flex items-start gap-4">
                                    <img :src="pickupLocationIcon" alt="" />
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[1.25rem] text-medium"
                                            >Zaragoza Railway Station,
                                            Dubai</span
                                        ><span class=""
                                            >20 Nov 2023, 02:30 PM</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="col flex items-start gap-4 mt-[2.5rem]"
                                >
                                    <img :src="returnLocationIcon" alt="" />
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[1.25rem] text-medium"
                                            >Zaragoza Railway Station,
                                            Dubai</span
                                        ><span class=""
                                            >20 Nov 2023, 02:30 PM</span
                                        >
                                    </div>
                                </div>

                                <div
                                    class="edit mt-[1rem] border-b-[1px] border-[#2B2B2B] pb-[2rem]"
                                >
                                    <button
                                        class="bg-[#153B4F1A] px-6 py-2 flex items-center gap-2 border-[1px] border-customPrimaryColor rounded-[84px]"
                                    >
                                        Edit <img :src="pencilIcon" alt="" />
                                    </button>
                                </div>

                                <div class="pricing py-5">
                                    <div
                                        class="column flex items-center justify-between"
                                    >
                                        <span class="text-[1.25rem]"
                                            >Total Price</span
                                        >
                                        <div>
                                            <span
                                                class="text-customPrimaryColor text-[1.875rem] font-medium"
                                                >€{{ vehicle?.price_per_day}}</span
                                            ><span>/day</span>
                                            <br />
                                            <span class="flex gap-3"
                                                >incl. VAT
                                                <img :src="infoIcon" alt=""
                                            /></span>
                                        </div>
                                    </div>
                                    <div class="column mt-[2rem]">
                                        <button
                                            class="button-primary p-5 w-full"
                                        >
                                            Proceed to Pay
                                        </button>
                                    </div>
                                    <div
                                        class="column text-center mt-[2rem] flex flex-col justify-center gap-5"
                                    >
                                        <p>Guaranteed safe & secure checkout</p>
                                        <img :src="partnersIcon" alt="" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-customVerticalSpacing"></section>
    </main>
</template>

<style>
.overview .col:not(:last-child) {
    border-bottom: 1px solid #2b2b2b;
}
.overview .col {
    padding: 2rem;
}
</style>
