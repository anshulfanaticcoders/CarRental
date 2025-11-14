<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import Footer from "@/Components/Footer.vue";
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import check from "../../assets/Check.svg";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import Clock from '../../assets/clock.svg';

// Icons
import doorIcon from "../../assets/door.svg";
import luggageIcon from "../../assets/luggage.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import carbonIcon from "../../assets/carbon-emmision.svg";
import MapPin from "../../assets/MapPin.svg";
import fullStar from "../../assets/fullstar.svg";
import halfStar from "../../assets/halfstar.svg";
import carguaranteeIcon from "../../assets/carguarantee.png";
import locationPinIcon from "../../assets/locationPin.svg";
import ShareIcon from "../../assets/ShareNetwork.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIcon.svg";
import partnersIcon from "../../assets/partners.svg";
import offerIcon from "../../assets/percentage-tag.svg";

// UI components
import { Skeleton } from '@/Components/ui/skeleton';
import '@vuepic/vue-datepicker/dist/main.css';
import VueDatepicker from '@vuepic/vue-datepicker';
import { useToast } from 'vue-toastification';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from "@/Components/ui/carousel";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import { ChevronRight, ImageIcon, ZoomIn } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from "@/Components/ui/button";
import Lightbox from "@/Components/Lightbox.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { Vue3Lottie } from 'vue3-lottie';
import universalLoader from '../../../public/animations/universal-loader.json';
import { useCurrency } from '@/composables/useCurrency';

const isBooking = ref(false);
const currencySymbols = ref({});
const exchangeRates = ref(null);
const { selectedCurrency, supportedCurrencies, changeCurrency } = useCurrency();

const props = defineProps({
    vehicle: Object,
    locationInfo: Object,
    searchParams: Object,
    locale: String,
});

const page = usePage();
const toast = useToast();
const map = ref(null);
const mapContainer = ref(null);
const isLoading = ref(false);
const currentImageIndex = ref(0);
const selectedImageIndex = ref(0);

// Adobe-specific vehicle images (usually just one main image)
const vehicleImages = computed(() => {
    const images = [props.vehicle?.image];
    return images.filter(img => img && img.trim() !== '');
});

// SEO Meta
const seoTitle = computed(() => {
    const vehicleName = props.vehicle?.model || 'Adobe Vehicle';
    const location = props.locationInfo?.label || 'Adobe Car Rental';
    return `${vehicleName} - ${location} | Adobe Car Rental`;
});

const seoDescription = computed(() => {
    const vehicleName = props.vehicle?.model || 'Adobe Vehicle';
    const features = [];
    if (props.vehicle?.seating_capacity) features.push(`${props.vehicle.seating_capacity} seats`);
    if (props.vehicle?.transmission) features.push(props.vehicle.transmission);
    if (props.vehicle?.mileage === 'unlimited') features.push('unlimited mileage');

    return `Rent ${vehicleName} from Adobe Car Rental. Features include ${features.join(', ')}. Best rates guaranteed.`;
});

const seoKeywords = computed(() => {
    return `${props.vehicle?.brand}, ${props.vehicle?.model}, Adobe car rental, ${props.locationInfo?.label}, rent car, vehicle hire`;
});

const currentUrl = computed(() => page.props.ziggy?.location || window.location.href);
const seoImageUrl = computed(() => props.vehicle?.image || '/images/adobe-placeholder.jpg');
const canonicalUrl = computed(() => currentUrl.value);

// Provider information
const providerName = ref('Adobe Car Rental');
const providerLogoText = ref('ACR');

// Map initialization
onMounted(() => {
    if (props.locationInfo?.latitude && props.locationInfo?.longitude) {
        nextTick(() => {
            initializeMap();
        });
    }
});

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});

const initializeMap = () => {
    if (!mapContainer.value) return;

    try {
        // Fix for default Leaflet icons
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: markerIcon2x,
            iconUrl: markerIcon,
            shadowUrl: markerShadow,
        });

        map.value = L.map(mapContainer.value).setView([
            props.locationInfo.latitude,
            props.locationInfo.longitude
        ], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map.value);

        L.marker([props.locationInfo.latitude, props.locationInfo.longitude])
            .addTo(map.value)
            .bindPopup(props.locationInfo.label || 'Adobe Location')
            .openPopup();
    } catch (error) {
        console.error('Error initializing map:', error);
    }
};

