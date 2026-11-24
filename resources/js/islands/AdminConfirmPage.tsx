import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

type Props = { email: string; csrfField: string; csrfToken: string; error: string; hasPassword: boolean };

/** /admin/confirm: an admin enters their password again before the admin panel opens */
export default function AdminConfirmPage({ email, csrfField, csrfToken, error, hasPassword }: Props) {
  return (
    <Page title="Confirm it's you" icon="fa-user-shield" back="/dashboard" width="max-w-md" subtitle="The admin panel asks for your password again after 2 hours without activity.">
      <Panel>
        {hasPassword ? (
          <form action="/admin/confirm" method="post" className="flex flex-col gap-4">
            <input type="hidden" name={csrfField} value={csrfToken} />
            <input type="hidden" name="email" value={email} autoComplete="username" />
            <div className="flex flex-col gap-1.5">
              <label htmlFor="admin-password" className="text-sm font-medium text-foreground">
                Password for {email}
              </label>
              <input
                id="admin-password"
                type="password"
                name="password"
                required
                autoFocus
                autoComplete="current-password"
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? 'admin-confirm-error' : undefined}
                className="min-h-11 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              />
              {error && (
                <p id="admin-confirm-error" role="alert" className="flex items-center gap-1.5 text-sm text-danger">
                  <Icon name="fa-circle-exclamation" />
                  {error}
                </p>
              )}
            </div>
            <Button type="submit" variant="primary" className="min-h-11">
              Open the admin panel
            </Button>
          </form>
        ) : (
          <p className="text-sm text-foreground">
            Your account has no password yet (you sign in with Google or Discord). Set one on your{' '}
            <a href="/account" className="font-medium text-accent-soft-foreground underline">
              Account
            </a>{' '}
            page with an emailed code, then come back here.
          </p>
        )}
      </Panel>
    </Page>
  );
}
