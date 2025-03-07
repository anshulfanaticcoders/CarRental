<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Blog Management</span>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button>Create New Blog</Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-4xl">
                        <DialogHeader>
                            <DialogTitle>Create New Blog Post</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="title" value="Title *" />
                                <Input v-model="form.title" required />
                            </div>

                            <div>
                                <InputLabel for="content" value="Content *" />
                                <textarea 
                                    v-model="form.content"
                                    class="w-full px-3 py-2 border rounded-lg h-32"
                                    required
                                ></textarea>
                            </div>

                            <div>
                                <InputLabel for="image" value="Blog Image" />
                                <Input 
                                    type="file"
                                    @input="form.image = $event.target.files[0]"
                                    accept="image/*"
                                />
                            </div>

                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Create Blog</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Blogs Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Content Preview</TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(blog, index) in blogs" :key="blog.id">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell>{{ blog.title }}</TableCell>
                            <TableCell>{{ blog.content.substring(0, 100) }}...</TableCell>
                            <TableCell>{{ formatDate(blog.created_at) }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="editBlog(blog)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive"  @click="confirmDeleteBlog(blog)">
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

// Import UI components
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogTrigger,
} from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { useToast } from 'vue-toastification';
const toast = useToast();
const props = defineProps({
    blogs: Array
});

const form = useForm({
    title: '',
    content: '',
    image: null,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const submit = () => {
    form.post(route('blogs.store'), {
        onSuccess: () => {
            form.reset();
            toast.success('Blog created successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error[0], {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        }
    });
};

const editBlog = (blog) => {
    router.get(route('blogs.edit', blog.id));
};

const confirmDeleteBlog = (blog) => {
  if (confirm('Are you sure you want to delete this blog?')) {
    deleteBlog(blog.id);
  }
};

const deleteBlog = (id) => {
    router.delete(route('blogs.destroy', id), {
        onSuccess: () => {
            toast.success('Blog deleted successfully!', {  // Toast on success
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => { // Toast on error
                toast.error(error[0], {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        }
    });
};
</script>