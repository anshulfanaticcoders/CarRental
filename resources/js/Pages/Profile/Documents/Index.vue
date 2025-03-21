<template>
    <MyProfileLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem] max-[768px]:w-full max-[768px]:ml-0">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold max-[768px]:text-[1.2rem]">My Documents</span>
                <Button 
                    @click="openUploadDialog" 
                    :disabled="documents.length >= 3"
                >
                    Upload Documents
                </Button>
            </div>

            <!-- Upload Dialog -->
            <Dialog v-model:open="isUploadDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Upload Documents</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="uploadDocuments">
                        <div v-for="(doc, index) in documentsToUpload" :key="index" class="mb-4">
                            <Label class="block text-sm font-medium">Document Type</Label>
                            <Input 
                                v-model="doc.type" 
                                class="border p-2 w-full mb-2" 
                                readonly 
                            />
                            <Label class="block text-sm font-medium">Upload Document</Label>
                            <Input 
                                type="file" 
                                @change="(event) => handleFileUpload(event, index)" 
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="border p-2 w-full" 
                            />
                        </div>
                        <DialogFooter>
                            <Button type="submit" :disabled="!isUploadValid">Upload</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Document Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Document</DialogTitle>
                    </DialogHeader>
                    
                    <form @submit.prevent="updateDocument">
                        <div class="mb-4">
                            <Label class="block text-sm font-medium">Upload New Document</Label>
                            <Input 
                                type="file" 
                                @change="handleEditFileUpload" 
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="border p-2 w-full" 
                            />
                        </div>

                        <DialogFooter>
                            <Button type="submit">Update</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table v-if="documents.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Document Type</TableHead>
                            <TableHead>Document Image</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="document in documents" :key="document.id">
                            <TableCell>{{ formatDocumentType(document.document_type) }}</TableCell>
                            <TableCell>
                                <img :src="`${document.document_file}`" alt="Document Image" class="h-20 w-[150px] object-cover max-[768px]:w-[60px] max-[768px]:h-10"/>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(document.verification_status)">
                                    {{ document.verification_status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    @click="openEditDialog(document)"
                                    :disabled="document.verification_status === 'verified'"
                                >
                                    Edit
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div v-else class="p-4 text-center">
                    <p>No documents uploaded yet.</p>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { 
    Table, TableHeader, TableRow, TableHead, 
    TableBody, TableCell 
} from "@/Components/ui/table";

import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";

const documents = ref(usePage().props.documents);
const isUploadDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const currentDocument = ref(null);
const editFile = ref(null);

// Edit document functions
const openEditDialog = (document) => {
    currentDocument.value = document;
    isEditDialogOpen.value = true;
};

const handleEditFileUpload = (event) => {
    editFile.value = event.target.files[0];
};

const updateDocument = () => {
    if (!editFile.value) {
        alert('Please select a file to upload');
        return;
    }

    let formData = new FormData();
    formData.append("document_file", editFile.value);
    formData.append("_method", "PATCH");

    router.post(`/user/documents/${currentDocument.value.id}`, formData, {
        onSuccess: () => {
            // Update the documents list without refreshing the page
            const updatedDocumentUrl = URL.createObjectURL(editFile.value);

            documents.value = documents.value.map(doc => 
                doc.id === currentDocument.value.id 
                ? { ...doc, document_file: updatedDocumentUrl } 
                : doc
            );

            // Close dialog and reset state
            isEditDialogOpen.value = false;
            editFile.value = null;
            currentDocument.value = null;
        }
    });
};


// Document upload functions
const DOCUMENT_TYPES = ['id_proof', 'address_proof', 'driving_license'];

const documentsToUpload = ref([
    { type: 'id_proof', file: null },
    { type: 'address_proof', file: null },
    { type: 'driving_license', file: null }
]);

const isUploadValid = computed(() => {
    return documentsToUpload.value.every(doc => doc.file);
});

const openUploadDialog = () => {
    if (documents.value.length < 3) {
        isUploadDialogOpen.value = true;
    }
};

const handleFileUpload = (event, index) => {
    const file = event.target.files[0];
    documentsToUpload.value[index].file = file;
};

const uploadDocuments = () => {
    if (!isUploadValid.value) {
        alert('Please fill in all document details');
        return;
    }

    const formData = new FormData();
    documentsToUpload.value.forEach((doc, index) => {
        formData.append(`document_type_${index}`, doc.type);
        formData.append(`document_file_${index}`, doc.file);
    });

    router.post('/user/documents/bulk-upload', formData, {
        onSuccess: (response) => {
            isUploadDialogOpen.value = false;
            // Reset upload form
            documentsToUpload.value = [
                { type: 'id_proof', file: null },
                { type: 'address_proof', file: null },
                { type: 'driving_license', file: null }
            ];
            // Update the documents array with the new data
            documents.value = response.props.documents;
        }
    });
};

const formatDocumentType = (type) => {
    return type.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const getStatusBadgeVariant = (status) => {
    return status === "verified" ? "default" : 
           status === "pending" ? "secondary" : "destructive";
};
</script>

<style scoped>
@media screen and (max-width:768px) {
    th{
        font-size: 0.75rem;
    }
    td{
        font-size: 0.75rem;
    }
}
</style>