import React, { useState } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface Email2FAShowPageProps {
  actionUrl: string;
  csrfToken: string;
  csrfField: string;
  email: string;
  error?: string | null;
  errors?: string[] | Record<string, string> | null;
}

export default function Email2FAShowPage({
  actionUrl,
  csrfToken,
  csrfField,
  email: initialEmail = '',
  error,
  errors,
}: Email2FAShowPageProps) {
  const [email, setEmail] = useState(initialEmail);

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
        {/* Shield Icon & Heading */}
        <div className="text-center space-y-2">
          <div className="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto text-2xl">
            <Icon name="fa-solid fa-shield-halved" />
          </div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground">Two-factor authentication</h1>
          <p className="text-sm text-muted">
            Confirm your registered email address to receive a one-time verification code.
          </p>
        </div>

        {/* Error alerts */}
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

        {/* Send Code Form */}
        <form action={actionUrl} method="post" className="space-y-4">
          <input type="hidden" name={csrfField} value={csrfToken} />

          <div className="space-y-1.5">
            <label htmlFor="2fa-email" className="block text-xs font-semibold text-muted">
              Confirm email address
            </label>
            <input
              id="2fa-email"
              type="email"
              name="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="manager@example.com"
              required
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
            Send verification code
          </Button>
        </form>

        {/* Back Link */}
        <div className="pt-2 border-t border-border/60 text-center">
          <a
            href="/login"
            className="inline-flex items-center gap-1.5 text-xs font-medium text-link hover:underline pointer-coarse:min-h-11"
          >
            <Icon name="fa-solid fa-chevron-left" className="text-[10px]" />
            Return to login
          </a>
        </div>
      </div>
    </div>
  );
}
