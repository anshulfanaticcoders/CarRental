<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import InputError from '@/Components/InputError.vue';
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
    <div class="">
        <h4 class="text-2xl ml-[1rem] text-customPrimaryColor mb-[2rem] font-medium">Travel Documents</h4>
        <h2 class="text-[1.75rem] font-medium text-gray-900 mb-[1rem] border-b-2">Some of Your Documents are Missing</h2>
        <div class="space-y-8 w-[24rem]">
            <!-- Driving License -->
            <div>
                <h5 class="font-medium text-[1.15rem] mb-[0.75rem]">Driving Licence</h5>
                <p class="text-sm text-gray-500">Upload a clear photo of both sides of your driving license.</p>
                <input
                    type="file"
                    @change="e => form.driving_license = e.target.files[0]"
                    accept="image/*,application/pdf"
                    class="mt-2 block w-full text-sm"
                />
                <InputError :message="form.errors.driving_license" />
                <button class="button-secondary mt-[1rem]" :disabled="form.processing" @click.prevent="submitDocument('driving_license')">Upload Driving License</button>
            </div>

            <!-- ID Proof -->
            <div>
                <h5 class="font-medium text-[1.15rem] mb-[0.75rem]">Passport/Identity Card (Both Sides)</h5>
                <p class="text-sm text-gray-500">Upload the photo page of your passport or both sides of your ID.</p>
                <input
                    type="file"
                    @change="e => form.id_proof = e.target.files[0]"
                    accept="image/*,application/pdf"
                    class="mt-2 block w-full text-sm"
                />
                <InputError :message="form.errors.id_proof" />
                <button class="mt-[1rem] button-secondary" :disabled="form.processing" @click.prevent="submitDocument('id_proof')">Upload ID Proof</button>
            </div>

            <!-- Address Proof -->
            <div>
                <h5 class="font-medium text-[1.15rem] mb-[0.75rem]">Proof of Address</h5>
                <p class="text-sm text-gray-500">Upload a clear photo of a proof of address (utility bill), issued less than 3 months ago. This must be a utility bill (electricity, gas or water) or a recent council tax bill, on which your full name, current address and date are clearly visible.</p>
                <input
                    type="file"
                    @change="e => form.address_proof = e.target.files[0]"
                    accept="image/*,application/pdf"
                    class="mt-2 block w-full text-sm"
                />
                <InputError :message="form.errors.address_proof" />
                <button class="mt-[1rem] button-secondary" :disabled="form.processing" @click.prevent="submitDocument('address_proof')">Upload Address Proof</button>
            </div>
        </div>
    </div>
</MyProfileLayout>
</template>


<style>
/* Add custom styling here if needed */
</style>