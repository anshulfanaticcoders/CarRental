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

// All countries with flags
const allCountries = [
  { code: 'af', name: 'Afghanistan', flag: '🇦🇫' },
  { code: 'al', name: 'Albania', flag: '🇦🇱' },
  { code: 'dz', name: 'Algeria', flag: '🇩🇿' },
  { code: 'ad', name: 'Andorra', flag: '🇦🇩' },
  { code: 'ao', name: 'Angola', flag: '🇦🇴' },
  { code: 'ar', name: 'Argentina', flag: '🇦🇷' },
  { code: 'am', name: 'Armenia', flag: '🇦🇲' },
  { code: 'au', name: 'Australia', flag: '🇦🇺' },
  { code: 'at', name: 'Austria', flag: '🇦🇹' },
  { code: 'az', name: 'Azerbaijan', flag: '🇦🇿' },
  { code: 'bh', name: 'Bahrain', flag: '🇧🇭' },
  { code: 'bd', name: 'Bangladesh', flag: '🇧🇩' },
  { code: 'by', name: 'Belarus', flag: '🇧🇾' },
  { code: 'be', name: 'Belgium', flag: '🇧🇪' },
  { code: 'bo', name: 'Bolivia', flag: '🇧🇴' },
  { code: 'ba', name: 'Bosnia and Herzegovina', flag: '🇧🇦' },
  { code: 'br', name: 'Brazil', flag: '🇧🇷' },
  { code: 'bg', name: 'Bulgaria', flag: '🇧🇬' },
  { code: 'kh', name: 'Cambodia', flag: '🇰🇭' },
  { code: 'cm', name: 'Cameroon', flag: '🇨🇲' },
  { code: 'ca', name: 'Canada', flag: '🇨🇦' },
  { code: 'cl', name: 'Chile', flag: '🇨🇱' },
  { code: 'cn', name: 'China', flag: '🇨🇳' },
  { code: 'co', name: 'Colombia', flag: '🇨🇴' },
  { code: 'cr', name: 'Costa Rica', flag: '🇨🇷' },
  { code: 'hr', name: 'Croatia', flag: '🇭🇷' },
  { code: 'cu', name: 'Cuba', flag: '🇨🇺' },
  { code: 'cy', name: 'Cyprus', flag: '🇨🇾' },
  { code: 'cz', name: 'Czech Republic', flag: '🇨🇿' },
  { code: 'dk', name: 'Denmark', flag: '🇩🇰' },
  { code: 'do', name: 'Dominican Republic', flag: '🇩🇴' },
  { code: 'ec', name: 'Ecuador', flag: '🇪🇨' },
  { code: 'eg', name: 'Egypt', flag: '🇪🇬' },
  { code: 'sv', name: 'El Salvador', flag: '🇸🇻' },
  { code: 'ee', name: 'Estonia', flag: '🇪🇪' },
  { code: 'fi', name: 'Finland', flag: '🇫🇮' },
  { code: 'fr', name: 'France', flag: '🇫🇷' },
  { code: 'ge', name: 'Georgia', flag: '🇬🇪' },
  { code: 'de', name: 'Germany', flag: '🇩🇪' },
  { code: 'gr', name: 'Greece', flag: '🇬🇷' },
  { code: 'gt', name: 'Guatemala', flag: '🇬🇹' },
  { code: 'hn', name: 'Honduras', flag: '🇭🇳' },
  { code: 'hk', name: 'Hong Kong', flag: '🇭🇰' },
  { code: 'hu', name: 'Hungary', flag: '🇭🇺' },
  { code: 'is', name: 'Iceland', flag: '🇮🇸' },
  { code: 'in', name: 'India', flag: '🇮🇳' },
  { code: 'id', name: 'Indonesia', flag: '🇮🇩' },
  { code: 'ir', name: 'Iran', flag: '🇮🇷' },
  { code: 'iq', name: 'Iraq', flag: '🇮🇶' },
  { code: 'ie', name: 'Ireland', flag: '🇮🇪' },
  { code: 'il', name: 'Israel', flag: '🇮🇱' },
  { code: 'it', name: 'Italy', flag: '🇮🇹' },
  { code: 'jp', name: 'Japan', flag: '🇯🇵' },
  { code: 'jo', name: 'Jordan', flag: '🇯🇴' },
  { code: 'kz', name: 'Kazakhstan', flag: '🇰🇿' },
  { code: 'kw', name: 'Kuwait', flag: '🇰🇼' },
  { code: 'lv', name: 'Latvia', flag: '🇱🇻' },
  { code: 'lb', name: 'Lebanon', flag: '🇱🇧' },
  { code: 'lt', name: 'Lithuania', flag: '🇱🇹' },
  { code: 'lu', name: 'Luxembourg', flag: '🇱🇺' },
  { code: 'my', name: 'Malaysia', flag: '🇲🇾' },
  { code: 'mx', name: 'Mexico', flag: '🇲🇽' },
  { code: 'mc', name: 'Monaco', flag: '🇲🇨' },
  { code: 'ma', name: 'Morocco', flag: '🇲🇦' },
  { code: 'nl', name: 'Netherlands', flag: '🇳🇱' },
  { code: 'nz', name: 'New Zealand', flag: '🇳🇿' },
  { code: 'ni', name: 'Nicaragua', flag: '🇳🇮' },
  { code: 'no', name: 'Norway', flag: '🇳🇴' },
  { code: 'om', name: 'Oman', flag: '🇴🇲' },
  { code: 'pk', name: 'Pakistan', flag: '🇵🇰' },
  { code: 'pa', name: 'Panama', flag: '🇵🇦' },
  { code: 'pe', name: 'Peru', flag: '🇵🇪' },
  { code: 'ph', name: 'Philippines', flag: '🇵🇭' },
  { code: 'pl', name: 'Poland', flag: '🇵🇱' },
  { code: 'pt', name: 'Portugal', flag: '🇵🇹' },
  { code: 'pr', name: 'Puerto Rico', flag: '🇵🇷' },
  { code: 'qa', name: 'Qatar', flag: '🇶🇦' },
  { code: 'ro', name: 'Romania', flag: '🇷🇴' },
  { code: 'ru', name: 'Russia', flag: '🇷🇺' },
  { code: 'sa', name: 'Saudi Arabia', flag: '🇸🇦' },
  { code: 'sg', name: 'Singapore', flag: '🇸🇬' },
  { code: 'sk', name: 'Slovakia', flag: '🇸🇰' },
  { code: 'si', name: 'Slovenia', flag: '🇸🇮' },
  { code: 'za', name: 'South Africa', flag: '🇿🇦' },
  { code: 'kr', name: 'South Korea', flag: '🇰🇷' },
  { code: 'es', name: 'Spain', flag: '🇪🇸' },
  { code: 'se', name: 'Sweden', flag: '🇸🇪' },
  { code: 'ch', name: 'Switzerland', flag: '🇨🇭' },
  { code: 'tw', name: 'Taiwan', flag: '🇹🇼' },
  { code: 'th', name: 'Thailand', flag: '🇹🇭' },
  { code: 'tn', name: 'Tunisia', flag: '🇹🇳' },
  { code: 'tr', name: 'Turkey', flag: '🇹🇷' },
  { code: 'ae', name: 'UAE', flag: '🇦🇪' },
  { code: 'gb', name: 'United Kingdom', flag: '🇬🇧' },
  { code: 'us', name: 'United States', flag: '🇺🇸' },
  { code: 'uy', name: 'Uruguay', flag: '🇺🇾' },
  { code: 've', name: 'Venezuela', flag: '🇻🇪' },
  { code: 'vn', name: 'Vietnam', flag: '🇻🇳' },
  { code: 'ye', name: 'Yemen', flag: '🇾🇪' }
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