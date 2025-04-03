<template>
    <div class="flex flex-col items-center justify-center min-h-screen bg-red-100">
        <h1 class="text-3xl font-bold text-red-600">Payment Failed</h1>
        <p class="mt-4 text-gray-700">Unfortunately, your payment was not successful.</p>
        <p class="mt-2 text-gray-700">Please try again or contact support.</p>
        <button @click="retryPayment" class="mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">
            Try Again
        </button>
    </div>
</template>

<script setup>

const retryPayment = () => {
    window.history.back();
};

</script>
