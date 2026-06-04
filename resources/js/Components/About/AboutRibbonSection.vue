<script setup>
defineProps({
    ribbon: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="about-ribbon" :aria-label="ribbon.backgroundImageAlt">
        <img :src="ribbon.backgroundImageUrl" :alt="ribbon.backgroundImageAlt" loading="lazy" decoding="async" />
        <div class="about-ribbon-shade" aria-hidden="true"></div>
        <div class="full-w-container about-ribbon-inner about-reveal">
            <h2>{{ ribbon.title }}</h2>
            <div v-html="ribbon.content"></div>
        </div>
    </section>
</template>

<style scoped>
.about-ribbon {
    position: relative;
    isolation: isolate;
    padding: clamp(3rem, 6vw, 5rem) 0;
    overflow: hidden;
    border-block: 1px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.about-ribbon img,
.about-ribbon-shade {
    position: absolute;
    inset: 0;
}

.about-ribbon img {
    z-index: -2;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.about-ribbon-shade {
    z-index: -1;
    background:
        linear-gradient(90deg, rgba(6, 19, 29, 0.97), rgba(21, 59, 79, 0.84), rgba(6, 19, 29, 0.97)),
        linear-gradient(120deg, transparent 0%, rgba(34, 211, 238, 0.12) 45%, transparent 70%);
}

.about-ribbon::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background: linear-gradient(120deg, transparent 0%, rgba(34, 211, 238, 0.12) 45%, transparent 70%);
    transform: translateX(-40%);
    animation: aboutRibbonScan 5.8s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.about-ribbon-inner {
    position: relative;
    z-index: 2;
    display: grid;
    gap: 1.5rem;
    align-items: center;
}

.about-ribbon h2 {
    max-width: 860px;
    margin: 0;
    color: #ffffff;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: clamp(1.8rem, 3.6vw, 3.25rem);
    font-weight: 800;
    line-height: 1.09;
    letter-spacing: 0;
    text-wrap: balance;
}

.about-ribbon div :deep(p) {
    max-width: 62ch;
    margin: 0;
    color: rgba(255, 255, 255, 0.72);
    font-size: 1rem;
    line-height: 1.65;
}

@keyframes aboutRibbonScan {
    0% {
        transform: translateX(-60%);
        opacity: 0;
    }
    22%,
    70% {
        opacity: 1;
    }
    100% {
        transform: translateX(55%);
        opacity: 0;
    }
}

@media (min-width: 1024px) {
    .about-ribbon-inner {
        grid-template-columns: minmax(0, 1fr) auto;
    }
}

@media (max-width: 759px) {
    .about-ribbon h2 {
        letter-spacing: 0;
    }

    .about-ribbon div :deep(p) {
        font-size: 0.98rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-ribbon::before {
        animation: none;
    }
}
</style>
