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
          class="search-form column px-[2rem] py-[1.5rem] bg-white grid grid-cols-12 gap-6 items-center max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-6 max-[768px]:px-[1.5rem] shadow-sm"
          :class="[
            simple ? 'w-full rounded-[20px]' : 'w-[80%] rounded-tr-[16px] rounded-br-[16px] max-[768px]:w-[100%] max-[768px]:rounded-tr-[0] max-[768px]:rounded-bl-[16px]'
          ]">

          <!-- Locations Section -->
          <div class="search-locations col-span-6 flex gap-4 relative max-[768px]:flex-col max-[768px]:w-full"
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
                  @focus="handleInputFocus"
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
                class="block text-xs font-semibold text-customLightGrayColor uppercase tracking-wider mb-2 pl-1">Drop-off
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
                  :placeholder="dropoffPlaceholder"
                  class="bg-transparent border-none p-0 w-full text-customDarkBlackColor placeholder-gray-400 focus:ring-0 focus:border-none focus:outline-none text-sm font-medium"
                  readonly />
              </div>
              <!-- One-way badge -->
              <div v-if="isOneWaySearch" class="mt-1 flex items-center gap-1">
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
                  One-way rental
                </span>
              </div>
            </div>
          </div>

          <!-- Date Picker Section -->
          <div class="search-dates col-span-4 relative rental-dates-container max-[768px]:w-full">
            <div class="flex flex-col w-full group">
              <label
                class="block text-xs font-semibold text-customLightGrayColor uppercase tracking-wider mb-2 pl-1">Rental
                dates</label>

              <!-- Desktop Version -->
              <VueDatePicker v-if="!isMobile" v-model="dateRange" range :multi-calendars="2" :enable-time-picker="false"
                :min-date="new Date()" :format="formatRangeDate" :close-on-click-outside="true" :teleport="true" dark
                :ui="datePickerUi" @internal-model-change="handleDateUpdate" placeholder="Select dates"
                class="w-full luxury-date-picker" ref="datepicker">
                <template #trigger>
                  <div
                    class="luxury-date-trigger flex min-h-[54px] items-center justify-between gap-3 rounded-xl border border-[#153b4f]/15 bg-[#f8fafc] px-3 py-2.5 shadow-sm cursor-pointer transition-all duration-200 group-hover:border-[#22d3ee]/60 hover:bg-white hover:shadow-md">
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] text-white shadow-[0_8px_20px_rgba(21,59,79,0.22)]">
                      <CalendarDays class="h-[18px] w-[18px]" />
                    </div>

                    <div v-if="dateRange && dateRange[0] && dateRange[1]"
                      class="date-chip-list flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                      <span
                        class="date-chip inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-[#153b4f]/10 bg-white px-2.5 py-1.5 text-xs font-semibold text-[#153b4f] shadow-sm">
                        <span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Pick-up</span>
                        <span class="whitespace-nowrap">{{ formatDateChip(dateRange[0]) }}</span>
                      </span>
                      <span
                        class="date-chip inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-[#153b4f]/10 bg-white px-2.5 py-1.5 text-xs font-semibold text-[#153b4f] shadow-sm">
                        <span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Return</span>
                        <span class="whitespace-nowrap">{{ formatDateChip(dateRange[1]) }}</span>
                      </span>
                      <span
                        class="duration-chip inline-flex items-center rounded-lg bg-[#153b4f]/10 px-2.5 py-1.5 text-xs font-bold text-[#153b4f]">
                        {{ rentalDurationLabel }}
                      </span>
                    </div>
                    <span v-else class="min-w-0 flex-1 text-sm font-semibold text-slate-500">
                      Select rental dates
                    </span>

                    <ChevronDown class="h-4 w-4 shrink-0 text-[#153b4f]/60 transition-transform group-hover:text-[#153b4f]" />
                  </div>
                </template>

                <template #action-row="{ selectDate, closePicker }">
                  <div class="luxury-date-actions flex w-full flex-col overflow-hidden rounded-[18px] text-white">
                    <!-- Time Selection Row -->
                    <div class="grid grid-cols-2 gap-4 p-5 border-b border-white/10">
                      <!-- Pick-up time -->
                      <div class="flex flex-col">
                        <label
                          class="mb-2 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-[#67e8f9]">
                          <Clock3 class="h-3.5 w-3.5" /> Pick-up
                          time</label>
                        <div class="relative group">
                          <select v-model="selectedStartTime"
                            class="luxury-time-select w-full appearance-none rounded-xl border border-white/10 bg-[#0f2936] px-4 py-3 text-sm font-semibold text-white outline-none transition-all hover:border-[#22d3ee]/50 focus:border-[#22d3ee] focus:ring-2 focus:ring-[#22d3ee]/25">
                            <option v-for="time in timeOptions" :key="`start-${time}`" :value="time"
                              class="bg-[#0F2936] text-white">{{ time }}</option>
                          </select>
                          <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/50 transition-colors group-hover:text-[#67e8f9]">
                            <ChevronDown class="h-4 w-4" />
                          </div>
                        </div>
                      </div>
                      <!-- Drop-off time -->
                      <div class="flex flex-col">
                        <label
                          class="mb-2 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-[#67e8f9]">
                          <Clock3 class="h-3.5 w-3.5" /> Return
                          time</label>
                        <div class="relative group">
                          <select v-model="selectedEndTime"
                            class="luxury-time-select w-full appearance-none rounded-xl border border-white/10 bg-[#0f2936] px-4 py-3 text-sm font-semibold text-white outline-none transition-all hover:border-[#22d3ee]/50 focus:border-[#22d3ee] focus:ring-2 focus:ring-[#22d3ee]/25">
                            <option v-for="time in timeOptions" :key="`end-${time}`" :value="time"
                              class="bg-[#0F2936] text-white">{{ time }}</option>
                          </select>
                          <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/50 transition-colors group-hover:text-[#67e8f9]">
                            <ChevronDown class="h-4 w-4" />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Total Duration Row -->
                    <div class="flex items-center justify-between gap-3 px-5 py-4">
                      <div>
                        <span class="block text-[11px] font-bold uppercase tracking-[0.16em] text-[#67e8f9]">Duration</span>
                        <span class="text-lg font-extrabold tracking-wide text-white">{{ rentalDurationLabel }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <button type="button" @click="closePicker"
                          class="rounded-full border border-white/15 px-4 py-2 text-sm font-semibold text-white/80 transition hover:border-white/30 hover:text-white">
                          Close
                        </button>
                        <button type="button" @click="applyDateSelection(selectDate)"
                          class="rounded-full bg-gradient-to-r from-[#153b4f] to-[#2ea7ad] px-5 py-2 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(34,211,238,0.18)] transition hover:shadow-[0_12px_30px_rgba(34,211,238,0.26)]">
                          Apply dates
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </VueDatePicker>

              <!-- Mobile Version -->
              <div v-else>
                <!-- Trigger -->
                <div @click="openMobileDatePicker"
                  class="luxury-date-trigger flex min-h-[54px] items-center justify-between gap-3 rounded-xl border border-[#153b4f]/15 bg-[#f8fafc] px-3 py-2.5 shadow-sm cursor-pointer transition-all duration-200 group-hover:border-[#22d3ee]/60 hover:bg-white hover:shadow-md">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] text-white shadow-[0_8px_20px_rgba(21,59,79,0.22)]">
                    <CalendarDays class="h-[18px] w-[18px]" />
                  </div>
                  <div v-if="dateRange && dateRange[0] && dateRange[1]"
                    class="date-chip-list flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                    <span
                      class="date-chip inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-[#153b4f]/10 bg-white px-2.5 py-1.5 text-xs font-semibold text-[#153b4f] shadow-sm">
                      <span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Pick-up</span>
                      <span class="whitespace-nowrap">{{ formatDateChip(dateRange[0]) }}</span>
                    </span>
                    <span
                      class="date-chip inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-[#153b4f]/10 bg-white px-2.5 py-1.5 text-xs font-semibold text-[#153b4f] shadow-sm">
                      <span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Return</span>
                      <span class="whitespace-nowrap">{{ formatDateChip(dateRange[1]) }}</span>
                    </span>
                    <span
                      class="duration-chip inline-flex items-center rounded-lg bg-[#153b4f]/10 px-2.5 py-1.5 text-xs font-bold text-[#153b4f]">
                      {{ rentalDurationLabel }}
                    </span>
                  </div>
                  <span v-else class="min-w-0 flex-1 text-sm font-semibold text-slate-500">
                    Select rental dates
                  </span>
                  <ChevronDown class="h-4 w-4 shrink-0 text-[#153b4f]/60" />
                </div>

                <!-- Full Screen Modal -->
                <Teleport to="body">
                  <div v-if="showMobileDatePicker"
                    class="fixed inset-0 z-[99999] flex flex-col bg-[linear-gradient(135deg,#0a1d28_0%,#153b4f_52%,#071720_100%)] text-white">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 p-4">
                      <div>
                        <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-[#67e8f9]">Pick-up and return</p>
                        <h3 class="m-0 text-lg font-extrabold leading-none text-white">Select rental dates</h3>
                      </div>
                      <button @click="closeMobileDatePicker"
                        class="-mr-1 rounded-full border border-white/10 bg-white/10 p-2 text-white/80 transition hover:bg-white/15 hover:text-white">
                        <X class="h-5 w-5" />
                      </button>
                    </div>

                    <!-- Scrollable Body -->
                    <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-6">
                      <!-- Calendar -->
                      <VueDatePicker v-model="mobileDateRange" inline auto-apply range dark :enable-time-picker="false"
                        :min-date="new Date()" :multi-calendars="0" :month-change-on-scroll="false" :ui="datePickerUi"
                        class="w-full justify-center flex luxury-date-picker luxury-date-picker-mobile" />

                      <!-- Time Selection -->
                      <div class="luxury-date-actions rounded-[18px] border border-white/10 p-4">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                          <!-- Pick-up time -->
                          <div class="flex flex-col">
                            <label
                              class="mb-2 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-[#67e8f9]">
                              <Clock3 class="h-3.5 w-3.5" /> Pick-up
                              time</label>
                            <div class="relative">
                              <select v-model="selectedStartTime"
                                class="luxury-time-select w-full appearance-none rounded-xl border border-white/10 bg-[#0f2936] px-3 py-3 text-sm font-semibold text-white outline-none focus:border-[#22d3ee] focus:ring-2 focus:ring-[#22d3ee]/25">
                                <option v-for="time in timeOptions" :key="`start-${time}`" :value="time"
                                  class="bg-[#0F2936] text-white">{{ time
                                  }}</option>
                              </select>
                              <ChevronDown
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/50" />
                            </div>
                          </div>
                          <!-- Drop-off time -->
                          <div class="flex flex-col">
                            <label
                              class="mb-2 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-[#67e8f9]">
                              <Clock3 class="h-3.5 w-3.5" /> Return
                              time</label>
                            <div class="relative">
                              <select v-model="selectedEndTime"
                                class="luxury-time-select w-full appearance-none rounded-xl border border-white/10 bg-[#0f2936] px-3 py-3 text-sm font-semibold text-white outline-none focus:border-[#22d3ee] focus:ring-2 focus:ring-[#22d3ee]/25">
                                <option v-for="time in timeOptions" :key="`end-${time}`" :value="time"
                                  class="bg-[#0F2936] text-white">{{ time }}
                                </option>
                              </select>
                              <ChevronDown
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/50" />
                            </div>
                          </div>
                        </div>
                        <div
                          class="flex items-center justify-between border-t border-white/10 pt-4 text-white">
                          <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#67e8f9]">Duration</span>
                          <span class="text-lg font-extrabold">{{ mobileDurationLabel }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-white/10 bg-[#071720]/80 p-4 backdrop-blur">
                      <button @click="confirmMobileDateSelection"
                        class="w-full rounded-xl bg-gradient-to-r from-[#153b4f] to-[#2ea7ad] py-4 text-lg font-extrabold text-white shadow-[0_14px_34px_rgba(34,211,238,0.22)] transition-transform active:scale-95">
                        Confirm dates
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
          <div class="search-submit col-span-2 flex justify-end items-center mt-[20px] max-[768px]:w-full">
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

        <!-- Search results dropdown (desktop only) -->
        <div v-if="showSearchBox && !isMobile && (searchResults.length > 0 || popularPlaces.length > 0 || searchPerformed)"
          class="search-results absolute z-[9999] top-[105%] w-[50%] rounded-[12px] border border-gray-100 left-0 p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto shadow-xl">

          <!-- Existing search results -->
          <div v-if="searchResults.length > 0">
            <div v-for="result in searchResults" :key="result.unified_location_id" @click="selectLocation(result)"
              class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
              <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
                <img :src="flighIcon" v-if="isAirportLocation(result)" alt="" aria-hidden="true" class="w-1/2 h-1/2" />
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
                <div class="font-medium">{{ formatLocationPrimaryLabel(result) }}</div>
                <div class="text-sm text-gray-500 group-hover:text-white">
                  {{ formatLocationSecondaryLabel(result) }}
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

        <!-- Dropoff search results dropdown (desktop only) -->
        <div v-if="showDropoffSearchBox && !isMobile && dropoffSearchResults.length > 0"
          class="search-results absolute z-[9999] top-[105%] w-[50%] rounded-[12px] border-[1px] border-white left-0 p-5 bg-white text-customDarkBlackColor max-h-[400px] overflow-y-auto">
          <div v-for="result in dropoffSearchResults" :key="result.unified_location_id"
            @click="selectDropoffLocation(result)"
            class="p-2 hover:bg-customPrimaryColor hover:text-white cursor-pointer flex gap-3 group rounded-[12px] hover:scale-[1.02] transition-transform">
            <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-100 text-gray-300 rounded flex justify-center items-center">
              <img :src="flighIcon" v-if="isAirportLocation(result)" alt="" aria-hidden="true" class="w-1/2 h-1/2" />
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
              <div class="font-medium">{{ formatLocationPrimaryLabel(result) }}</div>
              <div class="text-sm text-gray-500 group-hover:text-white">
                {{ formatLocationSecondaryLabel(result) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Pickup Location Fullscreen Modal -->
      <Teleport to="body">
        <div v-if="showMobileLocationPicker" class="fixed inset-0 z-[99999] bg-white flex flex-col">
          <!-- Header -->
          <div class="flex justify-between items-center p-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-customPrimaryColor m-0 leading-none">{{ _t('homepage', 'pickup_return_location_label') || 'Pickup Location' }}</h3>
            <button @click="closeMobileLocationPicker" class="p-2 -mr-2 text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Search Input -->
          <div class="p-4 border-b border-gray-100">
            <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 focus-within:border-customPrimaryColor focus-within:ring-1 focus-within:ring-customPrimaryColor/20">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-customPrimaryColor mr-3 flex-shrink-0">
                <path
                  d="M12 21.75C12 21.75 19.5 16.5 19.5 9.75C19.5 7.76088 18.7098 5.85322 17.3033 4.4467C15.8968 3.04018 13.9891 2.25 12 2.25C10.0109 2.25 8.10322 3.04018 6.6967 4.4467C5.29018 5.85322 4.5 7.76088 4.5 9.75C4.5 16.5 12 21.75 12 21.75ZM15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75Z"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <input type="text" v-model="form.where" @input="handleSearchInput" ref="mobileLocationInputRef"
                :placeholder="isSearching ? _t('homepage', 'searching_placeholder') : _t('homepage', 'pickup_location_placeholder')"
                class="bg-transparent border-none p-0 w-full text-customDarkBlackColor placeholder-gray-400 focus:ring-0 focus:outline-none text-sm font-medium" />
              <button v-if="form.where" @click="form.where = ''; searchResults = []; searchPerformed = false;"
                class="ml-2 p-1 text-gray-400 hover:text-gray-600 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Results -->
          <div class="flex-1 overflow-y-auto">
            <!-- Loading -->
            <div v-if="isSearching" class="p-6 text-center text-gray-400 text-sm">
              {{ _t('homepage', 'searching_placeholder') || 'Searching...' }}
            </div>

            <!-- Results list -->
            <div v-else-if="searchResults.length > 0" class="p-3">
              <div v-for="result in searchResults" :key="result.unified_location_id" @click="selectLocation(result)"
                class="p-3 active:bg-customPrimaryColor active:text-white cursor-pointer flex gap-3 rounded-xl transition-colors">
                <div class="h-10 w-10 bg-gray-100 text-gray-300 rounded flex justify-center items-center flex-shrink-0">
                  <img :src="flighIcon" v-if="isAirportLocation(result)" alt="" aria-hidden="true" class="w-1/2 h-1/2" />
                  <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2">
                    <path clip-rule="evenodd"
                      d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path clip-rule="evenodd"
                      d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                </div>
                <div class="flex flex-col justify-center">
                  <div class="font-medium text-sm">{{ formatLocationPrimaryLabel(result) }}</div>
                  <div class="text-xs text-gray-500">{{ formatLocationSecondaryLabel(result) }}</div>
                </div>
              </div>
            </div>

            <!-- No results -->
            <div v-else-if="searchPerformed && !isSearching" class="p-6 text-center text-gray-400 text-sm">
              {{ _t('homepage', 'no_location_found') }}
            </div>

            <!-- Prompt -->
            <div v-else class="p-6 text-center text-gray-400 text-sm">
              {{ _t('homepage', 'start_typing_to_search_all_locations') }}
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Mobile Dropoff Location Fullscreen Modal -->
      <Teleport to="body">
        <div v-if="showMobileDropoffPicker" class="fixed inset-0 z-[99999] bg-white flex flex-col">
          <!-- Header -->
          <div class="flex justify-between items-center p-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-customPrimaryColor m-0 leading-none">Drop-off Location</h3>
            <button @click="showMobileDropoffPicker = false" class="p-2 -mr-2 text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Dropoff Results list -->
          <div class="flex-1 overflow-y-auto p-3">
            <div v-for="result in dropoffSearchResults" :key="result.unified_location_id"
              @click="selectDropoffLocation(result)"
              class="p-3 active:bg-customPrimaryColor active:text-white cursor-pointer flex gap-3 rounded-xl transition-colors">
              <div class="h-10 w-10 bg-gray-100 text-gray-300 rounded flex justify-center items-center flex-shrink-0">
                <img :src="flighIcon" v-if="isAirportLocation(result)" alt="" aria-hidden="true" class="w-1/2 h-1/2" />
                <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-1/2 h-1/2">
                  <path clip-rule="evenodd"
                    d="M7.838 9.79c0 2.497 1.946 4.521 4.346 4.521 2.401 0 4.347-2.024 4.347-4.52 0-2.497-1.946-4.52-4.346-4.52-2.401 0-4.347 2.023-4.347 4.52Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path clip-rule="evenodd"
                    d="M20.879 9.79c0 7.937-6.696 12.387-8.335 13.36a.7.7 0 0 1-.718 0c-1.64-.973-8.334-5.425-8.334-13.36 0-4.992 3.892-9.04 8.693-9.04s8.694 4.048 8.694 9.04Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </div>
              <div class="flex flex-col justify-center">
                <div class="font-medium text-sm">{{ formatLocationPrimaryLabel(result) }}</div>
                <div class="text-xs text-gray-500">{{ formatLocationSecondaryLabel(result) }}</div>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import axios from "axios";
import { router, usePage } from "@inertiajs/vue3";
import { CalendarDays, ChevronDown, Clock3, X } from "lucide-vue-next";
import { resolveSearchCurrency } from "@/utils/searchCurrency";
import {
  buildLocationInputValue,
  formatLocationPrimaryLabel,
  formatLocationSecondaryLabel,
  getLocationSelectionData,
  isAirportLocation,
} from "@/utils/locationSearchDisplay";
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
  dropoff_unified_location_id: null,
  dropoff_where: "",
  dropoff_latitude: null,
  dropoff_longitude: null,
});

const props = defineProps({
  prefill: Object,
  simple: {
    type: Boolean,
    default: false
  }
});

const page = usePage();

const dateRange = ref(null);
const selectedStartTime = ref('09:00');
const selectedEndTime = ref('09:00');

const datePickerUi = {
  menu: 'luxury-date-menu',
  calendar: 'luxury-date-calendar',
  calendarCell: 'luxury-date-cell',
  navBtnNext: 'luxury-date-nav',
  navBtnPrev: 'luxury-date-nav',
};

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

const rentalDurationLabel = computed(() => {
  const days = totalDays.value || 1;
  return `${days} ${days > 1 ? 'days' : 'day'} rental`;
});

const formatRangeDate = (dates) => {
  if (!dates || !dates[0] || !dates[1]) return "";
  const start = dates[0];
  const end = dates[1];

  // Format: DD/MM/YYYY - DD/MM/YYYY
  const fmt = (d) => `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
  return `${fmt(start)} - ${fmt(end)}`;
};

const formatDateChip = (date) => {
  if (!date) return '';

  return new Intl.DateTimeFormat('en', {
    day: '2-digit',
    month: 'short',
  }).format(new Date(date));
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
const selectedPickupLocation = ref(null);
const hasSelectedPickupLocation = ref(false); // Track if user selected from dropdown
const hasSelectedDropoffLocation = ref(false); // Track if user selected dropoff from dropdown

// Computed placeholder for drop-off input
const dropoffPlaceholder = computed(() => {
  if (!form.value.where) {
    return 'Select pickup location first';
  }
  if (dropoffSearchResults.value.length === 0) {
    return 'Same as pickup';
  }
  return 'Select drop-off location (or same as pickup)';
});

const isOneWaySearch = computed(() => {
  const pickupId = String(form.value.unified_location_id || '');
  const dropoffId = String(form.value.dropoff_unified_location_id || '');

  return pickupId !== '' && dropoffId !== '' && pickupId !== dropoffId;
});

// Clear error messages
const clearError = () => {
  dateError.value = false;
  locationError.value = null;
  showSearchBox.value = false;
  showDropoffSearchBox.value = false;
};

const handleInputClick = (event) => {
  if (isMobile.value) {
    event?.target?.blur?.();
    openMobileLocationPicker();
    return;
  }
  showSearchBox.value = !showSearchBox.value;
  showDropoffSearchBox.value = false; // Close other dropdown
};

const handleInputFocus = (event) => {
  if (!isMobile.value) {
    return;
  }
  event?.target?.blur?.();
  if (!showMobileLocationPicker.value) {
    openMobileLocationPicker();
  }
};

const handleDropoffInputClick = () => {
  if (!dropoffSearchResults.value.length) {
    return;
  }
  if (isMobile.value) {
    showMobileDropoffPicker.value = true;
    return;
  }
  showDropoffSearchBox.value = !showDropoffSearchBox.value;
  showSearchBox.value = false; // Close other dropdown
};

const fetchPopularPlaces = async () => {
  popularPlaces.value = [];
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
      const expectedValue = buildLocationInputValue(selectedPickupLocation.value);
      if (newVal !== expectedValue) {
        // User modified the text manually
        hasSelectedPickupLocation.value = false;
      }
    }
  }
});

const showMobileDatePicker = ref(false);
const mobileDateRange = ref(null);

const showMobileLocationPicker = ref(false);
const showMobileDropoffPicker = ref(false);
const mobileLocationInputRef = ref(null);

const openMobileLocationPicker = () => {
  showMobileLocationPicker.value = true;
  nextTick(() => {
    mobileLocationInputRef.value?.focus();
  });
};

const closeMobileLocationPicker = () => {
  showMobileLocationPicker.value = false;
};

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

const mobileDurationLabel = computed(() => {
  const days = mobileTotalDays.value || totalDays.value || 1;
  return `${days} ${days > 1 ? 'days' : 'day'} rental`;
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
      const response = await axios.get('/api/unified-locations', {
        params: { search_term: form.value.where, limit: 20 }
      });
      searchResults.value = response.data;
      searchPerformed.value = true;
    } catch (error) {
      console.error("Error searching locations:", error);
      searchResults.value = [];
      searchPerformed.value = true;
    } finally {
      isSearching.value = false;
    }
  }, 300);
};

const selectLocation = (result) => {
  const selectionData = getLocationSelectionData(result);
  const normalizedResult = {
    ...result,
    name: selectionData.displayName,
    city: selectionData.city,
    country: selectionData.country,
  };

  selectedPickupLocation.value = normalizedResult;
  hasSelectedPickupLocation.value = true; // User selected from dropdown
  locationError.value = null; // Clear any error
  form.value.where = buildLocationInputValue(result);
  form.value.latitude = result.latitude;
  form.value.longitude = result.longitude;
  form.value.city = selectionData.city;
  form.value.country = selectionData.country;

  // Always set the unified_location_id for multi-provider matching
  form.value.unified_location_id = result.unified_location_id || null;

  showSearchBox.value = false;
  showMobileLocationPicker.value = false;
  searchPerformed.value = false;
  searchResults.value = [];

  if ((result.provider_count || 0) > 0) {
    form.value.provider = 'mixed';
    form.value.provider_pickup_id = null;

    // Set default dropoff to be same as pickup while keeping mixed provider
    form.value.dropoff_where = form.value.where;
    form.value.dropoff_location_id = null;
    form.value.dropoff_unified_location_id = result.unified_location_id || null;
    form.value.dropoff_latitude = result.latitude || null;
    form.value.dropoff_longitude = result.longitude || null;

    fetchDropoffLocationsByUnifiedId(result.unified_location_id);
  } else {
    isProviderLocation.value = false;
    form.value.provider = null;
    form.value.provider_pickup_id = null;
    form.value.unified_location_id = null;
    dropoffSearchResults.value = [];
  }
};

const fetchDropoffLocationsByUnifiedId = async (unifiedLocationId) => {
  if (!unifiedLocationId) return;

  try {
    const response = await axios.get(`/api/mobile/locations/${unifiedLocationId}/dropoffs`);
    let dropoffs = response.data.results || [];

    // Always surface the pickup location as an option so the user can revert
    // a one-way search back to round-trip without clearing the field. If the
    // in-memory ref is missing (e.g. restored from URL prefill), fetch it.
    let pickup = selectedPickupLocation.value;
    if (!pickup && form.value.unified_location_id) {
      try {
        const res = await axios.get('/api/unified-locations', {
          params: { unified_location_id: form.value.unified_location_id },
        });
        pickup = Array.isArray(res.data) ? res.data[0] : res.data;
        if (pickup) selectedPickupLocation.value = pickup;
      } catch (e) {
        console.warn('Failed to load pickup location for dropoff list:', e);
      }
    }

    if (pickup) {
      const pickupExists = dropoffs.some(loc => loc.unified_location_id === pickup.unified_location_id);
      if (!pickupExists) {
        dropoffs.unshift(pickup);
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
  form.value.dropoff_where = buildLocationInputValue(result);
  form.value.dropoff_unified_location_id = result.unified_location_id || null;
  form.value.dropoff_latitude = result.latitude || null;
  form.value.dropoff_longitude = result.longitude || null;
  form.value.dropoff_location_id = null;

  showDropoffSearchBox.value = false;
  showMobileDropoffPicker.value = false;
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
    form.value.currency = resolveSearchCurrency({
      currentCurrency: form.value.currency,
      prefillCurrency: props.prefill?.currency,
      selectedCurrency: page.props.currency,
    });

    const pickupDate = new Date(form.value.date_from);
    const returnDate = new Date(form.value.date_to);
    const diffTime = Math.abs(returnDate - pickupDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    let packageType = '';
    form.value.package_type = packageType;
    if (form.value.provider === 'mixed') {
      form.value.provider_pickup_id = null;
      form.value.dropoff_location_id = null;
    }

    // Create URL parameters object
    const urlParams = new URLSearchParams(form.value).toString();

    // Store the search URL in session storage
    sessionStorage.setItem('searchurl', `/s?${urlParams}`);

    // Remove radius adjustment since we’re not using radius-based filtering
    // form.value.radius = 30000; // Removed

    await new Promise(resolve => {
      router.get(route('search', { locale: page.props.locale }), form.value, {
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

  form.value.currency = resolveSearchCurrency({
    currentCurrency: form.value.currency,
    prefillCurrency: props.prefill?.currency,
    selectedCurrency: page.props.currency,
  });

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
    form.value.unified_location_id = props.prefill.unified_location_id || null;

    if (props.prefill.start_time) {
      selectedStartTime.value = props.prefill.start_time;
      form.value.start_time = props.prefill.start_time;
    }
    if (props.prefill.end_time) {
      selectedEndTime.value = props.prefill.end_time;
      form.value.end_time = props.prefill.end_time;
    }

    form.value.dropoff_location_id = props.prefill.dropoff_location_id || null;
    form.value.dropoff_unified_location_id = props.prefill.dropoff_unified_location_id || null;
    form.value.dropoff_latitude = props.prefill.dropoff_latitude || null;
    form.value.dropoff_longitude = props.prefill.dropoff_longitude || null;
    form.value.dropoff_where = props.prefill.dropoff_where || "";

    if (props.prefill.unified_location_id) {
      isProviderLocation.value = true;
      await fetchDropoffLocationsByUnifiedId(props.prefill.unified_location_id);

      if (props.prefill.dropoff_where) {
        form.value.dropoff_where = props.prefill.dropoff_where;
      } else if (props.prefill.dropoff_unified_location_id) {
        const dropoffLocation = dropoffSearchResults.value.find(
          loc => String(loc.unified_location_id) === String(props.prefill.dropoff_unified_location_id)
        );
        if (dropoffLocation) {
          form.value.dropoff_where = buildLocationInputValue(dropoffLocation);
        }
      }
    }

  }

  if (form.value.where && !form.value.dropoff_where) {
    form.value.dropoff_where = form.value.where;
  }

  // Ensure prefilled location is considered selected
  if (form.value.where && form.value.unified_location_id) {
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

.luxury-date-picker {
  --dp-font-family: var(--jakarta-font-family), "Plus Jakarta Sans", sans-serif;
}

.luxury-date-trigger {
  font-family: var(--jakarta-font-family), "Plus Jakarta Sans", sans-serif;
}

.luxury-date-menu {
  --dp-background-color: #0a1d28;
  --dp-text-color: #e6f7fb;
  --dp-hover-color: rgba(34, 211, 238, 0.12);
  --dp-hover-text-color: #ffffff;
  --dp-hover-icon-color: #67e8f9;
  --dp-primary-color: #22d3ee;
  --dp-primary-text-color: #06222e;
  --dp-secondary-color: rgba(226, 232, 240, 0.48);
  --dp-border-color: rgba(255, 255, 255, 0.1);
  --dp-menu-border-color: rgba(34, 211, 238, 0.22);
  --dp-border-color-hover: rgba(34, 211, 238, 0.42);
  --dp-border-color-focus: #22d3ee;
  --dp-disabled-color: rgba(255, 255, 255, 0.05);
  --dp-disabled-color-text: rgba(226, 232, 240, 0.32);
  --dp-icon-color: rgba(226, 232, 240, 0.72);
  --dp-highlight-color: rgba(34, 211, 238, 0.18);
  --dp-range-between-dates-background-color: rgba(34, 211, 238, 0.15);
  --dp-range-between-dates-text-color: #dffbff;
  --dp-range-between-border-color: rgba(34, 211, 238, 0.08);
  --dp-border-radius: 22px;
  --dp-cell-border-radius: 999px;
  --dp-cell-size: 40px;
  --dp-menu-padding: 14px;
  --dp-row-margin: 7px 0;
  --dp-multi-calendars-spacing: 18px;
  --dp-month-year-row-height: 44px;
  --dp-month-year-row-button-size: 34px;
  --dp-button-icon-height: 18px;
  --dp-font-size: 0.92rem;
  --dp-action-row-padding: 0;
  overflow: hidden;
  border-radius: 24px !important;
  border: 1px solid rgba(34, 211, 238, 0.22) !important;
  background:
    linear-gradient(135deg, rgba(10, 29, 40, 0.98) 0%, rgba(21, 59, 79, 0.98) 52%, rgba(7, 23, 32, 0.98) 100%) !important;
  box-shadow: 0 28px 70px rgba(10, 29, 40, 0.34), 0 12px 28px rgba(21, 59, 79, 0.18) !important;
}

.luxury-date-menu .dp__menu_inner {
  gap: 8px;
  padding: 16px !important;
}

.luxury-date-menu .dp__month_year_row {
  margin-bottom: 8px;
}

.luxury-date-menu .dp__month_year_select,
.luxury-date-menu .dp--year-select {
  border-radius: 999px;
  color: #ffffff;
  font-family: var(--jakarta-font-family), "Plus Jakarta Sans", sans-serif;
  font-weight: 800;
  letter-spacing: 0.01em;
}

.luxury-date-menu .dp__inner_nav {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.07);
  color: #e6f7fb;
  transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}

.luxury-date-menu .dp__inner_nav:hover {
  transform: translateY(-1px);
  border-color: rgba(34, 211, 238, 0.38);
  background: rgba(34, 211, 238, 0.16);
  color: #67e8f9;
}

.luxury-date-menu .dp__calendar_header {
  color: rgba(226, 232, 240, 0.72);
  font-family: var(--jakarta-font-family), "Plus Jakarta Sans", sans-serif;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.luxury-date-menu .dp__calendar_header_separator {
  background: rgba(255, 255, 255, 0.08);
}

.luxury-date-menu .dp__calendar_item {
  font-family: var(--jakarta-font-family), "Plus Jakarta Sans", sans-serif;
  font-weight: 700;
}

.luxury-date-menu .dp__cell_inner {
  transition: background 0.16s ease, color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
}

.luxury-date-menu .dp__cell_inner:hover {
  border-color: rgba(34, 211, 238, 0.32);
  box-shadow: inset 0 0 0 1px rgba(34, 211, 238, 0.14);
}

.luxury-date-menu .dp__today {
  border-color: rgba(34, 211, 238, 0.72);
  color: #ffffff;
}

.luxury-date-menu .dp__range_start,
.luxury-date-menu .dp__range_end,
.luxury-date-menu .dp__active_date {
  background: linear-gradient(135deg, #22d3ee 0%, #2ea7ad 100%) !important;
  color: #06222e !important;
  font-weight: 900;
  box-shadow: 0 8px 22px rgba(34, 211, 238, 0.28);
}

.luxury-date-menu .dp__range_between {
  border-color: rgba(34, 211, 238, 0.08) !important;
  background: linear-gradient(90deg, rgba(34, 211, 238, 0.1), rgba(46, 167, 173, 0.18)) !important;
  color: #e6f7fb !important;
}

.luxury-date-menu .dp__cell_offset,
.luxury-date-menu .dp__cell_disabled {
  color: rgba(226, 232, 240, 0.28) !important;
}

.luxury-date-menu .dp__action_row {
  margin-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.luxury-date-actions {
  background:
    linear-gradient(135deg, rgba(5, 20, 29, 0.98) 0%, rgba(15, 41, 54, 0.98) 48%, rgba(21, 59, 79, 0.96) 100%);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.07);
}

.luxury-time-select {
  font-family: var(--jakarta-font-family), "Plus Jakarta Sans", sans-serif;
}

.luxury-date-picker-mobile .luxury-date-menu,
.luxury-date-picker-mobile .dp__menu {
  width: 100%;
  max-width: 100%;
  box-shadow: none !important;
}

@media screen and (min-width: 769px) and (max-width: 1399px) {
  .search_bar .search-form {
    gap: 1rem;
    padding: 1.25rem 1.35rem;
  }

  .search_bar .search-submit button {
    min-height: 54px;
    padding-inline: 0.75rem;
  }

  .search_bar .luxury-date-trigger {
    min-height: 54px;
    gap: 0.45rem;
    padding: 0.55rem 0.6rem;
  }

  .search_bar .luxury-date-trigger > div:first-child {
    width: 2rem;
    height: 2rem;
    border-radius: 0.625rem;
  }

  .search_bar .luxury-date-trigger > svg {
    width: 0.875rem;
    height: 0.875rem;
  }

  .search_bar .date-chip-list {
    flex-wrap: nowrap;
    overflow: hidden;
    gap: 0.35rem;
  }

  .search_bar .date-chip {
    min-width: 0;
    flex: 1 1 0;
    justify-content: center;
    gap: 0;
    padding: 0.45rem 0.5rem;
    font-size: 0.72rem;
  }

  .search_bar .date-chip > span:first-child {
    display: none;
  }

  .search_bar .duration-chip {
    flex: 0 0 auto;
    padding: 0.45rem 0.5rem;
    font-size: 0.72rem;
    white-space: nowrap;
  }
}

@media screen and (max-width: 768px) {
  .luxury-date-menu {
    --dp-cell-size: 42px;
    --dp-menu-padding: 10px;
    border-radius: 20px !important;
  }

  .luxury-date-menu .dp__menu_inner {
    padding: 14px !important;
  }
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
