import type { ReactNode } from 'react';

/** A titled block separated by a rule instead of a box, so admin pages read as one document */
export function Section({ title, actions, className = '', children }: { title?: ReactNode; actions?: ReactNode; className?: string; children: ReactNode }) {
  return (
    <section className={`border-t border-border pt-3 ${className}`}>
      {(title || actions) && (
        <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
          {title ? <h2 className="font-semibold">{title}</h2> : <div />}
          {actions}
        </div>
      )}
      {children}
    </section>
  );
}
