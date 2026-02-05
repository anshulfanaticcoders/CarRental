<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

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
        <div class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>My Profile</CardTitle>
                    <CardDescription>Keep your personal details up to date.</CardDescription>
                </CardHeader>
                <CardContent>
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="w-full"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Security</CardTitle>
                    <CardDescription>Update your password to keep your account safe.</CardDescription>
                </CardHeader>
                <CardContent>
                    <UpdatePasswordForm class="w-full" />
                </CardContent>
            </Card>

            <Card class="border-rose-200/70">
                <CardHeader>
                    <CardTitle class="text-rose-600">Danger Zone</CardTitle>
                    <CardDescription>Delete your account and all data permanently.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DeleteUserForm class="w-full" />
                </CardContent>
            </Card>
        </div>
    </MyProfileLayout>
</template>
