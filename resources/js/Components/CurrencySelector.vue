<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Check, ChevronDown, Globe2, Search, X } from 'lucide-vue-next';
import { getCurrencyFlagCountry, getCurrencyMeta, selectableCurrencyCodes } from '@/utils/currencyRegistry';

const props = defineProps({
    selectedCurrency: { type: String, default: 'EUR' },
    supportedCurrencies: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    variant: { type: String, default: 'header' },
    hero: { type: Boolean, default: false },
});

const emit = defineEmits(['select']);

const POPULAR = ['EUR', 'USD', 'GBP', 'INR', 'AED', 'AUD', 'CAD', 'JPY'];

const root = ref(null);
const searchInput = ref(null);
const open = ref(false);
const query = ref('');

const supported = computed(() => props.supportedCurrencies?.length ? props.supportedCurrencies : selectableCurrencyCodes);

const currencyMeta = (code) => getCurrencyMeta(code);
const flagUrl = (code, size = 40) => {
    const flagCode = getCurrencyFlagCountry(code);
    return flagCode ? `https://flagcdn.com/w${size}/${flagCode}.png` : null;
};
const flagFallback = (code) => (getCurrencyFlagCountry(code) || String(code).slice(0, 2)).toUpperCase();

const selectedMeta = computed(() => currencyMeta(props.selectedCurrency));

const filteredCurrencies = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return supported.value;
    return supported.value.filter((code) => {
        const meta = currencyMeta(code);
        return code.toLowerCase().includes(q) || meta.name.toLowerCase().includes(q) || meta.symbol.toLowerCase().includes(q);
    });
});

const popularCurrencies = computed(() => {
    if (query.value.trim()) return [];
    return POPULAR.filter((code) => supported.value.includes(code));
});

const restCurrencies = computed(() => {
    const popularSet = new Set(popularCurrencies.value);
    return filteredCurrencies.value
        .filter((code) => !popularSet.has(code))
        .sort((a, b) => a.localeCompare(b));
});

const openPanel = async () => {
    if (props.loading) return;
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        searchInput.value?.focus();
    }
};

const closePanel = () => {
    open.value = false;
    query.value = '';
};

const selectCurrency = (currency) => {
    if (currency !== props.selectedCurrency) {
        emit('select', currency);
    }
    closePanel();
};

const closeOnOutsideClick = (event) => {
    if (open.value && root.value && !root.value.contains(event.target)) {
        closePanel();
    }
};

const closeOnEscape = (event) => {
    if (event.key === 'Escape') closePanel();
};

watch(() => props.loading, (isLoading) => {
    if (isLoading) open.value = false;
});

onMounted(() => {
    document.addEventListener('mousedown', closeOnOutsideClick);
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', closeOnOutsideClick);
    document.removeEventListener('keydown', closeOnEscape);
});
</script>

