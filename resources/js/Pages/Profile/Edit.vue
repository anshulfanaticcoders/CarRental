<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <MyProfileLayout>
        <Head title="Profile" />
        <div class="">
            <h4 class="text-[1.5rem] mb-[2rem] ml-[1rem] text-customPrimaryColor font-medium max-[768px]:ml-0">My Profile</h4>
            <div class=" mx-auto">
                <div class="">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class=" ">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class=" ">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>
