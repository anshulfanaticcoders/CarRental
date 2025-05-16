<template>
    <Head title="Bulk Vehicle Upload" />
   <AuthenticatedHeaderLayout></AuthenticatedHeaderLayout>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-if="$page.props.flash.message" class="mb-4">
                            <div :class="`p-4 rounded-md ${$page.props.flash.type === 'success' ? 'bg-green-100 text-green-700' : ($page.props.flash.type === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')}`" v-html="$page.props.flash.message">
                            </div>
                        </div>

                        <form @submit.prevent="submitForm">
                            <div class="mb-4">
                                <InputLabel for="csv_file" value="CSV File" />
                                <input type="file" id="csv_file" @change="handleFileChange" class="mt-1 block w-full" accept=".csv,.txt" required />
                                <InputError class="mt-2" :message="form.errors.csv_file" />
                            </div>

                            <div class="flex items-center justify-between mt-4">
                                <a :href="route('vehicles.bulk-upload.template')" class="text-sm text-blue-600 hover:underline">
                                    Download CSV Template
                                </a>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Upload Vehicles
                                </PrimaryButton>
                            </div>
                        </form>
                         <div v-if="form.progress" class="mt-4">
                            <progress :value="form.progress.percentage" max="100">
                                {{ form.progress.percentage }}%
                            </progress>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';

const page = usePage();

const form = useForm({
    csv_file: null,
});

const handleFileChange = (event) => {
    form.csv_file = event.target.files[0];
};

const submitForm = () => {
    form.post(route('vehicles.bulk-upload.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('csv_file');
            // Flash message will be handled by Inertia's $page.props.flash
        },
        onError: (errors) => {
            // Errors will be displayed by InputError component
            if (errors.csv_file) {
                 // Handle specific CSV file error if needed, though InputError should cover it
            }
        }
    });
};
</script>
