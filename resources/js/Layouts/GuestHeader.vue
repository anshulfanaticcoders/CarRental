<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div class="column border-b-2">
        <div class="container flex justify-between items-center py-[2rem]">
            <Link href="/"><ApplicationLogo /></Link>
            <p>This is menu</p>
        </div>
    </div>

    <!-- Page Content Under Guest header means for unauntheticated user -->
    <div class="column container">
        <slot />
    </div>
</template>

<style></style>
