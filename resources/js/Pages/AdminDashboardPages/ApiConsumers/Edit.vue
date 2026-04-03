<template>
    <AdminDashboardLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-8">
                        <div class="flex items-center space-x-4 mb-8">
                            <div class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center">
                                <Pencil class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Edit API Consumer</h1>
                                <p class="text-gray-600">Update details for {{ consumer.name }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Company Name <span class="text-red-500">*</span></label>
                                    <Input id="name" v-model="form.name" type="text" required class="w-full" />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-2">Contact Name <span class="text-red-500">*</span></label>
                                    <Input id="contact_name" v-model="form.contact_name" type="text" required class="w-full" />
                                    <p v-if="form.errors.contact_name" class="mt-1 text-sm text-red-600">{{ form.errors.contact_name }}</p>
                                </div>

                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">Contact Email <span class="text-red-500">*</span></label>
                                    <Input id="contact_email" v-model="form.contact_email" type="email" required class="w-full" />
                                    <p v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_email }}</p>
                                </div>

                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                    <Input id="contact_phone" v-model="form.contact_phone" type="tel" class="w-full" />
                                    <p v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</p>
                                </div>

                                <div>
                                    <label for="company_url" class="block text-sm font-medium text-gray-700 mb-2">Company URL</label>
                                    <Input id="company_url" v-model="form.company_url" type="url" class="w-full" />
                                    <p v-if="form.errors.company_url" class="mt-1 text-sm text-red-600">{{ form.errors.company_url }}</p>
                                </div>

                                <div>
                                    <label for="plan" class="block text-sm font-medium text-gray-700 mb-2">Plan <span class="text-red-500">*</span></label>
                                    <Select v-model="form.plan">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Select a plan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in plans" :key="p" :value="p">{{ p.charAt(0).toUpperCase() + p.slice(1) }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.plan" class="mt-1 text-sm text-red-600">{{ form.errors.plan }}</p>
                                </div>

                                <div>
                                    <label for="rate_limit" class="block text-sm font-medium text-gray-700 mb-2">Rate Limit (req/min)</label>
                                    <Input id="rate_limit" v-model.number="form.rate_limit" type="number" min="1" class="w-full" />
                                    <p class="mt-1 text-xs text-gray-500">Default for {{ form.plan }}: {{ defaultRateLimit[form.plan] }} req/min</p>
                                    <p v-if="form.errors.rate_limit" class="mt-1 text-sm text-red-600">{{ form.errors.rate_limit }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                    <Textarea id="notes" v-model="form.notes" rows="3" class="w-full" placeholder="Internal notes about this consumer..." />
                                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                <div class="flex items-center gap-4">
                                    <Button type="submit" :disabled="form.processing">
                                        {{ form.processing ? 'Saving...' : 'Update Consumer' }}
                                    </Button>
                                    <Link :href="route('admin.api-consumers.show', consumer.id)">
                                        <Button type="button" variant="outline">Cancel</Button>
                                    </Link>
                                </div>
                                <div v-if="form.processing" class="flex items-center text-blue-600">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm font-medium">Saving...</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Pencil } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    consumer: Object,
    plans: Array,
    defaultRateLimit: Object,
});

const form = useForm({
    name: props.consumer.name,
    contact_name: props.consumer.contact_name,
    contact_email: props.consumer.contact_email,
    contact_phone: props.consumer.contact_phone ?? '',
    company_url: props.consumer.company_url ?? '',
    plan: props.consumer.plan,
    rate_limit: props.consumer.rate_limit,
    notes: props.consumer.notes ?? '',
});

watch(() => form.plan, (newPlan, oldPlan) => {
    if (oldPlan && newPlan !== oldPlan) {
        form.rate_limit = props.defaultRateLimit?.[newPlan] ?? form.rate_limit;
    }
});

const submitForm = () => {
    form.put(route('admin.api-consumers.update', props.consumer.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('API consumer updated successfully!', { position: 'top-right', timeout: 3000 });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            if (firstError) toast.error(firstError, { position: 'top-right', timeout: 5000 });
        },
    });
};
</script>
