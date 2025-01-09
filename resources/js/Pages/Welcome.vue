<script setup>
import { Head, Link } from "@inertiajs/vue3";
import heroImg from "../../assets/heroImage.jpeg";
import Footer from '@/Components/Footer.vue'
import locationMapIcon from "../../assets/location.svg";
import chipIcon from "../../assets/chip.svg";
import phoneIcon from "../../assets/phone.svg";
import userCoverageIcon from "../../assets/usercoverage.svg";
import carImage from "../../assets/carImagebgrmoved.png";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import HowItWorks from "@/Components/ReusableComponents/HowItWorks.vue";
import CarCard from "@/Components/CarCard.vue";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});


import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import L from "leaflet"; // Import Leaflet

const form = ref({
    where: "",
    date_from: "",
    date_to: "",
    latitude: null,
    longitude: null,
    radius: 831867.4340914232,
});

const searchResults = ref([]);
let map = null; // Map instance
let marker = null; // Marker instance

const handleSearchInput = async () => {
    if (form.value.where.length < 3) return;

    try {
        const response = await axios.get(
            `/api/geocoding/autocomplete?text=${encodeURIComponent(
                form.value.where
            )}`
        );
        searchResults.value = response.data.features;
    } catch (error) {
        console.error("Error fetching locations:", error);
    }
};

const selectLocation = (result) => {
    form.value.where = result.properties?.label || "Unknown Location";
    form.value.latitude = result.geometry.coordinates[1];
    form.value.longitude = result.geometry.coordinates[0];
    searchResults.value = [];

    // Update map with the selected location
    const latLng = [form.value.latitude, form.value.longitude];

    if (map) {
        map.setView(latLng, 13); // Move the map to the selected location
        if (marker) {
            marker.setLatLng(latLng); // Update marker position
        } else {
            marker = L.marker(latLng).addTo(map); // Add marker if it doesn't exist
        }
    }
};

const submit = () => {
    router.get("/s", form.value);
};

onMounted(() => {
    // Initialize the map
    map = L.map("map").setView([20.5937, 78.9629], 5); // Default to India

    // Add tile layer (OpenStreetMap)
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
});
</script>

<template>
    <Head title="Welcome" />

    <div class="">
        <div v-if="canLogin" class="container"> 
                <div v-if="$page.props.auth.user">
                    <AuthenticatedHeaderLayout/>
                </div>

            <template v-else>
               <div class="flex justify-between py-[2rem]">
                <div class="column">
                   <Link href="/"><ApplicationLogo/></Link> 
               </div> 

               <div class="column">
                   <Link
                    :href="route('login')"
                    class="button-primary py-3 px-5  font-semibold text-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Log in</Link>

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="button-secondary ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Create an Account</Link>
               </div> 
               </div>
            </template>
        </div>
</div>

