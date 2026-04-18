<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { usePage, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import dayjs from 'dayjs';

const page = usePage();

const props = defineProps({
  offers: Object,
});

const placementOptions = ['homepage', 'search', 'checkout', 'success'];
const placementLabels = {
  homepage: 'Homepage',
  search: 'Search Results',
  checkout: 'Checkout',
  success: 'Booking Success',
};

const isDialogOpen = ref(false);
const isEditing = ref(false);
const processing = ref(false);
const currentOfferId = ref(null);

const form = reactive({
  name: '',
  slug: '',
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
  priority: 0,
  placements: ['homepage'],
  discount_percentage: 0,
  include_free_esim: false,
});

const flashMessage = computed(() => page.props.flash?.success || null);

const resetForm = () => {
  form.name = '';
  form.slug = '';
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
  form.priority = 0;
  form.placements = ['homepage'];
  form.discount_percentage = 0;
  form.include_free_esim = false;
};

const openCreateDialog = () => {
  isEditing.value = false;
  currentOfferId.value = null;
  resetForm();
  isDialogOpen.value = true;
};

const openEditDialog = (offer) => {
  isEditing.value = true;
  currentOfferId.value = offer.id;
  form.name = offer.name || '';
  form.slug = offer.slug || '';
  form.title = offer.title || '';
  form.description = offer.description || '';
  form.button_text = offer.button_text || '';
  form.button_link = offer.button_link || '';
  form.start_date = dayjs(offer.start_date).format('YYYY-MM-DDTHH:mm');
  form.end_date = dayjs(offer.end_date).format('YYYY-MM-DDTHH:mm');
  form.image = null;
  form.imagePreview = offer.image_path || null;
  form.is_active = Boolean(offer.is_active);
  form.is_external = Boolean(offer.is_external);
  form.priority = Number(offer.priority || 0);
  form.placements = Array.isArray(offer.placements) && offer.placements.length ? [...offer.placements] : ['homepage'];
  const discountEffect = (offer.effects || []).find((effect) => effect.type === 'price_discount_percentage');
  form.discount_percentage = Number(discountEffect?.config?.percentage || 0);
  form.include_free_esim = (offer.effects || []).some((effect) => effect.type === 'free_esim');
  isDialogOpen.value = true;
};

const handleImageChange = (event) => {
  const file = event.target.files?.[0] || null;
  form.image = file;
  form.imagePreview = file ? URL.createObjectURL(file) : form.imagePreview;
};

const togglePlacement = (placement) => {
  if (form.placements.includes(placement)) {
    if (form.placements.length === 1) {
      return;
    }
    form.placements = form.placements.filter((value) => value !== placement);
    return;
  }

  form.placements = [...form.placements, placement];
};

const saveOffer = () => {
  processing.value = true;
  const formData = new FormData();

  formData.append('name', form.name);
  formData.append('slug', form.slug);
  formData.append('title', form.title);
  formData.append('description', form.description);
  formData.append('button_text', form.button_text);
  formData.append('button_link', form.button_link);
  formData.append('start_date', form.start_date);
  formData.append('end_date', form.end_date);
  formData.append('is_active', form.is_active ? '1' : '0');
  formData.append('is_external', form.is_external ? '1' : '0');
  formData.append('priority', String(form.priority || 0));
  formData.append('discount_percentage', String(form.discount_percentage || 0));
  formData.append('include_free_esim', form.include_free_esim ? '1' : '0');
  form.placements.forEach((placement) => formData.append('placements[]', placement));

  if (form.image) {
    formData.append('image', form.image);
  }

  const options = {
    onFinish: () => {
      processing.value = false;
    },
    onSuccess: () => {
      isDialogOpen.value = false;
      resetForm();
    },
  };

  if (isEditing.value && currentOfferId.value) {
    formData.append('_method', 'PUT');
    router.post(route('admin.offers.update', currentOfferId.value), formData, options);
    return;
  }

  router.post(route('admin.offers.store'), formData, options);
};

const deleteOffer = (offer) => {
  if (!window.confirm(`Delete offer "${offer.title || offer.name}"?`)) {
    return;
  }

  router.delete(route('admin.offers.destroy', offer.id));
};

const goToPage = (nextPage) => {
  router.get(route('admin.offers.index', { page: nextPage }), {}, { preserveState: true });
};

const effectSummary = (offer) => {
  const labels = [];
  const discountEffect = (offer.effects || []).find((effect) => effect.type === 'price_discount_percentage');
  if (discountEffect?.config?.percentage) {
    labels.push(`${discountEffect.config.percentage}% discount`);
  }
  if ((offer.effects || []).some((effect) => effect.type === 'free_esim')) {
    labels.push('Free eSIM');
  }
  return labels.length ? labels.join(' • ') : 'Display only';
};

const statusLabel = (offer) => {
  const now = dayjs();
  if (!offer.is_active) return 'Inactive';
  if (dayjs(offer.start_date).isAfter(now)) return 'Scheduled';
  if (dayjs(offer.end_date).isBefore(now)) return 'Expired';
  return 'Active';
};

const placementLabel = (placement) => placementLabels[placement] || placement;
</script>

<template>
  <AdminDashboardLayout>
    <div class="container mx-auto p-6 space-y-6">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Offers</h1>
          <p class="text-sm text-muted-foreground mt-1">Manage customer-facing promos and perks from one place.</p>
        </div>

        <Dialog v-model:open="isDialogOpen">
          <DialogTrigger as-child>
            <Button @click="openCreateDialog">Create Offer</Button>
          </DialogTrigger>
          <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{{ isEditing ? 'Edit Offer' : 'Create Offer' }}</DialogTitle>
              <DialogDescription>
                Create simple customer offers here, like a free eSIM or a seasonal discount.
              </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="saveOffer" class="space-y-5">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium">Offer Name</label>
                  <Input v-model="form.name" required placeholder="Example: Free eSIM Offer" />
                  <p class="mt-1 text-xs text-muted-foreground">This is for the admin team to identify the offer.</p>
                </div>
                <div>
                  <label class="text-sm font-medium">Short Link Name</label>
                  <Input v-model="form.slug" placeholder="Optional. Auto-created if left empty." />
                </div>
                <div>
                  <label class="text-sm font-medium">Customer Heading</label>
                  <Input v-model="form.title" required placeholder="Example: Free eSIM With Every Booking" />
                </div>
                <div>
                  <label class="text-sm font-medium">Priority</label>
                  <Input v-model.number="form.priority" type="number" min="0" />
                  <p class="mt-1 text-xs text-muted-foreground">Higher number wins if more than one discount offer is active.</p>
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm font-medium">Offer Description</label>
                  <Textarea v-model="form.description" rows="3" />
                </div>
                <div>
                  <label class="text-sm font-medium">Button Text</label>
                  <Input v-model="form.button_text" placeholder="Example: Book Now" />
                </div>
                <div>
                  <label class="text-sm font-medium">Button Link</label>
                  <Input v-model="form.button_link" placeholder="/en/s or https://example.com" />
                </div>
                <div>
                  <label class="text-sm font-medium">Starts On</label>
                  <Input v-model="form.start_date" type="datetime-local" required />
                </div>
                <div>
                  <label class="text-sm font-medium">Ends On</label>
                  <Input v-model="form.end_date" type="datetime-local" required />
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm font-medium">Offer Image</label>
                  <input type="file" accept="image/*" @change="handleImageChange" class="mt-1 block w-full text-sm" />
                  <img v-if="form.imagePreview" :src="form.imagePreview" alt="Offer preview" class="mt-3 h-28 rounded-lg border object-cover" />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border rounded-lg p-4">
                <div>
                  <label class="text-sm font-semibold">Show This Offer On</label>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <button
                      v-for="placement in placementOptions"
                      :key="placement"
                      type="button"
                      class="px-3 py-1.5 rounded-full border text-sm"
                      :class="form.placements.includes(placement) ? 'bg-primary text-primary-foreground border-primary' : 'bg-background'"
                      @click="togglePlacement(placement)"
                    >
                      {{ placementLabel(placement) }}
                    </button>
                  </div>
                </div>

                <div class="space-y-3">
                  <label class="text-sm font-semibold">What This Offer Gives</label>
                  <div>
                    <label class="text-sm font-medium">Discount Percentage</label>
                    <Input v-model.number="form.discount_percentage" type="number" min="0" max="50" step="0.5" />
                    <p class="text-xs text-muted-foreground mt-1">Example: enter 10 for a 10% discount. Only one discount offer applies at a time.</p>
                  </div>
                  <label class="flex items-center gap-3 text-sm">
                    <input v-model="form.include_free_esim" type="checkbox" class="rounded border-gray-300" />
                    Add free eSIM to every booking
                  </label>
                </div>
              </div>

              <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm">
                  <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                  Offer is active
                </label>
                <label class="flex items-center gap-2 text-sm">
                  <input v-model="form.is_external" type="checkbox" class="rounded border-gray-300" />
                  Open button link outside the site
                </label>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                <Button type="submit" :disabled="processing">
                  {{ processing ? 'Saving...' : (isEditing ? 'Update Offer' : 'Create Offer') }}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div v-if="flashMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
        {{ flashMessage }}
      </div>

      <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow class="bg-muted/50">
                <TableHead>Name</TableHead>
                <TableHead>Effects</TableHead>
                <TableHead>Placements</TableHead>
                <TableHead>Schedule</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="offer in offers.data" :key="offer.id">
                <TableCell>
                  <div class="font-medium">{{ offer.title || offer.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ offer.slug }}</div>
                </TableCell>
                <TableCell>{{ effectSummary(offer) }}</TableCell>
                <TableCell>{{ (offer.placements || []).map(placementLabel).join(', ') }}</TableCell>
                <TableCell class="text-sm">
                  <div>{{ dayjs(offer.start_date).format('DD MMM YYYY HH:mm') }}</div>
                  <div class="text-muted-foreground">to {{ dayjs(offer.end_date).format('DD MMM YYYY HH:mm') }}</div>
                </TableCell>
                <TableCell>
                  <Badge>{{ statusLabel(offer) }}</Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" @click="openEditDialog(offer)">Edit</Button>
                    <Button variant="destructive" size="sm" @click="deleteOffer(offer)">Delete</Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="offers.data.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">No offers found.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <div v-if="offers.last_page > 1" class="flex justify-end pr-2">
        <Pagination :current-page="offers.current_page" :total-pages="offers.last_page" @page-change="goToPage" />
      </div>
    </div>
  </AdminDashboardLayout>
</template>
