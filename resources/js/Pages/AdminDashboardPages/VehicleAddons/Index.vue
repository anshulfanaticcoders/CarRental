<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold">Booking Addons Management</h4>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button>Create New Addon</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Booking Addon</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <InputLabel for="extra_type" value="Extra Type *" />
                                <Input v-model="form.extra_type" required />
                            </div>
                            <div>
                                <InputLabel for="extra_name" value="Extra Name *" />
                                <Input v-model="form.extra_name" required />
                            </div>
                            <div>
                                <InputLabel for="description" value="Description *" />
                                <textarea v-model="form.description" class="w-full border rounded p-2" required></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="quantity" value="Quantity *" />
                                    <Input v-model="form.quantity" type="number" required />
                                </div>
                                <div>
                                    <InputLabel for="price" value="Price *" />
                                    <Input v-model="form.price" type="number" step="0.01" required />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">Create Addon</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Booking Addon</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="updateAddon" class="space-y-4">
                        <div>
                            <InputLabel for="extra_type" value="Extra Type *" />
                            <Input v-model="editForm.extra_type" required />
                        </div>
                        <div>
                            <InputLabel for="extra_name" value="Extra Name *" />
                            <Input v-model="editForm.extra_name" required />
                        </div>
                        <div>
                            <InputLabel for="description" value="Description *" />
                            <textarea v-model="editForm.description" class="w-full border rounded p-2" required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="quantity" value="Quantity *" />
                                <Input v-model="editForm.quantity" type="number" required />
                            </div>
                            <div>
                                <InputLabel for="price" value="Price *" />
                                <Input v-model="editForm.price" type="number" step="0.01" required />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="submit">Update Addon</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Extra Type</TableHead>
                            <TableHead>Extra Name</TableHead>
                            <TableHead>Quantity</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="addon in addons.data" :key="addon.id">
                            <TableCell>{{ addon.extra_type }}</TableCell>
                            <TableCell>{{ addon.extra_name }}</TableCell>
                            <TableCell>{{ addon.quantity }}</TableCell>
                            <TableCell>${{ Number(addon.price).toFixed(2) }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(addon)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteAddon(addon.id)">Delete</Button>
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

const props = defineProps({
    addons: Array,
});

const form = ref({
    extra_type: '',
    extra_name: '',
    description: '',
    quantity: null,
    price: null
});

const isEditDialogOpen = ref(false);
const editForm = ref({});

const submitForm = () => {
    router.post("/booking-addons", form.value, {
        onSuccess: () => {
            form.value = {
                extra_type: '',
                extra_name: '',
                description: '',
                quantity: null,
                price: null
            };
        },
    });
};

const openEditDialog = (addon) => {
    editForm.value = { ...addon };
    isEditDialogOpen.value = true;
};

const updateAddon = () => {
    router.put(`/booking-addons/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const deleteAddon = (id) => {
    router.delete(`/booking-addons/${id}`);
};
</script>