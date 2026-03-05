<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Marketing Dashboard</h1>
                <div class="flex items-center gap-4">
                    <Dialog v-model:open="isDialogOpen">
                        <DialogTrigger as-child>
                            <Button @click="openCreateDialog" class="bg-blue-600 hover:bg-blue-700">
                                <Plus class="w-4 h-4 mr-2" />
                                Add New Advertisement
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>{{ isEditing ? 'Edit Advertisement' : 'Create Advertisement' }}</DialogTitle>
                                <DialogDescription>Fill in the details to {{ isEditing ? 'update' : 'create' }} an advertisement.</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="saveAdvertisement">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Offer Type -->
                                    <div>
                                        <label class="block text-sm font-medium">Offer Type (e.g., Limited Offer)</label>
                                        <Input v-model="form.offer_type" required />
                                    </div>
                                    <!-- Title -->
                                    <div>
                                        <label class="block text-sm font-medium">Title</label>
                                        <Input v-model="form.title" required />
                                    </div>
                                    <!-- Description -->
                                    <div class="col-span-1 md:col-span-2">
                                        <label class="block text-sm font-medium">Description</label>
                                        <Textarea v-model="form.description" required />
                                    </div>
                                    <!-- Button Text -->
                                    <div>
                                        <label class="block text-sm font-medium">Button Text</label>
                                        <Input v-model="form.button_text" required />
                                    </div>
                                    <!-- Button Link -->
                                    <div>
                                        <label class="block text-sm font-medium">Button Link</label>
                                        <Input v-model="form.button_link" placeholder="/search or https://google.com" />
                                        <div class="flex items-center gap-2 mt-2">
                                            <input type="checkbox" v-model="form.is_external" id="is_external" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <label for="is_external" class="text-xs text-gray-600">External Link (opens in new tab)</label>
                                        </div>
                                    </div>
                                    <!-- Dates -->
                                    <div>
                                        <label class="block text-sm font-medium">Start Date</label>
                                        <Input type="datetime-local" v-model="form.start_date" required />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium">End Date</label>
                                        <Input type="datetime-local" v-model="form.end_date" required />
                                    </div>
                                     <!-- Image Upload -->
                                     <div class="col-span-1 md:col-span-2">
                                        <label class="block text-sm font-medium">Ad Image</label>
                                        <input type="file" ref="imageInput" @change="handleImageChange" accept="image/*" class="mt-1 block w-full text-sm text-slate-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100
                                        " />
                                        <img v-if="form.imagePreview" :src="form.imagePreview" alt="Image Preview" class="mt-4 h-32 w-auto object-cover rounded-lg border" />
                                    </div>
                                     <!-- Is Active -->
                                     <div class="col-span-1 md:col-span-2 flex items-center gap-2">
                                        <input type="checkbox" v-model="form.is_active" id="is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <label for="is_active" class="text-sm font-medium">Active</label>
                                     </div>

                                     <!-- Promo Section -->
                                     <div class="col-span-1 md:col-span-2 border-t pt-4 mt-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <input type="checkbox" v-model="form.is_promo" id="is_promo" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                                            <label for="is_promo" class="text-sm font-semibold text-emerald-700">Enable as Promotional Campaign</label>
                                        </div>
                                        <div v-if="form.is_promo" class="bg-emerald-50 rounded-lg p-4 space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Discount Percentage (max 50%)</label>
                                                <Input v-model.number="form.discount_percentage" type="number" min="0" max="50" step="0.5" class="max-w-[200px]" />
                                            </div>
                                            <!-- Live Price Simulation -->
                                            <div v-if="form.discount_percentage > 0" class="bg-white rounded-md p-3 border border-emerald-200">
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Price Simulation (base: $100 vehicle)</p>
                                                <div class="text-sm space-y-1">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Vendor Price</span>
                                                        <span>$100.00</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">+ Platform Markup (15%)</span>
                                                        <span>$115.00</span>
                                                    </div>
                                                    <div class="flex justify-between text-gray-400">
                                                        <span>+ Promo Markup ({{ form.discount_percentage }}%)</span>
                                                        <span class="line-through">${{ (115 * (1 + form.discount_percentage / 100)).toFixed(2) }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-red-500 font-medium">
                                                        <span>- Discount ({{ form.discount_percentage }}%)</span>
                                                        <span>-${{ (115 * (1 + form.discount_percentage / 100) - 115).toFixed(2) }}</span>
                                                    </div>
                                                    <div class="flex justify-between font-bold border-t pt-1 mt-1">
                                                        <span>Customer Pays</span>
                                                        <span class="text-emerald-600">$115.00</span>
                                                    </div>
                                                </div>
                                                <p class="text-xs text-gray-400 mt-2">Vendor ($100) and platform ($15) amounts stay protected.</p>
                                            </div>
                                        </div>
                                     </div>
                                </div>
                                <DialogFooter class="mt-6">
                                    <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                                    <Button type="submit" :disabled="processing">
                                        <span v-if="processing" class="flex items-center gap-2">
                                            <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                            {{ isEditing ? 'Updating...' : 'Creating...' }}
                                        </span>
                                        <span v-else>{{ isEditing ? 'Update' : 'Create' }}</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>

            <!-- Tabs: Advertisements / Active Promotion -->
            <div class="flex gap-2 border-b">
                <button
                    @click="activeTab = 'ads'"
                    :class="[activeTab === 'ads' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500']"
                    class="px-4 py-2 text-sm transition-colors"
                >
                    Advertisements
                </button>
                <button
                    @click="activeTab = 'promo'"
                    :class="[activeTab === 'promo' ? 'border-b-2 border-emerald-600 text-emerald-600 font-semibold' : 'text-gray-500']"
                    class="px-4 py-2 text-sm transition-colors flex items-center gap-2"
                >
                    Active Promotion
                    <span v-if="livePromo" class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                </button>
            </div>

            <!-- Tab: Advertisements Table -->
            <div v-if="activeTab === 'ads'">
                <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead class="px-4 py-3 font-semibold">Image</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold">Offer Type</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold">Title</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold">Description</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold">Dates</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold">Type</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold">Status</TableHead>
                                    <TableHead class="px-4 py-3 font-semibold text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="ad in advertisements.data" :key="ad.id" class="hover:bg-muted/25 transition-colors">
                                    <TableCell class="px-4 py-3">
                                        <img :src="ad.image_path" alt="Ad Image" class="h-12 w-20 object-cover rounded border border-gray-200" />
                                    </TableCell>
                                    <TableCell class="px-4 py-3 font-medium">{{ ad.offer_type }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ ad.title }}</TableCell>
                                    <TableCell class="px-4 py-3 max-w-[200px] truncate" :title="ad.description">{{ ad.description }}</TableCell>
                                    <TableCell class="px-4 py-3 text-sm">
                                        <div class="flex flex-col">
                                            <span class="text-green-600">Start: {{ formatDate(ad.start_date) }}</span>
                                            <span class="text-red-500">End: {{ formatDate(ad.end_date) }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-4 py-3">
                                        <Badge v-if="ad.is_promo" variant="default" class="bg-emerald-100 text-emerald-700 border-emerald-200">
                                            Promo {{ ad.discount_percentage }}%
                                        </Badge>
                                        <Badge v-else variant="secondary">Banner</Badge>
                                    </TableCell>
                                    <TableCell class="px-4 py-3 flex items-center gap-2">
                                         <Switch
                                            :checked="Boolean(ad.is_active)"
                                            @update:checked="(val) => toggleStatus(ad, val)"
                                         />
                                         <Badge :variant="getAdStatus(ad).variant">
                                            {{ getAdStatus(ad).label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button v-if="getAdStatus(ad).label === 'Expired'" size="sm" variant="default" @click="reactivateAd(ad)">Reactivate</Button>
                                            <Button size="sm" variant="outline" @click="openEditDialog(ad)">Edit</Button>
                                            <Button size="sm" variant="destructive" @click="openDeleteDialog(ad.id)">Delete</Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="advertisements.data.length === 0">
                                    <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                                        No advertisements found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                     <div class="flex justify-end pt-4 pr-2" v-if="advertisements.last_page > 1">
                        <Pagination :current-page="advertisements.current_page" :total-pages="advertisements.last_page"
                            @page-change="handlePageChange" />
                    </div>
                </div>
            </div>

            <!-- Tab: Active Promotion -->
            <div v-if="activeTab === 'promo'" class="space-y-6">
                <!-- LIVE Status Card -->
                <div v-if="livePromo" class="bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-xl p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">LIVE</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">{{ livePromo.title }}</h3>
                            <p class="text-gray-600 mt-1">{{ livePromo.description }}</p>
                            <div class="flex gap-4 mt-3 text-sm text-gray-500">
                                <span>Discount: <strong class="text-emerald-600">{{ livePromo.discount_percentage }}%</strong></span>
                                <span>Ends: <strong>{{ formatDate(livePromo.end_date) }}</strong></span>
                            </div>
                        </div>
                        <Badge variant="default" class="bg-emerald-500 text-white text-lg px-4 py-1">
                            -{{ livePromo.discount_percentage }}%
                        </Badge>
                    </div>
                </div>

                <!-- No Active Promo -->
                <div v-else class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-3">
                        <Megaphone class="w-6 h-6 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700">No Active Promotion</h3>
                    <p class="text-gray-500 mt-1 text-sm">Create a promotional advertisement with a discount percentage to activate a promo campaign.</p>
                    <Button @click="openCreatePromoDialog" class="mt-4 bg-emerald-600 hover:bg-emerald-700">
                        Create Promo Campaign
                    </Button>
                </div>

                <!-- How It Works -->
                <div class="bg-white border rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">How Promotional Pricing Works</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-gray-900">$100</div>
                            <div class="text-gray-500 mt-1">Vendor Price</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600">$115</div>
                            <div class="text-gray-500 mt-1">+ 15% Platform</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-400 line-through">${{ livePromo ? (115 * (1 + livePromo.discount_percentage / 100)).toFixed(0) : '127' }}</div>
                            <div class="text-gray-500 mt-1">Inflated (shown crossed out)</div>
                        </div>
                        <div class="bg-emerald-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-emerald-600">$115</div>
                            <div class="text-gray-500 mt-1">Customer Pays</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Vendor and platform amounts are always protected. The discount is purely visual.</p>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirm Deletion</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this advertisement? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                 <DialogFooter class="mt-6">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Plus, Megaphone } from 'lucide-vue-next';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/Components/ui/table";
import { Switch } from "@/Components/ui/switch";
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import dayjs from 'dayjs';

const toast = useToast();
const page = usePage();

const props = defineProps({
    advertisements: Object,
    flash: Object,
});

const activeTab = ref('ads');
const isDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isEditing = ref(false);
const currentAdId = ref(null);
const adToDelete = ref(null);
const processing = ref(false);
const imageInput = ref(null);

// Find the currently live promo ad from the advertisements list
const livePromo = computed(() => {
    const promo = page.props.active_promo;
    if (promo) {
        // Find full ad data from the list
        const fullAd = props.advertisements.data.find(ad => ad.id === promo.id);
        return fullAd || promo;
    }
    return null;
});

const form = reactive({
    offer_type: '',
    title: '',
    description: '',
    button_text: '',
    button_link: '',
    start_date: '',
    end_date: '',
    image: null,
    imagePreview: null,
    is_active: true,
    is_external: false,
    is_promo: false,
    discount_percentage: 0,
});

const formatDate = (date) => {
    return dayjs(date).format('MMM DD, YYYY HH:mm');
};

const getAdStatus = (ad) => {
    const now = dayjs();
    const startDate = dayjs(ad.start_date);
    const endDate = dayjs(ad.end_date);

    if (!ad.is_active) {
        return { label: 'Inactive', variant: 'secondary' };
    }

    if (now.isBefore(startDate)) {
        return { label: 'Scheduled', variant: 'outline' };
    }

    if (now.isAfter(endDate)) {
        return { label: 'Expired', variant: 'destructive' };
    }

    return { label: 'Active', variant: 'default' };
};

const formatDateForInput = (dateStr) => {
    return dayjs(dateStr).format('YYYY-MM-DDTHH:mm');
};

const openCreateDialog = () => {
    isEditing.value = false;
    isDialogOpen.value = true;
    resetForm();
};

const openCreatePromoDialog = () => {
    isEditing.value = false;
    isDialogOpen.value = true;
    resetForm();
    form.is_promo = true;
    form.discount_percentage = 10;
};

const openEditDialog = (ad) => {
    isEditing.value = true;
    currentAdId.value = ad.id;
    isDialogOpen.value = true;

    form.offer_type = ad.offer_type;
    form.title = ad.title;
    form.description = ad.description;
    form.button_text = ad.button_text;
    form.button_link = ad.button_link;
    form.start_date = formatDateForInput(ad.start_date);
    form.end_date = formatDateForInput(ad.end_date);
    form.imagePreview = ad.image_path;
    form.image = null;
    form.is_active = Boolean(ad.is_active);
    form.is_external = Boolean(ad.is_external);
    form.is_promo = Boolean(ad.is_promo);
    form.discount_percentage = parseFloat(ad.discount_percentage) || 0;
};

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        form.imagePreview = URL.createObjectURL(file);
    }
};

