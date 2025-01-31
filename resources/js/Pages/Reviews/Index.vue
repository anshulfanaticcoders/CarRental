<template>
    <div>
      <h1>Reviews</h1>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Vehicle</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="review in reviews" :key="review.id">
            <td>{{ review.user.name }}</td>
            <td>{{ review.vehicle.name }}</td>
            <td>{{ review.rating }}</td>
            <td>{{ review.review_text }}</td>
            <td>{{ review.status }}</td>
            <td>
              <Link :href="`/reviews/${review.id}`">View</Link>
              <Link :href="`/reviews/${review.id}/edit`">Edit</Link>
              <button @click="deleteReview(review.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3';
  
  defineProps({
    reviews: Array,
  });
  
  const deleteReview = (id) => {
    if (confirm('Are you sure you want to delete this review?')) {
      Inertia.delete(`/reviews/${id}`);
    }
  };
  </script>