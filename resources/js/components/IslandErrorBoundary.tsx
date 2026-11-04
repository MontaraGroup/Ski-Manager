import { Component, type ReactNode } from 'react';

declare global {
  interface Window {
    Sentry?: { captureException: (error: unknown, context?: object) => void };
  }
}

type Props = { name: string; children: ReactNode };
type State = { failed: boolean };

/**
 * Keeps a crashing island from blanking the rest of the PHP page. The error goes to the
 * browser Sentry SDK the layout already loads (no second SDK in the bundle).
 */
export class IslandErrorBoundary extends Component<Props, State> {
  state: State = { failed: false };

  static getDerivedStateFromError(): State {
    return { failed: true };
  }

  componentDidCatch(error: unknown) {
    window.Sentry?.captureException(error, { tags: { island: this.props.name } });
  }

  render() {
    if (!this.state.failed) return this.props.children;
    return (
      <p role="alert" className="text-sm text-danger">
        This part of the page could not load. Reload the page to try again.
      </p>
    );
  }
}
