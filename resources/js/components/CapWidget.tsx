import type { DetailedHTMLProps, HTMLAttributes } from 'react';

declare module 'react' {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace JSX {
    interface IntrinsicElements {
      'cap-widget': DetailedHTMLProps<HTMLAttributes<HTMLElement>, HTMLElement> & { 'data-cap-api-endpoint'?: string };
    }
  }
}

/**
 * Cap's "I'm a human" check (App\Libraries\Cap). The widget script is loaded by the page's PHP view;
 * inside a form, the widget adds a hidden cap-token field that App\Filters\CapVerify checks.
 * Renders nothing when Cap isn't configured.
 */
export function CapWidget({ endpoint }: { endpoint?: string }) {
  if (!endpoint) return null;
  return <cap-widget data-cap-api-endpoint={endpoint} className="block" />;
}