<main>
    <section class="hero_section">
            <div class="wrapper flex justify-between w-full">
                <div
                    class="column bg-customPrimaryColor h-[65vh] w-full text-white flex flex-col items-end justify-center"
                >
                    <div class="w-[666px]">
                        <h1>Hit the Road with the Perfect Ride</h1>
                        <p class="text-[1.25rem] mt-3">
                            Get a car wherever and whenever you need it <br />
                            with your iOS or Android device.
                        </p>
                    </div>
                </div>
                <div class="column h-[80vh] w-full relative">
                    <img
                        class="rounded-bl-[20px] h-full w-full"
                        :src="heroImg"
                        alt=""
                    />
                    <div
                        class="bg-customOverlayColor absolute top-0 w-full h-full rounded-bl-[20px]"
                    ></div>
                </div>
            </div>
        </section>

        <section class="mt-[-14rem] mb-[10rem]">
            <div class="container">
                <div class="flex relative">
        <div
            class="column w-[20%] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px]"
        >
            <span class="text-[1.75rem] font-medium"
                >Do you need a rental car?</span
            >
        </div>
        <form @submit.prevent="submit"
            class="column w-[80%] p-[2rem] rounded-tr-[20px] rounded-br-[20px] bg-white grid grid-cols-5 items-center"
        >
            <div class="col col-span-2">
                <div class="flex flex-col">
                    <div class="col">
                        <label for="" class="mb-4 inline-block"
                            >Pickup & Return Location</label
                        >
                        <div class="flex items-center">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                                    stroke="#153B4F"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <input
                                type="text"
                                v-model="form.where"
                                @input="handleSearchInput"
                                placeholder="Choose Pickup Location"
                                class="pl-3 border-b border-customPrimaryColor mb-4 focus:outline-none w-[80%]"
                            />
                            <div
                                v-if="searchResults.length"
                                class="search-results absolute h-[15rem] bottom-[-135%] w-[50%] bg-customPrimaryColor text-white"
                            >
                                <div
                                    v-for="result in searchResults"
                                    :key="result.id"
                                    @click="
                                        selectLocation(result, 'pickupLocation')
                                    "
                                    class="p-2 hover:bg-white hover:text-customPrimaryColor cursor-pointer"
                                >
                                    {{
                                        result.properties?.label ||
                                        "Unknown Location"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col border-r-2 border-customLightGrayColor px-5">
                <label
                    class="block text-sm mb-1 text-customLightGrayColor font-medium"
                    >Pick Up Date & Time</label
                >
                <input
                    type="date"
                    v-model="form.date_from"
                    class="p-2 rounded border border-gray-300 w-full text-customPrimaryColor"
                />
            </div>
            <div class="col border-r-2 border-customLightGrayColor px-5">
                <label
                    class="block text-sm mb-1 text-customLightGrayColor font-medium"
                    >Return Date & Time</label
                >
                <input
                    type="date"
                    v-model="form.date_to"
                    class="p-2 rounded border border-gray-300 w-full text-customPrimaryColor"
                />
            </div>

            <div class="inner-col flex justify-end">
                <button
                    type="submit"
                    class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] py-4 text-center"
                >
                    Search
                </button>
            </div>
        </form>
          <!-- Map Container -->
    <div id="map" class="hidden w-full h-64 mt-4"></div>
    </div>
            </div>
        </section>

        
        <section class="container py-customVerticalSpacing">
           <CarCard/>
        </section>


          <!------------------------------- WHY CHOOSE US -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
            <section class="py-customVerticalSpacing">
            <div class="container flex flex-col gap-16">
                <div class="column text-center flex flex-col items-center">
                    <span class="text-[1.25rem]">-Why Choose Us-</span>
                    <h3 class="max-w-[883px]">
                        From luxury sedans to budget-friendly compacts, we've
                        got something for every journey
                    </h3>
                </div>
                <div class="column grid grid-cols-3 gap-16">
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="locationMapIcon" alt="" />
                            <div
                                class="text-customMediumBlackColor flex flex-col gap-3"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Convenient Locations</span
                                >
                                <p>
                                    With multiple rental locations at airports,
                                    city centers, and popular destinations,
                                    picking up and dropping off your rental is
                                    quick and easy.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="phoneIcon" alt="" />
                            <div
                                class="text-customMediumBlackColor flex flex-col gap-3"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Fast and Easy Booking Process</span
                                >
                                <p>
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <img
                            class="rounded-[20px] h-full w-full object-cover"
                            :src="carImage"
                            alt=""
                        />
                    </div>
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="chipIcon" alt="" />
                            <div
                                class="text-customMediumBlackColor flex flex-col gap-3"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Modern Fleet with the Latest
                                    Technology</span
                                >
                                <p>
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="userCoverageIcon" alt="" />
                            <div
                                class="flex flex-col gap-3 text-customMediumBlackColor"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Insurance Coverage</span
                                >
                                <p>
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             </section>
        <!------------------------------ <End>  -------------------------------------------------->

   <!------------------------------- How It Works -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
            <HowItWorks/>
            <!------------------------------ <End>  -------------------------------------------------->

</main>

<Footer/>
</template>

<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}
@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}
#map {
  height: 400px;
  width: 100%;
  margin-top: 1rem;
}
</style>
