/**
 * useBookingData Composable
 *
 * Normalizes booking data from all providers (internal + 11 external providers)
 * into a unified format for consistent display across the application.
 *
 * Providers Supported:
 * - Internal (vendor vehicles)
 * - GreenMotion, USave
 * - RecordGo
 * - Ok Mobility
 * - Renteon
 * - SicilyByCar
 * - LocautoRent
 * - Adobe
 * - Favrica
 * - XDrive
 * - Wheelsys
 */

import { computed } from 'vue';

export function useBookingData(booking, vehicle, payment) {
  // Provider metadata
  const providerMetadata = computed(() => booking?.provider_metadata || {});
  const providerSource = computed(() => {
    const source = booking?.provider_source || '';
    return source.toLowerCase().replace(/[_\s]/g, '');
  });

  const isInternalProvider = computed(() => {
    const source = providerSource.value;
    return !source || source === 'internal' || !!booking?.vehicle_id;
  });

  const isGreenMotionBased = computed(() => {
    return ['greenmotion', 'usave', 'green motion', 'u-save'].includes(providerSource.value);
  });

  const isRenteon = computed(() => providerSource.value === 'renteon');
  const isRecordGo = computed(() => providerSource.value === 'recordgo' || providerSource.value === 'record_go');
  const isOkMobility = computed(() => ['okmobility', 'ok mobility'].includes(providerSource.value));
  const isSicilyByCar = computed(() => ['sicilybycar', 'sicily_by_car', 'sicily'].includes(providerSource.value));
  const isLocautoRent = computed(() => ['locautorent', 'locauto_rent', 'locauto'].includes(providerSource.value));
  const isAdobe = computed(() => ['adobe', 'adobecar', 'adobe_car'].includes(providerSource.value));
  const isFavrica = computed(() => ['favrica'].includes(providerSource.value));
  const isXDrive = computed(() => ['xdrive', 'x_drive'].includes(providerSource.value));
  const isWheelsys = computed(() => ['wheelsys', 'wheelsys'].includes(providerSource.value));

  // Location details - normalized
  const pickupLocation = computed(() => {
    const meta = providerMetadata.value;
    const details = meta?.pickup_location_details || meta?.location || meta?.pickup_office || {};
    return normalizeLocationData(details, meta, 'pickup');
  });

  const dropoffLocation = computed(() => {
    const meta = providerMetadata.value;
    const details = meta?.dropoff_location_details || meta?.dropoff_office || {};
    return normalizeLocationData(details, meta, 'dropoff');
  });

  function normalizeLocationData(details, meta, type) {
    const result = {
      name: details?.name || details?.location_name || meta?.[`${type}_location_name`] || null,
      address: formatAddress(details),
      city: details?.address_city || details?.town || details?.city || null,
      postalCode: details?.address_postcode || details?.postal_code || null,
      country: details?.country || details?.address_country || null,
      latitude: details?.latitude || meta?.latitude || null,
      longitude: details?.longitude || meta?.longitude || null,
      phone: details?.telephone || details?.phone || meta?.[`${type}_phone`] || null,
      email: details?.email || meta?.[`${type}_email`] || null,
      whatsapp: details?.whatsapp || null,
      iata: details?.iata_code || details?.iata || null,
      openingHours: details?.opening_hours || details?.office_opening_hours || [],
      outOfHoursInfo: details?.out_of_hours_charge || details?.out_of_hours || null,
      pickupInstructions: details?.collection_details || details?.pickup_instructions || meta?.pickup_instructions || null,
      dropoffInstructions: details?.dropoff_instructions || details?.return_instructions || null,
    };
    return result;
  }

  function formatAddress(details) {
    if (!details) return null;
    const parts = [
      details.address_1 || details.address,
      details.address_2,
      details.address_3,
      details.address_city || details.town || details.city,
      details.address_county || details.county,
      details.address_postcode || details.postal_code,
    ].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : null;
  }

  // Pricing breakdown - normalized
  const pricingBreakdown = computed(() => {
    const meta = providerMetadata.value;
    const amounts = booking?.amounts; // booking_amounts relation

    // If booking_amounts exists, use it (most accurate)
    if (amounts) {
      const currency = amounts.booking_currency || 'EUR';
      const bookingTotal = parseFloat(amounts.booking_total_amount || 0);
      const bookingPaid = parseFloat(amounts.booking_paid_amount || 0);
      const bookingPending = parseFloat(amounts.booking_pending_amount || 0);
      const paymentPercentage = bookingTotal > 0 ? (bookingPaid / bookingTotal) * 100 : 0;

      // Get vendor pricing from amounts or fallback to metadata
      const vendorVehicleTotal = amounts.vendor_total_amount && amounts.vendor_extra_amount
        ? parseFloat(amounts.vendor_total_amount) - parseFloat(amounts.vendor_extra_amount)
        : (parseFloat(amounts.vendor_total_amount) ||
           meta?.provider_pricing?.vehicle_total ||
           meta?.provider_vehicle_total ||
           0);

      const vendorExtrasTotal = parseFloat(amounts.vendor_extra_amount || meta?.provider_pricing?.extras_total || meta?.provider_extras_total || 0);
      const vendorGrandTotal = parseFloat(amounts.vendor_total_amount || meta?.provider_pricing?.grand_total || meta?.provider_grand_total || 0);

      // Get deposit/excess from metadata (check benefits, top-level, and provider_pricing)
      const ben = meta?.benefits || {};
      const deposit = ben?.deposit_amount || meta?.deposit_amount || meta?.provider_pricing?.deposit_amount || meta?.deposit || meta?.Deposit || null;
      const excess = ben?.excess_amount || meta?.excess_amount || meta?.provider_pricing?.excess_amount || meta?.excess || meta?.Excess || null;
      const excessTheft = ben?.excess_theft_amount || meta?.excess_theft_amount || meta?.provider_pricing?.excess_theft_amount || null;
      const depositCurrency = ben?.deposit_currency || meta?.deposit_currency || meta?.provider_pricing?.deposit_currency || amounts.vendor_currency || meta?.currency || currency;

      return {
        currency,
        // Provider (vendor) pricing - what vendor receives
        provider: {
          currency: amounts.vendor_currency || meta?.currency || currency,
          vehicleTotal: vendorVehicleTotal,
          extrasTotal: vendorExtrasTotal,
          grandTotal: vendorGrandTotal,
        },
        // Customer pricing - what customer sees/pays
        booking: {
          vehicleTotal: parseFloat(booking?.base_price || 0) || (bookingTotal - parseFloat(amounts.booking_extra_amount || 0)),
          extrasTotal: parseFloat(amounts.booking_extra_amount || booking?.extra_charges || 0),
          taxTotal: parseFloat(booking?.tax_amount || 0),
          discountTotal: parseFloat(booking?.discount_amount || 0),
          grandTotal: bookingTotal,
        },
        // Payment breakdown
        payment: {
          paidNow: bookingPaid,
          dueOnArrival: bookingPending,
          paidPercentage: Math.round(paymentPercentage),
          duePercentage: Math.round(100 - paymentPercentage),
          isPOA: paymentPercentage < 100 && paymentPercentage > 0,
        },
        // Deposit & Excess (in vendor's currency)
        deposit: deposit ? { amount: parseFloat(deposit), currency: depositCurrency } : null,
        excess: excess ? { amount: parseFloat(excess), currency: depositCurrency } : null,
        excessTheft: excessTheft ? { amount: parseFloat(excessTheft), currency: depositCurrency } : null,
        // Exchange rate info if multi-currency
        exchangeRate: amounts.booking_to_vendor_rate || meta?.exchange_rates?.provider_to_booking || null,
      };
    }

    // Fallback: use provider_metadata and booking table
    const currency = booking?.booking_currency || 'EUR';

    // Provider pricing from metadata
    const providerPricing = meta?.provider_pricing || {};
    const providerVehicleTotal = parseFloat(
      providerPricing?.vehicle_total ||
      meta?.provider_vehicle_total ||
      meta?.vehicle_total ||
      booking?.provider_grand_total - (meta?.extras_total || 0) ||
      booking?.base_price ||
      0
    );

    const providerExtrasTotal = parseFloat(
      providerPricing?.extras_total ||
      meta?.provider_extras_total ||
      meta?.extras_total ||
      booking?.extra_charges ||
      0
    );

    const providerGrandTotal = parseFloat(
      providerPricing?.grand_total ||
      meta?.provider_grand_total ||
      meta?.grand_total ||
      booking?.provider_grand_total ||
      booking?.base_price + booking?.extra_charges ||
      0
    );

    // Customer pricing from metadata or booking
    const customerPricing = meta?.customer_pricing || {};
    const bookingVehicleTotal = parseFloat(customerPricing?.vehicle_total || booking?.base_price || 0);
    const bookingExtrasTotal = parseFloat(customerPricing?.extras_total || booking?.extra_charges || 0);
    const bookingTaxTotal = parseFloat(booking?.tax_amount || 0);
    const bookingDiscountTotal = parseFloat(booking?.discount_amount || 0);
    const bookingGrandTotal = parseFloat(customerPricing?.grand_total || booking?.total_amount || 0);

    // Get deposit/excess from provider metadata (check benefits, top-level, and provider_pricing)
    const ben = meta?.benefits || {};
    const deposit = ben?.deposit_amount || meta?.deposit_amount || meta?.provider_pricing?.deposit_amount || meta?.deposit || meta?.Deposit || null;
    const excess = ben?.excess_amount || meta?.excess_amount || meta?.provider_pricing?.excess_amount || meta?.excess || meta?.Excess || null;
    const excessTheft = ben?.excess_theft_amount || meta?.excess_theft_amount || meta?.provider_pricing?.excess_theft_amount || null;
    const depositCurrency = ben?.deposit_currency || meta?.deposit_currency || meta?.provider_pricing?.deposit_currency || meta?.currency || currency;

    // Get payment info
    const amountPaid = parseFloat(booking?.amount_paid || 0);
    const pendingAmount = parseFloat(booking?.pending_amount || 0);
    const paymentPercentage = bookingGrandTotal > 0 ? (amountPaid / bookingGrandTotal) * 100 : 0;

    return {
      currency,
      // Provider (vendor) pricing - what vendor receives
      provider: {
        currency: meta?.currency || meta?.provider_pricing?.currency || currency,
        vehicleTotal: providerVehicleTotal,
        extrasTotal: providerExtrasTotal,
        grandTotal: providerGrandTotal,
      },
      // Customer pricing - what customer sees/pays
      booking: {
        vehicleTotal: bookingVehicleTotal,
        extrasTotal: bookingExtrasTotal,
        taxTotal: bookingTaxTotal,
        discountTotal: bookingDiscountTotal,
        grandTotal: bookingGrandTotal,
      },
      // Payment breakdown
      payment: {
        paidNow: amountPaid,
        dueOnArrival: pendingAmount,
        paidPercentage: Math.round(paymentPercentage),
        duePercentage: Math.round(100 - paymentPercentage),
        isPOA: paymentPercentage < 100 && paymentPercentage > 0,
      },
      // Deposit & Excess (in vendor's currency)
      deposit: deposit ? { amount: parseFloat(deposit), currency: depositCurrency } : null,
      excess: excess ? { amount: parseFloat(excess), currency: depositCurrency } : null,
      excessTheft: excessTheft ? { amount: parseFloat(excessTheft), currency: depositCurrency } : null,
      // Exchange rate info if multi-currency
      exchangeRate: meta?.exchange_rates?.provider_to_booking || null,
    };
  });

  // Extras - normalized
  const normalizedExtras = computed(() => {
    // First try booking extras
    const bookingExtras = booking?.extras || [];
    if (bookingExtras.length > 0) {
      return bookingExtras.map(extra => ({
        id: extra.id,
        name: extra.extra_name,
        type: extra.extra_type || 'extra',
        quantity: extra.quantity,
        price: parseFloat(extra.price || 0),
        total: parseFloat(extra.price || 0) * (extra.quantity || 1),
        included: false,
      }));
    }

    // Then try provider metadata extras
    const meta = providerMetadata.value;
    const metaExtras = meta?.extras_selected || meta?.extras || meta?.Services || [];
    if (Array.isArray(metaExtras) && metaExtras.length > 0) {
      return metaExtras.map(extra => {
        const name = extra.name || extra.Name || extra.extra_name || extra.service_name || 'Extra';
        const qty = extra.qty || extra.quantity || extra.Quantity || 1;
        const price = parseFloat(extra.price || extra.Price || extra.total || extra.Total || 0);
        return {
          id: extra.id || extra.code || extra.optionID,
          name,
          type: extra.type || extra.extra_type || 'extra',
          quantity: qty,
          price,
          total: price * qty,
          included: extra.isFree || extra.included || extra.is_included || false,
        };
      });
    }

    return [];
  });

  // Policies - normalized
  const policies = computed(() => {
    const meta = providerMetadata.value;
    const benefits = meta?.benefits || meta?.benefit || {};

    // Mileage: check limited_km fields, unlimited_mileage, included_km, and general mileage field
    let mileageText = 'Unlimited';
    if (benefits?.limited_km_per_day == 1 || benefits?.limited_km_per_day === true) {
      const range = benefits?.limited_km_per_day_range;
      const pricePerKm = benefits?.price_per_km_per_day;
      mileageText = range ? `${range} km/day` : 'Limited';
      if (pricePerKm) mileageText += ` (+${pricePerKm}/km)`;
    } else if (benefits?.unlimited_mileage === false || benefits?.unlimited_mileage === 0) {
      mileageText = benefits?.included_km ? `${benefits.included_km} km included` : 'Limited';
    } else if (benefits?.mileage && benefits.mileage !== 'Unlimited') {
      mileageText = benefits.mileage;
    }

    // Cancellation: check cancellation_available_per_day + deadline
    let cancellationText = 'Non-refundable';
    if (benefits?.cancellation_available_per_day == 1 || benefits?.cancellation_available_per_day === true) {
      const days = benefits?.cancellation_available_per_day_date;
      cancellationText = days ? `Free cancellation (${days} days before)` : 'Free cancellation';
    }

    return {
      fuelPolicy: benefits?.fuel_policy || meta?.fuel_policy || meta?.fuelpolicy || null,
      mileage: mileageText,
      minimumDriverAge: benefits?.minimum_driver_age || meta?.min_age || meta?.minDriverAge || null,
      maximumDriverAge: benefits?.maximum_driver_age || meta?.max_age || null,
      youngDriverAge: benefits?.young_driver_age_from || null,
      seniorDriverAge: benefits?.senior_driver_age_from || null,
      cancellation: cancellationText,
      deposit: pricingBreakdown.value.deposit,
      excess: pricingBreakdown.value.excess,
      excessTheft: pricingBreakdown.value.excessTheft || null,
      securityDeposit: benefits?.security_deposit || null,
      depositPaymentMethod: benefits?.deposit_payment_method || null,
      selectedDepositType: benefits?.selected_deposit_type || null,
      // Provider-specific policies
      debitCardAccepted: meta?.debitcard === 'true' || meta?.debitCardAccepted || false,
      creditCardRequired: !meta?.debitcard || meta?.debitcard === 'false',
      transmission: benefits?.transmission || null,
    };
  });

  // Vehicle specs - normalized
  const vehicleSpecs = computed(() => {
    const meta = providerMetadata.value;

    return {
      brand: vehicle?.brand || meta?.brand || booking?.vehicle_name?.split(' ')[0] || 'Vehicle',
      model: vehicle?.model || meta?.model || booking?.vehicle_name || '',
      category: vehicle?.category?.name || meta?.category || meta?.CarCategory || 'Standard',
      sippCode: vehicle?.sipp_code || meta?.sipp_code || meta?.sipp || meta?.acriss_code || '',
      transmission: vehicle?.transmission || meta?.transmission || 'Manual',
      fuel: vehicle?.fuel || meta?.fuel || 'Petrol',
      seats: vehicle?.seating_capacity || meta?.seats || meta?.PassengerCapacity || 5,
      doors: vehicle?.doors || meta?.doors || meta?.NumberOfDoors || 4,
      luggage: {
        small: vehicle?.luggageSmall || vehicle?.bags || meta?.SmallBagsCapacity || 1,
        large: vehicle?.luggageLarge || vehicle?.suitcases || meta?.BigBagsCapacity || 1,
      },
      airConditioning: vehicle?.airConditioning !== false,
      image: vehicle?.images?.find(img => img.image_type === 'primary')?.image_url ||
              vehicle?.images?.[0]?.image_url ||
              booking?.vehicle_image ||
              meta?.image ||
              '',
    };
  });

  // Provider contact info
  const providerContact = computed(() => {
    const meta = providerMetadata.value;
    const pickup = pickupLocation.value;

    return {
      name: getProviderDisplayName(providerSource.value),
      logo: meta?.provider_logo || null,
      supportEmail: meta?.support_email || pickup?.email || null,
      supportPhone: meta?.support_phone || pickup?.phone || null,
      website: getProviderWebsite(providerSource.value),
      pickupLocation: pickup,
      dropoffLocation: dropoffLocation.value,
    };
  });

  // Booking reference numbers
  const bookingReferences = computed(() => {
    return {
      bookingNumber: booking?.booking_number || '',
      bookingReference: booking?.booking_reference || '',
      providerReference: booking?.provider_booking_ref || providerMetadata.value?.booking_ref || '',
      stripeSessionId: booking?.stripe_session_id || '',
      transactionId: payment?.transaction_id || booking?.stripe_payment_intent_id || '',
    };
  });

  // Payment type label
  const paymentTypeLabel = computed(() => {
    if (pricingBreakdown.value.payment.isPOA) {
      return isGreenMotionBased.value ? 'Deposit paid' : 'Pay 15% now, rest on arrival';
    }
    return 'Paid in full';
  });

  // Helper function for provider display names
  function getProviderDisplayName(source) {
    const names = {
      greenmotion: 'Green Motion',
      usave: 'U-Save',
      recordgo: 'Record Go',
      okmobility: 'OK Mobility',
      renteon: 'Renteon',
      sicilybycar: 'Sicily by Car',
      locautorent: 'Locauto Rent',
      adobe: 'Adobe Car Rental',
      favrica: 'Favrica',
      xdrive: 'XDrive',
      wheelsys: 'Wheelsys',
      internal: 'Vrooem',
    };
    return names[source] || source.charAt(0).toUpperCase() + source.slice(1);
  }

  // Helper function for provider websites
  function getProviderWebsite(source) {
    const sites = {
      greenmotion: 'https://www.greenmotion.com',
      usave: 'https://www.usave.com',
      recordgo: 'https://www.recordgo.com',
      okmobility: 'https://www.okmobility.com',
      renteon: 'https://www.renteon.com',
      sicilybycar: 'https://www.sicilybycar.it',
      locautorent: 'https://www.locautorent.com',
      adobe: 'https://www.adobecar.com',
      wheelsys: 'https://www.wheelsys.com',
    };
    return sites[source] || null;
  }

  // Format currency
  const formatCurrency = (amount, currency = null) => {
    const curr = currency || booking?.booking_currency || 'EUR';
    const symbols = {
      USD: '$', EUR: '€', GBP: '£', JPY: '¥',
      AUD: 'A$', CAD: 'C$', CHF: 'Fr', HKD: 'HK$',
      SGD: 'S$', SEK: 'kr', NOK: 'kr', NZD: 'NZ$',
      INR: '₹', MXN: 'Mex$', ZAR: 'R', AED: 'AED',
      MAD: 'د.م.', TRY: '₺'
    };
    const symbol = symbols[curr] || '€';
    const formattedAmount = new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount || 0);
    return `${symbol}${formattedAmount}`;
  };

  // Format date
  const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  // Format time
  const formatTime = (timeStr) => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${minutes || '00'} ${ampm}`;
  };

  // Computed rental period
  const rentalPeriod = computed(() => {
    const days = booking?.total_days || 0;
    if (days % 7 === 0 && days > 0) {
      return `${days / 7} ${days / 7 === 1 ? 'week' : 'weeks'}`;
    }
    return `${days} ${days === 1 ? 'day' : 'days'}`;
  });

  return {
    // Provider info
    providerSource,
    isInternalProvider,
    isGreenMotionBased,
    isRenteon,
    isRecordGo,
    isOkMobility,
    isSicilyByCar,
    isLocautoRent,
    isAdobe,
    isFavrica,
    isXDrive,
    isWheelsys,

    // Location
    pickupLocation,
    dropoffLocation,

    // Pricing
    pricingBreakdown,

    // Extras
    normalizedExtras,

    // Policies
    policies,

    // Vehicle
    vehicleSpecs,

    // Contact
    providerContact,

    // References
    bookingReferences,

    // Labels
    paymentTypeLabel,
    rentalPeriod,

    // Formatters
    formatCurrency,
    formatDate,
    formatTime,
  };
}
