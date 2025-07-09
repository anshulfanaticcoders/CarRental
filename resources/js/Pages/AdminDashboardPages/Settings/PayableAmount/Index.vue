<template>
    <AdminDashboardLayout>
        <!-- Loader Overlay -->
        <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
            <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
        </div>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Manage Payable Amount Percentage</span>
                <!-- No search input needed for a single setting -->
                <!-- No "Create New" button needed as it's a single setting -->
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Setting Name</TableHead>
                            <TableHead>Current Percentage (%)</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow>
                            <TableCell>Payment Percentage</TableCell>
                            <TableCell>{{ paymentPercentage }}%</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="outline" @click="openEditDialog">Edit</Button>
                                    <!-- Delete is less relevant for a single setting, but included for CRUD consistency -->
                                    <Button variant="destructive" @click="openDeleteDialog">Delete</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Edit Dialog -->
            <Dialog v-model:open="isDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Payable Amount Percentage</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="updateSetting()">
                        <div class="mb-4">
                            <InputLabel for="payment_percentage" value="Payment Percentage (%)" />
                            <TextInput
                                id="payment_percentage"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.payment_percentage"
                                required
                                min="0"
                                max="100"
                                step="0.01"
                            />
                            <InputError class="mt-2" :message="form.errors.payment_percentage" />
                        </div>
                        <div class="mt-4 flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                            <Button type="submit">Update Setting</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to reset the payment percentage to 0%? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete">Reset to 0%</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
        <Toaster />
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input"; // Not directly used but kept for consistency if needed
import { Textarea } from "@/Components/ui/textarea"; // Not directly used but kept for consistency if needed
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
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
import { useToast } from 'vue-toastification';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import loaderVariant from '../../../../../assets/loader-variant.svg'; // Adjust path if necessary

const props = defineProps({
    paymentPercentage: Number,
    errors: Object, // For server-side validation errors
});

const page = usePage();
const toast = useToast();

const paymentPercentage = ref(props.paymentPercentage);
const isDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const form = useForm({
    payment_percentage: paymentPercentage.value,
});

const openEditDialog = () => {
    form.payment_percentage = paymentPercentage.value; // Populate form with current value
    isDialogOpen.value = true;
};

const updateSetting = async () => {
    form.post(route("admin.settings.payable-amount.update"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash && page.props.flash.success) {
                toast.success(page.props.flash.success);
            }
            // Reload the page to get the updated percentage from the backend
            router.reload({ only: ['paymentPercentage'] });
            isDialogOpen.value = false;
        },
        onError: (formErrors) => {
            console.error("Error updating payment percentage:", formErrors);
            Object.values(formErrors).forEach(error => toast.error(error));
        }
    });
};

const openDeleteDialog = () => {
    isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
    // For a single setting, "delete" might mean resetting to a default (e.g., 0)
    // or removing the record entirely. Based on previous feedback, setting to 0.00.
    form.payment_percentage = 0.00; // Set to 0 for "deletion"
    form.post(route("admin.settings.payable-amount.update"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash && page.props.flash.success) {
                toast.success("Payment percentage reset to 0%.");
            }
            router.reload({ only: ['paymentPercentage'] });
            isDeleteDialogOpen.value = false;
        },
        onError: (formErrors) => {
            console.error("Error resetting payment percentage:", formErrors);
            toast.error("Failed to reset payment percentage.");
        }
    });
};

// Watch for changes in props.paymentPercentage to update local ref
watch(() => props.paymentPercentage, (newValue) => {
  paymentPercentage.value = newValue;
});
</script>

<style scoped>
table th{
    font-size: 0.95rem;
}
table td{
    font-size: 0.875rem;
}
</style>
