<template>
  <section class="full-w-container max-[768px]:pb-[2rem] max-[768px]:w-full" ref="searchBarContainer">
    <div class="search_bar rounded-[20px] max-[768px]:border-[1px]">
      <div class="flex relative max-[768px]:flex-col max-[768px]:items-center">
        <div v-if="!simple"
          class="column w-[20%] max-[768px]:w-[100%] max-[768px]:p-[1.5rem] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px] max-[768px]:rounded-tr-[16px] max-[768px]:rounded-tl-[16px] max-[768px]:rounded-bl-[0] max-[768px]:border-[1px]">
          <span class="text-[1.75rem] font-medium max-[768px]:text-[1.5rem]">{{ _t('homepage', 'search_bar_header')
            }}</span>
        </div>
        <form @submit.prevent="submit"
          class="column px-[2rem] py-[1.5rem] bg-white grid grid-cols-12 gap-6 items-center max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-6 max-[768px]:px-[1.5rem] shadow-sm"
          :class="[
            simple ? 'w-full rounded-[20px]' : 'w-[80%] rounded-tr-[16px] rounded-br-[16px] max-[768px]:w-[100%] max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px]'
          ]">

          <!-- Locations Section -->
          <div class="col-span-6 flex gap-4 relative max-[768px]:flex-col max-[768px]:w-full"
            :class="{ 'flex-col': !isProviderLocation, 'flex-row': isProviderLocation }">
            <!-- Pickup Location -->
            <div class="w-full relative group">
              <label class="block text-xs font-semibold text-customLightGrayColor uppercase tracking-wider mb-2 pl-1">{{
                _t('homepage', 'pickup_return_location_label') }}</label>
              <div
                class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 transition-colors group-hover:border-customPrimaryColor focus-within:border-customPrimaryColor focus-within:ring-1 focus-within:ring-customPrimaryColor/20">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                  class="text-customPrimaryColor mr-3 flex-shrink-0">
                  <path
                    d="M12 21.75C12 21.75 19.5 16.5 19.5 9.75C19.5 7.76088 18.7098 5.85322 17.3033 4.4467C15.8968 3.04018 13.9891 2.25 12 2.25C10.0109 2.25 8.10322 3.04018 6.6967 4.4467C5.29018 5.85322 4.5 7.76088 4.5 9.75C4.5 16.5 12 21.75 12 21.75ZM15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <input type="text" v-model="form.where" @input="handleSearchInput" @click="handleInputClick"
                  :placeholder="isSearching ? _t('homepage', 'searching_placeholder') : _t('homepage', 'pickup_location_placeholder')"
                  class="bg-transparent border-none p-0 w-full text-customDarkBlackColor placeholder-gray-400 focus:ring-0 focus:border-none focus:outline-none text-sm font-medium"
                  required />
              </div>
              <!-- Location Error Message -->
              <div v-if="locationError" class="text-red-500 text-xs mt-1 pl-1 flex items-center gap-1">
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
                </svg>
                {{ locationError }}
              </div>
            </div>

            <!-- Dropoff Location -->
            <div v-if="isProviderLocation" class="w-full relative group">
              <label
                class="block text-xs font-semibold text-customLightGrayColor uppercase tracking-wider mb-2 pl-1">Dropoff
                Location</label>
              <div
                class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 transition-colors group-hover:border-customPrimaryColor focus-within:border-customPrimaryColor focus-within:ring-1 focus-within:ring-customPrimaryColor/20">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                  class="text-customPrimaryColor mr-3 flex-shrink-0">
                  <path
                    d="M12 21.75C12 21.75 19.5 16.5 19.5 9.75C19.5 7.76088 18.7098 5.85322 17.3033 4.4467C15.8968 3.04018 13.9891 2.25 12 2.25C10.0109 2.25 8.10322 3.04018 6.6967 4.4467C5.29018 5.85322 4.5 7.76088 4.5 9.75C4.5 16.5 12 21.75 12 21.75ZM15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <input type="text" v-model="form.dropoff_where" @click="handleDropoffInputClick"
                  placeholder="Enter dropoff location"
                  class="bg-transparent border-none p-0 w-full text-customDarkBlackColor placeholder-gray-400 focus:ring-0 focus:border-none focus:outline-none text-sm font-medium"
                  readonly />
              </div>
            </div>
          </div>

          <!-- Date Picker Section -->
          <div class="col-span-4 relative rental-dates-container max-[768px]:w-full">
            <div class="flex flex-col w-full group">
              <label
                class="block text-xs font-semibold text-customLightGrayColor uppercase tracking-wider mb-2 pl-1">Rental
                dates</label>

              <!-- Desktop Version -->
              <VueDatePicker v-if="!isMobile" v-model="dateRange" range :multi-calendars="2" :enable-time-picker="false"
                :min-date="new Date()" :format="formatRangeDate" :close-on-click-outside="true" :teleport="true"
                @internal-model-change="handleDateUpdate" placeholder="Select dates" class="w-full" ref="datepicker">
                <template #trigger>
                  <div
                    class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 cursor-pointer transition-colors group-hover:border-customPrimaryColor hover:bg-gray-100">
                    <div class="flex items-center gap-2 overflow-hidden">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-customPrimaryColor flex-shrink-0"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span v-if="dateRange && dateRange[0] && dateRange[1]"
                        class="text-customDarkBlackColor font-medium text-sm truncate">
                        {{ formatRangeDate(dateRange) }}
                        <span
                          class="text-xs text-customPrimaryColor font-semibold ml-1 bg-customPrimaryColor/10 px-1.5 py-0.5 rounded">
                          {{ totalDays }} {{ totalDays > 1 ? 'days' : 'day' }}
                        </span>
                      </span>
                      <span v-else class="text-gray-400 text-sm">
                        Select Rental Dates
                      </span>
                    </div>
                  </div>
                </template>

                <template #action-row="{ selectDate, closePicker }">
                  <div class="flex flex-col bg-[#153B4F1A] text-white rounded-[3px] overflow-hidden w-full">
                    <!-- Time Selection Row -->
                    <div class="grid grid-cols-2 gap-4 p-5 border-b border-white/10">
                      <!-- Pick-up time -->
                      <div class="flex flex-col">
                        <label
                          class="text-[11px] uppercase tracking-wider text-customPrimaryColor font-bold mb-2">Pick-up
                          time</label>
                        <div class="relative group">
                          <select v-model="selectedStartTime"
                            class="w-full appearance-none bg-[#1a3b4b] border border-[#2c5265] rounded-lg px-4 py-2.5 text-sm text-white font-medium focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor outline-none cursor-pointer transition-all hover:bg-[#23485a]">
                            <option v-for="time in timeOptions" :key="`start-${time}`" :value="time"
                              class="bg-[#0F2936] text-white">{{ time }}</option>
                          </select>
                          <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/50 group-hover:text-white transition-colors">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                              <path
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Drop-off time -->
                      <div class="flex flex-col">
                        <label
                          class="text-[11px] uppercase tracking-wider text-customPrimaryColor font-bold mb-2">Drop-off
                          time</label>
                        <div class="relative group">
                          <select v-model="selectedEndTime"
                            class="w-full appearance-none bg-[#1a3b4b] border border-[#2c5265] rounded-lg px-4 py-2.5 text-sm text-white font-medium focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor outline-none cursor-pointer transition-all hover:bg-[#23485a]">
                            <option v-for="time in timeOptions" :key="`end-${time}`" :value="time"
                              class="bg-[#0F2936] text-white">{{ time }}</option>
                          </select>
                          <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/50 group-hover:text-white transition-colors">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                              <path
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Total Duration Row -->
                    <div class="px-5 py-4 bg-customPrimaryColor flex justify-between items-center">
                      <span class="text-sm text-gray-300 font-medium">Total Rental Duration:</span>
                      <span class="text-lg font-bold text-white tracking-wide">{{ totalDays }} <span
                          class="text-sm font-normal text-gray-400">{{ totalDays > 1 ? 'Days' : 'Day' }}</span></span>
                    </div>
                  </div>
                </template>
              </VueDatePicker>

              <!-- Mobile Version -->
              <div v-else>
                <!-- Trigger -->
                <div @click="openMobileDatePicker"
                  class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 cursor-pointer transition-colors group-hover:border-customPrimaryColor hover:bg-gray-100">
                  <div class="flex items-center gap-2 overflow-hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-customPrimaryColor flex-shrink-0"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span v-if="dateRange && dateRange[0] && dateRange[1]"
                      class="text-customDarkBlackColor font-medium text-sm truncate">
                      {{ formatRangeDate(dateRange) }}
                      <span
                        class="text-xs text-customPrimaryColor font-semibold ml-1 bg-customPrimaryColor/10 px-1.5 py-0.5 rounded">
                        {{ totalDays }} {{ totalDays > 1 ? 'days' : 'day' }}
                      </span>
                    </span>
                    <span v-else class="text-gray-400 text-sm">
                      Select Rental Dates
                    </span>
                  </div>
                </div>

                <!-- Full Screen Modal -->
                <Teleport to="body">
                  <div v-if="showMobileDatePicker" class="fixed inset-0 z-[99999] bg-white flex flex-col">
                    <!-- Header -->
                    <div class="flex justify-between items-center p-4 border-b border-gray-100">
                      <h3 class="text-lg font-bold text-customPrimaryColor m-0 leading-none">Select Rental Dates</h3>
                      <button @click="closeMobileDatePicker" class="p-2 -mr-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                      </button>
                    </div>

                    <!-- Scrollable Body -->
                    <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-6">
                      <!-- Calendar -->
                      <VueDatePicker v-model="mobileDateRange" inline auto-apply range :enable-time-picker="false"
                        :min-date="new Date()" :multi-calendars="0" :month-change-on-scroll="false"
                        class="w-full justify-center flex" />

                      <!-- Time Selection -->
                      <div class="bg-[#153B4F1A] rounded-xl p-4">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                          <!-- Pick-up time -->
                          <div class="flex flex-col">
                            <label
                              class="text-[11px] uppercase tracking-wider text-customPrimaryColor font-bold mb-2">Pick-up
                              time</label>
                            <div class="relative">
                              <select v-model="selectedStartTime"
                                class="w-full appearance-none bg-[#1a3b4b] border border-[#2c5265] rounded-lg px-3 py-2.5 text-sm text-white font-medium focus:ring-0 outline-none">
                                <option v-for="time in timeOptions" :key="`start-${time}`" :value="time"
                                  class="bg-[#0F2936]">{{ time
                                  }}</option>
                              </select>
                            </div>
                          </div>
                          <!-- Drop-off time -->
                          <div class="flex flex-col">
                            <label
                              class="text-[11px] uppercase tracking-wider text-customPrimaryColor font-bold mb-2">Drop-off
                              time</label>
                            <div class="relative">
                              <select v-model="selectedEndTime"
                                class="w-full appearance-none bg-[#1a3b4b] border border-[#2c5265] rounded-lg px-3 py-2.5 text-sm text-white font-medium focus:ring-0 outline-none">
                                <option v-for="time in timeOptions" :key="`end-${time}`" :value="time"
                                  class="bg-[#0F2936]">{{ time }}
                                </option>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div
                          class="pt-4 border-t border-white/10 flex justify-between items-center text-customPrimaryColor">
                          <span class="text-sm font-semibold">Total Duration:</span>
                          <span class="text-lg font-bold">{{ mobileTotalDays }} Days</span>
                        </div>
                      </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-4 border-t border-gray-100 bg-white">
                      <button @click="confirmMobileDateSelection"
                        class="w-full py-4 bg-customPrimaryColor text-white font-bold rounded-xl shadow-lg active:scale-95 transition-transform text-lg">
                        Confirm & Select
                      </button>
                    </div>
                  </div>
                </Teleport>
              </div>
            </div>
            <!-- Date Error Message -->
            <div v-if="dateError" class="text-red-500 text-xs mt-1 pl-1 flex items-center gap-1">
              <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd" />
              </svg>
              {{ _t('homepage', 'dates_required') || 'Rental dates are required' }}
            </div>
          </div>

          <!-- Submit Button -->
          <div class="col-span-2 flex justify-end items-center mt-[20px] max-[768px]:w-full">
            <button type="submit"
              class="bg-customPrimaryColor text-white rounded-xl w-full py-3.5 text-base font-bold shadow-md hover:bg-customPrimaryColor/90 hover:shadow-lg transition-all transform active:scale-[0.98] flex justify-center items-center gap-2"
              :disabled="isLoading">
              <span v-if="!isLoading">{{ _t('homepage', 'search_button') }}</span>
              <span v-else class="searching-text">Searching<span class="dot dot-1">.</span><span
                  class="dot dot-2">.</span><span class="dot dot-3">.</span></span>
            </button>
          </div>
        </form>

        <!-- Loader Overlay (Global) -->
        <div v-if="isLoading && false" class="loader-overlay"> <!-- Disabled global loader in favor of button state -->
          <Vue3Lottie :animation-data="LoaderAnimation" :height="200" :width="200" />
        </div>

        <!-- Search results dropdown -->
        <div v-if="showSearchBox && (searchResults.length > 0 || popularPlaces.length > 0 || searchPerformed)"
          class="search-results absolute z-[9999] top-[105%] w-[50%] rounded-[12px] border border-gray-100 left-0 p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto shadow-xl max-[768px]:w-full max-[768px]:top-[25%] max-[768px]:left-0">

          <!-- Existing search results -->
          <div v-if="searchResults.length > 0">
            <div v-for="result in searchResults" :key="result.unified_location_id" @click="selectLocation(result)"
              class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
              <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
                <img :src="flighIcon" v-if="result.name.toLowerCase().includes('airport')" class="w-1/2 h-1/2" />
                <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                  class="w-1/2 h-1/2 group-hover:stroke-white">
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

          <!-- Show search prompt when no search has been performed -->
          <div v-else-if="!searchPerformed && !isSearching && showSearchBox" class="p-3 text-center">
            <div class="text-sm text-gray-600">
              {{ _t('homepage', 'start_typing_to_search_all_locations') }}
            </div>
          </div>
        </div>

        <!-- Dropoff search results dropdown -->
        <div v-if="showDropoffSearchBox && dropoffSearchResults.length > 0"
          class="search-results absolute z-[9999] top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-0 p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto max-[768px]:w-full max-[768px]:top-[46%] max-[768px]:left-0">
          <div v-for="result in dropoffSearchResults" :key="result.unified_location_id"
            @click="selectDropoffLocation(result)"
            class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
            <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
              <img :src="flighIcon" v-if="result.name.toLowerCase().includes('airport')" class="w-1/2 h-1/2" />
              <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                class="w-1/2 h-1/2 group-hover:stroke-white">
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
  unified_location_id: null, // For multi-provider unified location search
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
  simple: {
    type: Boolean,
    default: false
  }
});