<template>
    <div ref="root" class="currency-picker" :class="[`currency-picker--${variant}`, { 'is-hero': hero, 'is-open': open }]">
        <button type="button" class="currency-trigger" :disabled="loading" aria-haspopup="listbox" :aria-expanded="open" aria-label="Change currency" @click.stop="openPanel">
            <span class="currency-trigger__main">
                <span class="currency-trigger__flag" aria-hidden="true">
                    <img v-if="flagUrl(selectedCurrency)" :src="flagUrl(selectedCurrency)" :srcset="`${flagUrl(selectedCurrency, 80)} 2x`" alt="" loading="lazy" @error="$event.currentTarget.classList.add('is-hidden')" />
                    <span class="currency-flag__fallback">{{ flagFallback(selectedCurrency) }}</span>
                </span>
                <span class="currency-trigger__text">
                    <span class="currency-trigger__code">{{ selectedCurrency }}</span>
                    <span class="currency-trigger__symbol">{{ selectedMeta.symbol }}</span>
                </span>
            </span>
            <ChevronDown class="currency-trigger__chevron" :size="15" :stroke-width="2.4" aria-hidden="true" />
        </button>

        <Transition
            enter-active-class="currency-enter-active"
            enter-from-class="currency-enter-from"
            enter-to-class="currency-enter-to"
            leave-active-class="currency-leave-active"
            leave-from-class="currency-leave-from"
            leave-to-class="currency-leave-to"
        >
            <div v-if="open" class="currency-popover" role="dialog" aria-label="Choose your currency" @click.stop>
                <div class="currency-popover__head">
                    <div>
                        <div class="currency-popover__eyebrow">Display currency</div>
                        <div class="currency-popover__title">Choose your currency</div>
                        <p>All prices will update to your selected currency.</p>
                    </div>
                    <button type="button" class="currency-popover__close" aria-label="Close currency selector" @click="closePanel">
                        <X :size="17" :stroke-width="2.4" />
                    </button>
                </div>

                <div class="currency-search">
                    <Search :size="17" :stroke-width="2.2" aria-hidden="true" />
                    <input ref="searchInput" v-model="query" type="search" autocomplete="off" placeholder="Search code or name" />
                    <button v-if="query" type="button" class="currency-search__clear" aria-label="Clear currency search" @click="query = ''">
                        <X :size="15" :stroke-width="2.4" />
                    </button>
                </div>

                <div class="currency-list currency-scrollbar" role="listbox">
                    <template v-if="popularCurrencies.length">
                        <div class="currency-section-label">Popular</div>
                        <button v-for="currency in popularCurrencies" :key="`popular-${currency}`" type="button" class="currency-row" :class="{ 'is-selected': currency === selectedCurrency }" role="option" :aria-selected="currency === selectedCurrency" @click="selectCurrency(currency)">
                            <span class="currency-row__flag" aria-hidden="true">
                                <img v-if="flagUrl(currency)" :src="flagUrl(currency)" :srcset="`${flagUrl(currency, 80)} 2x`" alt="" loading="lazy" @error="$event.currentTarget.classList.add('is-hidden')" />
                                <span class="currency-flag__fallback">{{ flagFallback(currency) }}</span>
                            </span>
                            <span class="currency-row__body">
                                <span class="currency-row__top">
                                    <span class="currency-row__code">{{ currency }}</span>
                                    <span class="currency-row__symbol">{{ currencyMeta(currency).symbol }}</span>
                                </span>
                                <span class="currency-row__name">{{ currencyMeta(currency).name }}</span>
                            </span>
                            <span v-if="currency === selectedCurrency" class="currency-row__check"><Check :size="15" :stroke-width="3" /></span>
                        </button>
                    </template>

                    <div class="currency-section-label">{{ popularCurrencies.length ? 'All currencies' : 'Currencies' }}</div>
                    <button v-for="currency in restCurrencies" :key="currency" type="button" class="currency-row" :class="{ 'is-selected': currency === selectedCurrency }" role="option" :aria-selected="currency === selectedCurrency" @click="selectCurrency(currency)">
                        <span class="currency-row__flag" aria-hidden="true">
                            <img v-if="flagUrl(currency)" :src="flagUrl(currency)" :srcset="`${flagUrl(currency, 80)} 2x`" alt="" loading="lazy" @error="$event.currentTarget.classList.add('is-hidden')" />
                            <span class="currency-flag__fallback">{{ flagFallback(currency) }}</span>
                        </span>
                        <span class="currency-row__body">
                            <span class="currency-row__top">
                                <span class="currency-row__code">{{ currency }}</span>
                                <span class="currency-row__symbol">{{ currencyMeta(currency).symbol }}</span>
                            </span>
                            <span class="currency-row__name">{{ currencyMeta(currency).name }}</span>
                        </span>
                        <span v-if="currency === selectedCurrency" class="currency-row__check"><Check :size="15" :stroke-width="3" /></span>
                    </button>

                    <div v-if="!popularCurrencies.length && !restCurrencies.length" class="currency-empty">
                        <Globe2 :size="28" :stroke-width="1.8" />
                        <span>No currency found</span>
                        <small>Try a different code or name.</small>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.currency-picker { --ease: cubic-bezier(0.22, 1, 0.36, 1); --duration: 0.3s; position: relative; }
