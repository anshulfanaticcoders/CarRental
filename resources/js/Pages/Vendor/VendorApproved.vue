<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import vendorApprovedStatusIcon from '../../../assets/vendorApprovedStatusIcon.svg';
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
         <img :src="vendorApprovedStatusIcon" alt="">
         <h3 class="text-[#333333] text-[1.75rem]">Approved</h3>
         <p class="text-center">Congratulations! Your account has been successfully verified. You can now start listing your vehicles and managing your fleet effortlessly. Click the button below to add your first vehicle.</p>
        </div>
        </div>
    </section>
</template>

<style></style>
