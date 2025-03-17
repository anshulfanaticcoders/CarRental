<script setup>
// car overview icons import
import doorIcon from "../../assets/door.svg";
import luggageIcon from "../../assets/luggage.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import carbonIcon from "../../assets/carbon-emmision.svg";
import ageIcon from "../../assets/age.svg";
import enginepowerIcon from "../../assets/enginepower.svg";
import unlimitedKmIcon from "../../assets/unlimitedKm.svg";
import cancellationIcon from "../../assets/cancellationAvailable.svg";
import starIcon from "../../assets/stars.svg";
import MapPin from "../../assets/MapPin.svg";
import fullStar from "../../assets/fullstar.svg";
import halfStar from "../../assets/halfstar.svg";
import blankStar from "../../assets/blankstar.svg";

import ShareIcon from "../../assets/ShareNetwork.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import carIcon from "../../assets/carIcon.svg";
import walkIcon from "../../assets/walking.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIcon.svg";
import partnersIcon from "../../assets/partners.svg";
import infoIcon from "../../assets/WarningCircle.svg";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
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

const { props } = usePage(); // Get the props passed from the controller
const vehicle = ref(props.vehicle);
const user = ref(null);
const reviews = ref([]);
const isLoading = ref(true);

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
});

const fetchReviews = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/vehicles/${props.vehicle.id}/reviews`);
        reviews.value = response.data.reviews;
    } catch (error) {
        console.error("Error fetching reviews:", error);
    } finally {
        isLoading.value = false;
    }
};
const formatTime = (timeString) => {
    if (!timeString) return ''; // Handle null or undefined time

    const [hours, minutes] = timeString.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const formattedHours = hours % 12 === 0 ? 12 : hours % 12;

    return `${formattedHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};
const averageRating = computed(() => {
    if (!reviews.value.length) return 0; // No reviews, return 0
    const totalRating = reviews.value.reduce((sum, review) => sum + review.rating, 0);
    return (totalRating / reviews.value.length).toFixed(1); // Round to 1 decimal place
});


// Feature-Icon Mapping
const featureIconMap = {
    "Bluetooth": "/storage/icons/bluetooth.svg",
    "Music System": "/storage/icons/music.svg",
    "Toolkit": "/storage/icons/toolkit.svg",
    "USB Charger": "/storage/icons/usb.svg",
    "Key Lock": "/storage/icons/lock.svg",
    "Back Camera": "/storage/icons/camera.svg",
    "Voice Control": "/storage/icons/voiceControl.svg",
    "Navigation": "/storage/icons/gps-navigation.png",
};


// Map Script
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { onMounted } from 'vue'

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
                
                <img src="${MapPin}" alt="Vehicle Location" />
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
                <p>${vehicle.value.location}</p>
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
});

import axios from "axios";
import { router } from "@inertiajs/vue3";
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
const searchResults = ref([]);

const handleSearchInput = async () => {
    if (form.value.where.length < 3) return;

    try {
        const response = await axios.get(
            `/api/geocoding/autocomplete?text=${encodeURIComponent(
                form.value.where
            )}`
        );
        searchResults.value = response.data.features;
    } catch (error) {
        console.error("Error fetching locations:", error);
    }
};

const selectLocation = (result) => {
    form.value.where = result.properties?.label || "Unknown Location";
    form.value.latitude = result.geometry.coordinates[1];
    form.value.longitude = result.geometry.coordinates[0];
    searchResults.value = [];

    const latLng = [form.value.latitude, form.value.longitude];

};

const submit = () => {
    router.get("/s", form.value);
};

const getCurrentDate = () => {
    return new Date().toISOString().split('T')[0];
};
// Add these helper functions
const departureTimeOptions = [
    { value: '09:00', label: 'From 9 AM' },
    { value: '14:00', label: 'From 2 PM' },
];

