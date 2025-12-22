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
import CarListingCard from "@/Components/CarListingCard.vue"; // Import CarListingCard
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

const { selectedCurrency, supportedCurrencies, changeCurrency, loading: currencyLoading } = useCurrency();

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

const exchangeRates = ref(null);

const fetchExchangeRates = async () => {
    try {
        const response = await fetch(`${import.meta.env.VITE_EXCHANGERATE_API_BASE_URL}/v6/${import.meta.env.VITE_EXCHANGERATE_API_KEY}/latest/USD`);
        const data = await response.json();
        if (data.result === 'success') {
            exchangeRates.value = data.conversion_rates;
        } else {
            console.error('Failed to fetch exchange rates:', data['error-type']);
        }
    } catch (error) {
        console.error('Error fetching exchange rates:', error);
    }
};

const symbolToCodeMap = {
    '$': 'USD',
    '€': 'EUR',
    '£': 'GBP',
    '¥': 'JPY',
    'A$': 'AUD',
    'C$': 'CAD',
    'Fr': 'CHF',
    'HK$': 'HKD',
    'S$': 'SGD',
    'kr': 'SEK',
    '₩': 'KRW',
    'kr': 'NOK',
    'NZ$': 'NZD',
    '₹': 'INR',
    'Mex$': 'MXN',
    'R$': 'BRL',
    '₽': 'RUB',
    'R': 'ZAR',
    'AED': 'AED'
    // Add other symbol-to-code mappings as needed
};

const convertCurrency = (price, fromCurrency) => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) {
        return 0; // Return 0 if price is not a number
    }

    let fromCurrencyCode = fromCurrency;
    if (symbolToCodeMap[fromCurrency]) {
        fromCurrencyCode = symbolToCodeMap[fromCurrency];
    }

    if (!exchangeRates.value || !fromCurrencyCode || !selectedCurrency.value) {
        return numericPrice; // Return original price if rates not loaded or currencies are invalid
    }
    const rateFrom = exchangeRates.value[fromCurrencyCode];
    const rateTo = exchangeRates.value[selectedCurrency.value];
    if (rateFrom && rateTo) {
        return (numericPrice / rateFrom) * rateTo;
    }
    return numericPrice; // Fallback to original price if conversion is not possible
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
    seoMeta: Object, // Added seoMeta prop
    locale: String, // Added locale prop
    greenMotionVehicles: Object, // New: GreenMotion vehicles data
    okMobilityVehicles: Object, // New: OK Mobility vehicles data
});

// Initialize map immediately for fast loading, then update when currency data loads
onMounted(() => {
    // Initialize map immediately with default currency
    setTimeout(() => {
        initMap();
    }, 50);

    // Load currency data in background and update map when ready
    loadCurrencyData();
});

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
        if (mobileMap) {
            addMobileMarkers();
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
    () => {
        const data = form.data();
        // Exclude client-side filters from triggering server search
        const { 
            brand, 
            category_id, 
            transmission, 
            fuel, 
            seating_capacity, 
            price_range, 
            color,
            ...serverParams 
        } = data;
        return serverParams;
    },
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
    const internal = props.vehicles.data || [];
    const greenMotion = props.greenMotionVehicles?.data || [];
    // Provider vehicles (including Adobe, Wheelsys, etc.) are already included in the main vehicles collection
    const providerVehicles = internal.filter(v => v.source !== 'internal');
    const internalOnly = internal.filter(v => v.source === 'internal');
    return [...internalOnly, ...greenMotion, ...providerVehicles];
});

