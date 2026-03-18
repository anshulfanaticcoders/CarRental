const ARAB_COUNTRY_CODES = new Set([
  'AE', 'BH', 'DZ', 'EG', 'IQ', 'JO', 'KW', 'LB', 'LY', 'MR',
  'OM', 'PS', 'QA', 'SA', 'SD', 'SO', 'SY', 'TN', 'YE', 'DJ',
  'KM', 'TD', 'ER', 'EH'
]);

const FRENCH_COUNTRY_CODES = new Set([
  'FR', 'BE', 'LU', 'MC'
]);

const DUTCH_COUNTRY_CODES = new Set([
  'NL', 'SR', 'AW', 'CW', 'SX'
]);

const SPANISH_COUNTRY_CODES = new Set([
  'ES', 'MX', 'AR', 'CO', 'CL', 'PE', 'VE', 'UY', 'PY', 'BO',
  'EC', 'GT', 'HN', 'SV', 'NI', 'CR', 'PA', 'DO', 'CU', 'PR'
]);

const DEFAULT_LOCALE = 'en';

const normalizeCountryCode = (countryCode) => String(countryCode ?? '').trim().toUpperCase();

export const isArabicCountryCode = (countryCode) => {
  const normalized = normalizeCountryCode(countryCode);
  return ARAB_COUNTRY_CODES.has(normalized);
};

export const getLocaleFromCountryCode = (countryCode) => {
  const normalized = normalizeCountryCode(countryCode);

  if (!normalized) {
    return DEFAULT_LOCALE;
  }

  if (ARAB_COUNTRY_CODES.has(normalized)) {
    return 'ar';
  }

  if (FRENCH_COUNTRY_CODES.has(normalized)) {
    return 'fr';
  }

  if (DUTCH_COUNTRY_CODES.has(normalized)) {
    return 'nl';
  }

  if (SPANISH_COUNTRY_CODES.has(normalized)) {
    return 'es';
  }

  return DEFAULT_LOCALE;
};

export const getGeoPreferredLocale = (countryCode, supportedLocales = ['en', 'fr', 'nl', 'es', 'ar']) => {
  const targetLocale = getLocaleFromCountryCode(countryCode);
  return supportedLocales.includes(targetLocale) ? targetLocale : DEFAULT_LOCALE;
};
