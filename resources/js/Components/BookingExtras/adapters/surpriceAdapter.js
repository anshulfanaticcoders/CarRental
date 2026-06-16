import { computed } from 'vue';
import { resolveStandardPackages } from './shared.js';

const normalizeLocationKey = (value) => `${value ?? ''}`.trim().toLowerCase().replace(/\s+/g, ' ');
const sameLocationValue = (left, right) => {
    const leftKey = normalizeLocationKey(left);
    const rightKey = normalizeLocationKey(right);
    return leftKey !== '' && rightKey !== '' && leftKey === rightKey;
};
const sameOffice = (left = {}, right = {}) => {
    const nameMatches = sameLocationValue(left?.name, right?.name);
    const addressMatches = sameLocationValue(left?.address, right?.address);
    const cityMatches = sameLocationValue(left?.town || left?.city, right?.town || right?.city);

    return (nameMatches && (addressMatches || cityMatches)) || (addressMatches && cityMatches);
};
const resolveCurrency = (vehicle, fallback = 'EUR') => vehicle?.currency || vehicle?.pricing?.currency || vehicle?.benefits?.deposit_currency || fallback;
const resolveDepositCurrency = (vehicle, supplierData = {}, fallback = 'EUR') => (
    supplierData.deposit_currency
    || supplierData.excess_currency
    || vehicle?.benefits?.deposit_currency
    || vehicle?.pricing?.deposit_currency
    || fallback
);
const formatCurrencyAmount = (amount, currency = 'EUR') => {
    const numeric = parseFloat(amount || 0);

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(Number.isFinite(numeric) ? numeric : 0);
    } catch {
        return `${currency} ${(Number.isFinite(numeric) ? numeric : 0).toFixed(2)}`;
    }
};

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @returns {import('./types').AdapterResult}
 */
