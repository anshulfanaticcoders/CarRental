<template>
  <section class="full-w-container max-[768px]:pb-[2rem]" ref="searchBarContainer">
    <div class="search_bar rounded-[20px] max-[768px]:border-[1px]">
      <div class="flex relative max-[768px]:flex-col max-[768px]:items-center">
        <div
          class="column w-[20%] max-[768px]:w-[100%] max-[768px]:p-[1.5rem] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px] max-[768px]:rounded-tr-[16px] max-[768px]:rounded-tl-[16px] max-[768px]:rounded-bl-[0] max-[768px]:border-[1px]">
          <span class="text-[1.75rem] font-medium max-[768px]:text-[1.5rem]">{{ _t('homepage', 'search_bar_header') }}</span>
        </div>
        <form @submit.prevent="submit"
          class="column w-[80%] max-[768px]:w-[100%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-7 max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-10 max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px] max-[768px]:px-[1rem]">
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
                  <input type="text" v-model="form.where" @input="handleSearchInput" @click="handleInputClick"
                    :placeholder="isSearching ? _t('homepage', 'searching_placeholder') : _t('homepage', 'pickup_location_placeholder')"
                    class="pl-7 border-b border-customLightGrayColor focus:outline-none w-[80%] max-[768px]:w-full"
                    required />
                  <!-- <span v-if="isSearching" class="absolute right-[1rem] top-0 text-customLightGrayColor">Searching...</span> -->
                </div>
              </div>
              <div v-if="isProviderLocation" class="col mt-4">
                <label for="" class="mb-4 inline-block text-customLightGrayColor font-medium">Dropoff Location</label>
                <div class="flex items-end relative">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="absolute left-[-0.35rem] top-[-0.15rem]">
                    <path
                      d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                      stroke="#153B4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <input type="text" v-model="form.dropoff_where" @click="handleDropoffInputClick"
                    placeholder="Enter dropoff location"
                    class="pl-7 border-b border-customLightGrayColor focus:outline-none w-[80%] max-[768px]:w-full"
                    readonly />
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-2 flex items-center gap-4 mr-4 max-[768px]:mr-0">
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'pickup_date_label') }}</label>
              <VueDatePicker v-model="pickupDate" :enable-time-picker="false" uid="pickup-date" auto-apply
                :placeholder="_t('homepage', 'pickup_date_placeholder')" class="w-full" :min-date="new Date()" :format="formatDate" />
            </div>
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">Start Time</label>
              <VueDatePicker v-model="startTime" time-picker auto-apply placeholder="Select time" uid="start-time" :minutes-increment="5" :minutes-grid-increment="5" />
            </div>
          </div>

          <div class="col-span-2 flex items-center gap-4">
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'return_date_label') }}</label>
              <VueDatePicker v-model="returnDate" :enable-time-picker="false" uid="return-date" auto-apply
                :placeholder="_t('homepage', 'return_date_placeholder')" class="w-full" :min-date="getMinReturnDate()" :format="formatDate" />
            </div>
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">End Time</label>
              <VueDatePicker v-model="endTime" time-picker auto-apply placeholder="Select time" uid="end-time" :minutes-increment="5" :minutes-grid-increment="5" />
            </div>
          </div>

          <div class="inner-col flex justify-center items-center col-span-1">
            <button type="submit"
              class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] max-[768px]:w-full py-4 text-center"
              :disabled="isLoading">
              {{ _t('homepage', 'search_button') }}
            </button>
          </div>
        </form>

        <!-- Loader Overlay -->
        <div v-if="isLoading" class="loader-overlay">
          <Vue3Lottie :animation-data="LoaderAnimation" :height="200" :width="200" />
        </div>

        <!-- Search results dropdown -->
        <div v-if="showSearchBox && (searchResults.length > 0 || popularPlaces.length > 0 || searchPerformed)"
          class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto max-[768px]:w-full max-[768px]:top-[45%] max-[768px]:left-0">

          <!-- Existing search results -->
          <div v-if="searchResults.length > 0">
            <div v-for="result in searchResults" :key="result.unified_location_id" @click="selectLocation(result)"
              class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
              <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
                <img :src="flighIcon" v-if="result.name.toLowerCase().includes('airport')" class="w-1/2 h-1/2" />
                <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2 group-hover:stroke-white">
                  <path clip-rule="evenodd"
                    d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path clip-rule="evenodd"
                    d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </div>
              <div class="flex flex-col">
                <div class="font-medium">{{ result.name }}</div>
                <div class="text-sm text-gray-500 group-hover:text-white">
                  {{ [result.city, result.country].filter(Boolean).join(', ') }}
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
            <div v-for="place in popularPlaces" :key="place.unified_location_id" @click="selectLocation(place)"
              class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
              <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center max-[768px]:flex-[0.2]">
                <img :src="flighIcon" v-if="place.name.toLowerCase().includes('airport')" class="w-1/2 h-1/2" />
                <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2 group-hover:stroke-white">
                  <path clip-rule="evenodd"
                    d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path clip-rule="evenodd"
                    d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </div>
              <div class="flex flex-col max-[768px]:flex-1">
                <div class="font-medium">{{ place.name }}</div>
                <div class="text-sm text-gray-500 group-hover:text-white">
                  {{ [place.city, place.country].filter(Boolean).join(', ') }}
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Dropoff search results dropdown -->
        <div v-if="showDropoffSearchBox && dropoffSearchResults.length > 0"
          class="search-results absolute z-20 top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-[20%] p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto max-[768px]:w-full max-[768px]:top-[60%] max-[768px]:left-0">
            <div v-for="result in dropoffSearchResults" :key="result.unified_location_id" @click="selectDropoffLocation(result)"
              class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
              <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
                <img :src="flighIcon" v-if="result.name.toLowerCase().includes('airport')" class="w-1/2 h-1/2" />
                <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2 group-hover:stroke-white">
                  <path clip-rule="evenodd"
                    d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path clip-rule="evenodd"
                    d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </div>
              <div class="flex flex-col">
                <div class="font-medium">{{ result.name }}</div>
                <div class="text-sm text-gray-500 group-hover:text-white">
                  {{ [result.city, result.country].filter(Boolean).join(', ') }}
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
const searchBarContainer = ref(null);
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { Vue3Lottie } from 'vue3-lottie';
import LoaderAnimation from '../../../public/animations/Loader-animation.json';
import flighIcon from '../../assets/flighIcon.svg';

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
  provider: null, // Replaces 'source'
  provider_pickup_id: null, // Replaces 'greenmotion_location_id'
  start_time: '09:00',
  end_time: '09:00',
  age: 35,
  rentalCode: '1',
  currency: null,
  fuel: null,
  userid: null,
  username: null,
  language: null,
  full_credit: null,
  promocode: null,
  dropoff_location_id: null,
  dropoff_where: "",
});

