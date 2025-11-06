<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    countries: Array,
    packages: Array,
    filters: Object,
});

const form = useForm({
    country: props.filters.country || '',
});

const search = () => {
    form.get(route('esim.search'));
};
</script>

<template>
    <div>
        <h1>Search for eSIMs</h1>

        <form @submit.prevent="search">
            <select v-model="form.country">
                <option value="">Select a country</option>
                <option v-for="country in countries" :key="country.code" :value="country.code">
                    {{ country.name }}
                </option>
            </select>
            <button type="submit">Search</button>
        </form>

        <div v-if="packages.length">
            <h2>Available Packages</h2>
            <ul>
                <li v-for="pkg in packages" :key="pkg.id">
                    {{ pkg.name }} - {{ pkg.price }}
                </li>
            </ul>
        </div>
    </div>
</template>
