<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { UserCog, Lock, TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    hasSocialAccount: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <MyProfileLayout>
        <Head title="Profile" />

        <div class="vr-phead">
            <div>
                <span class="vr-eyebrow"><UserCog /> My account</span>
                <h2>My Profile</h2>
                <p class="vr-sub">Keep your personal details up to date.</p>
            </div>
        </div>

        <div class="vr-panel">
            <div class="vr-panel-head">
                <h3><UserCog /> Personal Details</h3>
            </div>
            <div class="vr-panel-body">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="w-full"
                />
            </div>
        </div>

        <div class="vr-panel">
            <div class="vr-panel-head">
                <h3><Lock /> Security</h3>
            </div>
            <div class="vr-panel-body">
                <UpdatePasswordForm class="w-full" />
            </div>
        </div>

        <div class="vr-panel" style="border-color: #fecdd3">
            <div class="vr-panel-head">
                <h3 style="color: #e11d48"><TriangleAlert style="color: #e11d48" /> Danger Zone</h3>
            </div>
            <div class="vr-panel-body">
                <DeleteUserForm class="w-full" :has-social-account="hasSocialAccount" />
            </div>
        </div>
    </MyProfileLayout>
</template>
