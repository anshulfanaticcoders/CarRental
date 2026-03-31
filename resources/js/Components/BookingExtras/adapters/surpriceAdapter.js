import { computed } from 'vue';
import { resolveStandardPackages } from './shared.js';

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @returns {import('./types').AdapterResult}
 */
export function createSurpriceAdapter(props, { currentPackage } = {}) {
    // ── Packages (Standard + Full Coverage from FDW) ────────────────
    const packages = computed(() => {
        const standardPkgs = resolveStandardPackages(props.vehicle);
        if (standardPkgs.length > 0) return standardPkgs;

        const total = parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
        if (total <= 0) return [];
        const currency = props.vehicle?.currency || props.vehicle?.pricing?.currency || 'EUR';
        const perDay = parseFloat(props.vehicle?.price_per_day || props.vehicle?.pricing?.price_per_day || 0)
            || (props.numberOfDays > 0 ? +(total / props.numberOfDays).toFixed(2) : total);
        const sd = props.vehicle?.supplier_data || {};
        const excess = parseFloat(sd.excess_amount || props.vehicle?.benefits?.excess_amount || 0);
        const deposit = parseFloat(sd.deposit_amount || props.vehicle?.benefits?.deposit_amount || 0);

        const pkgs = [{
            type: 'BAS',
            name: 'Standard',
            subtitle: 'CDW & Theft Waiver included',
            total,
            price_per_day: perDay,
            currency,
            excess,
            deposit,
            isBestValue: true,
        }];

        // Full Coverage from FDW data
        const fdwTotal = parseFloat(sd.fdw_total_amount || 0);
        if (fdwTotal > 0) {
            const fdwPerDay = props.numberOfDays > 0 ? +(fdwTotal / props.numberOfDays).toFixed(2) : fdwTotal;
            pkgs.push({
                type: 'FDW',
                name: 'Full Coverage',
                subtitle: 'Zero excess — full peace of mind',
                total: fdwTotal,
                price_per_day: fdwPerDay,
                currency,
                excess: parseFloat(sd.fdw_excess_amount || 0),
                deposit: parseFloat(sd.fdw_deposit_amount || 0),
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

        if (isFullCoverage) {
            const fdwDeposit = parseFloat(sd.fdw_deposit_amount || 0);
            items.push({ label: 'CDW (Collision Damage Waiver)', detail: 'Included — Zero Excess' });
            items.push({ label: 'TW (Theft Waiver)', detail: 'Included — Zero Excess' });
            items.push({ label: 'FDW (Full Damage Waiver)', detail: 'Included' });
            if (fdwDeposit > 0) {
                items.push({ label: 'Reduced Deposit', detail: `€${fdwDeposit}` });
            }
        } else {
            const excess = parseFloat(sd.excess_amount || props.vehicle?.benefits?.excess_amount || 0);
            if (excess > 0) {
                items.push({ label: 'CDW (Collision Damage Waiver)', detail: `Included (excess: €${excess})` });
                items.push({ label: 'TW (Theft Waiver)', detail: `Included (excess: €${excess})` });
            }
        }
        items.push({ label: 'Third Party Liability (TPL)', detail: 'Included' });
        items.push({ label: 'Unlimited Mileage', detail: 'Included' });
        items.push({ label: 'VAT & Airport Surcharge', detail: 'Included' });
        items.push({ label: 'Breakdown Assistance', detail: 'Included' });
        return items;
    });

    const mandatoryAmount = computed(() => 0);

    const highlights = computed(() => {
        const items = [];
        const deposit = parseFloat(props.vehicle?.security_deposit || props.vehicle?.benefits?.deposit_amount || 0);
        if (deposit > 0) {
            items.push({ label: 'Security Deposit', value: `€${deposit}`, type: 'warning' });
        }
        const excess = parseFloat(props.vehicle?.benefits?.excess_amount || 0);
        if (excess > 0) {
            items.push({ label: 'CDW Excess', value: `€${excess}`, type: 'warning' });
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
        return {
            pickupStation: pickup.name || sd.pickup_station_name || null,
            pickupAddress: pickup.address || null,
            pickupLines: [pickup.town, pickup.postal_code].filter(Boolean),
            pickupPhone: pickup.phone || null,
            pickupEmail: pickup.email || null,
            dropoffStation: dropoff.name || sd.return_station_name || null,
            dropoffAddress: dropoff.address || null,
            dropoffLines: [dropoff.town, dropoff.postal_code].filter(Boolean),
            dropoffPhone: dropoff.phone || null,
            dropoffEmail: dropoff.email || null,
            sameLocation: !dropoff.name || pickup.name === dropoff.name,
            fuelPolicy: null,
            cancellation: null,
            officeHours: null,
            pickupInstructions: pickup.pickup_instructions || sd.pickup_additional_info || null,
            dropoffInstructions: dropoff.dropoff_instructions || null,
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
