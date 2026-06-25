<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
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
});

const page = usePage();

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
        primaryHref: localizedPath('/customer/bookings'),
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
        primaryHref: localizedPath('/customer/bookings'),
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
        title: 'Refund in progress',
        message: 'Your payment was received, but the vehicle could not be confirmed. Refund handling has started.',
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
        message: 'The supplier could not confirm this reservation. Our team has been notified and will handle the refund or support review.',
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

const outcome = computed(() => outcomes[props.state] || outcomes.support_review);
const Icon = computed(() => outcome.value.icon);
</script>

<template>
    <Head :title="outcome.title" />
    <AuthenticatedHeaderLayout />

    <BookingOutcomePage
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
