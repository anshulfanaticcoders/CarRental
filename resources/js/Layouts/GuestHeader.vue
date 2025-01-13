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
            <Link href="/"><ApplicationLogo /></Link>
            <div class="flex gap-[2rem] items-center">
                <div class="column">
                    <Link
                        v-if="isRegisterPage"
                        :href="route('login')"
                        class="button-primary py-3 px-5 font-semibold text-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Log in</Link>

                    <Link
                        v-if="isLoginPage"
                        :href="route('register')"
                        class="button-secondary ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Create an Account</Link>
                </div>
                <Sheet>
                    <SheetTrigger as-child>
                        <img
                            :src="hamburgerIcon"
                            alt=""
                            class="cursor-pointer"
                        />
                    </SheetTrigger>
                    <SheetContent>
                        <div class="mt-[3rem]">
                            <ul class="flex flex-col gap-[1rem] offcanvasList">
                                <li><Link href="/" class="w-full block"> Home </Link></li>
                                <li><Link href="/about" class="w-full block"> About </Link></li>
                                <li><Link href="/feature" class="w-full block"> Features </Link></li>
                                <li><Link href="/login" class="w-full block"> Login </Link></li>
                                <li><Link href="/register" class="w-full block"> Register </Link></li>
                            </ul>
                        </div>
                    </SheetContent>
                </Sheet>
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
