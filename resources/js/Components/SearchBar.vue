<template>
  <section class="full-w-container max-[768px]:pb-[2rem]" @click="closeSearchResults">
    <div class="search_bar rounded-[20px] max-[768px]:border-[1px]">
      <div class="flex relative max-[768px]:flex-col max-[768px]:items-center">
        <div
          class="column w-[20%] max-[768px]:w-[100%] max-[768px]:p-[1.5rem] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px] max-[768px]:rounded-tr-[16px] max-[768px]:rounded-tl-[16px] max-[768px]:rounded-bl-[0] max-[768px]:border-[1px]">
          <span class="text-[1.75rem] font-medium max-[768px]:text-[1.5rem]">{{ _t('homepage', 'search_bar_header') }}</span>
        </div>
        <form @submit.prevent="submit"
          class="column w-[80%] max-[768px]:w-[100%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-5 max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-10 max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px] max-[768px]:px-[1rem]">
          <div class="col col-span-2 flex flex-col justify-center">
            <div class="flex flex-col">
              <div class="col">
                <label for="" class="mb-4 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'pickup_return_location_label') }}</label>
                <div class="flex items-end relative">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="absolute left-[-0.35rem] top-[-0.15rem]">
                    <path
                      d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                      stroke="#153B4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <input type="text" v-model="form.where" @input="handleSearchInput" @focus="handleInputFocus"
                    :placeholder="isSearching ? _t('homepage', 'searching_placeholder') : _t('homepage', 'pickup_location_placeholder')"
                    class="pl-7 border-b border-customLightGrayColor focus:outline-none w-[80%] max-[768px]:w-full"
                    required />
                  <!-- <span v-if="isSearching" class="absolute right-[1rem] top-0 text-customLightGrayColor">Searching...</span> -->
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-2 flex items-center gap-4">
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'pickup_date_label') }}</label>
              <VueDatePicker v-model="pickupDate" :enable-time-picker="false" uid="pickup-date" auto-apply
                :placeholder="_t('homepage', 'pickup_date_placeholder')" class="w-full" :min-date="new Date()" :format="formatDate" />
            </div>
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'return_date_label') }}</label>
              <VueDatePicker v-model="returnDate" :enable-time-picker="false" uid="return-date" auto-apply
                :placeholder="_t('homepage', 'return_date_placeholder')" class="w-full" :min-date="getMinReturnDate()" :format="formatDate" />
            </div>
          </div>

          <div class="inner-col flex justify-center items-center">
            <button type="submit"
              class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] max-[768px]:w-full py-4 text-center">
              {{ _t('homepage', 'search_button') }}
            </button>
          </div>
        </form>

        <!-- Search results dropdown -->
        <div v-if="showSearchBox && (searchResults.length > 0 || popularPlaces.length > 0 || searchPerformed)"
          class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto max-[768px]:w-full max-[768px]:top-[45%] max-[768px]:left-0">

          <!-- Existing search results -->
          <div v-if="searchResults.length > 0">
            <div v-for="result in searchResults" :key="result.id" @click="selectLocation(result)"
              class="p-2 hover:bg-[#efefef4d] hover:text-customPrimaryColor cursor-pointer flex gap-3">
              <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2">
                  <path clip-rule="evenodd"
                    d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path clip-rule="evenodd"
                    d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </div>
              <div class="flex flex-col">
                <div class="font-medium">{{ result.label }}</div>
                <div v-if="result.below_label" class="text-sm text-gray-500">
                  {{ result.below_label }}
                </div>
              </div>
            </div>
          </div>

          <!-- Show "No location found" if a search was performed with no results -->
          <div v-else-if="searchPerformed && !isSearching" class="p-3 text-center">
            {{ _t('homepage', 'no_location_found') }}
          </div>

          <!-- Show popular places only if no search has been performed or input is empty -->
          <div v-else-if="popularPlaces.length > 0 && !isSearching">
  <div class="text-sm font-medium mb-2 text-customPrimaryColor">{{ _t('homepage', 'popular_searches_header') }}</div>
  <div v-for="place in popularPlaces" :key="place.id" @click="selectLocation(place)"
    class="p-2 hover:bg-[#efefef4d] hover:text-customPrimaryColor cursor-pointer flex gap-3">
    <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center max-[768px]:flex-[0.2]">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2">
        <path clip-rule="evenodd"
          d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        <path clip-rule="evenodd"
          d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
      </svg>
    </div>
    <div class="flex flex-col max-[768px]:flex-1">
      <div class="font-medium">{{ place.label }}</div> <!-- Changed from place.location to place.label -->
      <div v-if="place.below_label" class="text-sm text-gray-500">
        {{ place.below_label }}
      </div>
    </div>
  </div>
</div>
        </div>

         <!-- Error Dialog -->
         <ErrorDialog :show="errorMessage !== ''" :message="errorMessage" @close="clearError" />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import axios from "axios";
import { router, usePage } from "@inertiajs/vue3";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const form = ref({
  where: "",
  location: "",
  date_from: "",
  date_to: "",
  latitude: null,
  longitude: null,
  radius: 5000,
  package_type: "",
  city: null,
  state: null,
  country: null,
  matched_field: null,
  source: null, // Add source field
  greenmotion_location_id: null, // Add GreenMotion specific ID
  start_time: '09:00', // New: Start time for GreenMotion
  end_time: '09:00',   // New: End time for GreenMotion
  age: 35,       // New: Age for GreenMotion
  rentalCode: '1', // New: Rental code for GreenMotion
  currency: null, // New: Currency for GreenMotion
  fuel: null, // New: Fuel for GreenMotion
  userid: null, // New: User ID for GreenMotion
  username: null, // New: Username for GreenMotion
  language: null, // New: Language for GreenMotion
  full_credit: null, // New: Full Credit for GreenMotion
  promocode: null, // New: Promocode for GreenMotion
  dropoff_location_id: null, // New: Dropoff Location ID for GreenMotion
});

