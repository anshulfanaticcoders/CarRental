<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage } from "@inertiajs/vue3";
import hamburgerIcon from "../../assets/hamburgerMenu.svg";
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/Components/ui/sheet";

const { url } = usePage();

const isLoginPage = url.includes('/login');
const isRegisterPage = url.includes('/register');
</script>

<template>
    <div class="column border-b-2">
        <div class="full-w-container flex justify-between items-center py-[2rem]">
            <Link href="/" class="max-[480px]:w-[150px]"><ApplicationLogo class="max-[480px]:w-full" /></Link>
            <div class="flex gap-[2rem] items-center">
                <div class="column">
                    <Link
                        v-if="isRegisterPage"
                        :href="route('login')"
                        class="button-primary py-3 px-5 font-semibold text-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm
                        max-[480px]:text-[0.75rem]"
                    >Log in</Link>

                    <Link
                        v-if="isLoginPage"
                        :href="route('register')"
                        class="button-secondary ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm
                        max-[480px]:text-[0.75rem]"
                    >Create an Account</Link>
                </div>
                
            </div>
        </div>
    </div>
</template>

<style>
.offcanvasList a{
    padding:1rem;
    background-color: var(--custom-primary);
    color: white;
    border-radius: 12px;
}
</style>
