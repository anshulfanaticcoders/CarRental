<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showStatusToast = (message) => {
    if (message) {
        toast.success(message);
    }
};

onMounted(() => {
    if (props.status) {
        setTimeout(() => {
            showStatusToast(props.status);
        }, 100);
    }
});

watch(() => props.status, (newStatus) => {
    showStatusToast(newStatus);
});
</script>

<template>
    <MyProfileLayout>
        <Head title="Profile" />
        <div class="">
            <h4 class="text-[1.5rem] mb-0 ml-[1rem] text-customPrimaryColor font-medium max-[768px]:ml-0 max-[768px]:mb-3">My Profile</h4>
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
