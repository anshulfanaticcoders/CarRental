<template>
    <section class="full-w-container max-[480px]:pb-[2rem]" @click="closeSearchResults">
      <div class="search_bar rounded-[20px] max-[480px]:border-[1px]">
        <div class="flex relative max-[480px]:flex-col max-[480px]:items-center">
          <div
            class="column w-[20%] max-[480px]:w-[100%] max-[480px]:p-[1.5rem] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px]
            max-[480px]:rounded-tr-[16px] max-[480px]:rounded-tl-[16px] max-[480px]:rounded-bl-[0] max-[480px]:border-[1px]"
          >
            <span class="text-[1.75rem] font-medium max-[480px]:text-[1.5rem]">Do you need a rental car?</span>
          </div>
          <form
            @submit.prevent="submit"
            class="column w-[80%] max-[480px]:w-[100%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-5
            max-[480px]:flex max-[480px]:flex-col max-[480px]:gap-10 max-[480px]:rounded-tr-[0] max-[480px]:rounded-bl-[16px] max-[480px]:px-[1rem]"
          >
            <div class="col col-span-2 flex flex-col justify-center">
              <div class="flex flex-col">
                <div class="col">
                  <label
                    for=""
                    class="mb-4 inline-block text-customLightGrayColor font-medium"
                    >Pickup & Return Location</label
                  >
                  <div class="flex items-end gap-2">
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
                      class="px-3 border-b border-customLightGrayColor focus:outline-none w-[80%]"
                      required
                    />
                  </div>
                </div>
              </div>
            </div>
  
            <div class="col border-r-2 max-[480px]:border-r-0 border-customMediumBlackColor px-5 max-[480px]:px-1 flex flex-col justify-center">
              <label class="block text-sm mb-1 text-customLightGrayColor font-medium"
                >Pick Up Date & Time</label
              >
              <input
                type="date"
                v-model="form.date_from"
                :min="getCurrentDate()"
                class="p-2 rounded border border-customMediumBlackColor w-full text-customPrimaryColor"
                required
              />
               <p v-if="dateError && !form.date_from" class="text-red-500 text-xs mt-1">Please select a pickup date.</p>
            </div>
            <div class="col border-r-2 max-[480px]:border-r-0 border-customMediumBlackColor px-5 max-[480px]:px-1 flex flex-col justify-center">
              <label class="block text-sm mb-1 text-customLightGrayColor font-medium"
                >Return Date & Time</label
              >
              <input
                type="date"
                v-model="form.date_to"
                :min="form.date_from || getCurrentDate()"
                class="p-2 rounded border border-gray-300 w-full text-customPrimaryColor"
                required
              />
              <p v-if="dateError && !form.date_to" class="text-red-500 text-xs mt-1">Please select a return date.</p>
            </div>
  
            <div class="inner-col flex justify-center items-center">
              <button
                type="submit"
                class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] max-[480px]:w-full py-4 text-center"
              >
                Search
              </button>
            </div>
          </form>
          <div
            v-if="searchResults.length"
            class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-customPrimaryColor text-white"
          >
            <div
              v-for="result in searchResults"
              :key="result.id"
              @click="selectLocation(result, 'pickupLocation')"
              class="p-2 hover:bg-white hover:text-customPrimaryColor cursor-pointer"
            >
              {{ result.properties?.label || "Unknown Location" }}
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
  const props = defineProps({
    prefill:Object,
})

  const searchResults = ref([]);
  const dateError = ref(false);
  
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
  };
  
  const submit = () => {
    if (!form.value.date_from || !form.value.date_to || !form.value.where) {
        dateError.value = true;
        return;
    }
    dateError.value = false;
    
    // Calculate date difference and set package_type
    const pickupDate = new Date(form.value.date_from);
    const returnDate = new Date(form.value.date_to);
    const diffTime = Math.abs(returnDate - pickupDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Determine package type based on date difference
    let packageType = 'day'; // default
    
    if (diffDays === 7 || diffDays === 14 || diffDays === 21) {
        packageType = 'week';
    } else if (diffDays >= 28) {
        packageType = 'month';
    }
    
    // Add package_type to form data
    form.value.package_type = packageType;
    
    // Submit the form with all parameters
    router.get("/s", form.value);
};
  
  const getCurrentDate = () => {
    return new Date().toISOString().split("T")[0];
  };
  
  const closeSearchResults = (event) => {
    if (searchResults.value.length && !event.target.closest(".search-results")) {
      searchResults.value = [];
    }
  };
  onMounted(() => {
    document.addEventListener('click', closeSearchResults);
    if(props.prefill){
        form.value.where = props.prefill.where;
        form.value.date_from = props.prefill.date_from;
        form.value.date_to = props.prefill.date_to;
        form.value.latitude = props.prefill.latitude;
        form.value.longitude = props.prefill.longitude;
        form.value.radius = props.prefill.radius;
    }
});
  
  onUnmounted(() => {
    document.removeEventListener("click", closeSearchResults);
  });
  </script>
  
  <style>
  .search_bar {
    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
  }
  .search-results {
    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.349);
  }


  @media screen and (max-width:480px) {
    .search_bar{
      box-shadow: none;
    }
  }
  </style>