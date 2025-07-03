<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    phone: props.user.phone || '',
    company_name: props.user.admin_profile?.company_name || '',
    avatar: null, // Keep this separate for file handling
});

const submit = () => {
    // Create FormData to properly handle file upload
    const formData = new FormData();
    
    // Append all regular fields
    formData.append('first_name', form.first_name);
    formData.append('last_name', form.last_name);
    formData.append('email', form.email);
    formData.append('phone', form.phone);
    formData.append('company_name', form.company_name);
    
    // Append the file if it exists
    if (form.avatar) {
        formData.append('avatar', form.avatar);
    }

    // Use transform to send as FormData
    form.transform((data) => formData)
        .post('/admin/settings/profile', {
            preserveScroll: true,
            onSuccess: () => {
                // Use your preferred notification method
                alert('Profile updated successfully!');
            },
        });
};

const handleFileChange = (event) => {
    form.avatar = event.target.files[0];
};
</script>

<template>
    <AdminDashboardLayout>
    <div class="p-4">
        <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
        <p class="mt-1 text-sm text-gray-600">Update your account's profile information and email address.</p>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="first_name" class="block font-medium text-gray-700">First Name</label>
                <input type="text" id="first_name" v-model="form.first_name" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="form.errors.first_name" class="text-red-500">{{ form.errors.first_name }}</div>
            </div>
            <div>
                <label for="last_name" class="block font-medium text-gray-700">Last Name</label>
                <input type="text" id="last_name" v-model="form.last_name" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="form.errors.last_name" class="text-red-500">{{ form.errors.last_name }}</div>
            </div>
            <div>
                <label for="email" class="block font-medium text-gray-700">Email</label>
                <input type="email" id="email" v-model="form.email" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</div>
            </div>
            <div>
                <label for="phone" class="block font-medium text-gray-700">Phone</label>
                <input type="text" id="phone" v-model="form.phone"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="form.errors.phone" class="text-red-500">{{ form.errors.phone }}</div>
            </div>
            <div>
                <label for="company_name" class="block font-medium text-gray-700">Company Name</label>
                <input type="text" id="company_name" v-model="form.company_name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="form.errors.company_name" class="text-red-500">{{ form.errors.company_name }}</div>
            </div>
            <div>
                <label class="block font-medium text-gray-700">Avatar</label>
                <input type="file" @change="handleFileChange"
                    class="mt-1 block w-full text-sm text-slate-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-indigo-50 file:text-indigo-700
                    hover:file:bg-indigo-100" />
                <div v-if="form.errors.avatar" class="text-red-500">{{ form.errors.avatar }}</div>
            </div>
            <div v-if="props.user.admin_profile.avatar">
                <img :src="props.user.admin_profile.avatar" alt="Avatar" class="w-20 h-20 rounded-full">
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Save</button>
                <div v-if="form.processing" class="text-gray-500">Saving...</div>
            </div>
        </form>
    </div>
    </AdminDashboardLayout>
</template>
