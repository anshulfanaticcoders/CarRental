<script setup>
import { Link, useForm, usePage, router, Head } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, provide, ref, watch } from "vue";
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
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
import noVehicleIcon from "../../assets/traveling-car-illustration.png";
import seatingIcon from "../../assets/travellerIcon.svg";
import brandIcon from "../../assets/SedanCarIcon.svg";
import colorIcon from "../../assets/color-palette.svg";
import filterIcon from "../../assets/filterIcon.svg";
import SearchBar from "@/Components/SearchBar.vue";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import CaretDown from "../../assets/CaretDown.svg";
import fullStar from "../../assets/fullstar.svg";
import halfStar from "../../assets/halfstar.svg";
import blankStar from "../../assets/blankstar.svg";
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
    transmissions: Array,
    fuels: Array,
    mileages: Array,
    schema: Object, // Add schema prop
    seoMeta: Object, // Added seoMeta prop
    locale: String, // Added locale prop
    greenMotionVehicles: Object, // New: GreenMotion vehicles data
});
// New: Currency symbols for GreenMotion vehicles
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
    return currencySymbols.value[code] || '$'; // Use fetched symbol or default to '$'
};

const numberOfRentalDays = computed(() => {
    if (form.date_from && form.date_to) {
        const start = new Date(form.date_from);
        const end = new Date(form.date_to);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 1; // Ensure at least 1 day for calculation
    }
    return 1; // Default to 1 day if dates are not set
});

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};
const page = usePage();

const isCustomer = computed(() => {
    return page.props.auth?.user?.role === 'customer';
});

const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === props.locale) || {};
});

const constructedLocalizedUrlSlug = computed(() => {
    // Prioritize translated url_slug, fallback to main seoMeta url_slug, then 's'
    return seoTranslation.value.url_slug || props.seoMeta?.url_slug || 's';
});

const currentUrl = computed(() => {
    // Construct the full localized URL for Open Graph and Canonical
    return `${window.location.origin}/${props.locale}/${constructedLocalizedUrlSlug.value}`;
});

