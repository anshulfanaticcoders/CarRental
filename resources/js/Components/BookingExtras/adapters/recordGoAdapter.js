import { computed } from 'vue';
import { useEmptyLocationData, defaultComputeNetTotal } from './shared.js';

const complementCategory = (complement) => `${complement?.complementCategory || ''}`.trim().toUpperCase();

const complementPrice = (complement) => {
    const value = parseFloat(
        complement?.priceTaxIncComplementDiscount
        ?? complement?.priceTaxIncComplement
        ?? complement?.total_for_booking
        ?? complement?.total_price
        ?? 0
    );
    return Number.isFinite(value) ? value : 0;
};

const complementDailyPrice = (complement) => {
    const value = parseFloat(
        complement?.priceTaxIncDayDiscount
        ?? complement?.priceTaxIncDay
        ?? complement?.daily_rate
        ?? 0
    );
    return Number.isFinite(value) ? value : 0;
};

const preauthValue = (complement, type) => {
    const entries = complement?.['preauth&Excess'] || complement?.preauthExcess || [];
    if (!Array.isArray(entries)) return null;
    const match = entries.find(item => `${item?.type || ''}`.toLowerCase() === type.toLowerCase());
    const value = parseFloat(match?.value ?? null);
    return Number.isFinite(value) ? value : null;
};

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @param {{ currentPackage: import('vue').Ref<string>, stripHtml: (value: string) => string }} options
 * @returns {import('./types').AdapterResult & Record<string, any>}
 */
