<template>
    <AdminDashboardLayout>
      <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Testimonials Management</h1>
  
        <!-- Add New Testimonial Button -->
        <div class="mb-4">
          <Dialog v-model:open="isDialogOpen">
            <DialogTrigger as-child>
              <Button @click="openCreateDialog">Add New Testimonial</Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Edit Testimonial' : 'Create Testimonial' }}</DialogTitle>
                <DialogDescription>Fill in the details to {{ isEditing ? 'update' : 'create' }} a testimonial.</DialogDescription>
              </DialogHeader>
              <form @submit.prevent="saveTestimonial">
                <div class="space-y-4">
                  <!-- Avatar Upload -->
                  <div>
                    <label class="block text-sm font-medium">Avatar</label>
                    <input type="file" ref="avatarInput" @change="handleAvatarChange" accept="image/*" class="mt-1 block w-full" />
                    <img v-if="form.avatarPreview" :src="form.avatarPreview" alt="Avatar Preview" class="mt-2 h-20 w-20 object-cover rounded-full" />
                  </div>
                  <!-- Name -->
                  <div>
                    <label class="block text-sm font-medium">Name</label>
                    <Input v-model="form.name" required />
                  </div>
                  <!-- Review -->
                  <div>
                    <label class="block text-sm font-medium">Review</label>
                    <Textarea v-model="form.review" required />
                  </div>
                  <!-- Ratings -->
                  <div>
                    <label class="block text-sm font-medium">Ratings (1-5)</label>
                    <Input v-model.number="form.ratings" type="number" min="1" max="5" required />
                  </div>
                  <!-- Designation -->
                  <div>
                    <label class="block text-sm font-medium">Designation</label>
                    <Input v-model="form.designation" required />
                  </div>
                </div>
                <DialogFooter class="mt-6">
                  <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                  <Button type="submit">{{ isEditing ? 'Update' : 'Create' }}</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
  
        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteDialogOpen">
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Confirm Deletion</DialogTitle>
              <DialogDescription>
                Are you sure you want to delete this testimonial? This action cannot be undone.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-6">
              <Button variant="outline" @click="isDeleteDialogOpen = false">Cancel</Button>
              <Button variant="destructive" @click="confirmDelete">Delete</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
  
        <!-- Testimonials Table -->
        <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>Avatar</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Review</TableHead>
                <TableHead>Ratings</TableHead>
                <TableHead>Designation</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!testimonials || !testimonials.data || testimonials.data.length === 0">
                <TableCell colspan="7" class="text-center py-4">No testimonials found.</TableCell>
              </TableRow>
              <TableRow v-for="(testimonial, index) in testimonials.data" :key="testimonial.id">
                <TableCell>{{ (testimonials.current_page - 1) * testimonials.per_page + index + 1 }}</TableCell>
                <TableCell>
                  <img v-if="testimonial.avatar" :src="testimonial.avatar" alt="Avatar" class="h-12 w-12 object-cover rounded-full" />
                </TableCell>
                <TableCell>{{ testimonial.name }}</TableCell>
                <TableCell>{{ testimonial.review }}</TableCell>
                <TableCell>{{ testimonial.ratings }}</TableCell>
                <TableCell>{{ testimonial.designation }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button size="sm" variant="outline" @click="openEditDialog(testimonial)">Edit</Button>
                    <Button size="sm" variant="destructive" @click="openDeleteDialog(testimonial.id)">Delete</Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <!-- Pagination -->
          <div v-if="testimonials && testimonials.last_page && testimonials.last_page > 1" class="mt-4 flex justify-end">
            <Pagination
              :currentPage="testimonials.current_page"
              :totalPages="testimonials.last_page"
              @page-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </AdminDashboardLayout>
  </template>
  
  <script setup>
  import { ref, reactive, defineProps } from 'vue';
  import { router } from '@inertiajs/vue3'; // Added router
  import axios from 'axios';
  import { useToast } from 'vue-toastification'; // Added useToast
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
  import Table from '@/Components/ui/table/Table.vue';
  import TableHeader from '@/Components/ui/table/TableHeader.vue';
  import TableRow from '@/Components/ui/table/TableRow.vue';
  import TableHead from '@/Components/ui/table/TableHead.vue';
  import TableBody from '@/Components/ui/table/TableBody.vue';
  import TableCell from '@/Components/ui/table/TableCell.vue';
  import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
  import Pagination from '@/Components/ReusableComponents/Pagination.vue'; // Added Pagination
  
  const toast = useToast(); // Initialize toast

  // Props from Inertia
  const props = defineProps({
    testimonials: Object, // Changed from Array to Object
  });
  
  // State
  const isDialogOpen = ref(false);
  const isDeleteDialogOpen = ref(false);
  const isEditing = ref(false);
  const currentTestimonialId = ref(null);
  const testimonialToDelete = ref(null);
  
  const form = reactive({
    avatar: null,
    avatarPreview: null,
    name: '',
    review: '',
    ratings: 1,
    designation: '',
  });
  
  // Refs
  const avatarInput = ref(null);
  
  // CSRF Token Setup
  const getCsrfToken = async () => {
    await axios.get('/sanctum/csrf-cookie');
  };
  
  // Methods
  const openCreateDialog = () => {
    isEditing.value = false;
    isDialogOpen.value = true;
    resetForm();
  };
  
  const openEditDialog = async (testimonial) => {
    isEditing.value = true;
    currentTestimonialId.value = testimonial.id;
    isDialogOpen.value = true;
  
    form.name = testimonial.name;
    form.review = testimonial.review;
    form.ratings = testimonial.ratings;
    form.designation = testimonial.designation;
    form.avatarPreview = testimonial.avatar || null;
    form.avatar = null;
  };
  
  const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
      form.avatar = file;
      form.avatarPreview = URL.createObjectURL(file);
    }
  };
  
  const resetForm = () => {
    form.avatar = null;
    form.avatarPreview = null;
    form.name = '';
    form.review = '';
    form.ratings = 1;
    form.designation = '';
    if (avatarInput.value) {
      avatarInput.value.value = '';
    }
  };
  
  const saveTestimonial = async () => {
    await getCsrfToken(); 
  
    const formData = new FormData();
    if (form.avatar) {
      formData.append('avatar', form.avatar);
    }
    formData.append('name', form.name);
    formData.append('review', form.review);
    formData.append('ratings', form.ratings);
    formData.append('designation', form.designation);
  
    if (isEditing.value) {
      // Your route for update is POST, so we use router.post.
      // Laravel handles method spoofing if _method is present in FormData.
      formData.append('_method', 'POST'); // This might be redundant if your route is already POST for updates.
                                        // If it were PUT/PATCH, Inertia's router.put/patch would handle it.
      router.post(route('testimonials.update', currentTestimonialId.value), formData, {
        onSuccess: () => {
          isDialogOpen.value = false;
          resetForm();
          toast.success('Testimonial updated successfully!');
          // Data should refresh if controller redirects or returns updated Inertia response.
        },
        onError: (errors) => {
          console.error('Error updating testimonial:', errors);
          // Consider adding user-facing error messages here
        },
      });
    } else { // Creating new
      router.post(route('testimonials.store'), formData, {
        onSuccess: () => {
          isDialogOpen.value = false;
          resetForm();
          toast.success('Testimonial created successfully!');
        },
        onError: (errors) => {
          console.error('Error creating testimonial:', errors);
          // Consider adding user-facing error messages here
        },
      });
    }
  };
  
  const openDeleteDialog = (id) => {
    testimonialToDelete.value = id;
    isDeleteDialogOpen.value = true;
  };
  
  const confirmDelete = async () => {
    if (!testimonialToDelete.value) return;
    // Using Inertia router for delete with correct named route
    router.delete(route('testimonials.destroy', testimonialToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            testimonialToDelete.value = null;
            toast.success('Testimonial deleted successfully!');
            // Data should refresh via Inertia
        },
        onError: (errors) => {
            console.error('Error deleting testimonial:', errors);
            // Handle error display
        }
    });
  };

  const handlePageChange = (page) => {
    router.get(route('testimonials.index', { page: page }), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
  };
  </script>
  
  <style scoped>
  table th {
    font-size: 0.95rem;
  }
  table td {
    font-size: 0.875rem;
  }
  </style>
