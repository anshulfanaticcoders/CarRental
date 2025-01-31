<template>
    <div>
      <h1>Review Details</h1>
      <p>User: {{ review.user.name }}</p>
      <p>Vehicle: {{ review.vehicle.name }}</p>
      <p>Rating: {{ review.rating }}</p>
      <p>Review: {{ review.review_text }}</p>
      <p>Status: {{ review.status }}</p>
      <Link :href="`/reviews/${review.id}/edit`">Edit</Link>
      <Link href="/reviews">Back</Link>
    </div>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3';
  
  defineProps({
    review: Object,
  });
  </script>