const resetForm = () => {
    form.offer_type = '';
    form.title = '';
    form.description = '';
    form.button_text = '';
    form.button_link = '';
    form.start_date = '';
    form.end_date = '';
    form.image = null;
    form.imagePreview = null;
    form.is_active = true;
    form.is_external = false;
    form.is_promo = false;
    form.discount_percentage = 0;
    if (imageInput.value) imageInput.value.value = '';
};

const saveAdvertisement = () => {
    processing.value = true;
    const formData = new FormData();
    formData.append('offer_type', form.offer_type);
    formData.append('title', form.title);
    formData.append('description', form.description);
    formData.append('button_text', form.button_text);
    formData.append('button_link', form.button_link || '');
    formData.append('start_date', dayjs(form.start_date).toISOString());
    formData.append('end_date', dayjs(form.end_date).toISOString());
    formData.append('is_active', form.is_active ? '1' : '0');
    formData.append('is_external', form.is_external ? '1' : '0');
    formData.append('is_promo', form.is_promo ? '1' : '0');
    formData.append('discount_percentage', form.is_promo ? form.discount_percentage : '0');

    if (form.image) {
        formData.append('image', form.image);
    }

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        router.post(route('admin.advertisements.update', currentAdId.value), formData, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
                processing.value = false;
                toast.success('Advertisement updated successfully');
            },
            onError: (errors) => {
                processing.value = false;
                console.error(errors);
                toast.error('Error updating advertisement');
            },
        });
    } else {
        router.post(route('admin.advertisements.store'), formData, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
                processing.value = false;
                toast.success('Advertisement created successfully');
            },
            onError: (errors) => {
                processing.value = false;
                console.error(errors);
                toast.error('Error creating advertisement');
            },
        });
    }
};

