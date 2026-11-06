import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type GuideArticlePageProps = {
  slug: string;
  title: string;
  summary: string;
  contentHtml: string;
  prev: {
    slug: string;
    title: string;
  } | null;
  next: {
    slug: string;
    title: string;
  } | null;
  isLoggedIn: boolean;
};

export default function GuideArticlePage({
  title = 'Guide',
  summary = '',
  contentHtml = '',
  prev = null,
  next = null,
  isLoggedIn = true,
}: GuideArticlePageProps) {
  return (
    <Page
      title={title}
      icon="fa-book-open"
      iconTone="text-primary"
      back="/guides"
      width="max-w-3xl"
      subtitle={summary}
    >
      <div className="space-y-6">
        {/* Article Body */}
        <Panel>
          <div
            className="prose prose-sm dark:prose-invert max-w-none text-xs sm:text-sm text-foreground/90 leading-relaxed space-y-4"
            dangerouslySetInnerHTML={{ __html: contentHtml }}
          />
        </Panel>

        {/* Prev / Next Navigation */}
        <div className="flex items-center justify-between gap-3 pt-3 border-t border-border/40">
          {prev ? (
            <a href={`/guides/${prev.slug}`}>
              <Button variant="secondary" size="sm" className="font-semibold text-xs min-h-[36px] gap-1.5">
                <Icon name="fa-arrow-left" /> {prev.title}
              </Button>
            </a>
          ) : (
            <div />
          )}

          {next ? (
            <a href={`/guides/${next.slug}`}>
              <Button variant="secondary" size="sm" className="font-semibold text-xs min-h-[36px] gap-1.5">
                {next.title} <Icon name="fa-arrow-right" />
              </Button>
            </a>
          ) : (
            <div />
          )}
        </div>

        {/* Guest conversion banner */}
        {!isLoggedIn && (
          <div className="p-4 rounded-xl border border-border bg-base-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
            <div>
              <div className="font-bold text-xs sm:text-sm text-foreground">
                Run your own mountain resort
              </div>
              <div className="text-xs text-muted">
                Ski Manager is free to play right in your browser.
              </div>
            </div>
            <a href="/register">
              <Button variant="primary" size="sm" className="font-semibold text-xs min-h-[36px]">
                Play Free Now
              </Button>
            </a>
          </div>
        )}
      </div>
    </Page>
  );
}
