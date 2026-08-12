// Canonical provider-source normalization — mirror of the backend
// ProviderBookingContract::SOURCE_ALIASES. Always compare through this so a
// differently-cased or aliased source can never skip provider-specific rules.
const SOURCE_ALIASES = {
    adobe: 'adobe',
    adobe_car: 'adobe',
    green_motion: 'greenmotion',
    greenmotion: 'greenmotion',
    ok_mobility: 'okmobility',
    okmobility: 'okmobility',
    record_go: 'recordgo',
    recordgo: 'recordgo',
    sicilybycar: 'sicily_by_car',
    sicily_by_car: 'sicily_by_car',
    u_save: 'usave',
    usave: 'usave',
    yes_away: 'yesaway',
    yesaway: 'yesaway',
};

export function normalizeProviderSource(source) {
    const key = String(source || '').trim().toLowerCase();
    return SOURCE_ALIASES[key] || key;
}
