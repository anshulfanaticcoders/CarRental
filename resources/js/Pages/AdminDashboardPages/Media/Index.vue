<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Dialog, DialogContent } from '@/Components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/toast/use-toast';
import { Toaster } from '@/Components/ui/toast';
import { ref } from 'vue';

const props = defineProps({
  mediaItems: Object,
  uploadDisk: String,
  uploadDirectory: String,
  errors: Object,
});

const { flash } = usePage().props;
const { toast } = useToast();

const uploadForm = useForm({
  files: [],
});

const fileInput = ref(null);

const handleFileChange = (event) => {
  uploadForm.files = Array.from(event.target.files);
};

const submitUpload = () => {
  if (uploadForm.files.length === 0) {
    toast({
      title: 'No Files Selected',
      description: 'Please select one or more files to upload.',
      variant: 'destructive',
    });
    return;
  }
  uploadForm.post(route('admin.media.store'), {
    preserveScroll: true,
    onSuccess: () => {
      uploadForm.reset('files');
      if (fileInput.value) {
        fileInput.value.value = '';
      }
      toast({
        title: 'Upload Successful',
        description: 'Media files have been uploaded successfully.',
        class: 'bg-customDarkBlackColor text-white border-none p-4 rounded-md',
      });
    },
    onError: (errors) => {
      toast({
        title: 'Upload Failed',
        description: errors.files || 'An error occurred while uploading the files.',
        variant: 'destructive',
      });
    },
  });
};

const isDeleteDialogOpen = ref(false);
const mediaToDelete = ref(null);

const openDeleteDialog = (mediaId) => {
  mediaToDelete.value = mediaId;
  isDeleteDialogOpen.value = true;
};

const deleteMedia = () => {
  if (mediaToDelete.value) {
    router.delete(route('admin.media.destroy', mediaToDelete.value), {
      preserveScroll: true,
      onSuccess: () => {
        isDeleteDialogOpen.value = false;
        mediaToDelete.value = null;
        toast({
          title: 'Media Deleted',
          description: 'The media file has been successfully deleted.',
          class: 'bg-customDarkBlackColor text-white border-none p-4 rounded-md',
        });
      },
      onError: (errors) => {
        isDeleteDialogOpen.value = false;
        mediaToDelete.value = null;
        toast({
          title: 'Deletion Failed',
          description: errors.message || 'An error occurred while deleting the media file.',
          variant: 'destructive',
        });
      },
    });
  }
};

const cancelDelete = () => {
  isDeleteDialogOpen.value = false;
  mediaToDelete.value = null;
};

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    toast({
      title: 'URL Copied',
      description: 'The media URL has been copied to the clipboard.',
      class: 'bg-customDarkBlackColor text-white border-none p-4 rounded-md',
    });
  } catch (err) {
    toast({
      title: 'Copy Failed',
      description: 'Failed to copy the URL to the clipboard.',
      variant: 'destructive',
    });
  }
};

const getThumbnail = (media) => {
  if (media.mime_type && media.mime_type.startsWith('image/')) {
    return media.url;
  }
  return 'https://via.placeholder.com/100?text=No+Preview';
};

