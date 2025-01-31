<template>
    <div>
      <h1>{{ editMode ? 'Edit Review' : 'Create Review' }}</h1>
      <form @submit.prevent="submit">
        <label>Rating:</label>
        <input type="number" v-model="form.rating" min="1" max="5" required />
        <label>Review Text:</label>
        <textarea v-model="form.review_text" required></textarea>
        <label>Status:</label>
        <select v-model="form.status">
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <button type="submit">{{ editMode ? 'Update' : 'Create' }}</button>
      </form>
    </div>
  </template>
  
  <script setup>
  import { useForm } from '@inertiajs/vue3';
  
  const props = defineProps({
    review: Object,
    editMode: Boolean,
  });
  
  const form = useForm({
    rating: props.review?.rating || '',
    review_text: props.review?.review_text || '',
    status: props.review?.status || 'pending',
  });
  
  const submit = () => {
    if (props.editMode) {
      form.put(`/reviews/${props.review.id}`);
    } else {
      form.post('/reviews');
    }
  };