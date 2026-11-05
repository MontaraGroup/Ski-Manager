import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type ComparedPlayer = {
  id: number;
  username: string;
  cashFormatted: string;
  difficulty: string;
  resort_map: string;
  staff: number;
  buildings: number;
  items: number;
};

export type AdminComparePageProps = {
  users: { id: number; username: string }[];
  dataA: ComparedPlayer | null;
  dataB: ComparedPlayer | null;
  selectedA: number | null;
  selectedB: number | null;
};

export default function AdminComparePage({
  users,
  dataA,
  dataB,
  selectedA,
  selectedB,
}: AdminComparePageProps) {
  const [playerA, setPlayerA] = useState<string>(selectedA ? String(selectedA) : '');
  const [playerB, setPlayerB] = useState<string>(selectedB ? String(selectedB) : '');

  return (
    <Page
      title="Player Comparison"
      icon="fa-code-compare"
      iconTone="text-accent"
      back="/admin"
      subtitle="Side-by-side operational and financial benchmarking between two player accounts."
    >
      <div className="space-y-6">
        {/* Selection Bar */}
        <Panel>
          <form method="get" action="/admin/compare" className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div className="flex-1 space-y-1">
              <label className="text-xs font-semibold text-muted">Player A</label>
              <select
                name="a"
                value={playerA}
                onChange={(e) => setPlayerA(e.target.value)}
                className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
              >
                <option value="">Select First Player…</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.username} (ID: {u.id})
                  </option>
                ))}
              </select>
            </div>

            <div className="flex-1 space-y-1">
              <label className="text-xs font-semibold text-muted">Player B</label>
              <select
                name="b"
                value={playerB}
                onChange={(e) => setPlayerB(e.target.value)}
                className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
              >
                <option value="">Select Second Player…</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.username} (ID: {u.id})
                  </option>
                ))}
              </select>
            </div>

            <Button
              type="submit"
              variant="primary"
              className="text-xs font-bold min-h-10 shrink-0"
            >
              <Icon name="fa-code-compare" className="mr-1.5" />
              Compare
            </Button>
          </form>
        </Panel>

        {/* Head-to-Head Comparison Table */}
        {dataA && dataB ? (
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-chart-simple" className="text-accent" />
                <span>Benchmark Telemetry</span>
              </div>
            }
          >
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="border-b border-border text-[11px] font-semibold text-muted">
                  <tr>
                    <th className="pb-3 w-1/3">Metric</th>
                    <th className="pb-3 w-1/3">
                      <a
                        href={`/admin/user/${dataA.id}`}
                        className="text-accent hover:underline text-sm font-bold"
                      >
                        {dataA.username}
                      </a>
                    </th>
                    <th className="pb-3 w-1/3">
                      <a
                        href={`/admin/user/${dataB.id}`}
                        className="text-accent hover:underline text-sm font-bold"
                      >
                        {dataB.username}
                      </a>
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/60">
                  <tr className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-medium text-muted">Cash Reserves</td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataA.cashFormatted}
                    </td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataB.cashFormatted}
                    </td>
                  </tr>
                  <tr className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-medium text-muted">Difficulty Mode</td>
                    <td className="py-2.5">
                      <span className="rounded bg-surface-raised px-2 py-0.5 font-semibold text-foreground uppercase text-[10px]">
                        {dataA.difficulty}
                      </span>
                    </td>
                    <td className="py-2.5">
                      <span className="rounded bg-surface-raised px-2 py-0.5 font-semibold text-foreground uppercase text-[10px]">
                        {dataB.difficulty}
                      </span>
                    </td>
                  </tr>
                  <tr className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-medium text-muted">Resort Map</td>
                    <td className="py-2.5 font-semibold text-foreground">{dataA.resort_map}</td>
                    <td className="py-2.5 font-semibold text-foreground">{dataB.resort_map}</td>
                  </tr>
                  <tr className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-medium text-muted">Active Staff</td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataA.staff} employees
                    </td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataB.staff} employees
                    </td>
                  </tr>
                  <tr className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-medium text-muted">Buildings Constructed</td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataA.buildings} structures
                    </td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataB.buildings} structures
                    </td>
                  </tr>
                  <tr className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-medium text-muted">Lifts & Slopes Built</td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataA.items} items
                    </td>
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {dataB.items} items
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </Panel>
        ) : (
          <div className="rounded-2xl border border-dashed border-border bg-surface p-10 text-center text-xs text-muted">
            Select two accounts above to benchmark resort performance, infrastructure, and financial balances.
          </div>
        )}
      </div>
    </Page>
  );
}
