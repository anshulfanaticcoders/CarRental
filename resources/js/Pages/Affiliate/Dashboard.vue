<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import axios from 'axios';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';

const { props } = usePage();
const conversions = ref([]);
const totalEarnings = ref(0);
const affiliateId = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    try {
        const response = await axios.get('/api/affiliate/conversions');
        conversions.value = response.data.conversions;
        totalEarnings.value = response.data.totalEarnings;
        affiliateId.value = response.data.affiliateId;
    } catch (err) {
        error.value = 'Failed to load affiliate data. Please try again later.';
        console.error('Error fetching affiliate conversions:', err.response ? err.response.data : err.message);
    } finally {
        loading.value = false;
    }
});

const formatCurrency = (amount) => {
    // Assuming EUR as default, you might want to get this dynamically from vendorProfile or a global setting
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'EUR' // Default currency, adjust if needed
    }).format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<template>
    <Head title="Affiliate Dashboard" />
    <AuthenticatedHeaderLayout />

    <MyProfileLayout>

    <main class="full-w-container py-customVerticalSpacing">
        <h1 class="text-3xl font-semibold mb-8">Your Affiliate Dashboard</h1>

        <div v-if="loading" class="text-center text-gray-600">Loading affiliate data...</div>
        <div v-else-if="error" class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ error }}
        </div>
        <div v-else>
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-4">Summary</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600">Your Affiliate ID:</p>
                        <p class="text-xl font-bold">{{ affiliateId }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Earned Commissions:</p>
                        <p class="text-xl font-bold text-green-600">{{ formatCurrency(totalEarnings) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-semibold mb-4">Your Conversions</h2>
                <div v-if="conversions.length > 0" class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-left">Date</th>
                                <th class="py-2 px-4 border-b text-left">External ID</th>
                                <th class="py-2 px-4 border-b text-left">Amount</th>
                                <th class="py-2 px-4 border-b text-left">Customer ID</th>
                                <th class="py-2 px-4 border-b text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="conversion in conversions" :key="conversion.id">
                                <td class="py-2 px-4 border-b">{{ formatDate(conversion.created_at) }}</td>
                                <td class="py-2 px-4 border-b">{{ conversion.external_id }}</td>
                                <td class="py-2 px-4 border-b">{{ formatCurrency(conversion.amount) }}</td>
                                <td class="py-2 px-4 border-b">{{ conversion.customer_id || 'N/A' }}</td>
                                <td class="py-2 px-4 border-b capitalize"
                                    :class="{
                                        'text-green-600': conversion.status === 'approved',
                                        'text-yellow-600': conversion.status === 'pending',
                                        'text-red-600': conversion.status === 'declined'
                                    }">
                                    {{ conversion.status }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-gray-600">No conversions yet. Share your affiliate link to start earning!</div>
            </div>
        </div>
    </main>
    </MyProfileLayout>

    <Footer />
</template>

<style scoped>
.full-w-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

.py-customVerticalSpacing {
    padding-top: 3rem;
    padding-bottom: 3rem;
}

@media screen and (max-width: 768px) {
    .py-customVerticalSpacing {
        padding-top: 1.25rem;
        padding-bottom: 3rem;
    }
}
</style>
