<template>
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>Document Details</DialogTitle>
        <DialogDescription>
          View document information
        </DialogDescription>
      </DialogHeader>
      <div class="grid gap-4 py-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <Label class="text-sm font-medium text-gray-500">User</Label>
            <div>{{ document.user.first_name }} {{ document.user.last_name }}</div>
          </div>
  
          <div class="space-y-1">
            <Label class="text-sm font-medium text-gray-500">Email</Label>
            <div>{{ document.user.email }}</div>
          </div>
  
          <div class="space-y-1">
            <Label class="text-sm font-medium text-gray-500">Status</Label>
            <div>
              <Badge :variant="getStatusBadgeVariant(document.verification_status)">
                {{ document.verification_status }}
              </Badge>
            </div>
          </div>
  
          <div class="space-y-1">
            <Label class="text-sm font-medium text-gray-500">Submitted On</Label>
            <div>{{ formatDate(document.created_at) }}</div>
          </div>
  
          <div class="space-y-1">
            <Label class="text-sm font-medium text-gray-500">Verified On</Label>
            <div>{{ document.verified_at ? formatDate(document.verified_at) : 'Not verified yet' }}</div>
          </div>
        </div>
  
        <div class="space-y-2 mt-4">
          <Label class="text-sm font-medium text-gray-500">Document Previews</Label>
          <div class="grid grid-cols-2 gap-4">
            <div v-for="field in documentFields" :key="field.key" class="space-y-2">
              <Label class="text-sm font-medium text-gray-500">{{ field.label }}</Label>
              <div class="border rounded-md p-2 flex justify-center">
                <img
                  v-if="document[field.key]"
                  :src="document[field.key]"
                  :alt="field.label"
                  class="max-h-[150px] object-contain"
                />
                <span v-else class="text-gray-500">No file uploaded</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <DialogFooter>
        <Button @click="$emit('close')">Close</Button>
      </DialogFooter>
    </DialogContent>
  </template>
  
  <script setup>
  import {
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
  } from '@/Components/ui/dialog';
  import { Label } from '@/Components/ui/label';
  import Button from '@/Components/ui/button/Button.vue';
  import Badge from '@/Components/ui/badge/Badge.vue';
  
  const props = defineProps({
    document: Object,
  });
  
  const emit = defineEmits(['close']);
  
  const documentFields = [
    { key: 'driving_license_front', label: 'Driving License Front' },
    { key: 'driving_license_back', label: 'Driving License Back' },
    { key: 'passport_front', label: 'Passport Front' },
    { key: 'passport_back', label: 'Passport Back' },
  ];
  
  const getStatusBadgeVariant = (status) => {
    switch (status) {
      case 'verified':
        return 'default';
      case 'pending':
        return 'secondary';
      case 'rejected':
        return 'destructive';
      default:
        return 'default';
    }
  };
  
  const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
  };
  </script>