import { useRef, useState, type ReactNode } from 'react';
import { AlertDialog, Button } from '@heroui/react';
import { Icon } from './Icon';

/** Page frame for the React game pages: width, title row with a way back, optional actions */
export function Page({ title, icon, iconTone = 'text-accent', subtitle, back = '/dashboard', actions, width = 'max-w-5xl', children }: {
  title: string;
  icon: string;
  iconTone?: string;
  subtitle?: ReactNode;
  back?: string | null;
  actions?: ReactNode;
  width?: string;
  children: ReactNode;
}) {
  return (
    <div className={`${width} mx-auto flex flex-col gap-4 p-4 text-foreground lg:p-8`}>
      <header className="flex flex-wrap items-start gap-3">
        {back && (
          <a href={back} aria-label="Back to the dashboard" className="flex size-11 shrink-0 items-center justify-center rounded-full hover:bg-default">
            <Icon name="fa-chevron-left" />
          </a>
        )}
        <div className="min-w-0 flex-1">
          <h1 className="flex items-center gap-2 text-2xl font-semibold">
            <Icon name={icon} className={iconTone} />
            {title}
          </h1>
          {subtitle && <p className="text-sm text-muted">{subtitle}</p>}
        </div>
        {actions}
      </header>
      {children}
    </div>
  );
}

/** A plain surface for one block of content */
export function Panel({
  title,
  actions,
  children,
  className = '',
}: {
  title?: ReactNode;
  actions?: ReactNode;
  children: ReactNode;
  className?: string;
}) {
  return (
    <section className={`rounded-xl border border-border bg-surface p-4 ${className}`}>
      {(title || actions) && (
        <div className="mb-3 flex items-center justify-between gap-2">
          {title ? <h2 className="font-semibold">{title}</h2> : <div />}
          {actions}
        </div>
      )}
      {children}
    </section>
  );
}

/** A big figure with its label */
export function Figure({
  label,
  value,
  tone = '',
  note,
}: {
  label: string;
  value: ReactNode;
  tone?: string;
  note?: ReactNode;
}) {
  return (
    <div className="rounded-xl border border-border bg-surface p-4">
      <div className="text-sm text-muted">{label}</div>
      <div className={`text-2xl font-semibold tabular-nums ${tone}`}>{value}</div>
      {note && <div className="text-sm text-muted">{note}</div>}
    </div>
  );
}

export function csrf(): { field: string; token: string } {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  return { field: meta?.dataset.field || 'csrf_test_name', token: meta?.content || '' };
}

export function CsrfInput() {
  const { field, token } = csrf();
  return <input type="hidden" name={field} value={token} />;
}

/**
 * A normal form post (the server redirects back and its message shows as a toast). With
 * `confirm`, a dialog asks first; destructive actions say what they remove.
 */
export function PostButton({
  action,
  children,
  confirm,
  data,
  disabled,
  variant = 'secondary',
  size = 'md',
  className,
}: {
  action: string;
  children: ReactNode;
  confirm?: { title: string; body: string; confirmLabel: string; cancelLabel?: string };
  data?: Record<string, string | number | boolean>;
  disabled?: boolean;
  variant?: 'primary' | 'secondary' | 'tertiary' | 'ghost' | 'danger';
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}) {
  const form = useRef<HTMLFormElement>(null);
  const [open, setOpen] = useState(false);
  const { field, token } = csrf();
  return (
    <form ref={form} action={action} method="post" className={className}>
      <input type="hidden" name={field} value={token} />
      {data &&
        Object.entries(data).map(([k, v]) => (
          <input key={k} type="hidden" name={k} value={String(v)} />
        ))}
      {confirm ? (
        <>
          <Button variant={variant} size={size} isDisabled={disabled} onPress={() => setOpen(true)}>
            {children}
          </Button>
          {/* HeroUI's alert dialog ignores Escape by default; Escape means Cancel here (DESIGN.md: dialogs close on Escape) */}
          <AlertDialog.Backdrop isOpen={open} onOpenChange={setOpen} isKeyboardDismissDisabled={false}>
            <AlertDialog.Container>
              <AlertDialog.Dialog>
                <AlertDialog.Header>
                  <AlertDialog.Heading>{confirm.title}</AlertDialog.Heading>
                </AlertDialog.Header>
                <AlertDialog.Body>{confirm.body}</AlertDialog.Body>
                <AlertDialog.Footer>
                  <Button variant="tertiary" onPress={() => setOpen(false)}>
                    {confirm.cancelLabel ?? 'Cancel'}
                  </Button>
                  <Button variant="danger" onPress={() => form.current?.submit()}>
                    {confirm.confirmLabel}
                  </Button>
                </AlertDialog.Footer>
              </AlertDialog.Dialog>
            </AlertDialog.Container>
          </AlertDialog.Backdrop>
        </>
      ) : (
        <Button type="submit" variant={variant} size={size} isDisabled={disabled}>
          {children}
        </Button>
      )}
    </form>
  );
}

/** Empty state: why it's empty and what fills it */
export function Empty({ icon, title, children }: { icon: string; title: string; children?: ReactNode }) {
  return (
    <div className="flex flex-col items-center gap-2 py-10 text-center">
      <Icon name={icon} className="text-3xl text-muted" />
      <p className="font-semibold">{title}</p>
      {children && <div className="max-w-sm text-sm text-muted">{children}</div>}
    </div>
  );
}
