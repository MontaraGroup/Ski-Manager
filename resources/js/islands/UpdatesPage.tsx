import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page } from '@/components/Page';

export type UpdateCategory = {
  type: 'new' | 'improved' | 'fixed' | 'removed';
  label: string;
  icon: string;
  badge: 'success' | 'info' | 'warning' | 'danger';
  items: {
    title: string;
    description: string;
  }[];
};

export type ReleaseUpdate = {
  id: number;
  version: string;
  title: string;
  description: string;
  release_type: string;
  released_at: string;
  releasedAtFormatted: string;
  isLatest: boolean;
  categories: UpdateCategory[];
};

export type UpdatesPageProps = {
  updates: ReleaseUpdate[];
};

export default function UpdatesPage({ updates = [] }: UpdatesPageProps) {
  const getCategoryBadge = (badge: string) => {
    switch (badge) {
      case 'success':
        return 'bg-success/15 text-success border-success/30';
      case 'info':
        return 'bg-primary/15 text-primary border-primary/30';
      case 'warning':
        return 'bg-warning/15 text-warning border-warning/30';
      default:
        return 'bg-danger/15 text-danger border-danger/30';
    }
  };

  return (
    <Page
      title="Game Updates"
      icon="fa-bullhorn"
      iconTone="text-primary"
      back="/dashboard"
      width="max-w-3xl"
      subtitle="Changelogs and system releases. Track updates and new features shipped to Ski Manager"
      actions={
        <div className="flex items-center gap-2">
          <a href="/roadmap">
            <Button variant="outline" size="sm" className="font-semibold text-xs min-h-[36px] gap-1.5">
              <Icon name="fa-compass" className="text-primary" /> Roadmap
            </Button>
          </a>
          <a
            href="https://skimanager1.featurebase.app/en/changelog"
            target="_blank"
            rel="noopener noreferrer"
          >
            <Button variant="secondary" size="sm" className="text-xs min-h-[36px] gap-1 text-muted">
              <Icon name="fa-arrow-up-right-from-square" className="text-[10px]" /> External
            </Button>
          </a>
        </div>
      }
    >
      <div className="relative border-l-2 border-border/80 ml-3.5 space-y-8 pl-6 my-2">
        {updates.map((up) => {
          const isLatest = up.isLatest;

          return (
            <div key={up.id} className="relative">
              {/* Dot on timeline */}
              <span
                className={`absolute -left-[33px] top-4 w-4 h-4 rounded-full border-2 border-base-100 ${
                  isLatest ? 'bg-primary ring-2 ring-primary/40' : 'bg-border'
                }`}
              />

              <div
                className={`rounded-2xl border p-5 bg-base-100 shadow-sm transition-all ${
                  isLatest
                    ? 'border-primary/50 ring-1 ring-primary/20 shadow-md'
                    : 'border-border'
                }`}
              >
                <div className="flex items-center gap-2 mb-2 flex-wrap">
                  <span className="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-base-200 border border-border/60">
                    {up.version}
                  </span>

                  {isLatest && (
                    <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-success/15 text-success border border-success/30 flex items-center gap-1">
                      <Icon name="fa-star" className="text-[9px]" /> Latest
                    </span>
                  )}

                  <span className="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-base-200 text-muted">
                    {up.release_type}
                  </span>

                  <span className="text-xs text-muted ml-auto flex items-center gap-1">
                    <Icon name="fa-calendar" className="text-[11px]" />
                    {up.releasedAtFormatted}
                  </span>
                </div>

                <h3 className="text-base font-bold text-foreground">{up.title}</h3>
                {up.description && (
                  <p className="text-xs text-muted mt-1 leading-relaxed">{up.description}</p>
                )}

                {up.categories.length > 0 && (
                  <div className="mt-4 space-y-4 pt-3 border-t border-border/40">
                    {up.categories.map((cat) => (
                      <div key={cat.type}>
                        <div className="flex items-center gap-2 mb-2">
                          <span
                            className={`px-2 py-0.5 rounded text-[11px] font-bold border uppercase tracking-wider flex items-center gap-1 ${getCategoryBadge(
                              cat.badge
                            )}`}
                          >
                            <Icon name={`fa-${cat.icon}`} className="text-[10px]" />
                            {cat.label}
                          </span>
                          <span className="text-[10px] text-muted">({cat.items.length})</span>
                        </div>

                        <ul className="space-y-1.5 ml-2 text-xs">
                          {cat.items.map((it, idx) => (
                            <li key={idx} className="flex items-start gap-2 text-muted leading-relaxed">
                              <span className="w-1.5 h-1.5 rounded-full bg-primary mt-1.5 shrink-0" />
                              <div>
                                {it.title && (
                                  <span className="font-semibold text-foreground mr-1">
                                    {it.title}:
                                  </span>
                                )}
                                <span>{it.description || it.title}</span>
                              </div>
                            </li>
                          ))}
                        </ul>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </Page>
  );
}