// Helper to get vehicle price in selected currency
const getVehiclePriceConverted = (vehicle) => {
    if (!vehicle) return null;
    
    let originalPrice = null;
    let originalCurrency = 'USD';
    
    // Determine price and currency based on source
    if (vehicle.source === 'adobe' && vehicle.tdr) {
        // For Adobe, use tdr / rental days (or use price_per_day if already calculated)
        originalPrice = vehicle.price_per_day || (vehicle.tdr / (numberOfRentalDays.value || 1));
        originalCurrency = 'USD';
    } else if (vehicle.source === 'wheelsys' || vehicle.source === 'locauto_rent') {
        originalPrice = vehicle.price_per_day;
        originalCurrency = vehicle.currency || 'USD';
    } else if (vehicle.source === 'greenmotion' || vehicle.source === 'usave') {
        // For GreenMotion/USave, use the total rental price directly
        originalPrice = parseFloat(vehicle.products?.[0]?.total || 0);
        originalCurrency = vehicle.products?.[0]?.currency || 'USD';
    } else if (vehicle.source === 'okmobility') {
        originalPrice = vehicle.price_per_day;
        originalCurrency = 'EUR';
    } else {
        // Internal vehicles
        originalPrice = vehicle.price_per_day;
        originalCurrency = vehicle.currency || 'USD';
    }
    
    if (originalPrice === null || isNaN(parseFloat(originalPrice))) return null;
    
    return convertCurrency(parseFloat(originalPrice), originalCurrency);
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
const clientFilteredVehicles = computed(() => {
    let result = allVehiclesForMap.value;

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
                if (['M','N','C'].includes(char)) vTrans = 'manual';
                if (['A','B','D'].includes(char)) vTrans = 'automatic';
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
                 // N=Unspecified(Gas?), R=Yes(AC)+Gas?
                 // Standard SIPP: D=Diesel, Q=Diesel, H=Hybrid, I=Hybrid, E=Electric, C=Electric, L=LPG, S=LPG, Z=LPG, M=Multi, F=Multi, V=Petrol, N=Petrol...
                 if (['D','Q'].includes(char)) vFuel = 'diesel';
                 else if (['H','I'].includes(char)) vFuel = 'hybrid';
                 else if (['E','C'].includes(char)) vFuel = 'electric';
                 else vFuel = 'petrol'; // Default assumptions
             }
             return vFuel.includes(fuel);
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
                        if (['M','N','C'].includes(char)) vTrans = 'manual';
                        if (['A','B','D'].includes(char)) vTrans = 'automatic';
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
                         if (['D','Q'].includes(char)) vFuel = 'diesel';
                         else if (['H','I'].includes(char)) vFuel = 'hybrid';
                         else if (['E','C'].includes(char)) vFuel = 'electric';
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
            if (['M','N','C'].includes(char)) vTrans = 'manual';
            if (['A','B','D'].includes(char)) vTrans = 'automatic';
        }
        return vTrans.charAt(0).toUpperCase() + vTrans.slice(1);
    });
    const fuelCounts = countBy(filterExcluding('fuel'), v => {
         let vFuel = (v.fuel || v.fuel_type || 'Petrol').toLowerCase();
         const sipp = v.sipp || v.sipp_code;
        if (sipp && sipp.length === 4) {
             const char = sipp.charAt(3).toUpperCase();
             if (['D','Q'].includes(char)) vFuel = 'diesel';
             else if (['H','I'].includes(char)) vFuel = 'hybrid';
             else if (['E','C'].includes(char)) vFuel = 'electric';
             else vFuel = 'petrol';
         }
         return vFuel.charAt(0).toUpperCase() + vFuel.slice(1);
    });
    const seatCounts = countBy(filterExcluding('seats'), v => parseInt(v.seating_capacity || v.passenger_capacity || v.passengers || v.adults || v.seat_number || v.seats || 0));

    return {
        brands: Object.entries(brandCounts).map(([k, v]) => ({ label: k, value: k, count: v })).sort((a,b) => b.count - a.count),
        categories: Object.entries(categoryCounts).map(([k, v]) => ({ label: k, value: k, count: v })).sort((a,b) => b.count - a.count),
        transmissions: Object.entries(transmissionCounts).map(([k, v]) => ({ label: k, value: k.toLowerCase(), count: v })),
        fuels: Object.entries(fuelCounts).map(([k, v]) => ({ label: k, value: k.toLowerCase(), count: v })),
        seats: Object.entries(seatCounts).map(([k, v]) => ({ label: `${k} Seats`, value: k, count: v })).sort((a,b) => parseInt(a.value) - parseInt(b.value))
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

    if (vehicle.source === 'wheelsys') {
        const originalCurrency = vehicle.currency || 'USD';
        priceValue = convertCurrency(vehicle.price_per_day, originalCurrency);
        currencySymbol = getCurrencySymbol(selectedCurrency.value);
    } else if (vehicle.source !== 'internal') {
        // Handle specific providers that don't use the standard products array structure
        if (vehicle.source === 'adobe') {
            const total = parseFloat(vehicle.tdr || 0);
            priceValue = convertCurrency(total, 'USD');
        } else if ((vehicle.source === 'wheelsys' || vehicle.source === 'locauto_rent') && vehicle.price_per_day) {
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

    // The other fields like where, lat, lng, dates, source, etc., are NOT touched.
    // They will retain their current values.
    submitFilters();
};

const createPopupContent = (vehicle, primaryImage, popupPrice, detailRoute) => {
    if (vehicle.source === 'okmobility') {
        return `
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.brand} ${vehicle.model}" class="popup-image !w-40 !h-20" />
                <p class="font-semibold !w-40">${vehicle.brand} ${vehicle.model}</p>
                ${vehicle.sipp_code ? `<p class="!w-40 text-sm">SIPP: ${vehicle.sipp_code}</p>` : ''}
                <p class="!w-40">${vehicle.full_vehicle_address || ''}</p>
                ${vehicle.station ? `<p class="!w-40 text-sm"><strong>Station:</strong> ${vehicle.station}</p>` : ''}
                <p class="!w-40 font-semibold">Price: ${popupPrice}</p>
                <a href="${detailRoute}"
                   class="text-blue-500 hover:text-blue-700 block mt-2"
                   onclick="event.preventDefault(); window.location.href = this.href;">
                    View Details
                </a>
            </div>
        `;
    } else if (vehicle.source === 'wheelsys') {
        return `
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.brand} ${vehicle.model}" class="popup-image !w-40 !h-20" />
                <p class="font-semibold !w-40">${vehicle.brand} ${vehicle.model}</p>
                ${vehicle.acriss_code || vehicle.group_code ? `<p class="!w-40 text-sm">${vehicle.acriss_code || vehicle.group_code}</p>` : ''}
                <p class="!w-40">${vehicle.full_vehicle_address || ''}</p>
                <p class="!w-40 font-semibold">Price: ${popupPrice}</p>
                <a href="${detailRoute}"
                   class="text-blue-500 hover:text-blue-700 block mt-2"
                   onclick="event.preventDefault(); window.location.href = this.href;">
                    View Details
                </a>
            </div>
        `;
    } else if (vehicle.source === 'locauto_rent') {
        return `
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.brand} ${vehicle.model}" class="popup-image !w-40 !h-20" />
                <p class="font-semibold !w-40">${vehicle.brand} ${vehicle.model}</p>
                ${vehicle.sipp_code ? `<p class="!w-40 text-sm">SIPP: ${vehicle.sipp_code}</p>` : ''}
                
                <p class="!w-40">${vehicle.full_vehicle_address || ''}</p>
                <p class="!w-40 font-semibold">Price: ${popupPrice}</p>
                <a href="${detailRoute}"
                   class="text-blue-500 hover:text-blue-700 block mt-2"
                   onclick="event.preventDefault(); window.location.href = this.href;">
                    View Details
                </a>
            </div>
        `;
        } else {
        return `
            <div class="text-center popup-content">
                <img src="${primaryImage}" alt="${vehicle.brand} ${vehicle.model}" class="popup-image !w-40 !h-20" />
                ${vehicle.source === 'internal' && vehicle.average_rating ? `<p class="rating !w-40">${vehicle.average_rating.toFixed(1)} ★ (${vehicle.review_count} reviews)</p>` : ''}
                <p class="font-semibold !w-40">${vehicle.brand} ${vehicle.model}</p>
                <p class="!w-40">${vehicle.full_vehicle_address || ''}</p>
                <p class="!w-40">Price: ${popupPrice}</p>
                <a href="${detailRoute}"
                   class="text-blue-500 hover:text-blue-700"
                   onclick="event.preventDefault(); window.location.href = this.href;">
                    View Details
                </a>
            </div>
        `;
    }
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

onMounted(() => {
    if (form.price_range) {
        const [min, max] = form.price_range.split('-').map(Number);
        priceRangeValues.value = [min || dynamicPriceRange.value.min, max || dynamicPriceRange.value.max];
        tempPriceRangeValues.value = [min || dynamicPriceRange.value.min, max || dynamicPriceRange.value.max];
    }
});

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
    if (page.props.auth?.user) {
        fetchFavoriteStatus();
    }
    setupIntersectionObserver(); // Initialize Intersection Observer
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
    
    <!-- Mobile Filters Left Sidebar (Moved to root for Z-Index) -->
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-x-[-100%]"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition ease-in duration-300"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-[-100%]"
    >
        <div v-if="showMobileFilters" class="fixed inset-0 z-[2000] flex md:hidden">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showMobileFilters = false"></div>
            
            <!-- Sidebar -->
            <div class="relative w-[85%] max-w-sm bg-white h-full shadow-2xl flex flex-col z-50 transform transition-transform">
                <div class="flex justify-between items-center p-4 border-b border-gray-100 bg-white">
                    <div class="flex items-center gap-2">
                        <img :src="filterIcon" alt="" class="w-5 h-5" loading="lazy" />
                        <h2 class="text-lg font-bold text-gray-800">Filters</h2>
                    </div>
                    <button @click="showMobileFilters = false" class="p-2 bg-gray-50 rounded-full hover:bg-gray-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">

            <form class="space-y-6 pt-2">
                <!-- Price Range (Budget) -->
                <div class="border-b border-gray-100 pb-4">
                    <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="priceIcon" class="w-4 h-4 opacity-70" > Budget
                    </h4>
                    <div class="px-1">
                        <div class="flex justify-between text-xs text-gray-500 mb-2">
                            <span>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[0] }}</span>
                            <span>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[1] }}</span>
                        </div>
                        <VueSlider v-model="tempPriceRangeValues" :min="dynamicPriceRange.min" :max="dynamicPriceRange.max"
                            :interval="1" :tooltip="'none'" :height="6" :dot-size="16"
                            :process-style="{ backgroundColor: '#153b4f' }"
                            :rail-style="{ backgroundColor: '#e5e7eb' }"
                            :enable-cross="false"
                            class="mb-3" />
                        <div class="flex justify-end">
                             <button @click="applyPriceRange" class="text-xs bg-customPrimaryColor text-white px-3 py-1.5 rounded hover:opacity-90 transition">Apply Price</button>
                        </div>
                    </div>
                </div>

                <!-- Passenger Seats -->
                <div class="border-b border-gray-100 pb-4">
                    <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="seatingIcon" class="w-4 h-4 opacity-70"> Passenger Seats
                    </h4>
                    <div class="space-y-3">
                        <label v-for="option in facets.seats" :key="option.value" class="flex items-center gap-3 cursor-pointer group p-1 hover:bg-gray-50 rounded-lg transition-colors">
                            <input type="checkbox" :checked="form.seating_capacity == option.value" @change="form.seating_capacity = form.seating_capacity == option.value ? '' : option.value" 
                                    class="w-5 h-5 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition cursor-pointer">
                            <span class="text-base text-gray-600 group-hover:text-customPrimaryColor transition font-medium">{{ option.label }} <span class="text-xs text-gray-400 font-normal">({{ option.count }})</span></span>
                        </label>
                    </div>
                </div>

                <!-- Car Brand -->
                <div class="border-b border-gray-100 pb-4">
                    <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="brandIcon" class="w-4 h-4 opacity-70"> Car Brand
                    </h4>
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                        <label v-for="option in facets.brands" :key="option.value" class="flex items-center gap-3 cursor-pointer group p-1 hover:bg-gray-50 rounded-lg transition-colors">
                            <input type="checkbox" :checked="form.brand === option.value" @change="form.brand = form.brand === option.value ? '' : option.value" 
                                class="w-5 h-5 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition cursor-pointer">
                            <span class="text-base text-gray-600 group-hover:text-customPrimaryColor transition font-medium">{{ option.label }} <span class="text-xs text-gray-400 font-normal">({{ option.count }})</span></span>
                        </label>
                    </div>
                </div>

                    <!-- Vehicle Type -->
                <div class="border-b border-gray-100 pb-4">
                    <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="categoryIcon" class="w-4 h-4 opacity-70"> Vehicle Type
                    </h4>
                    <div class="space-y-3">
                        <label v-for="option in facets.categories" :key="option.value" class="flex items-center gap-3 cursor-pointer group p-1 hover:bg-gray-50 rounded-lg transition-colors">
                            <input type="checkbox" :checked="form.category_id === option.value" @change="form.category_id = form.category_id === option.value ? '' : option.value" 
                                    class="w-5 h-5 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition cursor-pointer">
                            <span class="text-base text-gray-600 group-hover:text-customPrimaryColor transition font-medium">{{ option.label }} <span class="text-xs text-gray-400 font-normal">({{ option.count }})</span></span>
                        </label>
                    </div>
                </div>

                <!-- Transmission -->
                <div class="border-b border-gray-100 pb-4">
                        <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="transmissionIcon" class="w-4 h-4 opacity-70"> Transmission
                    </h4>
                    <div class="space-y-3">
                        <label v-for="option in facets.transmissions" :key="option.value" class="flex items-center gap-3 cursor-pointer group p-1 hover:bg-gray-50 rounded-lg transition-colors">
                            <input type="checkbox" :checked="form.transmission === option.value" @change="form.transmission = form.transmission === option.value ? '' : option.value" 
                                    class="w-5 h-5 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition cursor-pointer">
                            <span class="text-base text-gray-600 group-hover:text-customPrimaryColor transition font-medium">{{ option.label }} <span class="text-xs text-gray-400 font-normal">({{ option.count }})</span></span>
                        </label>
                    </div>
                </div>

                <!-- Fuel -->
                <div class="border-b border-gray-100 pb-4">
                        <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="fuelIcon" class="w-4 h-4 opacity-70"> Fuel Type
                    </h4>
                    <div class="space-y-3">
                        <label v-for="option in facets.fuels" :key="option.value" class="flex items-center gap-3 cursor-pointer group p-1 hover:bg-gray-50 rounded-lg transition-colors">
                            <input type="checkbox" :checked="form.fuel === option.value" @change="form.fuel = form.fuel === option.value ? '' : option.value" 
                                    class="w-5 h-5 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition cursor-pointer">
                            <span class="text-base text-gray-600 group-hover:text-customPrimaryColor transition font-medium">{{ option.label }} <span class="text-xs text-gray-400 font-normal">({{ option.count }})</span></span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-3 pt-4 mt-2">
                    <button @click="resetFilters" type="button"
                        class="py-3 px-4 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-all duration-300 flex items-center justify-center gap-2">
                        Clear All
                    </button>
                    <button @click="showMobileFilters = false" type="button"
                        class="py-3 px-4 bg-customPrimaryColor text-white rounded-lg font-medium hover:bg-opacity-90 transition-all duration-300 flex items-center justify-center gap-2">
                        Done
                    </button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </transition>
    <div v-if="currencyLoading" class="fixed inset-0 z-[100] flex items-center justify-center bg-white bg-opacity-70">
        <img :src="moneyExchangeSymbol" alt="Loading..." class="w-16 h-16 animate-spin" />
    </div>
    <SchemaInjector v-if="schema" :schema="schema" />
    <section class="bg-customPrimaryColor py-customVerticalSpacing relative z-50">
        <div class="">
            <SearchBar class="border-[2px] rounded-[20px] border-white mt-0 mb-0 max-[768px]:border-none"
                :prefill="searchQuery"
                @update-search-params="handleSearchUpdate" />
                <SchemaInjector v-if="$page.props.organizationSchema" :schema="$page.props.organizationSchema" />
        </div>
    </section>

    <section>
    <div id="filter-section" class="full-w-container py-8 relative z-40">
        <!-- Mobile filter button (visible only on mobile, hidden when fixed button appears) -->
        <div class="md:hidden mb-4 flex items-center justify-center gap-4" v-if="!showFixedMobileFilterButton">
            <button @click="showMobileFilters = true"
                class="flex-1 flex items-center justify-center gap-2 p-3 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 text-gray-700">
                <img :src="filterIcon" alt="Filter" class="w-5 h-5" loading="lazy" />
                <span class="text-base font-semibold">Filter</span>
            </button>
        </div>



        <!-- Desktop filters moved to sidebar -->

        <!-- Mobile Filters Canvas/Sidebar -->


    </div>
