// Driver-details form persistence: an accidental refresh, tab discard, or
// quote-expired redirect must not throw away what the customer typed —
// re-typing under time pressure is exactly how junk data gets entered.
//
// sessionStorage (dies with the tab). The licence number is deliberately NOT
// persisted (short field, sensitive; the customer re-types it).

const FORM_KEY = 'vrooem_checkout_form';
const DRAFT_TTL_MS = 30 * 60 * 1000;

export function useCheckoutDraft() {
    const saveForm = (form) => {
        try {
            const { driver_license_number, ...rest } = form || {};
            sessionStorage.setItem(FORM_KEY, JSON.stringify({ form: rest, savedAt: Date.now() }));
        } catch {
            // Storage full/unavailable — persistence is best-effort.
        }
    };

    const readForm = () => {
        try {
            const raw = sessionStorage.getItem(FORM_KEY);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            if (!parsed?.savedAt || Date.now() - parsed.savedAt > DRAFT_TTL_MS) {
                sessionStorage.removeItem(FORM_KEY);
                return null;
            }
            return parsed.form || null;
        } catch {
            return null;
        }
    };

    return { saveForm, readForm };
}
