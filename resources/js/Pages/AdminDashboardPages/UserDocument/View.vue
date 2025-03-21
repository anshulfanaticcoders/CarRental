<template>
    <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
            <DialogTitle>Document Details</DialogTitle>
            <DialogDescription>
                View document information
            </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">User</Label>
                    <div>{{ document.user.first_name }} {{ document.user.last_name }}</div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Email</Label>
                    <div>{{ document.user.email }}</div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Document Type</Label>
                    <div>{{ formatDocumentType(document.document_type) }}</div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Document Number</Label>
                    <div>{{ document.document_number }}</div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Status</Label>
                    <div>
                        <Badge :variant="getStatusBadgeVariant(document.verification_status)">
                            {{ document.verification_status }}
                        </Badge>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Submitted On</Label>
                    <div>{{ formatDate(document.created_at) }}</div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Verified On</Label>
                    <div>{{ document.verified_at ? formatDate(document.verified_at) : 'Not verified yet' }}</div>
                </div>
                
                <div class="space-y-1">
                    <Label class="text-sm font-medium text-gray-500">Expires On</Label>
                    <div>{{ document.expires_at ? formatDate(document.expires_at) : 'No expiration date' }}</div>
                </div>
            </div>
            
            <div class="space-y-2 mt-4">
                <Label class="text-sm font-medium text-gray-500">Document Preview</Label>
                <div class="border rounded-md p-2 flex justify-center">
                    <img :src="document.document_file" alt="Document" class="max-h-[300px] object-contain" />
                </div>
            </div>
        </div>
        <DialogFooter>
            <Button @click="$emit('close')">Close</Button>
        </DialogFooter>
    </DialogContent>
</template>

<script setup>
import { 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription, 
    DialogFooter 
} from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import Button from "@/Components/ui/button/Button.vue";
import Badge from "@/Components/ui/badge/Badge.vue";

const props = defineProps({
    document: Object,
});

const emit = defineEmits(['close']);

const formatDocumentType = (type) => {
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'verified':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'rejected':
            return 'destructive';
        default:
            return 'default';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};
</script>