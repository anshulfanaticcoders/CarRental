<script setup>
 import { Link, useForm, usePage, router } from "@inertiajs/vue3";
import { computed, nextTick, onMounted, onUnmounted, provide, ref, watch } from "vue";
import axios from 'axios';
import { toast as sonnerToast } from "vue-sonner";
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import SeoHead from '@/Components/SeoHead.vue';
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { Toaster } from "@/Components/ui/sonner";
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
import CarListingCard from "@/Components/CarListingCard.vue"; // Import CarListingCard
import BookingExtrasStep from "@/Components/BookingExtrasStep.vue"; // Import BookingExtrasStep
import BookingCheckoutStep from '@/Components/BookingCheckoutStep.vue'; // Import BookingExtrasStep
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import CaretDown from "../../assets/CaretDown.svg";
import fullStar from "../../assets/fullstar.svg";
import halfStar from "../../assets/halfstar.svg";
import blankStar from "../../assets/blankstar.svg";
import VueSlider from 'vue-slider-component';
import 'vue-slider-component/theme/default.css';
import Dropdown from "@/Components/Dropdown.vue";
import moneyExchangeSymbol from '../../assets/money-exchange-symbol.svg';

import { useCurrency } from '@/composables/useCurrency';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';

const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();
const { convertPrice, fetchExchangeRates } = useCurrencyConversion();
const page = usePage();

const providerMarkupRate = computed(() => {
    const rawRate = parseFloat(page.props.provider_markup_rate ?? '');
    if (Number.isFinite(rawRate) && rawRate >= 0) return rawRate;
    const rawPercent = parseFloat(page.props.provider_markup_percent ?? '');
    if (Number.isFinite(rawPercent) && rawPercent >= 0) return rawPercent / 100;
    return 0.15;
});

// Currency names mapping for better display
const currencyNames = {
    'USD': 'United States Dollar',
    'EUR': 'Euro',
    'GBP': 'British Pound Sterling',
    'JPY': 'Japanese Yen',
    'AUD': 'Australian Dollar',
    'CAD': 'Canadian Dollar',
    'CHF': 'Swiss Franc',
    'CNH': 'Chinese Yuan',
    'HKD': 'Hong Kong Dollar',
    'SGD': 'Singapore Dollar',
    'SEK': 'Swedish Krona',
    'KRW': 'South Korean Won',
    'NOK': 'Norwegian Krone',
    'NZD': 'New Zealand Dollar',
    'INR': 'Indian Rupee',
    'MXN': 'Mexican Peso',
    'BRL': 'Brazilian Real',
    'RUB': 'Russian Ruble',
    'ZAR': 'South African Rand',
    'AED': 'United Arab Emirates Dirham',
    'MAD': 'Moroccan Dirham',
    'TRY': 'Turkish Lira',
    'JOD': 'Jordanian Dinar',
    'ISK': 'Iceland Krona',
    'AZN': 'Azerbaijanian Manat',
    'MYR': 'Malaysian Ringgit',
    'OMR': 'Rial Omani',
    'UGX': 'Uganda Shilling',
    'NIO': 'Nicaragua Cordoba Oro'
};

// Currency symbols mapping
const currencySymbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'AUD': 'A$',
    'CAD': 'C$',
    'CHF': 'Fr',
    'CNH': '¥',
    'HKD': 'HK$',
    'SGD': 'S$',
    'SEK': 'kr',
    'KRW': '₩',
    'NOK': 'kr',
    'NZD': 'NZ$',
    'INR': '₹',
    'MXN': '$',
    'BRL': 'R$',
    'RUB': '₽',
    'ZAR': 'R',
    'AED': 'د.إ',
    'MAD': 'د.م.‏',
    'TRY': '₺',
    'JOD': 'د.ا.‏',
    'ISK': 'kr.',
    'AZN': '₼',
    'MYR': 'RM',
    'OMR': '﷼',
    'UGX': 'USh',
    'NIO': 'C$'
};

// Function to format currency display
const formatCurrencyDisplay = (currency) => {
    const name = currencyNames[currency] || currency;
    const symbol = currencySymbols[currency] || '';
    return `${currency}(${name})${symbol}`;
};

// Function to format currency display for the trigger
const formatCurrencyTriggerDisplay = (currency) => {
    const symbol = currencySymbols[currency] || '';
    return `${currency}(${symbol})`;
};

const convertCurrency = (price, fromCurrency) => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) {
        return 0;
    }

    return convertPrice(numericPrice, fromCurrency);
};

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
    seo: Object,
    locale: String, // Added locale prop
    okMobilityVehicles: Object, // New: OK Mobility vehicles data
    renteonVehicles: Object, // New: Renteon vehicles data
    providerStatus: Array,
    searchError: String,
    optionalExtras: Array, // GreenMotion optional extras
    locationName: String, // Location Name
    // Price verification props
    search_session_id: String, // Unique session ID for price verification
    price_map: Object, // Map of vehicle_id => {price_hash, vehicle_id_hash}
});

const formatProviderLabel = (value) => {
    const text = `${value || ''}`.trim();
    if (!text) return 'Provider';
    return text
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const providerStatus = computed(() => props.providerStatus || []);
const providerStatusErrors = computed(() =>
    providerStatus.value.filter((item) => item && item.status === 'error')
);
const providerStatusErrorLabels = computed(() => {
    const labels = providerStatusErrors.value.map((item) => formatProviderLabel(item.provider));
    return Array.from(new Set(labels)).filter(Boolean);
});
const hasProviderErrors = computed(() => providerStatusErrorLabels.value.length > 0);
const searchErrorMessage = computed(() => `${props.searchError || ''}`.trim());
const hasSearchError = computed(() => searchErrorMessage.value.length > 0);

// SPA Booking State
const bookingStep = ref('results'); // 'results' | 'extras' | 'checkout'
const selectedVehicle = ref(null);
const selectedPackage = ref(null);
const selectedProtectionCode = ref(null);

const selectedBookingExtras = ref({});
const locationInstructions = ref(null);
const locationDetails = ref(null);
const driverRequirements = ref(null);
const termsData = ref(null);
const greenMotionCountries = ref(null);
const termsCountryId = ref(null);
// Initialize with 15% as default to prevent "Pay 0" bug - will be updated from API
const paymentPercentage = ref(15);

const fetchLocationDetails = async (locationId) => {
    if (!locationId) return;
    try {
        const response = await axios.get(route('green-motion-locations'), {
            params: { location_id: locationId }
        });
        if (response.data) {
            locationDetails.value = response.data;
            locationInstructions.value = response.data.collection_details || null;
        } else {
            locationDetails.value = null;
            locationInstructions.value = null;
        }
    } catch (error) {
        console.error("Error fetching location details:", error);
        locationInstructions.value = null;
        locationDetails.value = null;
    }
};

const fetchGreenMotionCountries = async () => {
    if (greenMotionCountries.value) return greenMotionCountries.value;
    try {
        const response = await axios.get(route('green-motion-countries'));
        greenMotionCountries.value = Array.isArray(response.data) ? response.data : [];
        return greenMotionCountries.value;
    } catch (error) {
        console.error('Error fetching Green Motion countries:', error);
        greenMotionCountries.value = [];
        return [];
    }
};

const fetchGreenMotionTerms = async (countryId) => {
    if (!countryId) {
        termsData.value = null;
        return;
    }
    try {
        const response = await axios.get(route('green-motion-terms-and-conditions'), {
            params: { country_id: countryId, language: form.language || 'en' }
        });
        termsData.value = Array.isArray(response.data) ? response.data : null;
    } catch (error) {
        console.error('Error fetching Green Motion terms:', error);
        termsData.value = null;
    }
};

const resolveGreenMotionRequirements = async () => {
    const countryName = form.country || '';
    if (!countryName) {
        driverRequirements.value = null;
        termsData.value = null;
        termsCountryId.value = null;
        return;
    }

    const countries = await fetchGreenMotionCountries();
    const match = countries.find(country =>
        `${country.countryName || ''}`.toLowerCase() === countryName.toLowerCase()
    );

    if (!match) {
        driverRequirements.value = null;
        termsData.value = null;
        termsCountryId.value = null;
        return;
    }

    driverRequirements.value = match.driver_requirements || null;
    termsCountryId.value = match.countryID || null;
    await fetchGreenMotionTerms(termsCountryId.value);
};

const scrollToSection = async (id) => {
    await nextTick();
    const element = document.getElementById(id);
    if (!element) return;
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
};


const handlePackageSelection = (event) => {
    // Event contains { vehicle, package, protection_code }
    console.log('Package Selected:', event);

    // Attach price_hash from price_map to vehicle for verification
    const vehicleWithPriceHash = { ...event.vehicle };
    if (props.price_map && event.vehicle) {
        const vehicleId = event.vehicle.id || event.vehicle.vehicle_id || event.vehicle.api_vehicle_id;
        if (vehicleId && props.price_map[vehicleId]) {
            vehicleWithPriceHash.price_hash = props.price_map[vehicleId].price_hash;
        }
    }

    selectedVehicle.value = vehicleWithPriceHash;
    selectedPackage.value = event.package;
    selectedProtectionCode.value = event.protection_code || null;
    bookingStep.value = 'extras';

    // Fetch location details if GreenMotion
    if (event.vehicle.source === 'greenmotion' || event.vehicle.source === 'usave') {
        const locId = event.vehicle.location_id;
        if (locId) {
            fetchLocationDetails(locId);
        } else {
            locationInstructions.value = null;
            locationDetails.value = null;
        }
        resolveGreenMotionRequirements();
    } else if (event.vehicle.location_details) {
        locationDetails.value = event.vehicle.location_details;
        locationInstructions.value = event.vehicle.location_instructions
            || event.vehicle.location_details.collection_details
            || null;
        driverRequirements.value = null;
        termsData.value = null;
        termsCountryId.value = null;
    } else {
        locationInstructions.value = null;
        locationDetails.value = null;
        driverRequirements.value = null;
        termsData.value = null;
        termsCountryId.value = null;
    }

    scrollToSection('extras-breadcrumb-section');
};

const handleBackToResults = () => {
    bookingStep.value = 'results';
    selectedVehicle.value = null;
    selectedPackage.value = null;
    selectedProtectionCode.value = null;
    scrollToSection('results-breadcrumb-section');
};

const selectedCheckoutData = ref(null);

const handleProceedToCheckout = (data) => {
    // console.log('Proceed to Checkout:', data);
    const vehicleTotal = data.vehicle_total ?? selectedVehicle.value?.total_price ?? 0;
    selectedCheckoutData.value = {
        ...data,
        vehicle_total: vehicleTotal
    };
    bookingStep.value = 'checkout';
    scrollToSection('checkout-form-section');
};

const handleBackToExtras = () => {
    bookingStep.value = 'extras';
    scrollToSection('extras-breadcrumb-section');
};

// Moved to consolidated onMounted at the bottom

const loadCurrencyData = async () => {
    await fetchExchangeRates();
    try {
        const response = await fetch('/currency.json');
        const data = await response.json();
        const fetchedSymbols = data.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
        // Merge with existing currencySymbols, overriding any duplicates
        Object.assign(currencySymbols, fetchedSymbols);

        // Update map markers with correct currency once data is loaded
        if (map) {
            addMarkers();
        }

    } catch (error) {
        console.error("Error loading currency symbols:", error);
    }
};

const getCurrencySymbol = (code) => {
    if (!currencySymbols) {
        return '$';
    }
    return currencySymbols[code] || '$'; // Use fetched symbol or default to '$'
};



const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};
const isCustomer = computed(() => {
    return page.props.auth?.user?.role === 'customer';
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
    unified_location_id: usePage().props.filters.unified_location_id || null,
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
    dropoff_unified_location_id: usePage().props.filters?.dropoff_unified_location_id || null,
    dropoff_where: usePage().props.filters?.dropoff_where || "",
    dropoff_latitude: usePage().props.filters?.dropoff_latitude || null,
    dropoff_longitude: usePage().props.filters?.dropoff_longitude || null,
});

