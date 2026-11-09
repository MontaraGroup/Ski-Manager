import type { ReactNode } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';

/**
 * The one panel the map uses for building, a built run's details and the admin inspector.
 * A column on the right from md up; a bottom sheet on phones, so the map stays visible above it.
 * `footer` stays put at the bottom (the Build button), whatever the body scrolls to.
 */
export function MapPanel({
  title,
  subtitle,
  icon,
  onClose,
  onBack,
  footer,
  children,
  labelId = 'map-panel-title',
}: {
  title: ReactNode;
  subtitle?: ReactNode;
  icon?: string;
  onClose: () => void;
  onBack?: () => void;
  footer?: ReactNode;
  children: ReactNode;
  labelId?: string;
}) {
  return (
    <aside
      aria-labelledby={labelId}
      className="absolute inset-x-0 bottom-0 z-[950] flex max-h-[50%] flex-col overflow-hidden rounded-t-2xl border border-b-0 border-border bg-surface text-foreground shadow-2xl md:inset-x-auto md:top-3 md:right-3 md:bottom-3 md:max-h-none md:w-96 md:rounded-xl md:border-b"
    >
      {/* Grab handle: a visual cue on phones that this is a sheet */}
      <div className="flex justify-center pt-2 md:hidden" aria-hidden="true">
        <span className="h-1 w-10 rounded-full bg-border" />
      </div>
      <header className="flex items-center gap-2 border-b border-border px-3 py-2 md:p-3.5">
        {onBack ? (
          <Button
            size="sm"
            variant="ghost"
            isIconOnly
            onPress={onBack}
            className="size-11 shrink-0 text-muted hover:text-foreground md:size-9"
            aria-label="Back to the list"
          >
            <Icon name="fa-arrow-left" />
          </Button>
        ) : icon ? (
          <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/15 text-accent-soft-foreground">
            <Icon name={icon} />
          </span>
        ) : null}
        <div className="min-w-0 flex-1">
          <h2 id={labelId} className="truncate text-base font-semibold text-foreground">
            {title}
          </h2>
          {subtitle && <div className="truncate text-sm text-muted">{subtitle}</div>}
        </div>
        <Button
          size="sm"
          variant="ghost"
          isIconOnly
          onPress={onClose}
          className="size-11 shrink-0 text-muted hover:text-foreground md:size-9"
          aria-label="Close panel"
        >
          <Icon name="fa-xmark" />
        </Button>
      </header>
      <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 md:p-3.5">{children}</div>
      {footer && <div className="border-t border-border bg-surface p-3 md:p-3.5">{footer}</div>}
    </aside>
  );
}
