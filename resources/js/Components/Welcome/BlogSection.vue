<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useScrollAnimation } from '@/composables/useScrollAnimation';
const props = defineProps({ blogs: { type: Array, default: () => [] } });

const page = usePage();
const _p = (key, fallback = '') => (page.props.translations?.homepage?.[key] || fallback || key);
const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
const plain = (value) => String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
const excerptText = (blog, max = 120) => {
    const base = plain(blog?.excerpt || blog?.description || blog?.content);
    if (!base) return 'Read the full story and discover practical tips for your next trip.';
    return base.length > max ? `${base.slice(0, max).trimEnd()}...` : base;
};

useScrollAnimation('.blog-section', '.blog-header, .blog-feat, .blog-card, .blog-cta', {
    y: 52,
    duration: 0.95,
    stagger: 0.1,
});
</script>

<template>
    <section v-if="blogs && blogs.length" class="blog-section blog-trigger">
        <div class="blog-glow"></div>
        <div class="full-w-container blog-z">
            <div class="blog-header sr-reveal">
                <div>
                    <span class="blog-label">{{ _p('blogs_title', 'From The Journal') }}</span>
                    <h3 class="blog-heading">{{ _p('blogs_subtitle', 'Travel stories & guides.') }}</h3>
                </div>
                <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })" class="blog-btn">{{ _p('more_blogs', 'View All Articles') }}</Link>
            </div>
            <div class="blog-grid">
                <Link :href="route('blog.show', { locale: page.props.locale, country: blogs[0].canonical_country || (page.props.country || 'us'), blog: blogs[0].translated_slug })" class="blog-feat sr-reveal">
                    <div class="blog-feat-img"><img :src="blogs[0].image" :alt="blogs[0].title" /></div>
                    <div class="blog-feat-content">
                        <span class="blog-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>{{ formatDate(blogs[0].created_at) }}</span>
                        <h4 class="blog-feat-title">{{ blogs[0].title }}</h4>
                        <p class="blog-feat-excerpt">{{ excerptText(blogs[0], 180) }}</p>
                        <span class="blog-read">Read Article <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                    </div>
                </Link>
                <div class="blog-stack">
                    <Link v-for="i in Math.min(3, blogs.length - 1)" :key="i" :href="route('blog.show', { locale: page.props.locale, country: blogs[i].canonical_country || (page.props.country || 'us'), blog: blogs[i].translated_slug })" class="blog-card sr-reveal">
                        <div class="blog-card-img"><img :src="blogs[i].image" :alt="blogs[i].title" /></div>
                        <div>
                            <span class="blog-date blog-date-dim"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>{{ formatDate(blogs[i].created_at) }}</span>
                            <h4 class="blog-card-title">{{ blogs[i].title }}</h4>
                            <p class="blog-card-excerpt">{{ excerptText(blogs[i], 100) }}</p>
                            <span class="blog-card-read">Read Story <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                        </div>
                    </Link>
                </div>
            </div>
            <div class="blog-cta sr-reveal">
                <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })" class="blog-btn">{{ _p('more_blogs', 'View All Articles') }}</Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

.blog-section { padding: clamp(4rem, 8vw, 7rem) 0; background: linear-gradient(160deg, #0a1d28 0%, #153b4f 45%, #0c2535 100%); color: #fff; position: relative; overflow: hidden; }
.blog-glow { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(circle at 18% 12%, rgba(34,211,238,0.08), transparent 45%), radial-gradient(circle at 80% 78%, rgba(10,29,40,0.3), transparent 55%); }
.blog-z { position: relative; z-index: 1; }

.blog-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 3rem; }
.blog-label { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: #22d3ee; }
.blog-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #22d3ee; }
.blog-heading { font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; line-height: 1.12; letter-spacing: -0.02em; color: #fff; margin-top: 0.75rem; }
.blog-btn { display: inline-flex; align-items: center; padding: 0.85rem 1.75rem; border-radius: 14px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.15); font-weight: 600; font-size: 0.92rem; transition: all 0.4s cubic-bezier(0.22,1,0.36,1); }
.blog-btn:hover { background: rgba(255,255,255,0.14); border-color: rgba(255,255,255,0.3); }

.blog-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem; align-items: stretch; }

.blog-feat { position: relative; border-radius: 20px; overflow: hidden; min-height: 520px; display: flex; flex-direction: column; justify-content: flex-end; cursor: pointer; }
.blog-feat-img { position: absolute; inset: 0; overflow: hidden; }
.blog-feat-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s cubic-bezier(0.22,1,0.36,1); }
.blog-feat:hover .blog-feat-img img { transform: scale(1.05); }
.blog-feat::before { content: ""; position: absolute; inset: 0; background: rgba(10,29,40,0.25); z-index: 1; }
.blog-feat::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,29,40,0.92) 0%, rgba(10,29,40,0.7) 25%, rgba(10,29,40,0.2) 50%, transparent 100%); z-index: 2; }
.blog-feat-content { position: relative; z-index: 3; padding: 2rem; }

.blog-date { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.76rem; font-weight: 500; color: rgba(255,255,255,0.65); margin-bottom: 0.75rem; }
.blog-date svg { width: 13px; height: 13px; }
.blog-date-dim { color: rgba(255,255,255,0.55); }

.blog-feat-title { font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1.25; margin-bottom: 0.5rem; text-shadow: 0 2px 12px rgba(0,0,0,0.4); }
.blog-feat-excerpt { font-size: 0.88rem; color: rgba(255,255,255,0.7); line-height: 1.6; margin-bottom: 1rem; max-width: 400px; text-shadow: 0 1px 6px rgba(0,0,0,0.25); }
.blog-read { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: #22d3ee; transition: gap 0.3s; }
.blog-read:hover { gap: 0.7rem; }
.blog-read svg { width: 14px; height: 14px; }

.blog-stack { display: flex; flex-direction: column; gap: 1.25rem; }
.blog-card { display: grid; grid-template-columns: 140px 1fr; gap: 1.25rem; align-items: center; padding: 1rem; border-radius: 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); transition: all 0.4s cubic-bezier(0.22,1,0.36,1); cursor: pointer; }
.blog-card:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); transform: translateX(4px); }
.blog-card-img { border-radius: 8px; overflow: hidden; aspect-ratio: 4/3; }
.blog-card-img img { width: 100%; height: 100%; object-fit: cover; }
.blog-card-title { font-size: 1rem; font-weight: 600; color: #fff; line-height: 1.35; margin-bottom: 0.25rem; }
.blog-card-excerpt { font-size: 0.82rem; color: rgba(255,255,255,0.5); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.blog-card-read { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 600; color: #22d3ee; margin-top: 0.5rem; }
.blog-card-read svg { width: 12px; height: 12px; }

.blog-cta { display: flex; justify-content: center; margin-top: 3rem; }

@media (max-width: 768px) { .blog-header { flex-direction: column; align-items: flex-start; gap: 1rem; } .blog-grid { grid-template-columns: 1fr; } .blog-feat { min-height: 360px; } .blog-card { grid-template-columns: 100px 1fr; } }
@media (max-width: 480px) { .blog-card { grid-template-columns: 1fr; } .blog-card-img { aspect-ratio: 16/9; } }
</style>