class ValidationError extends Error {
    constructor(message, field) {
        super(message);
        this.name = "ValidationError";
        this.field = field;
    }
}

const dateValidationError = ref("");
const pastDateErrorMessage = "Dates can't be in the past. Please choose today or later.";
const isSanitizingDates = ref(false);
const lastValidDates = ref({
    date_from: "",
    date_to: "",
});

const getTodayStart = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
};

const formatDateForInput = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

const parseDateInput = (value) => {
    if (value instanceof Date) {
        return new Date(value.getFullYear(), value.getMonth(), value.getDate());
    }

    if (typeof value !== "string" || value.trim() === "") {
        return null;
    }

    const [datePart] = value.split("T");
    const parts = datePart.split("-");
    if (parts.length !== 3) return null;

    const [yearStr, monthStr, dayStr] = parts;
    const year = Number(yearStr);
    const month = Number(monthStr);
    const day = Number(dayStr);
    if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
        return null;
    }

    const parsed = new Date(year, month - 1, day);
    if (
        parsed.getFullYear() !== year ||
        parsed.getMonth() !== month - 1 ||
        parsed.getDate() !== day
    ) {
        return null;
    }

    return parsed;
};

const validateSearchDates = ({ date_from, date_to }) => {
    const today = getTodayStart();
    const normalize = (value, field) => {
        if (typeof value === "undefined") {
            return { ok: true, value: undefined };
        }
        if (value === "" || value === null) {
            return { ok: true, value: "" };
        }
        const parsed = parseDateInput(value);
        if (!parsed) {
            return {
                ok: false,
                error: new ValidationError("Please enter a valid date.", field),
            };
        }
        if (parsed < today) {
            return {
                ok: false,
                error: new ValidationError(
                    pastDateErrorMessage,
                    field
                ),
            };
        }
        return { ok: true, value: formatDateForInput(parsed) };
    };

    const fromResult = normalize(date_from, "date_from");
    if (!fromResult.ok) return fromResult;

    const toResult = normalize(date_to, "date_to");
    if (!toResult.ok) return toResult;

    return {
        ok: true,
        value: {
            date_from: fromResult.value,
            date_to: toResult.value,
        },
    };
};

const getDateParamsFromUrl = (rawUrl) => {
    if (!rawUrl || typeof rawUrl !== "string") {
        return { date_from: undefined, date_to: undefined };
    }

    const [, queryString = ""] = rawUrl.split("?");
    if (!queryString) {
        return { date_from: undefined, date_to: undefined };
    }

    const params = new URLSearchParams(queryString);
    const dateFrom = params.get("date_from");
    const dateTo = params.get("date_to");

    return {
        date_from: dateFrom === null ? undefined : dateFrom,
        date_to: dateTo === null ? undefined : dateTo,
    };
};

const applyValidatedDates = (proposedDates) => {
    if (isSanitizingDates.value) return;
    isSanitizingDates.value = true;

    let errorMessage = "";
    const nextDates = {
        date_from: form.date_from,
        date_to: form.date_to,
    };

    const fromResult = validateSearchDates({ date_from: proposedDates?.date_from });
    if (fromResult.ok) {
        if (typeof fromResult.value.date_from !== "undefined") {
            nextDates.date_from = fromResult.value.date_from;
            if (nextDates.date_from) {
                lastValidDates.value.date_from = nextDates.date_from;
            }
        }
    } else {
        errorMessage = fromResult.error.message;
        nextDates.date_from = lastValidDates.value.date_from || formatDateForInput(getTodayStart());
        if (!lastValidDates.value.date_from) {
            lastValidDates.value.date_from = nextDates.date_from;
        }
    }

    const toResult = validateSearchDates({ date_to: proposedDates?.date_to });
    if (toResult.ok) {
        if (typeof toResult.value.date_to !== "undefined") {
            nextDates.date_to = toResult.value.date_to;
            if (nextDates.date_to) {
                lastValidDates.value.date_to = nextDates.date_to;
            }
        }
    } else {
        if (!errorMessage) {
            errorMessage = toResult.error.message;
        }
        let fallbackDateTo = formatDateForInput(getTodayStart());
        const startDateObj = parseDateInput(nextDates.date_from);
        if (startDateObj) {
            const nextDayEntry = new Date(startDateObj);
            nextDayEntry.setDate(nextDayEntry.getDate() + 1);
            fallbackDateTo = formatDateForInput(nextDayEntry);
        }
        nextDates.date_to = lastValidDates.value.date_to || fallbackDateTo;
        if (!lastValidDates.value.date_to) {
            lastValidDates.value.date_to = nextDates.date_to;
        }
    }

    if (nextDates.date_from !== form.date_from) {
        form.date_from = nextDates.date_from;
    }
    if (nextDates.date_to !== form.date_to) {
        form.date_to = nextDates.date_to;
    }

    dateValidationError.value = errorMessage;
    isSanitizingDates.value = false;
};

watch(dateValidationError, (newValue) => {
    if (newValue !== pastDateErrorMessage) return;

    sonnerToast.error(newValue);
    dateValidationError.value = "";
});

const numberOfRentalDays = computed(() => {
    if (!form.date_from || !form.date_to) return 1;
    const start = new Date(`${form.date_from}T${form.start_time || '00:00'}`);
    const end = new Date(`${form.date_to}T${form.end_time || '00:00'}`);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays || 1;
});

watch(
    () => [usePage().url, usePage().props.filters?.date_from, usePage().props.filters?.date_to],
    ([rawUrl, propDateFrom, propDateTo]) => {
        const { date_from, date_to } = getDateParamsFromUrl(rawUrl);
        applyValidatedDates({
            date_from: typeof date_from === "undefined" ? propDateFrom : date_from,
            date_to: typeof date_to === "undefined" ? propDateTo : date_to,
        });
    },
    { immediate: true }
);


const serverParams = computed(() => {
    const params = {
        date_from: form.date_from,
        date_to: form.date_to,
        where: form.where,
        latitude: form.latitude,
        longitude: form.longitude,
        radius: form.radius,
        package_type: form.package_type,
        city: form.city,
        state: form.state,
        country: form.country,
        matched_field: form.matched_field,
        location: form.location,
        provider: form.provider,
        provider_pickup_id: form.provider_pickup_id,
        unified_location_id: form.unified_location_id,
        start_time: form.start_time,
        end_time: form.end_time,
        age: form.age,
        rentalCode: form.rentalCode,
        userid: form.userid,
        username: form.username,
        language: form.language,
        full_credit: form.full_credit,
        promocode: form.promocode,
        dropoff_location_id: form.dropoff_location_id,
        dropoff_unified_location_id: form.dropoff_unified_location_id,
        dropoff_where: form.dropoff_where,
        dropoff_latitude: form.dropoff_latitude,
        dropoff_longitude: form.dropoff_longitude,
    };

    if (params.matched_field && (params.city || params.state || params.country)) {
        delete params.radius;
    }

    return params;
});

