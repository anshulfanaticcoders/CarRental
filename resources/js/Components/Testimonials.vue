<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const __ = (key, replacements = {}) => {
    const translations = page.props.translations?.messages || {};
    let translation = translations[key] || key;
    Object.keys(replacements).forEach((k) => {
        translation = translation.replace(`:${k}`, replacements[k]);
    });
    return translation;
};

const _p = (key, replacements = {}) => {
    const translations = page.props.translations?.homepage || {};
    let translation = translations[key] || key;
    Object.keys(replacements).forEach((k) => {
        translation = translation.replace(`:${k}`, replacements[k]);
    });
    return translation;
};

// Data
const testimonials = ref([]);
const loading = ref(true);
const error = ref(null);

// Slider state
const currentIndex = ref(0);
const isTransitioning = ref(false);
const isPaused = ref(false);

let autoplayTimer = null;
const AUTOPLAY_DELAY = 6000;
const progressRef = ref(null);

const totalSlides = computed(() => testimonials.value.length);

// Get initials from author name
function getInitials(name) {
    if (!name) return "?";
    return name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
}

// Position relative to active card (wrapping)
function getPosition(cardIndex) {
    const total = totalSlides.value;
    if (total === 0) return "hidden-left";

    let diff = cardIndex - currentIndex.value;
    if (diff > Math.floor(total / 2)) diff -= total;
    if (diff < -Math.floor(total / 2)) diff += total;

    if (diff === 0) return "0";
    if (diff === 1) return "1";
    if (diff === -1) return "-1";
    if (diff === 2) return "2";
    if (diff === -2) return "-2";
    return diff > 2 ? "hidden-right" : "hidden-left";
}

// Navigate
function goTo(index) {
    if (isTransitioning.value || totalSlides.value === 0) return;
    isTransitioning.value = true;

    const total = totalSlides.value;
    currentIndex.value = ((index % total) + total) % total;
    resetAutoplay();

    setTimeout(() => {
        isTransitioning.value = false;
    }, 700);
}

function goNext() {
    goTo(currentIndex.value + 1);
}

function goPrev() {
    goTo(currentIndex.value - 1);
}

function goToSlide(index) {
    goTo(index);
}

function onCardClick(cardIndex) {
    const pos = getPosition(cardIndex);
    if (pos === "-1") goPrev();
    else if (pos === "1") goNext();
}

// Autoplay
function startAutoplay() {
    stopAutoplay();

    const bar = progressRef.value;
    if (bar) {
        bar.classList.remove("running");
        void bar.offsetWidth;
        bar.classList.add("running");
    }

    autoplayTimer = setTimeout(() => {
        goNext();
    }, AUTOPLAY_DELAY);
}

function stopAutoplay() {
    if (autoplayTimer) {
        clearTimeout(autoplayTimer);
        autoplayTimer = null;
    }
    const bar = progressRef.value;
    if (bar) {
        bar.classList.remove("running");
    }
}

function resetAutoplay() {
    if (!isPaused.value) {
        startAutoplay();
    }
}

// Pause only when hovering over cards
function onCardEnter() {
    isPaused.value = true;
    stopAutoplay();
}

function onCardLeave() {
    isPaused.value = false;
    startAutoplay();
}

// Touch / swipe
let touchStartX = 0;

function onTouchStart(e) {
    touchStartX = e.changedTouches[0].screenX;
    stopAutoplay();
}

function onTouchEnd(e) {
    const diff = touchStartX - e.changedTouches[0].screenX;
    if (Math.abs(diff) > 50) {
        diff > 0 ? goNext() : goPrev();
    }
    if (!isPaused.value) startAutoplay();
}

// Keyboard
function onKeydown(e) {
    if (e.key === "ArrowLeft") goPrev();
    if (e.key === "ArrowRight") goNext();
}

// Fetch
async function fetchTestimonials() {
    try {
        loading.value = true;
        const response = await axios.get("/api/testimonials/frontend");
        testimonials.value = response.data.testimonials;
    } catch (err) {
        error.value = _p("testimonials_error");
        console.error("Error fetching testimonials:", err);
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await fetchTestimonials();
    if (testimonials.value.length > 0) {
        startAutoplay();
    }
    document.addEventListener("keydown", onKeydown);
});

onBeforeUnmount(() => {
    stopAutoplay();
    document.removeEventListener("keydown", onKeydown);
});
</script>

