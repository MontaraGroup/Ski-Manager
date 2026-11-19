import { useRef, useState, type ReactNode } from 'react';
import { AlertDialog, Button } from '@heroui/react';
import { CsrfInput } from '@/components/Page';

/**
 * A form post that asks first. Submitting (button or Enter in a field) only opens the dialog;
 * the form is sent from the dialog's confirm button, so Cancel or Escape sends nothing.
 * `describe` reads the current field values, so the dialog states the exact effect.
 */
export function ConfirmForm({
  action,
  submitLabel,
  title,
  describe,
  confirmLabel,
  danger = false,
  disabled = false,
  className,
  children,
}: {
  action: string;
  submitLabel: ReactNode;
  title: string;
  describe: (data: FormData) => ReactNode;
  confirmLabel: string;
  danger?: boolean;
  disabled?: boolean;
  className?: string;
  children?: ReactNode;
}) {
  const form = useRef<HTMLFormElement>(null);
  const [body, setBody] = useState<ReactNode>(null);
  const open = body !== null;

  return (
    <form
      ref={form}
      action={action}
      method="post"
      className={className}
      onSubmit={(e) => {
        e.preventDefault();
        setBody(describe(new FormData(e.currentTarget)));
      }}
    >
      <CsrfInput />
      {children}
      <Button type="submit" variant={danger ? 'danger' : 'secondary'} isDisabled={disabled} className="min-h-11 sm:min-h-9">
        {submitLabel}
      </Button>
      <AlertDialog.Backdrop isOpen={open} onOpenChange={(o) => !o && setBody(null)} isKeyboardDismissDisabled={false}>
        <AlertDialog.Container>
          <AlertDialog.Dialog>
            <AlertDialog.Header>
              <AlertDialog.Heading>{title}</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body>{body}</AlertDialog.Body>
            <AlertDialog.Footer>
              <Button variant="tertiary" onPress={() => setBody(null)}>
                Cancel
              </Button>
              {/* form.submit() skips onSubmit, so this is the only path that sends the form */}
              <Button variant={danger ? 'danger' : 'primary'} onPress={() => form.current?.submit()}>
                {confirmLabel}
              </Button>
            </AlertDialog.Footer>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </form>
  );
}

/** Label + native input, sized for fingers on phones */
export function Field({ label, hint, ...input }: { label: string; hint?: string } & React.InputHTMLAttributes<HTMLInputElement>) {
  return (
    <label className="flex flex-col gap-1 text-sm">
      <span className="font-medium">{label}</span>
      <input
        {...input}
        className="min-h-11 w-full rounded-lg border border-border bg-field px-3 text-base text-field-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus sm:min-h-9 sm:text-sm"
      />
      {hint && <span className="text-muted">{hint}</span>}
    </label>
  );
}
