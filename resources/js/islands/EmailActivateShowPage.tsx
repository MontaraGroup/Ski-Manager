import React, { useState, useEffect } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface EmailActivateShowPageProps {
  actionUrl: string;
  resendUrl: string;
  loginUrl: string;
  contactUrl: string;
  csrfTokenName: string;
  csrfTokenHash: string;
  userExists: boolean;
  maskedEmail?: string | null;
  mailNotice?: 'sent' | 'resent' | 'failed' | null;
  retryIn?: number;
  oldEmail?: string;
}

export default function EmailActivateShowPage({
  actionUrl,
  resendUrl,
  loginUrl,
  contactUrl,
  csrfTokenName,
  csrfTokenHash,
  userExists,
  maskedEmail,
  mailNotice,
  retryIn = 0,
  oldEmail = '',
}: EmailActivateShowPageProps) {
  const [secondsLeft, setSecondsLeft] = useState(retryIn);
  const [email, setEmail] = useState(oldEmail);
  const [code, setCode] = useState('');

  useEffect(() => {
    if (secondsLeft <= 0) return;
    const interval = setInterval(() => {
      setSecondsLeft((prev) => (prev <= 1 ? 0 : prev - 1));
    }, 1000);
    return () => clearInterval(interval);
  }, [secondsLeft]);

  return (
    <div className="mx-auto w-full max-w-md p-4 sm:p-8">
      <div className="rounded-2xl border border-border bg-card p-6 shadow-xl space-y-6">
        <div className="text-center space-y-2">
          <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-2xl text-primary">
            <Icon name="fa-solid fa-envelope-open-text" />
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">Confirm your email</h1>
          {!userExists ? (
            <p className="text-sm text-muted">Enter the email you signed up with and the 6-digit code we sent you.</p>
          ) : mailNotice === 'failed' ? (
            <p className="text-sm text-muted">
              We need to confirm <b className="text-foreground">{maskedEmail}</b> before you can play.
            </p>
          ) : (
            <p className="text-sm text-muted">
              We {mailNotice === 'resent' ? 'just sent a new' : 'sent a'} 6-digit code to{' '}
              <b className="text-foreground">{maskedEmail}</b>. It is valid for 30 minutes. Check your spam folder if it
              is not in your inbox.
            </p>
          )}
        </div>

        {mailNotice === 'failed' && (
          <div
            role="alert"
            className="flex items-start gap-2.5 rounded-xl border border-warning/30 bg-warning/10 p-3 text-xs text-warning"
          >
            <Icon name="fa-solid fa-triangle-exclamation" className="mt-0.5 shrink-0 text-sm" />
            <span>
              We could not send the email just now. Try again in a minute. If it keeps failing,{' '}
              <a href={contactUrl} className="underline hover:text-foreground">
                let us know
              </a>
              , or sign up with Google or Discord instead.
            </span>
          </div>
        )}

        <form action={actionUrl} method="post" className="space-y-4">
          <input type="hidden" name={csrfTokenName} value={csrfTokenHash} />

          {!userExists && (
            <div>
              <label className="block text-xs font-semibold text-muted mb-1.5" htmlFor="activate-email">
                Email
              </label>
              <input
                id="activate-email"
                type="email"
                name="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                autoComplete="email"
                required
                className="w-full rounded-xl border border-border bg-default/40 px-3 py-2 text-base sm:text-sm text-foreground placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 min-h-11 sm:min-h-9"
              />
            </div>
          )}

          <div>
            <label className="block text-xs font-semibold text-muted mb-1.5" htmlFor="activate-code">
              6-digit code
            </label>
            <input
              id="activate-code"
              type="text"
              name="code"
              value={code}
              onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              autoComplete="one-time-code"
              inputMode="numeric"
              maxLength={6}
              pattern="[0-9]{6}"
              required
              autoFocus={userExists}
              className="w-full rounded-xl border border-border bg-default/40 h-14 text-center font-mono text-2xl tracking-[0.5em] text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            />
          </div>

          <Button
            type="submit"
            className="inline-flex w-full min-h-11 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
          >
            <Icon name="fa-solid fa-circle-check" />
            <span>Confirm and start playing</span>
          </Button>
        </form>

        <div className="text-center text-xs text-muted">
          {userExists ? (
            secondsLeft > 0 ? (
              <span>
                You can ask for a new code in <span className="font-semibold text-foreground">{secondsLeft}</span>s
              </span>
            ) : (
              <span>
                Did not get it?{' '}
                <a href={resendUrl} className="font-medium text-accent hover:underline">
                  Send a new code
                </a>
              </span>
            )
          ) : (
            <span>
              Code expired?{' '}
              <a href={loginUrl} className="font-medium text-accent hover:underline">
                Sign in
              </a>{' '}
              and we will email a new one.
            </span>
          )}
        </div>
      </div>
    </div>
  );
}
