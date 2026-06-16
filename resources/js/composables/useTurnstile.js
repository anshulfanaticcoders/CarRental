import { onMounted, onUnmounted, ref } from 'vue';

const TURNSTILE_SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let turnstileScriptPromise = null;
let turnstileRenderQueue = Promise.resolve();
let turnstileContainerSequence = 0;

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

function createTurnstileError(message) {
    const error = new Error(message);
    error.isTurnstileError = true;

    return error;
}

export function useTurnstile(options = {}) {
    const turnstileContainer = ref(null);
    const turnstileToken = ref('');
    const turnstileError = ref('');
    const turnstileReady = ref(false);
    let widgetId = null;
    let pendingTokenPromise = null;
    let pendingTokenResolve = null;
    let pendingTokenReject = null;

    const ensureContainerSelector = () => {
        if (!turnstileContainer.value) return null;

        if (!turnstileContainer.value.id) {
            turnstileContainerSequence += 1;
            turnstileContainer.value.id = `turnstile-widget-${turnstileContainerSequence}`;
        }

        return `#${turnstileContainer.value.id}`;
    };

    const resolvePendingToken = (token) => {
        pendingTokenResolve?.(token);
        pendingTokenPromise = null;
        pendingTokenResolve = null;
        pendingTokenReject = null;
    };

    const rejectPendingToken = (message) => {
        pendingTokenReject?.(createTurnstileError(message));
        pendingTokenPromise = null;
        pendingTokenResolve = null;
        pendingTokenReject = null;
    };

    const waitForToken = () => {
        if (!pendingTokenPromise) {
            pendingTokenPromise = new Promise((resolve, reject) => {
                pendingTokenResolve = resolve;
                pendingTokenReject = reject;
            });
        }

        return pendingTokenPromise;
    };

    const renderTurnstile = async () => {
        if (typeof window === 'undefined') return null;
        if (widgetId !== null) return widgetId;

        const siteKey = options.siteKey || import.meta.env.VITE_TURNSTILE_SITE_KEY;

        if (!siteKey) {
            turnstileError.value = 'Security check is not configured.';
            return null;
        }

        const containerSelector = ensureContainerSelector();
        if (!containerSelector) return null;

        try {
            await queueTurnstileRender(async () => {
                const turnstile = await loadTurnstile();
                const queuedContainerSelector = ensureContainerSelector();
                if (!queuedContainerSelector || widgetId !== null) return;

                const renderOptions = {
                    sitekey: siteKey,
                    theme: options.theme || 'auto',
                    size: options.size || 'normal',
                    callback: (token) => {
                        turnstileToken.value = token;
                        turnstileError.value = '';
                        resolvePendingToken(token);
                    },
                    'expired-callback': () => {
                        turnstileToken.value = '';
                        const message = 'Security check expired. Please complete it again.';
                        turnstileError.value = message;
                        rejectPendingToken(message);
                    },
                    'error-callback': () => {
                        turnstileToken.value = '';
                        const message = 'Security check failed to load. Please refresh and try again.';
                        turnstileError.value = message;
                        rejectPendingToken(message);
                    },
                    'timeout-callback': () => {
                        turnstileToken.value = '';
                        const message = 'Security check timed out. Please try again.';
                        turnstileError.value = message;
                        rejectPendingToken(message);
                    },
                };

                if (options.action) renderOptions.action = options.action;
                if (options.appearance) renderOptions.appearance = options.appearance;
                if (options.execution) renderOptions.execution = options.execution;
                if (options.language) renderOptions.language = options.language;

                widgetId = turnstile.render(queuedContainerSelector, renderOptions);
                turnstileReady.value = true;
            });
        } catch (error) {
            turnstileError.value = 'Security check failed to load. Please refresh and try again.';
        }

        return widgetId;
    };

    const executeTurnstile = async () => {
        if (turnstileToken.value) return turnstileToken.value;

        const renderedWidgetId = await renderTurnstile();

        if (turnstileToken.value) return turnstileToken.value;

        if (typeof window === 'undefined' || !window.turnstile || renderedWidgetId === null) {
            const message = turnstileError.value || 'Security verification failed. Please try again.';
            throw createTurnstileError(message);
        }

        const tokenPromise = waitForToken();

        try {
            const containerSelector = ensureContainerSelector();
            if (!containerSelector) throw createTurnstileError('Security verification failed. Please try again.');

            window.turnstile.execute(containerSelector);
        } catch (error) {
            const message = 'Security verification failed. Please try again.';
            turnstileError.value = message;
            rejectPendingToken(message);
        }

        return tokenPromise;
    };

    const resetTurnstile = () => {
        turnstileToken.value = '';
        rejectPendingToken('Security check reset.');

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
        rejectPendingToken('Security check removed.');

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
        executeTurnstile,
        renderTurnstile,
        resetTurnstile,
    };
}
