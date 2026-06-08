import { onMounted, onUnmounted, ref } from 'vue';

const TURNSTILE_SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let turnstileScriptPromise = null;
let turnstileRenderQueue = Promise.resolve();

function waitForTurnstile(resolve, reject, attempts = 0) {
    if (typeof window !== 'undefined' && window.turnstile) {
        resolve(window.turnstile);
        return;
    }

    if (attempts > 100) {
        reject(new Error('Cloudflare Turnstile did not become available.'));
        return;
    }

    window.setTimeout(() => waitForTurnstile(resolve, reject, attempts + 1), 50);
}

function loadTurnstile() {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('Cloudflare Turnstile is unavailable during SSR.'));
    }

    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (!turnstileScriptPromise) {
        turnstileScriptPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]');

            if (existingScript) {
                waitForTurnstile(resolve, reject);
                return;
            }

            const script = document.createElement('script');
            script.src = TURNSTILE_SCRIPT_SRC;
            script.async = true;
            script.defer = true;
            script.onload = () => waitForTurnstile(resolve, reject);
            script.onerror = () => reject(new Error('Cloudflare Turnstile script failed to load.'));
            document.head.appendChild(script);
        });
    }

    return turnstileScriptPromise;
}

function queueTurnstileRender(callback) {
    const queuedRender = turnstileRenderQueue.then(callback, callback);
    turnstileRenderQueue = queuedRender.catch(() => {});

    return queuedRender;
}

export function useTurnstile(options = {}) {
    const turnstileContainer = ref(null);
    const turnstileToken = ref('');
    const turnstileError = ref('');
    const turnstileReady = ref(false);
    let widgetId = null;

    const renderTurnstile = async () => {
        if (typeof window === 'undefined' || widgetId !== null) return;

        const siteKey = options.siteKey || import.meta.env.VITE_TURNSTILE_SITE_KEY;

        if (!siteKey) {
            turnstileError.value = 'Security check is not configured.';
            return;
        }

        if (!turnstileContainer.value) return;

        try {
            await queueTurnstileRender(async () => {
                const turnstile = await loadTurnstile();
                if (!turnstileContainer.value || widgetId !== null) return;

                const renderOptions = {
                    sitekey: siteKey,
                    theme: options.theme || 'auto',
                    size: options.size || 'normal',
                    callback: (token) => {
                        turnstileToken.value = token;
                        turnstileError.value = '';
                    },
                    'expired-callback': () => {
                        turnstileToken.value = '';
                        turnstileError.value = 'Security check expired. Please complete it again.';
                    },
                    'error-callback': () => {
                        turnstileToken.value = '';
                        turnstileError.value = 'Security check failed to load. Please refresh and try again.';
                    },
                };

                if (options.action) {
                    renderOptions.action = options.action;
                }

                widgetId = turnstile.render(turnstileContainer.value, renderOptions);
                turnstileReady.value = true;
            });
        } catch (error) {
            turnstileError.value = 'Security check failed to load. Please refresh and try again.';
        }
    };

    const resetTurnstile = () => {
        turnstileToken.value = '';

        if (typeof window !== 'undefined' && window.turnstile && widgetId !== null) {
            window.turnstile.reset(widgetId);
        }
    };

    onMounted(() => {
        if (!options.defer) {
            renderTurnstile();
        }
    });

    onUnmounted(() => {
        if (typeof window !== 'undefined' && window.turnstile && widgetId !== null) {
            window.turnstile.remove(widgetId);
        }

        widgetId = null;
        turnstileReady.value = false;
        turnstileToken.value = '';
    });

    return {
        turnstileContainer,
        turnstileToken,
        turnstileError,
        turnstileReady,
        renderTurnstile,
        resetTurnstile,
    };
}
