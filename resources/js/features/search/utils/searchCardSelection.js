export const buildSearchCardSelection = ({
    vehicle,
    selectedPackage = 'BAS',
    selectedProtection = null,
    selectedInternalPlan = null,
    baseTotal = 0,
} = {}) => {
    const source = `${vehicle?.source ?? ''}`.trim().toLowerCase();
    const providerProducts = Array.isArray(vehicle?.products) ? vehicle.products : [];
    const selectedProviderProduct = providerProducts.find((product) => product?.type === selectedPackage);
    const fallbackProviderPackage = selectedProviderProduct?.type
        || providerProducts.find((product) => `${product?.type ?? ''}`.trim() !== '')?.type
        || 'BAS';

    if (source === 'locauto_rent') {
        return {
            vehicle,
            package: selectedProtection ? 'POA' : 'BAS',
            protection_code: selectedProtection?.code ?? null,
            protection_amount: selectedProtection?.amount ?? 0,
        };
    }

    if (source === 'adobe') {
        const protectionAmount = selectedProtection?.amount ?? 0;
        return {
            vehicle,
            package: selectedProtection?.code ?? 'BAS',
            protection_code: selectedProtection?.code ?? null,
            protection_amount: protectionAmount,
            total_price: baseTotal + protectionAmount,
        };
    }

    if (source === 'internal') {
        return {
            vehicle,
            package: selectedInternalPlan?.plan_type ?? 'BAS',
            protection_code: selectedInternalPlan?.id != null ? `${selectedInternalPlan.id}` : null,
            protection_amount: selectedInternalPlan?.price ?? 0,
            vendor_plan_id: selectedInternalPlan?.id ?? null,
        };
    }

    if (source === 'greenmotion' || source === 'usave') {
        return {
            vehicle,
            package: selectedPackage,
        };
    }

    return {
        vehicle,
        package: fallbackProviderPackage,
    };
};
