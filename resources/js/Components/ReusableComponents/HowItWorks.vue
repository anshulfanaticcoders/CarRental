<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Access page props
const page = usePage();

// Translation helpers
const __ = (key, replacements = {}) => {
    const translations = page.props.translations?.messages || {};
    let translation = translations[key] || key;
    
    // Replace placeholders if any
    Object.keys(replacements).forEach(k => {
        translation = translation.replace(`:${k}`, replacements[k]);
    });
    
    return translation;
};

const _p = (key, replacements = {}) => {
    const translations = page.props.translations?.homepage || {};
    let translation = translations[key] || key;
    
    // Replace placeholders if any
    Object.keys(replacements).forEach(k => {
        translation = translation.replace(`:${k}`, replacements[k]);
    });
    
    return translation;
};

const accordionItems = computed(() => [
    {
        value: "item-1",
        title: _p('how_it_works_step_1_title'), // Use translation key
        content: _p('how_it_works_step_1_content'),
    },
    {
        value: "item-2",
        title: _p('how_it_works_step_2_title'),
        content: _p('how_it_works_step_2_content'),
    },
    {
        value: "item-3",
        title: _p('how_it_works_step_3_title'),
        content: _p('how_it_works_step_3_content'),
    },
]);
</script>

<template>
    <section
        class="how-it-works home-section home-section--light"
    >
        <div class="full-w-container how-it-works-inner">
            <div class="how-it-works-header">
                <span class="how-it-works-tag">{{ _p('how_it_works_title') }}</span>
                <h3 class="how-it-works-title">{{ _p('how_it_works_subtitle') }}</h3>
                <p class="how-it-works-description">
                    {{ _p('how_it_works_description') }}
                </p>
            </div>

            <div class="how-it-works-steps">
                <div v-for="(item, index) in accordionItems" :key="item.value" class="how-it-works-card">
                    <div class="how-it-works-index">0{{ index + 1 }}</div>
                    <h4>{{ item.title }}</h4>
                    <p>{{ item.content }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.how-it-works-inner {
    display: flex;
    flex-direction: column;
    gap: 3rem;
}

.how-it-works-header {
    text-align: center;
    max-width: 720px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.how-it-works-tag {
    letter-spacing: 0.2em;
    text-transform: uppercase;
    font-size: 0.85rem;
    color: #2ea7ad;
}

.how-it-works-title {
    font-size: clamp(2.1rem, 4vw, 3.2rem);
    color: #0f172a;
    margin: 0;
}

.how-it-works-description {
    font-size: 1.1rem;
    color: #64748b;
    margin: 0;
}

.how-it-works-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 2rem;
    position: relative;
}

.how-it-works-steps::before {
    content: "";
    position: absolute;
    top: 52px;
    left: 10%;
    right: 10%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(46, 167, 173, 0.4), transparent);
}

.how-it-works-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 2rem;
    border: 1px solid rgba(148, 163, 184, 0.28);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.how-it-works-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
}

.how-it-works-index {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: rgba(46, 167, 173, 0.12);
    color: #153b4f;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.08em;
    position: relative;
    z-index: 1;
}

.how-it-works-card h4 {
    margin: 0;
    font-size: 1.4rem;
    color: #0f172a;
}

.how-it-works-card p {
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}

@media screen and (max-width: 1024px) {
    .how-it-works-steps {
        grid-template-columns: 1fr;
    }

    .how-it-works-steps::before {
        top: 0;
        left: 24px;
        right: auto;
        bottom: 10px;
        width: 1px;
        height: calc(100% - 20px);
        background: linear-gradient(180deg, transparent, rgba(46, 167, 173, 0.4), transparent);
    }
}
@media screen and (max-width: 768px) {
    .how-it-works {
        background-image: none !important;
    }
}
</style>
