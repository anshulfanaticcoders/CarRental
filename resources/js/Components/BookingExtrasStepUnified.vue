<script setup>
import { ref, computed, watch, watchEffect, onMounted, onUnmounted, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import check from "../../assets/Check.svg";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
// Additional Icons
import carIcon from "../../assets/carIcon.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmissionIcon from "../../assets/transmittionIcon.svg";
import seatingIcon from "../../assets/travellerIcon.svg";
import doorIcon from "../../assets/door.svg";
import acIcon from "../../assets/ac.svg";
import {
    MapPin,
    Wifi,
    Baby,
    Snowflake,
    UserPlus,
    Shield,
    Plus,
    Navigation,
    CircleDashed,
    Smartphone,
    Gauge,
    Leaf
} from "lucide-vue-next";
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';

// Check if vehicle is Adobe Cars
const isAdobeCars = computed(() => {
    return props.vehicle?.source === 'adobe';
});

// Check if vehicle is LocautoRent
const isLocautoRent = computed(() => {
    return props.vehicle?.source === 'locauto_rent';
});

const page = usePage();

const providerMarkupRate = computed(() => {
    const rawRate = parseFloat(page.props.provider_markup_rate ?? '');
    if (Number.isFinite(rawRate) && rawRate >= 0) return rawRate;
    const rawPercent = parseFloat(page.props.provider_markup_percent ?? '');
    if (Number.isFinite(rawPercent) && rawPercent >= 0) return rawPercent / 100;
    return 0.15;
});

// Check if vehicle is Internal
const isInternal = computed(() => {
    return props.vehicle?.source === 'internal';
});

const providerGrossMultiplier = computed(() => {
    // Apply 15% platform fee to ALL vehicles (including internal)
    const rate = providerMarkupRate.value;
    return Number.isFinite(rate) ? (1 + rate) : 1;
});

// Check if vehicle is Renteon
const isRenteon = computed(() => {
    return props.vehicle?.source === 'renteon';
});

// Check if vehicle is OK Mobility
const isOkMobility = computed(() => {
    return props.vehicle?.source === 'okmobility';
});

const isSicilyByCar = computed(() => {
    return props.vehicle?.source === 'sicily_by_car';
});

const isRecordGo = computed(() => {
    return props.vehicle?.source === 'recordgo';
});

const isSurprice = computed(() => {
    return props.vehicle?.source === 'surprice';
});

const normalizeExtraCode = (value) => {
    const text = `${value || ''}`.trim();
    return text ? text.toUpperCase() : '';
};

const normalizeExtraCodeList = (value) => {
    if (!value) return [];
    if (Array.isArray(value)) {
        return value.map(normalizeExtraCode).filter(Boolean);
    }
    return `${value}`
        .split(',')
        .map(normalizeExtraCode)
        .filter(Boolean);
};

const okMobilityExtrasIncluded = computed(() => normalizeExtraCodeList(props.vehicle?.extras_included));
const okMobilityExtrasRequired = computed(() => normalizeExtraCodeList(props.vehicle?.extras_required));
const okMobilityExtrasAvailable = computed(() => normalizeExtraCodeList(props.vehicle?.extras_available));

const okMobilityExtrasByCode = computed(() => {
    const extras = props.vehicle?.extras || [];
    const map = new Map();
    extras.forEach((extra, index) => {
        const id = extra.id || extra.extraID || extra.extraId || extra.extra_id || extra.code || extra.extra || index;
        const code = normalizeExtraCode(extra.code || extra.extraID || extra.extraId || extra.extra_id || extra.extra || id);
        if (!code || map.has(code)) return;
        const name = extra.name || extra.extra || extra.description || extra.displayName || extra.code || code;
        const description = extra.description || extra.displayDescription || '';
        map.set(code, { name, description });
    });
    return map;
});

const resolveOkMobilityExtraLabel = (code) => {
    const normalized = normalizeExtraCode(code);
    const extra = okMobilityExtrasByCode.value.get(normalized);
    if (extra?.name) return extra.name;
    if (extra?.description) return extra.description;
    return normalized || code;
};

const okMobilityExtraLabels = (codes) => {
    const labels = (codes || []).map(resolveOkMobilityExtraLabel).filter(Boolean);
    return Array.from(new Set(labels));
};

const okMobilityIncludedLabels = computed(() => okMobilityExtraLabels(okMobilityExtrasIncluded.value));
const okMobilityRequiredLabels = computed(() => okMobilityExtraLabels(okMobilityExtrasRequired.value));
const okMobilityAvailableLabels = computed(() => {
    const labels = okMobilityExtraLabels(okMobilityExtrasAvailable.value);
    if (!labels.length) return [];
    const exclude = new Set([...okMobilityIncludedLabels.value, ...okMobilityRequiredLabels.value]);
    return labels.filter(label => !exclude.has(label));
});

const okMobilityPetExtras = computed(() => {
    const matches = [];
    okMobilityExtrasByCode.value.forEach((extra) => {
        const text = `${extra?.name || ''} ${extra?.description || ''}`.toLowerCase();
        if (text.includes('pet') || text.includes('pets') || text.includes('animal')) {
            matches.push(extra?.name || extra?.description);
        }
    });
    return Array.from(new Set(matches.filter(Boolean)));
});

const okMobilityTaxBreakdown = computed(() => {
    const total = parseFloat(props.vehicle?.preview_value ?? props.vehicle?.total_price ?? 0);
    const base = parseFloat(props.vehicle?.value_without_tax ?? 0);
    const rateRaw = props.vehicle?.tax_rate;
    const rate = rateRaw !== null && rateRaw !== undefined && rateRaw !== '' ? parseFloat(rateRaw) : null;
    let taxValue = props.vehicle?.tax_value;

    if (taxValue === null || taxValue === undefined || taxValue === '') {
        if (Number.isFinite(total) && Number.isFinite(base) && total && base) {
            taxValue = total - base;
        }
    }

    const tax = parseFloat(taxValue ?? 0);

    return {
        base: Number.isFinite(base) && base > 0 ? base : null,
        tax: Number.isFinite(tax) && tax > 0 ? tax : null,
        total: Number.isFinite(total) && total > 0 ? total : null,
        rate: Number.isFinite(rate) && rate > 0 ? rate : null,
    };
});

const okMobilityPickupStation = computed(() => props.vehicle?.pickup_station_name || props.vehicle?.station || '');
const okMobilityDropoffStation = computed(() => props.vehicle?.dropoff_station_name || props.vehicle?.station || '');
const okMobilityPickupAddress = computed(() => props.vehicle?.pickup_address || '');
const okMobilityDropoffAddress = computed(() => props.vehicle?.dropoff_address || '');
const okMobilitySameLocation = computed(() => {
    return okMobilityPickupStation.value === okMobilityDropoffStation.value
        && okMobilityPickupAddress.value === okMobilityDropoffAddress.value;
});

const okMobilityFuelPolicy = computed(() => props.vehicle?.fuel_policy || null);

const okMobilityCancellation = computed(() => props.vehicle?.cancellation || null);

const SBC_PROTECTION_CODES = ['CDW', 'TLW', 'CPP', 'GLD', 'PAI', 'RAP'];

const getSbcExcessValue = (service) => {
    if (!service) return null;
    let raw = service.excess ?? service.excessAmount ?? service.excessValue ?? service.excess_amount ?? null;
    if (raw && typeof raw === 'object') {
        raw = raw.amount ?? raw.value ?? raw.total ?? null;
    }
    const parsed = parseFloat(raw);
    return Number.isFinite(parsed) ? parsed : null;
};

const sicilyByCarServices = computed(() => {
    return Array.isArray(props.vehicle?.extras) ? props.vehicle.extras : [];
});

const sicilyByCarIncludedServices = computed(() => {
    if (!isSicilyByCar.value) return [];
    return sicilyByCarServices.value.filter(service => service?.isMandatory);
});

const sicilyByCarProtectionPlans = computed(() => {
    if (!isSicilyByCar.value) return [];
    return sicilyByCarServices.value
        .filter(service => !service?.isMandatory && SBC_PROTECTION_CODES.includes(`${service?.id || ''}`.toUpperCase()))
        .map((service, index) => {
            const code = `${service?.id || ''}`.toUpperCase() || `PROT_${index}`;
            const total = parseFloat(service?.total || 0);
            const excess = getSbcExcessValue(service);
            return {
                id: `sbc_protection_${code}_${index}`,
                code,
                name: service?.description || code,
                description: service?.description || code,
                price: total,
                daily_rate: props.numberOfDays ? total / props.numberOfDays : total,
                total_for_booking: total,
                amount: total,
                excess,
                required: false,
                payment: service?.payment || null,
            };
        });
});

const sicilyByCarOptionalExtras = computed(() => {
    if (!isSicilyByCar.value) return [];
    return sicilyByCarServices.value
        .filter(service => !service?.isMandatory && !SBC_PROTECTION_CODES.includes(`${service?.id || ''}`.toUpperCase()))
        .map((service, index) => {
            const code = `${service?.id || ''}`.toUpperCase() || `EXTRA_${index}`;
            const total = parseFloat(service?.total || 0);
            return {
                id: `sbc_extra_${code}_${index}`,
                code,
                name: service?.description || code,
                description: service?.description || code,
                price: total,
                daily_rate: props.numberOfDays ? total / props.numberOfDays : total,
                total_for_booking: total,
                amount: total,
                required: false,
                payment: service?.payment || null,
            };
        });
});

const sicilyByCarAllExtras = computed(() => {
    if (!isSicilyByCar.value) return [];
    return [...sicilyByCarProtectionPlans.value, ...sicilyByCarOptionalExtras.value];
});

const okMobilityCancellationSummary = computed(() => {
    const cancellation = okMobilityCancellation.value;
    if (!cancellation) return null;
    const available = cancellation.available === true || `${cancellation.available}`.toLowerCase() === 'true';
    const penalty = cancellation.penalty === true || `${cancellation.penalty}`.toLowerCase() === 'true';
    const amount = parseFloat(cancellation.amount ?? 0);
    const currency = cancellation.currency || props.vehicle?.currency || null;
    const deadline = cancellation.deadline || null;
    return {
        available,
        penalty,
        amount: Number.isFinite(amount) ? amount : null,
        currency,
        deadline
    };
});

const okMobilityInfoAvailable = computed(() => {
    if (!isOkMobility.value) return false;
    const hasTaxes = okMobilityTaxBreakdown.value.total || okMobilityTaxBreakdown.value.base || okMobilityTaxBreakdown.value.tax;
    return Boolean(
        okMobilityPickupAddress.value
        || okMobilityDropoffAddress.value
        || okMobilityPickupStation.value
        || okMobilityDropoffStation.value
        || vehicleLocationText.value
        || okMobilityIncludedLabels.value.length
        || okMobilityRequiredLabels.value.length
        || okMobilityAvailableLabels.value.length
        || okMobilityPetExtras.value.length
        || okMobilityFuelPolicy.value
        || okMobilityCancellationSummary.value
        || hasTaxes
    );
});
const isLikelyCode = (value) => {
    const text = `${value || ''}`.trim();
    if (!text) return false;
    return /^[A-Z0-9]{3,5}$/.test(text);
};

const displayVehicleName = computed(() => {
    if (isOkMobility.value) {
        const displayName = props.vehicle?.display_name;
        const description = props.vehicle?.group_description;
        const model = props.vehicle?.model;

        if (displayName && !isLikelyCode(displayName)) return displayName;
        if (description && !isLikelyCode(description)) return description;
        if (model && !isLikelyCode(model)) return model;
        return displayName || description || model || '';
    }
    const parts = [props.vehicle?.brand, props.vehicle?.model].filter(Boolean);
    return parts.join(' ');
});

const isFavrica = computed(() => {
    return props.vehicle?.source === 'favrica';
});

const isXDrive = computed(() => {
    return props.vehicle?.source === 'xdrive';
});

const resolveInternalProviderLabel = () => {
    return props.vehicle?.vendorProfileData?.company_name
        || props.vehicle?.vendor_profile_data?.company_name
        || props.vehicle?.vendor?.profile?.company_name
        || props.vehicle?.vendorProfile?.company_name
        || props.vehicle?.vendor_profile?.company_name
        || 'Vrooem';
};

const toTitleCase = (value) => {
    return `${value}`
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const getProviderExtraLabel = (extra) => {
    if (!isXDrive.value && !isFavrica.value) return extra?.name || '';
    const code = `${extra?.code || ''}`.trim();
    if (code) return toTitleCase(code);
    return extra?.name || '';
};

const providerBadge = computed(() => {
    const source = (props.vehicle?.source || '').toString().toLowerCase();
    if (!source) return null;

    const badgeMap = {
        internal: {
            label: resolveInternalProviderLabel(),
            className: 'bg-slate-900 text-white',
            ribbonClassName: 'bg-gradient-to-r from-slate-900 to-slate-800 text-white'
        },
        greenmotion: {
            label: 'Green Motion',
            className: 'bg-emerald-100 text-emerald-700',
            ribbonClassName: 'bg-gradient-to-r from-emerald-700 to-emerald-500 text-white'
        },
        usave: {
            label: 'U-Save',
            className: 'bg-emerald-100 text-emerald-700',
            ribbonClassName: 'bg-gradient-to-r from-emerald-700 to-emerald-500 text-white'
        },
        locauto_rent: {
            label: 'Locauto',
            className: 'bg-indigo-100 text-indigo-700',
            ribbonClassName: 'bg-gradient-to-r from-indigo-700 to-indigo-500 text-white'
        },
        adobe: {
            label: 'Adobe',
            className: 'bg-amber-100 text-amber-700',
            ribbonClassName: 'bg-gradient-to-r from-amber-700 to-amber-500 text-white'
        },
        okmobility: {
            label: 'OK Mobility',
            className: 'bg-cyan-100 text-cyan-700',
            ribbonClassName: 'bg-gradient-to-r from-cyan-700 to-cyan-500 text-white'
        },
        renteon: {
            label: props.vehicle?.provider_code || 'Renteon',
            className: 'bg-sky-100 text-sky-700',
            ribbonClassName: 'bg-gradient-to-r from-sky-700 to-sky-500 text-white'
        },
        favrica: {
            label: 'Favrica',
            className: 'bg-pink-100 text-pink-700',
            ribbonClassName: 'bg-gradient-to-r from-pink-700 to-pink-500 text-white'
        },
        xdrive: {
            label: 'XDrive',
            className: 'bg-purple-100 text-purple-700',
            ribbonClassName: 'bg-gradient-to-r from-violet-700 to-violet-500 text-white'
        },
        wheelsys: {
            label: 'Wheelsys',
            className: 'bg-slate-100 text-slate-700',
            ribbonClassName: 'bg-gradient-to-r from-slate-700 to-slate-500 text-white'
        }
    };

    return badgeMap[source] || {
        label: toTitleCase(source),
        className: 'bg-gray-100 text-gray-700',
        ribbonClassName: 'bg-gradient-to-r from-gray-800 to-gray-700 text-white'
    };
});

const isGreenMotion = computed(() => {
    const source = props.vehicle?.source;
    return source === 'greenmotion' || source === 'usave';
});

const isValidCoordinate = (coord) => {
    const num = parseFloat(coord);
    return !isNaN(num) && isFinite(num);
};

// Get vehicle image (handles internal vehicles which use images array)
const vehicleImage = computed(() => {
    // Internal vehicles: find primary image from images array
    if (isInternal.value && props.vehicle?.images) {
        const primaryImg = props.vehicle.images.find(img => img.image_type === 'primary');
        if (primaryImg) return primaryImg.image_url;
        // Fallback to first gallery image
        const galleryImg = props.vehicle.images.find(img => img.image_type === 'gallery');
        if (galleryImg) return galleryImg.image_url;
    }
    // Other providers: use direct image property
    return props.vehicle?.image || props.vehicle?.image_url || props.vehicle?.image_path || props.vehicle?.largeImage || null;
});

// Carousel for internal vehicles with multiple images
const heroImageIndex = ref(0);
const allHeroImages = computed(() => {
    if (!isInternal.value || !props.vehicle?.images) return [];
    const primary = props.vehicle.images.find(img => img.image_type === 'primary');
    const gallery = props.vehicle.images.filter(img => img.image_type === 'gallery') || [];
    const images = [];
    if (primary) images.push(primary.image_url);
    gallery.forEach(img => images.push(img.image_url));
    return images;
});
const hasMultipleImages = computed(() => allHeroImages.value.length > 1);
const currentHeroImage = computed(() => allHeroImages.value[heroImageIndex.value] || vehicleImage.value);
const heroNextImage = () => {
    if (!hasMultipleImages.value) return;
    heroImageIndex.value = (heroImageIndex.value + 1) % allHeroImages.value.length;
};
const heroPrevImage = () => {
    if (!hasMultipleImages.value) return;
    heroImageIndex.value = (heroImageIndex.value - 1 + allHeroImages.value.length) % allHeroImages.value.length;
};

const props = defineProps({
    vehicle: Object,
    initialPackage: String,
    initialProtectionCode: String, // For LocautoRent: selected protection code from car card
    optionalExtras: {
        type: Array,
        default: () => []
    },
    currencySymbol: {
        type: String,
        default: '€'
    },
    locationName: String,
    pickupLocation: String,
    dropoffLocation: String,
    dropoffLatitude: { type: [String, Number], default: null },
    dropoffLongitude: { type: [String, Number], default: null },
    locationInstructions: String,
    locationDetails: {
        type: Object,
        default: null
    },
    driverRequirements: {
        type: Object,
        default: null
    },
    terms: {
        type: Array,
        default: null
    },
    pickupDate: String,
    pickupTime: String,
    dropoffDate: String,
    dropoffTime: String,
    numberOfDays: {
        type: Number,
        default: 1
    },
    paymentPercentage: {
        type: Number,
        default: 0
    },
    // Price verification props
    searchSessionId: String,
    priceMap: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['back', 'proceed-to-checkout']);

// Currency conversion composable
const { convertPrice, getSelectedCurrencySymbol, fetchExchangeRates, exchangeRates, loading } = useCurrencyConversion();

// Sticky Bar Logic
const summarySection = ref(null);
const isSummaryVisible = ref(false);

const scrollToSummary = () => {
    summarySection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const vehicleLocationText = computed(() => {
    if (!props.vehicle) return '';
    if (props.vehicle.full_vehicle_address) return props.vehicle.full_vehicle_address;
    const parts = [props.vehicle.location, props.vehicle.city, props.vehicle.state, props.vehicle.country]
        .filter(Boolean)
        .map(part => `${part}`.trim())
        .filter(part => part.length > 0);
    const fallback = parts.join(', ');
    if (fallback) return fallback;
    return props.pickupLocation || props.locationName || '';
});

const locationDetailLines = computed(() => {
    const details = props.locationDetails || {};
    const parts = [
        details.address_1,
        details.address_2,
        details.address_3,
        details.address_city,
        details.address_county,
        details.address_postcode
    ];
    return parts
        .map(part => `${part || ''}`.trim())
        .filter(part => part.length > 0);
});

const locationContact = computed(() => {
    const details = props.locationDetails || {};
    return {
        phone: details.telephone || null,
        email: details.email || null,
        iata: details.iata || null,
        whatsapp: details.whatsapp || null
    };
});

const locationOpeningHours = computed(() => {
    const details = props.locationDetails || {};
    if (Array.isArray(details.opening_hours) && details.opening_hours.length) {
        return details.opening_hours;
    }
    if (Array.isArray(details.office_opening_hours) && details.office_opening_hours.length) {
        return details.office_opening_hours;
    }
    return [];
});

const locationOutOfHours = computed(() => {
    const details = props.locationDetails || {};
    return Array.isArray(details.out_of_hours_dropoff) ? details.out_of_hours_dropoff : [];
});

const locationDaytimeClosures = computed(() => {
    const details = props.locationDetails || {};
    return Array.isArray(details.daytime_closures_hours) ? details.daytime_closures_hours : [];
});

const hasLocationHours = computed(() => {
    const details = props.locationDetails || {};
    return locationOpeningHours.value.length
        || locationOutOfHours.value.length
        || locationDaytimeClosures.value.length
        || !!details.out_of_hours_charge;
});

const formatHourWindow = (window) => {
    if (!window) return '';
    const start = window.open || window.start || '';
    const end = window.close || window.end || '';
    const start2 = window.start2 || '';
    const end2 = window.end2 || '';
    const first = start && end ? `${start} - ${end}` : '';
    const second = start2 && end2 ? `${start2} - ${end2}` : '';
    return [first, second].filter(Boolean).join(' / ');
};

const driverRequirementItems = computed(() => {
    const requirements = props.driverRequirements || {};
    const labelMap = {
        driving_licence: 'Driving licence',
        driving_licence_valid: 'Valid driving licence',
        passport: 'Passport',
        dvla_check_code: 'DVLA check code',
        two_proofs_of_address: 'Two proofs of address',
        valid_cc: 'Valid credit card',
        boarding_pass: 'Boarding pass'
    };

    return Object.entries(requirements)
        .filter(([key, value]) => key !== 'mileage_type' && ['1', 'true', 'yes', 'y', true].includes(`${value}`.toLowerCase()))
        .map(([key]) => labelMap[key] || key.replace(/_/g, ' '))
        .sort();
});

const mileageTypeLabel = computed(() => {
    const mileageType = props.driverRequirements?.mileage_type;
    return mileageType ? `${mileageType}` : null;
});

const hasVehicleCoords = computed(() => {
    return isValidCoordinate(props.vehicle?.latitude) && isValidCoordinate(props.vehicle?.longitude);
});

const hasDropoffCoords = computed(() => {
    return isValidCoordinate(props.dropoffLatitude) && isValidCoordinate(props.dropoffLongitude);
});

const isDifferentDropoff = computed(() => {
    if (!hasDropoffCoords.value || !hasVehicleCoords.value) return false;
    // If user selected same pickup & dropoff location name, it's not a different dropoff
    if (props.dropoffLocation && props.pickupLocation && props.dropoffLocation === props.pickupLocation) return false;
    const pickupLat = parseFloat(props.vehicle.latitude);
    const pickupLng = parseFloat(props.vehicle.longitude);
    const dropLat = parseFloat(props.dropoffLatitude);
    const dropLng = parseFloat(props.dropoffLongitude);
    return Math.abs(pickupLat - dropLat) > 0.001 || Math.abs(pickupLng - dropLng) > 0.001;
});

// True when user selected a different dropoff name
const hasOneWayDropoff = computed(() => {
    if (!props.dropoffLocation || !props.pickupLocation) return false;
    return props.dropoffLocation !== props.pickupLocation;
});

const vehicleLocationTitle = computed(() => {
    if (isInternal.value) return 'Vehicle Location';
    if (isDifferentDropoff.value) return 'Pickup & Dropoff Locations';
    return 'Pickup Location';
});

const vehicleMapRef = ref(null);
let vehicleMap = null;

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

    // Zoom control top-right with smooth style
    L.control.zoom({ position: 'topright' }).addTo(vehicleMap);

    // Stadia Maps OSM Bright - colorful, detailed, Google Maps-like
    const stadiaKey = import.meta.env.VITE_STADIA_MAPS_API_KEY || '';
    const keyParam = stadiaKey ? `?api_key=${stadiaKey}` : '';
    L.tileLayer(`https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.png${keyParam}`, {
        attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://openstreetmap.org/copyright">OSM</a>',
        maxZoom: 20,
    }).addTo(vehicleMap);

    const pickupLatLng = [pickupLat, pickupLng];

    if (isDifferentDropoff.value) {
        const dropoffLat = parseFloat(props.dropoffLatitude);
        const dropoffLng = parseFloat(props.dropoffLongitude);
        const dropoffLatLng = [dropoffLat, dropoffLng];

        const pickupIcon = createMapIcon('#059669', 'Pickup', true);
        const dropoffIcon = createMapIcon('#dc2626', 'Dropoff');

        L.marker(pickupLatLng, { icon: pickupIcon })
            .bindPopup(`<div style="font-family:system-ui;text-align:center;padding:4px 0;"><p style="font-weight:600;margin:0 0 2px;">Pickup</p><p style="margin:0;color:#6b7280;font-size:12px;">${props.pickupLocation || ''}</p></div>`, { className: 'rental-popup' })
            .addTo(vehicleMap);

        L.marker(dropoffLatLng, { icon: dropoffIcon })
            .bindPopup(`<div style="font-family:system-ui;text-align:center;padding:4px 0;"><p style="font-weight:600;margin:0 0 2px;">Dropoff</p><p style="margin:0;color:#6b7280;font-size:12px;">${props.dropoffLocation || ''}</p></div>`, { className: 'rental-popup' })
            .addTo(vehicleMap);

        // Curved route line between pickup and dropoff
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

// Fetch exchange rates on mount
onMounted(() => {
    fetchExchangeRates();

    // Setup Intersection Observer for summary visibility
    const observer = new IntersectionObserver(
        ([entry]) => {
            isSummaryVisible.value = entry.isIntersecting;
        },
        { threshold: 0.1 }
    );

    if (summarySection.value) {
        observer.observe(summarySection.value);
    }

    nextTick(() => {
        initVehicleMap();
    });
});

watch(
    () => [props.vehicle?.id, props.vehicle?.latitude, props.vehicle?.longitude],
    () => {
        nextTick(() => {
            initVehicleMap();
        });
    }
);

const showMapModal = ref(false);
const mapModalRef = ref(null);
const showLightbox = ref(false);
const lightboxIndex = ref(0);
const lightboxImages = computed(() => hasMultipleImages.value ? allHeroImages.value : (vehicleImage.value ? [vehicleImage.value] : []));
const lightboxNext = () => { lightboxIndex.value = (lightboxIndex.value + 1) % lightboxImages.value.length; };
const lightboxPrev = () => { lightboxIndex.value = (lightboxIndex.value - 1 + lightboxImages.value.length) % lightboxImages.value.length; };
watch(showLightbox, (open) => { if (open) lightboxIndex.value = hasMultipleImages.value ? heroImageIndex.value : 0; });
let modalMap = null;
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
                const dropoffLat = parseFloat(props.dropoffLatitude);
                const dropoffLng = parseFloat(props.dropoffLongitude);
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

const currentPackage = ref(props.initialPackage || 'BAS');

// (Forcing PLI removed as per user request to show Basic first)

const selectedExtras = ref({});
const showDetailsModal = ref(false);
const showLocationHoursModal = ref(false);

// Deposit type selector for internal vehicles
const selectedDepositType = ref(null);
const availableDepositTypes = computed(() => {
    const method = props.vehicle?.payment_method;
    if (!method) return [];
    let methods = [];
    try {
        if (typeof method === 'string' && (method.startsWith('[') || method.startsWith('{'))) {
            const parsed = JSON.parse(method);
            methods = Array.isArray(parsed) ? parsed : [method];
        } else if (Array.isArray(method)) {
            methods = method;
        } else {
            methods = [method];
        }
    } catch {
        methods = [method];
    }
    return methods.filter(Boolean);
});
// Auto-select first deposit type if only one option
watch(availableDepositTypes, (types) => {
    if (types.length === 1 && !selectedDepositType.value) {
        selectedDepositType.value = types[0];
    }
}, { immediate: true });

const ratesReady = computed(() => !!exchangeRates.value && !loading.value);

// (Moved above)

// Watch for changes to initialPackage prop
watch(() => props.initialPackage, (newPackage) => {
    currentPackage.value = newPackage || 'BAS';
});

const packageOrder = ['BAS', 'PLU', 'PRE', 'PMP'];

// (Moved above)


// Get LocautoRent protection plans from extras (all 7 plans)
const locautoProtectionPlans = computed(() => {
    if (!isLocautoRent.value) return [];
    const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
    const extras = props.vehicle?.extras || [];
    return extras.filter(extra =>
        protectionCodes.includes(extra.code) && extra.amount > 0
    );
});

// Selected LocautoRent protection plans (array for multi-select)
// Initialize from prop if passed from car card
const selectedLocautoProtections = ref(
    props.initialProtectionCode ? [props.initialProtectionCode] : []
);

// Toggle a LocautoRent protection plan on/off
const toggleLocautoProtection = (code) => {
    const idx = selectedLocautoProtections.value.indexOf(code);
    if (idx >= 0) {
        selectedLocautoProtections.value.splice(idx, 1);
    } else {
        selectedLocautoProtections.value.push(code);
    }
};

// Watch for changes to initialProtectionCode prop
watch(() => props.initialProtectionCode, (newCode) => {
    selectedLocautoProtections.value = newCode ? [newCode] : [];
});

// LocautoRent: Smart Cover plan (code 147)
const locautoSmartCoverPlan = computed(() => {
    return locautoProtectionPlans.value.find(p => p.code === '147') || null;
});

// LocautoRent: Don't Worry plan (code 136)
const locautoDontWorryPlan = computed(() => {
    return locautoProtectionPlans.value.find(p => p.code === '136') || null;
});

// (Moved above)


// Adobe Cars Data
const adobeBaseRate = computed(() => {
    if (!isAdobeCars.value || !props.vehicle?.tdr) return 0;
    return parseFloat(props.vehicle.tdr);
});

const adobeProtectionPlans = computed(() => {
    if (!isAdobeCars.value) return [];
    const protections = [];
    if (props.vehicle.pli !== undefined) protections.push({ code: 'PLI', name: 'Liability Protection', amount: parseFloat(props.vehicle.pli), required: true });
    // Adobe API returns LDW/SPP as Daily Rates, but PLI as Total. Multiply optional protections by days.
    if (props.vehicle.ldw !== undefined) protections.push({ code: 'LDW', name: 'Car Protection', amount: parseFloat(props.vehicle.ldw) * props.numberOfDays, required: false });
    if (props.vehicle.spp !== undefined) protections.push({ code: 'SPP', name: 'Extended Protection', amount: parseFloat(props.vehicle.spp) * props.numberOfDays, required: false });
    return protections;
});

// Selected Adobe Cars protection plan (managed via currentPackage for simplicity in template or separate ref?)
// Ideally we re-use currentPackage to store the "code" (BAS, PLI, LDW, etc.)
// But we need to handle the total calculation difference.

const adobePackages = computed(() => {
    if (!isAdobeCars.value) return [];

    // Only Basic package — LDW/SPP are now in unifiedProtectionPlans
    return [{
        type: 'BAS',
        name: 'Basic Rental',
        subtitle: 'Base Rental',
        total: adobeBaseRate.value,
        deposit: 0,
        benefits: [],
        isBestValue: false,
        isAddOn: false
    }];
});

const adobeMandatoryProtection = computed(() => {
    if (!isAdobeCars.value) return 0;
    const pli = adobeProtectionPlans.value.find(p => p.code === 'PLI');
    return pli ? pli.amount : 0;
});

const adobeOptionalExtras = computed(() => {
    if (!isAdobeCars.value) return [];

    // 1. Protection Plans (Hidden Extras)
    // We map them to extras so they participate in pricing logic (extrasTotal)
    // but we use 'isHidden' to filter them out of the bottom list.
    const options = adobeProtectionPlans.value.filter(p => p.code !== 'PLI').map(plan => ({
        id: `adobe_protection_${plan.code}`,
        code: plan.code,
        name: plan.name,
        description: 'Optional Protection',
        price: parseFloat(plan.amount),
        daily_rate: parseFloat(plan.amount) / props.numberOfDays,
        amount: plan.amount,
        isHidden: true,
        isProtection: true
    }));

    // 2. Standard Extras
    const extras = props.vehicle?.extras || [];
    extras.forEach(extra => {
        options.push({
            id: `adobe_extra_${extra.code}`,
            code: extra.code,
            name: extra.name || extra.displayName || extra.description || extra.code,
            description: extra.description || extra.displayDescription || extra.name,
            // Price in API is Total.
            daily_rate: parseFloat(extra.price || extra.amount || 0) / props.numberOfDays,
            price: parseFloat(extra.price || extra.amount || 0),
            amount: extra.price || extra.amount
        });
    });

    return options;
});

// Internal Vehicle Data
const internalPackages = computed(() => {
    if (!isInternal.value) return [];

    const packages = [];
    // Laravel returns relationship as camelCase 'vendorPlans' not 'vendor_plans'
    const vendorPlans = props.vehicle?.vendorPlans || props.vehicle?.vendor_plans || [];

    // Always add Basic package (base price_per_day)
    packages.push({
        type: 'BAS',
        name: 'Basic Rental',
        subtitle: 'Standard Package',
        pricePerDay: parseFloat(props.vehicle?.price_per_day) || 0,
        total: (parseFloat(props.vehicle?.price_per_day) || 0) * props.numberOfDays,
        deposit: parseFloat(props.vehicle?.security_deposit) || 0,
        benefits: [],
        isBestValue: vendorPlans.length === 0,
        isAddOn: false
    });

    // Add vendor plans if any exist
    vendorPlans.forEach((plan, index) => {
        const features = plan.features ? (typeof plan.features === 'string' ? JSON.parse(plan.features) : plan.features) : [];
        packages.push({
            type: plan.plan_type || `PLAN_${index}`,
            name: plan.plan_type || 'Custom Plan',
            subtitle: plan.plan_description || 'Vendor Package',
            pricePerDay: parseFloat(plan.price) || 0,
            total: (parseFloat(plan.price) || 0) * props.numberOfDays,
            deposit: parseFloat(props.vehicle?.security_deposit) || 0,
            benefits: features,
            isBestValue: index === 0,
            isAddOn: false,
            vendorPlanId: plan.id
        });
    });

    return packages;
});

const internalOptionalExtras = computed(() => {
    if (!isInternal.value) return [];

    const addons = props.vehicle?.addons || [];
    return addons.map(addon => ({
        id: `internal_addon_${addon.id}`,
        code: addon.addon_id?.toString() || addon.id?.toString(),
        name: addon.extra_name || 'Extra',
        description: addon.description || addon.extra_name || 'Optional Add-on',
        price: parseFloat(addon.price) * props.numberOfDays || 0,
        daily_rate: parseFloat(addon.price) || 0,
        amount: addon.price,
        maxQuantity: addon.quantity || 1
    }));
});

// LocautoRent: Optional extras (non-protection extras like GPS, child seat, etc.)
const locautoOptionalExtras = computed(() => {
    if (!isLocautoRent.value) return [];
    const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
    const extras = props.vehicle?.extras || [];
    return extras.filter(extra =>
        !protectionCodes.includes(extra.code)
    ).map((extra, index) => ({
        id: `locauto_extra_${extra.code}`,
        code: extra.code,
        name: extra.description,
        description: extra.description,
        price: parseFloat(extra.amount) * props.numberOfDays,
        daily_rate: parseFloat(extra.amount),
        amount: extra.amount
    }));
});

const renteonPackages = computed(() => {
    if (!isRenteon.value) return [];
    return [{
        type: 'BAS',
        name: 'Basic Rental',
        subtitle: 'Standard Package',
        pricePerDay: parseFloat(props.vehicle?.price_per_day) || 0,
        total: (parseFloat(props.vehicle?.price_per_day) || 0) * props.numberOfDays,
        deposit: parseFloat(props.vehicle?.security_deposit) || 0,
        benefits: [],
        isBestValue: true,
        isAddOn: false
    }];
});

const renteonIncludedServices = computed(() => {
    if (!isRenteon.value) return [];
    const extras = props.vehicle?.extras || [];
    return extras.filter(extra => extra.included || extra.free_of_charge || extra.included_in_price || extra.included_in_price_limited);
});

const isRenteonProtectionExtra = (extra) => {
    if (!extra) return false;
    const code = `${extra.code || ''}`.toUpperCase();
    const group = `${extra.service_group || extra.service_type || ''}`.toLowerCase();
    const name = `${extra.name || extra.description || ''}`.toLowerCase();
    if (code.startsWith('INS-')) return true;
    if (group.includes('insurance')) return true;
    return name.includes('insurance') || name.includes('cdw') || name.includes('scdw') || name.includes('pai');
};

const getRenteonExtraKey = (extra) => {
    if (!extra) return '';
    const code = `${extra.code || ''}`.trim();
    if (code) return code.toUpperCase();
    const name = `${extra.name || extra.description || ''}`.trim();
    if (name) return name.toUpperCase();
    return `${extra.service_id || extra.id || ''}`.trim();
};

const renteonProtectionPlans = computed(() => {
    if (!isRenteon.value) return [];
    const extras = props.vehicle?.extras || [];
    const byId = new Map();
    extras.filter(isRenteonProtectionExtra).forEach(extra => {
        const key = getRenteonExtraKey(extra);
        const id = `renteon_extra_${key || (extra.service_id || extra.id || extra.code)}`;
        if (!id || byId.has(id)) return;
        byId.set(id, {
            id,
            code: extra.code || extra.id,
            name: extra.name || extra.description || 'Protection',
            description: extra.description || extra.name || 'Protection Plan',
            price: parseFloat(extra.price || extra.amount || 0) * props.numberOfDays,
            daily_rate: parseFloat(extra.daily_rate || extra.price || extra.amount || 0),
            amount: extra.price || extra.amount,
            maxQuantity: extra.max_quantity || 1,
            numberAllowed: extra.max_quantity || 1,
            service_id: extra.service_id || extra.id,
            service_group: extra.service_group,
            service_type: extra.service_type,
            required: extra.required || false,
        });
    });
    return Array.from(byId.values());
});

const renteonDriverPolicy = computed(() => {
    if (!isRenteon.value) return null;
    const benefits = props.vehicle?.benefits || {};
    if (!benefits.young_driver_age_from && !benefits.senior_driver_age_from && !benefits.minimum_driver_age) {
        return null;
    }
    return {
        youngFrom: benefits.young_driver_age_from,
        youngTo: benefits.young_driver_age_to,
        seniorFrom: benefits.senior_driver_age_from,
        seniorTo: benefits.senior_driver_age_to,
        minAge: benefits.minimum_driver_age,
        maxAge: benefits.maximum_driver_age,
    };

});

const renteonOptionalExtras = computed(() => {
    if (!isRenteon.value) return [];
    // If Renteon provides extras in standard format, map them here
    // Assuming standard 'extras' array
    const extras = props.vehicle?.extras || [];
    const byId = new Map();
    extras
        .filter(extra => !extra.included && !extra.included_in_price && !extra.included_in_price_limited && !extra.is_one_time)
        .filter(extra => !isRenteonProtectionExtra(extra))
        .forEach(extra => {
            const key = getRenteonExtraKey(extra);
            const id = `renteon_extra_${key || (extra.service_id || extra.id || extra.code)}`;
            if (!id || byId.has(id)) return;
            byId.set(id, {
                id,
                code: extra.code || extra.id,
                name: extra.name || extra.description || 'Extra',
                description: extra.description || extra.name || 'Optional Extra',
                price: parseFloat(extra.price || extra.amount || 0) * props.numberOfDays,
                daily_rate: parseFloat(extra.daily_rate || extra.price || extra.amount || 0),
                amount: extra.price || extra.amount,
                maxQuantity: extra.max_quantity || 1,
                numberAllowed: extra.max_quantity || 1,
                service_id: extra.service_id || extra.id,
                service_group: extra.service_group,
                service_type: extra.service_type
            });
        });
    return Array.from(byId.values());

});

const renteonAllExtras = computed(() => {
    if (!isRenteon.value) return [];
    const byId = new Map();
    [...renteonProtectionPlans.value, ...renteonOptionalExtras.value].forEach(extra => {
        if (extra && !byId.has(extra.id)) {
            byId.set(extra.id, extra);
        }
    });
    return Array.from(byId.values());
});

const renteonPickupOffice = computed(() => props.vehicle?.pickup_office || null);
const renteonDropoffOffice = computed(() => props.vehicle?.dropoff_office || null);
const renteonSameOffice = computed(() => {
    const pickupId = renteonPickupOffice.value?.office_id || renteonPickupOffice.value?.office_code;
    const dropoffId = renteonDropoffOffice.value?.office_id || renteonDropoffOffice.value?.office_code;
    if (pickupId && dropoffId) return pickupId === dropoffId;
    return !renteonDropoffOffice.value;
});

const formatOfficeLines = (office) => {
    if (!office) return [];
    const lines = [office.address, office.town, office.postal_code]
        .map(line => `${line || ''}`.trim())
        .filter(Boolean);
    return lines;
};

const renteonPickupLines = computed(() => formatOfficeLines(renteonPickupOffice.value));
const renteonDropoffLines = computed(() => formatOfficeLines(renteonDropoffOffice.value));

const renteonPickupInstructions = computed(() => renteonPickupOffice.value?.pickup_instructions || null);
const renteonDropoffInstructions = computed(() => renteonDropoffOffice.value?.dropoff_instructions || null);

const renteonHasOfficeDetails = computed(() => {
    return Boolean(
        renteonPickupLines.value.length
        || renteonDropoffLines.value.length
        || renteonPickupOffice.value?.phone
        || renteonPickupOffice.value?.email
        || renteonDropoffOffice.value?.phone
        || renteonDropoffOffice.value?.email
    );
});

const renteonTaxBreakdown = computed(() => {
    if (!isRenteon.value) return null;
    const gross = parseFloat(props.vehicle?.provider_gross_amount ?? NaN);
    const net = parseFloat(props.vehicle?.provider_net_amount ?? NaN);
    const vat = parseFloat(props.vehicle?.provider_vat_amount ?? NaN);
    return {
        gross: Number.isFinite(gross) ? gross : null,
        net: Number.isFinite(net) ? net : null,
        vat: Number.isFinite(vat) ? vat : null,
    };
});

const hasRenteonTaxBreakdown = computed(() => {
    const breakdown = renteonTaxBreakdown.value;
    return Boolean(breakdown?.gross || breakdown?.net || breakdown?.vat);
});

const okMobilityBaseTotal = computed(() => {
    if (!isOkMobility.value) return 0;
    const total = parseFloat(props.vehicle?.total_price || 0);
    if (total > 0) return total;
    const daily = parseFloat(props.vehicle?.price_per_day || 0);
    return daily > 0 ? daily * props.numberOfDays : 0;
});

const OK_MOBILITY_COVER_CODES = ['OPC', 'OPCO'];

const getOkMobilityExtraTotal = (extra) => {
    if (!extra) return 0;
    if (extra.is_one_time) return parseFloat(extra.price || 0);
    const dailyRate = extra.daily_rate !== undefined
        ? parseFloat(extra.daily_rate)
        : (parseFloat(extra.price || 0) / props.numberOfDays);
    return dailyRate * props.numberOfDays;
};

const okMobilityNormalizedExtras = computed(() => {
    if (!isOkMobility.value) return [];
    const requiredCodes = new Set(okMobilityExtrasRequired.value);
    const includedCodes = new Set(okMobilityExtrasIncluded.value);
    const availableCodes = new Set(okMobilityExtrasAvailable.value);
    const hasAvailableFilter = availableCodes.size > 0;
    const extras = props.vehicle?.extras || [];
    return extras.map((extra, index) => {
        const id = extra.id || extra.extraID || extra.extraId || extra.extra_id || extra.code || extra.extra || index;
        const codeRaw = extra.code || extra.extraID || extra.extraId || extra.extra_id || extra.extra || id;
        const code = normalizeExtraCode(codeRaw);
        const name = extra.name || extra.extra || extra.description || extra.displayName || extra.code || code || 'Extra';
        const description = extra.description || extra.displayDescription || '';
        const priceValue = parseFloat(
            extra.priceWithTax ?? extra.valueWithTax ?? extra.value ?? extra.price ?? extra.amount ?? 0
        );
        const pricePerContract = extra.pricePerContract === true || extra.pricePerContract === 'true';
        const dailyRate = pricePerContract && props.numberOfDays
            ? (priceValue / props.numberOfDays)
            : priceValue;

        const isRequired = extra.required || extra.extra_Required === 'true' || (code && requiredCodes.has(code));
        const isIncluded = extra.included || extra.extra_Included === 'true' || (code && includedCodes.has(code));

        return {
            id: `okmobility_extra_${id}`,
            code: code || codeRaw || id,
            name,
            description,
            price: priceValue,
            daily_rate: dailyRate,
            amount: priceValue,
            included: isIncluded,
            required: isRequired,
            is_one_time: pricePerContract
        };
    }).filter(extra => {
        if (!extra) return false;
        if (extra.required || extra.included) return true;
        if (hasAvailableFilter) {
            return availableCodes.has(normalizeExtraCode(extra.code));
        }
        return extra.price > 0;
    });
});

const okMobilityCoverExtras = computed(() => {
    const coverCodes = new Set(OK_MOBILITY_COVER_CODES);
    return okMobilityNormalizedExtras.value.filter(extra => coverCodes.has(normalizeExtraCode(extra.code)));
});

// OKMobility cover features per tier (from OKMobility website)
const okMobilityBasicFeatures = [
    { label: 'Civil liability insurance', included: true },
    { label: 'Cover against damage to bodywork, windows and wheels', included: false },
    { label: 'Roadside assistance', included: false },
    { label: 'Telemedicine services', included: false },
];
const okMobilityPremiumFeatures = [
    { label: 'Civil liability insurance', included: true },
    { label: 'Cover against damage to bodywork, windows and wheels', included: true },
    { label: 'Roadside assistance', included: false },
    { label: 'Telemedicine services', included: false },
];
const okMobilitySuperPremiumFeatures = [
    { label: 'Civil liability insurance', included: true },
    { label: 'Cover against damage to bodywork, windows and wheels', included: true },
    { label: 'Roadside assistance', included: true },
    { label: 'Telemedicine services', included: true },
];

const okMobilityPackages = computed(() => {
    if (!isOkMobility.value) return [];
    const packages = [
        {
            type: 'BAS',
            name: 'Basic Cover',
            subtitle: 'Basic Cover',
            total: okMobilityBaseTotal.value,
            deposit: 0,
            benefits: [],
            coverFeatures: okMobilityBasicFeatures,
            isBestValue: okMobilityCoverExtras.value.length === 0,
            isAddOn: false
        }
    ];

    okMobilityCoverExtras.value.forEach((extra) => {
        const extraTotal = getOkMobilityExtraTotal(extra);
        const code = normalizeExtraCode(extra.code);
        const isSuperPremium = code === 'OPCO';
        packages.push({
            type: code || extra.code,
            name: extra.name || (isSuperPremium ? 'Super Premium Cover' : 'Premium Cover'),
            subtitle: 'Excess Waiver',
            total: okMobilityBaseTotal.value + extraTotal,
            deposit: 0,
            benefits: [],
            coverFeatures: isSuperPremium ? okMobilitySuperPremiumFeatures : okMobilityPremiumFeatures,
            isBestValue: code === 'OPC',
            isAddOn: false,
            extraId: extra.id
        });
    });

    return packages;
});

const sicilyByCarBaseTotal = computed(() => {
    if (!isSicilyByCar.value) return 0;
    const total = parseFloat(props.vehicle?.total_price || 0);
    if (total > 0) return total;
    const daily = parseFloat(props.vehicle?.price_per_day || 0);
    return daily > 0 ? daily * props.numberOfDays : 0;
});

const sicilyByCarPackages = computed(() => {
    if (!isSicilyByCar.value) return [];
    const benefits = [];
    if (props.vehicle?.rate_name) {
        benefits.push(props.vehicle.rate_name);
    }
    if (props.vehicle?.payment_type) {
        benefits.push(props.vehicle.payment_type === 'Prepaid' ? 'Prepaid' : 'Pay on arrival');
    }
    if (props.vehicle?.mileage) {
        benefits.push(`Mileage: ${props.vehicle.mileage}`);
    }
    const deposit = parseFloat(props.vehicle?.deposit ?? 0);

    return [
        {
            type: 'BAS',
            name: 'Basic Rental',
            subtitle: props.vehicle?.payment_type === 'Prepaid' ? 'Prepaid' : 'Pay on arrival',
            total: sicilyByCarBaseTotal.value,
            deposit: Number.isFinite(deposit) ? deposit : 0,
            benefits: benefits.filter(Boolean),
            isBestValue: true,
            isAddOn: false,
        }
    ];
});

const recordGoBaseTotal = computed(() => {
    if (!isRecordGo.value) return 0;
    const total = parseFloat(props.vehicle?.total_price || 0);
    if (total > 0) return total;
    const daily = parseFloat(props.vehicle?.price_per_day || 0);
    return daily > 0 ? daily * props.numberOfDays : 0;
});

const recordGoPackages = computed(() => {
    if (!isRecordGo.value) return [];
    const products = props.vehicle?.recordgo_products || [];
    if (!Array.isArray(products) || products.length === 0) {
        return [
            {
                type: 'BAS',
                name: 'Basic Rental',
                subtitle: 'Record Go',
                total: recordGoBaseTotal.value,
                benefits: [],
                isBestValue: true,
                isAddOn: false,
            }
        ];
    }

    return products.map((product, index) => {
        const benefits = [];
        if (product?.description) benefits.push(stripHtml(product.description));
        const refuel = product?.refuel_policy?.refuelPolicyTransName || product?.refuel_policy?.refuelPolicyName;
        if (refuel) benefits.push(refuel);
        const kmPolicy = product?.km_policy?.kmPolicyTransName || product?.km_policy?.kmPolicyName;
        if (kmPolicy) benefits.push(kmPolicy);

        return {
            type: product.type || `RG_${index}`,
            name: product.name || 'Record Go',
            subtitle: product.subtitle ? stripHtml(product.subtitle) : 'Record Go',
            total: product.total || 0,
            deposit: product.deposit || 0,
            excess: product.excess || 0,
            excessLow: product.excess_low || 0,
            benefits: benefits.filter(Boolean),
            isBestValue: index === 0,
            isAddOn: false,
            recordgo: product,
        };
    });
});

const recordGoSelectedProduct = computed(() => {
    if (!isRecordGo.value) return null;
    return currentProduct.value?.recordgo || null;
});

const recordGoIncludedComplements = computed(() => {
    return recordGoSelectedProduct.value?.complements_included || [];
});

const recordGoAutomaticComplements = computed(() => {
    return recordGoSelectedProduct.value?.complements_automatic
        || recordGoSelectedProduct.value?.complements_autom
        || [];
});

const recordGoAssociatedComplements = computed(() => {
    return recordGoSelectedProduct.value?.complements_associated || [];
});

const recordGoOptionalExtras = computed(() => {
    if (!isRecordGo.value) return [];
    return recordGoAssociatedComplements.value.map((extra, index) => {
        const id = `recordgo_extra_${extra.complementId ?? index}`;
        return {
            id,
            code: extra.complementId,
            name: extra.complementName ? stripHtml(extra.complementName) : 'Extra',
            description: extra.complementDescription ? stripHtml(extra.complementDescription)
                : (extra.complementSubtitle ? stripHtml(extra.complementSubtitle) : null),
            required: false,
            numberAllowed: extra.maxUnits || extra.complementUnits || null,
            daily_rate: extra.priceTaxIncDayDiscount ?? extra.priceTaxIncDay ?? null,
            total_for_booking: extra.priceTaxIncComplementDiscount ?? extra.priceTaxIncComplement ?? null,
            price: extra.priceTaxIncComplementDiscount ?? extra.priceTaxIncComplement ?? null,
            type: 'optional',
            recordgo_payload: extra,
        };
    });
});

const okMobilityOptionalExtras = computed(() => {
    if (!isOkMobility.value) return [];
    const coverCodes = new Set(OK_MOBILITY_COVER_CODES);
    return okMobilityNormalizedExtras.value.filter(extra => !coverCodes.has(normalizeExtraCode(extra.code)));
});

const normalizeFavricaExtra = (extra) => {
    if (!extra) return null;
    const price = parseFloat(extra.total_for_booking ?? extra.price ?? extra.amount ?? 0);
    const dailyRate = parseFloat(extra.daily_rate ?? 0);
    const id = extra.id || `favrica_extra_${extra.code || extra.service_id || ''}`;
    return {
        id,
        code: extra.code || extra.service_id,
        name: extra.name || extra.description || 'Extra',
        description: extra.description || extra.name || 'Optional Extra',
        price,
        daily_rate: dailyRate,
        amount: extra.amount ?? price,
        total_for_booking: extra.total_for_booking ?? price,
        currency: extra.currency,
        numberAllowed: extra.numberAllowed || extra.maxQuantity || 1,
        maxQuantity: extra.numberAllowed || extra.maxQuantity || 1,
        service_id: extra.service_id || extra.code,
        type: extra.type || null
    };
};

const favricaServicePool = computed(() => {
    if (!isFavrica.value) return [];
    const raw = [
        ...(props.vehicle?.insurance_options || []),
        ...(props.vehicle?.extras || [])
    ];
    const byId = new Map();
    raw.forEach((extra) => {
        if (!extra) return;
        const key = extra.id || extra.service_id || extra.code || extra.service_name;
        if (key && !byId.has(key)) {
            byId.set(key, extra);
        }
    });
    return Array.from(byId.values());
});

const xdriveServicePool = computed(() => {
    if (!isXDrive.value) return [];
    const raw = [
        ...(props.vehicle?.insurance_options || []),
        ...(props.vehicle?.extras || [])
    ];
    const byId = new Map();
    raw.forEach((extra) => {
        if (!extra) return;
        const key = extra.id || extra.service_id || extra.code || extra.service_name;
        if (key && !byId.has(key)) {
            byId.set(key, extra);
        }
    });
    return Array.from(byId.values());
});

const isFavricaInsuranceService = (extra) => {
    if (!extra) return false;
    if (extra.type === 'insurance') return true;
    const code = `${extra.code || extra.service_id || extra.service_name || ''}`.trim().toUpperCase();
    if (['CDW', 'SCDW', 'LCF', 'PAI'].includes(code)) return true;
    const text = `${extra.name || ''} ${extra.description || ''}`.toLowerCase();
    return ['insurance', 'damage', 'waiver', 'glass', 'tire', 'tyre', 'headlight', 'fuse'].some(keyword => text.includes(keyword));
};

const favricaInsuranceOptions = computed(() => {
    if (!isFavrica.value) return [];
    return favricaServicePool.value
        .filter(isFavricaInsuranceService)
        .map(normalizeFavricaExtra)
        .filter(Boolean)
        .filter(extra => extra.price > 0);
});

const xdriveInsuranceOptions = computed(() => {
    if (!isXDrive.value) return [];
    return xdriveServicePool.value
        .filter(isFavricaInsuranceService)
        .map(normalizeFavricaExtra)
        .filter(Boolean)
        .filter(extra => extra.price > 0);
});

const favricaOptionalExtras = computed(() => {
    if (!isFavrica.value) return [];
    return favricaServicePool.value
        .filter(extra => !isFavricaInsuranceService(extra))
        .map(normalizeFavricaExtra)
        .filter(Boolean)
        .filter(extra => extra.price > 0);
});

const xdriveOptionalExtras = computed(() => {
    if (!isXDrive.value) return [];
    return xdriveServicePool.value
        .filter(extra => !isFavricaInsuranceService(extra))
        .map(normalizeFavricaExtra)
        .filter(Boolean)
        .filter(extra => extra.price > 0);
});

const favricaAllExtras = computed(() => {
    if (!isFavrica.value) return [];
    return [...favricaInsuranceOptions.value, ...favricaOptionalExtras.value];
});

const xdriveAllExtras = computed(() => {
    if (!isXDrive.value) return [];
    return [...xdriveInsuranceOptions.value, ...xdriveOptionalExtras.value];
});

const providerInsuranceOptions = computed(() => {
    if (isFavrica.value) return favricaInsuranceOptions.value;
    if (isXDrive.value) return xdriveInsuranceOptions.value;
    return [];
});

const providerOptionalExtras = computed(() => {
    if (isFavrica.value) return favricaOptionalExtras.value;
    if (isXDrive.value) return xdriveOptionalExtras.value;
    return [];
});

const providerAllExtras = computed(() => {
    if (isFavrica.value) return favricaAllExtras.value;
    if (isXDrive.value) return xdriveAllExtras.value;
    return [];
});

const surpriceOptionalExtras = computed(() => {
    if (!isSurprice.value) return [];
    const extras = Array.isArray(props.vehicle?.extras) ? props.vehicle.extras : [];
    return extras.map((extra, index) => {
        const code = extra.code || extra.id || `EXTRA_${index}`;
        const totalPrice = parseFloat(extra.price || 0);
        const dailyRate = extra.per_day ? parseFloat(extra.price_per_day || 0) : (props.numberOfDays ? totalPrice / props.numberOfDays : totalPrice);
        return {
            id: `surprice_extra_${code}_${index}`,
            code,
            name: extra.name || code,
            description: extra.name || code,
            price: totalPrice,
            daily_rate: dailyRate,
            currency: extra.currency || 'EUR',
            required: false,
            allow_quantity: extra.allow_quantity || false,
            purpose: extra.purpose ?? null,
        };
    });
});

const greenMotionExtras = computed(() => {
    if (!isGreenMotion.value) return [];
    const options = [];
    const seen = new Set();

    const normalizeRequired = (value) => {
        if (value === true) return true;
        if (value === false || value === null || value === undefined) return false;
        return `${value}`.toLowerCase() === 'required';
    };

    const pushExtra = (extra, fallbackPrefix = 'gm_option_', typeOverride = null) => {
        if (!extra) return;
        const optionId = extra.option_id || extra.optionID || extra.id;
        if (!optionId) return;
        const key = String(optionId);
        if (seen.has(key)) return;
        seen.add(key);

        const totalForBooking = extra.total_for_booking ?? extra.Total_for_this_booking ?? extra.total ?? null;
        const numberAllowed = extra.numberAllowed ? parseInt(extra.numberAllowed) : null;
        const required = normalizeRequired(extra.required);

        options.push({
            id: extra.id || `${fallbackPrefix}${optionId}`,
            option_id: extra.option_id || extra.optionID || optionId,
            name: extra.name || extra.Name || extra.Description || 'Extra',
            description: extra.description || extra.Description || '',
            required,
            numberAllowed,
            prepay_available: (extra.prepay_available || extra.Prepay_available || '').toString().toLowerCase(),
            daily_rate: extra.daily_rate || extra.Daily_rate || 0,
            total_for_booking: totalForBooking,
            total_for_booking_currency: extra.total_for_booking_currency || extra.Total_for_this_booking_currency || extra.currency || null,
            code: extra.code || extra.Code || null,
            type: typeOverride || extra.type || 'option',
        });
    };

    const collect = (items, fallbackPrefix, typeOverride = null) => {
        (items || []).forEach((extra) => pushExtra(extra, fallbackPrefix, typeOverride));
    };

    const collectOptionalExtras = (items) => {
        (items || []).forEach((extra) => {
            if (Array.isArray(extra.options) && extra.options.length > 0) {
                extra.options.forEach((option) => {
                    pushExtra({
                        ...option,
                        name: option.name || option.Name || extra.Name,
                        description: option.description || option.Description || extra.Description,
                        required: option.required ?? extra.required,
                        numberAllowed: option.numberAllowed ?? extra.numberAllowed,
                        code: option.code || extra.code,
                        type: option.type || extra.type || 'optional'
                    }, 'gm_optional_', 'optional');
                });
                return;
            }
            pushExtra(extra, 'gm_optional_', 'optional');
        });
    };

    collect(props.vehicle?.options || [], 'gm_option_', 'option');
    collect(props.vehicle?.insurance_options || [], 'gm_insurance_', 'insurance');
    collectOptionalExtras(props.optionalExtras || []);

    return options;
});

watchEffect(() => {
    if (!isGreenMotion.value) return;
    greenMotionExtras.value.forEach((extra) => {
        if (extra.required) {
            setExtraQuantity(extra, Math.max(selectedExtras.value[extra.id] || 0, 1));
        }
    });
});

watchEffect(() => {
    if (!isOkMobility.value) return;
    okMobilityNormalizedExtras.value.forEach((extra) => {
        if (extra.required) {
            setExtraQuantity(extra, Math.max(selectedExtras.value[extra.id] || 0, 1));
        }
    });
});

const availablePackages = computed(() => {
    if (isAdobeCars.value) {
        return adobePackages.value;
    }
    if (isInternal.value) {
        return internalPackages.value;
    }
    if (isRenteon.value) {
        return renteonPackages.value;
    }
    if (isOkMobility.value) {
        return okMobilityPackages.value;
    }
    if (isSicilyByCar.value) {
        return sicilyByCarPackages.value;
    }
    if (isRecordGo.value) {
        return recordGoPackages.value;
    }
    if (!props.vehicle || !props.vehicle.products) return [];
    return packageOrder
        .map(type => props.vehicle.products.find(p => p.type === type))
        .filter(Boolean);
});

const selectedPackageType = computed(() => {
    const packages = availablePackages.value;
    if (!packages.length) return 'BAS';
    const current = currentPackage.value;
    if (packages.some(pkg => pkg.type === current)) {
        return current;
    }
    return packages[0].type;
});

watch(availablePackages, (packages) => {
    if (!packages.length) return;
    const current = currentPackage.value;
    if (!packages.some(pkg => pkg.type === current)) {
        currentPackage.value = packages[0].type;
    }
}, { immediate: true });

const currentProduct = computed(() => {
    if (isAdobeCars.value) {
        return adobePackages.value.find(p => p.type === selectedPackageType.value);
    }
    return availablePackages.value.find(p => p.type === selectedPackageType.value);
});

const locautoBaseTotal = computed(() => {
    const total = parseFloat(props.vehicle?.total_price || 0);
    if (total > 0) return total;
    const daily = parseFloat(props.vehicle?.price_per_day || 0);
    return daily > 0 ? daily * props.numberOfDays : 0;
});

const locautoBaseDaily = computed(() => {
    const total = locautoBaseTotal.value;
    if (!total || !props.numberOfDays) return 0;
    return total / props.numberOfDays;
});

const resolveVehicleCurrency = () => {
    const rawCurrency = props.vehicle?.currency
        || props.vehicle?.vendor_profile?.currency
        || props.vehicle?.vendorProfile?.currency
        || props.vehicle?.benefits?.deposit_currency
        || 'EUR';
    return normalizeCurrencyCode(rawCurrency);
};

const normalizeCurrencyCode = (currency) => {
    if (!currency) return 'EUR';
    const currencyMap = {
        '€': 'EUR',
        '$': 'USD',
        '£': 'GBP',
        '₹': 'INR',
        '₽': 'RUB',
        'A$': 'AUD',
        'C$': 'CAD',
        'د.إ': 'AED',
        '¥': 'JPY',
        'EURO': 'EUR',
        'TL': 'TRY'
    };
    const trimmed = `${currency}`.trim();
    return (currencyMap[trimmed] || trimmed).toUpperCase();
};

function stripHtml(value) {
    if (!value || typeof value !== 'string') return value || '';
    return value.replace(/<[^>]*>/g, '').trim();
}

const formatPrice = (val) => {
    const currencyCode = resolveVehicleCurrency();
    const converted = convertPrice(parseFloat(val), currencyCode);
    return `${getSelectedCurrencySymbol()}${converted.toFixed(2)}`;
};

// Provider vehicles: show customer price (gross) while keeping net values for provider payloads.
const formatRentalPrice = (val) => {
    const numeric = parseFloat(val ?? 0);
    return formatPrice(numeric * providerGrossMultiplier.value);
};

const getExtraTotal = (extra) => {
    if (!extra) return 0;
    if (extra.total_for_booking !== undefined && extra.total_for_booking !== null) {
        return parseFloat(extra.total_for_booking) || 0;
    }
    const dailyRate = extra.daily_rate !== undefined
        ? parseFloat(extra.daily_rate)
        : (parseFloat(extra.price || 0) / props.numberOfDays);
    return dailyRate * props.numberOfDays;
};

const getExtraPerDay = (extra) => {
    if (!extra) return 0;
    if (extra.daily_rate !== undefined && extra.daily_rate !== null) {
        return parseFloat(extra.daily_rate) || 0;
    }
    const total = getExtraTotal(extra);
    return props.numberOfDays ? total / props.numberOfDays : total;
};

const getBenefits = (product) => {
    if (!product) return [];

    // Return pre-calculated benefits (e.g. for AdobeCars)
    if (product.benefits && Array.isArray(product.benefits)) return product.benefits;

    const benefits = [];
    const type = product.type;
    const currencyCode = normalizeCurrencyCode(product?.currency || resolveVehicleCurrency());

    // Dynamic from API
    // Note: when excess > 0, the template renders it separately via pkg.excess
    // so we only add the zero-excess benefit text here to avoid duplication
    if (product.excess !== undefined && parseFloat(product.excess) === 0) {
        benefits.push('Glass and tyres covered');
    }

    if (product.debitcard === 'Y') {
        benefits.push('Debit Card Accepted');
    }

    if (product.fuelpolicy === 'FF') {
        benefits.push('Free Fuel / Full to Full');
    } else if (product.fuelpolicy === 'SL') {
        benefits.push('Like for Like fuel policy');
    }

    if (product.costperextradistance !== undefined && parseFloat(product.costperextradistance) === 0) {
        benefits.push('Unlimited mileage');
    } else if (product.mileage && product.mileage !== 'Unlimited' && product.mileage !== 'unlimited') {
        const mileageText = `${product.mileage}`.trim();
        const mileageValue = parseFloat(mileageText);
        if (Number.isFinite(mileageValue) && mileageText === `${mileageValue}`) {
            benefits.push(`KM Limit: ${mileageText}`);
        } else {
            benefits.push(`Mileage: ${product.mileage}`);
        }
    } else if (product.mileage === 'Unlimited' || product.mileage === 'unlimited') {
        // Fallback if costperextradistance logic doesn't catch it but string says separate
        benefits.push('Unlimited mileage');
    }


    // Static based on type (only what's not in API)
    if (type === 'BAS') {
        benefits.push('Non-refundable');
        benefits.push('Non-amendable');
    }


    if (type === 'PLU' || type === 'PRE' || type === 'PMP') {
        benefits.push('Cancellation in line with T&Cs');
    }

    return benefits;
};

const isRequiredExtra = (extra) => {
    return !!extra.required;
};

const getMaxQuantity = (extra) => {
    const max = extra.numberAllowed || extra.maxQuantity || null;
    return max ? Math.max(parseInt(max), 1) : 1;
};

const setExtraQuantity = (extra, quantity) => {
    const id = extra.id;
    const max = getMaxQuantity(extra);
    const required = isRequiredExtra(extra);
    const clamped = Math.min(Math.max(quantity, required ? 1 : 0), max);
    if (clamped <= 0) {
        delete selectedExtras.value[id];
        return;
    }
    selectedExtras.value[id] = clamped;
};

const updateExtraQuantity = (extra, delta) => {
    const id = extra.id;
    const current = selectedExtras.value[id] || 0;
    setExtraQuantity(extra, current + delta);
};

const toggleExtra = (extra) => {
    if (isRequiredExtra(extra)) {
        return;
    }
    const id = extra.id;
    if (selectedExtras.value[id]) {
        delete selectedExtras.value[id];
    } else {
        selectedExtras.value[id] = 1;
    }
};

const extrasTotal = computed(() => {
    let total = 0;
    for (const [id, qty] of Object.entries(selectedExtras.value)) {
        // Find extra from the correct source
        let extra = null;
        if (isLocautoRent.value) {
            extra = locautoOptionalExtras.value.find(e => e.id === id);
        } else if (isAdobeCars.value) {
            extra = adobeOptionalExtras.value.find(e => e.id === id);
        } else if (isInternal.value) {
            extra = internalOptionalExtras.value.find(e => e.id === id);
        } else if (isRenteon.value) {
            extra = renteonAllExtras.value.find(e => e.id === id);
        } else if (isOkMobility.value) {
            extra = okMobilityNormalizedExtras.value.find(e => e.id === id);
        } else if (isSicilyByCar.value) {
            extra = sicilyByCarAllExtras.value.find(e => e.id === id);
        } else if (isRecordGo.value) {
            extra = recordGoOptionalExtras.value.find(e => e.id === id);
        } else if (isSurprice.value) {
            extra = surpriceOptionalExtras.value.find(e => e.id === id);
        } else if (isFavrica.value || isXDrive.value) {
            extra = providerAllExtras.value.find(e => e.id === id);
        } else if (isGreenMotion.value) {
            extra = greenMotionExtras.value.find(e => e.id === id);
        } else {
            extra = props.optionalExtras.find(e => e.id === id);
        }

        if (extra) {
            if (extra.total_for_booking !== undefined && extra.total_for_booking !== null) {
                total += parseFloat(extra.total_for_booking) * qty;
            } else {
                const dailyRate = extra.daily_rate !== undefined
                    ? parseFloat(extra.daily_rate)
                    : (parseFloat(extra.price) / props.numberOfDays);
                total += dailyRate * props.numberOfDays * qty;
            }
        }
    }
    return total;
});

const netGrandTotal = computed(() => {
    if (isLocautoRent.value) {
        // For LocautoRent: base price + selected protection + extras
        const basePrice = locautoBaseTotal.value;
        const protectionAmount = selectedLocautoProtections.value.reduce((sum, code) => {
            const plan = locautoProtectionPlans.value.find(p => p.code === code);
            return sum + (plan ? parseFloat(plan.amount || 0) * props.numberOfDays : 0);
        }, 0);
        return basePrice + protectionAmount + extrasTotal.value;
    }
    // For GreenMotion/USave and Adobe
    const pkgPrice = parseFloat(currentProduct.value?.total || 0);
    const mandatoryExtra = isAdobeCars.value ? adobeMandatoryProtection.value : 0;

    // For Renteon, if simplistic logic:
    if (isRenteon.value) {
        const providerTotal = parseFloat(props.vehicle?.provider_gross_amount ?? NaN);
        const baseTotal = Number.isFinite(providerTotal) ? providerTotal : pkgPrice;
        return baseTotal + extrasTotal.value;
    }

    if (isOkMobility.value) {
        return okMobilityBaseTotal.value + extrasTotal.value;
    }

    return pkgPrice + mandatoryExtra + extrasTotal.value;
});

// Customer-facing grand total (grossed up for provider vehicles)
const grandTotal = computed(() => {
    return (parseFloat(netGrandTotal.value || 0) * providerGrossMultiplier.value).toFixed(2);
});

const effectivePaymentPercentage = computed(() => {
    if (isRenteon.value) return providerMarkupRate.value * 100;
    // Default to 15% if not provided, to prevent "Pay 0" bug
    return props.paymentPercentage || 15;
});

const payableAmount = computed(() => {
    if (isRenteon.value) {
        return (parseFloat(grandTotal.value) - parseFloat(netGrandTotal.value)).toFixed(2);
    }
    // Default to 15% if paymentPercentage is 0 or not provided
    const percent = props.paymentPercentage && props.paymentPercentage > 0 ? props.paymentPercentage : 15;
    return (parseFloat(grandTotal.value) * (percent / 100)).toFixed(2);
});

const pendingAmount = computed(() => {
    if (isRenteon.value) {
        return parseFloat(netGrandTotal.value || 0).toFixed(2);
    }
    return (parseFloat(grandTotal.value) - parseFloat(payableAmount.value)).toFixed(2);
});

const selectPackage = (pkgType) => {
    if (isOkMobility.value) {
        const coverExtra = okMobilityCoverExtras.value.find(extra => normalizeExtraCode(extra.code) === pkgType);
        if (coverExtra) {
            okMobilityCoverExtras.value.forEach((extra) => {
                if (extra.id !== coverExtra.id) {
                    delete selectedExtras.value[extra.id];
                }
            });
            setExtraQuantity(coverExtra, 1);
            currentPackage.value = pkgType;
            return;
        }

        okMobilityCoverExtras.value.forEach((extra) => {
            delete selectedExtras.value[extra.id];
        });
        currentPackage.value = pkgType;
        return;
    }

    currentPackage.value = pkgType;
    // Package change updates totals automatically
};

// Adobe Helper: Toggle Protection Add-on
const toggleAdobeProtection = (pkg) => {
    if (pkg.extraId) {
        toggleExtra({ id: pkg.extraId });
    }
};

const isAdobeProtectionSelected = (pkg) => {
    return !!selectedExtras.value[pkg.extraId];
};

// Helper to get comma-separated string of selected protection codes (LDW, SPP)
const getSelectedAdobeProtectionCodes = () => {
    const codes = [];
    for (const id in selectedExtras.value) {
        if (id.startsWith('adobe_protection_')) {
            codes.push(id.replace('adobe_protection_', ''));
        }
    }
    return codes.join(',');
};

const getSelectedExtrasDetails = computed(() => {
    const details = [];
    const coverCodes = new Set(OK_MOBILITY_COVER_CODES);
    for (const [id, qty] of Object.entries(selectedExtras.value)) {
        // Find extra from the correct source
        let extra = null;
        if (isLocautoRent.value) {
            extra = locautoOptionalExtras.value.find(e => e.id === id);
        } else if (isAdobeCars.value) {
            extra = adobeOptionalExtras.value.find(e => e.id === id);
        } else if (isInternal.value) {
            extra = internalOptionalExtras.value.find(e => e.id === id);
        } else if (isRenteon.value) {
            extra = renteonAllExtras.value.find(e => e.id === id);
        } else if (isOkMobility.value) {
            extra = okMobilityNormalizedExtras.value.find(e => e.id === id);
        } else if (isSicilyByCar.value) {
            extra = sicilyByCarAllExtras.value.find(e => e.id === id);
        } else if (isRecordGo.value) {
            extra = recordGoOptionalExtras.value.find(e => e.id === id);
        } else if (isSurprice.value) {
            extra = surpriceOptionalExtras.value.find(e => e.id === id);
        } else if (isFavrica.value || isXDrive.value) {
            extra = providerAllExtras.value.find(e => e.id === id);
        } else if (isGreenMotion.value) {
            extra = greenMotionExtras.value.find(e => e.id === id);
        } else {
            extra = props.optionalExtras.find(e => e.id === id);
        }

        if (extra) {
            if (isOkMobility.value && coverCodes.has(normalizeExtraCode(extra.code))) {
                continue;
            }
            const total = extra.total_for_booking !== undefined && extra.total_for_booking !== null
                ? parseFloat(extra.total_for_booking) * qty
                : (parseFloat(extra.daily_rate !== undefined ? extra.daily_rate : (extra.price / props.numberOfDays))
                    * props.numberOfDays * qty);

            details.push({
                id: extra.id, // Good for key
                option_id: extra.option_id ?? extra.id,
                name: getProviderExtraLabel(extra),
                qty,
                total,
                total_for_booking: extra.total_for_booking ?? null,
                daily_rate: extra.daily_rate ?? null,
                price: extra.price ?? null,
                excess: extra.excess ?? null,
                recordgo_payload: extra.recordgo_payload ?? null,
                currency: extra.currency ?? null,
                required: extra.required || false,
                numberAllowed: extra.numberAllowed ?? null,
                prepay_available: extra.prepay_available ?? null,
                service_id: extra.service_id,
                code: extra.code,
                purpose: extra.purpose ?? null
            });
        }
    }
    return details;
});

const getExtraIcon = (name) => {
    if (!name) return Plus;
    const lowerName = name.toLowerCase();

    if (lowerName.includes('gps') || lowerName.includes('nav') || lowerName.includes('sat')) return Navigation;
    if (lowerName.includes('mobile') || lowerName.includes('phone')) return Smartphone;
    if (lowerName.includes('wifi') || lowerName.includes('internet')) return Wifi;
    if (lowerName.includes('baby') || lowerName.includes('child') || lowerName.includes('booster') || lowerName.includes('infant')) return Baby;
    if (lowerName.includes('snow') || lowerName.includes('chain') || lowerName.includes('winter')) return Snowflake;
    if (lowerName.includes('driver') || lowerName.includes('additional')) return UserPlus;
    if (lowerName.includes('cover') || lowerName.includes('insurance') || lowerName.includes('protection') || lowerName.includes('waiver')) return Shield;
    if (lowerName.includes('tire') || lowerName.includes('tyre') || lowerName.includes('glass')) return CircleDashed;

    return Plus;
};

const vehicleSpecs = computed(() => {
    const v = props.vehicle;
    return {
        passengers: v.seating_capacity || v.passengers || v.adults,
        doors: v.doors,
        transmission: v.transmission, // 'Manual', 'Automatic'
        fuel: v.fuel, // 'Petrol', 'Diesel', etc.
        bagDisplay: (() => {
            // GreenMotion / USave: Return formatted string ONLY if non-zero total
            if (v.luggageLarge !== undefined || v.luggageMed !== undefined || v.luggageSmall !== undefined) {
                const small = parseInt(v.luggageSmall || 0);
                const med = parseInt(v.luggageMed || 0);
                const large = parseInt(v.luggageLarge || 0);
                if (small + med + large === 0) return null; // Don't show if all are 0
                return `S:${small} M:${med} L:${large}`;
            }
            // Wheelsys / External: Sum of bags
            if (v.bags !== undefined || v.suitcases !== undefined) {
                const total = (parseInt(v.bags) || 0) + (parseInt(v.suitcases) || 0);
                return total > 0 ? total : null;
            }
            // Locauto / Internal / Adobe -> Return count if valid
            if (v.luggage || v.luggage_capacity) {
                return v.luggage || v.luggage_capacity;
            }
            return null;
        })(),

        mpg: v.mpg,
        co2: v.co2,
        acriss: v.sipp_code || v.acriss_code || v.group_code || v.category,
        airConditioning: v.airConditioning === 'true' || v.airConditioning === true || (v.features && v.features.includes('Air Conditioning')),
    };
});

const isKeyBenefit = (text) => {
    if (!text) return false;
    const lower = text.toLowerCase();
    return lower.includes('excess') ||
        lower.includes('deposit') ||
        lower.includes('free') ||
        lower.includes('unlimited');
};

// Get short protection name for LocautoRent (extract English name from "English / Italian" format)
const getShortProtectionName = (description) => {
    if (!description) return '';
    // LocautoRent descriptions are like "Don't Worry" or "Roadside Plus / Assistenza Stradale"
    if (description.includes('/')) {
        return description.split('/')[0].trim();
    }
    return description;
};

// Get package display name
const getPackageDisplayName = (type) => {
    const names = {
        'BAS': 'Basic',
        'PLU': 'Plus',
        'PRE': 'Premium',
        'PMP': 'Premium Plus',
        // Adobe Codes
        'PLI': 'Liability Protection',
        'LDW': 'Car Protection',
        'SPP': 'Extended Protection'
    };
    return names[type] || type;
};

// Get package subtitle
const getPackageSubtitle = (type) => {
    const subtitles = {
        'BAS': 'Essential Cover',
        'PLU': 'Enhanced Cover',
        'PRE': 'Full Cover',
        'PMP': 'Ultimate Cover',
        // Adobe Codes
        'PLI': 'Essential Cover',
        'LDW': 'Standard Cover',
        'SPP': 'Maximum Cover'
    };
    return subtitles[type] || '';
};

const currentPackageLabel = computed(() => {
    if (isOkMobility.value) {
        const pkg = okMobilityPackages.value.find(item => item.type === currentPackage.value);
        if (pkg?.name) return pkg.name;
    }

    const display = getPackageDisplayName(currentPackage.value);
    return display || currentPackage.value;
});

// Get icon background class based on extra name
const getIconBackgroundClass = (name) => {
    if (!name) return 'icon-bg-gray';
    const lowerName = name.toLowerCase();

    if (lowerName.includes('gps') || lowerName.includes('nav')) return 'icon-bg-blue';
    if (lowerName.includes('baby') || lowerName.includes('child') || lowerName.includes('booster') || lowerName.includes('infant')) return 'icon-bg-pink';
    if (lowerName.includes('driver') || lowerName.includes('additional')) return 'icon-bg-green';
    if (lowerName.includes('wifi') || lowerName.includes('internet')) return 'icon-bg-purple';
    if (lowerName.includes('snow') || lowerName.includes('winter')) return 'icon-bg-blue';
    if (lowerName.includes('cover') || lowerName.includes('insurance') || lowerName.includes('protection')) return 'icon-bg-orange';

    return 'icon-bg-gray';
};

// Get icon color class based on extra name
const getIconColorClass = (name) => {
    if (!name) return 'icon-text-gray';
    const lowerName = name.toLowerCase();

    if (lowerName.includes('gps') || lowerName.includes('nav')) return 'icon-text-blue';
    if (lowerName.includes('baby') || lowerName.includes('child') || lowerName.includes('booster') || lowerName.includes('infant')) return 'icon-text-pink';
    if (lowerName.includes('driver') || lowerName.includes('additional')) return 'icon-text-green';
    if (lowerName.includes('wifi') || lowerName.includes('internet')) return 'icon-text-purple';
    if (lowerName.includes('snow') || lowerName.includes('winter')) return 'icon-text-blue';
    if (lowerName.includes('cover') || lowerName.includes('insurance') || lowerName.includes('protection')) return 'icon-text-orange';

    return 'icon-text-gray';
};
// Calculate cancellation deadline
const cancellationDeadline = computed(() => {
    // Only proceed if free cancellation is available and we have a policy duration
    if (!isInternal.value || !props.vehicle?.benefits?.cancellation_available_per_day || !props.vehicle?.benefits?.cancellation_available_per_day_date || !props.pickupDate) {
        return null;
    }

    const daysPrior = parseInt(props.vehicle?.benefits?.cancellation_available_per_day_date);
    if (isNaN(daysPrior)) return null;

    const deadline = new Date(props.pickupDate);
    deadline.setDate(deadline.getDate() - daysPrior);

    return deadline.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
});

const formatPaymentMethod = (method) => {
    if (!method) return '';

    let methods = [];
    try {
        // Check if it's a JSON string
        if (typeof method === 'string' && (method.startsWith('[') || method.startsWith('{'))) {
            const parsed = JSON.parse(method);
            if (Array.isArray(parsed)) {
                methods = parsed;
            } else {
                methods = [method];
            }
        } else if (Array.isArray(method)) {
            methods = method;
        } else {
            methods = [method];
        }
    } catch (e) {
        // Fallback if parsing fails
        methods = [method];
    }

    // Format each method: replace underscores/dashes with space, capitalize words
    return methods.map(m => {
        return m.toString()
            .replace(/[_-]/g, ' ')
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }).join(', ');
};

// ── Unified Computed Properties ──

const unifiedOptionalExtras = computed(() => {
    if (isLocautoRent.value) return locautoOptionalExtras.value;
    if (isAdobeCars.value) return adobeOptionalExtras.value;
    if (isInternal.value) return internalOptionalExtras.value;
    if (isRenteon.value) return renteonOptionalExtras.value;
    if (isOkMobility.value) return okMobilityOptionalExtras.value;
    if (isSicilyByCar.value) return sicilyByCarOptionalExtras.value;
    if (isRecordGo.value) return recordGoOptionalExtras.value;
    if (isSurprice.value) return surpriceOptionalExtras.value;
    if (isFavrica.value || isXDrive.value) return providerOptionalExtras.value;
    if (isGreenMotion.value) return greenMotionExtras.value;
    return props.optionalExtras || [];
});
const hasUnifiedExtras = computed(() => unifiedOptionalExtras.value.length > 0);

const unifiedProtectionPlans = computed(() => {
    if (isFavrica.value || isXDrive.value) return providerInsuranceOptions.value;
    if (isSicilyByCar.value) return sicilyByCarProtectionPlans.value;
    if (isRenteon.value) return renteonProtectionPlans.value;
    if (isAdobeCars.value) {
        const protections = props.vehicle?.protections || [];
        return protections.filter(p => p.code !== 'PLI' && !p.required).map(plan => ({
            id: `adobe_protection_${plan.code}`,
            code: plan.code,
            name: plan.displayName || plan.name || plan.code,
            description: plan.displayDescription || plan.description || '',
            price: parseFloat(plan.price || plan.total || 0),
            daily_rate: parseFloat(plan.price || plan.total || 0) / props.numberOfDays,
            total_for_booking: parseFloat(plan.price || plan.total || 0),
            required: plan.required || false,
        }));
    }
    return [];
});
const hasUnifiedProtectionPlans = computed(() => unifiedProtectionPlans.value.length > 0);

const unifiedTaxBreakdown = computed(() => {
    if (isRenteon.value && hasRenteonTaxBreakdown.value) {
        return { type: 'renteon', net: renteonTaxBreakdown.value.net, vat: renteonTaxBreakdown.value.vat, gross: renteonTaxBreakdown.value.gross, rate: null };
    }
    if (isOkMobility.value && (okMobilityTaxBreakdown.value.total || okMobilityTaxBreakdown.value.base || okMobilityTaxBreakdown.value.tax)) {
        return { type: 'okmobility', net: okMobilityTaxBreakdown.value.base, vat: okMobilityTaxBreakdown.value.tax, gross: okMobilityTaxBreakdown.value.total, rate: okMobilityTaxBreakdown.value.rate };
    }
    return null;
});

const unifiedIncludedItems = computed(() => {
    const items = [];
    // Internal benefits
    if (isInternal.value && props.vehicle?.benefits) {
        const b = props.vehicle.benefits;
        if (b.limited_km_per_day == 1) {
            items.push({ label: `Limited: ${b.limited_km_per_day_range} km/day`, detail: b.price_per_km_per_day ? `+${formatPrice(b.price_per_km_per_day)}/km extra` : null });
        } else {
            items.push({ label: 'Unlimited Mileage', detail: 'No daily kilometer limit' });
        }
        if (b.cancellation_available_per_day == 1) items.push({ label: 'Free Cancellation', detail: cancellationDeadline.value ? `Until ${cancellationDeadline.value}` : null });
        if (b.minimum_driver_age) items.push({ label: `Min. Driver Age: ${b.minimum_driver_age}`, detail: null });
        if (b.maximum_driver_age) items.push({ label: `Max. Driver Age: ${b.maximum_driver_age}`, detail: null });
        if (b.deposit_amount) items.push({ label: `Deposit: ${formatPrice(b.deposit_amount)}`, detail: b.deposit_currency ? `Currency: ${b.deposit_currency}` : null });
        if (b.excess_amount) items.push({ label: `Excess: ${formatPrice(b.excess_amount)}`, detail: null });
        if (b.excess_theft_amount) items.push({ label: `Theft Excess: ${formatPrice(b.excess_theft_amount)}`, detail: null });
    }
    // GreenMotion/USave benefits
    if (isGreenMotion.value && currentProduct.value) {
        const benefits = getBenefits({ type: selectedPackageType.value, benefits: currentProduct.value?.benefits });
        benefits.forEach(b => items.push({ label: b, detail: null }));
    }
    // OkMobility included
    if (isOkMobility.value) {
        okMobilityIncludedLabels.value.forEach(l => items.push({ label: l, detail: 'Included in base rate' }));
        if (okMobilityFuelPolicy.value) items.push({ label: `Fuel: ${okMobilityFuelPolicy.value}`, detail: null });
        if (okMobilityCancellationSummary.value?.available) {
            const cs = okMobilityCancellationSummary.value;
            items.push({ label: cs.amount > 0 ? `Cancellation fee ${formatRentalPrice(cs.amount)}` : 'Free Cancellation', detail: cs.deadline ? `Cancel by ${cs.deadline}` : null });
        }
    }
    // Renteon included
    if (isRenteon.value) {
        renteonIncludedServices.value.forEach(s => items.push({ label: s.name + (s.quantity_included ? ` (x${s.quantity_included})` : ''), detail: 'Included' }));
    }
    // SicilyByCar included
    if (isSicilyByCar.value) {
        sicilyByCarIncludedServices.value.forEach(s => items.push({ label: s.description || s.id, detail: s.excess != null ? `Excess: ${formatPrice(s.excess)}` : null }));
    }
    // RecordGo included
    if (isRecordGo.value) {
        recordGoIncludedComplements.value.forEach(s => items.push({ label: stripHtml(s.complementName), detail: s.complementDescription ? stripHtml(s.complementDescription) : null }));
    }
    return items;
});

const showProviderNotes = ref(false);

const hasProviderNotes = computed(() => {
    if (isInternal.value) {
        return !!(props.vehicle?.vendor?.profile || props.vehicle?.vendorProfile || props.vehicle?.vendor_profile || props.vehicle?.guidelines);
    }
    if (isRecordGo.value) return recordGoAutomaticComplements.value.length > 0;
    return false;
});

</script>


<template>
    <div class="font-display">
        <!-- Breadcrumb -->
        <div id="extras-breadcrumb-section" class="px-0 md:p-6 pb-0">
            <Breadcrumb class="mb-4">
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/">Home</BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbLink href="#" @click.prevent="$emit('back')">Search Results</BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Customize Your Rental</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </div>

        <!-- Main Layout -->
        <div class="flex flex-col lg:flex-row gap-8 px-0 md:p-6">
            <!-- ═══════ LEFT COLUMN ═══════ -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Location Instructions -->
                <div v-if="locationInstructions"
                    class="rounded-xl p-4 flex items-start gap-3 relative overflow-hidden bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold mb-0.5">Pickup Instructions</h4>
                        <p class="text-xs text-white/85 leading-relaxed">{{ locationInstructions }}</p>
                    </div>
                </div>

                <!-- ═══ 1. VEHICLE HERO — image left, map right ═══ -->
                <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden fade-in-up">
                    <!-- Fixed-height row: both halves equal -->
                    <div class="flex flex-col md:flex-row h-auto md:h-[240px]">
                        <!-- Left: Vehicle Image with carousel for internal -->
                        <div class="relative w-full md:w-1/2 h-[220px] md:h-[240px] bg-gray-50 overflow-hidden group">
                            <!-- Carousel image (internal) or single image -->
                            <img v-if="hasMultipleImages" :src="currentHeroImage" :alt="displayVehicleName" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300" />
                            <img v-else-if="vehicleImage" :src="vehicleImage" :alt="displayVehicleName" class="absolute inset-0 w-full h-full object-cover" />
                            <div v-else class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-24 h-24 text-gray-300" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                            </div>
                            <!-- Prev / Next arrows (show on hover) -->
                            <template v-if="hasMultipleImages">
                                <button @click.stop="heroPrevImage" class="absolute left-2 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-black/60">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @click.stop="heroNextImage" class="absolute right-2 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-black/60">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <!-- Dot indicators -->
                                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5">
                                    <button v-for="(img, idx) in allHeroImages" :key="idx" @click.stop="heroImageIndex = idx"
                                        class="w-2 h-2 rounded-full transition-all duration-200"
                                        :class="idx === heroImageIndex ? 'bg-white w-4' : 'bg-white/50 hover:bg-white/80'">
                                    </button>
                                </div>
                            </template>
                            <!-- Lightbox enlarge button -->
                            <button v-if="vehicleImage" @click.stop="showLightbox = true" class="absolute bottom-3 right-3 z-20 w-8 h-8 rounded-lg bg-black/40 backdrop-blur-sm text-white flex items-center justify-center hover:bg-black/60 transition-colors" :class="{ 'bottom-8': hasMultipleImages }" title="View fullscreen">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            </button>
                            <!-- Provider badge -->
                            <div v-if="providerBadge" class="provider-ribbon" :class="providerBadge.ribbonClassName">
                                {{ providerBadge.label }}
                            </div>
                            <div class="absolute bottom-3 left-3 flex items-center gap-1.5" :class="{ 'bottom-8': hasMultipleImages }">
                                <span v-if="vehicleSpecs.acriss" class="bg-black/40 backdrop-blur text-white text-[11px] font-bold px-2 py-1 rounded">{{ vehicleSpecs.acriss }}</span>
                                <span v-if="vehicle?.category" class="bg-black/40 backdrop-blur text-white text-[11px] font-medium px-2 py-1 rounded">{{ vehicle.category }}</span>
                            </div>
                        </div>
                        <!-- Right: Map — 50% width, same fixed height -->
                        <div v-if="hasVehicleCoords" class="relative w-full md:w-1/2 h-[200px] md:h-[240px] border-t md:border-t-0 md:border-l border-gray-200">
                            <div ref="vehicleMapRef" class="absolute inset-0 w-full h-full"></div>
                            <div v-if="isDifferentDropoff" class="absolute bottom-2 left-2 z-[1000] flex items-center gap-2 bg-white/90 backdrop-blur-sm rounded px-2 py-1 shadow-sm text-[10px] font-medium text-gray-600">
                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Pickup</span>
                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Dropoff</span>
                            </div>
                            <button @click="showMapModal = true" class="absolute bottom-2 right-2 z-[1000] bg-white/90 backdrop-blur-sm rounded-lg p-1.5 shadow hover:bg-white hover:shadow-md transition-all" title="Expand map">
                                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            </button>
                        </div>
                    </div>
                    <!-- Vehicle name + specs inline below -->
                    <div class="px-5 py-4 border-t border-gray-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-[#1e3a5f] leading-tight">{{ displayVehicleName }}</h2>
                                <p class="text-xs text-gray-500 mt-0.5">{{ vehicle?.category || 'Economy' }} &middot; {{ vehicleSpecs.transmission || 'Manual' }}</p>
                            </div>
                            <div class="bg-[#1e3a5f]/5 border border-[#1e3a5f]/15 rounded-lg px-3 py-2 text-center flex-shrink-0">
                                <span class="text-lg font-bold text-[#1e3a5f] block leading-none">{{ numberOfDays }}</span>
                                <span class="text-[10px] font-semibold text-[#1e3a5f]/70 uppercase tracking-wider">Days</span>
                            </div>
                        </div>
                        <!-- Specs chips inline -->
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            <div v-if="vehicleSpecs.passengers" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <img :src="seatingIcon" class="w-3.5 h-3.5" alt="" />
                                {{ vehicleSpecs.passengers }} Seats
                            </div>
                            <div v-if="vehicleSpecs.doors" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <img :src="doorIcon" class="w-3.5 h-3.5" alt="" />
                                {{ vehicleSpecs.doors }}
                            </div>
                            <div v-if="vehicleSpecs.transmission" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <img :src="transmissionIcon" class="w-3.5 h-3.5" alt="" />
                                {{ vehicleSpecs.transmission }}
                            </div>
                            <div v-if="vehicleSpecs.airConditioning" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <img :src="acIcon" class="w-3.5 h-3.5" alt="" />
                                A/C
                            </div>
                            <div v-if="vehicleSpecs.bagDisplay" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                {{ vehicleSpecs.bagDisplay }}
                            </div>
                            <div v-if="currentProduct?.mileage" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                {{ currentProduct.mileage }}
                            </div>
                            <div v-if="vehicleSpecs.fuel" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                                <img :src="fuelIcon" class="w-3.5 h-3.5" alt="" />
                                {{ vehicleSpecs.fuel }}
                            </div>
                            <div v-if="vehicleSpecs.co2" class="flex items-center gap-1.5 px-2 py-1 bg-green-50 rounded border border-green-100 text-xs text-green-700">
                                <component :is="Leaf" class="w-3.5 h-3.5 text-green-600" />
                                {{ vehicleSpecs.co2 }} g/km
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ 2. RENTAL DETAILS ═══ -->
                <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.05s">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Pickup -->
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-50 to-green-50 rounded-full flex items-center justify-center border-2 border-emerald-200 shadow-sm flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Pickup</span>
                                <p class="text-sm font-bold text-gray-900 leading-snug mt-0.5">{{ pickupLocation || locationName }}</p>
                                <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ pickupDate }} <span class="text-gray-300">|</span> {{ pickupTime }}
                                </p>
                            </div>
                        </div>
                        <!-- Dropoff -->
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-rose-50 to-pink-50 rounded-full flex items-center justify-center border-2 border-rose-200 shadow-sm flex-shrink-0">
                                <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Dropoff</span>
                                <p class="text-sm font-bold text-gray-900 leading-snug mt-0.5">{{ dropoffLocation || pickupLocation || locationName }}</p>
                                <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ dropoffDate }} <span class="text-gray-300">|</span> {{ dropoffTime }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ 5. LOCATION DETAILS ═══ -->
                <section v-if="vehicleLocationText || hasVehicleCoords || (isOkMobility && okMobilityInfoAvailable) || (isRenteon && renteonHasOfficeDetails) || (isAdobeCars && vehicle.office_address)"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.2s">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Location Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Pickup -->
                        <div class="bg-emerald-50/60 rounded-xl p-4 border border-emerald-100">
                            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Pick-up Location</span>
                            <p class="text-sm font-semibold text-gray-900 mt-2">{{ pickupLocation || locationName }}</p>
                            <p v-if="vehicleLocationText" class="text-xs text-gray-500 mt-1">{{ vehicleLocationText }}</p>
                            <!-- Adobe location details -->
                            <template v-if="isAdobeCars">
                                <p v-if="vehicle.office_address" class="text-xs text-gray-500 mt-1">{{ vehicle.office_address }}</p>
                                <div class="mt-3 pt-3 border-t border-emerald-200/50 space-y-1.5">
                                    <div v-if="vehicle.office_phone" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span>{{ vehicle.office_phone }}</span>
                                    </div>
                                    <div v-if="vehicle.office_schedule && vehicle.office_schedule.length === 2" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Open {{ vehicle.office_schedule[0] }} – {{ vehicle.office_schedule[1] }}</span>
                                    </div>
                                    <div v-if="vehicle.at_airport" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                                        <span>Airport location</span>
                                    </div>
                                </div>
                            </template>
                            <!-- Generic location details -->
                            <template v-if="!isOkMobility && !isRenteon && !isAdobeCars">
                                <div v-if="locationDetailLines.length" class="mt-2 space-y-0.5">
                                    <p v-for="(line, i) in locationDetailLines" :key="i" class="text-xs text-gray-500">{{ line }}</p>
                                </div>
                                <div v-if="locationContact.phone || locationContact.email" class="mt-3 pt-3 border-t border-emerald-200/50 space-y-1.5">
                                    <div v-if="locationContact.phone" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span>{{ locationContact.phone }}</span>
                                    </div>
                                    <div v-if="locationContact.email" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span>{{ locationContact.email }}</span>
                                    </div>
                                </div>
                                <button v-if="hasLocationHours" @click="showLocationHoursModal = true"
                                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-[#1e3a5f] hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    View hours & policies
                                </button>
                            </template>
                            <!-- OkMobility details -->
                            <template v-if="isOkMobility">
                                <p v-if="okMobilityPickupStation" class="text-xs text-gray-500 mt-1">{{ okMobilityPickupStation }}</p>
                                <p v-if="okMobilityPickupAddress" class="text-xs text-gray-500">{{ okMobilityPickupAddress }}</p>
                            </template>
                            <!-- Renteon details -->
                            <template v-if="isRenteon && renteonPickupOffice">
                                <p v-if="renteonPickupOffice.name" class="text-xs text-gray-500 mt-1">{{ renteonPickupOffice.name }}</p>
                                <div v-if="renteonPickupLines.length" class="mt-1 space-y-0.5">
                                    <p v-for="(line, i) in renteonPickupLines" :key="i" class="text-xs text-gray-500">{{ line }}</p>
                                </div>
                                <div v-if="renteonPickupOffice.phone || renteonPickupOffice.email" class="mt-3 pt-3 border-t border-emerald-200/50 space-y-1.5">
                                    <div v-if="renteonPickupOffice.phone" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span>{{ renteonPickupOffice.phone }}</span>
                                    </div>
                                    <div v-if="renteonPickupOffice.email" class="flex items-center gap-2 text-xs text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span>{{ renteonPickupOffice.email }}</span>
                                    </div>
                                </div>
                                <p v-if="renteonPickupInstructions" class="text-xs text-gray-400 mt-2 italic">{{ renteonPickupInstructions }}</p>
                            </template>
                        </div>
                        <!-- Dropoff -->
                        <div class="bg-rose-50/60 rounded-xl p-4 border border-rose-100">
                            <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Drop-off Location</span>
                            <template v-if="hasOneWayDropoff">
                                <p class="text-sm font-semibold text-gray-900 mt-2">{{ dropoffLocation }}</p>
                                <template v-if="isOkMobility">
                                    <p v-if="okMobilityDropoffStation" class="text-xs text-gray-500 mt-1">{{ okMobilityDropoffStation }}</p>
                                    <p v-if="okMobilityDropoffAddress" class="text-xs text-gray-500">{{ okMobilityDropoffAddress }}</p>
                                </template>
                                <template v-if="isRenteon && renteonDropoffOffice && !renteonSameOffice">
                                    <p v-if="renteonDropoffOffice.name" class="text-xs text-gray-500 mt-1">{{ renteonDropoffOffice.name }}</p>
                                    <div v-if="renteonDropoffLines.length" class="mt-1 space-y-0.5">
                                        <p v-for="(line, i) in renteonDropoffLines" :key="i" class="text-xs text-gray-500">{{ line }}</p>
                                    </div>
                                    <div v-if="renteonDropoffOffice.phone || renteonDropoffOffice.email" class="mt-3 pt-3 border-t border-rose-200/50 space-y-1.5">
                                        <div v-if="renteonDropoffOffice.phone" class="flex items-center gap-2 text-xs text-gray-600">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <span>{{ renteonDropoffOffice.phone }}</span>
                                        </div>
                                    </div>
                                    <p v-if="renteonDropoffInstructions" class="text-xs text-gray-400 mt-2 italic">{{ renteonDropoffInstructions }}</p>
                                </template>
                            </template>
                            <template v-else>
                                <p class="text-sm font-semibold text-gray-900 mt-2">Same as pick-up location</p>
                            </template>
                        </div>
                    </div>
                </section>

                <!-- ═══ 6. WHAT'S INCLUDED ═══ -->
                <section v-if="unifiedIncludedItems.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.25s">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        What's Included
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="(item, idx) in unifiedIncludedItems" :key="idx" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/60">
                            <div class="check-icon bg-emerald-500 text-white mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ item.label }}</p>
                                <p v-if="item.detail" class="text-xs text-gray-500">{{ item.detail }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ 7. DEPOSIT & EXCESS ═══ -->
                <section v-if="(isInternal && (vehicle?.security_deposit > 0 || vehicle?.benefits?.deposit_amount || vehicle?.benefits?.excess_amount)) || (isRenteon && (vehicle?.benefits?.deposit_amount || vehicle?.benefits?.excess_amount || vehicle?.benefits?.excess_theft_amount))"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.3s">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Deposit & Excess
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-if="vehicle?.security_deposit > 0" class="bg-amber-50/70 rounded-xl p-4 border border-amber-200/60">
                            <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Security Deposit</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(vehicle?.security_deposit) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Blocked on credit card at pick-up</p>
                        </div>
                        <div v-else-if="vehicle?.benefits?.deposit_amount" class="bg-amber-50/70 rounded-xl p-4 border border-amber-200/60">
                            <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Deposit Amount</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(vehicle?.benefits?.deposit_amount) }}</p>
                            <p v-if="vehicle?.benefits?.deposit_currency" class="text-xs text-gray-500 mt-1">Currency: {{ vehicle?.benefits?.deposit_currency }}</p>
                        </div>
                        <div v-if="vehicle?.benefits?.excess_amount" class="bg-orange-50/70 rounded-xl p-4 border border-orange-200/60">
                            <p class="text-xs font-bold text-orange-700 uppercase tracking-wider">Excess Amount</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(vehicle?.benefits?.excess_amount) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Maximum liability per incident</p>
                        </div>
                        <div v-if="vehicle?.benefits?.excess_theft_amount" class="bg-orange-50/70 rounded-xl p-4 border border-orange-200/60">
                            <p class="text-xs font-bold text-orange-700 uppercase tracking-wider">Theft Excess</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(vehicle?.benefits?.excess_theft_amount) }}</p>
                        </div>
                    </div>

                    <!-- Deposit Type Selector (internal only) -->
                    <div v-if="availableDepositTypes.length > 1" class="mt-4 rounded-xl border-2 p-4 transition-colors"
                        :class="selectedDepositType ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-300 bg-amber-50'">
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="selectedDepositType ? 'bg-emerald-100' : 'bg-amber-100'">
                                <svg v-if="selectedDepositType" class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <svg v-else class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-900">How would you like to pay the deposit at pickup?</h5>
                                <p class="text-xs text-gray-500 mt-0.5">The vendor requires a deposit of <strong>{{ formatPrice(vehicle?.security_deposit || vehicle?.benefits?.deposit_amount) }}</strong> when you collect the vehicle.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="dtype in availableDepositTypes" :key="dtype" class="relative cursor-pointer">
                                <input type="radio" :value="dtype" v-model="selectedDepositType" class="sr-only peer" />
                                <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all cursor-pointer peer-checked:border-[#1e3a5f] peer-checked:bg-[#1e3a5f] peer-checked:text-white peer-checked:shadow-lg border-gray-200 bg-white text-gray-700 hover:border-[#1e3a5f]/40 hover:shadow-sm">
                                    <svg v-if="selectedDepositType === dtype" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span v-else class="w-4 h-4 rounded-full border-2 border-gray-300"></span>
                                    {{ formatPaymentMethod(dtype) }}
                                </span>
                            </label>
                        </div>
                    </div>
                    <div v-else-if="availableDepositTypes.length === 1" class="mt-4 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-50 border border-blue-100">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <p class="text-sm text-blue-700">Deposit payable via <strong>{{ formatPaymentMethod(availableDepositTypes[0]) }}</strong> at pickup</p>
                    </div>
                </section>

                <!-- ═══ 8. TAXES & FEES ═══ -->
                <section v-if="unifiedTaxBreakdown" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.35s">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z"/></svg>
                        Taxes &amp; Fees
                    </h3>
                    <div class="space-y-3">
                        <div v-if="unifiedTaxBreakdown.net" class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Base Price (excl. tax)</span>
                            <span class="text-sm font-semibold text-gray-900">{{ unifiedTaxBreakdown.type === 'renteon' ? formatPrice(unifiedTaxBreakdown.net) : formatRentalPrice(unifiedTaxBreakdown.net) }}</span>
                        </div>
                        <div v-if="unifiedTaxBreakdown.vat" class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">VAT{{ unifiedTaxBreakdown.rate ? ` (${unifiedTaxBreakdown.rate}%)` : '' }}</span>
                            <span class="text-sm font-semibold text-gray-900">{{ unifiedTaxBreakdown.type === 'renteon' ? formatPrice(unifiedTaxBreakdown.vat) : formatRentalPrice(unifiedTaxBreakdown.vat) }}</span>
                        </div>
                        <div v-if="unifiedTaxBreakdown.gross" class="flex items-center justify-between py-2">
                            <span class="text-sm font-bold text-gray-900">Total (incl. tax)</span>
                            <span class="text-sm font-bold text-[#1e3a5f]">{{ unifiedTaxBreakdown.type === 'renteon' ? formatPrice(unifiedTaxBreakdown.gross) : formatRentalPrice(unifiedTaxBreakdown.gross) }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">Supplier totals shown; your final price in the summary includes our booking fee.</p>
                </section>

                <!-- ═══ OkMobility Policies ═══ -->
                <section v-if="isOkMobility && (okMobilityFuelPolicy || okMobilityCancellationSummary)"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Policies
                    </h3>
                    <div class="space-y-4">
                        <div v-if="okMobilityFuelPolicy" class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Fuel Policy</p>
                                <p class="text-xs text-gray-500">{{ okMobilityFuelPolicy }}</p>
                            </div>
                        </div>
                        <div v-if="okMobilityCancellationSummary">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Cancellation</p>
                                    <template v-if="!okMobilityCancellationSummary.available">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">Not available</span>
                                    </template>
                                    <template v-else>
                                        <p class="text-xs text-gray-500">{{ okMobilityCancellationSummary.amount > 0 ? `Fee: ${formatRentalPrice(okMobilityCancellationSummary.amount)}` : 'Free cancellation' }}</p>
                                        <p v-if="okMobilityCancellationSummary.deadline" class="text-xs text-gray-500">Cancel by {{ okMobilityCancellationSummary.deadline }}</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ Renteon Highlights ═══ -->
                <section v-if="isRenteon && (renteonIncludedServices.length || renteonDriverPolicy)"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Renteon Highlights
                    </h3>
                    <div class="space-y-4">
                        <div v-if="renteonIncludedServices.length">
                            <div class="flex flex-wrap gap-2">
                                <span v-for="service in renteonIncludedServices" :key="service.id"
                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ service.name }}<span v-if="service.quantity_included" class="ml-1">(x{{ service.quantity_included }})</span>
                                </span>
                            </div>
                        </div>
                        <div v-if="renteonDriverPolicy" class="text-sm text-gray-600">
                            <span v-if="renteonDriverPolicy.minAge">Min age: {{ renteonDriverPolicy.minAge }}</span>
                            <span v-if="renteonDriverPolicy.maxAge" class="ml-3">Max age: {{ renteonDriverPolicy.maxAge }}</span>
                            <span v-if="renteonDriverPolicy.youngFrom" class="ml-3">Young: {{ renteonDriverPolicy.youngFrom }}-{{ renteonDriverPolicy.youngTo || 'N/A' }}</span>
                            <span v-if="renteonDriverPolicy.seniorFrom" class="ml-3">Senior: {{ renteonDriverPolicy.seniorFrom }}-{{ renteonDriverPolicy.seniorTo || 'N/A' }}</span>
                        </div>
                    </div>
                </section>

                <!-- ═══ 9. DRIVER REQUIREMENTS ═══ -->
                <section v-if="driverRequirementItems.length || mileageTypeLabel"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Driver Requirements
                    </h3>
                    <p v-if="mileageTypeLabel" class="text-sm text-gray-600 mb-3">
                        <span class="font-semibold text-gray-800">Mileage type:</span> {{ mileageTypeLabel }}
                    </p>
                    <ul v-if="driverRequirementItems.length" class="text-sm text-gray-600 space-y-2">
                        <li v-for="item in driverRequirementItems" :key="item" class="flex items-start gap-2">
                            <span class="mt-1.5 w-2 h-2 rounded-full bg-[#1e3a5f] flex-shrink-0"></span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </section>

                <!-- ═══ 10. PACKAGE / RATE SELECTION ═══ -->
                <section v-if="!isLocautoRent && availablePackages.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.4s" id="extras-package-section">
                    <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Choose Your Package
                    </h3>
                    <p class="text-sm text-gray-500 mb-5">Select the rental package that best fits your needs</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="pkg in availablePackages" :key="pkg.type"
                            @click="isAdobeCars && pkg.isAddOn ? toggleAdobeProtection(pkg) : selectPackage(pkg.type)"
                            class="plan-card rounded-2xl border-2 p-5 relative cursor-pointer"
                            :class="(isAdobeCars && pkg.isAddOn ? isAdobeProtectionSelected(pkg) : selectedPackageType === pkg.type) ? 'selected' : 'border-gray-200 hover:border-gray-300 transition-colors'">
                            <!-- Radio/Check at top-right -->
                            <div class="absolute top-3 right-3">
                                <template v-if="isAdobeCars && pkg.isAddOn">
                                    <div class="w-5 h-5 rounded border-2 flex items-center justify-center"
                                        :class="isAdobeProtectionSelected(pkg) ? 'border-[#1e3a5f] bg-[#1e3a5f]' : 'border-gray-300'">
                                        <svg v-if="isAdobeProtectionSelected(pkg)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                        :class="selectedPackageType === pkg.type ? 'border-[#1e3a5f]' : 'border-gray-300'">
                                        <div v-if="selectedPackageType === pkg.type" class="w-2.5 h-2.5 rounded-full bg-[#1e3a5f] radio-dot"></div>
                                    </div>
                                </template>
                            </div>
                            <!-- Popular badge -->
                            <div v-if="pkg.type === 'PMP' || pkg.isBestValue" class="absolute top-3 left-4">
                                <span class="bg-gradient-to-r from-amber-400 to-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">{{ pkg.isBestValue ? 'Recommended' : 'Popular' }}</span>
                            </div>
                            <h4 class="text-base font-bold text-gray-900" :class="{'mt-4': pkg.type === 'PMP' || pkg.isBestValue}">{{ pkg.name || getPackageDisplayName(pkg.type) }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ pkg.subtitle || getPackageSubtitle(pkg.type) }}</p>
                            <!-- Cover features (OKMobility style: included/excluded) -->
                            <ul v-if="pkg.coverFeatures?.length" class="mt-3 space-y-1.5">
                                <li v-for="(feature, idx) in pkg.coverFeatures" :key="idx" class="flex items-center gap-2 text-xs" :class="feature.included ? 'text-gray-700' : 'text-gray-400'">
                                    <svg v-if="feature.included" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <svg v-else class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span :class="{ 'line-through': !feature.included }">{{ feature.label }}</span>
                                </li>
                            </ul>
                            <!-- Standard benefits list -->
                            <ul v-else-if="getBenefits(pkg).length || pkg.deposit || pkg.excess" class="mt-3 space-y-1.5">
                                <li v-for="(benefit, idx) in getBenefits(pkg)" :key="idx" class="flex items-center gap-2 text-xs text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    {{ benefit }}
                                </li>
                                <li v-if="pkg.deposit" class="flex items-center gap-2 text-xs text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Deposit: {{ formatPrice(pkg.deposit) }}
                                </li>
                                <li v-if="pkg.excess" class="flex items-center gap-2 text-xs text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Excess: {{ formatPrice(pkg.excess) }}
                                </li>
                            </ul>
                            <div class="mt-4 pt-3 border-t border-gray-200">
                                <span class="text-xl font-bold text-[#1e3a5f]">{{ formatRentalPrice(pkg.total) }}</span>
                                <span class="text-xs text-gray-500 ml-1">total</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- LocautoRent Protection Plans -->
                <section v-if="isLocautoRent && locautoProtectionPlans.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" id="extras-package-section">
                    <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Protection Plans
                    </h3>
                    <p class="text-sm text-gray-500 mb-5">Add protection to reduce your liability — select as many as you need</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Protection Plans (multi-select) -->
                        <div v-for="protection in locautoProtectionPlans" :key="protection.code"
                            @click="toggleLocautoProtection(protection.code)"
                            class="plan-card rounded-2xl border-2 p-5 relative cursor-pointer"
                            :class="selectedLocautoProtections.includes(protection.code) ? 'selected' : 'border-gray-200 hover:border-gray-300 transition-colors'">
                            <div class="absolute top-3 right-3">
                                <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all duration-200"
                                    :class="selectedLocautoProtections.includes(protection.code) ? 'border-[#1e3a5f] bg-[#1e3a5f]' : 'border-gray-300'">
                                    <svg v-if="selectedLocautoProtections.includes(protection.code)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                            <h4 class="text-base font-bold text-gray-900">{{ getShortProtectionName(protection.description) }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ protection.description }}</p>
                            <div class="mt-4 pt-3 border-t border-gray-200 flex items-baseline gap-1">
                                <span class="text-lg font-bold text-[#1e3a5f]">{{ formatRentalPrice(protection.amount) }}</span>
                                <span class="text-xs text-gray-500">/day</span>
                                <span class="text-xs text-gray-400 ml-auto">{{ formatRentalPrice(protection.amount * numberOfDays) }} total</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ 11. PROTECTION PLANS / INSURANCE ═══ -->
                <section v-if="hasUnifiedProtectionPlans" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.45s">
                    <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Protection Plans
                    </h3>
                    <p class="text-sm text-gray-500 mb-5">Add insurance to reduce your liability</p>

                    <div class="space-y-3">
                        <label v-for="extra in unifiedProtectionPlans" :key="extra.id"
                            @click="toggleExtra(extra)"
                            class="plan-card flex items-start gap-4 rounded-2xl border-2 p-5 cursor-pointer"
                            :class="selectedExtras[extra.id] ? 'selected' : 'border-gray-200 hover:border-gray-300 transition-colors'">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-5 h-5 rounded border-2 flex items-center justify-center"
                                    :class="selectedExtras[extra.id] ? 'border-[#1e3a5f] bg-[#1e3a5f]' : 'border-gray-300'">
                                    <svg v-if="selectedExtras[extra.id]" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-gray-900">{{ getProviderExtraLabel(extra) }}</h4>
                                    <span class="text-sm font-bold text-[#1e3a5f] whitespace-nowrap">
                                        <template v-if="isSicilyByCar">{{ formatRentalPrice(getExtraPerDay(extra)) }}/day</template>
                                        <template v-else>{{ formatRentalPrice(extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / numberOfDays))) }}{{ extra.total_for_booking != null ? '' : '/day' }}</template>
                                    </span>
                                </div>
                                <p v-if="extra.description" class="text-xs text-gray-500 mt-1 protection-desc" v-html="extra.description"></p>
                                <p v-if="extra.excess != null" class="text-xs text-gray-500 mt-0.5">Excess: <span class="font-semibold text-gray-700">{{ formatPrice(extra.excess) }}</span></p>
                                <div v-if="extra.numberAllowed && extra.numberAllowed > 1" class="flex items-center gap-2 mt-2" @click.stop>
                                    <button type="button" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors" @click.stop="updateExtraQuantity(extra, -1)" :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </button>
                                    <span class="w-6 text-center text-sm font-bold text-gray-900">{{ selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                                    <button type="button" class="w-7 h-7 rounded-lg bg-[#1e3a5f] text-white flex items-center justify-center hover:bg-[#2d5a8f] transition-colors" @click.stop="updateExtraQuantity(extra, 1)" :disabled="selectedExtras[extra.id] >= extra.numberAllowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </label>
                    </div>
                </section>
                <section v-else-if="isRenteon" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-lg font-bold text-[#1e3a5f] mb-2">Protection Plans</h3>
                    <p class="text-sm text-gray-500">No protection plans were provided by the supplier for this offer.</p>
                </section>

                <!-- ═══ Included Services (SicilyByCar) ═══ -->
                <section v-if="isSicilyByCar && sicilyByCarIncludedServices.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Included Services
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="service in sicilyByCarIncludedServices" :key="service.id" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/60">
                            <div class="check-icon bg-emerald-500 text-white mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ service.description || service.id }}</p>
                                <p v-if="service.excess != null" class="text-xs text-gray-500">Excess: {{ formatPrice(service.excess) }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ Included Coverage (RecordGo) ═══ -->
                <section v-if="isRecordGo && recordGoIncludedComplements.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Included Coverage
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="service in recordGoIncludedComplements" :key="service.complementId" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/60">
                            <div class="check-icon bg-emerald-500 text-white mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ stripHtml(service.complementName) }}</p>
                                <p v-if="service.complementDescription" class="text-xs text-gray-500">{{ stripHtml(service.complementDescription) }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ Automatic Supplements (RecordGo) ═══ -->
                <section v-if="isRecordGo && recordGoAutomaticComplements.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Automatic Supplements
                    </h3>
                    <div class="space-y-3">
                        <div v-for="service in recordGoAutomaticComplements" :key="service.complementId" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ stripHtml(service.complementName) }}</p>
                                <p v-if="service.complementDescription" class="text-xs text-gray-500">{{ stripHtml(service.complementDescription) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-gray-900">{{ formatRentalPrice(service.priceTaxIncDayDiscount ?? service.priceTaxIncDay ?? service.priceTaxIncComplement ?? 0) }}</span>
                                <p class="text-xs text-gray-400">Per Day</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ 12. OPTIONAL EXTRAS ═══ -->
                <section v-if="hasUnifiedExtras" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.5s">
                    <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        {{ (isFavrica || isXDrive) ? 'Additional Services' : 'Optional Extras' }}
                    </h3>
                    <p class="text-sm text-gray-500 mb-5">{{ (isFavrica || isXDrive) ? 'Add helpful services to your booking' : 'Enhance your rental experience' }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template v-for="extra in unifiedOptionalExtras" :key="extra.id">
                            <div v-if="!extra.isHidden" @click="toggleExtra(extra)"
                                class="card-hover rounded-2xl border p-4 bg-white cursor-pointer transition-all"
                                :class="selectedExtras[extra.id] ? 'border-2 border-[#1e3a5f] bg-[#f0f4f8]' : 'border-gray-200'">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                        :class="getIconBackgroundClass(getProviderExtraLabel(extra))">
                                        <component :is="getExtraIcon(getProviderExtraLabel(extra))" class="w-5 h-5"
                                            :class="getIconColorClass(getProviderExtraLabel(extra))" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ getProviderExtraLabel(extra) }}</h4>
                                        <p v-if="(isSicilyByCar || isRecordGo) && extra.description" class="text-xs text-gray-500 truncate">{{ extra.description }}</p>
                                    </div>
                                    <span v-if="extra.required" class="text-[10px] uppercase tracking-wide font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full flex-shrink-0">Required</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-[#1e3a5f]">
                                        <template v-if="isSicilyByCar">
                                            {{ formatRentalPrice(getExtraPerDay(extra)) }}<span class="text-xs font-normal text-gray-500">/day</span>
                                        </template>
                                        <template v-else>
                                            {{ formatRentalPrice(extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / numberOfDays))) }}<span class="text-xs font-normal text-gray-500">{{ extra.total_for_booking != null ? '/booking' : '/day' }}</span>
                                        </template>
                                    </span>
                                    <!-- Quantity controls or Add button -->
                                    <div v-if="extra.numberAllowed && extra.numberAllowed > 1" class="flex items-center gap-2" @click.stop>
                                        <button type="button" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors" @click.stop="updateExtraQuantity(extra, -1)" :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                        <span class="w-6 text-center text-sm font-bold text-gray-900">{{ selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                                        <button type="button" class="w-7 h-7 rounded-lg bg-[#1e3a5f] text-white flex items-center justify-center hover:bg-[#2d5a8f] transition-colors" @click.stop="updateExtraQuantity(extra, 1)" :disabled="selectedExtras[extra.id] >= extra.numberAllowed">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                                        </button>
                                    </div>
                                    <button v-else-if="!selectedExtras[extra.id]" class="px-4 py-1.5 rounded-lg bg-[#1e3a5f]/5 border border-[#1e3a5f]/20 text-[#1e3a5f] text-xs font-semibold hover:bg-[#1e3a5f]/10 transition-colors" @click.stop="toggleExtra(extra)">Add</button>
                                    <span v-else class="px-4 py-1.5 rounded-lg bg-[#1e3a5f] text-white text-xs font-semibold">Added</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- ═══ 13. PROVIDER-SPECIFIC NOTES ═══ -->
                <section v-if="hasProviderNotes" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden fade-in-up" style="animation-delay:0.55s">
                    <button @click="showProviderNotes = !showProviderNotes" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50/50 transition-colors">
                        <h3 class="text-lg font-bold text-[#1e3a5f] flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Important Information
                        </h3>
                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="showProviderNotes ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div v-show="showProviderNotes" class="px-6 pb-6 space-y-3">
                        <template v-if="isInternal">
                            <div v-if="vehicle?.vendor?.profile || vehicle?.vendorProfile || vehicle?.vendor_profile" class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                                <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-1">Vendor Information</p>
                                <p class="text-sm text-gray-700">{{ vehicle?.vendorProfileData?.company_name || vehicle?.vendor_profile_data?.company_name || vehicle?.vendor?.profile?.company_name || vehicle?.vendorProfile?.company_name || vehicle?.vendor_profile?.company_name || 'Vendor' }}{{ vehicle?.vendor?.profile?.about || vehicle?.vendorProfile?.about || vehicle?.vendor_profile?.about ? ' — ' + (vehicle?.vendor?.profile?.about || vehicle?.vendorProfile?.about || vehicle?.vendor_profile?.about) : '' }}</p>
                            </div>
                            <div v-if="vehicle?.guidelines" class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Pick-up Guidelines</p>
                                <p class="text-sm text-gray-700 whitespace-pre-line">{{ vehicle?.guidelines }}</p>
                            </div>
                        </template>
                        <template v-if="isRecordGo && recordGoAutomaticComplements.length > 0">
                            <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Automatic Charges</p>
                                <p class="text-sm text-gray-700">Some charges apply automatically based on booking conditions. See the Automatic Supplements section above for details.</p>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- ═══ 14. TERMS & CONDITIONS ═══ -->
                <section v-if="terms && terms.length" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.6s">
                    <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Terms &amp; Conditions
                    </h3>
                    <div class="space-y-3 max-h-48 overflow-y-auto pr-2">
                        <div v-for="(category, index) in terms" :key="`term-${index}`">
                            <p class="text-xs font-bold text-gray-700 mb-1">{{ category.name }}</p>
                            <ul class="space-y-1">
                                <li v-for="(condition, ci) in category.conditions" :key="`term-${index}-${ci}`" class="text-xs text-gray-600 leading-relaxed">{{ condition }}</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ═══════ RIGHT SIDEBAR ═══════ -->
            <div class="w-full lg:w-96 xl:w-[420px] flex-shrink-0" ref="summarySection">
                <div class="sticky-summary space-y-4">
                    <!-- ═══ BOOKING SUMMARY CARD ═══ -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] p-4">
                            <h3 class="text-white font-bold text-base">Booking Summary</h3>
                        </div>

                        <div class="p-5 space-y-5">
                            <!-- Vehicle mini card -->
                            <div class="flex items-center gap-3">
                                <div class="w-20 h-14 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                    <img v-if="vehicleImage" :src="vehicleImage" alt="" class="w-full h-full object-contain p-1" />
                                    <svg v-else class="w-8 h-8 text-gray-300" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ displayVehicleName }}</p>
                                    <span v-if="providerBadge" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="providerBadge.className">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="providerBadge.className.includes('emerald') ? 'bg-emerald-500' : providerBadge.className.includes('indigo') ? 'bg-indigo-500' : providerBadge.className.includes('amber') ? 'bg-amber-500' : providerBadge.className.includes('cyan') ? 'bg-cyan-500' : providerBadge.className.includes('sky') ? 'bg-sky-500' : providerBadge.className.includes('pink') ? 'bg-pink-500' : providerBadge.className.includes('purple') ? 'bg-purple-500' : providerBadge.className.includes('violet') ? 'bg-violet-500' : providerBadge.className.includes('slate') ? 'bg-slate-500' : 'bg-gray-500'"></span>
                                        {{ providerBadge.label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Trip timeline -->
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0 ring-4 ring-emerald-50"></div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ pickupDate }} &middot; {{ pickupTime }}</p>
                                        <p class="text-xs text-gray-500">{{ pickupLocation || locationName }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-2 h-2 rounded-full bg-rose-500 mt-1.5 flex-shrink-0 ring-4 ring-rose-50"></div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ dropoffDate }} &middot; {{ dropoffTime }}</p>
                                        <p class="text-xs text-gray-500">{{ dropoffLocation || pickupLocation || locationName }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Specs chips -->
                            <div class="flex flex-wrap gap-1.5">
                                <span v-if="vehicleSpecs.passengers" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">{{ vehicleSpecs.passengers }} Seats</span>
                                <span v-if="vehicleSpecs.transmission" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">{{ vehicleSpecs.transmission }}</span>
                                <span v-if="vehicleSpecs.airConditioning" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">AC</span>
                                <span v-if="vehicleSpecs.doors" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">{{ vehicleSpecs.doors }}</span>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-100"></div>

                            <!-- Price breakdown -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Car rental ({{ currentPackageLabel }})</span>
                                    <span class="text-base font-semibold text-gray-900" v-if="ratesReady">{{ formatRentalPrice(isLocautoRent ? locautoBaseTotal : (isOkMobility ? (currentProduct?.total || okMobilityBaseTotal) : (currentProduct?.total || 0))) }}</span>
                                    <span class="price-skeleton price-skeleton-sm" v-else></span>
                                </div>
                                <div v-if="isAdobeCars && adobeMandatoryProtection > 0" class="flex items-center justify-between text-amber-600">
                                    <span class="text-sm">Mandatory Liability (PLI)</span>
                                    <span class="text-base font-semibold" v-if="ratesReady">+{{ formatRentalPrice(adobeMandatoryProtection) }}</span>
                                    <span class="price-skeleton price-skeleton-sm" v-else></span>
                                </div>
                                <div v-for="item in getSelectedExtrasDetails" :key="item.id" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ item.name }}<span v-if="item.qty > 1"> &times; {{ item.qty }}</span></span>
                                    <span class="text-base font-semibold" :class="item.isFree ? 'text-emerald-600' : 'text-gray-900'">
                                        <span v-if="item.isFree">Free</span>
                                        <template v-else>
                                            <span v-if="ratesReady">+{{ formatRentalPrice(item.total) }}</span>
                                            <span class="price-skeleton price-skeleton-sm" v-else></span>
                                        </template>
                                    </span>
                                </div>
                            </div>

                            <!-- Dashed divider -->
                            <div class="border-t border-dashed border-gray-200"></div>

                            <!-- Grand total -->
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-gray-900">Grand Total</span>
                                <span class="text-2xl font-extrabold text-[#1e3a5f]" v-if="ratesReady">{{ formatPrice(grandTotal) }}</span>
                                <span class="price-skeleton price-skeleton-lg" v-else></span>
                            </div>

                            <!-- Payment split -->
                            <div v-if="effectivePaymentPercentage > 0" class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-emerald-800">Pay Now ({{ effectivePaymentPercentage }}%)</span>
                                    <span class="text-base font-bold text-emerald-700" v-if="ratesReady">{{ formatPrice(payableAmount) }}</span>
                                    <span class="price-skeleton price-skeleton-sm" v-else></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-600">Pay On Arrival</span>
                                    <span class="text-base font-semibold text-gray-700" v-if="ratesReady">{{ formatPrice(pendingAmount) }}</span>
                                    <span class="price-skeleton price-skeleton-sm" v-else></span>
                                </div>
                            </div>

                            <!-- Marquee -->
                            <div v-if="effectivePaymentPercentage > 0" class="overflow-hidden rounded-lg bg-[#1e3a5f]/5 py-1.5">
                                <div class="marquee-track flex whitespace-nowrap gap-8 text-[10px] font-semibold text-[#1e3a5f]/70">
                                    <span>Secure Checkout &bull; Best Price Guarantee &bull; Free Cancellation &bull; 24/7 Support &bull;</span>
                                    <span>Secure Checkout &bull; Best Price Guarantee &bull; Free Cancellation &bull; 24/7 Support &bull;</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="p-5 pt-0 space-y-3">
                            <!-- View Details -->
                            <button @click="showDetailsModal = true"
                                class="w-full text-sm py-2.5 rounded-xl border border-gray-200 font-semibold text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                View Booking Details
                            </button>
                            <!-- Proceed -->
                            <button @click="$emit('proceed-to-checkout', {
                                package: isLocautoRent ? (selectedLocautoProtections.length > 0 ? 'POA' : 'BAS') : selectedPackageType,
                                protection_code: isLocautoRent ? selectedLocautoProtections : (isAdobeCars ? getSelectedAdobeProtectionCodes() : null),
                                protection_amount: isLocautoRent && selectedLocautoProtections.length > 0
                                    ? selectedLocautoProtections.reduce((sum, code) => {
                                        const plan = locautoProtectionPlans.find(p => p.code === code);
                                        return sum + (plan ? parseFloat(plan.amount || 0) : 0);
                                    }, 0)
                                    : 0,
                                extras: selectedExtras,
                                detailedExtras: getSelectedExtrasDetails,
                                totals: {
                                    grandTotal,
                                    payableAmount,
                                    pendingAmount
                                },
                                vehicle_total: isLocautoRent ? locautoBaseTotal : (isOkMobility ? okMobilityBaseTotal : (currentProduct?.total || props.vehicle?.total_price || 0)),
                                selected_deposit_type: selectedDepositType || null,
                            })" class="btn-cta w-full py-3.5 rounded-xl bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white font-bold text-sm shadow-lg shadow-[#1e3a5f]/20 hover:shadow-xl hover:shadow-[#1e3a5f]/30 hover:-translate-y-0.5 transition-all duration-300 active:scale-[0.98]"
                                :disabled="!ratesReady || (availableDepositTypes.length > 1 && !selectedDepositType)" :class="{ 'opacity-60 cursor-not-allowed': !ratesReady }">
                                Proceed to Booking
                            </button>
                            <!-- Back -->
                            <button @click="$emit('back')"
                                class="w-full py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back to Results
                            </button>
                        </div>
                    </div>

                    <!-- ═══ TRUST INDICATORS ═══ -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <span class="text-[11px] font-semibold text-gray-600 leading-tight">Secure<br/>Checkout</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <span class="text-[11px] font-semibold text-gray-600 leading-tight">Best Price<br/>Guarantee</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <span class="text-[11px] font-semibold text-gray-600 leading-tight">Visa, MC<br/>Amex</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <span class="text-[11px] font-semibold text-gray-600 leading-tight">24/7<br/>Support</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDetailsModal" class="fixed inset-0 z-[100000] flex items-center justify-center p-4" @click.self="showDetailsModal = false">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="modal-content relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 bg-white border-b px-6 py-5 flex justify-between items-center rounded-t-3xl z-10">
                            <h2 class="text-2xl font-bold text-gray-900">Booking Details</h2>
                            <button @click="showDetailsModal = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-5">
                                <p class="text-sm text-gray-500 mb-2">Vehicle</p>
                                <p class="font-bold text-gray-900 text-lg">{{ displayVehicleName }}</p>
                                <p class="text-sm text-gray-500 mt-1">{{ currentPackageLabel }}</p>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Car Package ({{ currentPackageLabel }})</span>
                                    <span class="font-semibold text-gray-900">{{ formatRentalPrice(isOkMobility ? (currentProduct?.total || okMobilityBaseTotal) : (currentProduct?.total || 0)) }}</span>
                                </div>
                                <div v-if="isAdobeCars && adobeMandatoryProtection > 0" class="flex justify-between text-sm">
                                    <span class="text-amber-600">Mandatory Liability (PLI)</span>
                                    <span class="font-semibold text-amber-600">+{{ formatRentalPrice(adobeMandatoryProtection) }}</span>
                                </div>
                                <div v-for="item in getSelectedExtrasDetails" :key="item.id" class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ item.name }} <span v-if="item.qty > 1" class="text-xs text-gray-400">x{{ item.qty }}</span></span>
                                    <span class="font-semibold" :class="item.isFree ? 'text-green-600' : 'text-gray-800'">{{ item.isFree ? 'FREE' : formatRentalPrice(item.total) }}</span>
                                </div>
                            </div>
                            <hr class="border-gray-200">
                            <div class="space-y-3">
                                <div class="flex justify-between text-lg">
                                    <span class="font-bold text-gray-800">Grand Total</span>
                                    <span class="font-bold text-[#1e3a5f]">{{ formatPrice(grandTotal) }}</span>
                                </div>
                                <div class="flex justify-between text-sm bg-green-50 p-4 rounded-xl">
                                    <span class="font-semibold text-green-700">Pay Now ({{ effectivePaymentPercentage }}%)</span>
                                    <span class="font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm bg-amber-50 p-4 rounded-xl">
                                    <span class="font-semibold text-amber-700">Pay on Arrival</span>
                                    <span class="font-bold text-amber-700">{{ formatPrice(pendingAmount) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="sticky bottom-0 bg-white border-t px-6 py-5 rounded-b-3xl">
                            <button @click="showDetailsModal = false" class="btn-cta w-full py-4 rounded-xl bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white font-bold shadow-lg">Close</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Mobile Sticky Price Bar -->
        <Transition name="slide-up">
            <div v-if="!isSummaryVisible"
                class="fixed bottom-0 left-0 right-0 z-[100] bg-white/90 backdrop-blur-md border-t border-gray-100 p-4 lg:hidden shadow-[0_-10px_30px_rgba(0,0,0,0.08)]">
                <div class="flex items-center justify-between gap-4 max-w-lg mx-auto">
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Price</span>
                        <span class="text-xl font-bold text-gray-900">{{ formatPrice(grandTotal) }}</span>
                        <span v-if="effectivePaymentPercentage > 0" class="text-xs text-emerald-600 font-bold">
                            Pay Now {{ formatPrice(payableAmount) }} ({{ effectivePaymentPercentage }}%)
                        </span>
                    </div>
                    <button @click="scrollToSummary"
                        class="bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white px-5 py-3 rounded-xl font-bold text-sm shadow-lg active:scale-95 transition-transform">
                        View Summary &darr;
                    </button>
                </div>
            </div>
        </Transition>
    </div>

    <!-- Image Lightbox Modal -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showLightbox && lightboxImages.length" class="fixed inset-0 z-[10001] bg-black/90 flex items-center justify-center" @click.self="showLightbox = false">
                <div class="modal-content relative w-full h-full flex items-center justify-center">
                    <!-- Close -->
                    <button @click="showLightbox = false" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <!-- Counter -->
                    <div v-if="lightboxImages.length > 1" class="absolute top-4 left-4 z-10 bg-white/10 backdrop-blur text-white text-sm font-semibold px-3 py-1.5 rounded-full">
                        {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
                    </div>
                    <!-- Prev -->
                    <button v-if="lightboxImages.length > 1" @click="lightboxPrev" class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <!-- Image -->
                    <img :src="lightboxImages[lightboxIndex]" :alt="displayVehicleName" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl" />
                    <!-- Next -->
                    <button v-if="lightboxImages.length > 1" @click="lightboxNext" class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <!-- Dots -->
                    <div v-if="lightboxImages.length > 1" class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10 flex items-center gap-2">
                        <button v-for="(img, idx) in lightboxImages" :key="idx" @click="lightboxIndex = idx"
                            class="w-2.5 h-2.5 rounded-full transition-all duration-200"
                            :class="idx === lightboxIndex ? 'bg-white w-6' : 'bg-white/40 hover:bg-white/70'">
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Map Fullscreen Modal -->
    <Transition name="modal">
        <div v-if="showMapModal && hasVehicleCoords" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/60 px-4" @click.self="showMapModal = false">
            <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h3 class="text-base font-bold text-[#1e3a5f] flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Location Map
                    </h3>
                    <button @click="showMapModal = false" class="text-gray-400 hover:text-gray-700 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div ref="mapModalRef" class="w-full h-[60vh]"></div>
                <div v-if="isDifferentDropoff" class="px-5 py-2 border-t border-gray-100 flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pickup: {{ pickupLocation || locationName }}</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> Dropoff: {{ dropoffLocation }}</span>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Location Hours Modal -->
    <div v-if="showLocationHoursModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#1e3a5f]">Hours & Policies</h3>
                <button @click="showLocationHoursModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                <div v-if="locationOpeningHours.length" class="mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Opening Hours</p>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p v-for="(day, index) in locationOpeningHours" :key="`modal-open-${index}`">
                            <span class="font-medium text-gray-700">{{ day.name }}:</span>
                            <span class="ml-1">{{ formatHourWindow(day) || 'Closed' }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="locationOutOfHours.length" class="mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Out of Hours Dropoff</p>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p v-for="(day, index) in locationOutOfHours" :key="`modal-out-${index}`">
                            <span class="font-medium text-gray-700">{{ day.name }}:</span>
                            <span class="ml-1">{{ formatHourWindow(day) || 'Unavailable' }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="locationDaytimeClosures.length" class="mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Daytime Closures</p>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p v-for="(day, index) in locationDaytimeClosures" :key="`modal-closure-${index}`">
                            <span class="font-medium text-gray-700">{{ day.name }}:</span>
                            <span class="ml-1">{{ formatHourWindow(day) || 'None' }}</span>
                        </p>
                    </div>
                </div>
                <p v-if="locationDetails?.out_of_hours_charge" class="text-sm text-gray-600">
                    <span class="font-semibold text-gray-800">Out of Hours Charge:</span>
                    {{ locationDetails.out_of_hours_charge }}
                </p>
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet map marker pulse animation */
@keyframes marker-pulse {
    0% { transform: translate(-50%, -50%) scale(1); opacity: 0.25; }
    100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
}

/* Leaflet popup styling */
.rental-popup .leaflet-popup-content-wrapper {
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    border: none;
}
.rental-popup .leaflet-popup-tip {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.protection-desc :deep(a) {
    color: #1e3a5f;
    text-decoration: underline;
    font-weight: 500;
}
.protection-desc :deep(a:hover) {
    color: #2d5a8f;
}
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap');

.font-display {
    font-family: 'IBM Plex Sans', sans-serif;
}

/* ── Dashed timeline flow ── */
@keyframes dashed-flow {
    0%   { transform: translateY(-100%); opacity: 0; }
    20%  { opacity: 1; }
    80%  { opacity: 1; }
    100% { transform: translateY(500%); opacity: 0; }
}
.animate-dashed-flow {
    position: relative;
}
.animate-dashed-flow::before {
    content: '';
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(180deg, transparent, #1e3a5f, transparent);
    animation: dashed-flow 2s ease-in-out infinite;
}

/* ── Horizontal running dash (pickup→dropoff) ── */
@keyframes dash-run {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
.running-dash-h {
    position: absolute;
    top: 0;
    left: 0;
    width: 40%;
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, transparent, #059669, #e11d48, transparent);
    animation: dash-run 1.8s ease-in-out infinite;
}

/* ── Ripple on timeline dots ── */
@keyframes ripple {
    0%   { transform: scale(1); opacity: 0.5; }
    50%  { transform: scale(1.4); opacity: 0.2; }
    100% { transform: scale(1.8); opacity: 0; }
}
.ripple-ring {
    position: absolute; inset: -4px; border-radius: 9999px;
    border: 2px solid currentColor; animation: ripple 2.5s ease-out infinite;
}
.ripple-icon {
    position: relative;
}
.ripple-icon::after {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: inherit;
    border: 2px solid currentColor;
    animation: ripple 2s ease-out infinite;
}
.ripple-icon::before {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: inherit;
    border: 2px solid currentColor;
    animation: ripple 2s ease-out infinite;
    animation-delay: 1s;
}

/* ── Marquee ── */
@keyframes marquee {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.marquee-track {
    animation: marquee 22s linear infinite;
}
.marquee-text {
    animation: marquee 20s linear infinite;
    display: inline-block;
    padding-left: 100%;
}

/* ── Fade-in-up for stagger ── */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-in-up {
    animation: fadeInUp 0.45s ease-out both;
}
.benefit-item {
    opacity: 0;
    animation: fadeInUp 0.4s ease forwards;
}

/* ── Card hover lift ── */
.card-hover {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(30,58,95,0.08);
}

/* ── Radio pop ── */
@keyframes radioPop {
    0%   { transform: translate(-50%, -50%) scale(0); }
    50%  { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
.radio-dot {
    animation: radioPop 0.3s ease-out;
}

/* ── Radio Button Custom ── */
.radio-custom {
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}
.radio-custom.selected {
    border-color: #1e3a5f;
    background: #1e3a5f;
}
.radio-custom.selected::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 10px;
    height: 10px;
    background: white;
    border-radius: 50%;
    animation: radioPop 0.3s ease;
}

/* ── Checkbox Custom ── */
.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    flex-shrink: 0;
}
.checkbox-custom.selected {
    background: #1e3a5f;
    border-color: #1e3a5f;
}
.checkbox-custom.selected::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 14px;
    font-weight: bold;
}

/* ── Sticky Summary ── */
.sticky-summary {
    position: sticky;
    top: 5rem;
    transition: all 0.3s ease;
}
@media (max-width: 1024px) {
    .sticky-summary {
        position: relative;
    }
}

/* ── CTA shimmer ── */
@keyframes shimmer {
    0%   { left: -100%; }
    100% { left: 200%; }
}
.btn-cta {
    position: relative;
    overflow: hidden;
}
.btn-cta::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    animation: shimmer 3s ease-in-out infinite;
}

/* ── Button Animations ── */
.btn-primary {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8f 100%);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}
.btn-primary:hover::before {
    left: 100%;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(30, 58, 95, 0.3);
}
.btn-primary.is-loading {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}
.btn-secondary:hover {
    transform: translateY(-1px);
    background: #f3f4f6;
}

/* ── Provider badge ribbon ── */
.provider-ribbon {
    position: absolute; top: 16px; right: -6px; z-index: 10;
    padding: 4px 14px 4px 12px; font-size: 12px; font-weight: 600;
    color: #fff; border-radius: 4px 0 0 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.provider-ribbon::after {
    content: ''; position: absolute; right: 0; bottom: -6px;
    border-width: 3px 6px 3px 0; border-style: solid;
    border-color: transparent; border-right-color: rgba(0,0,0,0.15);
}

/* ── Plan/Package card ── */
.plan-card {
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    cursor: pointer;
}
.plan-card.selected {
    border-color: #1e3a5f;
    background: #f0f4f8;
    box-shadow: 0 0 0 2px #1e3a5f;
}

/* ── Package Card Styles (legacy compat) ── */
.package-card {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.package-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #1e3a5f;
    transform: scaleX(0);
    transition: transform 0.4s ease;
}
.package-card:hover::before {
    transform: scaleX(1);
}
.package-card.selected::before {
    transform: scaleX(1);
}

/* ── Included items checkmark ── */
.check-icon {
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}

/* ── Price skeleton ── */
.price-skeleton {
    display: inline-block;
    height: 16px;
    border-radius: 999px;
    background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
    background-size: 200% 100%;
    animation: shimmer-bg 1.4s ease-in-out infinite;
}
.price-skeleton-sm { width: 90px; }
.price-skeleton-md { width: 120px; height: 20px; }
.price-skeleton-lg { width: 160px; height: 26px; }

@keyframes shimmer-bg {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ── Extra Card ── */
.extra-card {
    transition: all 0.3s ease;
}
.extra-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* ── Info Card ── */
.info-card {
    position: relative;
    overflow: hidden;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-10%, -10%); }
}

/* ── Spec Icons ── */
.spec-icon {
    transition: all 0.3s ease;
}
.spec-icon:hover {
    transform: scale(1.05);
    background: #f0f9ff;
}

/* ── Icon Color Helper Classes ── */
.icon-bg-blue { background: linear-gradient(135deg, rgb(219 234 254) 0%, rgb(191 219 254) 100%); }
.icon-text-blue { color: rgb(37 99 235); }
.icon-bg-pink { background: linear-gradient(135deg, rgb(252 231 243) 0%, rgb(251 207 232) 100%); }
.icon-text-pink { color: rgb(219 39 119); }
.icon-bg-green { background: linear-gradient(135deg, rgb(220 252 231) 0%, rgb(187 247 208) 100%); }
.icon-text-green { color: rgb(22 163 74); }
.icon-bg-purple { background: linear-gradient(135deg, rgb(243 232 255) 0%, rgb(233 213 255) 100%); }
.icon-text-purple { color: rgb(147 51 234); }
.icon-bg-orange { background: linear-gradient(135deg, rgb(255 237 213) 0%, rgb(254 215 170) 100%); }
.icon-text-orange { color: rgb(249 115 22); }
.icon-bg-gray { background: linear-gradient(135deg, rgb(243 244 246) 0%, rgb(229 231 235) 100%); }
.icon-text-gray { color: rgb(71 85 105); }

/* ── Modal Transitions ── */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
}
.modal-enter-to .modal-content,
.modal-leave-from .modal-content {
    opacity: 1;
    transform: scale(1) translateY(0);
}

/* ── Slide Up Transition ── */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
</style>
