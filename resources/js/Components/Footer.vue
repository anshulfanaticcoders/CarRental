<script setup>
import ApplicationLogo from "./ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import facebookLogo from "../../assets/Facebook.svg";
import twitterLogo from "../../assets/Twitter.svg";
import instagramLogo from "../../assets/Instagram.svg";
import paypalLogos from "../../assets/paymentIcons.svg";
import { onMounted, ref } from "vue";

// Fetch footer places and categories data
const footerPlaces = ref([]);
const footerCategories = ref([]);

onMounted(async () => {
  try {
    const [placesResponse, categoriesResponse] = await Promise.all([
      axios.get('/api/footer-places'),
      axios.get('/api/footer-categories'),
    ]);

    footerPlaces.value = placesResponse.data;
    footerCategories.value = categoriesResponse.data;
  } catch (error) {
    console.error('Failed to fetch footer data:', error);
  }
});
</script>

<template>
    <div class="bg-customPrimaryColor py-customVerticalSpacing text-customPrimaryColor-foreground
        max-[768px]:py-0">
        <div class="full-w-container">
            <div class="column py-[3rem] flex justify-between gap-6
            max-[768px]:flex-col">
                <div class="col w-[30%] flex flex-col gap-5
                max-[768px]:w-full">
                    <Link class="w-full" href="/">
                    <ApplicationLogo logoColor="#FFFFFF" />
                    </Link>
                    <div class="socialIcons flex gap-6">
                        <Link href=""><img :src="facebookLogo" alt="" /></Link>
                        <a href="https://www.instagram.com/vrooemofficial?igsh=ZXZkMTdycmN6Mmhz" target="_blank" rel="noopener noreferrer">
  <img :src="instagramLogo" alt="" />
</a>

                        <Link href=""><img :src="twitterLogo" alt="" /></Link>
                    </div>
                    <div class="column flex flex-col gap-4 mt-[1rem]">
                        <span class="text-[1.5rem] max-[768px]:text-[1.2rem]">Subscribe to Newsletter</span>
                        <div class="max-w-[450px]">
                            <div class="relative">
                                <input type="text" placeholder="Email address"
                                    class="bg-transparent rounded-[100px] text-white p-3 border-[1px] w-full pl-4" />
                                <button
                                    class="h-full absolute right-0 button-tertiary w-[30%] max-[768px]:text-[0.875rem]">
                                    Subscribe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col w-[50%] flex justify-between gap-10
                max-[768px]:grid max-[768px]:grid-cols-2 max-[768px]:w-full max-[768px]:mt-5">
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Company</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li>
                                <Link :href="route('pages.show', 'about-us')">About Us</Link>
                            </li>
                            <li>
                                <Link href="/blog">Blogs</Link>
                            </li>
                            <li>
                                <Link href="/faq">FAQ</Link>
                            </li>
                            <li>
                                <Link href="/contact-us">Contact Us</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Information</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li>
                                <Link :href="route('pages.show', 'privacy-policy')">Privacy Policy</Link>
                            </li>
                            <li>
                                <Link :href="route('pages.show', 'terms-and-conditions')">Terms & Conditions</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Location</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li v-for="place in footerPlaces" :key="place.id">
                                <Link
                                    :href="`/s?where=${encodeURIComponent(place.place_name + ', ' + place.city + ', ' + place.country)}&latitude=${place.latitude}&longitude=${place.longitude}&radius=10000`">
                                {{ place.place_name }}
                                </Link>
                            </li>
                            <!-- Fallback if no places are selected -->
                            <li v-if="footerPlaces.length === 0">
                                <Link href="/">No locations available</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
            <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Categories</label>
            <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
              <li v-for="category in footerCategories" :key="category.id">
                <Link :href="`/search/category/${category.slug}`">{{ category.name }}</Link>
              </li>
              <!-- Fallback if no categories are selected -->
              <li v-if="footerCategories.length === 0"><Link href="/">No categories available</Link></li>
            </ul>
          </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-customDarkBlackColor py-[2rem] text-white">
        <div
            class="full-w-container flex justify-between items-center max-[768px]:flex-col max-[768px]:justify-center max-[768px]:gap-5">
            <div class="column max-[768px]:flex">
                <span
                    class="text-[1.25rem] max-[768px]:text-[0.95rem] max-[768px]:w-full max-[768px]:text-center">Copyright
                    @ 2025 Vrooem, All rights reserved.</span>
            </div>
            <div class="column">
                <img :src=paypalLogos alt="">
            </div>
        </div>
    </div>
</template>

<style></style>
