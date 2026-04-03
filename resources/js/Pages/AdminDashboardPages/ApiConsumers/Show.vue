<template>
    <AdminDashboardLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <!-- Page header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900">{{ consumer.name }}</h1>
                            <Badge :class="statusBadgeClass(consumer.status)">{{ consumer.status }}</Badge>
                            <Badge :class="planBadgeClass(consumer.plan)">{{ consumer.plan }}</Badge>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Created {{ formatDate(consumer.created_at) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.api-consumers.edit', consumer.id)">
                            <Button variant="outline"><Pencil class="w-4 h-4 mr-2" />Edit</Button>
                        </Link>
                        <Link :href="route('admin.api-consumers.index')">
                            <Button variant="outline">Back to List</Button>
                        </Link>
                    </div>
                </div>

                <!-- B) New Key Alert -->
                <div v-if="newKey" class="mb-6 rounded-xl border-2 border-amber-400 bg-amber-50 p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <Key class="w-5 h-5 text-amber-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-amber-800 mb-1">New API Key Generated</h3>
                            <p class="text-xs text-amber-700 mb-3">Copy this key now. It will only be shown once and cannot be retrieved later.</p>
                            <div class="flex items-center gap-2 bg-white rounded-lg border border-amber-200 p-3">
                                <code class="flex-1 text-sm font-mono text-gray-900 break-all select-all">{{ newKey }}</code>
                                <Button size="sm" variant="outline" @click="copyKey(newKey)" class="flex-shrink-0">
                                    <Copy class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left column -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- A) Consumer Info Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Shield class="w-5 h-5" /> Consumer Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">Contact Name</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ consumer.contact_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Contact Email</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ consumer.contact_email }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Contact Phone</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ consumer.contact_phone || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Company URL</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">
                                            <a v-if="consumer.company_url" :href="consumer.company_url" target="_blank" class="text-blue-600 hover:underline">{{ consumer.company_url }}</a>
                                            <span v-else>--</span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Rate Limit</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ consumer.rate_limit }} req/min</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Status</dt>
                                        <dd class="mt-0.5">
                                            <Button
                                                size="sm"
                                                :variant="consumer.status === 'active' ? 'destructive' : 'default'"
                                                @click="toggleStatus"
                                                :disabled="togglingStatus"
                                            >
                                                {{ consumer.status === 'active' ? 'Suspend' : 'Activate' }}
                                            </Button>
                                        </dd>
                                    </div>
                                </dl>
                                <div v-if="consumer.notes" class="mt-4 pt-4 border-t border-gray-100">
                                    <dt class="text-sm text-gray-500 mb-1">Notes</dt>
                                    <dd class="text-sm text-gray-700 whitespace-pre-line">{{ consumer.notes }}</dd>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- C) API Keys Card -->
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between">
                                <CardTitle class="flex items-center gap-2"><Key class="w-5 h-5" /> API Keys</CardTitle>
                                <Button size="sm" @click="showGenerateDialog = true"><Plus class="w-4 h-4 mr-1" /> Generate Key</Button>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Prefix</TableHead>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Scopes</TableHead>
                                            <TableHead>Last Used</TableHead>
                                            <TableHead>Created</TableHead>
                                            <TableHead class="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="key in apiKeys" :key="key.id">
                                            <TableCell class="font-mono text-sm">{{ key.key_prefix }}...</TableCell>
                                            <TableCell>{{ key.name || '--' }}</TableCell>
                                            <TableCell>
                                                <Badge :class="keyStatusClass(key.status)">{{ key.status }}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="scope in (key.scopes || [])" :key="scope" class="inline-block text-xs bg-gray-100 text-gray-600 rounded px-1.5 py-0.5">{{ scope }}</span>
                                                    <span v-if="!key.scopes || key.scopes.length === 0" class="text-xs text-gray-400">All</span>
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-sm">{{ formatRelative(key.last_used_at) }}</TableCell>
                                            <TableCell class="text-sm">{{ formatDate(key.created_at) }}</TableCell>
                                            <TableCell class="text-right">
                                                <div v-if="key.status === 'active'" class="flex items-center justify-end gap-2">
                                                    <Button size="sm" variant="outline" @click="confirmRotate(key)">
                                                        <RotateCcw class="w-3.5 h-3.5" />
                                                    </Button>
                                                    <Button size="sm" variant="destructive" @click="confirmRevoke(key)">
                                                        <Ban class="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                                <span v-else class="text-xs text-gray-400">
                                                    {{ key.revoked_at ? 'Revoked ' + formatDate(key.revoked_at) : 'Expired' }}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="apiKeys.length === 0">
                                            <TableCell colspan="7" class="text-center py-6 text-gray-500">No API keys yet. Generate one to get started.</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <!-- E) Recent API Logs Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Clock class="w-5 h-5" /> Recent API Logs</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Time</TableHead>
                                            <TableHead>Method</TableHead>
                                            <TableHead>Endpoint</TableHead>
                                            <TableHead class="text-center">Status</TableHead>
                                            <TableHead>IP</TableHead>
                                            <TableHead class="text-right">Duration</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="log in apiLogs" :key="log.id">
                                            <TableCell class="text-sm whitespace-nowrap">{{ formatTime(log.created_at) }}</TableCell>
                                            <TableCell>
                                                <span class="font-mono text-xs font-semibold px-1.5 py-0.5 rounded" :class="methodClass(log.method)">{{ log.method }}</span>
                                            </TableCell>
                                            <TableCell class="font-mono text-sm max-w-xs truncate">{{ log.endpoint }}</TableCell>
                                            <TableCell class="text-center">
                                                <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full" :class="httpStatusClass(log.response_status)">{{ log.response_status }}</span>
                                            </TableCell>
                                            <TableCell class="text-sm text-gray-500">{{ log.ip_address }}</TableCell>
                                            <TableCell class="text-right text-sm">{{ log.processing_time_ms }}ms</TableCell>
                                        </TableRow>
                                        <TableRow v-if="apiLogs.length === 0">
                                            <TableCell colspan="6" class="text-center py-6 text-gray-500">No API requests logged yet.</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right column: D) Usage Stats -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                            <Card v-for="stat in statCards" :key="stat.label">
                                <CardContent class="pt-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-500">{{ stat.label }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ stat.value.toLocaleString() }}</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="stat.bgClass">
                                            <component :is="stat.icon" class="w-5 h-5" :class="stat.iconClass" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Key Dialog -->
        <Dialog v-model:open="showGenerateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Generate New API Key</DialogTitle>
                    <DialogDescription>A new API key will be created for {{ consumer.name }}. You will only be able to see the full key once.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div>
                        <label for="key_name" class="block text-sm font-medium text-gray-700 mb-1">Key Name (optional)</label>
                        <Input id="key_name" v-model="keyName" placeholder="e.g. Production, Staging" class="w-full" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showGenerateDialog = false">Cancel</Button>
                    <Button @click="generateKey" :disabled="generatingKey">
                        {{ generatingKey ? 'Generating...' : 'Generate' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Rotate Key Dialog -->
        <Dialog v-model:open="showRotateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rotate API Key</DialogTitle>
                    <DialogDescription>This will revoke the current key ({{ selectedKey?.key_prefix }}...) and generate a new one. The old key will stop working immediately.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showRotateDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="rotateKey" :disabled="rotatingKey">
                        {{ rotatingKey ? 'Rotating...' : 'Rotate Key' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Revoke Key Dialog -->
        <Dialog v-model:open="showRevokeDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Revoke API Key</DialogTitle>
                    <DialogDescription>This will permanently revoke the key ({{ selectedKey?.key_prefix }}...). Any requests using this key will be rejected. This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showRevokeDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="revokeKey" :disabled="revokingKey">
                        {{ revokingKey ? 'Revoking...' : 'Revoke Key' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Key, Shield, Plus, RotateCcw, Ban, Pencil, Copy, Clock, BarChart3, CalendarDays, TrendingUp } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    consumer: Object,
    apiKeys: Array,
    apiLogs: Array,
    stats: Object,
    newKey: { type: String, default: null },
});

// Stats cards
const statCards = computed(() => [
    { label: 'Today', value: props.stats.today, icon: BarChart3, bgClass: 'bg-blue-50', iconClass: 'text-blue-600' },
    { label: 'This Week', value: props.stats.week, icon: TrendingUp, bgClass: 'bg-green-50', iconClass: 'text-green-600' },
    { label: 'This Month', value: props.stats.month, icon: CalendarDays, bgClass: 'bg-purple-50', iconClass: 'text-purple-600' },
    { label: 'Total Bookings', value: props.stats.total_bookings, icon: Shield, bgClass: 'bg-amber-50', iconClass: 'text-amber-600' },
]);

// Status toggle
const togglingStatus = ref(false);
const toggleStatus = () => {
    togglingStatus.value = true;
    router.post(route('admin.api-consumers.toggle-status', props.consumer.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Consumer ${props.consumer.status === 'active' ? 'suspended' : 'activated'} successfully!`, { position: 'top-right', timeout: 3000 });
        },
        onError: () => toast.error('Failed to update status.', { position: 'top-right', timeout: 5000 }),
        onFinish: () => { togglingStatus.value = false; },
    });
};

// Generate key
const showGenerateDialog = ref(false);
const keyName = ref('');
const generatingKey = ref(false);
const generateKey = () => {
    generatingKey.value = true;
    router.post(route('admin.api-consumers.keys.generate', props.consumer.id), { name: keyName.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showGenerateDialog.value = false;
            keyName.value = '';
            toast.success('API key generated!', { position: 'top-right', timeout: 3000 });
        },
        onError: () => toast.error('Failed to generate key.', { position: 'top-right', timeout: 5000 }),
        onFinish: () => { generatingKey.value = false; },
    });
};

// Rotate key
const showRotateDialog = ref(false);
const selectedKey = ref(null);
const rotatingKey = ref(false);
const confirmRotate = (key) => { selectedKey.value = key; showRotateDialog.value = true; };
const rotateKey = () => {
    rotatingKey.value = true;
    router.post(route('admin.api-consumers.keys.rotate', [props.consumer.id, selectedKey.value.id]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showRotateDialog.value = false;
            selectedKey.value = null;
            toast.success('API key rotated!', { position: 'top-right', timeout: 3000 });
        },
        onError: () => toast.error('Failed to rotate key.', { position: 'top-right', timeout: 5000 }),
        onFinish: () => { rotatingKey.value = false; },
    });
};

// Revoke key
const showRevokeDialog = ref(false);
const revokingKey = ref(false);
const confirmRevoke = (key) => { selectedKey.value = key; showRevokeDialog.value = true; };
const revokeKey = () => {
    revokingKey.value = true;
    router.post(route('admin.api-consumers.keys.revoke', [props.consumer.id, selectedKey.value.id]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showRevokeDialog.value = false;
            selectedKey.value = null;
            toast.success('API key revoked.', { position: 'top-right', timeout: 3000 });
        },
        onError: () => toast.error('Failed to revoke key.', { position: 'top-right', timeout: 5000 }),
        onFinish: () => { revokingKey.value = false; },
    });
};

// Copy key to clipboard
const copyKey = async (key) => {
    try {
        await navigator.clipboard.writeText(key);
        toast.success('API key copied to clipboard!', { position: 'top-right', timeout: 2000 });
    } catch {
        toast.error('Failed to copy. Please select and copy manually.', { position: 'top-right', timeout: 3000 });
    }
};

// Badge classes
const planBadgeClass = (plan) => ({
    'bg-gray-100 text-gray-700 border-gray-200': plan === 'basic',
    'bg-blue-100 text-blue-700 border-blue-200': plan === 'premium',
    'bg-purple-100 text-purple-700 border-purple-200': plan === 'enterprise',
});

const statusBadgeClass = (status) => ({
    'bg-green-100 text-green-700 border-green-200': status === 'active',
    'bg-red-100 text-red-700 border-red-200': status === 'suspended',
    'bg-gray-100 text-gray-500 border-gray-200': status === 'inactive',
});

const keyStatusClass = (status) => ({
    'bg-green-100 text-green-700 border-green-200': status === 'active',
    'bg-red-100 text-red-700 border-red-200': status === 'revoked',
    'bg-gray-100 text-gray-500 border-gray-200': status === 'expired',
});

// HTTP status color coding
const httpStatusClass = (status) => {
    if (status >= 200 && status < 300) return 'bg-green-100 text-green-700';
    if (status >= 400 && status < 500) return 'bg-yellow-100 text-yellow-700';
    if (status >= 500) return 'bg-red-100 text-red-700';
    return 'bg-gray-100 text-gray-600';
};

const methodClass = (method) => {
    const map = { GET: 'bg-blue-50 text-blue-700', POST: 'bg-green-50 text-green-700', PUT: 'bg-amber-50 text-amber-700', PATCH: 'bg-amber-50 text-amber-700', DELETE: 'bg-red-50 text-red-700' };
    return map[method] || 'bg-gray-50 text-gray-700';
};

// Date / time formatting
const formatDate = (dateStr) => {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
};

const formatTime = (dateStr) => {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
};

const formatRelative = (dateStr) => {
    if (!dateStr) return 'Never';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHrs = Math.floor(diffMin / 60);
    if (diffHrs < 24) return `${diffHrs}h ago`;
    const diffDays = Math.floor(diffHrs / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return formatDate(dateStr);
};
</script>
