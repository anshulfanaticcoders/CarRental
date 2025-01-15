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
import pencilIcon from "../../assets/Pencil.svg";
import partnersIcon from "../../assets/partners.svg";
import infoIcon from "../../assets/WarningCircle.svg";
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";

// Fetching Vehicle Details
import { usePage } from "@inertiajs/vue3";

const { props } = usePage(); // Get the props passed from the controller
const vehicle = ref(props.vehicle);

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


// Map Script
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { onMounted } from 'vue'

// Add this after your existing vehicle ref declaration
let map = null

// Add this function before the template
const initMap = () => {
    if (!vehicle.value || !vehicle.value.latitude || !vehicle.value.longitude) {
        console.warn('No vehicle location data available');
        return;
    }

    // Initialize map
    map = L.map('map', {
        zoomControl: true,
        maxZoom: 18,
        minZoom: 4,
    }).setView([vehicle.value.latitude, vehicle.value.longitude], 15);

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Create custom marker icon
    const customIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `
            <div class="marker-pin">
                <span>€${vehicle.value.price_per_day}</span>
            </div>
        `,
        iconSize: [50, 30],
        iconAnchor: [25, 15],
        popupAnchor: [0, -15]
    });

    // Add marker
    const marker = L.marker([vehicle.value.latitude, vehicle.value.longitude], {
        icon: customIcon
    })
        .bindPopup(`
            <div class="text-center">
                <p class="font-semibold">${vehicle.value.brand}</p>
                <p>${vehicle.value.location}</p>
            </div>
        `)
        .addTo(map);

    // Force a map refresh after a short delay
    setTimeout(() => {
        map.invalidateSize()
    }, 100)
}

// Add this in your script setup
onMounted(() => {
    initMap()
})
</script>

