<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Users Management</span>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button>Create New User</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New User</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="first_name" value="First Name *" />
                                    <Input v-model="form.first_name" required />
                                </div>
                                <div>
                                    <InputLabel for="last_name" value="Last Name *" />
                                    <Input v-model="form.last_name" required />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="email" value="Email *" />
                                <Input v-model="form.email" type="email" required />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Phone *" />
                                <Input v-model="form.phone" required />
                            </div>
                            <div>
                                <InputLabel for="password" value="Password *" />
                                <Input v-model="form.password" type="password" required />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Confirm Password *" />
                                <Input v-model="form.password_confirmation" type="password" required />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="role" value="Role *" />
                                    <Select v-model="form.role" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="customer">Customer</SelectItem>
                                            <SelectItem value="vendor">Vendor</SelectItem>
                                            <SelectItem value="admin">Admin</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="status" value="Status *" />
                                    <Select v-model="form.status" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                            <SelectItem value="suspended">Suspended</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">Create User</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="updateUser" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="first_name" value="First Name *" />
                                <Input v-model="editForm.first_name" required />
                            </div>
                            <div>
                                <InputLabel for="last_name" value="Last Name *" />
                                <Input v-model="editForm.last_name" required />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="email" value="Email *" />
                            <Input v-model="editForm.email" type="email" required />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Phone *" />
                            <Input v-model="editForm.phone" required />
                        </div>
                        <div>
                            <InputLabel for="role" value="Role *" />
                            <Select v-model="editForm.role" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="customer">Customer</SelectItem>
                                    <SelectItem value="vendor">Vendor</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <InputLabel for="status" value="Status *" />
                            <Select v-model="editForm.status" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="suspended">Suspended</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <DialogFooter>
                            <Button type="submit">Update User</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="rounded-md border p-5 h-[80vh] mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell>{{ user.first_name }} {{ user.last_name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <Badge :variant="getRoleBadgeVariant(user.role)">
                                    {{ user.role }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(user.status)">
                                    {{ user.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(user)">
                                        Edit
                                        <img :src=editIcon alt="">
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteUser(user.id)">Delete</Button>
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
import Badge from "@/Components/ui/badge/Badge.vue";
import editIcon from "../../../../assets/Pencil.svg";
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
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import SelectContent from "@/Components/ui/select/SelectContent.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
import Select from "@/Components/ui/select/Select.vue";
import SelectValue from "@/Components/ui/select/SelectValue.vue";

// (Import your UI components here)

const props = defineProps({
    users: Array,
});

const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: '',
    status: ''
});

const isEditDialogOpen = ref(false);
const editForm = ref({});

const submitForm = () => {
    router.post("/users", form.value, {
        onSuccess: () => {
            form.value = {
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
                password: '',
                password_confirmation: '',
                role: '',
                status: ''
            };
        },
    });
};

const openEditDialog = (user) => {
    editForm.value = { ...user };
    isEditDialogOpen.value = true;
};

const updateUser = () => {
    router.put(`/users/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const deleteUser = (id) => {
    router.delete(`/users/${id}`);
};

const getRoleBadgeVariant = (role) => {
    switch (role) {
        case 'admin': return 'destructive';
        case 'vendor': return 'secondary';
        default: return 'default';
    }
};

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'active': return 'default';
        case 'inactive': return 'secondary';
        case 'suspended': return 'destructive';
    }
};
</script>