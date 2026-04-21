import './bootstrap';
import '../css/app.css';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const el = document.getElementById('app');

if (el && el.dataset.page) {
  createInertiaApp({
    page: JSON.parse(el.dataset.page),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
      createRoot(el).render(<App {...props} />);
    },
    progress: {
      color: '#4B5563',
    },
  });
}
