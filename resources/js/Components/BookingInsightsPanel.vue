<script setup>
import { computed } from 'vue';

const props = defineProps({
    booking: {
        type: Object,
        default: null
    },
    vehicle: {
        type: Object,
        default: null
    },
    partner: {
        type: Object,
        default: null
    }
});

const formatDateTime = (dateString, timeString) => {
    if (!dateString) return 'TBD';
    const date = new Date(dateString);
    const formattedDate = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
    if (timeString) {
        return `${formattedDate}, ${timeString}`;
    }
    return formattedDate;
};

const statusLabel = computed(() => {
    if (!props.booking?.status && !props.booking?.booking_status) return 'Status pending';
    const status = props.booking?.status ?? props.booking?.booking_status;
    return status.charAt(0).toUpperCase() + status.slice(1);
});

const vehicleName = computed(() => {
    if (!props.vehicle) return 'Vehicle details pending';
    const nameParts = [props.vehicle.name, props.vehicle.brand, props.vehicle.model].filter(Boolean);
    return nameParts.join(' · ');
});

const vehicleImage = computed(() => {
    return props.vehicle?.image || '/images/vehicles/default-vehicle.jpg';
});

const partnerName = computed(() => {
    if (!props.partner) return 'Conversation';
    return `${props.partner.first_name || ''} ${props.partner.last_name || ''}`.trim();
});
</script>

<template>
    <div class="insights-panel">
        <div class="insights-header">
            <h3>Reservation Intelligence</h3>
            <p class="insights-subtitle">Curated context for {{ partnerName }}</p>
        </div>

        <div class="insights-card vehicle-card">
            <div class="vehicle-pill">Booking Focus</div>
            <div class="vehicle-details">
                <img :src="vehicleImage" :alt="vehicleName" class="vehicle-image" />
                <div>
                    <h4>{{ vehicleName }}</h4>
                    <p>{{ statusLabel }}</p>
                </div>
            </div>
            <div class="timeline">
                <div>
                    <strong>Pickup</strong>
                    <span>{{ formatDateTime(booking?.pickup_date, booking?.pickup_time) }}</span>
                </div>
                <div>
                    <strong>Return</strong>
                    <span>{{ formatDateTime(booking?.return_date, booking?.return_time) }}</span>
                </div>
            </div>
        </div>

        <div class="insights-card">
            <div class="insights-row">
                <span>Booking Status</span>
                <strong>{{ statusLabel }}</strong>
            </div>
            <div class="insights-row">
                <span>Total Amount</span>
                <strong>{{ booking?.total_amount ? `$${booking.total_amount}` : 'TBD' }}</strong>
            </div>
            <div class="insights-row">
                <span>Amount Paid</span>
                <strong>{{ booking?.amount_paid ? `$${booking.amount_paid}` : 'TBD' }}</strong>
            </div>
        </div>

        <div class="insights-card">
            <h4>Quick Actions</h4>
            <div class="quick-actions">
                <button type="button">Confirm Pickup</button>
                <button type="button">Reschedule</button>
                <button type="button">Offer Upgrade</button>
                <button type="button">Extend Booking</button>
            </div>
        </div>

        <div class="insights-card">
            <h4>Shared Files</h4>
            <div class="file-list">
                <div class="file-item">
                    <span>Rental Agreement</span>
                    <span>{{ booking ? 'Available' : 'Pending' }}</span>
                </div>
                <div class="file-item">
                    <span>Verification</span>
                    <span>{{ booking ? 'Verified' : 'Pending' }}</span>
                </div>
                <div class="file-item">
                    <span>Vehicle Checklist</span>
                    <span>{{ booking ? 'Uploaded' : 'Pending' }}</span>
                </div>
            </div>
        </div>

        <div v-if="!booking" class="empty-state">
            Select a conversation to see booking insights.
        </div>
    </div>
</template>

<style scoped>
.insights-panel {
    height: 100%;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: linear-gradient(160deg, rgba(232, 246, 252, 0.94), rgba(219, 234, 244, 0.9));
    border-left: 1px solid rgba(21, 59, 79, 0.14);
    overflow-y: auto;
}

.insights-header h3 {
    font-family: "Cormorant Garamond", serif;
    font-size: 20px;
    margin-bottom: 6px;
}

.insights-subtitle {
    font-size: 12px;
    color: #5a6a71;
}

.insights-card {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 18px;
    padding: 16px;
    border: 1px solid rgba(21, 59, 79, 0.18);
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-shadow: 0 12px 28px rgba(10, 26, 32, 0.08);
}

.vehicle-card {
    background: linear-gradient(135deg, #153b4f, #1f556f);
    color: #fffaf2;
}

.vehicle-pill {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: 999px;
    width: fit-content;
}

.vehicle-details {
    display: flex;
    gap: 12px;
    align-items: center;
}

.vehicle-image {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.vehicle-details h4 {
    font-size: 16px;
    font-weight: 600;
}

.vehicle-details p {
    font-size: 12px;
    opacity: 0.8;
}

.timeline {
    display: grid;
    gap: 8px;
    font-size: 12px;
}

.timeline span {
    display: block;
    color: rgba(255, 255, 255, 0.8);
}

.insights-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #1d2c32;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.quick-actions button {
    border: 1px solid rgba(21, 59, 79, 0.2);
    background: rgba(255, 255, 255, 0.85);
    padding: 10px;
    border-radius: 12px;
    font-size: 12px;
    cursor: pointer;
    transition: 0.2s ease;
}

.quick-actions button:hover {
    background: rgba(21, 59, 79, 0.08);
}

.file-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 12px;
}

.file-item {
    display: flex;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.6);
    padding: 8px 10px;
    border-radius: 10px;
}

.empty-state {
    font-size: 12px;
    color: #5a6a71;
    text-align: center;
    padding: 12px;
    border: 1px dashed rgba(21, 59, 79, 0.24);
    border-radius: 12px;
}
</style>
