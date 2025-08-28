<template>
  <section class="full-w-container max-[768px]:pb-[2rem]" @click="closeSearchResults">
    <div class="search_bar rounded-[20px] max-[768px]:border-[1px]">
      <div class="flex relative max-[768px]:flex-col max-[768px]:items-center">
        <div
          class="column w-[20%] max-[768px]:w-[100%] max-[768px]:p-[1.5rem] bg-green-600 text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px] max-[768px]:rounded-tr-[16px] max-[768px]:rounded-tl-[16px] max-[768px]:rounded-bl-[0] max-[768px]:border-[1px]">
          <span class="text-[1.75rem] font-medium max-[768px]:text-[1.5rem]">{{ _t('homepage', 'our_partner') }}</span>
        </div>
        <form @submit.prevent="handleSearch"
          class="column w-[80%] max-[768px]:w-[100%] px-[2rem] py-[1rem] rounded-tr-[16px] rounded-br-[16px] bg-white grid grid-cols-5 max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-10 max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px] max-[768px]:px-[1rem]">
          <div class="col col-span-2 flex flex-col justify-center">
            <div class="flex flex-col">
              <div class="col relative">
                <label for="location_name" class="mb-4 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'pickup_return_location_label') }}</label>
                <div class="flex items-end relative">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="absolute left-[-0.35rem] top-[-0.15rem]">
                    <path
                      d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                      stroke="#153B4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <input
                    type="text"
                    id="location_name"
                    v-model="searchForm.location_name"
                    @focus="showSuggestions = true"
                    @blur="handleBlur"
                    class="pl-7 border-b border-customLightGrayColor focus:outline-none w-[80%] max-[768px]:w-full"
                    :placeholder="_t('homepage', 'pickup_location_placeholder')"
                    autocomplete="off"
                    required
                  />
                  <input type="hidden" v-model="searchForm.location_id" />
                </div>
                <ul
                    v-if="showSuggestions && autocompleteSuggestions.length"
                    class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto mt-1"
                    @mousedown="handleSuggestionsMousedown"
                    @mouseup="handleSuggestionsMouseup"
                >
                    <li
                        v-for="location in autocompleteSuggestions"
                        :key="location.locationID"
                        @click="selectLocation(location)"
                        class="px-4 py-2 cursor-pointer hover:bg-gray-100"
                    >
                        {{ location.name }}
                    </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="col-span-2 flex items-center gap-4">
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'pickup_date_label') }}</label>
              <VueDatePicker v-model="pickupDate" :enable-time-picker="true" uid="pickup-date" auto-apply
                :placeholder="_t('homepage', 'pickup_date_placeholder')" class="w-full" :min-date="getMinPickupDate()" :format="formatDateTime"
                text-input />
            </div>
            <div class="flex flex-col">
              <label class="mb-2 inline-block text-customLightGrayColor font-medium">{{ _t('homepage', 'return_date_label') }}</label>
              <VueDatePicker v-model="returnDate" :enable-time-picker="true" uid="return-date" auto-apply
                :placeholder="_t('homepage', 'return_date_placeholder')" class="w-full" :min-date="getMinReturnDate()" :format="formatDateTime"
                text-input />
            </div>
          </div>

          <div class="inner-col flex justify-center items-center">
            <button type="submit"
              class="bg-green-500 text-customPrimaryColor-foreground rounded-[40px] w-[138px] max-[768px]:w-full py-4 text-center">
              {{ _t('homepage', 'search_button') }}
            </button>
          </div>
        </form>
      </div>
    </div>
     <!-- Error Dialog -->
     <ErrorDialog :show="errorMessage !== ''" :message="errorMessage" @close="clearError" />
  </section>
</template>

<script setup>
import { ref, watch, onMounted, computed } from "vue";
import axios from "axios";
import { router, usePage } from "@inertiajs/vue3";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { enGB } from 'date-fns/locale';

const props = defineProps({
  prefill: Object,
});

const searchForm = ref({
    location_id: props.prefill?.location_id || null,
    location_name: props.prefill?.location_name || '',
    start_date: props.prefill?.start_date || '',
    start_time: props.prefill?.start_time || '09:00', // Default to 09:00
    end_date: props.prefill?.end_date || '',
    end_time: props.prefill?.end_time || '09:00',   // Default to 09:00
    age: props.prefill?.age || 35,
    rentalCode: props.prefill?.rentalCode || '1',
});

const autocompleteSuggestions = ref([]);
const showSuggestions = ref(false);
let debounceTimeout = null;
const preventHide = ref(false);

const pickupDate = ref(null);
const returnDate = ref(null);
const dateError = ref(false);
const locationError = ref(null);

