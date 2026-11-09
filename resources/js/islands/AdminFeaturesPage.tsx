import { useMemo, useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel, PostButton } from '@/components/Page';

export type FeatureFlag = {
  id: number;
  name: string;
  cleanName: string;
  flag_key: string;
  description: string;
  enabled: number;
  isBeta: boolean;
  usage: {
    pages?: string[];
    code?: string[];
  } | null;
};

export type AdminFeaturesPageProps = {
  flags: FeatureFlag[];
  counts: {
    everyone: number;
    admins: number;
    off: number;
    unused: number;
  };
};

export default function AdminFeaturesPage({ flags, counts }: AdminFeaturesPageProps) {
  const [search, setSearch] = useState('');
  const [filterGroup, setFilterGroup] = useState<'all' | 'game' | 'beta'>('all');

  const filteredFlags = useMemo(() => {
    return flags.filter((f) => {
      if (filterGroup === 'game' && f.isBeta) return false;
      if (filterGroup === 'beta' && !f.isBeta) return false;

      if (!search.trim()) return true;
      const q = search.toLowerCase().trim();
      return (
        f.cleanName.toLowerCase().includes(q) ||
        f.flag_key.toLowerCase().includes(q) ||
        f.description.toLowerCase().includes(q)
      );
    });
  }, [flags, search, filterGroup]);

  const gameFlags = useMemo(
    () => filteredFlags.filter((f) => !f.isBeta),
    [filteredFlags],
  );
  const betaFlags = useMemo(
    () => filteredFlags.filter((f) => f.isBeta),
    [filteredFlags],
  );

  return (
    <Page
      title="Feature Flags"
      icon="fa-toggle-on"
      iconTone="text-accent"
      back="/admin"
      subtitle={
        <span>
          <strong className="text-foreground">{counts.everyone}</strong> on for everyone &middot;{' '}
          <strong className="text-foreground">{counts.admins}</strong> admins only &middot;{' '}
          <strong className="text-foreground">{counts.off}</strong> off
          {counts.unused > 0 && (
            <span className="text-warning ml-1">
              &middot; {counts.unused} not connected to code
            </span>
          )}
        </span>
      }
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <PostButton
            action="/admin/features/disable-all-beta"
            variant="secondary"
            size="sm"
            confirm={{
              title: 'Reset All Beta Flags?',
              body: 'This switches every beta flag off for all players and admins.',
              confirmLabel: 'Disable Beta Flags',
            }}
          >
            Reset Beta
          </PostButton>
          <PostButton
            action="/admin/features/enable-all"
            variant="primary"
            size="sm"
            confirm={{
              title: 'Enable All Feature Flags?',
              body: 'This turns on EVERY feature flag for all players, including experimental and beta features.',
              confirmLabel: 'Enable Everything',
            }}
          >
            Enable All
          </PostButton>
        </div>
      }
    >
      {/* Description explanation */}
      <div className="rounded-xl border border-border bg-surface-raised p-3.5 text-xs text-muted">
        <strong className="text-foreground">Off:</strong> Hides feature from players (pages redirect to dashboard and navigation links are removed).{' '}
        <strong className="text-foreground">Admins:</strong> Allows administrators to test the feature in production before players see it.{' '}
        <strong className="text-foreground">Everyone:</strong> Live for all signed-in resort managers.
      </div>

      {/* Search & Filter Toolbar */}
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-md">
          <Icon
            name="fa-magnifying-glass"
            className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted"
          />
          <input
            type="search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search flags by name, key, or description…"
            aria-label="Search flags"
            className="w-full rounded-xl border border-border bg-surface py-2 pl-9 pr-3 text-xs text-foreground placeholder:text-muted focus:border-accent focus:outline-hidden"
          />
        </div>

        <div className="flex items-center gap-1">
          {(
            [
              { id: 'all', label: 'All', count: flags.length },
              { id: 'game', label: 'Game Features', count: flags.filter((f) => !f.isBeta).length },
              { id: 'beta', label: 'Beta Flags', count: flags.filter((f) => f.isBeta).length },
            ] as const
          ).map((t) => (
            <Button
              key={t.id}
              type="button"
              size="sm"
              variant={filterGroup === t.id ? 'primary' : 'ghost'}
              onPress={() => setFilterGroup(t.id)}
              className="rounded-lg px-2.5 py-1 text-xs font-semibold"
            >
              {t.label} <span className="font-mono text-[10px] opacity-75">({t.count})</span>
            </Button>
          ))}
        </div>
      </div>

      {/* Flag Groups */}
      {gameFlags.length > 0 && (
        <section className="space-y-2">
          <h2 className="flex items-center gap-2 text-sm font-bold text-foreground">
            <Icon name="fa-gamepad" className="text-accent text-xs" />
            Game Features ({gameFlags.length})
          </h2>
          <div className="overflow-hidden rounded-2xl border border-border bg-surface divide-y divide-border/60 shadow-xs">
            {gameFlags.map((f) => (
              <FlagRow key={f.id} flag={f} />
            ))}
          </div>
        </section>
      )}

      {betaFlags.length > 0 && (
        <section className="space-y-2">
          <h2 className="flex items-center gap-2 text-sm font-bold text-warning">
            <Icon name="fa-flask" className="text-warning text-xs" />
            Beta Features ({betaFlags.length})
          </h2>
          <div className="overflow-hidden rounded-2xl border border-border bg-surface divide-y divide-border/60 shadow-xs">
            {betaFlags.map((f) => (
              <FlagRow key={f.id} flag={f} />
            ))}
          </div>
        </section>
      )}

      {filteredFlags.length === 0 && (
        <div className="rounded-2xl border border-dashed border-border bg-surface p-8 text-center text-xs text-muted">
          No feature flags matched "{search}".
        </div>
      )}
    </Page>
  );
}

