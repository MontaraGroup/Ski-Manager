import React from 'react';
import { createRoot } from 'react-dom/client';
import '@heroui/styles/css';

// Import components
import HeroDemo from './components/HeroDemo';

const COMPONENTS = {
  HeroDemo,
};

/**
 * Auto-mount all elements with [data-react-component]
 * Usage in PHP view:
 * <div data-react-component="HeroDemo" data-props='{"resortName": "Aspen Snowmass"}'></div>
 */
function mountComponents() {
  const elements = document.querySelectorAll('[data-react-component]');
  elements.forEach((el) => {
    // Avoid double mounting
    if (el.dataset.mounted) return;

    const name = el.getAttribute('data-react-component');
    const Component = COMPONENTS[name];

    if (!Component) {
      console.warn(`[HeroUI] Component "${name}" not found in registered components.`);
      return;
    }

    let props = {};
    const rawProps = el.getAttribute('data-props');
    if (rawProps) {
      try {
        props = JSON.parse(rawProps);
      } catch (err) {
        console.error(`[HeroUI] Failed to parse props for "${name}":`, err);
      }
    }

    const root = createRoot(el);
    root.render(<Component {...props} />);
    el.dataset.mounted = 'true';
  });

  // Also check for standalone #react-root
  const rootEl = document.getElementById('react-root');
  if (rootEl && !rootEl.dataset.mounted) {
    const root = createRoot(rootEl);
    root.render(<HeroDemo />);
    rootEl.dataset.mounted = 'true';
  }
}

// Global helper for dynamic mounting from vanilla JS / Alpine
window.HeroUI = {
  mount: mountComponents,
  render(componentName, targetEl, props = {}) {
    const Component = COMPONENTS[componentName];
    if (!Component) {
      console.error(`Component ${componentName} not found.`);
      return;
    }
    const root = createRoot(targetEl);
    root.render(<Component {...props} />);
  },
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountComponents);
} else {
  mountComponents();
}
