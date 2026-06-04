export function useSmoothAnchorScroll() {
    function scrollToAnchor(event, url) {
        if (typeof url !== 'string' || !url.startsWith('#') || url.length < 2 || typeof document === 'undefined') {
            return;
        }

        const target = document.getElementById(url.slice(1));
        if (!target) return;

        event?.preventDefault();

        if (typeof window !== 'undefined' && window.location.hash) {
            window.history.replaceState(window.history.state, document.title, `${window.location.pathname}${window.location.search}`);
        }

        const reducedMotion = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        target.scrollIntoView({
            behavior: reducedMotion ? 'auto' : 'smooth',
            block: 'start',
        });
    }

    return { scrollToAnchor };
}
