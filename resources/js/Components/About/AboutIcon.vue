<script setup>
import { computed } from 'vue';
import {
    BadgeCheck,
    Building2,
    Car,
    CheckCircle2,
    Clock3,
    Globe2,
    Headphones,
    MapPinned,
    MessageCircle,
    ReceiptText,
    Route,
    ShieldCheck,
    Sparkles,
    Users,
    WalletCards,
} from 'lucide-vue-next';

const props = defineProps({
    name: {
        type: String,
        default: 'sparkles',
    },
    size: {
        type: Number,
        default: 22,
    },
});

const icons = {
    award: BadgeCheck,
    badge: BadgeCheck,
    building: Building2,
    car: Car,
    check: CheckCircle2,
    clock: Clock3,
    coverage: Globe2,
    globe: Globe2,
    headset: Headphones,
    headphones: Headphones,
    location: MapPinned,
    map: MapPinned,
    message: MessageCircle,
    receipt: ReceiptText,
    route: Route,
    shield: ShieldCheck,
    sparkles: Sparkles,
    support: Headphones,
    supplier: Building2,
    team: Users,
    users: Users,
    wallet: WalletCards,
};

const component = computed(() => icons[String(props.name).toLowerCase().trim()] || Sparkles);
</script>

<template>
    <component :is="component" :size="size" :stroke-width="2" aria-hidden="true" />
</template>
