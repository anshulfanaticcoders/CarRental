<template>
<AdminDashboardLayout>
    <div class="p-5">
        <h1 class="text-3xl font-bold mb-6">Create New Blog</h1>

        <form @submit.prevent="submit" class="max-w-2xl">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Title
                </label>
                <input 
                    v-model="form.title"
                    type="text"
                    class="w-full px-3 py-2 border rounded-lg"
                    required
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Content
                </label>
                <textarea 
                    v-model="form.content"
                    class="w-full px-3 py-2 border rounded-lg"
                    rows="6"
                    required
                ></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Image
                </label>
                <input 
                    type="file"
                    @input="form.image = $event.target.files[0]"
                    class="w-full"
                    accept="image/*"
                >
            </div>

            <div class="flex justify-end space-x-2">
                <Link 
                    :href="route('blogs.index')"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                >
                    Cancel
                </Link>
                <Button 
                    type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    :disabled="form.processing"
                >
                    Create Blog
                </Button>
            </div>
        </form>
    </div>
</AdminDashboardLayout>
</template>

<script setup>
import Button from '@/Components/ui/button/Button.vue';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const toast = useToast();

const form = useForm({
    title: '',
    content: '',
    image: null
});

const submit = () => {
    form.post(route('blogs.store'), {
        onSuccess: () => {
            toast.success('Blog created successfully!', {
                position: 'top-right',
                timeout: 3000, 
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            form.reset();
        },
        onError: (errors) => {
            
             Object.values(errors).forEach(error => {
                toast.error(error[0], { 
                    position: 'top-right',
                    timeout: 5000,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                });
            });
        }
    });
};
</script>