const props = defineProps({
  prefill: Object,
});

const pickupDate = ref(null);
const returnDate = ref(null);
const startTime = ref({ hours: 9, minutes: 0 });
const endTime = ref({ hours: 9, minutes: 0 });
const searchResults = ref([]);
const dateError = ref(false);
const isSearching = ref(false);
const isLoading = ref(false);
const searchTimeout = ref(null);
const searchPerformed = ref(false);
const showSearchBox = ref(false);
const popularPlaces = ref([]);
const locationError = ref(null);
const isProviderLocation = ref(false);
const dropoffSearchResults = ref([]);
const showDropoffSearchBox = ref(false);
const selectedLocationProviders = ref([]);
const showProviderSelection = ref(false);
const dropoffProvider = ref(null);
const selectedPickupLocation = ref(null);


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

const handleInputClick = () => {
  showSearchBox.value = !showSearchBox.value;
  showDropoffSearchBox.value = false; // Close other dropdown
  if (showSearchBox.value && !isSearching.value && !searchPerformed.value) {
    fetchPopularPlaces();
  }
};

const handleDropoffInputClick = () => {
  showDropoffSearchBox.value = !showDropoffSearchBox.value;
  showSearchBox.value = false; // Close other dropdown
};

const fetchPopularPlaces = async () => {
  try {
    const response = await axios.get(`/unified_locations.json`);
    // popularPlaces.value = response.data.filter(place => place.our_location_id !== null || (place.providers && place.providers.length > 0));
    popularPlaces.value = response.data;
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

watch(pickupDate, (newPickupDate) => {
  if (newPickupDate) {
    form.value.date_from = newPickupDate.toISOString().split('T')[0];
    const newReturnDate = new Date(newPickupDate);
    newReturnDate.setDate(newReturnDate.getDate() + 1);
    
    if (!returnDate.value || newReturnDate > returnDate.value) {
        returnDate.value = newReturnDate;
    }
  }
}, { deep: true });

watch(returnDate, (newValue) => {
  if (newValue) {
    form.value.date_to = newValue.toISOString().split('T')[0];
  }
}, { deep: true });

watch(startTime, (newTime) => {
  if (newTime) {
    const hours = newTime.hours.toString().padStart(2, '0');
    const minutes = newTime.minutes.toString().padStart(2, '0');
    form.value.start_time = `${hours}:${minutes}`;
  }
});

watch(endTime, (newTime) => {
  if (newTime) {
    const hours = newTime.hours.toString().padStart(2, '0');
    const minutes = newTime.minutes.toString().padStart(2, '0');
    form.value.end_time = `${hours}:${minutes}`;
  }
});

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
      // Fetch from the static JSON file
      const response = await axios.get(`/unified_locations.json`);
      const allLocations = response.data;
      const searchTerm = form.value.where.toLowerCase();

      // Filter locations based on the search term
      searchResults.value = allLocations.filter(location =>
        location.name.toLowerCase().includes(searchTerm) ||
        (location.city && location.city.toLowerCase().includes(searchTerm)) ||
        (location.country && location.country.toLowerCase().includes(searchTerm))
      );

      searchPerformed.value = true;
    } catch (error) {
      console.error("Error fetching or filtering locations:", error);
      searchResults.value = [];
      searchPerformed.value = true;
    } finally {
      isSearching.value = false;
    }
  }, 300);
};

