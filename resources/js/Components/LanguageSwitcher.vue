<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const currentLocale = computed(() => usePage().props.locale);

const locales = {
    en: 'En',
    fr: 'Fr',
    nl: 'Nl'
};
</script>

<template>
    <div class="language-switcher">
        <div v-for="(name, code) in locales" :key="code" class="language-option">
            <Link 
                :href="route('language.switch', code)"
                :class="{ 'active': currentLocale === code }"
                preserve-scroll
            >
                {{ name }}
            </Link>
        </div>
    </div>
</template>

<style scoped>
.language-switcher {
    display: flex;
    gap: 10px;
}

.language-option a {
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.language-option a:hover {
    background-color: #f0f0f0;
}

.language-option a.active {
    background-color: #e0e0e0;
    font-weight: bold;
}
</style>