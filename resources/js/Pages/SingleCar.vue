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
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import Autoplay from 'embla-carousel-autoplay';
// Fetching Vehicle Details
import { usePage } from "@inertiajs/vue3";

const { props } = usePage(); // Get the props passed from the controller
const vehicle = ref(props.vehicle);
const reviews = ref([]);
const isLoading = ref(true);
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
        date_from: form.value.date_from,
        date_to: form.value.date_to,
        time_from: form.value.time_from,
        time_to: form.value.time_to
    }));
};

// Function to load saved dates from session storage
const loadSavedDates = () => {
    const savedDates = sessionStorage.getItem('rentalDates');
    if (savedDates) {
        const dates = JSON.parse(savedDates);
        form.value.date_from = dates.date_from || '';
        form.value.date_to = dates.date_to || '';
        form.value.time_from = dates.time_from || '';
        form.value.time_to = dates.time_to || '';
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
const toggleFavourite = async (vehicle) => {
    const action = vehicle.is_favourite ? 'removed from' : 'added to';
    const endpoint = vehicle.is_favourite
        ? `/vehicles/${vehicle.id}/unfavourite`
        : `/vehicles/${vehicle.id}/favourite`;

    try {
        await axios.post(endpoint);
        vehicle.is_favourite = !vehicle.is_favourite;

        // Show toast notification
        toast.success(`Vehicle ${action} favorites!`, {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            icon: vehicle.is_favourite ? '❤️' : '💔',
        });

    } catch (error) {
        toast.error('Failed to update favorites', {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
        });
        console.error('Error:', error);
    }
};


import { Calendar, Clock } from 'lucide-vue-next';

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { CardHeader, CardTitle } from "@/Components/ui/card";

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
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
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

const bookedDates = ref(props.booked_dates || []);
// Create a function to check if a date is booked
const isDateBooked = (dateStr) => {
    if (!dateStr || !bookedDates.value.length) return false;

    const checkDate = new Date(dateStr);

    return bookedDates.value.some(({ pickup_date, return_date }) => {
        const pickupDate = new Date(pickup_date);
        const returnDate = new Date(return_date);

        // Set times to beginning of day for consistent comparison
        pickupDate.setHours(0, 0, 0, 0);
        returnDate.setHours(0, 0, 0, 0);
        checkDate.setHours(0, 0, 0, 0);

        // Check if the date falls within a booked period
        return checkDate >= pickupDate && checkDate <= returnDate;
    });
};

// Create a function that returns all disabled dates as an array
const getDisabledDates = () => {
    if (!bookedDates.value.length) return [];

    const disabledDates = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    bookedDates.value.forEach(({ pickup_date, return_date }) => {
        const start = new Date(pickup_date);
        const end = new Date(return_date);

        // For each booking, add all dates between pickup and return
        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
            if (d >= today) { // Only include future dates
                disabledDates.push(new Date(d).toISOString().split('T')[0]);
            }
        }
    });

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

    if (isDateBooked(selectedDate)) {
        // If the date is booked, prevent selection
        // toast.error('This date is already booked');

        if (type === 'pickup') {
            form.value.date_from = '';
        } else {
            form.value.date_to = '';
        }

        // Find next available date
        const availableDates = findNextAvailableDates(selectedDate);
        if (availableDates.length) {
            toast.info(`Next available date: ${availableDates[0]}`);
        }
    } else if (type === 'pickup') {
        // Clear return date when pickup date changes
        form.value.date_to = '';
    }
};

const findNextAvailableDates = (fromDate, count = 5) => {
    const startDate = new Date(fromDate);
    const availableDates = [];

    // Look 30 days ahead
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

    // Ensure minDate is after all booked dates
    bookedDates.value.forEach(({ return_date }) => {
        const bookedReturnDate = new Date(return_date);
        if (bookedReturnDate >= minDate) {
            minDate.setDate(bookedReturnDate.getDate() + 1);
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


const proceedToPayment = () => {
    // Validate rental details before proceeding
    if (!validateRentalDetails()) {
        return; // Stop the function if validation fails
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
            <div class="full-w-container py-customVerticalSpacing">
                <div class="flex gap-2 items-center mb-1">
                    <h4 class="font-medium">{{ vehicle?.brand }}</h4>
                    <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]">
                        {{ vehicle?.category.name }}
                    </span>
                </div>
                <div class="flex gap-2 items-center text-[1.25rem]">
                    <div class="car_ratings flex gap-2 items-center">
                        <img :src=starIcon alt="">
                        <span>5(1)</span>
                    </div>
                    <div class="dot_seperator"><strong>.</strong></div>
                    <div class="car_location">
                        <span>{{ vehicle?.location }}</span>
                    </div>
                </div>
                <div class="w-full mt-[1rem] flex gap-2">
                    <div class="primary-image w-[60%] max-h-[500px]">
                        <img v-if="vehicle?.images" :src="`/storage/${vehicle.images.find(
                            (image) => image.image_type === 'primary'
                        )?.image_path
                            }`" alt="Primary Image" class="w-full h-full object-cover rounded-lg" />
                    </div>

                    <!-- Display the gallery images -->
                    <div class="gallery w-[50%] grid grid-cols-2 gap-2 max-h-[245px]">
                        <div v-for="(image, index) in vehicle?.images.filter(
                            (image) => image.image_type === 'gallery'
                        )" :key="image.id" class="gallery-item">
                            <img :src="`/storage/${image.image_path}`" :alt="`Gallery Image ${index + 1}`"
                                class="w-full h-[245px] object-cover rounded-lg" />
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-[4rem]">
                    <div class="column w-[50%]">
                        <div class="column flex flex-col gap-10">
                            <!-- Vehicle Features Section -->
                            <span class="text-[2rem] font-medium">Car Overview</span>
                            <div class="features grid grid-cols-4 gap-x-[2rem] gap-y-[2rem]">
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="peopleIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">People</span>
                                        <span class="font-medium text-[1rem]">{{
                                            vehicle?.seating_capacity
                                            }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="doorIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Doors</span>
                                        <span class="font-medium text-[1rem]">{{
                                            vehicle?.number_of_doors
                                            }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="luggageIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Luggage</span>
                                        <span class="font-medium text-[1rem]">{{
                                            vehicle?.luggage_capacity
                                            }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="transmisionIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Transmission</span>
                                        <span class="font-medium capitalize">{{
                                            vehicle?.transmission
                                            }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="fuelIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Fuel Type</span>
                                        <span class="font-medium capitalize">{{
                                            vehicle?.fuel
                                            }}</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="enginepowerIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Horsepower</span>
                                        <span class="font-medium text-[1rem]">{{ vehicle?.horsepower }} hp</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="carbonIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Co2 Emission</span>
                                        <span class="font-medium text-[1rem]">{{ vehicle?.co2 }} (g/km)</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="mileageIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Mileage</span>
                                        <span class="font-medium text-[1rem]">{{ vehicle?.mileage }} km/d</span>
                                    </div>
                                </div>
                                <div class="feature-item items-center flex gap-3">
                                    <img :src="ageIcon" alt="" class='w-[30px] h-[30px]' />
                                    <div class="flex flex-col">
                                        <span class="text-customLightGrayColor text-[1rem]">Minimum Driving Age</span>
                                        <span class="font-medium text-[1rem]">21</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="features mt-[3rem]">
                            <span class="text-[2rem] font-medium">Features</span>
                            <div class="grid grid-cols-4 mt-[2rem] gap-y-[2rem]">
                                <div class="flex items-center gap-3" v-if="vehicle?.features" v-for="(feature, index) in JSON.parse(
                                    vehicle.features
                                )" :key="index">
                                    <img :src="featureIconMap[feature]" alt="Feature Icon"
                                        class="feature-icon w-[30px] h-[30px]" />
                                    {{ feature }}
                                </div>
                                <div v-else>
                                    <p>No features available.</p>
                                </div>
                            </div>
                        </div>

                        <div class=" mt-[3rem]">
                            <span class="text-[2rem] font-medium">Car Location</span>
                            <div class="gap-y-[2rem]">
                                {{ vehicle?.location }}
                            </div>
                            <div id="map" class="h-full rounded-lg mt-4"></div>
                        </div>

                        <div class="mt-[5rem]">
                            <span class="text-[2rem] font-medium">Banefits</span>
                            <div class="flex justify-between gap-5">
                                <div
                                    class=" flex justify-between gap-5 border-[1px] border-customPrimaryColor rounded-[0.75em] px-[1rem] py-[2rem]">
                                    <img :src=unlimitedKmIcon alt="">
                                    <div>
                                        <h4 class="text-customPrimaryColor text-[1.75rem] font-medium">Unlimited KMs
                                        </h4>
                                        <p class="text-customLightGrayColor">Endless kilometres with no extra charge</p>
                                    </div>
                                </div>
                                <div
                                    class=" flex justify-between gap-5 border-[1px] border-customPrimaryColor rounded-[0.75em] px-[1rem] py-[2rem]">
                                    <img :src=cancellationIcon alt="">
                                    <div>
                                        <h4 class="text-customPrimaryColor text-[1.75rem] font-medium">Cancellation
                                            Unavailable</h4>
                                        <p class="text-customLightGrayColor">This booking is non-refundable</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-[5rem]">
                            <span class="text-[2rem] font-medium">Meet Vehicle Vendor</span>
                            <div
                                class="mt-[2rem] flex gap-5 border-[1px] border-customPrimaryColor rounded-[0.75em] px-[1rem] py-[2rem]">
                                <img :src="vehicle?.user?.profile?.avatar
                                    ? `/storage/${vehicle?.user?.profile?.avatar}`
                                    : '/storage/avatars/default-avatar.svg'
                                    " alt="User Avatar" class="w-[100px] h-[100px] rounded-full object-cover" />
                                <div>
                                    <h4 class="text-customPrimaryColor text-[1.75rem] font-medium">
                                        {{ vehicle.user.first_name }} {{ vehicle.user.last_name }}
                                    </h4>
                                    <p class="text-customLightGrayColor">{{ vehicle.vendor_profile.about }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column w-[40%]">
                        <div class="paymentInfoDiv p-5 sticky top-[3rem]">
                            <div class="flex items-center justify-between gap-3">
                                <h4>{{ vehicle?.brand }}</h4>
                                <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]">
                                    {{ vehicle?.category.name }}
                                </span>
                                <div class="icons flex items-center gap-3">
                                    <Link href="" class=""><img :src="ShareIcon" alt="" /></Link>
                                    <button @click.stop="toggleFavourite(vehicle)" class="heart-icon"
                                        :class="{ 'filled-heart': vehicle.is_favourite }">
                                        <img :src="vehicle.is_favourite ? FilledHeart : Heart" alt="Favorite"
                                            class="w-[2rem] transition-colors duration-300" />
                                    </button>
                                </div>
                            </div>
                            <div>
                                <span>Hosted by
                                    <span class="vendorName uppercase">
                                        {{ vehicle?.user.first_name }}
                                        {{ vehicle?.user.last_name }}
                                    </span>
                                </span>
                            </div>
                            <div class="car_short_info mt-[1rem] flex gap-3">
                                <img :src="carIcon" alt="" />
                                <div class="features">
                                    <span class="text-[1.15rem] capitalize">
                                        {{ vehicle?.transmission }} .
                                        {{ vehicle?.fuel }} .
                                        {{ vehicle?.seating_capacity }} Seats
                                    </span>
                                </div>
                            </div>
                            <div class="extra_details flex gap-5 mt-[1rem]">
                                <div class="col flex gap-3">
                                    <img :src="walkIcon" alt="" /><span class="text-[1.15rem]">9.3 KM Away</span>
                                </div>
                                <div class="col flex gap-3">
                                    <img :src="mileageIcon" alt="" /><span class="text-[1.15rem]">{{ vehicle?.mileage }}
                                        km/d</span>
                                </div>
                            </div>

                            <div class="ratings"></div>

                            <div class="location mt-[2rem]">
                                <span class="text-[1.5rem] font-medium mb-[1rem] inline-block">Location</span>
                                <div class="col flex items-start gap-4">
                                    <img :src="pickupLocationIcon" alt="" />
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[1.25rem] text-medium">{{ vehicle?.location }}</span>
                                        <span>{{ vehicle?.created_at }}</span>
                                    </div>
                                </div>



                                <div class="pricing py-5">
                                    <div class="column flex items-center justify-between">
                                        <div class="mx-auto px-6">
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
                                                        <p class="text-lg font-semibold">
                                                            Current Price: {{formatPrice(pricingPackages.find(pkg =>
                                                                pkg.id === selectedPackage).price)}}
                                                        </p>
                                                        <p v-if="pricingPackages.find(pkg => pkg.id === selectedPackage).discount"
                                                            class="text-lg text-green-600">
                                                            Discount: -{{formatPrice(pricingPackages.find(pkg => pkg.id
                                                                === selectedPackage).discount)}}
                                                        </p>
                                                        <p class="text-[1.75rem] font-semibold">
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
                                    <div class="column mt-[2rem]">
                                        <button @click="proceedToPayment"
                                            class="button-primary block text-center p-5 w-full">Proceed to Pay</button>
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
            <div class="reviews-section mt-[3rem] full-w-container">
                <span class="text-[2rem] font-bold">Overall Rating</span>

                <div v-if="isLoading">Loading reviews...</div>
                <div v-else-if="reviews && reviews.length > 0">
                    <Carousel class="relative w-full py-[4rem] px-[2rem]" :plugins="[plugin]" @mouseenter="plugin.stop"
                        @mouseleave="[plugin.reset(), plugin.play(), console.log('Running')]">
                        <CarouselContent>
                            <CarouselItem v-for="review in reviews" :key="review.id"
                                class="pl-1 md:basis-1/2 lg:basis-1/3">
                                <Card class="h-[15rem]">
                                    <CardContent>
                                        <div class="review-item  px-[1rem] py-[2rem] h-full">
                                            <div class="flex items-center gap-3">
                                                <img :src="review.user.profile?.avatar ? `/storage/${review.user.profile?.avatar}` : '/storage/avatars/default-avatar.svg'"
                                                    alt="User Avatar"
                                                    class="w-[50px] h-[50px] rounded-full object-cover" />
                                                <div>
                                                    <h4 class="text-customPrimaryColor font-medium">{{
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
                                            <p class="mt-2">{{ review.review_text }}</p>
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
                <div v-else class="mt-[2rem]">
                    <p>No reviews yet.</p>
                </div>
            </div>
        </section>

        <section class="full-w-container py-customVerticalSpacing">
            <div
                class="mt-[2rem] flex items-center justify-center gap-5 border-[1px] border-customPrimaryColor rounded-[0.75em] px-[1rem] py-[2rem]">
                <div class="flex flex-col items-center gap-5 w-[50%]">
                    <img :src="vehicle?.user?.profile?.avatar
                        ? `/storage/${vehicle?.user?.profile?.avatar}`
                        : '/storage/avatars/default-avatar.svg'
                        " alt="User Avatar" class="w-[100px] h-[100px] rounded-full object-cover" />
                    <h4 class="text-customPrimaryColor text-[1.75rem] font-medium">
                        {{ vehicle.user.first_name }} {{ vehicle.user.last_name }}
                    </h4>
                    <span>On VROOEM since {{ formatDate(vehicle.user.created_at) }}</span>
                    <div class="flex justify-between w-full">
                        <div class="col flex flex-col items-center">
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
                        </div>
                        <div class="col flex flex-col items-center">
                            <p class="capitalize text-[1.5rem] text-customPrimaryColor font-bold">{{
                                vehicle?.vendor_profile_data.status }}</p>
                            <span class="text-customLightGrayColor">Verification Status</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <Footer />
</template>

<style>
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
</style>