const openDeleteDialog = (id) => {
    adToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!adToDelete.value) return;
    router.delete(route('admin.advertisements.destroy', adToDelete.value), {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            adToDelete.value = null;
            toast.success('Advertisement deleted successfully');
        },
        onError: () => toast.error('Error deleting advertisement')
    });
};

const handlePageChange = (page) => {
    router.get(route('admin.advertisements.index', { page }), {}, { preserveState: true });
};

const toggleStatus = (ad, checked) => {
    const formData = new FormData();
    formData.append('offer_type', ad.offer_type);
    formData.append('title', ad.title);
    formData.append('description', ad.description);
    formData.append('button_text', ad.button_text);
    formData.append('button_link', ad.button_link || '');
    formData.append('start_date', ad.start_date);
    formData.append('end_date', ad.end_date);
    formData.append('is_active', checked ? '1' : '0');
    formData.append('is_external', ad.is_external ? '1' : '0');
    formData.append('is_promo', ad.is_promo ? '1' : '0');
    formData.append('discount_percentage', ad.discount_percentage || '0');
    formData.append('_method', 'PUT');

    router.post(route('admin.advertisements.update', ad.id), formData, {
        preserveScroll: true,
        onError: (errors) => {
            console.error(errors);
            toast.error('Failed to update status');
        },
    });
};

const reactivateAd = (ad) => {
    const now = dayjs();
    const newStartDate = now.toISOString();
    const newEndDate = now.add(7, 'day').toISOString();

    const formData = new FormData();
    formData.append('offer_type', ad.offer_type);
    formData.append('title', ad.title);
    formData.append('description', ad.description);
    formData.append('button_text', ad.button_text);
    formData.append('button_link', ad.button_link || '');
    formData.append('start_date', newStartDate);
    formData.append('end_date', newEndDate);
    formData.append('is_active', '1');
    formData.append('is_external', ad.is_external ? '1' : '0');
    formData.append('is_promo', ad.is_promo ? '1' : '0');
    formData.append('discount_percentage', ad.discount_percentage || '0');
    formData.append('_method', 'PUT');

    router.post(route('admin.advertisements.update', ad.id), formData, {
        onSuccess: () => {
            toast.success('Advertisement reactivated for 7 days');
        },
        onError: (errors) => {
            console.error(errors);
            toast.error('Error reactivating advertisement');
        },
    });
};

onMounted(() => {
    if (props.flash?.success) {
        setTimeout(() => router.clearFlashMessages(), 3000);
    }
});
</script>
