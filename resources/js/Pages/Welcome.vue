<script setup>
import { Head, Link } from "@inertiajs/vue3";
import heroImg from "../../assets/heroImage.jpeg";
import SearchBar from "@/Components/SearchBar.vue";
import locationMapIcon from "../../assets/location.svg";
import chipIcon from "../../assets/chip.svg";
import goIcon from "../../assets/goIcon.svg";
import phoneIcon from "../../assets/phone.svg";
import userCoverageIcon from "../../assets/usercoverage.svg";
import carImage from "../../assets/carImagebgrmoved.png";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import HowItWorks from "@/Components/ReusableComponents/HowItWorks.vue";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="">
        <div v-if="canLogin" class="container"> 
                <div v-if="$page.props.auth.user">
                    <AuthenticatedHeaderLayout/>
                </div>

            <template v-else>
               <div class="flex justify-between py-[2rem]">
                <div class="column">
                   <Link href="/"><ApplicationLogo/></Link> 
               </div> 

               <div class="column">
                   <Link
                    :href="route('login')"
                    class="button-primary py-3 px-5  font-semibold text-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Log in</Link>

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="button-secondary ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Create an Account</Link>
               </div> 
               </div>
            </template>
        </div>
</div>

<main>
    <section class="hero_section">
            <div class="wrapper flex justify-between w-full">
                <div
                    class="column bg-customPrimaryColor h-[65vh] w-full text-white flex flex-col items-end justify-center"
                >
                    <div class="w-[666px]">
                        <h1>Hit the Road with the Perfect Ride</h1>
                        <p class="text-[1.25rem] mt-3">
                            Get a car wherever and whenever you need it <br />
                            with your iOS or Android device.
                        </p>
                    </div>
                </div>
                <div class="column h-[80vh] w-full relative">
                    <img
                        class="rounded-bl-[20px] h-full w-full"
                        :src="heroImg"
                        alt=""
                    />
                    <div
                        class="bg-customOverlayColor absolute top-0 w-full h-full rounded-bl-[20px]"
                    ></div>
                </div>
            </div>
        </section>

        <section class="mt-[-14rem] mb-[10rem]">
            <div class="container">
                <SearchBar />
            </div>
        </section>

        


          <!------------------------------- WHY CHOOSE US -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
            <section class="py-customVerticalSpacing">
            <div class="container flex flex-col gap-16">
                <div class="column text-center flex flex-col items-center">
                    <span class="text-[1.25rem]">-Why Choose Us-</span>
                    <h3 class="max-w-[883px]">
                        From luxury sedans to budget-friendly compacts, we've
                        got something for every journey
                    </h3>
                </div>
                <div class="column grid grid-cols-3 gap-16">
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="locationMapIcon" alt="" />
                            <div
                                class="text-customMediumBlackColor flex flex-col gap-3"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Convenient Locations</span
                                >
                                <p>
                                    With multiple rental locations at airports,
                                    city centers, and popular destinations,
                                    picking up and dropping off your rental is
                                    quick and easy.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="phoneIcon" alt="" />
                            <div
                                class="text-customMediumBlackColor flex flex-col gap-3"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Fast and Easy Booking Process</span
                                >
                                <p>
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <img
                            class="rounded-[20px] h-full w-full object-cover"
                            :src="carImage"
                            alt=""
                        />
                    </div>
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="chipIcon" alt="" />
                            <div
                                class="text-customMediumBlackColor flex flex-col gap-3"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Modern Fleet with the Latest
                                    Technology</span
                                >
                                <p>
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="userCoverageIcon" alt="" />
                            <div
                                class="flex flex-col gap-3 text-customMediumBlackColor"
                            >
                                <span class="text-[1.5rem] font-medium"
                                    >Insurance Coverage</span
                                >
                                <p>
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             </section>
        <!------------------------------ <End>  -------------------------------------------------->

   <!------------------------------- How It Works -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
            <HowItWorks/>
            <!------------------------------ <End>  -------------------------------------------------->

</main>
</template>

<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}
@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}
</style>