const canonicalUrl = computed(() => {
    // Canonical URL should also reflect the localized slug
    return props.seoMeta?.canonical_url || currentUrl.value;
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || 'Search Results'; // Fallback to 'Search Results'
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

const form = useForm({
    seating_capacity: usePage().props.filters.seating_capacity || "",
    brand: usePage().props.filters.brand || "",
    transmission: usePage().props.filters.transmission || "",
    price_range: usePage().props.filters.price_range || "",
    color: usePage().props.filters.color || "",
    mileage: usePage().props.filters.mileage || "",
    date_from: usePage().props.filters.date_from || "",
    date_to: usePage().props.filters.date_to || "",
    where: usePage().props.filters.where || "",
    latitude: usePage().props.filters.latitude || null,
    longitude: usePage().props.filters.longitude || null,
    radius: usePage().props.filters.radius || null,
    package_type: usePage().props.filters.package_type || "",
    category_id: usePage().props.filters.category_id || "",
    city: usePage().props.filters.city || "",
    state: usePage().props.filters.state || "",
    country: usePage().props.filters.country || "",
    matched_field: usePage().props.filters.matched_field || "",
    location: usePage().props.filters.location || "",
    provider: usePage().props.filters.provider || null,
    provider_pickup_id: usePage().props.filters.provider_pickup_id || null,
    start_time: usePage().props.filters?.start_time || '09:00',
    end_time: usePage().props.filters?.end_time || '09:00',
    age: usePage().props.filters?.age || 35,
    rentalCode: usePage().props.filters?.rentalCode || '1',
    currency: usePage().props.filters?.currency || null,
    fuel: usePage().props.filters?.fuel || null,
    userid: usePage().props.filters?.userid || null,
    username: usePage().props.filters?.username || null,
    language: usePage().props.filters?.language || null,
    full_credit: usePage().props.filters?.full_credit || null,
    promocode: usePage().props.filters?.promocode || null,
    dropoff_location_id: usePage().props.filters?.dropoff_location_id || null,
    dropoff_where: usePage().props.filters?.dropoff_where || "",
});

const submitFilters = debounce(() => {
    const dataToSend = { ...form.data() };
    if (dataToSend.matched_field && (dataToSend.city || dataToSend.state || dataToSend.country)) {
        delete dataToSend.radius;
    }


    form.get(`/${page.props.locale}/s`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (response) => {
            // console.log('Filter response:', response.props.vehicles);
        },
        onError: (errors) => {
            console.error('Filter errors:', errors);
        },
        onSuccess: () => {
            const urlParams = new URLSearchParams(form.data()).toString();
            sessionStorage.setItem('searchurl', `/s?${urlParams}`);
        }
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

const allVehiclesForMap = computed(() => {
    const internal = props.vehicles.data || [];
    const greenMotion = props.greenMotionVehicles?.data || [];
    return [...internal, ...greenMotion];
});

const isValidCoordinate = (coord) => {
    const num = parseFloat(coord);
    return !isNaN(num) && isFinite(num);
};

const initMap = () => {
    const getValidVehicleCoords = () => {
        if (!allVehiclesForMap.value || allVehiclesForMap.value.length === 0) return [];
        return allVehiclesForMap.value
            .map(vehicle =>
                (isValidCoordinate(vehicle.latitude) && isValidCoordinate(vehicle.longitude))
                ? [parseFloat(vehicle.latitude), parseFloat(vehicle.longitude)]
                : null
            )
            .filter(coord => coord !== null);
    };

    let vehicleCoords = getValidVehicleCoords();

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

    if (vehicleCoords.length === 0) {
        console.warn("No vehicles with valid coordinates to initialize map view. Setting default view.");
        map.setView([20, 0], 2);
    } else {
        const bounds = L.latLngBounds(vehicleCoords);
        if (bounds.isValid()) {
             if (vehicleCoords.length === 1) {
                map.setView(bounds.getCenter(), 13);
            } else {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        } else {
            map.setView([20,0],2);
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
            if (allVehiclesForMap.value && allVehiclesForMap.value.length > 0) {
                const currentCoords = getValidVehicleCoords();
                if (currentCoords.length > 0) {
                    const currentBounds = L.latLngBounds(currentCoords);
                    if (currentBounds.isValid()) {
                        if (currentCoords.length === 1) {
                           if(map.getZoom() < 10) map.setView(currentBounds.getCenter(), 13);
                           else map.panTo(currentBounds.getCenter());
                        } else {
                           map.fitBounds(currentBounds, { padding: [50, 50] });
                        }
                    }
                } else if (!map.getCenter() || (map.getCenter().lat === 20 && map.getCenter().lng === 0 && map.getZoom() === 2) ) {
                    map.setView([20,0],2);
                }
            }
        }
    }, 200);
};

const createCustomIcon = (vehicle, isHighlighted = false) => {
    let currencySymbol = '$';
    let priceToDisplay = "N/A";
    let priceValue = null;

    if (vehicle.source !== 'internal') {
        const currencyCode = vehicle.products[0]?.currency || 'USD';
        currencySymbol = getCurrencySymbol(currencyCode);
        // Calculate price per day for provider vehicles
        const totalProviderPrice = parseFloat(vehicle.products[0]?.total || 0);
        priceValue = totalProviderPrice / numberOfRentalDays.value;
    } else {
        currencySymbol = vehicle.vendor_profile?.currency || "$";
        if (form.package_type === 'day' && vehicle.price_per_day) {
            priceValue = vehicle.price_per_day;
        } else if (form.package_type === 'week' && vehicle.price_per_week) {
            priceValue = vehicle.price_per_week;
        } else if (form.package_type === 'month' && vehicle.price_per_month) {
            priceValue = vehicle.price_per_month;
        } else {
            // Fallback if no package_type is selected or price for selected type is null
            if (vehicle.price_per_day) {
                priceValue = vehicle.price_per_day;
            } else if (vehicle.price_per_week) {
                priceValue = vehicle.price_per_week;
            } else if (vehicle.price_per_month) {
                priceValue = vehicle.price_per_month;
            }
        }
    }

    if (priceValue !== null && priceValue > 0) { // Ensure priceValue is greater than 0
        priceToDisplay = `${currencySymbol}${parseFloat(priceValue).toFixed(2)}`;
    } else {
        priceToDisplay = "N/A"; // Explicitly set to N/A if price is 0 or null
    }

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

const resetFilters = () => {
    // Reset only the vehicle-specific filters
    form.seating_capacity = "";
    form.brand = "";
    form.transmission = "";
    form.fuel = "";
    form.price_range = "";
    form.color = "";
    form.mileage = "";
    form.package_type = "";
    form.category_id = "";

    // Reset UI components
    priceRangeValues.value = [0, 20000];
    tempPriceRangeValues.value = [0, 20000];

    // Reset other non-essential GreenMotion params to default
    form.currency = null;
    form.userid = null;
    form.username = null;
    form.language = null;
    form.full_credit = null;
    form.promocode = null;
    form.dropoff_location_id = null;

    // The other fields like where, lat, lng, dates, source, etc., are NOT touched.
    // They will retain their current values.
    submitFilters();
};

const addMarkers = () => {
    markers.forEach((marker) => marker.remove());
    markers = [];
    vehicleMarkers.value = {}; // Clear previous vehicle to marker mappings

    if (!allVehiclesForMap.value || allVehiclesForMap.value.length === 0) {
        return;
    }

    const coordData = new Map();

    allVehiclesForMap.value.forEach((vehicle) => {
        if (!isValidCoordinate(vehicle.latitude) || !isValidCoordinate(vehicle.longitude)) {
            console.warn(`Skipping vehicle ID ${vehicle.id} with invalid coordinates: Lat=${vehicle.latitude}, Lng=${vehicle.longitude}`);
            return;
        }

        const lat = parseFloat(vehicle.latitude);
        const lng = parseFloat(vehicle.longitude);
        const coordKey = `${lat.toFixed(5)}_${lng.toFixed(5)}`;

        if (!coordData.has(coordKey)) {
            coordData.set(coordKey, { count: 0, originalLat: lat, originalLng: lng });
        }
        const dataAtCoord = coordData.get(coordKey);
        dataAtCoord.count += 1;

        let displayLat = lat;
        let displayLng = lng;
        const occurrence = dataAtCoord.count;

        if (occurrence > 1) {
            const K_MAX_MARKERS_PER_RING = 8; // Max markers in one ring before increasing radius
            const ringNum = Math.floor((occurrence - 2) / K_MAX_MARKERS_PER_RING);
            const indexInRing = (occurrence - 2) % K_MAX_MARKERS_PER_RING;

            const angle = indexInRing * (2 * Math.PI / K_MAX_MARKERS_PER_RING);

            // Start with a more significant base radius, and increase for new "rings"
            const baseEffectiveRadius = 0.00030; // Approx 33 meters, adjust as needed
            const effectiveRadius = baseEffectiveRadius * (1 + ringNum * 0.65); // Increase radius for outer rings

            displayLat = lat + effectiveRadius * Math.sin(angle);
            displayLng = lng + effectiveRadius * Math.cos(angle);
        }

        const primaryImage = vehicle.source === 'greenmotion' ? vehicle.image : (vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png');
        const detailRoute = vehicle.source !== 'internal'
            ? route(getProviderRoute(vehicle), { locale: page.props.locale, id: vehicle.id.substring(vehicle.id.indexOf('_') + 1), location_id: vehicle.provider_pickup_id, start_date: form.date_from, end_date: form.date_to, start_time: form.start_time, end_time: form.end_time, age: form.age, rentalCode: form.rentalCode, currency: form.currency, fuel: form.fuel, userid: form.userid, username: form.username, language: form.language, full_credit: form.full_credit, promocode: form.promocode, dropoff_location_id: form.dropoff_location_id, dropoff_where: form.dropoff_where, where: form.where, provider: vehicle.source })
            : route('vehicle.show', { locale: page.props.locale, id: vehicle.id, package: form.package_type, pickup_date: form.date_from, return_date: form.date_to });

        let popupPrice = "N/A";
        let popupCurrencySymbol = "$";

        if (vehicle.source !== 'internal' && vehicle.products && vehicle.products[0]?.total && vehicle.products[0].total > 0) {
            const currencyCode = vehicle.products[0]?.currency || 'USD';
            popupCurrencySymbol = getCurrencySymbol(currencyCode);
            const totalProviderPrice = parseFloat(vehicle.products[0]?.total || 0);
            const pricePerDay = totalProviderPrice / numberOfRentalDays.value;
            popupPrice = `${popupCurrencySymbol}${pricePerDay.toFixed(2)}`; // Display price per day
        } else if (vehicle.source === 'internal' && vehicle.price_per_day && vehicle.price_per_day > 0) {
            popupCurrencySymbol = vehicle.vendor_profile?.currency || "$";
            popupPrice = `${popupCurrencySymbol}${vehicle.price_per_day}`;
        }

        const marker = L.marker([displayLat, displayLng], {
            icon: createCustomIcon(vehicle),
            pane: "markers",
        }).bindPopup(`
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.brand} ${vehicle.model}" class="popup-image !w-40 !h-20" />
                <p class="rating !w-40">${vehicle.average_rating ? vehicle.average_rating.toFixed(1) : '0.0'} ★ (${vehicle.review_count} reviews)</p>
                <p class="font-semibold !w-40">${vehicle.brand} ${vehicle.model}</p>
                <p class="!w-40">${vehicle.full_vehicle_address || ''}</p>
                <p class="!w-40">Price: ${popupPrice}</p>
                <a href="${detailRoute}"
                   class="text-blue-500 hover:text-blue-700"
                   onclick="event.preventDefault(); window.location.href = this.href;">
                    View Details
                </a>
            </div>
        `);

        map.addLayer(marker);
        markers.push(marker);
        vehicleMarkers.value[vehicle.id] = marker; // Store marker instance
    });

    const validCoords = allVehiclesForMap.value
        .filter(v => isValidCoordinate(v.latitude) && isValidCoordinate(v.longitude))
        .map(v => [parseFloat(v.latitude), parseFloat(v.longitude)]);

    if (validCoords.length > 0) {
        const allVehicleBounds = L.latLngBounds(validCoords);
        if (allVehicleBounds.isValid()) {
            if (validCoords.length === 1) {
                map.setView(allVehicleBounds.getCenter(), 13);
            } else {
                map.fitBounds(allVehicleBounds, { padding: [50, 50] });
            }
        }
    } else {
        if (map && (!map.getCenter() || (map.getCenter().lat === 20 && map.getCenter().lng === 0 && map.getZoom() === 2))) {
             map.setView([20,0],2);
        }
        console.warn("No vehicles with valid coordinates to fit map bounds after adding markers.");
    }
};


watch(
    () => props.vehicles.data,
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
    () => props.greenMotionVehicles?.data,
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
    () => form.package_type,
    () => {
        if (map) {
            initMap();
        }
    }
);

onMounted(() => {
    initMap();
});

const vehicleMarkers = ref({}); // To store vehicle.id -> marker mapping

const highlightVehicleOnMap = (vehicle) => {
    if (!map || !vehicle || !isValidCoordinate(vehicle.latitude) || !isValidCoordinate(vehicle.longitude)) return;

    const marker = vehicleMarkers.value[vehicle.id];
    if (marker) {
        map.panTo([parseFloat(vehicle.latitude), parseFloat(vehicle.longitude)], { animate: true, duration: 0.5 });
        marker.setIcon(createCustomIcon(vehicle, true)); // Highlight marker
        marker.openPopup();
    }
};

const unhighlightVehicleOnMap = (vehicle) => {
    if (!map || !vehicle) return;

    const marker = vehicleMarkers.value[vehicle.id];
    if (marker) {
        marker.setIcon(createCustomIcon(vehicle, false)); // Revert marker to normal state
        // Closing the popup on mouseleave might be disruptive if the user wants to interact with it.
        // Consider if marker.closePopup() is desired here or only on a different event.
    }
};


const showMap = ref(true);

const handleMapToggle = (value) => {
    showMap.value = value;
    if (value && map) {
        setTimeout(() => {
            map.invalidateSize();
            const validCoords = allVehiclesForMap.value
                .filter(v => isValidCoordinate(v.latitude) && isValidCoordinate(v.longitude))
                .map(v => [parseFloat(v.latitude), parseFloat(v.longitude)]);

            if (validCoords.length > 0) {
                const currentBounds = L.latLngBounds(validCoords);
                if (currentBounds.isValid()) {
                    if (validCoords.length === 1) {
                        map.setView(currentBounds.getCenter(), 13);
                    } else {
                        map.fitBounds(currentBounds, { padding: [50, 50] });
                    }
                }
            } else if(map && (!map.getCenter() || (map.getCenter().lat === 20 && map.getCenter().lng === 0 && map.getZoom() === 2))){
                 map.setView([20,0],2);
            }
        }, 100);
    }
};

import { useToast } from "vue-toastification";
import { Inertia } from "@inertiajs/inertia";
import CustomDropdown from "@/Components/CustomDropdown.vue";
const toast = useToast();
const favoriteStatus = ref({});

const fetchFavoriteStatus = async () => {
    if (!page.props.auth?.user) return;
    try {
        // Fetch favorites for internal vehicles only
        if (!props.vehicles.data || props.vehicles.data.length === 0) return;
        const response = await axios.get(route('favorites.status'));
        const favoriteIds = response.data; // Now an array of IDs
        const newStatus = {};
        props.vehicles.data.forEach((vehicle) => {
            newStatus[vehicle.id] = favoriteIds.includes(vehicle.id);
        });
        favoriteStatus.value = newStatus;
    } catch (error) {
        console.error("Error fetching favorite status:", error);
    }
};
const $page = usePage();

const popEffect = ref({});

const toggleFavourite = async (vehicle) => {
    if (!$page.props.auth?.user) {
        return router.get(route('login', {}, usePage().props.locale));
    }

    if (vehicle.source === 'greenmotion') {
        toast.info("Favorites are not supported for GreenMotion vehicles.", {
            position: "top-right",
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
        });
        return;
    }

    const endpoint = favoriteStatus.value[vehicle.id]
        ? route('vehicles.unfavourite', { vehicle: vehicle.id })
        : route('vehicles.favourite', { vehicle: vehicle.id });

    try {
        await axios.post(endpoint);
        favoriteStatus.value[vehicle.id] = !favoriteStatus.value[vehicle.id];

        if (favoriteStatus.value[vehicle.id]) {
            popEffect.value[vehicle.id] = true;
            setTimeout(() => {
                popEffect.value[vehicle.id] = false;
            }, 300);
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
            router.get(route('login', {}, usePage().props.locale));
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
        // For prefill consistency with SearchBar
        city: usePage().props.filters?.city || "",
        state: usePage().props.filters?.state || "",
        country: usePage().props.filters?.country || "",
        matched_field: usePage().props.filters?.matched_field || null,
        location: usePage().props.filters?.location || "",
        provider: usePage().props.filters?.provider || null,
        provider_pickup_id: usePage().props.filters?.provider_pickup_id || null,
        start_time: usePage().props.filters?.start_time || '09:00',
        end_time: usePage().props.filters?.end_time || '09:00',
        age: usePage().props.filters?.age || 35,
        rentalCode: usePage().props.filters?.rentalCode || '1',
        currency: usePage().props.filters?.currency || null,
        fuel: usePage().props.filters?.fuel || null,
        userid: usePage().props.filters?.userid || null,
        username: usePage().props.filters?.username || null,
        language: usePage().props.filters?.language || null,
        full_credit: usePage().props.filters?.full_credit || null,
        promocode: usePage().props.filters?.promocode || null,
        dropoff_location_id: usePage().props.filters?.dropoff_location_id || null,
        dropoff_where: usePage().props.filters?.dropoff_where || "",
    };
});

// Add this handler function to update the form with data from SearchBar
const handleSearchUpdate = (params) => {
    form.where = params.where || "";
    form.latitude = params.latitude || null;
    form.longitude = params.longitude || null;
    form.radius = params.radius || null;
    form.date_from = params.date_from || "";
    form.date_to = params.date_to || "";
    // Update missing location fields
    form.city = params.city || "";
    form.state = params.state || "";
    form.country = params.country || "";
    form.matched_field = params.matched_field || null;
    form.source = params.source || null;
    form.greenmotion_location_id = params.greenmotion_location_id || null;
    form.start_time = params.start_time || '09:00';
    form.end_time = params.end_time || '09:00';
    form.age = params.age || 35;
    form.rentalCode = params.rentalCode || '1';
    form.currency = params.currency || null;
    form.fuel = params.fuel || null;
    form.userid = params.userid || null;
    form.username = params.username || null;
    form.language = params.language || null;
    form.full_credit = params.full_credit || null;
    form.promocode = params.promocode || null;
    form.dropoff_location_id = params.dropoff_location_id || null;
    form.dropoff_where = params.dropoff_where || "";


    if (params.matched_field === 'location') {
        form.location = params.location_name || params.where || ""; // Use location_name if provided by SearchBar, else fallback
    } else {
        form.location = ""; // Clear if not a 'location' type match
    }
    // The watch on form.data() will automatically trigger submitFilters.
};

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

const getProviderRoute = (vehicle) => {
    if (vehicle.source === 'greenmotion') {
        return 'green-motion-car.show';
    }
    // Add other providers here as needed
    // if (vehicle.source === 'usave') {
    //     return 'usave-car.show';
    // }
    return 'green-motion-car.show'; // Default for now
};

const showPriceSlider = ref(false);
const priceRangeMin = ref(0);
const priceRangeMax = ref(20000);
const priceRangeValues = ref([0, 20000]);
const tempPriceRangeValues = ref([0, 20000]);

onMounted(() => {
    if (form.price_range) {
        const [min, max] = form.price_range.split('-').map(Number);
        priceRangeValues.value = [min || 0, max || 20000];
        tempPriceRangeValues.value = [min || 0, max || 20000];
    }
});

const applyPriceRange = () => {
    priceRangeValues.value = [...tempPriceRangeValues.value];
    form.price_range = `${priceRangeValues.value[0]}-${priceRangeValues.value[1]}`;
    showPriceSlider.value = false;
};

const resetPriceRange = () => {
    tempPriceRangeValues.value = [0, 20000];
    priceRangeValues.value = [0, 20000];
    form.price_range = '0-20000';
    showPriceSlider.value = false;
};

const activeDropdown = ref(null);

const setActiveDropdown = (name) => {
    if (activeDropdown.value === name) {
        activeDropdown.value = null;
    } else {
        activeDropdown.value = name;
    }
};

provide('activeDropdown', activeDropdown);
provide('setActiveDropdown', setActiveDropdown);

const showFilterButton = ref(false);
const showFixedMobileFilterButton = ref(false);

const handleScroll = () => {
    const filterSection = document.getElementById('filter-section');
    if (filterSection) {
        const rect = filterSection.getBoundingClientRect();
        // Show button if filter section is scrolled out of view (top is above viewport)
        showFilterButton.value = rect.top < -320;

        // For mobile, show fixed button if scrolled past filter section and screen is small
        const isMobile = window.innerWidth <= 768; // Adjust breakpoint as needed
        showFixedMobileFilterButton.value = isMobile && rect.top < -100; // Show when filter section is mostly out of view
    }
};

const scrollToFilter = () => {
    const filterSection = document.getElementById('filter-section');
    if (filterSection) {
        filterSection.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (observer.value) {
        observer.value.disconnect();
    }
});

const observer = ref(null);
const vehiclesInView = ref(new Set()); // To track vehicles that have already animated

const saveSearchUrl = () => {
    sessionStorage.setItem('searchurl', window.location.href);
};

const setupIntersectionObserver = () => {
    if (observer.value) {
        observer.value.disconnect();
    }

    observer.value = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !vehiclesInView.value.has(entry.target.dataset.vehicleId)) {
                    // Force reflow to ensure initial state is applied before transition
                    void entry.target.offsetWidth;
                    entry.target.classList.add('fade-up-visible');
                    vehiclesInView.value.add(entry.target.dataset.vehicleId);
                }
            });
        },
        {
            root: null, // viewport
            rootMargin: '0px',
            threshold: 0.1, // Trigger when 10% of the item is visible
        }
    );

    // Observe all vehicle cards
    document.querySelectorAll('.vehicle-card').forEach((card) => {
        if (!vehiclesInView.value.has(card.dataset.vehicleId)) {
            card.classList.add('fade-up-hidden'); // Ensure initial hidden state
        }
        observer.value.observe(card);
    });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    initMap();
    if (page.props.auth?.user) {
        fetchFavoriteStatus();
    }
    setupIntersectionObserver(); // Initialize Intersection Observer
});

watch(
    () => props.vehicles.data,
    () => {
        // Re-observe elements when vehicles data changes (e.g., on filter change)
        vehiclesInView.value.clear(); // Clear seen vehicles
        if (observer.value) {
            observer.value.disconnect();
        }
        // Allow a small delay for DOM to update before re-observing
        setTimeout(() => {
            setupIntersectionObserver();
        }, 100);
    },
    { deep: true }
);

</script>

<template>
    <Head>
        <meta name="robots" content="index, follow" />
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
    <SchemaInjector v-if="schema" :schema="schema" />
    <section class="bg-customPrimaryColor py-customVerticalSpacing">
        <div class="">
            <SearchBar class="border-[2px] rounded-[20px] border-white mt-0 mb-0 max-[768px]:border-none"
                :prefill="searchQuery"
                @update-search-params="handleSearchUpdate" />
                <SchemaInjector v-if="$page.props.organizationSchema" :schema="$page.props.organizationSchema" />
        </div>
    </section>

    <section>
    <div id="filter-section" class="full-w-container py-8">
        <!-- Mobile filter button (visible only on mobile, hidden when fixed button appears) -->
        <div class="md:hidden mb-4" v-if="!showFixedMobileFilterButton">
            <button @click="showMobileFilters = true"
                class="flex items-center justify-center gap-3 p-3 w-full bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                <img :src="filterIcon" alt="Filter" class="w-5 h-5" loading="lazy" />
                <span class="text-lg font-medium">Find Your Perfect Car</span>
            </button>
        </div>

        <!-- Desktop filter header (hidden on mobile) -->
        <div class="hidden md:flex items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-3">
                <img :src="filterIcon" alt="" class="w-6 h-6" loading="lazy" />
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
                        :options="$page.props.brands.map(brand => ({ value: brand, label: brand }))"
                        placeholder="Any Brand" :left-icon="brandIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Category Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Vehicle Type</div>
                <CustomDropdown v-model="form.category_id" unique-id="category"
                        :options="$page.props.categories.map(category => ({ value: category.id, label: category.name }))"
                        placeholder="All Categories" :left-icon="categoryIcon" :right-icon="CaretDown" />
                    </div>

                <!-- Transmission Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Transmission Type</div>
                    <CustomDropdown v-model="form.transmission" unique-id="transmission"
                        :options="$page.props.transmissions.map(transmission => ({ value: transmission, label: transmission.charAt(0).toUpperCase() + transmission.slice(1) }))"
                        placeholder="Any Type" :left-icon="transmissionIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Fuel Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Fuel Type</div>
                    <CustomDropdown v-model="form.fuel" unique-id="fuel"
                        :options="$page.props.fuels.map(fuel => ({ value: fuel, label: fuel.charAt(0).toUpperCase() + fuel.slice(1) }))"
                        placeholder="Any Fuel" :left-icon="fuelIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Price Range Filter -->
                <div class="relative  filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Budget</div>
                    <div class="relative w-full">
                        <img :src="priceIcon" alt="Price Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none text-gray-500" loading="lazy" />
                        <button type="button" @click="showPriceSlider = !showPriceSlider"
                            class="pl-10 pr-4 py-2 w-full text-left flex gap-4 items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm hover:border-customPrimaryColor transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor/20">
                            <span class="text-gray-700 font-medium">
                                {{ priceRangeValues[0] === 0 && priceRangeValues[1] === 20000 ? 'Set Price Range' :
                                    `$${priceRangeValues[0]} - $${priceRangeValues[1]}` }}
                            </span>
                            <img :src="CaretDown" alt="Caret Down"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
                                :class="{ 'rotate-180': showPriceSlider }" loading="lazy" />
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
                        :options="$page.props.colors.map(color => ({ value: color, label: color }))"
                        placeholder="Any Color" :left-icon="colorIcon" :right-icon="CaretDown"
                        class="hover:border-customPrimaryColor transition-all duration-300" />
                </div>

                <!-- Mileage Filter -->
                <div class="relative w-48 filter-group">
                    <div class="text-xs font-medium text-gray-500 mb-1 ml-1">Fuel Efficiency</div>
                    <CustomDropdown v-model="form.mileage" unique-id="mileage"
                        :options="$page.props.mileages.map(mileage => ({ value: mileage, label: `${mileage} km/L` }))"
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
                    ]" placeholder="Any Rate" :left-icon="priceperdayicon" :right-icon="CaretDown"
                    class="hover:border-customPrimaryColor bg-customPrimaryColor/5 transition-all duration-300" />
                </div>

            </div>
        </form>

        <!-- Mobile Filters Canvas/Sidebar -->
        <div v-if="showMobileFilters" class="fixed inset-0 bg-black bg-opacity-50 z-50 md:hidden"
            @click="showMobileFilters = false">
            <div class="fixed inset-x-0 bottom-0 max-h-[85%] bg-white rounded-t-xl overflow-y-auto p-5 pt-0" @click.stop>
                <div class="flex justify-between items-center mb-4 sticky z-50 top-0 bg-white pb-3 border-b border-gray-100 py-4">
                    <div class="flex items-center gap-2">
                        <img :src="filterIcon" alt="" class="w-5 h-5" loading="lazy" />
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
                            :options="$page.props.brands.map(brand => ({ value: brand, label: brand }))"
                            placeholder="Any Brand" :left-icon="brandIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-item">
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Vehicle Type</label>
                    <CustomDropdown v-model="form.category_id" unique-id="category-mobile"
                        :options="$page.props.categories.map(category => ({ value: category.id, label: category.name }))"
                        placeholder="All Categories" :left-icon="categoryIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Transmission Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Transmission Type</label>
                        <CustomDropdown v-model="form.transmission" unique-id="transmission-mobile"
                            :options="$page.props.transmissions.map(transmission => ({ value: transmission, label: transmission.charAt(0).toUpperCase() + transmission.slice(1) }))"
                            placeholder="Any Type" :left-icon="transmissionIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Fuel Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Fuel Type</label>
                        <CustomDropdown v-model="form.fuel" unique-id="fuel-mobile"
                            :options="$page.props.fuels.map(fuel => ({ value: fuel, label: fuel.charAt(0).toUpperCase() + fuel.slice(1) }))"
                            placeholder="Any Fuel" :left-icon="fuelIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Budget</label>
                        <div class="relative w-full">
                        <img :src="priceIcon" alt="Price Icon"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 pointer-events-none text-gray-500" loading="lazy" />
                        <button type="button" @click="showPriceSlider = !showPriceSlider"
                            class="pl-10 pr-4 py-2 w-full text-left flex gap-4 items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm hover:border-customPrimaryColor transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor/20">
                            <span class="text-gray-700 font-medium">
                                {{ priceRangeValues[0] === 0 && priceRangeValues[1] === 20000 ? 'Set Price Range' :
                                    `$${priceRangeValues[0]} - $${priceRangeValues[1]}` }}
                            </span>
                            <img :src="CaretDown" alt="Caret Down"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 ease-in-out pointer-events-none"
                                :class="{ 'rotate-180': showPriceSlider }" loading="lazy" />
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
                            :options="$page.props.colors.map(color => ({ value: color, label: color }))"
                            placeholder="Any Color" :left-icon="colorIcon" :right-icon="CaretDown" />
                    </div>

                    <!-- Mileage Filter -->
                    <div class="filter-item">
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Fuel Efficiency</label>
                        <CustomDropdown v-model="form.mileage" unique-id="mileage-mobile"
                            :options="$page.props.mileages.map(mileage => ({ value: mileage, label: `${mileage} km/L` }))"
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
        <div class="flex gap-[2.5rem] max-[768px]:flex-col">
            <!-- Filter button for desktop (appears on scroll) -->
            <button v-if="showFilterButton" @click="scrollToFilter"
                class="fixed left-0 top-[1.5rem] transform -translate-y-1/2 z-40 p-3  text-white rounded-r-lg shadow-lg opacity-50 hover:opacity-100 transition-opacity duration-300 hidden md:flex items-center gap-2"
                style="background: linear-gradient(90deg, #FC466B 0%, #3F5EFB 100%);">
                <img :src="filterIcon" alt="Filter" class="w-6 h-6 brightness-[20]" loading="lazy" />
                <span class="font-medium">Filter</span>
            </button>

            <!-- Fixed Mobile Filter Button (appears on scroll) -->
            <button v-if="showFixedMobileFilterButton && !showMobileFilters" @click="showMobileFilters = true"
                class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 p-3 px-6 text-white rounded-full shadow-lg flex items-center gap-3 md:hidden"
                style="background: linear-gradient(90deg, #FC466B 0%, #3F5EFB 100%);">
                <img :src="filterIcon" alt="Filter" class="w-5 h-5 brightness-[20]" loading="lazy" />
                <span class="font-medium">Filters</span>
            </button>

            <!-- Left Column - Vehicle List -->
            <div class="w-full">
                <div :class="[
                    'grid gap-5',
                    showMap ? 'w-full grid-cols-2' : 'w-full grid-cols-4',
                ]" class="max-[768px]:grid-cols-1">
                    <div v-if="!allVehiclesForMap || allVehiclesForMap.length === 0"
                        class="text-center text-gray-500 col-span-2 flex flex-col justify-center items-center gap-4">
                        <img :src=noVehicleIcon alt="" class="w-[25rem] max-[768px]:w-full" loading="lazy">
                        <p class="text-lg font-medium text-customPrimaryColor">No vehicles available at the moment</p>
                        <span>Search for another location</span>
                        <strong>Or</strong>
                        <span>Try to reduce the number of search filters.</span>
                        <button @click="resetFilters"
                            class="mt-4 px-6 py-2 bg-customPrimaryColor text-white rounded-lg hover:bg-opacity-90 transition">
                            Reset All Filters
                        </button>
                    </div>
                    <div v-for="vehicle in allVehiclesForMap" :key="vehicle.id"
                        class="rounded-[12px] border-[1px] border-[#E7E7E7] relative overflow-hidden vehicle-card fade-up-hidden"
                        :data-vehicle-id="vehicle.id"
                        @mouseenter="highlightVehicleOnMap(vehicle)"
                        @mouseleave="unhighlightVehicleOnMap(vehicle)">
                        <div class="flex justify-end mb-3 absolute right-3 topseas-3">
                            <div class="column flex justify-end">
                                <button v-if="(!$page.props.auth?.user || isCustomer) && vehicle.source === 'internal'" @click.stop="toggleFavourite(vehicle)"
                                    class="heart-icon bg-white rounded-[99px] p-2" :class="{
                                        'filled-heart':
                                            favoriteStatus[vehicle.id],
                                        'pop-animation': popEffect[vehicle.id], // Apply animation class dynamically
                                    }">
                                    <img :src="favoriteStatus[vehicle.id]
                                        ? FilledHeart
                                        : Heart
                                        " alt="Favorite" class="w-[1.5rem] transition-colors duration-300" loading="lazy" />
                                </button>
                            </div>
                        </div>
                        <a
                            @click="saveSearchUrl"
                            :href="vehicle.source !== 'internal' ? route(getProviderRoute(vehicle), { locale: page.props.locale, id: vehicle.id.substring(vehicle.id.indexOf('_') + 1), location_id: vehicle.provider_pickup_id, start_date: form.date_from, end_date: form.date_to, start_time: form.start_time, end_time: form.end_time, age: form.age, rentalCode: form.rentalCode, currency: form.currency, fuel: form.fuel, userid: form.userid, username: form.username, language: form.language, full_credit: form.full_credit, promocode: form.promocode, dropoff_location_id: form.dropoff_location_id, dropoff_where: form.dropoff_where, where: form.where, provider: vehicle.source }) : route('vehicle.show', { locale: page.props.locale, id: vehicle.id, package: form.package_type, pickup_date: form.date_from, return_date: form.date_to })">
                            <div class="column flex flex-col gap-5 items-start">
                                <img :src="vehicle.source !== 'internal' ? vehicle.image : (vehicle.images?.find(
                                    (image) =>
                                        image.image_type === 'primary'
                                )?.image_url || '/default-image.png')
                                    " alt="Vehicle Image"
                                    class="w-full h-[250px] object-cover rounded-tl-lg rounded-tr-lg max-[768px]:h-[200px]" loading="lazy" />
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
                                                " class="w-[16px] h-[16px]" loading="lazy" />
                                    </div>
                                    <span class="text-[1rem]" v-if="vehicle.review_count > 0">
                                        {{
                                            vehicle.average_rating.toFixed(1)
                                        }}
                                        ({{ vehicle.review_count }})
                                    </span>
                                    <span class="text-[1rem] text-gray-500" v-else>No reviews</span>
                                </div>

                                 <div>
                                    <span class="italic font-medium">{{vehicle.full_vehicle_address}}</span>
                                 </div>

                                <div class="car_short_info mt-[1rem] flex gap-3">
                                    <img :src="carIcon" alt="" loading="lazy" />
                                    <div class="features">
                                        <span class="capitalize text-[1.15rem] max-[768px]:text-[1rem]">{{
                                            vehicle.transmission }} .
                                            {{ vehicle.source === 'greenmotion' ? vehicle.fuel : vehicle.fuel }} .
                                            {{ vehicle.seating_capacity }}
                                            Seats</span>
                                    </div>
                                </div>
                                <div class="extra_details flex gap-5 mt-[1rem] items-center">
                                    <div class="col flex gap-3">
                                        <img :src="mileageIcon" alt="" loading="lazy" /><span
                                            class="text-[1.15rem] max-[768px]:text-[0.95rem]">
                                            {{ vehicle.source === 'greenmotion' ? vehicle.mileage + ' MPG' : vehicle.mileage + ' km/L' }}</span>
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
                                        vehicle.benefits.cancellation_available_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Free
                                        Cancellation ({{
                                            vehicle.benefits
                                                .cancellation_available_per_day_date
                                        }}
                                        days)
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.cancellation_available_per_week
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Free
                                        Cancellation ({{
                                            vehicle.benefits
                                                .cancellation_available_per_week_date
                                        }}
                                        days)
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.cancellation_available_per_month
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Free
                                        Cancellation ({{
                                            vehicle.benefits
                                                .cancellation_available_per_month_date
                                        }}
                                        days)
                                    </span>
                                    <span v-else-if="vehicle.source === 'greenmotion' && vehicle.benefits?.cancellation_available_per_day" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Free Cancellation
                                    </span>

                                    <!-- Mileage information based on the selected package type -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        !vehicle.benefits.limited_km_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Unlimited
                                        mileage
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.limited_km_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Limited to
                                        {{
                                            vehicle.benefits
                                                .limited_km_per_day_range
                                        }}
                                        km/day
                                    </span>
                                    <span v-else-if="vehicle.source === 'greenmotion' && !vehicle.benefits?.limited_km_per_day" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Unlimited mileage
                                    </span>


                                    <!-- Additional cost per km if applicable -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.price_per_km_per_day
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />{{
                                            vehicle.benefits
                                                .price_per_km_per_day
                                        }}/km extra above limit
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.price_per_km_per_week
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />{{
                                            vehicle.benefits
                                                .price_per_km_per_week
                                        }}/km extra above limit
                                    </span>
                                    <span v-else-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.price_per_km_per_month
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />{{
                                            vehicle.benefits
                                                .price_per_km_per_month
                                        }}/km extra above limit
                                    </span>

                                    <!-- Minimum driver age if applicable -->
                                    <span v-if="
                                        vehicle.benefits &&
                                        vehicle.benefits.minimum_driver_age
                                    " class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Min age:
                                        {{
                                            vehicle.benefits.minimum_driver_age
                                        }}
                                        years
                                    </span>
                                    <span v-else-if="vehicle.source === 'greenmotion' && vehicle.benefits?.minimum_driver_age" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Min age: {{ vehicle.benefits.minimum_driver_age }} years
                                    </span>
                                    <span v-if="vehicle.source === 'greenmotion' && vehicle.benefits?.fuel_policy" class="flex gap-3 items-center text-[12px]">
                                        <img :src="check" alt="" loading="lazy" />Fuel Policy: {{ vehicle.benefits.fuel_policy }}
                                    </span>
                                </div>

                                <div class="mt-[2rem] flex justify-between items-center">
                                    <!-- Conditional Price Display Block -->
                                    <div>
                                        <!-- If a specific package_type filter is active (day, week, or month) -->
                                        <div v-if="form.package_type === 'day' || form.package_type === 'week' || form.package_type === 'month'">
                                            <div v-if="vehicle.source !== 'internal'">
                                                <div v-if="vehicle.products && vehicle.products[0]?.total && vehicle.products[0].total > 0">
                                                    <span class="text-customPrimaryColor text-[1.875rem] font-medium max-[768px]:text-[1.3rem] max-[768px]:font-bold">
                                                        {{ getCurrencySymbol(vehicle.products[0].currency) }}{{ (parseFloat(vehicle.products[0].total) / numberOfRentalDays).toFixed(2) }}
                                                    </span>
                                                    <span>/day</span>
                                                </div>
                                                <div v-else>
                                                    <span class="text-sm text-gray-500">Price not available</span>
                                                </div>
                                            </div>
                                            <div v-else-if="vehicle[priceField] && vehicle[priceField] > 0">
                                                <span class="text-customPrimaryColor text-[1.875rem] font-medium max-[768px]:text-[1.3rem] max-[768px]:font-bold">
                                                    {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle[priceField] }}
                                                </span>
                                                <span>/{{ priceUnit }}</span>
                                            </div>
                                            <div v-else>
                                                <span class="text-sm text-gray-500">Price not available for {{ priceUnit }}</span>
                                            </div>
                                        </div>
                                        <!-- Else (no package_type filter is active, form.package_type is '') -->
                                        <div v-else class="flex flex-col">
                                            <template v-if="vehicle.source !== 'internal'">
                                                <div v-if="vehicle.products && vehicle.products[0]?.total && vehicle.products[0].total > 0" class="flex items-baseline">
                                                    <span class="text-customPrimaryColor text-lg font-semibold">
                                                        {{ getCurrencySymbol(vehicle.products[0].currency) }}{{ (parseFloat(vehicle.products[0].total) / numberOfRentalDays).toFixed(2) }}
                                                    </span>
                                                    <span class="text-xs text-gray-600 ml-1">/day</span>
                                                </div>
                                                <div v-else class="mt-1">
                                                    <span class="text-sm text-gray-500">Price not available</span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div v-if="vehicle.price_per_day && vehicle.price_per_day > 0" class="flex items-baseline">
                                                    <span class="text-customPrimaryColor text-lg font-semibold">
                                                        {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle.price_per_day }}
                                                    </span>
                                                    <span class="text-xs text-gray-600 ml-1">/day</span>
                                                </div>
                                                <div v-if="vehicle.price_per_week && vehicle.price_per_week > 0" class="flex items-baseline mt-1">
                                                    <span class="text-customPrimaryColor text-lg font-semibold">
                                                        {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle.price_per_week }}
                                                    </span>
                                                    <span class="text-xs text-gray-600 ml-1">/week</span>
                                                </div>
                                                <div v-if="vehicle.price_per_month && vehicle.price_per_month > 0" class="flex items-baseline mt-1">
                                                    <span class="text-customPrimaryColor text-lg font-semibold">
                                                        {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle.price_per_month }}
                                                    </span>
                                                    <span class="text-xs text-gray-600 ml-1">/month</span>
                                                </div>
                                                <div v-if="!(vehicle.price_per_day && vehicle.price_per_day > 0) && !(vehicle.price_per_week && vehicle.price_per_week > 0) && !(vehicle.price_per_month && vehicle.price_per_month > 0)" class="mt-1">
                                                    <span class="text-sm text-gray-500">Price not available</span>
                                                </div>
                                            </template>
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
    width: max-content; /* Fixed width to ensure consistent iconSize */
    height: 30px;
    /* background color is now controlled by Tailwind classes in HTML */
    border: 2px solid #666;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.marker-pin span {
    /* text color is now controlled by Tailwind classes in HTML */
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
    /* Removed transform: translate3d(0, 0, 1000px); as it interferes with Leaflet's positioning */
}

.leaflet-popup {
    z-index: 1001 !important;
}

/* Hardware acceleration - Removed explicit transforms as they might interfere with Leaflet's positioning */
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-popup {
    /* will-change: transform; */
    /* transform: translate3d(0, 0, 0); */
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

/* Removed .marker-cluster-small styles as clustering is removed */

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

/* Fade-up animation styles */
.fade-up-hidden {
    opacity: 0;
    transform: translateY(20px);
}

.fade-up-visible {
    opacity: 1;
    transform: translateY(0);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}
</style>
