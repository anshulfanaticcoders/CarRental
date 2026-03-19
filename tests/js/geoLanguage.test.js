import test from 'node:test';
import assert from 'node:assert/strict';
import countries from '../../public/countries.json' with { type: 'json' };
import { getGeoPreferredLocale, getLocaleFromCountryCode, toCountryCodeSet } from '../../resources/js/utils/geoLanguage.js';

const knownCountryCodes = toCountryCodeSet(countries);

test('maps arab countries to ar locale', () => {
  assert.equal(getLocaleFromCountryCode('AE'), 'ar');
  assert.equal(getLocaleFromCountryCode('sa'), 'ar');
  assert.equal(getLocaleFromCountryCode('EG'), 'ar');
});

test('maps known non-arab countries to fr locale', () => {
  assert.equal(getLocaleFromCountryCode('DE', knownCountryCodes), 'fr');
  assert.equal(getLocaleFromCountryCode('FR', knownCountryCodes), 'fr');
  assert.equal(getLocaleFromCountryCode('NL', knownCountryCodes), 'fr');
  assert.equal(getLocaleFromCountryCode('IN', knownCountryCodes), 'fr');
});

test('uses countries.json as authoritative country code source', () => {
  assert.ok(knownCountryCodes.has('DE'));
  assert.ok(knownCountryCodes.has('AE'));
  assert.equal(getLocaleFromCountryCode('DE', knownCountryCodes), 'fr');
});

test('defaults to en for unknown/empty country', () => {
  assert.equal(getLocaleFromCountryCode(''), 'en');
  assert.equal(getLocaleFromCountryCode(null), 'en');
  assert.equal(getLocaleFromCountryCode('ZZ', knownCountryCodes), 'en');
});

test('geo preferred locale respects supported locales', () => {
  assert.equal(getGeoPreferredLocale('DE', ['en', 'fr', 'ar'], knownCountryCodes), 'fr');
  assert.equal(getGeoPreferredLocale('AE', ['en', 'fr', 'ar'], knownCountryCodes), 'ar');
  assert.equal(getGeoPreferredLocale('DE', ['en', 'ar'], knownCountryCodes), 'en');
});