const returnTimeOptions = [
    { value: '12:00', label: 'Before 12 PM' },
    { value: '20:00', label: 'Before 8 PM' },
];

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
import { useToast } from 'vue-toastification';
const toast = useToast();
const fetchFavoriteStatus = async () => {
    try {
        const response = await axios.get("/favorites");
        const favoriteIds = response.data.map(v => v.id);

        // ✅ Check if this vehicle is in favorites
        vehicle.value.is_favourite = favoriteIds.includes(vehicle.value.id);
    } catch (error) {
        console.error("Error fetching favorite status:", error);
    }
};

// ✅ Toggle Favorite Status
const toggleFavourite = async () => {
    if (!props.auth?.user) {
        return Inertia.visit('/login'); // Redirect if not logged in
    }

    const endpoint = vehicle.value.is_favourite
        ? `/vehicles/${vehicle.value.id}/unfavourite`
        : `/vehicles/${vehicle.value.id}/favourite`;

    try {
        await axios.post(endpoint);
        vehicle.value.is_favourite = !vehicle.value.is_favourite;

        toast.success(`Vehicle ${vehicle.value.is_favourite ? 'added to' : 'removed from'} favorites!`, {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            icon: vehicle.value.is_favourite ? '❤️' : '💔',
        });

    } catch (error) {
        if (error.response && error.response.status === 401) {
            Inertia.visit('/login');
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

    return totalPrice;
});

const discountAmount = computed(() => {
    const packageDetails = pricingPackages.value.find(pkg => pkg.id === selectedPackage.value);
    return Number(packageDetails?.discount || 0);
});


const formatPrice = (price) => {
    const currencySymbol = vehicle.value.vendor_profile.currency;
    return `${currencySymbol}${price}`;
};


const validateDates = () => {
    if (!form.value.date_from || !form.value.date_to) return;

    const pickupDate = new Date(form.value.date_from);
    const returnDate = new Date(form.value.date_to);
    const diffDays = rentalDuration.value;


    switch (selectedPackage.value) {
        case 'week':
            if (diffDays % 7 !== 0 || diffDays > 28) {
                dateError.value = 'Weekly rentals must be for 7, 14, 21, or 28 days';
                form.value.date_to = '';
                toast.error(dateError.value);
            }
            break;

        case 'month':
            const monthDiff = (returnDate.getMonth() - pickupDate.getMonth()) +
                (12 * (returnDate.getFullYear() - pickupDate.getFullYear()));
            if (monthDiff !== 1) {
                dateError.value = 'Monthly rentals must be for exactly one month';
                form.value.date_to = '';
                toast.error(dateError.value);
            }
            break;

        case 'day':
            if (diffDays > 30) {
                dateError.value = 'Daily rentals cannot exceed 30 days';
                form.value.date_to = '';
                toast.error(dateError.value);
            }
            break;
    }
};


const blockedStartDate = props.vehicle.blocking_start_date;
const blockedEndDate = props.vehicle.blocking_end_date;
const bookedDates = ref(props.booked_dates || []);
const blockedDates = ref([]);
// Add blocked dates to blockedDates array
if (blockedStartDate && blockedEndDate) {
    blockedDates.value.push({
        blocking_start_date: blockedStartDate,
        blocking_end_date: blockedEndDate
    });
}

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

const isDateRangeBooked = (startDateStr, endDateStr) => {
    const startDate = new Date(startDateStr);
    const endDate = new Date(endDateStr);
    startDate.setHours(0, 0, 0, 0);
    endDate.setHours(0, 0, 0, 0);

    return bookedDates.value.some(({ pickup_date, return_date }) => {
        const pickupDate = new Date(pickup_date);
        const returnDate = new Date(return_date);
        pickupDate.setHours(0, 0, 0, 0);
        returnDate.setHours(0, 0, 0, 0);
        return (startDate <= returnDate && endDate >= pickupDate);
    }) || blockedDates.value.some(({ blocking_start_date, blocking_end_date }) => {
        const blockStartDate = new Date(blocking_start_date);
        const blockEndDate = new Date(blocking_end_date);
        blockStartDate.setHours(0, 0, 0, 0);
        blockEndDate.setHours(0, 0, 0, 0);
        return (startDate <= blockEndDate && endDate >= blockStartDate);
    });
};

// Create a function that returns all disabled dates as an array
const getDisabledDates = () => {
    const disabledDates = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const addDisabledDates = (dates, startProp, endProp) => {
        dates.forEach(dateRange => {
            const start = new Date(dateRange[startProp]);
            const end = new Date(dateRange[endProp]);
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                if (d >= today) {
                    disabledDates.push(new Date(d).toISOString().split('T')[0]);
                }
            }
        });
    };

    addDisabledDates(bookedDates.value, 'pickup_date', 'return_date');
    addDisabledDates(blockedDates.value, 'blocking_start_date', 'blocking_end_date');

    return disabledDates;
};
const disabledDates = computed(() => getDisabledDates());

