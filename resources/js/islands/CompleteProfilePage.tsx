import React, { useState } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface DifficultyOption {
  key: string;
  name: string;
  badge: string;
  badgeColor: string;
  startingCash: number;
  desc: string;
}

interface ResortOption {
  key: string;
  name: string;
  location: string;
  icon: string;
  enabled: boolean;
}

interface CompleteProfilePageProps {
  actionUrl: string;
  csrfToken: string;
  csrfField: string;
  difficulties: DifficultyOption[];
  resorts: ResortOption[];
  error?: string | null;
  errors?: string[] | Record<string, string> | null;
}

export default function CompleteProfilePage({
  actionUrl,
  csrfToken,
  csrfField,
  difficulties,
  resorts,
  error,
  errors,
}: CompleteProfilePageProps) {
  const [selectedDifficulty, setSelectedDifficulty] = useState('standard');
  const [selectedResort, setSelectedResort] = useState('ParkCity');
  const [termsAccepted, setTermsAccepted] = useState(false);

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
      <div className="w-full max-w-5xl rounded-2xl border border-border bg-card shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2">
        {/* Left Side: Visual Alpine Panel */}
        <div className="relative isolate overflow-hidden bg-[#141b24] text-white p-6 sm:p-8 lg:p-10 flex flex-col justify-between min-h-[16rem] lg:min-h-[38rem]">
          <div
            className="absolute inset-0 -z-20 bg-cover bg-center opacity-75 transform scale-105 transition-transform duration-700"
            style={{
              backgroundImage: 'url(/img/ParkCity_med.jpg), url(/img/ParkCity_low.jpg)',
            }}
          />
          <div className="absolute inset-0 -z-10 bg-gradient-to-t lg:bg-gradient-to-r from-[#0a0e14]/95 via-[#0a0e14]/75 to-[#0a0e14]/40" />

          {/* Brand Header */}
          <a href="/" className="inline-flex items-center gap-2.5 text-white no-underline w-fit">
            <svg viewBox="0 0 32 32" className="w-8 h-8 shrink-0" aria-hidden="true">
              <rect width="32" height="32" rx="6" fill="#1d232b" />
              <path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0" />
              <path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35" />
              <path d="M20 9 L19 25" stroke="#2f6fd6" strokeWidth="1.6" strokeLinecap="round" />
            </svg>
            <span className="font-bold text-xl tracking-tight">Ski Manager</span>
          </a>

          {/* Welcome Copy */}
          <div className="hidden lg:block space-y-4 my-auto">
            <h2 className="text-3xl font-extrabold tracking-tight leading-tight">
              Welcome to the <br />
              <span className="text-[#7fb4ff]">director&apos;s chair.</span>
            </h2>
            <ul className="space-y-3 text-sm text-white/90">
              <li className="flex items-center gap-3">
                <span className="w-3 h-3 rounded-full bg-[#23a55a] shrink-0" />
                <span>Configure your resort before your opening season</span>
              </li>
              <li className="flex items-center gap-3">
                <span className="w-3 h-3 bg-[#2f6fd6] shrink-0" />
                <span>Choose your starting capital and difficulty</span>
              </li>
              <li className="flex items-center gap-3">
                <span className="w-2.5 h-2.5 bg-white transform rotate-45 shrink-0 mx-0.5" />
                <span>Begin on Park City&apos;s real alpine trail map</span>
              </li>
            </ul>
          </div>

          {/* Footer note */}
          <div className="pt-4 border-t border-white/20 text-xs text-white/70">
            Resort profile configuration
          </div>
        </div>

        {/* Right Side: Setup Form */}
        <div className="p-6 sm:p-8 lg:p-10 flex flex-col justify-center overflow-y-auto max-h-[90vh]">
          <div className="w-full max-w-md mx-auto space-y-6">
            <div>
              <h1 className="text-2xl font-bold text-foreground">Complete your profile</h1>
              <p className="text-xs text-muted mt-1">Set up your starting parameters to open your mountain.</p>
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

            <form action={actionUrl} method="post" className="space-y-5">
              <input type="hidden" name={csrfField} value={csrfToken} />

              {/* Difficulty Selection */}
              <div className="space-y-2">
                <label className="block text-xs font-semibold text-muted">
                  Game difficulty
                </label>
                <div className="grid grid-cols-3 gap-2">
                  {difficulties.map((diff) => {
                    const isSelected = selectedDifficulty === diff.key;
                    return (
                      <label
                        key={diff.key}
                        className={`cursor-pointer rounded-xl border p-3 text-center transition-all ${
                          isSelected
                            ? 'border-primary bg-primary/10 text-foreground ring-1 ring-primary'
                            : 'border-border bg-surface-secondary/30 text-muted hover:border-border/80'
                        }`}
                      >
                        <input
                          type="radio"
                          name="difficulty"
                          value={diff.key}
                          checked={isSelected}
                          onChange={() => setSelectedDifficulty(diff.key)}
                          className="sr-only"
                        />
                        <div className="text-sm font-bold flex items-center justify-center gap-1.5 mb-1">
                          <span className={diff.badgeColor} aria-hidden="true">
                            {diff.badge}
                          </span>
                          <span>{diff.name}</span>
                        </div>
                        <div className="text-[11px] font-mono tabular-nums text-foreground/80 font-semibold">
                          {(diff.startingCash / 1000).toLocaleString()}k €
                        </div>
                      </label>
                    );
                  })}
                </div>
              </div>

              {/* Resort Selection */}
              <div className="space-y-2">
                <label className="block text-xs font-semibold text-muted">
                  Choose your resort
                </label>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  {resorts.map((resort) => {
                    const isSelected = selectedResort === resort.key;
                    return (
                      <label
                        key={resort.key}
                        className={`rounded-xl border p-2.5 text-center transition-all ${
                          resort.enabled ? 'cursor-pointer' : 'opacity-40 cursor-not-allowed bg-surface-secondary/20'
                        } ${
                          isSelected && resort.enabled
                            ? 'border-primary bg-primary/10 text-foreground ring-1 ring-primary'
                            : 'border-border bg-surface-secondary/30 text-muted'
                        }`}
                      >
                        <input
                          type="radio"
                          name="resort_map"
                          value={resort.key}
                          disabled={!resort.enabled}
                          checked={isSelected}
                          onChange={() => resort.enabled && setSelectedResort(resort.key)}
                          className="sr-only"
                        />
                        <div className="w-full h-9 rounded-lg bg-surface-secondary/80 flex items-center justify-center mb-1.5 text-primary text-base">
                          <Icon name={`fa-solid ${resort.icon}`} />
                        </div>
                        <div className="text-xs font-semibold text-foreground truncate">
                          {resort.name}
                        </div>
                        <div className="text-[10px] text-muted truncate">
                          {resort.location}
                        </div>
                        {!resort.enabled && (
                          <span className="inline-block mt-1 text-[9px] font-semibold bg-border/60 text-muted px-1.5 py-0.2 rounded">
                            Soon
                          </span>
                        )}
                      </label>
                    );
                  })}
                </div>
              </div>

              {/* Terms Checkbox */}
              <div className="pt-2">
                <label className="cursor-pointer flex items-start gap-2.5 text-xs text-muted">
                  <input
                    type="checkbox"
                    name="terms"
                    value="1"
                    checked={termsAccepted}
                    onChange={(e) => setTermsAccepted(e.target.checked)}
                    required
                    className="mt-0.5 rounded border-border text-primary focus:ring-primary h-4 w-4"
                  />
                  <span>
                    I agree to the{' '}
                    <a href="/terms" target="_blank" rel="noopener noreferrer" className="text-link hover:underline">
                      Terms of Service
                    </a>{' '}
                    and{' '}
                    <a href="/privacy" target="_blank" rel="noopener noreferrer" className="text-link hover:underline">
                      Privacy Policy
                    </a>.
                  </span>
                </label>
              </div>

              <Button
                type="submit"
                variant="primary"
                className="w-full font-semibold pointer-coarse:min-h-11 shadow-sm mt-2"
                isDisabled={!termsAccepted}
              >
                Start playing
              </Button>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
