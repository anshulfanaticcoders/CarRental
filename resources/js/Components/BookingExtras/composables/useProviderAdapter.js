import { computed } from 'vue';
import { createAdobeAdapter } from '../adapters/adobeAdapter';
import { createLocautoAdapter } from '../adapters/locautoAdapter';
import { createInternalAdapter } from '../adapters/internalAdapter';
import { createRenteonAdapter } from '../adapters/renteonAdapter';
import { createOkMobilityAdapter } from '../adapters/okmobilityAdapter';
import { createSicilyByCarAdapter } from '../adapters/sicilyByCarAdapter';
import { createRecordGoAdapter } from '../adapters/recordGoAdapter';
import { createSurpriceAdapter } from '../adapters/surpriceAdapter';
import { createFavricaXdriveAdapter } from '../adapters/favricaXdriveAdapter';
import { createGreenMotionAdapter } from '../adapters/greenMotionAdapter';
import { createDefaultAdapter } from '../adapters/defaultAdapter';
import { createClick2RentAdapter } from '../adapters/click2rentAdapter';

/**
 * Factory composable that resolves the correct provider adapter based on vehicle.source.
 *
 * @param {Object} props - Component props (vehicle, numberOfDays, optionalExtras, etc.)
 * @param {Object} deps  - Dependencies injected from parent ({ formatPrice, currentPackage, stripHtml })
 * @returns {Object} The resolved adapter instance
 */
export function useProviderAdapter(props, deps = {}) {
    const source = computed(() => (props.vehicle?.source || '').toString().toLowerCase());

    const sourceChecks = {
        isAdobeCars: computed(() => source.value === 'adobe'),
        isLocautoRent: computed(() => source.value === 'locauto_rent'),
        isInternal: computed(() => source.value === 'internal'),
        isRenteon: computed(() => source.value === 'renteon'),
        isOkMobility: computed(() => source.value === 'okmobility'),
        isSicilyByCar: computed(() => source.value === 'sicily_by_car'),
        isRecordGo: computed(() => source.value === 'recordgo'),
        isSurprice: computed(() => source.value === 'surprice'),
        isFavrica: computed(() => source.value === 'favrica'),
        isXDrive: computed(() => source.value === 'xdrive'),
        isEmr: computed(() => source.value === 'emr'),
        isGreenMotion: computed(() => source.value === 'greenmotion' || source.value === 'usave'),
        isClick2Rent: computed(() => source.value === 'click2rent'),
    };

    // Create all adapters lazily — only the active one is used
    const adapters = {
        adobe: () => createAdobeAdapter(props),
        locauto_rent: () => createLocautoAdapter(props),
        internal: () => createInternalAdapter(props, deps),
        renteon: () => createRenteonAdapter(props),
        okmobility: () => createOkMobilityAdapter(props),
        sicily_by_car: () => createSicilyByCarAdapter(props),
        recordgo: () => createRecordGoAdapter(props, deps),
        surprice: () => createSurpriceAdapter(props, deps),
        favrica: () => createFavricaXdriveAdapter(props, 'favrica'),
        xdrive: () => createFavricaXdriveAdapter(props, 'xdrive'),
        emr: () => createFavricaXdriveAdapter(props, 'emr'),
        greenmotion: () => createGreenMotionAdapter(props),
        usave: () => createGreenMotionAdapter(props),
        click2rent: () => createClick2RentAdapter(props),
    };

    // Eagerly resolve the adapter for the current source
    // This is safe because source doesn't change during component lifetime
    const resolvedSource = source.value;
    const factory = adapters[resolvedSource];
    const adapter = factory ? factory() : createDefaultAdapter(props);

    return {
        adapter,
        source,
        ...sourceChecks,
    };
}
