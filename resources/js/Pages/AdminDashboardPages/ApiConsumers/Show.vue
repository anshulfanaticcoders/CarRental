<template>
    <AdminDashboardLayout>
        <div class="p-6 lg:p-8 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <Link :href="route('admin.api-consumers.index')" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                        <ArrowLeft class="w-3.5 h-3.5" /> Back to consumers
                    </Link>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-xl font-bold text-gray-900">{{ consumer.name }}</h1>
                        <Badge :class="statusBadge(consumer.status)">{{ consumer.status }}</Badge>
                        <Badge :class="modeBadge(consumer.mode)">{{ consumer.mode }}</Badge>
                        <Badge :class="planBadge(consumer.plan)">{{ consumer.plan }}</Badge>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button size="sm" :variant="consumer.status === 'active' ? 'destructive' : 'default'" @click="toggleStatus" :disabled="togglingStatus">
                        {{ consumer.status === 'active' ? 'Suspend' : 'Activate' }}
                    </Button>
                    <Link :href="route('admin.api-consumers.edit', consumer.id)">
                        <Button size="sm" variant="outline"><Pencil class="w-3.5 h-3.5 mr-1.5" />Edit</Button>
                    </Link>
                    <Button size="sm" variant="destructive" @click="showDelete = true"><Trash2 class="w-3.5 h-3.5 mr-1.5" />Delete</Button>
                </div>
            </div>

            <!-- New Key Alert -->
            <div v-if="newKey" class="mb-6 rounded-xl border-2 border-amber-300 bg-amber-50 p-5">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <Key class="w-4 h-4 text-amber-600" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-amber-800">API Key Generated — Copy Now</p>
                        <p class="text-xs text-amber-600 mt-0.5 mb-3">This key will not be shown again. Store it securely.</p>
                        <div class="flex items-center gap-2 bg-white rounded-lg border border-amber-200 px-3 py-2.5">
                            <code class="flex-1 text-[13px] font-mono text-gray-900 break-all select-all">{{ newKey }}</code>
                            <Button size="sm" variant="outline" @click="copyToClipboard(newKey)" class="flex-shrink-0 h-8">
                                <Copy class="w-3.5 h-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div v-for="s in statCards" :key="s.label" class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ s.label }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ (s.value || 0).toLocaleString() }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="s.bg">
                            <component :is="s.icon" class="w-4.5 h-4.5" :class="s.ic" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left: Details + Keys + Logs -->
                <div class="lg:col-span-3 space-y-6">

                    <!-- Consumer Details -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 class="text-[15px] font-bold text-gray-900 mb-4">Company Details</h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><p class="text-gray-400 text-xs mb-0.5">Contact</p><p class="font-medium text-gray-900">{{ consumer.contact_name }}</p></div>
                            <div><p class="text-gray-400 text-xs mb-0.5">Email</p><p class="font-medium text-gray-900">{{ consumer.contact_email }}</p></div>
                            <div><p class="text-gray-400 text-xs mb-0.5">Phone</p><p class="font-medium text-gray-900">{{ consumer.contact_phone || '—' }}</p></div>
                            <div><p class="text-gray-400 text-xs mb-0.5">Website</p><a v-if="consumer.company_url" :href="consumer.company_url" target="_blank" class="text-sm font-medium text-blue-600 hover:underline">{{ consumer.company_url }}</a><p v-else class="font-medium text-gray-900">—</p></div>
                            <div><p class="text-gray-400 text-xs mb-0.5">Rate Limit</p><p class="font-medium text-gray-900">{{ consumer.rate_limit }} req/min</p></div>
                            <div><p class="text-gray-400 text-xs mb-0.5">Created</p><p class="font-medium text-gray-900">{{ fmtDate(consumer.created_at) }}</p></div>
                        </div>
                        <div v-if="consumer.notes" class="mt-4 pt-3 border-t border-gray-100">
                            <p class="text-gray-400 text-xs mb-1">Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ consumer.notes }}</p>
                        </div>
                    </div>

                    <!-- API Logs -->
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h2 class="text-[15px] font-bold text-gray-900">Recent API Requests</h2>
                        </div>
                        <div v-if="apiLogs.length" class="divide-y divide-gray-50">
                            <div v-for="log in apiLogs" :key="log.id" class="px-5 py-3 flex items-center gap-4 text-sm hover:bg-gray-50/50">
                                <span class="font-mono text-[11px] font-bold px-1.5 py-0.5 rounded" :class="methodCls(log.method)">{{ log.method }}</span>
                                <span class="font-mono text-xs text-gray-600 truncate flex-1">{{ log.endpoint }}</span>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="statusCls(log.response_status)">{{ log.response_status }}</span>
                                <span class="text-xs text-gray-400 w-14 text-right">{{ log.processing_time_ms }}ms</span>
                                <span class="text-xs text-gray-400 w-20 text-right whitespace-nowrap">{{ fmtTime(log.created_at) }}</span>
                            </div>
                        </div>
                        <div v-else class="px-5 py-10 text-center text-gray-400 text-sm">No API requests logged yet.</div>
                    </div>
                </div>

                <!-- Right: Keys -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- API Keys -->
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-[15px] font-bold text-gray-900">API Keys</h2>
                            <Button size="sm" @click="showGenerate = true" class="h-8 text-xs"><Plus class="w-3.5 h-3.5 mr-1" />New Key</Button>
                        </div>
                        <div v-if="apiKeys.length" class="divide-y divide-gray-50">
                            <div v-for="k in apiKeys" :key="k.id" class="px-5 py-4" :class="k.status !== 'active' ? 'opacity-50' : ''">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <code class="text-sm font-mono font-bold text-gray-900">{{ k.key_prefix }}•••</code>
                                        <button v-if="k.status === 'active'" @click="copyToClipboard(k.key_prefix)" class="text-gray-300 hover:text-gray-600 transition-colors" title="Copy prefix"><Copy class="w-3 h-3" /></button>
                                        <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded" :class="keyBadge(k.status)">{{ k.status }}</span>
                                    </div>
                                    <div v-if="k.status === 'active'" class="flex gap-1.5">
                                        <button @click="confirmRotate(k)" class="text-xs text-gray-500 hover:text-blue-600 font-medium transition-colors">Rotate</button>
                                        <span class="text-gray-300">|</span>
                                        <button @click="confirmRevoke(k)" class="text-xs text-gray-500 hover:text-red-600 font-medium transition-colors">Revoke</button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400">
                                    {{ k.name || 'Default' }} · {{ fmtDate(k.created_at) }}
                                    <template v-if="k.last_used_at"> · Used {{ fmtRel(k.last_used_at) }}</template>
                                    <template v-if="k.revoked_at"> · Revoked {{ fmtDate(k.revoked_at) }}</template>
                                </p>
                            </div>
                        </div>
                        <div v-else class="px-5 py-10 text-center">
                            <Key class="w-6 h-6 mx-auto mb-2 text-gray-300" />
                            <p class="text-sm text-gray-400">No keys yet</p>
                        </div>
                    </div>

                    <!-- Quick Info -->
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 text-xs text-gray-500 space-y-2">
                        <p><strong class="text-gray-700">API Docs:</strong> <code class="bg-white px-1.5 py-0.5 rounded border text-[11px]">{{ docsUrl }}</code></p>
                        <p><strong class="text-gray-700">Auth Header:</strong> <code class="bg-white px-1.5 py-0.5 rounded border text-[11px]">X-Api-Key: vrm_live_•••</code></p>
                        <p><strong class="text-gray-700">Mode:</strong> {{ consumer.mode === 'sandbox' ? 'Test bookings only — no notifications' : 'Live — real bookings & notifications' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Dialog -->
        <Dialog v-model:open="showGenerate">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Generate New API Key</DialogTitle>
                    <DialogDescription>The key will only be shown once after generation.</DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Key Name</label>
                    <Input v-model="keyName" placeholder="e.g. Production, Staging" class="w-full" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showGenerate = false">Cancel</Button>
                    <Button @click="generateKey" :disabled="loading">{{ loading ? 'Generating...' : 'Generate' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Rotate Dialog -->
        <Dialog v-model:open="showRotate">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rotate Key</DialogTitle>
                    <DialogDescription>The current key <code class="font-mono">{{ selectedKey?.key_prefix }}•••</code> will be revoked immediately and a new key generated.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showRotate = false">Cancel</Button>
                    <Button variant="destructive" @click="rotateKey" :disabled="loading">{{ loading ? 'Rotating...' : 'Rotate' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Revoke Dialog -->
        <Dialog v-model:open="showRevoke">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Revoke Key</DialogTitle>
                    <DialogDescription>Key <code class="font-mono">{{ selectedKey?.key_prefix }}•••</code> will be permanently disabled. This cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showRevoke = false">Cancel</Button>
                    <Button variant="destructive" @click="revokeKey" :disabled="loading">{{ loading ? 'Revoking...' : 'Revoke' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Consumer Dialog -->
        <Dialog v-model:open="showDelete">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Consumer</DialogTitle>
                    <DialogDescription>This will permanently delete <strong>{{ consumer.name }}</strong> and all their API keys. Any active integrations will stop working immediately. This cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDelete = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteConsumer" :disabled="loading">{{ loading ? 'Deleting...' : 'Delete Forever' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Key, Plus, Pencil, Copy, Clock, BarChart3, CalendarDays, TrendingUp, ArrowLeft, Shield, Trash2 } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();
const props = defineProps({ consumer: Object, apiKeys: Array, apiLogs: Array, stats: Object, newKey: { type: String, default: null } });

const docsUrl = computed(() => `${window.location.protocol}//${window.location.hostname}:8001/provider/docs`);

const statCards = computed(() => [
    { label: 'Requests Today', value: props.stats?.today, icon: BarChart3, bg: 'bg-blue-50', ic: 'text-blue-600' },
    { label: 'This Week', value: props.stats?.week, icon: TrendingUp, bg: 'bg-emerald-50', ic: 'text-emerald-600' },
    { label: 'This Month', value: props.stats?.month, icon: CalendarDays, bg: 'bg-violet-50', ic: 'text-violet-600' },
    { label: 'Bookings', value: props.stats?.total_bookings, icon: Shield, bg: 'bg-amber-50', ic: 'text-amber-600' },
]);

// Badges
const statusBadge = (s) => ({ 'bg-emerald-100 text-emerald-700': s === 'active', 'bg-red-100 text-red-700': s === 'suspended', 'bg-gray-100 text-gray-500': s === 'inactive' });
const modeBadge = (m) => ({ 'bg-amber-100 text-amber-800': m === 'sandbox', 'bg-emerald-100 text-emerald-800': m === 'live' });
const planBadge = (p) => ({ 'bg-gray-100 text-gray-700': p === 'basic', 'bg-blue-100 text-blue-700': p === 'premium', 'bg-purple-100 text-purple-700': p === 'enterprise' });
const keyBadge = (s) => ({ 'bg-emerald-100 text-emerald-700': s === 'active', 'bg-red-100 text-red-700': s === 'revoked', 'bg-gray-100 text-gray-500': s === 'expired' });
const methodCls = (m) => ({ GET: 'bg-blue-50 text-blue-700', POST: 'bg-emerald-50 text-emerald-700', DELETE: 'bg-red-50 text-red-700' }[m] || 'bg-gray-50 text-gray-700');
const statusCls = (s) => s >= 200 && s < 300 ? 'bg-emerald-100 text-emerald-700' : s >= 400 && s < 500 ? 'bg-amber-100 text-amber-700' : s >= 500 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600';

// Formatting
const fmtDate = (d) => { if (!d) return '—'; const dt = new Date(d); return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); };
const fmtTime = (d) => { if (!d) return '—'; const dt = new Date(d); return dt.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }); };
const fmtRel = (d) => { if (!d) return 'Never'; const ms = Date.now() - new Date(d).getTime(); const m = Math.floor(ms/60000); if (m < 1) return 'just now'; if (m < 60) return m + 'm ago'; const h = Math.floor(m/60); if (h < 24) return h + 'h ago'; return Math.floor(h/24) + 'd ago'; };

// Actions
const togglingStatus = ref(false);
const toggleStatus = () => { togglingStatus.value = true; router.patch(route('admin.api-consumers.toggle-status', props.consumer.id), {}, { preserveScroll: true, onSuccess: () => toast.success('Status updated'), onFinish: () => togglingStatus.value = false }); };

const showGenerate = ref(false); const keyName = ref(''); const loading = ref(false);
const generateKey = () => { loading.value = true; router.post(route('admin.api-consumers.generate-key', props.consumer.id), { name: keyName.value || 'Default' }, { preserveScroll: true, onSuccess: () => { showGenerate.value = false; keyName.value = ''; toast.success('Key generated'); }, onFinish: () => loading.value = false }); };

const showRotate = ref(false); const showRevoke = ref(false); const selectedKey = ref(null);
const confirmRotate = (k) => { selectedKey.value = k; showRotate.value = true; };
const confirmRevoke = (k) => { selectedKey.value = k; showRevoke.value = true; };
const rotateKey = () => { loading.value = true; router.post(route('admin.api-consumers.rotate-key', selectedKey.value.id), {}, { preserveScroll: true, onSuccess: () => { showRotate.value = false; toast.success('Key rotated'); }, onFinish: () => loading.value = false }); };
const revokeKey = () => { loading.value = true; router.post(route('admin.api-consumers.revoke-key', selectedKey.value.id), {}, { preserveScroll: true, onSuccess: () => { showRevoke.value = false; toast.success('Key revoked'); }, onFinish: () => loading.value = false }); };

const showDelete = ref(false);
const deleteConsumer = () => { loading.value = true; router.delete(route('admin.api-consumers.destroy', props.consumer.id), { onSuccess: () => toast.success('Consumer deleted'), onFinish: () => loading.value = false }); };

const copyToClipboard = async (text) => { try { await navigator.clipboard.writeText(text); toast.success('Copied!', { timeout: 1500 }); } catch { toast.error('Copy failed'); } };
</script>