</section>



    <div class="full-w-container mx-auto mb-[4rem]">
        <div class="grid grid-cols-12 gap-6">
            
            <!-- Left Sidebar (Sticky Filters) -->
            <aside class="hidden md:block col-span-3 h-fit sticky top-24 z-30">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 overflow-y-auto max-h-[85vh] custom-scrollbar">
                    <div class="flex items-center justify-between mb-4 border-b pb-2">
                        <h3 class="font-bold text-lg text-gray-800">Filters</h3>
                        <button @click="resetFilters" class="text-xs text-customPrimaryColor font-medium hover:underline">Reset All</button>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-sm text-gray-700 mb-3 flex items-center gap-2">
                            <img :src="priceIcon" class="w-4 h-4 opacity-70" > Budget
                        </h4>
                        <div class="px-2">
                            <div class="flex justify-between text-xs text-gray-500 mb-2">
                                <span>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[0] }}</span>
                                <span>{{ getCurrencySymbol(selectedCurrency) }}{{ tempPriceRangeValues[1] }}</span>
                            </div>
                            <!-- Using v-model on temp and @drag-end for apply to avoid excessive re-fetches or use a separate Apply button if strictly needed, 
                                 but user wants immediate feedback usually. Given current logic uses 'applyPriceRange', 
                                 I'll add a small 'Apply' button or rely on change event if Slider supports it. 
                                 The previous code had an Apply button. I'll keep it simple: Slider + Apply text/button 
                            -->
                            <VueSlider v-model="tempPriceRangeValues" :min="dynamicPriceRange.min" :max="dynamicPriceRange.max"
                                :interval="1" :tooltip="'none'" :height="6" :dot-size="16"
                                :process-style="{ backgroundColor: '#153b4f' }"
                                :rail-style="{ backgroundColor: '#e5e7eb' }"
                                :enable-cross="false"
                                class="mb-2" />
                            <div class="flex justify-end">
                                <button @click="applyPriceRange" class="text-xs bg-customPrimaryColor text-white px-3 py-1 rounded hover:opacity-90 transition">Apply Price</button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Group Component (Inline for simplicity or create reusable if needed, sticking to inline loops for stability) -->
                    
                    <!-- Passenger Seats -->
                    <div class="mb-5 border-b border-gray-50 pb-4">
                        <h4 class="font-semibold text-sm text-gray-700 mb-2 flex items-center gap-2">
                             <img :src="seatingIcon" class="w-4 h-4 opacity-70"> Passenger Seats
                        </h4>
                        <div class="space-y-2">
                            <label v-for="option in facets.seats" :key="option.value" class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" :checked="form.seating_capacity == option.value" @change="form.seating_capacity = form.seating_capacity == option.value ? '' : option.value" 
                                       class="w-4 h-4 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition">
                                <span class="text-sm text-gray-600 group-hover:text-customPrimaryColor transition">{{ option.label }} <span class="text-xs text-gray-400">({{ option.count }})</span></span>
                            </label>
                        </div>
                    </div>

                    <!-- Car Brand -->
                    <div class="mb-5 border-b border-gray-50 pb-4">
                        <h4 class="font-semibold text-sm text-gray-700 mb-2 flex items-center gap-2">
                             <img :src="brandIcon" class="w-4 h-4 opacity-70"> Car Brand
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto pr-1 custom-scrollbar">
                            <label v-for="option in facets.brands" :key="option.value" class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" :checked="form.brand === option.value" @change="form.brand = form.brand === option.value ? '' : option.value" 
                                    class="w-4 h-4 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition">
                                <span class="text-sm text-gray-600 group-hover:text-customPrimaryColor transition">{{ option.label }} <span class="text-xs text-gray-400">({{ option.count }})</span></span>
                            </label>
                        </div>
                    </div>

                     <!-- Vehicle Type (Categories) -->
                    <div class="mb-5 border-b border-gray-50 pb-4">
                        <h4 class="font-semibold text-sm text-gray-700 mb-2 flex items-center gap-2">
                             <img :src="categoryIcon" class="w-4 h-4 opacity-70"> Vehicle Type
                        </h4>
                        <div class="space-y-2">
                            <label v-for="option in facets.categories" :key="option.value" class="flex items-center gap-3 cursor-pointer group">
                                <!-- Note: form.category_id was typically an ID. With generic facets, option.value is likely the Name if we normalized. 
                                     If form binds to ID, we need to map back.
                                     Simplification for Client Filter: Bind to label/name if filtering by name. 
                                     However, the server reload logic uses ID. 
                                     We are disabling server filtered reload for these. So usage of Name is fine for local.
                                     BUT, checking implementation plan: 'Disable Server Reload'.
                                     So I will bind form.category_id to the Name string OR find the ID. 
                                     Let's simplisticly bind to string name for client filter. 
                                     Or safer: If facet value is name, bind to name. form.category_id might need to change to form.category or stay as 'id' but hold string. 
                                     If it holds string, existing server logic might break if we reload page.
                                     For now, let's treat form.category_id as holding the Name for the purpose of this client filter, 
                                     as I am rewriting logic to be client-side. -->
                                <input type="checkbox" :checked="form.category_id === option.value" @change="form.category_id = form.category_id === option.value ? '' : option.value" 
                                       class="w-4 h-4 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition">
                                <span class="text-sm text-gray-600 group-hover:text-customPrimaryColor transition">{{ option.label }} <span class="text-xs text-gray-400">({{ option.count }})</span></span>
                            </label>
                        </div>
                    </div>

                    <!-- Transmission -->
                    <div class="mb-5 border-b border-gray-50 pb-4">
                         <h4 class="font-semibold text-sm text-gray-700 mb-2 flex items-center gap-2">
                             <img :src="transmissionIcon" class="w-4 h-4 opacity-70"> Transmission
                        </h4>
                        <div class="space-y-2">
                            <label v-for="option in facets.transmissions" :key="option.value" class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" :checked="form.transmission === option.value" @change="form.transmission = form.transmission === option.value ? '' : option.value" 
                                       class="w-4 h-4 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition">
                                <span class="text-sm text-gray-600 group-hover:text-customPrimaryColor transition">{{ option.label }} <span class="text-xs text-gray-400">({{ option.count }})</span></span>
                            </label>
                        </div>
                    </div>

                    <!-- Fuel -->
                    <div class="mb-5 border-b border-gray-50 pb-4">
                         <h4 class="font-semibold text-sm text-gray-700 mb-2 flex items-center gap-2">
                             <img :src="fuelIcon" class="w-4 h-4 opacity-70"> Fuel Type
                        </h4>
                        <div class="space-y-2">
                            <label v-for="option in facets.fuels" :key="option.value" class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" :checked="form.fuel === option.value" @change="form.fuel = form.fuel === option.value ? '' : option.value" 
                                       class="w-4 h-4 rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor transition">
                                <span class="text-sm text-gray-600 group-hover:text-customPrimaryColor transition">{{ option.label }} <span class="text-xs text-gray-400">({{ option.count }})</span></span>
                            </label>
                        </div>
                    </div>



                </div>
            </aside>

            <!-- Main Content - Vehicle List -->
            <div class="col-span-12 md:col-span-9 w-full">
                <!-- Map and Sort Controls -->
                <div class="mb-4 bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-800 px-2">
                            Available Vehicles
                            <span class="text-sm font-normal text-gray-500 ml-2">({{ vehicles?.total || 0 }} found)</span>
                        </h2>

                        <button @click="showMap = true" class="flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-customPrimaryColor transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-customPrimaryColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <span>View Map</span>
                        </button>
                    </div>
                </div>

                <div class="grid gap-5 grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 max-[768px]:grid-cols-1">
                    <!-- Loading State or No Vehicles -->
                    <div v-if="!clientFilteredVehicles || clientFilteredVehicles.length === 0"
                        class="text-center text-gray-500 col-span-full flex flex-col justify-center items-center gap-4 py-10 bg-white rounded-xl border border-gray-100 border-dashed">
                        <img :src=noVehicleIcon alt="" class="w-[15rem] opacity-80" loading="lazy">
                        <p class="text-lg font-medium text-customPrimaryColor">No vehicles found</p>
                        <span class="text-sm">Try adjusting your filters or search criteria.</span>
                        <button @click="resetFilters"
                            class="mt-2 px-6 py-2 bg-customPrimaryColor text-white rounded-lg hover:bg-opacity-90 transition shadow-sm">
                            Reset All Filters
                        </button>
                    </div>
                    
                    <div class="col-span-full grid gap-5 grid-cols-1 lg:grid-cols-1 xl:grid-cols-1"> 
                       <CarListingCard 
                            v-for="vehicle in clientFilteredVehicles" 
                            :key="vehicle.id" 
                            :vehicle="vehicle"
                            :form="form"
                            :favorite-status="favoriteStatus[vehicle.id]"
                            :pop-effect="popEffect[vehicle.id]"
                            @toggle-favourite="toggleFavourite"
                            @save-search-url="saveSearchUrl"
                            @mouseenter="highlightVehicleOnMap(vehicle)"
                            @mouseleave="unhighlightVehicleOnMap(vehicle)"
                            class="vehicle-card fade-up-hidden"
                        >
                            <template #dailyPrice>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-customPrimaryColor text-2xl font-bold">
                                        {{ getCurrencySymbol(selectedCurrency) }}{{ convertCurrency(vehicle.price_per_day, vehicle.currency).toFixed(2) }}
                                    </span>
                                    <span class="text-xs text-gray-500">/day</span>
                                </div>
                            </template>

                           </CarListingCard>
                    </div>
                </div>
                <!-- Pagination -->
                <div class="mt-4 pagination">
                    <div v-html="pagination_links"></div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Map Modal (Global) -->
    <div v-if="showMap" class="fixed inset-0 z-[9999]">
        <!-- Full-screen overlay with map -->
        <div class="relative w-full h-full bg-white">
            <!-- Close Button Header -->
            <div class="absolute top-0 left-0 right-0 z-[10000] bg-white shadow-md">
                <div class="flex items-center justify-between p-4">
                    <h2 class="text-xl font-semibold text-customPrimaryColor">Vehicle Map</h2>
                    <button @click="showMap = false"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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

.marker-pin {
    width: 80px; /* Fixed width to match iconSize */
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
