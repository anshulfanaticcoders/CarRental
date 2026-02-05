<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    driving_license: null,
    id_proof: null,
    address_proof: null,
});

// Function to submit individual documents
const submitDocument = (documentType) => {
    const data = new FormData();
    data.append(documentType, form[documentType]);

    // Post the document to the server
    form.post(route('documents.upload'), {
        data,
        onSuccess: () => {
            form.reset(); 
        },
        onError: () => {
            
        },
    });
};
</script>
<template>
<MyProfileLayout>
    <Card>
        <CardHeader>
            <CardTitle>Travel Documents</CardTitle>
            <CardDescription>Upload the required documents to complete your profile.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
            <div class="rounded-xl border border-slate-200 p-4">
                <h5 class="font-semibold text-slate-900">Driving Licence</h5>
                <p class="text-sm text-slate-500">Upload a clear photo of both sides of your driving license.</p>
                <input
                    type="file"
                    @change="e => form.driving_license = e.target.files[0]"
                    accept="image/*,application/pdf"
                    class="mt-3 block w-full text-sm"
                />
                <InputError :message="form.errors.driving_license" />
                <Button class="mt-4" :disabled="form.processing" @click.prevent="submitDocument('driving_license')">Upload Driving License</Button>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <h5 class="font-semibold text-slate-900">Passport/Identity Card (Both Sides)</h5>
                <p class="text-sm text-slate-500">Upload the photo page of your passport or both sides of your ID.</p>
                <input
                    type="file"
                    @change="e => form.id_proof = e.target.files[0]"
                    accept="image/*,application/pdf"
                    class="mt-3 block w-full text-sm"
                />
                <InputError :message="form.errors.id_proof" />
                <Button class="mt-4" :disabled="form.processing" @click.prevent="submitDocument('id_proof')">Upload ID Proof</Button>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <h5 class="font-semibold text-slate-900">Proof of Address</h5>
                <p class="text-sm text-slate-500">Upload a proof of address issued within 3 months.</p>
                <input
                    type="file"
                    @change="e => form.address_proof = e.target.files[0]"
                    accept="image/*,application/pdf"
                    class="mt-3 block w-full text-sm"
                />
                <InputError :message="form.errors.address_proof" />
                <Button class="mt-4" :disabled="form.processing" @click.prevent="submitDocument('address_proof')">Upload Address Proof</Button>
            </div>
        </CardContent>
    </Card>
</MyProfileLayout>
</template>


<style>
/* Add custom styling here if needed */
</style>
