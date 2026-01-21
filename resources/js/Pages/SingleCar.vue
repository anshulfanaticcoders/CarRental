<script setup>
// car overview icons import
import doorIcon from "../../assets/door.svg";
import luggageIcon from "../../assets/luggage.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import carbonIcon from "../../assets/carbon-emmision.svg";
import enginepowerIcon from "../../assets/enginepower.svg";
import MapPin from "../../assets/MapPin.svg";
import fullStar from "../../assets/fullstar.svg";
import halfStar from "../../assets/halfstar.svg";
import blankStar from "../../assets/blankstar.svg";
import carguaranteeIcon from "../../assets/carguarantee.png";
import locationPinIcon from "../../assets/locationPin.svg";
import SchemaInjector from '@/Components/SchemaInjector.vue';
import ShareIcon from "../../assets/ShareNetwork.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIcon.svg";
import partnersIcon from "../../assets/partners.svg";
import offerIcon from "../../assets/percentage-tag.svg";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { Vue3Lottie } from 'vue3-lottie';
import universalLoader from '../../../public/animations/universal-loader.json';
import { Skeleton } from '@/Components/ui/skeleton';
import '@vuepic/vue-datepicker/dist/main.css';
import VueDatepicker from '@vuepic/vue-datepicker';
import { useToast } from 'vue-toastification';
import { useCurrency } from '@/composables/useCurrency';

// Currency conversion variables
const { selectedCurrency, supportedCurrencies, changeCurrency } = useCurrency();
const exchangeRates = ref(null);
const currencySymbols = ref({});

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
    'R': 'ZAR',
    'AED': 'AED'
    // Add other symbol-to-code mappings as needed
};


import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/Components/ui/carousel";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import Autoplay from 'embla-carousel-autoplay';
// Fetching Vehicle Details
import { usePage } from "@inertiajs/vue3";
import { ChevronRight } from 'lucide-vue-next';


const { props } = usePage(); // Get the props passed from the controller
const locale = usePage().props.locale;
const vehicle = ref(props.vehicle);
const user = ref(null);
const reviews = ref([]);
const paymentPercentage = ref(0.00); // New ref for payment percentage
const affiliateData = ref(props.affiliate_data || null); // Affiliate data from session

const metaTitle = computed(() => {
  return `Rent ${vehicle.value.brand} ${vehicle.value.model} - ${vehicle.value.full_vehicle_address} - ${vehicle.value.id} - Vrooem`;
});

const canonicalUrl = computed(() => {
  return `${props.appUrl}${locale}/vehicle/${vehicle.value.id}`;
});
const isLoading = ref(true);

defineProps({
    schema: Object,
    appUrl: String,
});

// Reference to the reviews section
const reviewsSection = ref(null);

const scrollToReviews = () => {
    if (reviewsSection.value) {
        reviewsSection.value.scrollIntoView({ behavior: "smooth" });
    }
};


// getting authenticated user role info 
const authUser = props.auth?.user; // Get authenticated user
const isVendor = authUser?.role;


const plugin = Autoplay({
    delay: 2000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
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
onMounted(async () => {
    await fetchReviews();
    // Fetch payment percentage from API
    try {
        const response = await axios.get('/api/payment-percentage');
        if (response.data && response.data.payment_percentage !== undefined) {
            paymentPercentage.value = Number(response.data.payment_percentage);
        }
    } catch (error) {
        console.error('Error fetching payment percentage:', error);
        // Keep default 0.00 if API call fails
    }
});

const toast = useToast();

const shareVehicle = async () => {
    try {
        const shareData = {
            title: metaTitle.value,
            text: `Check out this amazing ${vehicle.value.brand} ${vehicle.value.model} for rent on Vrooem!`,
            url: canonicalUrl.value,
        };

        // First check basic sharing capability
        if (navigator.share) {
            // For image sharing (only on supported platforms)
            if (primaryImage.value?.image_url) {
                try {
                    // Check if files can be shared (Android Chrome)
                    if (navigator.canShare && navigator.canShare({ files: [] })) {
                        const response = await fetch(primaryImage.value.image_url);
                        const blob = await response.blob();
                        const file = new File([blob], 'car-image.jpg', { type: blob.type });
                        
                        // Try sharing with image
                        await navigator.share({
                            ...shareData,
                            files: [file]
                        });
                        return;
                    }
                } catch (fileShareError) {
                    console.log('File sharing not supported, falling back to text', fileShareError);
                }
            }
            
            // Fallback to regular share without files
            await navigator.share(shareData);
        } else {
            // Fallback for browsers without Web Share API
            const shareText = `${shareData.text}\n${shareData.url}`;
            
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(shareText);
                toast.success('Link copied to clipboard!');
            } else {
                // Final fallback - show prompt
                window.prompt('Copy this link:', shareData.url);
            }
        }
    } catch (error) {
        console.error('Error sharing:', error);
        if (error.name !== 'AbortError') {
            toast.error('Failed to share. Please try another method.');
        }
    }
};

const fetchReviews = async () => {
    isLoading.value = true;
    try {
        const vendorProfileId = vehicle.value.vendor_profile_data?.id;

        if (!vendorProfileId) {
            throw new Error("❌ Vendor profile ID is missing");
        }

        const response = await axios.get(`/api/vendors/${vendorProfileId}/reviews`);
        reviews.value = response.data.reviews;
    } catch (error) {
        console.error("❌ Error fetching reviews:", error);
    } finally {
        isLoading.value = false;
    }
};


const averageRating = computed(() => {
    if (!reviews.value.length) return 0; // No reviews, return 0
    const totalRating = reviews.value.reduce((sum, review) => sum + review.rating, 0);
    return (totalRating / reviews.value.length).toFixed(1); // Round to 1 decimal place
});


// Feature-Icon Mapping
const allFeaturesMap = ref({});

const fetchAllVehicleFeatures = async () => {
    try {
        // Assuming '/api/vehicle-features' returns all features: [{ id, feature_name, icon_url }, ...]
        // If you have a different endpoint or it's paginated, this might need adjustment.
        const response = await axios.get('/api/vehicle-features');
        if (response.data && Array.isArray(response.data)) {
            response.data.forEach(feature => {
                allFeaturesMap.value[feature.feature_name] = feature.icon_url;
            });
            // console.log('All Features Map Populated (SingleCar.vue):', JSON.parse(JSON.stringify(allFeaturesMap.value)));
            if (vehicle.value && vehicle.value.features && isValidJSON(vehicle.value.features)) {
                // console.log('Vehicle Specific Feature Names (SingleCar.vue):', JSON.parse(vehicle.value.features));
            } else {
                console.log('Vehicle features data (SingleCar.vue):', vehicle.value?.features);
            }
        } else {
            console.error('Failed to fetch all vehicle features or data is not an array (SingleCar.vue):', response.data);
        }
    } catch (error) {
        console.error('Error fetching all vehicle features (SingleCar.vue):', error);
    }
};


// Map Script
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { onMounted } from 'vue'

// Currency conversion functions
const fetchExchangeRates = async () => {
    try {
        const response = await fetch('/api/currency-rates');
        const data = await response.json();
        if (data.success) {
            exchangeRates.value = data.rates;
        } else {
            console.error('Failed to fetch exchange rates:', data.message || 'Unknown error');
        }
    } catch (error) {
        console.error('Error fetching exchange rates:', error);
    }
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

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$'; // Use fetched symbol or default to '$'
};

// Add this after your existing vehicle ref declaration
let map = null

// Add this function before the template
const initMap = () => {
    if (!vehicle.value || !vehicle.value.latitude || !vehicle.value.longitude) {
        console.warn('No vehicle location data available');
        return;
    }

    // Initialize map
    map = L.map('map', {
        zoomControl: true,
        maxZoom: 18,
        minZoom: 4,
    }).setView([vehicle.value.latitude, vehicle.value.longitude], 15);

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Create custom marker icon
    const customIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `
            <div class="marker-pin">
                
                <img src="${MapPin}" alt="Vehicle Location" loading="lazy" />
            </div>
        `,
        iconSize: [50, 30],
        iconAnchor: [25, 15],
        popupAnchor: [0, -15]
    });

    // Add marker
    const marker = L.marker([vehicle.value.latitude, vehicle.value.longitude], {
        icon: customIcon
    })
        .bindPopup(`
            <div class="text-center">
                <p class="font-semibold">${vehicle.value.brand}</p>
                <p>${vehicle.value.full_vehicle_address}</p>
            </div>
        `)
        .addTo(map);

    // Force a map refresh after a short delay
    setTimeout(() => {
        map.invalidateSize()
    }, 100)
}

// Add this in your script setup
onMounted(() => {
    initMap();
    fetchAllVehicleFeatures();
    fetchExchangeRates();

    // Load currency symbols
    try {
        fetch('/currency.json')
            .then(response => response.json())
            .then(data => {
                currencySymbols.value = data.reduce((acc, curr) => {
                    acc[curr.code] = curr.symbol;
                    return acc;
                }, {});
            })
            .catch(error => console.error("Error loading currency symbols:", error));
    } catch (error) {
        console.error("Error loading currency symbols:", error);
    }
});


