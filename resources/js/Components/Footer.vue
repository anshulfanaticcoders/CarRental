<script setup >
import ApplicationLogo from "./ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import facebookLogo from "../../assets/Facebook.svg";
import twitterLogo from "../../assets/Twitter.svg";
import instagramLogo from "../../assets/Instagram.svg";
import paypalLogos from "../../assets/paymentIcons.svg";
</script>

<template>
    <div
        class="bg-customPrimaryColor py-customVerticalSpacing text-customPrimaryColor-foreground"
    >
        <div class="container">
            <div class="column py-[3rem] flex justify-between gap-6">
                <div class="col w-[30%] flex flex-col gap-5">
                    <Link class="w-full" href="/"><ApplicationLogo logoColor="#FFFFFF" /></Link>
                    <div class="socialIcons flex gap-6">
                        <Link href=""><img :src="facebookLogo" alt="" /></Link>
                        <Link href=""><img :src="instagramLogo" alt="" /></Link>
                        <Link href=""><img :src="twitterLogo" alt="" /></Link>
                    </div>
                    <div class="column flex flex-col gap-4 mt-[1rem]">
                <span class="text-[1.5rem]"
                    >Subscribe to Newsletter</span
                >
                <div class="max-w-[450px]">
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Email address"
                            class="bg-transparent rounded-[100px] text-white p-3 border-[1px] w-full pl-4"
                        />
                        <button
                            class="h-full absolute right-0 button-tertiary w-[30%]"
                        >
                            Subscribe
                        </button>
                    </div>
                </div>
            </div>
                </div>
                <div class="col w-[50%] flex justify-between gap-10">
                    <div class="col flex flex-col gap-8">
                        <label for="" class="text-[1.25rem]">Company</label>
                        <ul class="flex flex-col gap-4">
                            <li>
                                <Link href="">About Us</Link>
                            </li>
                            <li>
                                <Link href="">Blogs</Link>
                            </li>
                            <li>
                                <Link href="">FAQ</Link>
                            </li>
                            <li>
                                <Link href="">Contact Us</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8">
                        <label for="" class="text-[1.25rem]">Information</label>
                        <ul class="flex flex-col gap-4">
                            <li>
                                <Link href="">Privacy Policy</Link>
                            </li>
                            <li>
                                <Link href="">Terms & Conditions</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8">
                        <label for="" class="text-[1.25rem]">Location</label>
                        <ul class="flex flex-col gap-4">
                            <li>
                                <Link href="">India</Link>
                            </li>
                            <li>
                                <Link href="">London</Link>
                            </li>
                            <li>
                                <Link href="">Paris</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8">
                        <label for="" class="text-[1.25rem]"
                            >Rent By Type</label
                        >
                        <ul class="flex flex-col gap-4">
                            <li>
                                <Link href="">Car</Link>
                            </li>
                            <li>
                                <Link href="">SUV</Link>
                            </li>
                            <li>
                                <Link href="">Sports</Link>
                            </li>
                            <li>
                                <Link href="">Van</Link>
                            </li>
                            <li>
                                <Link href="">TRuck</Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-customDarkBlackColor py-[2rem] text-white">
     <div class="container flex justify-between items-center">
        <div class="column">
            <span class="text-[1.25rem] font-[jakartaFont]">Copyright @ 2024 Vrooem, All rights reserved.</span>
        </div>
        <div class="column">
            <img :src=paypalLogos alt="">
        </div>
     </div>
    </div>
</template>

<style></style>
