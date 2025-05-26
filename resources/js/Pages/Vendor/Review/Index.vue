<!-- Pages/Vendor/Review/Index.vue -->
<script setup>
import { computed, ref, watch, getCurrentInstance } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
import Pagination from './Pagination.vue'
import { router } from '@inertiajs/vue3'

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
  reviews: {
    type: Object,
    required: true
  },
  statistics: {
    type: Object,
    required: true
  }
})

const form = useForm({
  status: ''
});

const searchQuery = ref('');
watch(searchQuery, (newQuery) => {
  router.get(
    route('vendor.reviews'),
    { search: newQuery },
    { preserveState: true, preserveScroll: true }
  );
});

const filteredReviews = computed(() => {
  if (!searchQuery.value) return props.reviews.data
  return props.reviews.data.filter(review => 
    review.user?.first_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    review.user?.last_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    review.vehicle?.brand?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    review.vehicle?.model?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    review.review_text?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
});

const averageRating = computed(() => {
  const rating = Number(props.statistics.average_rating)
  return isNaN(rating) ? '0.0' : rating.toFixed(1)
});

const getRatingColor = (rating) => {
  if (!rating) return 'text-gray-400'
  const numRating = Number(rating)
  if (numRating >= 4) return 'text-green-600'
  if (numRating >= 3) return 'text-yellow-600'
  return 'text-red-600'
};

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
};

const updateStatus = (review, status) => {
  form.status = status
  form.patch(`/reviews/${review.id}/status`, {
    preserveScroll: true,
    onSuccess: () => {
      // Show success message if needed
    }
  });
};

const handlePageChange = (page) => {
    router.get(
        route('vendor.reviews'),
        { ...props.filters, page },
        { preserveState: true, preserveScroll: true }
    )
};
</script>

<template>
  <MyProfileLayout>
    <div>
      <Head :title="_t('vendorprofilepages', 'customer_reviews_header')" />

      <div class="">
        <!-- Statistics Header -->
        <p class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[2rem] max-[768px]:text-[1.2rem]">{{ _t('vendorprofilepages', 'customer_reviews_header') }}</p>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
              <h3 class="text-lg font-medium text-gray-900">{{ _t('vendorprofilepages', 'total_reviews_label') }}</h3>
              <p class="text-3xl font-bold text-gray-700">{{ statistics.total_reviews || 0 }}</p>
            </div>
            <div class="text-center">
              <h3 class="text-lg font-medium text-gray-900">{{ _t('vendorprofilepages', 'average_rating_label') }}</h3>
              <p class="text-3xl font-bold" :class="getRatingColor(statistics.average_rating)">
                {{ averageRating }} <span class="text-lg">/5</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Search Input -->
        <div class="mb-4">
          <input 
            v-model="searchQuery" 
            type="text" 
            :placeholder="_t('vendorprofilepages', 'search_reviews_placeholder')" 
            class="px-4 py-2 border border-gray-300 rounded-md w-full">
        </div>

        <!-- Reviews Table -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _t('vendorprofilepages', 'table_id_header') }}</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'table_customer_header') }}
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'table_vehicle_header') }}
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'table_rating_header') }}
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'table_review_header') }}
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'table_date_header') }}
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'status_table_header') }}
                  </th>
                  <!-- <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ _t('vendorprofilepages', 'actions_table_header') }}
                  </th> -->
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(review, index) in filteredReviews" :key="review.id">
                  <td class="px-6 py-4 whitespace-nowrap">{{ (reviews.current_page - 1) * reviews.per_page + index + 1 }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <img v-if="review.user?.profile?.avatar" 
                             :src="`${review.user.profile.avatar}`"
                             class="h-10 w-10 rounded-full object-cover"
                             :alt="review.user?.first_name">
                        <div v-else 
                             class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                          <span class="text-gray-500 text-sm">
                            {{ review.user?.first_name?.charAt(0).toUpperCase() || '?' }}
                          </span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ review.user?.first_name }} {{ review.user?.last_name }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ review.vehicle?.brand }} <span class="bg-customLightPrimaryColor p-1 rounded-[12px] ml-1">{{ review.vehicle?.model }}</span></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div :class="getRatingColor(review.rating)" class="text-sm font-medium">
                      {{ review.rating }}/5
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">{{ review.review_text }}</div>
                    <div v-if="review.reply_text" class="mt-2 text-sm text-gray-500">
                      <span class="font-medium">{{ _t('vendorprofilepages', 'text_reply_prefix') }}</span> {{ review.reply_text }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ formatDate(review.created_at) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                          :class="getStatusColor(review.status)">
                      {{ review.status }}
                    </span>
                  </td>
                  <!-- <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button
                        @click="updateStatus(review, 'approved')"
                        :disabled="review.status === 'approved'"
                        :class="{
                          'bg-green-600 hover:bg-green-700': review.status !== 'approved',
                          'bg-gray-400': review.status === 'approved'
                        }"
                        class="px-3 py-1 rounded text-white text-sm">
                        {{ _t('vendorprofilepages', 'button_approve') }}
                      </button>
                      <button
                        @click="updateStatus(review, 'rejected')"
                        :disabled="review.status === 'rejected'"
                        :class="{
                          'bg-red-600 hover:bg-red-700': review.status !== 'rejected',
                          'bg-gray-400': review.status === 'rejected'
                        }"
                        class="px-3 py-1 rounded text-white text-sm">
                        {{ _t('vendorprofilepages', 'button_reject') }}
                      </button>
                    </div>
                  </td> -->
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
         <div class="mt-[1rem] flex justify-end">
        <Pagination :current-page="reviews.current_page" :total-pages="reviews.last_page"
        @page-change="handlePageChange" />
      </div>
      </div>
    </div>
  </MyProfileLayout>
</template>
