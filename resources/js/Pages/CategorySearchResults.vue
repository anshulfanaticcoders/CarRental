<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, onMounted, provide, ref, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import goIcon from "../../assets/goIcon.svg";
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import check from "../../assets/Check.svg";
import priceIcon from "../../assets/percent.svg";
import priceperdayicon from "../../assets/priceFilter.png";
import fuelIcon from "../../assets/fuel.svg";
import transmissionIcon from "../../assets/transmittionIcon.svg";
import mileageIcon2 from "../../assets/unlimitedKm.svg";
import noVehicleIcon from "../../assets/traveling-car-illustration.png";
import seatingIcon from "../../assets/travellerIcon.svg";
import brandIcon from "../../assets/SedanCarIcon.svg";
import colorIcon from "../../assets/color-palette.svg";
import filterIcon from "../../assets/filterIcon.svg";
import SearchBar from "@/Components/SearchBar.vue";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import CaretDown from "../../assets/CaretDown.svg";
import fullStar from "../../assets/fullstar.svg"; // Add star imports
import halfStar from "../../assets/halfstar.svg";
import blankStar from "../../assets/blankstar.svg";
import 'leaflet.markercluster/dist/leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import VueSlider from 'vue-slider-component';
import 'vue-slider-component/theme/default.css';


const props = defineProps({
    vehicles: Object,
    filters: Object,
    pagination_links: String,
    categories: Array,
    brands: Array,
    colors: Array,
    seatingCapacities: Array,
    transmissions: Array, // Add this
    fuels: Array,         // Add this
    mileages: Array,      // Add this
});

// Debounce function
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};
const page = usePage();
// Use Inertia's form handling
const form = useForm({
    seating_capacity: usePage().props.filters.seating_capacity || "",
    brand: usePage().props.filters.brand || "",
    transmission: usePage().props.filters.transmission || "",
    fuel: usePage().props.filters.fuel || "",
    price_range: usePage().props.filters.price_range || "",
    color: usePage().props.filters.color || "",
    mileage: usePage().props.filters.mileage || "",
    date_from: usePage().props.filters.date_from || "",
    date_to: usePage().props.filters.date_to || "",
    where: usePage().props.filters.where || "",
    latitude: usePage().props.filters.latitude || null,
    longitude: usePage().props.filters.longitude || null,
    radius: usePage().props.filters.radius || null,
    package_type: usePage().props.filters.package_type || "day",
    category_id: usePage().props.filters.category_id || "",
});

