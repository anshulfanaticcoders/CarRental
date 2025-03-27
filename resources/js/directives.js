export default {
    install(app) {
      app.directive('numeric', {
        beforeMount(el) {
          if (el.tagName === 'INPUT' && el.type === 'number') {
            el.addEventListener('input', (event) => {
              event.target.value = event.target.value.replace(/[^0-9]/g, ''); // Restrict input
              event.target.dispatchEvent(new Event('input')); // Trigger Vue reactivity
            });
          }
        }
      });
    }
  };
  