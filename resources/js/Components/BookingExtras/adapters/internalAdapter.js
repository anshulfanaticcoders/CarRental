import { computed } from 'vue';
import { getSearchVehicleLegacyPayload } from '@/features/search/utils/searchVehiclePresentation';
import { defaultComputeNetTotal } from './shared.js';

export function createInternalAdapter(props, { formatPrice }) {
  const legacyPayload = computed(() => getSearchVehicleLegacyPayload(props.vehicle));

  const packages = computed(() => {
    const packages = [];
    const vendorPlans = legacyPayload.value?.vendorPlans || legacyPayload.value?.vendor_plans || [];
    const benefits = legacyPayload.value?.benefits || {};
    const deposit = parseFloat(props.vehicle?.pricing?.deposit_amount ?? benefits?.deposit_amount ?? legacyPayload.value?.security_deposit) || 0;
    const depositCurrency = props.vehicle?.pricing?.deposit_currency ?? benefits?.deposit_currency ?? props.vehicle?.pricing?.currency ?? 'EUR';
    const excessAmount = parseFloat(props.vehicle?.pricing?.excess_amount ?? benefits?.excess_amount) || null;
    const excessTheftAmount = parseFloat(props.vehicle?.pricing?.excess_theft_amount ?? benefits?.excess_theft_amount) || null;

    packages.push({
      type: 'BAS',
      name: 'Basic Rental',
      subtitle: 'Standard Package',
      pricePerDay: parseFloat(props.vehicle?.pricing?.price_per_day) || 0,
      total: parseFloat(props.vehicle?.pricing?.total_price) || ((parseFloat(props.vehicle?.pricing?.price_per_day) || 0) * props.numberOfDays),
      deposit,
      deposit_currency: depositCurrency,
      excess: excessAmount,
      excess_theft_amount: excessTheftAmount,
      benefits: [],
      isBestValue: vendorPlans.length === 0,
      isAddOn: false
    });

    vendorPlans.forEach((plan, index) => {
      const features = plan.features ? (typeof plan.features === 'string' ? JSON.parse(plan.features) : plan.features) : [];
      packages.push({
        type: plan.plan_type || `PLAN_${index}`,
        name: plan.plan_type || 'Custom Plan',
        subtitle: plan.plan_description || 'Vendor Package',
        pricePerDay: parseFloat(plan.price) || 0,
        total: (parseFloat(plan.price) || 0) * props.numberOfDays,
        deposit,
        deposit_currency: depositCurrency,
        excess: excessAmount,
        excess_theft_amount: excessTheftAmount,
        benefits: features,
        isBestValue: index === 0,
        isAddOn: false,
        vendorPlanId: plan.id
      });
    });

    return packages;
  });

  const optionalExtras = computed(() => {
    const addons = legacyPayload.value?.addons || [];
    return addons.map(addon => ({
      id: `internal_addon_${addon.id}`,
      code: addon.addon_id?.toString() || addon.id?.toString(),
      name: addon.extra_name || 'Extra',
      description: addon.description || addon.extra_name || 'Optional Add-on',
      price: parseFloat(addon.price) * props.numberOfDays || 0,
      daily_rate: parseFloat(addon.price) || 0,
      amount: addon.price,
      maxQuantity: addon.quantity || 1
    }));
  });

  const protectionPlans = computed(() => []);
  const allExtras = computed(() => [...optionalExtras.value]);
  const mandatoryAmount = computed(() => 0);
  const baseTotal = computed(() => 0);
  const taxBreakdown = computed(() => null);

  const cancellationDeadline = computed(() => {
    const benefits = legacyPayload.value?.benefits;
    if (!benefits?.cancellation_available_per_day || !benefits?.cancellation_available_per_day_date || !props.pickupDate) {
      return null;
    }

    const daysPrior = parseInt(benefits?.cancellation_available_per_day_date);
    if (isNaN(daysPrior)) return null;

    const deadline = new Date(props.pickupDate);
    deadline.setDate(deadline.getDate() - daysPrior);

    return deadline.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
  });

  const includedItems = computed(() => {
    const items = [];
    const b = legacyPayload.value?.benefits;
    if (!b) return items;
    if (b.limited_km_per_day == 1) {
      items.push({ label: `Limited: ${b.limited_km_per_day_range} km/day`, detail: b.price_per_km_per_day ? `+${formatPrice(b.price_per_km_per_day)}/km extra` : null });
    } else {
      items.push({ label: 'Unlimited Mileage', detail: 'No daily kilometer limit' });
    }
    if (b.cancellation_available_per_day == 1) items.push({ label: 'Free Cancellation', detail: cancellationDeadline.value ? `Until ${cancellationDeadline.value}` : null });
    if (b.minimum_driver_age) items.push({ label: `Min. Driver Age: ${b.minimum_driver_age}`, detail: null });
    if (b.maximum_driver_age) items.push({ label: `Max. Driver Age: ${b.maximum_driver_age}`, detail: null });
    if (b.deposit_amount) items.push({ label: `Deposit: ${formatPrice(b.deposit_amount)}`, detail: b.deposit_currency ? `Currency: ${b.deposit_currency}` : null });
    if (b.excess_amount) items.push({ label: `Excess: ${formatPrice(b.excess_amount)}`, detail: null });
    if (b.excess_theft_amount) items.push({ label: `Theft Excess: ${formatPrice(b.excess_theft_amount)}`, detail: null });
    return items;
  });

  const locationData = computed(() => {
    const v = props.vehicle;
    const rawLocation = typeof v?.location === 'string' ? v.location : null;
    const pickupName = v?.location?.pickup?.name || null;
    const address = v?.full_vehicle_address || pickupName || rawLocation || legacyPayload.value?.location || null;
    const vendorProfile = legacyPayload.value?.vendorProfileData || legacyPayload.value?.vendor_profile_data || legacyPayload.value?.vendorProfile || {};
    return {
      pickupStation: address,
      pickupAddress: [v?.city || vendorProfile.city, v?.state, v?.country || vendorProfile.country].filter(Boolean).join(', ') || null,
      pickupLines: [],
      pickupPhone: vendorProfile.phone || vendorProfile.company_phone || null,
      pickupEmail: vendorProfile.email || vendorProfile.company_email || null,
      dropoffStation: null, dropoffAddress: null, dropoffLines: [],
      dropoffPhone: null, dropoffEmail: null,
      sameLocation: true, fuelPolicy: null, cancellation: null,
      officeHours: null, pickupInstructions: null, dropoffInstructions: null,
    };
  });
  const highlights = computed(() => []);

  const hasProviderNotes = computed(() => {
    return !!(legacyPayload.value?.vendor?.profile || legacyPayload.value?.vendorProfile || legacyPayload.value?.vendor_profile || legacyPayload.value?.guidelines || legacyPayload.value?.terms_policy);
  });

  return {
    packages, optionalExtras, protectionPlans, allExtras,
    includedItems, taxBreakdown, baseTotal, mandatoryAmount,
    locationData, highlights, computeNetTotal: defaultComputeNetTotal,
    cancellationDeadline, hasProviderNotes,
  };
}
