<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3"; // Import router
import { computed, onMounted, provide, ref, watch, nextTick } from "vue"; // Import nextTick
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import goIcon from "../../assets/goIcon.svg";
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import check from "../../assets/Check.svg";
import noVehicleIcon from "../../assets/traveling-car-illustration.png";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import axios from 'axios'; // Import axios for API calls

const currencySymbols = ref({});

onMounted(async () => {
    try {
        const response = await fetch('/currency.json');
        const data = await response.json();
        currencySymbols.value = data.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
    } catch (error) {
        console.error("Error loading currency symbols:", error);
    }
});

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$';
};

const props = defineProps({
    vehicles: Object, // Data from GreenMotion API
    locations: Array, // Locations from GreenMotion API (for map coordinates) - now an array of 0 or 1 location
    optionalExtras: Array, // Optional extras from GreenMotion API
    filters: Object, // Current filter values (e.g., location_id)
    pagination_links: String, // For pagination if needed
    seoMeta: Object,
    locale: String,
});

const page = usePage();

const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === props.locale) || {};
});

const constructedLocalizedUrlSlug = computed(() => {
    return seoTranslation.value.url_slug || props.seoMeta?.url_slug || 'green-motion-cars';
});

const currentUrl = computed(() => {
    return `${window.location.origin}/${props.locale}/${constructedLocalizedUrlSlug.value}`;
});

const canonicalUrl = computed(() => {
    return props.seoMeta?.canonical_url || currentUrl.value;
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || 'GreenMotion Vehicles';
});

const seoDescription = computed(() => {
    return seoTranslation.value.meta_description || props.seoMeta?.meta_description || '';
});

const seoKeywords = computed(() => {
    return seoTranslation.value.keywords || props.seoMeta?.keywords || '';
});

const seoImageUrl = computed(() => {
    return props.seoMeta?.seo_image_url || '';
});

let map = null;
let markers = [];

const isValidCoordinate = (coord) => {
    const num = parseFloat(coord);
    return !isNaN(num) && isFinite(num);
};

const initMap = () => {
    if (map) {
        map.remove();
        map = null;
    }

    map = L.map("map", {
        zoomControl: true,
        maxZoom: 18,
        minZoom: 3,
        zoomSnap: 0.25,
        markerZoomAnimation: false,
        preferCanvas: true,
    });

    const defaultView = [20, 0]; // Default to a global view
    const defaultZoom = 2;

    let initialCoords = [];
    if (props.locations && props.locations.length > 0) {
        const location = props.locations[0]; // Expecting only one location now
        if (isValidCoordinate(location.latitude) && isValidCoordinate(location.longitude)) {
            initialCoords = [[parseFloat(location.latitude), parseFloat(location.longitude)]];
        }
    }

    if (initialCoords.length === 0) {
        console.warn("No valid location coordinates to initialize map view. Setting default view.");
        map.setView(defaultView, defaultZoom);
    } else {
        const bounds = L.latLngBounds(initialCoords);
        if (bounds.isValid()) {
            map.setView(bounds.getCenter(), 13); // Zoom in on the single location
        } else {
            map.setView(defaultView, defaultZoom);
        }
    }

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    map.createPane("markers");
    map.getPane("markers").style.zIndex = 1000;

    addMarkers();

    setTimeout(() => {
        if (map) {
            map.invalidateSize();
            if (initialCoords.length > 0) {
                const currentBounds = L.latLngBounds(initialCoords);
                if (currentBounds.isValid()) {
                    if (map.getZoom() < 10) map.setView(currentBounds.getCenter(), 13);
                    else map.panTo(currentBounds.getCenter());
                }
            } else if (!map.getCenter() || (map.getCenter().lat === defaultView[0] && map.getCenter().lng === defaultView[1] && map.getZoom() === defaultZoom)) {
                map.setView(defaultView, defaultZoom);
            }
        }
    }, 200);
};

const createCustomIcon = (vehicle, isHighlighted = false) => {
    const currencyCode = vehicle.products[0]?.currency;
    const currencySymbol = getCurrencySymbol(currencyCode);
    const priceToDisplay = vehicle.products[0]?.total ? `${currencySymbol}${vehicle.products[0].total}` : "N/A";

    const bgColor = isHighlighted ? 'bg-black' : 'bg-white';
    const textColor = isHighlighted ? 'text-white' : 'text-black';

    return L.divIcon({
        className: "custom-div-icon",
        html: `
    <div class="marker-pin ${bgColor} rounded-[99px] flex justify-center p-2 shadow-md">
      <span class="font-bold ${textColor}">${priceToDisplay}</span>
    </div>
  `,
        iconSize: [80, 30],
        iconAnchor: [40, 30],
        popupAnchor: [0, -30],
        pane: "markers",
    });
};

