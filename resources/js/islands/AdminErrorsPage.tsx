import { useState, useMemo } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type AdminErrorsPageProps = {
  lines: string[];
  today: string;
};

export default function AdminErrorsPage({ lines = [], today }: AdminErrorsPageProps) {
  const [filter, setFilter] = useState('');
  const [copied, setCopied] = useState(false);

  const filteredLines = useMemo(() => {
    const q = filter.trim().toLowerCase();
    if (!q) return lines;
    return lines.filter((l) => l.toLowerCase().includes(q));
  }, [lines, filter]);

  const handleCopy = () => {
    navigator.clipboard.writeText(lines.join('\n')).then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  };

  return (
    <Page
      title="Error Log"
      icon="fa-bug"
      iconTone="text-danger"
      back="/admin/settings"
      width="max-w-5xl"
      subtitle="Recent application errors and critical events from today's system log."
    >
      <Panel
        title={`Today's Errors (${today})`}
        actions={
          <div className="flex min-w-0 flex-wrap items-center justify-end gap-2">
            {lines.length > 0 && (
              <>
                <input
                  type="search"
                  aria-label="Filter error log"
                  placeholder="Filter log entries…"
                  value={filter}
                  onChange={(e) => setFilter(e.target.value)}
                  className="min-h-11 w-full sm:min-h-0 sm:w-64 rounded-md border border-border bg-field px-3 py-1.5 text-xs text-foreground placeholder:text-muted focus:border-focus focus:outline-none"
                />
                <Button
                  size="sm"
                  variant="secondary"
                  onPress={handleCopy}
                  aria-label="Copy logs to clipboard"
                  className="min-h-11 gap-1.5 sm:min-h-8"
                >
                  <Icon name={copied ? 'fa-check' : 'fa-copy'} />
                  {copied ? 'Copied' : 'Copy'}
                </Button>
              </>
            )}
            <a
              href="/admin/errors"
              className="inline-flex min-h-11 sm:min-h-8 items-center gap-1.5 rounded-md border border-border bg-surface px-2.5 text-xs font-medium text-foreground hover:bg-surface-secondary transition-colors"
              title="Refresh logs"
            >
              <Icon name="fa-arrow-rotate-right" />
              Refresh
            </a>
          </div>
        }
      >
        {lines.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-12 text-center">
            <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-success/15 text-success">
              <Icon name="fa-circle-check" className="text-xl" />
            </div>
            <p className="text-sm font-semibold text-foreground">No errors today</p>
            <p className="mt-1 text-xs text-muted">The system log has zero non-debug errors recorded for {today}.</p>
          </div>
        ) : filteredLines.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted">No log lines match your filter.</p>
        ) : (
          <div className="overflow-x-auto rounded-lg border border-edge bg-surface-3/70 p-4 font-mono text-xs leading-relaxed">
            <pre className="whitespace-pre-wrap break-words space-y-1">
              {filteredLines.map((line, idx) => {
                const isCriticalOrError = line.includes('CRITICAL') || line.includes('ERROR');
                const isWarning = line.includes('WARNING');

                let lineClass = 'text-muted';
                if (isCriticalOrError) {
                  lineClass = 'text-danger font-medium';
                } else if (isWarning) {
                  lineClass = 'text-warning font-medium';
                }

                return (
                  <div key={idx} className={`py-0.5 ${lineClass}`}>
                    <span className="select-none pr-3 text-muted/40 font-mono text-[11px]">
                      {String(idx + 1).padStart(2, '0')}
                    </span>
                    <span>{line}</span>
                  </div>
                );
              })}
            </pre>
          </div>
        )}
      </Panel>
    </Page>
  );
}
