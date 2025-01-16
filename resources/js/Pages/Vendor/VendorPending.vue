<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import vendorPendingStatusIcon from '../../../assets/vendorPendingStatusIcon.svg';
</script>

<template>
    <section class="full-w-container">
        <div class="py-[2rem]">
            <Link href="/" class="inline-block">
                <ApplicationLogo />
            </Link>
        </div>

        <div class="min-h-[80vh] flex justify-center items-center">
        <div class="flex flex-col justify-center items-center w-[650px]">
         <img :src="vendorPendingStatusIcon" alt="">
         <h3 class="text-[#333333] text-[1.75rem]">Account Verification Pending</h3>
         <p class="text-center">Your account is currently being verified. Our team is reviewing your documents and details to ensure everything is in order. This process may take up to 24 hours. We appreciate your patience!</p>
        </div>
        </div>
    </section>
</template>

<style></style>