<template>
    <Head title="Single Car" />
    <AuthenticatedHeaderLayout />
    <main>
        <section>
            <div class="full-w-container py-customVerticalSpacing">
                <div class="flex gap-2 items-center mb-2">
                    <h4 class="font-medium">{{ vehicle?.brand }}</h4>
                    <span
                        class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                    >
                        {{ vehicle?.category.name }}
                    </span>
                </div>
                <div class="flex gap-2 items-center text-[1.25rem]">
                    <div class="car_ratings">5(1)</div>
                    <div class="dot_seperator"><strong>.</strong></div>
                    <div class="car_location">
                        <span>{{ vehicle?.location }}</span>
                    </div>
                </div>
                <div class="w-full mt-[2rem] flex gap-2">
                    <div class="primary-image w-[60%] max-h-[500px]">
                        <img
                            v-if="vehicle?.images"
                            :src="`/storage/${
                                vehicle.images.find(
                                    (image) => image.image_type === 'primary'
                                )?.image_path
                            }`"
                            alt="Primary Image"
                            class="w-full h-full object-cover rounded-lg"
                        />
                    </div>

                    <!-- Display the gallery images -->
                    <div class="gallery w-[50%] grid grid-cols-2 gap-2 max-h-[245px]">
                        <div
                            v-for="(image, index) in vehicle?.images.filter(
                                (image) => image.image_type === 'gallery'
                            )"
                            :key="image.id"
                            class="gallery-item"
                        >
                            <img
                                :src="`/storage/${image.image_path}`"
                                :alt="`Gallery Image ${index + 1}`"
                                class="w-full h-[245px] object-cover rounded-lg"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-[4rem]">
                    <div class="column w-[50%]">
                        <div class="column flex flex-col gap-10">
                            <!-- Vehicle Features Section -->
                            <span class="text-[2rem] font-medium"
                                >Car Overview</span
                            >
                            <div
                                class="features grid grid-cols-4 gap-x-[2rem] gap-y-[2rem]"
                            >
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="peopleIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >People</span
                                        >
                                        <span class="font-medium text-[1rem]">{{
                                            vehicle?.seating_capacity
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="doorIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Doors</span
                                        >
                                        <span class="font-medium text-[1rem]">{{
                                            vehicle?.number_of_doors
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="luggageIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Luggage</span
                                        >
                                        <span class="font-medium text-[1rem]">{{
                                            vehicle?.luggage_capacity
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="transmisionIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Transmission</span
                                        >
                                        <span class="font-medium capitalize">{{
                                            vehicle?.transmission
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="fuelIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Fuel Type</span
                                        >
                                        <span class="font-medium capitalize">{{
                                            vehicle?.fuel
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="enginepowerIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Horsepower</span
                                        >
                                        <span class="font-medium text-[1rem]"
                                            >{{ vehicle?.horsepower }} hp</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="carbonIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Co2 Emission</span
                                        >
                                        <span class="font-medium text-[1rem]"
                                            >{{ vehicle?.co2 }} (g/km)</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="mileageIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Mileage</span
                                        >
                                        <span class="font-medium text-[1rem]"
                                            >{{ vehicle?.mileage }} km/d</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="feature-item items-center flex gap-3"
                                >
                                    <img :src="ageIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]"
                                            >Minimum Driving Age</span
                                        >
                                        <span class="font-medium text-[1rem]">21</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="features mt-[3rem]">
                            <span class="text-[2rem] font-medium"
                                >Features</span
                            >
                            <div
                                class="grid grid-cols-4 mt-[2rem] gap-y-[2rem]"
                            >
                                <div
                                    class="flex items-center gap-3"
                                    v-if="vehicle?.features"
                                    v-for="(feature, index) in JSON.parse(
                                        vehicle.features
                                    )"
                                    :key="index"
                                >
                                    <img
                                        :src="featureIconMap[feature]"
                                        alt="Feature Icon"
                                        class="feature-icon w-[30px] h-[30px]"
                                    />
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
                            <div class="gap-y-[2rem]">
                                {{ vehicle?.location }}
                            </div>
                            <div id="map" class="h-full rounded-lg mt-4"></div>
                        </div>
                    </div>

                    <div class="column w-[40%]">
                        <div class="paymentInfoDiv p-5 sticky top-[153px]">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <h4>{{ vehicle?.brand }}</h4>
                                <span
                                    class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                                >
                                    {{ vehicle?.category.name }}
                                </span>
                                <div class="icons flex items-center gap-3">
                                    <Link href="" class="w-full"
                                        ><img :src="ShareIcon" alt=""
                                    /></Link>
                                    <Link href="" class="w-full"
                                        ><img :src="Heart" alt=""
                                    /></Link>
                                </div>
                            </div>
                            <div>
                                <span
                                    >Hosted by
                                    <span class="vendorName uppercase">
                                        {{ vehicle?.user.first_name }}
                                        {{ vehicle?.user.last_name }}
                                    </span>
                                </span>
                            </div>
                            <div class="car_short_info mt-[1rem] flex gap-3">
                                <img :src="carIcon" alt="" />
                                <div class="features">
                                    <span class="text-[1.15rem] capitalize">
                                        {{ vehicle?.transmission }} .
                                        {{ vehicle?.fuel }} .
                                        {{ vehicle?.seating_capacity }} Seats
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
                                        >{{ vehicle?.mileage }} km/d</span
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
                                            >{{ vehicle?.location }}</span
                                        >
                                        <span>{{ vehicle?.created_at }}</span>
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
                                                >€{{
                                                    vehicle?.price_per_day
                                                }}</span
                                            ><span>/day</span>
                                            <br />
                                            <span class="flex gap-3"
                                                >incl. VAT
                                                <img :src="infoIcon" alt=""
                                            /></span>
                                        </div>
                                    </div>
                                    <div class="column mt-[2rem]">
                                        <Link 
                                        :href="`/booking/${vehicle.id}`"
                                        class="button-primary block text-center p-5 w-full"
                                    >
                                        Proceed to Pay
                                    </Link>
                                    </div>
                                    <div
                                        class="column text-center mt-[2rem] flex flex-col justify-center items-center gap-5"
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
.paymentInfoDiv {
    border-radius: 0.75rem;
    border: 0.5px solid #ede7e7;
    background: #fff;
    box-shadow: 0px 0px 32px 0px rgba(196, 196, 196, 0.24);
}
.galley-item {
    border-radius: 0.75rem;
    border: 0.5px solid #ede7e7;
    background: #fff;
    box-shadow: 0px 0px 32px 0px rgba(196, 196, 196, 0.24);
}
@import 'leaflet/dist/leaflet.css';

.marker-pin {
    width: auto;
    min-width: 50px;
    height: 30px;
    background: white;
    border: 2px solid #666;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.marker-pin span {
    color: black;
    font-weight: bold;
    font-size: 12px;
    padding: 0 8px;
}

.custom-div-icon {
    background: none;
    border: none;
}

#map {
    height: 400px;
    width: 100%;
}
</style>
