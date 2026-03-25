<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
    },
    mileageTypeLabel: {
        type: String,
        default: null,
    },
})
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Driver Requirements
        </h3>
        <p v-if="mileageTypeLabel" class="text-sm text-gray-600 mb-3">
            <span class="font-semibold text-gray-800">Mileage type:</span> {{ mileageTypeLabel }}
        </p>
        <ul v-if="items.length" class="text-sm text-gray-600 space-y-2">
            <li v-for="item in items" :key="item" class="flex items-start gap-2">
                <span class="mt-1.5 w-2 h-2 rounded-full bg-[#1e3a5f] flex-shrink-0"></span>
                <span>{{ item }}</span>
            </li>
        </ul>
    </section>
</template>
