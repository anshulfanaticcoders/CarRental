<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold">Vehicle Categories</h4>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button>Create New Category</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create Vehicle Category</DialogTitle>
                            <DialogDescription>
                                Add a new vehicle category to your system
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Name *" />
                                <Input 
                                    v-model="form.name" 
                                    placeholder="Name" 
                                    required 
                                />
                            </div>
                            <div>
                                <InputLabel for="description" value="Description *" />
                                <Textarea 
                                    v-model="form.description" 
                                    placeholder="Description" 
                                    required 
                                />
                            </div>
                            <div>
                                <InputLabel for="image" value="Image *" />
                                <input
                                    class="border-[2px] rounded-[10px] border-customPrimaryColor p-5 border-dotted"
                                    type="file"
                                    @change="handleFileUpload"
                                    accept="image/jpeg,image/png,image/jpg,image/gif"
                                    required
                                />
                            </div>
                            <DialogFooter>
                                <Button type="submit">Create</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
            
            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Vehicle Category</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="updateCategory" class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Name *" />
                            <Input 
                                v-model="editForm.name" 
                                placeholder="Name" 
                                required 
                            />
                        </div>
                        <div>
                            <InputLabel for="description" value="Description *" />
                            <Textarea class="h-[5rem]"
                                v-model="editForm.description" 
                                placeholder="Description" 
                                required 
                            />
                        </div>
                        <div>
                            <InputLabel for="image" value="Image" class="mb-2" />
                            <div v-if="editForm.existing_image" class="mb-2">
                                <img 
                                    :src="`/storage/${editForm.existing_image}`" 
                                    alt="Existing Category Image" 
                                    class="w-[200px] h-[150px] object-cover mb-2"
                                />
                                <span class="text-sm text-gray-500">Current Image</span>
                            </div>
                            <input
                                class="border-[2px] rounded-[10px] border-customPrimaryColor p-5 border-dotted"
                                type="file"
                                @change="handleEditFileUpload"
                                accept="image/jpeg,image/png,image/jpg,image/gif"
                            />
                        </div>
                        <DialogFooter>
                            <Button type="submit">Update</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Image</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="category in categories.data" :key="category.id">
                            <TableCell class="font-medium">
                                <img :src="`/storage/${category.image}`" alt="" class="w-[100px] h-[70px] rounded-[10px]">
                            </TableCell>
                            <TableCell class="font-medium">{{ category.name }}</TableCell>
                            <TableCell>{{ category.slug }}</TableCell>
                            <TableCell>
                                <Badge :variant="category.status ? 'default' : 'secondary'">
                                    {{ category.status ? "Active" : "Inactive" }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(category)">
                                        <img :src=editIcon alt="">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteCategory(category.id)">Delete</Button>
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
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import editIcon from "../../../../assets/Pencil.svg";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogTrigger,
} from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import Textarea from "@/Components/ui/textarea/Textarea.vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    categories: Array,
});

const form = ref({
    name: "",
    description: "",
    image: null,
});

const editForm = ref({
    id: null,
    name: "",
    description: "",
    image: null,
    existing_image: null,
});

const isEditDialogOpen = ref(false);

const handleFileUpload = (event) => {
    form.value.image = event.target.files[0];
};

const handleEditFileUpload = (event) => {
    editForm.value.image = event.target.files[0];
};

const submitForm = () => {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('description', form.value.description);
    formData.append('image', form.value.image);

    router.post("/vehicle-categories", formData, {
        onSuccess: () => {
            form.value = { name: "", description: "", image: null };
        },
    });
};

const openEditDialog = (category) => {
    editForm.value = {
        id: category.id,
        name: category.name,
        description: category.description,
        image: null,
        existing_image: category.image,
    };
    isEditDialogOpen.value = true;
};

const updateCategory = () => {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', editForm.value.name);
    formData.append('description', editForm.value.description);
    
    if (editForm.value.image) {
        formData.append('image', editForm.value.image);
    }

    router.post(`/vehicle-categories/${editForm.value.id}`, formData, {
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editForm.value = { 
                id: null, 
                name: "", 
                description: "", 
                image: null, 
                existing_image: null 
            };
        },
    });
};

const deleteCategory = (id) => {
    router.delete(`/vehicle-categories/${id}`, {
        onSuccess: () => {
            router.reload();
        },
    });
};
</script>