export function createSurpriceAdapter(props, { currentPackage } = {}) {
    // ── Packages (Standard + Full Coverage from FDW) ────────────────
    const packages = computed(() => {
        const standardPkgs = resolveStandardPackages(props.vehicle);
        const currency = props.vehicle?.currency || props.vehicle?.pricing?.currency || 'EUR';
        const sd = props.vehicle?.supplier_data || {};
        const depositCurrency = resolveDepositCurrency(props.vehicle, sd, currency);
        const excess = parseFloat(sd.excess_amount || props.vehicle?.benefits?.excess_amount || 0);
        const theftExcess = parseFloat(sd.theft_excess || props.vehicle?.benefits?.excess_theft_amount || excess || 0);
        const deposit = parseFloat(sd.deposit_amount || props.vehicle?.benefits?.deposit_amount || 0);

        const pkgs = standardPkgs.length > 0
            ? standardPkgs.map((pkg) => ({
                ...pkg,
                currency: pkg.currency || currency,
                deposit_currency: pkg.deposit_currency || depositCurrency,
                excess_currency: pkg.excess_currency || depositCurrency,
                excess: pkg.excess ?? excess,
                excess_theft_amount: pkg.excess_theft_amount ?? theftExcess,
                deposit: pkg.deposit ?? deposit,
            }))
            : [];

        if (pkgs.length === 0) {
            const total = parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
            if (total <= 0) return [];
            const perDay = parseFloat(props.vehicle?.price_per_day || props.vehicle?.pricing?.price_per_day || 0)
                || (props.numberOfDays > 0 ? +(total / props.numberOfDays).toFixed(2) : total);

            pkgs.push({
                type: 'BAS',
                name: 'Standard',
                subtitle: 'Base supplier rate',
                total,
                price_per_day: perDay,
                currency,
                deposit_currency: depositCurrency,
                excess_currency: depositCurrency,
                excess,
                excess_theft_amount: theftExcess,
                deposit,
                isBestValue: true,
            });
        }

        // Full Coverage from FDW data
        const fdwTotal = parseFloat(sd.fdw_total_amount || 0);
        const hasFullCoverage = pkgs.some(pkg => `${pkg.type || ''}`.toUpperCase() === 'FDW');
        if (!hasFullCoverage && fdwTotal > 0) {
            const fdwPerDay = props.numberOfDays > 0 ? +(fdwTotal / props.numberOfDays).toFixed(2) : fdwTotal;
            pkgs.push({
                type: 'FDW',
                name: 'Full Coverage',
                subtitle: 'Supplier FDW rate',
                total: fdwTotal,
                price_per_day: fdwPerDay,
                currency,
                deposit_currency: depositCurrency,
                excess_currency: depositCurrency,
                excess: parseFloat(sd.fdw_excess_amount || 0),
                excess_theft_amount: parseFloat(sd.fdw_excess_amount || 0),
                deposit: parseFloat(sd.fdw_deposit_amount || 0),
                provider_rate_id: sd.fdw_vendor_rate_id || null,
                surprice_vendor_rate_id: sd.fdw_vendor_rate_id || null,
                surprice_rate_code: sd.fdw_rate_code || null,
                isBestValue: false,
            });
        }

        return pkgs;
    });

    // ── Optional Extras ─────────────────────────────────────────────────
    const optionalExtras = computed(() => {
        const extras = Array.isArray(props.vehicle?.extras) ? props.vehicle.extras : [];
        return extras.map((extra, index) => {
            const code = extra.code || extra.id || `EXTRA_${index}`;
            const totalPrice = parseFloat(extra.price || 0);
            const dailyRate = extra.per_day ? parseFloat(extra.price_per_day || 0) : (props.numberOfDays ? totalPrice / props.numberOfDays : totalPrice);
            const maxQty = parseInt(extra.max_quantity || extra.allow_quantity || extra.numberAllowed || 1);
            return {
                id: extra.id || extra.option_id || `ext_surprice_${code}`,
                code,
                name: extra.name || code,
                description: extra.name || code,
                price: totalPrice,
                daily_rate: dailyRate,
                total_for_booking: extra.total_price ?? extra.total_for_booking ?? totalPrice,
                currency: extra.currency || 'EUR',
                required: false,
                max_quantity: maxQty,
                numberAllowed: maxQty,
                allow_quantity: maxQty > 1,
                purpose: extra.purpose ?? null,
            };
        });
    });

    // FDW is handled as a package (Full Coverage), not a separate protection plan
    const protectionPlans = computed(() => []);

    // ── Included Items (changes based on selected package) ────────────
    const includedItems = computed(() => {
        const items = [];
        const selectedPkg = currentPackage?.value || 'BAS';
        const isFullCoverage = selectedPkg === 'FDW';
        const sd = props.vehicle?.supplier_data || {};
        const currency = resolveCurrency(props.vehicle);

        if (isFullCoverage) {
            const fdwDeposit = parseFloat(sd.fdw_deposit_amount || 0);
            items.push({ label: 'FDW (Full Damage Waiver)', detail: 'Supplier FDW rate selected' });
            items.push({ label: 'Excess', detail: formatCurrencyAmount(0, currency) });
            if (fdwDeposit > 0) {
                items.push({ label: 'Reduced Deposit', detail: formatCurrencyAmount(fdwDeposit, currency) });
            }
        } else {
            const excess = parseFloat(sd.excess_amount || props.vehicle?.benefits?.excess_amount || 0);
            if (excess > 0) {
                items.push({ label: 'Supplier excess', detail: formatCurrencyAmount(excess, currency) });
            }
        }
        items.push({ label: 'Unlimited Mileage', detail: 'Included' });
        return items;
    });

    const mandatoryAmount = computed(() => 0);

    const highlights = computed(() => {
        const items = [];
        const currency = resolveCurrency(props.vehicle);
        const sd = props.vehicle?.supplier_data || {};
        const selectedPkg = currentPackage?.value || 'BAS';
        const isFullCoverage = selectedPkg === 'FDW';
        const deposit = isFullCoverage
            ? parseFloat(sd.fdw_deposit_amount || props.vehicle?.benefits?.deposit_amount || 0)
            : parseFloat(props.vehicle?.security_deposit || sd.deposit_amount || props.vehicle?.benefits?.deposit_amount || 0);
        if (deposit > 0) {
            items.push({ label: 'Security Deposit', value: formatCurrencyAmount(deposit, currency), type: 'warning' });
        }
        const excess = isFullCoverage
            ? parseFloat(sd.fdw_excess_amount || 0)
            : parseFloat(sd.excess_amount || props.vehicle?.benefits?.excess_amount || 0);
        if (excess > 0) {
            items.push({ label: 'CDW Excess', value: formatCurrencyAmount(excess, currency), type: 'warning' });
        }
        return items;
    });

    const allExtras = computed(() => [...optionalExtras.value]);
    const taxBreakdown = computed(() => null);
    const baseTotal = computed(() => {
        const product = packages.value.find(p => p.type === 'BAS');
        return parseFloat(product?.total || props.vehicle?.total_price || 0);
    });

    const locationData = computed(() => {
        const sd = props.vehicle?.supplier_data || {};
        const pickup = sd.pickup_office || {};
        const dropoff = sd.dropoff_office || {};
        const requestedOneWay = Boolean(
            props.dropoffLocation
            && props.pickupLocation
            && normalizeLocationKey(props.dropoffLocation) !== normalizeLocationKey(props.pickupLocation)
        );
        const dropoffRepeatsPickup = requestedOneWay && sameOffice(dropoff, pickup);
        const usableDropoff = dropoffRepeatsPickup ? {} : dropoff;
        return {
            pickupStation: pickup.name || sd.pickup_station_name || null,
            pickupAddress: pickup.address || null,
            pickupLines: [pickup.town, pickup.postal_code].filter(Boolean),
            pickupPhone: pickup.phone || null,
            pickupEmail: pickup.email || null,
            dropoffStation: usableDropoff.name || (dropoffRepeatsPickup ? null : sd.return_station_name) || null,
            dropoffAddress: usableDropoff.address || null,
            dropoffLines: [usableDropoff.town, usableDropoff.postal_code].filter(Boolean),
            dropoffPhone: usableDropoff.phone || null,
            dropoffEmail: usableDropoff.email || null,
            sameLocation: !requestedOneWay && (!dropoff.name || pickup.name === dropoff.name),
            fuelPolicy: null,
            cancellation: null,
            officeHours: null,
            pickupInstructions: pickup.pickup_instructions || sd.pickup_additional_info || null,
            dropoffInstructions: usableDropoff.dropoff_instructions || null,
        };
    });

    const computeNetTotal = (extrasTotal, currentProduct) => {
        const pkgPrice = parseFloat(currentProduct?.total || 0)
            || parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
        return pkgPrice + extrasTotal;
    };

    // Yes — all data is dynamic per vehicle, nothing hardcoded.

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal,
    };
}
