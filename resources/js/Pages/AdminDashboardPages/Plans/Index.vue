<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <h4 class="font-semibold">Vehicle Plans Management</h4>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button>Create New Plan</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Vehicle Plan</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <InputLabel for="plan_type" value="Plan Type *" />
                                <Input v-model="form.plan_type" required />
                            </div>
                            <div>
                                <InputLabel for="plan_value" value="Plan Value *" />
                                <Input 
                                    v-model="form.plan_value" 
                                    type="number" 
                                    step="0.01" 
                                    required 
                                />
                            </div>
                            <div>
                                <InputLabel value="Plan Features" />
                                <div 
                                    v-for="(feature, index) in form.features" 
                                    :key="index" 
                                    class="flex items-center gap-2 mb-2"
                                >
                                    <Input 
                                        v-model="form.features[index]" 
                                        placeholder="Enter feature"
                                    />
                                    <Button 
                                        type="button" 
                                        variant="destructive" 
                                        size="sm"
                                        @click="removeFeature(index)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    @click="addFeature"
                                >
                                    Add Feature
                                </Button>
                            </div>
                            <DialogFooter>
                                <Button type="submit">Create Plan</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Vehicle Plan</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="updatePlan" class="space-y-4">
                        <div>
                            <InputLabel for="plan_type" value="Plan Type *" />
                            <Input v-model="editForm.plan_type" required />
                        </div>
                        <div>
                            <InputLabel for="plan_value" value="Plan Value *" />
                            <Input 
                                v-model="editForm.plan_value" 
                                type="number" 
                                step="0.01" 
                                required 
                            />
                        </div>
                        <div>
                            <InputLabel value="Plan Features" />
                            <div 
                                v-for="(feature, index) in editForm.features" 
                                :key="index" 
                                class="flex items-center gap-2 mb-2"
                            >
                                <Input 
                                    v-model="editForm.features[index]" 
                                    placeholder="Enter feature"
                                />
                                <Button 
                                    type="button" 
                                    variant="destructive" 
                                    size="sm"
                                    @click="removeEditFeature(index)"
                                >
                                    Remove
                                </Button>
                            </div>
                            <Button 
                                type="button" 
                                variant="outline" 
                                @click="addEditFeature"
                            >
                                Add Feature
                            </Button>
                        </div>
                        <DialogFooter>
                            <Button type="submit">Update Plan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Plan Type</TableHead>
                            <TableHead>Plan Value</TableHead>
                            <TableHead>Features</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="plan in plans.data" :key="plan.id">
                            <TableCell>{{ plan.plan_type }}</TableCell>
                            <TableCell>${{ Number(plan.plan_value).toFixed(2) }}</TableCell>
                            <TableCell>
                                <div v-if="plan.features && plan.features.length">
                                    <ul class="list-disc list-inside">
                                        <li v-for="(feature, index) in plan.features" :key="index">
                                            {{ feature }}
                                        </li>
                                    </ul>
                                </div>
                                <span v-else>No features</span>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(plan)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deletePlan(plan.id)">
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
import { ref, computed } from "vue";
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
    plans: Object, // Accept paginated plans object
});

const form = ref({
    plan_type: '',
    plan_value: null,
    features: []
});

const isEditDialogOpen = ref(false);
const editForm = ref({});

const addFeature = () => {
    form.value.features.push('');
};

const removeFeature = (index) => {
    form.value.features.splice(index, 1);
};

const addEditFeature = () => {
    editForm.value.features.push('');
};

const removeEditFeature = (index) => {
    editForm.value.features.splice(index, 1);
};

const submitForm = () => {
    router.post("/plans", form.value, {
        onSuccess: () => {
            form.value = {
                plan_type: '',
                plan_value: null,
                features: []
            };
        },
    });
};

const openEditDialog = (plan) => {
    editForm.value = { 
        ...plan,
        features: plan.features || []
    };
    isEditDialogOpen.value = true;
};

const updatePlan = () => {
    router.put(`/plans/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const deletePlan = (id) => {
    router.delete(`/plans/${id}`);
};
</script>
