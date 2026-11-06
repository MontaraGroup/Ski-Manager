import React, { useState } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface MagicLinkFormPageProps {
  actionUrl: string;
  loginUrl: string;
  csrfToken: string;
  csrfField: string;
  oldEmail?: string;
  error?: string | null;
  errors?: string[] | Record<string, string> | null;
  message?: string | null;
}

export default function MagicLinkFormPage({
  actionUrl,
  loginUrl,
  csrfToken,
  csrfField,
  oldEmail = '',
  error,
  errors,
  message,
}: MagicLinkFormPageProps) {
  const [email, setEmail] = useState(oldEmail);

  // Normalize error messages
  const errorList: string[] = [];
  if (error) errorList.push(error);
  if (errors) {
    if (Array.isArray(errors)) {
      errorList.push(...errors);
    } else if (typeof errors === 'object') {
      errorList.push(...Object.values(errors));
    }
  }

  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8">
      <div className="w-full max-w-md rounded-2xl border border-border bg-card shadow-2xl p-6 sm:p-8 space-y-6">
        {/* Header Icon & Title */}
        <div className="text-center space-y-2">
          <div className="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto text-xl">
            <Icon name="fa-solid fa-wand-magic-sparkles" />
          </div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground">Log in with magic link</h1>
          <p className="text-sm text-muted">
            Enter your resort email and we will send you a passwordless sign-in link.
          </p>
        </div>

        {/* Status & Error Alerts */}
        {message && (
          <div className="rounded-xl border border-success/30 bg-success/10 p-3 text-xs text-success flex items-start gap-2.5">
            <Icon name="fa-solid fa-circle-check" className="mt-0.5 shrink-0 text-sm" />
            <div className="flex-1">{message}</div>
          </div>
        )}

        {errorList.length > 0 && (
          <div className="rounded-xl border border-danger/30 bg-danger/10 p-3 text-xs text-danger space-y-1">
            {errorList.map((err, idx) => (
              <div key={idx} className="flex items-start gap-2">
                <Icon name="fa-solid fa-circle-exclamation" className="mt-0.5 shrink-0" />
                <span>{err}</span>
              </div>
            ))}
          </div>
        )}

        {/* Request Form */}
        <form action={actionUrl} method="post" className="space-y-4">
          <input type="hidden" name={csrfField} value={csrfToken} />

          <div className="space-y-1.5">
            <label htmlFor="magic-email" className="block text-xs font-semibold text-muted">
              Email address
            </label>
            <input
              id="magic-email"
              type="email"
              name="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="manager@example.com"
              required
              autoFocus
              autoComplete="email"
              className="w-full rounded-xl border border-input-border bg-input-bg px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted/60 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors"
            />
          </div>

          <Button
            type="submit"
            variant="primary"
            className="w-full font-semibold pointer-coarse:min-h-11 shadow-sm mt-2"
          >
            <Icon name="fa-solid fa-paper-plane" className="mr-1.5" />
            Send magic link
          </Button>
        </form>

        {/* Back Link */}
        <div className="pt-2 border-t border-border/60 text-center">
          <a
            href={loginUrl}
            className="inline-flex items-center gap-1.5 text-xs font-medium text-link hover:underline pointer-coarse:min-h-11"
          >
            <Icon name="fa-solid fa-chevron-left" className="text-[10px]" />
            Back to login
          </a>
        </div>
      </div>
    </div>
  );
}
