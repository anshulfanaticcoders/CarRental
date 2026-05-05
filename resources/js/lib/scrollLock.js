let lockCount = 0;
let previousStyles = null;
let cachedScrollbarWidth = 0;

const measureScrollbarWidth = () => {
    // Difference between layout viewport and scrollable area = scrollbar width.
    return Math.max(0, window.innerWidth - document.documentElement.clientWidth);
};

const cacheStyles = () => {
    const html = document.documentElement;
    const body = document.body;

    previousStyles = {
        htmlOverflow: html.style.overflow,
        bodyOverflow: body.style.overflow,
        htmlOverscrollBehavior: html.style.overscrollBehavior,
        bodyOverscrollBehavior: body.style.overscrollBehavior,
        bodyPaddingRight: body.style.paddingRight,
    };
};

const applyLockStyles = () => {
    const html = document.documentElement;
    const body = document.body;

    // Measure BEFORE we hide overflow — once overflow is hidden the scrollbar
    // disappears and the measurement would return 0.
    cachedScrollbarWidth = measureScrollbarWidth();

    html.style.overflow = 'hidden';
    body.style.overflow = 'hidden';
    html.style.overscrollBehavior = 'contain';
    body.style.overscrollBehavior = 'contain';

    // Compensate for the scrollbar going away on long pages so content doesn't
    // shift right. On short pages there's no scrollbar to begin with → 0 → no-op
    // → no empty strip.
    if (cachedScrollbarWidth > 0) {
        const currentPad = parseInt(getComputedStyle(body).paddingRight, 10) || 0;
        body.style.paddingRight = `${currentPad + cachedScrollbarWidth}px`;
    }

    // Expose for fixed-positioned elements (header, floating widget) so their
    // CSS can compensate via padding-right. Without this, fixed elements
    // anchored to the viewport edge appear to slide right when scrollbar
    // disappears.
    html.style.setProperty('--scrollbar-lock-pad', `${cachedScrollbarWidth}px`);
    html.dataset.scrollLocked = 'true';
};

const restoreStyles = () => {
    const html = document.documentElement;
    const body = document.body;

    if (!previousStyles) {
        html.style.overflow = '';
        body.style.overflow = '';
        html.style.overscrollBehavior = '';
        body.style.overscrollBehavior = '';
        body.style.paddingRight = '';
        return;
    }

    html.style.overflow = previousStyles.htmlOverflow;
    body.style.overflow = previousStyles.bodyOverflow;
    html.style.overscrollBehavior = previousStyles.htmlOverscrollBehavior;
    body.style.overscrollBehavior = previousStyles.bodyOverscrollBehavior;
    body.style.paddingRight = previousStyles.bodyPaddingRight;
    html.style.removeProperty('--scrollbar-lock-pad');
    delete html.dataset.scrollLocked;
};

export const setScrollLock = (isLocked) => {
    if (typeof document === 'undefined' || !document.body || !document.documentElement) {
        return;
    }

    if (isLocked) {
        if (lockCount === 0) {
            cacheStyles();
            applyLockStyles();
        }
        lockCount += 1;
        return;
    }

    if (lockCount === 0) {
        return;
    }

    lockCount = Math.max(0, lockCount - 1);
    if (lockCount === 0) {
        restoreStyles();
        previousStyles = null;
    }
};
