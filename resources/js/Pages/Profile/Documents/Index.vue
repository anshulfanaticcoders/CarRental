<template>
    <MyProfileLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem] max-[480px]:w-full max-[480px]:ml-0">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold max-[480px]:text-[1.2rem]">My Documents</span>
                <Button 
                    @click="openUploadDialog" 
                    :disabled="documents.length >= 3"
                >
                    Upload Documents
                </Button>
            </div>

            <Dialog v-model:open="isUploadDialogOpen">
                <DialogContent class="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>Upload Documents</DialogTitle>
                        <DialogDescription>
                            You can upload up to 3 documents. Currently uploaded: {{ documents.length }}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <div 
                            v-for="(doc, index) in documentsToUpload" 
                            :key="index" 
                            class="grid grid-cols-4 items-center gap-4"
                        >
                            <Label class="text-right">Document {{ index + 1 }}</Label>
                            <Select 
                                v-model="doc.type" 
                                @update:modelValue="validateDocumentTypes"
                            >
                                <SelectTrigger class="col-span-2">
                                    <SelectValue placeholder="Select Document Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="type in availableDocumentTypes" 
                                        :key="type" 
                                        :value="type"
                                    >
                                        {{ formatDocumentType(type) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Input 
                                type="file" 
                                @change="event => handleFileUpload(event, index)"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="col-span-2"
                            />
                        </div>
                    </div>
                    
                    <DialogFooter>
                        <Button 
                            type="submit" 
                            @click="uploadDocuments"
                            :disabled="!isUploadValid"
                        >
                            Upload Documents
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table v-if="documents.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Document Type</TableHead>
                            <TableHead>Document Image</TableHead>
                            <TableHead>Status</TableHead>
                           
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="document in documents" :key="document.id">
                            <TableCell>{{ formatDocumentType(document.document_type) }}</TableCell>
                            <TableCell>
                                <img :src="`${document.document_file}`" alt="Document Image" class="h-20 w-[150px] object-cover max-[480px]:w-[60px] max-[480px]:h-10"/>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(document.verification_status)">
                                    {{ document.verification_status }}
                                </Badge>
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";

const documents = ref(usePage().props.documents);
const isUploadDialogOpen = ref(false);

const DOCUMENT_TYPES = ['id_proof', 'address_proof', 'driving_license'];

const documentsToUpload = ref([
    { type: null, file: null },
    { type: null, file: null },
    { type: null, file: null }
]);

const availableDocumentTypes = computed(() => {
    const usedTypes = documentsToUpload.value.map(doc => doc.type).filter(Boolean);
    return DOCUMENT_TYPES.filter(type => 
        !usedTypes.includes(type) && 
        !documents.value.some(doc => doc.document_type === type)
    );
});

const isUploadValid = computed(() => {
    return documentsToUpload.value.every(doc => 
        doc.type && doc.file
    );
});

const openUploadDialog = () => {
    if (documents.value.length < 3) {
        isUploadDialogOpen.value = true;
    }
};

const validateDocumentTypes = () => {
    // Ensure no duplicate document types are selected
    const types = documentsToUpload.value.map(doc => doc.type).filter(Boolean);
    const uniqueTypes = new Set(types);
    if (types.length !== uniqueTypes.size) {
        alert('Please select unique document types');
        return false;
    }
    return true;
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
        onSuccess: () => {
            isUploadDialogOpen.value = false;
            // Reset upload form
            documentsToUpload.value = [
                { type: null, file: null },
                { type: null, file: null },
                { type: null, file: null }
            ];
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

@media screen and (max-width:480px) {
    
    th{
        font-size: 0.75rem;
    }
    td{
        font-size: 0.75rem;
    }
   
}
</style>