<template>
    <section
        class="testimonials-section"
        aria-label="Customer testimonials"
    >
        <!-- Ambient orbs -->
        <div class="ts-orb ts-orb--1"></div>
        <div class="ts-orb ts-orb--2"></div>
        <div class="ts-orb ts-orb--3"></div>

        <!-- Header -->
        <div class="ts-header">
            <div class="ts-label">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"
                    />
                </svg>
                {{ _p("testimonials_title") }}
            </div>
            <h3 class="ts-title">{{ _p("testimonials_subtitle") }}</h3>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="ts-state">
            <div class="ts-spinner"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="ts-state ts-state--error">
            {{ error }}
        </div>

        <!-- Empty -->
        <div
            v-else-if="testimonials.length === 0"
            class="ts-state"
        >
            {{ _p("testimonials_no_data") }}
        </div>

        <!-- Slider -->
        <div v-else class="ts-slider-wrapper">
            <div
                class="ts-viewport"
                @touchstart.passive="onTouchStart"
                @touchend.passive="onTouchEnd"
            >
                <div
                    v-for="(testimonial, index) in testimonials"
                    :key="index"
                    class="ts-card"
                    :data-position="getPosition(index)"
                    @click="onCardClick(index)"
                    @mouseenter="onCardEnter"
                    @mouseleave="onCardLeave"
                >
                    <div class="ts-card-inner">
                        <!-- Stars -->
                        <div class="ts-stars">
                            <svg
                                v-for="s in 5"
                                :key="s"
                                class="ts-star"
                                :class="{
                                    'ts-star--empty': s > Math.floor(testimonial.rating),
                                }"
                                viewBox="0 0 24 24"
                                :fill="
                                    s <= Math.floor(testimonial.rating)
                                        ? 'currentColor'
                                        : 'none'
                                "
                                :stroke="
                                    s > Math.floor(testimonial.rating)
                                        ? 'currentColor'
                                        : 'none'
                                "
                                stroke-width="1.5"
                            >
                                <path
                                    d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"
                                />
                            </svg>
                        </div>

                        <!-- Review -->
                        <p class="ts-review">{{ testimonial.comment }}</p>

                        <!-- Divider -->
                        <div class="ts-divider"></div>

                        <!-- Author -->
                        <div class="ts-author">
                            <div
                                v-if="testimonial.avatar"
                                class="ts-avatar"
                            >
                                <img
                                    :src="testimonial.avatar"
                                    :alt="testimonial.author"
                                />
                            </div>
                            <div v-else class="ts-avatar ts-avatar--initials">
                                {{ getInitials(testimonial.author) }}
                            </div>
                            <div class="ts-author-info">
                                <span class="ts-author-name">{{
                                    testimonial.author
                                }}</span>
                                <span class="ts-author-title">{{
                                    testimonial.title
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="ts-controls">
                <button
                    class="ts-btn"
                    aria-label="Previous testimonial"
                    @click="goPrev"
                >
                    <svg viewBox="0 0 24 24">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                </button>

                <div class="ts-dots">
                    <button
                        v-for="(_, i) in testimonials"
                        :key="i"
                        class="ts-dot"
                        :class="{ 'ts-dot--active': i === currentIndex }"
                        :aria-label="`Go to testimonial ${i + 1}`"
                        @click="goToSlide(i)"
                    ></button>
                </div>

                <button
                    class="ts-btn"
                    aria-label="Next testimonial"
                    @click="goNext"
                >
                    <svg viewBox="0 0 24 24">
                        <polyline points="9 6 15 12 9 18" />
                    </svg>
                </button>
            </div>

            <!-- Progress bar -->
            <div class="ts-progress">
                <div ref="progressRef" class="ts-progress-bar"></div>
            </div>

            <!-- Mobile hint -->
            <div class="ts-drag-hint">Swipe to navigate</div>
        </div>
    </section>
</template>

<style scoped>
/* ========================================
   SECTION WRAPPER
   ======================================== */
.testimonials-section {
    position: relative;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 0 100px;
    background: linear-gradient(
        155deg,
        #0a1d28 0%,
        #153b4f 40%,
        #0f2936 70%,
        #0b1b26 100%
    );
    overflow: hidden;
}

/* ========================================
   AMBIENT BACKGROUND
   ======================================== */
.testimonials-section::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(
            ellipse 600px 500px at 15% 20%,
            rgba(6, 182, 212, 0.15),
            transparent
        ),
        radial-gradient(
            ellipse 500px 400px at 85% 75%,
            rgba(6, 182, 212, 0.08),
            transparent
        ),
        radial-gradient(
            ellipse 800px 300px at 50% 50%,
            rgba(21, 59, 79, 0.5),
            transparent
        );
    pointer-events: none;
    z-index: 0;
}

.testimonials-section::after {
    content: "";
    position: absolute;
    inset: 0;
    background-image: linear-gradient(
            rgba(6, 182, 212, 0.03) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(6, 182, 212, 0.03) 1px,
            transparent 1px
        );
    background-size: 60px 60px;
    mask-image: radial-gradient(
        ellipse 70% 60% at 50% 50%,
        black 20%,
        transparent 70%
    );
    -webkit-mask-image: radial-gradient(
        ellipse 70% 60% at 50% 50%,
        black 20%,
        transparent 70%
    );
    pointer-events: none;
    z-index: 0;
}

/* Orbs */
.ts-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
    pointer-events: none;
    z-index: 0;
    animation: tsOrbFloat 12s ease-in-out infinite;
}

