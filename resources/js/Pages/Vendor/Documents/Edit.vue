<template>
    <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle>{{ _t('vendorprofilepages', 'edit_vendor_documents_dialog_title') }}</DialogTitle>
            <DialogDescription>
                {{ _t('vendorprofilepages', 'edit_vendor_documents_dialog_description') }}
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="updateDocuments">
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="company_name">{{ _t('vendorprofilepages', 'company_name_form_label') }}</Label>
                        <Input id="company_name" v-model="form.company_name" />
                        <div v-if="form.errors.company_name" class="text-sm text-red-500">
                            {{ form.errors.company_name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="company_phone_number">{{ _t('vendorprofilepages', 'company_phone_form_label') }}</Label>
                        <Input id="company_phone_number" v-model="form.company_phone_number" />
                        <div v-if="form.errors.company_phone_number" class="text-sm text-red-500">
                            {{ form.errors.company_phone_number }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="company_gst_number">{{ _t('vendorprofilepages', 'company_gst_form_label') }}</Label>
                        <Input id="company_gst_number" v-model="form.company_gst_number" />
                        <div v-if="form.errors.company_gst_number" class="text-sm text-red-500">
                            {{ form.errors.company_gst_number }}
                        </div>
                    </div>
                    <div class="space-y-2 max-[768px]:col-span-2">
                        <Label for="company_email">{{ _t('vendorprofilepages', 'company_email_form_label') }}</Label>
                        <Input id="company_email" v-model="form.company_email" />
                        <div v-if="form.errors.company_email" class="text-sm text-red-500">
                            {{ form.errors.company_email }}
                        </div>
                    </div>
                    
                </div>

                <div class="space-y-2">
                    <Label for="company_address">{{ _t('vendorprofilepages', 'company_address_form_label') }}</Label>
                    <Textarea id="company_address" v-model="form.company_address" />
                    <div v-if="form.errors.company_address" class="text-sm text-red-500">
                        {{ form.errors.company_address }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                <!-- Passport Front -->
                <div class="space-y-2">
                    <Label for="passport_front">{{ _t('vendorprofilepages', 'passport_front_form_label') }}</Label>
                    <div class="flex flex-col items-center gap-2 max-[768px]:flex-col">
                        <Input id="passport_front" type="file" @change="handleFileChange('passport_front', $event)" />
                        <Button v-if="document.passport_front" variant="outline" size="sm" class="w-full bg-customPrimaryColor text-white"
                            @click.prevent="viewDocument(document.passport_front)">
                            {{ _t('vendorprofilepages', 'view_current_button') }}
                        </Button>
                    </div>
                    <div v-if="form.errors.passport_front" class="text-sm text-red-500">
                        {{ form.errors.passport_front }}
                    </div>
                </div>

                <!-- Passport Back -->
                <div class="space-y-2">
                    <Label for="passport_back">{{ _t('vendorprofilepages', 'passport_back_form_label') }}</Label>
                    <div class="flex flex-col items-center gap-2 max-[768px]:flex-col">
                        <Input id="passport_back" type="file" @change="handleFileChange('passport_back', $event)" />
                        <Button v-if="document.passport_back" variant="outline" size="sm" class="w-full bg-customPrimaryColor text-white"
                            @click.prevent="viewDocument(document.passport_back)">
                            {{ _t('vendorprofilepages', 'view_current_button') }}
                        </Button>
                    </div>
                    <div v-if="form.errors.passport_back" class="text-sm text-red-500">
                        {{ form.errors.passport_back }}
                    </div>
                </div>
            </div>
            </div>
            <DialogFooter class="max-[768px]:flex max-[768px]:flex-col max-[768px]:gap-3">
                <Button type="button" variant="secondary" @click="$emit('close')">{{ _t('vendorprofilepages', 'cancel_button') }}</Button>
                <Button type="submit" :disabled="form.processing">{{ _t('vendorprofilepages', 'save_changes_button') }}</Button>
            </DialogFooter>
        </form>
    </DialogContent>

    <!-- Separate Warning Dialog -->
    <Dialog v-model:open="isWarningDialogOpen">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>{{ _t('vendorprofilepages', 'warning_dialog_title') }}</DialogTitle>
            </DialogHeader>
            <div class="p-4">
                <p>{{ _t('vendorprofilepages', 'document_update_warning_text') }}</p>
            </div>
            <DialogFooter>
                <Button @click="confirmUpdate">{{ _t('vendorprofilepages', 'proceed_button') }}</Button>
                <Button @click="isWarningDialogOpen = false">{{ _t('vendorprofilepages', 'cancel_button') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, getCurrentInstance } from 'vue';
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
</script>
