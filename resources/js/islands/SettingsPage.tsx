import { useState } from 'react';
import { Button, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel, PostButton } from '@/components/Page';
import { SeasonChoiceFields } from '@/components/SeasonChoiceFields';
import { Select } from '@/components/Select';
import { ApiError } from '@/lib/api';
import { SEASON_OPTIONS, sendVote, type SeasonChoice, type SeasonVoteResults, type SeasonVoteState } from '@/lib/seasonVote';

export type UserMeta = {
  id: number;
  username: string;
  email: string;
  hasGoogle: boolean;
  isDemo: boolean;
};

export type ResortMeta = {
  name: string;
};

export type TutorialProgress = {
  step: number;
} | null;

export type SettingsPageProps = {
  user: UserMeta;
  resort: ResortMeta;
  codeFor: string | null;
  units: 'metric' | 'imperial';
  difficulty: 'easy' | 'standard' | 'hard';
  allowTours: boolean;
  tutorial: TutorialProgress;
  notifCount: number;
  activityCount: number;
  seasonVote?: SeasonVoteState & { closed: boolean; myComment: string; results: SeasonVoteResults | null };
};

export default function SettingsPage({
  user,
  resort,
  codeFor,
  units,
  difficulty,
  allowTours,
  tutorial,
  notifCount = 0,
  activityCount = 0,
  seasonVote,
}: SettingsPageProps) {
  const [usernameVal, setUsernameVal] = useState(user?.username ?? '');
  const [emailVal, setEmailVal] = useState(user?.email ?? '');
  const [resortNameVal, setResortNameVal] = useState(resort?.name ?? '');
  const [deleteConfirmText, setDeleteConfirmText] = useState('');

  const handleManageCookies = () => {
    localStorage.removeItem('cookie_consent');
    window.location.reload();
  };

  return (
    <Page
      title="Settings"
      icon="fa-gear"
      iconTone="text-primary"
      back="/dashboard"
      width="max-w-3xl"
      subtitle="Game preferences, account credentials, and data controls"
    >
      <div className="space-y-6">
        {/* Demo banner */}
        {user?.isDemo && (
          <div className="flex items-center gap-3 rounded-lg border border-warning/40 bg-warning/10 p-4 text-xs text-foreground">
            <Icon name="fa-cloud-arrow-up" className="text-warning text-base shrink-0" />
            <div className="flex-1">
              You are playing a demo mountain. To keep your progress,{' '}
              <a href="/demo/save" className="font-semibold text-primary underline">
                save it to a permanent account
              </a>
              .
            </div>
          </div>
        )}

        {/* Profile Card */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-user" className="text-primary" />
              Profile Credentials
            </span>
          }
        >
          <div className="space-y-4">
            <form action="/account/username" method="post" className="flex flex-col sm:flex-row gap-2 sm:items-end">
              <CsrfInput />
              <div className="flex-1 space-y-1">
                <label className="text-xs font-semibold text-muted">Username</label>
                <input
                  type="text"
                  name="username"
                  aria-label="Username"
                  value={usernameVal}
                  onChange={(e) => setUsernameVal(e.target.value)}
                  minLength={3}
                  maxLength={30}
                  required
                  autoComplete="username"
                  className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                />
              </div>
              <Button type="submit" variant="primary" size="sm" className="shrink-0 min-h-11 sm:min-h-8 text-xs font-semibold px-4">
                Update Username
              </Button>
            </form>

            <div className="border-t border-edge pt-4">
              <form action="/account/email" method="post" className="flex flex-col sm:flex-row gap-2 sm:items-end">
                <CsrfInput />
                <div className="flex-1 space-y-1">
                  <label className="text-xs font-semibold text-muted">Email Address</label>
                  <input
                    type="email"
                    name="email"
                    aria-label="Email Address"
                    value={emailVal}
                    onChange={(e) => setEmailVal(e.target.value)}
                    required
                    autoComplete="email"
                    className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                  />
                </div>
                <Button type="submit" variant="primary" size="sm" className="shrink-0 min-h-11 sm:min-h-8 text-xs font-semibold px-4">
                  Update Email
                </Button>
              </form>
            </div>
          </div>
        </Panel>

        {/* Password Card */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-key" className="text-secondary" />
              Password & Security
            </span>
          }
        >
          <p className="text-xs text-muted mb-4">
            Signed up via third-party provider or forgot your password? You can request a one-time code via email.
          </p>

          <form action="/account/password" method="post" className="space-y-3">
            <CsrfInput />
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
              {codeFor === 'password' ? (
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Emailed Code</label>
                  <input
                    type="text"
                    name="code"
                    inputMode="numeric"
                    maxLength={6}
                    pattern="[0-9]{6}"
                    placeholder="6-digit code"
                    required
                    autoComplete="one-time-code"
                    className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 font-mono text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                  />
                </div>
              ) : (
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Current Password</label>
                  <input
                    type="password"
                    name="current_password"
                    required
                    autoComplete="current-password"
                    className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                  />
                </div>
              )}

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">New Password</label>
                <input
                  type="password"
                  name="new_password"
                  minLength={8}
                  required
                  autoComplete="new-password"
                  className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Confirm Password</label>
                <input
                  type="password"
                  name="confirm_password"
                  minLength={8}
                  required
                  autoComplete="new-password"
                  className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                />
              </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
              <Button type="submit" variant="primary" size="sm" className="min-h-11 sm:min-h-8 text-xs font-semibold px-4">
                {codeFor === 'password' ? 'Set New Password' : 'Change Password'}
              </Button>

              <form action="/account/send-code" method="post" className="inline-block">
                <CsrfInput />
                <input type="hidden" name="purpose" value="password" />
                <Button
                  type="submit"
                  variant="ghost"
                  size="sm"
                  className="inline-flex items-center gap-1.5 text-xs text-muted hover:text-primary underline min-h-11 sm:min-h-8"
                >
                  <Icon name="fa-envelope" />
                  {codeFor === 'password' ? 'Resend verification code' : 'No password? Email me a code'}
                </Button>
              </form>
            </div>
          </form>
        </Panel>

        {/* Resort Preferences */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-mountain" className="text-primary" />
              Resort & Gameplay Preferences
            </span>
          }
        >
          <div className="space-y-5">
            {/* Resort Name */}
            <form action="/settings/resort-name" method="post" className="flex flex-col sm:flex-row gap-2 sm:items-end">
              <CsrfInput />
              <div className="flex-1 space-y-1">
                <label className="text-xs font-semibold text-muted">Resort Name</label>
                <input
                  type="text"
                  name="resort_name"
                  aria-label="Resort Name"
                  value={resortNameVal}
                  onChange={(e) => setResortNameVal(e.target.value)}
                  maxLength={50}
                  required
                  className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-focus focus:outline-none"
                />
              </div>
              <Button type="submit" variant="primary" size="sm" className="shrink-0 min-h-11 sm:min-h-8 text-xs font-semibold px-4">
                Rename Resort
              </Button>
            </form>

            <div className="border-t border-edge pt-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {/* Units */}
                <form action="/settings" method="post" className="space-y-2">
                  <CsrfInput />
                  <label className="text-xs font-semibold text-muted block">Measurement Units & Currency</label>
                  <div className="flex items-center gap-2">
                    <Select
                      name="units"
                      aria-label="Measurement Units & Currency"
                      defaultValue={units}
                      options={[
                        { id: 'metric', label: 'Metric (Meters, km/h, °C, EUR €)' },
                        { id: 'imperial', label: 'Imperial (Feet, mph, °F, USD $)' },
                      ]}
                      className="w-full"
                    />
                    <Button type="submit" variant="secondary" size="sm" className="shrink-0 min-h-11 sm:min-h-8 text-xs font-semibold px-4">
                      Save
                    </Button>
                  </div>
                </form>

                {/* Difficulty */}
                <form action="/settings/difficulty" method="post" className="space-y-2">
                  <CsrfInput />
                  <label className="text-xs font-semibold text-muted block">Simulation Difficulty</label>
                  <div className="flex items-center gap-2">
                    <Select
                      name="difficulty"
                      aria-label="Simulation Difficulty"
                      defaultValue={difficulty}
                      options={[
                        { id: 'easy', label: 'Easy (Higher income, relaxed costs)' },
                        { id: 'standard', label: 'Standard (Balanced simulation)' },
                        { id: 'hard', label: 'Hard (Strict expenses, challenging weather)' },
                      ]}
                      className="w-full capitalize"
                    />
                    <Button type="submit" variant="secondary" size="sm" className="shrink-0 min-h-11 sm:min-h-8 text-xs font-semibold px-4">
                      Save
                    </Button>
                  </div>
                </form>
              </div>
            </div>

            <div className="border-t border-edge pt-4 flex flex-wrap items-center justify-between gap-4">
              <div>
                <div className="text-xs font-semibold text-foreground">Resort Tours</div>
                <div className="text-[11px] text-muted">Allow other players to visit and rate your mountain</div>
              </div>
              <form action="/settings/toggle-tours" method="post">
                <CsrfInput />
                <Button
                  type="submit"
                  size="sm"
                  variant={allowTours ? 'primary' : 'secondary'}
                >
                  <Icon name={allowTours ? 'fa-check' : 'fa-xmark'} className="mr-1.5" />
                  {allowTours ? 'Tours Enabled' : 'Tours Disabled'}
                </Button>
              </form>
            </div>

            <div className="border-t border-edge pt-4 flex flex-wrap items-center justify-between gap-4">
              <div>
                <div className="text-xs font-semibold text-foreground">Interactive Tutorial</div>
                <div className="text-[11px] text-muted">
                  {tutorial ? `Currently on step ${tutorial.step}` : 'Completed or dismissed'}
                </div>
              </div>
              <PostButton
                action="/settings/reset-tutorial"
                variant="secondary"
                size="sm"
                confirm={{
                  title: 'Restart Tutorial?',
                  body: 'This will reset the guided onboarding walkthrough back to step 1.',
                  confirmLabel: 'Restart Tutorial',
                }}
              >
                <Icon name="fa-rotate-left" className="mr-1.5" />
                Restart Walkthrough
              </PostButton>
            </div>
          </div>
        </Panel>

        {/* Data Management */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-database" className="text-muted" />
              Data & Storage Controls
            </span>
          }
        >
          <div className="divide-y divide-edge -mx-4 -my-4 sm:-mx-6 sm:-my-6">
            <div className="flex items-center justify-between px-4 py-3 sm:px-6">
              <div>
                <div className="text-xs font-semibold text-foreground">Inbox Notifications</div>
                <div className="text-[11px] text-muted">{notifCount} total stored notification{notifCount === 1 ? '' : 's'}</div>
              </div>
              <PostButton
                action="/settings/clear-notifications"
                variant="secondary"
                size="sm"
                confirm={{
                  title: 'Clear All Notifications?',
                  body: 'This will permanently remove all notifications in your inbox.',
                  confirmLabel: 'Clear All',
                }}
              >
                <Icon name="fa-trash" className="mr-1.5 text-danger" />
                Clear
              </PostButton>
            </div>

            <div className="flex items-center justify-between px-4 py-3 sm:px-6">
              <div>
                <div className="text-xs font-semibold text-foreground">Activity Journal</div>
                <div className="text-[11px] text-muted">{activityCount} logged game event{activityCount === 1 ? '' : 's'}</div>
              </div>
              <PostButton
                action="/settings/clear-activity"
                variant="secondary"
                size="sm"
                confirm={{
                  title: 'Clear Activity Log?',
                  body: 'This will clear all past event entries recorded in your activity journal.',
                  confirmLabel: 'Clear Log',
                }}
              >
                <Icon name="fa-trash" className="mr-1.5 text-danger" />
                Clear
              </PostButton>
            </div>

            <div className="flex items-center justify-between px-4 py-3 sm:px-6">
              <div>
                <div className="text-xs font-semibold text-foreground">Cookie Consent</div>
                <div className="text-[11px] text-muted">Reset cookie preferences and prompt consent banner</div>
              </div>
              <Button
                variant="secondary"
                size="sm"
                onPress={handleManageCookies}
              >
                <Icon name="fa-cookie-bite" className="mr-1.5 text-warning" />
                Reset Preferences
              </Button>
            </div>
          </div>
        </Panel>

        {seasonVote && (seasonVote.open || (seasonVote.closed && seasonVote.results)) && <SeasonVoteSection vote={seasonVote} />}

        {/* Delete Account (Danger Zone) */}
        <Panel
          className="border-danger/30 bg-danger/5"
          title={
            <span className="flex items-center gap-2 text-danger">
              <Icon name="fa-triangle-exclamation" />
              Danger Zone: Delete Account
            </span>
          }
        >
          <p className="text-xs text-muted mb-4">
            Permanently deletes your account, resort assets, finances, staff roster, and historical telemetry. This cannot be undone.
          </p>

          <form action="/account/delete" method="post" className="space-y-3">
            <CsrfInput />
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              {codeFor === 'delete' ? (
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Emailed Code</label>
                  <input
                    type="text"
                    name="code"
                    inputMode="numeric"
                    maxLength={6}
                    pattern="[0-9]{6}"
                    placeholder="6-digit code from email"
                    required
                    autoComplete="one-time-code"
                    className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 font-mono text-base sm:text-xs text-foreground focus:border-danger focus:outline-none"
                  />
                </div>
              ) : (
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Confirm Password</label>
                  <input
                    type="password"
                    name="password"
                    placeholder="Enter current password"
                    required
                    autoComplete="current-password"
                    className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-danger focus:outline-none"
                  />
                </div>
              )}

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Type DELETE to confirm</label>
                <input
                  type="text"
                  name="confirm_delete"
                  placeholder="DELETE"
                  value={deleteConfirmText}
                  onChange={(e) => setDeleteConfirmText(e.target.value)}
                  autoComplete="off"
                  required
                  className="w-full min-h-11 sm:min-h-9 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-base sm:text-xs text-foreground focus:border-danger focus:outline-none font-mono"
                />
              </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
              <Button
                type="submit"
                variant="danger"
                size="sm"
                className="min-h-11 sm:min-h-8 text-xs font-semibold px-4"
                isDisabled={deleteConfirmText !== 'DELETE'}
              >
                Permanently Delete Account
              </Button>

              <form action="/account/send-code" method="post" className="inline-block">
                <CsrfInput />
                <input type="hidden" name="purpose" value="delete" />
                <Button
                  type="submit"
                  variant="ghost"
                  size="sm"
                  className="inline-flex items-center gap-1.5 text-xs text-muted hover:text-danger underline min-h-11 sm:min-h-8"
                >
                  <Icon name="fa-envelope" />
                  {codeFor === 'delete' ? 'Resend deletion code' : 'No password? Email me a code'}
                </Button>
              </form>
            </div>
          </form>
        </Panel>
      </div>
    </Page>
  );
}

