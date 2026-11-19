import { useRef, useState, type ReactNode } from 'react';
import { AlertDialog, Button, Card, buttonVariants, chipVariants, cn } from '@heroui/react';
import { Icon } from './Icon';

/** Page frame for the React game pages: width, title row with a way back, optional actions */
/** Set by layouts/main.php when the player sees this page because it's in beta */
function isBetaPage(): boolean {
  return typeof document !== 'undefined' && document.body.dataset.betaPage === '1';
}

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
          <a href={back} aria-label={back === '/dashboard' ? 'Back to the dashboard' : 'Back'} className={buttonVariants({ variant: 'ghost', isIconOnly: true, className: 'size-11 shrink-0 rounded-full' })}>
            <Icon name="fa-chevron-left" />
          </a>
        )}
        <div className="min-w-0 flex-1">
          <h1 className="flex items-center gap-2 text-2xl font-semibold">
            <Icon name={icon} className={iconTone} />
            {title}
            {isBetaPage() && (
              <a
                href="/beta"
                aria-label="Beta feature, see beta features"
                className={chipVariants({ color: 'warning', variant: 'soft', className: 'min-h-8 px-2.5 text-sm font-semibold hover:underline' }).base()}
              >
                Beta
              </a>
            )}
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
  // Block layout, not Card's flex column, so existing panel contents keep their own spacing
  return (
    <Card render={(props) => <section {...props} />} className={cn('block border border-border', className)}>
      {(title || actions) && (
        <Card.Header className="mb-3 flex-row items-center justify-between gap-2">
          {title ? <Card.Title render={(props) => <h2 {...props} />} className="text-base font-semibold">{title}</Card.Title> : <div />}
          {actions}
        </Card.Header>
      )}
      {children}
    </Card>
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
    <Card className="gap-0 border border-border">
      <Card.Description render={(props) => <div {...props} />}>{label}</Card.Description>
      <div className={`text-2xl font-semibold tabular-nums ${tone}`}>{value}</div>
      {note && <Card.Description render={(props) => <div {...props} />}>{note}</Card.Description>}
    </Card>
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
