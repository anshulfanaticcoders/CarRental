let lockCount = 0;
let previousStyles = null;

const cacheStyles = () => {
    const html = document.documentElement;
    const body = document.body;

    previousStyles = {
        htmlOverflow: html.style.overflow,
        bodyOverflow: body.style.overflow,
        htmlOverscrollBehavior: html.style.overscrollBehavior,
        bodyOverscrollBehavior: body.style.overscrollBehavior,
    };
};

const applyLockStyles = () => {
    const html = document.documentElement;
    const body = document.body;

    html.style.overflow = 'hidden';
    body.style.overflow = 'hidden';
    html.style.overscrollBehavior = 'contain';
    body.style.overscrollBehavior = 'contain';
};

const restoreStyles = () => {
    const html = document.documentElement;
    const body = document.body;

    if (!previousStyles) {
        html.style.overflow = '';
        body.style.overflow = '';
        html.style.overscrollBehavior = '';
        body.style.overscrollBehavior = '';
        return;
    }

    html.style.overflow = previousStyles.htmlOverflow;
    body.style.overflow = previousStyles.bodyOverflow;
    html.style.overscrollBehavior = previousStyles.htmlOverscrollBehavior;
    body.style.overscrollBehavior = previousStyles.bodyOverscrollBehavior;
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
