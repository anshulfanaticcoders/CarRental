const TAWK_SCRIPT_SRC = 'https://embed.tawk.to/6876027021757c1912dc0f68/1j06fj2e0';

let loadPromise = null;
let scriptEl = null;

export function isTawkLoaded() {
    return typeof window !== 'undefined' && !!window.Tawk_API;
}

export function ensureTawkLoaded() {
    if (typeof window === 'undefined') {
        return Promise.resolve(false);
    }

    if (window.Tawk_API) {
        return Promise.resolve(true);
    }

    if (loadPromise) {
        return loadPromise;
    }

    window.Tawk_API = window.Tawk_API || {};
    window.Tawk_LoadStart = new Date();

    loadPromise = new Promise((resolve) => {
        scriptEl = document.createElement('script');
        scriptEl.async = true;
        scriptEl.src = TAWK_SCRIPT_SRC;
        scriptEl.charset = 'UTF-8';
        scriptEl.setAttribute('crossorigin', '*');

        scriptEl.onload = () => resolve(true);
        scriptEl.onerror = () => {
            loadPromise = null;
            resolve(false);
        };

        document.head.appendChild(scriptEl);
    });

    return loadPromise;
}

export async function showTawk() {
    const loaded = await ensureTawkLoaded();
    if (!loaded) return;

    try {
        window.Tawk_API?.showWidget?.();
    } catch (e) {
        // Non-fatal: chat widget should never break the page.
        console.warn('Tawk showWidget failed', e);
    }
}

export function hideTawk() {
    if (typeof window === 'undefined' || !window.Tawk_API) return;

    try {
        window.Tawk_API?.hideWidget?.();
    } catch (e) {
        console.warn('Tawk hideWidget failed', e);
    }
}

export function removeTawkScriptTag() {
    if (scriptEl && scriptEl.parentNode) {
        scriptEl.parentNode.removeChild(scriptEl);
        scriptEl = null;
    }
}
