<script setup>
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Card, CardContent } from '@/Components/ui/card'
// Import the background image
import abstractWaveBg from '../../assets/abstract-wave-min.png'

// Translation helper
const _t = (key, fallback = '') => {
    const { props } = usePage();
    if (props.translations && props.translations.esim && props.translations.esim[key]) {
        return props.translations.esim[key];
    }
    return fallback || key;
}

// Redirect to partner portal
const redirectToPartnerPortal = () => {
    window.open('https://vrooem.esimqr.link/', '_blank')
}
</script>

<template>
    <section class="py-customVerticalSpacing relative bg-cover bg-center bg-no-repeat" :style="`background-image: url('${abstractWaveBg}');`">
        <!-- Dark overlay for better text visibility -->
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="full-w-container relative z-10">
            <div class="text-center mb-12">
                <span class="text-[1.25rem] text-white">- {{ _t('tagline') }} -</span>
                <h2 class="text-[3rem] font-bold text-white max-[768px]:text-[2rem] mt-2">
                    {{ _t('title') }}
                </h2>
                <p class="text-lg text-white/90 mt-4 max-w-2xl mx-auto">
                    {{ _t('subtitle') }}
                </p>
            </div>

            <div class="max-w-2xl mx-auto">
                <!-- Main CTA -->
                <Card class="bg-white/95 backdrop-blur-sm text-center">
                    <CardContent class="p-8">
                        <div class="mb-6">
                            <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 011.404 1.404l3.586 3.586a1 1 0 001.414 0l3.586-3.586A1 1 0 0117.414 0l3.586 3.586A1 1 0 0121 12v5a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">
                                {{ _t('card_title') }}
                            </h3>
                            <p class="text-gray-600 mb-4">
                                {{ _t('card_description') }}
                            </p>

                            <!-- Key Benefits -->
                            <div class="grid grid-cols-3 gap-3 mb-6 text-center">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-lg font-bold text-blue-600">∞</div>
                                    <div class="text-xs font-medium text-gray-700">{{ _t('unlimited_data') }}</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-lg font-bold text-blue-600">🌍</div>
                                    <div class="text-xs font-medium text-gray-700">{{ _t('destinations') }}</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-lg font-bold text-blue-600">⚡</div>
                                    <div class="text-xs font-medium text-gray-700">{{ _t('instant_setup') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <Button
                                @click="redirectToPartnerPortal"
                                size="lg"
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-8 text-lg transform transition-transform hover:scale-105"
                            >
                                {{ _t('get_esim') }}
                            </Button>

                            <div class="text-sm text-gray-500">
                                <p>{{ _t('support_info') }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>
</template>