function SeasonVoteSection({ vote }: { vote: NonNullable<SettingsPageProps['seasonVote']> }) {
  const [choice, setChoice] = useState<SeasonChoice | null>(vote.myChoice);
  const [saved, setSaved] = useState<SeasonChoice | null>(vote.myChoice);
  const [comment, setComment] = useState(vote.myComment);
  const [error, setError] = useState<string>();
  const [busy, setBusy] = useState(false);

  const save = async () => {
    if (!choice) return;
    setBusy(true);
    setError(undefined);
    try {
      await sendVote(choice, comment.trim());
      setSaved(choice);
      toast.success('Your vote is saved.');
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) setError(e.message);
      else toast.danger(e instanceof ApiError && e.status === 403 ? e.message : "Your vote didn't save. Try again.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <Panel
      title={
        <span className="flex items-center gap-2">
          <Icon name="fa-check-to-slot" className="text-accent" />
          Season format vote
        </span>
      }
    >
      {vote.open ? (
        <div className="flex flex-col gap-4">
          <p className="text-sm text-muted">
            {saved ? `Your vote: ${SEASON_OPTIONS[saved].title}.` : "You haven't voted yet."} You can change it until {vote.closesLabel}.
          </p>
          <SeasonChoiceFields order={vote.order} choice={choice} onChoice={(c) => { setChoice(c); setError(undefined); }} comment={comment} onComment={setComment} error={error} />
          <Button variant="primary" className="min-h-11 self-start" onPress={save} isDisabled={!choice || busy} isPending={busy}>
            Save my vote
          </Button>
        </div>
      ) : vote.results && (
        <p className="text-sm">
          Voting closed on {vote.closesLabel}. Shared season {vote.results.shared.pct}%, your own pace {vote.results.individual.pct}% ({vote.results.votes} {vote.results.votes === 1 ? 'vote' : 'votes'}).
        </p>
      )}
    </Panel>
  );
}