.ts-orb--1 {
    width: 300px;
    height: 300px;
    background: #06b6d4;
    top: 10%;
    left: -5%;
}

.ts-orb--2 {
    width: 200px;
    height: 200px;
    background: #153b4f;
    bottom: 15%;
    right: -3%;
    animation-delay: -4s;
    opacity: 0.4;
}

.ts-orb--3 {
    width: 150px;
    height: 150px;
    background: #22d3ee;
    top: 60%;
    left: 50%;
    animation-delay: -8s;
    opacity: 0.15;
}

@keyframes tsOrbFloat {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -20px) scale(1.05);
    }
    66% {
        transform: translate(-20px, 15px) scale(0.95);
    }
}

/* ========================================
   HEADER
   ======================================== */
.ts-header {
    position: relative;
    z-index: 2;
    text-align: center;
    margin-bottom: 64px;
    padding: 0 24px;
}

.ts-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #06b6d4;
    margin-bottom: 20px;
    padding: 8px 20px;
    background: rgba(6, 182, 212, 0.12);
    border: 1px solid rgba(6, 182, 212, 0.15);
    border-radius: 100px;
}

.ts-label svg {
    width: 14px;
    height: 14px;
    fill: #06b6d4;
}

.ts-title {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    line-height: 1.15;
    color: #ffffff;
    letter-spacing: -0.02em;
}

/* ========================================
   STATES (loading / error / empty)
   ======================================== */
.ts-state {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 48px;
    color: #f8fafc;
    font-size: 1rem;
}

.ts-state--error {
    color: #f87171;
}

.ts-spinner {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid rgba(6, 182, 212, 0.2);
    border-top-color: #06b6d4;
    animation: tsSpin 0.8s linear infinite;
}

@keyframes tsSpin {
    to {
        transform: rotate(360deg);
    }
}

/* ========================================
   SLIDER
   ======================================== */
.ts-slider-wrapper {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

.ts-viewport {
    position: relative;
    width: 100%;
    height: 420px;
    perspective: 1200px;
    perspective-origin: 50% 50%;
}

/* ========================================
   CARDS
   ======================================== */
.ts-card {
    position: absolute;
    top: 50%;
    left: 50%;
    width: min(460px, 85vw);
    transform: translate(-50%, -50%);
    padding: 40px 36px 36px;
    background: rgba(21, 59, 79, 0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(6, 182, 212, 0.08);
    border-radius: 20px;
    cursor: pointer;
    user-select: none;
    transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1),
        opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1),
        filter 0.7s cubic-bezier(0.22, 1, 0.36, 1),
        border-color 0.5s ease, box-shadow 0.5s ease;
    will-change: transform, opacity, filter;
}

/* Glow overlay */
.ts-card::before {
    content: "";
    position: absolute;
    inset: -1px;
    border-radius: 21px;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.25) 0%,
        transparent 40%,
        transparent 60%,
        rgba(6, 182, 212, 0.1) 100%
    );
    opacity: 0;
    transition: opacity 0.5s ease;
    pointer-events: none;
    z-index: -1;
}

/* Decorative quote */
.ts-card::after {
    content: "\201C";
    position: absolute;
    top: -8px;
    left: 28px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 7rem;
    line-height: 1;
    color: rgba(6, 182, 212, 0.08);
    pointer-events: none;
    transition: color 0.5s ease;
}

