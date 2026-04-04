<script setup>
import { ref, computed, watch, watchEffect, onMounted, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import { resolveProviderMarkupRate, toProviderGrossMultiplier } from '@/utils/platformPricing';
import { expandSicilyByCarSelectedExtras } from '@/utils/sicilyByCarExtras';
import {
    getSearchVehicleLegacyPayload,
    resolveSearchVehicleDisplayName,
    resolveSearchVehicleImage,
    resolveSearchVehicleProviderLabel,
    resolveSearchVehicleSpecs,
} from '@/features/search/utils/searchVehiclePresentation';
import check from "../../../assets/Check.svg";
import carIcon from "../../../assets/carIcon.svg";
import fuelIcon from "../../../assets/fuel.svg";
import transmissionIcon from "../../../assets/transmittionIcon.svg";
import seatingIcon from "../../../assets/travellerIcon.svg";
import doorIcon from "../../../assets/door.svg";
import acIcon from "../../../assets/ac.svg";
import {
    MapPin, Wifi, Baby, Snowflake, UserPlus, Shield, Plus,
    Navigation, CircleDashed, Smartphone, Gauge, Leaf
} from "lucide-vue-next";
import {
    Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList,
    BreadcrumbPage, BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';

// ── Composables & Adapters ──────────────────────────────────────────
import { useProviderAdapter } from './composables/useProviderAdapter';
import { useExtrasState } from './composables/useExtrasState';
import { useVehicleMap } from './composables/useVehicleMap';
import { usePricingCalculation, stripHtml } from './composables/usePricingCalculation';

// ── Section Components ──────────────────────────────────────────────
import VehicleHero from './sections/VehicleHero.vue';
import RentalTimeline from './sections/RentalTimeline.vue';
import LocationDetails from './sections/LocationDetails.vue';
import IncludedItems from './sections/IncludedItems.vue';
import DepositExcess from './sections/DepositExcess.vue';
import PackageSelection from './sections/PackageSelection.vue';
import ProtectionPlans from './sections/ProtectionPlans.vue';
import OptionalExtras from './sections/OptionalExtras.vue';
import ProviderHighlights from './sections/ProviderHighlights.vue';
import ProviderNotes from './sections/ProviderNotes.vue';
import DriverRequirements from './sections/DriverRequirements.vue';
import TermsConditions from './sections/TermsConditions.vue';
import BookingSummary from './summary/BookingSummary.vue';
import MobileStickyBar from './summary/MobileStickyBar.vue';
import DetailsModal from './modals/DetailsModal.vue';
import ImageLightbox from './modals/ImageLightbox.vue';
import MapModal from './modals/MapModal.vue';
import LocationHoursModal from './modals/LocationHoursModal.vue';

// ── Props & Emits ───────────────────────────────────────────────────
const props = defineProps({
    vehicle: Object,
    initialPackage: String,
    initialProtectionCode: String,
    optionalExtras: { type: Array, default: () => [] },
    currencySymbol: { type: String, default: '€' },
    locationName: String,
    pickupLocation: String,
    dropoffLocation: String,
    dropoffLatitude: { type: [String, Number], default: null },
    dropoffLongitude: { type: [String, Number], default: null },
    locationInstructions: String,
    locationDetails: { type: Object, default: null },
    driverRequirements: { type: Object, default: null },
    terms: { type: Array, default: null },
    pickupDate: String,
    pickupTime: String,
    dropoffDate: String,
    dropoffTime: String,
    numberOfDays: { type: Number, default: 1 },
    paymentPercentage: { type: Number, default: 0 },
    searchSessionId: String,
    priceMap: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['back', 'proceed-to-checkout']);

// ── Page-level values ───────────────────────────────────────────────
const page = usePage();
const providerMarkupRate = computed(() => resolveProviderMarkupRate(page.props));
const providerGrossMultiplier = computed(() => toProviderGrossMultiplier(providerMarkupRate.value));

// ── Provider adapter ────────────────────────────────────────────────
const {
    adapter, source,
    isAdobeCars, isLocautoRent, isInternal, isRenteon, isOkMobility,
    isSicilyByCar, isRecordGo, isSurprice, isFavrica, isXDrive, isEmr, isGreenMotion, isClick2Rent,
} = useProviderAdapter(props, {
    formatPrice: (val) => formatPrice(val),
    currentPackage: computed(() => currentPackage.value),
    stripHtml,
});

// ── Extras state ────────────────────────────────────────────────────
const { selectedExtras, isRequiredExtra, getMaxQuantity, setExtraQuantity, updateExtraQuantity, toggleExtra } = useExtrasState();

// ── Map ─────────────────────────────────────────────────────────────
const {
    vehicleMapRef, mapModalRef, showMapModal, hasVehicleCoords, hasDropoffCoords,
    isDifferentDropoff, initVehicleMap, createMapIcon,
} = useVehicleMap(props);

// ── Currency + exchange rates ───────────────────────────────────────
const { fetchExchangeRates, exchangeRates, loading } = useCurrencyConversion();
const ratesReady = computed(() => !!exchangeRates.value && !loading.value);

// ── UI state ────────────────────────────────────────────────────────
const summarySection = ref(null);
const isSummaryVisible = ref(false);
const scrollToSummary = () => { summarySection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
const currentPackage = ref(props.initialPackage || 'BAS');
const showDetailsModal = ref(false);
const showLocationHoursModal = ref(false);
const showLightbox = ref(false);
const lightboxIndex = ref(0);
const showProviderNotes = ref(true);
const vehicleHeroRef = ref(null);
const mapModalCompRef = ref(null);

// ── Deposit type (internal vehicles) ────────────────────────────────
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
    } catch { methods = [method]; }
    return methods.filter(Boolean);
});
watch(availableDepositTypes, (types) => {
    if (types.length === 1 && !selectedDepositType.value) selectedDepositType.value = types[0];
}, { immediate: true });

// ── Package selection ───────────────────────────────────────────────
const packageOrder = ['BAS', 'PLU', 'PRE', 'PMP'];

watch(() => props.initialPackage, (newPackage) => { currentPackage.value = newPackage || 'BAS'; });

// Unified packages from adapter
const availablePackages = computed(() => {
    const adapterPkgs = adapter.packages?.value ?? [];
    if (adapterPkgs.length > 0) return adapterPkgs;
    // Fallback for default/green motion
    if (!props.vehicle?.products) return [];
    return packageOrder.map(type => props.vehicle.products.find(p => p.type === type)).filter(Boolean);
});

const selectedPackageType = computed(() => {
    const packages = availablePackages.value;
    if (!packages.length) return 'BAS';
    if (packages.some(pkg => pkg.type === currentPackage.value)) return currentPackage.value;
    return packages[0].type;
});

watch(availablePackages, (packages) => {
    if (!packages.length) return;
    if (!packages.some(pkg => pkg.type === currentPackage.value)) currentPackage.value = packages[0].type;
}, { immediate: true });

const currentProduct = computed(() => {
    if (isAdobeCars.value) return adapter.packages?.value?.find(p => p.type === selectedPackageType.value);
    return availablePackages.value.find(p => p.type === selectedPackageType.value);
});

// ── Locauto-specific refs (delegated to adapter) ────────────────────
const selectedLocautoProtections = adapter.selectedLocautoProtections ?? ref([]);
const locautoProtectionPlans = adapter.locautoProtectionPlans ?? computed(() => []);
const toggleLocautoProtection = adapter.toggleLocautoProtection ?? (() => {});
const locautoSmartCoverPlan = adapter.locautoSmartCoverPlan ?? computed(() => null);
const locautoDontWorryPlan = adapter.locautoDontWorryPlan ?? computed(() => null);
const locautoBaseTotal = adapter.locautoBaseDaily ? computed(() => adapter.baseTotal?.value ?? 0) : computed(() => 0);
const locautoBaseDaily = adapter.locautoBaseDaily ?? computed(() => 0);
watch(() => props.initialProtectionCode, (newCode) => {
    if (adapter.selectedLocautoProtections) {
        adapter.selectedLocautoProtections.value = newCode ? [newCode] : [];
    }
});

// ── Pricing ─────────────────────────────────────────────────────────
const {
    pricingCurrency, vehicleTotalCurrency, formatPrice, formatRentalPrice,
    getExtraTotal, getExtraPerDay, getSelectedSicilyByCarExtraTotal,
    extrasTotal, netGrandTotal, bookingChargeBreakdown,
    grandTotal, payableAmount, pendingAmount, effectivePaymentPercentage,
} = usePricingCalculation({
    props,
    selectedExtras,
    adapter,
    currentProduct,
    providerMarkupRate,
    providerGrossMultiplier,
    selectedLocautoProtections,
    locautoProtectionPlans,
});

// ── Vehicle image / carousel ────────────────────────────────────────
const vehicleImage = computed(() => {
    return resolveSearchVehicleImage(props.vehicle);
});

const heroImageIndex = ref(0);
const allHeroImages = computed(() => {
    const legacyPayload = getSearchVehicleLegacyPayload(props.vehicle);
    if (!isInternal.value || !legacyPayload?.images) return [];
    const primary = legacyPayload.images.find(img => img.image_type === 'primary');
    const gallery = legacyPayload.images.filter(img => img.image_type === 'gallery') || [];
    const images = [];
    if (primary) images.push(primary.image_url);
    gallery.forEach(img => images.push(img.image_url));
    return images;
});
const hasMultipleImages = computed(() => allHeroImages.value.length > 1);
const currentHeroImage = computed(() => allHeroImages.value[heroImageIndex.value] || vehicleImage.value);
const heroNextImage = () => { if (hasMultipleImages.value) heroImageIndex.value = (heroImageIndex.value + 1) % allHeroImages.value.length; };
const heroPrevImage = () => { if (hasMultipleImages.value) heroImageIndex.value = (heroImageIndex.value - 1 + allHeroImages.value.length) % allHeroImages.value.length; };
const lightboxImages = computed(() => hasMultipleImages.value ? allHeroImages.value : (vehicleImage.value ? [vehicleImage.value] : []));
const lightboxNext = () => { lightboxIndex.value = (lightboxIndex.value + 1) % lightboxImages.value.length; };
const lightboxPrev = () => { lightboxIndex.value = (lightboxIndex.value - 1 + lightboxImages.value.length) % lightboxImages.value.length; };
watch(showLightbox, (open) => { if (open) lightboxIndex.value = hasMultipleImages.value ? heroImageIndex.value : 0; });

// ── Display name ────────────────────────────────────────────────────
const displayVehicleName = computed(() => resolveSearchVehicleDisplayName(props.vehicle));

// ── Provider badge ──────────────────────────────────────────────────
const resolveInternalProviderLabel = () => {
    return resolveSearchVehicleProviderLabel(props.vehicle) || 'Vrooem';
};
const toTitleCase = (value) => `${value}`.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
const resolveRenteonSubProvider = () => {
    const code = (props.vehicle?.provider_code || '').toLowerCase();
    const names = {
        luxgoo: 'LuxGoo', letsdrive: 'LetsDrive', capitalcarrental: 'Capital Car Rental',
        autoclick: 'AutoClick', delpaso: 'Del Paso', centauro: 'Centauro',
        goldcar: 'Goldcar', firefly: 'Firefly', thrifty: 'Thrifty', hertz: 'Hertz',
        sixt: 'Sixt', europcar: 'Europcar', avis: 'Avis', budget: 'Budget',
        enterprise: 'Enterprise', alamo: 'Alamo', national: 'National',
    };
    return names[code] || props.vehicle?.provider_code || 'Renteon';
};
const providerBadge = computed(() => {
    const src = source.value;
    if (!src) return null;
    const badgeMap = {
        internal: { label: resolveInternalProviderLabel(), className: 'bg-slate-900 text-white', ribbonClassName: 'bg-gradient-to-r from-slate-900 to-slate-800 text-white' },
        greenmotion: { label: 'Green Motion', className: 'bg-emerald-100 text-emerald-700', ribbonClassName: 'bg-gradient-to-r from-emerald-700 to-emerald-500 text-white' },
        usave: { label: 'U-Save', className: 'bg-emerald-100 text-emerald-700', ribbonClassName: 'bg-gradient-to-r from-emerald-700 to-emerald-500 text-white' },
        locauto_rent: { label: 'Locauto', className: 'bg-indigo-100 text-indigo-700', ribbonClassName: 'bg-gradient-to-r from-indigo-700 to-indigo-500 text-white' },
        adobe: { label: 'Adobe', className: 'bg-amber-100 text-amber-700', ribbonClassName: 'bg-gradient-to-r from-amber-700 to-amber-500 text-white' },
        okmobility: { label: 'OK Mobility', className: 'bg-cyan-100 text-cyan-700', ribbonClassName: 'bg-gradient-to-r from-cyan-700 to-cyan-500 text-white' },
        renteon: { label: resolveRenteonSubProvider(), className: 'bg-sky-100 text-sky-700', ribbonClassName: 'bg-gradient-to-r from-sky-700 to-sky-500 text-white' },
        favrica: { label: 'Favrica', className: 'bg-pink-100 text-pink-700', ribbonClassName: 'bg-gradient-to-r from-pink-700 to-pink-500 text-white' },
        xdrive: { label: 'XDrive', className: 'bg-purple-100 text-purple-700', ribbonClassName: 'bg-gradient-to-r from-violet-700 to-violet-500 text-white' },
        wheelsys: { label: 'Wheelsys', className: 'bg-slate-100 text-slate-700', ribbonClassName: 'bg-gradient-to-r from-slate-700 to-slate-500 text-white' },
    };
    return badgeMap[src] || { label: toTitleCase(src), className: 'bg-gray-100 text-gray-700', ribbonClassName: 'bg-gradient-to-r from-gray-800 to-gray-700 text-white' };
});

// ── Vehicle specs ───────────────────────────────────────────────────
const vehicleSpecs = computed(() => {
    return {
        ...resolveSearchVehicleSpecs(props.vehicle),
        mpg: props.vehicle?.mpg,
        co2: props.vehicle?.co2,
    };
});

// ── Location data ───────────────────────────────────────────────────
const vehicleLocationText = computed(() => {
    if (!props.vehicle) return '';
    if (props.vehicle.full_vehicle_address) return props.vehicle.full_vehicle_address;
    const loc = typeof props.vehicle.location === 'string' ? props.vehicle.location : (props.vehicle.location?.pickup?.name || null);
    const parts = [loc, props.vehicle.city, props.vehicle.state, props.vehicle.country].filter(v => v && typeof v === 'string').map(p => p.trim()).filter(p => p.length > 0);
    return parts.join(', ') || props.pickupLocation || props.locationName || '';
});
const locationDetailLines = computed(() => {
    const d = props.locationDetails || {};
    return [d.address_1, d.address_2, d.address_3, d.address_city, d.address_county, d.address_postcode].map(p => `${p || ''}`.trim()).filter(p => p.length > 0);
});
const locationContact = computed(() => {
    const d = props.locationDetails || {};
    return { phone: d.telephone || null, email: d.email || null, iata: d.iata || null, whatsapp: d.whatsapp || null };
});
const locationOpeningHours = computed(() => {
    const d = props.locationDetails || {};
    if (Array.isArray(d.opening_hours) && d.opening_hours.length) return d.opening_hours;
    if (Array.isArray(d.office_opening_hours) && d.office_opening_hours.length) return d.office_opening_hours;
    return [];
});
const locationOutOfHours = computed(() => { const d = props.locationDetails || {}; return Array.isArray(d.out_of_hours_dropoff) ? d.out_of_hours_dropoff : []; });
const locationDaytimeClosures = computed(() => { const d = props.locationDetails || {}; return Array.isArray(d.daytime_closures_hours) ? d.daytime_closures_hours : []; });
const hasLocationHours = computed(() => locationOpeningHours.value.length || locationOutOfHours.value.length || locationDaytimeClosures.value.length || !!props.locationDetails?.out_of_hours_charge);
const formatHourWindow = (window) => {
    if (!window) return '';
    const first = (window.open || window.start || '') && (window.close || window.end || '') ? `${window.open || window.start} - ${window.close || window.end}` : '';
    const second = window.start2 && window.end2 ? `${window.start2} - ${window.end2}` : '';
    return [first, second].filter(Boolean).join(' / ');
};

const hasOneWayDropoff = computed(() => props.dropoffLocation && props.pickupLocation && props.dropoffLocation !== props.pickupLocation);
const vehicleLocationTitle = computed(() => { if (isInternal.value) return 'Vehicle Location'; if (isDifferentDropoff.value) return 'Pickup & Dropoff Locations'; return 'Pickup Location'; });

// ── Driver requirements ─────────────────────────────────────────────
const driverRequirementItems = computed(() => {
    const requirements = props.driverRequirements || {};
    const labelMap = { driving_licence: 'Driving licence', driving_licence_valid: 'Valid driving licence', passport: 'Passport', dvla_check_code: 'DVLA check code', two_proofs_of_address: 'Two proofs of address', valid_cc: 'Valid credit card', boarding_pass: 'Boarding pass' };
    return Object.entries(requirements).filter(([key, value]) => key !== 'mileage_type' && ['1', 'true', 'yes', 'y', true].includes(`${value}`.toLowerCase())).map(([key]) => labelMap[key] || key.replace(/_/g, ' ')).sort();
});
const mileageTypeLabel = computed(() => { const mt = props.driverRequirements?.mileage_type; return mt ? `${mt}` : null; });

// ── Package helpers ─────────────────────────────────────────────────
const selectPackage = (pkgType) => {
    if (isOkMobility.value && adapter.okMobilityCoverExtras) {
        const normalizeExtraCode = adapter.normalizeExtraCode || ((v) => `${v || ''}`.trim().toUpperCase());
        const coverExtra = adapter.okMobilityCoverExtras.value.find(extra => normalizeExtraCode(extra.code) === pkgType);
        if (coverExtra) {
            adapter.okMobilityCoverExtras.value.forEach((extra) => { if (extra.id !== coverExtra.id) delete selectedExtras.value[extra.id]; });
            setExtraQuantity(coverExtra, 1);
            currentPackage.value = pkgType;
            return;
        }
        adapter.okMobilityCoverExtras.value.forEach((extra) => { delete selectedExtras.value[extra.id]; });
        currentPackage.value = pkgType;
        return;
    }
    currentPackage.value = pkgType;
};
const toggleAdobeProtection = (pkg) => { if (pkg.extraId) toggleExtra({ id: pkg.extraId }); };
const isAdobeProtectionSelected = (pkg) => !!selectedExtras.value[pkg.extraId];
const getSelectedAdobeProtectionCodes = () => {
    const codes = [];
    for (const id in selectedExtras.value) { if (id.startsWith('adobe_protection_')) codes.push(id.replace('adobe_protection_', '')); }
    return codes.join(',');
};

// ── Unified computed (from adapters) ────────────────────────────────
const unifiedOptionalExtras = computed(() => adapter.optionalExtras?.value ?? props.optionalExtras ?? []);
const hasUnifiedExtras = computed(() => unifiedOptionalExtras.value.length > 0);
const unifiedProtectionPlans = computed(() => adapter.protectionPlans?.value ?? []);
const hasUnifiedProtectionPlans = computed(() => unifiedProtectionPlans.value.length > 0);
const unifiedTaxBreakdown = computed(() => {
    const tb = adapter.taxBreakdown?.value;
    if (!tb) return null;
    if (isRenteon.value && (tb.net || tb.vat || tb.gross)) return { type: 'renteon', ...tb };
    if (isOkMobility.value && (tb.total || tb.base || tb.tax)) return { type: 'okmobility', net: tb.base, vat: tb.tax, gross: tb.total, rate: tb.rate };
    return tb;
});
const unifiedIncludedItems = computed(() => {
    const adapterItems = adapter.includedItems?.value ?? [];
    if (adapterItems.length > 0) return adapterItems;
    // GreenMotion benefits from product
    if (isGreenMotion.value && currentProduct.value) {
        const benefits = getBenefits({ type: selectedPackageType.value, benefits: currentProduct.value?.benefits });
        return benefits.map(b => ({ label: b, detail: null }));
    }
    return [];
});

// ── OkMobility-specific delegations ────────────────────────────────
const okMobilityPickupStation = adapter.okMobilityPickupStation ?? computed(() => '');
const okMobilityDropoffStation = adapter.okMobilityDropoffStation ?? computed(() => '');
const okMobilityPickupAddress = adapter.okMobilityPickupAddress ?? computed(() => '');
const okMobilityDropoffAddress = adapter.okMobilityDropoffAddress ?? computed(() => '');
const okMobilitySameLocation = adapter.okMobilitySameLocation ?? computed(() => true);
const okMobilityFuelPolicy = adapter.okMobilityFuelPolicy ?? computed(() => null);
const okMobilityCancellationSummary = adapter.okMobilityCancellationSummary ?? computed(() => null);
const okMobilityInfoAvailable = adapter.okMobilityInfoAvailable ?? computed(() => false);
const okMobilityIncludedLabels = adapter.okMobilityIncludedLabels ?? computed(() => []);
const okMobilityRequiredLabels = adapter.okMobilityRequiredLabels ?? computed(() => []);
const okMobilityAvailableLabels = adapter.okMobilityAvailableLabels ?? computed(() => []);
const okMobilityPetExtras = adapter.okMobilityPetExtras ?? computed(() => []);
const okMobilityBaseTotal = adapter.okMobilityBaseTotal ?? adapter.baseTotal ?? computed(() => 0);
const okMobilityNormalizedExtras = adapter.okMobilityNormalizedExtras ?? computed(() => []);
const okMobilityPackages = adapter.okMobilityPackages ?? computed(() => []);

// ── Renteon-specific delegations ────────────────────────────────────
const renteonIncludedServices = adapter.renteonIncludedServices ?? computed(() => []);
const renteonDriverPolicy = adapter.renteonDriverPolicy ?? computed(() => null);
const renteonPickupOffice = adapter.renteonPickupOffice ?? computed(() => null);
const renteonDropoffOffice = adapter.renteonDropoffOffice ?? computed(() => null);
const renteonSameOffice = adapter.renteonSameOffice ?? computed(() => true);
const renteonPickupLines = adapter.renteonPickupLines ?? computed(() => []);
const renteonDropoffLines = adapter.renteonDropoffLines ?? computed(() => []);
const renteonPickupInstructions = adapter.renteonPickupInstructions ?? computed(() => null);
const renteonDropoffInstructions = adapter.renteonDropoffInstructions ?? computed(() => null);
const renteonHasOfficeDetails = adapter.renteonHasOfficeDetails ?? computed(() => false);
const renteonTaxBreakdown = adapter.renteonTaxBreakdown ?? computed(() => null);
const hasRenteonTaxBreakdown = adapter.hasRenteonTaxBreakdown ?? computed(() => false);

// ── SicilyByCar-specific ────────────────────────────────────────────
const sicilyByCarIncludedServices = adapter.sicilyByCarIncludedServices ?? computed(() => []);
const sicilyByCarAllExtras = adapter.sicilyByCarAllExtras ?? computed(() => []);

// ── RecordGo-specific ───────────────────────────────────────────────
const recordGoSelectedProduct = computed(() => { if (!isRecordGo.value) return null; return currentProduct.value?.recordgo || null; });
const recordGoIncludedComplements = adapter.recordGoIncludedComplements ?? computed(() => recordGoSelectedProduct.value?.complements_included || []);
const recordGoAutomaticComplements = adapter.recordGoAutomaticComplements ?? computed(() => recordGoSelectedProduct.value?.complements_automatic || recordGoSelectedProduct.value?.complements_autom || []);

// ── Extras details for emit ─────────────────────────────────────────
const OK_MOBILITY_COVER_CODES = adapter.OK_MOBILITY_COVER_CODES ?? ['OPC', 'OPCO'];
const normalizeExtraCode = adapter.normalizeExtraCode ?? ((value) => `${value || ''}`.trim().toUpperCase());
const getProviderExtraLabel = adapter.getProviderExtraLabel ?? ((extra) => {
    if (!isXDrive.value && !isFavrica.value && !isEmr.value) return extra?.name || '';
    const code = `${extra?.code || ''}`.trim();
    return code ? toTitleCase(code) : (extra?.name || '');
});

const getSelectedExtrasDetails = computed(() => {
    const details = [];
    const coverCodes = new Set(Array.isArray(OK_MOBILITY_COVER_CODES) ? OK_MOBILITY_COVER_CODES : OK_MOBILITY_COVER_CODES.value ?? ['OPC', 'OPCO']);
    const allExtras = adapter.allExtras?.value ?? [];
    for (const [id, qty] of Object.entries(selectedExtras.value)) {
        const extra = allExtras.find(e => e.id === id) || props.optionalExtras?.find(e => e.id === id);
        if (!extra) continue;
        if (isOkMobility.value && coverCodes.has(normalizeExtraCode(extra.code))) continue;
        const total = isSicilyByCar.value
            ? getSelectedSicilyByCarExtraTotal(extra, qty)
            : (extra.total_for_booking !== undefined && extra.total_for_booking !== null
                ? parseFloat(extra.total_for_booking) * qty
                : (parseFloat(extra.daily_rate !== undefined ? extra.daily_rate : (extra.price / props.numberOfDays)) * props.numberOfDays * qty));
        details.push({
            id: extra.id, option_id: extra.option_id ?? extra.id,
            name: typeof getProviderExtraLabel === 'function' ? getProviderExtraLabel(extra) : (extra.name || ''),
            qty, total, total_for_booking: extra.total_for_booking ?? null,
            daily_rate: extra.daily_rate ?? null, price: extra.price ?? null,
            excess: extra.excess ?? null, recordgo_payload: extra.recordgo_payload ?? null,
            currency: extra.currency ?? null, required: extra.required || false,
            numberAllowed: extra.numberAllowed ?? null, prepay_available: extra.prepay_available ?? null,
            service_id: extra.service_id, code: extra.code, purpose: extra.purpose ?? null
        });
    }

    // Add Locauto selected protection plans as line items
    if (isLocautoRent.value && selectedLocautoProtections.value.length > 0) {
        for (const code of selectedLocautoProtections.value) {
            const plan = locautoProtectionPlans.value.find(p => p.code === code);
            if (plan) {
                const total = parseFloat(plan.amount || 0) * props.numberOfDays;
                details.push({
                    id: `locauto_protection_${code}`,
                    name: plan.description || plan.name || code,
                    qty: 1,
                    total,
                    daily_rate: parseFloat(plan.amount || 0),
                    code,
                });
            }
        }
    }

    return details;
});

const providerSelectedExtrasDetails = computed(() => {
    if (!isSicilyByCar.value) return getSelectedExtrasDetails.value;
    return expandSicilyByCarSelectedExtras(selectedExtras.value, sicilyByCarAllExtras.value);
});

// ── Auto-select required extras ─────────────────────────────────────
watchEffect(() => {
    if (isGreenMotion.value) {
        (adapter.greenMotionExtras?.value ?? adapter.optionalExtras?.value ?? []).forEach((extra) => {
            if (extra.required) setExtraQuantity(extra, Math.max(selectedExtras.value[extra.id] || 0, 1));
        });
    }
});
watchEffect(() => {
    if (isOkMobility.value) {
        (adapter.okMobilityNormalizedExtras?.value ?? []).forEach((extra) => {
            if (extra.required) setExtraQuantity(extra, Math.max(selectedExtras.value[extra.id] || 0, 1));
        });
    }
});

// ── Extra icon helpers ──────────────────────────────────────────────
const getExtraIcon = (name) => {
    if (!name) return Plus;
    const n = name.toLowerCase();
    if (n.includes('gps') || n.includes('nav') || n.includes('sat')) return Navigation;
    if (n.includes('mobile') || n.includes('phone')) return Smartphone;
    if (n.includes('wifi') || n.includes('internet')) return Wifi;
    if (n.includes('baby') || n.includes('child') || n.includes('booster') || n.includes('infant')) return Baby;
    if (n.includes('snow') || n.includes('chain') || n.includes('winter')) return Snowflake;
    if (n.includes('driver') || n.includes('additional')) return UserPlus;
    if (n.includes('cover') || n.includes('insurance') || n.includes('protection') || n.includes('waiver')) return Shield;
    if (n.includes('tire') || n.includes('tyre') || n.includes('glass')) return CircleDashed;
    return Plus;
};
const getIconBackgroundClass = (name) => {
    if (!name) return 'icon-bg-gray';
    const n = name.toLowerCase();
    if (n.includes('gps') || n.includes('nav')) return 'icon-bg-blue';
    if (n.includes('baby') || n.includes('child') || n.includes('booster') || n.includes('infant')) return 'icon-bg-pink';
    if (n.includes('driver') || n.includes('additional')) return 'icon-bg-green';
    if (n.includes('wifi') || n.includes('internet')) return 'icon-bg-purple';
    if (n.includes('snow') || n.includes('winter')) return 'icon-bg-blue';
    if (n.includes('cover') || n.includes('insurance') || n.includes('protection')) return 'icon-bg-orange';
    return 'icon-bg-gray';
};
const getIconColorClass = (name) => {
    if (!name) return 'icon-text-gray';
    const n = name.toLowerCase();
    if (n.includes('gps') || n.includes('nav')) return 'icon-text-blue';
    if (n.includes('baby') || n.includes('child') || n.includes('booster') || n.includes('infant')) return 'icon-text-pink';
    if (n.includes('driver') || n.includes('additional')) return 'icon-text-green';
    if (n.includes('wifi') || n.includes('internet')) return 'icon-text-purple';
    if (n.includes('snow') || n.includes('winter')) return 'icon-text-blue';
    if (n.includes('cover') || n.includes('insurance') || n.includes('protection')) return 'icon-text-orange';
    return 'icon-text-gray';
};

// ── Package display helpers ─────────────────────────────────────────
const isKeyBenefit = (text) => { if (!text) return false; const l = text.toLowerCase(); return l.includes('excess') || l.includes('deposit') || l.includes('free') || l.includes('unlimited'); };
const getShortProtectionName = (description) => { if (!description) return ''; return description.includes('/') ? description.split('/')[0].trim() : description; };
const getPackageDisplayName = (type) => ({ 'BAS': 'Basic', 'PLU': 'Plus', 'PRE': 'Premium', 'PMP': 'Premium Plus', 'PLI': 'Liability Protection', 'LDW': 'Car Protection', 'SPP': 'Extended Protection' }[type] || type);
const getPackageSubtitle = (type) => ({ 'BAS': 'Essential Cover', 'PLU': 'Enhanced Cover', 'PRE': 'Full Cover', 'PMP': 'Ultimate Cover', 'PLI': 'Essential Cover', 'LDW': 'Standard Cover', 'SPP': 'Maximum Cover' }[type] || '');
const currentPackageLabel = computed(() => {
    if (currentProduct.value?.name) return currentProduct.value.name;
    if (isOkMobility.value) { const pkg = (adapter.okMobilityPackages?.value ?? availablePackages.value).find(item => item.type === currentPackage.value); if (pkg?.name) return pkg.name; }
    return getPackageDisplayName(currentPackage.value) || currentPackage.value;
});

const getBenefits = (product) => {
    if (!product) return [];
    if (product.benefits && Array.isArray(product.benefits)) return product.benefits;
    const benefits = [];
    if (product.excess !== undefined && parseFloat(product.excess) === 0) benefits.push('Glass and tyres covered');
    if (product.debitcard === 'Y') benefits.push('Debit Card Accepted');
    if (product.fuelpolicy === 'FF') benefits.push('Free Fuel / Full to Full');
    else if (product.fuelpolicy === 'SL') benefits.push('Like for Like fuel policy');
    if (product.costperextradistance !== undefined && parseFloat(product.costperextradistance) === 0) benefits.push('Unlimited mileage');
    else if (product.mileage && product.mileage !== 'Unlimited' && product.mileage !== 'unlimited') {
        const mv = parseFloat(`${product.mileage}`.trim());
        benefits.push(Number.isFinite(mv) && `${product.mileage}`.trim() === `${mv}` ? `KM Limit: ${product.mileage}` : `Mileage: ${product.mileage}`);
    } else if (product.mileage === 'Unlimited' || product.mileage === 'unlimited') benefits.push('Unlimited mileage');
    if (product.type === 'BAS') { benefits.push('Non-refundable'); benefits.push('Non-amendable'); }
    if (['PLU', 'PRE', 'PMP'].includes(product.type)) benefits.push('Cancellation in line with T&Cs');
    return benefits;
};

// ── Misc ────────────────────────────────────────────────────────────
const cancellationDeadline = adapter.cancellationDeadline ?? computed(() => null);
const formatPaymentMethod = (method) => {
    if (!method) return '';
    let methods = [];
    try {
        if (typeof method === 'string' && (method.startsWith('[') || method.startsWith('{'))) { const parsed = JSON.parse(method); methods = Array.isArray(parsed) ? parsed : [method]; }
        else if (Array.isArray(method)) methods = method;
        else methods = [method];
    } catch { methods = [method]; }
    return methods.map(m => m.toString().replace(/[_-]/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ')).join(', ');
};

const hasProviderNotes = computed(() => {
    if (isInternal.value) return true; // Always show for internal — has fuel policy, cancellation policy at minimum
    if (isRecordGo.value) return recordGoAutomaticComplements.value.length > 0;
    return false;
});

const vehicleFeatures = computed(() => {
    const raw = props.vehicle?.features;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw.filter(f => f && typeof f === 'string');
    if (typeof raw === 'string') {
        try { const parsed = JSON.parse(raw); return Array.isArray(parsed) ? parsed.filter(f => f && typeof f === 'string') : []; }
        catch { return []; }
    }
    return [];
});

const adobeMandatoryProtection = adapter.mandatoryAmount ?? computed(() => 0);

// ── Checkout handler ────────────────────────────────────────────────
const handleProceedToCheckout = () => {
    emit('proceed-to-checkout', {
        package: isLocautoRent.value ? (selectedLocautoProtections.value.length > 0 ? 'POA' : 'BAS') : selectedPackageType.value,
        protection_code: isLocautoRent.value ? selectedLocautoProtections.value : (isAdobeCars.value ? getSelectedAdobeProtectionCodes() : null),
        protection_amount: isLocautoRent.value && selectedLocautoProtections.value.length > 0
            ? selectedLocautoProtections.value.reduce((sum, code) => {
                const plan = locautoProtectionPlans.value.find(p => p.code === code);
                return sum + (plan ? parseFloat(plan.amount || 0) : 0);
            }, 0)
            : (isAdobeCars.value ? (adobeMandatoryProtection.value || 0) : 0),
        extras: selectedExtras.value,
        detailedExtras: providerSelectedExtrasDetails.value,
        totals: { grandTotal: grandTotal.value, payableAmount: payableAmount.value, pendingAmount: pendingAmount.value },
        totals_currency: pricingCurrency.value,
        vehicle_total: isLocautoRent.value ? locautoBaseTotal.value : (isOkMobility.value ? okMobilityBaseTotal.value : (currentProduct.value?.total || props.vehicle?.total_price || 0)),
        vehicle_total_currency: vehicleTotalCurrency.value,
        selected_deposit_type: selectedDepositType.value || null,
    });
};

// ── Lifecycle ───────────────────────────────────────────────────────
// Map init/cleanup and modal map are handled by useVehicleMap composable
// Sync child component refs with composable refs for map initialization
onMounted(() => {
    fetchExchangeRates();
    const observer = new IntersectionObserver(([entry]) => { isSummaryVisible.value = entry.isIntersecting; }, { threshold: 0.1 });
    if (summarySection.value) observer.observe(summarySection.value);

    // Sync VehicleHero's exposed vehicleMapRef with the composable's vehicleMapRef
    nextTick(() => {
        if (vehicleHeroRef.value?.vehicleMapRef) {
            vehicleMapRef.value = vehicleHeroRef.value.vehicleMapRef;
        }
    });
});

// Watch for VehicleHero component mount to sync the map ref
watch(() => vehicleHeroRef.value?.vehicleMapRef, (el) => {
    if (el) vehicleMapRef.value = el;
}, { flush: 'post' });

// Watch for MapModal component mount to sync the modal map ref
watch(() => mapModalCompRef.value?.mapModalRef, (el) => {
    if (el) mapModalRef.value = el;
}, { flush: 'post' });
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

                <!-- ═══ 1. VEHICLE HERO ═══ -->
                <VehicleHero
                    ref="vehicleHeroRef"
                    :vehicle-image="vehicleImage"
                    :display-vehicle-name="displayVehicleName"
                    :has-multiple-images="hasMultipleImages"
                    :current-hero-image="currentHeroImage"
                    :all-hero-images="allHeroImages"
                    :hero-image-index="heroImageIndex"
                    :vehicle-specs="vehicleSpecs"
                    :has-vehicle-coords="hasVehicleCoords"
                    :is-different-dropoff="isDifferentDropoff"
                    :provider-badge="providerBadge"
                    :vehicle="vehicle"
                    :number-of-days="numberOfDays"
                    :current-product="currentProduct"
                    :seating-icon="seatingIcon"
                    :door-icon="doorIcon"
                    :transmission-icon="transmissionIcon"
                    :ac-icon="acIcon"
                    :fuel-icon="fuelIcon"
                    @hero-next="heroNextImage"
                    @hero-prev="heroPrevImage"
                    @set-hero-index="heroImageIndex = $event"
                    @show-lightbox="showLightbox = true"
                    @show-map="showMapModal = true"
                />

                <!-- ═══ 2. RENTAL TIMELINE ═══ -->
                <RentalTimeline
                    :pickup-location="pickupLocation"
                    :dropoff-location="dropoffLocation"
                    :location-name="locationName"
                    :pickup-date="pickupDate"
                    :pickup-time="pickupTime"
                    :dropoff-date="dropoffDate"
                    :dropoff-time="dropoffTime"
                />

                <!-- ═══ 3. LOCATION DETAILS ═══ -->
                <LocationDetails
                    v-if="vehicleLocationText || hasVehicleCoords || (isOkMobility && okMobilityInfoAvailable) || (isRenteon && renteonHasOfficeDetails) || (isAdobeCars && vehicle.office_address) || adapter.locationData?.value?.pickupStation"
                    :pickup-location="pickupLocation"
                    :dropoff-location="dropoffLocation"
                    :location-name="locationName"
                    :vehicle-location-text="vehicleLocationText"
                    :has-one-way-dropoff="hasOneWayDropoff"
                    :is-adobe-cars="isAdobeCars"
                    :vehicle="vehicle"
                    :location-detail-lines="locationDetailLines"
                    :location-contact="locationContact"
                    :has-location-hours="hasLocationHours"
                    :is-ok-mobility="isOkMobility"
                    :ok-mobility-pickup-station="okMobilityPickupStation"
                    :ok-mobility-pickup-address="okMobilityPickupAddress"
                    :ok-mobility-dropoff-station="okMobilityDropoffStation"
                    :ok-mobility-dropoff-address="okMobilityDropoffAddress"
                    :is-renteon="isRenteon"
                    :renteon-pickup-office="renteonPickupOffice"
                    :renteon-dropoff-office="renteonDropoffOffice"
                    :renteon-same-office="renteonSameOffice"
                    :renteon-pickup-lines="renteonPickupLines"
                    :renteon-dropoff-lines="renteonDropoffLines"
                    :renteon-pickup-instructions="renteonPickupInstructions"
                    :renteon-dropoff-instructions="renteonDropoffInstructions"
                    :adapter-location-data="adapter.locationData?.value"
                    @show-location-hours="showLocationHoursModal = true"
                />

                <!-- ═══ 4. PACKAGE / RATE SELECTION ═══ -->
                <PackageSelection
                    v-if="(!isLocautoRent && availablePackages.length > 0) || (isLocautoRent && locautoProtectionPlans.length > 0)"
                    :available-packages="availablePackages"
                    :selected-package-type="selectedPackageType"
                    :is-locauto-rent="isLocautoRent"
                    :is-adobe-cars="isAdobeCars"
                    :is-ok-mobility="isOkMobility"
                    :locauto-protection-plans="locautoProtectionPlans"
                    :selected-locauto-protections="selectedLocautoProtections"
                    :format-rental-price="formatRentalPrice"
                    :format-price="formatPrice"
                    :get-package-display-name="getPackageDisplayName"
                    :get-package-subtitle="getPackageSubtitle"
                    :get-benefits="getBenefits"
                    :get-short-protection-name="getShortProtectionName"
                    :is-adobe-protection-selected="isAdobeProtectionSelected"
                    :number-of-days="numberOfDays"
                    @select-package="selectPackage"
                    @toggle-adobe-protection="toggleAdobeProtection"
                    @toggle-locauto-protection="toggleLocautoProtection"
                />

                <!-- ═══ 5. WHAT'S INCLUDED (after package so customer sees what they picked) ═══ -->
                <IncludedItems
                    v-if="unifiedIncludedItems.length > 0"
                    :items="unifiedIncludedItems"
                />

                <!-- ═══ 6. DEPOSIT & EXCESS ═══ -->
                <DepositExcess
                    v-if="(isInternal && (vehicle?.security_deposit > 0 || vehicle?.benefits?.deposit_amount || vehicle?.benefits?.excess_amount)) || (isRenteon && (currentProduct?.deposit || currentProduct?.excess || currentProduct?.excess_theft_amount || vehicle?.benefits?.deposit_amount || vehicle?.benefits?.excess_amount || vehicle?.benefits?.excess_theft_amount)) || ((vehicle?.security_deposit > 0 || vehicle?.benefits?.excess_amount || vehicle?.benefits?.deposit_amount) && (isSurprice || isRecordGo || isOkMobility || isFavrica || isXDrive || isEmr || isClick2Rent || isSicilyByCar)) || (isLocautoRent && (vehicle?.benefits?.excess_amount || vehicle?.benefits?.excess_theft_amount))"
                    :vehicle="vehicle"
                    :current-product="currentProduct"
                    :format-price="formatPrice"
                    :format-payment-method="formatPaymentMethod"
                    v-model:selected-deposit-type="selectedDepositType"
                    :available-deposit-types="availableDepositTypes"
                />

                <!-- ═══ 7. PROVIDER HIGHLIGHTS (Taxes, Policies, Included Services) ═══ -->
                <ProviderHighlights
                    :unified-tax-breakdown="unifiedTaxBreakdown"
                    :is-ok-mobility="isOkMobility"
                    :is-renteon="isRenteon"
                    :ok-mobility-fuel-policy="okMobilityFuelPolicy"
                    :ok-mobility-cancellation-summary="okMobilityCancellationSummary"
                    :renteon-included-services="renteonIncludedServices"
                    :renteon-driver-policy="renteonDriverPolicy"
                    :is-sicily-by-car="isSicilyByCar"
                    :is-record-go="isRecordGo"
                    :sicily-by-car-included-services="sicilyByCarIncludedServices"
                    :record-go-included-complements="recordGoIncludedComplements"
                    :record-go-automatic-complements="recordGoAutomaticComplements"
                    :format-price="formatPrice"
                    :format-rental-price="formatRentalPrice"
                    :strip-html="stripHtml"
                />

                <!-- ═══ 8. PROTECTION PLANS / INSURANCE ═══ -->
                <ProtectionPlans
                    v-if="(hasUnifiedProtectionPlans && !isLocautoRent) || isRenteon"
                    :plans="unifiedProtectionPlans"
                    :selected-extras="selectedExtras"
                    :is-renteon="isRenteon"
                    :is-sicily-by-car="isSicilyByCar"
                    :format-rental-price="formatRentalPrice"
                    :format-price="formatPrice"
                    :get-provider-extra-label="getProviderExtraLabel"
                    :get-extra-per-day="getExtraPerDay"
                    :number-of-days="numberOfDays"
                    @toggle-extra="toggleExtra"
                    @update-extra-quantity="(extra, delta) => updateExtraQuantity(extra, delta)"
                />

                <!-- ═══ 10. OPTIONAL EXTRAS ═══ -->
                <OptionalExtras
                    v-if="hasUnifiedExtras"
                    :extras="unifiedOptionalExtras"
                    :selected-extras="selectedExtras"
                    :is-favrica="isFavrica"
                    :is-x-drive="isXDrive"
                    :is-emr="isEmr"
                    :is-sicily-by-car="isSicilyByCar"
                    :is-record-go="isRecordGo"
                    :format-rental-price="formatRentalPrice"
                    :get-provider-extra-label="getProviderExtraLabel"
                    :get-extra-per-day="getExtraPerDay"
                    :get-extra-icon="getExtraIcon"
                    :get-icon-background-class="getIconBackgroundClass"
                    :get-icon-color-class="getIconColorClass"
                    :number-of-days="numberOfDays"
                    @toggle-extra="toggleExtra"
                    @update-extra-quantity="(extra, delta) => updateExtraQuantity(extra, delta)"
                />

                <!-- ═══ 11. PROVIDER NOTES ═══ -->
                <ProviderNotes
                    v-if="hasProviderNotes"
                    :is-internal="isInternal"
                    :is-record-go="isRecordGo"
                    :vehicle="vehicle"
                    :record-go-automatic-complements="recordGoAutomaticComplements"
                    v-model:show-provider-notes="showProviderNotes"
                />

                <!-- ═══ 12. DRIVER REQUIREMENTS ═══ -->
                <DriverRequirements
                    v-if="driverRequirementItems.length || mileageTypeLabel"
                    :items="driverRequirementItems"
                    :mileage-type-label="mileageTypeLabel"
                />

                <!-- ═══ 13. TERMS & CONDITIONS ═══ -->
                <TermsConditions
                    v-if="terms && terms.length"
                    :terms="terms"
                />
            </div>

            <!-- ═══════ RIGHT SIDEBAR ═══════ -->
            <div class="w-full lg:w-96 xl:w-[420px] flex-shrink-0" ref="summarySection">
                <BookingSummary
                    :vehicle-image="vehicleImage"
                    :display-vehicle-name="displayVehicleName"
                    :provider-badge="providerBadge"
                    :vehicle-specs="vehicleSpecs"
                    :pickup-date="pickupDate"
                    :pickup-time="pickupTime"
                    :dropoff-date="dropoffDate"
                    :dropoff-time="dropoffTime"
                    :pickup-location="pickupLocation"
                    :dropoff-location="dropoffLocation"
                    :location-name="locationName"
                    :current-package-label="currentPackageLabel"
                    :is-locauto-rent="isLocautoRent"
                    :is-ok-mobility="isOkMobility"
                    :is-adobe-cars="isAdobeCars"
                    :locauto-base-total="locautoBaseTotal"
                    :ok-mobility-base-total="okMobilityBaseTotal"
                    :current-product="currentProduct"
                    :adobe-mandatory-protection="adobeMandatoryProtection"
                    :get-selected-extras-details="getSelectedExtrasDetails"
                    :format-rental-price="formatRentalPrice"
                    :format-price="formatPrice"
                    :rates-ready="ratesReady"
                    :grand-total="grandTotal"
                    :payable-amount="payableAmount"
                    :pending-amount="pendingAmount"
                    :effective-payment-percentage="effectivePaymentPercentage"
                    :available-deposit-types="availableDepositTypes"
                    :selected-deposit-type="selectedDepositType"
                    @show-details-modal="showDetailsModal = true"
                    @proceed-to-checkout="handleProceedToCheckout"
                    @back="$emit('back')"
                />
            </div>
        </div>

        <!-- ═══════ MODALS ═══════ -->

        <!-- Booking Details Modal -->
        <DetailsModal
            :show="showDetailsModal"
            :display-vehicle-name="displayVehicleName"
            :current-package-label="currentPackageLabel"
            :is-ok-mobility="isOkMobility"
            :is-adobe-cars="isAdobeCars"
            :current-product="currentProduct"
            :ok-mobility-base-total="okMobilityBaseTotal"
            :adobe-mandatory-protection="adobeMandatoryProtection"
            :get-selected-extras-details="getSelectedExtrasDetails"
            :format-rental-price="formatRentalPrice"
            :format-price="formatPrice"
            :grand-total="grandTotal"
            :payable-amount="payableAmount"
            :pending-amount="pendingAmount"
            :effective-payment-percentage="effectivePaymentPercentage"
            @close="showDetailsModal = false"
        />

        <!-- Mobile Sticky Price Bar -->
        <MobileStickyBar
            :is-summary-visible="isSummaryVisible"
            :grand-total="grandTotal"
            :payable-amount="payableAmount"
            :effective-payment-percentage="effectivePaymentPercentage"
            :format-price="formatPrice"
            @scroll-to-summary="scrollToSummary"
        />

        <!-- Image Lightbox -->
        <ImageLightbox
            :show="showLightbox"
            :images="lightboxImages"
            :current-index="lightboxIndex"
            :display-vehicle-name="displayVehicleName"
            @close="showLightbox = false"
            @next="lightboxNext"
            @prev="lightboxPrev"
            @set-index="lightboxIndex = $event"
        />

        <!-- Map Fullscreen Modal -->
        <MapModal
            ref="mapModalCompRef"
            :show="showMapModal"
            :has-vehicle-coords="hasVehicleCoords"
            :is-different-dropoff="isDifferentDropoff"
            :pickup-location="pickupLocation"
            :dropoff-location="dropoffLocation"
            :location-name="locationName"
            @close="showMapModal = false"
        />

        <!-- Location Hours Modal -->
        <LocationHoursModal
            :show="showLocationHoursModal"
            :location-opening-hours="locationOpeningHours"
            :location-out-of-hours="locationOutOfHours"
            :location-daytime-closures="locationDaytimeClosures"
            :location-details="locationDetails"
            :format-hour-window="formatHourWindow"
            @close="showLocationHoursModal = false"
        />
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

/* ── Shared classes used by child components (must be unscoped) ── */

/* Fade-in-up stagger */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-in-up {
    animation: fadeInUp 0.45s ease-out both;
}

/* Plan/Package card */
.plan-card {
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    cursor: pointer;
}
.plan-card.selected {
    border-color: #1e3a5f;
    background: #f0f4f8;
    box-shadow: 0 0 0 2px #1e3a5f;
}

/* Card hover lift */
.card-hover {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(30,58,95,0.08);
}

/* Included items checkmark */
.check-icon {
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}

/* Radio pop */
@keyframes radioPop {
    0%   { transform: translate(-50%, -50%) scale(0); }
    50%  { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
.radio-dot {
    animation: radioPop 0.3s ease-out;
}

/* Icon Color Helper Classes */
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

/* Provider badge ribbon */
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

/* fade-in-up, card-hover, radioPop, radio-dot moved to unscoped <style> */

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

/* sticky-summary moved to BookingSummary.vue */

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

/* provider-ribbon, plan-card moved to unscoped <style> */

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

/* check-icon moved to unscoped <style> */

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

/* icon-bg-*, icon-text-* moved to unscoped <style> */

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
