<script setup>
import { Link } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import FilterSlot from "@/Components/FilterSlot.vue";
import goIcon from "../../assets/goIcon.svg";
import carIcon from "../../assets/carIcon.svg";
import walkIcon from "../../assets/walking.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import Heart from "../../assets/Heart.svg";
import check from "../../assets/Check.svg";
import SearchBar from "@/Components/SearchBar.vue";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
const props = defineProps({
    vehicles: Object,
    filters: Object,
    pagination_links: String,
});

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

const createCustomIcon = (price) => {
    return L.divIcon({
        className: "custom-div-icon",
        html: `
    <div class="marker-pin">
      <span>₹${price}</span>
    </div>
  `,
        iconSize: [50, 30],
        iconAnchor: [25, 15],
        popupAnchor: [0, -15],
        pane: "markers",
    });
};

const addMarkers = () => {
    markers.forEach((marker) => marker.remove());
    markers = [];

    if (!props.vehicles.data || props.vehicles.data.length === 0) {
        console.warn("No vehicles data available to add markers.");
        return;
    }
    // Create a feature group for markers
    const markerGroup = L.featureGroup();

    props.vehicles.data.forEach((vehicle) => {
        const marker = L.marker([vehicle.latitude, vehicle.longitude], {
            icon: createCustomIcon(vehicle.price_per_day),
            pane: "markers",
        }).bindPopup(`
    <div class="text-center">
      <p class="font-semibold">${vehicle.brand}</p>
      <p class="">${vehicle.location}</p>
      <a href="/vehicle/${vehicle.id}" 
         class="text-blue-500 hover:text-blue-700"
         onclick="window.location.href='/vehicle/${vehicle.id}'; return false;">
        View Details
      </a>
    </div>
  `);

        markerGroup.addLayer(marker);
        markers.push(marker);
    });

    markerGroup.addTo(map);

    // Fit bounds after adding markers
    const groupBounds = markerGroup.getBounds();
    map.fitBounds(groupBounds, {
        padding: [50, 50],
        maxZoom: 12,
    });
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
const showMap = ref(true)

// Add a function to handle the toggle
const handleMapToggle = (value) => {
  showMap.value = value
  // Force map to refresh when showing it again
  if (value && map) {
    setTimeout(() => {
      map.invalidateSize()
    }, 100)
  }
}
</script>

<template>
    <AuthenticatedHeaderLayout />
    <section class="bg-customPrimaryColor py-customVerticalSpacing">
        <div class="full-w-container">
            <SearchBar
                class="border-[2px] rounded-[20px] border-white mt-0 mb-0"
            />
        </div>
    </section>

    <section>
        <div class="full-w-container py-customVerticalSpacing">
            <FilterSlot />
        </div>
    </section>

    <div class="full-w-container flex justify-end">
        <div class="flex items-center space-x-2 mb-[2rem]">
            <Label for="mapToggle" class="text-customPrimaryColor">Map</Label>
            <Switch
                id="mapToggle"
                :checked="showMap"
                @update:checked="handleMapToggle"
            />
        </div>
    </div>

    <div class="full-w-container mx-auto mb-[4rem]">
        <div class="flex gap-4">
            <!-- Left Column - Vehicle List -->
            <div class="w-full">
              <div :class="[
               'grid gap-5',
                  showMap ? 'w-full grid-cols-2' : 'w-full grid-cols-4'
                ]">
                <div
                    v-if="!vehicles.data || vehicles.data.length === 0"
                    class="text-center text-gray-500"
                >
                    No vehicles available at the moment.
                </div>
                <div
                    v-for="vehicle in vehicles.data"
                    :key="vehicle.id"
                    class="p-[1rem] rounded-[12px] border-[1px] border-[#E7E7E7]"
                >
                    <div class="column flex justify-end">
                        <Link href="" class="block"
                            ><img :src="Heart" alt="" class="w-full mb-[1rem]"
                        /></Link>
                    </div>
                    <Link :href="`/vehicle/${vehicle.id}`">
                        <div class="column flex flex-col gap-5 items-start">
                            <img
                                v-if="vehicle.images"
                                :src="`/storage/${
                                    vehicle.images.find(
                                        (image) =>
                                            image.image_type === 'primary'
                                    )?.image_path
                                }`"
                                alt="Primary Image"
                                class="w-full h-[250px] object-cover rounded-lg"
                            />
                            <span
                                class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                            >
                                {{ vehicle.model }}
                            </span>
                        </div>

                        <div class="column mt-[2rem]">
                            <h5
                                class="font-medium text-[1.5rem] text-customPrimaryColor"
                            >
                                {{ vehicle.brand }}
                            </h5>
                            <div class="car_short_info mt-[1rem] flex gap-3">
                                <img :src="carIcon" alt="" />
                                <div class="features">
                                    <span class="capitalize text-[1.15rem]"
                                        >{{ vehicle.transmission }} .
                                        {{ vehicle.fuel }} .
                                        {{
                                            vehicle.seating_capacity
                                        }}
                                        Seats</span
                                    >
                                </div>
                            </div>
                            <div class="extra_details flex gap-5 mt-[1rem]">
                                <div class="col flex gap-3">
                                    <img :src="walkIcon" alt="" /><span
                                        class="text-[1.15rem]"
                                        >9.3 KM Away</span
                                    >
                                </div>
                                <div class="col flex gap-3">
                                    <img :src="mileageIcon" alt="" /><span
                                        class="text-[1.15rem]"
                                        >{{ vehicle.mileage }}km/d</span
                                    >
                                </div>
                            </div>

                            <div
                                class="benefits mt-[2rem] grid grid-cols-2 gap-3"
                            >
                                <span
                                    class="flex gap-3 items-center text-[12px]"
                                >
                                    <img :src="check" alt="" />Free Cancellation
                                </span>
                                <span
                                    class="flex gap-3 items-center text-[12px]"
                                >
                                    <img :src="check" alt="" />Unlimited mileage
                                </span>
                                <span
                                    class="flex gap-3 items-center text-[12px]"
                                >
                                    <img :src="check" alt="" />Unlimited
                                    kilometers
                                </span>
                            </div>

                            <div
                                class="mt-[2rem] flex justify-between items-center"
                            >
                                <div>
                                    <span
                                        class="text-customPrimaryColor text-[1.875rem] font-medium"
                                        >€{{ vehicle.price_per_day }}</span
                                    ><span>/day</span>
                                </div>
                                <img :src="goIcon" alt="" />
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
            <!-- Pagination -->
            <div class="mt-4">
              <div v-html="pagination_links"></div>
            </div>
            </div>
            <!-- Right Column - Map -->
            <div
                class="w-full sticky top-4 h-[calc(100vh-2rem)]"
                v-show="showMap"
            >
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
    min-width: 50px;
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
.leaflet-pane.leaflet-marker-pane,
.leaflet-pane.leaflet-popup-pane {
    z-index: 1000 !important;
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
</style>
