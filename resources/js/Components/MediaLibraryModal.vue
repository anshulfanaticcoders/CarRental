<script setup>
import { ref, defineEmits, defineProps, onMounted, watch } from 'vue';
import axios from 'axios'; // Using axios for a direct API call to fetch media

const props = defineProps({
  show: Boolean,
});

const emits = defineEmits(['close', 'media-selected']);

const mediaItems = ref({ data: [], links: [], current_page: 1 });
const isLoading = ref(false);
const currentPage = ref(1);

async function fetchMedia(page = 1) {
  if (isLoading.value) return;
  isLoading.value = true;
  try {
    // Note: This makes a direct API call.
    // For Inertia-driven apps, you might typically navigate or use Inertia partial reloads.
    // However, for a modal that needs to fetch data independently, a direct API call is common.
    // This route 'api.admin.media.list' would need to be created.
    // For now, let's assume the existing 'admin.media.index' can return JSON if requested appropriately,
    // or we create a dedicated API endpoint.
    // Let's try to use the existing Inertia endpoint with an axios call, expecting JSON.
    const response = await axios.get(route('admin.media.index', { page: page }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    });
    mediaItems.value = response.data.mediaItems; // Assuming controller returns { mediaItems: {data: [], ...} }
    currentPage.value = response.data.mediaItems.current_page;
  } catch (error) {
    console.error('Failed to fetch media:', error);
    // Handle error (e.g., show a message)
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  if (props.show) {
    fetchMedia();
  }
});

watch(() => props.show, (newVal) => {
  if (newVal && mediaItems.value.data.length === 0) { // Fetch only if modal becomes visible and no data
    fetchMedia();
  }
});

const selectMedia = (media) => {
  emits('media-selected', media.url);
  emits('close');
};

const closeModal = () => {
  emits('close');
};

const onPageChange = (pageUrl) => {
  if (pageUrl) {
    const url = new URL(pageUrl);
    const page = url.searchParams.get('page');
    if (page) {
      fetchMedia(parseInt(page));
    }
  }
};

const getThumbnail = (media) => {
  if (media.mime_type && media.mime_type.startsWith('image/')) {
    return media.url;
  }
  return 'https://via.placeholder.com/100?text=No+Preview';
};

</script>

<template>
  <div v-if="show" class="fixed inset-0 z-[999999] overflow-y-auto pointer-events-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Modal panel -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative z-[1000000] pointer-events-auto">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                Select Media
              </h3>
              <div class="mt-4">
                <div v-if="isLoading" class="text-center">Loading media...</div>
                <div v-else>
                  <div v-if="mediaItems.data && mediaItems.data.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 max-h-96 overflow-y-auto">
                    <div v-for="item in mediaItems.data" :key="item.id"
                         class="border rounded-lg overflow-hidden shadow cursor-pointer hover:shadow-md"
                         @click="selectMedia(item)">
                      <img :src="getThumbnail(item)" :alt="item.title || item.filename" class="w-full h-24 object-cover bg-gray-100">
                      <p class="text-xs p-1 truncate" :title="item.title || item.filename">{{ item.title || item.filename }}</p>
                    </div>
                  </div>
                  <div v-else class="text-center text-gray-500 py-8">
                    <p>No media files found. <a :href="route('admin.media.index')" class="text-blue-500 hover:underline">Upload some?</a></p>
                  </div>
                  <!-- Pagination for Modal -->
                   <div v-if="mediaItems && mediaItems.links && mediaItems.links.length > 3" class="mt-4">
                        <div class="flex justify-center">
                            <button
                                v-for="(link, index) in mediaItems.links"
                                :key="index"
                                @click.prevent="onPageChange(link.url)"
                                :disabled="!link.url || link.active"
                                v-html="link.label"
                                class="px-3 py-1 mx-1 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="{ 'bg-blue-500 text-white': link.active }"
                            ></button>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="button" @click="closeModal"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
