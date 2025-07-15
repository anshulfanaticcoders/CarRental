<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    affiliate: Object,
    totalEarnings: Number,
    pendingCommissions: Number,
    paidCommissions: Number,
    referrals: Array,
    referralLink: String,
});

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        alert('Referral link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
};
</script>

<template>
    <Head title="Affiliate Dashboard" />

    <MyProfileLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Welcome, {{ affiliate.user.first_name }}!</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-100 p-4 rounded-lg shadow">
                                <h4 class="font-semibold text-blue-800">Total Earnings</h4>
                                <p class="text-2xl text-blue-900">${{ totalEarnings.toFixed(2) }}</p>
                            </div>
                            <div class="bg-yellow-100 p-4 rounded-lg shadow">
                                <h4 class="font-semibold text-yellow-800">Pending Commissions</h4>
                                <p class="text-2xl text-yellow-900">${{ pendingCommissions.toFixed(2) }}</p>
                            </div>
                            <div class="bg-green-100 p-4 rounded-lg shadow">
                                <h4 class="font-semibold text-green-800">Paid Commissions</h4>
                                <p class="text-2xl text-green-900">${{ paidCommissions.toFixed(2) }}</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="font-semibold text-gray-900 mb-2">Your Referral Link:</h4>
                            <div class="flex items-center">
                                <TextInput
                                    :value="referralLink"
                                    class="flex-grow mr-2"
                                    readonly
                                />
                                <PrimaryButton @click="copyToClipboard(referralLink)">Copy</PrimaryButton>
                            </div>
                        </div>

                        <h4 class="text-lg font-medium text-gray-900 mb-4">Your Referrals</h4>
                        <div v-if="referrals.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Order ID
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Referred User
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Commission Amount
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="referral in referrals" :key="referral.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ referral.order ? referral.order.booking_number : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ referral.referred_user ? referral.referred_user.first_name + ' ' + referral.referred_user.last_name : 'Guest' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ referral.commission_amount.toFixed(2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                            {{ referral.status }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ new Date(referral.created_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-gray-600">
                            No referrals yet. Share your link to start earning!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>
