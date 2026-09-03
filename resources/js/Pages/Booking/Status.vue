<script setup>
import { computed, onBeforeUnmount, onMounted, shallowRef, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import BookingOutcomePage from '@/Components/Booking/BookingOutcomePage.vue';
import { AlertCircle, CheckCircle2, Clock3, CreditCard, RefreshCw, ShieldCheck } from 'lucide-vue-next';
import bookingConfirmedIllustration from '../../../assets/booking-outcomes/booking-confirmed.webp';
import paymentCancelledIllustration from '../../../assets/booking-outcomes/payment-cancelled.webp';
import paymentNotCompletedIllustration from '../../../assets/booking-outcomes/payment-not-completed.webp';
import quoteExpiredIllustration from '../../../assets/booking-outcomes/quote-expired.webp';
import refundInProgressIllustration from '../../../assets/booking-outcomes/refund-in-progress.webp';
import supplierPendingIllustration from '../../../assets/booking-outcomes/supplier-pending.webp';
import supportReviewIllustration from '../../../assets/booking-outcomes/support-review.webp';

const props = defineProps({
    state: {
        type: String,
        default: 'support_review',
    },
    session_id: {
        type: String,
        default: null,
    },
    booking: {
        type: Object,
        default: null,
    },
    search_url: {
        type: String,
        default: null,
    },
    confirmation_deadline_at: {
        type: String,
        default: null,
    },
    server_time: {
        type: String,
        default: null,
    },
});

const page = usePage();
let refreshTimer = null;
let clockTimer = null;
const clockMs = shallowRef(Date.now());
const serverOffsetMs = shallowRef(0);

const syncServerClock = () => {
    const serverTime = Date.parse(props.server_time || '');
    serverOffsetMs.value = Number.isFinite(serverTime) ? serverTime - Date.now() : 0;
};

const clearRefreshTimer = () => {
    if (!refreshTimer) return;
    window.clearTimeout(refreshTimer);
    refreshTimer = null;
};

const isConfirmationPending = computed(() => props.state === 'card_authorized_supplier_confirmation');
const hasPersistedSupplierReference = computed(() => Boolean(props.booking?.provider_booking_ref));
const deadlineMs = computed(() => Date.parse(props.confirmation_deadline_at || ''));
const serverNowMs = computed(() => clockMs.value + serverOffsetMs.value);
const remainingSeconds = computed(() => {
    if (!Number.isFinite(deadlineMs.value)) return null;
    return Math.max(0, Math.ceil((deadlineMs.value - serverNowMs.value) / 1000));
});
const isTakingLonger = computed(() => isConfirmationPending.value
    && remainingSeconds.value !== null
    && remainingSeconds.value === 0);
const confirmationElapsedMs = computed(() => {
    if (!Number.isFinite(deadlineMs.value)) return 0;
    return Math.max(0, (5 * 60 * 1000) - (deadlineMs.value - serverNowMs.value));
});

const scheduleRefresh = () => {
    clearRefreshTimer();
    if (!isConfirmationPending.value) return;

    const delay = isTakingLonger.value ? 15000 : confirmationElapsedMs.value < 30000 ? 2000 : 5000;
    refreshTimer = window.setTimeout(() => {
        router.reload({
            only: ['state', 'booking', 'confirmation_deadline_at', 'server_time'],
            preserveScroll: true,
            onFinish: scheduleRefresh,
        });
    }, delay);
};

onMounted(() => {
    syncServerClock();
    clockTimer = window.setInterval(() => { clockMs.value = Date.now(); }, 1000);
    scheduleRefresh();
});

onBeforeUnmount(() => {
    clearRefreshTimer();
    if (clockTimer) window.clearInterval(clockTimer);
});

watch(() => props.server_time, syncServerClock);
watch(() => props.state, scheduleRefresh);

const currentLocale = computed(() => {
    const propLocale = page.props.locale;

    if (propLocale) return propLocale;
    if (typeof window === 'undefined') return 'en';

    const pathLocale = window.location.pathname.split('/').filter(Boolean)[0];
    return ['en', 'fr', 'nl', 'es', 'ar'].includes(pathLocale) ? pathLocale : 'en';
});

const localizedPath = (path = '/') => {
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
    return normalizedPath === '/' ? `/${currentLocale.value}` : `/${currentLocale.value}${normalizedPath}`;
};

const normalizeSearchUrl = (url) => {
    const value = typeof url === 'string' ? url.trim() : '';
    if (!value || typeof window === 'undefined') return null;

    try {
        const parsed = new URL(value, window.location.origin);
        const path = parsed.pathname;
        const query = parsed.search || '';

        if (/^\/(en|fr|nl|es|ar)\/s\/?$/.test(path)) {
            return `${path.replace(/\/$/, '')}${query}`;
        }

        if (path === '/s' || path === '/s/') {
            return localizedPath(`/s${query}`);
        }
    } catch {
        if (value.startsWith('/s?') || value === '/s') {
            return localizedPath(value);
        }
    }

    return null;
};

const searchUrl = computed(() => {
    if (typeof window === 'undefined') return localizedPath('/');

    const serverSearchUrl = normalizeSearchUrl(props.search_url);
    if (serverSearchUrl) return serverSearchUrl;

    const storedSearchUrl = normalizeSearchUrl(sessionStorage.getItem('searchurl'));
    return storedSearchUrl || localizedPath('/');
});

const outcomes = {
    confirmed: {
        icon: CheckCircle2,
        illustration: bookingConfirmedIllustration,
        tone: 'success',
        title: 'Booking confirmed',
        message: 'Your booking is confirmed. Your reservation details are ready.',
        primaryLabel: 'View booking',
        primaryHref: localizedPath('/profile/bookings'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    card_authorized_supplier_confirmation: {
        icon: Clock3,
        illustration: supplierPendingIllustration,
        tone: 'warning',
        title: 'Confirming your vehicle',
        message: 'Your card is authorized but has not been charged. We will capture the amount only after the supplier reference is saved.',
        primaryLabel: 'View booking status',
        primaryHref: localizedPath('/profile/bookings'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    authorization_released: {
        icon: ShieldCheck,
        illustration: paymentCancelledIllustration,
        tone: 'neutral',
        title: 'Card authorization released',
        message: 'The supplier booking was not created and the card authorization was released. Your card was not charged.',
        primaryLabel: 'Return to search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    authorization_review: {
        icon: AlertCircle,
        illustration: supportReviewIllustration,
        tone: 'warning',
        title: 'Card authorization under review',
        message: 'The supplier booking was stopped, but we could not verify the authorization release automatically. Our team has been alerted. No supplier reservation will be retried automatically.',
        primaryLabel: 'Contact support',
        primaryHref: localizedPath('/contact-us'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    supplier_confirmed_payment_review: {
        icon: AlertCircle,
        illustration: supportReviewIllustration,
        tone: 'warning',
        title: 'Vehicle reserved — payment review',
        message: 'The supplier reference is saved, but Stripe did not return a conclusive final capture result. Your payment status is under review, and we will not create another supplier reservation.',
        primaryLabel: 'Contact support',
        primaryHref: localizedPath('/contact-us'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    pending_supplier_confirmation: {
        icon: Clock3,
        illustration: supplierPendingIllustration,
        tone: 'warning',
        title: 'Payment received',
        message: 'We are confirming your reservation with the supplier. You will receive confirmation when the supplier reference is ready.',
        primaryLabel: 'View booking status',
        primaryHref: localizedPath('/profile/bookings'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    payment_cancelled: {
        icon: CreditCard,
        illustration: paymentCancelledIllustration,
        tone: 'neutral',
        title: 'Payment cancelled',
        message: 'Your payment was cancelled. No booking has been confirmed.',
        primaryLabel: 'Return to search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    payment_not_completed: {
        icon: CreditCard,
        illustration: paymentNotCompletedIllustration,
        tone: 'warning',
        title: 'Payment not completed',
        message: 'Your payment was not completed. No booking has been confirmed.',
        primaryLabel: 'Try again',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    quote_expired: {
        icon: RefreshCw,
        illustration: quoteExpiredIllustration,
        tone: 'warning',
        title: 'Offer expired',
        message: 'This offer changed or expired. Please refresh your search before booking.',
        primaryLabel: 'Refresh search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    refund_pending: {
        icon: ShieldCheck,
        illustration: refundInProgressIllustration,
        tone: 'warning',
        title: 'Payment received — under review',
        message: 'Your payment was received, but the vehicle could not be confirmed. Our team is reviewing your booking and will contact you about your refund.',
        primaryLabel: 'Contact support',
        primaryHref: localizedPath('/contact-us'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    reservation_failed: {
        icon: AlertCircle,
        illustration: supportReviewIllustration,
        tone: 'danger',
        title: 'Supplier could not confirm',
        message: 'The supplier could not confirm this reservation. Our team has been notified and will review your booking and contact you about the next steps, including any refund.',
        primaryLabel: 'Contact support',
        primaryHref: localizedPath('/contact-us'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    invalid_session: {
        icon: AlertCircle,
        illustration: quoteExpiredIllustration,
        tone: 'danger',
        title: 'Booking link expired',
        message: 'This booking link is missing or expired. Please return to your search and try again.',
        primaryLabel: 'Return to search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
    support_review: {
        icon: AlertCircle,
        illustration: supportReviewIllustration,
        tone: 'danger',
        title: 'Support review needed',
        message: 'We could not load the booking result safely. Our team can trace this using the payment reference.',
        primaryLabel: 'Contact support',
        primaryHref: localizedPath('/contact-us'),
        secondaryLabel: 'Back to home',
        secondaryHref: localizedPath('/'),
    },
};

const confirmationEyebrow = computed(() => {
    if (!isConfirmationPending.value) return 'Booking status';
    if (isTakingLonger.value) return 'Supplier confirmation · Still processing';
    if (remainingSeconds.value === null) return 'Supplier confirmation';

    const minutes = Math.floor(remainingSeconds.value / 60);
    const seconds = String(remainingSeconds.value % 60).padStart(2, '0');
    return `Supplier confirmation · ${minutes}:${seconds} remaining`;
});

const outcome = computed(() => {
    const resolved = outcomes[props.state] || outcomes.support_review;
    if (!isConfirmationPending.value) return resolved;

    return {
        ...resolved,
        eyebrow: confirmationEyebrow.value,
        title: hasPersistedSupplierReference.value
            ? 'Supplier confirmed — finalizing payment'
            : isTakingLonger.value ? 'Confirmation is taking longer' : resolved.title,
        message: isTakingLonger.value
            ? hasPersistedSupplierReference.value
                ? 'The supplier reference is saved. Your payment status is being finalized with Stripe; you may safely close this page and we will email you when it is resolved.'
                : 'Your card has not been charged. You may safely close this page; we will email you when the booking is resolved.'
            : hasPersistedSupplierReference.value
                ? 'The supplier reference is saved. We are finalizing your payment status with Stripe now.'
                : resolved.message,
    };
});
const Icon = computed(() => outcome.value.icon);
</script>

<template>
    <Head :title="outcome.title" />
    <AuthenticatedHeaderLayout />

    <BookingOutcomePage
        :eyebrow="outcome.eyebrow"
        :title="outcome.title"
        :message="outcome.message"
        :tone="outcome.tone"
        :icon="Icon"
        :illustration="outcome.illustration"
        :primary-label="outcome.primaryLabel"
        :primary-href="outcome.primaryHref"
        :secondary-label="outcome.secondaryLabel"
        :secondary-href="outcome.secondaryHref"
        :booking="booking"
        :session-id="session_id"
    />

    <Footer />
</template>
