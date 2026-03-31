<script setup>
import { ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Euro, Clock, QrCode, MousePointerClick } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const props = defineProps({
    partner: Object,
    commissionStats: Object,
    commissions: Object,
    qrCodes: Array,
    totalScans: Number,
});

const activeTab = ref('commissions');
const page = usePage();
const isVerifying = ref(false);

const statusColor = (val) => {
    const map = { active: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700', suspended: 'bg-red-100 text-red-700' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

const verificationColor = (val) => {
    const map = { verified: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700', rejected: 'bg-red-100 text-red-700' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

const commissionStatusColor = (val) => {
    const map = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-blue-100 text-blue-700', paid: 'bg-emerald-100 text-emerald-700', rejected: 'bg-red-100 text-red-700' };
    return map[val] || 'bg-gray-100 text-gray-600';
};

const conversionRate = () => {
    if (!props.totalScans) return '0%';
    const conversions = props.commissions?.total || 0;
    return ((conversions / props.totalScans) * 100).toFixed(1) + '%';
};

const notifyFromFlash = () => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
        return;
    }

    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
};

const handlePartnerActionError = () => {
    const firstError = Object.values(page.props.errors || {})[0];
    const message = Array.isArray(firstError) ? firstError[0] : firstError;

    toast.error(message || 'Partner action failed.');
};

const postPartnerAction = (routeName) => {
    router.post(route(routeName, { businessId: props.partner.id }), {}, {
        preserveScroll: true,
        onStart: () => {
            if (routeName === 'admin.affiliate.businesses.verify') {
                isVerifying.value = true;
            }
        },
        onSuccess: notifyFromFlash,
        onError: handlePartnerActionError,
        onFinish: () => {
            if (routeName === 'admin.affiliate.businesses.verify') {
                isVerifying.value = false;
            }
        },
    });
};

const verifyPartner = () => {
    postPartnerAction('admin.affiliate.businesses.verify');
};

const rejectPartner = () => {
    postPartnerAction('admin.affiliate.businesses.reject');
};

const suspendPartner = () => {
    postPartnerAction('admin.affiliate.businesses.suspend');
};

const activatePartner = () => {
    postPartnerAction('admin.affiliate.businesses.activate');
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.affiliate.partners')">
                        <Button variant="outline" size="sm" class="h-8 w-8 p-0">
                            <ArrowLeft class="w-4 h-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ partner.name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span :class="[statusColor(partner.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                {{ partner.status }}
                            </span>
                            <span :class="[verificationColor(partner.verification_status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                {{ partner.verification_status }}
                            </span>
                            <span class="text-xs text-gray-400 capitalize">{{ partner.business_type?.replace('_', ' ') }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="partner.verification_status === 'pending'"
                        size="sm"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white"
                        :disabled="isVerifying"
                        @click="verifyPartner"
                    >
                        {{ isVerifying ? 'Verifying...' : 'Verify' }}
                    </Button>
                    <Button
                        v-if="partner.verification_status === 'pending'"
                        variant="outline"
                        size="sm"
                        class="text-red-600 border-red-200 hover:bg-red-50"
                        @click="rejectPartner"
                    >
                        Reject
                    </Button>
                    <Button
                        v-if="partner.status === 'active'"
                        variant="outline"
                        size="sm"
                        class="text-amber-600 border-amber-200 hover:bg-amber-50"
                        @click="suspendPartner"
                    >
                        Suspend
                    </Button>
                    <Button
                        v-if="partner.status === 'suspended'"
                        size="sm"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white"
                        @click="activatePartner"
                    >
                        Activate
                    </Button>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Business Info -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base font-semibold">Business Info</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-y-3 gap-x-6 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs">Contact Email</p>
                            <p class="font-medium">{{ partner.contact_email || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Phone</p>
                            <p class="font-medium">{{ partner.contact_phone || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Website</p>
                            <p class="font-medium truncate">{{ partner.website || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Location</p>
                            <p class="font-medium">{{ [partner.city, partner.country].filter(Boolean).join(', ') || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Registration #</p>
                            <p class="font-medium font-mono text-xs">{{ partner.business_registration_number || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Tax ID</p>
                            <p class="font-medium font-mono text-xs">{{ partner.tax_id || '-' }}</p>
                        </div>
                        <div class="col-span-2" v-if="partner.user">
                            <p class="text-gray-400 text-xs">Linked User</p>
                            <p class="font-medium">{{ partner.user.first_name }} {{ partner.user.last_name }} ({{ partner.user.email }})</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bank Details -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base font-semibold">Bank Details</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-y-3 gap-x-6 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs">Bank Name</p>
                            <p class="font-medium">{{ partner.bank_name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Account Name</p>
                            <p class="font-medium">{{ partner.bank_account_name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">IBAN</p>
                            <p class="font-medium font-mono text-xs">{{ partner.bank_iban || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">BIC</p>
                            <p class="font-medium font-mono text-xs">{{ partner.bank_bic || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Currency</p>
                            <p class="font-medium">{{ partner.currency || 'EUR' }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Commission Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="flex items-center gap-3 p-4">
                        <div class="rounded-lg p-2.5 bg-indigo-50">
                            <Euro class="w-5 h-5 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Total Revenue</p>
                            <p class="text-xl font-bold">&euro;{{ parseFloat(commissionStats.total || 0).toFixed(2) }}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 p-4">
                        <div class="rounded-lg p-2.5 bg-amber-50">
                            <Clock class="w-5 h-5 text-amber-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Pending</p>
                            <p class="text-xl font-bold">&euro;{{ parseFloat(commissionStats.pending || 0).toFixed(2) }}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 p-4">
                        <div class="rounded-lg p-2.5 bg-blue-50">
                            <QrCode class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Total Scans</p>
                            <p class="text-xl font-bold">{{ totalScans }}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 p-4">
                        <div class="rounded-lg p-2.5 bg-emerald-50">
                            <MousePointerClick class="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Conversion Rate</p>
                            <p class="text-xl font-bold">{{ conversionRate() }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab" default-value="commissions">
                <TabsList>
                    <TabsTrigger value="commissions">Commissions ({{ commissions.total }})</TabsTrigger>
                    <TabsTrigger value="qrcodes">QR Codes ({{ qrCodes.length }})</TabsTrigger>
                </TabsList>

                <!-- Commissions Tab -->
                <TabsContent value="commissions">
                    <Card>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="px-4">Date</TableHead>
                                        <TableHead class="px-4">Booking #</TableHead>
                                        <TableHead class="px-4">Booking Amount</TableHead>
                                        <TableHead class="px-4">Rate</TableHead>
                                        <TableHead class="px-4">Commission</TableHead>
                                        <TableHead class="px-4">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="c in commissions.data" :key="c.id">
                                        <TableCell class="px-4 text-gray-500 text-xs">
                                            {{ new Date(c.created_at).toLocaleDateString() }}
                                        </TableCell>
                                        <TableCell class="px-4 font-mono text-xs">
                                            {{ c.booking?.booking_number || '-' }}
                                        </TableCell>
                                        <TableCell class="px-4">&euro;{{ parseFloat(c.booking_amount || 0).toFixed(2) }}</TableCell>
                                        <TableCell class="px-4">{{ c.commission_rate }}%</TableCell>
                                        <TableCell class="px-4 font-semibold">&euro;{{ parseFloat(c.commission_amount || 0).toFixed(2) }}</TableCell>
                                        <TableCell class="px-4">
                                            <span :class="[commissionStatusColor(c.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                                {{ c.status }}
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!commissions.data.length">
                                        <TableCell colspan="6" class="px-4 text-center text-gray-400 py-8">No commissions yet.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Commission Pagination -->
                    <div v-if="commissions.last_page > 1" class="flex items-center justify-between mt-4">
                        <p class="text-sm text-gray-500">
                            Showing {{ commissions.from }} to {{ commissions.to }} of {{ commissions.total }}
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in commissions.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1.5 rounded-md text-sm transition-colors',
                                        link.active ? 'bg-indigo-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50'
                                    ]"
                                    v-html="link.label"
                                    preserve-state
                                />
                                <span v-else class="px-3 py-1.5 rounded-md text-sm text-gray-300" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </TabsContent>

                <!-- QR Codes Tab -->
                <TabsContent value="qrcodes">
                    <Card>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="px-4">Short Code</TableHead>
                                        <TableHead class="px-4">Label</TableHead>
                                        <TableHead class="px-4">Destination</TableHead>
                                        <TableHead class="px-4 text-center">Scans</TableHead>
                                        <TableHead class="px-4">Status</TableHead>
                                        <TableHead class="px-4">Created</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="qr in qrCodes" :key="qr.id">
                                        <TableCell class="px-4 font-mono text-xs font-medium">{{ qr.short_code }}</TableCell>
                                        <TableCell class="px-4">{{ qr.label || '-' }}</TableCell>
                                        <TableCell class="px-4 text-xs text-gray-500 max-w-[200px] truncate">{{ qr.destination_url || '-' }}</TableCell>
                                        <TableCell class="px-4 text-center font-semibold">{{ qr.customer_scans_count }}</TableCell>
                                        <TableCell class="px-4">
                                            <span :class="[qr.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600', 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                                {{ qr.status }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="px-4 text-gray-500 text-xs">
                                            {{ new Date(qr.created_at).toLocaleDateString() }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!qrCodes.length">
                                        <TableCell colspan="6" class="px-4 text-center text-gray-400 py-8">No QR codes yet.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AdminDashboardLayout>
</template>
