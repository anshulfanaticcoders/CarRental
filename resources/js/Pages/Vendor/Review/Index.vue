<!-- Pages/Vendor/Review/Index.vue -->
<script setup>
import { computed, ref, watch, getCurrentInstance } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
 import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { router } from '@inertiajs/vue3'
import { Star, MessageSquare, Search } from 'lucide-vue-next'

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
  const v = _t(group, key);
  return (!v || v === key) ? fallback : v;
};
const vrStatus = (status) => ({ approved: 'ok', pending: 'warn', rejected: 'bad' }[status] || 'mut');

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

      <div>
        <!-- Header -->
        <div class="vr-phead">
          <div>
            <span class="vr-eyebrow"><Star /> {{ tt('vendorprofilepages', 'reputation_eyebrow', 'Reputation') }}</span>
            <h2>{{ tt('vendorprofilepages', 'customer_reviews_header', 'Customer Reviews') }}</h2>
            <p class="vr-sub">{{ tt('vendorprofilepages', 'customer_reviews_subtitle', 'See what customers say about your fleet.') }}</p>
          </div>
        </div>

        <!-- Stats -->
        <div class="vr-stat-grid c2">
          <div class="vr-stat">
            <div class="vr-ic vr-ic-teal"><MessageSquare /></div>
            <div class="vr-v">{{ statistics.total_reviews || 0 }}</div>
            <div class="vr-l">{{ tt('vendorprofilepages', 'total_reviews_label', 'Total Reviews') }}</div>
          </div>
          <div class="vr-stat">
            <div class="vr-ic vr-ic-amber"><Star /></div>
            <div class="vr-v">{{ averageRating }} <span style="font-size:1rem;color:#64748b">/5</span></div>
            <div class="vr-l">{{ tt('vendorprofilepages', 'average_rating_label', 'Average Rating') }}</div>
          </div>
        </div>

        <!-- Search -->
        <div class="vr-toolbar">
          <label class="vr-search">
            <Search />
            <input v-model="searchQuery" type="text"
              :placeholder="_t('vendorprofilepages', 'search_reviews_placeholder')" />
          </label>
        </div>

        <!-- Reviews Table -->
        <div class="vr-panel">
          <div v-if="filteredReviews.length" class="overflow-x-auto">
            <table>
              <thead>
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
                  <td>{{ (reviews.current_page - 1) * reviews.per_page + index + 1 }}</td>
                  <td>
                    <span class="vr-cust">
                      <img v-if="review.user?.profile?.avatar" :src="`${review.user.profile.avatar}`"
                        class="vr-ava-img" :alt="review.user?.first_name">
                      <span v-else class="vr-ava">{{ review.user?.first_name?.charAt(0).toUpperCase() || '?' }}</span>
                      <span class="cell-strong">{{ review.user?.first_name }} {{ review.user?.last_name }}</span>
                    </span>
                  </td>
                  <td>
                    <span class="cell-strong">{{ review.vehicle?.brand }}</span>
                    <span class="vr-vbadge">{{ review.vehicle?.model }}</span>
                  </td>
                  <td>
                    <span :class="getRatingColor(review.rating)" class="font-semibold">{{ review.rating }}/5</span>
                  </td>
                  <td style="white-space:normal;max-width:320px">
                    <div>{{ review.review_text }}</div>
                    <div v-if="review.reply_text" class="vr-mut" style="margin-top:6px">
                      <span class="font-medium">{{ _t('vendorprofilepages', 'text_reply_prefix') }}</span> {{ review.reply_text }}
                    </div>
                  </td>
                  <td>{{ formatDate(review.created_at) }}</td>
                  <td>
                    <span class="vr-chip capitalize" :class="vrStatus(review.status)">{{ review.status }}</span>
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
          <div v-else class="vr-empty">
            <div class="e-ic"><Star /></div>
            <h4>{{ tt('vendorprofilepages', 'no_reviews_found', 'No reviews found') }}</h4>
            <p>{{ tt('vendorprofilepages', 'customer_reviews_subtitle', 'See what customers say about your fleet.') }}</p>
          </div>
          <div v-if="filteredReviews.length" class="vr-pager">
            <span class="info"></span>
            <Pagination :current-page="reviews.current_page" :total-pages="reviews.last_page"
              @page-change="handlePageChange" />
          </div>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<style scoped>
.vr-ava-img {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  object-fit: cover;
}
</style>
