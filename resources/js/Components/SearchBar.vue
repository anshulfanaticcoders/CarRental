<template>
  <section class="full-w-container max-[768px]:pb-[2rem]" @click="closeSearchResults">
    <div class="search_bar rounded-[20px] max-[768px]:border-[1px]">
      <div class="flex relative max-[768px]:flex-col max-[768px]:items-center">
        <div class="column w-[20%] max-[768px]:w-[100%] max-[768px]:p-[1.5rem] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px] max-[768px]:rounded-tr-[16px] max-[768px]:rounded-tl-[16px] max-[768px]:rounded-bl-[0] max-[768px]:border-[1px]">
          <span class="text-[1.75rem] font-medium max-[768px]:text-[1.5rem]">Do you need a rental car?</span>
        </div>
        <form @submit.prevent="submit" class="column w-[80%] max-[768px]:w-[100%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-5 max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-10 max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px] max-[768px]:px-[1rem]">
          <div class="col col-span-2 flex flex-col justify-center">
            <div class="flex flex-col">
              <div class="col">
                <label for="" class="mb-4 inline-block text-customLightGrayColor font-medium">Pickup & Return Location</label>
                <div class="flex items-end relative">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute left-[-0.35rem] top-[-0.15rem]">
                    <path d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z" stroke="#153B4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <input type="text" v-model="form.where" @input="handleSearchInput" @focus="handleInputFocus" placeholder="Choose Pickup Location" class="pl-7 border-b border-customLightGrayColor focus:outline-none w-[80%] max-[768px]:w-full" required />
                  <span v-if="isSearching" class="absolute right-0 top-0 text-customLightGrayColor">Searching...</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-2 flex items-center gap-4">
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">Pickup Date</label>
              <VueDatePicker v-model="pickupDate" :enable-time-picker="false" uid="pickup-date" placeholder="Pickup Date" class="w-full" :min-date="new Date()" />
            </div>
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">Return Date</label>
              <VueDatePicker v-model="returnDate" :enable-time-picker="false" uid="return-date" placeholder="Return Date" class="w-full" :min-date="getMinReturnDate()" />
            </div>
          </div>

          <div class="inner-col flex justify-center items-center">
            <button type="submit" class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] max-[768px]:w-full py-4 text-center">
              Search
            </button>
          </div>
        </form>
        
        <!-- Search results dropdown -->
        <div v-if="showSearchBox && (searchResults.length > 0 || searchPerformed)" 
       class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto max-[768px]:w-full max-[768px]:top-[45%] max-[768px]:left-0">
    <div v-if="searchResults.length > 0">
      <div v-for="result in searchResults" 
           :key="result.id" 
           @click="selectLocation(result)" 
           class="p-2 hover:bg-[#efefefcc] hover:text-customPrimaryColor cursor-pointer">
        <div class="font-medium">{{ result.label }}</div>
        <div v-if="result.location" class="text-sm text-gray-500">
          {{ [result.city, result.state, result.country].filter(Boolean).join(', ') }}
        </div>
      </div>
    </div>
    <div v-else-if="searchPerformed && !isSearching" class="p-3 text-center">
      No location found. Please try another search.
    </div>
  </div>
        
        <div v-if="dateError" class="absolute top-[105%] w-[50%] text-red-500 text-center max-[768px]:w-full max-[768px]:top-[55%]">
          Please fill in all fields: location, pickup date, and return date.
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
  location: "", // Add location field
  date_from: "",
  date_to: "",
  latitude: null,
  longitude: null,
  radius: 5000,
  package_type: "",
  city: null,
  state: null,
  country: null,
});

const props = defineProps({
  prefill: Object,
});

// Calendar value refs
const pickupDate = ref(null);
const returnDate = ref(null);

// Search results
const searchResults = ref([]);
const dateError = ref(false);
const isSearching = ref(false); // Track search state for UX
const searchTimeout = ref(null); // Debounce search
const searchPerformed = ref(false); // Track if a search has been performed
const showSearchBox = ref(false); // Control visibility of search results box

