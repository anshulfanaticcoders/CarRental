<script setup>
import { computed, getCurrentInstance } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { BadgeCheck, Clock, CircleX, LayoutDashboard, Car, MapPin, CalendarCheck, ArrowRight } from 'lucide-vue-next';

const page = usePage();
const vendorStatus = computed(() => page.props.status);
const locale = computed(() => page.props.locale);

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};

const quickActions = computed(() => [
    { icon: Car, label: tt('vendorprofilepages', 'add_vehicle_tile', 'Add a vehicle'), sub: tt('vendorprofilepages', 'add_vehicle_tile_sub', 'List your first car'), href: route('vehicles.create', { locale: locale.value }) },
    { icon: MapPin, label: tt('vendorprofilepages', 'locations_tile', 'Set up locations'), sub: tt('vendorprofilepages', 'locations_tile_sub', 'Pickup & dropoff points'), href: route('vendor.locations.index', { locale: locale.value }) },
    { icon: CalendarCheck, label: tt('vendorprofilepages', 'bookings_tile', 'View bookings'), sub: tt('vendorprofilepages', 'bookings_tile_sub', 'Track your rentals'), href: route('bookings.index', { locale: locale.value }) },
]);
</script>

<template>
    <MyProfileLayout>
        <div class="vr-phead">
            <div>
                <span class="vr-eyebrow"><BadgeCheck /> {{ tt('vendorprofilepages', 'account_eyebrow', 'Account') }}</span>
                <h2>{{ tt('vendorprofilepages', 'verification_status_header', 'Verification Status') }}</h2>
                <p class="vr-sub">{{ tt('vendorprofilepages', 'verification_status_subtitle', 'Your vendor account approval status.') }}</p>
            </div>
        </div>

        <!-- Pending -->
        <div v-if="vendorStatus === 'pending'" class="status-hero hero-amber">
            <span class="sh-glow glow-amber"></span>
            <div class="sh-ic amber"><Clock /></div>
            <h3>{{ _t('vendorprofilepages', 'pending_header') }}</h3>
            <p>{{ _t('vendorprofilepages', 'pending_message') }}</p>
            <div class="sh-links">
                <Link :href="route('welcome', { locale })" class="sh-link">{{ _t('vendorprofilepages', 'go_to_home_link') }}</Link>
                <Link :href="route('profile.edit', { locale })" class="sh-link">{{ _t('vendorprofilepages', 'go_to_profile_link') }}</Link>
            </div>
        </div>

        <!-- Rejected -->
        <div v-else-if="vendorStatus === 'rejected'" class="status-hero hero-rose">
            <span class="sh-glow glow-rose"></span>
            <div class="sh-ic rose"><CircleX /></div>
            <h3>{{ _t('vendorprofilepages', 'rejected_header') }}</h3>
            <p>{{ _t('vendorprofilepages', 'rejected_message') }}</p>
            <div class="sh-links">
                <Link :href="route('welcome', { locale })" class="sh-link">{{ _t('vendorprofilepages', 'go_to_home_link') }}</Link>
                <Link :href="route('vendor.documents.index', { locale })" class="sh-link sh-link--primary">{{ _t('vendorprofilepages', 'check_documents_link') }}</Link>
            </div>
        </div>

        <!-- Approved -->
        <template v-else-if="vendorStatus === 'approved'">
            <div class="status-hero hero-green">
                <span class="sh-glow glow-green"></span>
                <div class="sh-ic green"><BadgeCheck /></div>
                <h3>{{ _t('vendorprofilepages', 'approved_header') }}</h3>
                <p>{{ _t('vendorprofilepages', 'approved_message') }}</p>
                <div class="sh-links">
                    <Link :href="route('vendor.overview', { locale })" class="sh-link sh-link--primary">
                        <LayoutDashboard class="w-4 h-4" /> {{ tt('vendorprofilepages', 'go_to_overview', 'Go to Overview') }}
                    </Link>
                </div>
            </div>

            <div class="sh-quick">
                <Link v-for="(a, i) in quickActions" :key="i" :href="a.href" class="sh-tile">
                    <div class="sh-tile-ic"><component :is="a.icon" /></div>
                    <div class="sh-tile-body">
                        <div class="sh-tile-label">{{ a.label }}</div>
                        <div class="sh-tile-sub">{{ a.sub }}</div>
                    </div>
                    <ArrowRight class="sh-tile-arrow" />
                </Link>
            </div>
        </template>
    </MyProfileLayout>
