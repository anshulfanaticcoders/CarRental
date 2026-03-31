import { computed } from 'vue';

/**
 * Creates a Renteon provider adapter for BookingExtrasStep.
 *
 * @param {Object} props - Component props (vehicle, numberOfDays, etc.)
 * @returns {import('./types').AdapterResult & Record<string, any>}
 */
export function createRenteonAdapter(props) {
    // ── Packages ───────────────────────────────────────────────────────
    const packages = computed(() => {
        const vehicleProducts = Array.isArray(props.vehicle?.products) ? props.vehicle.products : [];

        if (vehicleProducts.length > 0) {
            return vehicleProducts.map((product, index) => ({
                type: product.type || `REN_${index + 1}`,
                name: product.name || `Rate Option ${index + 1}`,
                subtitle: product.subtitle || 'Rental option',
                pricePerDay: parseFloat(product.price_per_day ?? props.vehicle?.price_per_day ?? 0) || 0,
                total: parseFloat(product.total ?? props.vehicle?.total_price ?? 0) || 0,
                deposit: parseFloat(product.deposit ?? props.vehicle?.security_deposit ?? 0) || 0,
                excess: product.excess !== undefined && product.excess !== null ? parseFloat(product.excess) : null,
                excessTheft: product.excess_theft_amount !== undefined && product.excess_theft_amount !== null ? parseFloat(product.excess_theft_amount) : null,
                benefits: Array.isArray(product.benefits) ? product.benefits : [],
                isBestValue: Boolean(product.isBestValue ?? index === 0),
                isAddOn: false,
                gateway_vehicle_id: product.gateway_vehicle_id || props.vehicle?.gateway_vehicle_id || null,
                connector_id: product.connector_id || props.vehicle?.connector_id || null,
                provider_pickup_office_id: product.provider_pickup_office_id || props.vehicle?.provider_pickup_office_id || null,
                provider_dropoff_office_id: product.provider_dropoff_office_id || props.vehicle?.provider_dropoff_office_id || null,
                pricelist_id: product.pricelist_id || props.vehicle?.pricelist_id || null,
                pricelist_code: product.pricelist_code || props.vehicle?.pricelist_code || null,
                price_date: product.price_date || props.vehicle?.price_date || null,
                prepaid: product.prepaid ?? props.vehicle?.prepaid ?? false,
                currency: product.currency || props.vehicle?.currency || 'EUR',
            }));
        }

        return [{
            type: 'BAS',
            name: 'Basic Rental',
            subtitle: 'Standard Package',
            pricePerDay: parseFloat(props.vehicle?.price_per_day) || 0,
            total: parseFloat(props.vehicle?.total_price) || ((parseFloat(props.vehicle?.price_per_day) || 0) * props.numberOfDays),
            deposit: parseFloat(props.vehicle?.security_deposit) || 0,
            excess: props.vehicle?.benefits?.excess_amount ?? null,
            excessTheft: props.vehicle?.benefits?.excess_theft_amount ?? null,
            benefits: [],
            isBestValue: true,
            isAddOn: false
        }];
    });

    // ── Included Services ──────────────────────────────────────────────
    const renteonIncludedServices = computed(() => {
        const extras = props.vehicle?.extras || [];
        return extras.filter(extra => extra.included || extra.free_of_charge || extra.included_in_price || extra.included_in_price_limited);
    });

    // ── Helpers ────────────────────────────────────────────────────────
    const isRenteonProtectionExtra = (extra) => {
        if (!extra) return false;
        const code = `${extra.code || ''}`.toUpperCase();
        const group = `${extra.service_group || extra.service_type || ''}`.toLowerCase();
        const name = `${extra.name || extra.description || ''}`.toLowerCase();
        if (code.startsWith('INS-')) return true;
        if (group.includes('insurance')) return true;
        return name.includes('insurance') || name.includes('cdw') || name.includes('scdw') || name.includes('pai');
    };

    const getRenteonExtraKey = (extra) => {
        if (!extra) return '';
        const code = `${extra.code || ''}`.trim();
        if (code) return code.toUpperCase();
        const name = `${extra.name || extra.description || ''}`.trim();
        if (name) return name.toUpperCase();
        return `${extra.service_id || extra.id || ''}`.trim();
    };

    // ── Protection Plans ───────────────────────────────────────────────
    const protectionPlans = computed(() => {
        const extras = props.vehicle?.extras || [];
        const byId = new Map();
        extras.filter(isRenteonProtectionExtra).forEach(extra => {
            const key = getRenteonExtraKey(extra);
            const id = extra.id || extra.option_id || `ext_renteon_${key || (extra.service_id || extra.code)}`;
            if (!id || byId.has(id)) return;
            byId.set(id, {
                id,
                code: extra.code || extra.id,
                name: extra.name || extra.description || 'Protection',
                description: extra.description || extra.name || 'Protection Plan',
                price: parseFloat(extra.price || extra.amount || 0) * props.numberOfDays,
                daily_rate: parseFloat(extra.daily_rate || extra.price || extra.amount || 0),
                total_for_booking: extra.total_price ?? extra.total_for_booking ?? extra.Total_for_this_booking ?? (parseFloat(extra.price || extra.amount || 0) * props.numberOfDays),
                amount: extra.price || extra.amount,
                maxQuantity: extra.max_quantity || 1,
                numberAllowed: extra.max_quantity || 1,
                service_id: extra.service_id || extra.id,
                service_group: extra.service_group,
                service_type: extra.service_type,
                required: extra.required || false,
            });
        });
        return Array.from(byId.values());
    });

    // ── Driver Policy ──────────────────────────────────────────────────
    const renteonDriverPolicy = computed(() => {
        const benefits = props.vehicle?.benefits || {};
        if (!benefits.young_driver_age_from && !benefits.senior_driver_age_from && !benefits.minimum_driver_age) {
            return null;
        }
        return {
            youngFrom: benefits.young_driver_age_from,
            youngTo: benefits.young_driver_age_to,
            seniorFrom: benefits.senior_driver_age_from,
            seniorTo: benefits.senior_driver_age_to,
            minAge: benefits.minimum_driver_age,
            maxAge: benefits.maximum_driver_age,
        };
    });

    // ── Optional Extras ────────────────────────────────────────────────
    const optionalExtras = computed(() => {
        const extras = props.vehicle?.extras || [];
        const byId = new Map();
        extras
            .filter(extra => !extra.included && !extra.included_in_price && !extra.included_in_price_limited && !extra.is_one_time)
            .filter(extra => !isRenteonProtectionExtra(extra))
            .forEach(extra => {
                const key = getRenteonExtraKey(extra);
                const id = extra.id || extra.option_id || `ext_renteon_${key || (extra.service_id || extra.code)}`;
                if (!id || byId.has(id)) return;
                byId.set(id, {
                    id,
                    code: extra.code || extra.id,
                    name: extra.name || extra.description || 'Extra',
                    description: extra.description || extra.name || 'Optional Extra',
                    price: parseFloat(extra.price || extra.amount || 0) * props.numberOfDays,
                    daily_rate: parseFloat(extra.daily_rate || extra.price || extra.amount || 0),
                    total_for_booking: extra.total_price ?? extra.total_for_booking ?? extra.Total_for_this_booking ?? (parseFloat(extra.price || extra.amount || 0) * props.numberOfDays),
                    amount: extra.price || extra.amount,
                    maxQuantity: extra.max_quantity || 1,
                    numberAllowed: extra.max_quantity || 1,
                    service_id: extra.service_id || extra.id,
                    service_group: extra.service_group,
                    service_type: extra.service_type
                });
            });
        return Array.from(byId.values());
    });

    // ── All Extras (combined, deduped) ─────────────────────────────────
    const allExtras = computed(() => {
        const byId = new Map();
        [...protectionPlans.value, ...optionalExtras.value].forEach(extra => {
            if (extra && !byId.has(extra.id)) {
                byId.set(extra.id, extra);
            }
        });
        return Array.from(byId.values());
    });

    // ── Office Details ─────────────────────────────────────────────────
    const renteonPickupOffice = computed(() => props.vehicle?.pickup_office || null);
    const renteonDropoffOffice = computed(() => props.vehicle?.dropoff_office || null);

    const renteonSameOffice = computed(() => {
        const pickupId = renteonPickupOffice.value?.office_id || renteonPickupOffice.value?.office_code;
        const dropoffId = renteonDropoffOffice.value?.office_id || renteonDropoffOffice.value?.office_code;
        if (pickupId && dropoffId) return pickupId === dropoffId;
        return !renteonDropoffOffice.value;
    });

    const formatOfficeLines = (office) => {
        if (!office) return [];
        const lines = [office.address, office.town, office.postal_code]
            .map(line => `${line || ''}`.trim())
            .filter(Boolean);
        return lines;
    };

    const renteonPickupLines = computed(() => formatOfficeLines(renteonPickupOffice.value));
    const renteonDropoffLines = computed(() => formatOfficeLines(renteonDropoffOffice.value));

    const renteonPickupInstructions = computed(() => renteonPickupOffice.value?.pickup_instructions || null);
    const renteonDropoffInstructions = computed(() => renteonDropoffOffice.value?.dropoff_instructions || null);

    const renteonHasOfficeDetails = computed(() => {
        return Boolean(
            renteonPickupLines.value.length
            || renteonDropoffLines.value.length
            || renteonPickupOffice.value?.phone
            || renteonPickupOffice.value?.email
            || renteonDropoffOffice.value?.phone
            || renteonDropoffOffice.value?.email
        );
    });

    // ── Location Data ──────────────────────────────────────────────────
    const locationData = computed(() => ({
        pickupStation: renteonPickupOffice.value?.name || null,
        pickupAddress: null,
        pickupLines: renteonPickupLines.value,
        pickupPhone: renteonPickupOffice.value?.phone || null,
        pickupEmail: renteonPickupOffice.value?.email || null,
        dropoffStation: renteonDropoffOffice.value?.name || null,
        dropoffAddress: null,
        dropoffLines: renteonDropoffLines.value,
        dropoffPhone: renteonDropoffOffice.value?.phone || null,
        dropoffEmail: renteonDropoffOffice.value?.email || null,
        sameLocation: renteonSameOffice.value,
        fuelPolicy: null,
        cancellation: null,
        officeHours: null,
        pickupInstructions: renteonPickupOffice.value?.pickup_instructions || null,
        dropoffInstructions: renteonDropoffOffice.value?.dropoff_instructions || null,
    }));

    // ── Tax Breakdown ──────────────────────────────────────────────────
    const taxBreakdown = computed(() => {
        const gross = parseFloat(props.vehicle?.provider_gross_amount ?? NaN);
        const net = parseFloat(props.vehicle?.provider_net_amount ?? NaN);
        const vat = parseFloat(props.vehicle?.provider_vat_amount ?? NaN);
        return {
            gross: Number.isFinite(gross) ? gross : null,
            net: Number.isFinite(net) ? net : null,
            vat: Number.isFinite(vat) ? vat : null,
        };
    });

    const hasRenteonTaxBreakdown = computed(() => {
        const breakdown = taxBreakdown.value;
        return Boolean(breakdown?.gross || breakdown?.net || breakdown?.vat);
    });

    // ── Highlights ─────────────────────────────────────────────────────
    const highlights = computed(() => {
        const items = [];
        renteonIncludedServices.value.forEach(s => {
            items.push({ type: 'included', label: s.name + (s.quantity_included ? ` (x${s.quantity_included})` : '') });
        });
        return items;
    });

    // ── Included Items ─────────────────────────────────────────────────
    const includedItems = computed(() => {
        return renteonIncludedServices.value.map(s => ({
            label: s.name + (s.quantity_included ? ` (x${s.quantity_included})` : ''),
            detail: 'Included'
        }));
    });

    // ── Totals ─────────────────────────────────────────────────────────
    const mandatoryAmount = computed(() => 0);
    const baseTotal = computed(() => 0);

    const computeNetTotal = (extrasTotal, currentProduct = null) => {
        const selectedPackage = currentProduct || packages.value[0] || null;
        const pkgPrice = parseFloat(selectedPackage?.total || 0);
        return pkgPrice + extrasTotal;
    };

    // ── Return ─────────────────────────────────────────────────────────
    return {
        packages,
        optionalExtras,
        protectionPlans,
        allExtras,
        includedItems,
        taxBreakdown,
        baseTotal,
        mandatoryAmount,
        locationData,
        highlights,
        computeNetTotal,
        // Renteon-specific exports
        renteonIncludedServices,
        renteonDriverPolicy,
        renteonPickupOffice,
        renteonDropoffOffice,
        renteonSameOffice,
        renteonPickupLines,
        renteonDropoffLines,
        renteonPickupInstructions,
        renteonDropoffInstructions,
        renteonHasOfficeDetails,
        hasRenteonTaxBreakdown,
    };
}
