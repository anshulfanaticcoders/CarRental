<template>
    <section class="full-w-container" @click="closeSearchResults">
        <div class="search_bar rounded-[20px]">
            <div class="flex relative">
                <div
                    class="column w-[20%] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px]">
                    <span class="text-[1.75rem] font-medium">Do you need a rental car?</span>
                </div>
                <form @submit.prevent="submit"
                    class="column w-[80%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-5">
                    <div class="col col-span-2 flex flex-col justify-center">
                        <div class="flex flex-col">
                            <div class="col">
                                <label for="" class="mb-4 inline-block text-customLightGrayColor font-medium">Pickup &
                                    Return Location</label>
                                <div class="flex items-end gap-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                                            stroke="#153B4F" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <input type="text" v-model="form.where" @input="handleSearchInput"
                                        placeholder="Choose Pickup Location"
                                        class="px-3 border-b border-customLightGrayColor focus:outline-none w-[80%]" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col border-r-2 border-customMediumBlackColor px-5 flex flex-col justify-center">
                        <label class="block text-sm mb-1 text-customLightGrayColor font-medium">Pick Up Date &
                            Time</label>
                        <input type="date" v-model="form.date_from" :min="getCurrentDate()"
                            class="p-2 rounded border border-customMediumBlackColor w-full text-customPrimaryColor" />
                    </div>
                    <div class="col border-r-2 border-customMediumBlackColor px-5 flex flex-col justify-center">
                        <label class="block text-sm mb-1 text-customLightGrayColor font-medium">Return Date &
                            Time</label>
                        <input type="date" v-model="form.date_to" :min="form.date_from || getCurrentDate()"
                            class="p-2 rounded border border-gray-300 w-full text-customPrimaryColor" />
                    </div>

                    <div class="inner-col flex justify-center items-center">
                        <button type="submit"
                            class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] py-4 text-center">
                            Search
                        </button>
                    </div>
                </form>
                <div v-if="searchResults.length"
                    class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-customPrimaryColor text-white">
                    <div v-for="result in searchResults" :key="result.id" @click="
                        selectLocation(result, 'pickupLocation')
                        " class="p-2 hover:bg-white hover:text-customPrimaryColor cursor-pointer">
                        {{
                            result.properties?.label ||
                            "Unknown Location"
                        }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";

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
};

const submit = () => {
    router.get("/s", form.value);
};
const getCurrentDate = () => {
    return new Date().toISOString().split('T')[0];
};

const closeSearchResults = (event) => {
  if (searchResults.value.length && !event.target.closest('.search-results')) {
    searchResults.value = [];
  }
};
onMounted(() => {
  document.addEventListener('click', closeSearchResults);
});

// Clean up the event listener when the component is unmounted to prevent memory leaks.
onUnmounted(() => {
  document.removeEventListener('click', closeSearchResults);
});
</script>

<style>
.search_bar {
    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
}
.search-results{
    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.349);
}
</style>