.currency-trigger { display: inline-flex; align-items: center; justify-content: space-between; gap: 8px; min-height: 40px; border: 1px solid transparent; border-radius: 12px; cursor: pointer; transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease), color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.currency-trigger:hover { transform: translateY(-1px); }
.currency-trigger:focus-visible { outline: 2px solid #22d3ee; outline-offset: 2px; }
.currency-trigger[disabled] { cursor: not-allowed; opacity: 0.7; transform: none; }
.currency-trigger__main { display: inline-flex; align-items: center; gap: 7px; min-width: 0; }
.currency-trigger__flag { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; overflow: hidden; font-size: 0.56rem; line-height: 1; border-radius: 999px; background: rgba(255, 255, 255, 0.72); box-shadow: 0 1px 3px rgba(21, 59, 79, 0.08); flex-shrink: 0; }
.currency-trigger__flag img,
.currency-row__flag img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
.currency-trigger__flag img.is-hidden,
.currency-row__flag img.is-hidden { display: none; }
.currency-flag__fallback { display: inline-flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: #153b4f; font-size: 0.64rem; font-weight: 800; letter-spacing: 0; background: #fff; }
.currency-trigger__text { display: inline-flex; align-items: baseline; gap: 4px; min-width: 0; }
.currency-trigger__code { font-size: 0.84rem; font-weight: 800; letter-spacing: 0.01em; }
.currency-trigger__symbol { font-size: 0.72rem; font-weight: 700; opacity: 0.72; }
.currency-trigger__chevron { flex-shrink: 0; transition: transform var(--duration) var(--ease); }
.currency-picker.is-open .currency-trigger__chevron { transform: rotate(180deg); }

.currency-picker--header .currency-trigger { padding: 7px 11px 7px 9px; }
.currency-picker--header:not(.is-hero) .currency-trigger { background: #f8fafc; border-color: #e2e8f0; color: #334155; }
.currency-picker--header:not(.is-hero) .currency-trigger:hover { background: #f0f8fc; border-color: #153b4f; color: #153b4f; box-shadow: 0 4px 16px rgba(21, 59, 79, 0.06); }
.currency-picker--header.is-hero .currency-trigger { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.1); color: rgba(255, 255, 255, 0.88); }
.currency-picker--header.is-hero .currency-trigger:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.2); color: #fff; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }
.currency-picker--header.is-hero .currency-trigger__flag { background: rgba(255, 255, 255, 0.14); }

.currency-picker--offcanvas { width: 100%; margin-bottom: 6px; }
.currency-picker--offcanvas .currency-trigger { width: 100%; padding: 13px 16px; border-radius: 14px; background: #fff; border-color: rgba(226, 232, 240, 0.6); color: #334155; box-shadow: 0 1px 2px rgba(21, 59, 79, 0.02); }
.currency-picker--offcanvas .currency-trigger:hover { border-color: #153b4f; color: #153b4f; background: #f0f8fc; transform: translateX(4px); box-shadow: 0 4px 12px rgba(21, 59, 79, 0.04); }

.currency-popover { position: absolute; top: calc(100% + 10px); right: 0; z-index: 100002; width: min(380px, calc(100vw - 24px)); overflow: hidden; border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 22px; background: #fff; box-shadow: 0 24px 56px rgba(15, 23, 42, 0.18); transform-origin: top right; }
.currency-picker--offcanvas .currency-popover { position: static; width: 100%; margin-top: 8px; border-radius: 18px; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12); }
.currency-popover__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 18px 18px 30px; color: #fff; background: linear-gradient(135deg, #0b2230 0%, #153b4f 55%, #0b1b26 100%); }
.currency-popover__eyebrow { color: #22d3ee; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; }
.currency-popover__title { margin-top: 4px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.24rem; font-weight: 800; letter-spacing: 0; line-height: 1.2; }
.currency-popover__head p { margin: 5px 0 0; color: rgba(255, 255, 255, 0.66); font-size: 0.82rem; line-height: 1.45; }
.currency-popover__close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.12); color: #fff; background: rgba(255, 255, 255, 0.1); transition: background var(--duration) var(--ease), transform var(--duration) var(--ease); }
.currency-popover__close:hover { background: rgba(255, 255, 255, 0.16); transform: translateY(-1px); }
.currency-popover__close:focus-visible { outline: 2px solid #22d3ee; outline-offset: 2px; }

.currency-search { position: relative; z-index: 1; display: flex; align-items: center; gap: 9px; margin: -18px 14px 10px; padding: 12px 13px; border-radius: 15px; background: #fff; color: #153b4f; box-shadow: 0 12px 26px rgba(21, 59, 79, 0.14); }
.currency-search input { min-width: 0; width: 100%; border: 0; outline: 0; background: transparent; color: #0f172a; font-size: 0.92rem; }
.currency-search input::placeholder { color: #94a3b8; }
.currency-search__clear { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; flex-shrink: 0; border-radius: 999px; color: #94a3b8; transition: color var(--duration) var(--ease), background var(--duration) var(--ease); }
.currency-search__clear:hover { color: #153b4f; background: #f0f8fc; }

.currency-list { max-height: 338px; overflow-y: auto; padding: 2px 0 8px; }
.currency-picker--offcanvas .currency-list { max-height: 300px; }
.currency-section-label { padding: 10px 18px 6px; color: #94a3b8; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; }
.currency-row { display: flex; align-items: center; width: 100%; gap: 10px; padding: 11px 18px; text-align: left; background: #fff; border: 0; cursor: pointer; transition: background var(--duration) var(--ease), transform var(--duration) var(--ease); }
.currency-row:hover { background: #f8fafc; }
.currency-row:focus-visible { outline: 2px solid #22d3ee; outline-offset: -2px; }
.currency-row.is-selected { background: #f0f8fc; }
.currency-row__flag { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; overflow: hidden; flex-shrink: 0; border-radius: 999px; background: #fff; font-size: 0.72rem; box-shadow: 0 1px 4px rgba(21, 59, 79, 0.08); }
.currency-row__body { display: flex; flex: 1; min-width: 0; flex-direction: column; gap: 2px; }
.currency-row__top { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
.currency-row__code { color: #153b4f; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.98rem; font-weight: 800; }
.currency-row__symbol { color: #64748b; font-size: 0.76rem; font-weight: 700; }
.currency-row__name { color: #64748b; font-size: 0.78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.currency-row__check { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; flex-shrink: 0; border-radius: 999px; color: #0b2230; background: #22d3ee; }
.currency-empty { display: grid; place-items: center; padding: 36px 18px 42px; color: #94a3b8; text-align: center; }
.currency-empty span { margin-top: 10px; color: #334155; font-weight: 800; }
.currency-empty small { margin-top: 3px; color: #64748b; }

.currency-scrollbar::-webkit-scrollbar { width: 6px; }
.currency-scrollbar::-webkit-scrollbar-track { background: transparent; }
.currency-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
.currency-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.currency-enter-active, .currency-leave-active { transition: opacity 0.2s var(--ease), transform 0.2s var(--ease); }
.currency-enter-from, .currency-leave-to { opacity: 0; transform: translateY(-6px) scale(0.97); }
.currency-enter-to, .currency-leave-from { opacity: 1; transform: translateY(0) scale(1); }

@media (max-width: 480px) {
    .currency-picker--header .currency-popover { right: -54px; width: min(360px, calc(100vw - 18px)); }
}
</style>
