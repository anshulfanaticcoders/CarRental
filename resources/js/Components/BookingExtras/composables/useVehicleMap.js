import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const isValidCoordinate = (coord) => {
    const num = parseFloat(coord);
    return !isNaN(num) && isFinite(num);
};

export function useVehicleMap(props) {
    const vehicleMapRef = ref(null);
    const mapModalRef = ref(null);
    const showMapModal = ref(false);

    let vehicleMap = null;
    let modalMap = null;

    const hasVehicleCoords = computed(() => {
        return isValidCoordinate(props.vehicle?.latitude) && isValidCoordinate(props.vehicle?.longitude);
    });

    // Prefer provider-reported dropoff coords (`vehicle.location.dropoff`) from the
    // gateway Vehicle payload over the form-supplied dropoff, because the form may
    // still hold the pickup coords when the user hasn't picked a distinct dropoff
    // from the search dropdown. Falls back to form-supplied values.
    const resolvedDropoffLat = computed(() => {
        const providerLat = props.vehicle?.location?.dropoff?.latitude;
        if (isValidCoordinate(providerLat)) return parseFloat(providerLat);
        if (isValidCoordinate(props.dropoffLatitude)) return parseFloat(props.dropoffLatitude);
        return null;
    });

    const resolvedDropoffLng = computed(() => {
        const providerLng = props.vehicle?.location?.dropoff?.longitude;
        if (isValidCoordinate(providerLng)) return parseFloat(providerLng);
        if (isValidCoordinate(props.dropoffLongitude)) return parseFloat(props.dropoffLongitude);
        return null;
    });

    const hasDropoffCoords = computed(() => {
        return resolvedDropoffLat.value !== null && resolvedDropoffLng.value !== null;
    });

    const isDifferentDropoff = computed(() => {
        if (!hasDropoffCoords.value || !hasVehicleCoords.value) return false;
        const pickupLat = parseFloat(props.vehicle.latitude);
        const pickupLng = parseFloat(props.vehicle.longitude);
        const dropLat = resolvedDropoffLat.value;
        const dropLng = resolvedDropoffLng.value;
        return Math.abs(pickupLat - dropLat) > 0.001 || Math.abs(pickupLng - dropLng) > 0.001;
    });

    const createMapIcon = (color, label, pulse = false) => {
        const pulseRing = pulse
            ? `<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:28px;height:28px;border-radius:50%;background:${color};opacity:0.2;animation:marker-pulse 2s ease-out infinite;"></div>`
            : '';
        return L.divIcon({
            className: '',
            html: `<div style="position:relative;display:flex;flex-direction:column;align-items:center;">
            <div style="background:${color};color:#fff;font-size:10px;font-weight:600;letter-spacing:0.3px;padding:3px 8px;border-radius:6px;white-space:nowrap;margin-bottom:4px;box-shadow:0 2px 8px ${color}40;backdrop-filter:blur(4px);">${label}</div>
            <div style="position:relative;display:flex;align-items:center;justify-content:center;">
                ${pulseRing}
                <svg width="16" height="16" viewBox="0 0 16 16" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                    <circle cx="8" cy="8" r="7" fill="${color}" stroke="#fff" stroke-width="2.5"/>
                </svg>
            </div>
        </div>`,
            iconSize: [70, 38],
            iconAnchor: [35, 38],
        });
    };

    const initVehicleMap = () => {
        if (!hasVehicleCoords.value || !vehicleMapRef.value) return;

        if (vehicleMap) {
            vehicleMap.remove();
            vehicleMap = null;
        }

        const pickupLat = parseFloat(props.vehicle.latitude);
        const pickupLng = parseFloat(props.vehicle.longitude);

        vehicleMap = L.map(vehicleMapRef.value, {
            zoomControl: false,
            maxZoom: 18,
            minZoom: 3,
            zoomSnap: 0.5,
            zoomDelta: 0.5,
            wheelPxPerZoomLevel: 120,
            zoomAnimation: true,
            fadeAnimation: true,
            markerZoomAnimation: true,
        });

        L.control.zoom({ position: 'topright' }).addTo(vehicleMap);

        const stadiaKey = import.meta.env.VITE_STADIA_MAPS_API_KEY || '';
        const keyParam = stadiaKey ? `?api_key=${stadiaKey}` : '';
        L.tileLayer(`https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.png${keyParam}`, {
            attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://openstreetmap.org/copyright">OSM</a>',
            maxZoom: 20,
        }).addTo(vehicleMap);

        const pickupLatLng = [pickupLat, pickupLng];

        if (isDifferentDropoff.value) {
            const dropoffLat = resolvedDropoffLat.value;
            const dropoffLng = resolvedDropoffLng.value;
            const dropoffLatLng = [dropoffLat, dropoffLng];

            const pickupIcon = createMapIcon('#059669', 'Pickup', true);
            const dropoffIcon = createMapIcon('#dc2626', 'Dropoff');

            L.marker(pickupLatLng, { icon: pickupIcon })
                .bindPopup(`<div style="font-family:system-ui;text-align:center;padding:4px 0;"><p style="font-weight:600;margin:0 0 2px;">Pickup</p><p style="margin:0;color:#6b7280;font-size:12px;">${props.pickupLocation || ''}</p></div>`, { className: 'rental-popup' })
                .addTo(vehicleMap);

            L.marker(dropoffLatLng, { icon: dropoffIcon })
                .bindPopup(`<div style="font-family:system-ui;text-align:center;padding:4px 0;"><p style="font-weight:600;margin:0 0 2px;">Dropoff</p><p style="margin:0;color:#6b7280;font-size:12px;">${props.dropoffLocation || ''}</p></div>`, { className: 'rental-popup' })
                .addTo(vehicleMap);

            const midLat = (pickupLat + dropoffLat) / 2;
            const midLng = (pickupLng + dropoffLng) / 2;
            const latDiff = Math.abs(pickupLat - dropoffLat);
            const lngDiff = Math.abs(pickupLng - dropoffLng);
            const curveOffset = Math.max(latDiff, lngDiff) * 0.15;
            const curvePoints = [];
            for (let t = 0; t <= 1; t += 0.05) {
                const lat = (1 - t) * (1 - t) * pickupLat + 2 * (1 - t) * t * (midLat + curveOffset) + t * t * dropoffLat;
                const lng = (1 - t) * (1 - t) * pickupLng + 2 * (1 - t) * t * midLng + t * t * dropoffLng;
                curvePoints.push([lat, lng]);
            }

            L.polyline(curvePoints, {
                color: '#6366f1',
                weight: 2.5,
                opacity: 0.5,
                dashArray: '8, 10',
                lineCap: 'round',
                lineJoin: 'round',
            }).addTo(vehicleMap);

            const bounds = L.latLngBounds([pickupLatLng, dropoffLatLng]);
            vehicleMap.fitBounds(bounds, { padding: [50, 50], animate: true, duration: 0.8 });
        } else {
            const icon = createMapIcon('#059669', 'Pickup', true);
            L.marker(pickupLatLng, { icon }).addTo(vehicleMap);
            vehicleMap.setView(pickupLatLng, 14, { animate: true, duration: 0.6 });
        }

        setTimeout(() => {
            if (vehicleMap) {
                vehicleMap.invalidateSize();
            }
        }, 200);
    };

    // Re-init the map when vehicle OR dropoff inputs change. Previously the watcher
    // only reacted to vehicle fields, so a dropoff lat/lng arriving after mount (or
    // changing mid-session) would not draw the second marker.
    watch(
        () => [
            props.vehicle?.id,
            props.vehicle?.latitude,
            props.vehicle?.longitude,
            props.vehicle?.location?.dropoff?.latitude,
            props.vehicle?.location?.dropoff?.longitude,
            props.dropoffLatitude,
            props.dropoffLongitude,
            props.dropoffLocation,
            props.pickupLocation,
        ],
        () => {
            nextTick(() => {
                initVehicleMap();
            });
        }
    );

    // Watch modal open/close to create/destroy modal map
    watch(showMapModal, (open) => {
        if (open && hasVehicleCoords.value) {
            nextTick(() => {
                if (!mapModalRef.value) return;
                if (modalMap) { modalMap.remove(); modalMap = null; }
                const pickupLat = parseFloat(props.vehicle.latitude);
                const pickupLng = parseFloat(props.vehicle.longitude);
                modalMap = L.map(mapModalRef.value, { zoomControl: true, maxZoom: 18, minZoom: 3 });
                const stadiaKey = import.meta.env.VITE_STADIA_MAPS_API_KEY || '';
                const keyParam = stadiaKey ? `?api_key=${stadiaKey}` : '';
                L.tileLayer(`https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.png${keyParam}`, {
                    attribution: '&copy; Stadia Maps &copy; OpenMapTiles &copy; OSM',
                    maxZoom: 20,
                }).addTo(modalMap);
                const pickupIcon = createMapIcon('#059669', 'Pickup', true);
                L.marker([pickupLat, pickupLng], { icon: pickupIcon })
                    .bindPopup(`<div style="font-family:system-ui;text-align:center;padding:4px 0;"><p style="font-weight:600;margin:0 0 2px;">Pickup</p><p style="margin:0;color:#6b7280;font-size:12px;">${props.pickupLocation || ''}</p></div>`, { className: 'rental-popup' })
                    .addTo(modalMap);
                if (isDifferentDropoff.value) {
                    const dropoffLat = resolvedDropoffLat.value;
                    const dropoffLng = resolvedDropoffLng.value;
                    const dropoffIcon = createMapIcon('#dc2626', 'Dropoff');
                    L.marker([dropoffLat, dropoffLng], { icon: dropoffIcon })
                        .bindPopup(`<div style="font-family:system-ui;text-align:center;padding:4px 0;"><p style="font-weight:600;margin:0 0 2px;">Dropoff</p><p style="margin:0;color:#6b7280;font-size:12px;">${props.dropoffLocation || ''}</p></div>`, { className: 'rental-popup' })
                        .addTo(modalMap);
                    modalMap.fitBounds([[pickupLat, pickupLng], [dropoffLat, dropoffLng]], { padding: [50, 50] });
                } else {
                    modalMap.setView([pickupLat, pickupLng], 14);
                }
            });
        } else if (modalMap) {
            modalMap.remove();
            modalMap = null;
        }
    });

    // Initialize map on mount
    onMounted(() => {
        nextTick(() => {
            initVehicleMap();
        });
    });

    // Cleanup on unmount
    onUnmounted(() => {
        if (vehicleMap) {
            vehicleMap.remove();
            vehicleMap = null;
        }
        if (modalMap) {
            modalMap.remove();
            modalMap = null;
        }
    });

    return {
        vehicleMapRef,
        mapModalRef,
        showMapModal,
        hasVehicleCoords,
        hasDropoffCoords,
        isDifferentDropoff,
        initVehicleMap,
        createMapIcon,
    };
}
