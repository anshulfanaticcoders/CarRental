<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.25s">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            What's Included
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="(item, idx) in items" :key="idx" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/60">
                <div class="check-icon bg-emerald-500 text-white mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ item.label }}</p>
                    <p v-if="item.detail" class="text-xs text-gray-500">{{ item.detail }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
