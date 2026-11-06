import React, { useState, useRef, useEffect } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface Email2FAVerifyPageProps {
  actionUrl: string;
  resendUrl: string;
  csrfToken: string;
  csrfField: string;
  error?: string | null;
  errors?: string[] | Record<string, string> | null;
}

export default function Email2FAVerifyPage({
  actionUrl,
  resendUrl,
  csrfToken,
  csrfField,
  error,
  errors,
}: Email2FAVerifyPageProps) {
  const [token, setToken] = useState('');
  const formRef = useRef<HTMLFormElement>(null);

  // Auto-submit when 6 digits entered
  useEffect(() => {
    const cleanToken = token.replace(/\D/g, '');
    if (cleanToken.length === 6 && formRef.current) {
      formRef.current.submit();
    }
  }, [token]);

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
        {/* Keyhole Icon & Heading */}
        <div className="text-center space-y-2">
          <div className="w-14 h-14 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center mx-auto text-2xl">
            <Icon name="fa-solid fa-key" />
          </div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground">Enter verification code</h1>
          <p className="text-sm text-muted">
            We sent a 6-digit code to your email. Enter it below to unlock your dashboard.
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

        {/* OTP Input Form */}
        <form ref={formRef} action={actionUrl} method="post" className="space-y-5">
          <input type="hidden" name={csrfField} value={csrfToken} />

          <div className="space-y-2 text-center">
            <label htmlFor="otp-token" className="block text-xs font-semibold text-muted">
              6-digit login token
            </label>
            <input
              id="otp-token"
              type="text"
              name="token"
              value={token}
              onChange={(e) => setToken(e.target.value.replace(/\D/g, '').slice(0, 6))}
              placeholder="000000"
              maxLength={6}
              pattern="\d*"
              inputMode="numeric"
              autoComplete="one-time-code"
              required
              autoFocus
              className="w-full max-w-xs mx-auto text-center tracking-[0.4em] font-mono text-2xl font-bold rounded-xl border border-input-border bg-input-bg px-4 py-3 text-foreground placeholder:text-muted/30 focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-colors"
            />
          </div>

          <Button
            type="submit"
            variant="primary"
            className="w-full font-semibold pointer-coarse:min-h-11 shadow-sm"
            isDisabled={token.length < 6}
          >
            <Icon name="fa-solid fa-unlock-keyhole" className="mr-1.5" />
            Confirm token
          </Button>
        </form>

        {/* Resend & Return Links */}
        <div className="pt-2 border-t border-border/60 flex items-center justify-between text-xs">
          <a
            href={resendUrl}
            className="text-link hover:underline inline-flex items-center gap-1 font-medium pointer-coarse:min-h-11"
          >
            <Icon name="fa-solid fa-arrow-rotate-right" className="text-[10px]" />
            Resend code
          </a>
          <a
            href="/login"
            className="text-muted hover:text-foreground inline-flex items-center gap-1 pointer-coarse:min-h-11"
          >
            Cancel
          </a>
        </div>
      </div>
    </div>
  );
}
