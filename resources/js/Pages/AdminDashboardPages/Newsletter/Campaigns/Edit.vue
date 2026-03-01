<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6 max-w-4xl">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Edit Campaign</h1>
                <Button variant="outline" @click="router.visit('/admin/newsletter-campaigns')">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Back
                </Button>
            </div>

            <div class="rounded-xl border bg-white p-6 space-y-6">
                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <Input
                        id="subject"
                        v-model="form.subject"
                        placeholder="Enter email subject line"
                        class="h-12 text-base"
                    />
                    <p v-if="form.errors.subject" class="mt-2 text-sm text-red-600">
                        {{ form.errors.subject }}
                    </p>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
                    <strong>Available variables:</strong> <code class="bg-blue-100 px-1.5 py-0.5 rounded" v-text="'{{email}}'"></code> — will be replaced with the subscriber's email address.
                    <br />
                    <strong>Subscribers:</strong> This campaign will be sent to <strong>{{ subscriberCount }}</strong> active subscriber(s).
                </div>

                <!-- Content Editor -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <Editor
                        v-if="editorReady"
                        id="content"
                        v-model="form.content"
                        api-key="l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1"
                        :init="{
                            height: 500,
                            menubar: true,
                            skin: 'oxide',
                            content_css: 'default',
                            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
                            plugins: 'lists link image code',
                        }"
                        class="border border-gray-300 rounded-lg"
                    />
                    <p v-if="form.errors.content" class="mt-2 text-sm text-red-600">
                        {{ form.errors.content }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <Button variant="outline" @click="router.visit('/admin/newsletter-campaigns')">
                        Cancel
                    </Button>
                    <Button @click="updateCampaign" :disabled="form.processing">
                        <Save class="w-4 h-4 mr-2" />
                        Update Campaign
                    </Button>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Editor from '@tinymce/tinymce-vue';
import { ArrowLeft, Save } from 'lucide-vue-next';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

const props = defineProps({
    campaign: Object,
    subscriberCount: Number,
});

const editorReady = ref(false);
onMounted(() => {
    editorReady.value = true;
});

const form = useForm({
    subject: props.campaign.subject,
    content: props.campaign.content,
});

const updateCampaign = () => {
    form.put(`/admin/newsletter-campaigns/${props.campaign.id}`);
};
</script>
