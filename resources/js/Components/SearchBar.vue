<template>
  <section class="full-w-container max-[768px]:pb-[2rem]" @click="closeSearchResults">
    <div class="search_bar rounded-[20px] max-[768px]:border-[1px]">
      <div class="flex relative max-[768px]:flex-col max-[768px]:items-center">
        <div
          class="column w-[20%] max-[768px]:w-[100%] max-[768px]:p-[1.5rem] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px]
          max-[768px]:rounded-tr-[16px] max-[768px]:rounded-tl-[16px] max-[768px]:rounded-bl-[0] max-[768px]:border-[1px]">
          <span class="text-[1.75rem] font-medium max-[768px]:text-[1.5rem]">Do you need a rental car?</span>
        </div>
        <form @submit.prevent="submit"
          class="column w-[80%] max-[768px]:w-[100%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-5
          max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-10 max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px] max-[768px]:px-[1rem]">
          <div class="col col-span-2 flex flex-col justify-center">
            <div class="flex flex-col">
              <div class="col">
                <label for="" class="mb-4 inline-block text-customLightGrayColor font-medium">Pickup & Return
                  Location</label>
                <div class="flex items-end relative">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="absolute left-[-0.35rem] top-[-0.15rem]">
                    <path
                      d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                      stroke="#153B4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <input type="text" v-model="form.where" @input="handleSearchInput"
                    placeholder="Choose Pickup Location"
                    class="pl-7 border-b border-customLightGrayColor focus:outline-none w-[80%] max-[768px]:w-full"
                    required />
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-2 flex items-center gap-4">
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">Pickup Date</label>
              <VueDatePicker v-model="pickupDate" :enable-time-picker="false" uid="pickup-date"
                placeholder="Pickup Date" class="w-full" :min-date="new Date()" />
            </div>
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">Return Date</label>
              <VueDatePicker v-model="returnDate" :enable-time-picker="false" uid="return-date"
                placeholder="Return Date" class="w-full" :min-date="getMinReturnDate()" />
            </div>
          </div>

          <div class="inner-col flex justify-center items-center">
            <button type="submit"
              class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] max-[768px]:w-full py-4 text-center">
              Search
            </button>
          </div>
        </form>
        <div v-if="searchResults.length" class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-customPrimaryColor text-white
          max-[768px]:w-full max-[768px]:top-[45%] max-[768px]:left-0">
          <div v-for="result in searchResults" :key="result.id" @click="selectLocation(result, 'pickupLocation')"
            class="p-2 hover:bg-white hover:text-customPrimaryColor cursor-pointer">
            {{ result.properties?.label || "Unknown Location" }}
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const form = ref({
  where: "",
  date_from: "",
  date_to: "",
  latitude: null,
  longitude: null,
  radius: 5,
});

const props = defineProps({
  prefill: Object,
});

// Calendar value refs
const pickupDate = ref(null);
const returnDate = ref(null);

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return "Select date";
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Watch for pickupDate changes and update form.date_from
watch(pickupDate, (newValue) => {
  if (newValue) {
    // Convert to YYYY-MM-DD format
    form.value.date_from = newValue.toISOString().split('T')[0];

    // If return date is before pickup date, reset it
    if (returnDate.value && newValue > returnDate.value) {
      returnDate.value = null;
      form.value.date_to = '';
    }
  }
}, { deep: true });

// Watch for returnDate changes and update form.date_to
// Watch for return date changes
watch(returnDate, (newValue) => {
  if (newValue) {
    // Convert to YYYY-MM-DD format
    form.value.date_to = newValue.toISOString().split('T')[0];
  }
}, { deep: true });

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

// Get current date for Calendar component
const getCurrentCalendarDate = () => {
  return today(getLocalTimeZone());
};

// Get minimum return date (either pickup date or today)
const getMinReturnDate = () => {
  if (pickupDate.value) {
    // Create a new Date object and add 1 day to the pickup date
    const minReturnDate = new Date(pickupDate.value);
    minReturnDate.setDate(minReturnDate.getDate() + 1);
    return minReturnDate;
  }
  return new Date(); // Default to current date if no pickup date selected
};



const closeSearchResults = (event) => {
  if (searchResults.value.length && !event.target.closest(".search-results")) {
    searchResults.value = [];
  }
};

onMounted(() => {
  document.addEventListener('click', closeSearchResults);

  // Handle prefill if exists
  if (props.prefill) {
    form.value.where = props.prefill.where;

    // Set pickup and return dates if prefill dates exist
    if (props.prefill.date_from) {
      pickupDate.value = new Date(props.prefill.date_from);
    }

    if (props.prefill.date_to) {
      returnDate.value = new Date(props.prefill.date_to);
    }

    form.value.latitude = props.prefill.latitude;
    form.value.longitude = props.prefill.longitude;
    form.value.radius = props.prefill.radius;
  }
});

onUnmounted(() => {
  document.removeEventListener("click", closeSearchResults);
});

onUnmounted(() => {
  document.removeEventListener("click", closeSearchResults);
});

// Reset return date if pickup date changes to a later date
watch(pickupDate, (newPickupDate) => {
  if (returnDate.value && newPickupDate) {
    // If return date exists and is not after the new pickup date
    if (returnDate.value <= newPickupDate) {
      returnDate.value = null; // Reset return date
    }
  }
});

</script>

<style>
.search_bar {
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
}

.search-results {
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.349);
}

@media screen and (max-width:768px) {
  .search_bar {
    box-shadow: none;
  }
}
</style>