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
import { Filter, DollarSign, Car, Cog, Fuel, Users, ChevronDown, X } from 'lucide-vue-next';
import CarListingCard from "@/Components/CarListingCard.vue"; // Import CarListingCard
import BookingExtrasStep from "@/Components/BookingExtrasStepUnified.vue"; // Import BookingExtrasStep (unified)
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
import { resolveProviderMarkupRate } from '@/utils/platformPricing';
import { resolveSearchCurrency } from '@/utils/searchCurrency';
import { computeVehicleDisplayDailyPrice } from '@/utils/vehicleSearchPricing';
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
    return resolveProviderMarkupRate(page.props);
});

// Active promo from backend (shared via HandleInertiaRequests)
const activePromo = computed(() => page.props.active_promo ?? null);
const promoMarkupRate = computed(() => activePromo.value?.promo_markup_rate ?? 0);

const getInflatedPrice = (basePrice) => {
    if (!activePromo.value || promoMarkupRate.value <= 0) return null;
    return basePrice * (1 + promoMarkupRate.value);
};

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
    via_gateway: { type: Boolean, default: false },
    gateway_search_id: { type: String, default: null },
    gateway_response_time_ms: { type: Number, default: null },
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
let savedScrollPosition = 0;

const selectedBookingExtras = ref({});
const locationInstructions = ref(null);
const locationDetails = ref(null);
const driverRequirements = ref(null);
const termsData = ref(null);
const greenMotionCountries = ref(null);
const termsCountryId = ref(null);
// Initialize with 15% as default to prevent "Pay 0" bug - will be updated from API
const paymentPercentage = ref(15);

