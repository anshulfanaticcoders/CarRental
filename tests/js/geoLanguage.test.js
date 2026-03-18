import test from 'node:test';
import assert from 'node:assert/strict';
import { getGeoPreferredLocale, getLocaleFromCountryCode } from '../../resources/js/utils/geoLanguage.js';

test('maps arab countries to ar locale', () => {
  assert.equal(getLocaleFromCountryCode('AE'), 'ar');
  assert.equal(getLocaleFromCountryCode('sa'), 'ar');
  assert.equal(getLocaleFromCountryCode('EG'), 'ar');
});

test('maps french countries to fr locale', () => {
  assert.equal(getLocaleFromCountryCode('FR'), 'fr');
  assert.equal(getLocaleFromCountryCode('BE'), 'fr');
});

test('maps dutch countries to nl locale', () => {
  assert.equal(getLocaleFromCountryCode('NL'), 'nl');
});

test('maps spanish countries to es locale', () => {
  assert.equal(getLocaleFromCountryCode('ES'), 'es');
  assert.equal(getLocaleFromCountryCode('MX'), 'es');
});

test('maps remaining countries to en locale', () => {
  assert.equal(getLocaleFromCountryCode('DE'), 'en');
  assert.equal(getLocaleFromCountryCode('IN'), 'en');
});

test('defaults to en for empty or unknown country', () => {
  assert.equal(getLocaleFromCountryCode(''), 'en');
  assert.equal(getLocaleFromCountryCode(null), 'en');
  assert.equal(getLocaleFromCountryCode('ZZ'), 'en');
});

test('geo preferred locale respects supported locales', () => {
  assert.equal(getGeoPreferredLocale('FR', ['en', 'ar']), 'en');
  assert.equal(getGeoPreferredLocale('AE', ['en', 'ar']), 'ar');
});
