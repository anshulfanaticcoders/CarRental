<template>
    <MyProfileLayout>
        <div class="flex flex-col gap-4 w-[95%] max-[768px]:w-full max-[768px]:ml-0">
            
                <p class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]">My Vendor Documents</p>

            <Dialog v-model:open="isEditDialogOpen">
                <EditDocument :document="document" @close="isEditDialogOpen = false" />
            </Dialog>

            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <div v-if="document">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Driving License Front</TableHead>
                                <TableHead>Driving License Back</TableHead>
                                <TableHead>Passport Front</TableHead>
                                <TableHead>Passport Back</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell>
                                    <img :src="`${document.driving_license_front}`" alt="Driving License" class="w-[150px] h-[100px] mb-2 object-cover max-[768px]:hidden">
                                    <Button v-if="document.driving_license_front" variant="outline" size="sm" @click="viewDocument(document.driving_license_front)" class="w-[150px]  max-[768px]:w-full">
                                        View
                                    </Button>
                                    <span v-else>Not uploaded</span>
                                </TableCell>
                                <TableCell>
                                    <img :src="`${document.driving_license_back}`" alt="Driving License" class="w-[150px] h-[100px] mb-2 object-cover max-[768px]:hidden">
                                    <Button v-if="document.driving_license_back" variant="outline" size="sm" @click="viewDocument(document.driving_license_back)" class="w-[150px]  max-[768px]:w-full">
                                        View
                                    </Button>
                                    <span v-else>Not uploaded</span>
                                </TableCell>
                                <TableCell>
                                    <img :src="`${document.passport_front}`" alt="Passport" class="w-[150px] h-[100px] mb-2 object-cover max-[768px]:hidden">
                                    <Button v-if="document.passport_front" variant="outline" size="sm" @click="viewDocument(document.passport_front)" class="w-[150px]  max-[768px]:w-full">
                                        View
                                    </Button>
                                    <span v-else>Not uploaded</span>
                                </TableCell>
                                <TableCell>
                                    <img :src="`${document.passport_back}`" alt="Passport" class="w-[150px] h-[100px] mb-2 object-cover max-[768px]:hidden">
                                    <Button v-if="document.passport_back" variant="outline" size="sm" @click="viewDocument(document.passport_back)" class="w-[150px]  max-[768px]:w-full">
                                        View
                                    </Button>
                                    <span v-else>Not uploaded</span>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusBadgeVariant(document.vendor_profile?.status)">
                                        {{ document.vendor_profile?.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="openEditDialog()">
                                            Edit
                                            <img :src="editIcon" alt="Edit">
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-else class="p-4 text-center">
                    <p>No vendor documents found. Please complete your vendor registration first.</p>
                </div>
            </div>
            
            <div v-if="document && document.vendor_profile" class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <h2 class="text-lg font-semibold mb-4">Company Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="font-medium text-sm">Company Name:</p>
                        <p>{{ document.vendor_profile.company_name }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-sm">Phone Number:</p>
                        <p>{{ document.vendor_profile.company_phone_number }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-sm">Email:</p>
                        <p>{{ document.vendor_profile.company_email }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-sm">VAT Number:</p>
                        <p>{{ document.vendor_profile.company_gst_number }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="font-medium text-sm">Address:</p>
                        <p>{{ document.vendor_profile.company_address }}</p>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref } from "vue";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import editIcon from "../../../../assets/Pencil.svg";
import { Dialog } from "@/Components/ui/dialog";
import EditDocument from "@/Pages/Vendor/Documents/Edit.vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";

const props = defineProps({
    document: Object,
});

const isEditDialogOpen = ref(false);

const openEditDialog = () => {
    isEditDialogOpen.value = true;
};

const viewDocument = (path) => {
    window.open(`${path}`, '_blank');
};

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'approved': return 'default';
        case 'pending': return 'secondary';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

</script>

<style scoped>

@media screen and (max-width:768px) {
    
    th{
        font-size: 0.75rem;
    }
    td{
        padding: 0.5rem;
    }
}
</style>