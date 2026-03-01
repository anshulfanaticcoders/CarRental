<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button variant="ghost" size="sm" class="mb-2" @click="router.visit('/admin/newsletter-campaigns')">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to Campaigns
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">{{ campaign.subject }}</h1>
                    <div class="flex items-center gap-3 mt-2">
                        <Badge :variant="statusBadgeVariant(campaign.status)" class="capitalize">
                            {{ campaign.status }}
                        </Badge>
                        <span class="text-sm text-slate-500">
                            Created {{ formatDate(campaign.created_at) }}
                            <span v-if="campaign.creator"> by {{ campaign.creator.name }}</span>
                        </span>
                        <span v-if="campaign.scheduled_at" class="text-sm text-blue-600">
                            Scheduled for {{ formatDateTime(campaign.scheduled_at) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button v-if="campaign.status === 'draft' || campaign.status === 'scheduled'" variant="outline" @click="sendTestEmail">
                        Test Email
                    </Button>
                    <Button v-if="campaign.status === 'draft'" variant="outline" @click="showScheduleDialog = true">
                        <Clock class="w-4 h-4 mr-2" />
                        Schedule
                    </Button>
                    <Button v-if="campaign.status === 'draft' || campaign.status === 'scheduled'" @click="sendNow">
                        <Send class="w-4 h-4 mr-2" />
                        Send Now
                    </Button>
                    <Button v-if="campaign.status === 'scheduled' || campaign.status === 'sending'" variant="destructive" @click="cancelCampaign">
                        Cancel
                    </Button>
                </div>
            </div>

            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ flash.error }}
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Sent</div>
                    <div class="text-2xl font-semibold text-slate-900">
                        {{ campaign.sent_count }} / {{ campaign.total_recipients }}
                    </div>
                    <div v-if="campaign.total_recipients > 0" class="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: sentPercent + '%' }"></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Opened</div>
                    <div class="text-2xl font-semibold text-blue-600">{{ campaign.open_rate }}%</div>
                    <div class="text-sm text-slate-400">{{ campaign.opened_count }} opens</div>
                    <div v-if="campaign.sent_count > 0" class="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: campaign.open_rate + '%' }"></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Clicked</div>
                    <div class="text-2xl font-semibold text-purple-600">{{ campaign.click_rate }}%</div>
                    <div class="text-sm text-slate-400">{{ campaign.clicked_count }} clicks</div>
                    <div v-if="campaign.sent_count > 0" class="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full transition-all" :style="{ width: campaign.click_rate + '%' }"></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-slate-500">Failed</div>
                    <div class="text-2xl font-semibold text-red-600">{{ campaign.failed_count }}</div>
                    <div class="text-sm text-slate-400">{{ campaign.unsubscribed_count }} unsubscribed</div>
                </div>
            </div>

            <!-- Content Preview -->
            <div class="rounded-xl border bg-white">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Content Preview</h2>
                </div>
                <div class="p-6 prose max-w-none" v-html="campaign.content"></div>
            </div>

            <!-- Delivery Log -->
            <div class="rounded-xl border bg-white">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Delivery Log</h2>
                </div>
                <div v-if="logs?.data?.length" class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap px-4 py-3">Email</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Sent At</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Opened At</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3">Clicked At</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="log in logs.data" :key="log.id">
                                <TableCell class="px-4 py-3">
                                    <div class="text-sm">{{ log.subscription?.email || '-' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="logStatusVariant(log.status)" class="capitalize">
                                        {{ log.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ formatDateTime(log.sent_at) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ formatDateTime(log.opened_at) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ formatDateTime(log.clicked_at) }}</div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="flex justify-end pt-4 pr-2">
                        <Pagination :current-page="logs.current_page" :total-pages="logs.last_page"
                            @page-change="handleLogPageChange" />
                    </div>
                </div>
                <div v-else class="p-6 text-center text-slate-500">
                    No delivery logs yet.
                </div>
            </div>

            <!-- Schedule Modal (plain HTML) -->
            <div v-if="showScheduleDialog" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/80" @click="showScheduleDialog = false"></div>
                <div class="relative z-50 w-full max-w-md bg-white rounded-lg p-6 shadow-lg">
                    <h3 class="text-lg font-semibold mb-1">Schedule Campaign</h3>
                    <p class="text-sm text-slate-500 mb-4">Choose when to send this campaign.</p>
                    <div class="mb-4">
                        <label for="schedule-at" class="block text-sm font-medium text-gray-700 mb-2">Send At</label>
                        <Input
                            id="schedule-at"
                            v-model="scheduleForm.scheduled_at"
                            type="datetime-local"
                            class="h-12"
                        />
                        <p v-if="scheduleForm.errors.scheduled_at" class="mt-2 text-sm text-red-600">
                            {{ scheduleForm.errors.scheduled_at }}
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showScheduleDialog = false">Cancel</Button>
                        <Button @click="scheduleCampaign" :disabled="scheduleForm.processing">Schedule</Button>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Send, Clock } from 'lucide-vue-next';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    campaign: Object,
    logs: Object,
    flash: Object,
});

const showScheduleDialog = ref(false);

const scheduleForm = useForm({
    scheduled_at: '',
});

const sentPercent = computed(() => {
    if (!props.campaign.total_recipients) return 0;
    return Math.round((props.campaign.sent_count / props.campaign.total_recipients) * 100);
});

const handleLogPageChange = (page) => {
    router.get(`/admin/newsletter-campaigns/${props.campaign.id}`, {
        page,
    }, { preserveState: true, replace: true });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString();
};

const statusBadgeVariant = (statusValue) => {
    const map = {
        draft: 'secondary',
        scheduled: 'outline',
        sending: 'default',
        sent: 'default',
        failed: 'destructive',
        cancelled: 'destructive',
    };
    return map[statusValue] || 'secondary';
};

const logStatusVariant = (statusValue) => {
    if (statusValue === 'sent') return 'default';
    if (statusValue === 'failed') return 'destructive';
    return 'secondary';
};

const sendTestEmail = () => {
    router.post(`/admin/newsletter-campaigns/${props.campaign.id}/test`, {}, {
        preserveScroll: true,
    });
};

const sendNow = () => {
    if (!window.confirm('Send this campaign to all active subscribers now?')) return;
    router.post(`/admin/newsletter-campaigns/${props.campaign.id}/send`, {}, {
        preserveScroll: true,
    });
};

const scheduleCampaign = () => {
    scheduleForm.post(`/admin/newsletter-campaigns/${props.campaign.id}/schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleDialog.value = false;
        },
    });
};

const cancelCampaign = () => {
    if (!window.confirm('Cancel this campaign? Unsent emails will not be delivered.')) return;
    router.post(`/admin/newsletter-campaigns/${props.campaign.id}/cancel`, {}, {
        preserveScroll: true,
    });
};
</script>