// Debounced filter submission
const submitFilters = debounce(() => {
    form.get(`/search/category/${form.category_id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (response) => {
            console.log('Filter response:', response.props.vehicles);
        },
        onError: (errors) => {
            console.error('Filter errors:', errors);
        },
    });
}, 300);

watch(
    () => form.data(),
    () => {
        submitFilters();
    },
    { deep: true }
);
let map = null;
let markers = [];

const initMap = () => {
    if (!props.vehicles.data || props.vehicles.data.length === 0) {
        console.warn("No vehicles data available to initialize map.");
        map = L.map("map", {
            zoomControl: true,
            maxZoom: 18,
            minZoom: 4,
        }).setView([0, 0], 2); // Default to world view
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors",
        }).addTo(map);
        return;
    }

    // Calculate bounds for all vehicles
    const bounds = L.latLngBounds(
        props.vehicles.data.map((vehicle) => [
            vehicle.latitude,
            vehicle.longitude,
        ])
    );

    map = L.map("map", {
        zoomSnap: 0.25,
        markerZoomAnimation: false,
        preferCanvas: true,
        zoomControl: true,
        maxZoom: 18,
        minZoom: 4,
    });

    // Set initial view to fit all markers
    map.fitBounds(bounds, {
        padding: [50, 50],
        maxZoom: 12,
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    // Create a custom pane for markers with high z-index
    map.createPane("markers");
    map.getPane("markers").style.zIndex = 1000;

    addMarkers();

    // Force a map refresh after a short delay
    setTimeout(() => {
        map.invalidateSize();
        map.fitBounds(bounds, {
            padding: [50, 50],
            maxZoom: 12,
        });
    }, 100);
};

const createCustomIcon = (price, currency) => {
    return L.divIcon({
        className: "custom-div-icon",
        html: `
     <div class="marker-pin bg-white rounded-[99px] flex justify-center p-2 shadow-md">
      <span class="font-bold">${currency || "₹"}${price}</span>
    </div>
  `,
        iconSize: [50, 30],
        iconAnchor: [25, 15],
        popupAnchor: [0, -15],
        pane: "markers",
    });
};

const resetFilters = () => {
    // Completely reset the form to initial empty state
    form.reset();

    // Reset all individual form fields explicitly
    form.seating_capacity = "";
    form.brand = "";
    form.transmission = "";
    form.fuel = "";
    form.price_range = "";
    form.color = "";
    form.mileage = "";
    form.package_type = "day";

    // Reset price range slider
    priceRangeValues.value = [0, 20000];
    tempPriceRangeValues.value = [0, 20000];

    // Force submit to reload with empty filters
    submitFilters();
};


const addMarkers = () => {
    // Remove existing markers
    markers.forEach((marker) => marker.remove());
    markers = [];

    if (!props.vehicles.data || props.vehicles.data.length === 0) {
        console.warn("No vehicles data available to add markers.");
        return;
    }

    // Create a MarkerClusterGroup
    const markerClusterGroup = L.markerClusterGroup({
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: true,
        maxClusterRadius: 40,
        disableClusteringAtZoom: 20,
    });

    // Add markers to the cluster group
    props.vehicles.data.forEach((vehicle) => {
        const currency = vehicle.vendor_profile?.currency || "$";
        // Find the primary image
        const primaryImage = vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png';

        const marker = L.marker([vehicle.latitude, vehicle.longitude], {
            icon: createCustomIcon(vehicle.price_per_day, currency),
            pane: "markers",
        }).bindPopup(`
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.brand} ${vehicle.model}" class="popup-image" />
                <p class="rating">${vehicle.average_rating} ★ (${vehicle.review_count} reviews)</p>
                <p class="font-semibold">${vehicle.brand} ${vehicle.model}</p>
                <p class="">${vehicle.location}</p>
                <a href="/vehicle/${vehicle.id}" 
                   class="text-blue-500 hover:text-blue-700"
                   onclick="window.location.href='/vehicle/${vehicle.id}'; return false;">
                    View Details
                </a>
            </div>
        `);

        markerClusterGroup.addLayer(marker);
        markers.push(marker);
    });

    // Add the cluster group to the map
    map.addLayer(markerClusterGroup);

    // Fit bounds after adding markers
    const groupBounds = markerClusterGroup.getBounds();
    if (groupBounds.isValid()) {
        map.fitBounds(groupBounds, {
            padding: [50, 50],
            maxZoom: 14,
        });

        // Listen for zoom changes to trigger spiderfy
        map.on('zoomend', () => {
            if (map.getZoom() >= markerClusterGroup.options.disableClusteringAtZoom) {
                markerClusterGroup.eachLayer((layer) => {
                    if (layer instanceof L.MarkerCluster && map.getBounds().contains(layer.getLatLng())) {
                        layer.spiderfy();
                    }
                });
            }
        });
    }
};

// Watch for changes in vehicles data
watch(
    () => props.vehicles,
    () => {
        if (map) {
            addMarkers();
        }
    },
    { deep: true }
);

onMounted(() => {
    initMap();
});

// Toggle map functionality
const showMap = ref(true);

// Add a function to handle the toggle
const handleMapToggle = (value) => {
    showMap.value = value;
    // Force map to refresh when showing it again
    if (value && map) {
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }
};

// add to favourite vehicle functionality

// Function to toggle favourite status
import { useToast } from "vue-toastification"; // Reuse your existing import
import { Inertia } from "@inertiajs/inertia";
import CustomDropdown from "@/Components/CustomDropdown.vue";
const toast = useToast(); // Initialize toast
const favoriteStatus = ref({}); // Store favorite status for each vehicle

const fetchFavoriteStatus = async () => {
    try {
        const response = await axios.get("/favorites");
        const favoriteIds = response.data.map((v) => v.id);

        // ✅ Initialize favorite status for each vehicle
        props.vehicles.data.forEach((vehicle) => {
            favoriteStatus.value[vehicle.id] = favoriteIds.includes(vehicle.id);
        });
    } catch (error) {
        console.error("Error fetching favorite status:", error);
    }
};
const $page = usePage();

const popEffect = ref({}); // Store animation state

const toggleFavourite = async (vehicle) => {
    if (!$page.props.auth?.user) {
        return Inertia.visit("/login"); // Redirect if not logged in
    }

    const endpoint = favoriteStatus.value[vehicle.id]
        ? `/vehicles/${vehicle.id}/unfavourite`
        : `/vehicles/${vehicle.id}/favourite`;

    try {
        await axios.post(endpoint);
        favoriteStatus.value[vehicle.id] = !favoriteStatus.value[vehicle.id];

        // Trigger pop effect
        if (favoriteStatus.value[vehicle.id]) {
            popEffect.value[vehicle.id] = true;
            setTimeout(() => {
                popEffect.value[vehicle.id] = false;
            }, 300); // Remove class after animation duration
        }

        toast.success(
            `Vehicle ${favoriteStatus.value[vehicle.id] ? "added to" : "removed from"
            } favorites!`,
            {
                position: "top-right",
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                icon: favoriteStatus.value[vehicle.id] ? "❤️" : "💔",
            }
        );
    } catch (error) {
        if (error.response && error.response.status === 401) {
            Inertia.visit("/login");
        } else {
            toast.error("Failed to update favorites", {
                position: "top-right",
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        }
    }
};

// ✅ Fetch Data on Component Mount
// onMounted(fetchFavoriteStatus);

onMounted(() => {
    if (page.props.auth?.user) {
        fetchFavoriteStatus();
    }
});

const priceField = computed(() => {
    switch (form.package_type) {
        case "week":
            return "price_per_week";
        case "month":
            return "price_per_month";
        default:
            return "price_per_day";
    }
});

const priceUnit = computed(() => {
    switch (form.package_type) {
        case "week":
            return "week";
        case "month":
            return "month";
        default:
            return "day";
    }
});

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, "0")}/${String(
        date.getDate()
    ).padStart(2, "0")}/${date.getFullYear()}`;
};
const showRentalDates = ref(false);

const searchQuery = computed(() => {
    return {
        where: usePage().props.filters?.where || "",
        date_from: usePage().props.filters?.date_from || "",
        date_to: usePage().props.filters?.date_to || "",
        latitude: usePage().props.filters?.latitude || "",
        longitude: usePage().props.filters?.longitude || "",
        radius: usePage().props.filters?.radius || "",
    };
});

const showMobileFilters = ref(false);
const applyFilters = () => {
    showMobileFilters.value = false;
};

const getStarIcon = (rating, starNumber) => {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;

    if (starNumber <= fullStars) {
        return fullStar;
    } else if (starNumber === fullStars + 1 && hasHalfStar) {
        return halfStar;
    } else {
        return blankStar;
    }
};

const getStarAltText = (rating, starNumber) => {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;

    if (starNumber <= fullStars) {
        return "Full Star";
    } else if (starNumber === fullStars + 1 && hasHalfStar) {
        return "Half Star";
    } else {
        return "Blank Star";
    }
};


// Price slider logic
const showPriceSlider = ref(false);
const priceRangeMin = ref(0);
const priceRangeMax = ref(20000);
const priceRangeValues = ref([0, 20000]);
const tempPriceRangeValues = ref([0, 20000]);

// Initialize price range from form if it exists
onMounted(() => {
    if (form.price_range) {
        const [min, max] = form.price_range.split('-').map(Number);
        priceRangeValues.value = [min || 0, max || 20000];
        tempPriceRangeValues.value = [min || 0, max || 20000];
    }
});

// Apply price range and update form
const applyPriceRange = () => {
    priceRangeValues.value = [...tempPriceRangeValues.value];
    form.price_range = `${priceRangeValues.value[0]}-${priceRangeValues.value[1]}`;
    showPriceSlider.value = false;
};

// Reset price range to default
const resetPriceRange = () => {
    tempPriceRangeValues.value = [0, 20000];
    priceRangeValues.value = [0, 20000];
    form.price_range = '0-20000';
    showPriceSlider.value = false;
};

// Global dropdown state management
const activeDropdown = ref(null);

const setActiveDropdown = (name) => {
    if (activeDropdown.value === name) {
        activeDropdown.value = null;
    } else {
        activeDropdown.value = name;
    }
};

// Provide these to child components
provide('activeDropdown', activeDropdown);
provide('setActiveDropdown', setActiveDropdown);


onMounted(() => {

const urlPath = window.location.pathname;
const categoryMatch = urlPath.match(/\/search\/category\/(\d+)/);

if (categoryMatch && categoryMatch[1]) {
  const categoryIdFromUrl = categoryMatch[1];
  
  // Check if this category ID exists in our options
  const categoryExists = props.categories && props.categories.some(
    category => category.id.toString() === categoryIdFromUrl
  );
  
  if (categoryExists) {
    form.category_id = categoryIdFromUrl;
    submitFilters();
  }
}
});
</script>

<template>
    <AuthenticatedHeaderLayout />
    <section class="bg-customPrimaryColor py-customVerticalSpacing">
        <div class="">
            <SearchBar class="border-[2px] rounded-[20px] border-white mt-0 mb-0 max-[768px]:border-none"
                :prefill="searchQuery" />
        </div>
    </section>

    <section>
    <div class="full-w-container py-8">
        <!-- Mobile filter button (visible only on mobile) -->
        <div class="md:hidden mb-4">
            <button @click="showMobileFilters = true"
                class="flex items-center justify-center gap-3 p-3 w-full bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                <img :src="filterIcon" alt="Filter" class="w-5 h-5" />
                <span class="text-lg font-medium">Find Your Perfect Car</span>
            </button>
        </div>

        <!-- Desktop filter header (hidden on mobile) -->
        <div class="hidden md:flex items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-3">
                <img :src="filterIcon" alt="" class="w-6 h-6" />
                <span class="text-xl font-semibold">Customize Your Search</span>
            </div>
            <button @click="resetFilters"
                class="px-5 py-2 bg-customPrimaryColor text-white rounded-lg hover:bg-opacity-90 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Clear All Filters
            </button>
        </div>

        <!-- Desktop filters (hidden on mobile) -->
        <form class="hidden md:block">
            <div class="flex gap-5 flex-wrap filter-slot items-center">
                <!-- Seating Capacity Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Passenger Seats</div>
                    <CustomDropdown v-model="form.seating_capacity" unique-id="seating-capacity"
                        :options="$page.props.seatingCapacities.map(capacity => ({ value: capacity, label: capacity + ' Seats' }))"
                        placeholder="Any Capacity" :left-icon="seatingIcon" :right-icon="CaretDown" 
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Brand Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Car Brand</div>
                    <CustomDropdown v-model="form.brand" unique-id="brand"
                        :options="[...$page.props.brands.map(brand => ({ value: brand, label: brand })), { value: '', label: 'Any Brand' }]"
                        placeholder="Any Brand" :left-icon="brandIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Transmission Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Transmission Type</div>
                    <CustomDropdown v-model="form.transmission" unique-id="transmission"
                        :options="[{ value: '', label: 'Any Type' }, ...$page.props.transmissions.map(transmission => ({ value: transmission, label: transmission.charAt(0).toUpperCase() + transmission.slice(1) }))]"
                        placeholder="Any Type" :left-icon="transmissionIcon" :right-icon="CaretDown" 
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Fuel Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Fuel Type</div>
                    <CustomDropdown v-model="form.fuel" unique-id="fuel"
                        :options="[{ value: '', label: 'Any Fuel' }, ...$page.props.fuels.map(fuel => ({ value: fuel, label: fuel.charAt(0).toUpperCase() + fuel.slice(1) }))]"
                        placeholder="Any Fuel" :left-icon="fuelIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Price Range Filter -->
                <div class="relative  filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Budget</div>
                    <div class="relative w-full">
                        <img :src="priceIcon" alt="Price Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none text-gray-500" />
                        <button type="button" @click="showPriceSlider = !showPriceSlider"
                            class="pl-10 pr-4 py-2 w-full text-left flex gap-4 items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm hover:border-customPrimaryColor transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor/20">
                            <span class="text-gray-700 font-medium">
                                {{ priceRangeValues[0] === 0 && priceRangeValues[1] === 20000 ? 'Set Price Range' :
                                    `$${priceRangeValues[0]} - $${priceRangeValues[1]}` }}
                            </span>
                            <img :src="CaretDown" alt="Caret Down"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
                                :class="{ 'rotate-180': showPriceSlider }" />
                        </button>
                        <!-- Price Range Slider Dropdown -->
                        <div v-if="showPriceSlider"
                            class="absolute z-20 mt-2 w-[20rem] h-[12rem] bg-white shadow-xl rounded-lg p-5 border border-gray-100 animate-fade-in">
                            <div class="mb-4">
                                <div class="flex justify-between mb-3">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-500">Minimum</span>
                                        <span class="text-lg font-semibold text-gray-800">
                                            {{ tempPriceRangeValues[0] }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm text-gray-500">Maximum</span>
                                        <span class="text-lg font-semibold text-gray-800">
                                            {{ tempPriceRangeValues[1] }}
                                        </span>
                                    </div>
                                </div>
                                <VueSlider v-model="tempPriceRangeValues" :min="priceRangeMin" :max="priceRangeMax"
                                    :interval="500" :tooltip="'none'" :height="8" :dot-size="20"
                                    :process-style="{ backgroundColor: '#153b4f', borderRadius: '4px' }"
                                    :rail-style="{ backgroundColor: '#e5e7eb', borderRadius: '4px' }"
                                    :dot-style="{ backgroundColor: '#ffffff', border: '2px solid #153b4f', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }"
                                    class="mb-4" />
                            </div>
                            <div class="flex justify-between items-center">
                                <button @click="resetPriceRange"
                                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-all duration-300">
                                    Reset
                                </button>
                                <button @click="applyPriceRange"
                                    class="px-3 py-1.5 bg-customPrimaryColor text-white rounded-md text-sm font-medium hover:bg-opacity-90 transition-all duration-300">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Color Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Car Color</div>
                    <CustomDropdown v-model="form.color" unique-id="color"
                        :options="[...$page.props.colors.map(color => ({ value: color, label: color })), { value: '', label: 'Any Color' }]"
                        placeholder="Any Color" :left-icon="colorIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Mileage Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Fuel Efficiency</div>
                    <CustomDropdown v-model="form.mileage" unique-id="mileage"
                        :options="[{ value: '', label: 'Any Mileage' }, ...$page.props.mileages.map(mileage => ({ value: mileage, label: `${mileage} km/liter` }))]"
                        placeholder="Any Mileage" :left-icon="mileageIcon2" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Package Type Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Rental Period</div>
                    <CustomDropdown v-model="form.package_type" unique-id="package-type" :options="[
                        { value: 'day', label: 'Daily Rate' },
                        { value: 'week', label: 'Weekly Rate' },
                        { value: 'month', label: 'Monthly Rate' }
                    ]" placeholder="Daily Rate" :left-icon="priceperdayicon" :right-icon="CaretDown"
                    class="hover:border-customPrimaryColor bg-customPrimaryColor/5 transition-all duration-300" />
                </div>
                
                <!-- Search Button -->
                <!-- <div class="filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">&nbsp;</div>
                    <button @click="applyFilters" type="button"
                        class="h-10 bg-customPrimaryColor text-white py-2 px-5 rounded-lg font-medium hover:bg-opacity-90 transition-all duration-300 flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        Search Cars
                    </button>
                </div> -->
            </div>
        </form>

        <!-- Mobile Filters Canvas/Sidebar -->
        <div v-if="showMobileFilters" class="fixed inset-0 bg-black bg-opacity-50 z-50 md:hidden"
            @click="showMobileFilters = false">
            <div class="fixed inset-x-0 bottom-0 max-h-[85%] bg-white rounded-t-xl overflow-y-auto p-5 pt-0" @click.stop>
                <div class="flex justify-between items-center mb-4 sticky z-50 top-0 bg-white pb-3 border-b border-gray-100 py-4">
                    <div class="flex items-center gap-2">
                        <img :src="filterIcon" alt="" class="w-5 h-5" />
                        <h2 class="text-xl font-medium">Search Options</h2>
                    </div>
                    <button @click="showMobileFilters = false" class="p-2 bg-gray-100 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-5 filter-slot py-[1rem]">
                    <!-- Seating Capacity Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Passenger Seats</label>
                        <CustomDropdown v-model="form.seating_capacity" unique-id="seating-capacity-mobile"
                            :options="$page.props.seatingCapacities.map(capacity => ({ value: capacity, label: capacity + ' Seats' }))"
                            placeholder="Any Capacity" :left-icon="seatingIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Brand Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Car Brand</label>
                        <CustomDropdown v-model="form.brand" unique-id="brand-mobile"
                            :options="[...$page.props.brands.map(brand => ({ value: brand, label: brand })), { value: '', label: 'Any Brand' }]"
                            placeholder="Any Brand" :left-icon="brandIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Transmission Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Transmission Type</label>
                        <CustomDropdown v-model="form.transmission" unique-id="transmission-mobile"
                            :options="[{ value: '', label: 'Any Type' }, ...$page.props.transmissions.map(transmission => ({ value: transmission, label: transmission.charAt(0).toUpperCase() + transmission.slice(1) }))]"
                            placeholder="Any Type" :left-icon="transmissionIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Fuel Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Fuel Type</label>
                        <CustomDropdown v-model="form.fuel" unique-id="fuel-mobile"
                            :options="[{ value: '', label: 'Any Fuel' }, ...$page.props.fuels.map(fuel => ({ value: fuel, label: fuel.charAt(0).toUpperCase() + fuel.slice(1) }))]"
                            placeholder="Any Fuel" :left-icon="fuelIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Budget</label>
                        <div class="relative w-full">
                        <img :src="priceIcon" alt="Price Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none text-gray-500" />
                        <button type="button" @click="showPriceSlider = !showPriceSlider"
                            class="pl-10 pr-4 py-2 w-full text-left flex gap-4 items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm hover:border-customPrimaryColor transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor/20">
                            <span class="text-gray-700 font-medium">
                                {{ priceRangeValues[0] === 0 && priceRangeValues[1] === 20000 ? 'Set Price Range' :
                                    `$${priceRangeValues[0]} - $${priceRangeValues[1]}` }}
                            </span>
                            <img :src="CaretDown" alt="Caret Down"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
                                :class="{ 'rotate-180': showPriceSlider }" />
                        </button>
                        <!-- Price Range Slider Dropdown -->
                        <div v-if="showPriceSlider"
                            class="absolute z-20 mt-2 w-full h-[12rem] bg-white shadow-xl rounded-lg p-5 border border-gray-100 animate-fade-in">
                            <div class="mb-4">
                                <div class="flex justify-between mb-3">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-500">Minimum</span>
                                        <span class="text-lg font-semibold text-gray-800">
                                            {{ tempPriceRangeValues[0] }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm text-gray-500">Maximum</span>
                                        <span class="text-lg font-semibold text-gray-800">
                                            {{ tempPriceRangeValues[1] }}
                                        </span>
                                    </div>
                                </div>
                                <VueSlider v-model="tempPriceRangeValues" :min="priceRangeMin" :max="priceRangeMax"
                                    :interval="500" :tooltip="'none'" :height="8" :dot-size="20"
                                    :process-style="{ backgroundColor: '#153b4f', borderRadius: '4px' }"
                                    :rail-style="{ backgroundColor: '#e5e7eb', borderRadius: '4px' }"
                                    :dot-style="{ backgroundColor: '#ffffff', border: '2px solid #153b4f', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }"
                                    class="mb-4" />
                            </div>
                            <div class="flex justify-between items-center">
                                <button @click="resetPriceRange"
                                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-all duration-300">
                                    Reset
                                </button>
                                <button @click="applyPriceRange"
                                    class="px-3 py-1.5 bg-customPrimaryColor text-white rounded-md text-sm font-medium hover:bg-opacity-90 transition-all duration-300">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- Color Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Car Color</label>
                        <CustomDropdown v-model="form.color" unique-id="color-mobile"
                            :options="[...$page.props.colors.map(color => ({ value: color, label: color })), { value: '', label: 'Any Color' }]"
                            placeholder="Any Color" :left-icon="colorIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Mileage Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Fuel Efficiency</label>
                        <CustomDropdown v-model="form.mileage" unique-id="mileage-mobile"
                            :options="[{ value: '', label: 'Any Mileage' }, ...$page.props.mileages.map(mileage => ({ value: mileage, label: `${mileage} km/liter` }))]"
                            placeholder="Any Mileage" :left-icon="mileageIcon2" :right-icon="CaretDown" />
                    </div>

                    <!-- Package Type Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Rental Period</label>
                        <CustomDropdown v-model="form.package_type" unique-id="package-type-mobile" :options="[
                            { value: 'day', label: 'Daily Rate' },
                            { value: 'week', label: 'Weekly Rate' },
                            { value: 'month', label: 'Monthly Rate' }
                        ]" placeholder="Daily Rate" :left-icon="priceperdayicon" :right-icon="CaretDown" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-3 pt-3 mt-4 border-t border-gray-100">
                        <button @click="resetFilters" type="button"
                            class="py-3 px-4 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-all duration-300 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Clear All
                        </button>
                        <button @click="applyFilters" type="button"
                            class="py-3 px-4 bg-customPrimaryColor text-white rounded-lg font-medium hover:bg-opacity-90 transition-all duration-300 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                            Search Cars
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
       
    </div>
</section>

    <div class="full-w-container flex justify-end max-[768px]:hidden">
        <div class="flex items-center space-x-2 mb-[2rem]">
            <Label for="mapToggle" class="text-customPrimaryColor">Map</Label>
            <Switch id="mapToggle" :checked="showMap" @update:checked="handleMapToggle" />
        </div>
    </div>

    <div class="full-w-container mx-auto mb-[4rem]">
        <div class="flex gap-4 max-[768px]:flex-col">
            <!-- Left Column - Vehicle List -->
            <div class="w-full">
                <div :class="[
                    'grid gap-5',
                    showMap ? 'w-full grid-cols-2' : 'w-full grid-cols-4',
                ]" class="max-[768px]:grid-cols-1">
                    <div v-if="!vehicles.data || vehicles.data.length === 0"
                        class="text-center text-gray-500 col-span-2 flex flex-col justify-center items-center gap-4">
                        <img :src=noVehicleIcon alt="" class="w-[25rem] max-[768px]:w-full">
                        <p class="text-lg font-medium text-customPrimaryColor">No vehicles available at the moment</p>
                        <span>Search for another location</span>
                        <strong>Or</strong>
                        <span>Try to reduce the number of search filters.</span>
                        <button @click="resetFilters"
                            class="mt-4 px-6 py-2 bg-customPrimaryColor text-white rounded-lg hover:bg-opacity-90 transition">
                            Reset All Filters
                        </button>
                    </div>
                    <div v-for="vehicle in vehicles.data" :key="vehicle.id"
                        class="rounded-[12px] border-[1px] border-[#E7E7E7] relative overflow-hidden">
                        <div class="flex justify-end mb-3 absolute right-3 top-3">
                            <div class="column flex justify-end">
                                <button @click.stop="toggleFavourite(vehicle)"
                                    class="heart-icon bg-white rounded-[99px] p-2" :class="{
                                        'filled-heart':
                                            favoriteStatus[vehicle.id],
                                        'pop-animation': popEffect[vehicle.id], // Apply animation class dynamically
                                    }">
                                    <img :src="favoriteStatus[vehicle.id]
                                        ? FilledHeart
                                        : Heart
                                        " alt="Favorite" class="w-[1.5rem] transition-colors duration-300" />
                                </button>
                            </div>
                        </div>
                        <a
                            :href="`/vehicle/${vehicle.id}?package=${form.package_type}&pickup_date=${form.date_from}&return_date=${form.date_to}`">
                            <div class="column flex flex-col gap-5 items-start">
                                <img v-if="vehicle.images" :src="`${vehicle.images.find(
                                    (image) =>
                                        image.image_type === 'primary'
                                )?.image_url
                                    }`" alt="Primary Image"
                                    class="w-full h-[250px] object-cover rounded-tl-lg rounded-tr-lg max-[768px]:h-[200px]" />
                                <span
                                    class="bg-[#f5f5f5] ml-[1rem] inline-block px-8 py-2 text-center rounded-[40px] max-[768px]:text-[0.95rem]">
                                    {{ vehicle.model }}
                                </span>
                            </div>

                            <div class="column p-[1rem]">
                                <h5 class="font-medium text-[1.5rem] text-customPrimaryColor max-[768px]:text-[1.2rem]">
                                    {{ vehicle.brand }}
                                </h5>

                                <!-- Add Reviews Here -->
                                <div class="reviews mt-[1rem] flex gap-2 items-center">
                                    <div class="flex items-center gap-1">
                                        <img v-for="n in 5" :key="n" :src="getStarIcon(
                                            vehicle.review_count > 0
                                                ? vehicle.average_rating
                                                : 0,
                                            n
                                        )
                                            " :alt="getStarAltText(
                                                vehicle.review_count > 0
                                                    ? vehicle.average_rating
                                                    : 0,
                                                n
                                            )
                                                " class="w-[16px] h-[16px]" />
                                    </div>
                                    <span class="text-[1rem]" v-if="vehicle.review_count > 0">
                                        {{
                                            vehicle.average_rating.toFixed(1)
                                        }}
                                        ({{ vehicle.review_count }})
                                    </span>
                                    <span class="text-[1rem] text-gray-500" v-else>No reviews</span>
                                </div>
                                <div class="car_short_info mt-[1rem] flex gap-3">
                                    <img :src="carIcon" alt="" />
                                    <div class="features">
                                        <span class="capitalize text-[1.15rem] max-[768px]:text-[1rem]">{{
                                            vehicle.transmission }} .
                                            {{ vehicle.fuel }} .
                                            {{ vehicle.seating_capacity }}
                                            Seats</span>
                                    </div>
                                </div>
                                <div class="extra_details flex gap-5 mt-[1rem] items-center">
                                    <div class="col flex gap-3">
                                        <img :src="mileageIcon" alt="" /><span
                                            class="text-[1.15rem] max-[768px]:text-[0.95rem]">
                                            {{ vehicle.mileage }}km/d</span>
                                    </div>
                                    <!-- <div class="col flex gap-3" v-if="vehicle.distance_in_km !== undefined">
                                        <img :src="walkIcon" alt="" /><span
                                            class="text-[1.15rem] max-[768px]:text-[0.95rem]">
                                            {{ vehicle.distance_in_km.toFixed(1) }}km away</span>
                                    </div> -->
                                </div>


                                <div class="benefits mt-[2rem] grid grid-cols-2 gap-3">
                                    <!-- Free Cancellation based on the selected package type -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'day' &&
                                        vehicle.benefits
                                            .cancellation_available_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Free
                                        Cancellation ({{
                                            vehicle.benefits
                                                .cancellation_available_per_day_date
                                        }}
                                        days)
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'week' &&
                                        vehicle.benefits
                                            .cancellation_available_per_week
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Free
                                        Cancellation ({{
                                            vehicle.benefits
                                                .cancellation_available_per_week_date
                                        }}
                                        days)
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'month' &&
                                        vehicle.benefits
                                            .cancellation_available_per_month
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Free
                                        Cancellation ({{
                                            vehicle.benefits
                                                .cancellation_available_per_month_date
                                        }}
                                        days)
                                    </span>

                                    <!-- Mileage information based on the selected package type -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'day' &&
                                        !vehicle.benefits.limited_km_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Unlimited
                                        mileage
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'day' &&
                                        vehicle.benefits.limited_km_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Limited to
                                        {{
                                            vehicle.benefits
                                                .limited_km_per_day_range
                                        }}
                                        km/day
                                    </span>

                                    <span v-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'week' &&
                                        !vehicle.benefits
                                            .limited_km_per_week
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Unlimited
                                        mileage
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'week' &&
                                        vehicle.benefits.limited_km_per_week
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Limited to
                                        {{
                                            vehicle.benefits
                                                .limited_km_per_week_range
                                        }}
                                        km/week
                                    </span>

                                    <span v-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'month' &&
                                        !vehicle.benefits
                                            .limited_km_per_month
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Unlimited
                                        mileage
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'month' &&
                                        vehicle.benefits
                                            .limited_km_per_month
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Limited to
                                        {{
                                            vehicle.benefits
                                                .limited_km_per_month_range
                                        }}
                                        km/month
                                    </span>

                                    <!-- Additional cost per km if applicable -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'day' &&
                                        vehicle.benefits
                                            .price_per_km_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />{{
                                            vehicle.benefits
                                                .price_per_km_per_day
                                        }}/km extra above limit
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'week' &&
                                        vehicle.benefits
                                            .price_per_km_per_week
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />{{
                                            vehicle.benefits
                                                .price_per_km_per_week
                                        }}/km extra above limit
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        filters.package_type === 'month' &&
                                        vehicle.benefits
                                            .price_per_km_per_month
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />{{
                                            vehicle.benefits
                                                .price_per_km_per_month
                                        }}/km extra above limit
                                    </span>

                                    <!-- Minimum driver age if applicable -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.minimum_driver_age
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" />Min age:
                                        {{
                                            vehicle.benefits.minimum_driver_age
                                        }}
                                        years
                                    </span>
                                </div>

                                <div class="mt-[2rem] flex justify-between items-center">
                                    <div>
                                        <span
                                            class="text-customPrimaryColor text-[1.875rem] font-medium max-[768px]:text-[1.3rem] max-[768px]:font-bold">{{
                                                vehicle.vendor_profile.currency
                                            }}{{ vehicle[priceField] }}</span><span>/{{ priceUnit }}</span>
                                    </div>
                                    <img :src="goIcon" alt="" class="max-[768px]:w-[35px]" />
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
            <div class="w-full sticky top-4 h-[calc(100vh-2rem)] max-[768px]:hidden" v-show="showMap">
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
    width: auto;
    min-width: fit-content;
    height: 30px;
    background: white;
    border: 2px solid #666;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transform: translate3d(0, 0, 1000px);
}

.marker-pin span {
    color: black;
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

.leaflet-marker-icon {
    transform: translate3d(0, 0, 1000px);
}

.leaflet-popup {
    z-index: 1001 !important;
}

/* Hardware acceleration */
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-popup {
    will-change: transform;
    transform: translate3d(0, 0, 0);
}

/* Additional styles to ensure markers are always visible */
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

.marker-cluster-small div {
    box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
}

.marker-cluster-small div::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 40px;
    height: 40px;
    background-image: url('../../assets/carmarkerIcon.svg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: white;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
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

.filter-slot select{
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