const maxPickupDate = computed(() => {
    // Find the furthest future date that isn't booked
    const today = new Date();
    const futureDate = new Date(today);
    futureDate.setMonth(today.getMonth() + 3); // Look 3 months ahead
    return futureDate.toISOString().split('T')[0];
});

const handleDateInput = (event, type) => {
    const selectedDate = event.target.value;
    const endDate = type === 'pickup' ? form.value.date_to : form.value.date_from;

    if (type === 'pickup' && endDate && isDateRangeBooked(selectedDate, endDate)) {
        form.value.date_from = '';
        toast.error(`Vehicle already booked from ${blockedStartDate} to ${blockedEndDate}.`);
    } else if (type === 'return' && endDate && isDateRangeBooked(endDate, selectedDate)) {
        form.value.date_to = '';
        toast.error(`Vehicle already booked from ${blockedStartDate} to ${blockedEndDate}.`);
    } else if (isDateBooked(selectedDate)) {
        if (type === 'pickup') {
            form.value.date_from = '';
        } else {
            form.value.date_to = '';
        }

        const availableDates = findNextAvailableDates(selectedDate);
        if (availableDates.length) {
            toast.info(`Next available date: ${availableDates[0]}`);
        }
    } else if (type === 'pickup') {
        form.value.date_to = '';
    }
};

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

const minReturnDate = computed(() => {
    if (!form.value.date_from) return getCurrentDate();

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

    [...bookedDates.value, ...blockedDates.value].forEach(({ return_date, blocking_end_date }) => {
        const endDate = new Date(return_date || blocking_end_date);
        if (endDate >= minDate) {
            minDate.setDate(endDate.getDate() + 1);
        }
    });

    return minDate.toISOString().split('T')[0];
});

const maxReturnDate = computed(() => {
    if (!form.value.date_from) return '';

    const pickupDate = new Date(form.value.date_from);
    const maxDate = new Date(pickupDate);

    switch (selectedPackage.value) {
        case 'week':
            maxDate.setDate(pickupDate.getDate() + 28); // Max 4 weeks
            break;
        case 'month':
            maxDate.setMonth(pickupDate.getMonth() + 1); // Exactly 1 month
            break;
        default:
            maxDate.setDate(pickupDate.getDate() + 30); // Max 30 days
    }

    return maxDate.toISOString().split('T')[0];
});

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
    () => form.value.date_to,
    calculateTotalPrice
], () => {
    if (form.value.date_from && form.value.date_to && !dateError.value) {
        storeRentalData();
    }
});

const showWarningModal = ref(false);
const proceedToPayment = () => {
    // Validate rental details before proceeding
    if (!validateRentalDetails()) {
        return; // Stop the function if validation fails
    }

    if (isVendor === 'vendor') {
        showWarningModal.value = true;
        return;
    }
    storeRentalData();
    // Proceed to payment page with query parameters
    router.get(`/booking/${vehicle.value.id}`, {
        packageType: selectedPackage.value,
        dateFrom: form.value.date_from,
        dateTo: form.value.date_to,
        timeFrom: form.value.time_from,
        timeTo: form.value.time_to,
        totalPrice: calculateTotalPrice.value,
        discountAmount: discountAmount.value,
    });

};

// Get package type from query parameter
const urlParams = new URLSearchParams(window.location.search);
const initialPackageType = urlParams.get('package') || 'day';
selectedPackage.value = initialPackageType;
</script>

