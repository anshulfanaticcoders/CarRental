const compact = (value) => `${value ?? ''}`.trim();

export const buildOfferMediaSummary = ({
    supplierName,
    pickupOffice,
    transmission,
    fuelType,
    seats,
    mileagePolicy,
    cancellation,
} = {}) => {
    const specs = [compact(transmission), compact(fuelType), Number.isFinite(Number(seats)) ? `${Number(seats)} seats` : '']
        .filter(Boolean)
        .join(' • ');

    const cancellationValue = cancellation?.available
        ? `Free up to ${Number(cancellation?.daysBeforePickup ?? 0)} days before pickup`
        : 'Non-refundable';

    return [
        compact(supplierName) ? { icon: 'badge', label: 'Supplier', value: compact(supplierName) } : null,
        compact(pickupOffice) ? { icon: 'pin', label: 'Pickup', value: compact(pickupOffice) } : null,
        specs ? { icon: 'gauge', label: 'Specs', value: specs } : null,
        compact(mileagePolicy) ? { icon: 'road', label: 'Mileage', value: compact(mileagePolicy) } : null,
        { icon: 'shield', label: 'Cancellation', value: cancellationValue },
    ].filter(Boolean);
};