import Faq from "@/Components/Faq.vue";
import Footer from "@/Components/Footer.vue";
const form = ref({
    where: "",
    date_from: "",
    date_to: "",
    time_from: "",
    time_to: "",
    latitude: null,
    longitude: null,
    radius: 831867.4340914232,
});

// Add these helper functions
const departureTimeOptions = computed(() => {
    return vehicle.value.pickup_times?.map(time => ({
        value: time,
        label: time
    })) || [];
});

const returnTimeOptions = computed(() => {
    return vehicle.value.return_times?.map(time => ({
        value: time,
        label: time
    })) || [];
});


// Function to update URL and session storage
const updateDateTimeSelection = () => {
    // Save to session storage
    sessionStorage.setItem('rentalDates', JSON.stringify({
        vehicle_id: vehicle.value.id, // Include vehicle ID in the stored data
        date_from: form.value.date_from,
        date_to: form.value.date_to,
        time_from: form.value.time_from,
        time_to: form.value.time_to
    }));
};

watch(() => vehicle.value.id, (newId, oldId) => {
    if (newId !== oldId) {
        clearStoredRentalDates();
    }
});
const clearStoredRentalDates = () => {
    sessionStorage.removeItem('rentalDates');
    form.value.date_from = '';
    form.value.date_to = '';
    form.value.time_from = '';
    form.value.time_to = '';
};

// Function to load saved dates from session storage
const loadSavedDates = () => {
    const savedDates = sessionStorage.getItem('rentalDates');
    if (savedDates) {
        const dates = JSON.parse(savedDates);
        if (dates.vehicle_id === vehicle.value.id) { // Check if the saved data matches the current vehicle ID
            form.value.date_from = dates.date_from || '';
            form.value.date_to = dates.date_to || '';
            form.value.time_from = dates.time_from || '';
            form.value.time_to = dates.time_to || '';
        }
    }

    // Load booked dates from props
    bookedDates.value = props.booked_dates || [];
};
// Call this in onMounted
onMounted(() => {
    loadSavedDates();
});

// formatted joined date of vendor
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.getFullYear();
};


const validateRentalDetails = () => {
    if (!form.value.date_from) {
        alert("Please select a pickup date.");
        return false;
    }
    if (!form.value.time_from) {
        alert("Please select a pickup time.");
        return false;
    }
    if (!form.value.date_to) {
        alert("Please select a return date.");
        return false;
    }
    if (!form.value.time_to) {
        alert("Please select a return time.");
        return false;
    }
    return true;
};


// Function to toggle favourite status
const fetchFavoriteStatus = async () => {
    if (!props.auth?.user) {
        return;
    }
    try {
        const response = await axios.get(route('favorites.status'));
        const favoriteIds = response.data; // The endpoint now returns an array of IDs
        const isFav = Array.isArray(favoriteIds) && favoriteIds.includes(vehicle.value.id);

        // Replace the object to ensure reactivity is triggered
        vehicle.value = { ...vehicle.value, is_favourite: isFav };
    } catch (error) {
        console.error("Error fetching favorite status:", error);
    }
};

// ✅ Toggle Favorite Status
const popEffect = ref(false);