const addMarkers = () => {
    markers.forEach((marker) => marker.remove());
    markers = [];
    vehicleMarkers.value = {};

    if (!props.vehicles.data || props.vehicles.data.length === 0 || !props.locations || props.locations.length === 0) {
        return;
    }

    const mainLocation = props.locations[0]; // Assuming only one location is passed now
    if (!isValidCoordinate(mainLocation.latitude) || !isValidCoordinate(mainLocation.longitude)) {
        console.warn(`Main location ID ${mainLocation.id} has missing or invalid coordinates.`);
        return;
    }

    const lat = parseFloat(mainLocation.latitude);
    const lng = parseFloat(mainLocation.longitude);

    // For now, all vehicles will point to the same main location.
    // If multiple vehicles are at the exact same coordinate, offset them slightly.
    let offsetCount = 0;
    props.vehicles.data.forEach((vehicle) => {
        let displayLat = lat;
        let displayLng = lng;

        // Simple offset for multiple vehicles at the same location
        if (offsetCount > 0) {
            const angle = offsetCount * (2 * Math.PI / 10); // Distribute up to 10 markers in a circle
            const radius = 0.0001 * (Math.floor(offsetCount / 10) + 1); // Increase radius for more markers
            displayLat = lat + radius * Math.sin(angle);
            displayLng = lng + radius * Math.cos(angle);
        }
        offsetCount++;

        const primaryImage = vehicle.image || '/default-image.png';
        const price = vehicle.products[0]?.total || 'N/A';
        const currency = vehicle.products[0]?.currency || '$';

        const marker = L.marker([displayLat, displayLng], {
            icon: createCustomIcon(vehicle),
            pane: "markers",
        }).bindPopup(`
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.name}" class="popup-image" />
                <p class="font-semibold">${vehicle.name}</p>
                <p class="">Price: ${currency}${price}</p>
                <a href="${route('green-motion-car.show', { locale: props.locale, id: vehicle.id, ...props.filters })}"
                   class="text-blue-500 hover:text-blue-700"
                   onclick="event.preventDefault(); window.location.href = this.href;">
                    View Details
                </a>
            </div>
        `);

        map.addLayer(marker);
        markers.push(marker);
        vehicleMarkers.value[vehicle.id] = marker;
    });

    // Adjust map view to fit the single location
    const singleLocationCoords = [[lat, lng]];
    const bounds = L.latLngBounds(singleLocationCoords);
    if (bounds.isValid()) {
        map.setView(bounds.getCenter(), 13); // Zoom in on the single location
    } else {
        map.setView([20, 0], 2); // Fallback
    }
};


watch(
    () => props.vehicles,
    (newVehicles, oldVehicles) => {
        if (map) {
            if (JSON.stringify(newVehicles) !== JSON.stringify(oldVehicles)) {
                 addMarkers();
            }
        }
    },
    { deep: true }
);

watch(
    () => props.locations,
    (newLocations, oldLocations) => {
        if (map) {
            if (JSON.stringify(newLocations) !== JSON.stringify(oldLocations)) {
                initMap(); // Re-initialize map if locations change
            }
        }
    },
    { deep: true }
);

onMounted(() => {
    initMap();
});

const vehicleMarkers = ref({});

const highlightVehicleOnMap = (vehicle) => {
    if (!map || !vehicle || !props.locations || props.locations.length === 0) return;

    const mainLocation = props.locations[0]; // Assuming only one location
    if (!isValidCoordinate(mainLocation.latitude) || !isValidCoordinate(mainLocation.longitude)) return;

    const marker = vehicleMarkers.value[vehicle.id];
    if (marker) {
        map.panTo([parseFloat(mainLocation.latitude), parseFloat(mainLocation.longitude)], { animate: true, duration: 0.5 });
        marker.setIcon(createCustomIcon(vehicle, true));
        marker.openPopup();
    }
};

const unhighlightVehicleOnMap = (vehicle) => {
    if (!map || !vehicle) return;

    const marker = vehicleMarkers.value[vehicle.id];
    if (marker) {
        marker.setIcon(createCustomIcon(vehicle, false));
    }
};

const showMap = ref(true);