// Vehicle specifications
const vehicleSpecs = computed(() => {
    const specs = [];

    if (props.vehicle?.seating_capacity) {
        specs.push({
            icon: peopleIcon,
            label: 'Passengers',
            value: props.vehicle.seating_capacity
        });
    }

    if (props.vehicle?.benefits?.doors) {
        specs.push({
            icon: doorIcon,
            label: 'Doors',
            value: props.vehicle.benefits.doors
        });
    }

    if (props.vehicle?.transmission) {
        specs.push({
            icon: transmisionIcon,
            label: 'Transmission',
            value: props.vehicle.transmission
        });
    }

    if (props.vehicle?.fuel) {
        specs.push({
            icon: fuelIcon,
            label: 'Fuel',
            value: props.vehicle.fuel
        });
    }

    if (props.vehicle?.benefits?.vehicle_type) {
        specs.push({
            icon: carIcon,
            label: 'Type',
            value: props.vehicle.benefits.vehicle_type
        });
    }

    return specs;
});

// Adobe benefits and features
const vehicleBenefits = computed(() => {
    const benefits = [];

    if (props.vehicle?.mileage === 'unlimited') {
        benefits.push({
            icon: mileageIcon,
            title: 'Unlimited Mileage',
            description: 'Drive without distance restrictions'
        });
    }

    if (props.vehicle?.benefits?.cancellation_available_per_day) {
        benefits.push({
            icon: Clock,
            title: 'Flexible Cancellation',
            description: 'Free cancellation up to 24 hours before pickup'
        });
    }

    if (props.vehicle?.benefits?.fuel_policy) {
        benefits.push({
            icon: fuelIcon,
            title: 'Fuel Policy',
            description: props.vehicle.benefits.fuel_policy.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
        });
    }

    if (props.vehicle?.benefits?.minimum_driver_age) {
        benefits.push({
            icon: carguaranteeIcon,
            title: 'Driver Requirements',
            description: `Minimum age: ${props.vehicle.benefits.minimum_driver_age} years`
        });
    }

    return benefits;
});

// Pricing information
const pricingInfo = computed(() => {
    const pricing = [];

    if (props.vehicle?.tdr) {
        pricing.push({
            label: 'Time & Distance Rate',
            amount: props.vehicle.tdr,
            description: 'Base rental charge'
        });
    }

    if (props.vehicle?.pli) {
        pricing.push({
            label: 'Liability Protection (PLI)',
            amount: props.vehicle.pli,
            description: 'Third-party liability insurance'
        });
    }

    if (props.vehicle?.ldw) {
        pricing.push({
            label: 'Loss Damage Waiver (LDW)',
            amount: props.vehicle.ldw,
            description: 'Vehicle damage protection',
            optional: true
        });
    }

    if (props.vehicle?.spp) {
        pricing.push({
            label: 'Super Protection (SPP)',
            amount: props.vehicle.spp,
            description: 'Comprehensive coverage',
            optional: true
        });
    }

    return pricing;
});

// Protections and extras
const availableProtections = computed(() => {
    return props.vehicle?.protections?.filter(p => p.type === 'Proteccion') || [];
});

const availableExtras = computed(() => {
    return props.vehicle?.extras?.filter(e => e.type !== 'Proteccion') || [];
});

// Navigation methods
const startBooking = () => {
    isLoading.value = true;

    const bookingParams = new URLSearchParams({
        id: props.vehicle.id,
        ...props.searchParams
    });

    const route = router.visit(route('adobe-booking.create', {
        locale: props.locale,
        id: props.vehicle.id
    }), {
        method: 'get',
        data: props.searchParams,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            toast.error('Unable to start booking. Please try again.');
        }
    });
};

const imageLightboxOpen = ref(false);
const currentLightboxImage = ref(null);

const openImageLightbox = (imageUrl) => {
    currentLightboxImage.value = imageUrl;
    imageLightboxOpen.value = true;
};

