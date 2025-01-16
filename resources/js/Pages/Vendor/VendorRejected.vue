<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import vendorRejectedStatusIcon from '../../../assets/vendorRejectedStatusIcon.svg';
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
         <img :src="vendorRejectedStatusIcon" alt="">
         <h3 class="text-[#333333] text-[1.75rem]">Rejected</h3>
         <p class="text-center">We’re sorry, but your account verification could not be completed. Please review the following issues and resubmit your details for approval:
Missing identity proof.
Incorrect phone number.</p>
        </div>
        </div>
    </section>
</template>

<style></style>
