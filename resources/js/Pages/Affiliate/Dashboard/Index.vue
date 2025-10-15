<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    business: {
        type: Object,
        required: true,
    },
    businessModel: {
        type: Object,
        required: true,
    },
    qrCodes: {
        type: Array,
        default: () => [],
    },
    totalScans: {
        type: Number,
        default: 0,
    },
    totalCommissions: {
        type: Number,
        default: 0,
    },
    pendingCommissions: {
        type: Number,
        default: 0,
    },
    locale: {
        type: String,
        required: true,
    },
});

const activeTab = ref('overview');
const refreshing = ref(false);
const showQrModal = ref(false);
const selectedQrCode = ref(null);

const formattedCurrency = computed(() => {
    return props.business.currency === 'EUR' ? '€' : props.business.currency || '$';
});

const formattedTotalCommissions = computed(() => {
    return `${formattedCurrency.value}${props.totalCommissions.toFixed(2)}`;
});

const formattedPendingCommissions = computed(() => {
    return `${formattedCurrency.value}${props.pendingCommissions.toFixed(2)}`;
});

const commissionRate = computed(() => {
    if (!props.businessModel) return '0%';
    return `${props.businessModel.customer_discount_rate}%`;
});

const discountRate = computed(() => {
    if (!props.businessModel) return '0%';
    return `${props.businessModel.customer_discount_rate}%`;
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getQrCodeStatus = (qrCode) => {
    const now = new Date();
    const expiresAt = new Date(qrCode.expires_at);

    if (qrCode.is_revoked) return { text: 'Revoked', class: 'bg-red-100 text-red-800' };
    if (now > expiresAt) return { text: 'Expired', class: 'bg-gray-100 text-gray-800' };
    if (qrCode.usage_count >= qrCode.usage_limit) return { text: 'Limit Reached', class: 'bg-yellow-100 text-yellow-800' };
    return { text: 'Active', class: 'bg-green-100 text-green-800' };
};

const generateNewQrCode = () => {
    // Get the current dashboard token from URL
    const currentUrl = window.location.href;
    const tokenMatch = currentUrl.match(/\/dashboard\/([A-Za-z0-9-]+)/);
    const token = tokenMatch ? tokenMatch[1] : '';

    // Navigate to QR code creation page with token
    if (token) {
        router.visit(`/en/business/qr-codes/create/${token}?business_id=${props.business.id}`);
    } else {
        console.error('Dashboard token not found in URL');
    }
};

const downloadQrCode = (qrCode) => {
    // Get the current dashboard token from URL
    const currentUrl = window.location.href;
    const tokenMatch = currentUrl.match(/\/dashboard\/([A-Za-z0-9-]+)/);
    const token = tokenMatch ? tokenMatch[1] : '';

    if (!token) {
        console.error('Dashboard token not found in URL');
        return;
    }

    // Construct download URL
    const downloadUrl = `/${props.locale}/business/qr-codes/download/${token}/${qrCode.id}`;

    // Create temporary link to trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `qr-code-${qrCode.short_code || qrCode.id}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const viewQrCode = (qrCode) => {
    selectedQrCode.value = qrCode;
    showQrModal.value = true;
};

const closeQrModal = () => {
    showQrModal.value = false;
    selectedQrCode.value = null;
};

const getQrCodeImageUrl = (qrCode) => {
    // Just construct the full URL from the path
    const imagePath = qrCode.qr_image_path;
    if (!imagePath) return null;

    return `https://my-public-bucket.4tcl8.upcloudobjects.com/${imagePath}`;
};

const refreshDashboard = async () => {
    refreshing.value = true;
    try {
        // Refresh the page to get updated data
        window.location.reload();
    } finally {
        refreshing.value = false;
    }
};

const logout = () => {
    const currentUrl = window.location.href;
    const tokenMatch = currentUrl.match(/\/dashboard\/([A-Za-z0-9-]+)/);
    if (tokenMatch) {
        window.location.href = `/${props.locale}/business/logout/${tokenMatch[1]}`;
    }
};

// Auto-refresh every 5 minutes
onMounted(() => {
    setInterval(() => {
        refreshDashboard();
    }, 5 * 60 * 1000);
});
</script>

<template>
    <Head :title="'Dashboard - ' + business.name" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ business.name }}</h1>
                        <p class="text-sm text-gray-500">Affiliate Dashboard</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button
                            @click="refreshDashboard"
                            :disabled="refreshing"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            <svg v-if="refreshing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh
                        </button>
                        <button
                            @click="logout"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">QR Codes</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ qrCodes.length }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Scans</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ totalScans.toLocaleString() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Earnings</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ formattedTotalCommissions }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Earnings</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ formattedPendingCommissions }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button
                            @click="activeTab = 'overview'"
                            :class="[
                                activeTab === 'overview'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Overview
                        </button>
                        <button
                            @click="activeTab = 'qr-codes'"
                            :class="[
                                activeTab === 'qr-codes'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            QR Codes
                        </button>
                        <button
                            @click="activeTab = 'analytics'"
                            :class="[
                                activeTab === 'analytics'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Analytics
                        </button>
                        <button
                            @click="activeTab = 'settings'"
                            :class="[
                                activeTab === 'settings'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Settings
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Business Information -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Business Information</h3>
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Business Type</dt>
                                        <dd class="text-sm text-gray-900 capitalize">{{ business.business_type.replace('_', ' ') }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Customer Discount</dt>
                                        <dd class="text-sm text-gray-900">{{ discountRate }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Commission Rate</dt>
                                        <dd class="text-sm text-gray-900">{{ commissionRate }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="text-sm text-gray-900 capitalize">{{ business.status }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Recent Activity -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-sm text-gray-600">Dashboard accessed</span>
                                        <span class="text-xs text-gray-500">Just now</span>
                                    </div>
                                    <div v-if="qrCodes.length === 0" class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span class="text-sm text-gray-600">Business registered</span>
                                        <span class="text-xs text-gray-500">{{ formatDate(business.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Getting Started -->
                        <div v-if="qrCodes.length === 0" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-900 mb-4">Getting Started</h3>
                            <div class="space-y-4">
                                <p class="text-blue-700">
                                    Welcome to your affiliate dashboard! To start earning commissions, you'll need to create QR codes for your business locations.
                                </p>
                                <button
                                    @click="activeTab = 'qr-codes'"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Create Your First QR Code
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- QR Codes Tab -->
                    <div v-if="activeTab === 'qr-codes'" class="space-y-6">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Your QR Codes</h3>
                            <button
                                @click="generateNewQrCode"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Generate QR Code
                            </button>
                        </div>

                        <div v-if="qrCodes.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No QR codes yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first QR code.</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="qrCode in qrCodes" :key="qrCode.id" class="bg-white border rounded-lg p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900">{{ qrCode.location_name || 'QR Code ' + qrCode.id }}</h4>
                                        <p class="text-sm text-gray-500">{{ qrCode.location_address }}</p>
                                    </div>
                                    <span :class="getQrCodeStatus(qrCode).class" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ getQrCodeStatus(qrCode).text }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Scans:</span>
                                        <span class="font-medium">{{ qrCode.usage_count }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Limit:</span>
                                        <span class="font-medium">{{ qrCode.usage_limit || 'Unlimited' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Expires:</span>
                                        <span class="font-medium">{{ formatDate(qrCode.expires_at) }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t flex space-x-2">
                                    <button
                                        @click="viewQrCode(qrCode)"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        View
                                    </button>
                                    <button
                                        @click="downloadQrCode(qrCode)"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Download
                                    </button>
                                    <button class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Stats
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    <div v-if="activeTab === 'analytics'" class="space-y-6">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Analytics Coming Soon</h3>
                            <p class="mt-1 text-sm text-gray-500">Detailed analytics and reporting will be available soon.</p>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div v-if="activeTab === 'settings'" class="space-y-6">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Business Settings</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Business Name</label>
                                    <input type="text" :value="business.name" disabled class="mt-1 block w-full border-gray-300 rounded-md bg-gray-100">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                                    <input type="email" :value="business.contact_email" disabled class="mt-1 block w-full border-gray-300 rounded-md bg-gray-100">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Customer Discount Rate</label>
                                    <input type="text" :value="discountRate" disabled class="mt-1 block w-full border-gray-300 rounded-md bg-gray-100">
                                </div>
                                <p class="text-sm text-gray-500">
                                    To update these settings, please contact our support team.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- QR Code Modal -->
        <div v-if="showQrModal" class="fixed inset-0 z-50 overflow-y-auto" @click="closeQrModal">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <!-- Center the modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    QR Code Preview
                                </h3>

                                <div v-if="selectedQrCode" class="space-y-4">
                                    <!-- QR Code Info -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2">{{ selectedQrCode.location_name || 'QR Code ' + selectedQrCode.id }}</h4>
                                        <p class="text-sm text-gray-500">{{ selectedQrCode.location_address }}</p>

                                        <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">Short Code:</span>
                                                <span class="ml-2 font-medium">{{ selectedQrCode.short_code }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Status:</span>
                                                <span :class="getQrCodeStatus(selectedQrCode).class" class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                                                    {{ getQrCodeStatus(selectedQrCode).text }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QR Code Image -->
                                    <div class="flex justify-center">
                                        <div class="bg-white p-4 border-2 border-gray-200 rounded-lg">
                                            <img
                                                v-if="getQrCodeImageUrl(selectedQrCode)"
                                                :src="getQrCodeImageUrl(selectedQrCode)"
                                                :alt="'QR Code for ' + (selectedQrCode.location_name || 'QR Code ' + selectedQrCode.id)"
                                                class="w-64 h-64 object-contain"
                                            />
                                            <div v-else class="w-64 h-64 flex items-center justify-center bg-gray-100">
                                                <div class="text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                    </svg>
                                                    <p class="mt-2 text-sm text-gray-500">QR Code Image</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QR Code Details -->
                                    <div class="bg-blue-50 rounded-lg p-4">
                                        <h5 class="font-medium text-blue-900 mb-2">QR Code Details</h5>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-blue-700">Scans:</span>
                                                <span class="font-medium text-blue-900">{{ selectedQrCode.usage_count || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-blue-700">Limit:</span>
                                                <span class="font-medium text-blue-900">{{ selectedQrCode.usage_limit || 'Unlimited' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-blue-700">Expires:</span>
                                                <span class="font-medium text-blue-900">{{ formatDate(selectedQrCode.expires_at) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-blue-700">Created:</span>
                                                <span class="font-medium text-blue-900">{{ formatDate(selectedQrCode.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            v-if="selectedQrCode"
                            @click="downloadQrCode(selectedQrCode)"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Download QR Code
                        </button>
                        <button
                            @click="closeQrModal"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>