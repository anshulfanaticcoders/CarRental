<template>
    <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
            <DialogTitle>Edit Vendor Documents</DialogTitle>
            <DialogDescription>
                Update your vendor documents and profile information here.
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="updateDocuments">
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="company_name">Company Name</Label>
                        <Input id="company_name" v-model="form.company_name" />
                        <div v-if="form.errors.company_name" class="text-sm text-red-500">
                            {{ form.errors.company_name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="company_phone_number">Company Phone</Label>
                        <Input id="company_phone_number" v-model="form.company_phone_number" />
                        <div v-if="form.errors.company_phone_number" class="text-sm text-red-500">
                            {{ form.errors.company_phone_number }}
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="company_email">Company Email</Label>
                        <Input id="company_email" v-model="form.company_email" />
                        <div v-if="form.errors.company_email" class="text-sm text-red-500">
                            {{ form.errors.company_email }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="company_gst_number">GST Number</Label>
                        <Input id="company_gst_number" v-model="form.company_gst_number" />
                        <div v-if="form.errors.company_gst_number" class="text-sm text-red-500">
                            {{ form.errors.company_gst_number }}
                        </div>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <Label for="company_address">Company Address</Label>
                    <Textarea id="company_address" v-model="form.company_address" />
                    <div v-if="form.errors.company_address" class="text-sm text-red-500">
                        {{ form.errors.company_address }}
                    </div>
                </div>
                
                <div class="space-y-2">
                    <Label for="driving_license_front">Driving License Front</Label>
                    <div class="flex items-center gap-2">
                        <Input id="driving_license_front" type="file" @change="handleFileChange('driving_license_front', $event)" />
                        <Button v-if="document.driving_license_front" variant="outline" size="sm" @click.prevent="viewDocument(document.driving_license_front)">
                            View Current
                        </Button>
                    </div>
                    <div v-if="form.errors.driving_license_front" class="text-sm text-red-500">
                        {{ form.errors.driving_license_front }}
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="driving_license_back">Driving License Back</Label>
                    <div class="flex items-center gap-2">
                        <Input id="driving_license_back" type="file" @change="handleFileChange('driving_license_back', $event)" />
                        <Button v-if="document.driving_license_back" variant="outline" size="sm" @click.prevent="viewDocument(document.driving_license_back)">
                            View Current
                        </Button>
                    </div>
                    <div v-if="form.errors.driving_license_back" class="text-sm text-red-500">
                        {{ form.errors.driving_license_back }}
                    </div>
                </div>
                
                <!-- Passport Front -->
<div class="space-y-2">
    <Label for="passport_front">Passport Front</Label>
    <div class="flex items-center gap-2">
        <Input 
            id="passport_front" 
            type="file" 
            @change="handleFileChange('passport_front', $event)" 
        />
        <Button 
            v-if="document.passport_front" 
            variant="outline" 
            size="sm" 
            @click.prevent="viewDocument(document.passport_front)"
        >
            View Current
        </Button>
    </div>
    <div v-if="form.errors.passport_front" class="text-sm text-red-500">
        {{ form.errors.passport_front }}
    </div>
</div>

<!-- Passport Back -->
<div class="space-y-2">
    <Label for="passport_back">Passport Back</Label>
    <div class="flex items-center gap-2">
        <Input 
            id="passport_back" 
            type="file" 
            @change="handleFileChange('passport_back', $event)" 
        />
        <Button 
            v-if="document.passport_back" 
            variant="outline" 
            size="sm" 
            @click.prevent="viewDocument(document.passport_back)"
        >
            View Current
        </Button>
    </div>
    <div v-if="form.errors.passport_back" class="text-sm text-red-500">
        {{ form.errors.passport_back }}
    </div>
</div>
            </div>
            <DialogFooter>
                <Button type="button" variant="secondary" @click="$emit('close')">Cancel</Button>
                <Button type="submit" :disabled="form.processing">Save Changes</Button>
            </DialogFooter>
        </form>
    </DialogContent>

    <!-- Separate Warning Dialog -->
    <Dialog v-model:open="isWarningDialogOpen">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>Warning</DialogTitle>
            </DialogHeader>
            <div class="p-4">
                <p>If you change the driving license, passport photo, or passport, you will not be able to create a vehicle listing because it will go to pending status.</p>
            </div>
            <DialogFooter>
                <Button @click="confirmUpdate">Proceed</Button>
                <Button @click="isWarningDialogOpen = false">Cancel</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
    Dialog,
    DialogDescription,
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogFooter 
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import Button from '@/Components/ui/button/Button.vue';

const props = defineProps({
    document: Object,
});

const emit = defineEmits(['close']);

const isWarningDialogOpen = ref(false);
const documentToUpdate = ref(null);
const fileEvent = ref(null);

const form = useForm({
    company_name: props.document.vendor_profile?.company_name || '',
    company_phone_number: props.document.vendor_profile?.company_phone_number || '',
    company_email: props.document.vendor_profile?.company_email || '',
    company_address: props.document.vendor_profile?.company_address || '',
    company_gst_number: props.document.vendor_profile?.company_gst_number || '',
    driving_license_front: null,
    driving_license_back: null,
    passport_front: null,
    passport_back: null,
    // passport_photo: null,
});

const viewDocument = (path) => {
    window.open(`${path}`, '_blank');
};

const handleFileChange = (fileType, event) => {
    documentToUpdate.value = fileType;
    fileEvent.value = event;
    isWarningDialogOpen.value = true;
};

const confirmUpdate = () => {
    isWarningDialogOpen.value = false;
    if (fileEvent.value) {
        form[documentToUpdate.value] = fileEvent.value.target.files[0];
        fileEvent.value = null;
    }
};

const updateDocuments = () => {
    form.post('/vendor/update', {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>