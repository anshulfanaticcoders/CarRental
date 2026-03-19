const ARAB_COUNTRY_CODES = new Set([
  'AE', 'BH', 'DZ', 'EG', 'IQ', 'JO', 'KW', 'LB', 'LY', 'MA',
  'MR', 'OM', 'PS', 'QA', 'SA', 'SD', 'SO', 'SY', 'TN', 'YE',
  'DJ', 'KM', 'TD', 'ER', 'EH'
]);

const DEFAULT_LOCALE = 'en';
const NON_ARAB_LOCALE = 'fr';

const normalizeCountryCode = (countryCode) => String(countryCode ?? '').trim().toUpperCase();

export const toCountryCodeSet = (countries) => {
  if (!Array.isArray(countries)) {
    return new Set();
  }

  return new Set(
    countries
      .map((country) => normalizeCountryCode(country?.code))
      .filter(Boolean),
  );
};

export const isArabicCountryCode = (countryCode) => {
  const normalized = normalizeCountryCode(countryCode);
  return ARAB_COUNTRY_CODES.has(normalized);
};

export const getLocaleFromCountryCode = (countryCode, knownCountryCodes = null) => {
  const normalized = normalizeCountryCode(countryCode);

  if (!normalized) {
    return DEFAULT_LOCALE;
  }

  if (ARAB_COUNTRY_CODES.has(normalized)) {
    return 'ar';
  }

  if (knownCountryCodes instanceof Set && knownCountryCodes.size > 0) {
    return knownCountryCodes.has(normalized) ? NON_ARAB_LOCALE : DEFAULT_LOCALE;
  }

  return NON_ARAB_LOCALE;
};

export const getGeoPreferredLocale = (countryCode, supportedLocales = ['en', 'fr', 'nl', 'es', 'ar'], knownCountryCodes = null) => {
  const targetLocale = getLocaleFromCountryCode(countryCode, knownCountryCodes);
  return supportedLocales.includes(targetLocale) ? targetLocale : DEFAULT_LOCALE;
};
