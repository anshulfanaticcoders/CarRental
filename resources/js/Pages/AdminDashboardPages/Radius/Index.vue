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
                                <Input v-model="form.city" type="text" placeholder="Enter city" />
                            </div>

                            <div>
                                <InputLabel for="state" value="State" />
                                <Input v-model="form.state" type="text" placeholder="Enter state" />
                            </div>

                            <div>
                                <InputLabel for="country" value="Country" />
                                <Input v-model="form.country" type="text" placeholder="Enter country" />
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

            <!-- Search Box -->
            <!-- Search Box -->
<div class="mt-2 mb-2">
    <div class="flex gap-2">
        <div class="flex-1">
            <Input 
                v-model="search" 
                type="text" 
                placeholder="Search by city, state, or country..." 
                class="w-full"
            />
        </div>
        <Button variant="outline" @click="resetSearch" v-if="search">Clear</Button>
    </div>
</div>

            <!-- Combinations Table -->
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
                        <TableRow v-for="(combination, index) in combinations.data" :key="index">
                            <TableCell>{{ (combinations.current_page - 1) * combinations.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ combination.city || 'N/A' }}</TableCell>
                            <TableCell>{{ combination.state || 'N/A' }}</TableCell>
                            <TableCell>{{ combination.country || 'N/A' }}</TableCell>
                            <TableCell>
                                {{ getRadiusForCombination(combination.city, combination.state, combination.country) || 'N/A' }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(combination)">Edit</Button>
                                    <Button size="sm" variant="destructive" @click="confirmDeleteRadius(combination)">
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="combinations.data.length === 0">
                            <TableCell colspan="6" class="text-center py-4">No data found</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <!-- Pagination Controls -->
                <div class="flex justify-between items-center mt-4">
                    <div>
                        Showing {{ combinations.from || 0 }} to {{ combinations.to || 0 }} of {{ combinations.total }} entries
                    </div>
                    <div class="flex gap-2">
                        <Button
                            :disabled="!combinations.prev_page_url"
                            @click="navigateToPage(combinations.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            :disabled="!combinations.next_page_url"
                            @click="navigateToPage(combinations.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
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
                        <Input v-model="editForm.city" type="text" placeholder="Enter city" />
                    </div>

                    <div>
                        <InputLabel for="state" value="State" />
                        <Input v-model="editForm.state" type="text" placeholder="Enter state" />
                    </div>

                    <div>
                        <InputLabel for="country" value="Country" />
                        <Input v-model="editForm.country" type="text" placeholder="Enter country" />
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
import { ref, watch, onMounted } from 'vue';
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
import { debounce } from 'lodash';

const toast = useToast();
const props = defineProps({
    combinations: Object,
    radiuses: Array,
    filters: Object,
});

// State to control dialog visibility
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingRadius = ref(null);
const formError = ref(null);
const editFormError = ref(null);
const search = ref('');

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

// Initialize search from props
onMounted(() => {
    if (props.filters && props.filters.search) {
        search.value = props.filters.search;
    }
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
    if (newFlash && newFlash.error) {
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
    
    if (newFlash && newFlash.success) {
        isCreateDialogOpen.value = false;
        isEditDialogOpen.value = false;
        
        toast.success(newFlash.success, {
            position: 'top-right',
            timeout: 3000,
        });
    }
}, { deep: true });

// Search functionality
const performSearch = debounce(() => {
    router.get(
        route('radiuses.index'),
        {
            search: search.value,
            page: 1, // Reset to first page when searching
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}, 300);

watch(search, () => {
    performSearch();
});



// Reset search
const resetSearch = () => {
    search.value = '';
    performSearch();
};

// Open create dialog and reset form
const openCreateDialog = () => {
    form.reset();
    formError.value = null;
    isCreateDialogOpen.value = true;
};

// Open edit dialog and pre-fill form with combination data
const openEditDialog = (combination) => {
    editingRadius.value = combination;
    editForm.city = combination.city || '';
    editForm.state = combination.state || '';
    editForm.country = combination.country || '';
    editForm.radius_km = getRadiusForCombination(combination.city, combination.state, combination.country) || null;
    editFormError.value = null;
    isEditDialogOpen.value = true;
};

// Get radius for a specific combination
const getRadiusForCombination = (city, state, country) => {
    const radius = props.radiuses.find(r => 
        (r.city === city || (!r.city && !city)) &&
        (r.state === state || (!r.state && !state)) &&
        (r.country === country || (!r.country && !country))
    );
    return radius ? radius.radius_km : null;
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
    const existingRadius = props.radiuses.find(r => 
        (r.city === editingRadius.value.city || (!r.city && !editingRadius.value.city)) &&
        (r.state === editingRadius.value.state || (!r.state && !editingRadius.value.state)) &&
        (r.country === editingRadius.value.country || (!r.country && !editingRadius.value.country))
    );
    
    if (existingRadius) {
        editForm.put(route('radiuses.update', existingRadius.id), {
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
    } else {
        // If no existing radius, create a new one
        form.city = editForm.city;
        form.state = editForm.state;
        form.country = editForm.country;
        form.radius_km = editForm.radius_km;
        submit();
    }
};

// Confirm and delete radius
const confirmDeleteRadius = (combination) => {
    const existingRadius = props.radiuses.find(r => 
        (r.city === combination.city || (!r.city && !combination.city)) &&
        (r.state === combination.state || (!r.state && !combination.state)) &&
        (r.country === combination.country || (!r.country && !combination.country))
    );
    
    if (existingRadius && confirm('Are you sure you want to delete this radius?')) {
        deleteRadius(existingRadius.id);
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

// Navigate to a specific page
const navigateToPage = (page) => {
    router.get(route('radiuses.index'), { 
        page,
        search: search.value, // Preserve search when navigating
    }, {
        preserveState: true,
        preserveScroll: true,
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