</template>

<style scoped>
.status-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    padding: 56px 28px;
    text-align: center;
    box-shadow: 0 12px 32px rgba(21, 59, 79, 0.1), 0 4px 8px rgba(21, 59, 79, 0.05);
    max-width: 680px;
    margin: 18px auto 0;
}

.hero-green { background: linear-gradient(180deg, #f0fdf9 0%, #ffffff 55%); }
.hero-amber { background: linear-gradient(180deg, #fffbeb 0%, #ffffff 55%); }
.hero-rose { background: linear-gradient(180deg, #fff1f3 0%, #ffffff 55%); }

.sh-glow {
    position: absolute;
    top: -90px;
    left: 50%;
    transform: translateX(-50%);
    width: 320px;
    height: 320px;
    border-radius: 999px;
    filter: blur(60px);
    opacity: 0.5;
    pointer-events: none;
}

.glow-green { background: radial-gradient(circle, rgba(16, 185, 129, 0.4), transparent 70%); }
.glow-amber { background: radial-gradient(circle, rgba(245, 158, 11, 0.35), transparent 70%); }
.glow-rose { background: radial-gradient(circle, rgba(225, 29, 72, 0.3), transparent 70%); }

.sh-ic {
    position: relative;
    width: 84px;
    height: 84px;
    border-radius: 26px;
    display: inline-grid;
    place-items: center;
    margin-bottom: 22px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.sh-ic::after {
    content: "";
    position: absolute;
    inset: -8px;
    border-radius: 32px;
    border: 1.5px solid currentColor;
    opacity: 0.18;
}

.sh-ic :deep(svg) { width: 40px; height: 40px; }
.sh-ic.green { background: #ecfdf5; color: #059669; }
.sh-ic.amber { background: #fffbeb; color: #d97706; }
.sh-ic.rose { background: #fff1f2; color: #e11d48; }

.status-hero h3 {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 10px;
    position: relative;
}

.status-hero p {
    font-size: 0.92rem;
    color: #64748b;
    max-width: 460px;
    margin: 0 auto;
    position: relative;
}

.sh-links {
    display: flex;
    justify-content: center;
    gap: 14px;
    margin-top: 26px;
    flex-wrap: wrap;
    position: relative;
}

.sh-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-family: "Plus Jakarta Sans", sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    color: #334155;
    text-decoration: none;
    transition: border-color 0.25s cubic-bezier(0.22, 1, 0.36, 1), background 0.25s cubic-bezier(0.22, 1, 0.36, 1), color 0.25s cubic-bezier(0.22, 1, 0.36, 1), transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.sh-link:hover {
    border-color: #153b4f;
    background: #f0f8fc;
    color: #153b4f;
    transform: translateY(-1px);
}

.sh-link--primary {
    background: linear-gradient(135deg, #153b4f, #1c4d66);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 6px 16px rgba(21, 59, 79, 0.24);
}

.sh-link--primary:hover {
    background: linear-gradient(135deg, #153b4f, #1c4d66);
    color: #fff;
    box-shadow: 0 10px 22px rgba(21, 59, 79, 0.3);
    transform: translateY(-1px);
}

/* quick actions */
.sh-quick {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    max-width: 680px;
    margin: 18px auto 0;
}

.sh-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    text-decoration: none;
    box-shadow: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.sh-tile:hover {
    transform: translateY(-3px);
    border-color: rgba(21, 59, 79, 0.2);
    box-shadow: 0 12px 28px rgba(21, 59, 79, 0.1);
}

.sh-tile-ic {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    flex: 0 0 40px;
    display: grid;
    place-items: center;
    background: rgba(21, 59, 79, 0.1);
    color: #153b4f;
}

.sh-tile-ic :deep(svg) { width: 19px; height: 19px; }
.sh-tile-body { flex: 1; min-width: 0; text-align: left; }
.sh-tile-label { font-family: "Plus Jakarta Sans", sans-serif; font-weight: 700; font-size: 0.88rem; color: #0f172a; }
.sh-tile-sub { font-size: 0.75rem; color: #64748b; margin-top: 1px; }
.sh-tile-arrow { width: 16px; height: 16px; color: #94a3b8; flex: 0 0 16px; transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), color 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
.sh-tile:hover .sh-tile-arrow { color: #153b4f; transform: translateX(3px); }

@media (max-width: 768px) {
    .sh-quick { grid-template-columns: 1fr; }
}
</style>
