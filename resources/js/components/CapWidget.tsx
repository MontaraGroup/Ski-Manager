import { useEffect, useRef, type DetailedHTMLProps, type HTMLAttributes } from 'react';

declare module 'react' {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace JSX {
    interface IntrinsicElements {
      'cap-widget': DetailedHTMLProps<HTMLAttributes<HTMLElement>, HTMLElement> & { 'data-cap-api-endpoint'?: string };
    }
  }
}

declare global {
  interface Window {
    posthog?: { captureException?: (error: unknown, properties?: Record<string, unknown>) => void };
  }
}

type CapErrorDetail = { isCap?: boolean; code?: string | number; message?: string };

// Cap codes already reported on this page, so a widget that keeps retrying sends one report, not one a second
const reported = new Set<string>();

/**
 * Cap's "I'm a human" check (App\Libraries\Cap). The widget script is loaded by the page's PHP view;
 * inside a form, the widget adds a hidden cap-token field that App\Filters\CapVerify checks.
 * Renders nothing when Cap isn't configured.
 *
 * When it fails, the widget fires a CustomEvent named "error" that bubbles up to window. PostHog's and
 * Sentry's global error handlers then record the bare event ("CustomEvent captured as exception with
 * keys: isTrusted"), with no message or stack. Stop it at the widget (the widget's own listener on the
 * element still runs and shows "Try again") and report it once as a real Error with Cap's code and message.
 */
export function CapWidget({ endpoint }: { endpoint?: string }) {
  const ref = useRef<HTMLElement>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const onError = (event: Event) => {
      event.stopPropagation();
      const detail = ((event as CustomEvent<CapErrorDetail>).detail ?? {}) as CapErrorDetail;
      const code = String(detail.code ?? 'unknown');
      if (reported.has(code)) return;
      reported.add(code);
      const error = new Error(`Cap widget error [${code}]: ${detail.message || 'no message'}`);
      error.name = 'CapWidgetError';
      window.posthog?.captureException?.(error, { cap_code: code });
      window.Sentry?.captureException(error, { tags: { cap_code: code } });
    };
    el.addEventListener('error', onError);
    return () => el.removeEventListener('error', onError);
  }, [endpoint]);

  if (!endpoint) return null;
  return <cap-widget ref={ref} data-cap-api-endpoint={endpoint} className="block" />;
}
