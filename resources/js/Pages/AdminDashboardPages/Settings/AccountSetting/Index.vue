<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();
const user = usePage().props.auth.user;

const form = useForm({
    first_name: user.first_name,
    last_name: user.last_name,
    email: user.email,
    password: '',
    password_confirmation: '',
    avatar: user.avatar || '',
});

const avatarPreview = ref(user.avatar
    ? (user.avatar.startsWith('http') ? user.avatar : `/storage/${user.avatar}`)
    : '/storage/avatars/default-avatar.svg');

function handleAvatarUpload(event) {
    const file = event.target.files[0];
    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
        form.avatar = file;
    }
}

const handleSubmit = () => {
    form.post(route('admin.account.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Account updated successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            form.reset('password', 'password_confirmation');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        },
        onError: (errors) => {
            console.log('Validation errors:', errors);
            const firstErrorField = Object.keys(errors)[0];
            if (firstErrorField) {
                const inputElement = document.getElementById(firstErrorField);
                if (inputElement) {
                    inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    inputElement.focus();
                }
            }
        },
    });
};
</script>

<template>
    <header>
        <h2 class="text-[1.75rem] font-medium text-gray-900 max-[768px]:text-[1.2rem]">Admin Account Settings</h2>
    </header>
    <section v-bind="$attrs" class="mt-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <div>
                <!-- Avatar preview with edit button -->
                <div class="relative w-24 h-24">
                    <img :src="avatarPreview" alt="Admin Avatar" class="w-24 h-24 rounded-full object-cover" />
                    <button type="button" @click="() => $refs.avatarInput.click()"
                        class="absolute bottom-0 right-0 bg-gray-700 text-white p-1 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M17.414 2.586a2 2 0 00-2.828 0L6 11.172V14h2.828l8.586-8.586a2 2 0 000-2.828zM5 15v1a2 2 0 002 2h1a2 2 0 002-2v-1H5z" />
                        </svg>
                    </button>
                </div>
                <input ref="avatarInput" id="avatar" type="file" accept="image/*" class="hidden"
                    @change="handleAvatarUpload" />
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>
            <div class="grid grid-cols-2 gap-8">
                <div class="w-full">
                    <InputLabel for="first_name" value="First Name" />
                    <TextInput id="first_name" type="text" class="mt-1 block w-full" v-model="form.first_name"
                        required />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>
                <div class="w-full">
                    <InputLabel for="last_name" value="Last Name" />
                    <TextInput id="last_name" type="text" class="mt-1 block w-full" v-model="form.last_name"
                        required />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
                <div class="w-full">
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div class="w-full">
                    <InputLabel for="password" value="New Password (optional)" />
                    <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div class="w-full">
                    <InputLabel for="password_confirmation" value="Confirm New Password" />
                    <TextInput id="password_confirmation" type="password" class="mt-1 block w-full"
                        v-model="form.password_confirmation" />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
                <div class="flex items-end gap-4 col-span-2">
                    <PrimaryButton :disabled="form.processing" class="w-[10rem]">Update Account</PrimaryButton>
                </div>
            </div>
        </form>
    </section>
</template>

<style scoped>
input {
    border-radius: 0.75rem;
    border: 1px solid rgba(43, 43, 43, 0.50);
    padding: 1rem;
}

@media screen and (max-width: 768px) {
    input {
        font-size: 0.75rem;
    }
    label {
        font-size: 0.75rem !important;
    }
}
</style>