const submitFilters = debounce(() => {
    router.get(`/${page.props.locale}/s`, serverParams.value, {
        preserveState: true,
        preserveScroll: true,
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
    () => serverParams.value,
    () => {
        submitFilters();
    },
    { deep: true }
);
let map = null;
// mobileMap removed
let markers = [];
// mobileMarkers removed

const allVehiclesForMap = computed(() => {
    // Source of truth: backend already returns a combined list in props.vehicles.
    // Still defensively de-dupe by id so UI pagination never hides vehicles.
    const base = props.vehicles?.data || [];
    const merged = [...base, ...(props.okMobilityVehicles?.data || []), ...(props.renteonVehicles?.data || [])];
    const byId = new Map();
    merged.forEach(v => {
        if (!v || !v.id) return;
        if (!byId.has(v.id)) byId.set(v.id, v);
    });
    return Array.from(byId.values());
});

// Helper to get vehicle price in selected currency
const grossUpProviderPrice = (value, vehicle) => {
    const numeric = parseFloat(value);
    if (!Number.isFinite(numeric)) return null;
    // Apply 15% platform fee to ALL vehicles (including internal)
    const rate = providerMarkupRate.value;
    if (!Number.isFinite(rate) || rate <= 0) return numeric;
    return numeric * (1 + rate);
};

const getVehiclePriceConverted = (vehicle) => {
    if (!vehicle) return null;

    let originalPrice = null;
    let originalCurrency = 'USD';

    // Determine price and currency based on source
    if (vehicle.source === 'adobe' && vehicle.tdr) {
        // For Adobe, use tdr / rental days (or use price_per_day if already calculated)
        originalPrice = vehicle.price_per_day || (vehicle.tdr / (numberOfRentalDays.value || 1));
        originalCurrency = 'USD';
    } else if (vehicle.source === 'wheelsys' || vehicle.source === 'locauto_rent' || vehicle.source === 'sicily_by_car' || vehicle.source === 'recordgo') {
        originalPrice = vehicle.price_per_day;
        originalCurrency = vehicle.currency || 'USD';
    } else if (vehicle.source === 'greenmotion' || vehicle.source === 'usave') {
        // For GreenMotion/USave, use the total rental price directly
        originalPrice = parseFloat(vehicle.products?.[0]?.total || 0);
        originalCurrency = vehicle.products?.[0]?.currency || 'USD';
    } else if (vehicle.source === 'okmobility') {
        originalPrice = vehicle.price_per_day;
        originalCurrency = 'EUR';
    } else if (vehicle.source === 'renteon') {
        // For Renteon, use price_per_day or calculate from products
        originalPrice = vehicle.price_per_day || parseFloat(vehicle.products?.[0]?.total || 0);
        originalCurrency = vehicle.currency || vehicle.products?.[0]?.currency || 'EUR';
    } else if (vehicle.source === 'favrica') {
        const days = numberOfRentalDays.value || 1;
        originalPrice = vehicle.price_per_day || (parseFloat(vehicle.products?.[0]?.total || 0) / days);
        originalCurrency = vehicle.currency || vehicle.products?.[0]?.currency || 'EUR';
    } else {
        // Internal vehicles
        originalPrice = vehicle.price_per_day;
        originalCurrency = vehicle.currency || vehicle.vendor_profile?.currency || 'USD';
    }

    if (originalPrice === null || isNaN(parseFloat(originalPrice))) return null;

    const converted = convertCurrency(parseFloat(originalPrice), originalCurrency);
    if (converted === null) return null;
    return grossUpProviderPrice(converted, vehicle);
};

// Compute dynamic min/max price range from all vehicles
const dynamicPriceRange = computed(() => {
    const vehicles = allVehiclesForMap.value || [];

    if (vehicles.length === 0) {
        return { min: 0, max: 1000 };
    }

    const prices = vehicles
        .map(v => getVehiclePriceConverted(v))
        .filter(p => p !== null && p > 0);

    if (prices.length === 0) {
        return { min: 0, max: 1000 };
    }

    const min = Math.floor(Math.min(...prices));
    const max = Math.ceil(Math.max(...prices));

    // Add some padding for better UX
    return {
        min: Math.max(0, min - 10),
        max: max + 50
    };
});

// Normalization Helper
const getVehicleCategory = (vehicle) => {
    // 1. Try SIPP code char 0
    let sipp = vehicle.sipp || vehicle.sipp_code;
    if (sipp && sipp.length === 4) {
        const char = sipp.charAt(0).toUpperCase();
        const map = {
            'M': 'Mini', 'N': 'Mini',
            'E': 'Economy', 'H': 'Economy',
            'C': 'Compact', 'D': 'Compact',
            'I': 'Intermediate',
            'S': 'Standard',
            'F': 'Fullsize',
            'P': 'Premium',
            'L': 'Luxury', 'X': 'Special',
            'J': 'SUV', 'G': 'SUV', 'R': 'SUV', 'K': 'Van', 'V': 'Van'
        };
        if (map[char]) return map[char];
    }

    // 2. Try mapped categories from internal ID or name
    // internal vehicles use 'category_id' usually
    const catId = vehicle.vehicle_category_id || vehicle.category_id;
    if (catId && props.categories) {
        const cat = props.categories.find(c => c.id === catId);
        if (cat) return cat.name;
    }

    // 3. Try string matching on name/description/category field
    const name = (vehicle.category || vehicle.group || vehicle.vehicle_name || '').toLowerCase();
    if (name.includes('suv') || name.includes('4x4') || name.includes('jeep')) return 'SUV';
    if (name.includes('van') || name.includes('minivan')) return 'Van';
    if (name.includes('luxury') || name.includes('prem')) return 'Luxury';
    if (name.includes('estate') || name.includes('wagon')) return 'Estate';
    if (name.includes('convertible') || name.includes('cabrio')) return 'Convertible';
    if (name.includes('mini')) return 'Mini';
    if (name.includes('eco')) return 'Economy';
    if (name.includes('compact')) return 'Compact';
    if (name.includes('stand')) return 'Standard';
    if (name.includes('inter')) return 'Intermediate';
    if (name.includes('full')) return 'Fullsize';

    return 'Other'; // Fallback
};

// Main Client-Side Filtering
const sortBy = ref('recommended');
const viewMode = ref('grid'); // 'grid' or 'list'
const showSortDropdown = ref(false);
const sortDropdownRef = ref(null);

const selectSort = (value) => {
    sortBy.value = value;
    showSortDropdown.value = false;
};

// Pagination / Load More
const displayLimit = ref(12);
const loadMore = () => {
    displayLimit.value += 12;
};

const paginatedVehicles = computed(() => {
    return clientFilteredVehicles.value.slice(0, displayLimit.value);
});

// Reset pagination when filters change
watch([form, sortBy], () => {
    displayLimit.value = 12;
}, { deep: true });


// Close sort dropdown when clicking outside
const handleSortDropdownClickOutside = (event) => {
    if (sortDropdownRef.value && !sortDropdownRef.value.contains(event.target)) {
        showSortDropdown.value = false;
    }
};

// Add click listener on mount
watch(showSortDropdown, (isOpen) => {
    if (isOpen) {
        document.addEventListener('click', handleSortDropdownClickOutside);
    } else {
        document.removeEventListener('click', handleSortDropdownClickOutside);
    }
});

const clientFilteredVehicles = computed(() => {
    // Avoid in-place mutation by creating a shallow copy
    let result = [...allVehiclesForMap.value];

    // 1. Price Filter (Reused logic)
    if (form.price_range && form.price_range !== `${dynamicPriceRange.value.min}-${dynamicPriceRange.value.max}`) {
        const [minPrice, maxPrice] = form.price_range.split('-').map(Number);
        result = result.filter(vehicle => {
            const convertedPrice = getVehiclePriceConverted(vehicle);
            if (convertedPrice === null) return true;
            return convertedPrice >= minPrice && convertedPrice <= maxPrice;
        });
    }

    // 2. Brand Filter
    if (form.brand) {
        const selectedBrand = form.brand.toLowerCase();
        result = result.filter(v => {
            const b = (v.brand || v.make || v.vehicle_name || '').toLowerCase();
            return b === selectedBrand || b.includes(selectedBrand);
        });
    }

    // 3. Category Filter
    if (form.category_id) {
        // If categories are normalized names, form.category_id might be a name string now?
        // Wait, props.categories are objects {id, name}. The form uses IDs for internal?
        // For client-side unify, we should probably toggle by Name if we normalize everything.
        // User wants "SUV", "Luxury".
        // Let's assume form.category_id handles the label (Name) if we switch input values.
        // Or if it's ID, we need to map back.
        // Simplify: Let's assume the new Checklist input will pass the 'Name' as value if we update the template.
        // Checking current template: :value="category.id", label="category.name".
        // User said "show categories like suv, luxury...".
        // I should normalize the internal categories list too to these standard names.
        // For now, let's filter by matching normalized category to selected 'Name'.
        // I will update the template to pass Name.
        const selectedCat = isNaN(form.category_id) ? form.category_id : props.categories.find(c => c.id == form.category_id)?.name;
        if (selectedCat) {
            result = result.filter(v => getVehicleCategory(v) === selectedCat);
        }
    }

    // 4. Seats Filter
    if (form.seating_capacity) {
        const seats = parseInt(form.seating_capacity);
        result = result.filter(v => {
            const s = parseInt(v.seating_capacity || v.passenger_capacity || v.passengers || v.adults || v.seat_number || v.seats || 0);
            return s >= seats; // Logic: "4 seats" filter means 4 or 4+? User usually wants explicit count or min.
            // "5 seats" usually means strict 5 or 5+. Let's do exact if standard dropdown, or >=.
            // Given "4, 5, 7" options, exact match is often better for "Passenger Capacity" unless it's "4+ ".
            // Let's use exact match for now as per usual Facet logic, or maybe >=.
            // Actually, for "5 seats", a 7 seater is acceptable? Usually yes.
            // But let's stick to equality for facets count accuracy if options are discrete numbers.
            return s == seats;
        });
    }

    // 5. Transmission
    if (form.transmission) {
        const trans = form.transmission.toLowerCase();
        result = result.filter(v => {
            // SIPP char 2: M=Manual, A=Auto, N=Manual, B=Auto, C=Manual, D=Auto
            let vTrans = (v.transmission || v.transmission_type || '').toLowerCase();
            const sipp = v.sipp || v.sipp_code;
            if (sipp && sipp.length === 4) {
                const char = sipp.charAt(2).toUpperCase();
                if (['M', 'N', 'C'].includes(char)) vTrans = 'manual';
                if (['A', 'B', 'D'].includes(char)) vTrans = 'automatic';
            }
            return vTrans.includes(trans);
        });
    }

    // 6. Fuel
    if (form.fuel) {
        const fuel = form.fuel.toLowerCase();
        result = result.filter(v => {
            // SIPP char 3
            let vFuel = (v.fuel || v.fuel_type || '').toLowerCase();
            const sipp = v.sipp || v.sipp_code;
            if (sipp && sipp.length === 4) {
                const char = sipp.charAt(3).toUpperCase();
                if (['D', 'Q'].includes(char)) vFuel = 'diesel';
                else if (['H', 'I'].includes(char)) vFuel = 'hybrid';
                else if (['E', 'C'].includes(char)) vFuel = 'electric';
                else vFuel = 'petrol';
            }
            return vFuel.includes(fuel);
        });
    }

    // 7. Sort (Only if not 'recommended' to maintain default order)
    if (sortBy.value !== 'recommended') {
        result.sort((a, b) => {
            const priceA = getVehiclePriceConverted(a);
            const priceB = getVehiclePriceConverted(b);

            // Handle nulls (missing prices) - push to end of list
            if (priceA === null && priceB === null) return 0;
            if (priceA === null) return 1;
            if (priceB === null) return -1;

            if (sortBy.value === 'price_asc') {
                return priceA - priceB;
            } else if (sortBy.value === 'price_desc') {
                return priceB - priceA;
            }
            return 0;
        });
    }

    return result;
});

// Dynamic Facets
const facets = computed(() => {
    const all = allVehiclesForMap.value;
    // We need counts based on "current filters EXCEPT self" -> Multi-select facet logic.
    // But our UI is single-select for now (radio/toggle).
    // If single select:
    // Available options are those present in the filtered set?
    // Reference: "I don't want to filter fake... only need to show filter values according to availability".
    // If I select "SUV", the "SUV" count is N. "Economy" count should be visible (M) to allow switching?
    // YES. Standard ecommerce: Facets show counts based on current filters excluding the facet's own filter.
    // OR simpler: Counts of global set available matching other active filters.

    // Helper to count
    const countBy = (collection, keyFn) => {
        const counts = {};
        collection.forEach(item => {
            const key = keyFn(item);
            if (key) counts[key] = (counts[key] || 0) + 1;
        });
        return counts;
    };

    // For simplicty and robust "switching", we often compute counts against the set filtered by everything ELSE.
    // 1. Brands
    // Filter by everything except Brand
    const brandBase = clientFilteredVehicles.value.length > 0 ? clientFilteredVehicles.value : all; // Logic flaw: if Brand is filtered to Toyota, clientFilteredVehicles only has Toyota.
    // Correct approach:
    // Base for Brand = Set filtered by (Price AND Category AND Seats AND Trans AND Fuel).
    // Base for Category = Set filtered by (Price AND Brand AND Seats ...).

    const filterExcluding = (excludeField) => {
        return all.filter(v => {
            // Check Price
            if (form.price_range && form.price_range !== `${dynamicPriceRange.value.min}-${dynamicPriceRange.value.max}`) {
                const [min, max] = form.price_range.split('-').map(Number);
                const p = getVehiclePriceConverted(v);
                if (p !== null && (p < min || p > max)) return false;
            }
            // Check Brand
            if (excludeField !== 'brand' && form.brand) {
                const b = (v.brand || v.make || v.vehicle_name || '').toLowerCase();
                if (b !== form.brand.toLowerCase() && !b.includes(form.brand.toLowerCase())) return false;
            }
            // Check Category
            if (excludeField !== 'category' && form.category_id) {
                const selectedCat = isNaN(form.category_id) ? form.category_id : props.categories.find(c => c.id == form.category_id)?.name;
                if (getVehicleCategory(v) !== selectedCat) return false;
            }
            // Check Seats
            if (excludeField !== 'seats' && form.seating_capacity) {
                const s = parseInt(v.seating_capacity || v.passenger_capacity || v.passengers || v.adults || v.seat_number || v.seats || 0);
                if (s != parseInt(form.seating_capacity)) return false;
            }
            // Check Trans
            if (excludeField !== 'transmission' && form.transmission) {
                let vTrans = (v.transmission || v.transmission_type || '').toLowerCase();
                const sipp = v.sipp || v.sipp_code;
                if (sipp && sipp.length === 4) {
                    const char = sipp.charAt(2).toUpperCase();
                    if (['M', 'N', 'C'].includes(char)) vTrans = 'manual';
                    if (['A', 'B', 'D'].includes(char)) vTrans = 'automatic';
                }
                if (!vTrans.includes(form.transmission.toLowerCase())) return false;
            }
            // Check Fuel
            if (excludeField !== 'fuel' && form.fuel) {
                let vFuel = (v.fuel || v.fuel_type || '').toLowerCase();
                // ... normalize fuel same as above ...
                const sipp = v.sipp || v.sipp_code;
                if (sipp && sipp.length === 4) {
                    const char = sipp.charAt(3).toUpperCase();
                    if (['D', 'Q'].includes(char)) vFuel = 'diesel';
                    else if (['H', 'I'].includes(char)) vFuel = 'hybrid';
                    else if (['E', 'C'].includes(char)) vFuel = 'electric';
                    else vFuel = 'petrol';
                }
                if (!vFuel.includes(form.fuel.toLowerCase())) return false;
            }
            return true;
        });
    };

    const brandCounts = countBy(filterExcluding('brand'), v => (v.brand || v.make || v.vehicle_name || 'Other').trim());
    const categoryCounts = countBy(filterExcluding('category'), v => getVehicleCategory(v));
    const transmissionCounts = countBy(filterExcluding('transmission'), v => {
        let vTrans = (v.transmission || v.transmission_type || 'Manual').toLowerCase();
        const sipp = v.sipp || v.sipp_code;
        if (sipp && sipp.length === 4) {
            const char = sipp.charAt(2).toUpperCase();
            if (['M', 'N', 'C'].includes(char)) vTrans = 'manual';
            if (['A', 'B', 'D'].includes(char)) vTrans = 'automatic';
        }
        return vTrans.charAt(0).toUpperCase() + vTrans.slice(1);
    });
    const fuelCounts = countBy(filterExcluding('fuel'), v => {
        let vFuel = (v.fuel || v.fuel_type || 'Petrol').toLowerCase();
        const sipp = v.sipp || v.sipp_code;
        if (sipp && sipp.length === 4) {
            const char = sipp.charAt(3).toUpperCase();
            if (['D', 'Q'].includes(char)) vFuel = 'diesel';
            else if (['H', 'I'].includes(char)) vFuel = 'hybrid';
            else if (['E', 'C'].includes(char)) vFuel = 'electric';
            else vFuel = 'petrol';
        }
        return vFuel.charAt(0).toUpperCase() + vFuel.slice(1);
    });
    const seatCounts = countBy(filterExcluding('seats'), v => parseInt(v.seating_capacity || v.passenger_capacity || v.passengers || v.adults || v.seat_number || v.seats || 0));

    return {
        brands: Object.entries(brandCounts).map(([k, v]) => ({ label: k, value: k, count: v })).sort((a, b) => b.count - a.count),
        categories: Object.entries(categoryCounts).map(([k, v]) => ({ label: k, value: k, count: v })).sort((a, b) => b.count - a.count),
        transmissions: Object.entries(transmissionCounts).map(([k, v]) => ({ label: k, value: k.toLowerCase(), count: v })),
        fuels: Object.entries(fuelCounts).map(([k, v]) => ({ label: k, value: k.toLowerCase(), count: v })),
        seats: Object.entries(seatCounts).map(([k, v]) => ({ label: `${k} Seats`, value: k, count: v })).sort((a, b) => parseInt(a.value) - parseInt(b.value))
    };
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

    L.control.zoom({ position: 'topright' }).addTo(map);

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
            map.setView([20, 0], 2);
        }
    }

    // Stadia Maps OSM Bright
    const stadiaKey = import.meta.env.VITE_STADIA_MAPS_API_KEY || '';
    const keyParam = stadiaKey ? `?api_key=${stadiaKey}` : '';
    L.tileLayer(`https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.png${keyParam}`, {
        attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://openstreetmap.org/copyright">OSM</a>',
        maxZoom: 20,
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
                            if (map.getZoom() < 10) map.setView(currentBounds.getCenter(), 13);
                            else map.panTo(currentBounds.getCenter());
                        } else {
                            map.fitBounds(currentBounds, { padding: [50, 50] });
                        }
                    }
                } else if (!map.getCenter() || (map.getCenter().lat === 20 && map.getCenter().lng === 0 && map.getZoom() === 2)) {
                    map.setView([20, 0], 2);
                }
            }
        }
    }, 200);
};



