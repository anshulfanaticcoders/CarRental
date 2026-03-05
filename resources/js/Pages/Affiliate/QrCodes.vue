<template>
    <AffiliateHeader currentPage="qr-codes" />
        <!-- Pending Approval Banner -->
        <div v-if="!isVerified" class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-[min(92%,1200px)] mx-auto py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0">
                    <AlertCircle class="w-4 h-4" />
                </div>
                <div>
                    <span class="text-sm font-bold text-amber-800">Account Pending Approval</span>
                    <span class="text-xs text-amber-600 ml-1">— Your partner account is being reviewed. You'll be notified by email once approved. Some features are limited.</span>
                </div>
            </div>
        </div>

        <!-- Dark Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#0a1d28] to-[#153b4f]">
            <div class="absolute top-[5%] left-[-3%] w-[220px] h-[220px] rounded-full bg-cyan-500 opacity-20 blur-[80px] pointer-events-none animate-float"></div>

            <div class="relative z-10 max-w-[min(92%,1200px)] mx-auto py-4 md:py-6">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
                    <div>
                        <h1 class="text-xl md:text-[1.75rem] font-[800] text-white mb-0.5">QR Codes</h1>
                        <p class="text-[0.85rem] text-slate-400">Manage your location QR codes and view performance.</p>
                    </div>
                    <button @click="isVerified && (showCreateForm = !showCreateForm)"
                        :disabled="!isVerified"
                        :class="[
                            'inline-flex items-center gap-1.5 px-4 py-2.5 text-[0.8rem] font-bold text-white rounded-[10px] bg-gradient-to-br from-cyan-500 to-cyan-600 shadow-[0_4px_14px_rgba(6,182,212,0.25)] transition-all duration-250',
                            isVerified ? 'hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(6,182,212,0.35)] cursor-pointer' : 'opacity-50 cursor-not-allowed'
                        ]">
                        + New QR
                    </button>
                </div>
            </div>
        </section>

        <!-- QR Code Grid -->
        <section class="bg-gradient-to-b from-slate-50 to-white py-4 md:py-6">
            <div class="max-w-[min(92%,1200px)] mx-auto">

                <!-- Empty State -->
                <div v-if="!qrCodes.length && !showCreateForm" class="text-center py-8">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <QrCode class="w-8 h-8 text-slate-400" :stroke-width="1.5" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No QR codes yet</h3>
                    <p class="text-sm text-slate-400 mb-4">Create your first QR code to start tracking customer scans and earning commissions.</p>
                    <button @click="isVerified && (showCreateForm = true)"
                        :disabled="!isVerified"
                        :class="[
                            'inline-flex items-center gap-2 px-5 py-3 text-sm font-bold text-white rounded-xl bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] shadow-[0_4px_14px_rgba(21,59,79,0.18)] transition-all',
                            isVerified ? 'hover:-translate-y-0.5 cursor-pointer' : 'opacity-50 cursor-not-allowed'
                        ]">
                        Create QR Code
                    </button>
                    <p v-if="!isVerified" class="text-xs text-amber-600 mt-2">Your account must be approved before you can create QR codes.</p>
                </div>

                <!-- QR Cards Grid -->
                <div v-if="qrCodes.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="qr in qrCodes" :key="qr.id"
                        class="bg-white border border-[rgba(15,23,42,0.07)] rounded-[20px] shadow-[0_1px_2px_rgba(21,59,79,0.03),0_8px_24px_rgba(21,59,79,0.06)] transition-all duration-400 hover:shadow-[0_1px_2px_rgba(21,59,79,0.03),0_12px_32px_rgba(21,59,79,0.08)] hover:border-[rgba(15,23,42,0.1)]">

                        <!-- QR Preview Area -->
                        <div class="bg-gradient-to-br from-[#f0f8fc] to-[#e0f7fa] p-4 flex items-center justify-center relative rounded-t-[20px] overflow-hidden">
                            <div class="w-[90px] h-[90px] bg-white rounded-xl shadow-[0_4px_16px_rgba(21,59,79,0.1)] grid grid-cols-7 gap-[1.5px] p-2.5">
                                <div v-for="(cell, ci) in getQrPattern(qr.id)" :key="ci"
                                    :class="['rounded-[1px]', cell ? 'bg-[#153b4f]' : 'bg-transparent']">
                                </div>
                            </div>
                            <div class="absolute top-2 right-2">
                                <span :class="getStatusBadgeClass(qr.status)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.65rem] font-semibold uppercase tracking-wide">
                                    {{ qr.status || 'Active' }}
                                </span>
                            </div>
                        </div>

                        <!-- QR Card Body -->
                        <div class="p-4">
                            <h3 class="font-bold text-[0.9rem] text-[#153b4f] mb-0.5">{{ qr.label || qr.short_code }}</h3>
                            <div class="text-[0.75rem] text-slate-500 mb-3.5 flex items-center gap-1">
                                <MapPin class="w-3.5 h-3.5 shrink-0" />
                                {{ qr.location ? [qr.location.name, qr.location.city].filter(Boolean).join(', ') : 'No location' }}
                            </div>

                            <!-- Mini Stats -->
                            <div class="grid grid-cols-3 gap-1 mb-3.5">
                                <div class="text-center py-1.5 bg-slate-50 rounded-lg">
                                    <div class="font-bold text-[0.88rem] text-[#153b4f]">{{ formatNumber(qr.customer_scans_count || 0) }}</div>
                                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wider">Scans</div>
                                </div>
                                <div class="text-center py-1.5 bg-slate-50 rounded-lg">
                                    <div class="font-bold text-[0.88rem] text-[#153b4f]">{{ formatNumber(qr.bookings_count || 0) }}</div>
                                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wider">Bookings</div>
                                </div>
                                <div class="text-center py-1.5 bg-slate-50 rounded-lg">
                                    <div class="font-bold text-[0.88rem] text-[#153b4f]">&euro;{{ formatNumber(qr.revenue || 0) }}</div>
                                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wider">Revenue</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="grid grid-cols-2 gap-1.5 pt-3 border-t border-slate-100">
                                <button @click="copyLink(qr)"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-[10px] transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                                    <Link class="w-4 h-4" /> Copy Link
                                </button>
                                <div class="relative">
                                    <button @click="shareLink(qr)"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-[10px] transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                                        <Share2 class="w-4 h-4" /> Share
                                    </button>
                                    <!-- Desktop Fallback Share Dropdown (only shown when navigator.share unavailable) -->
                                    <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                        <div v-if="shareMenuQr === qr.id" class="absolute z-50 bottom-full mb-1.5 right-0 w-[200px] bg-white border border-slate-200 rounded-xl shadow-[0_8px_24px_rgba(21,59,79,0.12)] py-1.5 origin-bottom-right">
                                            <div class="flex items-center justify-between px-3 py-1.5 mb-0.5">
                                                <span class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wider">Share via</span>
                                                <button @click="closeShareMenu" class="text-slate-400 hover:text-slate-600"><X class="w-3.5 h-3.5" /></button>
                                            </div>
                                            <button @click="shareVia(qr, 'whatsapp')" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                WhatsApp
                                            </button>
                                            <button @click="shareVia(qr, 'facebook')" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                Facebook
                                            </button>
                                            <button @click="shareVia(qr, 'instagram')" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/></svg>
                                                Instagram
                                            </button>
                                            <button @click="shareVia(qr, 'twitter')" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#000000"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                                X (Twitter)
                                            </button>
                                            <button @click="shareVia(qr, 'telegram')" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#0088cc"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012.056 0h-.109zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.492-1.302.48-.428-.013-1.252-.242-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                                Telegram
                                            </button>
                                            <button @click="shareVia(qr, 'email')" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                <Mail class="w-4 h-4 text-slate-500 shrink-0" /> Email
                                            </button>
                                            <div class="border-t border-slate-100 mt-1 pt-1">
                                                <button @click="copyLink(qr); closeShareMenu()" class="w-full flex items-center gap-2.5 px-3 py-2 text-[0.8rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                                    <Copy class="w-4 h-4 text-cyan-600" /> Copy Link
                                                </button>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                                <a v-if="qr.download_url" :href="qr.download_url" target="_blank"
                                    :download="(qr.label || qr.short_code) + '-qr.png'"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-[10px] transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                                    <Download class="w-4 h-4" /> Download
                                </a>
                                <span v-else class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-slate-300 border-[1.5px] border-slate-100 rounded-[10px]">
                                    <Download class="w-4 h-4" /> No Image
                                </span>
                                <button @click="toggleQrStats(qr.id)"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-[10px] transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                                    <BarChart3 class="w-4 h-4" /> Details
                                </button>
                            </div>

                            <!-- Expanded Details -->
                            <div v-if="expandedQr === qr.id" class="mt-3 pt-3 border-t border-slate-100 text-[0.78rem] space-y-1.5">
                                <div class="flex justify-between"><span class="text-slate-400">Location</span><span class="text-[#153b4f] font-medium">{{ qr.location?.name || '-' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Address</span><span class="text-[#153b4f] font-medium text-right max-w-[60%] truncate">{{ qr.location_address || '-' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Short Code</span><span class="text-[#153b4f] font-mono font-medium">{{ qr.short_code }}</span></div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="text-slate-400 shrink-0">Share URL</span>
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="text-[#153b4f] font-mono font-medium text-[0.7rem] truncate">{{ getShareableLink(qr) }}</span>
                                        <button @click="copyLink(qr)" class="shrink-0 text-cyan-600 hover:text-cyan-800 transition-colors" title="Copy link">
                                            <Copy class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-between"><span class="text-slate-400">Status</span><span class="font-medium" :class="qr.status === 'active' ? 'text-emerald-600' : 'text-red-500'">{{ qr.status || 'Active' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Created</span><span class="text-[#153b4f] font-medium">{{ new Date(qr.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span></div>
                                <div v-if="qr.last_scanned_at" class="flex justify-between"><span class="text-slate-400">Last Scan</span><span class="text-[#153b4f] font-medium">{{ new Date(qr.last_scanned_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create QR Section -->
        <section v-if="showCreateForm && isVerified" class="bg-white pb-4">
            <div class="max-w-[min(92%,1200px)] mx-auto">
                <div class="mb-3">
                    <span class="text-[0.76rem] font-bold tracking-[0.12em] uppercase text-cyan-500">New QR Code</span>
                    <h2 class="text-lg font-[800] text-[#153b4f]">
                        Create a QR code for <span class="bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] bg-clip-text text-transparent">a location.</span>
                    </h2>
                </div>

                <!-- Info Box -->
                <div class="max-w-[620px] mb-3 flex items-start gap-2.5 px-3.5 py-2.5 bg-sky-50 border border-sky-200 rounded-xl text-[0.78rem] text-sky-700">
                    <Info class="w-4 h-4 text-sky-500 shrink-0 mt-0.5" />
                    <span>Each QR code is linked to one location. Place it where customers can scan it (reception desk, menu, counter). When scanned, customers get a discount and you earn a commission on their booking.</span>
                </div>

                <div class="max-w-[620px] bg-white border border-[rgba(15,23,42,0.07)] rounded-[20px] shadow-[0_1px_2px_rgba(21,59,79,0.03),0_8px_24px_rgba(21,59,79,0.06)] p-5">
                    <!-- Location Picker -->
                    <div class="mb-3.5">
                        <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Select Location</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div v-for="loc in locations" :key="loc.id"
                                @click="selectedLocation = loc.id"
                                :class="[
                                    'p-3.5 border-[1.5px] rounded-xl cursor-pointer transition-all duration-250 bg-white',
                                    selectedLocation === loc.id
                                        ? 'border-[#2ea7ad] bg-emerald-50/50 shadow-[0_2px_8px_rgba(46,167,173,0.1)]'
                                        : 'border-slate-200 hover:border-[rgba(46,167,173,0.4)]'
                                ]">
                                <h4 class="font-semibold text-[0.82rem] text-[#153b4f]">{{ loc.name }}</h4>
                                <p class="text-[0.72rem] text-slate-500">{{ [loc.address_line_1, loc.city, loc.country].filter(Boolean).join(', ') || 'No address' }}</p>
                            </div>
                            <div @click="selectedLocation = 'new'"
                                :class="[
                                    'p-3.5 border-[1.5px] rounded-xl cursor-pointer transition-all duration-250 bg-white',
                                    selectedLocation === 'new'
                                        ? 'border-[#2ea7ad] bg-emerald-50/50 shadow-[0_2px_8px_rgba(46,167,173,0.1)]'
                                        : 'border-slate-200 hover:border-[rgba(46,167,173,0.4)]'
                                ]">
                                <h4 class="font-semibold text-[0.82rem] text-[#153b4f] inline-flex items-center gap-1"><Plus class="w-4 h-4" /> New Location</h4>
                                <p class="text-[0.72rem] text-slate-500">Add a new location</p>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Location Summary -->
                    <div v-if="selectedExistingLocation" class="mb-3.5 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <div class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Location Details</div>
                        <div class="text-sm text-[#153b4f] font-medium">{{ selectedExistingLocation.name }}</div>
                        <div class="text-[0.78rem] text-slate-500 mt-0.5">{{ [selectedExistingLocation.address_line_1, selectedExistingLocation.city, selectedExistingLocation.state, selectedExistingLocation.country].filter(Boolean).join(', ') }}</div>
                        <div v-if="selectedExistingLocation.latitude" class="text-[0.68rem] text-slate-400 mt-0.5">{{ selectedExistingLocation.latitude }}, {{ selectedExistingLocation.longitude }}</div>
                    </div>

                    <!-- New Location: Google Places Search -->
                    <div v-if="selectedLocation === 'new'" class="mb-3.5 space-y-3">
                        <div>
                            <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Location Name</label>
                            <input v-model="qrForm.location_name" type="text" placeholder="e.g. Hotel Lobby"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            <p v-if="qrForm.errors.location_name" class="text-red-500 text-xs mt-1">{{ qrForm.errors.location_name }}</p>
                        </div>

                        <div>
                            <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Search Address</label>
                            <input ref="placeInputRef" type="text" placeholder="Start typing an address..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            <span class="text-[0.68rem] text-slate-400 mt-0.5 block">Powered by Google Places</span>
                        </div>

                        <!-- Auto-filled Address Fields -->
                        <div v-if="addressConfirmed" class="space-y-2.5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                                    <input v-model="qrForm.address_line_1" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                    <p v-if="qrForm.errors.address_line_1" class="text-red-500 text-xs mt-1">{{ qrForm.errors.address_line_1 }}</p>
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">City</label>
                                    <input v-model="qrForm.city" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                    <p v-if="qrForm.errors.city" class="text-red-500 text-xs mt-1">{{ qrForm.errors.city }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">State</label>
                                    <input v-model="qrForm.state" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Country</label>
                                    <input v-model="qrForm.country" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                    <p v-if="qrForm.errors.country" class="text-red-500 text-xs mt-1">{{ qrForm.errors.country }}</p>
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Postal Code</label>
                                    <input v-model="qrForm.postal_code" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                </div>
                            </div>

                            <!-- Leaflet Map -->
                            <div>
                                <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Pin Location</label>
                                <div ref="mapContainerRef" class="w-full h-[200px] rounded-xl border border-slate-200 overflow-hidden z-0"></div>
                                <span class="text-[0.68rem] text-slate-400 mt-1 block">Drag the marker to adjust the exact position.</span>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Label -->
                    <div class="mb-3.5">
                        <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">QR Code Label</label>
                        <input v-model="qrForm.label" type="text" placeholder="e.g. Restaurant Terrace"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                        <p v-if="qrForm.errors.label" class="text-red-500 text-xs mt-1">{{ qrForm.errors.label }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-2">
                        <button @click="submitQrForm" :disabled="qrForm.processing"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.88rem] font-bold text-white rounded-xl bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] shadow-[0_4px_14px_rgba(21,59,79,0.18)] transition-all hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(21,59,79,0.22)] disabled:opacity-50">
                            <span v-if="qrForm.processing">Creating...</span>
                            <span v-else>Generate QR Code</span>
                        </button>
                        <button @click="showCreateForm = false"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.88rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-xl transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <Toaster position="bottom-right" />
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/Components/ui/sonner';
import AffiliateHeader from '@/Layouts/AffiliateHeader.vue';
import { MapPin, Link, Share2, Download, BarChart3, Plus, Copy, AlertCircle, QrCode, Info, X, Mail } from 'lucide-vue-next';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isVerified = computed(() => page.props.affiliateVerificationStatus === 'verified');

const props = defineProps({
    business: Object,
    qrCodes: Array,
    locations: Array,
});

const showCreateForm = ref(false);
const selectedLocation = ref(null);
const expandedQr = ref(null);
const shareMenuQr = ref(null);

function toggleQrStats(id) {
    expandedQr.value = expandedQr.value === id ? null : id;
}

const qrForm = useForm({
    label: '',
    location_id: null,
    location_name: '',
    address_line_1: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    latitude: null,
    longitude: null,
});

// Google Places autocomplete
const placeInputRef = ref(null);
let autocompleteInstance = null;
const addressConfirmed = ref(false);

// Leaflet map
const mapContainerRef = ref(null);
let mapInstance = null;
let markerInstance = null;

const selectedExistingLocation = computed(() => {
    if (!selectedLocation.value || selectedLocation.value === 'new') return null;
    return props.locations?.find(l => l.id === selectedLocation.value) || null;
});

watch(selectedLocation, (val) => {
    if (val === 'new') {
        qrForm.location_id = 'new';
        qrForm.location_name = '';
        qrForm.address_line_1 = '';
        qrForm.city = '';
        qrForm.state = '';
        qrForm.country = '';
        qrForm.postal_code = '';
        qrForm.latitude = null;
        qrForm.longitude = null;
        addressConfirmed.value = false;
        nextTick(() => initAutocomplete());
    } else {
        qrForm.location_id = val;
        qrForm.location_name = '';
        addressConfirmed.value = false;
        destroyMap();
    }
});

function initAutocomplete() {
    if (!placeInputRef.value || autocompleteInstance) return;
    if (typeof google === 'undefined' || !google.maps?.places) return;

    autocompleteInstance = new google.maps.places.Autocomplete(placeInputRef.value, {
        fields: ['address_components', 'geometry', 'name', 'formatted_address'],
    });

    autocompleteInstance.addListener('place_changed', () => {
        const place = autocompleteInstance.getPlace();
        if (!place.geometry?.location) return;

        const lat = parseFloat(place.geometry.location.lat().toFixed(6));
        const lng = parseFloat(place.geometry.location.lng().toFixed(6));

        let streetNumber = '', routeName = '', locality = '', adminArea = '', countryName = '', postalCode = '';
        (place.address_components || []).forEach(c => {
            if (c.types.includes('street_number')) streetNumber = c.long_name;
            if (c.types.includes('route')) routeName = c.long_name;
            if (c.types.includes('locality')) locality = c.long_name;
            if (c.types.includes('postal_town') && !locality) locality = c.long_name;
            if (c.types.includes('administrative_area_level_1')) adminArea = c.long_name;
            if (c.types.includes('country')) countryName = c.long_name;
            if (c.types.includes('postal_code')) postalCode = c.long_name;
        });

        qrForm.address_line_1 = `${streetNumber} ${routeName}`.trim() || place.formatted_address || '';
        qrForm.city = locality || '';
        qrForm.state = adminArea || '';
        qrForm.country = countryName || '';
        qrForm.postal_code = postalCode || '';
        qrForm.latitude = lat;
        qrForm.longitude = lng;

        if (!qrForm.location_name) {
            qrForm.location_name = place.name || locality || qrForm.address_line_1;
        }

        addressConfirmed.value = true;
        nextTick(() => initMap(lat, lng));
    });
}

function initMap(lat, lng) {
    if (!mapContainerRef.value) return;

    if (mapInstance) {
        mapInstance.setView([lat, lng], 15);
        updateMarker(lat, lng);
        return;
    }

    mapInstance = L.map(mapContainerRef.value, { scrollWheelZoom: true, zoomControl: true })
        .setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(mapInstance);

    updateMarker(lat, lng);
}

function updateMarker(lat, lng) {
    if (!mapInstance) return;
    if (markerInstance) {
        markerInstance.setLatLng([lat, lng]);
    } else {
        markerInstance = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
        markerInstance.on('dragend', async (e) => {
            const { lat: newLat, lng: newLng } = e.target.getLatLng();
            qrForm.latitude = parseFloat(newLat.toFixed(6));
            qrForm.longitude = parseFloat(newLng.toFixed(6));
            await reverseGeocode(qrForm.latitude, qrForm.longitude);
        });
    }
}

async function reverseGeocode(lat, lng) {
    if (typeof google === 'undefined' || !google.maps?.Geocoder) return;
    try {
        const geocoder = new google.maps.Geocoder();
        const result = await geocoder.geocode({ location: { lat, lng } });
        const geo = result?.results?.[0];
        if (!geo) return;

        let streetNumber = '', routeName = '', locality = '', adminArea = '', countryName = '', postalCode = '';
        (geo.address_components || []).forEach(c => {
            if (c.types.includes('street_number')) streetNumber = c.long_name;
            if (c.types.includes('route')) routeName = c.long_name;
            if (c.types.includes('locality')) locality = c.long_name;
            if (c.types.includes('postal_town') && !locality) locality = c.long_name;
            if (c.types.includes('administrative_area_level_1')) adminArea = c.long_name;
            if (c.types.includes('country')) countryName = c.long_name;
            if (c.types.includes('postal_code')) postalCode = c.long_name;
        });

        qrForm.address_line_1 = `${streetNumber} ${routeName}`.trim() || geo.formatted_address || '';
        qrForm.city = locality || '';
        qrForm.state = adminArea || '';
        qrForm.country = countryName || '';
        qrForm.postal_code = postalCode || '';
    } catch (e) {
        console.error('Reverse geocode error:', e);
    }
}

function destroyMap() {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        markerInstance = null;
    }
}

function destroyAutocomplete() {
    if (autocompleteInstance) {
        google.maps.event.clearInstanceListeners(autocompleteInstance);
        autocompleteInstance = null;
    }
}

function handleClickOutside(e) {
    if (shareMenuQr.value && !e.target.closest('.relative')) {
        shareMenuQr.value = null;
    }
}

onMounted(async () => {
    document.addEventListener('click', handleClickOutside);
    if (window.googleMapsReady) {
        await window.googleMapsReady;
        await google.maps.importLibrary('places');
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    destroyMap();
    destroyAutocomplete();
});

const submitQrForm = () => {
    qrForm.post(route('affiliate.qr-codes.store', { locale: locale.value }), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
            qrForm.reset();
            selectedLocation.value = null;
            addressConfirmed.value = false;
            destroyMap();
            destroyAutocomplete();
            toast.success('QR code created successfully!');
        },
        onError: () => {
            toast.error('Failed to create QR code. Please check the form.');
        },
    });
};

function getQrPattern(id) {
    const base = [1,1,1,0,1,1,1,1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1,0,1,0,1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1,1,1,1,0,1,1,1];
    const seed = typeof id === 'number' ? id : 0;
    return base.map((v, i) => {
        const shifted = (i + seed * 3) % 3 === 0 ? !v : v;
        return shifted ? 1 : 0;
    });
}

function getStatusBadgeClass(status) {
    const s = (status || 'active').toLowerCase();
    const map = {
        active: 'bg-emerald-100 text-emerald-800',
        expiring: 'bg-amber-100 text-amber-800',
        expired: 'bg-red-100 text-red-800',
        revoked: 'bg-slate-100 text-slate-600',
    };
    return map[s] || 'bg-emerald-100 text-emerald-800';
}

function formatNumber(val) {
    const num = parseInt(val || 0);
    return num >= 1000 ? (num / 1000).toFixed(1) + 'k' : num.toString();
}

function getShareableLink(qr) {
    return `${window.location.origin}/affiliate/qr/${qr.short_code}`;
}

async function copyLink(qr) {
    try {
        await navigator.clipboard.writeText(getShareableLink(qr));
        toast.success('Link copied to clipboard!');
    } catch {
        toast.error('Failed to copy link');
    }
}

const shareText = 'Get an exclusive discount on car rentals!';

async function shareLink(qr) {
    const url = getShareableLink(qr);
    if (navigator.share) {
        try {
            await navigator.share({
                title: qr.label || qr.short_code,
                text: shareText,
                url,
            });
        } catch (e) {
            // User cancelled — do nothing
        }
    } else {
        // Desktop fallback: show dropdown
        shareMenuQr.value = shareMenuQr.value === qr.id ? null : qr.id;
    }
}

function closeShareMenu() {
    shareMenuQr.value = null;
}

async function shareVia(qr, platform) {
    const url = getShareableLink(qr);
    const urls = {
        whatsapp: `https://wa.me/?text=${encodeURIComponent(shareText + ' ' + url)}`,
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        twitter: `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(url)}`,
        telegram: `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(shareText)}`,
    };
    if (platform === 'email') {
        window.location.href = `mailto:?subject=${encodeURIComponent(qr.label || 'Exclusive car rental discount')}&body=${encodeURIComponent(shareText + '\n\n' + url)}`;
    } else if (platform === 'instagram') {
        try {
            await navigator.clipboard.writeText(url);
            toast.success('Link copied! Paste it in your Instagram story or DM.');
        } catch { /* ignore */ }
        window.open('https://www.instagram.com/', '_blank');
    } else {
        window.open(urls[platform], '_blank', 'width=600,height=400');
    }
    closeShareMenu();
}
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-14px) scale(1.04); }
}
.animate-float { animation: float 14s ease-in-out infinite; }
</style>
