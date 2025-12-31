<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { defineProps, ref } from 'vue';
import MediaLibraryModal from '@/Components/MediaLibraryModal.vue';
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";

const props = defineProps({
  category: Object,
  errors: Object,
});

const { flash } = usePage().props;

const form = useForm({
  category_id: props.category.id,
  feature_name: '',
  icon_url: '',
});

const showMediaModal = ref(false);

const openMediaModal = () => {
  showMediaModal.value = true;
};

const handleMediaSelected = (url) => {
  form.icon_url = url;
  showMediaModal.value = false;
};

const submit = () => {
  form.post(route('admin.features.store'), {
    onSuccess: () => {
      toast.success('Feature added successfully');
    },
    onError: () => {
      toast.error('Failed to add feature');
    }
  });
};
</script>

<template>
  <Head :title="'Add Feature to ' + category.name" />

  <AdminDashboardLayout>
    <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
      <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
    </div>

    <div class="py-12 px-4 sm:px-6 lg:px-8">
      <div class="mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-8">
            <div class="flex items-center space-x-4 mb-8">
              <div class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">Add Feature to {{ category.name }}</h1>
                <p class="text-gray-600">Create a new feature for this category</p>
              </div>
            </div>

            <div v-if="flash && flash.success" class="mb-6 p-4 bg-green-100 text-green-700 border border-green-300 rounded-lg">
              {{ flash.success }}
            </div>
            <div v-if="flash && flash.error" class="mb-6 p-4 bg-red-100 text-red-700 border border-red-300 rounded-lg">
              {{ flash.error }}
            </div>

            <form @submit.prevent>
              <input type="hidden" v-model="form.category_id" />

              <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Feature Details</h3>
                <div class="grid grid-cols-1 gap-6">
                  <!-- Feature Name -->
                  <div>
                    <label for="feature_name" class="block text-sm font-medium text-gray-700 mb-2">
                      Feature Name <span class="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      id="feature_name"
                      v-model="form.feature_name"
                      required
                      aria-required="true"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                      placeholder="Enter feature name"
                    />
                    <div v-if="errors && errors.feature_name" class="mt-2 text-sm text-red-600">
                      {{ errors.feature_name }}
                    </div>
                  </div>

                  <!-- Icon URL -->
                  <div>
                    <label for="icon_url" class="block text-sm font-medium text-gray-700 mb-2">
                      Feature Icon <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-4">
                      <div class="flex-1">
                        <input
                          type="url"
                          id="icon_url"
                          v-model="form.icon_url"
                          aria-required="true"
                          class="w-full px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                          placeholder="Select or enter icon URL"
                        />
                      </div>
                      <button
                        type="button"
                        @click="openMediaModal"
                        class="inline-flex items-center px-4 py-3 border border-customPrimaryColor text-customPrimaryColor bg-white rounded-r-lg hover:bg-customLightPrimaryColor/10 transition-colors duration-200 text-sm font-medium"
                      >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Choose from Library
                      </button>
                    </div>
                    <div v-if="form.icon_url" class="mt-4">
                      <img :src="form.icon_url" alt="Feature Icon Preview" class="h-16 w-16 object-cover rounded-lg ring-2 ring-customLightPrimaryColor/20" />
                    </div>
                    <div v-if="errors && errors.icon_url" class="mt-2 text-sm text-red-600">
                      {{ errors.icon_url }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                  <AlertDialog>
                    <AlertDialogTrigger as-child>
                      <Button
                        :disabled="form.processing"
                        class="inline-flex items-center px-6 py-3 bg-customPrimaryColor text-white rounded-lg hover:bg-customPrimaryColor/90 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium"
                      >
                        <svg
                          v-if="form.processing"
                          class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          ></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width-2 d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ form.processing ? 'Adding...' : 'Add Feature' }}
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Add Feature?</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to add this feature to {{ category.name }}?
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="submit">Continue</AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>

                  <Link
                    :href="route('admin.features.index')"
                    class="inline-flex items-center px-6 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    Cancel
                  </Link>
                </div>

                <div v-if="form.processing" class="flex items-center text-customPrimaryColor">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  <span class="text-sm font-medium">Adding feature...</span>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <MediaLibraryModal :show="showMediaModal" @close="showMediaModal = false" @media-selected="handleMediaSelected" />
  </AdminDashboardLayout>
</template>

<style>
.text-customPrimaryColor {
  color: var(--custom-primary);
}

.bg-customPrimaryColor {
  background-color: var(--custom-primary);
}

.border-customPrimaryColor {
  border-color: var(--custom-primary);
}

.ring-customPrimaryColor {
  --tw-ring-color: var(--custom-primary);
}

.focus\:ring-customPrimaryColor:focus {
  --tw-ring-color: var(--custom-primary);
}

.focus\:border-customPrimaryColor:focus {
  --tw-border-opacity: 1;
  border-color: var(--custom-primary);
}

.hover\:bg-customPrimaryColor\/90:hover {
  background-color: color-mix(in srgb, var(--custom-primary) 90%, transparent);
}

.bg-customLightPrimaryColor {
  background-color: var(--custom-light-primary);
}

.hover\:bg-customLightPrimaryColor\/10:hover {
  background-color: color-mix(in srgb, var(--custom-light-primary) 10%, transparent);
}

.ring-customLightPrimaryColor\/20 {
  --tw-ring-color: color-mix(in srgb, var(--custom-light-primary) 20%, transparent);
}
</style>