const createCustomIcon = (vehicle, isHighlighted = false) => {
    let currencySymbol = '$';
    let priceToDisplay = "N/A";
    let priceValue = null;

    if (vehicle.source === 'wheelsys') {
        const originalCurrency = vehicle.currency || 'USD';
        priceValue = convertCurrency(vehicle.price_per_day, originalCurrency);
        currencySymbol = getCurrencySymbol(selectedCurrency.value);
    } else if (vehicle.source !== 'internal') {
        // Handle specific providers that don't use the standard products array structure
        if (vehicle.source === 'adobe') {
            const total = parseFloat(vehicle.tdr || 0);
            priceValue = convertCurrency(total, 'USD');
        } else if ((vehicle.source === 'wheelsys' || vehicle.source === 'locauto_rent' || vehicle.source === 'renteon' || vehicle.source === 'favrica' || vehicle.source === 'sicily_by_car' || vehicle.source === 'recordgo') && vehicle.price_per_day) {
            priceValue = convertCurrency(vehicle.price_per_day, vehicle.currency || 'USD');
        } else {
            // For GreenMotion/USave, show total rental price
            const currencyCode = vehicle.products?.[0]?.currency || 'USD';
            const totalProviderPrice = parseFloat(vehicle.products?.[0]?.total || 0);
            priceValue = convertCurrency(totalProviderPrice, currencyCode);
        }
        currencySymbol = getCurrencySymbol(selectedCurrency.value);
    } else {
        const originalCurrency = vehicle.vendor_profile?.currency || 'USD';
        if (form.package_type === 'day' && vehicle.price_per_day) {
            priceValue = convertCurrency(vehicle.price_per_day, originalCurrency);
        } else if (form.package_type === 'week' && vehicle.price_per_week) {
            priceValue = convertCurrency(vehicle.price_per_week, originalCurrency);
        } else if (form.package_type === 'month' && vehicle.price_per_month) {
            priceValue = convertCurrency(vehicle.price_per_month, originalCurrency);
        } else {
            // Fallback if no package_type is selected or price for selected type is null
            if (vehicle.price_per_day) {
                priceValue = convertCurrency(vehicle.price_per_day, originalCurrency);
            } else if (vehicle.price_per_week) {
                priceValue = convertCurrency(vehicle.price_per_week, originalCurrency);
            } else if (vehicle.price_per_month) {
                priceValue = convertCurrency(vehicle.price_per_month, originalCurrency);
            }
        }
        currencySymbol = getCurrencySymbol(selectedCurrency.value);
    }

    if (vehicle.source !== 'internal' && priceValue !== null) {
        priceValue = grossUpProviderPrice(priceValue, vehicle);
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

    // Reset UI components to dynamic range
    priceRangeValues.value = [dynamicPriceRange.value.min, dynamicPriceRange.value.max];
    tempPriceRangeValues.value = [dynamicPriceRange.value.min, dynamicPriceRange.value.max];

    // Reset other non-essential GreenMotion params to default
    form.currency = null;
    form.userid = null;
    form.username = null;
    form.language = null;
    form.full_credit = null;
    form.promocode = null;
    form.dropoff_location_id = null;
    form.dropoff_unified_location_id = null;
    form.dropoff_latitude = null;
    form.dropoff_longitude = null;

    // The other fields like where, lat, lng, dates, source, etc., are NOT touched.
    // They will retain their current values.
    submitFilters();
};

const createPopupContent = (vehicle, primaryImage, popupPrice, detailRoute) => {
    // Shared content generator for consistency
    const popupName = vehicle?.source === 'okmobility'
        ? (vehicle.display_name || vehicle.group_description || vehicle.model || '')
        : `${vehicle.brand || ''} ${vehicle.model || ''}`.trim();
    const content = `
        <div class="text-center popup-content">
            <img src="${primaryImage}" alt="${popupName}" class="popup-image !w-40 !h-20" />
            ${vehicle.source === 'internal' && vehicle.average_rating ? `<p class="rating !w-40">${vehicle.average_rating.toFixed(1)} ★ (${vehicle.review_count} reviews)</p>` : ''}
            <p class="font-semibold !w-40">${popupName}</p>
            ${vehicle.sipp_code ? `<p class="!w-40 text-sm">SIPP: ${vehicle.sipp_code}</p>` : ''}
            ${vehicle.acriss_code || vehicle.group_code ? `<p class="!w-40 text-sm">${vehicle.acriss_code || vehicle.group_code}</p>` : ''}
            <p class="!w-40">${vehicle.full_vehicle_address || ''}</p>
            ${vehicle.station ? `<p class="!w-40 text-sm"><strong>Station:</strong> ${vehicle.station}</p>` : ''}
            <p class="!w-40 font-semibold">Price: ${popupPrice}</p>
            <button onclick="window.selectVehicleFromMap('${vehicle.id}')"
               class="text-white bg-[#153B4F] hover:bg-[#0f2936] block mt-2 px-4 py-2 rounded text-sm font-semibold w-full">
                Book Deal
            </button>
        </div>
    `;
    return content;
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

        const primaryImage = (vehicle.source === 'greenmotion' || vehicle.source === 'wheelsys' || vehicle.source === 'adobe' || vehicle.source === 'locauto_rent') ? vehicle.image : (vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png');
        const detailRoute = vehicle.source !== 'internal'
            ? route(getProviderRoute(vehicle), { locale: page.props.locale, id: vehicle.id, provider: vehicle.source, location_id: vehicle.provider_pickup_id, start_date: form.date_from, end_date: form.date_to, start_time: form.start_time, end_time: form.end_time, dropoff_location_id: form.dropoff_location_id, rentalCode: form.rentalCode })
            : route('vehicle.show', { locale: page.props.locale, id: vehicle.id, package: form.package_type, pickup_date: form.date_from, return_date: form.date_to });

        let popupPrice = "N/A";
        let popupCurrencySymbol = getCurrencySymbol(selectedCurrency.value);

        if (vehicle.source === 'wheelsys' && vehicle.price_per_day) {
            const originalCurrency = vehicle.currency || 'USD';
            const convertedPrice = convertCurrency(vehicle.price_per_day, originalCurrency);
            popupCurrencySymbol = getCurrencySymbol(selectedCurrency.value);
            popupPrice = `${popupCurrencySymbol}${convertedPrice.toFixed(2)} total`; // Display price per day (actually total)
        } else if (vehicle.source === 'adobe' && vehicle.tdr) {
            const convertedPrice = convertCurrency(vehicle.tdr, 'USD');
            popupCurrencySymbol = getCurrencySymbol(selectedCurrency.value);
            popupPrice = `${popupCurrencySymbol}${convertedPrice.toFixed(2)} total`; // Display total price
        } else if (vehicle.source !== 'internal' && vehicle.products && vehicle.products[0]?.total && vehicle.products[0].total > 0) {
            // For GreenMotion/USave, show total rental price
            const currencyCode = vehicle.products[0]?.currency || 'USD';
            const totalProviderPrice = parseFloat(vehicle.products[0]?.total || 0);
            const convertedPrice = convertCurrency(totalProviderPrice, currencyCode);
            popupCurrencySymbol = getCurrencySymbol(selectedCurrency.value);
            popupPrice = `${popupCurrencySymbol}${convertedPrice.toFixed(2)} total`; // Display total price
        } else if (vehicle.source === 'internal' && vehicle.price_per_day && vehicle.price_per_day > 0) {
            const originalCurrency = vehicle.vendor_profile?.currency || 'USD';
            const convertedPrice = convertCurrency(vehicle.price_per_day, originalCurrency);
            popupCurrencySymbol = getCurrencySymbol(selectedCurrency.value);
            popupPrice = `${popupCurrencySymbol}${convertedPrice.toFixed(2)}`;
        }

        const marker = L.marker([displayLat, displayLng], {
            icon: createCustomIcon(vehicle),
            pane: "markers",
        }).bindPopup(createPopupContent(vehicle, primaryImage, popupPrice, detailRoute));

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
            map.setView([20, 0], 2);
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

watch(selectedCurrency, () => {
    if (map) {
        addMarkers();
    }
});

// Exchange rates watch is no longer needed since we call addMarkers() directly in loadCurrencyData()

// This onMounted is now handled in the async function above to ensure proper loading order

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


const showMap = ref(false);
// showMobileMapModal is removed

// Watch for Map Modal visibility
watch(showMap, (newValue) => {
    if (newValue) {
        // Initialize map when modal opens
        setTimeout(() => {
            initMap();
        }, 100);
    } else {
        // Clean up map when modal closes
        if (map) {
            map.remove();
            map = null;
            markers = [];
        }
    }
});

// handleMapToggle removed

import { Inertia } from "@inertiajs/inertia";
import CustomDropdown from "@/Components/CustomDropdown.vue";
const favoriteStatus = ref({});
const favoriteLoading = ref({});

const fetchFavoriteStatus = async () => {
    if (!page.props.auth?.user) return;
    try {
        const allVehicles = allVehiclesForMap.value || [];
        if (allVehicles.length === 0) return;
        const response = await axios.get(route('favorites.status'));
        const favoriteIds = response.data; // Now an array of IDs
        const newStatus = {};
        allVehicles.forEach((vehicle) => {
            const vehicleKey = String(vehicle.id);
            newStatus[vehicle.id] = favoriteIds.includes(vehicleKey);
        });
        favoriteStatus.value = newStatus;
    } catch (error) {
        console.error("Error fetching favorite status:", error);
    }
};
const $page = usePage();

const popEffect = ref({});

const getFavoriteImage = (vehicle) => {
    if (!vehicle) return null;
    if (vehicle.image) return vehicle.image;
    return vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || null;
};

const buildProviderFavoritePayload = (vehicle) => {
    return {
        vehicle_key: String(vehicle.id),
        source: vehicle.source,
        brand: vehicle.brand || null,
        model: vehicle.model || null,
        image: getFavoriteImage(vehicle),
        price_per_day: vehicle.price_per_day ?? null,
        currency: vehicle.currency ?? null,
        provider_pickup_id: vehicle.provider_pickup_id ?? null,
        search: {
            where: form.where,
            dropoff_where: form.dropoff_where || null,
            provider: form.provider || null,
            provider_pickup_id: form.provider_pickup_id || null,
            unified_location_id: form.unified_location_id || null,
            dropoff_location_id: form.dropoff_location_id || null,
            dropoff_unified_location_id: form.dropoff_unified_location_id || null,
            date_from: form.date_from || null,
            date_to: form.date_to || null,
            start_time: form.start_time || null,
            end_time: form.end_time || null,
            rentalCode: form.rentalCode || null,
        },
    };
};

const toggleFavourite = async (vehicle) => {
    if (!$page.props.auth?.user) {
        return router.get(route('login', {}, usePage().props.locale));
    }

    const isFavorite = !!favoriteStatus.value[vehicle.id];
    favoriteLoading.value[vehicle.id] = true;

    try {
        if (vehicle.source === 'internal') {
            const endpoint = isFavorite
                ? route('vehicles.unfavourite', { vehicle: vehicle.id })
                : route('vehicles.favourite', { vehicle: vehicle.id });
            await axios.post(endpoint);
        } else {
            await axios.post(route('favorites.provider.toggle'), {
                vehicle_key: String(vehicle.id),
                source: vehicle.source,
                payload: buildProviderFavoritePayload(vehicle),
            });
        }

        favoriteStatus.value[vehicle.id] = !favoriteStatus.value[vehicle.id];

        if (favoriteStatus.value[vehicle.id]) {
            popEffect.value[vehicle.id] = true;
            setTimeout(() => {
                popEffect.value[vehicle.id] = false;
            }, 300);
        }

        sonnerToast.success(
            favoriteStatus.value[vehicle.id]
                ? "Vehicle added to favorite."
                : "Vehicle removed from favorite.",
            {
                position: "bottom-right",
                duration: 3000,
            }
        );
    } catch (error) {
        if (error.response && error.response.status === 401) {
            router.get(route('login', {}, usePage().props.locale));
        } else {
            sonnerToast.error("Failed to update favorite.", {
                position: "bottom-right",
                duration: 3000,
            });
        }
    } finally {
        favoriteLoading.value[vehicle.id] = false;
    }
};

// onMounted merged to the bottom of the script section

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
    // Handle Date objects directly (for fallbacks) or string dates
    const date = dateStr instanceof Date ? dateStr : new Date(dateStr);

    // Check if date is valid
    if (isNaN(date.getTime())) {
        return 'Select Date';
    }

    return `${String(date.getMonth() + 1).padStart(2, "0")}/${String(
        date.getDate()
    ).padStart(2, "0")}/${date.getFullYear()}`;
};

const displayDateFrom = computed(() => {
    if (form.date_from) return form.date_from;
    return new Date(); // Today
});

const displayDateTo = computed(() => {
    if (form.date_to) return form.date_to;
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow; // Tomorrow
});
const showRentalDates = ref(false);

const searchQuery = computed(() => {
    return {
        where: usePage().props.filters?.where || "",
        date_from: form.date_from || "",
        date_to: form.date_to || "",
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
        unified_location_id: usePage().props.filters?.unified_location_id || null,
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
        dropoff_unified_location_id: usePage().props.filters?.dropoff_unified_location_id || null,
        dropoff_where: usePage().props.filters?.dropoff_where || "",
        dropoff_latitude: usePage().props.filters?.dropoff_latitude || null,
        dropoff_longitude: usePage().props.filters?.dropoff_longitude || null,
    };
});

// Add this handler function to update the form with data from SearchBar
const handleSearchUpdate = (params) => {
    form.where = params.where || "";
    form.latitude = params.latitude || null;
    form.longitude = params.longitude || null;
    form.radius = params.radius || null;
    applyValidatedDates({ date_from: params.date_from, date_to: params.date_to });
    // Update missing location fields
    form.city = params.city || "";
    form.state = params.state || "";
    form.country = params.country || "";
    form.matched_field = params.matched_field || null;
    form.source = params.source || null;
    form.greenmotion_location_id = params.greenmotion_location_id || null;
    form.unified_location_id = params.unified_location_id || null;
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
    form.dropoff_unified_location_id = params.dropoff_unified_location_id || null;
    form.dropoff_where = params.dropoff_where || "";
    form.dropoff_latitude = params.dropoff_latitude || null;
    form.dropoff_longitude = params.dropoff_longitude || null;


    if (params.matched_field === 'location') {
        form.location = params.location_name || params.where || ""; // Use location_name if provided by SearchBar, else fallback
    } else {
        form.location = ""; // Clear if not a 'location' type match
    }
    // The watch on form.data() will automatically trigger submitFilters.
};

const showMobileFilters = ref(false);

const toggleMobileFilters = () => {
    showMobileFilters.value = !showMobileFilters.value;
};

// Filter accordion state
const expandedFilters = ref({
    price: true,
    category: true,
    transmission: true,
    fuel: true,
    seats: true
});

const toggleFilterSection = (section) => {
    expandedFilters.value[section] = !expandedFilters.value[section];
};

const applyFilters = () => {
    showMobileFilters.value = false;
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (form.price_range) count++;
    if (form.seating_capacity) count++;
    if (form.brand) count++;
    if (form.transmission) count++;
    if (form.fuel) count++;
    if (form.category_id) count++;
    return count;
});

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
    if (vehicle.source === 'okmobility') {
        return 'ok-mobility-car.show';
    }
    if (vehicle.source === 'wheelsys') {
        return 'wheelsys-car.show';
    }
    if (vehicle.source === 'adobe') {
        return 'adobe-car.show';
    }
    if (vehicle.source === 'locauto_rent') {
        return 'locauto-rent-car.show';
    }
    if (vehicle.source === 'renteon') {
        return 'renteon-car.show';
    }
    // Add other providers here as needed
    // if (vehicle.source === 'usave') {
    //     return 'usave-car.show';
    // }
    return 'green-motion-car.show'; // Default for now
};