/* Active (center) */
.ts-card[data-position="0"] {
    transform: translate(-50%, -50%) rotateY(0deg) scale(1);
    opacity: 1;
    filter: blur(0) brightness(1);
    z-index: 5;
    border-color: rgba(6, 182, 212, 0.35);
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35),
        0 0 60px rgba(6, 182, 212, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.ts-card[data-position="0"]::before {
    opacity: 1;
}

.ts-card[data-position="0"]::after {
    color: rgba(6, 182, 212, 0.12);
}

/* Left neighbor */
.ts-card[data-position="-1"] {
    transform: translate(calc(-50% - 380px), -50%) rotateY(8deg) scale(0.82);
    opacity: 0.45;
    filter: blur(2.5px) brightness(0.7);
    z-index: 3;
}

/* Right neighbor */
.ts-card[data-position="1"] {
    transform: translate(calc(-50% + 380px), -50%) rotateY(-8deg) scale(0.82);
    opacity: 0.45;
    filter: blur(2.5px) brightness(0.7);
    z-index: 3;
}

/* Far left */
.ts-card[data-position="-2"] {
    transform: translate(calc(-50% - 680px), -50%) rotateY(14deg) scale(0.65);
    opacity: 0.15;
    filter: blur(6px) brightness(0.5);
    z-index: 1;
}

/* Far right */
.ts-card[data-position="2"] {
    transform: translate(calc(-50% + 680px), -50%) rotateY(-14deg) scale(0.65);
    opacity: 0.15;
    filter: blur(6px) brightness(0.5);
    z-index: 1;
}

/* Hidden off-screen */
.ts-card[data-position="hidden-left"] {
    transform: translate(calc(-50% - 900px), -50%) rotateY(18deg) scale(0.5);
    opacity: 0;
    filter: blur(10px);
    z-index: 0;
    pointer-events: none;
}

.ts-card[data-position="hidden-right"] {
    transform: translate(calc(-50% + 900px), -50%) rotateY(-18deg) scale(0.5);
    opacity: 0;
    filter: blur(10px);
    z-index: 0;
    pointer-events: none;
}

/* Active card float */
.ts-card[data-position="0"] .ts-card-inner {
    animation: tsCardFloat 4s ease-in-out infinite;
}

@keyframes tsCardFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

/* ========================================
   CARD CONTENT
   ======================================== */
.ts-card-inner {
    position: relative;
    z-index: 1;
}

.ts-stars {
    display: flex;
    gap: 3px;
    margin-bottom: 20px;
}

.ts-star {
    width: 18px;
    height: 18px;
    color: #f59e0b;
    filter: drop-shadow(0 0 4px rgba(245, 158, 11, 0.3));
}

.ts-star--empty {
    color: #64748b;
    filter: none;
}

.ts-review {
    font-family: "IBM Plex Sans", serif;
    font-size: 1.05rem;
    font-weight: 300;
    line-height: 1.7;
    color: #cbd5e1;
    margin-bottom: 28px;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 7.2em;
}

.ts-card[data-position="0"] .ts-review {
    color: rgba(248, 250, 252, 0.9);
}

.ts-divider {
    width: 100%;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(6, 182, 212, 0.2) 20%,
        rgba(6, 182, 212, 0.2) 80%,
        transparent
    );
    margin-bottom: 24px;
}

.ts-author {
    display: flex;
    align-items: center;
    gap: 14px;
}

.ts-avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    overflow: hidden;
    border: 2px solid rgba(6, 182, 212, 0.2);
    flex-shrink: 0;
}

.ts-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ts-avatar--initials {
    background: linear-gradient(135deg, #153b4f, #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #ffffff;
}

.ts-author-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ts-author-name {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -0.01em;
}

.ts-author-title {
    font-family: "IBM Plex Sans", serif;
    font-size: 0.8rem;
    font-weight: 400;
    color: #64748b;
}

/* ========================================
   CONTROLS
   ======================================== */
.ts-controls {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 32px;
    margin-top: 48px;
}

.ts-btn {
    width: 52px;
    height: 52px;
    border: 1px solid rgba(6, 182, 212, 0.2);
    border-radius: 50%;
    background: rgba(21, 59, 79, 0.4);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #f8fafc;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    outline: none;
}

.ts-btn:hover {
    background: rgba(6, 182, 212, 0.15);
    border-color: rgba(6, 182, 212, 0.4);
    box-shadow: 0 0 24px rgba(6, 182, 212, 0.15);
    transform: scale(1.08);
}

.ts-btn:active {
    transform: scale(0.95);
}

.ts-btn svg {
    width: 20px;
    height: 20px;
    stroke: #f8fafc;
    stroke-width: 2;
    fill: none;
    transition: stroke 0.3s ease;
}

.ts-btn:hover svg {
    stroke: #22d3ee;
}

/* Dots */
.ts-dots {
    display: flex;
    gap: 10px;
    align-items: center;
}

.ts-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: none;
    background: rgba(148, 163, 184, 0.3);
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    outline: none;
    padding: 0;
}