const selectLocation = (result) => {
  selectedPickupLocation.value = result;
  form.value.where = result.name + (result.city ? `, ${result.city}` : '');
  form.value.latitude = result.latitude;
  form.value.longitude = result.longitude;
  form.value.city = result.city;
  form.value.country = result.country;

  showSearchBox.value = false;
  searchPerformed.value = false;
  searchResults.value = [];

  // If it's our own location, we can submit directly.
  if (result.our_location_id) {
    form.value.provider = 'internal';
    isProviderLocation.value = false;
    // Potentially auto-submit or enable submit button here if desired.
    return;
  }

  // If there are external providers
  if (result.providers && result.providers.length > 0) {
    // For both single and multiple providers, we want to show the dropoff location.
    // We'll use the first provider to fetch potential dropoff locations.
    selectProvider(result.providers[0]);

    // If there are multiple providers, we set the provider type to 'mixed' for the search query.
    if (result.providers.length > 1) {
      form.value.provider = 'mixed';
      dropoffProvider.value = result.providers[0].provider;
    }
  } else {
    // No providers and not an internal location, reset
    isProviderLocation.value = false;
    form.value.provider = null;
    form.value.provider_pickup_id = null;
    dropoffProvider.value = null;
  }
};

const selectProvider = async (provider) => {
  form.value.provider = provider.provider;
  form.value.provider_pickup_id = provider.pickup_id;
  showProviderSelection.value = false;
  selectedLocationProviders.value = [];

  // Set default dropoff to be same as pickup
  form.value.dropoff_where = form.value.where;
  form.value.dropoff_location_id = form.value.provider_pickup_id;

  isProviderLocation.value = true;
  await fetchDropoffLocations(provider.provider, provider.pickup_id);
};