const toggleFavourite = async (vehicle) => {
    if (!props.auth?.user) {
        return router.get(route('login', {}, usePage().props.locale)); // Redirect if not logged in
    }


    const endpoint = vehicle.is_favourite
        ? route('vehicles.unfavourite', { vehicle: vehicle.id })
        : route('vehicles.favourite', { vehicle: vehicle.id });

    try {
        await axios.post(endpoint);
        vehicle.is_favourite = !vehicle.is_favourite;

        // Trigger animation
        popEffect.value = true;

        toast.success(`Vehicle ${vehicle.is_favourite ? 'added to' : 'removed from'} favorites!`, {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            icon: vehicle.is_favourite ? '❤️' : '💔',
        });

    } catch (error) {
        if (error.response && error.response.status === 401) {
            router.get(route('login', {}, usePage().props.locale));
        } else {
            toast.error('Failed to update favorites', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        }
    }
};


// ✅ Fetch Data on Component Mount
onMounted(fetchFavoriteStatus);


import { Calendar, Clock } from 'lucide-vue-next';

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { CardHeader, CardTitle } from "@/Components/ui/card";
import { Inertia } from "@inertiajs/inertia";
import { Button } from "@/Components/ui/button";
import Lightbox from "@/Components/Lightbox.vue";

const selectedPackage = ref('day');
const dateError = ref('');

const pricingPackages = computed(() => [
    {
        id: 'day',
        label: 'Daily Package',
        description: 'Flexible daily rates',
        price: vehicle.value.price_per_day,
        icon: Clock,
        priceLabel: '/day'
    },
    {
        id: 'week',
        label: 'Weekly Package',
        description: 'Discounted weekly rates',
        price: vehicle.value.price_per_week,
        discount: vehicle.value.weekly_discount,
        icon: Calendar,
        priceLabel: '/week'
    },
    {
        id: 'month',
        label: 'Monthly Package',
        description: 'Best value for long-term',
        price: vehicle.value.price_per_month,
        discount: vehicle.value.monthly_discount,
        icon: Calendar,
        priceLabel: '/month'
    }
].filter(pkg => pkg.price)); // Only show packages with valid prices

const rentalDuration = computed(() => {
    if (!form.value.date_from || !form.value.date_to) return 0;
    const pickupDate = new Date(form.value.date_from);
    const returnDate = new Date(form.value.date_to);
    const diffTime = Math.abs(returnDate - pickupDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const calculateTotalPrice = computed(() => {
    if (!form.value.date_from || !form.value.date_to) return 0;

    const duration = rentalDuration.value;
    let totalPrice = 0;

    switch (selectedPackage.value) {
        case 'week':
            totalPrice = (duration / 7) * vehicle.value.price_per_week;
            if (vehicle.value.weekly_discount) {
                totalPrice = totalPrice - vehicle.value.weekly_discount;
            }
            break;
        case 'month':
            totalPrice = vehicle.value.price_per_month;
            if (vehicle.value.monthly_discount) {
                totalPrice = totalPrice - vehicle.value.monthly_discount;
            }
            break;
        default:
            totalPrice = duration * vehicle.value.price_per_day;
    }

    // Apply affiliate discount if available
    if (affiliateData.value && totalPrice > 0) {
        const discountType = affiliateData.value.discount_type || 'percentage';
        const discountValue = affiliateData.value.discount_value || 0;

        if (discountType === 'fixed_amount') {
            totalPrice = Math.max(0, totalPrice - discountValue);
        } else {
            // Percentage discount
            totalPrice = totalPrice * (1 - discountValue / 100);
        }
    }

    return totalPrice;
});

const discountAmount = computed(() => {
    const packageDetails = pricingPackages.value.find(pkg => pkg.id === selectedPackage.value);
    return Number(packageDetails?.discount || 0);
});

// Calculate original price before affiliate discount
const originalTotalPrice = computed(() => {
    if (!form.value.date_from || !form.value.date_to) return 0;

    const duration = rentalDuration.value;
    let totalPrice = 0;

    switch (selectedPackage.value) {
        case 'week':
            totalPrice = (duration / 7) * vehicle.value.price_per_week;
            if (vehicle.value.weekly_discount) {
                totalPrice = totalPrice - vehicle.value.weekly_discount;
            }
            break;
        case 'month':
            totalPrice = vehicle.value.price_per_month;
            if (vehicle.value.monthly_discount) {
                totalPrice = totalPrice - vehicle.value.monthly_discount;
            }
            break;
        default:
            totalPrice = duration * vehicle.value.price_per_day;
    }

    return totalPrice;
});

// Calculate affiliate discount amount
const affiliateDiscountAmount = computed(() => {
    if (!affiliateData.value) return 0;

    const originalPrice = originalTotalPrice.value;
    if (originalPrice <= 0) return 0;

    const discountType = affiliateData.value.discount_type || 'percentage';
    const discountValue = affiliateData.value.discount_value || 0;

    if (discountType === 'fixed_amount') {
        return Math.min(discountValue, originalPrice);
    } else {
        // Percentage discount
        return originalPrice * (discountValue / 100);
    }
});

// Check if user has affiliate discount
const hasAffiliateDiscount = computed(() => {
    return affiliateData.value && affiliateData.value.discount_value > 0;
});


const formatPrice = (price) => {
    const originalCurrency = vehicle.value.vendor_profile?.currency || 'USD';
    const convertedPrice = convertCurrency(price, originalCurrency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};


const validateDates = () => {
    if (!form.value.date_from || !form.value.date_to) return;
    const pickupDate = new Date(form.value.date_from);
    const returnDate = new Date(form.value.date_to);
    const diffDays = Math.ceil((returnDate - pickupDate) / (1000 * 60 * 60 * 24));

    let dateError = '';
    switch (selectedPackage.value) {
        case 'week':
            if (diffDays % 7 !== 0 || diffDays > 28) {
                dateError = 'Weekly rentals must be for 7, 14, 21, or 28 days';
                form.value.date_to = '';
            }
            break;
        case 'month':
            const monthDiff = (returnDate.getMonth() - pickupDate.getMonth()) +
                (12 * (returnDate.getFullYear() - pickupDate.getFullYear()));
            if (monthDiff !== 1) {
                dateError = 'Monthly rentals must be for exactly one month';
                form.value.date_to = '';
            }
            break;
        case 'day':
            if (diffDays > 30) {
                dateError = 'Daily rentals cannot exceed 30 days';
                form.value.date_to = '';
            }
            break;
    }
    if (dateError) toast.error(dateError);
};



const bookedDates = ref(props.booked_dates || []);
const blockedDates = ref(props.blocked_dates || []);


// Create a function to check if a date is booked
const isDateBooked = (dateStr) => {
    if (!dateStr || (!bookedDates.value.length && !blockedDates.value.length)) return false;

    const checkDate = new Date(dateStr);
    checkDate.setHours(0, 0, 0, 0);

    return bookedDates.value.some(({ pickup_date, return_date }) => {
        const pickupDate = new Date(pickup_date);
        const returnDate = new Date(return_date);
        pickupDate.setHours(0, 0, 0, 0);
        returnDate.setHours(0, 0, 0, 0);
        return checkDate >= pickupDate && checkDate <= returnDate;
    }) || blockedDates.value.some(({ blocking_start_date, blocking_end_date }) => {
        const startDate = new Date(blocking_start_date);
        const endDate = new Date(blocking_end_date);
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(0, 0, 0, 0);
        return checkDate >= startDate && checkDate <= endDate;
    });
};



// Create a function that returns all disabled dates as an array
const getDisabledDates = () => {
    const disabledDates = [];
    const addDisabledDates = (dates, startProp, endProp) => {
        dates.forEach(dateRange => {
            const start = new Date(dateRange[startProp]);
            const end = new Date(dateRange[endProp]);
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                disabledDates.push(new Date(d));
            }
        });
    };
    addDisabledDates(bookedDates.value, 'pickup_date', 'return_date');
    addDisabledDates(blockedDates.value, 'blocking_start_date', 'blocking_end_date');
    return disabledDates;
};
const disabledDates = computed(() => getDisabledDates());



const handleDateInput = (event, type) => {
    const selectedDate = event.target.value;
    const otherDate = type === 'pickup' ? form.value.date_to : form.value.date_from;

    // Check if selected date falls within a booked or blocked range
    if (isDateBooked(selectedDate)) {
        if (type === 'pickup') {
            form.value.date_from = '';
        } else {
            form.value.date_to = '';
        }

        const availableDates = findNextAvailableDates(selectedDate);
        // toast.error(`Vehicle is not available on ${selectedDate}.`);

        if (availableDates.length) {
            toast.info(`Next available date: ${availableDates[0]}`);
        }
        return;
    }

    // Check if the entire selected range is already booked or blocked
    if (otherDate) {
        const startDate = type === 'pickup' ? selectedDate : otherDate;
        const endDate = type === 'pickup' ? otherDate : selectedDate;

        // Find conflicting booking
        const conflictingBooking = findConflictingBooking(startDate, endDate);

        if (conflictingBooking) {
            if (type === 'pickup') {
                form.value.date_from = '';
            } else {
                form.value.date_to = '';
            }

            // Use the actual conflicting dates in the error message
            const conflictStart = new Date(conflictingBooking.pickup_date || conflictingBooking.blocking_start_date)
                .toISOString().split('T')[0];
            const conflictEnd = new Date(conflictingBooking.return_date || conflictingBooking.blocking_end_date)
                .toISOString().split('T')[0];

            toast.error(`Vehicle already booked from ${conflictStart} to ${conflictEnd}.`);
        }
    }

    // Reset return date when a new pickup date is selected
    if (type === 'pickup') {
        form.value.date_to = '';
    }
};

// Helper function to find a conflicting booking
const findConflictingBooking = (startDateStr, endDateStr) => {
    const startDate = new Date(startDateStr);
    const endDate = new Date(endDateStr);
    startDate.setHours(0, 0, 0, 0);
    endDate.setHours(0, 0, 0, 0);

    // Check booked dates
    const conflictingBooked = bookedDates.value.find(({ pickup_date, return_date }) => {
        const pickupDate = new Date(pickup_date);
        const returnDate = new Date(return_date);
        pickupDate.setHours(0, 0, 0, 0);
        returnDate.setHours(0, 0, 0, 0);
        return (startDate <= returnDate && endDate >= pickupDate);
    });

    if (conflictingBooked) return conflictingBooked;

    // Check blocked dates
    const conflictingBlocked = blockedDates.value.find(({ blocking_start_date, blocking_end_date }) => {
        const blockStartDate = new Date(blocking_start_date);
        const blockEndDate = new Date(blocking_end_date);
        blockStartDate.setHours(0, 0, 0, 0);
        blockEndDate.setHours(0, 0, 0, 0);
        return (startDate <= blockEndDate && endDate >= blockStartDate);
    });

    return conflictingBlocked;
};

// Add this method
const validateInitialDates = () => {
    // Skip if no dates are prefilled
    if (!form.value.date_from || !form.value.date_to) return;

    const startDate = form.value.date_from;
    const endDate = form.value.date_to;

    // Check if any single date is booked
    if (isDateBooked(startDate)) {
        const availableDates = findNextAvailableDates(startDate);
        // toast.error(`Vehicle is not available on ${startDate}.`);
        if (availableDates.length) {
            toast.info(`Next available date: ${availableDates[0]}`);
        }
        form.value.date_from = '';
        form.value.date_to = '';
        return;
    }

    if (isDateBooked(endDate)) {
        const availableDates = findNextAvailableDates(endDate);
        // toast.error(`Vehicle is not available on ${endDate}.`);
        if (availableDates.length) {
            toast.info(`Next available date: ${availableDates[0]}`);
        }
        form.value.date_to = '';
        return;
    }

    // Check if the entire range conflicts
    const conflictingBooking = findConflictingBooking(startDate, endDate);
    if (conflictingBooking) {
        const conflictStart = new Date(conflictingBooking.pickup_date || conflictingBooking.blocking_start_date)
            .toISOString().split('T')[0];
        const conflictEnd = new Date(conflictingBooking.return_date || conflictingBooking.blocking_end_date)
            .toISOString().split('T')[0];

        toast.error(`Selected dates conflict with existing booking from ${conflictStart} to ${conflictEnd}.`);
        form.value.date_from = '';
        form.value.date_to = '';
    } else {
        // If dates are valid, also validate against rental package rules
        validateDates();
    }
};

// Call this in your component's created/mounted hook
onMounted(() => {
    validateInitialDates();
});

// You could also watch for changes to prefilled data
watch(() => props.booked_dates, () => {
    bookedDates.value = props.booked_dates || [];
    validateInitialDates();
});


const findNextAvailableDates = (fromDate, count = 5) => {
    const startDate = new Date(fromDate);
    const availableDates = [];

    for (let i = 1; i <= 30 && availableDates.length < count; i++) {
        startDate.setDate(startDate.getDate() + 1);
        const dateStr = startDate.toISOString().split('T')[0];

        if (!isDateBooked(dateStr)) {
            availableDates.push(dateStr);
        }
    }

    return availableDates;
};

const getDayClass = (date) => {
    const dateStr = date.toISOString().split('T')[0];
    const isBooked = bookedDates.value.some(range => {
        const start = new Date(range.pickup_date);
        const end = new Date(range.return_date);
        return date >= start && date <= end;
    });
    const isBlocked = blockedDates.value.some(range => {
        const start = new Date(range.blocking_start_date);
        const end = new Date(range.blocking_end_date);
        return date >= start && date <= end;
    });
    if (isBooked) return 'booked-date';
    if (isBlocked) return 'blocked-date';
    return '';
};

// Minimum date (today)
const minDate = computed(() => new Date());

// Maximum pickup date (e.g., 3 months from now)
const maxPickupDate = computed(() => {
    const today = new Date();
    const futureDate = new Date(today);
    futureDate.setMonth(today.getMonth() + 3);
    return futureDate;
});

// Minimum return date based on package and pickup date
const minReturnDate = computed(() => {
    if (!form.value.date_from) return new Date();
    const pickupDate = new Date(form.value.date_from);
    const minDate = new Date(pickupDate);
    switch (selectedPackage.value) {
        case 'week':
            minDate.setDate(pickupDate.getDate() + 7);
            break;
        case 'month':
            minDate.setMonth(pickupDate.getMonth() + 1);
            break;
        default:
            minDate.setDate(pickupDate.getDate() + 1);
    }
    return minDate;
});

// Maximum return date based on package
const maxReturnDate = computed(() => {
    if (!form.value.date_from) return null;
    const pickupDate = new Date(form.value.date_from);
    const maxDate = new Date(pickupDate);
    switch (selectedPackage.value) {
        case 'week':
            maxDate.setDate(pickupDate.getDate() + 28);
            break;
        case 'month':
            maxDate.setMonth(pickupDate.getMonth() + 1);
            break;
        default:
            maxDate.setDate(pickupDate.getDate() + 30);
    }
    return maxDate;
});

// Handle date selection
const handleDateFrom = (date) => {
    form.value.date_from = date ? date.toISOString().split('T')[0] : '';
    form.value.date_to = ''; // Reset return date when pickup changes
    updateDateTimeSelection();
    validateDates();
};

const handleDateTo = (date) => {
    form.value.date_to = date ? date.toISOString().split('T')[0] : '';
    updateDateTimeSelection();
    validateDates();
};



// Watch for package changes
watch(selectedPackage, () => {
    form.value.date_to = ''; // Reset return date when package changes
    dateError.value = '';
    localStorage.removeItem('rentalData');
});



// Function to store rental data in localStorage
const storeRentalData = () => {
    const rentalData = {
        packageType: selectedPackage.value,
        dateFrom: form.value.date_from,
        dateTo: form.value.date_to,
        duration: rentalDuration.value,
        totalPrice: calculateTotalPrice.value,
        discountAmount: discountAmount.value,
        selectedPackageDetails: pricingPackages.value.find(pkg => pkg.id === selectedPackage.value),
        vehicleDetails: {
            id: vehicle.value.id,
            name: vehicle.value.name,
            price_per_day: vehicle.value.price_per_day,
            price_per_week: vehicle.value.price_per_week,
            price_per_month: vehicle.value.price_per_month
        }
    };

    localStorage.setItem('rentalData', JSON.stringify(rentalData));
};

const isValidJSON = (str) => {
    try {
        JSON.parse(str);
        return true;
    } catch (e) {
        return false;
    }
};

// Watch for date changes
watch([() => form.value.date_from, () => form.value.date_to], () => {
    if (form.value.date_from && form.value.date_to) {
        validateDates();
        if (!dateError.value) {
            storeRentalData();
        }
    }
});

watch([
    selectedPackage,
    () => form.value.date_from,
    () => form.value.date_to
], () => {
    if (form.value.date_from && form.value.date_to && !dateError.value) {
        storeRentalData();
    }
});

const showWarningModal = ref(false);
const isBooking = ref(false);
const proceedToPayment = async () => { // Make the function async
    if (!props.auth?.user) {
        sessionStorage.setItem('returnToUrl', window.location.href);
        return router.get(route('login', {}, usePage().props.locale));
    }
    // Validate rental details before proceeding
    if (!validateRentalDetails()) {
        return; // Stop the function if validation fails
    }

    if (isVendor === 'vendor') {
        showWarningModal.value = true;
        return;
    }

    isBooking.value = true;

    try { // Add try block for the API call and navigation
        // Call backend to set session variable allowing booking page access
        await axios.post(route('booking.allow_access'));
        console.log('Booking access permission set.'); // Optional: for debugging

        // Your existing logic for localStorage and sessionStorage:
        storeRentalData(); // Persists data to localStorage, potentially for other uses

        // Clear session storage items from any previous booking attempt
        sessionStorage.removeItem('selectionData');
        sessionStorage.removeItem('driverInfo');

        // Prepare data for sessionStorage to pass to the booking page
        const bookingDataForSession = {
            vehicleId: vehicle.value.id,
            packageType: selectedPackage.value,
            dateFrom: form.value.date_from,
            dateTo: form.value.date_to,
            timeFrom: form.value.time_from,
            timeTo: form.value.time_to,
            totalPrice: calculateTotalPrice.value,
            discountAmount: discountAmount.value,
            duration: rentalDuration.value,
            selectedPackageDetails: pricingPackages.value.find(pkg => pkg.id === selectedPackage.value),
            vehicleDetails: {
                id: vehicle.value.id,
                brand: vehicle.value.brand,
                model: vehicle.value.model,
                category: vehicle.value.category?.name,
                primaryImageUrl: primaryImage.value?.image_url,
                full_vehicle_address: vehicle.value.full_vehicle_address,
                price_per_day: vehicle.value.price_per_day,
                price_per_week: vehicle.value.price_per_week,
                price_per_month: vehicle.value.price_per_month
            },
            vendorDetails: {
                company_name: vehicle.value.vendor_profile_data?.company_name,
                currency: vehicle.value.vendor_profile?.currency
            }
        };
        sessionStorage.setItem('bookingDetails', JSON.stringify(bookingDataForSession));
        console.log('Booking details stored in session storage:', bookingDataForSession); // Optional: for debugging

        // Proceed to payment page with currency parameter
        router.get(route('booking.show', { id: vehicle.value.id, currency: selectedCurrency.value }), {
            onFinish: () => {
                isBooking.value = false;
            },
        });

    } catch (error) {
        console.error("Error setting booking access or navigating:", error);
        // You might want to use your toast notification system here
        // For example: toast.error('Could not proceed to booking. Please try again.');
        alert('Could not proceed to booking. Please try again.'); // Simple alert as a placeholder
        isBooking.value = false;
    }
};




const queryParams = new URLSearchParams(window.location.search);
const initialPickupDate = queryParams.get('pickup_date') || '';
const initialReturnDate = queryParams.get('return_date') || '';
const initialPackageType = queryParams.get('package') || 'day';
selectedPackage.value = initialPackageType;
form.value.date_from = initialPickupDate;
form.value.date_to = initialReturnDate;

onMounted(() => {
    clearStoredRentalDates();
    loadSavedDates(); // Ensure this function respects the initial values set above
    // Additional logic to ensure the dates are properly set in the form
    form.value.date_from = initialPickupDate;
    form.value.date_to = initialReturnDate;
});

// Lightbox for gallery
const lightboxRef = ref(null);

// Computed properties for easier access
const primaryImage = computed(() => {
    if (!props.vehicle?.images) return null;
    return props.vehicle.images.find(image => image.image_type === 'primary');
});

const galleryImages = computed(() => {
    if (!props.vehicle?.images) return [];
    return props.vehicle.images.filter(image => image.image_type === 'gallery');
});

const allImages = computed(() => {
    if (!props.vehicle?.images) return [];
    // Create an array with primary image first, followed by gallery images
    const primary = primaryImage.value ? [primaryImage.value] : [];
    return [...primary, ...galleryImages.value];
});

// Method to open the lightbox
const openLightbox = (index) => {
    lightboxRef.value.openLightbox(index);
};
const searchUrl = computed(() => {
    if (typeof window !== 'undefined' && sessionStorage.getItem('searchurl')) {
        return sessionStorage.getItem('searchurl');
    }
    return '';
});
</script>

<template>

    <Head>
        <meta name="robots" content="index, follow" />
        <title>{{ metaTitle }}</title>
        <meta name="keywords" :content="seoKeywords" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="metaTitle" />
        <meta property="og:image" :content="primaryImage?.image_url" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="metaTitle" />
        <meta name="twitter:image" :content="primaryImage?.image_url" />
    </Head>
    <SchemaInjector v-if="schema" :schema="schema" />
    <SchemaInjector v-if="$page.props.organizationSchema" :schema="$page.props.organizationSchema" />
    <AuthenticatedHeaderLayout />
    <main>
        <section>
            <div class="full-w-container py-customVerticalSpacing max-[768px]:py-0">
                <div class="breadcrumb mb-8 flex items-center gap-2 max-[768px]:mt-8 max-[768px]:text-[0.85rem]">
                    <Link :href="`/${$page.props.locale}`" class="text-customPrimaryColor">Home</Link>
                    <ChevronRight class="h-5 w-5 text-customPrimaryColor" />
                    <Link :href="searchUrl ? `/${$page.props.locale}${searchUrl}` : `/${$page.props.locale}/s`"
                        class="text-customPrimaryColor">Vehicle</Link>
                    <ChevronRight class="h-5 w-5 text-customPrimaryColor" />
                    <span class="font-medium">{{ vehicle?.brand }} {{ vehicle?.model }}</span>
                </div>
                <div class="flex gap-2 items-center mb-1 max-[768px]:hidden">
                    <h4 class="font-medium">{{ vehicle?.brand }}</h4>
                    <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]">
                        {{ vehicle?.category.name }}
                    </span>
                </div>
                <div class="flex gap-2 items-center text-[1.25rem] max-[768px]:hidden">
                    <div class="car_ratings flex gap-2 items-center cursor-pointer" v-if="reviews.length > 0"
                        @click="scrollToReviews">
                        <div class="flex items-center gap-1">
                            <img v-for="n in 5" :key="n" :src="getStarIcon(averageRating, n)"
                                :alt="getStarAltText(averageRating, n)" class="w-[20px] h-[20px]" loading="lazy" />
                        </div>
                        <span>{{ averageRating }} ({{ reviews.length }})</span>
                    </div>
                    <p v-else>No ratings yet.</p>

                    <div class="dot_seperator"><strong>.</strong></div>
                    <div class="car_location">
                        <span>{{ vehicle?.full_vehicle_address }}</span>
                    </div>
                </div>
                <div>
                    <div class="w-full mt-4 flex gap-2 max-[768px]:flex-col">
                        <!-- Primary image -->
                        <div class="primary-image w-[60%] max-h-[500px] aspect-video max-[768px]:w-full max-[768px]:aspect-[4/3] cursor-pointer"
                            @click="openLightbox(0)">
                            <img v-if="!isLoading && vehicle?.images" :src="primaryImage?.image_url" alt="Primary Image"
                                class="w-full h-full object-cover rounded-lg transition-all duration-300 hover:brightness-90"
                                loading="lazy" />
                            <Skeleton v-else
                                class="w-full h-[500px] object-cover rounded-lg max-[768px]:w-full max-[768px]:max-h-[200px]" />
                        </div>

                        <!-- Gallery images -->
                        <div
                            class="gallery w-[40%] grid grid-cols-2 gap-2 max-h-[500px] max-[768px]:w-full max-[768px]:flex">
                            <template v-if="vehicle?.images && vehicle.images.length > 5">
                                <div v-for="(image, index) in galleryImages.slice(0, 3)" :key="image.id"
                                    class="gallery-item max-[768px]:flex-1 max-[768px]:aspect-square cursor-pointer"
                                    @click="openLightbox(index + 1)">
                                    <img v-if="!isLoading && vehicle" :src="image.image_url"
                                        :alt="`Gallery Image ${index + 1}`"
                                        class="w-full h-[245px] object-cover rounded-lg max-[768px]:h-full transition-all duration-300 hover:brightness-90"
                                        loading="lazy" />
                                    <Skeleton v-else
                                        class="w-full h-[245px] object-cover rounded-lg max-[768px]:h-full" />
                                </div>

                                <!-- View All overlay -->
                                <div class="gallery-item max-[768px]:flex-1 max-[768px]:aspect-square cursor-pointer relative"
                                    @click="openLightbox(4)">
                                    <div
                                        class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-lg z-50">
                                        <span class="text-white text-lg font-semibold max-[768px]:text-[0.75rem]">
                                            +{{ vehicle.images.length - 5 }} View All
                                        </span>
                                    </div>
                                    <img v-if="!isLoading && vehicle" :src="galleryImages[3].image_url"
                                        alt="View All Images"
                                        class="w-full h-[245px] object-cover rounded-lg max-[768px]:h-full opacity-50"
                                        loading="lazy" />
                                    <Skeleton v-else
                                        class="w-full h-[245px] object-cover rounded-lg max-[768px]:h-full" />
                                </div>
                            </template>

                            <!-- Default gallery rendering when 5 or fewer images -->
                            <div v-else v-for="(image, index) in galleryImages" :key="image.id"
                                class="gallery-item max-[768px]:flex-1 max-[768px]:aspect-square cursor-pointer"
                                @click="openLightbox(index + 1)">
                                <img v-if="!isLoading && vehicle" :src="image.image_url"
                                    :alt="`Gallery Image ${index + 1}`"
                                    class="w-full h-[245px] object-cover rounded-lg max-[768px]:h-full transition-all duration-300 hover:brightness-90"
                                    loading="lazy" />
                                <Skeleton v-else class="w-full h-[245px] object-cover rounded-lg max-[768px]:h-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Import the Lightbox component -->
                    <Lightbox ref="lightboxRef" :images="allImages" />
                </div>
                <div class="mobile_display hidden max-[768px]:block max-[768px]:mt-8">
                    <div class="flex gap-2 items-center mb-1">
                        <h4 class="font-medium max-[768px]:text-[1.25rem]">{{ vehicle?.brand }}</h4>
                        <span
                            class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] max-[768px]:text-[1rem]">
                            {{ vehicle?.category.name }}
                        </span>
                    </div>
                    <div class="flex gap-2 items-center text-[1.25rem] max-[768px]:flex-wrap">
                        <div class="car_ratings flex gap-2 items-center" v-if="reviews.length > 0"
                            @click="scrollToReviews">
                            <div class="flex items-center gap-1">
                                <img v-for="n in 5" :key="n" :src="getStarIcon(averageRating, n)"
                                    :alt="getStarAltText(averageRating, n)" class="w-[20px] h-[20px]" loading="lazy" />
                            </div>
                            <span class="max-[768px]:text-[0.875rem]">{{ averageRating }} ({{ reviews.length }})</span>
                        </div>
                        <p v-else class="max-[768px]:text-[12px] max-[768px]:mt-2">No ratings yet.</p>

                        <div class="dot_seperator"><strong>.</strong></div>
                        <div class="car_location">
                            <span class="text-[0.875rem]"><span>{{ vehicle?.full_vehicle_address }}</span></span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-[4rem] max-[768px]:flex-col max-[768px]:mt-10">
                    <div class="column w-[50%] max-[768px]:w-full">
                        <div class="column flex flex-col gap-10 max-[768px]:gap-5">
                            <!-- Vehicle Features Section -->
                            <span class="text-[2rem] font-medium max-[768px]:text-[1rem]">Car Overview</span>
                            <div class="features grid grid-cols-4 gap-x-[2rem] gap-y-[2rem] max-[768px]:grid-cols-3">
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="peopleIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">People</span>
                                        <span class="font-medium text-[1rem] max-[768px]:text-[0.85rem]">{{
                                            vehicle?.seating_capacity
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="doorIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Doors</span>
                                        <span class="font-medium text-[1rem] max-[768px]:text-[0.85rem]">{{
                                            vehicle?.number_of_doors
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="luggageIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Luggage</span>
                                        <span class="font-medium text-[1rem] max-[768px]:text-[0.85rem]">{{
                                            vehicle?.luggage_capacity
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="transmisionIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Transmission</span>
                                        <span class="font-medium capitalize max-[768px]:text-[0.85rem]">{{
                                            vehicle?.transmission
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="fuelIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Fuel
                                            Type</span>
                                        <span class="font-medium capitalize max-[768px]:text-[0.85rem]">{{
                                            vehicle?.fuel
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="enginepowerIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Horsepower</span>
                                        <span class="font-medium text-[1rem] max-[768px]:text-[0.85rem]">{{
                                            vehicle?.horsepower }} hp</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="carbonIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Co2
                                            Emission</span>
                                        <span class="font-medium text-[1rem] max-[768px]:text-[0.85rem]">{{ vehicle?.co2
                                        }} (g/km)</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="mileageIcon" alt=""
                                        class='w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px]'
                                        loading="lazy" />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-customLightGrayColor text-[1rem] max-[768px]:text-[0.75rem]">Mileage</span>
                                        <span class="font-medium text-[1rem] max-[768px]:text-[0.85rem]">{{
                                            vehicle?.mileage }} km/L</span>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <div class="features mt-[3rem]">
                            <span class="text-[2rem] font-medium max-[768px]:text-[1rem]">Features</span>
                            <div v-if="vehicle?.features && isValidJSON(vehicle.features) && JSON.parse(vehicle.features).length > 0"
                                class="grid grid-cols-4 mt-[2rem] gap-y-[2rem] max-[768px]:mt-[1rem] max-[768px]:grid-cols-3">
                                <div v-for="(featureName, index) in JSON.parse(vehicle.features)" :key="index"
                                    class="flex items-center gap-3 max-[768px]:text-[0.65rem] whitespace-nowrap">
                                    <img v-if="allFeaturesMap[featureName]" :src="allFeaturesMap[featureName]"
                                        :alt="featureName"
                                        class="feature-icon w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px] object-contain"
                                        loading="lazy" />
                                    <span v-else
                                        class="feature-icon w-[30px] h-[30px] max-[768px]:w-[24px] max-[768px]:h-[24px] inline-flex items-center justify-center text-gray-400 text-xs border rounded">?</span>
                                    <!-- Placeholder for no icon -->
                                    {{ featureName }}
                                </div>
                            </div>
                            <div v-else class="mt-[2rem]">
                                <p>No features listed for this vehicle.</p>
                            </div>
                        </div>

                        <div class="mt-[3rem] max-[768px]:mt-[2rem]">
                            <span class="text-[2rem] font-medium max-[768px]:text-[1rem]">Car Location</span>
                            <div
                                class="gap-y-[2rem] max-[768px]:mt-[0.5rem] flex items-end mt-[1rem] gap-2 max-[768px]:gap-1 max-[768px]:items-center">
                                <img :src=locationPinIcon alt="" class="w-8 h-8 max-[768px]:w-6" loading="lazy"> <span
                                    class="text-[1.2rem] max-[768px]:text-[0.95rem]">{{ vehicle?.full_vehicle_address
                                    }}</span>
                            </div>
                            <div id="map" class="h-full rounded-lg mt-4 z-10"></div>
                        </div>

                        <div class="mt-8 md:mt-16 max-w-4xl mx-auto">
                            <h2 class="text-xl md:text-2xl lg:text-3xl font-medium mb-4 md:mb-6">Rental Conditions &
                                Benefits</h2>

                            <div class="p-4 md:p-6 border rounded-lg shadow-sm bg-white">
                                <!-- If there are benefits, display them -->
                                <div v-if="vehicle?.benefits && Object.keys(vehicle?.benefits).length > 0"
                                    class="space-y-4">
                                    <!-- Kilometer Limitations -->
                                    <div v-if="vehicle?.benefits?.limited_km_per_day || vehicle?.benefits?.limited_km_per_week || vehicle?.benefits?.limited_km_per_month"
                                        class="border-b pb-4">
                                        <h3 class="text-lg md:text-xl font-medium mb-3 text-gray-800">Distance
                                            Limitations</h3>

                                        <div class="space-y-3">
                                            <div v-if="vehicle?.benefits?.limited_km_per_day"
                                                class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                                <span class="font-medium text-blue-700">Daily Limit:</span>
                                                <span class="text-base">{{ vehicle?.benefits?.limited_km_per_day_range
                                                }} km/day</span>
                                                <span class="text-gray-700">
                                                    (Extra: {{ formatPrice(vehicle?.benefits?.price_per_km_per_day)
                                                    }}/km)
                                                </span>
                                            </div>

                                            <div v-if="vehicle?.benefits?.limited_km_per_week"
                                                class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                                <span class="font-medium text-blue-700">Weekly Limit:</span>
                                                <span class="text-base">{{ vehicle?.benefits?.limited_km_per_week_range
                                                }} km/week</span>
                                                <span class="text-gray-700">
                                                    (Extra: {{ formatPrice(vehicle?.benefits?.price_per_km_per_week)
                                                    }}/km)
                                                </span>
                                            </div>

                                            <div v-if="vehicle?.benefits?.limited_km_per_month"
                                                class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                                <span class="font-medium text-blue-700">Monthly Limit:</span>
                                                <span class="text-base">{{ vehicle?.benefits?.limited_km_per_month_range
                                                }} km/month</span>
                                                <span class="text-gray-700">
                                                    (Extra: {{ formatPrice(vehicle?.benefits?.price_per_km_per_month)
                                                    }}/km)
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancellation Policies -->
                                    <div v-if="vehicle?.benefits?.cancellation_available_per_day || vehicle?.benefits?.cancellation_available_per_week || vehicle?.benefits?.cancellation_available_per_month"
                                        class="border-b pb-4">
                                        <h3 class="text-lg md:text-xl font-medium mb-3 text-gray-800">Cancellation
                                            Policy</h3>

                                        <div class="space-y-3">
                                            <div v-if="vehicle?.benefits?.cancellation_available_per_day"
                                                class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                                <span class="font-medium text-blue-700">Daily Package:</span>
                                                <span class="text-base">Free cancellation up to {{
                                                    vehicle?.benefits?.cancellation_available_per_day_date }} days
                                                    before rental</span>
                                            </div>

                                            <div v-if="vehicle?.benefits?.cancellation_available_per_week"
                                                class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                                <span class="font-medium text-blue-700">Weekly Package:</span>
                                                <span class="text-base">Free cancellation up to {{
                                                    vehicle?.benefits?.cancellation_available_per_week_date }} days
                                                    before rental</span>
                                            </div>

                                            <div v-if="vehicle?.benefits?.cancellation_available_per_month"
                                                class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                                <span class="font-medium text-blue-700">Monthly Package:</span>
                                                <span class="text-base">Free cancellation up to {{
                                                    vehicle?.benefits?.cancellation_available_per_month_date }} days
                                                    before rental</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Other Requirements -->
                                    <div v-if="vehicle?.benefits?.minimum_driver_age">
                                        <h3 class="text-lg md:text-xl font-medium mb-3 text-gray-800">Requirements</h3>

                                        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                                            <span class="font-medium text-blue-700">Minimum Driver Age:</span>
                                            <span class="text-base">{{ vehicle?.benefits?.minimum_driver_age }}
                                                years</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fallback Message if No Benefits Exist -->
                                <p v-else class="text-gray-500 text-base md:text-lg">
                                    No additional benefits available for this vehicle.
                                </p>
                            </div>
                        </div>

                        <div class="mt-[3rem]">
                            <h2 class="text-xl md:text-2xl lg:text-3xl font-medium mb-4 md:mb-6"> Guarantee Deposite (
                                {{
                                    formatPrice(vehicle.security_deposit) }} )</h2>

                            <!-- Payment Methods Section -->
                            <div class="flex flex-col gap-3">
                                <span class="max-[768px]:text-[0.875rem]">You can make payment to vendor for security
                                    deposite using
                                    these <strong>payment
                                        methods...</strong></span>
                                <div v-if="isValidJSON(vehicle.payment_method)"
                                    class="flex gap-3 max-[768px]:flex-wrap">
                                    <span v-for="(payment_method, index) in JSON.parse(vehicle.payment_method)"
                                        :key="index"
                                        class="max-[768px]:text-[0.875rem] bg-customLightPrimaryColor py-2 px-3 rounded-sm capitalize border-[1px] border-customLightGrayColor">{{
                                            payment_method }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="vehicle.guidelines" class="mt-[3rem]">
                            <h2 class="text-xl md:text-2xl lg:text-3xl font-medium mb-4 md:mb-6">Guidelines</h2>
                            <p class="whitespace-pre-wrap p-2 bg-customLightPrimaryColor rounded-[10px]">{{ vehicle.guidelines }}</p>
                        </div>

                        <div v-else>

                        </div>

                        <div class="mt-[5rem] max-[768px]:mt-[2rem]">
                            <span class="text-[2rem] font-medium max-[768px]:text-[1rem]">Meet Vehicle Vendor</span>
                            <div class="mt-[2rem] flex gap-5 border-[1px] border-customPrimaryColor
                                 rounded-[0.75em] px-[1rem] py-[2rem] max-[768px]:py-[1rem]">
                                <img :src="vehicle.vendor_profile?.avatar
                                    ? `${vehicle.vendor_profile.avatar}`
                                    : '/storage/avatars/default-avatar.svg'" alt="User Avatar"
                                    class="w-[100px] h-[100px] max-[768px]:w-[60px] max-[768px]:h-[60px] rounded-full object-cover"
                                    v-if="!isLoading" loading="lazy" />
                                <Skeleton v-else
                                    class="w-[100px] h-[100px] max-[768px]:w-[60px] max-[768px]:h-[60px] rounded-full object-cover" />
                                <div>
                                    <h4
                                        class="text-customPrimaryColor text-[1.75rem] font-medium max-[768px]:text-[1rem]">
                                        {{ vehicle.vendor_profile_data.company_name }}
                                    </h4>
                                    <p class="text-customLightGrayColor max-[768px]:text-[0.95rem]">{{
                                        vehicle.vendor_profile.about }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column w-[40%] max-[768px]:w-full max-[768px]:mt-[2rem]">
                        <div class="paymentInfoDiv p-5 sticky top-[3rem]">
                            <div class="flex items-center justify-between gap-3 max-[768px]:mb-4">
                                <div class="flex items-center gap-4 max-[768px]:w-[75%]">
                                    <h4
                                        class="max-[768px]:text-[1rem] max-[768px]:max-w-[170px] max-[768px]:overflow-hidden max-[768px]:text-ellipsis max-[768px]:whitespace-nowrap">
                                        {{ vehicle?.brand }} {{ vehicle?.model }}</h4>
                                    <span
                                        class="bg-[#f5f5f5] inline-block px-8 py-2 max-[768px]:text-nowrap max-[768px]:px-4 text-center rounded-[40px] max-[768px]:text-[0.75rem]">
                                        {{ vehicle?.category.name }}
                                    </span>
                                </div>
                                <div class="icons flex items-center gap-3">
                                    <button @click="shareVehicle" class="max-[768px]:w-[1.5rem]"><img :src="ShareIcon" alt=""
                                        loading="lazy" /></button>
                                    <button @click.stop="toggleFavourite(vehicle)" class="heart-icon"
                                        :class="{ 'filled-heart': vehicle.is_favourite, 'pop-animation': popEffect }"
                                        @animationend="popEffect = false"
                                        :disabled="authUser?.role === 'vendor' || authUser?.role === 'admin'">
                                        <img :src="vehicle.is_favourite ? FilledHeart : Heart" alt="Favorite"
                                            class="w-[2rem] max-[768px]:w-[1.5rem] transition-colors duration-300"
                                            loading="lazy" />
                                    </button>

                                </div>
                            </div>
                            <div class="max-[768px]:text-[0.85rem]">
                                <span>Hosted by
                                    <span class="vendorName uppercase font-medium">
                                        . {{ vehicle.vendor_profile_data.company_name }}
                                    </span>
                                </span>
                            </div>
                            <div class="car_short_info mt-[1rem] flex gap-3">
                                <img :src="carIcon" alt="" class="max-[768px]:w-[24px]" loading="lazy" />
                                <div class="features">
                                    <span class="text-[1.15rem] capitalize max-[768px]:text-[0.85rem]">
                                        {{ vehicle?.transmission }} .
                                        {{ vehicle?.fuel }} .
                                        {{ vehicle?.seating_capacity }} Seats
                                    </span>
                                </div>
                            </div>
                            <div class="extra_details flex gap-5 mt-[1rem]">

                                <div class="col flex gap-3">
                                    <img :src="mileageIcon" alt="" class="max-[768px]:w-[24px]" loading="lazy" />
                                    <span class="text-[1.15rem] max-[768px]:text-[0.85rem]">{{ vehicle?.mileage }}
                                        km/L</span>
                                </div>
                            </div>

                            <div class="ratings"></div>

                            <div class="location mt-[2rem]">
                                <span
                                    class="text-[1.5rem] font-medium mb-[1rem] inline-block max-[768px]:text-[1.2rem]">Location</span>
                                <div class="col flex items-start gap-4">
                                    <img :src="pickupLocationIcon" alt="" class="max-[768px]:w-[24px]" loading="lazy" />
                                    <div class="flex flex-col gap-1">
                                        <span>Pickup Location</span>
                                        <span class="text-[1.25rem] text-medium max-[768px]:text-[1rem]">{{
                                            vehicle?.full_vehicle_address }}</span>
                                        <span class="max-[768px]:text-[0.95rem]">{{ route().params.pickup_date }}</span>
                                    </div>
                                </div>
                                <div class="col flex items-start gap-4 mt-10">
                                    <img :src="returnLocationIcon" alt="" class="max-[768px]:w-[24px]" loading="lazy" />
                                    <div class="flex flex-col gap-1">
                                        <span>Return Location</span>
                                        <span class="text-[1.25rem] text-medium max-[768px]:text-[1rem]">{{
                                            vehicle?.full_vehicle_address }}</span>
                                        <span class="max-[768px]:text-[0.95rem]">{{ route().params.return_date }}</span>
                                    </div>
                                </div>



                                <div class="pricing py-5">
                                    <div class="column flex items-center justify-between">
                                        <div class="mx-auto px-6 max-[768px]:px-0 max-[768px]:w-full">
                                            <Card>
                                                <CardHeader>
                                                    <div v-if="paymentPercentage > 0" class="flex gap-3 items-end bg-yellow-100 p-2 rounded-[12px] max-[768px]:mb-3">
                                                        <img :src="offerIcon" alt="" class="w-6 h-6">
                                                    <p class="text-lg text-customDarkBlackColor font-bold max-[768px]:text-[0.75rem]">
                                                        Pay <span class="text-green-500">{{ paymentPercentage }}%</span> now and rest pay on arrival
                                                    </p>
                                                    </div>
                                                    <div class="flex items-center justify-between mb-4">
                                                        <CardTitle class="inline-block text-[1rem]">Choose Your Rental
                                                            Package</CardTitle>
                                                    </div>
                                                    <div
                                                        class="relative overflow-hidden h-10 mb-4 rounded-lg bg-white shadow-sm max-[768px]:mt-[2rem]">
                                                        <!-- Left gradient fade -->
                                                        <div
                                                            class="absolute left-0 top-0 h-full w-12 bg-gradient-to-r from-white to-transparent z-10">
                                                        </div>

                                                        <!-- Right gradient fade -->
                                                        <div
                                                            class="absolute right-0 top-0 h-full w-12 bg-gradient-to-l from-white to-transparent z-10">
                                                        </div>

                                                        <!-- Marquee content -->
                                                        <div
                                                            class="marquee-wrapper overflow-hidden relative w-full h-10">
                                                            <div
                                                                class="marquee-content flex absolute whitespace-nowrap animate-marquee">
                                                                <div class="flex items-center gap-3 px-4">
                                                                    <img :src="carguaranteeIcon" alt="Guarantee Icon"
                                                                        class="w-5 h-5 object-contain" loading="lazy" />
                                                                    <p
                                                                        class="text-sm text-gray-800 font-medium tracking-wide">
                                                                        No car on arrival? <span
                                                                            class="text-blue-600 font-semibold">Vrooem.com</span>
                                                                        guarantees you’ll get one. All vehicles come
                                                                        from trusted rental
                                                                        companies handpicked by us.
                                                                    </p>
                                                                </div>
                                                                <!-- Duplicate for seamless loop -->
                                                                <div class="flex items-center gap-3 px-4">
                                                                    <img :src="carguaranteeIcon" alt="Guarantee Icon"
                                                                        class="w-5 h-5 object-contain" loading="lazy" />
                                                                    <p
                                                                        class="text-sm text-gray-800 font-medium tracking-wide">
                                                                        No car on arrival? <span
                                                                            class="text-blue-600 font-semibold">Vrooem.com</span>
                                                                        guarantees you’ll get one. All vehicles come
                                                                        from trusted rental
                                                                        companies handpicked by us.
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>

                                                </CardHeader>
                                                <CardContent>
                                                    <!-- Package Selection -->
                                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                                                        <div v-for="pkg in pricingPackages" :key="pkg.id"
                                                            @click="selectedPackage = pkg.id" :class="[
                                                                'cursor-pointer rounded-lg border p-4 transition-all duration-200 hover:shadow-md',
                                                                selectedPackage === pkg.id
                                                                    ? 'border-blue-500 bg-blue-50'
                                                                    : 'border-gray-200 hover:border-blue-200'
                                                            ]">
                                                            <div class="flex items-center gap-3 mb-2">
                                                                <component :is="pkg.icon" class="w-6 h-6" />
                                                                <span class="font-semibold text-[1rem]">{{ pkg.label
                                                                    }}</span>
                                                            </div>
                                                            <p class="text-sm text-gray-600 mb-2">{{ pkg.description }}
                                                            </p>
                                                            <p class="text-lg font-bold text-blue-600">
                                                                {{ formatPrice(pkg.price) }}
                                                                <span class="text-sm font-normal text-gray-600">{{
                                                                    pkg.priceLabel }}</span>
                                                            </p>
                                                            <p v-if="pkg.discount" class="text-sm text-green-600">
                                                                Discount: {{ formatPrice(pkg.discount) }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Date Selection -->
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <!-- Pickup Date -->
                                                        <div>
                                                            <label
                                                                class="block text-sm font-medium text-gray-700 mb-2">Pickup
                                                                Date</label>
                                                            <VueDatepicker v-model="form.date_from" :min-date="minDate"
                                                                :max-date="maxPickupDate" :day-class="getDayClass"
                                                                :disabled-dates="disabledDates"
                                                                @update:model-value="handleDateFrom"
                                                                placeholder="Select pickup date" class="w-full"
                                                                :enable-time-picker="false" :clearable="true"
                                                                :format="'yyyy-MM-dd'" auto-apply />
                                                        </div>

                                                        <!-- Return Date -->
                                                        <div>
                                                            <label
                                                                class="block text-sm font-medium text-gray-700 mb-2">Return
                                                                Date</label>
                                                            <VueDatepicker v-model="form.date_to"
                                                                :min-date="minReturnDate" :max-date="maxReturnDate"
                                                                :day-class="getDayClass" :disabled-dates="disabledDates"
                                                                @update:model-value="handleDateTo"
                                                                placeholder="Select return date" class="w-full"
                                                                :enable-time-picker="false" :clearable="true"
                                                                :format="'yyyy-MM-dd'" auto-apply />
                                                        </div>
                                                        <div>
                                                            <!-- Departure Time Dropdown -->
                                                            <label
                                                                class="block text-sm mt-3 mb-1 text-customLightGrayColor font-medium">Departure
                                                                Time</label>
                                                            <select v-model="form.time_from"
                                                                @change="updateDateTimeSelection"
                                                                class="p-2 rounded border border-customMediumBlackColor w-full text-customPrimaryColor">
                                                                <option value="">Select time</option>
                                                                <option v-for="option in departureTimeOptions"
                                                                    :key="option.value" :value="option.value">
                                                                    {{ option.label }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <!-- Return Time Dropdown -->
                                                            <label
                                                                class="block text-sm mt-3 mb-1 text-customLightGrayColor font-medium">Return
                                                                Time</label>
                                                            <select v-model="form.time_to"
                                                                @change="updateDateTimeSelection"
                                                                class="p-2 rounded border border-customMediumBlackColor w-full text-customPrimaryColor">
                                                                <option value="">Select time</option>
                                                                <option v-for="option in returnTimeOptions"
                                                                    :key="option.value" :value="option.value">
                                                                    {{ option.label }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                    <!-- Show Total Price -->
                                                    <div v-if="form.date_from && form.date_to && !dateError"
                                                        class="mt-6 p-4 bg-blue-50 rounded-lg">
                                                        <!-- Regular pricing -->
                                                        <p class="text-lg max-[768px]:text-[1rem] font-semibold">
                                                            Current Price: {{formatPrice(pricingPackages.find(pkg =>
                                                                pkg.id === selectedPackage).price)}}
                                                        </p>
                                                        <p v-if="pricingPackages.find(pkg => pkg.id === selectedPackage).discount"
                                                            class="text-lg max-[768px]:text-[1rem] text-green-600">
                                                            Discount: -{{formatPrice(pricingPackages.find(pkg => pkg.id
                                                                === selectedPackage).discount)}}
                                                        </p>

                                                        <!-- Affiliate Discount Section -->
                                                        <div v-if="hasAffiliateDiscount" class="border-t border-blue-200 mt-3 pt-3">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                                <p class="text-sm font-semibold text-green-700">
                                                                    🎉 Special Affiliate Discount Applied!
                                                                </p>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <p class="text-sm text-gray-600">
                                                                    Original Price: <span class="line-through">{{ formatPrice(originalTotalPrice) }}</span>
                                                                </p>
                                                                <p class="text-sm text-green-600 font-semibold">
                                                                    Affiliate Discount ({{ affiliateData.discount_type === 'percentage' ? affiliateData.discount_value + '%' : formatPrice(affiliateData.discount_value) }}):
                                                                    -{{ formatPrice(affiliateDiscountAmount) }}
                                                                </p>
                                                                <p class="text-sm text-gray-500">
                                                                    From: {{ affiliateData.business_name }}
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Final Total Price -->
                                                        <p
                                                            class="text-[1.75rem] max-[768px]:text-[1.5rem] font-semibold mt-3 pt-3 border-t border-blue-200">
                                                            Total Price: {{ formatPrice(calculateTotalPrice) }}
                                                        </p>
                                                        <p class="text-sm text-gray-600">
                                                            for {{ rentalDuration }} days of rental
                                                        </p>
                                                    </div>

                                                    <!-- Error Message -->
                                                    <Alert v-if="dateError" class="mt-4" variant="destructive">
                                                        <AlertDescription>{{ dateError }}</AlertDescription>
                                                    </Alert>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                    <!-- Warning Modal -->
                                    <Dialog v-model:open="showWarningModal">
                                        <DialogContent class="">
                                            <DialogHeader>
                                                <DialogTitle>Access Denied</DialogTitle>
                                                <DialogDescription>
                                                    You cannot proceed to the payment page because you are a vendor, not
                                                    a customer.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter>
                                                <Button @click="showWarningModal = false" class="button-primary">
                                                    Okay
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                    <div class="column mt-[2rem]">
                                        <button @click="proceedToPayment" :disabled="isBooking"
                                            class="button-primary block text-center p-5 w-full max-[768px]:text-[0.875rem]">Proceed
                                            to Pay</button>
                                    </div>
                                    <div
                                        class="column text-center mt-[2rem] flex flex-col justify-center items-center gap-5">
                                        <p>Guaranteed safe & secure checkout</p>
                                        <img :src="partnersIcon" alt="" loading="lazy" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-customVerticalSpacing">
            <Faq />
        </section>

        <section ref="reviewsSection" class="" style="background: linear-gradient(to bottom, #FFFFFF, #F8F8F8);">
            <div class="reviews-section mt-[3rem] full-w-container max-[768px]:mt-0">
                <span class="text-[2rem] font-bold">Overall Rating</span>

                <div v-if="isLoading">
                    <div class="flex gap-4">
                        <Skeleton v-for="n in 3" :key="n" class="h-[15rem] w-[30%] rounded-lg" />
                    </div>
                </div>
                <div v-else-if="reviews && reviews.length > 0">
                    <Carousel class="relative w-full py-[4rem] px-[2rem] max-[768px]:px-0 max-[768px]:py-[2rem]"
                        :plugins="[plugin]" @mouseenter="plugin.stop"
                        @mouseleave="[plugin.reset(), plugin.play(), console.log('Running')]">
                        <CarouselContent class="max-[768px]:px-5">
                            <CarouselItem v-for="review in reviews" :key="review.id"
                                class="pl-1 md:basis-1/2 lg:basis-1/3 ml-[1rem]">
                                <Card class="h-[15rem]">
                                    <CardContent>
                                        <div class="review-item px-[1rem] py-[2rem] h-full">
                                            <div class="flex items-center gap-3">
                                                <img :src="review.user.profile?.avatar ? `${review.user.profile?.avatar}` : '/storage/avatars/default-avatar.svg'"
                                                    alt="User Avatar"
                                                    class="w-[50px] h-[50px] rounded-full object-cover"
                                                    loading="lazy" />
                                                <div>
                                                    <h4
                                                        class="text-customPrimaryColor font-medium max-[768px]:text-[1.1rem]">
                                                        {{ review.user.first_name }} {{ review.user.last_name }}
                                                    </h4>
                                                    <div class="flex items-center gap-1">
                                                        <div class="star-rating">
                                                            <img v-for="n in 5" :key="n"
                                                                :src="getStarIcon(review.rating, n)"
                                                                :alt="getStarAltText(review.rating, n)"
                                                                class="w-[20px] h-[20px]" loading="lazy" />
                                                        </div>
                                                        <span>{{ review.rating }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="mt-2 max-[768px]:text-[0.875rem]">{{ review.review_text }}</p>
                                            <div v-if="review.reply_text"
                                                class="mt-2 reply-text border-[1px] rounded-[0.75em] px-[1rem] py-[1rem] bg-[#f5f5f5]">
                                                <p class="text-gray-600">Vendor Reply:</p>
                                                <p>{{ review.reply_text }}</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </CarouselItem>
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                    <div class="flex justify-center pb-[3rem] max-[768px]:mt-[3rem]">
                        <a v-if="reviews && reviews.length > 0"
                            :href="`/${$page.props.locale}/vendor/${vehicle.vendor_profile_data?.id}/reviews`"
                            class="button-primary px-[2rem] py-[0.75rem]">View all</a>
                    </div>
                </div>
                <div v-else class="mt-[2rem] pb-[3rem]">
                    <p>No reviews yet.</p>
                </div>
            </div>
        </section>

        <section class="full-w-container py-customVerticalSpacing">
            <div
                class="mt-[2rem] max-[768px]:mt-0 flex items-center justify-center gap-5 border-[1px] border-customPrimaryColor rounded-[0.75em] px-[1rem] py-[2rem]">
                <div class="flex flex-col items-center gap-5 w-[50%] max-[768px]:w-full">
                    <img :src="vehicle.vendor_profile?.avatar
                        ? `${vehicle.vendor_profile.avatar}`
                        : '/storage/avatars/default-avatar.svg'" alt="User Avatar"
                        class="w-[100px] h-[100px] max-[768px]:w-[60px] max-[768px]:h-[60px] rounded-full object-cover"
                        v-if="!isLoading" loading="lazy" />
                    <Skeleton v-else
                        class="w-[100px] h-[100px] max-[768px]:w-[60px] max-[768px]:h-[60px] rounded-full object-cover" />

                    <h4 class="text-customPrimaryColor text-[1.75rem] font-medium max-[768px]:text-[1.2rem]">
                        {{ vehicle.vendor_profile_data.company_name }}
                    </h4>
                    <span>On VROOEM since {{ formatDate(vehicle.user.created_at) }}</span>
                    <div class="flex justify-center w-full max-[768px]:flex-wrap max-[768px]:gap-5">
                        <div class="col flex flex-col items-center">
                            <p
                                class="capitalize text-[1.5rem] text-customPrimaryColor font-bold max-[768px]:text-[1.2rem]">
                                {{
                                    vehicle?.vendor_profile_data.status }}</p>
                            <span class="text-customLightGrayColor max-[768px]:text-[1rem]">Verification Status</span>
                        </div>
                        <!-- <div class="col flex flex-col items-center">
                            <p class="capitalize text-[1.5rem] text-customPrimaryColor font-bold">{{
                                vehicle?.vendor_profile_data.status }}</p>
                            <span class="text-customLightGrayColor">Verification Status</span>
                        </div>
                        <div class="col flex flex-col items-center">
                            <p class="capitalize text-[1.5rem] text-customPrimaryColor font-bold">{{
                                vehicle?.vendor_profile_data.status }}</p>
                            <span class="text-customLightGrayColor">Verification Status</span>
                        </div>
                        <div class="col flex flex-col items-center">
                            <p class="capitalize text-[1.5rem] text-customPrimaryColor font-bold">{{
                                vehicle?.vendor_profile_data.status }}</p>
                            <span class="text-customLightGrayColor">Verification Status</span>
                        </div> -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    <Footer />

    <!-- Loader Overlay -->
    <div v-if="isBooking" class="loader-overlay">
        <Vue3Lottie :animation-data="universalLoader" :height="200" :width="200" />
    </div>
</template>

<style scoped>
@import 'leaflet/dist/leaflet.css';

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
.overview .col:not(:last-child) {
    border-bottom: 1px solid #2b2b2b;
}

.overview .col {
    padding: 2rem;
}

.paymentInfoDiv {
    border-radius: 0.75rem;
    border: 0.5px solid #ede7e7;
    background: #fff;
    box-shadow: 0px 0px 32px 0px rgba(196, 196, 196, 0.24);
}

.galley-item {
    border-radius: 0.75rem;
    border: 0.5px solid #ede7e7;
    background: #fff;
    box-shadow: 0px 0px 32px 0px rgba(196, 196, 196, 0.24);
}



.marker-pin {
    width: auto;
    min-width: 50px;
    height: 30px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.marker-pin span {
    color: black;
    font-weight: bold;
    font-size: 12px;
    padding: 0 8px;
}

.custom-div-icon {
    background: none;
    border: none;
}

#map {
    height: 400px;
    width: 100%;
}

.star-rating {
    display: flex;
    /* Ensure stars are displayed horizontally */
}

.reviews-section .next-btn {
    top: 0;
    justify-content: center;
    z-index: 99;
}

.reviews-section .prev-btn {
    top: 0;
    left: 90% !important;
    justify-content: center;
    z-index: 99;
}

.vehicle-benefits>li::before {
    content: "";
    background-color: #153b4f;
    height: 0.5rem;
    width: 0.5rem;
    border-radius: 100%;
    display: flex;
    margin-right: 0.75rem;
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


@keyframes marquee {
    0% {
        transform: translateX(0%);
    }

    100% {
        transform: translateX(-50%);
    }
}

.marquee-wrapper {
    position: relative;
    overflow: hidden;
}

.marquee-content {
    animation: marquee 25s linear infinite;
    display: flex;
}



:deep(.dp__cell_inner.booked-date) {
    color: red !important;
    background-color: rgba(255, 0, 0, 0.1);
}

:deep(.dp__cell_inner.blocked-date) {
    color: red !important;
    background-color: rgba(255, 0, 0, 0.1);
}

:deep(.dp__cell_inner.dp__cell_disabled) {
    opacity: 0.6;
}



@media screen and (max-width:768px) {
    .reviews-section .next-btn {
        top: 100% !important;
        left: 60%;
        justify-content: center;
        z-index: 99;
    }

    .reviews-section .prev-btn {
        top: 100% !important;
        left: 30% !important;
        justify-content: center;
        z-index: 99;
    }

    #map {
        height: 200px;
    }

}
</style>
