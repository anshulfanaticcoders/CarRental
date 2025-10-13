<template>
  <div class="relative">
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Target Countries <span class="text-gray-500 font-normal">(Select countries where this blog should be visible)</span>
    </label>

    <!-- Dropdown Trigger -->
    <div class="relative">
      <button
        type="button"
        @click="toggleDropdown"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-left focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-all duration-200 hover:border-gray-400"
        :class="{ 'ring-2 ring-customPrimaryColor border-customPrimaryColor': isOpen }"
      >
        <div class="flex items-center justify-between">
          <div class="flex flex-wrap items-center gap-2 flex-1">
            <div v-if="selectedCountries.length === 0" class="text-gray-500">
              Select countries...
            </div>
            <div
              v-for="country in selectedCountries"
              :key="country.code"
              class="inline-flex items-center gap-1 px-2 py-1 bg-customPrimaryColor text-white text-sm rounded-md"
            >
              <span>{{ country.flag }} {{ country.name }}</span>
              <button
                type="button"
                @click.stop="removeCountry(country.code)"
                class="ml-1 hover:bg-customPrimaryColor/80 rounded-full p-0.5 transition-colors"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span v-if="selectedCountries.length > 0" class="text-sm text-gray-500">
              {{ selectedCountries.length }} selected
            </span>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform duration-200"
              :class="{ 'rotate-180': isOpen }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </div>
        </div>
      </button>
    </div>

    <!-- Dropdown Menu -->
    <div
      v-if="isOpen"
      class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"
      @click.stop
    >
      <!-- Search Box -->
      <div class="p-3 border-b border-gray-200">
        <div class="relative">
          <input
            ref="searchInput"
            v-model="searchQuery"
            type="text"
            placeholder="Search countries..."
            class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor text-sm"
          />
          <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="p-3 border-b border-gray-200 flex gap-2">
        <button
          type="button"
          @click="selectAllVisible"
          class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors"
        >
          Select All Visible
        </button>
        <button
          type="button"
          @click="clearSelection"
          class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors"
        >
          Clear All
        </button>
        <button
          type="button"
          @click="selectPopular"
          class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors"
        >
          Select Popular
        </button>
      </div>

      <!-- Country List -->
      <div class="max-h-48 overflow-y-auto">
        <div
          v-for="country in filteredCountries"
          :key="country.code"
          @click="toggleCountry(country)"
          class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer transition-colors"
          :class="{ 'bg-customPrimaryColor/10': isCountrySelected(country.code) }"
        >
          <input
            type="checkbox"
            :checked="isCountrySelected(country.code)"
            class="w-4 h-4 text-customPrimaryColor border-gray-300 rounded focus:ring-customPrimaryColor"
            @change="toggleCountry(country)"
            @click.stop
          />
          <span class="text-lg">{{ country.flag }}</span>
          <span class="flex-1 text-sm font-medium">{{ country.name }}</span>
          <span class="text-xs text-gray-500">{{ country.code.toUpperCase() }}</span>
        </div>
      </div>

      <!-- No Results -->
      <div v-if="filteredCountries.length === 0" class="p-4 text-center text-gray-500 text-sm">
        No countries found
      </div>
    </div>

    <!-- Help Text -->
    <p class="mt-2 text-sm text-gray-600">
      Select the countries where this blog post should be visible. The blog will only be shown to users from these countries.
    </p>

    <!-- Selected Countries Display -->
    <div v-if="selectedCountries.length > 0" class="mt-3 p-3 bg-gray-50 rounded-lg">
      <div class="text-sm font-medium text-gray-700 mb-2">Selected Countries ({{ selectedCountries.length }}):</div>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="country in selectedCountries"
          :key="country.code"
          class="inline-flex items-center gap-1 px-2 py-1 bg-white border border-gray-300 rounded-md text-sm"
        >
          {{ country.flag }} {{ country.name }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue'])

// All countries with flags (comprehensive list matching SetCurrency middleware)
const allCountries = [
  // North America
  { code: 'us', name: 'United States', flag: '🇺🇸' },
  { code: 'ca', name: 'Canada', flag: '🇨🇦' },
  { code: 'mx', name: 'Mexico', flag: '🇲🇽' },
  { code: 'ni', name: 'Nicaragua', flag: '🇳🇮' },
  { code: 'cr', name: 'Costa Rica', flag: '🇨🇷' },
  { code: 'pa', name: 'Panama', flag: '🇵🇦' },
  { code: 'gt', name: 'Guatemala', flag: '🇬🇹' },
  { code: 'hn', name: 'Honduras', flag: '🇭🇳' },
  { code: 'sv', name: 'El Salvador', flag: '🇸🇻' },
  { code: 'bz', name: 'Belize', flag: '🇧🇿' },
  { code: 'jm', name: 'Jamaica', flag: '🇯🇲' },
  { code: 'bb', name: 'Barbados', flag: '🇧🇧' },
  { code: 'tt', name: 'Trinidad and Tobago', flag: '🇹🇹' },
  { code: 'do', name: 'Dominican Republic', flag: '🇩🇴' },
  { code: 'ht', name: 'Haiti', flag: '🇭🇹' },
  { code: 'ag', name: 'Antigua and Barbuda', flag: '🇦🇬' },
  { code: 'dm', name: 'Dominica', flag: '🇩🇲' },
  { code: 'gd', name: 'Grenada', flag: '🇬🇩' },
  { code: 'kn', name: 'Saint Kitts and Nevis', flag: '🇰🇳' },
  { code: 'lc', name: 'Saint Lucia', flag: '🇱🇨' },
  { code: 'vc', name: 'Saint Vincent and the Grenadines', flag: '🇻🇨' },
  { code: 'cu', name: 'Cuba', flag: '🇨🇺' },
  { code: 'pr', name: 'Puerto Rico', flag: '🇵🇷' },
  { code: 'gp', name: 'Guadeloupe', flag: '🇬🇵' },
  { code: 'mq', name: 'Martinique', flag: '🇲🇶' },
  { code: 'bs', name: 'Bahamas', flag: '🇧🇸' },
  { code: 'ky', name: 'Cayman Islands', flag: '🇰🇾' },
  { code: 'bm', name: 'Bermuda', flag: '🇧🇲' },
  { code: 'ai', name: 'Anguilla', flag: '🇦🇮' },
  { code: 'vg', name: 'British Virgin Islands', flag: '🇻🇬' },
  { code: 'ms', name: 'Montserrat', flag: '🇲🇸' },
  { code: 'tc', name: 'Turks and Caicos Islands', flag: '🇹🇨' },
  { code: 'aw', name: 'Aruba', flag: '🇦🇼' },
  { code: 'cw', name: 'Curaçao', flag: '🇨🇼' },
  { code: 'sx', name: 'Sint Maarten', flag: '🇸🇽' },

  // Europe - Eurozone (All 20 countries)
  { code: 'de', name: 'Germany', flag: '🇩🇪' },
  { code: 'fr', name: 'France', flag: '🇫🇷' },
  { code: 'it', name: 'Italy', flag: '🇮🇹' },
  { code: 'es', name: 'Spain', flag: '🇪🇸' },
  { code: 'nl', name: 'Netherlands', flag: '🇳🇱' },
  { code: 'be', name: 'Belgium', flag: '🇧🇪' },
  { code: 'at', name: 'Austria', flag: '🇦🇹' },
  { code: 'pt', name: 'Portugal', flag: '🇵🇹' },
  { code: 'fi', name: 'Finland', flag: '🇫🇮' },
  { code: 'ie', name: 'Ireland', flag: '🇮🇪' },
  { code: 'gr', name: 'Greece', flag: '🇬🇷' },
  { code: 'cy', name: 'Cyprus', flag: '🇨🇾' },
  { code: 'mt', name: 'Malta', flag: '🇲🇹' },
  { code: 'sk', name: 'Slovakia', flag: '🇸🇰' },
  { code: 'si', name: 'Slovenia', flag: '🇸🇮' },
  { code: 'ee', name: 'Estonia', flag: '🇪🇪' },
  { code: 'lv', name: 'Latvia', flag: '🇱🇻' },
  { code: 'lt', name: 'Lithuania', flag: '🇱🇹' },
  { code: 'lu', name: 'Luxembourg', flag: '🇱🇺' },
  { code: 'hr', name: 'Croatia', flag: '🇭🇷' },

  // Europe - Non-Eurozone
  { code: 'gb', name: 'United Kingdom', flag: '🇬🇧' },
  { code: 'ch', name: 'Switzerland', flag: '🇨🇭' },
  { code: 'se', name: 'Sweden', flag: '🇸🇪' },
  { code: 'no', name: 'Norway', flag: '🇳🇴' },
  { code: 'dk', name: 'Denmark', flag: '🇩🇰' },
  { code: 'is', name: 'Iceland', flag: '🇮🇸' },
  { code: 'pl', name: 'Poland', flag: '🇵🇱' },
  { code: 'cz', name: 'Czech Republic', flag: '🇨🇿' },
  { code: 'hu', name: 'Hungary', flag: '🇭🇺' },
  { code: 'ro', name: 'Romania', flag: '🇷🇴' },
  { code: 'bg', name: 'Bulgaria', flag: '🇧🇬' },

  // Asia Pacific - Major Economies
  { code: 'cn', name: 'China', flag: '🇨🇳' },
  { code: 'jp', name: 'Japan', flag: '🇯🇵' },
  { code: 'in', name: 'India', flag: '🇮🇳' },
  { code: 'kr', name: 'South Korea', flag: '🇰🇷' },
  { code: 'id', name: 'Indonesia', flag: '🇮🇩' },
  { code: 'th', name: 'Thailand', flag: '🇹🇭' },
  { code: 'my', name: 'Malaysia', flag: '🇲🇾' },
  { code: 'ph', name: 'Philippines', flag: '🇵🇭' },
  { code: 'vn', name: 'Vietnam', flag: '🇻🇳' },
  { code: 'sg', name: 'Singapore', flag: '🇸🇬' },
  { code: 'hk', name: 'Hong Kong', flag: '🇭🇰' },
  { code: 'tw', name: 'Taiwan', flag: '🇹🇼' },
  { code: 'bd', name: 'Bangladesh', flag: '🇧🇩' },
  { code: 'pk', name: 'Pakistan', flag: '🇵🇰' },
  { code: 'lk', name: 'Sri Lanka', flag: '🇱🇰' },
  { code: 'mm', name: 'Myanmar', flag: '🇲🇲' },
  { code: 'kh', name: 'Cambodia', flag: '🇰🇭' },
  { code: 'la', name: 'Laos', flag: '🇱🇦' },
  { code: 'np', name: 'Nepal', flag: '🇳🇵' },
  { code: 'bt', name: 'Bhutan', flag: '🇧🇹' },

  // Oceania
  { code: 'au', name: 'Australia', flag: '🇦🇺' },
  { code: 'nz', name: 'New Zealand', flag: '🇳🇿' },
  { code: 'fj', name: 'Fiji', flag: '🇫🇯' },
  { code: 'pg', name: 'Papua New Guinea', flag: '🇵🇬' },
  { code: 'sb', name: 'Solomon Islands', flag: '🇸🇧' },
  { code: 'vu', name: 'Vanuatu', flag: '🇻🇺' },
  { code: 'ws', name: 'Samoa', flag: '🇼🇸' },
  { code: 'to', name: 'Tonga', flag: '🇹🇴' },
  { code: 'ki', name: 'Kiribati', flag: '🇰🇮' },
  { code: 'tv', name: 'Tuvalu', flag: '🇹🇻' },
  { code: 'nr', name: 'Nauru', flag: '🇳🇷' },
  { code: 'pw', name: 'Palau', flag: '🇵🇼' },
  { code: 'fm', name: 'Micronesia', flag: '🇫🇲' },
  { code: 'mh', name: 'Marshall Islands', flag: '🇲🇭' },

  // Middle East & North Africa
  { code: 'sa', name: 'Saudi Arabia', flag: '🇸🇦' },
  { code: 'ae', name: 'United Arab Emirates', flag: '🇦🇪' },
  { code: 'il', name: 'Israel', flag: '🇮🇱' },
  { code: 'qa', name: 'Qatar', flag: '🇶🇦' },
  { code: 'kw', name: 'Kuwait', flag: '🇰🇼' },
  { code: 'bh', name: 'Bahrain', flag: '🇧🇭' },
  { code: 'om', name: 'Oman', flag: '🇴🇲' },
  { code: 'jo', name: 'Jordan', flag: '🇯🇴' },
  { code: 'lb', name: 'Lebanon', flag: '🇱🇧' },
  { code: 'sy', name: 'Syria', flag: '🇸🇾' },
  { code: 'iq', name: 'Iraq', flag: '🇮🇶' },
  { code: 'ir', name: 'Iran', flag: '🇮🇷' },
  { code: 'af', name: 'Afghanistan', flag: '🇦🇫' },
  { code: 'eg', name: 'Egypt', flag: '🇪🇬' },
  { code: 'ly', name: 'Libya', flag: '🇱🇾' },
  { code: 'tn', name: 'Tunisia', flag: '🇹🇳' },
  { code: 'dz', name: 'Algeria', flag: '🇩🇿' },
  { code: 'ma', name: 'Morocco', flag: '🇲🇦' },
  { code: 'sd', name: 'Sudan', flag: '🇸🇩' },
  { code: 'ye', name: 'Yemen', flag: '🇾🇪' },

  // Sub-Saharan Africa
  { code: 'za', name: 'South Africa', flag: '🇿🇦' },
  { code: 'ng', name: 'Nigeria', flag: '🇳🇬' },
  { code: 'ke', name: 'Kenya', flag: '🇰🇪' },
  { code: 'gh', name: 'Ghana', flag: '🇬🇭' },
  { code: 'ug', name: 'Uganda', flag: '🇺🇬' },
  { code: 'tz', name: 'Tanzania', flag: '🇹🇿' },
  { code: 'zm', name: 'Zambia', flag: '🇿🇲' },
  { code: 'zw', name: 'Zimbabwe', flag: '🇿🇼' },
  { code: 'mw', name: 'Malawi', flag: '🇲🇼' },
  { code: 'mz', name: 'Mozambique', flag: '🇲🇿' },
  { code: 'ao', name: 'Angola', flag: '🇦🇴' },
  { code: 'bw', name: 'Botswana', flag: '🇧🇼' },
  { code: 'na', name: 'Namibia', flag: '🇳🇦' },
  { code: 'sz', name: 'Eswatini', flag: '🇸🇿' },
  { code: 'ls', name: 'Lesotho', flag: '🇱🇸' },
  { code: 'lr', name: 'Liberia', flag: '🇱🇷' },
  { code: 'sl', name: 'Sierra Leone', flag: '🇸🇱' },
  { code: 'gn', name: 'Guinea', flag: '🇬🇳' },
  { code: 'ci', name: 'Ivory Coast', flag: '🇨🇮' },
  { code: 'bf', name: 'Burkina Faso', flag: '🇧🇫' },
  { code: 'ml', name: 'Mali', flag: '🇲🇱' },
  { code: 'ne', name: 'Niger', flag: '🇳🇪' },
  { code: 'sn', name: 'Senegal', flag: '🇸🇳' },
  { code: 'tg', name: 'Togo', flag: '🇹🇬' },
  { code: 'bj', name: 'Benin', flag: '🇧🇯' },
  { code: 'cm', name: 'Cameroon', flag: '🇨🇲' },
  { code: 'cf', name: 'Central African Republic', flag: '🇨🇫' },
  { code: 'td', name: 'Chad', flag: '🇹🇩' },
  { code: 'cg', name: 'Republic of the Congo', flag: '🇨🇬' },
  { code: 'ga', name: 'Gabon', flag: '🇬🇦' },
  { code: 'gq', name: 'Equatorial Guinea', flag: '🇬🇶' },
  { code: 'cd', name: 'Democratic Republic of the Congo', flag: '🇨🇩' },
  { code: 'rw', name: 'Rwanda', flag: '🇷🇼' },
  { code: 'bi', name: 'Burundi', flag: '🇧🇮' },
  { code: 'dj', name: 'Djibouti', flag: '🇩🇯' },
  { code: 'er', name: 'Eritrea', flag: '🇪🇷' },
  { code: 'et', name: 'Ethiopia', flag: '🇪🇹' },
  { code: 'so', name: 'Somalia', flag: '🇸🇴' },
  { code: 'gm', name: 'Gambia', flag: '🇬🇲' },
  { code: 'cv', name: 'Cape Verde', flag: '🇨🇻' },
  { code: 'st', name: 'São Tomé and Príncipe', flag: '🇸🇹' },
  { code: 'sh', name: 'Saint Helena', flag: '🇸🇭' },
  { code: 'sc', name: 'Seychelles', flag: '🇸🇨' },
  { code: 'mu', name: 'Mauritius', flag: '🇲🇺' },
  { code: 'mg', name: 'Madagascar', flag: '🇲🇬' },
  { code: 'km', name: 'Comoros', flag: '🇰🇲' },
  { code: 're', name: 'Réunion', flag: '🇷🇪' },
  { code: 'yt', name: 'Mayotte', flag: '🇾🇹' },

  // South America
  { code: 'br', name: 'Brazil', flag: '🇧🇷' },
  { code: 'ar', name: 'Argentina', flag: '🇦🇷' },
  { code: 'cl', name: 'Chile', flag: '🇨🇱' },
  { code: 'co', name: 'Colombia', flag: '🇨🇴' },
  { code: 'pe', name: 'Peru', flag: '🇵🇪' },
  { code: 've', name: 'Venezuela', flag: '🇻🇪' },
  { code: 'uy', name: 'Uruguay', flag: '🇺🇾' },
  { code: 'py', name: 'Paraguay', flag: '🇵🇾' },
  { code: 'bo', name: 'Bolivia', flag: '🇧🇴' },
  { code: 'ec', name: 'Ecuador', flag: '🇪🇨' },
  { code: 'gy', name: 'Guyana', flag: '🇬🇾' },
  { code: 'sr', name: 'Suriname', flag: '🇸🇷' },
  { code: 'gf', name: 'French Guiana', flag: '🇬🇫' },

  // Central America & Caribbean (additional)
  { code: 'an', name: 'Netherlands Antilles', flag: '🇳🇱' },

  // Former Soviet Union
  { code: 'ru', name: 'Russia', flag: '🇷🇺' },
  { code: 'ua', name: 'Ukraine', flag: '🇺🇦' },
  { code: 'by', name: 'Belarus', flag: '🇧🇾' },
  { code: 'md', name: 'Moldova', flag: '🇲🇩' },
  { code: 'ge', name: 'Georgia', flag: '🇬🇪' },
  { code: 'am', name: 'Armenia', flag: '🇦🇲' },
  { code: 'az', name: 'Azerbaijan', flag: '🇦🇿' },
  { code: 'kz', name: 'Kazakhstan', flag: '🇰🇿' },
  { code: 'kg', name: 'Kyrgyzstan', flag: '🇰🇬' },
  { code: 'uz', name: 'Uzbekistan', flag: '🇺🇿' },
  { code: 'tj', name: 'Tajikistan', flag: '🇹🇯' },
  { code: 'tm', name: 'Turkmenistan', flag: '🇹🇲' },

  // Southeast Asia continued
  { code: 'bn', name: 'Brunei', flag: '🇧🇳' },
  { code: 'mv', name: 'Maldives', flag: '🇲🇻' },

  // Others
  { code: 'tr', name: 'Turkey', flag: '🇹🇷' },
  { code: 'mc', name: 'Monaco', flag: '🇲🇨' },
  { code: 'ad', name: 'Andorra', flag: '🇦🇩' },
  { code: 'sm', name: 'San Marino', flag: '🇸🇲' },
  { code: 'va', name: 'Vatican City', flag: '🇻🇦' },
  { code: 'li', name: 'Liechtenstein', flag: '🇱🇮' },
  { code: 'fo', name: 'Faroe Islands', flag: '🇫🇴' },
  { code: 'gl', name: 'Greenland', flag: '🇬🇱' }
]

// Popular countries (top markets)
const popularCountries = ['us', 'gb', 'ca', 'au', 'de', 'fr', 'in', 'jp', 'br', 'mx', 'es', 'it', 'nl', 'se', 'no', 'dk', 'fi', 'ch', 'at', 'nz', 'sg', 'hk', 'kr', 'cn', 'ae', 'sa', 'il', 'za']

const isOpen = ref(false)
const searchQuery = ref('')
const searchInput = ref(null)

// Computed properties
const selectedCountries = computed(() => {
  return props.modelValue.map(countryCode => {
    return allCountries.find(c => c.code === countryCode)
  }).filter(Boolean)
})

const filteredCountries = computed(() => {
  if (!searchQuery.value) {
    return allCountries
  }

  const query = searchQuery.value.toLowerCase()
  return allCountries.filter(country =>
    country.name.toLowerCase().includes(query) ||
    country.code.toLowerCase().includes(query)
  )
})

// Methods
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    nextTick(() => {
      searchInput.value?.focus()
    })
  }
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
}

