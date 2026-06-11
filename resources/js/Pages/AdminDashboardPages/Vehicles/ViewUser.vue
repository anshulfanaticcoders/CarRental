<template>
    <DialogContent class="admin-vehicle-view-dialog max-h-[92vh] overflow-hidden p-0">
        <div class="admin-vehicle-view">
            <DialogHeader class="admin-vehicle-view__header">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="admin-vehicle-view__icon">
                        <Car class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 space-y-1">
                        <p class="admin-vehicle-view__eyebrow">Vehicle profile</p>
                        <DialogTitle class="admin-vehicle-view__title">
                            {{ vehicleTitle }}
                        </DialogTitle>
                        <p class="admin-vehicle-view__subtitle">
                            {{ vehicleSubtitle }}
                        </p>
                    </div>
                </div>

                <span :class="statusBadgeClass">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                    {{ normalizedStatus }}
                </span>
            </DialogHeader>

            <div class="admin-vehicle-view__body">
                <section class="admin-vehicle-view__visual">
                    <div class="admin-vehicle-view__image-wrap">
                        <img
                            v-if="primaryImage"
                            :src="primaryImage"
                            :alt="vehicleTitle"
                            class="admin-vehicle-view__image"
                        />
                        <div v-else class="admin-vehicle-view__image-empty">
                            <ImageIcon class="h-9 w-9" />
                            <span>No vehicle image</span>
                        </div>
                    </div>

                    <div class="admin-vehicle-view__price-card">
                        <span>Daily price</span>
                        <strong>{{ dailyPrice }}</strong>
                    </div>

                    <div class="admin-vehicle-view__quick-grid">
                        <div>
                            <span>Seats</span>
                            <strong>{{ valueOrDash(user.seating_capacity) }}</strong>
                        </div>
                        <div>
                            <span>Fuel</span>
                            <strong>{{ valueOrDash(user.fuel) }}</strong>
                        </div>
                        <div>
                            <span>Gearbox</span>
                            <strong>{{ valueOrDash(user.transmission) }}</strong>
                        </div>
                        <div>
                            <span>Color</span>
                            <strong>{{ valueOrDash(user.color) }}</strong>
                        </div>
                    </div>
                </section>

                <section class="admin-vehicle-view__content">
                    <div class="admin-vehicle-view__section">
                        <div class="admin-vehicle-view__section-title">
                            <Info class="h-4 w-4" />
                            Vehicle details
                        </div>
                        <dl class="admin-vehicle-view__list">
                            <div>
                                <dt>Vehicle ID</dt>
                                <dd>#{{ valueOrDash(user.id) }}</dd>
                            </div>
                            <div>
                                <dt>Brand</dt>
                                <dd>{{ valueOrDash(user.brand) }}</dd>
                            </div>
                            <div>
                                <dt>Model</dt>
                                <dd>{{ valueOrDash(user.model) }}</dd>
                            </div>
                            <div>
                                <dt>Spec</dt>
                                <dd>{{ vehicleSpec }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="admin-vehicle-view__section">
                        <div class="admin-vehicle-view__section-title">
                            <Building2 class="h-4 w-4" />
                            Vendor
                        </div>
                        <dl class="admin-vehicle-view__list">
                            <div>
                                <dt>Company</dt>
                                <dd>{{ vendorName }}</dd>
                            </div>
                            <div>
                                <dt>Contact</dt>
                                <dd>{{ vendorContact }}</dd>
                            </div>
                            <div>
                                <dt>Vendor ID</dt>
                                <dd>#{{ valueOrDash(user.vendor_id) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="admin-vehicle-view__section admin-vehicle-view__section--wide">
                        <div class="admin-vehicle-view__section-title">
                            <MapPin class="h-4 w-4" />
                            Office and location
                        </div>
                        <dl class="admin-vehicle-view__list">
                            <div>
                                <dt>Office</dt>
                                <dd>{{ officeName }}</dd>
                            </div>
                            <div>
                                <dt>Meta</dt>
                                <dd>{{ officeMeta }}</dd>
                            </div>
                            <div>
                                <dt>Address</dt>
                                <dd>{{ officeAddress }}</dd>
                            </div>
                            <div>
                                <dt>Added</dt>
                                <dd>{{ createdAt }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>
        </div>
    </DialogContent>
</template>

<script setup>
import { computed } from "vue";
import { Building2, Car, Image as ImageIcon, Info, MapPin } from "lucide-vue-next";
import { DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { useCurrencyConversion } from "@/composables/useCurrencyConversion";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const { getCurrencySymbol } = useCurrencyConversion();

const valueOrDash = (value) => {
    if (value === null || value === undefined || value === "") {
        return "N/A";
    }

    return value;
};

const titleCase = (value) => {
    if (!value) return "N/A";

    return String(value)
        .replace(/[_-]+/g, " ")
        .replace(/\s+/g, " ")
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const vehicleTitle = computed(() => {
    return [props.user.brand, props.user.model].filter(Boolean).join(" ") || "Vehicle";
});

const vehicleSpec = computed(() => {
    return [props.user.color, props.user.transmission, props.user.fuel]
        .filter(Boolean)
        .map(titleCase)
        .join(" / ") || "N/A";
});

const vehicleSubtitle = computed(() => {
    return [vehicleSpec.value, props.user.seating_capacity ? `${props.user.seating_capacity} seats` : null]
        .filter(Boolean)
        .join(" / ");
});

const primaryImage = computed(() => {
    return props.user.images?.[0]?.image_url || "";
});

const normalizedStatus = computed(() => titleCase(props.user.status));

const statusBadgeClass = computed(() => {
    const status = String(props.user.status || "").toLowerCase();
    const base = "admin-vehicle-view__status";

    if (status.includes("available") || status.includes("active")) return `${base} ${base}--success`;
    if (status.includes("rented") || status.includes("pending")) return `${base} ${base}--warning`;
    if (status.includes("maintenance") || status.includes("rejected")) return `${base} ${base}--danger`;

    return base;
});

const currencySymbol = computed(() => {
    const code = props.user.vendor_profile?.currency || props.user.User?.profile?.currency;

    return code ? getCurrencySymbol(code) : "$";
});

const dailyPrice = computed(() => {
    return props.user.price_per_day ? `${currencySymbol.value}${props.user.price_per_day}` : "Not set";
});

const vendorName = computed(() => {
    return props.user.vendor_profile_data?.company_name
        || [props.user.User?.first_name, props.user.User?.last_name].filter(Boolean).join(" ")
        || [props.user.user?.first_name, props.user.user?.last_name].filter(Boolean).join(" ")
        || (props.user.vendor_id ? `Vendor #${props.user.vendor_id}` : "N/A");
});

const vendorContact = computed(() => {
    return props.user.User?.email || props.user.user?.email || (props.user.vendor_id ? `Vendor ID: ${props.user.vendor_id}` : "N/A");
});

const officeName = computed(() => {
    return props.user.vendor_location?.name || props.user.location || "No office assigned";
});

const officeMeta = computed(() => {
    const location = props.user.vendor_location;
    const parts = [
        location?.location_type || props.user.location_type,
        location?.iata_code,
        location?.city || props.user.city,
        location?.country || props.user.country,
    ].filter(Boolean);

    return parts.join(" / ") || "Office details unavailable";
});

const officeAddress = computed(() => {
    const location = props.user.vendor_location;

    return [
        location?.address_line_1,
        location?.address_line_2,
        location?.city || props.user.city,
        location?.state || props.user.state,
        location?.country || props.user.country,
    ].filter(Boolean).join(", ") || props.user.full_vehicle_address || "No address";
});

const createdAt = computed(() => {
    if (!props.user.created_at) return "N/A";

    return new Date(props.user.created_at).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
});
</script>

<style scoped>
:global(.admin-vehicle-view-dialog) {
    width: min(980px, calc(100vw - 2rem));
    max-width: min(980px, calc(100vw - 2rem)) !important;
}

.admin-vehicle-view {
    position: relative;
    overflow: hidden;
    color: #e8f6fb;
    background:
        radial-gradient(circle at 18% 8%, rgba(34, 211, 238, 0.18), transparent 30%),
        linear-gradient(145deg, rgba(7, 25, 35, 0.98), rgba(10, 39, 53, 0.96));
}

.admin-vehicle-view__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.35rem 1.5rem 1rem;
    border-bottom: 1px solid rgba(176, 212, 230, 0.16);
}

.admin-vehicle-view__icon {
    display: grid;
    width: 2.75rem;
    height: 2.75rem;
    place-items: center;
    flex: 0 0 auto;
    border: 1px solid rgba(34, 211, 238, 0.24);
    border-radius: 1rem;
    color: #67e8f9;
    background: rgba(34, 211, 238, 0.12);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.admin-vehicle-view__eyebrow {
    margin: 0;
    color: #78a6ba;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.admin-vehicle-view__title {
    margin: 0;
    color: #f4fbff;
    font-size: clamp(1.25rem, 2vw, 1.75rem);
    font-weight: 850;
    line-height: 1.12;
}

.admin-vehicle-view__subtitle {
    margin: 0;
    color: #a7c1cf;
    font-size: 0.9rem;
}

.admin-vehicle-view__status {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    white-space: nowrap;
    border: 1px solid rgba(34, 211, 238, 0.2);
    border-radius: 999px;
    padding: 0.45rem 0.72rem;
    color: #67e8f9;
    background: rgba(34, 211, 238, 0.12);
    font-size: 0.72rem;
    font-weight: 850;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.admin-vehicle-view__status--success {
    border-color: rgba(16, 185, 129, 0.34);
    color: #6ee7b7;
    background: rgba(16, 185, 129, 0.14);
}

.admin-vehicle-view__status--warning {
    border-color: rgba(245, 158, 11, 0.34);
    color: #fcd34d;
    background: rgba(245, 158, 11, 0.14);
}

.admin-vehicle-view__status--danger {
    border-color: rgba(248, 113, 113, 0.34);
    color: #fca5a5;
    background: rgba(248, 113, 113, 0.14);
}

.admin-vehicle-view__body {
    display: grid;
    grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.4fr);
    gap: 1rem;
    max-height: calc(92vh - 5.5rem);
    overflow: auto;
    padding: 1rem 1.5rem 1.5rem;
}

.admin-vehicle-view__visual,
.admin-vehicle-view__section {
    min-width: 0;
    border: 1px solid rgba(176, 212, 230, 0.14);
    border-radius: 1.1rem;
    background: rgba(6, 22, 31, 0.64);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.admin-vehicle-view__visual {
    align-self: start;
    overflow: hidden;
}

.admin-vehicle-view__image-wrap {
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: rgba(3, 12, 18, 0.84);
}

.admin-vehicle-view__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.admin-vehicle-view__image-empty {
    display: grid;
    height: 100%;
    place-items: center;
    gap: 0.5rem;
    color: #78a6ba;
    font-size: 0.85rem;
    font-weight: 800;
}

.admin-vehicle-view__price-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin: 1rem;
    border: 1px solid rgba(34, 211, 238, 0.22);
    border-radius: 0.95rem;
    padding: 0.9rem 1rem;
    background: linear-gradient(135deg, rgba(21, 59, 79, 0.72), rgba(8, 145, 178, 0.2));
}

.admin-vehicle-view__price-card span,
.admin-vehicle-view__quick-grid span {
    color: #9bb8c7;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.admin-vehicle-view__price-card strong {
    color: #f4fbff;
    font-size: 1.35rem;
    font-weight: 900;
}

.admin-vehicle-view__quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    padding: 0 1rem 1rem;
}

.admin-vehicle-view__quick-grid div {
    min-width: 0;
    border: 1px solid rgba(176, 212, 230, 0.12);
    border-radius: 0.85rem;
    padding: 0.78rem;
    background: rgba(10, 29, 40, 0.72);
}

.admin-vehicle-view__quick-grid strong {
    display: block;
    margin-top: 0.2rem;
    overflow: hidden;
    color: #e8f6fb;
    font-size: 0.92rem;
    font-weight: 850;
    text-overflow: ellipsis;
    text-transform: capitalize;
    white-space: nowrap;
}

.admin-vehicle-view__content {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    min-width: 0;
}

.admin-vehicle-view__section {
    padding: 1rem;
}

.admin-vehicle-view__section--wide {
    grid-column: 1 / -1;
}

.admin-vehicle-view__section-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    color: #67e8f9;
    font-size: 0.78rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.admin-vehicle-view__list {
    display: grid;
    gap: 0.65rem;
    margin: 0.9rem 0 0;
}

.admin-vehicle-view__list div {
    display: grid;
    grid-template-columns: minmax(7.25rem, 0.45fr) minmax(0, 1fr);
    gap: 0.9rem;
    align-items: start;
    border-top: 1px solid rgba(176, 212, 230, 0.1);
    padding-top: 0.65rem;
}

.admin-vehicle-view__list dt {
    color: #91adbc;
    font-size: 0.78rem;
    font-weight: 760;
}

.admin-vehicle-view__list dd {
    min-width: 0;
    margin: 0;
    overflow-wrap: anywhere;
    color: #edf9fd;
    font-size: 0.9rem;
    font-weight: 760;
    text-align: left;
}

@media (max-width: 860px) {
    .admin-vehicle-view__header {
        flex-direction: column;
        padding-right: 3rem;
    }

    .admin-vehicle-view__body {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 620px) {
    .admin-vehicle-view__body {
        max-height: calc(92vh - 7rem);
        padding: 0.85rem;
    }

    .admin-vehicle-view__content {
        grid-template-columns: 1fr;
    }

    .admin-vehicle-view__quick-grid {
        grid-template-columns: 1fr;
    }

    .admin-vehicle-view__list div {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }

    .admin-vehicle-view__list dd {
        text-align: left;
    }
}

@media (prefers-reduced-motion: reduce) {
    .admin-vehicle-view *,
    .admin-vehicle-view *::before,
    .admin-vehicle-view *::after {
        transition-duration: 1ms !important;
        animation-duration: 1ms !important;
    }
}
</style>
