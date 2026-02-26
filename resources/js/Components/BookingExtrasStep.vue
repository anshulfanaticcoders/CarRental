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

onUnmounted(() => {
    if (vehicleMap) {
        vehicleMap.remove();
        vehicleMap = null;
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

// Selected LocautoRent protection plan (null = Basic)
// Initialize from prop if passed from car card
const selectedLocautoProtection = ref(props.initialProtectionCode || null);

// Watch for changes to initialProtectionCode prop
watch(() => props.initialProtectionCode, (newCode) => {
    selectedLocautoProtection.value = newCode || null;
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

    const packages = [];

    // 1. Basic Package (Base Rate)
    // Always present, acts as the base.
    packages.push({
        type: 'BAS',
        name: 'Basic Rental',
        subtitle: 'Base Rental',
        total: adobeBaseRate.value,
        deposit: 0,
        benefits: ['Base rental rate', 'Mandatory Liability (PLI) billed separately'],
        isBestValue: false,
        isAddOn: false
    });

    // 2. Protection Plans as Add-on Packages
    adobeProtectionPlans.value.filter(p => p.code !== 'PLI').forEach(plan => {
        packages.push({
            type: plan.code,
            name: plan.name,
            subtitle: 'Optional Protection',
            total: plan.amount, // Just the add-on price
            deposit: 0,
            benefits: [
                'Optional Extra Protection',
                plan.required ? 'Mandatory' : 'Click to Add'
            ],
            isBestValue: plan.code === 'SPP',
            isAddOn: true,
            extraId: `adobe_protection_${plan.code}`
        });
    });

    return packages;
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
        benefits: ['Base rental rate', 'Standard coverage'],
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
            benefits: features.length > 0 ? features : ['Custom vendor package'],
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
        benefits: ['Base rental rate', 'Standard coverage'],
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

const okMobilityPackages = computed(() => {
    if (!isOkMobility.value) return [];
    const benefits = ['Base rental rate'];
    if (props.vehicle?.mileage) {
        benefits.push(`Mileage: ${props.vehicle.mileage}`);
    }
    if (props.vehicle?.fuel_policy) {
        benefits.push(props.vehicle.fuel_policy);
    }
    const packages = [
        {
            type: 'BAS',
            name: 'Basic Rental',
            subtitle: 'Standard Package',
            total: okMobilityBaseTotal.value,
            deposit: 0,
            benefits,
            isBestValue: okMobilityCoverExtras.value.length === 0,
            isAddOn: false
        }
    ];

    okMobilityCoverExtras.value.forEach((extra) => {
        const extraTotal = getOkMobilityExtraTotal(extra);
        const coverBenefits = [
            ...benefits,
            extra.description || extra.name || 'Premium cover protection'
        ];
        packages.push({
            type: normalizeExtraCode(extra.code) || extra.code,
            name: extra.name,
            subtitle: 'Premium Cover',
            total: okMobilityBaseTotal.value + extraTotal,
            deposit: 0,
            benefits: coverBenefits,
            isBestValue: normalizeExtraCode(extra.code) === 'OPC',
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
        const protectionAmount = selectedLocautoProtection.value
            ? parseFloat(locautoProtectionPlans.value.find(p => p.code === selectedLocautoProtection.value)?.amount || 0) * props.numberOfDays
            : 0;
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

</script>

<template>
    <div>
        <div id="extras-breadcrumb-section" class=" px-0 md:p-6 pb-0">
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
                        <BreadcrumbPage>Booking Options</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </div>
        <div class="flex flex-col lg:flex-row gap-8 px-0 md:p-6">
            <!-- Left Column: Upgrades & Extras -->
            <div class="flex-1 space-y-8">
                <!-- Location Instructions -->
                <div v-if="locationInstructions"
                    class="info-card rounded-2xl p-6 flex items-start gap-4 shadow-lg relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-[#1e3a5f] to-[#2d5a8f]"></div>
                    <div class="absolute inset-0 opacity-10">
                        <div
                            class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-32 translate-x-32">
                        </div>
                    </div>
                    <div
                        class="relative z-10 w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="relative z-10 flex-1">
                        <h4 class="font-display text-xl font-bold mb-2 text-white">Pickup Instructions</h4>
                        <p class="text-sm text-white/90 leading-relaxed">{{ locationInstructions }}</p>
                    </div>
                </div>

                <!-- Vehicle / Pickup Location -->
                <div v-if="vehicleLocationText || hasVehicleCoords"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <MapPin class="w-5 h-5" :class="hasOneWayDropoff ? 'text-green-600' : 'text-[#1e3a5f]'" />
                        <h4 class="font-display text-xl font-bold text-gray-900">{{ vehicleLocationTitle }}</h4>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ vehicleLocationText || 'Location details unavailable.' }}
                    </p>
                    <div v-if="locationDetailLines.length" class="text-sm text-gray-600 space-y-1 mb-4">
                        <p v-for="(line, index) in locationDetailLines" :key="`location-line-${index}`">
                            {{ line }}
                        </p>
                    </div>
                    <div v-if="!isOkMobility && (locationContact.phone || locationContact.email || locationContact.iata || locationContact.whatsapp)"
                        class="text-sm text-gray-600 mb-4 space-y-1">
                        <p v-if="locationContact.phone"><span class="font-semibold text-gray-800">Phone:</span> {{
                            locationContact.phone }}</p>
                        <p v-if="locationContact.email"><span class="font-semibold text-gray-800">Email:</span> {{
                            locationContact.email }}</p>
                        <p v-if="locationContact.whatsapp"><span class="font-semibold text-gray-800">WhatsApp:</span> {{
                            locationContact.whatsapp }}</p>
                        <p v-if="locationContact.iata"><span class="font-semibold text-gray-800">Airport Code:</span> {{
                            locationContact.iata }}</p>
                    </div>
                    <div v-if="hasLocationHours" class="mt-4">
                        <button @click="showLocationHoursModal = true"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-[#1e3a5f] hover:bg-gray-50">
                            View hours & policies
                        </button>
                    </div>
                </div>

                <!-- Dropoff Location (only when different from pickup) -->
                <div v-if="hasOneWayDropoff"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <MapPin class="w-5 h-5 text-red-600" />
                        <h4 class="font-display text-xl font-bold text-gray-900">Dropoff Location</h4>
                    </div>
                    <p class="text-sm text-gray-600">{{ dropoffLocation }}</p>
                </div>

                <!-- Map -->
                <div v-if="hasVehicleCoords"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden relative">
                    <div v-if="isDifferentDropoff"
                        class="absolute top-3 left-3 z-[1000] flex items-center gap-3 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-1.5 shadow-sm text-xs font-medium text-gray-600">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pickup
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span> Dropoff
                        </span>
                    </div>
                    <div ref="vehicleMapRef"
                        :class="isDifferentDropoff ? 'h-72' : 'h-56'"
                        class="w-full"></div>
                </div>

                <div v-if="okMobilityInfoAvailable" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#1e3a5f]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="font-display text-xl font-bold text-gray-900">Vehicle Details</h4>
                    </div>

                    <div class="space-y-5 text-sm text-gray-600">
                        <div
                            v-if="okMobilityPickupStation || okMobilityPickupAddress || okMobilityDropoffStation || okMobilityDropoffAddress">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pickup &
                                Dropoff</p>
                            <div v-if="okMobilitySameLocation" class="space-y-1">
                                <p v-if="okMobilityPickupStation" class="font-semibold text-gray-900">{{
                                    okMobilityPickupStation }}</p>
                                <p v-if="okMobilityPickupAddress">{{ okMobilityPickupAddress }}</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div v-if="okMobilityPickupStation || okMobilityPickupAddress">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pickup
                                    </p>
                                    <p v-if="okMobilityPickupStation" class="font-semibold text-gray-900">{{
                                        okMobilityPickupStation }}</p>
                                    <p v-if="okMobilityPickupAddress">{{ okMobilityPickupAddress }}</p>
                                </div>
                                <div v-if="okMobilityDropoffStation || okMobilityDropoffAddress">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Dropoff
                                    </p>
                                    <p v-if="okMobilityDropoffStation" class="font-semibold text-gray-900">{{
                                        okMobilityDropoffStation }}</p>
                                    <p v-if="okMobilityDropoffAddress">{{ okMobilityDropoffAddress }}</p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="okMobilityTaxBreakdown.total || okMobilityTaxBreakdown.base || okMobilityTaxBreakdown.tax">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Taxes & Fees
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div v-if="okMobilityTaxBreakdown.base">
                                    <p class="text-xs text-gray-500">Before tax</p>
                                    <p class="font-semibold text-gray-900">{{
                                        formatRentalPrice(okMobilityTaxBreakdown.base) }}</p>
                                </div>
                                <div v-if="okMobilityTaxBreakdown.tax">
                                    <p class="text-xs text-gray-500">Tax{{ okMobilityTaxBreakdown.rate ? `
                                        (${okMobilityTaxBreakdown.rate}%)` : '' }}</p>
                                    <p class="font-semibold text-gray-900">{{
                                        formatRentalPrice(okMobilityTaxBreakdown.tax) }}</p>
                                </div>
                                <div v-if="okMobilityTaxBreakdown.total">
                                    <p class="text-xs text-gray-500">Total (incl. tax)</p>
                                    <p class="font-semibold text-gray-900">{{
                                        formatRentalPrice(okMobilityTaxBreakdown.total) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="okMobilityFuelPolicy">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Fuel Policy</p>
                            <p class="text-sm text-gray-600">{{ okMobilityFuelPolicy }}</p>
                        </div>

                        <div v-if="okMobilityCancellationSummary">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cancellation
                            </p>
                            <div v-if="!okMobilityCancellationSummary.available" class="text-sm text-gray-600">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">Not
                                    available</span>
                            </div>
                            <div v-else class="text-sm text-gray-600 space-y-2">
                                <p class="text-base font-semibold text-gray-900">
                                    {{ okMobilityCancellationSummary.amount && okMobilityCancellationSummary.amount > 0
                                        ? `Cancellation fee ${formatRentalPrice(okMobilityCancellationSummary.amount)}`
                                        : 'Free cancellation' }}
                                </p>
                                <p v-if="okMobilityCancellationSummary.deadline" class="text-sm text-gray-600">
                                    Cancel by <span class="font-semibold text-gray-900">{{
                                        okMobilityCancellationSummary.deadline }}</span>
                                </p>
                                <p v-if="okMobilityCancellationSummary.penalty" class="text-sm text-amber-700">
                                    Penalty applies after the deadline.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isRenteon && renteonHasOfficeDetails"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#1e3a5f]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="font-display text-xl font-bold text-gray-900">Pickup & Dropoff Details</h4>
                    </div>

                    <div class="space-y-5 text-sm text-gray-600">
                        <div v-if="renteonSameOffice">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pickup &
                                Dropoff</p>
                            <p v-if="renteonPickupOffice?.name" class="font-semibold text-gray-900">{{
                                renteonPickupOffice.name }}</p>
                            <p v-for="(line, index) in renteonPickupLines" :key="`renteon-pickup-line-${index}`">{{ line
                                }}</p>
                            <p v-if="renteonPickupOffice?.phone"><span class="font-semibold text-gray-800">Phone:</span>
                                {{ renteonPickupOffice.phone }}</p>
                            <p v-if="renteonPickupOffice?.email"><span class="font-semibold text-gray-800">Email:</span>
                                {{ renteonPickupOffice.email }}</p>
                            <p v-if="renteonPickupInstructions" class="text-xs text-gray-500 mt-2">{{
                                renteonPickupInstructions }}</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pickup</p>
                                <p v-if="renteonPickupOffice?.name" class="font-semibold text-gray-900">{{
                                    renteonPickupOffice.name }}</p>
                                <p v-for="(line, index) in renteonPickupLines" :key="`renteon-pickup-line-${index}`">{{
                                    line }}</p>
                                <p v-if="renteonPickupOffice?.phone"><span
                                        class="font-semibold text-gray-800">Phone:</span> {{ renteonPickupOffice.phone
                                        }}</p>
                                <p v-if="renteonPickupOffice?.email"><span
                                        class="font-semibold text-gray-800">Email:</span> {{ renteonPickupOffice.email
                                        }}</p>
                                <p v-if="renteonPickupInstructions" class="text-xs text-gray-500 mt-2">{{
                                    renteonPickupInstructions }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Dropoff</p>
                                <p v-if="renteonDropoffOffice?.name" class="font-semibold text-gray-900">{{
                                    renteonDropoffOffice.name }}</p>
                                <p v-for="(line, index) in renteonDropoffLines" :key="`renteon-dropoff-line-${index}`">
                                    {{ line }}</p>
                                <p v-if="renteonDropoffOffice?.phone"><span
                                        class="font-semibold text-gray-800">Phone:</span> {{ renteonDropoffOffice.phone
                                        }}</p>
                                <p v-if="renteonDropoffOffice?.email"><span
                                        class="font-semibold text-gray-800">Email:</span> {{ renteonDropoffOffice.email
                                        }}</p>
                                <p v-if="renteonDropoffInstructions" class="text-xs text-gray-500 mt-2">{{
                                    renteonDropoffInstructions }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isRenteon && hasRenteonTaxBreakdown"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#1e3a5f]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="font-display text-xl font-bold text-gray-900">Taxes & Fees</h4>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div v-if="renteonTaxBreakdown.net !== null">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Base (Net)</p>
                            <p class="font-semibold text-gray-900">{{ formatPrice(renteonTaxBreakdown.net) }}</p>
                        </div>
                        <div v-if="renteonTaxBreakdown.vat !== null">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">VAT</p>
                            <p class="font-semibold text-gray-900">{{ formatPrice(renteonTaxBreakdown.vat) }}</p>
                        </div>
                        <div v-if="renteonTaxBreakdown.gross !== null">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Total (Incl. Tax)</p>
                            <p class="font-semibold text-gray-900">{{ formatPrice(renteonTaxBreakdown.gross) }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Supplier totals shown; your final price in the summary includes our booking fee.
                    </p>
                </div>

                <div v-if="driverRequirementItems.length || mileageTypeLabel"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#1e3a5f]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 10a4 4 0 11-6 0m1 10h4m-4 0a2 2 0 01-2-2v-1a4 4 0 014-4h2a4 4 0 014 4v1a2 2 0 01-2 2m-6 0h6" />
                        </svg>
                        <h4 class="font-display text-xl font-bold text-gray-900">Driver Requirements</h4>
                    </div>
                    <p v-if="mileageTypeLabel" class="text-sm text-gray-600 mb-3">
                        <span class="font-semibold text-gray-800">Mileage type:</span> {{ mileageTypeLabel }}
                    </p>
                    <ul v-if="driverRequirementItems.length" class="text-sm text-gray-600 space-y-2">
                        <li v-for="item in driverRequirementItems" :key="item" class="flex items-start gap-2">
                            <span class="mt-0.5 w-2 h-2 rounded-full bg-[#1e3a5f]"></span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">Requirement details unavailable for this location.</p>
                </div>

                <div v-if="terms && terms.length" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#1e3a5f]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h4 class="font-display text-xl font-bold text-gray-900">Terms & Conditions</h4>
                    </div>
                    <div v-for="(category, index) in terms" :key="`term-${index}`" class="mb-4">
                        <p class="font-semibold text-gray-800 mb-2">{{ category.name }}</p>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li v-for="(condition, conditionIndex) in category.conditions"
                                :key="`term-${index}-${conditionIndex}`">
                                {{ condition }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Vendor Info & Guidelines (Internal Only) -->
                <section v-if="isInternal" class="mb-8 space-y-6">
                    <!-- 1. Vendor Information Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm"
                        v-if="vehicle.vendor?.profile || vehicle.vendorProfile || vehicle.vendor_profile">
                        <h3 class="font-display text-xl font-bold text-gray-900 border-b border-gray-100 pb-4 mb-6">
                            Rental Information
                        </h3>
                        <div class="flex items-start gap-4">
                            <img :src="(vehicle.vendor?.profile?.avatar || vehicle.vendorProfile?.avatar || vehicle.vendor_profile?.avatar) ? (vehicle.vendor?.profile?.avatar || vehicle.vendorProfile?.avatar || vehicle.vendor_profile?.avatar) : '/images/default-avatar.png'"
                                alt="Vendor"
                                class="w-16 h-16 rounded-full object-cover border border-gray-200 shadow-sm flex-shrink-0"
                                @error="$event.target.src = '/images/default-avatar.png'" />
                            <div>
                                <h4 class="font-bold text-gray-900 text-xl leading-tight">
                                    {{ vehicle.vendorProfileData?.company_name ||
                                        vehicle.vendor_profile_data?.company_name
                                        || vehicle.vendor?.profile?.company_name || vehicle.vendorProfile?.company_name ||
                                        vehicle.vendor_profile?.company_name || 'Vendor' }}
                                </h4>
                                <p class="text-base text-gray-500 mt-1"
                                    v-if="vehicle?.vendor?.profile?.about || vehicle?.vendorProfile?.about || vehicle?.vendor_profile?.about">
                                    {{ vehicle?.vendor?.profile?.about || vehicle?.vendorProfile?.about ||
                                        vehicle?.vendor_profile?.about }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Guidelines Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm" v-if="vehicle?.guidelines">
                        <h5
                            class="text-base font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#1e3a5f]" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Guidelines
                        </h5>
                        <p
                            class="text-base text-gray-600 leading-relaxed bg-gray-50 p-5 rounded-lg border border-gray-100 whitespace-pre-line">
                            {{ vehicle?.guidelines }}
                        </p>
                    </div>

                    <!-- 3. Benefits & Policy Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm"
                        v-if="(vehicle && vehicle.benefits) || (vehicle && vehicle.security_deposit > 0)">
                        <h5
                            class="text-base font-bold text-gray-900 uppercase tracking-wider mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#1e3a5f]" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Benefits & Policy
                        </h5>

                        <!-- Deposit & Security Section -->
                        <div v-if="vehicle?.security_deposit > 0 || vehicle?.benefits?.deposit_amount || vehicle?.benefits?.excess_amount"
                            class="mb-8 border-b border-gray-100 pb-6">

                            <!-- Deposit Amount + Excess in a compact row -->
                            <div class="grid grid-cols-2 gap-4 mb-5">
                                <div v-if="vehicle?.security_deposit > 0">
                                    <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Security Deposit</h5>
                                    <p class="text-xl font-bold text-gray-900">{{ formatPrice(vehicle?.security_deposit) }}</p>
                                </div>
                                <div v-if="vehicle?.benefits?.deposit_amount && !vehicle?.security_deposit">
                                    <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Deposit Amount</h5>
                                    <p class="text-xl font-bold text-gray-900">{{ formatPrice(vehicle?.benefits?.deposit_amount) }}</p>
                                    <p v-if="vehicle?.benefits?.deposit_currency" class="text-xs text-gray-500">Currency: {{ vehicle?.benefits?.deposit_currency }}</p>
                                </div>
                                <div v-if="vehicle?.benefits?.excess_amount">
                                    <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Excess Amount</h5>
                                    <p class="text-xl font-bold text-gray-900">{{ formatPrice(vehicle?.benefits?.excess_amount) }}</p>
                                </div>
                            </div>

                            <!-- Deposit Type Selector - prominent with clear context -->
                            <div v-if="availableDepositTypes.length > 1"
                                class="rounded-xl border-2 p-4 transition-colors"
                                :class="selectedDepositType ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-300 bg-amber-50'">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                        :class="selectedDepositType ? 'bg-emerald-100' : 'bg-amber-100'">
                                        <svg v-if="selectedDepositType" class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <svg v-else class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-bold text-gray-900">How would you like to pay the deposit at pickup?</h5>
                                        <p class="text-xs text-gray-500 mt-0.5">The vendor requires a deposit of <strong>{{ formatPrice(vehicle?.security_deposit || vehicle?.benefits?.deposit_amount) }}</strong> when you collect the vehicle. Please select your preferred payment method.</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="dtype in availableDepositTypes"
                                        :key="dtype"
                                        class="relative cursor-pointer"
                                    >
                                        <input type="radio" :value="dtype" v-model="selectedDepositType" class="sr-only peer" />
                                        <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all cursor-pointer
                                            peer-checked:border-[#1e3a5f] peer-checked:bg-[#1e3a5f] peer-checked:text-white peer-checked:shadow-lg
                                            border-gray-200 bg-white text-gray-700 hover:border-[#1e3a5f]/40 hover:shadow-sm">
                                            <svg v-if="selectedDepositType === dtype" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            <span class="w-4 h-4 rounded-full border-2 border-gray-300" v-else></span>
                                            {{ formatPaymentMethod(dtype) }}
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Single deposit method - just show it as info -->
                            <div v-else-if="availableDepositTypes.length === 1"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-50 border border-blue-100">
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <p class="text-sm text-blue-700">Deposit payable via <strong>{{ formatPaymentMethod(availableDepositTypes[0]) }}</strong> at pickup</p>
                            </div>
                        </div>

                        <!-- Benefits Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                            <div v-if="vehicle?.benefits?.excess_theft_amount" class="flex items-start gap-3">
                                <div class="mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 11c0 2.761-2.239 5-5 5-2.761 0-5-2.239-5-5 0-2.761 2.239-5 5-5 2.761 0 5 2.239 5 5zm0 0c0 2.761 2.239 5 5 5 2.761 0 5-2.239 5-5 0-2.761-2.239-5-5-5-2.761 0-5 2.239-5 5zm0 0v12" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-base font-bold text-gray-900">Excess Theft</span>
                                    <span class="text-sm text-gray-500 block mt-0.5">{{
                                        formatPrice(vehicle?.benefits?.excess_theft_amount) }}</span>
                                </div>
                            </div>

                            <div v-if="vehicle?.benefits?.maximum_driver_age" class="flex items-start gap-3">
                                <div class="mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14c3.866 0 7 1.343 7 3v3H5v-3c0-1.657 3.134-3 7-3zm0-2a4 4 0 100-8 4 4 0 000 8z" />
                                    </svg>
                                </div>
                                <span class="text-base font-medium text-gray-900 mt-1">Max. Driver Age: {{
                                    vehicle?.benefits?.maximum_driver_age }}</span>
                            </div>

                            <!-- Cancellation -->
                            <div class="flex items-start gap-3">
                                <div class="mt-1">
                                    <svg v-if="vehicle?.benefits?.cancellation_available_per_day == 1"
                                        xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <template v-if="vehicle?.benefits?.cancellation_available_per_day == 1">
                                        <span class="block text-base font-bold text-gray-900">Free Cancellation</span>
                                        <span v-if="cancellationDeadline"
                                            class="text-sm text-gray-500 block mt-0.5">Until
                                            {{ cancellationDeadline }}</span>
                                    </template>
                                    <template v-else>
                                        <span class="block text-base font-medium text-gray-900">Cancellation Policy
                                            Applies</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Mileage -->
                            <div class="flex items-start gap-3">
                                <div class="mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <span v-if="vehicle?.benefits?.limited_km_per_day == 1">
                                        <span class="block text-base font-bold text-gray-900">Limited: {{
                                            vehicle?.benefits?.limited_km_per_day_range
                                            }} km/day</span>
                                        <span v-if="vehicle?.benefits?.price_per_km_per_day"
                                            class="text-base font-semibold text-gray-700 block mt-0.5">
                                            +{{ formatPrice(vehicle?.benefits?.price_per_km_per_day) }}/km
                                        </span>
                                    </span>
                                    <span v-else class="block text-base font-bold text-gray-900">Unlimited
                                        Mileage</span>
                                </div>
                            </div>

                            <!-- Min Age -->
                            <div v-if="vehicle?.benefits?.minimum_driver_age" class="flex items-start gap-3">
                                <div class="mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span class="text-base font-medium text-gray-900 mt-1">Min. Driver Age: {{
                                    vehicle?.benefits?.minimum_driver_age
                                    }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 1. Package Upgrade Section -->
                <section v-if="!isLocautoRent" id="extras-package-section">
                    <!-- GreenMotion/USave: Package Selection -->
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Choose Your Package</h2>
                        <p class="text-gray-600">Select the perfect package for your journey</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-for="pkg in availablePackages" :key="pkg.type"
                            @click="isAdobeCars && pkg.isAddOn ? toggleAdobeProtection(pkg) : selectPackage(pkg.type)"
                            class="package-card bg-white rounded-2xl p-6 border-2 cursor-pointer transition-all relative"
                            :class="(isAdobeCars && pkg.isAddOn ? isAdobeProtectionSelected(pkg) : selectedPackageType === pkg.type) ? 'selected border-[#1e3a5f] shadow-xl' : 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg'">
                            <!-- Popular Badge -->
                            <div v-if="pkg.type === 'PMP' || pkg.isBestValue"
                                class="absolute top-0 right-0 bg-gradient-to-r from-yellow-400 to-yellow-500 text-xs font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl text-white uppercase tracking-wide">
                                {{ pkg.isBestValue ? 'Recommended' : 'Popular' }}
                            </div>

                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-sm font-bold text-gray-900 block tracking-wide">{{
                                        pkg.name || getPackageDisplayName(pkg.type) }}</span>
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">{{
                                        pkg.subtitle || getPackageSubtitle(pkg.type) }}</span>
                                </div>
                                <div v-if="isAdobeCars && pkg.isAddOn" class="checkbox-custom"
                                    :class="{ selected: isAdobeProtectionSelected(pkg) }"></div>
                                <div v-else class="radio-custom" :class="{ selected: selectedPackageType === pkg.type }">
                                </div>
                            </div>

                            <!-- Price -->
                            <div class="mb-4 pb-4 border-b border-gray-100">
                                <div class="flex items-baseline gap-1 mb-2">
                                    <span class="text-3xl font-bold"
                                        :class="selectedPackageType === pkg.type ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatRentalPrice(pkg.total / numberOfDays) }}
                                    </span>
                                    <span class="text-sm text-gray-500">/day</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total:</span>
                                    <span class="text-lg font-bold"
                                        :class="selectedPackageType === pkg.type ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatRentalPrice(pkg.total) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Benefits List -->
                            <ul class="space-y-2.5">
                                <li v-for="(benefit, idx) in getBenefits(pkg)" :key="idx"
                                    class="benefit-item flex items-start gap-2.5 text-sm"
                                    :style="{ animationDelay: `${idx * 0.05}s` }">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span class="leading-snug"
                                        :class="isKeyBenefit(benefit) ? 'font-bold text-gray-900' : 'text-gray-600'">{{
                                            benefit }}</span>
                                </li>
                                <li v-if="pkg.deposit" class="benefit-item text-sm flex items-start gap-2.5">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span
                                        :class="isKeyBenefit('Deposit') ? 'font-bold text-gray-900' : 'text-gray-600'">Deposit:
                                        {{ formatPrice(pkg.deposit) }}</span>
                                </li>
                                <li v-if="pkg.excess" class="benefit-item text-sm flex items-start gap-2.5">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span
                                        :class="isKeyBenefit('Excess') ? 'font-bold text-gray-900' : 'text-gray-600'">Excess:
                                        {{ formatPrice(pkg.excess) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section v-if="isRenteon && (renteonIncludedServices.length || renteonDriverPolicy)"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-4">Renteon Highlights</h3>
                    <div class="space-y-4">
                        <div v-if="renteonIncludedServices.length">
                            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Included
                                Services</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="service in renteonIncludedServices" :key="service.id"
                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-100">
                                    {{ service.name }}<span v-if="service.quantity_included" class="ml-1">(x{{
                                        service.quantity_included
                                        }})</span>
                                </span>
                            </div>
                        </div>

                        <div v-if="renteonDriverPolicy">
                            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Driver Age
                                Policy</p>
                            <p class="text-sm text-gray-600">
                                <span v-if="renteonDriverPolicy.minAge">Minimum age: {{ renteonDriverPolicy.minAge
                                    }}</span>
                                <span v-if="renteonDriverPolicy.maxAge" class="ml-4">Maximum age: {{
                                    renteonDriverPolicy.maxAge
                                    }}</span>
                                <span v-if="renteonDriverPolicy.youngFrom" class="ml-4">Young driver: {{
                                    renteonDriverPolicy.youngFrom
                                    }}-{{ renteonDriverPolicy.youngTo || 'N/A' }}</span>
                                <span v-if="renteonDriverPolicy.seniorFrom" class="ml-4">Senior driver: {{
                                    renteonDriverPolicy.seniorFrom }}-{{ renteonDriverPolicy.seniorTo || 'N/A' }}</span>
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="isRenteon && (vehicle?.benefits?.deposit_amount || vehicle?.benefits?.excess_amount || vehicle?.benefits?.excess_theft_amount)"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-4">Deposit & Excess</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div v-if="vehicle?.benefits?.deposit_amount">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Deposit</p>
                            <p class="font-semibold text-gray-900">{{ formatPrice(vehicle?.benefits?.deposit_amount) }}
                            </p>
                            <p v-if="vehicle?.benefits?.deposit_currency" class="text-xs text-gray-500">Currency: {{
                                vehicle?.benefits?.deposit_currency }}</p>
                        </div>
                        <div v-if="vehicle?.benefits?.excess_amount">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Excess</p>
                            <p class="font-semibold text-gray-900">{{ formatPrice(vehicle?.benefits?.excess_amount) }}
                            </p>
                        </div>
                        <div v-if="vehicle?.benefits?.excess_theft_amount">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Excess Theft</p>
                            <p class="font-semibold text-gray-900">{{
                                formatPrice(vehicle?.benefits?.excess_theft_amount) }}</p>
                        </div>
                    </div>
                </section>

                <!-- LocautoRent: Protection Plans Section -->
                <section v-if="isLocautoRent && locautoProtectionPlans.length > 0" id="extras-package-section">
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Choose Your Protection Plan</h2>
                        <p class="text-gray-600">Select the coverage that suits your needs</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Basic Plan -->
                        <div @click="selectedLocautoProtection = null"
                            class="package-card bg-white rounded-2xl p-6 border-2 cursor-pointer transition-all"
                            :class="!selectedLocautoProtection ? 'selected border-[#1e3a5f] shadow-xl' : 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg'">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-sm font-bold text-gray-900 block tracking-wide">Basic</span>
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">Standard</span>
                                </div>
                                <div class="radio-custom" :class="{ selected: !selectedLocautoProtection }"></div>
                            </div>

                            <div class="mb-4 pb-4 border-b border-gray-100">
                                <div class="flex items-baseline gap-1 mb-2">
                                    <span class="text-3xl font-bold"
                                        :class="!selectedLocautoProtection ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatRentalPrice(locautoBaseDaily) }}
                                    </span>
                                    <span class="text-sm text-gray-500">/day</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total:</span>
                                    <span class="text-lg font-bold"
                                        :class="!selectedLocautoProtection ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatRentalPrice(locautoBaseTotal) }}
                                    </span>
                                </div>
                            </div>

                            <ul class="space-y-2.5">
                                <li class="benefit-item flex items-start gap-2.5 text-sm">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span class="leading-snug text-gray-600">Standard protection included</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Protection Plans -->
                        <div v-for="protection in locautoProtectionPlans" :key="protection.code"
                            @click="selectedLocautoProtection = protection.code"
                            class="package-card bg-white rounded-2xl p-6 border-2 cursor-pointer transition-all"
                            :class="selectedLocautoProtection === protection.code ? 'selected border-[#1e3a5f] shadow-xl' : 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg'">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-sm font-bold text-gray-900 block tracking-wide">{{
                                        getShortProtectionName(protection.description) }}</span>
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">Protection</span>
                                </div>
                                <div class="radio-custom"
                                    :class="{ selected: selectedLocautoProtection === protection.code }"></div>
                            </div>

                            <div class="mb-4 pb-4 border-b border-gray-100">
                                <div class="flex items-baseline gap-1 mb-2">
                                    <span class="text-3xl font-bold"
                                        :class="selectedLocautoProtection === protection.code ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatRentalPrice(locautoBaseDaily + protection.amount) }}
                                    </span>
                                    <span class="text-sm text-gray-500">/day</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total:</span>
                                    <span class="text-lg font-bold"
                                        :class="selectedLocautoProtection === protection.code ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatRentalPrice(locautoBaseTotal + (protection.amount * numberOfDays)) }}
                                    </span>
                                </div>
                            </div>

                            <ul class="space-y-2.5">
                                <li class="benefit-item flex items-start gap-2.5 text-sm">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span class="leading-snug text-gray-600">{{ protection.description }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section v-if="(isFavrica || isXDrive) && providerInsuranceOptions.length > 0"
                    id="extras-insurance-section">
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Insurance Packages</h2>
                        <p class="text-gray-600">Select the coverage that suits your needs</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template v-for="extra in providerInsuranceOptions" :key="extra.id">
                            <div @click="toggleExtra(extra)"
                                class="extra-card bg-white rounded-2xl p-4 border-2 cursor-pointer transition-all"
                                :class="{ 'selected border-[#1e3a5f] bg-gradient-to-br from-blue-50 to-blue-100': selectedExtras[extra.id], 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg': !selectedExtras[extra.id], 'opacity-80 cursor-not-allowed': extra.required }">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="checkbox-custom flex-shrink-0"
                                            :class="{ selected: !!selectedExtras[extra.id] }"></div>
                                        <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="getIconBackgroundClass(getProviderExtraLabel(extra))">
                                            <component :is="getExtraIcon(getProviderExtraLabel(extra))" class="w-5 h-5"
                                                :class="getIconColorClass(getProviderExtraLabel(extra))" />
                                        </div>
                                        <div class="flex items-center justify-between w-full">
                                            <h4 class="font-bold text-gray-900 text-[1rem]">{{ getProviderExtraLabel(extra) }}</h4>
                                            <span v-if="extra.required"
                                                class="text-[0.65rem] uppercase tracking-wide font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Required</span>
                                        </div>
                                    </div>

                                    <p v-if="extra.description" class="text-xs text-gray-500 pl-8">
                                        {{ extra.description }}
                                    </p>
                                    <p v-if="extra.excess !== null && extra.excess !== undefined"
                                        class="text-xs text-gray-500 pl-8">
                                        Excess: <span class="font-semibold text-gray-700">{{ formatPrice(extra.excess) }}</span>
                                    </p>

                                    <p v-if="extra.description" class="text-xs text-gray-500 pl-8">
                                        {{ extra.description }}
                                    </p>

                                    <div class="flex items-center justify-between pl-8 mt-auto">
                                        <div v-if="extra.numberAllowed && extra.numberAllowed > 1"
                                            class="flex items-center gap-2">
                                            <button type="button"
                                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                                                @click.stop="updateExtraQuantity(extra, -1)"
                                                :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">
                                                -
                                            </button>
                                            <span class="text-sm font-semibold text-gray-700">{{
                                                selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                                            <button type="button"
                                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                                                @click.stop="updateExtraQuantity(extra, 1)"
                                                :disabled="selectedExtras[extra.id] >= extra.numberAllowed">
                                                +
                                            </button>
                                        </div>
                                        <div class="text-right ml-auto">
                                            <template v-if="isSicilyByCar">
                                                <span class="text-base font-bold text-gray-900">{{ formatRentalPrice(getExtraPerDay(extra)) }}</span>
                                                <p class="text-xs text-gray-400">Per Day</p>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Total: <span class="font-semibold text-gray-800">{{ formatRentalPrice(getExtraTotal(extra)) }}</span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-base font-bold text-gray-900">
                                                    {{ formatRentalPrice(extra.total_for_booking !== undefined &&
                                                        extra.total_for_booking !== null
                                                        ? extra.total_for_booking
                                                        : (extra.daily_rate !== undefined ? extra.daily_rate :
                                                            (extra.price / numberOfDays))) }}
                                                </span>
                                                <p class="text-xs text-gray-400">{{ extra.total_for_booking !== undefined &&
                                                    extra.total_for_booking !== null ? 'Total' : 'Per Day' }}</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section v-if="isSicilyByCar && sicilyByCarIncludedServices.length > 0"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-2">Included Services</h3>
                    <p class="text-gray-600 mb-4">These items are included in your base rate.</p>
                    <ul class="space-y-2">
                        <li v-for="service in sicilyByCarIncludedServices" :key="service.id"
                            class="flex items-start gap-2 text-sm text-gray-700">
                            <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                            <div>
                                <span>{{ service.description || service.id }}</span>
                                <div v-if="service.excess !== undefined && service.excess !== null" class="text-xs text-gray-500 mt-1">
                                    Excess: <span class="font-semibold text-gray-700">{{ formatPrice(service.excess) }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section v-if="isRecordGo && recordGoIncludedComplements.length > 0"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-2">Included Coverage</h3>
                    <p class="text-gray-600 mb-4">Coverage and services included in the selected plan.</p>
                    <ul class="space-y-3">
                        <li v-for="service in recordGoIncludedComplements" :key="service.complementId"
                            class="text-sm text-gray-700">
                            <div class="font-semibold text-gray-900">{{ stripHtml(service.complementName) }}</div>
                            <p v-if="service.complementDescription" class="text-sm text-gray-600">{{ stripHtml(service.complementDescription) }}</p>
                            <div v-if="service['preauth&Excess'] && service['preauth&Excess'].length" class="text-sm text-gray-600 mt-1">
                                <span v-for="(item, idx) in service['preauth&Excess']" :key="idx" class="mr-3">
                                    {{ item.type }}: <span class="font-semibold text-gray-700">{{ formatPrice(item.value) }}</span>
                                </span>
                            </div>
                        </li>
                    </ul>
                </section>

                <section v-if="isRecordGo && recordGoAutomaticComplements.length > 0"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-2">Automatic Supplements</h3>
                    <p class="text-gray-600 mb-4">These charges apply automatically based on booking conditions.</p>
                    <ul class="space-y-3">
                        <li v-for="service in recordGoAutomaticComplements" :key="service.complementId"
                            class="text-sm text-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ stripHtml(service.complementName) }}</div>
                                    <p v-if="service.complementDescription" class="text-sm text-gray-600">{{ stripHtml(service.complementDescription) }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ formatRentalPrice(service.priceTaxIncDayDiscount ?? service.priceTaxIncDay ?? service.priceTaxIncComplement ?? 0) }}
                                    </div>
                                    <div class="text-sm text-gray-600">Per Day</div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section v-if="isSicilyByCar && sicilyByCarProtectionPlans.length > 0" id="sbc-insurance-section">
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Protection Plans</h2>
                        <p class="text-gray-600">Choose the protection that suits your trip</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template v-for="extra in sicilyByCarProtectionPlans" :key="extra.id">
                            <div @click="toggleExtra(extra)"
                                class="extra-card bg-white rounded-2xl p-4 border-2 cursor-pointer transition-all"
                                :class="{ 'selected border-[#1e3a5f] bg-gradient-to-br from-blue-50 to-blue-100': selectedExtras[extra.id], 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg': !selectedExtras[extra.id], 'opacity-80 cursor-not-allowed': extra.required }">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="checkbox-custom flex-shrink-0"
                                            :class="{ selected: !!selectedExtras[extra.id] }"></div>
                                        <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="getIconBackgroundClass(getProviderExtraLabel(extra))">
                                            <component :is="getExtraIcon(getProviderExtraLabel(extra))" class="w-5 h-5"
                                                :class="getIconColorClass(getProviderExtraLabel(extra))" />
                                        </div>
                                        <div class="flex items-center justify-between w-full">
                                            <h4 class="font-bold text-gray-900 text-[1rem]">{{ getProviderExtraLabel(extra) }}</h4>
                                            <span v-if="extra.required"
                                                class="text-[0.65rem] uppercase tracking-wide font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Required</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pl-8 mt-auto">
                                        <div class="text-right ml-auto">
                                            <span class="text-base font-bold text-gray-900">
                                                {{ formatRentalPrice(getExtraPerDay(extra)) }}
                                            </span>
                                            <p class="text-xs text-gray-400">Per Day</p>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Total: <span class="font-semibold text-gray-800">{{ formatRentalPrice(getExtraTotal(extra)) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section v-if="isRenteon && renteonProtectionPlans.length > 0" id="renteon-insurance-section">
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Protection Plans</h2>
                        <p class="text-gray-600">Choose the protection that suits your trip</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template v-for="extra in renteonProtectionPlans" :key="extra.id">
                            <div @click="toggleExtra(extra)"
                                class="extra-card bg-white rounded-2xl p-4 border-2 cursor-pointer transition-all"
                                :class="{ 'selected border-[#1e3a5f] bg-gradient-to-br from-blue-50 to-blue-100': selectedExtras[extra.id], 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg': !selectedExtras[extra.id], 'opacity-80 cursor-not-allowed': extra.required }">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="checkbox-custom flex-shrink-0"
                                            :class="{ selected: !!selectedExtras[extra.id] }"></div>
                                        <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="getIconBackgroundClass(getProviderExtraLabel(extra))">
                                            <component :is="getExtraIcon(getProviderExtraLabel(extra))" class="w-5 h-5"
                                                :class="getIconColorClass(getProviderExtraLabel(extra))" />
                                        </div>
                                        <div class="flex items-center justify-between w-full">
                                            <h4 class="font-bold text-gray-900 text-[1rem]">{{ getProviderExtraLabel(extra) }}</h4>
                                            <span v-if="extra.required"
                                                class="text-[0.65rem] uppercase tracking-wide font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Required</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pl-8 mt-auto">
                                        <div v-if="extra.numberAllowed && extra.numberAllowed > 1"
                                            class="flex items-center gap-2">
                                            <button type="button"
                                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                                                @click.stop="updateExtraQuantity(extra, -1)"
                                                :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">
                                                -
                                            </button>
                                            <span class="text-sm font-semibold text-gray-700">{{
                                                selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                                            <button type="button"
                                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                                                @click.stop="updateExtraQuantity(extra, 1)"
                                                :disabled="selectedExtras[extra.id] >= extra.numberAllowed">
                                                +
                                            </button>
                                        </div>
                                        <div class="text-right ml-auto">
                                            <span class="text-base font-bold text-gray-900">
                                                {{ formatRentalPrice(extra.total_for_booking !== undefined &&
                                                    extra.total_for_booking !== null
                                                    ? extra.total_for_booking
                                                    : (extra.daily_rate !== undefined ? extra.daily_rate :
                                                        (extra.price / numberOfDays))) }}
                                            </span>
                                            <p class="text-xs text-gray-400">{{ extra.total_for_booking !== undefined &&
                                                extra.total_for_booking !== null ? 'Total' : 'Per Day' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
                <section v-else-if="isRenteon" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-2">Protection Plans</h3>
                    <p class="text-gray-600">No protection plans were provided by the supplier for this offer.</p>
                </section>

                <!-- 2. Extras Section -->
                <section
                    v-if="(isGreenMotion && greenMotionExtras.length > 0) || (!isGreenMotion && !isFavrica && !isXDrive && !isSurprice && optionalExtras && optionalExtras.length > 0) || (isLocautoRent && locautoOptionalExtras.length > 0) || (isAdobeCars && adobeOptionalExtras.length > 0) || (isInternal && internalOptionalExtras.length > 0) || (isRenteon && renteonOptionalExtras.length > 0) || (isOkMobility && okMobilityOptionalExtras.length > 0) || (isSicilyByCar && sicilyByCarOptionalExtras.length > 0) || (isRecordGo && recordGoOptionalExtras.length > 0) || (isSurprice && surpriceOptionalExtras.length > 0) || ((isFavrica || isXDrive) && providerOptionalExtras.length > 0)">
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">{{ (isFavrica || isXDrive)?'AdditionalServices': 'Optional Extras' }}</h2>
                        <p class="text-gray-600">{{ (isFavrica || isXDrive) ? 'Add helpful services to your booking': 'Enhance your journey with these add - ons' }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template
                            v-for="extra in (isLocautoRent ? locautoOptionalExtras : (isAdobeCars ? adobeOptionalExtras : (isInternal ? internalOptionalExtras : (isRenteon ? renteonOptionalExtras : (isOkMobility ? okMobilityOptionalExtras : (isSicilyByCar ? sicilyByCarOptionalExtras : (isRecordGo ? recordGoOptionalExtras : (isSurprice ? surpriceOptionalExtras : ((isFavrica || isXDrive) ? providerOptionalExtras : (isGreenMotion ? greenMotionExtras : optionalExtras))))))))))"
                            :key="extra.id">
                            <div v-if="!extra.isHidden" @click="toggleExtra(extra)"
                                class="extra-card bg-white rounded-2xl p-4 border-2 cursor-pointer transition-all"
                                :class="{ 'selected border-[#1e3a5f] bg-gradient-to-br from-blue-50 to-blue-100': selectedExtras[extra.id], 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg': !selectedExtras[extra.id], 'opacity-80 cursor-not-allowed': extra.required }">
                                <div class="flex flex-col gap-3">
                                    <!-- Top Row: Checkbox + Icon + Title -->
                                    <div class="flex items-center gap-3">
                                        <div class="checkbox-custom flex-shrink-0"
                                            :class="{ selected: !!selectedExtras[extra.id] }"></div>
                                        <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="getIconBackgroundClass(getProviderExtraLabel(extra))">
                                            <component :is="getExtraIcon(getProviderExtraLabel(extra))" class="w-5 h-5"
                                                :class="getIconColorClass(getProviderExtraLabel(extra))" />
                                        </div>
                                        <div class="flex items-center justify-between w-full">
                                            <h4 class="font-bold text-gray-900 text-[1rem]">{{ getProviderExtraLabel(extra) }}</h4>
                                            <span v-if="extra.required"
                                                class="text-[0.65rem] uppercase tracking-wide font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Required</span>
                                        </div>
                                    </div>

                                    <p v-if="(isSicilyByCar || isRecordGo) && extra.description" class="text-sm text-gray-600 pl-8">
                                        {{ extra.description }}
                                    </p>

                                    <!-- Bottom: Price -->
                                    <div class="flex items-center justify-between pl-8 mt-auto">
                                        <div v-if="extra.numberAllowed && extra.numberAllowed > 1"
                                            class="flex items-center gap-2">
                                            <button type="button"
                                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                                                @click.stop="updateExtraQuantity(extra, -1)"
                                                :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">-</button>
                                            <span class="text-sm font-semibold text-gray-700">{{
                                                selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                                            <button type="button"
                                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                                                @click.stop="updateExtraQuantity(extra, 1)"
                                                :disabled="selectedExtras[extra.id] >= extra.numberAllowed">+</button>
                                        </div>
                                        <div class="text-right ml-auto">
                                            <template v-if="isSicilyByCar">
                                                <span class="text-base font-bold text-gray-900">{{ formatRentalPrice(getExtraPerDay(extra)) }}</span>
                                                <span class="text-xs text-gray-500 block">Per Day</span>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Total: <span class="font-semibold text-gray-800">{{ formatRentalPrice(getExtraTotal(extra)) }}</span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-base font-bold text-gray-900">
                                                    {{ formatRentalPrice(extra.total_for_booking !== undefined &&
                                                        extra.total_for_booking !== null
                                                        ? extra.total_for_booking
                                                        : (extra.daily_rate !== undefined ? extra.daily_rate :
                                                            (extra.price / numberOfDays))) }}
                                                </span>
                                                <span class="text-xs text-gray-500 block">
                                                    {{ (extra.total_for_booking !== undefined && extra.total_for_booking !==
                                                        null) ? '/booking' : '/day' }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <!-- Right Column: Sticky Summary -->
            <div class="lg:w-96 xl:w-[420px]" ref="summarySection">
                <div
                    class="sticky-summary bg-white rounded-3xl shadow-2xl border border-gray-100 p-6 relative overflow-hidden">
                    <h3 class="font-display text-2xl font-bold text-gray-900 mb-6 pb-4 border-b">Booking Summary</h3>
                    <span v-if="providerBadge"
                        class="pointer-events-none absolute top-6 right-[-72px] rotate-45 w-56 text-center py-1.5 text-[11px] font-extrabold uppercase tracking-widest shadow-lg z-10"
                        :class="providerBadge.ribbonClassName" :title="providerBadge.label">
                        {{ providerBadge.label }}
                    </span>

                    <!-- Car Details -->
                    <div class="flex flex-col gap-4 mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-28 h-20 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                                <img v-if="vehicleImage" :src="vehicleImage" alt="Car"
                                    class="w-full h-full object-cover" />
                                <svg v-else class="w-full h-full p-3 text-gray-400" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="font-display font-bold text-gray-900 text-xl">{{ displayVehicleName }}</div>
                                <div class="text-sm text-gray-500 mb-3">{{ vehicle?.category || 'Economy' }}</div>
                            </div>
                        </div>

                        <!-- Location Timeline -->
                        <div class="relative pl-4">
                            <!-- Animated Dotted Line -->
                            <div class="absolute left-[40px] top-10 bottom-10 w-0.5 flex flex-col">
                                <div class="flex-1 border-l-2 border-dotted border-gray-300"></div>
                                <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                                    <div class="w-full h-2 animate-dashed-flow"></div>
                                </div>
                            </div>

                            <!-- Pickup -->
                            <div class="flex items-start gap-4 mb-8 relative">
                                <div class="relative flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-full flex items-center justify-center border-2 border-emerald-200 shadow-lg shadow-emerald-100/50 relative overflow-hidden ripple-icon"
                                        style="color: rgb(5, 150, 105);">
                                        <div
                                            class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-teal-400/10">
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-6 h-6 text-emerald-600 relative z-10" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <span
                                        class="text-xs font-bold text-emerald-700 uppercase tracking-wider block mb-1">Pickup</span>
                                    <div class="font-bold text-gray-900 text-sm md:text-base leading-snug">
                                        {{ pickupLocation || locationName }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1.5 font-medium flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ pickupDate }}
                                        <span class="text-gray-300">|</span>
                                        {{ pickupTime }}
                                    </div>
                                </div>
                            </div>

                            <!-- Dropoff -->
                            <div class="flex items-start gap-4 relative">
                                <div class="relative flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-rose-50 to-pink-50 rounded-full flex items-center justify-center border-2 border-rose-200 shadow-lg shadow-rose-100/50 relative overflow-hidden ripple-icon"
                                        style="color: rgb(225, 29, 72);">
                                        <div class="absolute inset-0 bg-gradient-to-br from-rose-400/10 to-pink-400/10">
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-6 h-6 text-rose-600 relative z-10" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <span
                                        class="text-xs font-bold text-rose-700 uppercase tracking-wider block mb-1">Dropoff</span>
                                    <div class="font-bold text-gray-900 text-sm md:text-base leading-snug">
                                        {{ dropoffLocation || pickupLocation || locationName }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1.5 font-medium flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ dropoffDate }}
                                        <span class="text-gray-300">|</span>
                                        {{ dropoffTime }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Specs Icons -->
                    <div class="flex flex-wrap gap-3 mb-6 pb-6 border-b border-gray-100">
                        <!-- Luggage -->
                        <div v-if="vehicleSpecs.bagDisplay"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>{{ vehicleSpecs.bagDisplay }}</span>
                        </div>
                        <!-- Passengers -->
                        <div v-if="vehicleSpecs.passengers"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <img :src="seatingIcon" class="w-4 h-4" alt="Seats" />
                            <span>{{ vehicleSpecs.passengers }}</span>
                        </div>
                        <!-- Transmission -->
                        <div v-if="vehicleSpecs.transmission"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <img :src="transmissionIcon" class="w-4 h-4" alt="Trans" />
                            <span class="whitespace-nowrap">{{ vehicleSpecs.transmission }}</span>
                        </div>
                        <!-- Fuel -->
                        <div v-if="vehicleSpecs.fuel"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <img :src="fuelIcon" class="w-4 h-4" alt="Fuel" />
                            <span>{{ vehicleSpecs.fuel }}</span>
                        </div>
                        <!-- SIPP/ACRISS -->
                        <div v-if="vehicleSpecs.acriss"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <span class="uppercase tracking-wide">SIPP</span>
                            <span class="font-semibold text-gray-900">{{ vehicleSpecs.acriss }}</span>
                        </div>
                        <!-- AC -->
                        <div v-if="vehicleSpecs.airConditioning"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <img :src="acIcon" class="w-4 h-4" alt="AC" />
                            <span>AC</span>
                        </div>
                        <!-- Doors -->
                        <div v-if="vehicleSpecs.doors"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <img :src="doorIcon" class="w-4 h-4" alt="Doors" />
                            <span>{{ vehicleSpecs.doors }}</span>
                        </div>
                        <!-- Mileage -->
                        <div v-if="currentProduct?.mileage"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                            <component :is="Gauge" class="w-4 h-4" />
                            <span>{{ currentProduct.mileage }}</span>
                        </div>
                        <!-- CO2 -->
                        <div v-if="vehicleSpecs.co2"
                            class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-green-50 rounded-lg text-xs font-medium text-green-700 transition-all hover:gap-2">
                            <component :is="Leaf" class="w-4 h-4 text-green-600" />
                            <span>{{ vehicleSpecs.co2 }} g/km</span>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="space-y-3 text-sm text-gray-700 mb-6 pb-6 border-b border-gray-100">
                        <div class="flex justify-between">
                            <span>Car Package ({{ currentPackageLabel }})</span>
                            <span class="font-medium" v-if="ratesReady">{{ formatRentalPrice(isLocautoRent ?
                                locautoBaseTotal :
                                (isOkMobility ? (currentProduct?.total || okMobilityBaseTotal) : (currentProduct?.total
                                    || 0)))
                            }}</span>
                            <span class="price-skeleton price-skeleton-sm" v-else></span>
                        </div>
                        <div v-if="isAdobeCars && adobeMandatoryProtection > 0"
                            class="flex justify-between text-amber-600">
                            <span>Mandatory Liability (PLI)</span>
                            <span class="font-medium" v-if="ratesReady">+{{ formatRentalPrice(adobeMandatoryProtection)
                                }}</span>
                            <span class="price-skeleton price-skeleton-sm" v-else></span>
                        </div>
                        <!-- Selected Extras List -->
                        <div v-for="item in getSelectedExtrasDetails" :key="item.id"
                            class="flex justify-between text-blue-600">
                            <div>
                                <span>{{ item.name }} <span v-if="item.qty > 1">x{{ item.qty }}</span></span>
                                <div v-if="item.excess !== null && item.excess !== undefined" class="text-xs text-gray-500">
                                    Excess: <span class="font-semibold text-gray-700">{{ formatPrice(item.excess) }}</span>
                                </div>
                            </div>
                            <span class="font-medium">
                                <span v-if="item.isFree" class="text-green-600 font-bold">Free</span>
                                <template v-else>
                                    <span v-if="ratesReady">+{{ formatRentalPrice(item.total) }}</span>
                                    <span class="price-skeleton price-skeleton-sm" v-else></span>
                                </template>
                            </span>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center border-t pt-4 mb-3">
                        <span class="text-lg font-bold text-gray-800">Total</span>
                        <span class="text-3xl font-bold text-[#1e3a5f]" v-if="ratesReady">{{ formatPrice(grandTotal)
                            }}</span>
                        <span class="price-skeleton price-skeleton-lg" v-else></span>
                    </div>

                    <!-- Payable Amount -->
                    <div v-if="effectivePaymentPercentage > 0"
                        class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-4 mb-3">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-green-800">Pay Now ({{ effectivePaymentPercentage }}%)</span>
                            <span class="text-2xl font-bold text-green-700" v-if="ratesReady">{{
                                formatPrice(payableAmount)
                                }}</span>
                            <span class="price-skeleton price-skeleton-md" v-else></span>
                        </div>
                    </div>

                    <!-- Running Text -->
                    <div v-if="effectivePaymentPercentage > 0" class="overflow-hidden rounded-xl mb-6"
                        style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8f 100%);">
                        <p class="py-3 whitespace-nowrap marquee-text text-sm font-medium text-white">
                            💳 Pay {{ effectivePaymentPercentage }}% now and rest pay on arrival &nbsp;•&nbsp; 💳 Pay {{
                                effectivePaymentPercentage
                            }}% now and rest pay on arrival &nbsp;•&nbsp; 💳 Pay {{ effectivePaymentPercentage }}% now
                            and rest pay
                            on
                            arrival &nbsp;•&nbsp;
                        </p>
                    </div>

                    <!-- View Booking Details Button -->
                    <button @click="showDetailsModal = true"
                        class="w-full text-sm py-3 mb-4 rounded-xl border-2 font-semibold transition-all flex items-center justify-center gap-2 border-[#1e3a5f] text-[#1e3a5f] hover:bg-[#1e3a5f]/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        View Booking Details
                    </button>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <button @click="$emit('proceed-to-checkout', {
                            package: isLocautoRent ? (selectedLocautoProtection ? 'POA' : 'BAS') : selectedPackageType,
                            protection_code: isLocautoRent ? selectedLocautoProtection : (isAdobeCars ? getSelectedAdobeProtectionCodes() : null),
                            protection_amount: isLocautoRent && selectedLocautoProtection
                                ? locautoProtectionPlans.find(p => p.code === selectedLocautoProtection)?.amount || 0
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
                        })" class="btn-primary w-full py-4 rounded-xl text-white font-bold text-lg shadow-lg"
                            :disabled="!ratesReady || (availableDepositTypes.length > 1 && !selectedDepositType)" :class="{ 'is-loading': !ratesReady }">
                            Proceed to Booking
                        </button>
                        <button @click="$emit('back')"
                            class="btn-secondary w-full py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition-all">
                            Back to Results
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDetailsModal" class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                    @click.self="showDetailsModal = false">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <!-- Modal Content -->
                    <div
                        class="modal-content relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <!-- Header -->
                        <div
                            class="sticky top-0 bg-white border-b px-6 py-5 flex justify-between items-center rounded-t-3xl z-10">
                            <h2 class="font-display text-2xl font-bold text-gray-900">Booking Details</h2>
                            <button @click="showDetailsModal = false"
                                class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-5">
                            <!-- Vehicle Info -->
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-5">
                                <p class="text-sm text-gray-500 mb-2">Vehicle</p>
                                <p class="font-bold text-gray-900 text-lg">{{ displayVehicleName }}</p>
                                <p class="text-sm text-gray-500 mt-1">{{ currentPackageLabel }}</p>
                            </div>

                            <!-- Line Items -->
                            <div class="space-y-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Car Package ({{ currentPackageLabel }})</span>
                                    <span class="font-semibold text-gray-900">{{ formatRentalPrice(isOkMobility ?
                                        (currentProduct?.total || okMobilityBaseTotal) : (currentProduct?.total || 0))
                                        }}</span>
                                </div>
                                <div v-if="isAdobeCars && adobeMandatoryProtection > 0"
                                    class="flex justify-between text-sm">
                                    <span class="text-amber-600">Mandatory Liability (PLI)</span>
                                    <span class="font-semibold text-amber-600">+{{
                                        formatRentalPrice(adobeMandatoryProtection)
                                        }}</span>
                                </div>
                                <div v-for="item in getSelectedExtrasDetails" :key="item.id"
                                    class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ item.name }} <span v-if="item.qty > 1"
                                            class="text-xs text-gray-400">x{{ item.qty }}</span></span>
                                    <span class="font-semibold"
                                        :class="item.isFree ? 'text-green-600' : 'text-gray-800'">
                                        {{ item.isFree ? 'FREE' : formatRentalPrice(item.total) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Divider -->
                            <hr class="border-gray-200">

                            <!-- Totals -->
                            <div class="space-y-3">
                                <div class="flex justify-between text-lg">
                                    <span class="font-bold text-gray-800">Grand Total</span>
                                    <span class="font-bold text-[#1e3a5f]">{{ formatPrice(grandTotal) }}</span>
                                </div>
                                <div class="flex justify-between text-sm bg-green-50 p-4 rounded-xl">
                                    <span class="font-semibold text-green-700">Pay Now ({{ effectivePaymentPercentage
                                        }}%)</span>
                                    <span class="font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm bg-amber-50 p-4 rounded-xl">
                                    <span class="font-semibold text-amber-700">Pay on Arrival</span>
                                    <span class="font-bold text-amber-700">{{ formatPrice(pendingAmount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="sticky bottom-0 bg-white border-t px-6 py-5 rounded-b-3xl">
                            <button @click="showDetailsModal = false"
                                class="btn-primary w-full py-4 rounded-xl text-white font-bold shadow-lg">
                                Close
                            </button>
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
                        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Price</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl font-bold text-gray-900">{{ formatPrice(grandTotal) }}</span>
                        </div>
                        <span v-if="effectivePaymentPercentage > 0" class="text-[10px] text-emerald-600 font-bold">
                            Pay {{ formatPrice(payableAmount) }} Now ({{ effectivePaymentPercentage }}%)
                        </span>
                    </div>
                    <button @click="scrollToSummary"
                        class="bg-[#1e3a5f] text-white px-5 py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-100/50 active:scale-95 transition-all flex items-center gap-2">
                        View Summary
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </div>

    <div v-if="showLocationHoursModal"
        class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#1e3a5f]">Hours & Policies</h3>
                <button @click="showLocationHoursModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
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
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.font-display {
    font-family: 'Outfit', sans-serif;
}

/* Package Card Styles */
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

/* Radio Button Custom */
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

@keyframes radioPop {
    0% {
        transform: translate(-50%, -50%) scale(0);
    }

    50% {
        transform: translate(-50%, -50%) scale(1.2);
    }

    100% {
        transform: translate(-50%, -50%) scale(1);
    }
}

.price-skeleton {
    display: inline-block;
    height: 16px;
    border-radius: 999px;
    background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}

.price-skeleton-sm {
    width: 90px;
}

.price-skeleton-md {
    width: 120px;
    height: 20px;
}

.price-skeleton-lg {
    width: 160px;
    height: 26px;
}

.btn-primary.is-loading {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}


/* Marquee Animation */
@keyframes marquee {
    0% {
        transform: translateX(0);
    }

    100% {
        transform: translateX(-50%);
    }
}

.marquee-text {
    animation: marquee 20s linear infinite;
    display: inline-block;
    padding-left: 100%;
}

/* Sticky Summary */
.sticky-summary {
    position: sticky;
    top: 2rem;
    transition: all 0.3s ease;
}

@media (max-width: 1024px) {
    .sticky-summary {
        position: relative;
        margin-top: 0rem;
    }
}

/* Button Animations */
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

.btn-secondary:hover {
    transform: translateY(-1px);
    background: #f3f4f6;
}

/* Benefit Item Animation */
.benefit-item {
    opacity: 0;
    animation: fadeInUp 0.4s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Info Card */
.info-card {
    position: relative;
    overflow: hidden;
}



@keyframes float {

    0%,
    100% {
        transform: translate(0, 0);
    }

    50% {
        transform: translate(-10%, -10%);
    }
}

/* Spec Icons */
.spec-icon {
    transition: all 0.3s ease;
}

.spec-icon:hover {
    transform: scale(1.05);
    background: #f0f9ff;
}

/* Extra Card */
.extra-card {
    transition: all 0.3s ease;
}

.extra-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Checkbox Custom */
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

/* Modal Transitions */
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

/* Icon Color Helper Classes */
.icon-bg-blue {
    background: linear-gradient(135deg, rgb(219 234 254) 0%, rgb(191 219 254) 100%);
}

.icon-text-blue {
    color: rgb(37 99 235);
}

.icon-bg-pink {
    background: linear-gradient(135deg, rgb(252 231 243) 0%, rgb(251 207 232) 100%);
}

.icon-text-pink {
    color: rgb(219 39 119);
}

.icon-bg-green {
    background: linear-gradient(135deg, rgb(220 252 231) 0%, rgb(187 247 208) 100%);
}

.icon-text-green {
    color: rgb(22 163 74);
}

.icon-bg-purple {
    background: linear-gradient(135deg, rgb(243 232 255) 0%, rgb(233 213 255) 100%);
}

.icon-text-purple {
    color: rgb(147 51 234);
}

.icon-bg-orange {
    background: linear-gradient(135deg, rgb(255 237 213) 0%, rgb(254 215 170) 100%);
}

.icon-text-orange {
    color: rgb(249 115 22);
}

.icon-bg-gray {
    background: linear-gradient(135deg, rgb(243 244 246) 0%, rgb(229 231 235) 100%);
}

.icon-text-gray {
    color: rgb(71 85 105);
}

/* Dashed Line Flow Animation */
@keyframes dashed-flow {
    0% {
        transform: translateY(-100%);
        opacity: 0;
    }

    10% {
        opacity: 1;
    }

    90% {
        opacity: 1;
    }

    100% {
        transform: translateY(500%);
        opacity: 0;
    }
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

/* Ripple Effect */
@keyframes ripple {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }

    50% {
        transform: scale(1.3);
        opacity: 0.3;
    }

    100% {
        transform: scale(1.6);
        opacity: 0;
    }
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

/* Slide Up Transition */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
