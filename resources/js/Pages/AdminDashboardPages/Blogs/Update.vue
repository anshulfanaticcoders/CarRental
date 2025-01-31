<template>
    <AdminDashboardLayout>
        <div class="p-6">
        <h1 class="text-3xl font-bold mb-6">Edit Blog</h1>

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
                <div v-if="blog.image" class="mb-2">
                    <img 
                        :src="`/${blog.image}`" 
                        alt="Current image"
                        class="w-32 h-32 object-cover rounded"
                    >
                </div>
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
                    class="px-4 py-2 bg-[#EA3C3C] text-white rounded"
                >
                    Cancel
                </Link>
                <Button 
                    type="submit"
                    class="px-4 py-5 bg-customPrimaryColor"
                    :disabled="form.processing"
                >
                    Update Blog
                </Button>
            </div>
        </form>
    </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { Button } from '@/Components/ui/button';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    blog: Object
});

const form = useForm({
    title: props.blog.title,
    content: props.blog.content,
    image: null,
    _method: 'PUT'
});

const submit = () => {
    form.post(route('blogs.update', props.blog.id));
};
</script>