<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";
const sameLocation = ref(true);
</script>

<template>
    <div class="flex relative">
        <div
            class="column w-[20%] bg-customPrimaryColor text-customPrimaryColor-foreground p-[2rem] rounded-tl-[20px] rounded-bl-[20px]"
        >
            <span class="text-[1.75rem] font-medium"
                >Do you need a rental car?</span
            >
        </div>
        <form
            class="column w-[80%] p-[2rem] rounded-tr-[20px] rounded-br-[20px] bg-white grid grid-cols-5 items-center"
        >
            <div class="col col-span-2">
                <div class="flex flex-col">
                    <div class="col">
                        <label for="" class="mb-4 inline-block"
                            >Pickup & Return Location</label
                        >
                        <div class="flex">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                                    stroke="#153B4F"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <input
                                type="text"
                                placeholder="Choose Pickup Location"
                                class="pl-3 border-b border-customPrimaryColor mb-4 focus:outline-none w-[80%]"
                            />
                        </div>
                        <div v-if="!sameLocation" class="flex">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M5.25 21.75H18.75M15 9.75C15 11.4069 13.6569 12.75 12 12.75C10.3431 12.75 9 11.4069 9 9.75C9 8.09315 10.3431 6.75 12 6.75C13.6569 6.75 15 8.09315 15 9.75ZM19.5 9.75C19.5 16.5 12 21.75 12 21.75C12 21.75 4.5 16.5 4.5 9.75C4.5 7.76088 5.29018 5.85322 6.6967 4.4467C8.10322 3.04018 10.0109 2.25 12 2.25C13.9891 2.25 15.8968 3.04018 17.3033 4.4467C18.7098 5.85322 19.5 7.76088 19.5 9.75Z"
                                    stroke="#153B4F"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <input
                                type="text"
                                placeholder="Choose Return Location"
                                class="pl-3 border-b border-customPrimaryColor mb-4 focus:outline-none w-[80%]"
                            />
                        </div>
                    </div>
                    <div class="col">
                        <label class="custom-checkbox">
                            <input
                                type="checkbox"
                                id="same-location"
                                v-model="sameLocation"
                            />
                            <span class="checkmark"></span>
                            Same Location
                        </label>
                    </div>
                </div>
            </div>

            <div class="col border-r-2 border-customLightGrayColor px-5">
                <label
                    class="block text-sm mb-1 text-customLightGrayColor font-medium"
                    >Pick Up Date & Time</label
                >
                <input
                    type="datetime-local"
                    class="p-2 rounded border border-gray-300 w-full text-customPrimaryColor"
                />
            </div>
            <div class="col border-r-2 border-customLightGrayColor px-5">
                <label
                    class="block text-sm mb-1 text-customLightGrayColor font-medium"
                    >Return Date & Time</label
                >
                <input
                    type="datetime-local"
                    class="p-2 rounded border border-gray-300 w-full text-customPrimaryColor"
                />
            </div>

            <div class="inner-col flex justify-end">
                <Link href="listing"
                    class="bg-customPrimaryColor text-customPrimaryColor-foreground rounded-[40px] w-[138px] py-4 text-center"
                >
                    Search 
                </Link>
            </div>
        </form>
    </div>
</template>

<style scoped></style>
