<script setup>
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, usePage } from "@inertiajs/vue3";
import hamburgerIcon from "../../assets/hamburgerMenu.svg";
import {
    Sheet,
    SheetContent,
    SheetTrigger,
} from "@/Components/ui/sheet";

const { url } = usePage();
// const isLoginPage = url.includes('/login');
// const isRegisterPage = url.includes('/register');
const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="border-b-[1px] border-b-customMediumBlackColor">
        <div class="py-[1.5rem] full-w-container">
        <!-- Authenticated User Header -->
        <div v-if="$page.props.auth.user" class="">
            <nav class="">
                <div class="mx-auto">
                    <div class="flex justify-between">
                        <!-- Logo Section -->
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link href="/">
                                    <ApplicationLogo class="block fill-current text-gray-800" />
                                </Link>
                            </div>
                        </div>

                        <!-- Desktop Navigation -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Vendor/Customer Specific Buttons -->
                            <div v-if="$page.props.auth.user.role === 'vendor'" class="mr-4">
                                <Link href="/vehicle-listing" class="button-secondary inline-block">
                                    Create a Listing
                                </Link>
                            </div>
                            <div v-else-if="$page.props.auth.user.role === 'customer'">
                                <Link href="/vendor/register" class="button-secondary inline-block">
                                    Register as Vendor
                                </Link>
                            </div>

                            <!-- User Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                            Hey!! {{ $page.props.auth.user.first_name }}
                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                            <!-- Mobile Menu -->
                    <Sheet>
                        <SheetTrigger as-child>
                            <img :src="hamburgerIcon" alt="Menu" class="cursor-pointer" />
                        </SheetTrigger>
                        <SheetContent>
                            <div class="mt-[3rem]">
                                <ul class="flex flex-col gap-[1rem] offcanvasList">
                                    <li><Link href="/" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Home</Link></li>
                                    <li><Link href="/about" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">About</Link></li>
                                    <li><Link href="/feature" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Features</Link></li>
                                    <li><Link href="/login" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Login</Link></li>
                                    <li><Link href="/register" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Register</Link></li>
                                </ul>
                            </div>
                        </SheetContent>
                    </Sheet>
                        </div>

                        <!-- Mobile Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" 
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800">{{ $page.props.auth.user.name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Guest User Header -->
        <div v-else class="column py-[0.5rem]">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <Link href="/">
                    <ApplicationLogo />
                </Link>

                <!-- Navigation -->
                <div class="flex gap-[2rem] items-center">
                    <!-- Auth Links -->
                    <div class="column">
                        <Link 
                              :href="route('login')" 
                              class="button-primary py-3 px-5 font-semibold text-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                            Log in
                        </Link>

                        <Link  
                              :href="route('register')" 
                              class="button-secondary ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                            Create an Account
                        </Link>
                    </div>

                    <!-- Mobile Menu -->
                    <Sheet>
                        <SheetTrigger as-child>
                            <img :src="hamburgerIcon" alt="Menu" class="cursor-pointer" />
                        </SheetTrigger>
                        <SheetContent>
                            <div class="mt-[3rem]">
                                <ul class="flex flex-col gap-[1rem] offcanvasList">
                                    <li><Link href="/" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Home</Link></li>
                                    <li><Link href="/about" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">About</Link></li>
                                    <li><Link href="/feature" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Features</Link></li>
                                    <li><Link href="/login" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Login</Link></li>
                                    <li><Link href="/register" class="w-full block bg-customPrimaryColor p-[1rem] rounded-lg text-white">Register</Link></li>
                                </ul>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

<style scoped>

</style>