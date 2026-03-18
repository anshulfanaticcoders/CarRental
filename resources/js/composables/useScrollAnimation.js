import { onMounted, onUnmounted } from 'vue';
const DEFAULT_SCROLL_TRIGGER = { start: 'top 80%' };

const DEFAULT_TWEEN = {
  y: 32,
  duration: 1.1,
  ease: 'power2.out',
  stagger: 0.12,
};

const EASING_MAP = {
  'power2.out': 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
  'power3.out': 'cubic-bezier(0.215, 0.61, 0.355, 1)',
  'power4.out': 'cubic-bezier(0.165, 0.84, 0.44, 1)',
};

const parseStartToRootMargin = (startValue) => {
  const value = String(startValue || '').trim();
  const match = value.match(/top\s+(\d+)%/i);
  if (!match) return '0px 0px -20% 0px';
  const threshold = Number(match[1]);
  if (!Number.isFinite(threshold)) return '0px 0px -20% 0px';
  const bottomMargin = Math.max(0, 100 - threshold);
  return `0px 0px -${bottomMargin}% 0px`;
};

const unhide = (elements) => {
  for (const element of elements) {
    if (!element) continue;
    element.classList?.remove('sr-reveal');
    element.style.visibility = 'visible';
    element.style.opacity = '1';
    element.style.transform = 'translate3d(0, 0, 0)';
    element.style.willChange = '';
  }
};

const animateIn = (elements, options, registerTimeout, registerAnimation) => {
  const y = Number(options.y ?? DEFAULT_TWEEN.y);
  const duration = Number(options.duration ?? DEFAULT_TWEEN.duration);
  const stagger = Number(options.stagger ?? DEFAULT_TWEEN.stagger);
  const easing = EASING_MAP[options.ease] || EASING_MAP[DEFAULT_TWEEN.ease];

  elements.forEach((element, index) => {
    if (!element) return;

    const delayMs = Math.max(0, stagger * 1000 * index);
    element.classList?.remove('sr-reveal');
    element.style.visibility = 'visible';
    element.style.willChange = 'transform, opacity';

    if (typeof element.animate === 'function') {
      const animation = element.animate(
        [
          { opacity: 0, transform: `translate3d(0, ${y}px, 0)` },
          { opacity: 1, transform: 'translate3d(0, 0, 0)' },
        ],
        {
          duration: duration * 1000,
          delay: delayMs,
          easing,
          fill: 'both',
        },
      );

      registerAnimation(animation);
      animation.addEventListener(
        'finish',
        () => {
          element.style.opacity = '1';
          element.style.transform = 'translate3d(0, 0, 0)';
          element.style.willChange = '';
        },
        { once: true },
      );
      return;
    }

    registerTimeout(() => {
      element.style.opacity = '0';
      element.style.transform = `translate3d(0, ${y}px, 0)`;
      window.requestAnimationFrame(() => {
        element.style.transition = `opacity ${duration}s ${easing}, transform ${duration}s ${easing}`;
        element.style.opacity = '1';
        element.style.transform = 'translate3d(0, 0, 0)';
      });
      registerTimeout(() => {
        element.style.willChange = '';
      }, duration * 1000 + 40);
    }, delayMs);
  });
};

export function useScrollAnimation(triggerSelector, elementSelector, animationProps = {}) {
  let observer = null;
  const timeouts = new Set();
  const animations = new Set();

  onMounted(() => {
    if (typeof window === 'undefined') return;

    const trigger = document.querySelector(triggerSelector);
    if (!trigger) return;

    const elements = Array.from(trigger.querySelectorAll(elementSelector));
    if (elements.length === 0) return;

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
      unhide(elements);
      return;
    }

    const registerTimeout = (fn, delay) => {
      const id = window.setTimeout(() => {
        timeouts.delete(id);
        fn();
      }, delay);
      timeouts.add(id);
      return id;
    };
    const registerAnimation = (animation) => {
      if (!animation) return;
      animations.add(animation);
      animation.addEventListener(
        'cancel',
        () => {
          animations.delete(animation);
        },
        { once: true },
      );
      animation.addEventListener(
        'finish',
        () => {
          animations.delete(animation);
        },
        { once: true },
      );
    };

    const scrollTrigger = {
      ...DEFAULT_SCROLL_TRIGGER,
      ...(animationProps.scrollTrigger || {}),
    };

    const reveal = () => {
      animateIn(elements, {
        ...DEFAULT_TWEEN,
        ...animationProps,
      }, registerTimeout, registerAnimation);
      observer?.disconnect();
      observer = null;
    };

    if (!('IntersectionObserver' in window)) {
      reveal();
      return;
    }

    observer = new IntersectionObserver(
      ([entry]) => {
        if (entry?.isIntersecting) {
          reveal();
        }
      },
      {
        root: null,
        threshold: 0.01,
        rootMargin: parseStartToRootMargin(scrollTrigger.start),
      },
    );

    observer.observe(trigger);
  });

  onUnmounted(() => {
    observer?.disconnect();
    observer = null;
    for (const timeoutId of timeouts) {
      window.clearTimeout(timeoutId);
    }
    timeouts.clear();
    for (const animation of animations) {
      animation.cancel();
    }
    animations.clear();
  });
}