const showPriceSlider = ref(false);
// These will be initialized dynamically based on vehicle prices
const priceRangeValues = ref([0, 1000]);
const tempPriceRangeValues = ref([0, 1000]);

// Initialize price range based on dynamic values when vehicles are loaded
watch(() => dynamicPriceRange.value, (newRange) => {
    if (newRange) {
        // Always update to dynamic range when it changes (vehicles loaded)
        if (!form.price_range || priceRangeValues.value[1] === 1000) {
            priceRangeValues.value = [newRange.min, newRange.max];
            tempPriceRangeValues.value = [newRange.min, newRange.max];
        }
    }
}, { immediate: true });

// Watch for currency changes to reset price range
watch(selectedCurrency, () => {
    // When currency changes, recalculate will happen automatically via dynamicPriceRange
    // Reset slider to new range
    const newRange = dynamicPriceRange.value;
    priceRangeValues.value = [newRange.min, newRange.max];
    tempPriceRangeValues.value = [newRange.min, newRange.max];
    form.price_range = ''; // Clear filter when currency changes
});

// onMounted merged to the bottom of the script section

const applyPriceRange = () => {
    priceRangeValues.value = [...tempPriceRangeValues.value];
    form.price_range = `${priceRangeValues.value[0]}-${priceRangeValues.value[1]}`;
    showPriceSlider.value = false;
    // Don't submit to backend - we filter client-side
};

