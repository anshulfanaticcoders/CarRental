<template>
    <MyProfileLayout>
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/70 backdrop-blur-sm">
            <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
        </div>

        <div class="space-y-5">
            <!-- Back + Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--gray-200)] bg-white hover:bg-[var(--gray-50)] transition-colors"
                        @click="goBack"
                    >
                        <ArrowLeft class="w-4 h-4 text-[var(--gray-600)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--gray-900)] flex items-center gap-2.5">
                            {{ booking.booking_number }}
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                :class="statusBadgeClass(booking.booking_status)"
                            >
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(booking.booking_status)"></span>
                                {{ booking.booking_status }}
                            </span>
                        </h1>
                        <p class="text-xs text-[var(--gray-400)] mt-0.5">Booked on {{ formatDate(booking.created_at) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-[var(--gray-200)] bg-white text-sm font-medium text-[var(--gray-700)] hover:bg-[var(--gray-50)] transition-colors"
                        @click="goToDamageProtection(booking.id)"
                    >
                        <ShieldCheck class="w-4 h-4 text-[var(--accent-600)]" />
                        Damage Photos
                    </button>
                    <button
                        v-if="booking.booking_status !== 'cancelled'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-200 bg-white text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                        @click="cancelBooking(booking.id)"
                    >
                        <Ban class="w-3.5 h-3.5" />
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- LEFT: Booking Info (2 cols) -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Trip Details Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Car class="w-4 h-4 text-[var(--primary-600)]" />
                                Trip Details
                            </h2>
                        </div>
                        <div class="p-5">
                            <!-- Vehicle -->
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-[var(--primary-50)] flex items-center justify-center shrink-0">
                                    <Car class="w-5 h-5 text-[var(--primary-600)]" />
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--gray-900)]">{{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}</p>
                                    <p class="text-xs text-[var(--gray-500)] capitalize">Plan: {{ booking.plan }} &middot; {{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</p>
                                </div>
                            </div>

                            <!-- Pickup / Return Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        <span class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Pickup</span>
                                    </div>
                                    <p class="text-sm font-semibold text-[var(--gray-800)]">{{ formatDate(booking.pickup_date) }}</p>
                                    <p class="text-xs text-[var(--gray-500)] mt-0.5">{{ booking.pickup_time }}</p>
                                    <p class="text-xs text-[var(--gray-500)] mt-2 leading-relaxed">
                                        <MapPin class="w-3 h-3 inline-block mr-0.5 -mt-0.5 text-[var(--gray-400)]" />
                                        {{ booking.pickup_location }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                        <span class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Return</span>
                                    </div>
                                    <p class="text-sm font-semibold text-[var(--gray-800)]">{{ formatDate(booking.return_date) }}</p>
                                    <p class="text-xs text-[var(--gray-500)] mt-0.5">{{ booking.return_time }}</p>
                                    <p class="text-xs text-[var(--gray-500)] mt-2 leading-relaxed">
                                        <MapPin class="w-3 h-3 inline-block mr-0.5 -mt-0.5 text-[var(--gray-400)]" />
                                        {{ booking.return_location }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <UserIcon class="w-4 h-4 text-[var(--primary-600)]" />
                                Customer Information
                            </h2>
                            <button
                                v-if="booking.customer?.id"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium text-[var(--primary-700)] bg-[var(--primary-50)] hover:bg-[var(--primary-100)] transition-colors"
                                @click="openCustomerDocumentsDialog(booking.customer.id)"
                            >
                                <FileText class="w-3 h-3" />
                                View Documents
                            </button>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-[var(--primary-100)] text-[var(--primary-700)] text-sm font-bold shrink-0">
                                    {{ booking.customer?.first_name?.[0] }}{{ booking.customer?.last_name?.[0] }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-[var(--gray-900)]">
                                        {{ booking.customer?.first_name }} {{ booking.customer?.last_name }}
                                    </p>
                                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                                        <div class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Mail class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span class="truncate">{{ booking.customer?.email || 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Phone class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>{{ booking.customer?.phone || 'N/A' }}</span>
                                        </div>
                                        <div v-if="booking.customer?.flight_number" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Plane class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>Flight: {{ booking.customer.flight_number }}</span>
                                        </div>
                                        <div v-if="booking.customer?.driver_age" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <UserIcon class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>Driver age: {{ booking.customer.driver_age }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Address & Notes -->
                    <div v-if="customerAddress || customerSnapshot.notes || customerSnapshot.flight_number" class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <MapPin class="w-4 h-4 text-[var(--primary-600)]" />
                                Customer Address & Notes
                            </h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div v-if="customerAddress" class="flex items-start gap-3">
                                <MapPin class="w-4 h-4 text-[var(--gray-400)] shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-0.5">Address</p>
                                    <p v-if="customerSnapshot.address" class="text-sm text-[var(--gray-700)]">{{ customerSnapshot.address }}</p>
                                    <p v-if="customerSnapshot.city || customerSnapshot.postal_code" class="text-sm text-[var(--gray-700)]">
                                        {{ [customerSnapshot.city, customerSnapshot.postal_code].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="customerSnapshot.country" class="text-sm text-[var(--gray-600)]">{{ customerSnapshot.country }}</p>
                                </div>
                            </div>
                            <div v-if="customerSnapshot.flight_number" class="flex items-start gap-3">
                                <Plane class="w-4 h-4 text-[var(--gray-400)] shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-0.5">Flight Number</p>
                                    <p class="text-sm text-[var(--gray-700)]">{{ customerSnapshot.flight_number }}</p>
                                </div>
                            </div>
                            <div v-if="customerSnapshot.notes" class="flex items-start gap-3">
                                <Info class="w-4 h-4 text-[var(--gray-400)] shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-0.5">Customer Notes</p>
                                    <p class="text-sm text-[var(--gray-700)] whitespace-pre-line">{{ customerSnapshot.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deposit & Security -->
                    <div v-if="bookingData.pricingBreakdown.value.deposit || bookingData.pricingBreakdown.value.excess || bookingData.policies.value.selectedDepositType" class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-[var(--primary-600)]" />
                                Deposit & Security
                            </h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Customer's Selected Deposit Type -->
                            <div v-if="bookingData.policies.value.selectedDepositType" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <component :is="depositTypeIcon" class="w-5 h-5 text-emerald-600 shrink-0" />
                                    <div class="flex-1">
                                        <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Customer will pay deposit via</p>
                                        <p class="text-sm font-bold text-emerald-700 mt-0.5">{{ formatDepositType(bookingData.policies.value.selectedDepositType) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Deposit Amount -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-if="bookingData.pricingBreakdown.value.deposit" class="rounded-lg border border-[var(--gray-200)] p-3.5">
                                    <p class="text-xs font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-1">Security Deposit</p>
                                    <p class="text-lg font-bold text-[var(--gray-900)]">
                                        {{ bookingData.formatCurrency(bookingData.pricingBreakdown.value.deposit.amount, bookingData.pricingBreakdown.value.deposit.currency) }}
                                    </p>
                                    <p class="text-[11px] text-[var(--gray-400)] mt-0.5">Refundable at return</p>
                                </div>
                                <div v-if="bookingData.pricingBreakdown.value.excess" class="rounded-lg border border-amber-200 bg-amber-50/50 p-3.5">
                                    <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">Excess (Damage)</p>
                                    <p class="text-lg font-bold text-amber-700">
                                        {{ bookingData.formatCurrency(bookingData.pricingBreakdown.value.excess.amount, bookingData.pricingBreakdown.value.excess.currency) }}
                                    </p>
                                    <p v-if="bookingData.pricingBreakdown.value.excessTheft" class="text-[11px] text-amber-500 mt-0.5">
                                        Theft excess: {{ bookingData.formatCurrency(bookingData.pricingBreakdown.value.excessTheft.amount, bookingData.pricingBreakdown.value.excessTheft.currency) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Accepted deposit methods (what vendor set) -->
                            <div v-if="acceptedDepositMethods.length > 0" class="text-xs text-[var(--gray-500)]">
                                <span class="font-medium">Accepted methods:</span>
                                {{ acceptedDepositMethods.map(m => formatDepositType(m)).join(', ') }}
                            </div>
                        </div>
                    </div>

                    <!-- Rental Policies -->
                    <div v-if="bookingData.policies.value.mileage || bookingData.policies.value.fuelPolicy || bookingData.policies.value.cancellation || bookingData.policies.value.minimumDriverAge" class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Info class="w-4 h-4 text-[var(--primary-600)]" />
                                Rental Policies
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-if="bookingData.policies.value.mileage" class="flex items-start gap-2.5 p-3 rounded-lg bg-[var(--gray-50)] border border-[var(--gray-100)]">
                                    <Gauge class="w-4 h-4 text-[var(--gray-500)] shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Mileage</p>
                                        <p class="text-sm font-medium text-[var(--gray-800)] mt-0.5">{{ bookingData.policies.value.mileage }}</p>
                                    </div>
                                </div>
                                <div v-if="bookingData.policies.value.fuelPolicy" class="flex items-start gap-2.5 p-3 rounded-lg bg-[var(--gray-50)] border border-[var(--gray-100)]">
                                    <Fuel class="w-4 h-4 text-[var(--gray-500)] shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Fuel Policy</p>
                                        <p class="text-sm font-medium text-[var(--gray-800)] mt-0.5 capitalize">{{ bookingData.policies.value.fuelPolicy }}</p>
                                    </div>
                                </div>
                                <div v-if="bookingData.policies.value.cancellation" class="flex items-start gap-2.5 p-3 rounded-lg border" :class="bookingData.policies.value.cancellation.includes('Free') ? 'bg-emerald-50/50 border-emerald-100' : 'bg-amber-50/50 border-amber-100'">
                                    <CalendarX class="w-4 h-4 shrink-0 mt-0.5" :class="bookingData.policies.value.cancellation.includes('Free') ? 'text-emerald-500' : 'text-amber-500'" />
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-wider" :class="bookingData.policies.value.cancellation.includes('Free') ? 'text-emerald-500' : 'text-amber-500'">Cancellation</p>
                                        <p class="text-sm font-medium mt-0.5" :class="bookingData.policies.value.cancellation.includes('Free') ? 'text-emerald-700' : 'text-amber-700'">{{ bookingData.policies.value.cancellation }}</p>
                                    </div>
                                </div>
                                <div v-if="bookingData.policies.value.minimumDriverAge" class="flex items-start gap-2.5 p-3 rounded-lg bg-[var(--gray-50)] border border-[var(--gray-100)]">
                                    <UserIcon class="w-4 h-4 text-[var(--gray-500)] shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Min. Driver Age</p>
                                        <p class="text-sm font-medium text-[var(--gray-800)] mt-0.5">{{ bookingData.policies.value.minimumDriverAge }} years</p>
                                    </div>
                                </div>
                                <div v-if="bookingData.policies.value.transmission" class="flex items-start gap-2.5 p-3 rounded-lg bg-[var(--gray-50)] border border-[var(--gray-100)]">
                                    <Car class="w-4 h-4 text-[var(--gray-500)] shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Transmission</p>
                                        <p class="text-sm font-medium text-[var(--gray-800)] mt-0.5 capitalize">{{ bookingData.policies.value.transmission }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Reason -->
                    <div v-if="booking.booking_status === 'cancelled' && booking.cancellation_reason" class="rounded-xl border border-red-200 bg-red-50 p-5">
                        <h3 class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1.5">Cancellation Reason</h3>
                        <p class="text-sm text-red-700">{{ booking.cancellation_reason }}</p>
                    </div>
                </div>

                <!-- RIGHT: Pricing Sidebar (1 col) -->
                <div class="space-y-5">

                    <!-- Price Breakdown Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Receipt class="w-4 h-4 text-[var(--primary-600)]" />
                                Price Breakdown
                            </h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <!-- Vehicle base -->
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--gray-600)]">Vehicle ({{ booking.total_days }}d)</span>
                                <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(vendorBasePrice) }}</span>
                            </div>

                            <!-- Extras -->
                            <template v-if="booking.extras?.length">
                                <div v-for="extra in booking.extras" :key="extra.id" class="flex justify-between text-sm">
                                    <span class="text-[var(--gray-600)]">
                                        {{ extra.extra_name }}
                                        <span v-if="extra.quantity > 1" class="text-[var(--gray-400)]">&times;{{ extra.quantity }}</span>
                                    </span>
                                    <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(getExtraVendorPrice(extra)) }}</span>
                                </div>
                            </template>
                            <div v-else-if="vendorExtraAmount > 0" class="flex justify-between text-sm">
                                <span class="text-[var(--gray-600)]">Extras / Add-ons</span>
                                <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(vendorExtraAmount) }}</span>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-dashed border-[var(--gray-200)] my-1"></div>

                            <!-- Total -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-[var(--gray-900)]">Total Amount</span>
                                <span class="text-lg font-bold text-[var(--primary-800)]">
                                    {{ currSym }}{{ formatNumber(vendorTotal) }}
                                </span>
                            </div>

                            <p class="text-[11px] text-[var(--gray-400)] text-right">
                                {{ getVendorCurrency(booking) }}
                            </p>
                        </div>
                    </div>

                    <!-- Status Change Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)]">Update Status</h2>
                        </div>
                        <div class="p-5">
                            <select
                                v-model="bookingStatus"
                                @change="updateStatus"
                                class="w-full py-2.5 px-3 border border-[var(--gray-200)] rounded-lg text-sm font-medium focus:ring-2 focus:ring-[var(--primary-400)] focus:border-[var(--primary-400)] outline-none transition-colors"
                                :class="{
                                    'text-emerald-600': bookingStatus === 'completed' || bookingStatus === 'confirmed',
                                    'text-amber-600': bookingStatus === 'pending',
                                    'text-red-500': bookingStatus === 'cancelled',
                                }"
                            >
                                <option value="pending">{{ _t('vendorprofilepages', 'booking_status_pending') }}</option>
                                <option value="confirmed">{{ _t('vendorprofilepages', 'booking_status_confirmed') }}</option>
                                <option value="completed">{{ _t('vendorprofilepages', 'booking_status_completed') }}</option>
                                <option value="cancelled">{{ _t('vendorprofilepages', 'booking_status_cancelled') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Damage Protection Card -->
                    <div
                        class="rounded-xl border border-dashed border-[var(--gray-300)] hover:border-[var(--primary-400)] bg-white shadow-sm p-5 cursor-pointer transition-colors group"
                        @click="goToDamageProtection(booking.id)"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-[var(--accent-100)] flex items-center justify-center group-hover:bg-[var(--accent-100)] transition-colors">
                                <ShieldCheck class="w-5 h-5 text-[var(--accent-600)]" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-[var(--gray-800)]">Damage Protection</p>
                                <p class="text-xs text-[var(--gray-400)]">Upload before & after photos</p>
                            </div>
                            <ChevronRight class="w-4 h-4 text-[var(--gray-400)] group-hover:text-[var(--primary-600)] transition-colors" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Documents Dialog -->
        <Dialog v-model:open="isCustomerDocumentsDialogOpen">
            <DialogContent class="max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>{{ _t('vendorprofilepages', 'customer_documents_dialog_title') }}</DialogTitle>
                </DialogHeader>
                <div v-if="customerDocument" class="grid grid-cols-2 gap-4">
                    <div v-for="field in documentFields" :key="field.key" class="flex flex-col gap-2 items-center">
                        <p class="font-semibold text-sm">{{ field.label }}</p>
                        <img v-if="customerDocument[field.key]" :src="customerDocument[field.key]" :alt="field.label"
                            class="h-20 w-[150px] object-cover cursor-pointer rounded-md"
                            @click="openImageModal(customerDocument[field.key])" />
                        <span v-else class="text-gray-500 text-sm">{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                        <p class="text-xs capitalize" :class="{
                            'text-yellow-600': customerDocument.verification_status === 'pending',
                            'text-green-600': customerDocument.verification_status === 'verified',
                            'text-red-600': customerDocument.verification_status === 'rejected',
                        }">
                            {{ customerDocument.verification_status }}
                        </p>
                    </div>
                </div>
                <div v-else class="text-center py-6">
                    <span class="text-gray-500">{{ _t('vendorprofilepages', 'no_documents_available_text') }}</span>
                </div>
                <DialogFooter>
                    <button
                        class="px-4 py-2 rounded-lg border text-sm font-medium hover:bg-[var(--gray-50)] transition-colors"
                        @click="isCustomerDocumentsDialogOpen = false"
                    >
                        {{ _t('vendorprofilepages', 'button_close') }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Image Modal -->
        <Dialog v-model:open="isImageModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <img :src="selectedImage" :alt="_t('vendorprofilepages', 'document_preview_dialog_title')" class="w-full h-auto rounded-md" />
                <DialogFooter>
                    <button
                        class="px-4 py-2 rounded-lg border text-sm font-medium hover:bg-[var(--gray-50)] transition-colors"
                        @click="isImageModalOpen = false"
                    >
                        {{ _t('vendorprofilepages', 'button_close') }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useToast } from 'vue-toastification';
import { useBookingData } from '@/composables/useBookingData.js';
import loaderVariant from '../../../../assets/loader-variant.svg';
import {
    ArrowLeft, ShieldCheck, Ban, Car, MapPin, Mail, Phone, Plane,
    User as UserIcon, FileText, Receipt, ChevronRight,
    CreditCard, Banknote, Landmark, AlertTriangle, Fuel, Gauge, CalendarX, Info,
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const toast = useToast();

const props = defineProps({
    booking: { type: Object, required: true },
});

// Use the booking data composable for normalized policies, deposit, etc.
const bookingData = useBookingData(props.booking, props.booking.vehicle);

const isLoading = ref(false);
const bookingStatus = ref(props.booking.booking_status);

// Provider metadata shortcuts
const providerMeta = computed(() => props.booking.provider_metadata || {});
const benefits = computed(() => providerMeta.value.benefits || {});
const customerSnapshot = computed(() => providerMeta.value.customer_snapshot || {});

// Customer address
const customerAddress = computed(() => {
    const snap = customerSnapshot.value;
    const parts = [snap.address, snap.city, snap.postal_code, snap.country].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : null;
});

// Selected deposit type formatting
const formatDepositType = (type) => {
    if (!type) return null;
    const labels = {
        cash: 'Cash',
        credit_card: 'Credit Card',
        debit_card: 'Debit Card',
        cheque: 'Cheque',
        crypto: 'Crypto',
        bank_transfer: 'Bank Transfer',
    };
    return labels[type] || type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ');
};

// Deposit type icon component mapping
const depositTypeIcon = computed(() => {
    const type = bookingData.policies.value.selectedDepositType;
    if (!type) return null;
    const icons = { cash: Banknote, credit_card: CreditCard, debit_card: CreditCard, cheque: FileText, crypto: Landmark, bank_transfer: Landmark };
    return icons[type] || CreditCard;
});

// Deposit payment methods (what vendor accepts)
const acceptedDepositMethods = computed(() => {
    const methods = benefits.value.deposit_payment_method;
    if (!methods) return [];
    if (Array.isArray(methods)) return methods;
    try { return JSON.parse(methods); } catch { return [methods]; }
});
const isCustomerDocumentsDialogOpen = ref(false);
const isImageModalOpen = ref(false);
const customerDocument = ref(null);
const selectedImage = ref('');

const documentFields = computed(() => [
    { key: 'driving_license_front', label: _t('vendorprofilepages', 'doc_label_driving_license_front') },
    { key: 'driving_license_back', label: _t('vendorprofilepages', 'doc_label_driving_license_back') },
    { key: 'passport_front', label: _t('vendorprofilepages', 'passport_front_table_header') },
    { key: 'passport_back', label: _t('vendorprofilepages', 'passport_back_table_header') },
]);

// --- Currency helpers ---
const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥',
        'AUD': 'A$', 'CAD': 'C$', 'CHF': 'Fr', 'HKD': 'HK$',
        'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩', 'NOK': 'kr',
        'NZD': 'NZ$', 'INR': '₹', 'MXN': 'Mex$', 'ZAR': 'R',
        'AED': 'AED', 'MAD': 'MAD',
    };
    return symbols[currency] || currency || '$';
};

const getVendorCurrency = (booking) => {
    return booking.amounts?.vendor_currency
        || booking.vendor_profile?.currency
        || booking.booking_currency
        || 'EUR';
};

const currSym = computed(() => getCurrencySymbol(getVendorCurrency(props.booking)));

// --- Vendor amounts (from pre-calculated booking_amounts table) ---
const vendorTotal = computed(() => {
    return parseFloat(props.booking.amounts?.vendor_total_amount ?? props.booking.total_amount ?? 0);
});

const vendorExtraAmount = computed(() => {
    return parseFloat(props.booking.amounts?.vendor_extra_amount ?? props.booking.extra_charges ?? 0);
});

const vendorBasePrice = computed(() => {
    return Math.max(0, vendorTotal.value - vendorExtraAmount.value);
});

// Per-extra vendor price: distribute vendor_extra_amount proportionally
const getExtraVendorPrice = (extra) => {
    const extras = props.booking.extras || [];
    const extraBookingTotal = extras.reduce((sum, e) => sum + (parseFloat(e.price) * e.quantity), 0);
    if (extraBookingTotal <= 0) return 0;
    const proportion = (parseFloat(extra.price) * extra.quantity) / extraBookingTotal;
    return proportion * vendorExtraAmount.value;
};

const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

// --- Status ---
const statusBadgeClass = (status) => ({
    confirmed: 'bg-emerald-50 text-emerald-700',
    completed: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    cancelled: 'bg-red-50 text-red-600',
}[status] || 'bg-gray-50 text-gray-600');

const statusDotClass = (status) => ({
    confirmed: 'bg-emerald-500',
    completed: 'bg-emerald-500',
    pending: 'bg-amber-500',
    cancelled: 'bg-red-500',
}[status] || 'bg-gray-400');

// --- Actions ---
const goBack = () => {
    router.get(route('bookings.index', { locale: usePage().props.locale }));
};

const goToDamageProtection = (bookingId) => {
    router.get(route('vendor.damage-protection.index', { locale: usePage().props.locale, booking: bookingId }));
};

const updateStatus = async () => {
    isLoading.value = true;
    try {
        await axios.put(route('bookings.update', { locale: usePage().props.locale, booking: props.booking.id }), {
            booking_status: bookingStatus.value,
        });
        const statusMessages = {
            pending: _t('vendorprofilepages', 'booking_status_pending'),
            confirmed: _t('vendorprofilepages', 'booking_status_confirmed'),
            completed: _t('vendorprofilepages', 'booking_status_completed'),
            cancelled: _t('vendorprofilepages', 'booking_status_cancelled'),
        };
        toast.success(`Status changed to ${statusMessages[bookingStatus.value]}!`, {
            position: 'top-right', timeout: 3000,
        });
        router.reload();
    } catch (error) {
        console.error('Error updating status:', error);
        router.reload();
    } finally {
        isLoading.value = false;
    }
};

const cancelBooking = async (bookingId) => {
    if (confirm(_t('vendorprofilepages', 'confirm_cancel_booking_message'))) {
        try {
            await axios.post(route('bookings.cancel', { locale: usePage().props.locale, booking: bookingId }));
            router.reload();
        } catch (err) {
            console.error('Error canceling booking:', err);
            alert(_t('vendorprofilepages', 'alert_failed_to_cancel_booking'));
        }
    }
};

const openCustomerDocumentsDialog = async (customerId) => {
    try {
        isLoading.value = true;
        const response = await axios.get(route('vendor.customer-documents.index', { locale: usePage().props.locale, customer: customerId }));
        customerDocument.value = response.data.document || null;
        isCustomerDocumentsDialogOpen.value = true;
    } catch (error) {
        console.error('Error fetching customer documents:', error);
        toast.error('Failed to fetch customer documents.');
    } finally {
        isLoading.value = false;
    }
};

const openImageModal = (imageUrl) => {
    if (imageUrl) {
        selectedImage.value = imageUrl;
        isImageModalOpen.value = true;
    }
};
</script>
