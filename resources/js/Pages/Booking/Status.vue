<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import { AlertCircle, CheckCircle2, Clock3, CreditCard, RefreshCw, ShieldCheck } from 'lucide-vue-next';

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
});

const searchUrl = computed(() => {
    if (typeof window === 'undefined') return '/';
    return sessionStorage.getItem('searchurl') || '/';
});

const outcomes = {
    confirmed: {
        icon: CheckCircle2,
        tone: 'success',
        title: 'Booking confirmed',
        message: 'Your booking is confirmed. Your reservation details are ready.',
        primaryLabel: 'View booking',
        primaryHref: '/customer/bookings',
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    pending_supplier_confirmation: {
        icon: Clock3,
        tone: 'warning',
        title: 'Payment received',
        message: 'We are confirming your reservation with the supplier. You will receive confirmation when the supplier reference is ready.',
        primaryLabel: 'View booking status',
        primaryHref: '/customer/bookings',
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    payment_cancelled: {
        icon: CreditCard,
        tone: 'neutral',
        title: 'Payment cancelled',
        message: 'Your payment was cancelled. No booking has been confirmed.',
        primaryLabel: 'Return to search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    payment_not_completed: {
        icon: CreditCard,
        tone: 'warning',
        title: 'Payment not completed',
        message: 'Your payment was not completed. No booking has been confirmed.',
        primaryLabel: 'Try again',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    quote_expired: {
        icon: RefreshCw,
        tone: 'warning',
        title: 'Offer expired',
        message: 'This offer changed or expired. Please refresh your search before booking.',
        primaryLabel: 'Refresh search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    refund_pending: {
        icon: ShieldCheck,
        tone: 'warning',
        title: 'Refund in progress',
        message: 'Your payment was received, but the vehicle could not be confirmed. Refund handling has started.',
        primaryLabel: 'Contact support',
        primaryHref: '/contact-us',
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    reservation_failed: {
        icon: AlertCircle,
        tone: 'danger',
        title: 'Supplier could not confirm',
        message: 'The supplier could not confirm this reservation. Our team has been notified and will handle the refund or support review.',
        primaryLabel: 'Contact support',
        primaryHref: '/contact-us',
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    invalid_session: {
        icon: AlertCircle,
        tone: 'danger',
        title: 'Booking link expired',
        message: 'This booking link is missing or expired. Please return to your search and try again.',
        primaryLabel: 'Return to search',
        primaryHref: searchUrl.value,
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
    support_review: {
        icon: AlertCircle,
        tone: 'danger',
        title: 'Support review needed',
        message: 'We could not load the booking result safely. Our team can trace this using the payment reference.',
        primaryLabel: 'Contact support',
        primaryHref: '/contact-us',
        secondaryLabel: 'Back to home',
        secondaryHref: '/',
    },
};

const outcome = computed(() => outcomes[props.state] || outcomes.support_review);
const Icon = computed(() => outcome.value.icon);

const toneClasses = computed(() => {
    switch (outcome.value.tone) {
        case 'success':
            return {
                ring: 'bg-emerald-100 text-emerald-700',
                panel: 'bg-emerald-50 border-emerald-200 text-emerald-800',
            };
        case 'warning':
            return {
                ring: 'bg-amber-100 text-amber-700',
                panel: 'bg-amber-50 border-amber-200 text-amber-800',
            };
        case 'danger':
            return {
                ring: 'bg-red-100 text-red-700',
                panel: 'bg-red-50 border-red-200 text-red-800',
            };
        default:
            return {
                ring: 'bg-slate-100 text-slate-700',
                panel: 'bg-slate-50 border-slate-200 text-slate-700',
            };
    }
});
</script>

<template>
    <Head :title="outcome.title" />
    <AuthenticatedHeaderLayout />

    <main class="min-h-screen bg-slate-50 py-16 font-['IBM_Plex_Sans',sans-serif]">
        <section class="mx-auto w-[min(92%,680px)] rounded-[20px] bg-white p-6 text-center shadow-[0_12px_32px_rgba(21,59,79,0.12)] md:p-8">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full" :class="toneClasses.ring">
                <component :is="Icon" class="h-9 w-9" />
            </div>

            <h1 class="font-['Plus_Jakarta_Sans',sans-serif] text-2xl font-bold text-slate-900 md:text-3xl">
                {{ outcome.title }}
            </h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600 md:text-base">
                {{ outcome.message }}
            </p>

            <div v-if="booking || session_id" class="mt-6 rounded-xl border p-4 text-left text-sm" :class="toneClasses.panel">
                <div v-if="booking" class="grid gap-2 sm:grid-cols-2">
                    <div>
                        <span class="block text-xs font-semibold uppercase text-current/70">Booking number</span>
                        <span class="font-semibold">{{ booking.booking_number }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase text-current/70">Status</span>
                        <span class="font-semibold capitalize">{{ booking.booking_status?.replace('_', ' ') }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase text-current/70">Payment</span>
                        <span class="font-semibold capitalize">{{ booking.payment_status?.replace('_', ' ') }}</span>
                    </div>
                    <div v-if="booking.amount_paid">
                        <span class="block text-xs font-semibold uppercase text-current/70">Paid</span>
                        <span class="font-semibold">{{ booking.booking_currency }} {{ Number(booking.amount_paid).toFixed(2) }}</span>
                    </div>
                </div>
                <div v-else-if="session_id">
                    <span class="block text-xs font-semibold uppercase text-current/70">Payment reference</span>
                    <span class="break-all font-semibold">{{ session_id }}</span>
                </div>
            </div>

            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                <Link :href="outcome.primaryHref" class="rounded-full bg-[#153b4f] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1c4d66]">
                    {{ outcome.primaryLabel }}
                </Link>
                <Link :href="outcome.secondaryHref" class="rounded-full border border-[#153b4f] px-5 py-3 text-sm font-semibold text-[#153b4f] transition hover:bg-slate-50">
                    {{ outcome.secondaryLabel }}
                </Link>
            </div>
        </section>
    </main>

    <Footer />
</template>
