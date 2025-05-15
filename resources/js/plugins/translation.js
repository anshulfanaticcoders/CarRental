import { usePage } from '@inertiajs/vue3';

export default {
  install(app) {
    // General translation function (for 'messages')
    app.config.globalProperties.__ = (key, replacements = {}) => {
      const page = usePage(); // Access page dynamically
      const translations = page.props.translations?.messages || {};
      let translation = translations[key] || key;

      // Replace placeholders if any
      Object.keys(replacements).forEach(k => {
        translation = translation.replace(`:${k}`, replacements[k]);
      });

      return translation;
    };

    // Page-specific translation function (for 'homepage' or other files)
    app.config.globalProperties._p = (key, replacements = {}) => {
      const page = usePage(); // Access page dynamically
      const translations = page.props.translations?.homepage || {};
      let translation = translations[key] || key;

      // Replace placeholders if any
      Object.keys(replacements).forEach(k => {
        translation = translation.replace(`:${k}`, replacements[k]);
      });

      return translation;
    };
  }
};