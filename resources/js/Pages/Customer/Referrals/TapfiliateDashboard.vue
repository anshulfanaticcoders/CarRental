<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'; // Correct layout for Vue 3
import { Copy, Share } from 'lucide-vue-next'; // lucide-vue-next for Vue 3 icons
import axios from 'axios';

const copied = ref(false);
const referralCode = ref(null);
const stats = ref(null); // To store stats fetched from Tapfiliate API

const user = usePage().props.auth.user; // Get authenticated user from Inertia props

onMounted(async () => {
    try {
        // Fetch user's referral code from your backend
        const response = await axios.get(route('customer.referrals.tapfiliate.code', { locale: usePage().props.locale }));
        referralCode.value = response.data.referralCode;
    } catch (error) {
        console.error('Error fetching referral code:', error);
    }

    // Optionally, fetch stats from Tapfiliate API via your backend
    // You'd need a backend endpoint to proxy requests to Tapfiliate's API
    // try {
    //     const statsResponse = await axios.get(route('customer.referrals.tapfiliate.stats', { locale: usePage().props.locale }));
    //     stats.value = statsResponse.data.stats;
    // } catch (error) {
    //     console.error('Error fetching stats:', error);
    // }
});

const referralLink = computed(() => {
    return referralCode.value ? `https:/vrooem.be/${usePage().props.locale}/register?ref=${referralCode.value}` : 'Loading...';
});

const copyReferralLink = () => {
    navigator.clipboard.writeText(referralLink.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

const shareReferralLink = () => {
    if (navigator.share) {
        navigator.share({
            title: 'Join our car rental platform!',
            text: 'Get exclusive benefits when you sign up using my referral link',
            url: referralLink.value
        });
    }
};
</script>

<template>
    <MyProfileLayout>
        <Head title="Referral Program" />
        <div class="max-w-7xl mx-auto py-6 px-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Your Referral Program</h1>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Your Referral Link</h2>
                <div class="flex items-center gap-4">
                    <div class="flex-1 bg-gray-50 rounded-md p-3 border">
                        <code class="text-sm text-gray-800">{{ referralLink }}</code>
                    </div>
                    <button
                        @click="copyReferralLink"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center gap-2"
                    >
                        <Copy :size="16" />
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                    <button
                        @click="shareReferralLink"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center gap-2"
                    >
                        <Share :size="16" />
                        Share
                    </button>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <p><strong>Referral Code:</strong> {{ referralCode || 'Loading...' }}</p>
                </div>
            </div>

            <!-- You can add sections here to display stats fetched from Tapfiliate API -->
            <!-- For example, total clicks, conversions, commissions earned -->
            <!-- This would require additional backend endpoints to proxy Tapfiliate API calls -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Referral Statistics (from Tapfiliate)</h2>
                <p class="text-gray-600">
                    You can fetch and display real-time clicks, conversions, and earnings from Tapfiliate's API here.
                    This would involve creating a backend endpoint to securely proxy requests to Tapfiliate.
                </p>
                <!-- Example:
                <div v-if="stats">
                    <p>Total Clicks: {{ stats.clicks }}</p>
                    <p>Total Conversions: {{ stats.conversions }}</p>
                    <p>Total Earned: {{ stats.earnings }}</p>
                </div>
                <div v-else>
                    <p>Loading stats...</p>
                </div>
                -->
            </div>
        </div>
    </MyProfileLayout>
</template>
