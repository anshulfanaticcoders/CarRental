<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { defineProps, ref } from 'vue';
import MediaLibraryModal from '@/Components/MediaLibraryModal.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import InputLabel from "@/Components/InputLabel.vue";
import { Textarea } from "@/Components/ui/textarea";
import { Badge } from "@/Components/ui/badge";
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";
import { Plus, Eye, Edit, Trash2, List, Sparkles, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
  categories: Array,
});

const activeTab = ref(props.categories && props.categories.length > 0 ? props.categories[0].id.toString() : '');
const isBulkDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);
const deleteFeatureId = ref(null);

// Bulk add form
const bulkForm = ref({
  category_id: '',
  featureRows: [
    { name: '', icon_url: '' }
  ],
});
const showBulkMediaModal = ref(false);
const currentEditingRow = ref(null);
const isBulkSubmitting = ref(false);

const addFeatureRow = () => {
  bulkForm.value.featureRows.push({ name: '', icon_url: '' });
};

const removeFeatureRow = (index) => {
  if (bulkForm.value.featureRows.length > 1) {
    bulkForm.value.featureRows.splice(index, 1);
  } else {
    toast.error('At least one feature is required');
  }
};

const openBulkDialog = (categoryId) => {
  bulkForm.value = {
    category_id: categoryId,
    featureRows: [
      { name: '', icon_url: '' }
    ],
  };
  isBulkDialogOpen.value = true;
};

const openBulkMediaModal = (index) => {
  currentEditingRow.value = index;
  showBulkMediaModal.value = true;
};

const handleBulkMediaSelected = (url) => {
  if (currentEditingRow.value !== null) {
    bulkForm.value.featureRows[currentEditingRow.value].icon_url = url;
  }
  showBulkMediaModal.value = false;
  currentEditingRow.value = null;
};

const submitBulkFeatures = () => {
  // Filter out empty rows
  const validFeatures = bulkForm.value.featureRows.filter(f => f.name.trim() !== '');

  if (validFeatures.length === 0) {
    toast.error('Please enter at least one feature name');
    return;
  }

  isBulkSubmitting.value = true;

  // Create all features
  const promises = validFeatures.map(feature => {
    return router.post(route('admin.features.store'), {
      category_id: bulkForm.value.category_id,
      feature_name: feature.name.trim(),
      icon_url: feature.icon_url,
    });
  });

  Promise.all(promises)
    .then(() => {
      toast.success(`${validFeatures.length} feature(s) added successfully`);
      isBulkDialogOpen.value = false;
      isBulkSubmitting.value = false;
    })
    .catch(() => {
      toast.error('Some features failed to add');
      isBulkSubmitting.value = false;
    });
};

const openDeleteDialog = (id) => {
  deleteFeatureId.value = id;
  isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
  isDeleting.value = true;
  router.delete(route('admin.features.destroy', deleteFeatureId.value), {
    onSuccess: () => {
      toast.success('Feature deleted successfully');
      isDeleteDialogOpen.value = false;
      isDeleting.value = false;
    },
    onError: () => {
      toast.error('Failed to delete feature');
      isDeleting.value = false;
    }
  });
};
</script>