const handleMapToggle = (value) => {
    showMap.value = value;
    if (value && map) {
        setTimeout(() => {
            map.invalidateSize();
            if (props.locations && props.locations.length > 0) {
                const mainLocation = props.locations[0];
                if (isValidCoordinate(mainLocation.latitude) && isValidCoordinate(mainLocation.longitude)) {
                    map.setView([parseFloat(mainLocation.latitude), parseFloat(mainLocation.longitude)], 13);
                } else {
                    map.setView([20, 0], 2);
                }
            } else {
                map.setView([20, 0], 2);
            }
        }, 100);
    }
};

// Favorites are not directly applicable to GreenMotion API vehicles.
const favoriteStatus = ref({});
const popEffect = ref({});
const toggleFavourite = async (vehicle) => {
    console.log("Favorites are not supported for GreenMotion vehicles.");
};

const searchForm = ref({
    location_id: props.filters.location_id || null,
    location_name: '',
    start_date: props.filters.start_date || '', // Removed static prefill
    start_time: props.filters.start_time || '', // Removed static prefill
    end_date: props.filters.end_date || '',   // Removed static prefill
    end_time: props.filters.end_time || '',     // Removed static prefill
    age: props.filters.age || null, // Removed static prefill, set to null
    rentalCode: props.filters.rentalCode || '1', // Prefill with '1'
});

const autocompleteSuggestions = ref([]);
const showSuggestions = ref(false);
let debounceTimeout = null;
const preventHide = ref(false); // New flag to prevent immediate hiding on blur

const fetchLocations = async () => {
    console.log('fetchLocations called. Current search_term:', searchForm.value.location_name);
    if (searchForm.value.location_name.length < 2) {
        autocompleteSuggestions.value = [];
        showSuggestions.value = false;
        console.log('Search term too short. Suggestions cleared.');
        return;
    }

    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        try {
            console.log('Making API call for search_term:', searchForm.value.location_name);
            const response = await axios.get(route('api.greenmotion.locations-autocomplete', { locale: props.locale }), {
                params: {
                    search_term: searchForm.value.location_name,
                    country_id: 1, // Assuming default country ID 1 (e.g., UK) for now
                    language: props.locale,
                }
            });
            console.log('Raw API response data:', response.data); // Log raw response data
            autocompleteSuggestions.value = response.data;
            showSuggestions.value = true;
            console.log('autocompleteSuggestions.value updated:', autocompleteSuggestions.value);
            console.log('showSuggestions.value updated:', showSuggestions.value);
        } catch (error) {
            console.error("Error fetching locations for autocomplete:", error);
            autocompleteSuggestions.value = [];
            showSuggestions.value = false;
            console.log('Error during API call. Suggestions cleared.');
        }
    }, 300); // 300ms debounce
};

const selectLocation = (location) => {
    searchForm.value.location_name = location.name;
    searchForm.value.location_id = location.locationID;
    autocompleteSuggestions.value = [];
    showSuggestions.value = false;
};

// Method to handle blur event for autocomplete input
const handleBlur = () => {
    if (!preventHide.value) {
        showSuggestions.value = false;
    }
};

// Methods to manage preventHide flag for mousedown/mouseup on suggestions list
const handleSuggestionsMousedown = () => {
    preventHide.value = true;
};

const handleSuggestionsMouseup = () => {
    preventHide.value = false;
};

