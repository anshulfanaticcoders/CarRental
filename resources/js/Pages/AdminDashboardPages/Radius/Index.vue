<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Radius Management</span>
                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button @click="openCreateDialog">Create New Radius</Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-4xl">
                        <DialogHeader>
                            <DialogTitle>Create New Radius</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="city" value="City" />
                                <select v-model="form.city" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">Select City</option>
                                    <option v-for="city in cities" :value="city" :key="city">
                                        {{ city || 'N/A' }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="state" value="State" />
                                <select v-model="form.state" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">Select State</option>
                                    <option v-for="state in states" :value="state" :key="state">
                                        {{ state || 'N/A' }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="country" value="Country" />
                                <select v-model="form.country" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">Select Country</option>
                                    <option v-for="country in countries" :value="country" :key="country">
                                        {{ country || 'N/A' }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="radius_km" value="Radius (km) *" />
                                <Input v-model.number="form.radius_km" type="number" step="0.01" required />
                            </div>

                            <p v-if="formError" class="text-sm text-red-500 mt-2">{{ formError }}</p>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="isCreateDialogOpen = false">Cancel</Button>
                                <Button type="submit" :disabled="form.processing">Create Radius</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                {{ $page.props.flash.error }}
            </div>

            <!-- Radiuses Table -->
            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>City</TableHead>
                            <TableHead>State</TableHead>
                            <TableHead>Country</TableHead>
                            <TableHead>Radius (km)</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(radius, index) in radiuses" :key="radius.id">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell>{{ radius.city || 'N/A' }}</TableCell>
                            <TableCell>{{ radius.state || 'N/A' }}</TableCell>
                            <TableCell>{{ radius.country || 'N/A' }}</TableCell>
                            <TableCell>{{ radius.radius_km }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(radius)">Edit</Button>
                                    <Button size="sm" variant="destructive" @click="confirmDeleteRadius(radius)">
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
        
        <!-- Edit Dialog -->
        <Dialog v-model:open="isEditDialogOpen">
            <DialogContent class="max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Edit Radius</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="update" class="space-y-4">
                    <div>
                        <InputLabel for="city" value="City" />
                        <select v-model="editForm.city" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Select City</option>
                            <option v-for="city in cities" :value="city" :key="city">
                                {{ city || 'N/A' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="state" value="State" />
                        <select v-model="editForm.state" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Select State</option>
                            <option v-for="state in states" :value="state" :key="state">
                                {{ state || 'N/A' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="country" value="Country" />
                        <select v-model="editForm.country" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Select Country</option>
                            <option v-for="country in countries" :value="country" :key="country">
                                {{ country || 'N/A' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="radius_km" value="Radius (km) *" />
                        <Input v-model.number="editForm.radius_km" type="number" step="0.01" required />
                    </div>

                    <p v-if="editFormError" class="text-sm text-red-500 mt-2">{{ editFormError }}</p>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditDialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="editForm.processing">Update Radius</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
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
    cities: Array,
    states: Array,
    countries: Array,
    radiuses: Array,
});

// State to control dialog visibility
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingRadius = ref(null);
const formError = ref(null);
const editFormError = ref(null);

// Form for creating a new radius
const form = useForm({
    city: '',
    state: '',
    country: '',
    radius_km: null,
});

// Form for editing an existing radius
const editForm = useForm({
    city: '',
    state: '',
    country: '',
    radius_km: null,
});

// Reset form errors when dialogs close
watch(isCreateDialogOpen, (value) => {
    if (!value) formError.value = null;
});

watch(isEditDialogOpen, (value) => {
    if (!value) editFormError.value = null;
});

// Watch for flash messages
watch(() => props.flash, (newFlash) => {
    if (newFlash.error) {
        // Update the appropriate error state based on which dialog is open
        if (isCreateDialogOpen.value) {
            formError.value = newFlash.error;
        } else if (isEditDialogOpen.value) {
            editFormError.value = newFlash.error;
        } else {
            toast.error(newFlash.error, {
                position: 'top-right',
                timeout: 5000,
            });
        }
    }
    
    if (newFlash.success) {
        // Close dialogs on success
        isCreateDialogOpen.value = false;
        isEditDialogOpen.value = false;
        
        toast.success(newFlash.success, {
            position: 'top-right',
            timeout: 3000,
        });
    }
}, { deep: true });

// Open create dialog and reset form
const openCreateDialog = () => {
    form.reset();
    formError.value = null;
    isCreateDialogOpen.value = true;
};

// Open edit dialog and pre-fill form with radius data
const openEditDialog = (radius) => {
    editingRadius.value = radius;
    editForm.city = radius.city || '';
    editForm.state = radius.state || '';
    editForm.country = radius.country || '';
    editForm.radius_km = radius.radius_km;
    editFormError.value = null;
    isEditDialogOpen.value = true;
};

// Submit new radius
const submit = () => {
    formError.value = null;
    form.post(route('radiuses.store'), {
        onSuccess: () => {
            form.reset();
            isCreateDialogOpen.value = false;
            toast.success('Radius created successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error, {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        },
    });
};

// Update existing radius
const update = () => {
    if (!editingRadius.value) return;
    
    editFormError.value = null;
    editForm.put(route('radiuses.update', editingRadius.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            toast.success('Radius updated successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error, {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        },
    });
};

// Confirm and delete radius
const confirmDeleteRadius = (radius) => {
    if (confirm('Are you sure you want to delete this radius?')) {
        deleteRadius(radius.id);
    }
};

const deleteRadius = (id) => {
    router.delete(route('radiuses.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Radius deleted successfully!', {
                position: 'top-right',
                timeout: 3000,
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.error(error, {
                    position: 'top-right',
                    timeout: 5000,
                });
            });
        },
    });
};
</script>

<style scoped>
table th {
    font-size: 0.95rem;
}
table td {
    font-size: 0.875rem;
}
</style>