const resetPriceRange = () => {
    const range = dynamicPriceRange.value;
    tempPriceRangeValues.value = [range.min, range.max];
    priceRangeValues.value = [range.min, range.max];
    form.price_range = '';
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

// onUnmounted merged to the bottom or handled properly
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

    // Initialize price range from form
    if (form.price_range) {
        const [min, max] = form.price_range.split('-').map(Number);
        priceRangeValues.value = [min || dynamicPriceRange.value.min, max || dynamicPriceRange.value.max];
        tempPriceRangeValues.value = [min || dynamicPriceRange.value.min, max || dynamicPriceRange.value.max];
    }

    if (page.props.auth?.user) {
        fetchFavoriteStatus();
    }

    setupIntersectionObserver(); // Initialize Intersection Observer

    // Initialize map immediately for fast loading
    setTimeout(() => {
        initMap();
    }, 50);

    // Load currency data in background
    loadCurrencyData();

    // Expose selectVehicleFromMap to window for Leadlet popup
    window.selectVehicleFromMap = (vehicleId) => {
        const vehicle = allVehiclesForMap.value.find(v => v.id == vehicleId);
        if (vehicle) {
            handlePackageSelection({
                vehicle: vehicle,
                package: 'BAS', // Default logic
                protection_code: null
            });
            // Close mobile map modal if open, though handlePackageSelection scrolls up and changes step
            showMap.value = false;
        } else {
            console.error("Vehicle not found for map selection:", vehicleId);
        }
    };

    // Fetch payment percentage
    axios.get('/api/payment-percentage').then(response => {
        if (response.data && response.data.payment_percentage !== undefined) {
            paymentPercentage.value = parseFloat(response.data.payment_percentage);
        }
    }).catch(error => {
        console.error('Error fetching payment percentage:', error);
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (observer.value) {
        observer.value.disconnect();
    }
    // Cleanup global handler
    delete window.selectVehicleFromMap;
});

const handleImageError = (event) => {
    // Prevent infinite loop by checking if we're already trying the fallback
    if (event.target.src.includes('placeholder') || event.target.src.includes('no-vehicle') || event.target.onerror === null) {
        return;
    }

    console.error('Image failed to load:', event.target.src);
    console.error('Data URL:', event.target.dataset.imageUrl);

    // Set fallback image - use Wheelsys placeholder for Wheelsys vehicles
    if (event.target.dataset.imageUrl && event.target.dataset.imageUrl.includes('wheelsys')) {
        event.target.src = '/wheelsys-placeholder.jpg';
    } else {
        // Use general dummy car image for others
        event.target.src = '/images/dummyCarImaage.png';
    }

    // Prevent further error handling to avoid infinite loops
    event.target.onerror = null;
};

const handleImageLoad = (event) => {
    // Image loaded successfully
};

const getImageSource = (vehicle) => {
    if (vehicle.source === 'internal') {
        return vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png';
    }

    // For external providers (Wheelsys, GreenMotion, etc.)
    if (vehicle.image) {
        return vehicle.image;
    }

    // Fallback for providers without images
    if (vehicle.source === 'wheelsys') {
        return '/wheelsys-placeholder.jpg';
    }

    return '/images/dummyCarImaage.png';
};

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

    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />
    <Toaster class="pointer-events-auto" />

    <!-- Mobile Filters Left Sidebar (Moved to root for Z-Index) -->

    <div v-if="currencyLoading" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
        <img :src="moneyExchangeSymbol" alt="Loading..." class="w-16 h-16 animate-spin" />
    </div>

    <!-- Mobile Offcanvas Filters -->
    <Teleport to="body">
        <transition name="slide-left">
            <div v-if="showMobileFilters" class="fixed inset-0 z-[99999] flex">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showMobileFilters = false"></div>

                <!-- Sidebar -->
                <div class="relative w-full max-w-xs bg-white h-full shadow-2xl flex flex-col">
                    <div class="flex items-center justify-between p-4 border-b">
                        <h2 class="text-xl font-bold text-gray-900 font-['Outfit']">Filters</h2>
                        <button @click="showMobileFilters = false" class="p-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-6">
                        <!-- Filters will be injected here via a reusable component or raw HTML -->
                        <!-- I'll use a slot or similar pattern if I refactor, but for now I'll copy the filter logic -->
                        <div class="filter-card border-none shadow-none p-0">
                            <div class="filter-section-header" @click="toggleFilterSection('price')">
                                <span class="filter-section-title">Price Per Day</span>
                                <svg :class="{ 'rotate-180': !expandedFilters.price }"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="w-4 h-4 text-gray-400 transition-transform duration-200">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                            <div v-show="expandedFilters.price" class="px-2 pb-2 mt-4">
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[0] }} - {{
                                            getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[1] }}
                                    </span>
                                </div>
                                <VueSlider v-model="tempPriceRangeValues" :min="dynamicPriceRange.min"
                                    :max="dynamicPriceRange.max" :enable-cross="false" :lazy="true"
                                    @change="applyPriceRange" :tooltip="'none'"
                                    :process-style="{ backgroundColor: '#245f7d' }"
                                    :bg-style="{ backgroundColor: '#e2e8f0' }"></VueSlider>
                            </div>
                        </div>

                        <!-- Other Filters -->
                        <div class="space-y-6">
                            <!-- Category -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider">Vehicle Type
                                </h3>
                                <div class="space-y-3">
                                    <label
                                        class="flex items-center justify-between p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                                        v-for="cat in facets.categories" :key="cat.value">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :value="cat.value"
                                                :checked="form.category_id === cat.value"
                                                @change="form.category_id = form.category_id === cat.value ? '' : cat.value"
                                                class="w-5 h-5 rounded-full border-gray-300 text-[#245f7d] focus:ring-[#245f7d]">
                                            <span class="text-sm font-medium text-gray-700">{{ cat.label }}</span>
                                        </div>
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{
                                            cat.count }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Transmission -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider">Transmission
                                </h3>
                                <div class="space-y-3">
                                    <label
                                        class="flex items-center justify-between p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                                        v-for="item in facets.transmissions" :key="item.value">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :value="item.value"
                                                :checked="form.transmission === item.value"
                                                @change="form.transmission = form.transmission === item.value ? '' : item.value"
                                                class="w-5 h-5 rounded-full border-gray-300 text-[#245f7d] focus:ring-[#245f7d]">
                                            <span class="text-sm font-medium text-gray-700 capitalize">{{ item.label
                                                }}</span>
                                        </div>
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{
                                            item.count }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Fuel Type -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider">Fuel Type</h3>
                                <div class="space-y-3">
                                    <label
                                        class="flex items-center justify-between p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                                        v-for="item in facets.fuels" :key="item.value">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :value="item.value"
                                                :checked="form.fuel === item.value"
                                                @change="form.fuel = form.fuel === item.value ? '' : item.value"
                                                class="w-5 h-5 rounded-full border-gray-300 text-[#245f7d] focus:ring-[#245f7d]">
                                            <span class="text-sm font-medium text-gray-700 capitalize">{{ item.label
                                                }}</span>
                                        </div>
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{
                                            item.count }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Capacity -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider">Capacity</h3>
                                <div class="space-y-3">
                                    <label
                                        class="flex items-center justify-between p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                                        v-for="item in facets.seats" :key="item.value">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :value="item.value"
                                                :checked="form.seating_capacity == item.value"
                                                @change="form.seating_capacity = form.seating_capacity == item.value ? '' : item.value"
                                                class="w-5 h-5 rounded-full border-gray-300 text-[#245f7d] focus:ring-[#245f7d]">
                                            <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
                                        </div>
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{
                                            item.count }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border-t gap-3 flex">
                        <button @click="resetFilters"
                            class="flex-1 py-3 border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors text-sm">Reset</button>
                        <button @click="showMobileFilters = false"
                            class="flex-[2] py-3 bg-[#245f7d] text-white rounded-xl font-bold hover:bg-[#1e4a63] shadow-lg shadow-blue-100 transition-all text-sm">Show
                            {{ clientFilteredVehicles?.length }} Cars</button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
    <SchemaInjector v-if="schema" :schema="schema" />
    <!-- Search Header -->
    <section class="search-header">
        <div class="search-header-inner">
            <div class="search-header-top">
                <div class="search-location">
                    <div class="search-location-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                    </div>
                    <div class="search-location-text">
                        <h1>Car Rental in {{ form.where || 'Selected Location' }}</h1>
                        <p>{{ form.country || 'Morocco' }} • {{ vehicles?.total || clientFilteredVehicles?.length || 0
                        }} cars available</p>
                    </div>
                </div>
                <div class="search-dates-badge">
                    <div class="date-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                            <line x1="16" x2="16" y1="2" y2="6" />
                            <line x1="8" x2="8" y1="2" y2="6" />
                            <line x1="3" x2="21" y1="10" y2="10" />
                        </svg>
                        <span>{{ formatDate(displayDateFrom) }}</span>
                    </div>
                    <div class="date-separator"></div>
                    <div class="date-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                            <line x1="16" x2="16" y1="2" y2="6" />
                            <line x1="8" x2="8" y1="2" y2="6" />
                            <line x1="3" x2="21" y1="10" y2="10" />
                        </svg>
                        <span>{{ formatDate(displayDateTo) }}</span>
                    </div>
                    <span class="days-badge">{{ numberOfRentalDays }} days</span>
                </div>
            </div>

            <div class="search-form-card">
                <SearchBar class="searchbar-in-header !w-full" :prefill="searchQuery" :simple="true"
                    @update-search-params="handleSearchUpdate" />
                <SchemaInjector v-if="$page.props.organizationSchema" :schema="$page.props.organizationSchema" />
            </div>
        </div>
    </section>

    <div v-if="bookingStep === 'results' && hasSearchError"
        class="main-container mx-auto px-4 pb-2">
        <div
            class="rounded-xl border border-rose-200 bg-rose-50 text-rose-900 px-4 py-3 text-sm flex items-start gap-3 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v4m0 4h.01M10.29 3.86l-7.09 12.27A2 2 0 0 0 4.91 19h14.18a2 2 0 0 0 1.72-2.87L13.71 3.86a2 2 0 0 0-3.42 0z" />
            </svg>
            <div>
                <div class="font-semibold">Search is unavailable right now.</div>
                <div class="text-rose-800">{{ searchErrorMessage }}</div>
            </div>
        </div>
    </div>





    <!-- Main Content -->
    <div id="results-breadcrumb-section" class="main-container mx-auto px-4 py-4" v-if="bookingStep === 'results'">
        <Breadcrumb>
            <BreadcrumbList>
                <BreadcrumbItem>
                    <BreadcrumbLink href="/">Home</BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>Search Results</BreadcrumbPage>
                </BreadcrumbItem>
            </BreadcrumbList>
        </Breadcrumb>
    </div>
    <div class="main-container"
        :style="bookingStep !== 'results' ? 'display: block; max-width: 1440px; margin: 0 auto;' : ''">
        <!-- Filters Sidebar -->
        <aside class="filters-sidebar hidden lg:flex" v-if="bookingStep === 'results'">
            <div class="filters-header">
                <span class="filters-title">FILTERS</span>
                <button class="filters-reset" @click="resetFilters">Reset All</button>
            </div>

            <!-- Price Range -->
            <div class="filter-card">
                <div class="filter-section-header" @click="toggleFilterSection('price')">
                    <span class="filter-section-title">Price Per Day</span>
                    <svg :class="{ 'rotate-180': !expandedFilters.price }" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"
                        class="w-4 h-4 text-gray-400 transition-transform duration-200">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </div>
                <div v-show="expandedFilters.price" class="px-2 pb-2">
                    <div class="mb-4">
                        <span class="text-sm font-medium text-gray-900">
                            {{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[0] }} - {{
                                getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[1] }}
                        </span>
                    </div>
                    <VueSlider v-model="tempPriceRangeValues" :min="dynamicPriceRange.min" :max="dynamicPriceRange.max"
                        :enable-cross="false" :lazy="true" @change="applyPriceRange" :tooltip="'none'"
                        :process-style="{ backgroundColor: '#245f7d' }" :bg-style="{ backgroundColor: '#e2e8f0' }">
                    </VueSlider>
                </div>
            </div>

            <!-- Specific Checkbox Filters -->
            <div class="filter-card">
                <!-- Vehicle Type -->
                <div class="filter-section">
                    <div class="filter-section-header" @click="toggleFilterSection('category')">
                        <span class="filter-section-title">Vehicle Type</span>
                        <svg :class="{ 'rotate-180': !expandedFilters.category }" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="w-4 h-4 text-gray-400 transition-transform duration-200">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </div>
                    <div class="filter-options" v-show="expandedFilters.category">
                        <label class="filter-checkbox" v-for="cat in facets.categories" :key="cat.value">
                            <input type="checkbox" :value="cat.value" :checked="form.category_id === cat.value"
                                @change="form.category_id = form.category_id === cat.value ? '' : cat.value">
                            <div class="checkbox-visual">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-check">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </div>
                            <span class="checkbox-label">{{ cat.label }}</span>
                            <span class="checkbox-count">{{ cat.count }}</span>
                        </label>
                    </div>
                </div>

                <!-- Transmission -->
                <div class="filter-section">
                    <div class="filter-section-header" @click="toggleFilterSection('transmission')">
                        <span class="filter-section-title">Transmission</span>
                        <svg :class="{ 'rotate-180': !expandedFilters.transmission }" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="w-4 h-4 text-gray-400 transition-transform duration-200">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </div>
                    <div class="filter-options" v-show="expandedFilters.transmission">
                        <label class="filter-checkbox" v-for="item in facets.transmissions" :key="item.value">
                            <input type="checkbox" :value="item.value" :checked="form.transmission === item.value"
                                @change="form.transmission = form.transmission === item.value ? '' : item.value">
                            <div class="checkbox-visual">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-check">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </div>
                            <span class="checkbox-label capitalize">{{ item.label }}</span>
                            <span class="checkbox-count">{{ item.count }}</span>
                        </label>
                    </div>
                </div>

                <!-- Fuel Type -->
                <div class="filter-section">
                    <div class="filter-section-header" @click="toggleFilterSection('fuel')">
                        <span class="filter-section-title">Fuel Type</span>
                        <svg :class="{ 'rotate-180': !expandedFilters.fuel }" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="w-4 h-4 text-gray-400 transition-transform duration-200">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </div>
                    <div class="filter-options" v-show="expandedFilters.fuel">
                        <label class="filter-checkbox" v-for="item in facets.fuels" :key="item.value">
                            <input type="checkbox" :value="item.value" :checked="form.fuel === item.value"
                                @change="form.fuel = form.fuel === item.value ? '' : item.value">
                            <div class="checkbox-visual">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-check">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </div>
                            <span class="checkbox-label capitalize">{{ item.label }}</span>
                            <span class="checkbox-count">{{ item.count }}</span>
                        </label>
                    </div>
                </div>

                <!-- Seats -->
                <div class="filter-section">
                    <div class="filter-section-header" @click="toggleFilterSection('seats')">
                        <span class="filter-section-title">Capacity</span>
                        <svg :class="{ 'rotate-180': !expandedFilters.seats }" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="w-4 h-4 text-gray-400 transition-transform duration-200">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </div>
                    <div class="filter-options" v-show="expandedFilters.seats">
                        <label class="filter-checkbox" v-for="item in facets.seats" :key="item.value">
                            <input type="checkbox" :value="item.value" :checked="form.seating_capacity == item.value"
                                @change="form.seating_capacity = form.seating_capacity == item.value ? '' : item.value">
                            <div class="checkbox-visual">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-check">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </div>
                            <span class="checkbox-label">{{ item.label }}</span>
                            <span class="checkbox-count">{{ item.count }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Results Section -->
        <section v-if="bookingStep === 'results'" class="results-section w-full">
            <!-- Results Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="results-info">
                    <h2 class="results-count"><span>{{ clientFilteredVehicles?.length }}</span> cars available</h2>
                    <p class="results-subtitle">Prices include taxes and fees</p>
                </div>

                <!-- Controls -->
                <div class="results-controls">
                    <!-- Mobile Filter Button -->
                    <button @click="toggleMobileFilters" class="mobile-filter-btn lg:hidden">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                    </button>

                    <!-- Map Toggle Button -->
                    <button @click="showMap = true" class="map-toggle-btn" title="Show Map">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                        <span class="md:hidden lg:inline">Map View</span>
                    </button>

                    <!-- Sort Dropdown -->
                    <div class="sort-dropdown-wrapper" ref="sortDropdownRef">
                        <button class="sort-dropdown" @click="showSortDropdown = !showSortDropdown">
                            <span>Sort: {{ sortBy === 'recommended' ? 'Recommended' : (sortBy === 'price_asc' ?
                                'Price:Low to High' : 'Price: High to Low') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div v-if="showSortDropdown" class="sort-dropdown-menu">
                            <button @click="selectSort('recommended')"
                                :class="{ 'active': sortBy === 'recommended' }">Recommended</button>
                            <button @click="selectSort('price_asc')"
                                :class="{ 'active': sortBy === 'price_asc' }">Price: Low to High</button>
                            <button @click="selectSort('price_desc')"
                                :class="{ 'active': sortBy === 'price_desc' }">Price: High to Low</button>
                        </div>
                    </div>

                    <!-- View Toggle -->
                    <div class="view-toggle">
                        <button @click="viewMode = 'grid'" :class="{ 'active': viewMode === 'grid' }" title="Grid View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                            </svg>
                        </button>
                        <button @click="viewMode = 'list'" :class="{ 'active': viewMode === 'list' }" title="List View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6" />
                                <line x1="8" y1="12" x2="21" y2="12" />
                                <line x1="8" y1="18" x2="21" y2="18" />
                                <line x1="3" y1="6" x2="3.01" y2="6" />
                                <line x1="3" y1="12" x2="3.01" y2="12" />
                                <line x1="3" y1="18" x2="3.01" y2="18" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cars Grid -->
            <div v-if="clientFilteredVehicles && clientFilteredVehicles.length > 0" class="car-listings"
                :class="[viewMode === 'list' ? 'list-view' : 'grid-view']">
                <CarListingCard v-for="vehicle in paginatedVehicles" :key="vehicle.id" :vehicle="vehicle" :form="form"
                    :view-mode="viewMode" :favoriteStatus="favoriteStatus[vehicle.id] || false"
                    :favoriteLoading="favoriteLoading[vehicle.id] || false" :popEffect="popEffect[vehicle.id] || false"
                    @toggleFavourite="toggleFavourite" @saveSearchUrl="saveSearchUrl"
                    @select-package="handlePackageSelection">
                    <template #dailyPrice>
                        <div class="flex items-baseline gap-1">
                            <span class="text-customPrimaryColor text-2xl font-bold font-['Outfit']">
                                {{ getCurrencySymbol(selectedCurrency) }}{{
                                    getVehiclePriceConverted(vehicle)?.toFixed(2)
                                }}
                            </span>
                        </div>
                    </template>
                </CarListingCard>
            </div>

            <!-- No Results State -->
            <div v-else
                class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-100 mt-6 col-span-full">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No vehicles found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your filters.</p>
                <button @click="resetFilters"
                    class="px-6 py-2.5 bg-[#153b4f] text-white rounded-lg font-medium hover:bg-[#0f2936] transition-colors">
                    Reset Filters
                </button>
            </div>

            <!-- Load More Button -->
            <div v-if="displayLimit < clientFilteredVehicles.length" class="mt-10 flex justify-center pb-12">
                <button @click="loadMore" class="load-more-btn group">
                    <span>Load More Results</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="transition-transform group-hover:translate-y-0.5">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>

        </section>

        <!-- Booking Steps -->
        <BookingExtrasStep v-else-if="bookingStep === 'extras' && selectedVehicle" class="w-full"
            :vehicle="selectedVehicle" :initial-package="selectedPackage"
            :initial-protection-code="selectedProtectionCode" :optional-extras="optionalExtras"
            :currency-symbol="getCurrencySymbol(selectedCurrency)" :location-name="locationName"
            :pickup-location="form.where" :dropoff-location="form.dropoff_where || form.where"
            :dropoff-latitude="form.dropoff_latitude" :dropoff-longitude="form.dropoff_longitude"
            :pickup-date="form.date_from" :pickup-time="form.start_time" :dropoff-date="form.date_to"
            :dropoff-time="form.end_time" :number-of-days="numberOfRentalDays"
            :location-instructions="locationInstructions" :location-details="locationDetails"
            :driver-requirements="driverRequirements" :terms="termsData" :payment-percentage="paymentPercentage"
            :search-session-id="props.search_session_id" :price-map="props.price_map"
            @back="handleBackToResults" @proceed-to-checkout="handleProceedToCheckout" />

        <BookingCheckoutStep v-else-if="bookingStep === 'checkout' && selectedVehicle" class="w-full"
            :vehicle="selectedVehicle" :package="selectedCheckoutData.package"
            :protection-code="selectedCheckoutData.protection_code"
            :protection-amount="selectedCheckoutData.protection_amount" :extras="selectedCheckoutData.extras"
            :detailed-extras="selectedCheckoutData.detailedExtras" :optional-extras="optionalExtras"
            :pickup-date="form.date_from" :pickup-time="form.start_time" :dropoff-date="form.date_to"
            :dropoff-time="form.end_time" :pickup-location="form.where"
            :dropoff-location="form.dropoff_where || form.where" :number-of-days="numberOfRentalDays"
            :currency-symbol="getCurrencySymbol(selectedCurrency)" :selected-currency-code="selectedCurrency"
            :payment-percentage="paymentPercentage" :totals="selectedCheckoutData.totals"
            :vehicle-total="selectedCheckoutData.vehicle_total" :location-details="locationDetails"
            :location-instructions="locationInstructions" :driver-requirements="driverRequirements" :terms="termsData"
            :search-session-id="props.search_session_id"
            :selected-deposit-type="selectedCheckoutData?.selected_deposit_type || null"
            @back="handleBackToExtras" />
    </div>

    <!-- Map Modal (Global) -->
    <div v-if="showMap" class="fixed inset-0 z-[100000]">
        <!-- Full-screen overlay with map -->
        <div class="relative w-full h-full bg-white">
            <!-- Close Button Header -->
            <div class="absolute top-0 left-0 right-0 z-[10000] bg-white shadow-md">
                <div class="flex items-center justify-between p-4">
                    <h2 class="text-xl font-semibold text-customPrimaryColor">Vehicle Map</h2>
                    <button @click="showMap = false"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Map Container -->
            <div class="w-full h-full pt-16">
                <!-- Using 'map' ID to reuse existing desktop initMap logic -->
                <div id="map" class="w-full h-full"></div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style>
@import "leaflet/dist/leaflet.css";
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap');

.marker-pin {
    width: 80px;
    /* Fixed width to match iconSize */
    height: 30px;
    /* background color is now controlled by Tailwind classes in HTML */
    border: 2px solid #666;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.marker-pin span {
    /* text color is now controlled by Tailwind classes in HTML */
    font-weight: bold;
    font-size: 11px;
    padding: 0 4px;
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
    -moz-appearance: none;
    appearance: none;
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

/* SearchBar in Header Styles */
.searchbar-in-header :deep(.full-w-container) {
    padding-bottom: 0 !important;
}

.searchbar-in-header :deep(.search_bar) {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
}

/* Hide the left header column completely */
.searchbar-in-header :deep(.search_bar > .flex > .column:first-child) {
    display: none !important;
}

/* Make the form column span full width */
.searchbar-in-header :deep(.search_bar > .flex > .column:last-child) {
    width: 100% !important;
    padding: 0 !important;
    border-radius: 0 !important;
}

/* Reset the form styling */
.searchbar-in-header :deep(.search_bar form) {
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1rem;
    align-items: end;
}

/* Reset input styling */
.searchbar-in-header :deep(.search_bar form input),
.searchbar-in-header :deep(.search_bar form select) {
    background: white !important;
}

/* Hide labels in header mode */
.searchbar-in-header :deep(label) {
    display: none;
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

/* Slide Transition */
.slide-left-enter-active,
.slide-left-leave-active {
    transition: all 0.3s ease-in-out;
}

.slide-left-enter-from {
    transform: translateX(-100%);
}

.slide-left-leave-to {
    transform: translateX(-100%);
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

/* ============================================
   VROOEM DESIGN SYSTEM - Component Styles
============================================ */
/* Fonts */
/* Fonts */

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--gray-50);
}

h1,
h2,
h3,
h4,
h5,
h6 {
    font-family: 'Outfit', sans-serif;
}

/* Search Header */
.search-header {
    background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-800) 50%, var(--primary-700) 100%);
    padding: var(--space-6) var(--space-6) var(--space-8);
    position: relative;
    z-index: 30;
}

.search-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 100%, rgba(6, 182, 212, 0.15) 0%, transparent 50%),
        radial-gradient(ellipse 60% 40% at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 40%);
    pointer-events: none;
}