const closeImageLightbox = () => {
    imageLightboxOpen.value = false;
    currentLightboxImage.value = null;
};

// Format currency
const formatCurrency = (amount) => {
    const formattedAmount = selectedCurrency.value ?
        convertCurrency(amount, 'USD', selectedCurrency.value) : amount;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: selectedCurrency.value || 'USD'
    }).format(formattedAmount);
};

const convertCurrency = (amount, fromCurrency, toCurrency) => {
    // This would integrate with your currency conversion service
    // For now, return the original amount
    return amount;
};

// Share functionality
const shareVehicle = async () => {
    try {
        if (navigator.share) {
            await navigator.share({
                title: seoTitle.value,
                text: seoDescription.value,
                url: currentUrl.value,
            });
        } else {
            // Fallback: copy to clipboard
            await navigator.clipboard.writeText(currentUrl.value);
            toast.success('Link copied to clipboard!');
        }
    } catch (error) {
        console.error('Error sharing:', error);
        toast.error('Unable to share vehicle');
    }
};
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow" />
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

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between text-white">
                <div>
                    <h1 class="text-4xl font-bold mb-2">{{ vehicle.model || 'Adobe Vehicle' }}</h1>
                    <p class="text-xl opacity-90">{{ locationInfo?.label || 'Adobe Car Rental Location' }}</p>
                </div>
                <div class="text-right">
                    <div class="text-sm opacity-75">Starting from</div>
                    <div class="text-3xl font-bold">{{ formatCurrency(vehicle.price_per_day) }}</div>
                    <div class="text-sm opacity-75">per day</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vehicle Images and Basic Info -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Vehicle Images -->
                <div>
                    <div v-if="vehicleImages.length > 0" class="relative">
                        <Carousel class="w-full">
                            <CarouselContent>
                                <CarouselItem v-for="(image, index) in vehicleImages" :key="index">
                                    <div class="relative aspect-video rounded-lg overflow-hidden bg-gray-200">
                                        <img
                                            :src="image"
                                            :alt="`${vehicle.model} - Image ${index + 1}`"
                                            class="w-full h-full object-cover cursor-pointer"
                                            @click="openImageLightbox(image)"
                                            @error="$event.target.src = '/images/adobe-placeholder.jpg'"
                                        />
                                        <div class="absolute bottom-2 right-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm">
                                            {{ index + 1 }} / {{ vehicleImages.length }}
                                        </div>
                                    </div>
                                </CarouselItem>
                            </CarouselContent>
                            <CarouselPrevious class="left-2" />
                            <CarouselNext class="right-2" />
                        </Carousel>

                        <!-- Image Lightbox -->
                        <Dialog :open="imageLightboxOpen" @update:open="imageLightboxOpen = $event">
                            <DialogContent class="max-w-4xl w-full p-0">
                                <div class="relative">
                                    <img
                                        v-if="currentLightboxImage"
                                        :src="currentLightboxImage"
                                        :alt="`${vehicle.model} - Full Size`"
                                        class="w-full h-auto max-h-[80vh] object-contain"
                                        @error="$event.target.src = '/images/adobe-placeholder.jpg'"
                                    />
                                    <Button
                                        @click="closeImageLightbox"
                                        class="absolute top-4 right-4"
                                        variant="secondary"
                                        size="sm"
                                    >
                                        Close
                                    </Button>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <!-- Fallback image -->
                    <div v-else class="aspect-video rounded-lg overflow-hidden bg-gray-200">
                        <img
                            src="/images/adobe-placeholder.jpg"
                            :alt="vehicle.model"
                            class="w-full h-full object-cover"
                        />
                    </div>

                    <!-- Quick Share -->
                    <div class="mt-4 flex items-center gap-4">
                        <Button @click="shareVehicle" variant="outline" size="sm" class="flex items-center gap-2">
                            <img :src="ShareIcon" alt="Share" class="w-4 h-4" />
                            Share Vehicle
                        </Button>
                    </div>
                </div>

                <!-- Vehicle Specifications -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <img :src="carIcon" alt="Specifications" class="w-6 h-6" />
                                Vehicle Specifications
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="spec in vehicleSpecs" :key="spec.label" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <img :src="spec.icon" :alt="spec.label" class="w-5 h-5" />
                                    <div>
                                        <div class="text-xs text-gray-500">{{ spec.label }}</div>
                                        <div class="font-semibold">{{ spec.value }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Adobe Category Information -->
                            <div v-if="vehicle.category" class="mt-6 p-4 bg-blue-50 rounded-lg">
                                <div class="text-sm text-blue-800">
                                    <strong>Adobe Category:</strong> {{ vehicle.category.toUpperCase() }}
                                </div>
                                <div class="text-xs text-blue-600 mt-1">
                                    Adobe vehicle categories define specific car types and pricing tiers
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pricing Breakdown -->
                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <span class="text-2xl">💰</span>
                                Pricing Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="price in pricingInfo" :key="price.label"
                                     :class="['flex justify-between items-center p-3 rounded-lg',
                                            price.optional ? 'bg-gray-50' : 'bg-white border']">
                                    <div>
                                        <div class="font-medium">{{ price.label }}</div>
                                        <div class="text-sm text-gray-600">{{ price.description }}</div>
                                        <span v-if="price.optional" class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                            Optional
                                        </span>
                                    </div>
                                    <div class="font-semibold">{{ formatCurrency(price.amount) }}</div>
                                </div>

                                <div class="border-t pt-3 mt-3">
                                    <div class="flex justify-between items-center text-lg font-bold">
                                        <span>Total Daily Rate</span>
                                        <span>{{ formatCurrency(vehicle.price_per_day) }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </section>

    <!-- Vehicle Benefits -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">Why Choose This Vehicle</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card v-for="benefit in vehicleBenefits" :key="benefit.title" class="text-center">
                    <CardContent class="pt-6">
                        <img :src="benefit.icon" :alt="benefit.title" class="w-12 h-12 mx-auto mb-4" />
                        <h3 class="font-semibold mb-2">{{ benefit.title }}</h3>
                        <p class="text-gray-600 text-sm">{{ benefit.description }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>

    <!-- Available Protections -->
    <section v-if="availableProtections.length > 0" class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">Protection Options</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card v-for="protection in availableProtections" :key="protection.code">
                    <CardContent class="pt-6">
                        <h3 class="font-semibold mb-2">{{ protection.name || protection.code }}</h3>
                        <p class="text-gray-600 text-sm mb-3">{{ protection.description }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold">{{ formatCurrency(protection.total) }}</span>
                            <span v-if="protection.required" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">
                                Required
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>

    <!-- Location Information -->
    <section v-if="locationInfo" class="py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">Pickup Location</h2>
            <div class="grid lg:grid-cols-2 gap-8">
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <img :src="locationPinIcon" alt="Location" class="w-6 h-6" />
                                {{ locationInfo.label }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <p class="text-gray-600">{{ locationInfo.address || 'Adobe Car Rental Location' }}</p>
                                <div v-if="locationInfo.below_label" class="text-sm text-gray-500">
                                    {{ locationInfo.below_label }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <div>
                    <div ref="mapContainer" class="h-64 rounded-lg bg-gray-200"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking CTA -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Ready to Book This Vehicle?</h2>
            <p class="text-xl mb-8 opacity-90">
                Reserve your {{ vehicle.model }} now for the best rates
            </p>
            <Button
                @click="startBooking"
                :disabled="isLoading"
                size="lg"
                class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-3 text-lg font-semibold"
            >
                <span v-if="isLoading" class="flex items-center gap-2">
                    <Vue3Lottie :animationData="universalLoader" :height="24" :width="24" />
                    Processing...
                </span>
                <span v-else class="flex items-center gap-2">
                    Reserve Now
                    <ChevronRight :size="20" />
                </span>
            </Button>
        </div>
    </section>

    <!-- Loader Overlay -->
    <div v-if="isLoading" class="loader-overlay">
        <Vue3Lottie :animationData="universalLoader" :height="120" :width="120" />
    </div>

    <Footer />
</template>

<style scoped>
.loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.aspect-video {
  aspect-ratio: 16/9;
}
</style>