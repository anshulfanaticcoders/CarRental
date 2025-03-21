<script setup>
import vendorApprovedStatusIcon from '../../../../assets/vendorApprovedStatusIcon.svg';
import vendorPendingStatusIcon from '../../../../assets/vendorPendingStatusIcon.svg';
import vendorRejectedStatusIcon from '../../../../assets/vendorRejectedStatusIcon.svg';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';

const page = usePage();
const vendorStatus = computed(() => page.props.status);
</script>

<template>
    <MyProfileLayout>
        <section class="full-w-container">
            <div class="flex justify-center items-center h-[80vh]">
                <div class="flex flex-col justify-center items-center w-[650px] max-[768px]:w-full">
                    <!-- Show Pending UI -->
                    <template v-if="vendorStatus === 'pending'">
                        <img :src="vendorPendingStatusIcon" alt="Pending Status" class="max-[768px]:w-[50px]" />
                        <h3
                            class="text-[#333333] text-[1.75rem] text-center max-[768px]:my-4 max-[768px]:text-[1.2rem]">
                            Account Verification Pending</h3>
                        <p class="text-center max-[768px]:text-[0.875rem]">
                            Your account is currently being verified. Our team is reviewing your documents and details
                            to ensure everything is in order. This process may take up to 24 hours. We appreciate your
                            patience!
                        </p>
                        <div class="flex justify-between items-center gap-10 mt-[2rem] max-[768px]:text-[0.875rem]">
                            <Link href="/" class="text-blue-700 underline">Go to Home Page</Link>
                            <Link href="/profile" class="text-blue-700 underline">Go to Profile</Link>
                        </div>
                    </template>

                    <!-- Show Rejected UI -->
                    <template v-else-if="vendorStatus === 'cancelled'">
                        <img :src="vendorRejectedStatusIcon" alt="Rejected Status" class="max-[768px]:w-[50px]" />
                        <h3
                            class="text-[#333333] text-[1.75rem] text-center max-[768px]:my-4 max-[768px]:text-[1.2rem]">
                            Rejected</h3>
                        <p class="text-center max-[768px]:text-[0.875rem]">
                            We’re sorry, but your account verification could not be completed. Please review the
                            following issues
                            and resubmit your details for approval.
                        </p>
                        <div class="flex justify-between items-center gap-10 mt-[2rem] max-[768px]:text-[0.875rem]">
                            <Link href="/" class="text-blue-700 underline">Go to Home Page</Link>
                            <Link href="/vendor/documents" class="text-blue-700 underline">Check your doucments</Link>
                        </div>
                    </template>

                    <!-- Show Confirmed UI -->
                    <template v-else-if="vendorStatus === 'approved'">
                        <img :src="vendorApprovedStatusIcon" alt="" class="max-[768px]:w-[50px]">
                        <h3
                            class="text-[#333333] text-[1.75rem] text-center max-[768px]:my-4 max-[768px]:text-[1.2rem]">
                            Approved</h3>
                        <p class="text-center max-[768px]:text-[0.875rem]">Congratulations! Your account has been
                            successfully verified. You can now start listing your vehicles and managing your fleet
                            effortlessly. Click the button below to add your first vehicle.</p>
                    </template>
                </div>
            </div>
        </section>
    </MyProfileLayout>
</template>

<style></style>
