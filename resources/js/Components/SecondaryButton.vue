<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="vr-secondary-btn inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-[#e2e8f0] rounded-xl font-semibold text-sm text-[#334155] shadow-[0_1px_2px_rgba(21,59,79,0.04)] hover:border-[#153b4f] hover:bg-[#f0f8fc] hover:text-[#153b4f] focus:outline-none focus:ring-2 focus:ring-[#153b4f]/20 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-[transform,box-shadow,background-color,border-color,color] duration-300"
    >
        <slot />
    </button>
</template>
