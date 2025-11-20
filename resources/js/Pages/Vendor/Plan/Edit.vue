<template>
  <MyProfileLayout>
    <div class="container mx-auto p-6 space-y-6">
      <!-- Enhanced Header -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-6 shadow-sm max-w-4xl mx-auto">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
              <FileText class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ _t('vendorprofilepages', 'edit_plan_title') }}</h1>
              <p class="text-sm text-gray-600 mt-1">Update your vehicle rental plan details</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="text-right">
              <Badge variant="secondary" class="mb-1">
                Plan #{{ props.plan.id }}
              </Badge>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Form -->
      <div class="max-w-4xl mx-auto">
        <div class="rounded-xl border bg-card shadow-sm p-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4">
              <div>
                <Label for="plan_type" class="flex items-center gap-2">
                  <FileText class="w-4 h-4" />
                  {{ _t('vendorprofilepages', 'edit_plan_plan_type_label') }}
                </Label>
                <Input
                  id="plan_type"
                  v-model="form.plan_type"
                  placeholder="Enter plan type"
                  class="mt-1"
                  required
                />
                <div v-if="errors.plan_type" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 mt-2">
                  {{ errors.plan_type }}
                </div>
              </div>

              <div>
                <Label for="price" class="flex items-center gap-2">
                  <DollarSign class="w-4 h-4" />
                  {{ _t('vendorprofilepages', 'edit_plan_price_label') }}
                </Label>
                <Input
                  id="price"
                  type="number"
                  step="0.01"
                  v-model="form.plan_value"
                  placeholder="Enter price"
                  class="mt-1"
                  required
                />
                <div v-if="errors.plan_value" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 mt-2">
                  {{ errors.plan_value }}
                </div>
              </div>

              <div>
                <Label for="plan_description" class="flex items-center gap-2">
                  <FileText class="w-4 h-4" />
                  {{ _t('vendorprofilepages', 'edit_plan_description_label') }}
                </Label>
                <Textarea
                  id="plan_description"
                  v-model="form.plan_description"
                  placeholder="Enter plan description"
                  rows="4"
                  class="mt-1 resize-none"
                />
                <div v-if="errors.plan_description" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 mt-2">
                  {{ errors.plan_description }}
                </div>
              </div>

              <div>
                <Label class="flex items-center gap-2">
                  <Star class="w-4 h-4" />
                  {{ _t('vendorprofilepages', 'edit_plan_features_label') }}
                </Label>
                <div class="space-y-3">
                  <div v-for="(feature, index) in form.features" :key="index" class="flex gap-2">
                    <Input
                      type="text"
                      v-model="form.features[index]"
                      :placeholder="_t('vendorprofilepages', 'edit_plan_feature_description_placeholder')"
                      class="flex-1"
                    />
                    <Button
                      type="button"
                      @click="removeFeature(index)"
                      variant="destructive"
                      size="sm"
                      class="flex items-center gap-1"
                    >
                      <X class="w-4 h-4" />
                    </Button>
                  </div>
                  <Button
                    type="button"
                    @click="addFeature"
                    variant="outline"
                    size="sm"
                    class="flex items-center gap-2 w-full"
                  >
                    <Plus class="w-4 h-4" />
                    {{ _t('vendorprofilepages', 'edit_plan_add_feature_button') }}
                  </Button>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t">
              <Link :href="route('VendorPlanIndex', { locale: usePage().props.locale })">
                <Button variant="outline" class="flex items-center gap-2">
                  <X class="w-4 h-4" />
                  {{ _t('vendorprofilepages', 'edit_plan_cancel_button') }}
                </Button>
              </Link>
              <Button type="submit" :disabled="form.processing" class="flex items-center gap-2">
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                {{ _t('vendorprofilepages', 'edit_plan_save_button') }}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    FileText,
    DollarSign,
    Star,
    Plus,
    X,
    Save,
    Loader2,
} from 'lucide-vue-next';

// Define props
const props = defineProps({
  plan: Object,
  errors: Object,
});

// Parse features if they're stored as JSON string
let features = [];
try {
  features = props.plan.features ? JSON.parse(props.plan.features) : [];
} catch (e) {
  features = Array.isArray(props.plan.features) ? props.plan.features : [];
}

// Create form with initial data
const form = useForm({
  plan_type: props.plan.plan_type || '',
  plan_value: props.plan.price,
  plan_description: props.plan.plan_description || '',
  features: features
});

// Add a new empty feature
function addFeature() {
  form.features.push('');
}

// Remove a feature by index
function removeFeature(index) {
  form.features.splice(index, 1);
}

// Submit function
function submit() {
  form.put(route('VendorPlanUpdate', { locale: usePage().props.locale, id: props.plan.id }));
}
</script>
