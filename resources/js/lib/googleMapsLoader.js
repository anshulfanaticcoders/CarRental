let googleMapsPromise = null;

const getGoogleMapsApiKey = () =>
    document.querySelector('meta[name="google-maps-api-key"]')?.getAttribute('content')?.trim() || '';

export function loadGoogleMaps() {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('Google Maps can only be loaded in the browser.'));
    }

    if (window.google?.maps?.importLibrary) {
        const resolved = Promise.resolve(window.google);
        window.googleMapsReady = resolved;
        return resolved;
    }

    if (window.googleMapsReady) {
        return window.googleMapsReady;
    }

    const apiKey = getGoogleMapsApiKey();

    if (!apiKey) {
        return Promise.reject(new Error('Google Maps API key meta tag is missing.'));
    }

    googleMapsPromise ??= new Promise((resolve, reject) => {
        const existingScript = document.getElementById('google-maps-sdk');

        const cleanup = () => {
            window.initGoogleMaps = undefined;
        };

        const resolveReady = () => {
            cleanup();
            resolve(window.google);
        };

        const rejectReady = () => {
            cleanup();
            googleMapsPromise = null;
            window.googleMapsReady = null;
            reject(new Error('Failed to load Google Maps SDK.'));
        };

        window.initGoogleMaps = resolveReady;

        if (existingScript) {
            existingScript.addEventListener('load', resolveReady, { once: true });
            existingScript.addEventListener('error', rejectReady, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.id = 'google-maps-sdk';
        script.async = true;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&loading=async&callback=initGoogleMaps`;
        script.addEventListener('error', rejectReady, { once: true });
        document.head.appendChild(script);
    });

    window.googleMapsReady = googleMapsPromise;

    return googleMapsPromise;
}
