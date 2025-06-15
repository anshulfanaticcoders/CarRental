<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import loginBg from '../../../assets/loginpageImage.jpg'
import GuestHeader from '@/Layouts/GuestHeader.vue';
import { ref } from 'vue';

const page = usePage();

const _t = (group, key) => {
    return page.props.translations[group][key] || key;
};

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);


const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <main class="">

       <Head :title="_t('login', 'log_in')" />

        <GuestHeader />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <div class="ml-[10%] flex justify-between items-center gap-16 h-[91vh] sign_in
        max-[768px]:flex-col max-[768px]:ml-0 max-[768px]:px-[1.5rem] max-[768px]:justify-center relative
        ">
            <div class="column w-[40%] max-[768px]:w-full">
                <div class="text-center mb-[4rem] text-[#111111]">
                    <h3 class="font-medium text-[3rem] max-[768px]:text-[1.5rem] max-[768px]:text-white">{{ _t('login', 'sign_in') }}</h3>
                    <p
                        class='text-customLightGrayColor max-[768px]:text-white max-[768px]:text-[1rem] max-[768px]:mt-2'>
                        {{ _t('login', 'login_description') }}</p>
                </div>
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" :value="_t('login', 'email_address')" class="max-[768px]:!text-white" />

                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autofocus autocomplete="username" />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-4 relative">
                        <InputLabel for="password" :value="_t('login', 'password')" class="max-[768px]:!text-white" />

                        <TextInput :type="showPassword ? 'text' : 'password'" id="password"
                            class="mt-1 block w-full pr-12" v-model="form.password" required
                            autocomplete="current-password" />

                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-[50%] translate-y-[0%] font-medium text-customDarkBlackColor text-sm max-[768px]:text-white">
                             {{ showPassword ? _t('registerUser', 'hide_password') : _t('registerUser', 'show_password') }}
                        </button>

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>


                    <div class="flex mt-4 justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span
                                class="ms-2 text-lg text-gray-600 max-[768px]:text-white max-[768px]:text-[1rem]">{{ _t('login', 'remember_me') }}</span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="underline max-[768px]:text-[1rem] max-[768px]:text-white text-lg text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ _t('login', 'forgot_password') }}
                        </Link>

                    </div>

                    <div class="flex flex-col gap-4 justify-end mt-4">
                        <button
                            class="button-primary w-full p-4 text-[1.15rem] max-[768px]:text-[1rem] max-[768px]:mt-5"
                            :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ _t('login', 'sign_in_button') }}
                        </button>
                    </div>
                </form>
            </div>

            <div
                class="column overflow-hidden h-full w-[50%] max-[768px]:w-full max-[768px]:absolute max-[768px]:top-0 max-[768px]:-z-10">
                <img :src=loginBg alt="" class="w-full h-full brightness-90 object-cover repeat-0 max-[768px]:brightness-50">
            </div>
        </div>

    </main>
</template>


<style>
.sign_in input {
    border: 1px solid #2B2B2B80;
    border-radius: 12px;
    padding: 1rem;
}

.sign_in label {
    color: #2B2B2BBF;
}
</style>
