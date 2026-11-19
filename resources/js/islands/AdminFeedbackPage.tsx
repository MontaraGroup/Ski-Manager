import { useMemo, useState } from 'react';
import { Chip } from '@heroui/react';
import { Empty, Page, Panel, PostButton } from '@/components/Page';

type Category = 'bug' | 'idea' | 'other';
type Status = 'new' | 'read' | 'closed';

type Report = {
  id: number;
  userId: number;
  username: string;
  category: Category;
  message: string;
  appVersion: string | null;
  device: string | null;
  status: Status;
  hasScreenshot: boolean;
  timeAgo: string;
  createdAt: string;
};

type OpenReport = Report & {
  diagnostics: Record<string, unknown> | null;
  screenshotUrl: string | null;
  purged: boolean;
};

export type AdminFeedbackPageProps = {
  reports: Report[];
  open: OpenReport | null;
  retentionDays: number;
};

const CATEGORY_LABEL: Record<Category, string> = { bug: 'Bug', idea: 'Idea', other: 'Other' };
const CATEGORY_COLOR: Record<Category, 'danger' | 'accent' | 'default'> = { bug: 'danger', idea: 'accent', other: 'default' };

/** Diagnostics sections in reading order, with the names the app's toggles use */
const SECTIONS: { key: string; label: string }[] = [
  { key: 'device', label: 'Device info' },
  { key: 'app', label: 'App' },
  { key: 'logs', label: 'App logs' },
  { key: 'lastCrash', label: 'Crash from the previous run' },
  { key: 'network', label: 'Network activity' },
  { key: 'screens', label: 'Recent screens' },
  { key: 'layout', label: 'Screen layout' },
  { key: 'savedData', label: 'Saved app data' },
  { key: 'system', label: 'System diagnostics' },
  { key: 'notificationHistory', label: 'Notification history' },
];

export default function AdminFeedbackPage({ reports = [], open = null, retentionDays }: AdminFeedbackPageProps) {
  const [status, setStatus] = useState<Status | 'all'>('all');
  const [category, setCategory] = useState<Category | 'all'>('all');
  const shown = useMemo(
    () => reports.filter((r) => (status === 'all' || r.status === status) && (category === 'all' || r.category === category)),
    [reports, status, category],
  );
  const unread = reports.filter((r) => r.status === 'new').length;

  return (
    <Page
      title="App Feedback"
      icon="fa-comment-dots"
      back="/admin"
      width="max-w-6xl"
      subtitle={`${reports.length} report${reports.length === 1 ? '' : 's'}${unread ? ` · ${unread} new` : ''}. Replies go through the support inbox.`}
    >
      <div className="flex flex-col gap-6 lg:grid lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:items-start">
        <Panel
          title={`Reports (${shown.length})`}
          actions={
            <div className="flex flex-wrap gap-2">
              <FilterSelect label="Status" value={status} onChange={(v) => setStatus(v as Status | 'all')} options={['all', 'new', 'read', 'closed']} />
              <FilterSelect label="Category" value={category} onChange={(v) => setCategory(v as Category | 'all')} options={['all', 'bug', 'idea', 'other']} />
            </div>
          }
        >
          {reports.length === 0 ? (
            <Empty icon="fa-inbox" title="No feedback yet">
              <p className="text-sm text-muted">Reports sent from the Android app show up here.</p>
            </Empty>
          ) : shown.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted">No reports match these filters.</p>
          ) : (
            <ul className="-mx-4 -my-4 divide-y divide-edge sm:-mx-6 sm:-my-6">
              {shown.map((r) => (
                <li key={r.id}>
                  <a
                    href={`/admin/feedback/${r.id}`}
                    aria-current={open?.id === r.id ? 'page' : undefined}
                    className={`flex flex-col gap-1 px-4 py-3 sm:px-6 hover:bg-surface-2/60 ${open?.id === r.id ? 'bg-surface-2' : ''} ${r.status === 'new' ? 'border-l-2 border-accent' : ''}`}
                  >
                    <span className="flex flex-wrap items-center gap-2 text-xs">
                      <Chip size="sm" variant="soft" color={CATEGORY_COLOR[r.category]}>{CATEGORY_LABEL[r.category]}</Chip>
                      <span className="font-semibold">{r.username}</span>
                      <span className="font-mono text-muted">#{r.id}</span>
                      {r.status !== 'read' && <span className="text-muted">{r.status}</span>}
                      <span className="ml-auto text-muted">{r.timeAgo}</span>
                    </span>
                    <span className={`truncate text-sm ${r.status === 'new' ? 'font-medium' : 'text-muted'}`}>{r.message}</span>
                  </a>
                </li>
              ))}
            </ul>
          )}
        </Panel>

        {open ? (
          <ReportDetail report={open} retentionDays={retentionDays} />
        ) : (
          <Panel title="Report">
            <p className="text-sm text-muted">Pick a report to see its message, screenshot and attached details.</p>
          </Panel>
        )}
      </div>
    </Page>
  );
}

