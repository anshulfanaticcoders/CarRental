<script setup>
import vendorApprovedStatusIcon from '../../../../assets/vendorApprovedStatusIcon.svg';
import vendorPendingStatusIcon from '../../../../assets/vendorPendingStatusIcon.svg';
import vendorRejectedStatusIcon from '../../../../assets/vendorRejectedStatusIcon.svg';
import { computed, getCurrentInstance } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';

const page = usePage();
const vendorStatus = computed(() => page.props.status);

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
</script>

<template>
    <MyProfileLayout>
        <section class="full-w-container">
            <div class="flex justify-center items-center h-[80vh]">
                <div class="flex flex-col justify-center items-center w-[650px] max-[768px]:w-full">
                    <!-- Show Pending UI -->
                    <template v-if="vendorStatus === 'pending'">
                        <img :src="vendorPendingStatusIcon" :alt="_t('vendorprofilepages', 'alt_pending_status')" class="max-[768px]:w-[50px]" />
                        <h3
                            class="text-[#333333] text-[1.75rem] text-center max-[768px]:my-4 max-[768px]:text-[1.2rem]">
                            {{ _t('vendorprofilepages', 'pending_header') }}</h3>
                        <p class="text-center max-[768px]:text-[0.875rem]">
                            {{ _t('vendorprofilepages', 'pending_message') }}
                        </p>
                        <div class="flex justify-between items-center gap-10 mt-[2rem] max-[768px]:text-[0.875rem]">
                            <Link href="/" class="text-blue-700 underline">{{ _t('vendorprofilepages', 'go_to_home_link') }}</Link>
                            <Link href="/profile" class="text-blue-700 underline">{{ _t('vendorprofilepages', 'go_to_profile_link') }}</Link>
                        </div>
                    </template>

                    <!-- Show Rejected UI -->
                    <template v-else-if="vendorStatus === 'rejected'">
                        <img :src="vendorRejectedStatusIcon" :alt="_t('vendorprofilepages', 'alt_rejected_status')" class="max-[768px]:w-[50px]" />
                        <h3
                            class="text-[#333333] text-[1.75rem] text-center max-[768px]:my-4 max-[768px]:text-[1.2rem]">
                            {{ _t('vendorprofilepages', 'rejected_header') }}</h3>
                        <p class="text-center max-[768px]:text-[0.875rem]">
                            {{ _t('vendorprofilepages', 'rejected_message') }}
                        </p>
                        <div class="flex justify-between items-center gap-10 mt-[2rem] max-[768px]:text-[0.875rem]">
                            <Link href="/" class="text-blue-700 underline">{{ _t('vendorprofilepages', 'go_to_home_link') }}</Link>
                            <Link href="/vendor/documents" class="text-blue-700 underline">{{ _t('vendorprofilepages', 'check_documents_link') }}</Link>
                        </div>
                    </template>

                    <!-- Show Confirmed UI -->
                    <template v-else-if="vendorStatus === 'approved'">
                        <img :src="vendorApprovedStatusIcon" :alt="_t('vendorprofilepages', 'alt_approved_status')" class="max-[768px]:w-[50px]">
                        <h3
                            class="text-[#333333] text-[1.75rem] text-center max-[768px]:my-4 max-[768px]:text-[1.2rem]">
                            {{ _t('vendorprofilepages', 'approved_header') }}</h3>
                        <p class="text-center max-[768px]:text-[0.875rem]">{{ _t('vendorprofilepages', 'approved_message') }}</p>
                    </template>
                </div>
            </div>
        </section>
    </MyProfileLayout>
</template>

<style></style>
