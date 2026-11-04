import { Icon } from '@/components/Icon';
import { Empty, Page, Panel } from '@/components/Page';

type Log = { id: number; message: string; day: number; category: string; icon: string };

/** /activity: the last 50 things that happened at the resort */
export default function ActivityPage({ logs }: { logs: Log[] }) {
  return (
    <Page title="Activity log" icon="fa-clock-rotate-left" subtitle="The last 50 events at your resort, newest first." width="max-w-4xl">
      <Panel>
        {logs.length === 0 ? (
          <Empty icon="fa-scroll" title="No activity yet">Building, hiring and purchases show up here.</Empty>
        ) : (
          <ul className="divide-y divide-border">
            {logs.map((log) => (
              <li key={log.id} className="flex items-start gap-3 py-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-default">
                  <Icon name={log.icon} />
                </span>
                <div className="min-w-0">
                  <p className="text-sm">{log.message}</p>
                  <p className="text-sm text-muted">Day {log.day} · {log.category}</p>
                </div>
              </li>
            ))}
          </ul>
        )}
      </Panel>
    </Page>
  );
}