<template>
  <Head title="Manage Vehicle Features" />

  <AdminDashboardLayout>
    <div class="container mx-auto p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Features Management</h1>
          <p class="text-sm text-muted-foreground mt-1">Manage vehicle features by category</p>
        </div>
      </div>

      <!-- Alert Dialog for Delete Confirmation -->
      <Dialog v-model:open="isDeleteDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Are you sure?</DialogTitle>
          </DialogHeader>
          <p class="text-sm text-muted-foreground">Do you really want to delete this feature? This action cannot be undone.</p>
          <DialogFooter>
            <Button variant="outline" @click="isDeleteDialogOpen = false">Cancel</Button>
            <Button variant="destructive" @click="confirmDelete" :disabled="isDeleting">
              <span v-if="isDeleting" class="flex items-center gap-2">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                Deleting...
              </span>
              <span v-else>Delete</span>
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Bulk Add Dialog -->
      <Dialog v-model:open="isBulkDialogOpen">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Bulk Add Features</DialogTitle>
            <p class="text-sm text-muted-foreground">Add multiple features at once with individual icons</p>
          </DialogHeader>
          <div class="space-y-4">
            <!-- Feature Rows -->
            <div class="space-y-3">
              <div v-for="(row, index) in bulkForm.featureRows" :key="index" class="flex items-center gap-2 p-3 border rounded-lg bg-muted/20">
                <span class="text-sm font-mono text-muted-foreground w-8">{{ index + 1 }}</span>
                <div class="flex-1">
                  <Input
                    v-model="row.name"
                    placeholder="Enter feature name"
                    class="w-full"
                  />
                </div>
                <div class="w-[300px]">
                  <Input
                    v-model="row.icon_url"
                    placeholder="Icon URL (optional)"
                    class="w-full"
                  />
                </div>
                <Button type="button" variant="outline" size="sm" @click="openBulkMediaModal(index)">
                  <Sparkles class="w-4 h-4" />
                </Button>
                <div v-if="row.icon_url" class="w-10 h-10 flex-shrink-0">
                  <img :src="row.icon_url" alt="Preview" class="w-full h-full object-cover rounded" />
                </div>
                <Button type="button" variant="destructive" size="sm" @click="removeFeatureRow(index)" :disabled="bulkForm.featureRows.length === 1">
                  <Trash2 class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <!-- Add More Button -->
            <Button type="button" variant="outline" @click="addFeatureRow" class="w-full">
              <Plus class="w-4 h-4 mr-2" />
              Add Another Feature
            </Button>

            <p class="text-xs text-muted-foreground">
              Tip: You can add different icons for each feature by clicking the sparkle button.
            </p>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="isBulkDialogOpen = false">Cancel</Button>
            <Button @click="submitBulkFeatures" :disabled="isBulkSubmitting">
              <span v-if="isBulkSubmitting" class="flex items-center gap-2">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                Adding...
              </span>
              <span v-else>Add Features</span>
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <MediaLibraryModal :show="showBulkMediaModal" @close="showBulkMediaModal = false" @media-selected="handleBulkMediaSelected" />

      <!-- Categories Tabs -->
      <div v-if="categories && categories.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="w-full justify-start rounded-none border-b bg-muted/50 p-0">
            <TabsTrigger
              v-for="category in categories"
              :key="category.id"
              :value="category.id.toString()"
              class="data-[state=active]:bg-background data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none px-6 py-3"
            >
              {{ category.name }}
            </TabsTrigger>
          </TabsList>

          <TabsContent v-for="category in categories" :key="`content-${category.id}`" :value="category.id.toString()" class="p-6">
            <!-- Category Header -->
            <div class="flex justify-between items-center mb-6">
              <div>
                <h3 class="text-2xl font-bold">{{ category.name }}</h3>
                <p class="text-sm text-muted-foreground">{{ category.features?.length || 0 }} features</p>
              </div>
              <div class="flex items-center gap-2">
                <Link :href="route('admin.features.create', category.id)">
                  <Button variant="outline" size="sm" class="flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Add Single Feature
                  </Button>
                </Link>
                <Button @click="openBulkDialog(category.id)" class="flex items-center gap-2">
                  <List class="w-4 h-4" />
                  Bulk Add Features
                </Button>
              </div>
            </div>

            <!-- Features Table -->
            <div v-if="category.features && category.features.length > 0">
              <div class="rounded-lg border overflow-hidden">
                <table class="w-full">
                  <thead class="bg-muted/50">
                    <tr>
                      <th class="px-4 py-3 text-left text-sm font-semibold">ID</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold">Feature Name</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold">Icon</th>
                      <th class="px-4 py-3 text-right text-sm font-semibold">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y">
                    <tr v-for="feature in category.features" :key="feature.id" class="hover:bg-muted/25 transition-colors">
                      <td class="px-4 py-3 text-sm font-medium">{{ feature.id }}</td>
                      <td class="px-4 py-3">
                        <span class="font-medium">{{ feature.feature_name }}</span>
                      </td>
                      <td class="px-4 py-3">
                        <img v-if="feature.icon_url" :src="feature.icon_url" :alt="feature.feature_name" class="h-8 w-8 object-cover rounded">
                        <span v-else class="text-muted-foreground text-sm">No Icon</span>
                      </td>
                      <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                          <Link :href="route('admin.features.edit', feature.id)">
                            <Button size="sm" variant="outline" class="flex items-center gap-1">
                              <Edit class="w-3 h-3" />
                              Edit
                            </Button>
                          </Link>
                          <Button size="sm" variant="destructive" @click="openDeleteDialog(feature.id)" class="flex items-center gap-1">
                            <Trash2 class="w-3 h-3" />
                            Delete
                          </Button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-lg border bg-muted/20 p-12 text-center">
              <AlertCircle class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
              <h3 class="text-lg font-semibold mb-2">No features yet</h3>
              <p class="text-muted-foreground mb-4">Add features to {{ category.name }} category</p>
              <div class="flex items-center justify-center gap-2">
                <Link :href="route('admin.features.create', category.id)">
                  <Button variant="outline" size="sm" class="flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Add Single Feature
                  </Button>
                </Link>
                <Button @click="openBulkDialog(category.id)" class="flex items-center gap-2">
                  <List class="w-4 h-4" />
                  Bulk Add Features
                </Button>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      <!-- No Categories State -->
      <div v-else class="rounded-xl border bg-card p-12 text-center">
        <AlertCircle class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
        <h3 class="text-xl font-semibold mb-2">No Categories Found</h3>
        <p class="text-muted-foreground">Please add vehicle categories first.</p>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
