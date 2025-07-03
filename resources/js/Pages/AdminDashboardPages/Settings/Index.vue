<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/toast/use-toast';
import { Toaster } from '@/Components/ui/toast';
import loaderVariant from '../../../../assets/loader-variant.svg';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    }
});

const { toast } = useToast();

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    phone: props.user.phone || '',
    company_name: props.user.admin_profile?.company_name || '',
    avatar: null,
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    const formData = new FormData();

    formData.append('first_name', form.first_name);
    formData.append('last_name', form.last_name);
    formData.append('email', form.email);
    formData.append('phone', form.phone);
    formData.append('company_name', form.company_name);

    if (form.avatar) {
        // Optional: Validate avatar file type and size
        const validTypes = ['image/jpeg', 'image/png'];
        if (!validTypes.includes(form.avatar.type)) {
            toast({
                title: 'Invalid File Type',
                description: 'Please upload a JPG or PNG file.',
                variant: 'destructive',
                class: 'bg-black text-white border-none p-4 rounded-md',
            });
            return;
        }
        if (form.avatar.size > 5 * 1024 * 1024) { // 5MB limit
            toast({
                title: 'File Too Large',
                description: 'Avatar file size must be less than 5MB.',
                variant: 'destructive',
                class: 'bg-black text-white border-none p-4 rounded-md',
            });
            return;
        }
        formData.append('avatar', form.avatar);
    }

    // Conditionally append password fields if new password is provided
    if (form.password) {
        if (!form.current_password) {
            toast({
                title: 'Missing Current Password',
                description: 'Please enter your current password to set a new one.',
                variant: 'destructive',
                class: 'bg-black text-white border-none p-4 rounded-md',
            });
            return;
        }
        if (form.password.length < 8) {
            toast({
                title: 'Password Too Short',
                description: 'New password must be at least 8 characters long.',
                variant: 'destructive',
                class: 'bg-black text-white border-none p-4 rounded-md',
            });
            return;
        }
        if (form.password !== form.password_confirmation) {
            toast({
                title: 'Passwords Do Not Match',
                description: 'New password and confirm password do not match.',
                variant: 'destructive',
                class: 'bg-black text-white border-none p-4 rounded-md',
            });
            return;
        }
        formData.append('current_password', form.current_password);
        formData.append('password', form.password);
        formData.append('password_confirmation', form.password_confirmation);
    }

     form.transform((data) => formData)
        .post('/admin/settings/profile', {
            preserveScroll: true,
            onSuccess: () => {
                toast({
                    title: 'Profile Updated',
                    description: 'Your profile information has been successfully updated.',
                    class: 'bg-black text-white border-none p-4 rounded-md',
                });
                window.location.reload();
            },
            onError: (errors) => {
                toast({
                    title: 'Update Failed',
                    description: errors.message || 'An error occurred while updating your profile.',
                    variant: 'destructive',
                    class: 'bg-black text-white border-none p-4 rounded-md',
                });
            },
        });
};

const handleFileChange = (event) => {
    form.avatar = event.target.files[0];
};
</script>

