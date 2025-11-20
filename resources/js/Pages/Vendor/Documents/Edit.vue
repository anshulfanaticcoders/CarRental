<template>
    <DialogContent class="sm:max-w-[700px] max-h-[95vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
                <Edit class="w-5 h-5" />
                {{ _t('vendorprofilepages', 'edit_vendor_documents_dialog_title') }}
            </DialogTitle>
            <DialogDescription>
                {{ _t('vendorprofilepages', 'edit_vendor_documents_dialog_description') }}
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="updateDocuments" class="space-y-6">
            <!-- Company Information Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Building class="w-5 h-5" />
                    Company Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="company_name" class="flex items-center gap-2">
                            <Building class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'company_name_form_label') }}
                        </Label>
                        <Input id="company_name" v-model="form.company_name" placeholder="Enter company name" />
                        <div v-if="form.errors.company_name" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.company_name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="company_phone_number" class="flex items-center gap-2">
                            <Phone class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'company_phone_form_label') }}
                        </Label>
                        <Input id="company_phone_number" v-model="form.company_phone_number" placeholder="Enter phone number" />
                        <div v-if="form.errors.company_phone_number" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.company_phone_number }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="company_gst_number" class="flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'company_gst_form_label') }}
                        </Label>
                        <Input id="company_gst_number" v-model="form.company_gst_number" placeholder="Enter GST/VAT number" />
                        <div v-if="form.errors.company_gst_number" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.company_gst_number }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="company_email" class="flex items-center gap-2">
                            <Mail class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'company_email_form_label') }}
                        </Label>
                        <Input id="company_email" type="email" v-model="form.company_email" placeholder="Enter company email" />
                        <div v-if="form.errors.company_email" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.company_email }}
                        </div>
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="company_address" class="flex items-center gap-2">
                            <MapPin class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'company_address_form_label') }}
                        </Label>
                        <Textarea id="company_address" v-model="form.company_address" placeholder="Enter company address" rows="3" />
                        <div v-if="form.errors.company_address" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.company_address }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <FileText class="w-5 h-5" />
                    Document Uploads
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Passport Front -->
                    <div class="space-y-2">
                        <Label for="passport_front" class="flex items-center gap-2">
                            <Upload class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'passport_front_form_label') }}
                        </Label>
                        <div class="space-y-3">
                            <Input id="passport_front" type="file" @change="handleFileChange('passport_front', $event)" accept="image/*,.pdf" class="cursor-pointer" />
                            <div v-if="document.passport_front" class="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click.prevent="viewDocument(document.passport_front)"
                                    class="flex items-center gap-1"
                                >
                                    <Eye class="w-3 h-3" />
                                    {{ _t('vendorprofilepages', 'view_current_button') }}
                                </Button>
                            </div>
                        </div>
                        <div v-if="form.errors.passport_front" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.passport_front }}
                        </div>
                    </div>

                    <!-- Passport Back -->
                    <div class="space-y-2">
                        <Label for="passport_back" class="flex items-center gap-2">
                            <Upload class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'passport_back_form_label') }}
                        </Label>
                        <div class="space-y-3">
                            <Input id="passport_back" type="file" @change="handleFileChange('passport_back', $event)" accept="image/*,.pdf" class="cursor-pointer" />
                            <div v-if="document.passport_back" class="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click.prevent="viewDocument(document.passport_back)"
                                    class="flex items-center gap-1"
                                >
                                    <Eye class="w-3 h-3" />
                                    {{ _t('vendorprofilepages', 'view_current_button') }}
                                </Button>
                            </div>
                        </div>
                        <div v-if="form.errors.passport_back" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ form.errors.passport_back }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Summary -->
            <div v-if="hasErrors" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="font-medium">Please fix the errors above before submitting.</p>
            </div>

            <DialogFooter class="flex gap-2 md:flex-row flex-col">
                <Button type="button" variant="outline" @click="$emit('close')" class="flex items-center gap-2">
                    <X class="w-4 h-4" />
                    {{ _t('vendorprofilepages', 'cancel_button') }}
                </Button>
                <Button type="submit" :disabled="form.processing" class="flex items-center gap-2">
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <Save v-else class="w-4 h-4" />
                    {{ _t('vendorprofilepages', 'save_changes_button') }}
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>

    <!-- Warning Alert Dialog -->
    <AlertDialog v-model:open="isWarningDialogOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-yellow-500" />
                    {{ _t('vendorprofilepages', 'warning_dialog_title') }}
                </AlertDialogTitle>
                <AlertDialogDescription>
                    {{ _t('vendorprofilepages', 'document_update_warning_text') }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter class="flex gap-2">
                <AlertDialogCancel @click="isWarningDialogOpen = false" class="flex items-center gap-2">
                    <X class="w-4 h-4" />
                    {{ _t('vendorprofilepages', 'cancel_button') }}
                </AlertDialogCancel>
                <AlertDialogAction @click="confirmUpdate" class="flex items-center gap-2">
                    <CheckCircle class="w-4 h-4" />
                    {{ _t('vendorprofilepages', 'proceed_button') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script setup>
import { ref, getCurrentInstance, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogDescription,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/Components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import Button from '@/Components/ui/button/Button.vue';
import {
    Edit,
    Building,
    Phone,
    Mail,
    FileText,
    MapPin,
    Upload,
    Eye,
    X,
    Save,
    Loader2,
    AlertTriangle,
    CheckCircle,
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

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

// Computed property to check if there are any errors
const hasErrors = computed(() => {
    return Object.keys(form.errors).length > 0;
});
</script>