.search-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}

.search-header-inner {
    max-width: 1440px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.search-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-5);
}

.search-location {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.search-location-icon {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-400);
}

@media (max-width: 768px) {
    .search-location-icon {
        width: 82px;
        height: 54px;
    }
}

.search-location-icon svg {
    width: 24px;
    height: 24px;
}

.search-location-text h1 {
    font-size: 22px;
    color: var(--white);
    margin-bottom: 2px;
    line-height: 1.25;
}

.search-location-text p {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.6);
}

.search-dates-badge {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) var(--space-5);
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-full);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.date-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--white);
    font-size: 14px;
}

.date-item svg {
    width: 18px;
    height: 18px;
    opacity: 0.7;
}

.date-separator {
    width: 1px;
    height: 24px;
    background: rgba(255, 255, 255, 0.2);
}

.days-badge {
    background: var(--accent-500);
    color: var(--white);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: 12px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .days-badge {
        text-align: center;
    }
}

.search-form-card {
    background: transparent;
    border-radius: var(--radius-xl);
    padding: var(--space-4) 0;
    box-shadow: none;
}

/* Main Container Grid */
.main-container {
    max-width: 1440px;
    margin: 0 auto;
    padding: 2rem 1rem;
    display: block;
}

@media (min-width: 1280px) {
    .main-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: var(--space-6);
        align-items: start;
    }
}