const dateRange = ref(null);
const selectedStartTime = ref('09:00');
const selectedEndTime = ref('09:00');

// Generate time options (00:00 to 23:30)
const timeOptions = [];
for (let i = 0; i < 24; i++) {
  for (let j = 0; j < 60; j += 30) {
    const hour = i.toString().padStart(2, '0');
    const minute = j.toString().padStart(2, '0');
    timeOptions.push(`${hour}:${minute}`);
  }
}

const totalDays = computed(() => {
  if (!dateRange.value || !dateRange.value[0] || !dateRange.value[1]) return 0;
  const start = new Date(dateRange.value[0]);
  const end = new Date(dateRange.value[1]);
  const diffTime = Math.abs(end - start);
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1; // Minimum 1 day
});

const formatRangeDate = (dates) => {
  if (!dates || !dates[0] || !dates[1]) return "";
  const start = dates[0];
  const end = dates[1];

  // Format: DD/MM/YYYY - DD/MM/YYYY
  const fmt = (d) => `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
  return `${fmt(start)} - ${fmt(end)}`;
};

const applyDateSelection = (selectDate) => {
  if (dateRange.value && dateRange.value[0] && dateRange.value[1]) {
    // Update form values
    form.value.date_from = dateRange.value[0].toISOString().split('T')[0];
    form.value.date_to = dateRange.value[1].toISOString().split('T')[0];
    form.value.start_time = selectedStartTime.value;
    form.value.end_time = selectedEndTime.value;

    selectDate(); // Close picker
  }
};

const handleDateUpdate = (val) => {
  // Check if we have a valid range selection (2 dates)
  if (val && Array.isArray(val) && val.length === 2 && val[0] && val[1]) {
    // PREVENT RECURSION: Only update if values actually changed
    const currentStart = dateRange.value?.[0]?.getTime();
    const currentEnd = dateRange.value?.[1]?.getTime();
    const newStart = val[0].getTime();
    const newEnd = val[1].getTime();

    if (currentStart !== newStart || currentEnd !== newEnd) {
      dateRange.value = val;
    }
  }
};

const searchResults = ref([]);
const dateError = ref(false);
const isSearching = ref(false);
const isLoading = ref(false);
const searchTimeout = ref(null);
const searchPerformed = ref(false);
const showSearchBox = ref(false);
const popularPlaces = ref([]);
const locationError = ref(null);
const isProviderLocation = ref(true);
const isMobile = ref(false);
const dropoffSearchResults = ref([]);
const showDropoffSearchBox = ref(false);
const selectedLocationProviders = ref([]);
const showProviderSelection = ref(false);
const dropoffProvider = ref(null);
const selectedPickupLocation = ref(null);
const hasSelectedPickupLocation = ref(false); // Track if user selected from dropdown
const hasSelectedDropoffLocation = ref(false); // Track if user selected dropoff from dropdown

// Clear error messages
const clearError = () => {
  dateError.value = false;
  locationError.value = null;
  showSearchBox.value = false;
  showDropoffSearchBox.value = false;
};

const handleInputClick = () => {
  showSearchBox.value = !showSearchBox.value;
  showDropoffSearchBox.value = false; // Close other dropdown
  // No need to fetch popular places since we want search-only behavior
};

const handleDropoffInputClick = () => {
  if (!dropoffSearchResults.value.length) {
    return;
  }
  showDropoffSearchBox.value = !showDropoffSearchBox.value;
  showSearchBox.value = false; // Close other dropdown
};

const fetchPopularPlaces = async () => {
  try {
    const response = await axios.get(`/unified_locations.json`);
    popularPlaces.value = []; // Don't show any popular places
  } catch (error) {
    console.error("Error fetching locations:", error);
    popularPlaces.value = [];
  }
};

// Watchers for dateRange to update form immediately if needed, mainly for debugging or live updates
watch(dateRange, (newVal) => {
  if (newVal && newVal[0] && newVal[1]) {
    form.value.date_from = newVal[0].toISOString().split('T')[0];
    form.value.date_to = newVal[1].toISOString().split('T')[0];
  }
});

watch(selectedStartTime, (newVal) => {
  form.value.start_time = newVal;
});

watch(selectedEndTime, (newVal) => {
  form.value.end_time = newVal;
});

// Watch for manual text changes to location field
watch(() => form.value.where, (newVal, oldVal) => {
  // If value changes and it's not from selectLocation (which sets the flag to true),
  // it means user manually typed/pasted something
  if (oldVal && newVal !== oldVal && hasSelectedPickupLocation.value) {
    // Check if the new value matches the selected location
    if (selectedPickupLocation.value) {
      const expectedValue = selectedPickupLocation.value.name + (selectedPickupLocation.value.city ? `, ${selectedPickupLocation.value.city}` : '');
      if (newVal !== expectedValue) {
        // User modified the text manually
        hasSelectedPickupLocation.value = false;
      }
    }
  }
});

const showMobileDatePicker = ref(false);
const mobileDateRange = ref(null);

const openMobileDatePicker = () => {
  // Sync mobile range with current selected range
  mobileDateRange.value = dateRange.value;
  showMobileDatePicker.value = true;
};

const closeMobileDatePicker = () => {
  showMobileDatePicker.value = false;
};

const confirmMobileDateSelection = () => {
  if (mobileDateRange.value) {
    dateRange.value = mobileDateRange.value;
  }
  showMobileDatePicker.value = false;
};

const mobileTotalDays = computed(() => {
  if (!mobileDateRange.value || !mobileDateRange.value[0] || !mobileDateRange.value[1]) return 0;
  const start = new Date(mobileDateRange.value[0]);
  const end = new Date(mobileDateRange.value[1]);
  const diffTime = Math.abs(end - start);
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;
});

const handleSearchInput = () => {
  // User manually typed something, reset the dropdown selection flag
  hasSelectedPickupLocation.value = false;

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
  hasSelectedPickupLocation.value = true; // User selected from dropdown
  locationError.value = null; // Clear any error
  form.value.where = result.name + (result.city ? `, ${result.city}` : '');
  form.value.latitude = result.latitude;
  form.value.longitude = result.longitude;
  form.value.city = result.city;
  form.value.country = result.country;

  // Always set the unified_location_id for multi-provider matching
  form.value.unified_location_id = result.unified_location_id || null;

  showSearchBox.value = false;
  searchPerformed.value = false;
  searchResults.value = [];

  // Check if it has our own location and/or external providers
  const hasInternalLocation = !!result.our_location_id;
  const hasProviders = result.providers && result.providers.length > 0;

  if (hasInternalLocation && !hasProviders) {
    // Only internal location, no external providers
    form.value.provider = 'internal';
    form.value.provider_pickup_id = null;
    form.value.dropoff_where = form.value.where;
    form.value.dropoff_location_id = null;
    dropoffSearchResults.value = [];
    isProviderLocation.value = true;
    return;
  }

  if (hasProviders) {
    // Has external providers - always use 'mixed' to fetch from ALL providers
    // (including internal if hasInternalLocation is true)
    form.value.provider = 'mixed';

    // Use first provider's pickup_id as the reference (backend will find all)
    form.value.provider_pickup_id = result.providers[0].pickup_id;
    dropoffProvider.value = result.providers[0].provider;

    // Fetch dropoff locations for the first provider
    selectProvider(result.providers[0]);
  } else {
    // No providers and not an internal location, reset
    isProviderLocation.value = false;
    form.value.provider = null;
    form.value.provider_pickup_id = null;
    form.value.unified_location_id = null;
    dropoffProvider.value = null;
  }
};

const providersWithDropoffList = new Set(['greenmotion', 'usave', 'adobe', 'locauto_rent', 'renteon']);
const providerSupportsDropoffList = (provider) => providersWithDropoffList.has(provider);

const selectProvider = async (provider) => {
  form.value.provider = provider.provider;
  form.value.provider_pickup_id = provider.pickup_id;
  showProviderSelection.value = false;
  selectedLocationProviders.value = [];

  // Set default dropoff to be same as pickup
  form.value.dropoff_where = form.value.where;
  form.value.dropoff_location_id = form.value.provider_pickup_id;

  isProviderLocation.value = true;
  dropoffSearchResults.value = [];

  if (providerSupportsDropoffList(provider.provider)) {
    await fetchDropoffLocations(provider.provider, provider.pickup_id);
  }
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
  hasSelectedDropoffLocation.value = true; // User selected dropoff from dropdown
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
  // Validate dates
  if (!form.value.date_from || !form.value.date_to || !form.value.where) {
    dateError.value = true;
    return;
  }

  // Validate that user selected from dropdown (not manually typed text)
  if (!hasSelectedPickupLocation.value) {
    locationError.value = 'Please select a location from the dropdown';
    showSearchBox.value = true; // Show dropdown to help user
    return;
  }

  dateError.value = false;
  locationError.value = null;
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



const closeSearchResults = (event) => {
  if (searchBarContainer.value && !searchBarContainer.value.contains(event.target)) {
    showSearchBox.value = false;
    showDropoffSearchBox.value = false;
  }
};

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768;
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
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateRange.value = [today, tomorrow];
    form.value.date_from = today.toISOString().split('T')[0];
    form.value.date_to = tomorrow.toISOString().split('T')[0];
  }

  if (props.prefill) {
    form.value.where = props.prefill.where || '';

    if (props.prefill.date_from && props.prefill.date_to) {
      dateRange.value = [new Date(props.prefill.date_from), new Date(props.prefill.date_to)];
    }

    form.value.latitude = props.prefill.latitude || null;
    form.value.longitude = props.prefill.longitude || null;
    form.value.radius = props.prefill.radius || 5000;
    form.value.city = props.prefill.city || null;
    form.value.state = props.prefill.state || null;
    form.value.country = props.prefill.country || null;
    form.value.provider = props.prefill.provider || null;
    form.value.provider_pickup_id = props.prefill.provider_pickup_id || null;

    if (props.prefill.start_time) {
      selectedStartTime.value = props.prefill.start_time;
      form.value.start_time = props.prefill.start_time;
    }
    if (props.prefill.end_time) {
      selectedEndTime.value = props.prefill.end_time;
      form.value.end_time = props.prefill.end_time;
    }

    form.value.dropoff_location_id = props.prefill.dropoff_location_id || null;
    form.value.dropoff_where = props.prefill.dropoff_where || "";

    if (props.prefill.provider && props.prefill.provider !== 'internal' && props.prefill.provider_pickup_id) {
      isProviderLocation.value = true;
      if (providerSupportsDropoffList(props.prefill.provider)) {
        await fetchDropoffLocations(props.prefill.provider, props.prefill.provider_pickup_id);
      }
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

  if (form.value.where && !form.value.dropoff_where) {
    form.value.dropoff_where = form.value.where;
  }

  // Ensure prefilled location is considered selected
  if (form.value.where) {
    hasSelectedPickupLocation.value = true;
  }

  checkMobile();
  window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
  document.removeEventListener("click", closeSearchResults);
  window.removeEventListener('resize', checkMobile); // Clean up resize listener
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
});

// Removed individual date watchers as we now use dateRange watcher defined above.
// Also removed invalid date range watcher as date picker handles much of this, but logic can be re-added inside the dateRange watcher if strict invalidation is needed.
// VueDatePicker range mode generally prevents selecting end date before start date effectively.
</script>

<style>
.search-results::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.search-results {
  scrollbar-width: none;
  /* Firefox */
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

/* Ensure VueDatePicker dropdown appears above other page elements */
:deep(.dp__menu) {
  z-index: 9999 !important;
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

/* Searching animation */
.searching-text {
  display: inline-flex;
  align-items: center;
}

.searching-text .dot {
  animation: dotPulse 1.4s infinite ease-in-out;
  opacity: 0;
}

.searching-text .dot-1 {
  animation-delay: 0s;
}

.searching-text .dot-2 {
  animation-delay: 0.2s;
}

.searching-text .dot-3 {
  animation-delay: 0.4s;
}

@keyframes dotPulse {

  0%,
  80%,
  100% {
    opacity: 0;
  }

  40% {
    opacity: 1;
  }
}

/* Running Border Animation */
@property --border-angle {
  syntax: "<angle>";
  inherits: false;
  initial-value: 0deg;
}



.search_bar {
  position: relative;
  z-index: 100;
  /* Elevate above main content so dropdowns appear on top */
  box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
  /* Ensure border radius is consistent for the pseudo element */
  border-radius: 20px;
}

.search_bar::before {
  content: "";
  position: absolute;
  inset: -2px;
  /* Border width */
  z-index: -1;
  /* Behind content */
  background: conic-gradient(from var(--border-angle),
      transparent 0%,
      transparent 80%,
      red 90%,
      /* The shining tail */
      transparent 100%);
  border-radius: inherit;
  /* Matches parent 20px */
  /* Animation: 3s duration * 3 iterations = 9s total. 
     Then fadeOut triggers at 9s and holds opacity: 0. */
  animation: borderRotate 3s linear 3, fadeOut 0.5s ease-in-out 9s forwards;
}

/* Ensure background covers the middle so only border shows */
/* The children divs (column) already have backgrounds (bg-customPrimaryColor, bg-white), so they act as the cover. */

@keyframes borderRotate {
  from {
    --border-angle: 0deg;
  }

  to {
    --border-angle: 360deg;
  }
}

@keyframes fadeOut {
  to {
    opacity: 0;
  }
}

/* Keep existing media query at the end if needed or merge */
@media screen and (max-width:768px) {
  .search_bar {
    box-shadow: none;
    /* User mentioned existing max-[768px]:border-[1px], we can keep or override. 
       If we want the animation to replace the static border, we might need to unset border. 
       But current HTML class has 'max-[768px]:border-[1px]'. 
       The ::before might cover it or sit behind it. */
  }
}
</style>
