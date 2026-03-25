<script setup>
defineProps({
    terms: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.6s">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Terms &amp; Conditions
        </h3>
        <div class="space-y-3 max-h-48 overflow-y-auto pr-2">
            <div v-for="(category, index) in terms" :key="`term-${index}`">
                <p class="text-xs font-bold text-gray-700 mb-1">{{ category.name }}</p>
                <ul class="space-y-1">
                    <li v-for="(condition, ci) in category.conditions" :key="`term-${index}-${ci}`" class="text-xs text-gray-600 leading-relaxed">{{ condition }}</li>
                </ul>
            </div>
        </div>
    </section>
</template>