const handlePageChange = (page) => {
  router.get(route('admin.media.index', { page }), {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const isLightboxOpen = ref(false);
const currentLightboxImage = ref({ src: '', alt: '' });

const openLightbox = (item) => {
  if (item.mime_type && item.mime_type.startsWith('image/') && item.url) {
    currentLightboxImage.value = { src: item.url, alt: item.title || item.filename };
    isLightboxOpen.value = true;
  }
};

const cancelUploadSelection = () => {
  uploadForm.reset('files');
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};
</script>

<template>
  <Head title="Media Library" />

  <AdminDashboardLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media Library</h2>
    </template>

    <div class="py-8">
      <div class="mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div v-if="flash && flash.success" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded">
              {{ flash.success }}
            </div>
            <div v-if="flash && flash.error" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded">
              {{ flash.error }}
            </div>
            <div v-if="uploadForm.errors.files" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded">
              {{ typeof uploadForm.errors.files === 'string' ? uploadForm.errors.files : 'Error with one or more files.' }}
              <ul v-if="typeof uploadForm.errors.files === 'object'">
                <li v-for="(error, index) in uploadForm.errors.files" :key="index">{{ error }}</li>
              </ul>
            </div>

            <!-- Upload Form -->
            <form @submit.prevent="submitUpload" class="mb-8 p-4 border rounded-lg">
              <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Upload New Media</h3>
              <div>
                <div class="flex items-center gap-2">
                  <input type="file" @change="handleFileChange" ref="fileInput" id="file-upload"
                         class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                         multiple required />
                  <button type="submit"
                          :disabled="uploadForm.processing || uploadForm.files.length === 0"
                          class="px-4 py-2 bg-customPrimaryColor text-white rounded-md hover:bg-customPrimaryColor disabled:opacity-50 whitespace-nowrap">
                    Upload
                  </button>
                  <button type="button" @click="cancelUploadSelection"
                          v-if="uploadForm.files.length > 0"
                          class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 whitespace-nowrap">
                    Cancel
                  </button>
                </div>
                </div>
              </form>

            <!-- Media Grid -->
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Uploaded Media</h3>
            <div v-if="mediaItems && mediaItems.data && mediaItems.data.length > 0"
                 class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
              <div v-for="item in mediaItems.data" :key="item.id" class="border rounded-lg overflow-hidden shadow">
                <img :src="getThumbnail(item)" :alt="item.title || item.filename"
                     class="w-full h-32 object-cover bg-gray-100 cursor-pointer hover:opacity-80 transition-opacity"
                     @click="openLightbox(item)">
                <div class="p-2">
                  <p class="text-sm font-medium text-gray-900 truncate" :title="item.title || item.filename">
                    {{ item.title || item.filename }}
                  </p>
                  <p class="text-xs text-gray-500">{{ item.size }}</p>
                  <div class="mt-2 flex flex-wrap gap-1">
                    <button @click="copyToClipboard(item.url)"
                            class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-2 rounded">
                      Copy URL
                    </button>
                    <AlertDialog>
                      <AlertDialogTrigger as-child>
                        <Button @click="openDeleteDialog(item.id)"
                                class="text-xs bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded">
                          Delete
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Delete Media File?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Are you sure you want to delete this media file? This action cannot be undone.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel @click="cancelDelete">Cancel</AlertDialogCancel>
                          <AlertDialogAction @click="deleteMedia">Delete</AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 py-8">
              <p>No media files found.</p>
            </div>

            <!-- Pagination -->
            <div v-if="mediaItems && mediaItems.last_page > 1" class="mt-6">
              <Pagination
                :current-page="mediaItems.current_page"
                :total-pages="mediaItems.last_page"
                @page-change="handlePageChange"
              />
            </div>

            <!-- Lightbox Dialog -->
            <Dialog v-model:open="isLightboxOpen">
              <DialogContent 
                class="sm:max-w-[90vw] md:max-w-[80vw] lg:max-w-[70vw] xl:max-w-[60vw] p-0 bg-transparent border-none shadow-xl"
                @escapeKeyDown="isLightboxOpen = false"
                @pointerDownOutside="isLightboxOpen = false"
              >
                <img 
                  v-if="currentLightboxImage.src"
                  :src="currentLightboxImage.src" 
                  :alt="currentLightboxImage.alt" 
                  class="w-full h-auto max-h-[85vh] object-contain block rounded-md" 
                />
                <button 
                  @click="isLightboxOpen = false" 
                  class="absolute top-2 right-2 p-2 bg-black/40 text-white rounded-full hover:bg-black/60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 transition-colors"
                  aria-label="Close lightbox"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </DialogContent>
            </Dialog>

            <!-- Toast Container -->
            <Toaster />
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<style>
/* Inherit custom styles from the application, assuming they're defined globally */
</style>