/* Filters Sidebar */
.filters-sidebar {
    position: sticky;
    top: 90px;
    height: fit-content;
    display: none;
    flex-direction: column;
    gap: 12px;
}

@media (min-width: 1280px) {
    .filters-sidebar {
        display: flex;
    }
}

.filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.filters-title {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray-500);
}

.filters-reset {
    background: none;
    border: none;
    font-size: 13px;
    font-weight: 500;
    color: var(--primary-600);
    cursor: pointer;
    font-family: inherit;
    transition: color var(--duration-fast);
}

.filters-reset:hover {
    color: var(--primary-800);
}

.filter-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 14px;
    box-shadow: var(--shadow-sm);
}

.filter-section {
    padding: 8px 0;
}

.filter-section:first-child {
    padding-top: 0;
}

.filter-section:not(:last-child) {
    border-bottom: 1px solid var(--gray-100);
}

.filter-section:last-child {
    padding-bottom: 0;
}

.filter-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    margin-bottom: 8px;
}

.filter-section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
}

.filter-section-header svg {
    width: 16px;
    height: 16px;
    color: var(--gray-400);
    transition: transform var(--duration-fast);
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    cursor: pointer;
    transition: opacity var(--duration-fast);
}

.filter-checkbox:hover {
    opacity: 0.8;
}

.filter-checkbox input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.checkbox-visual {
    width: 20px;
    height: 20px;
    background: var(--gray-100);
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--duration-fast);
    flex-shrink: 0;
}

.filter-checkbox input:checked+.checkbox-visual {
    background: var(--primary-800);
    border-color: var(--primary-800);
}

.checkbox-visual svg {
    width: 12px;
    height: 12px;
    color: var(--white);
    opacity: 0;
    transform: scale(0.5);
    transition: all var(--duration-fast);
}

.filter-checkbox input:checked+.checkbox-visual svg {
    opacity: 1;
    transform: scale(1);
}

.checkbox-label {
    flex: 1;
    font-size: 14px;
    color: var(--gray-700);
}

.checkbox-count {
    font-size: 12px;
    color: var(--gray-400);
    background: var(--gray-100);
    padding: 2px 8px;
    border-radius: var(--radius-full);
}

/* Results Header */
.results-info {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.results-count {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 600;
    color: var(--gray-900);
    margin-top: 0;
    line-height: 1.2;
    /* Override huge global line-height */
}

.results-count span {
    color: var(--primary-600);
}

.results-subtitle {
    font-size: 14px;
    color: var(--gray-500);
}

.results-controls {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

@media (max-width: 640px) {
    .results-controls {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        width: 100%;
        padding: 0 4px;
    }
}

.map-toggle-btn {
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #153B4F;
    border: 1px solid #153B4F;
    border-radius: 12px;
    color: white;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
    padding: 0 16px;
    cursor: pointer;
}

.map-toggle-btn:hover {
    background: #0f2936;
    border-color: #0f2936;
}

@media (max-width: 640px) {
    .map-toggle-btn {
        width: 100%;
    }
}

.mobile-filter-btn {
    display: none;
    align-items: center;
    justify-content: center;
    height: 48px;
    gap: 8px;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
}

.mobile-filter-btn:hover {
    background: var(--gray-50);
}

@media (max-width: 1023px) {
    .mobile-filter-btn {
        display: flex;
    }
}

.sort-dropdown {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-2);
    padding: 0 var(--space-4);
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
    cursor: pointer;
    font-family: inherit;
    transition: all var(--duration-fast);
    height: 48px;
    min-width: 200px;
}

@media (max-width: 640px) {
    .sort-dropdown {
        width: 100%;
        min-width: 0;
    }
}

.sort-dropdown:hover {
    border-color: var(--gray-300);
    background: var(--gray-50);
}

.sort-dropdown svg {
    width: 16px;
    height: 16px;
    color: var(--gray-400);
}

.sort-dropdown-wrapper {
    position: relative;
}

@media (max-width: 640px) {
    .sort-dropdown-wrapper {
        width: 100%;
    }
}

.sort-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: var(--space-2);
    min-width: 180px;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    z-index: 50;
    overflow: hidden;
}

.sort-dropdown-menu button {
    display: block;
    width: 100%;
    padding: var(--space-3) var(--space-4);
    text-align: left;
    font-size: 14px;
    color: var(--gray-700);
    background: none;
    border: none;
    cursor: pointer;
    transition: background var(--duration-fast);
}

.sort-dropdown-menu button:hover {
    background: var(--gray-50);
}

.sort-dropdown-menu button.active {
    background: var(--gray-100);
    font-weight: 600;
}

.view-toggle {
    display: flex;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    overflow: hidden;
    height: 48px;
}

@media (max-width: 640px) {
    .view-toggle {
        justify-content: center;
        width: 100%;
    }

    .view-toggle button {
        flex: 1;
    }
}

.view-toggle button {
    padding: var(--space-2) var(--space-3);
    background: var(--white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--duration-fast);
    color: var(--gray-400);
}

.view-toggle button:first-child {
    border-right: 1px solid var(--gray-200);
}

.view-toggle button:hover {
    background: var(--gray-50);
    color: var(--gray-600);
}

.view-toggle button.active {
    background: var(--primary-800);
    color: var(--white);
}

.view-toggle button svg {
    width: 18px;
    height: 18px;
}

/* Z-Index Fixes */
.search-header {
    position: relative;
    z-index: 30;
}

.search-form-card {
    position: relative;
    z-index: 40;
}

.results-controls {
    position: relative;
    z-index: 20;
}

/* Car Listings Layout */
.car-listings {
    gap: var(--space-6);
}

.car-listings.grid-view {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
}

@media (min-width: 768px) {
    .car-listings.grid-view {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1280px) {
    .car-listings.grid-view {
        grid-template-columns: repeat(3, 1fr);
    }
}

.car-listings.list-view {
    display: flex;
    flex-direction: column;
}

/* Mobile Responsive Adjustments */
@media (max-width: 1024px) {
    .search-header-top {
        flex-wrap: wrap;
        gap: var(--space-3);
    }
}

@media (max-width: 600px) {
    .search-header {
        padding: var(--space-4);
    }

    .search-header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-4);
    }

    .search-dates-badge {
        width: 100%;
        justify-content: center;
    }
}

.load-more-btn {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-8);
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-full);
    font-size: 15px;
    font-weight: 600;
    color: var(--primary-800);
    cursor: pointer;
    box-shadow: var(--shadow-sm);
    transition: all var(--duration-base);
}

.load-more-btn:hover {
    background: var(--gray-50);
    border-color: var(--primary-400);
    box-shadow: var(--shadow-md);
    color: var(--primary-600);
}

.load-more-btn:active {
    transform: scale(0.98);
}
</style>