const handleInputFocus = () => {
  // Show the search box when input is focused if there's a value of at least 3 chars
  if (form.value.where.length >= 3) {
    showSearchBox.value = true;
  }
};

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return "Select date";
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Watch for pickupDate changes and update form.date_from
watch(pickupDate, (newValue) => {
  if (newValue) {
    form.value.date_from = newValue.toISOString().split('T')[0];
    if (returnDate.value && newValue > returnDate.value) {
      returnDate.value = null;
      form.value.date_to = '';
    }
  }
}, { deep: true });

// Watch for returnDate changes and update form.date_to
watch(returnDate, (newValue) => {
  if (newValue) {
    form.value.date_to = newValue.toISOString().split('T')[0];
  }
}, { deep: true });

// Debounced search handler
const handleSearchInput = () => {
  if (form.value.where.length < 3) {
    searchResults.value = [];
    searchPerformed.value = false;
    showSearchBox.value = false;
    return;
  }

  // Show search box immediately when typing
  showSearchBox.value = true;

  // Clear previous timeout
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }

  // Debounce to prevent excessive API calls
  searchTimeout.value = setTimeout(async () => {
    isSearching.value = true;
    try {
      const response = await axios.get(`/api/vehicles/search-locations`, {
        params: { text: form.value.where },
      });
      searchResults.value = response.data.results;
      searchPerformed.value = true;
    } catch (error) {
      console.error("Error fetching locations:", error);
      searchResults.value = [];
      searchPerformed.value = true;
    } finally {
      isSearching.value = false;
    }
  }, 300); // 300ms debounce
};

// Select a location from search results
const selectLocation = (result) => {
  form.value.where = result.label;
  form.value.location = result.location || null; // Set location field
  form.value.latitude = result.latitude;
  form.value.longitude = result.longitude;
  form.value.city = result.city;
  form.value.state = result.state;
  form.value.country = result.country;
  showSearchBox.value = false;
  searchPerformed.value = false;
};

// Form submission
const submit = () => {
  if (!form.value.date_from || !form.value.date_to || !form.value.where) {
    dateError.value = true;
    return;
  }
  dateError.value = false;

  const pickupDate = new Date(form.value.date_from);
  const returnDate = new Date(form.value.date_to);
  const diffTime = Math.abs(returnDate - pickupDate);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  let packageType = 'day';
  form.value.package_type = packageType;

  // Adjust radius based on search specificity
  if (form.value.city) {
    form.value.radius = 10000; // 10km for city-specific searches
  } else if (form.value.state) {
    form.value.radius = 50000; // 50km for state searches
  } else if (form.value.country) {
    form.value.radius = 1000000; // 1000km for country searches
  }

  router.get("/s", form.value);
};

// Get minimum return date
const getMinReturnDate = () => {
  if (pickupDate.value) {
    const minReturnDate = new Date(pickupDate.value);
    minReturnDate.setDate(minReturnDate.getDate() + 1);
    return minReturnDate;
  }
  return new Date();
};

// Close search results on outside click
const closeSearchResults = (event) => {
  // Only close if clicking outside the search results area and not on the search input
  if (showSearchBox.value && 
      !event.target.closest(".search-results") && 
      !event.target.closest("input[type='text']")) {
    showSearchBox.value = false;
  }
};

// Initialize with prefill data
onMounted(() => {
  document.addEventListener('click', closeSearchResults);
  if (props.prefill) {
    form.value.where = props.prefill.where || '';
    if (props.prefill.date_from) {
      pickupDate.value = new Date(props.prefill.date_from);
    }
    if (props.prefill.date_to) {
      returnDate.value = new Date(props.prefill.date_to);
    }
    form.value.latitude = props.prefill.latitude || null;
    form.value.longitude = props.prefill.longitude || null;
    form.value.radius = props.prefill.radius || 5000;
    form.value.city = props.prefill.city || null;
    form.value.state = props.prefill.state || null;
    form.value.country = props.prefill.country || null;
  }
});

onUnmounted(() => {
  document.removeEventListener("click", closeSearchResults);
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
});

// Reset return date if pickup date changes to a later date
watch(pickupDate, (newPickupDate) => {
  if (returnDate.value && newPickupDate && returnDate.value <= newPickupDate) {
    returnDate.value = null;
  }
});
</script>

<style>
.search_bar {
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
}
.search-results div div {
  transition: all 0.2s ease;
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