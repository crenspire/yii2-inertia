import React from 'react';
import ReactDOM from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';

// Import page components
import Home from './pages/Home';
import Dashboard from './pages/Dashboard';

createInertiaApp({
  resolve: (name) => {
    const pages = {
      Home,
      Dashboard,
    };
    return pages[name];
  },
  setup({ el, App, props }) {
    ReactDOM.createRoot(el).render(<App {...props} />);
  },
});