const toggleCountry = (country) => {
  const isSelected = isCountrySelected(country.code)
  if (isSelected) {
    removeCountry(country.code)
  } else {
    addCountry(country.code)
  }
}

const addCountry = (countryCode) => {
  if (!isCountrySelected(countryCode)) {
    const newValue = [...props.modelValue, countryCode]
    emit('update:modelValue', newValue)
  }
}

const removeCountry = (countryCode) => {
  const newValue = props.modelValue.filter(code => code !== countryCode)
  emit('update:modelValue', newValue)
}

const isCountrySelected = (countryCode) => {
  return props.modelValue.includes(countryCode)
}

const selectAllVisible = () => {
  const visibleCountries = filteredCountries.value.map(c => c.code)
  const newSelection = [...new Set([...props.modelValue, ...visibleCountries])]
  emit('update:modelValue', newSelection)
}

const clearSelection = () => {
  emit('update:modelValue', [])
}

const selectPopular = () => {
  emit('update:modelValue', popularCountries)
}

// Handle clicks outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for external changes
watch(() => props.modelValue, () => {
  // Component will react to external changes
}, { deep: true })
</script>

<style scoped>
.text-customPrimaryColor {
  color: var(--custom-primary);
}

.bg-customPrimaryColor {
  background-color: var(--custom-primary);
}

.border-customPrimaryColor {
  border-color: var(--custom-primary);
}

.ring-customPrimaryColor {
  --tw-ring-color: var(--custom-primary);
}

.focus\:ring-customPrimaryColor:focus {
  --tw-ring-color: var(--custom-primary);
}

.focus\:border-customPrimaryColor:focus {
  --tw-border-opacity: 1;
  border-color: var(--custom-primary);
}

.hover\:bg-customPrimaryColor\/80:hover {
  background-color: color-mix(in srgb, var(--custom-primary) 80%, transparent);
}

.hover\:bg-customPrimaryColor\/10:hover {
  background-color: color-mix(in srgb, var(--custom-primary) 10%, transparent);
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
