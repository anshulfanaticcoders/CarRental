export const ALTERNATIVES_INITIAL_COUNT = 3;
export const ALTERNATIVES_REVEAL_STEP = 3;

const PARTNERS = {
    trabber: { key: 'trabber', label: 'Trabber', translationKey: 'partner_source_trabber' },
    skyscanner: { key: 'skyscanner', label: 'Skyscanner', translationKey: 'partner_source_skyscanner' },
    partner: { key: 'partner', label: 'Partner', translationKey: 'partner_source_generic' },
};

const pathSegments = (url) => `${url ?? ''}`
    .split('#')[0]
    .split('?')[0]
    .replace(/^[a-z][a-z0-9+.-]*:\/\/[^/]+/i, '')
    .split('/')
    .filter(Boolean);

/**
 * The Inertia page url is the only reliable partner signal: Skyscanner and
 * Trabber share the OfferResults page and neither payload names its source.
 */
export const resolveOfferPartner = (url) => {
    const segments = pathSegments(url);
    const offersIndex = segments.indexOf('offers');

    if (offersIndex === -1) {
        return PARTNERS.partner;
    }

    return segments[offersIndex - 1] === 'trabber' ? PARTNERS.trabber : PARTNERS.skyscanner;
};

export const revealMoreAlternatives = (visibleCount, totalCount) => Math.min(visibleCount + ALTERNATIVES_REVEAL_STEP, totalCount);

/**
 * Internal booking steps share the offer URL. Preserve Inertia's page state so
 * its own popstate listener never receives an entry without component metadata.
 */
export const buildOfferHistoryState = (currentState, bookingStep) => ({
    ...(currentState && typeof currentState === 'object' && !Array.isArray(currentState) ? currentState : {}),
    bookingStep,
});

const roundToCents = (amount) => Math.round((amount + Number.EPSILON) * 100) / 100;

/**
 * The payment percentage is supplier-driven, so an absent or out-of-range value
 * must read as unavailable rather than silently become a made-up deposit rate.
 * Zero is a legitimate rate and stays zero.
 */
export const normalizeOfferPaymentPercentage = (value) => {
    if (value === null || value === undefined || `${value}`.trim() === '') {
        return null;
    }

    const percentage = Number(value);

    if (!Number.isFinite(percentage) || percentage < 0 || percentage > 100) {
        return null;
    }

    return percentage;
};

export const resolveOfferPaymentSplit = (total, percentage) => {
    const amount = Number(total);
    const rate = normalizeOfferPaymentPercentage(percentage);

    if (rate === null || total === null || total === undefined || !Number.isFinite(amount)) {
        return null;
    }

    const payNow = roundToCents((amount * rate) / 100);

    return { payNow, payLater: roundToCents(amount - payNow) };
};

const SPEC_ORDER = ['class', 'seats', 'bags', 'transmission', 'fuel', 'airConditioning', 'mileage'];
const SPEC_KEYS = { airConditioning: 'air_conditioning' };

/**
 * Every spec the supplier actually sent is a decision fact, but repeating the
 * same wording twice (category echoing the SIPP class, for instance) is noise.
 */
/**
 * @param {Record<string, unknown>} specs
 * @returns {Array<{ key: string, value: string }>}
 */
export const buildOfferSpecFacts = (specs = {}) => {
    const seen = new Set();

    return SPEC_ORDER.reduce((facts, field) => {
        const value = `${specs[field] ?? ''}`.trim();
        const fingerprint = value.toLowerCase();

        if (value === '' || seen.has(fingerprint)) {
            return facts;
        }

        seen.add(fingerprint);
        facts.push({ key: SPEC_KEYS[field] ?? field, value });

        return facts;
    }, /** @type {Array<{ key: string, value: string }>} */ ([]));
};
