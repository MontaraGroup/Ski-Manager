import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page } from '@/components/Page';

export type GuideItem = {
  number: number;
  slug: string;
  title: string;
  summary: string;
  icon: string;
};

export type GuidesPageProps = {
  guides: GuideItem[];
  isLoggedIn: boolean;
};

export default function GuidesPage({ guides = [], isLoggedIn = true }: GuidesPageProps) {
  return (
    <Page
      title="Ski Manager Guides"
      icon="fa-book-open"
      iconTone="text-primary"
      back="/dashboard"
      width="max-w-4xl"
      subtitle="Comprehensive gameplay mechanics, formulas, and operational guidelines"
    >
      <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {guides.map((g) => {
            const iconName = g.icon.startsWith('fa-') ? g.icon : `fa-${g.icon}`;

            return (
              <a
                key={g.slug}
                href={`/guides/${g.slug}`}
                className="p-4 rounded-xl border border-border bg-base-100 hover:bg-base-200/50 hover:border-primary/50 transition-all flex items-start gap-3.5 group shadow-sm"
              >
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                  <Icon name={iconName} className="text-primary text-base" />
                </div>
                <div className="min-w-0">
                  <div className="text-[11px] font-bold uppercase tracking-wider text-muted">
                    Guide {g.number}
                  </div>
                  <h3 className="font-bold text-sm text-foreground group-hover:text-primary transition-colors mt-0.5">
                    {g.title}
                  </h3>
                  <p className="text-xs text-muted mt-1 leading-relaxed">{g.summary}</p>
                </div>
              </a>
            );
          })}
        </div>

        <div className="text-center pt-2">
          <a href={isLoggedIn ? '/dashboard' : '/register'}>
            <Button variant="primary" className="min-h-[44px] px-8 font-semibold text-sm">
              {isLoggedIn ? 'Back to Resort Dashboard' : 'Play Ski Manager Free'}
            </Button>
          </a>
        </div>
      </div>
    </Page>
  );
}