const handleSearch = () => {
    // Ensure location_id is set before searching
    if (!searchForm.value.location_id && searchForm.value.location_name) {
        // If user typed a name but didn't select from autocomplete, try to find a match
        const matchedLocation = autocompleteSuggestions.value.find(loc => loc.name === searchForm.value.location_name);
        if (matchedLocation) {
            searchForm.value.location_id = matchedLocation.locationID;
        } else {
            // Optionally, handle cases where no match is found, e.g., alert user or prevent search
            alert('Please select a valid location from the suggestions.');
            return;
        }
    }

    router.get(route('green-motion-cars', { locale: props.locale }), searchForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch for changes in location_name to trigger autocomplete
watch(() => searchForm.value.location_name, (newValue, oldValue) => {
    console.log('location_name changed from', oldValue, 'to', newValue);
    fetchLocations();
});

// On initial load, if location_id is present, fetch its name to pre-fill the input
onMounted(async () => {
    initMap();
    if (props.filters.location_id) {
        try {
            const response = await axios.get(route('api.greenmotion.locations', { locale: props.locale }), {
                params: {
                    location_id: props.filters.location_id,
                }
            });
            if (response.data && response.data.name) {
                searchForm.value.location_name = response.data.name;
            }
        } catch (error) {
            console.error("Error fetching initial location name:", error);
        }
    }
});

</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow" />
        <title>{{ seoTitle }}</title>
        <meta name="description" :content="seoDescription" />
        <meta name="keywords" :content="seoKeywords" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="seoTitle" />
        <meta property="og:description" :content="seoDescription" />
        <meta property="og:image" :content="seoImageUrl" />
        <meta property="og:url" :content="currentUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoTitle" />
        <meta name="twitter:description" :content="seoDescription" />
        <meta name="twitter:image" :content="seoImageUrl" />
    </Head>
    <AuthenticatedHeaderLayout />
    <section class="bg-customPrimaryColor py-customVerticalSpacing">
        <div class="full-w-container flex flex-col items-center justify-center py-8">
            <h1 class="text-white text-3xl font-bold mb-4">GreenMotion Vehicles</h1>
            <p class="text-white text-lg">Find your perfect ride with GreenMotion.</p>
        </div>
    </section>

    <div class="full-w-container mx-auto mt-8">
        <form @submit.prevent="handleSearch" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 bg-white shadow-md rounded-lg">
            <div class="relative">
                <label for="location_name" class="block text-sm font-medium text-gray-700">Location</label>
                <input
                    type="text"
                    id="location_name"
                    v-model="searchForm.location_name"
                    @focus="showSuggestions = true"
                    @blur="handleBlur"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="Enter location name"
                    autocomplete="off"
                />
                <input type="hidden" v-model="searchForm.location_id" />
                <ul
                    v-if="showSuggestions && autocompleteSuggestions.length"
                    class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto mt-1"
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
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" id="start_date" v-model="searchForm.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                <input type="time" id="start_time" v-model="searchForm.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" id="end_date" v-model="searchForm.end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                <input type="time" id="end_time" v-model="searchForm.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
           
            </div>
            <div>
                <label for="age" class="block text-sm font-medium text-gray-700">Age</label>
                <input type="number" id="age" v-model="searchForm.age" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="rentalCode" class="block text-sm font-medium text-gray-700">Rental Code (Optional)</label>
                <input type="text" id="rentalCode" v-model="searchForm.rentalCode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-customPrimaryColor text-white py-2 px-4 rounded-md hover:bg-customPrimaryColor-dark focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-opacity-50">
                    Search Vehicles
                </button>
            </div>
        </form>
    </div>

    <div class="full-w-container flex justify-end max-[768px]:hidden my-[2rem]">
        <div class="flex items-center space-x-2">
            <Label for="mapToggle" class="text-customPrimaryColor">Map</Label>
            <Switch id="mapToggle" :checked="showMap" @update:checked="handleMapToggle" />
        </div>
    </div>

    <div class="full-w-container mx-auto mb-[4rem]">
        <div class="flex gap-[2.5rem] max-[768px]:flex-col">
            <!-- Left Column - Vehicle List -->
            <div class="w-full">
                <div :class="[
                    'grid gap-5',
                    showMap ? 'w-full grid-cols-2' : 'w-full grid-cols-4',
                ]" class="max-[768px]:grid-cols-1">
                    <div v-if="!vehicles.data || vehicles.data.length === 0"
                        class="text-center text-gray-500 col-span-2 flex flex-col justify-center items-center gap-4">
                        <img :src=noVehicleIcon alt="" class="w-[25rem] max-[768px]:w-full" loading="lazy">
                        <p class="text-lg font-medium text-customPrimaryColor">No GreenMotion vehicles available at the moment</p>
                        <span>Please ensure a valid location is selected.</span>
                    </div>
                    <div v-for="vehicle in vehicles.data" :key="vehicle.id"
                        class="rounded-[12px] border-[1px] border-[#E7E7E7] relative overflow-hidden"
                        @mouseenter="highlightVehicleOnMap(vehicle)"
                        @mouseleave="unhighlightVehicleOnMap(vehicle)">
                        <a :href="route('green-motion-car.show', { locale: locale, id: vehicle.id, ...filters })">
                            <div class="column flex flex-col gap-5 items-start">
                                <img :src="vehicle.image || '/default-image.png'" alt="Vehicle Image"
                                    class="w-full h-[250px] object-cover rounded-tl-lg rounded-tr-lg max-[768px]:h-[200px]" loading="lazy" />
                                <span
                                    class="bg-[#f5f5f5] ml-[1rem] inline-block px-8 py-2 text-center rounded-[40px] max-[768px]:text-[0.95rem]">
                                    {{ vehicle.name }}
                                </span>
                            </div>

                            <div class="column p-[1rem]">
                                <h5 class="font-medium text-[1.5rem] text-customPrimaryColor max-[768px]:text-[1.2rem]">
                                    {{ vehicle.groupName }}
                                </h5>

                                 <div>
                                    <span class="italic font-medium">{{ vehicle.acriss }}</span>
                                 </div>

                                <div class="car_short_info mt-[1rem] flex gap-3">
                                    <img :src="carIcon" alt="" loading="lazy" />
                                    <div class="features">
                                        <span class="capitalize text-[1.15rem] max-[768px]:text-[1rem]">{{
                                            vehicle.transmission }} .
                                            {{ vehicle.fuel }} .
                                            {{ vehicle.adults }} Adults, {{ vehicle.children }} Children</span>
                                    </div>
                                </div>


                                <div class="benefits mt-[2rem] grid grid-cols-2 gap-3">
                                    <span v-if="vehicle.airConditioning === 'Yes'" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Air Conditioning
                                    </span>
                                    <span v-if="vehicle.refrigerated === 'Yes'" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Refrigerated
                                    </span>
                                    <span v-if="vehicle.keyngo === 'Yes'" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Key N Go
                                    </span>
                                    <span v-if="vehicle.driveandgo === 'Yes'" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Drive N Go
                                    </span>
                                    <span v-if="vehicle.products[0]?.fuelpolicy" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Fuel Policy: {{ vehicle.products[0].fuelpolicy }}
                                    </span>
                                    <span v-if="vehicle.products[0]?.minage" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Min Age: {{ vehicle.products[0].minage }}
                                    </span>
                                </div>

                                <div class="mt-[2rem] flex justify-between items-center">
                                    <div>
                                        <div v-if="vehicle.products[0]?.total && vehicle.products[0].total > 0">
                                            <span class="text-customPrimaryColor text-[1.875rem] font-medium max-[768px]:text-[1.3rem] max-[768px]:font-bold">
                                                {{ getCurrencySymbol(vehicle.products[0].currency) }}{{ vehicle.products[0].total }}
                                            </span>
                                            <span>/rental</span>
                                        </div>
                                        <div v-else>
                                            <span class="text-sm text-gray-500">Price not available</span>
                                        </div>
                                    </div>
                                    <img :src="goIcon" alt="Go" class="max-[768px]:w-[35px]" loading="lazy" />
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <!-- Pagination -->
                <div class="mt-4 pagination">
                    <div v-html="pagination_links"></div>
                </div>
            </div>
            <!-- Right Column - Map -->
            <div class="w-full sticky top-0 h-[100vh] max-[768px]:hidden mr-[-2.1%]" v-show="showMap">
                <div class="bg-white h-full">
                    <div id="map" class="h-full rounded-lg"></div>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style>
@import "leaflet/dist/leaflet.css";

.marker-pin {
    width: 80px;
    height: 30px;
    border: 2px solid #666;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.marker-pin span {
    font-weight: bold;
    font-size: 12px;
    padding: 0 8px;
}

.custom-div-icon {
    background: none;
    border: none;
}

/* Leaflet pane z-index overrides */
.leaflet-pane.leaflet-marker-pane {
    z-index: 1000 !important;
}

.leaflet-pane.leaflet-popup-pane {
    z-index: 1001 !important;
}

.leaflet-pane.leaflet-tile-pane {
    z-index: 200;
}

.leaflet-pane.leaflet-overlay-pane {
    z-index: 400;
}

.leaflet-popup {
    z-index: 1001 !important;
}

.leaflet-container {
    z-index: 1;
}

.leaflet-control-container {
    z-index: 2000;
}

#map {
    height: 100%;
    width: 100%;
}

.filter-slot>div:hover>select {
    background-color: rgba(128, 128, 128, 0.145);
}

.filter-slot>div>select {
    -webkit-appearance: none;
}

/* Rotate caret when select is focused */
select:focus+.caret-rotate {
    transform: translateY(-50%) rotate(180deg);
}

@keyframes pop {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.3);
    }

    100% {
        transform: scale(1);
    }
}

.pop-animation {
    animation: pop 0.3s ease-in-out;
}

.popup-image {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    margin-bottom: 5px;
}

.animate-fade-in {
    animation: fadeIn 0.2s ease-in-out;
}

.filter-slot select {
    background-color: white;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media screen and (max-width: 768px) {
    .filter-slot>div {
        width: 100%;
        justify-content: space-between;
    }

    .pagination nav .hidden {
        display: flex;
        width: 100%;
        justify-content: center;
    }

    .pagination nav div:first-child {
        display: none;
    }
}
</style>
