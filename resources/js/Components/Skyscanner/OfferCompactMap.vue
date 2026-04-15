<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import { resolveOfferMapDetails } from '@/features/skyscanner/utils/offerMapDetails'

type LocationDetails = {
  name?: string | null
  latitude?: number | string | null
  longitude?: number | string | null
}

const props = defineProps<{
  pickupLocation?: LocationDetails
  dropoffLocation?: LocationDetails
}>()

const mapRef = ref<HTMLElement | null>(null)
let map: L.Map | null = null

const mapDetails = computed(() => resolveOfferMapDetails(props.pickupLocation ?? {}, props.dropoffLocation ?? {}))

const createMarker = (color: string, label: string) => L.divIcon({
  className: 'or-offer-map-marker',
  html: `<div class="or-offer-map-pin" style="--pin-color:${color}">
      <span class="or-offer-map-label">${label}</span>
      <span class="or-offer-map-dot"></span>
    </div>`,
  iconSize: [76, 40],
  iconAnchor: [38, 40],
})

const destroyMap = () => {
  if (map) {
    map.remove()
    map = null
  }
}

const initMap = () => {
  if (!mapRef.value || !mapDetails.value.hasMap || !mapDetails.value.pickup) {
    destroyMap()
    return
  }

  destroyMap()

  map = L.map(mapRef.value, {
    zoomControl: false,
    attributionControl: false,
    dragging: false,
    scrollWheelZoom: false,
    doubleClickZoom: false,
    boxZoom: false,
    keyboard: false,
    tapHold: false,
    touchZoom: false,
  })

  const stadiaKey = import.meta.env.VITE_STADIA_MAPS_API_KEY || ''
  const keyParam = stadiaKey ? `?api_key=${stadiaKey}` : ''

  L.tileLayer(`https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.png${keyParam}`, {
    maxZoom: 20,
  }).addTo(map)

  const pickup = mapDetails.value.pickup
  const pickupLatLng: [number, number] = [pickup.latitude, pickup.longitude]

  L.marker(pickupLatLng, { icon: createMarker('#059669', 'Pickup') }).addTo(map)

  if (mapDetails.value.hasDropoff && mapDetails.value.dropoff) {
    const dropoff = mapDetails.value.dropoff
    const dropoffLatLng: [number, number] = [dropoff.latitude, dropoff.longitude]

    L.marker(dropoffLatLng, { icon: createMarker('#dc2626', 'Dropoff') }).addTo(map)
    L.polyline([pickupLatLng, dropoffLatLng], {
      color: '#153b4f',
      weight: 3,
      opacity: 0.8,
      dashArray: '6, 8',
    }).addTo(map)
    map.fitBounds([pickupLatLng, dropoffLatLng], {
      padding: [24, 24],
      animate: false,
    })
  } else {
    map.setView(pickupLatLng, 12)
  }

  nextTick(() => {
    map?.invalidateSize()
  })
}

watch(mapDetails, () => {
  nextTick(initMap)
}, { deep: true })

onMounted(() => {
  nextTick(initMap)
})

onUnmounted(() => {
  destroyMap()
})
</script>

<template>
  <div v-if="mapDetails.hasMap" class="or-offer-map-card">
    <div class="or-offer-map-head">
      <div>
        <p class="or-offer-map-eyebrow">Location map</p>
        <h3>{{ pickupLocation?.name || 'Pickup location' }}</h3>
      </div>
      <div v-if="mapDetails.hasDropoff" class="or-offer-map-legend">
        <span><i class="pickup"></i>Pickup</span>
        <span><i class="dropoff"></i>Drop-off</span>
      </div>
    </div>
    <div ref="mapRef" class="or-offer-map-canvas"></div>
  </div>
</template>

<style scoped>
.or-offer-map-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  height: 100%;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
  border-left: 1px solid #e2e8f0;
}

.or-offer-map-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  padding: 14px 14px 0;
}

.or-offer-map-eyebrow {
  margin: 0 0 4px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #94a3b8;
}

.or-offer-map-head h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #153b4f;
  line-height: 1.3;
}

.or-offer-map-legend {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
}

.or-offer-map-legend span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.or-offer-map-legend i {
  width: 8px;
  height: 8px;
  border-radius: 9999px;
  display: inline-block;
}

.or-offer-map-legend i.pickup { background: #059669; }
.or-offer-map-legend i.dropoff { background: #dc2626; }

.or-offer-map-canvas {
  flex: 1;
  min-height: 220px;
  border-top: 1px solid #eef2f7;
}

:global(.or-offer-map-marker) {
  background: transparent;
  border: 0;
}

:global(.or-offer-map-pin) {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

:global(.or-offer-map-label) {
  padding: 3px 8px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(21, 59, 79, 0.12);
  color: #153b4f;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
}

:global(.or-offer-map-dot) {
  width: 14px;
  height: 14px;
  border-radius: 9999px;
  background: var(--pin-color);
  border: 3px solid rgba(255, 255, 255, 0.98);
  box-shadow: 0 3px 10px rgba(15, 23, 42, 0.18);
}
</style>
