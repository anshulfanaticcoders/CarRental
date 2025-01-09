<script setup>
import { Link } from '@inertiajs/vue3'
import { onMounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'
import Footer from '@/Components/Footer.vue'

const props = defineProps({
  vehicles: Object,
  filters: Object
})

let map = null
let markers = []

const initMap = () => {
  // Calculate bounds for all vehicles
  const bounds = L.latLngBounds(props.vehicles.data.map(vehicle => [
    vehicle.latitude,
    vehicle.longitude
  ]))

  map = L.map('map', {
    zoomSnap: 0.25,
    markerZoomAnimation: false,
    preferCanvas: true,
    zoomControl: true,
    maxZoom: 18,
    minZoom: 4
  })

  // Set initial view to fit all markers
  map.fitBounds(bounds, {
    padding: [50, 50],
    maxZoom: 12
  })
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)

  // Create a custom pane for markers with high z-index
  map.createPane('markers')
  map.getPane('markers').style.zIndex = 1000

  addMarkers()

  // Force a map refresh after a short delay
  setTimeout(() => {
    map.invalidateSize()
    map.fitBounds(bounds, {
      padding: [50, 50],
      maxZoom: 12
    })
  }, 100)
}

const createCustomIcon = (price) => {
  return L.divIcon({
    className: 'custom-div-icon',
    html: `
      <div class="marker-pin">
        <span>₹${price}</span>  
      </div>
    `,
    iconSize: [50, 30],
    iconAnchor: [25, 15],
    popupAnchor: [0, -15],
    pane: 'markers'
  })
}

const addMarkers = () => {
  markers.forEach(marker => marker.remove())
  markers = []

  // Create a feature group for markers
  const markerGroup = L.featureGroup()

  props.vehicles.data.forEach(vehicle => {
    const marker = L.marker([vehicle.latitude, vehicle.longitude], {
      icon: createCustomIcon(vehicle.price_per_day),
      pane: 'markers'
    })
    .bindPopup(`
      <div class="text-center">
        <h3 class="font-semibold">${vehicle.brand}</h3>
        <p class="font-semibold">€${vehicle.price_per_day} /day</p>
        <a href="/vehicle/${vehicle.id}" 
           class="text-blue-500 hover:text-blue-700"
           onclick="window.location.href='/vehicle/${vehicle.id}'; return false;">
          View Details
        </a>
      </div>
    `)
    
    markerGroup.addLayer(marker)
    markers.push(marker)
  })

  markerGroup.addTo(map)

  // Fit bounds after adding markers
  const groupBounds = markerGroup.getBounds()
  map.fitBounds(groupBounds, {
    padding: [50, 50],
    maxZoom: 12
  })
}

// Watch for changes in vehicles data
watch(() => props.vehicles, () => {
  if (map) {
    addMarkers()
  }
}, { deep: true })

onMounted(() => {
  initMap()
})
</script>

<template>
  <AuthenticatedHeaderLayout/>
  <div class="container mx-auto p-4">
      <div class="flex gap-4">
          <!-- Left Column - Vehicle List -->
          <div class="w-1/2 space-y-4">
            <div
            v-for="vehicle in vehicles.data"
            :key="vehicle.id"
            class="max-w-[370px] p-[2rem] rounded-[12px] border-[1px] border-[#E7E7E7]"
        >
            <div class="column flex justify-end">
                <Link href=""><img :src="Heart" alt="" class="w-full" /></Link>
            </div>
            <Link :href="`/vehicle/${vehicle.id}`">
                <div class="column flex flex-col gap-5 items-start h-[20rem]">
                    <img
                            v-if="vehicle.images"
                            :src="`/storage/${
                                vehicle.images.find(
                                    (image) =>
                                        image.image_type === 'primary'
                                )?.image_path
                            }`"
                            alt="Primary Image"
                            class="w-full h-full object-cover rounded-lg"
                        />
                    <span
                        class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]"
                    >
                        {{ vehicle.model }}
                    </span>
                </div>

                <div class="column mt-[2rem]">
                    <h5 class="font-medium text-[1.5rem] text-customPrimaryColor">
                        {{ vehicle.brand }}
                    </h5>
                    <div class="car_short_info mt-[1rem] flex gap-3">
                        <img :src="carIcon" alt="" />
                        <div class="features">
                            <span class="capitalize text-[1.15rem]"
                                >{{ vehicle.transmission }} . {{ vehicle.fuel }} . {{ vehicle.seating_capacity }}
                                Seats</span
                            >
                        </div>
                    </div>
                    <div class="extra_details flex gap-5 mt-[1rem]">
                        <div class="col flex gap-3">
                            <img :src="walkIcon" alt="" /><span class="text-[1.15rem]">9.3 KM Away</span>
                        </div>
                        <div class="col flex gap-3">
                            <img :src="mileageIcon" alt="" /><span class="text-[1.15rem]">{{ vehicle.mileage }}km/d</span>
                        </div>
                    </div>

                    <div class="benefits mt-[2rem] grid grid-cols-2 gap-3">
                        <span class="flex gap-3 items-center text-[12px]">
                            <img :src="check" alt="" />Free Cancellation
                        </span>
                        <span class="flex gap-3 items-center text-[12px]">
                            <img :src="check" alt="" />Unlimited mileage
                        </span>
                        <span class="flex gap-3 items-center text-[12px]">
                            <img :src="check" alt="" />Unlimited kilometers
                        </span>
                    </div>

                    <div class="mt-[2rem] flex justify-between items-center">
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
                  <Pagination :links="vehicles.links" />

          </div>

          <!-- Right Column - Map -->
          <div class="w-1/2 sticky top-4 h-[calc(100vh-2rem)]">
              <div class="bg-white rounded-lg shadow h-full">
                  <div id="map" class="h-full rounded-lg"></div>
              </div>
          </div>
      </div>
  </div>
  <Footer/>
</template>

<style>
@import 'leaflet/dist/leaflet.css';

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
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  transform: translate3d(0,0,1000px);
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
  transform: translate3d(0,0,1000px);
}

.leaflet-popup {
  z-index: 1001 !important;
}

/* Hardware acceleration */
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-popup {
  will-change: transform;
  transform: translate3d(0,0,0);
}

/* Additional styles to ensure markers are always visible */
.leaflet-container {
  z-index: 1;
}

.leaflet-control-container {
  z-index: 2000;
}
</style>