const fetchDropoffLocations = async (provider, locationId) => {
  if (!provider || !locationId) return;
  try {
    const response = await axios.get(`/api/${provider}/dropoff-locations/${locationId}`);
    let dropoffs = response.data.locations || response.data;
    if (selectedPickupLocation.value) {
        // Ensure pickup location is not already in the list before adding
        const pickupExists = dropoffs.some(loc => loc.unified_location_id === selectedPickupLocation.value.unified_location_id);
        if (!pickupExists) {
            dropoffs.unshift(selectedPickupLocation.value);
        }
    }
    dropoffSearchResults.value = dropoffs;
  } catch (error) {
    console.error(`Error fetching dropoff locations for ${provider}:`, error);
    dropoffSearchResults.value = [];
  }
};

const selectDropoffLocation = (result) => {
  const locationName = result.name + (result.city ? `, ${result.city}` : '');
  form.value.dropoff_where = locationName;
  
  const providerToFind = form.value.provider === 'mixed' ? dropoffProvider.value : form.value.provider;
  const providerData = result.providers.find(p => p.provider === providerToFind);

  if (providerData) {
    form.value.dropoff_location_id = providerData.pickup_id;
  } else {
    console.error('Selected provider not available at the chosen dropoff location.');
    form.value.dropoff_location_id = null; 
  }
  
  showDropoffSearchBox.value = false;
};


const submit = async () => {
  if (!form.value.date_from || !form.value.date_to || !form.value.where) {
    dateError.value = true;
    return;
  }
  dateError.value = false;
  isLoading.value = true;

  try {
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

    await new Promise(resolve => {
        router.get(route('search', { locale: usePage().props.locale }), form.value, {
            onFinish: () => resolve(),
        });
    });
  } catch (error) {
    console.error("An error occurred during submission:", error);
  } finally {
    isLoading.value = false;
  }
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
  if (searchBarContainer.value && !searchBarContainer.value.contains(event.target)) {
    showSearchBox.value = false;
    showDropoffSearchBox.value = false;
  }
};

onMounted(async () => {
  document.addEventListener('click', closeSearchResults);
  fetchPopularPlaces();

  // Set default currency if not already set
  if (!form.value.currency) {
    form.value.currency = 'USD';
  }

  // Set default dates if not prefilled
  if (!props.prefill?.date_from) {
    const today = new Date();
    pickupDate.value = today;
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    returnDate.value = tomorrow;
  }

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
    form.value.provider = props.prefill.provider || null;
    form.value.provider_pickup_id = props.prefill.provider_pickup_id || null;
    form.value.start_time = props.prefill.start_time || '09:00';
    form.value.end_time = props.prefill.end_time || '09:00';

    if (props.prefill.start_time) {
        const [hours, minutes] = props.prefill.start_time.split(':');
        startTime.value = { hours: parseInt(hours), minutes: parseInt(minutes) };
    }
    if (props.prefill.end_time) {
        const [hours, minutes] = props.prefill.end_time.split(':');
        endTime.value = { hours: parseInt(hours), minutes: parseInt(minutes) };
    }
    form.value.dropoff_location_id = props.prefill.dropoff_location_id || null;
    form.value.dropoff_where = props.prefill.dropoff_where || "";

    if (props.prefill.provider && props.prefill.provider !== 'internal' && props.prefill.provider_pickup_id) {
      isProviderLocation.value = true;
      await fetchDropoffLocations(props.prefill.provider, props.prefill.provider_pickup_id);
      if (props.prefill.dropoff_where) {
        form.value.dropoff_where = props.prefill.dropoff_where;
      } else if (props.prefill.dropoff_location_id) {
        const dropoffLocation = dropoffSearchResults.value.find(
          loc => loc.provider_location_id === props.prefill.dropoff_location_id
        );
        if (dropoffLocation) {
          const locationName = dropoffLocation.label + (dropoffLocation.below_label ? `, ${dropoffLocation.below_label}` : '');
          form.value.dropoff_where = locationName;
        }
      }
    }

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
.search-results::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.search-results {
  scrollbar-width: none; /* Firefox */
}

.search_bar {
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
}

.search-results div div {
  transition: all 0.2s ease;
}

.search-results {
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.349);
}

.loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.2);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

@media screen and (max-width:768px) {
  .search_bar {
    box-shadow: none;
  }
}
</style>
