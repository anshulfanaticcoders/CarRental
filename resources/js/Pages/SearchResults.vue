<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";
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
import categoryIcon from "../../assets/categoryIcon.png";
import priceperdayicon from "../../assets/priceFilter.png";
import fuelIcon from "../../assets/fuel.svg";
import transmissionIcon from "../../assets/transmittionIcon.svg";
import mileageIcon2 from "../../assets/unlimitedKm.svg";
import walkIcon from "../../assets/walking.svg";
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
    form.get('/s', {
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
    <div class="marker-pin">
      <span>${currency || "₹"}${price}</span>
    </div>
  `,
        iconSize: [50, 30],
        iconAnchor: [25, 15],
        popupAnchor: [0, -15],
        pane: "markers",
    });
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
                    class="flex items-center gap-2 p-2 bg-white rounded-lg shadow">
                    <img :src="filterIcon" alt="Filter" />
                    <span class="text-lg">Filters</span>
                </button>
            </div>

            <!-- Desktop filter header (hidden on mobile) -->
            <div class="hidden md:flex items-center gap-3 mb-8">
                <img :src="filterIcon" alt="" />
                <span class="text-[1.5rem]">Filters</span>
            </div>

            <!-- Desktop filters (hidden on mobile) -->
            <form class="hidden md:block">
                <div class="flex gap-6 flex-wrap filter-slot items-center">
                    <!-- Seating Capacity Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="seatingIcon" alt="Seating Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.seating_capacity" id="seating_capacity"
                            class="pl-10 py-2 pr-10 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">Seating Capacity</option>
                            <option v-for="capacity in $page.props
                                .seatingCapacities" :key="capacity" :value="capacity">
                                {{ capacity }}
                            </option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Brand Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="brandIcon" alt="Brand Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.brand" id="brand"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">All Brands</option>
                            <option v-for="brand in $page.props.brands" :key="brand" :value="brand">
                                {{ brand }}
                            </option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Category Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="categoryIcon" alt="Category Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.category_id" id="category_id"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">All Categories</option>
                            <option v-for="category in $page.props.categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Transmission Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="transmissionIcon" alt="Transmission Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.transmission" id="transmission"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">Transmission</option>
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual</option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Fuel Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="fuelIcon" alt="Fuel Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.fuel" id="fuel"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">Fuel Type</option>
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Price Range Filter -->
                    <div class="relative w-full md:w-64">
                        <img :src="priceIcon" alt="Price Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none text-gray-500" />
                        <button type="button" @click="showPriceSlider = !showPriceSlider"
                            class="pl-10 pr-4 py-2 w-full text-left flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <span class="text-gray-700 font-medium">
                                {{ priceRangeValues[0] === 0 && priceRangeValues[1] === 20000 ? 'Price Range' :
                                    `${priceRangeValues[0]} - ${priceRangeValues[1]}` }}
                            </span>
                            <img :src="CaretDown" alt="Caret Down"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
                                :class="{ 'rotate-180': showPriceSlider }" />
                        </button>

                        <!-- Price Range Slider Dropdown -->
                        <div v-if="showPriceSlider"
                            class="absolute z-20 mt-2 w-full bg-white shadow-xl rounded-lg p-5 border border-gray-100 animate-fade-in">
                            <div class="mb-4">
                                <div class="flex justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-800">{{ tempPriceRangeValues[0]
                                        }}</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ tempPriceRangeValues[1]
                                        }}</span>
                                </div>
                                <VueSlider v-model="tempPriceRangeValues" :min="priceRangeMin" :max="priceRangeMax"
                                    :interval="500" :tooltip="'none'" :height="8" :dot-size="20"
                                    :process-style="{ backgroundColor: '#3b82f6', borderRadius: '4px' }"
                                    :rail-style="{ backgroundColor: '#e5e7eb', borderRadius: '4px' }"
                                    :dot-style="{ backgroundColor: '#ffffff', border: '2px solid #3b82f6', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }"
                                    class="mb-4" />
                            </div>
                            <div class="flex justify-between items-center">
                                <button @click="resetPriceRange"
                                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors duration-200">
                                    Reset
                                </button>
                                <button @click="applyPriceRange"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Color Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="colorIcon" alt="Color Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.color" id="color"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">Color</option>
                            <option v-for="color in $page.props.colors" :key="color" :value="color">
                                {{ color }}
                            </option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Mileage Filter -->
                    <div class="relative w-full md:w-auto">
                        <img :src="mileageIcon2" alt="Mileage Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.mileage" id="mileage"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="">Mileage</option>
                            <option value="0-10">0 - 10 km/d</option>
                            <option value="10-20">10 - 20 km/d</option>
                            <option value="20-30">20 - 30 km/d</option>
                            <option value="30-40">30 - 40 km/d</option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>

                    <!-- Package Type Filter -->
                    <div class="relative w-full md:w-auto">
                        <!-- Assuming you need an icon for package type -->
                        <img :src="priceperdayicon" alt="Package Type Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                        <select v-model="form.package_type" id="package_type"
                            class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                            <option value="day">Price Per Day</option>
                            <option value="week">Price Per Week</option>
                            <option value="month">Price Per Month</option>
                        </select>
                        <img :src="CaretDown" alt=""
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                    </div>
                </div>
            </form>

            <!-- Mobile Filters Canvas/Sidebar -->
            <div v-if="showMobileFilters" class="fixed inset-0 bg-black bg-opacity-50 z-50 md:hidden"
                @click="showMobileFilters = false">
                <div class="fixed right-0 top-0 h-full w-4/5 bg-white overflow-y-auto p-4" @click.stop>
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-2">
                            <img :src="filterIcon" alt="" />
                            <h2 class="text-xl font-medium">Filters</h2>
                        </div>
                        <button @click="showMobileFilters = false" class="text-2xl">
                            &times;
                        </button>
                    </div>

                    <form class="space-y-6 filter-slot">
                        <!-- Seating Capacity Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="seatingIcon" alt="Seating Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.seating_capacity" id="seating_capacity"
                                class="pl-10 py-2 pr-10 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">Seating Capacity</option>
                                <option v-for="capacity in $page.props
                                    .seatingCapacities" :key="capacity" :value="capacity">
                                    {{ capacity }}
                                </option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Brand Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="brandIcon" alt="Brand Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.brand" id="brand"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">All Brands</option>
                                <option v-for="brand in $page.props.brands" :key="brand" :value="brand">
                                    {{ brand }}
                                </option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Category Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="categoryIcon" alt="Category Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.category_id" id="category_id"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">All Categories</option>
                                <option v-for="category in $page.props.categories" :key="category.id"
                                    :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Transmission Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="transmissionIcon" alt="Transmission Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.transmission" id="transmission"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">Transmission</option>
                                <option value="automatic">Automatic</option>
                                <option value="manual">Manual</option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Fuel Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="fuelIcon" alt="Fuel Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.fuel" id="fuel"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">Fuel Type</option>
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Price Range Filter -->
                    <div class="relative w-full md:w-64">
                        <img :src="priceIcon" alt="Price Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none text-gray-500" />
                        <button type="button" @click="showPriceSlider = !showPriceSlider"
                            class="pl-10 pr-4 py-2 w-full text-left flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <span class="text-gray-700 font-medium">
                                {{ priceRangeValues[0] === 0 && priceRangeValues[1] === 20000 ? 'Price Range' :
                                    `${priceRangeValues[0]} - ${priceRangeValues[1]}` }}
                            </span>
                            <img :src="CaretDown" alt="Caret Down"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
                                :class="{ 'rotate-180': showPriceSlider }" />
                        </button>

                        <!-- Price Range Slider Dropdown -->
                        <div v-if="showPriceSlider"
                            class="absolute z-20 mt-2 w-full bg-white shadow-xl rounded-lg p-5 border border-gray-100 animate-fade-in">
                            <div class="mb-4">
                                <div class="flex justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-800">{{ tempPriceRangeValues[0]
                                        }}</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ tempPriceRangeValues[1]
                                        }}</span>
                                </div>
                                <VueSlider v-model="tempPriceRangeValues" :min="priceRangeMin" :max="priceRangeMax"
                                    :interval="500" :tooltip="'none'" :height="8" :dot-size="20"
                                    :process-style="{ backgroundColor: '#3b82f6', borderRadius: '4px' }"
                                    :rail-style="{ backgroundColor: '#e5e7eb', borderRadius: '4px' }"
                                    :dot-style="{ backgroundColor: '#ffffff', border: '2px solid #3b82f6', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }"
                                    class="mb-4" />
                            </div>
                            <div class="flex justify-between items-center">
                                <button @click="resetPriceRange"
                                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors duration-200">
                                    Reset
                                </button>
                                <button @click="applyPriceRange"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>

                        <!-- Color Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="colorIcon" alt="Color Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.color" id="color"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">Color</option>
                                <option v-for="color in $page.props.colors" :key="color" :value="color">
                                    {{ color }}
                                </option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Mileage Filter -->
                        <div class="relative w-full md:w-auto">
                            <img :src="mileageIcon2" alt="Mileage Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.mileage" id="mileage"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="">Mileage</option>
                                <option value="0-10">0 - 10 km/l</option>
                                <option value="10-20">10 - 20 km/l</option>
                                <option value="20-30">20 - 30 km/l</option>
                                <option value="30-40">30 - 40 km/l</option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Package Type Filter -->
                        <div class="relative w-full md:w-auto">
                            <!-- Assuming you need an icon for package type -->
                            <img :src="priceperdayicon" alt="Package Type Icon"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none" />
                            <select v-model="form.package_type" id="package_type"
                                class="pl-10 pr-10 py-2 cursor-pointer border border-[#e7e7e7] rounded-sm w-full">
                                <option value="day">Price Per Day</option>
                                <option value="week">Price Per Week</option>
                                <option value="month">Price Per Month</option>
                            </select>
                            <img :src="CaretDown" alt=""
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 transition-transform duration-300 ease-in-out pointer-events-none caret-rotate" />
                        </div>

                        <!-- Apply Filters Button -->
                        <button @click="applyFilters"
                            class="w-full bg-customPrimaryColor text-white py-3 px-4 rounded-lg font-medium hover:bg-opacity-90 transition">
                            Apply Filters
                        </button>
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
                    <div v-if="!vehicles.data || vehicles.data.length === 0" class="text-center text-gray-500">
                        No vehicles available at the moment.
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

<style scoped>
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
select{
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