const props = defineProps({
  prefill: Object,
});

const pickupDate = ref(null);
const returnDate = ref(null);
const searchResults = ref([]);
const dateError = ref(false);
const isSearching = ref(false);
const searchTimeout = ref(null);
const searchPerformed = ref(false);
const showSearchBox = ref(false);
const popularPlaces = ref([]);
const locationError = ref(null);

// Compute error message to display in dialog
const errorMessage = computed(() => {
  if (dateError.value) {
    return "Please fill in all fields: location, pickup date, and return date.";
  }
  if (locationError.value) {
    return locationError.value;
  }
  return "";
});

// Clear error messages
const clearError = () => {
  dateError.value = false;
  locationError.value = null;
};

const handleInputFocus = () => {
  showSearchBox.value = true;
  if (!isSearching.value && !searchPerformed.value) {
    fetchPopularPlaces();
  }
};

const fetchPopularPlaces = async () => {
  try {
    const response = await axios.get(`/api/unified-locations`); // Removed locale prefix
    popularPlaces.value = response.data.filter(place => place.source === 'internal' || place.source === 'greenmotion'); // Filter for relevant sources
  } catch (error) {
    console.error("Error fetching popular places:", error);
    popularPlaces.value = [];
  }
};

const formatDate = (dateString) => {
  if (!dateString) return "Select date";
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

watch(pickupDate, (newValue) => {
  if (newValue) {
    form.value.date_from = newValue.toISOString().split('T')[0];
    if (returnDate.value && newValue > returnDate.value) {
      returnDate.value = null;
      form.value.date_to = '';
    }
  }
}, { deep: true });

watch(returnDate, (newValue) => {
  if (newValue) {
    form.value.date_to = newValue.toISOString().split('T')[0];
  }
}, { deep: true });

const handleSearchInput = () => {
  if (form.value.where.length === 0) {
    searchResults.value = [];
    searchPerformed.value = false;
    showSearchBox.value = true;
    fetchPopularPlaces();
    return;
  }
  if (form.value.where.length < 3) {
    searchResults.value = [];
    searchPerformed.value = false;
    showSearchBox.value = false;
    return;
  }

  showSearchBox.value = true;

  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }

  searchTimeout.value = setTimeout(async () => {
    isSearching.value = true;
    try {
      const response = await axios.get(`/api/unified-locations`, { // Removed locale prefix
        params: { search_term: form.value.where },
      });
      searchResults.value = response.data;
      searchPerformed.value = true;
    } catch (error) {
      console.error("Error fetching locations:", error);
      searchResults.value = [];
      searchPerformed.value = true;
    } finally {
      isSearching.value = false;
    }
  }, 300);
};

const selectLocation = (result) => {
  form.value.where = result.label + (result.below_label ? `, ${result.below_label}` : '');
  form.value.location = result.location || null; // This should hold the specific location name if available
  form.value.latitude = result.latitude;
  form.value.longitude = result.longitude;
  form.value.city = result.city;
  form.value.state = result.state;
  form.value.country = result.country;
  form.value.matched_field = result.matched_field; // Set matched_field
  form.value.source = result.source; // Set source
  form.value.greenmotion_location_id = result.greenmotion_location_id || null; // Set GreenMotion specific ID
  showSearchBox.value = false;
  searchPerformed.value = false;
  searchResults.value = [];
};

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

  let packageType = '';
  form.value.package_type = packageType;

  // Create URL parameters object
  const urlParams = new URLSearchParams(form.value).toString();

  // Store the search URL in session storage
   sessionStorage.setItem('searchurl', `/s?${urlParams}`);

  // Remove radius adjustment since we’re not using radius-based filtering
  // form.value.radius = 30000; // Removed

  router.get(route('search', { locale: usePage().props.locale }), form.value);
};

const getMinReturnDate = () => {
  if (pickupDate.value) {
    const minReturnDate = new Date(pickupDate.value);
    minReturnDate.setDate(minReturnDate.getDate() + 1);
    return minReturnDate;
  }
  return new Date();
};

const closeSearchResults = (event) => {
  if (showSearchBox.value &&
    !event.target.closest(".search-results") &&
    !event.target.closest("input[type='text']")) {
    showSearchBox.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeSearchResults);
  fetchPopularPlaces();
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
  form.value.source = props.prefill.source || null;
  form.value.greenmotion_location_id = props.prefill.greenmotion_location_id || null;
  }
});

onUnmounted(() => {
  document.removeEventListener("click", closeSearchResults);
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
});

watch(pickupDate, (newPickupDate) => {
  if (returnDate.value && newPickupDate && returnDate.value <= newPickupDate) {
    returnDate.value = null;
  }
});


// Error Dialog Component
const ErrorDialog = {
  props: {
    show: Boolean,
    message: String,
  },
  emits: ['close'],
  template: `
    <div v-if="show" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
      <div class="bg-white rounded-lg p-6 w-[90%] max-w-[400px] shadow-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-red-600">Error</h3>
          <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <p class="text-gray-700">{{ message }}</p>
        <div class="mt-6 flex justify-end">
          <button @click="$emit('close')"
            class="bg-customPrimaryColor text-customPrimaryColor-foreground px-4 py-2 rounded-lg hover:bg-opacity-90">
            OK
          </button>
        </div>
      </div>
    </div>
  `,
};
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