export function createRecordGoAdapter(props, { currentPackage, stripHtml }) {
    // ── Base Total ──────────────────────────────────────────────────────
    const baseTotal = computed(() => {
        const total = parseFloat(props.vehicle?.total_price || 0);
        if (total > 0) return total;
        const daily = parseFloat(props.vehicle?.price_per_day || 0);
        return daily > 0 ? daily * props.numberOfDays : 0;
    });

    // ── Packages ────────────────────────────────────────────────────────
    const packages = computed(() => {
        const products = props.vehicle?.recordgo_products || [];
        if (!Array.isArray(products) || products.length === 0) {
            return [
                {
                    type: 'BAS',
                    name: 'Basic Rental',
                    subtitle: 'Record Go',
                    total: baseTotal.value,
                    benefits: [],
                    isBestValue: true,
                    isAddOn: false,
                }
            ];
        }

        return products.map((product, index) => {
            const benefits = [];
            if (product?.description) benefits.push(stripHtml(product.description));
            const refuel = product?.refuel_policy?.refuelPolicyTransName || product?.refuel_policy?.refuelPolicyName;
            if (refuel) benefits.push(refuel);
            const kmPolicy = product?.km_policy?.kmPolicyTransName || product?.km_policy?.kmPolicyName;
            if (kmPolicy) benefits.push(kmPolicy);
            (product?.complements_included || [])
                .filter(complement => complementCategory(complement) === 'COVERAGE')
                .forEach((complement) => {
                    const name = complement.complementName ? stripHtml(complement.complementName) : null;
                    const description = complement.complementDescription ? stripHtml(complement.complementDescription) : null;
                    const preauth = preauthValue(complement, 'Preauth');
                    const excess = preauthValue(complement, 'Excess');
                    if (name) benefits.push(name);
                    if (description) benefits.push(description);
                    if (preauth !== null) benefits.push(`Preauthorisation: ${preauth}`);
                    if (excess !== null) benefits.push(`Excess: ${excess}`);
                });

            return {
                type: product.type || `RG_${index}`,
                name: product.name || 'Record Go',
                subtitle: product.subtitle ? stripHtml(product.subtitle) : 'Record Go',
                total: product.total || 0,
                deposit: product.deposit || 0,
                excess: product.excess || 0,
                excessLow: product.excess_low || 0,
                benefits: benefits.filter(Boolean),
                isBestValue: index === 0,
                isAddOn: false,
                recordgo: product,
            };
        });
    });

    // ── Selected Product (based on currentPackage ref) ──────────────────
    const selectedPackageType = computed(() => {
        const pkgs = packages.value;
        if (!pkgs.length) return 'BAS';
        const current = currentPackage.value;
        if (pkgs.some(pkg => pkg.type === current)) return current;
        return pkgs[0].type;
    });

    const currentProduct = computed(() => {
        return packages.value.find(p => p.type === selectedPackageType.value);
    });

    const recordGoSelectedProduct = computed(() => {
        return currentProduct.value?.recordgo || null;
    });

    // ── Complements ─────────────────────────────────────────────────────
    const recordGoIncludedComplements = computed(() => {
        return recordGoSelectedProduct.value?.complements_included || [];
    });

    const recordGoAutomaticComplements = computed(() => {
        const complements = recordGoSelectedProduct.value?.complements_automatic
            || recordGoSelectedProduct.value?.complements_autom
            || [];

        if (!Array.isArray(complements)) return [];

        return complements.filter((complement) => (
            complement?.applied === true
            || complement?.is_applied === true
            || complement?.included === true
            || complement?.selected === true
        ));
    });

    const recordGoAssociatedComplements = computed(() => {
        return recordGoSelectedProduct.value?.complements_associated || [];
    });

    // ── Optional Extras ─────────────────────────────────────────────────
    const optionalExtras = computed(() => {
        // Primary: complements_associated from selected RecordGo product
        const complements = recordGoAssociatedComplements.value;
        if (complements.length > 0) {
            return complements
                .filter(extra => complementCategory(extra) !== 'COVERAGE')
                .map((extra, index) => {
                const id = extra.id || `ext_recordgo_${extra.complementId ?? index}`;
                return {
                    id,
                    code: extra.complementId,
                    name: extra.complementName ? stripHtml(extra.complementName) : 'Extra',
                    description: extra.complementDescription ? stripHtml(extra.complementDescription)
                        : (extra.complementSubtitle ? stripHtml(extra.complementSubtitle) : null),
                    required: false,
                    numberAllowed: extra.maxUnits || extra.complementUnits || null,
                    daily_rate: extra.priceTaxIncDayDiscount ?? extra.priceTaxIncDay ?? null,
                    total_for_booking: extra.priceTaxIncComplementDiscount ?? extra.priceTaxIncComplement ?? null,
                    price: extra.priceTaxIncComplementDiscount ?? extra.priceTaxIncComplement ?? null,
                    type: 'optional',
                    recordgo_payload: extra,
                };
            });
        }

        // Fallback: gateway extras (from vehicle.extras merged by adapter)
        const extras = props.vehicle?.extras || [];
        return extras.filter(e => !e.mandatory).map(extra => ({
            id: extra.id || extra.code,
            code: extra.code || extra.id,
            name: extra.name || extra.description || 'Extra',
            description: extra.description || null,
            required: false,
            numberAllowed: extra.max_quantity || extra.numberAllowed || null,
            daily_rate: parseFloat(extra.daily_rate || extra.Daily_rate || 0),
            total_for_booking: extra.total_price ?? extra.total_for_booking ?? extra.Total_for_this_booking ?? null,
            price: extra.total_price ?? extra.total_for_booking ?? null,
            type: 'optional',
        }));
    });

    const protectionPlans = computed(() => {
        return recordGoAssociatedComplements.value
            .filter(extra => complementCategory(extra) === 'COVERAGE')
            .map((extra, index) => {
                const id = extra.id || `ext_recordgo_protection_${extra.complementId ?? index}`;
                const total = complementPrice(extra);
                const daily = complementDailyPrice(extra);
                return {
                    id,
                    code: extra.complementId,
                    name: extra.complementName ? stripHtml(extra.complementName) : 'Protection',
                    description: extra.complementDescription ? stripHtml(extra.complementDescription)
                        : (extra.complementSubtitle ? stripHtml(extra.complementSubtitle) : null),
                    price: total,
                    total_for_booking: total,
                    daily_rate: daily,
                    amount: daily || total,
                    currency: props.vehicle?.currency || null,
                    required: false,
                    numberAllowed: extra.maxUnits || extra.complementUnits || null,
                    type: 'protection',
                    purpose: 'protection',
                    recordgo_payload: extra,
                };
            });
    });
    const allExtras = computed(() => [...protectionPlans.value, ...optionalExtras.value]);

    const includedItems = computed(() => {
        const items = [];
        recordGoIncludedComplements.value.forEach(c => {
            items.push({
                label: c.complementName ? stripHtml(c.complementName) : 'Included',
                detail: 'Included',
            });
        });
        return items;
    });

    const taxBreakdown = computed(() => null);
    const mandatoryAmount = computed(() => 0);
    const locationData = useEmptyLocationData();
    const highlights = computed(() => []);

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal: defaultComputeNetTotal,
        // Record Go specific exports
        recordGoIncludedComplements,
        recordGoAutomaticComplements,
        recordGoAssociatedComplements,
    };
}