.ts-dot:hover {
    background: rgba(6, 182, 212, 0.5);
    transform: scale(1.3);
}

.ts-dot--active {
    width: 28px;
    border-radius: 100px;
    background: #06b6d4;
    box-shadow: 0 0 16px rgba(6, 182, 212, 0.4);
}

/* ========================================
   PROGRESS BAR
   ======================================== */
.ts-progress {
    position: relative;
    z-index: 2;
    width: 120px;
    height: 2px;
    background: rgba(148, 163, 184, 0.15);
    border-radius: 2px;
    margin: 24px auto 0;
    overflow: hidden;
}

.ts-progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #06b6d4, #22d3ee);
    border-radius: 2px;
}

.ts-progress-bar.running {
    animation: tsProgressFill 6s linear forwards;
}

@keyframes tsProgressFill {
    from {
        width: 0%;
    }
    to {
        width: 100%;
    }
}

/* ========================================
   DRAG HINT
   ======================================== */
.ts-drag-hint {
    position: relative;
    z-index: 2;
    text-align: center;
    margin-top: 16px;
    font-family: "IBM Plex Sans", serif;
    font-size: 0.75rem;
    font-weight: 400;
    color: #64748b;
    opacity: 0.6;
    display: none;
}

@media (pointer: coarse) {
    .ts-drag-hint {
        display: block;
    }
}

/* ========================================
   ENTRANCE ANIMATIONS
   ======================================== */
.ts-header,
.ts-viewport,
.ts-controls,
.ts-progress {
    opacity: 0;
    transform: translateY(30px);
    animation: tsFadeUp 0.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.ts-viewport {
    animation-delay: 0.15s;
}
.ts-controls {
    animation-delay: 0.3s;
}
.ts-progress {
    animation-delay: 0.4s;
}

@keyframes tsFadeUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ========================================
   RESPONSIVE
   ======================================== */
@media (max-width: 1024px) {
    .ts-card[data-position="-1"] {
        transform: translate(calc(-50% - 280px), -50%) rotateY(6deg)
            scale(0.8);
    }
    .ts-card[data-position="1"] {
        transform: translate(calc(-50% + 280px), -50%) rotateY(-6deg)
            scale(0.8);
    }
    .ts-card[data-position="-2"],
    .ts-card[data-position="2"] {
        opacity: 0;
        pointer-events: none;
    }
}

@media (max-width: 768px) {
    .testimonials-section {
        padding: 60px 0 80px;
    }

    .ts-header {
        margin-bottom: 48px;
    }

    .ts-viewport {
        height: 380px;
    }

    .ts-card {
        padding: 32px 28px 28px;
    }

    .ts-card[data-position="-1"] {
        transform: translate(calc(-50% - 60px), -50%) rotateY(3deg)
            scale(0.88);
        opacity: 0.25;
        filter: blur(4px) brightness(0.6);
    }

    .ts-card[data-position="1"] {
        transform: translate(calc(-50% + 60px), -50%) rotateY(-3deg)
            scale(0.88);
        opacity: 0.25;
        filter: blur(4px) brightness(0.6);
    }

    .ts-card[data-position="-2"],
    .ts-card[data-position="2"] {
        opacity: 0;
        pointer-events: none;
    }

    .ts-controls {
        gap: 20px;
        margin-top: 36px;
    }

    .ts-btn {
        width: 44px;
        height: 44px;
    }
}

@media (max-width: 480px) {
    .ts-card[data-position="-1"],
    .ts-card[data-position="1"] {
        opacity: 0.12;
        filter: blur(6px) brightness(0.5);
    }

    .ts-review {
        font-size: 0.95rem;
        -webkit-line-clamp: 3;
        min-height: 5.4em;
    }
}
</style>
