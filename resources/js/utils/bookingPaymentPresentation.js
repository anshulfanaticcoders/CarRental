export function getCapturedPaymentReview(booking, minorUnitResolver = () => 2) {
    const mismatch = booking?.provider_metadata?.amount_mismatch;
    const currency = String(mismatch?.charged_currency || '').trim().toUpperCase();
    const chargedMinor = Number(mismatch?.charged_minor);

    if (!currency || !Number.isFinite(chargedMinor) || chargedMinor < 0) {
        return null;
    }

    const minorUnit = Number(minorUnitResolver(currency));
    if (!Number.isInteger(minorUnit) || minorUnit < 0) {
        return null;
    }

    return {
        amount: chargedMinor / (10 ** minorUnit),
        currency,
        minorUnit,
    };
}