<template>

    <Head title="Single Car" />
    <AuthenticatedHeaderLayout />
    <main>
        <section>
            <div class="full-w-container py-customVerticalSpacing max-[480px]:py-0">
                <div class="flex gap-2 items-center mb-1 max-[480px]:hidden">
                    <h4 class="font-medium">{{ vehicle?.brand }}</h4>
                    <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]">
                        {{ vehicle?.category.name }}
                    </span>
                </div>
                <div class="flex gap-2 items-center text-[1.25rem] max-[480px]:hidden">
                    <div class="car_ratings flex gap-2 items-center" v-if="reviews.length > 0">
                        <div class="flex items-center gap-1">
                            <img v-for="n in 5" :key="n" :src="getStarIcon(averageRating, n)"
                                :alt="getStarAltText(averageRating, n)" class="w-[20px] h-[20px]" />
                        </div>
                        <span>{{ averageRating }} ({{ reviews.length }})</span>
                    </div>
                    <p v-else>No ratings yet.</p>

                    <div class="dot_seperator"><strong>.</strong></div>
                    <div class="car_location">
                        <span>{{ vehicle?.location }}</span>
                    </div>
                </div>
                <div class="w-full mt-[1rem] flex gap-2 max-[480px]:flex-col">
                    <div class="primary-image w-[60%] max-h-[500px] max-[480px]:w-full max-[480px]:max-h-auto">
                        <img v-if="vehicle?.images" :src="`${vehicle.images.find(
                            (image) => image.image_type === 'primary'
                        )?.image_url
                            }`" alt="Primary Image" class="w-full h-full object-cover rounded-lg" />
                    </div>

                    <!-- Display the gallery images -->
                    <div class="gallery w-[50%] grid grid-cols-2 gap-2 max-h-[245px] max-[480px]:w-full max-[480px]:flex max-[480px]:h-[100px]">
                        <div v-for="(image, index) in vehicle?.images.filter(
                            (image) => image.image_type === 'gallery'
                        )" :key="image.id" class="gallery-item max-[480px]:flex-1">
                            <img :src="`${image.image_url}`" :alt="`Gallery Image ${index + 1}`"
                                class="w-full h-[245px] object-cover rounded-lg max-[480px]:h-full" />
                        </div>
                    </div>
                </div>
                <div class="mobile_display hidden max-[480px]:block max-[480px]:mt-8">
                    <div class="flex gap-2 items-center mb-1">
                    <h4 class="font-medium max-[480px]:text-[1.25rem]">{{ vehicle?.brand }}</h4>
                    <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] max-[480px]:text-[1rem]">
                        {{ vehicle?.category.name }}
                    </span>
                </div>
                <div class="flex gap-2 items-center text-[1.25rem] max-[480px]:flex-wrap">
                    <div class="car_ratings flex gap-2 items-center" v-if="reviews.length > 0">
                        <div class="flex items-center gap-1">
                            <img v-for="n in 5" :key="n" :src="getStarIcon(averageRating, n)"
                                :alt="getStarAltText(averageRating, n)" class="w-[20px] h-[20px]" />
                        </div>
                        <span class="max-[480px]:text-[0.875rem]">{{ averageRating }} ({{ reviews.length }})</span>
                    </div>
                    <p v-else class="max-[480px]:text-[12px] max-[480px]:mt-2">No ratings yet.</p>

                    <div class="dot_seperator"><strong>.</strong></div>
                    <div class="car_location">
                        <span class="max-[480px]:text-[12px]">{{ vehicle?.location }}</span>
                    </div>
                </div>
                </div>
                <div class="flex justify-between mt-[4rem] max-[480px]:flex-col max-[480px]:mt-10">
                    <div class="column w-[50%] max-[480px]:w-full">
                        <div class="column flex flex-col gap-10 max-[480px]:gap-5">
                            <!-- Vehicle Features Section -->
                            <span class="text-[2rem] font-medium max-[480px]:text-[1rem]">Car Overview</span>
                            <div class="features grid grid-cols-4 gap-x-[2rem] gap-y-[2rem] max-[480px]:grid-cols-3">
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="peopleIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">People</span>
                                        <span class="font-medium text-[1rem] max-[480px]:text-[0.85rem]">{{
                                            vehicle?.seating_capacity
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="doorIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Doors</span>
                                        <span class="font-medium text-[1rem] max-[480px]:text-[0.85rem]">{{
                                            vehicle?.number_of_doors
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="luggageIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Luggage</span>
                                        <span class="font-medium text-[1rem] max-[480px]:text-[0.85rem]">{{
                                            vehicle?.luggage_capacity
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="transmisionIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Transmission</span>
                                        <span class="font-medium capitalize max-[480px]:text-[0.85rem]">{{
                                            vehicle?.transmission
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="fuelIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Fuel Type</span>
                                        <span class="font-medium capitalize max-[480px]:text-[0.85rem]">{{
                                            vehicle?.fuel
                                        }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="enginepowerIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Horsepower</span>
                                        <span class="font-medium text-[1rem] max-[480px]:text-[0.85rem]">{{ vehicle?.horsepower }} hp</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="carbonIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Co2 Emission</span>
                                        <span class="font-medium text-[1rem] max-[480px]:text-[0.85rem]">{{ vehicle?.co2 }} (g/km)</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="mileageIcon" alt="" class='w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem] max-[480px]:text-[0.75rem]">Mileage</span>
                                        <span class="font-medium text-[1rem] max-[480px]:text-[0.85rem]">{{ vehicle?.mileage }} km/d</span>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <div class="features mt-[3rem]">
                            <span class="text-[2rem] font-medium max-[480px]:text-[1rem]">Features</span>
                            <div class="grid grid-cols-4 mt-[2rem] gap-y-[2rem] max-[480px]:mt-[1rem]">
                                <div class="flex items-center gap-3 max-[480px]:text-[0.95rem]" v-if="vehicle?.features" v-for="(feature, index) in JSON.parse(
                                    vehicle.features
                                )" :key="index">
                                    <img :src="featureIconMap[feature]" alt="Feature Icon"
                                        class="feature-icon w-[30px] h-[30px] max-[480px]:w-[24px] max-[480px]:h-[24px" />
                                    {{ feature }}
                                </div>
                                <div v-else>
                                    <p>No features available.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-[3rem] max-[480px]:mt-[2rem]">
                            <span class="text-[2rem] font-medium max-[480px]:text-[1rem]">Car Location</span>
                            <div class="gap-y-[2rem] max-[480px]:mt-[0.5rem]">
                                {{ vehicle?.location }}
                            </div>
                            <div id="map" class="h-full rounded-lg mt-4"></div>
                        </div>

                        <div class="mt-[5rem] benefits max-[480px]:mt-[2rem]">
                            <span class="text-[2rem] font-medium mb-5 inline-block max-[480px]:text-[1rem]">Rental Conditions & Banefits</span>
                            <ul class="vehicle-benefits p-4 border rounded-lg shadow-sm bg-white flex flex-col gap-2">
                                <!-- Limited Kilometer Display -->
                                <li v-if="vehicle?.benefits?.limited_km_per_day" class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Limited Kilometer Per Day: {{ vehicle?.benefits?.limited_km_per_day_range }} km
                                    </p>
                                    <span class="text-customDarkBlackColor font-medium">
                                        -> After that {{ formatPrice(vehicle?.benefits?.price_per_km_per_day) }}/km will
                                        be charged.
                                    </span>
                                </li>
                                <li v-if="vehicle?.benefits?.limited_km_per_week" class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Limited Kilometer Per Week: {{ vehicle?.benefits?.limited_km_per_week_range }}
                                        km
                                    </p>
                                    <span class="text-customDarkBlackColor font-medium">
                                        -> After that {{ formatPrice(vehicle?.benefits?.price_per_km_per_week) }}/km
                                        will be charged.
                                    </span>
                                </li>
                                <li v-if="vehicle?.benefits?.limited_km_per_month" class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Limited Kilometer Per Month: {{ vehicle?.benefits?.limited_km_per_month_range }}
                                        km
                                    </p>
                                    <span class="text-customDarkBlackColor font-medium">
                                        -> After that {{ formatPrice(vehicle?.benefits?.price_per_km_per_month) }}/km
                                        will be charged.
                                    </span>
                                </li>

                                <!-- Cancellation Availability Display -->
                                <li v-if="vehicle?.benefits?.cancellation_available_per_day"
                                    class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Cancellation Available (for daily package): {{
                                            vehicle?.benefits?.cancellation_available_per_day_date }} days before rental
                                        date
                                    </p>
                                </li>
                                <li v-if="vehicle?.benefits?.cancellation_available_per_week"
                                    class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Cancellation Available (for weekly package): {{
                                            vehicle?.benefits?.cancellation_available_per_week_date }} days before rental
                                        date
                                    </p>
                                </li>
                                <li v-if="vehicle?.benefits?.cancellation_available_per_month"
                                    class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Cancellation Available (for monthly package): {{
                                            vehicle?.benefits?.cancellation_available_per_month_date }} days before rental
                                        date
                                    </p>
                                </li>

                                <!-- Minimum Driver Age -->
                                <li v-if="vehicle?.benefits?.minimum_driver_age" class="flex items-center gap-1">
                                    <p class="text-[1.2rem] max-[480px]:text-[0.95rem] text-customPrimaryColor font-medium">
                                        Minimum Driver Age: {{ vehicle?.benefits?.minimum_driver_age }} years
                                    </p>
                                </li>

                                <!-- Fallback Message if No Benefits Exist -->
                                <li v-else-if="!vehicle?.benefits || Object.keys(vehicle?.benefits).length === 0">
                                    <p class="text-gray-500 text-lg">No additional benefits available for this vehicle.
                                    </p>
                                </li>
                            </ul>


                        </div>

                        <div class="mt-[5rem] max-[480px]:mt-[2rem]">
                            <span class="text-[2rem] font-medium max-[480px]:text-[1rem]">Meet Vehicle Vendor</span>
                            <div
                                class="mt-[2rem] flex gap-5 border-[1px] border-customPrimaryColor
                                 rounded-[0.75em] px-[1rem] py-[2rem] max-[480px]:py-[1rem]">
                                <img :src="vehicle.vendor_profile?.avatar
                                    ? `${vehicle.vendor_profile.avatar}`
                                    : '/storage/avatars/default-avatar.svg'" alt="User Avatar"
                                    class="w-[100px] h-[100px] max-[480px]:w-[60px] max-[480px]:h-[60px] rounded-full object-cover" />
                                <div>
                                    <h4 class="text-customPrimaryColor text-[1.75rem] font-medium max-[480px]:text-[1rem]">
                                        {{ vehicle.user.first_name }} {{ vehicle.user.last_name }}
                                    </h4>
                                    <p class="text-customLightGrayColor max-[480px]:text-[0.95rem]">{{ vehicle.vendor_profile.about }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column w-[40%] max-[480px]:w-full max-[480px]:mt-[2rem]">
                        <div class="paymentInfoDiv p-5 sticky top-[3rem]">
                            <div class="flex items-center justify-between gap-3 max-[480px]:mb-4">
                                <h4 class="max-[480px]:text-[1.2rem]">{{ vehicle?.brand }} {{ vehicle?.model }}</h4>
                                <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] max-[480px]:text-[0.95rem]">
                                    {{ vehicle?.category.name }}
                                </span>
                                <div class="icons flex items-center gap-3">
                                    <Link href="" class="max-[480px]:w-[1.5rem]"><img :src="ShareIcon" alt="" /></Link>
                                    <button @click.stop="toggleFavourite(vehicle)" class="heart-icon"
                                        :class="{ 'filled-heart': vehicle.is_favourite }">
                                        <img :src="vehicle.is_favourite ? FilledHeart : Heart" alt="Favorite"
                                            class="w-[2rem] max-[480px]:w-[1.5rem] transition-colors duration-300" />
                                    </button>
                                </div>
                            </div>
                            <div class="max-[480px]:text-[0.85rem]">
                                <span>Hosted by
                                    <span class="vendorName uppercase">
                                        {{ vehicle?.user.first_name }}
                                        {{ vehicle?.user.last_name }}
                                    </span>
                                </span>
                            </div>
                            <div class="car_short_info mt-[1rem] flex gap-3">
                                <img :src="carIcon" alt="" class="max-[480px]:w-[24px]"/>
                                <div class="features">
                                    <span class="text-[1.15rem] capitalize max-[480px]:text-[0.85rem]">
                                        {{ vehicle?.transmission }} .
                                        {{ vehicle?.fuel }} .
                                        {{ vehicle?.seating_capacity }} Seats
                                    </span>
                                </div>
                            </div>
                            <div class="extra_details flex gap-5 mt-[1rem]">
                                
                                <div class="col flex gap-3">
                                    <img :src="mileageIcon" alt="" class="max-[480px]:w-[24px]"/>
                                    <span class="text-[1.15rem] max-[480px]:text-[0.85rem]">{{ vehicle?.mileage }}
                                        km/d</span>
                                </div>
                            </div>

                            <div class="ratings"></div>

                            <div class="location mt-[2rem]">
                                <span class="text-[1.5rem] font-medium mb-[1rem] inline-block max-[480px]:text-[1.2rem]">Location</span>
                                <div class="col flex items-start gap-4">
                                    <img :src="pickupLocationIcon" alt="" class="max-[480px]:w-[24px]"/>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[1.25rem] text-medium max-[480px]:text-[1rem]">{{ vehicle?.location }}</span>
                                        <span class="max-[480px]:text-[0.95rem]">{{ route().params.pickup_date }}</span>
                                    </div>
                                </div>
                                <div class="col flex items-start gap-4 mt-10">
                                    <img :src="returnLocationIcon" alt="" class="max-[480px]:w-[24px]"/>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[1.25rem] text-medium max-[480px]:text-[1rem]">{{ vehicle?.location }}</span>
                                        <span class="max-[480px]:text-[0.95rem]">{{ route().params.return_date }}</span>
                                    </div>
                                </div>



                                <div class="pricing py-5">
                                    <div class="column flex items-center justify-between">
                                        <div class="mx-auto px-6 max-[480px]:px-0 max-[480px]:w-full">
                                            <Card>
                                                <CardHeader>
                                                    <CardTitle class="inline-block text-[1rem]">Choose Your Rental
                                                        Package</CardTitle>
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
                                                                Discount: {{ pkg.discount }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Date Selection -->
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <label
                                                                class="block text-sm font-medium text-gray-700 mb-2">Pickup
                                                                Date</label>
                                                            <input type="date" v-model="form.date_from"
                                                                :min="getCurrentDate()" :max="maxPickupDate"
                                                                @input="handleDateInput($event, 'pickup')"
                                                                @change="updateDateTimeSelection"
                                                                class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                                        </div>

                                                        <div>
                                                            <label
                                                                class="block text-sm font-medium text-gray-700 mb-2">Return
                                                                Date</label>
                                                            <input type="date" v-model="form.date_to"
                                                                :min="minReturnDate" :max="maxReturnDate"
                                                                @input="handleDateInput($event, 'return')"
                                                                @change="updateDateTimeSelection"
                                                                class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                                        </div>
                                                        <div>
                                                            <!-- Time Dropdown -->
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
                                                            <!-- Time Dropdown -->
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
                                                        <p class="text-lg max-[480px]:text-[1rem] font-semibold">
                                                            Current Price: {{formatPrice(pricingPackages.find(pkg =>
                                                                pkg.id === selectedPackage).price)}}
                                                        </p>
                                                        <p v-if="pricingPackages.find(pkg => pkg.id === selectedPackage).discount"
                                                            class="text-lg max-[480px]:text-[1rem] text-green-600">
                                                            Discount: -{{formatPrice(pricingPackages.find(pkg => pkg.id
                                                                === selectedPackage).discount)}}
                                                        </p>
                                                        <p class="text-[1.75rem] max-[480px]:text-[1.5rem] font-semibold">
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
                                        <button @click="proceedToPayment"
                                            class="button-primary block text-center p-5 w-full max-[480px]:text-[0.875rem]">Proceed to Pay</button>
                                    </div>
                                    <div
                                        class="column text-center mt-[2rem] flex flex-col justify-center items-center gap-5">
                                        <p>Guaranteed safe & secure checkout</p>
                                        <img :src="partnersIcon" alt="" />
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

        <section class="" style="
                        background: linear-gradient(to bottom, #FFFFFF, #F8F8F8); 
                    ">
            <div class="reviews-section mt-[3rem] full-w-container max-[480px]:mt-0">
                <span class="text-[2rem] font-bold">Overall Rating</span>

                <div v-if="isLoading">Loading reviews...</div>
                <div v-else-if="reviews && reviews.length > 0">
                    <Carousel class="relative w-full py-[4rem] px-[2rem] max-[480px]:px-0" :plugins="[plugin]" @mouseenter="plugin.stop"
                        @mouseleave="[plugin.reset(), plugin.play(), console.log('Running')]">
                        <CarouselContent class="max-[480px]:px-5">
                            <CarouselItem v-for="review in reviews" :key="review.id"
                                class="pl-1 md:basis-1/2 lg:basis-1/3 ml-[1rem]">
                                <Card class="h-[15rem]">
                                    <CardContent>
                                        <div class="review-item  px-[1rem] py-[2rem] h-full">
                                            <div class="flex items-center gap-3">
                                                <img :src="review.user.profile?.avatar ? `${review.user.profile?.avatar}` : '/storage/avatars/default-avatar.svg'"
                                                    alt="User Avatar"
                                                    class="w-[50px] h-[50px] rounded-full object-cover" />
                                                <div>
                                                    <h4 class="text-customPrimaryColor font-medium max-[480px]:text-[1.1rem]">{{
                                                        review.user.first_name }} {{ review.user.last_name }}</h4>
                                                    <div class="flex items-center gap-1">
                                                        <div class="star-rating">
                                                            <img v-for="n in 5" :key="n"
                                                                :src="getStarIcon(review.rating, n)"
                                                                :alt="getStarAltText(review.rating, n)"
                                                                class="w-[20px] h-[20px]" />
                                                        </div>
                                                        <span>{{ review.rating }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="mt-2 max-[480px]:text-[0.875rem]">{{ review.review_text }}</p>
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
                </div>
                <div v-else class="mt-[2rem] pb-[3rem]">
                    <p>No reviews yet.</p>
                </div>
            </div>
        </section>

        <section class="full-w-container py-customVerticalSpacing">
            <div
                class="mt-[2rem] max-[480px]:mt-0 flex items-center justify-center gap-5 border-[1px] border-customPrimaryColor rounded-[0.75em] px-[1rem] py-[2rem]">
                <div class="flex flex-col items-center gap-5 w-[50%] max-[480px]:w-full">
                    <img :src="vehicle.vendor_profile?.avatar
                        ? `${vehicle.vendor_profile.avatar}`
                        : '/storage/avatars/default-avatar.svg'" alt="User Avatar"
                        class="w-[100px] h-[100px] max-[480px]:w-[60px] max-[480px]:h-[60px] rounded-full object-cover" />
                    <h4 class="text-customPrimaryColor text-[1.75rem] font-medium max-[480px]:text-[1.2rem]">
                        {{ vehicle.user.first_name }} {{ vehicle.user.last_name }}
                    </h4>
                    <span>On VROOEM since {{ formatDate(vehicle.user.created_at) }}</span>
                    <div class="flex justify-center w-full max-[480px]:flex-wrap max-[480px]:gap-5">
                        <div class="col flex flex-col items-center">
                            <p class="capitalize text-[1.5rem] text-customPrimaryColor font-bold max-[480px]:text-[1.2rem]">{{
                                vehicle?.vendor_profile_data.status }}</p>
                            <span class="text-customLightGrayColor max-[480px]:text-[1rem]">Verification Status</span>
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
</template>

<style scoped>
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

@import 'leaflet/dist/leaflet.css';

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


@media screen and (max-width:480px) {
    .reviews-section .next-btn {
    top: 100%!important;
    left: 60%;
    justify-content: center;
    z-index: 99;
}

.reviews-section .prev-btn {
    top: 100%!important;
    left: 30% !important;
    justify-content: center;
    z-index: 99;
}
}
</style>
