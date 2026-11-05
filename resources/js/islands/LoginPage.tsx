import React, { useState } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface LoginPageProps {
  actionUrl: string;
  csrfToken: string;
  csrfField: string;
  oldEmail?: string;
  allowRemember: boolean;
  allowMagicLink: boolean;
  allowDiscord: boolean;
  error?: string | null;
  errors?: string[] | null;
  message?: string | null;
  season: number;
  seasonDay?: number | null;
  conditionName?: string;
  temperature?: string;
  playerCount?: number;
}

export default function LoginPage({
  actionUrl,
  csrfToken,
  csrfField,
  oldEmail = '',
  allowRemember,
  allowMagicLink,
  allowDiscord,
  error,
  errors,
  message,
  season,
  seasonDay,
  conditionName,
  temperature,
  playerCount = 0,
}: LoginPageProps) {
  const [showPassword, setShowPassword] = useState(false);

  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8">
      <div className="w-full max-w-5xl rounded-2xl border border-border bg-card shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2">
        {/* Left Side: Alpine Trail Map Visual Panel */}
        <div className="relative isolate overflow-hidden bg-[#141b24] text-white p-6 sm:p-8 lg:p-10 flex flex-col justify-between min-h-[16rem] lg:min-h-[36rem]">
          {/* Background image & gradient overlay */}
          <div
            className="absolute inset-0 -z-20 bg-cover bg-center opacity-75 transform scale-105 transition-transform duration-700"
            style={{
              backgroundImage: 'url(/img/ParkCity_med.jpg), url(/img/ParkCity_low.jpg)',
            }}
          />
          <div className="absolute inset-0 -z-10 bg-gradient-to-t lg:bg-gradient-to-r from-[#0a0e14]/95 via-[#0a0e14]/75 to-[#0a0e14]/40" />

          {/* Top Brand */}
          <a href="/" className="inline-flex items-center gap-2.5 text-white no-underline w-fit">
            <svg viewBox="0 0 32 32" className="w-8 h-8 shrink-0" aria-hidden="true">
              <rect width="32" height="32" rx="6" fill="#1d232b" />
              <path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0" />
              <path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35" />
              <path d="M20 9 L19 25" stroke="#2f6fd6" strokeWidth="1.6" strokeLinecap="round" />
            </svg>
            <span className="font-bold text-xl tracking-tight">Ski Manager</span>
          </a>

          {/* Center Pitch (desktop) */}
          <div className="hidden lg:block space-y-4 my-auto">
            <h2 className="text-3xl font-extrabold tracking-tight leading-tight">
              Back to the <br />
              <span className="text-[#7fb4ff]">mountain.</span>
            </h2>
            <ul className="space-y-3 text-sm text-white/90">
              <li className="flex items-center gap-3">
                <span className="w-3 h-3 rounded-full bg-[#23a55a] shrink-0" />
                <span>Check today&apos;s snow and open your runs</span>
              </li>
              <li className="flex items-center gap-3">
                <span className="w-3 h-3 bg-[#2f6fd6] shrink-0" />
                <span>See how your resort performed overnight</span>
              </li>
              <li className="flex items-center gap-3">
                <span className="w-2.5 h-2.5 bg-white transform rotate-45 shrink-0 mx-0.5" />
                <span>Climb the leaderboard before the season ends</span>
              </li>
            </ul>
          </div>

          {/* Bottom Live Mountain Telemetry */}
          <div className="pt-4 border-t border-white/20 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-white/70">
            <span>
              Season <strong className="text-white font-semibold">{season}</strong>
              {seasonDay ? (
                <> · Day <strong className="text-white font-semibold">{seasonDay}</strong></>
              ) : null}
            </span>
            {conditionName && (
              <span>
                Park City: <strong className="text-white font-semibold">{conditionName}</strong>{temperature ? `, ${temperature}` : ''}
              </span>
            )}
            {playerCount >= 10 && (
              <span>
                <strong className="text-white font-semibold">{playerCount.toLocaleString()}</strong> managers
              </span>
            )}
            <span>Free in your browser</span>
          </div>
        </div>

        {/* Right Side: Authentication Form */}
        <div className="p-6 sm:p-8 lg:p-12 flex flex-col justify-center">
          <div className="w-full max-w-sm mx-auto space-y-6">
            <div>
              <h1 className="text-2xl font-bold text-foreground">Welcome back</h1>
              <p className="text-sm text-muted mt-1">Log in to manage your ski resort.</p>
            </div>

            {/* Notifications / Alerts */}
            {error && (
              <div className="p-3.5 rounded-lg bg-danger/10 border border-danger/30 text-xs text-danger font-medium flex items-center gap-2" role="alert">
                <Icon name="fa-circle-exclamation" className="w-4 h-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}
            {errors && errors.length > 0 && (
              <div className="p-3.5 rounded-lg bg-danger/10 border border-danger/30 text-xs text-danger space-y-1" role="alert">
                {errors.map((err, idx) => (
                  <p key={idx}>{err}</p>
                ))}
              </div>
            )}
            {message && (
              <div className="p-3.5 rounded-lg bg-success/10 border border-success/30 text-xs text-success font-medium flex items-center gap-2" role="status">
                <Icon name="fa-circle-check" className="w-4 h-4 shrink-0" />
                <span>{message}</span>
              </div>
            )}

            {/* Social Logins */}
            <div className="space-y-2">
              <div className={`grid ${allowDiscord ? 'grid-cols-2' : 'grid-cols-1'} gap-2.5`}>
                <a
                  href="/auth/google"
                  className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-border px-4 py-2 text-xs font-semibold hover:bg-default transition-colors text-foreground"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" className="w-4 h-4 shrink-0" aria-hidden="true">
                    <path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.9 33.1 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.2-2.7-.4-3.9z" />
                    <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.3 15.7 18.8 13 24 13c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
                    <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.2 26.7 36 24 36c-5.4 0-9.9-3.5-11.5-8.3l-6.5 5C9.5 39.6 16.2 44 24 44z" />
                    <path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.7 39.5 44 34 44 24c0-1.3-.2-2.7-.4-3.9z" />
                  </svg>
                  <span>Google</span>
                </a>
                {allowDiscord && (
                  <a
                    href="/auth/discord"
                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-border px-4 py-2 text-xs font-semibold hover:bg-default transition-colors text-foreground"
                  >
                    <Icon name="fa-discord" className="w-4 h-4 text-[#5865F2]" />
                    <span>Discord</span>
                  </a>
                )}
              </div>

              <div className="relative flex items-center justify-center py-2">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-border" />
                </div>
                <span className="relative bg-card px-2 text-[11px] uppercase tracking-wider text-muted font-medium">
                  or email
                </span>
              </div>
            </div>

            {/* Email + Password Form */}
            <form action={actionUrl} method="post" className="space-y-4">
              <input type="hidden" name={csrfField} value={csrfToken} />

              <div className="space-y-1.5">
                <label htmlFor="email" className="block text-xs font-medium text-foreground">
                  Email
                </label>
                <input
                  type="email"
                  name="email"
                  id="email"
                  defaultValue={oldEmail}
                  placeholder="you@example.com"
                  required
                  autoFocus
                  autoComplete="email"
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary min-h-11"
                />
              </div>

              <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <label htmlFor="password" className="block text-xs font-medium text-foreground">
                    Password
                  </label>
                  {allowMagicLink && (
                    <a href="/magic-link" className="text-xs text-link hover:underline min-h-11 sm:min-h-0 flex items-center">
                      Forgot password?
                    </a>
                  )}
                </div>
                <div className="relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    name="password"
                    id="password"
                    placeholder="Your password"
                    required
                    autoComplete="current-password"
                    className="w-full rounded-lg border border-border bg-surface px-3 py-2.5 pr-10 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary min-h-11"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    className="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-muted hover:text-foreground transition-colors"
                  >
                    <Icon name={showPassword ? 'fa-eye-slash' : 'fa-eye'} className="w-4 h-4" />
                  </button>
                </div>
              </div>

              {allowRemember && (
                <label className="flex items-center gap-2 cursor-pointer select-none text-xs text-muted min-h-11 sm:min-h-0">
                  <input
                    type="checkbox"
                    name="remember"
                    defaultChecked
                    className="rounded border-border text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                  />
                  <span>Keep me logged in</span>
                </label>
              )}

              <Button
                type="submit"
                variant="primary"
                className="w-full font-semibold min-h-11"
              >
                Log in
              </Button>
            </form>

            <div className="text-center text-xs text-muted pt-2 border-t border-border">
              <span>New to Ski Manager? </span>
              <a href="/register" className="text-link font-medium hover:underline">
                Create a free account
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