<template>
    <AdminDashboardLayout>
        <!-- Loader Overlay -->
        <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
            <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
        </div>
        <div class="mx-auto p-6">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
                        <p class="text-gray-600">Manage your account information and preferences</p>
                    </div>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-8">
                    <!-- Profile Picture Section -->
                    <div class="mb-8 pb-8 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile Picture</h3>
                        <div class="flex items-center space-x-6">
                            <div class="relative">
                                <div v-if="props.user.admin_profile?.avatar"
                                    class="w-20 h-20 rounded-full overflow-hidden ring-4 ring-customLightPrimaryColor/20">
                                    <img :src="props.user.admin_profile.avatar" alt="Profile Avatar"
                                        class="w-full h-full object-cover">
                                </div>
                                <div v-else
                                    class="w-20 h-20 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Change Avatar</label>
                                <div class="flex items-center space-x-4">
                                    <label class="cursor-pointer">
                                        <input type="file" @change="handleFileChange" accept="image/*" class="hidden">
                                        <span
                                            class="inline-flex items-center px-4 py-2 border border-customPrimaryColor text-customPrimaryColor bg-white rounded-lg hover:bg-customLightPrimaryColor/10 transition-colors duration-200 text-sm font-medium">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                </path>
                                            </svg>
                                            Upload New
                                        </span>
                                    </label>
                                    <span class="text-sm text-gray-500">JPG, PNG up to 5MB</span>
                                </div>
                                <div v-if="form.errors.avatar" class="mt-2 text-sm text-red-600">{{ form.errors.avatar
                                    }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Form -->
                    <form @submit.prevent>
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- First Name -->
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="first_name" v-model="form.first_name" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter your first name" />
                                    <div v-if="form.errors.first_name" class="mt-2 text-sm text-red-600">{{
                                        form.errors.first_name }}</div>
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="last_name" v-model="form.last_name" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter your last name" />
                                    <div v-if="form.errors.last_name" class="mt-2 text-sm text-red-600">{{
                                        form.errors.last_name }}</div>
                                </div>

                                <!--Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="email" v-model="form.email" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter your email address" />
                                    <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email
                                        }}</div>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" id="phone" v-model="form.phone"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter your phone number" />
                                    <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600">{{ form.errors.phone
                                        }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Company Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Company Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Company Name
                                    </label>
                                    <input type="text" id="company_name" v-model="form.company_name"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter your company name" />
                                    <div v-if="form.errors.company_name" class="mt-2 text-sm text-red-600">{{
                                        form.errors.company_name }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Management -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Password Management</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Current Password -->
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Current Password
                                    </label>
                                    <input type="password" id="current_password" v-model="form.current_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter your current password" />
                                    <div v-if="form.errors.current_password" class="mt-2 text-sm text-red-600">{{
                                        form.errors.current_password }}</div>
                                </div>

                                <!-- New Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                        New Password
                                    </label>
                                    <input type="password" id="password" v-model="form.password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Enter new password" />
                                    <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{
                                        form.errors.password }}</div>
                                </div>

                                <!-- Verify Password -->
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                        Verify Password
                                    </label>
                                    <input type="password" id="password_confirmation" v-model="form.password_confirmation"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="Re-enter new password" />
                                    <div v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{
                                        form.errors.password_confirmation }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <div class="flex items-center space-x-4">
                                <AlertDialog>
                                    <AlertDialogTrigger as-child>
                                        <Button :disabled="form.processing"
                                            class="inline-flex items-center px-6 py-3 bg-customPrimaryColor text-white rounded-lg hover:bg-customPrimaryColor/90 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium">
                                            <svg v-if="form.processing"
                                                class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Save Profile Changes?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                Are you sure you want to save these changes? This will update your
                                                profile information.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="submit">Continue</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>

                                <!-- <button 
                                    type="button" 
                                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 transition-all duration-200 font-medium"
                                >
                                    Cancel
                                </button> -->
                            </div>

                            <div v-if="form.processing" class="flex items-center text-customPrimaryColor">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="text-sm font-medium">Updating profile...</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <Toaster />
    </AdminDashboardLayout>
</template>

<style>
/* Custom CSS for your brand colors */
.text-customPrimaryColor {
    color: var(--custom-primary);
}

.bg-customPrimaryColor {
    background-color: var(--custom-primary);
}

.border-customPrimaryColor {
    border-color: var(--custom-primary);
}

.ring-customPrimaryColor {
    --tw-ring-color: var(--custom-primary);
}

.focus\:ring-customPrimaryColor:focus {
    --tw-ring-color: var(--custom-primary);
}

.focus\:border-customPrimaryColor:focus {
    --tw-border-opacity: 1;
    border-color: var(--custom-primary);
}

.hover\:bg-customPrimaryColor\/90:hover {
    background-color: color-mix(in srgb, var(--custom-primary) 90%, transparent);
}

.bg-customLightPrimaryColor {
    background-color: var(--custom-light-primary);
}

.hover\:bg-customLightPrimaryColor\/10:hover {
    background-color: color-mix(in srgb, var(--custom-light-primary) 10%, transparent);
}

.ring-customLightPrimaryColor\/20 {
    --tw-ring-color: color-mix(in srgb, var(--custom-light-primary) 20%, transparent);
}
</style>
