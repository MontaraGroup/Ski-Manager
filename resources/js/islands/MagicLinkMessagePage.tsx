import React from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface MagicLinkMessagePageProps {
  loginUrl: string;
  lifetimeMinutes?: number;
  message?: string | null;
}

export default function MagicLinkMessagePage({
  loginUrl,
  lifetimeMinutes = 60,
  message,
}: MagicLinkMessagePageProps) {
  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8">
      <div className="w-full max-w-md rounded-2xl border border-border bg-card shadow-2xl p-6 sm:p-8 space-y-6 text-center">
        {/* Success Icon */}
        <div className="w-16 h-16 rounded-full bg-success/10 text-success flex items-center justify-center mx-auto text-2xl">
          <Icon name="fa-solid fa-envelope-circle-check" />
        </div>

        {/* Title & Copy */}
        <div className="space-y-2">
          <h1 className="text-2xl font-bold tracking-tight text-foreground">Check your inbox</h1>
          <p className="text-sm text-muted">
            We sent a passwordless sign-in link to your email address.
          </p>
        </div>

        {message && (
          <div className="rounded-xl border border-success/30 bg-success/10 p-3 text-xs text-success text-left flex items-start gap-2.5">
            <Icon name="fa-solid fa-circle-check" className="mt-0.5 shrink-0 text-sm" />
            <div className="flex-1">{message}</div>
          </div>
        )}

        {/* Details Card */}
        <div className="rounded-xl border border-border/80 bg-surface-secondary/40 p-4 text-xs text-muted text-left space-y-2">
          <div className="flex items-center gap-2 font-medium text-foreground">
            <Icon name="fa-solid fa-clock" className="text-primary text-sm" />
            <span>Link expires in {lifetimeMinutes} minutes</span>
          </div>
          <p>
            Click the button or link inside the email to sign in directly without a password. Check your spam or junk folder if the message does not appear in a few moments.
          </p>
        </div>

        {/* Action Buttons */}
        <div className="space-y-2 pt-2">
          <a href={loginUrl} className="block no-underline">
            <Button
              variant="primary"
              className="w-full font-semibold pointer-coarse:min-h-11 shadow-sm"
            >
              Return to login
            </Button>
          </a>
          <a href="/" className="block no-underline">
            <Button
              variant="outline"
              className="w-full font-medium pointer-coarse:min-h-11"
            >
              Home page
            </Button>
          </a>
        </div>
      </div>
    </div>
  );
}