let locationFetchId = 0; // Guard against stale async responses
const fetchLocationDetails = async (locationId, provider = 'greenmotion') => {
    if (!locationId) return;
    const fetchId = ++locationFetchId;
    try {
        const response = await axios.get(route('green-motion-locations'), {
            params: { location_id: locationId, provider }
        });
        if (fetchId !== locationFetchId) return; // Stale response, discard
        if (response.data) {
            locationDetails.value = response.data;
            locationInstructions.value = response.data.collection_details || null;
        } else {
            locationDetails.value = null;
            locationInstructions.value = null;
        }
    } catch (error) {
        if (fetchId !== locationFetchId) return;
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
    // Attach price_hash from price_map to vehicle for verification
    const vehicleWithPriceHash = { ...event.vehicle };
    if (props.price_map && event.vehicle) {
        const vehicleId = event.vehicle.id || event.vehicle.vehicle_id || event.vehicle.api_vehicle_id;
        if (vehicleId && props.price_map[vehicleId]) {
            vehicleWithPriceHash.price_hash = props.price_map[vehicleId].price_hash;
        }
    }

    savedScrollPosition = window.scrollY;
    selectedVehicle.value = vehicleWithPriceHash;
    selectedPackage.value = event.package;
    selectedProtectionCode.value = event.protection_code || null;
    bookingStep.value = 'extras';

    // Clear previous location state immediately to prevent leaking across providers
    locationInstructions.value = null;
    locationDetails.value = null;
    driverRequirements.value = null;
    termsData.value = null;
    termsCountryId.value = null;

    // Fetch location details if GreenMotion/USave
    if (event.vehicle.source === 'greenmotion' || event.vehicle.source === 'usave') {
        const locId = event.vehicle.location_id;
        if (locId) {
            fetchLocationDetails(locId, event.vehicle.source);
        }
        resolveGreenMotionRequirements();
    } else if (event.vehicle.location_details) {
        locationDetails.value = event.vehicle.location_details;
        locationInstructions.value = event.vehicle.location_instructions
            || event.vehicle.location_details.collection_details
            || null;
    }

    window.scrollTo({ top: 0, behavior: 'instant' });
    nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
};

const handleBackToResults = () => {
    bookingStep.value = 'results';
    selectedVehicle.value = null;
    selectedPackage.value = null;
    selectedProtectionCode.value = null;
    locationFetchId++; // Cancel any in-flight location fetch
    locationDetails.value = null;
    locationInstructions.value = null;
    nextTick(() => window.scrollTo({ top: savedScrollPosition, behavior: 'instant' }));
};

const selectedCheckoutData = ref(null);

const handleProceedToCheckout = (data) => {
    const vehicleTotal = data.vehicle_total ?? selectedVehicle.value?.total_price ?? 0;
    selectedCheckoutData.value = {
        ...data,
        vehicle_total: vehicleTotal
    };
    bookingStep.value = 'checkout';
    window.scrollTo({ top: 0, behavior: 'instant' });
    nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
};

const handleBackToExtras = () => {
    bookingStep.value = 'extras';
    window.scrollTo({ top: 0, behavior: 'instant' });
    nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
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
    currency: resolveSearchCurrency({
        currentCurrency: usePage().props.filters?.currency,
        selectedCurrency: selectedCurrency.value,
    }),
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

const getVehiclePriceConverted = (vehicle) => {
    return computeVehicleDisplayDailyPrice({
        vehicle,
        rentalDays: numberOfRentalDays.value,
        markupRate: providerMarkupRate.value,
        convertAmount: convertCurrency,
    });
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
            return s === seats;
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
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    let priceToDisplay = "N/A";
    const priceValue = getVehiclePriceConverted(vehicle);

    if (priceValue !== null && priceValue > 0) { // Ensure priceValue is greater than 0
        priceToDisplay = `${currencySymbol}${parseFloat(priceValue).toFixed(2)}`;
    } else {
        priceToDisplay = "N/A"; // Explicitly set to N/A if price is 0 or null
    }

    const hlClass = isHighlighted ? ' marker-bnb-highlighted' : '';

    return L.divIcon({
        className: "custom-div-icon",
        html: `
    <div class="marker-bnb${hlClass}">
      <div class="marker-bnb-inner">${priceToDisplay}</div>
    </div>
  `,
        iconSize: [90, 42],
        iconAnchor: [45, 42],
        popupAnchor: [0, -42],
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
    form.currency = resolveSearchCurrency({
        selectedCurrency: selectedCurrency.value,
    });
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
    const popupName = vehicle?.source === 'okmobility'
        ? (vehicle.display_name || vehicle.group_description || vehicle.model || '')
        : `${vehicle.brand || ''} ${vehicle.model || ''}`.trim();

    const ratingHtml = vehicle.source === 'internal' && vehicle.average_rating
        ? `<div class="popup-bnb-rating"><span class="popup-bnb-star">&#9733;</span> ${vehicle.average_rating.toFixed(1)} (${vehicle.review_count} reviews)</div>`
        : '';

    const locationText = vehicle.station
        ? vehicle.station
        : (vehicle.full_vehicle_address || '');

    const content = `
        <div class="popup-bnb">
            <img src="${primaryImage}" alt="${popupName}" class="popup-bnb-img" />
            <div class="popup-bnb-body">
                <div class="popup-bnb-name">${popupName}</div>
                ${ratingHtml}
                ${locationText ? `<div class="popup-bnb-location"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>${locationText}</div>` : ''}
                <div class="popup-bnb-footer">
                    <div class="popup-bnb-price">${popupPrice}</div>
                    <button onclick="window.selectVehicleFromMap('${vehicle.id}')" class="popup-bnb-btn">Book Deal</button>
                </div>
            </div>
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
            const K_MAX_MARKERS_PER_RING = 10;
            const ringNum = Math.floor((occurrence - 2) / K_MAX_MARKERS_PER_RING);
            const indexInRing = (occurrence - 2) % K_MAX_MARKERS_PER_RING;

            const angle = indexInRing * (2 * Math.PI / K_MAX_MARKERS_PER_RING);

            const baseEffectiveRadius = 0.0012; // ~130 meters, prevents marker overlap
            const effectiveRadius = baseEffectiveRadius * (1 + ringNum * 0.7);

            displayLat = lat + effectiveRadius * Math.sin(angle);
            displayLng = lng + effectiveRadius * Math.cos(angle);
        }

        const primaryImage = vehicle.image || (vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png');
        const detailRoute = vehicle.source !== 'internal'
            ? route(getProviderRoute(vehicle), { locale: page.props.locale, id: vehicle.id, provider: vehicle.source, location_id: vehicle.provider_pickup_id, start_date: form.date_from, end_date: form.date_to, start_time: form.start_time, end_time: form.end_time, dropoff_location_id: form.dropoff_location_id, rentalCode: form.rentalCode })
            : route('vehicle.show', { locale: page.props.locale, id: vehicle.id, package: form.package_type, pickup_date: form.date_from, return_date: form.date_to });

        let popupPrice = "N/A";
        let popupCurrencySymbol = getCurrencySymbol(selectedCurrency.value);
        const popupPriceValue = getVehiclePriceConverted(vehicle);

        if (popupPriceValue !== null && popupPriceValue > 0) {
            popupPrice = `${popupCurrencySymbol}${parseFloat(popupPriceValue).toFixed(2)}`;
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
        currency: resolveSearchCurrency({
            currentCurrency: usePage().props.filters?.currency,
            selectedCurrency: selectedCurrency.value,
        }),
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
    form.currency = resolveSearchCurrency({
        currentCurrency: params.currency,
        selectedCurrency: selectedCurrency.value,
    });
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
    price: false,
    category: true,
    transmission: false,
    fuel: false,
    seats: false
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

const isSectionActive = (section) => {
    switch (section) {
        case 'price': return !!form.price_range;
        case 'category': return !!form.category_id;
        case 'transmission': return !!form.transmission;
        case 'fuel': return !!form.fuel;
        case 'seats': return !!form.seating_capacity;
        default: return false;
    }
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
                    <div class="fpm-header">
                        <span class="fpm-title">Filters</span>
                        <button @click="showMobileFilters = false" class="fpm-close-btn">
                            <X :size="14" />
                        </button>
                    </div>

                    <div class="fpm-body">
                        <!-- Price Per Day -->
                        <div class="fp-section">
                            <div class="fp-section-header" :aria-expanded="expandedFilters.price" @click="toggleFilterSection('price')">
                                <div class="fp-icon-badge fp-icon-badge-sm fp-icon-price"><DollarSign :size="14" /></div>
                                <span class="fp-section-label fpm-label">Price Per Day</span>
                                <span v-show="isSectionActive('price')" class="fp-active-dot"></span>
                                <ChevronDown :size="14" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.price }" />
                            </div>
                            <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.price }">
                                <div class="fp-section-body fpm-section-body">
                                    <div class="fp-price-display">
                                        <strong>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[0] }}</strong>
                                        <strong>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[1] }}</strong>
                                    </div>
                                    <VueSlider v-model="tempPriceRangeValues" :min="dynamicPriceRange.min"
                                        :max="dynamicPriceRange.max" :enable-cross="false" :lazy="true"
                                        @change="applyPriceRange" :tooltip="'none'"
                                        :process-style="{ backgroundColor: '#245f7d' }"
                                        :bg-style="{ backgroundColor: '#e2e8f0' }"></VueSlider>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Type -->
                        <div class="fp-section">
                            <div class="fp-section-header" :aria-expanded="expandedFilters.category" @click="toggleFilterSection('category')">
                                <div class="fp-icon-badge fp-icon-badge-sm fp-icon-category"><Car :size="14" /></div>
                                <span class="fp-section-label fpm-label">Vehicle Type</span>
                                <span v-show="isSectionActive('category')" class="fp-active-dot"></span>
                                <ChevronDown :size="14" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.category }" />
                            </div>
                            <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.category }">
                                <div class="fp-options fpm-options">
                                    <label class="fp-option fpm-option" :class="{ 'fp-option-active': form.category_id === cat.value }" v-for="cat in facets.categories" :key="cat.value">
                                        <input type="checkbox" :value="cat.value" :checked="form.category_id === cat.value"
                                            @change="form.category_id = form.category_id === cat.value ? '' : cat.value">
                                        <div class="fp-checkbox fpm-checkbox">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                        </div>
                                        <span class="fp-option-label fpm-option-label">{{ cat.label }}</span>
                                        <span class="fp-option-count fpm-option-count">{{ cat.count }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Transmission -->
                        <div class="fp-section">
                            <div class="fp-section-header" :aria-expanded="expandedFilters.transmission" @click="toggleFilterSection('transmission')">
                                <div class="fp-icon-badge fp-icon-badge-sm fp-icon-transmission"><Cog :size="14" /></div>
                                <span class="fp-section-label fpm-label">Transmission</span>
                                <span v-show="isSectionActive('transmission')" class="fp-active-dot"></span>
                                <ChevronDown :size="14" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.transmission }" />
                            </div>
                            <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.transmission }">
                                <div class="fp-options fpm-options">
                                    <label class="fp-option fpm-option" :class="{ 'fp-option-active': form.transmission === item.value }" v-for="item in facets.transmissions" :key="item.value">
                                        <input type="checkbox" :value="item.value" :checked="form.transmission === item.value"
                                            @change="form.transmission = form.transmission === item.value ? '' : item.value">
                                        <div class="fp-checkbox fpm-checkbox">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                        </div>
                                        <span class="fp-option-label fpm-option-label capitalize">{{ item.label }}</span>
                                        <span class="fp-option-count fpm-option-count">{{ item.count }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Fuel Type -->
                        <div class="fp-section">
                            <div class="fp-section-header" :aria-expanded="expandedFilters.fuel" @click="toggleFilterSection('fuel')">
                                <div class="fp-icon-badge fp-icon-badge-sm fp-icon-fuel"><Fuel :size="14" /></div>
                                <span class="fp-section-label fpm-label">Fuel Type</span>
                                <span v-show="isSectionActive('fuel')" class="fp-active-dot"></span>
                                <ChevronDown :size="14" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.fuel }" />
                            </div>
                            <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.fuel }">
                                <div class="fp-options fpm-options">
                                    <label class="fp-option fpm-option" :class="{ 'fp-option-active': form.fuel === item.value }" v-for="item in facets.fuels" :key="item.value">
                                        <input type="checkbox" :value="item.value" :checked="form.fuel === item.value"
                                            @change="form.fuel = form.fuel === item.value ? '' : item.value">
                                        <div class="fp-checkbox fpm-checkbox">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                        </div>
                                        <span class="fp-option-label fpm-option-label capitalize">{{ item.label }}</span>
                                        <span class="fp-option-count fpm-option-count">{{ item.count }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Capacity -->
                        <div class="fp-section">
                            <div class="fp-section-header" :aria-expanded="expandedFilters.seats" @click="toggleFilterSection('seats')">
                                <div class="fp-icon-badge fp-icon-badge-sm fp-icon-seats"><Users :size="14" /></div>
                                <span class="fp-section-label fpm-label">Capacity</span>
                                <span v-show="isSectionActive('seats')" class="fp-active-dot"></span>
                                <ChevronDown :size="14" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.seats }" />
                            </div>
                            <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.seats }">
                                <div class="fp-options fpm-options">
                                    <label class="fp-option fpm-option" :class="{ 'fp-option-active': form.seating_capacity == item.value }" v-for="item in facets.seats" :key="item.value">
                                        <input type="checkbox" :value="item.value" :checked="form.seating_capacity == item.value"
                                            @change="form.seating_capacity = form.seating_capacity == item.value ? '' : item.value">
                                        <div class="fp-checkbox fpm-checkbox">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                        </div>
                                        <span class="fp-option-label fpm-option-label">{{ item.label }}</span>
                                        <span class="fp-option-count fpm-option-count">{{ item.count }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fpm-footer">
                        <button @click="resetFilters"
                            class="fpm-btn-reset">Reset</button>
                        <button @click="showMobileFilters = false"
                            class="fpm-btn-show">Show
                            {{ clientFilteredVehicles?.length }} Cars</button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
    <SchemaInjector v-if="schema" :schema="schema" />

    <!-- Booking Stepper (extras & checkout steps) -->
    <section v-if="bookingStep !== 'results'" class="booking-stepper-bar">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex items-center">
                <!-- Step 1: Search (completed) -->
                <div class="flex items-center gap-2.5 cursor-pointer group" @click="bookingStep = 'results'">
                    <div class="stepper-dot stepper-done">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="hidden sm:block">
                        <span class="stepper-label text-[#1e3a5f] group-hover:underline">Search</span>
                        <span class="stepper-sub">Find your car</span>
                    </div>
                </div>

                <!-- Connector 1→2 -->
                <div class="stepper-line flex-1 mx-3">
                    <div class="stepper-line-fill" :style="{ width: '100%' }"></div>
                </div>

                <!-- Step 2: Customize -->
                <div class="flex items-center gap-2.5" :class="bookingStep === 'checkout' ? 'cursor-pointer' : ''" @click="bookingStep === 'checkout' ? bookingStep = 'extras' : null">
                    <div class="stepper-dot" :class="bookingStep === 'checkout' ? 'stepper-done' : bookingStep === 'extras' ? 'stepper-active' : 'stepper-pending'">
                        <svg v-if="bookingStep === 'checkout'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    </div>
                    <div class="hidden sm:block">
                        <span class="stepper-label" :class="bookingStep === 'extras' ? 'text-[#1e3a5f]' : bookingStep === 'checkout' ? 'text-[#1e3a5f]' : 'text-gray-400'">Customize</span>
                        <span class="stepper-sub" :class="bookingStep === 'extras' ? 'text-gray-500' : ''">Extras & options</span>
                    </div>
                </div>

                <!-- Connector 2→3 -->
                <div class="stepper-line flex-1 mx-3">
                    <div class="stepper-line-fill" :style="{ width: bookingStep === 'checkout' ? '100%' : '0%' }"></div>
                </div>

                <!-- Step 3: Checkout -->
                <div class="flex items-center gap-2.5">
                    <div class="stepper-dot" :class="bookingStep === 'checkout' ? 'stepper-active' : 'stepper-pending'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div class="hidden sm:block">
                        <span class="stepper-label" :class="bookingStep === 'checkout' ? 'text-[#1e3a5f]' : 'text-gray-400'">Checkout</span>
                        <span class="stepper-sub" :class="bookingStep === 'checkout' ? 'text-gray-500' : ''">Secure payment</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Header -->
    <section v-if="bookingStep === 'results'" class="search-header">
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
            <div class="filters-panel">
                <!-- Panel Header -->
                <div class="fp-header">
                    <span class="fp-title">
                        <Filter :size="16" />
                        Filters
                        <span v-show="activeFiltersCount > 0" class="fp-count-badge">{{ activeFiltersCount }}</span>
                    </span>
                    <button class="fp-reset" @click="resetFilters">Reset All</button>
                </div>

                <!-- Scrollable Sections -->
                <div class="fp-scroll-area">
                    <!-- Price Per Day -->
                    <div class="fp-section">
                        <div class="fp-section-header" :aria-expanded="expandedFilters.price" @click="toggleFilterSection('price')">
                            <div class="fp-icon-badge fp-icon-price"><DollarSign :size="17" /></div>
                            <span class="fp-section-label">Price Per Day</span>
                            <span v-show="isSectionActive('price')" class="fp-active-dot"></span>
                            <ChevronDown :size="16" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.price }" />
                        </div>
                        <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.price }">
                            <div class="fp-section-body">
                                <div class="fp-price-display">
                                    <strong>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[0] }}</strong>
                                    <strong>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[1] }}</strong>
                                </div>
                                <VueSlider v-model="tempPriceRangeValues" :min="dynamicPriceRange.min" :max="dynamicPriceRange.max"
                                    :enable-cross="false" :lazy="true" @change="applyPriceRange" :tooltip="'none'"
                                    :process-style="{ backgroundColor: '#245f7d' }" :bg-style="{ backgroundColor: '#e2e8f0' }">
                                </VueSlider>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Type -->
                    <div class="fp-section">
                        <div class="fp-section-header" :aria-expanded="expandedFilters.category" @click="toggleFilterSection('category')">
                            <div class="fp-icon-badge fp-icon-category"><Car :size="17" /></div>
                            <span class="fp-section-label">Vehicle Type</span>
                            <span v-show="isSectionActive('category')" class="fp-active-dot"></span>
                            <ChevronDown :size="16" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.category }" />
                        </div>
                        <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.category }">
                            <div class="fp-options">
                                <label class="fp-option" :class="{ 'fp-option-active': form.category_id === cat.value }" v-for="cat in facets.categories" :key="cat.value">
                                    <input type="checkbox" :value="cat.value" :checked="form.category_id === cat.value"
                                        @change="form.category_id = form.category_id === cat.value ? '' : cat.value">
                                    <div class="fp-checkbox">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                    </div>
                                    <span class="fp-option-label">{{ cat.label }}</span>
                                    <span class="fp-option-count">{{ cat.count }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Transmission -->
                    <div class="fp-section">
                        <div class="fp-section-header" :aria-expanded="expandedFilters.transmission" @click="toggleFilterSection('transmission')">
                            <div class="fp-icon-badge fp-icon-transmission"><Cog :size="17" /></div>
                            <span class="fp-section-label">Transmission</span>
                            <span v-show="isSectionActive('transmission')" class="fp-active-dot"></span>
                            <ChevronDown :size="16" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.transmission }" />
                        </div>
                        <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.transmission }">
                            <div class="fp-options">
                                <label class="fp-option" :class="{ 'fp-option-active': form.transmission === item.value }" v-for="item in facets.transmissions" :key="item.value">
                                    <input type="checkbox" :value="item.value" :checked="form.transmission === item.value"
                                        @change="form.transmission = form.transmission === item.value ? '' : item.value">
                                    <div class="fp-checkbox">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                    </div>
                                    <span class="fp-option-label capitalize">{{ item.label }}</span>
                                    <span class="fp-option-count">{{ item.count }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Fuel Type -->
                    <div class="fp-section">
                        <div class="fp-section-header" :aria-expanded="expandedFilters.fuel" @click="toggleFilterSection('fuel')">
                            <div class="fp-icon-badge fp-icon-fuel"><Fuel :size="17" /></div>
                            <span class="fp-section-label">Fuel Type</span>
                            <span v-show="isSectionActive('fuel')" class="fp-active-dot"></span>
                            <ChevronDown :size="16" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.fuel }" />
                        </div>
                        <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.fuel }">
                            <div class="fp-options">
                                <label class="fp-option" :class="{ 'fp-option-active': form.fuel === item.value }" v-for="item in facets.fuels" :key="item.value">
                                    <input type="checkbox" :value="item.value" :checked="form.fuel === item.value"
                                        @change="form.fuel = form.fuel === item.value ? '' : item.value">
                                    <div class="fp-checkbox">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                    </div>
                                    <span class="fp-option-label capitalize">{{ item.label }}</span>
                                    <span class="fp-option-count">{{ item.count }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Capacity -->
                    <div class="fp-section">
                        <div class="fp-section-header" :aria-expanded="expandedFilters.seats" @click="toggleFilterSection('seats')">
                            <div class="fp-icon-badge fp-icon-seats"><Users :size="17" /></div>
                            <span class="fp-section-label">Capacity</span>
                            <span v-show="isSectionActive('seats')" class="fp-active-dot"></span>
                            <ChevronDown :size="16" class="fp-chevron" :class="{ 'fp-chevron-collapsed': !expandedFilters.seats }" />
                        </div>
                        <div class="fp-collapse" :class="{ 'fp-collapse-open': expandedFilters.seats }">
                            <div class="fp-options">
                                <label class="fp-option" :class="{ 'fp-option-active': form.seating_capacity == item.value }" v-for="item in facets.seats" :key="item.value">
                                    <input type="checkbox" :value="item.value" :checked="form.seating_capacity == item.value"
                                        @change="form.seating_capacity = form.seating_capacity == item.value ? '' : item.value">
                                    <div class="fp-checkbox">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                    </div>
                                    <span class="fp-option-label">{{ item.label }}</span>
                                    <span class="fp-option-count">{{ item.count }}</span>
                                </label>
                            </div>
                        </div>
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
                        <template v-if="activePromo && getInflatedPrice(getVehiclePriceConverted(vehicle))">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 text-sm font-semibold line-through font-['Outfit']">
                                    {{ getCurrencySymbol(selectedCurrency) }}{{ getInflatedPrice(getVehiclePriceConverted(vehicle))?.toFixed(2) }}
                                </span>
                                <span class="bg-red-500 text-white text-[11px] font-bold px-2 py-0.5 rounded-full leading-none">
                                    -{{ activePromo.discount_percentage }}%
                                </span>
                            </div>
                            <span class="text-customPrimaryColor text-2xl font-bold font-['Outfit']">
                                {{ getCurrencySymbol(selectedCurrency) }}{{ getVehiclePriceConverted(vehicle)?.toFixed(2) }}
                            </span>
                        </template>
                        <template v-else>
                            <span class="text-customPrimaryColor text-2xl font-bold font-['Outfit']">
                                {{ getCurrencySymbol(selectedCurrency) }}{{ getVehiclePriceConverted(vehicle)?.toFixed(2) }}
                            </span>
                        </template>
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
            :totals-currency="selectedCheckoutData.totals_currency"
            :vehicle-total="selectedCheckoutData.vehicle_total"
            :vehicle-total-currency="selectedCheckoutData.vehicle_total_currency"
            :location-details="locationDetails"
            :location-instructions="locationInstructions" :driver-requirements="driverRequirements" :terms="termsData"
            :search-session-id="props.search_session_id"
            :gateway-search-id="props.gateway_search_id"
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

/* Airbnb-Style Marker Styles */
.marker-bnb {
    position: relative;
    cursor: pointer;
    transition: all 0.15s ease;
}

.marker-bnb-inner {
    background: white;
    color: #1a1a1a;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600;
    font-size: 13px;
    padding: 6px 12px;
    border-radius: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.08);
    white-space: nowrap;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1.5px solid rgba(0, 0, 0, 0.04);
    position: relative;
    text-align: center;
}

.marker-bnb-inner::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    width: 10px;
    height: 10px;
    background: white;
    border-radius: 2px;
    transform: translateX(-50%) rotate(45deg);
    box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.06);
}

.marker-bnb-inner:hover {
    background: #153B4F;
    color: white;
    transform: scale(1.08);
    box-shadow: 0 4px 16px rgba(21, 59, 79, 0.35), 0 2px 4px rgba(0, 0, 0, 0.1);
}

.marker-bnb-inner:hover::after {
    background: #153B4F;
}

.marker-bnb-inner:active {
    background: #0f2936;
    color: white;
    transform: scale(1.02);
}

.marker-bnb-inner:active::after {
    background: #0f2936;
}

.marker-bnb-highlighted .marker-bnb-inner {
    background: #153B4F;
    color: white;
    transform: scale(1.08);
    box-shadow: 0 4px 16px rgba(21, 59, 79, 0.35), 0 2px 4px rgba(0, 0, 0, 0.1);
}

.marker-bnb-highlighted .marker-bnb-inner::after {
    background: #153B4F;
}

.custom-div-icon {
    background: none !important;
    border: none !important;
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

/* Airbnb-Style Popup Styles */
.leaflet-popup-content-wrapper {
    padding: 0 !important;
    overflow: hidden;
    border-radius: 16px !important;
    background: white !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06) !important;
    border: none !important;
}

.leaflet-popup-content {
    margin: 0 !important;
    width: 240px !important;
}

.leaflet-popup-tip {
    background: white !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.leaflet-popup-close-button {
    top: 8px !important;
    right: 8px !important;
    width: 24px !important;
    height: 24px !important;
    font-size: 18px !important;
    line-height: 24px !important;
    background: rgba(255, 255, 255, 0.9) !important;
    color: #1a1a1a !important;
    border-radius: 50% !important;
    text-align: center !important;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    z-index: 10;
}

.popup-bnb {
    font-family: 'DM Sans', sans-serif;
}

.popup-bnb-img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}

.popup-bnb-body {
    padding: 14px 16px 16px;
}

.popup-bnb-name {
    font-family: 'Outfit', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 4px;
    line-height: 1.3;
}

.popup-bnb-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.popup-bnb-star {
    color: #153B4F;
}

.popup-bnb-location {
    font-size: 12px;
    color: #9ca3af;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.popup-bnb-footer {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.popup-bnb-price {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: #153B4F;
}

.popup-bnb-btn {
    background: #153B4F;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    width: 100%;
    text-align: center;
}

.popup-bnb-btn:hover {
    background: #0f2936;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(21, 59, 79, 0.3);
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

/* .popup-image replaced by .popup-dark-img */

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

/* Booking Stepper Bar */
.booking-stepper-bar {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f4f8 100%);
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 0;
    position: sticky;
    top: 0;
    z-index: 10;
    backdrop-filter: blur(8px);
}
.stepper-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.stepper-done {
    background: linear-gradient(135deg, #059669, #10b981);
    color: white;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}
.stepper-active {
    background: linear-gradient(135deg, #1e3a5f, #2d5a8f);
    color: white;
    box-shadow: 0 2px 12px rgba(30, 58, 95, 0.35);
    animation: stepper-pulse 2s ease-in-out infinite;
}
.stepper-pending {
    background: #e2e8f0;
    color: #94a3b8;
}
@keyframes stepper-pulse {
    0%, 100% { box-shadow: 0 2px 12px rgba(30, 58, 95, 0.35); }
    50% { box-shadow: 0 2px 20px rgba(30, 58, 95, 0.5), 0 0 0 6px rgba(30, 58, 95, 0.08); }
}
.stepper-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.01em;
}
.stepper-sub {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    line-height: 1.3;
}
.stepper-line {
    height: 3px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
    position: relative;
}
.stepper-line-fill {
    height: 100%;
    background: linear-gradient(90deg, #059669, #10b981);
    border-radius: 9999px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
    max-height: calc(100vh - 110px);
    display: none;
    flex-direction: column;
    overflow: hidden;
}

@media (min-width: 1280px) {
    .filters-sidebar {
        display: flex;
    }
}

/* Unified Filter Panel */
.filters-panel {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
    padding: 18px 18px 14px;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 110px);
}

/* Panel Header */
.fp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--gray-100);
    flex-shrink: 0;
}

.fp-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fp-title svg {
    color: var(--gray-600);
}

.fp-count-badge {
    font-size: 11px;
    background: var(--primary-800);
    color: var(--white);
    padding: 1px 7px;
    border-radius: 10px;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.fp-reset {
    background: none;
    border: none;
    font-size: 12px;
    font-weight: 500;
    color: var(--primary-600);
    cursor: pointer;
    font-family: inherit;
    padding: 4px 0;
    transition: color var(--duration-fast);
}

.fp-reset:hover {
    color: var(--primary-800);
    text-decoration: underline;
}

/* Scrollable Sections Area */
.fp-scroll-area {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.15) transparent;
}

.fp-scroll-area::-webkit-scrollbar {
    width: 4px;
}

.fp-scroll-area::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 4px;
}

.fp-scroll-area::-webkit-scrollbar-track {
    background: transparent;
}

/* Filter Section */
.fp-section {
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.fp-section:last-child {
    border-bottom: none;
}

/* Section Header */
.fp-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 2px 0;
    transition: opacity 0.15s;
}

.fp-section-header:hover {
    opacity: 0.85;
}

/* Icon Badges */
.fp-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.15s;
}

.fp-section-header:hover .fp-icon-badge {
    transform: scale(1.05);
}

.fp-icon-badge-sm {
    width: 30px;
    height: 30px;
    border-radius: 7px;
}

.fp-icon-price { background: #fef3c7; color: #b45309; }
.fp-icon-category { background: #dbeafe; color: #1d4ed8; }
.fp-icon-transmission { background: #f3e8ff; color: #7c3aed; }
.fp-icon-fuel { background: #dcfce7; color: #15803d; }
.fp-icon-seats { background: #ffe4e6; color: #be123c; }

.fp-section-label {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
}

/* Active Dot */
.fp-active-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--primary-800);
    flex-shrink: 0;
}

/* Chevron */
.fp-chevron {
    color: var(--gray-400);
    transition: transform 0.25s ease;
    flex-shrink: 0;
}

.fp-chevron-collapsed {
    transform: rotate(-90deg);
}

/* Collapse Animation (grid-template-rows) */
.fp-collapse {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows 0.25s ease;
}

.fp-collapse-open {
    grid-template-rows: 1fr;
}

.fp-collapse > div {
    overflow: hidden;
    min-height: 0;
}

/* Section Body (price) */
.fp-section-body {
    margin-top: 10px;
    margin-left: 44px;
}

.fp-price-display {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--gray-500);
    margin-bottom: 12px;
}

.fp-price-display strong {
    color: #0f172a;
    font-weight: 600;
    font-size: 15px;
}

/* Options (checkboxes) */
.fp-options {
    margin-top: 10px;
    margin-left: 44px;
    display: flex;
    flex-direction: column;
    gap: 1px;
    max-height: 220px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.12) transparent;
}

.fp-options::-webkit-scrollbar {
    width: 3px;
}

.fp-options::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 3px;
}

/* Filter Option Row */
.fp-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    user-select: none;
}

.fp-option:hover {
    background: var(--gray-50);
}

.fp-option-active {
    background: #f0f7fa;
}

.fp-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Custom Checkbox */
.fp-checkbox {
    width: 19px;
    height: 19px;
    border-radius: 5px;
    border: 1.5px solid var(--gray-300);
    background: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
}

.fp-option-active .fp-checkbox {
    background: var(--primary-800);
    border-color: var(--primary-800);
    box-shadow: 0 0 0 2px rgba(36, 95, 125, 0.15);
}

.fp-checkbox svg {
    width: 12px;
    height: 12px;
    color: var(--white);
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.fp-option-active .fp-checkbox svg {
    opacity: 1;
    transform: scale(1);
}

.fp-option-label {
    flex: 1;
    font-size: 13.5px;
    color: var(--gray-600);
    transition: all 0.15s;
}

.fp-option-active .fp-option-label {
    color: var(--gray-800);
    font-weight: 500;
}

.fp-option-count {
    font-size: 11px;
    color: var(--gray-400);
    background: var(--gray-100);
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.15s;
}

.fp-option-active .fp-option-count {
    background: #dbeef5;
    color: var(--primary-600);
}

/* ===== Mobile Filter Drawer ===== */
.fpm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 14px;
    border-bottom: 1px solid var(--gray-100);
    flex-shrink: 0;
}

.fpm-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.fpm-close-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--gray-100);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--gray-600);
    transition: background 0.15s;
}

.fpm-close-btn:hover {
    background: var(--gray-200);
}

.fpm-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px 14px;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.12) transparent;
}

.fpm-label {
    font-size: 13px;
}

.fpm-section-body {
    margin-left: 38px;
}

.fpm-options {
    margin-left: 38px;
}

.fpm-option {
    padding: 6px 8px;
    gap: 8px;
}

.fpm-checkbox {
    width: 17px;
    height: 17px;
    border-radius: 4px;
}

.fpm-checkbox svg {
    width: 10px;
    height: 10px;
}

.fpm-option-label {
    font-size: 12.5px;
}

.fpm-option-count {
    font-size: 10px;
    padding: 1px 6px;
}

.fpm-footer {
    display: flex;
    gap: 10px;
    padding: 14px;
    border-top: 1px solid var(--gray-100);
    flex-shrink: 0;
}

.fpm-btn-reset {
    flex: 1;
    padding: 10px 0;
    border-radius: 10px;
    border: 1.5px solid var(--gray-200);
    background: var(--white);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-600);
    cursor: pointer;
    transition: background 0.15s;
}

.fpm-btn-reset:hover {
    background: var(--gray-50);
}

.fpm-btn-show {
    flex: 2;
    padding: 10px 0;
    border-radius: 10px;
    border: none;
    background: var(--primary-600);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--white);
    cursor: pointer;
    transition: background 0.15s;
    box-shadow: 0 4px 12px rgba(36, 95, 125, 0.2);
}

.fpm-btn-show:hover {
    background: var(--primary-800);
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