function FlagRow({ flag }: { flag: FeatureFlag }) {
  const levels = [
    { value: 0, label: 'Off', color: 'bg-red-600 text-white' },
    { value: 1, label: 'Admins', color: 'bg-warning text-black font-bold' },
    { value: 2, label: 'Everyone', color: 'bg-emerald-600 text-white font-bold' },
  ];

  return (
    <div className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between transition-colors hover:bg-surface-raised/40">
      <div className="min-w-0 flex-1 space-y-1">
        <div className="flex flex-wrap items-baseline gap-2">
          <span className="font-semibold text-sm text-foreground">{flag.cleanName}</span>
          <span className="rounded bg-surface-raised px-1.5 py-0.5 font-mono text-[11px] text-muted">
            {flag.flag_key}
          </span>
          {flag.isBeta && (
            <span className="rounded bg-warning/15 px-1.5 py-0.5 text-[10px] font-bold text-warning uppercase">
              Beta
            </span>
          )}
        </div>

        {flag.description && (
          <p className="text-xs text-muted leading-relaxed">{flag.description}</p>
        )}

        <div className="flex flex-wrap items-center gap-1.5 pt-0.5 text-[11px] text-muted">
          {!flag.usage ? (
            <span className="inline-flex items-center gap-1 text-warning font-medium">
              <Icon name="fa-triangle-exclamation" className="text-[10px]" />
              Not connected to code. Changing it has no effect.
            </span>
          ) : (
            <>
              {flag.usage.pages && flag.usage.pages.length > 0 && (
                <span className="inline-flex items-center gap-1">
                  <span>Gates:</span>
                  {flag.usage.pages.map((pg) => (
                    <code
                      key={pg}
                      className="rounded bg-surface-raised px-1 py-0.2 font-mono text-[10px] text-foreground"
                    >
                      {pg}
                    </code>
                  ))}
                </span>
              )}
              {flag.usage.code && flag.usage.code.length > 0 && (
                <span className="inline-flex items-center gap-1">
                  {flag.usage.pages && flag.usage.pages.length > 0 && <span>&middot;</span>}
                  <span>Checked in:</span>
                  {flag.usage.code.slice(0, 3).map((c) => (
                    <code
                      key={c}
                      className="rounded bg-surface-raised px-1 py-0.2 font-mono text-[10px] text-foreground"
                    >
                      {c}
                    </code>
                  ))}
                  {flag.usage.code.length > 3 && (
                    <span className="text-[10px]">+{flag.usage.code.length - 3} more</span>
                  )}
                </span>
              )}
            </>
          )}
        </div>
      </div>

      {/* Segmented Level Selector */}
      <div className="flex shrink-0 items-center rounded-xl border border-border bg-surface-raised p-1 shadow-2xs">
        {levels.map((lvl) => {
          const isActive = flag.enabled === lvl.value;
          return (
            <form
              key={lvl.value}
              action={`/admin/features/set/${flag.id}`}
              method="post"
              className="inline"
            >
              <CsrfInput />
              <input type="hidden" name="level" value={lvl.value} />
              <Button
                type="submit"
                size="sm"
                variant={isActive ? 'primary' : 'ghost'}
                isDisabled={isActive}
                className="px-3 py-1.5 text-xs font-semibold"
              >
                {lvl.label}
              </Button>
            </form>
          );
        })}
      </div>
    </div>
  );
}
