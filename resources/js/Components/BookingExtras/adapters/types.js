/**
 * Adapter interface type definitions for BookingExtras provider adapters.
 *
 * Every provider adapter (internal, gateway, etc.) must return an object
 * matching the {@link AdapterResult} shape so the UI layer stays
 * provider-agnostic.
 */

/**
 * @typedef {Object} Package
 * @property {string}  id
 * @property {string}  name
 * @property {string}  [description]
 * @property {number}  price        - Total price in customer currency
 * @property {string}  [currency]
 * @property {Extra[]} extras       - Extras bundled in this package
 * @property {boolean} [recommended]
 */

/**
 * @typedef {Object} Extra
 * @property {string}  id
 * @property {string}  name
 * @property {string}  [description]
 * @property {number}  price         - Per-unit price in customer currency
 * @property {number}  [maxQuantity]
 * @property {string}  [currency]
 * @property {string}  [type]        - e.g. "insurance", "equipment", "service"
 * @property {boolean} [mandatory]
 * @property {boolean} [includedInPackage]
 * @property {string}  [icon]
 */

/**
 * @typedef {Object} Item
 * @property {string} name
 * @property {string} [description]
 * @property {string} [icon]
 */

/**
 * @typedef {Object} Highlight
 * @property {string} label
 * @property {string} value
 * @property {string} [icon]
 * @property {string} [type]  - e.g. "positive", "neutral", "warning"
 */

/**
 * @typedef {Object} LocationData
 * @property {string}  [pickupStation]
 * @property {string}  [pickupAddress]
 * @property {string[]} [pickupLines]
 * @property {string}  [pickupPhone]
 * @property {string}  [pickupEmail]
 * @property {string}  [dropoffStation]
 * @property {string}  [dropoffAddress]
 * @property {string[]} [dropoffLines]
 * @property {string}  [dropoffPhone]
 * @property {string}  [dropoffEmail]
 * @property {boolean} [sameLocation]
 * @property {string}  [fuelPolicy]
 * @property {string}  [cancellation]
 * @property {Object}  [officeHours]
 * @property {string}  [pickupInstructions]
 * @property {string}  [dropoffInstructions]
 */

/**
 * @typedef {Object} TaxBreakdown
 * @property {number} [taxRate]
 * @property {number} [taxAmount]
 * @property {string} [taxLabel]
 * @property {Array<{label: string, amount: number}>} [items]
 */

/**
 * The contract every provider adapter must fulfill.
 *
 * All reactive properties are Vue `ComputedRef` values so the UI
 * re-renders automatically when the underlying data changes.
 *
 * @typedef {Object} AdapterResult
 * @property {import('vue').ComputedRef<Package[]>}          packages
 * @property {import('vue').ComputedRef<Extra[]>}            optionalExtras
 * @property {import('vue').ComputedRef<Extra[]>}            protectionPlans
 * @property {import('vue').ComputedRef<Extra[]>}            allExtras         - Combined list for lookup
 * @property {import('vue').ComputedRef<Item[]>}             includedItems
 * @property {import('vue').ComputedRef<TaxBreakdown|null>}  taxBreakdown
 * @property {import('vue').ComputedRef<number>}             baseTotal
 * @property {import('vue').ComputedRef<number>}             mandatoryAmount
 * @property {import('vue').ComputedRef<LocationData>}       locationData
 * @property {import('vue').ComputedRef<Highlight[]>}        highlights
 * @property {(extrasTotal: number) => number}               computeNetTotal
 */

export {};