function FilterSelect({ label, value, onChange, options }: { label: string; value: string; onChange: (v: string) => void; options: string[] }) {
  return (
    <label className="flex items-center gap-1 text-xs text-muted">
      {label}
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="min-h-11 rounded-md border border-edge bg-surface-2 px-2 text-xs text-foreground focus:border-focus focus:outline-none sm:min-h-8"
      >
        {options.map((o) => (
          <option key={o} value={o}>{o === 'all' ? 'All' : o[0].toUpperCase() + o.slice(1)}</option>
        ))}
      </select>
    </label>
  );
}

function ReportDetail({ report, retentionDays }: { report: OpenReport; retentionDays: number }) {
  const diag = report.diagnostics ?? {};
  const present = SECTIONS.filter((s) => diag[s.key] !== undefined && diag[s.key] !== null);

  return (
    <Panel
      title={
        <span className="flex flex-wrap items-center gap-2">
          #{report.id}
          <Chip size="sm" variant="soft" color={CATEGORY_COLOR[report.category]}>{CATEGORY_LABEL[report.category]}</Chip>
          <Chip size="sm" variant="soft">{report.status}</Chip>
        </span>
      }
      actions={
        <div className="flex flex-wrap gap-2">
          {report.status !== 'closed' ? (
            <PostButton action={`/admin/feedback/${report.id}/status`} data={{ status: 'closed' }} size="sm" className="min-h-11 sm:min-h-8">Close</PostButton>
          ) : (
            <PostButton action={`/admin/feedback/${report.id}/status`} data={{ status: 'read' }} size="sm" variant="tertiary" className="min-h-11 sm:min-h-8">Reopen</PostButton>
          )}
          <PostButton action={`/admin/feedback/${report.id}/status`} data={{ status: 'new' }} size="sm" variant="tertiary" className="min-h-11 sm:min-h-8">Mark unread</PostButton>
        </div>
      }
    >
      <div className="flex flex-col gap-4">
        <p className="text-xs text-muted">
          <a href={`/admin/user/${report.userId}`} className="font-medium text-foreground underline">{report.username}</a>
          {' · '}{report.createdAt}
          {report.appVersion && <> · app {report.appVersion}</>}
          {report.device && <> · {report.device}</>}
        </p>
        <p className="whitespace-pre-wrap break-words text-sm">{report.message}</p>
        <a href={`/admin/support/${report.userId}`} className="inline-flex min-h-11 items-center self-start text-sm font-medium text-accent-soft-foreground underline sm:min-h-0">
          Reply in the support chat
        </a>

        {report.screenshotUrl && (
          <figure className="flex flex-col gap-1">
            <a href={report.screenshotUrl} target="_blank" rel="noopener">
              <img src={report.screenshotUrl} alt={`Screenshot attached to report #${report.id}`} className="max-h-[32rem] w-auto rounded-md border border-edge" />
            </a>
            <figcaption className="text-xs text-muted">Screenshot. Opens full size in a new tab.</figcaption>
          </figure>
        )}

        {report.purged ? (
          <p className="text-sm text-muted">Attachments were deleted after {retentionDays} days.</p>
        ) : present.length === 0 ? (
          <p className="text-sm text-muted">The player attached no details.</p>
        ) : (
          <div className="flex flex-col gap-2">
            {present.map((s) => (
              <details key={s.key} className="rounded-md border border-edge" open={s.key === 'device' || s.key === 'lastCrash'}>
                <summary className="flex min-h-11 cursor-pointer items-center px-3 text-sm font-medium sm:min-h-9">
                  {s.label}
                  {Array.isArray(diag[s.key]) && <span className="ml-2 text-xs text-muted">({(diag[s.key] as unknown[]).length})</span>}
                </summary>
                <Section value={diag[s.key]} />
              </details>
            ))}
          </div>
        )}
      </div>
    </Panel>
  );
}

/** Lists of strings read as log lines; flat objects as a key/value table; anything else as JSON */
function Section({ value }: { value: unknown }) {
  if (Array.isArray(value) && value.every((v) => typeof v === 'string')) {
    return <pre className="max-h-96 overflow-auto border-t border-edge p-3 text-[11px] leading-relaxed">{(value as string[]).join('\n')}</pre>;
  }
  if (value && typeof value === 'object' && !Array.isArray(value) && Object.values(value).every((v) => v === null || typeof v !== 'object')) {
    return (
      <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 border-t border-edge p-3 text-xs">
        {Object.entries(value as Record<string, unknown>).map(([k, v]) => (
          <div key={k} className="contents">
            <dt className="text-muted">{k}</dt>
            <dd className="break-words font-mono">{String(v)}</dd>
          </div>
        ))}
      </dl>
    );
  }
  return <pre className="max-h-96 overflow-auto border-t border-edge p-3 text-[11px] leading-relaxed">{JSON.stringify(value, null, 2)}</pre>;
}
