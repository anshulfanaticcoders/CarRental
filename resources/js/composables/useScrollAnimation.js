import { onMounted, onUnmounted } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export function useScrollAnimation(triggerSelector, elementSelector, animationProps) {
  let scrollTriggerInstance;

  onMounted(() => {
    const trigger = document.querySelector(triggerSelector);
    if (!trigger) return;

    const elements = gsap.utils.toArray(elementSelector);
    if (elements.length === 0) return;

    scrollTriggerInstance = gsap.from(elements, {
      ...animationProps,
      scrollTrigger: {
        trigger: trigger,
        start: 'top 80%',
        end: 'bottom 20%',
        toggleActions: 'play none none none',
      },
      stagger: 0.3,
      duration: 1,
    }).scrollTrigger;
  });

  onUnmounted(() => {
    if (scrollTriggerInstance) {
      scrollTriggerInstance.kill();
    }
  });
}
