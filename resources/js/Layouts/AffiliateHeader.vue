<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    currentPage: { type: String, default: 'dashboard' },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const businessName = computed(() => page.props.auth?.user?.affiliateBusiness?.name || page.props.business?.name || 'Partner Portal');

const navTabs = computed(() => [
    { key: 'dashboard', label: 'Dashboard', href: route('affiliate.dashboard', { locale: locale.value }) },
    { key: 'commissions', label: 'Commissions', href: route('affiliate.commissions', { locale: locale.value }) },
    { key: 'qr-codes', label: 'QR Codes', href: route('affiliate.qr-codes', { locale: locale.value }) },
    { key: 'settings', label: 'Settings', href: route('affiliate.settings', { locale: locale.value }) },
]);

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <header class="sticky top-0 z-[999] bg-gradient-to-r from-[#0a1d28] to-[#153b4f] border-b border-[rgba(6,182,212,0.15)] shadow-[0_2px_16px_rgba(0,0,0,0.2)]">
        <div class="max-w-[min(92%,1200px)] mx-auto">
            <!-- Top row: brand + logout -->
            <div class="flex items-center justify-between py-2.5">
                <div class="flex items-center gap-3 min-w-0">
                    <Link :href="route('affiliate.dashboard', { locale })"
                        class="text-white font-extrabold text-[1rem] tracking-tight whitespace-nowrap">
                        Vrooem
                    </Link>
                    <span class="hidden sm:inline-block text-[0.65rem] font-bold tracking-[0.12em] uppercase text-cyan-400 border-l border-white/10 pl-3">Partner Portal</span>
                    <span class="hidden md:inline-block text-[0.72rem] text-slate-500 border-l border-white/10 pl-3 truncate max-w-[180px]">{{ businessName }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="'/' + locale"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[0.72rem] font-semibold text-slate-400 rounded-lg border border-white/10 bg-white/[0.03] transition-all hover:text-white hover:border-white/25 hover:bg-white/[0.06] whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1" />
                        </svg>
                        <span class="hidden sm:inline">Home</span>
                    </a>
                    <button @click="logout"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[0.72rem] font-semibold text-slate-400 rounded-lg border border-white/10 bg-white/[0.03] transition-all hover:text-white hover:border-white/25 hover:bg-white/[0.06] whitespace-nowrap cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </div>
            </div>

            <!-- Tab navigation -->
            <nav class="flex overflow-x-auto scrollbar-hide -mb-px" role="tablist">
                <Link v-for="tab in navTabs" :key="tab.key"
                    :href="tab.href"
                    :class="[
                        'px-4 py-2.5 text-[0.8rem] font-semibold whitespace-nowrap border-b-2 transition-all duration-200',
                        currentPage === tab.key
                            ? 'text-cyan-400 border-cyan-400'
                            : 'text-white/40 border-transparent hover:text-white/70'
                    ]"
                    role="tab"
                    :aria-selected="currentPage === tab.key">
                    {{ tab.label }}
                </Link>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