// Compute error message to display in dialog
const errorMessage = computed(() => {
  if (dateError.value) {
    return "Please fill in all fields: location, pickup date, pickup time, return date, and return time.";
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

const fetchLocations = async () => {
    if (searchForm.value.location_name.length < 2) {
        autocompleteSuggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(route('api.greenmotion.locations-autocomplete', { locale: usePage().props.locale }), {
                params: {
                    search_term: searchForm.value.location_name,
                    country_id: 1, // Assuming default country ID 1 (e.g., UK) for now
                    language: usePage().props.locale,
                }
            });
            autocompleteSuggestions.value = response.data;
            showSuggestions.value = true;
        } catch (error) {
            console.error("Error fetching locations for autocomplete:", error);
            autocompleteSuggestions.value = [];
            showSuggestions.value = false;
        }
    }, 300);
};

const selectLocation = (location) => {
    searchForm.value.location_name = location.name;
    searchForm.value.location_id = location.locationID;
    autocompleteSuggestions.value = [];
    showSuggestions.value = false;
};

const handleBlur = () => {
    if (!preventHide.value) {
        showSuggestions.value = false;
    }
};

const handleSuggestionsMousedown = () => {
    preventHide.value = true;
};

const handleSuggestionsMouseup = () => {
    preventHide.value = false;
};

const handleSearch = () => {
    if (!searchForm.value.location_id && searchForm.value.location_name) {
        const matchedLocation = autocompleteSuggestions.value.find(loc => loc.name === searchForm.value.location_name);
        if (matchedLocation) {
            searchForm.value.location_id = matchedLocation.locationID;
        } else {
            locationError.value = 'Please select a valid location from the suggestions.';
            return;
        }
    }

    if (!searchForm.value.location_id || !searchForm.value.start_date || !searchForm.value.start_time || !searchForm.value.end_date || !searchForm.value.end_time) {
        dateError.value = true;
        return;
    }
    dateError.value = false;
    locationError.value = null;

    router.get(route('green-motion-cars', { locale: usePage().props.locale }), searchForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(() => searchForm.value.location_name, (newValue, oldValue) => {
    fetchLocations();
});

const formatDateTime = (date) => {
  if (!date) return "Select date and time";
  const options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  };
  return date.toLocaleDateString('en-GB', options);
};

watch(pickupDate, (newDateTime) => {
  if (newDateTime) {
    // If the time is not explicitly set by the user, default to 09:00
    if (newDateTime.getHours() === new Date().getHours() && newDateTime.getMinutes() === new Date().getMinutes()) {
        newDateTime.setHours(9, 0, 0, 0);
    }
    searchForm.value.start_date = newDateTime.toISOString().split('T')[0];
    searchForm.value.start_time = newDateTime.toTimeString().split(' ')[0].substring(0, 5); // HH:mm
    if (returnDate.value && newDateTime > returnDate.value) {
      returnDate.value = null;
      searchForm.value.end_date = '';
      searchForm.value.end_time = '';
    }
  } else {
    searchForm.value.start_date = '';
    searchForm.value.start_time = '';
  }
});

watch(returnDate, (newDateTime) => {
  if (newDateTime) {
    // If the time is not explicitly set by the user, default to 09:00
    if (newDateTime.getHours() === new Date().getHours() && newDateTime.getMinutes() === new Date().getMinutes()) {
        newDateTime.setHours(9, 0, 0, 0);
    }
    searchForm.value.end_date = newDateTime.toISOString().split('T')[0];
    searchForm.value.end_time = newDateTime.toTimeString().split(' ')[0].substring(0, 5); // HH:mm
  } else {
    searchForm.value.end_date = '';
    searchForm.value.end_time = '';
  }
});

const getMinPickupDate = () => {
  const today = new Date();
  today.setHours(0, 0, 0, 0); // Set time to the beginning of the day
  return today;
};

const getMinReturnDate = () => {
  if (pickupDate.value) {
    const minReturnDate = new Date(pickupDate.value);
    minReturnDate.setDate(minReturnDate.getDate() + 1);
    minReturnDate.setHours(0, 0, 0, 0); // Set time to the beginning of the day
    return minReturnDate;
  }
  const today = new Date();
  today.setHours(0, 0, 0, 0); // Set time to the beginning of the day
  return today;
};

onMounted(async () => {
    if (props.prefill) {
        if (props.prefill.location_id && !searchForm.value.location_name) {
            try {
                const response = await axios.get(route('api.greenmotion.locations', { locale: usePage().props.locale }), {
                    params: {
                        location_id: props.prefill.location_id,
                    }
                });
                if (response.data && response.data.name) {
                    searchForm.value.location_name = response.data.name;
                } else {
                    searchForm.value.location_id = null;
                    searchForm.value.location_name = '';
                }
            } catch (error) {
                console.error("Error fetching initial location name:", error);
                searchForm.value.location_id = null;
                searchForm.value.location_name = '';
            }
        }
        if (props.prefill.start_date) {
            const startTime = props.prefill.start_time || '09:00';
            pickupDate.value = new Date(`${props.prefill.start_date}T${startTime}:00`);
        }
        if (props.prefill.end_date) {
            const endTime = props.prefill.end_time || '09:00';
            returnDate.value = new Date(`${props.prefill.end_date}T${endTime}:00`);
        }
    }
});


const closeSearchResults = (event) => {
  if (showSuggestions.value &&
    !event.target.closest("ul") && // Check if click is outside the suggestions list
    !event.target.closest("input[type='text']")) { // Check if click is outside the input field
    showSuggestions.value = false;
  }
};

// Error Dialog Component (copied from SearchBar.vue)
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
