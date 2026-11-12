import { useState, type ReactNode } from 'react';
import { Button, Meter, Modal } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf, Page, Panel } from '@/components/Page';
import { ConfirmForm, Field } from './admin/ConfirmForm';

export type UserMeta = {
  id: number;
  username: string;
  active: boolean;
  state?: 'active' | 'banned' | 'unconfirmed';
  isOwner: boolean;
  createdFormatted: string;
  email: string;
};

export type FinanceMeta = {
  cashRaw: number;
  cashFormatted: string;
  reputation: number;
  difficulty: string;
  units: string;
  resortMap: string;
  resortOpen: boolean;
  resortName: string;
  dailyVisitors: string;
  lastActive: string;
};

export type GenepisMeta = {
  balance: number;
  balanceFormatted: string;
};

export type UserStats = {
  staffCount: number;
  buildingCount: number;
  itemCount: number;
  loanCount: number;
  achievementCount: number;
  facilityCount: number;
};

export type StaffItem = { id: number; role: string; name: string; salaryFormatted: string; morale: number };
export type ItemRow = { id: number; name: string; type: string; lengthFormatted: string; status: string };
export type BuildingRow = { id: number; name: string; type: string; level: number };
export type LoanRow = { id: number; type: string; remainingFormatted: string; daysRemaining: number };
export type AchievementRow = { key: string; name: string; claimed: boolean };
export type TutorialMeta = { step: number } | null;
export type LogEntry = { icon: string; message: string; game_day: number };
export type TransactionRow = {
  id: number;
  gameDay: number;
  category: string;
  description: string;
  amountFormatted: string;
  isExpense: boolean;
  when: string;
};
export type GenepisRow = { id: number; amountFormatted: string; isSpent: boolean; reason: string; when: string };
export type LoginRow = { id: number; when: string; success: boolean; ip: string };
export type NoteRow = { id: number; note: string; admin: string; when: string };

export type AdminUserPageProps = {
  user: UserMeta;
  finance: FinanceMeta;
  genepis: GenepisMeta;
  stats: UserStats;
  staff: StaffItem[];
  items: ItemRow[];
  buildings: BuildingRow[];
  loans: LoanRow[];
  achievements: AchievementRow[];
  tutorial: TutorialMeta;
  logs: LogEntry[];
  transactions: TransactionRow[];
  genepisLog: GenepisRow[];
  logins: LoginRow[];
  notes: NoteRow[];
  money?: MoneyFormat;
};

const fmt = (v: number) => v.toLocaleString('en-US');
export type MoneyFormat = { symbol: string; prefix: boolean };
/** Same layout as currency() in currency_helper.php: $1,000 or 1,000 € */
let moneyFmt: MoneyFormat = { symbol: '€', prefix: false };
const money = (v: number) =>
  `${v < 0 ? '−' : ''}${moneyFmt.prefix ? moneyFmt.symbol : ''}${fmt(Math.abs(v))}${moneyFmt.prefix ? '' : ' ' + moneyFmt.symbol}`;

/** POST a form body the way the CI4 CSRF filter expects; returns status and JSON message */
async function post(url: string, fields: Record<string, string>): Promise<{ ok: boolean; status: number; message: string }> {
  const { field, token } = csrf();
  const body = new FormData();
  body.set(field, token);
  for (const [k, v] of Object.entries(fields)) body.set(k, v);
  try {
    const res = await fetch(url, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok && data.success === true, status: res.status, message: data.message ?? '' };
  } catch {
    return { ok: false, status: 0, message: 'The request did not reach the server. Check your connection and try again.' };
  }
}

/** Cash or Génépis up or down by a signed amount, through the ledger, with a reason */
function AdjustDialog({ user, cash, genepis }: { user: UserMeta; cash: number; genepis: number }) {
  const [open, setOpen] = useState(false);
  const [currency, setCurrency] = useState<'cash' | 'genepis'>('cash');
  const [amount, setAmount] = useState('');
  const [reason, setReason] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const value = Number(amount);
  const valid = amount.trim() !== '' && Number.isInteger(value) && value !== 0;
  const current = currency === 'cash' ? cash : genepis;
  const show = (v: number) => (currency === 'cash' ? money(v) : `${fmt(v)} Génépis`);
  const after = current + value;

  let effect: ReactNode = 'Enter a whole number: positive to add, negative to take away.';
  if (valid) {
    effect =
      after < 0 ? (
        <>
          <Icon name="fa-circle-exclamation" className="mr-1 text-danger" />
          {user.username} has only {show(current)}, so the server will refuse to take {show(Math.abs(value))}.
        </>
      ) : (
        <>
          {value > 0 ? 'Adds' : 'Takes'} {show(Math.abs(value))} {value > 0 ? 'to' : 'from'} {user.username}'s{' '}
          {currency === 'cash' ? 'cash' : 'Génépis'}: {show(current)} becomes {show(after)}.
        </>
      );
  }

  const reset = () => {
    setAmount('');
    setReason('');
    setError('');
    setCurrency('cash');
  };

  const submit = async () => {
    if (!valid || !reason.trim()) {
      setError('Enter a non-zero whole amount and a reason.');
      return;
    }
    setBusy(true);
    setError('');
    const r = await post(`/admin/user/${user.id}/adjust`, { currency, amount: String(value), reason: reason.trim() });
    setBusy(false);
    if (r.ok) {
      window.location.reload();
      return;
    }
    setError(r.message || (r.status === 403 ? 'Your session expired. Reload the page and try again.' : `The server refused the change (HTTP ${r.status}).`));
  };

  return (
    <>
      <Button variant="primary" className="min-h-11 sm:min-h-9" onPress={() => setOpen(true)}>
        Adjust balance
      </Button>
      <Modal.Backdrop
        isOpen={open}
        onOpenChange={(o) => {
          setOpen(o);
          if (!o) reset();
        }}
        isDismissable
      >
        <Modal.Container placement="center" size="md">
          <Modal.Dialog aria-labelledby="adjust-title">
            <Modal.Header>
              <Modal.Heading id="adjust-title" className="text-lg font-semibold">
                Adjust {user.username}'s balance
              </Modal.Heading>
            </Modal.Header>
            <Modal.Body>
              <form
                id="adjust-form"
                className="flex flex-col gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  void submit();
                }}
              >
                <fieldset className="flex flex-col gap-1 text-sm">
                  <legend className="mb-1 font-medium">Currency</legend>
                  <div className="flex gap-2">
                    {(['cash', 'genepis'] as const).map((c) => (
                      <label
                        key={c}
                        className={`flex min-h-11 flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-focus sm:min-h-9 ${
                          currency === c ? 'border-accent bg-accent-soft font-semibold' : 'border-border'
                        }`}
                      >
                        <input type="radio" name="currency" value={c} checked={currency === c} onChange={() => setCurrency(c)} />
                        {c === 'cash' ? `Cash (${money(cash)})` : `Génépis (${fmt(genepis)})`}
                      </label>
                    ))}
                  </div>
                </fieldset>
                <Field
                  label="Amount"
                  hint="Positive adds, negative takes away. Example: -500"
                  type="number"
                  step={1}
                  inputMode="numeric"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  required
                />
                <Field
                  label="Reason"
                  hint="Saved in the player's history and the audit log. Up to 180 characters."
                  type="text"
                  maxLength={180}
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  required
                />
                <p aria-live="polite" className="rounded-lg bg-surface-secondary p-3 text-sm">
                  {effect}
                </p>
                {error && (
                  <p role="alert" className="text-sm">
                    <Icon name="fa-circle-exclamation" className="mr-1 text-danger" />
                    {error}
                  </p>
                )}
              </form>
            </Modal.Body>
            <Modal.Footer className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <Button
                variant="tertiary"
                className="min-h-11 sm:min-h-9"
                onPress={() => {
                  setOpen(false);
                  reset();
                }}
              >
                Cancel
              </Button>
              <Button type="submit" form="adjust-form" variant="primary" className="min-h-11 sm:min-h-9" isDisabled={busy}>
                {busy ? 'Saving…' : 'Apply change'}
              </Button>
            </Modal.Footer>
          </Modal.Dialog>
        </Modal.Container>
      </Modal.Backdrop>
    </>
  );
}

function NoteForm({ userId }: { userId: number }) {
  const [note, setNote] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  return (
    <form
      className="flex flex-col gap-2"
      onSubmit={async (e) => {
        e.preventDefault();
        if (!note.trim()) return setError('Write something first.');
        setBusy(true);
        const r = await post(`/admin/user/${userId}/note`, { note: note.trim() });
        setBusy(false);
        if (r.ok) window.location.reload();
        else setError(r.message || `The note was not saved (HTTP ${r.status}).`);
      }}
    >
      <label className="flex flex-col gap-1 text-sm">
        <span className="font-medium">New note</span>
        <textarea
          value={note}
          onChange={(e) => setNote(e.target.value)}
          maxLength={2000}
          rows={3}
          required
          className="w-full rounded-lg border border-border bg-field p-3 text-base text-field-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus sm:text-sm"
        />
        <span className="text-muted">Only admins see notes. {2000 - note.length} characters left.</span>
      </label>
      {error && (
        <p role="alert" className="text-sm">
          <Icon name="fa-circle-exclamation" className="mr-1 text-danger" />
          {error}
        </p>
      )}
      <Button type="submit" variant="secondary" className="min-h-11 self-start sm:min-h-9" isDisabled={busy}>
        {busy ? 'Saving…' : 'Add note'}
      </Button>
    </form>
  );
}

/** Key/value rows */
function Facts({ rows }: { rows: [string, ReactNode][] }) {
  return (
    <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
      {rows.map(([k, v]) => (
        <div key={k} className="contents">
          <dt className="text-muted">{k}</dt>
          <dd className="min-w-0 break-words">{v}</dd>
        </div>
      ))}
    </dl>
  );
}

function Table({ head, children, empty, rows }: { head: string[]; children: ReactNode; empty: string; rows: number }) {
  if (rows === 0) return <p className="py-4 text-sm text-muted">{empty}</p>;
  return (
    <div className="max-h-80 overflow-auto">
      <table className="w-full text-left text-sm">
        <thead className="sticky top-0 bg-surface text-muted">
          <tr className="border-b border-border">
            {head.map((h) => (
              <th key={h} scope="col" className="px-2 py-1.5 font-semibold">
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-border">{children}</tbody>
      </table>
    </div>
  );
}

const td = 'px-2 py-1.5 align-top';

/** active=0 is either a ban or an email that was never confirmed (Admin::accountState) */
function stateLabel(u: UserMeta): string {
  const state = u.state ?? (u.active ? 'active' : 'banned');
  return state === 'unconfirmed' ? 'Email not confirmed' : state === 'banned' ? 'Banned' : 'Active';
}
function stateNote(u: UserMeta): string {
  return stateLabel(u) === 'Active' ? '' : `, ${stateLabel(u).toLowerCase()}`;
}

export default function AdminUserPage({
  user,
  finance,
  genepis,
  stats,
  staff = [],
  items = [],
  buildings = [],
  loans = [],
  achievements = [],
  tutorial,
  logs = [],
  transactions = [],
  genepisLog = [],
  logins = [],
  notes = [],
  money: moneyFormat,
}: AdminUserPageProps) {
  if (moneyFormat) moneyFmt = moneyFormat;
  return (
    <Page
      title={user.username}
      icon="fa-user-gear"
      back="/admin"
      width="max-w-6xl"
      subtitle={`Player #${user.id}, joined ${user.createdFormatted}${stateNote(user)}`}
      actions={
        user.isOwner ? (
          <span className="rounded-full bg-warning/15 px-3 py-1 text-sm font-semibold">Owner account</span>
        ) : (
          <div className="flex flex-wrap items-center gap-2">
            {user.state === 'unconfirmed' ? null : user.active ? (
              <ConfirmForm
                action={`/admin/ban/${user.id}`}
                submitLabel="Ban"
                title={`Ban ${user.username}?`}
                describe={() => `${user.username} will not be able to log in until you unban them.`}
                confirmLabel="Ban"
                danger
              />
            ) : (
              <ConfirmForm
                action={`/admin/unban/${user.id}`}
                submitLabel="Unban"
                title={`Unban ${user.username}?`}
                describe={() => `${user.username} will be able to log in again.`}
                confirmLabel="Unban"
              />
            )}
            <ConfirmForm
              action={`/admin/reset/${user.id}`}
              submitLabel="Reset progress"
              title={`Reset ${user.username}'s progress?`}
              describe={() => `Removes ${user.username}'s lifts, slopes, staff, buildings and loans and puts cash back to the starting amount. This cannot be undone.`}
              confirmLabel="Reset everything"
              danger
            />
            <ConfirmForm
              action={`/admin/impersonate/${user.id}`}
              submitLabel="Log in as player"
              title={`Log in as ${user.username}?`}
              describe={() => `You leave your admin session and see the game as ${user.username} until you stop impersonating.`}
              confirmLabel="Log in as them"
            />
            <ConfirmForm
              action={`/admin/delete/${user.id}`}
              submitLabel="Delete"
              title={`Delete ${user.username}?`}
              describe={() => `Deletes ${user.username} and every record of their resort. This cannot be undone.`}
              confirmLabel="Delete for good"
              danger
            />
          </div>
        )
      }
    >
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Panel title="Balances" actions={<AdjustDialog user={user} cash={finance?.cashRaw ?? 0} genepis={genepis?.balance ?? 0} />}>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <div className="text-sm text-muted">Cash</div>
              <div className="text-2xl font-semibold tabular-nums">{finance?.cashFormatted ?? '$0'}</div>
            </div>
            <div>
              <div className="text-sm text-muted">Génépis</div>
              <div className="text-2xl font-semibold tabular-nums">{genepis?.balanceFormatted ?? '0'}</div>
            </div>
          </div>
          <details className="mt-4 text-sm">
            <summary className="flex min-h-11 cursor-pointer items-center font-medium sm:min-h-8">Set an exact balance instead</summary>
            <div className="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <ConfirmForm
                action="/admin/cash"
                className="flex flex-col gap-2"
                submitLabel="Set cash"
                title="Set cash?"
                describe={(d) => `${user.username}'s cash goes from ${finance?.cashFormatted} to ${money(Number(d.get('cash')))}. No transaction is recorded, only the audit log.`}
                confirmLabel="Set it"
              >
                <input type="hidden" name="user_id" value={user.id} />
                <Field label={`New cash (${moneyFmt.symbol})`} type="number" name="cash" defaultValue={finance?.cashRaw ?? 0} step={1} required />
                <Field label="Reason" type="text" name="reason" maxLength={180} required />
              </ConfirmForm>
              <ConfirmForm
                action="/admin/genepis"
                className="flex flex-col gap-2"
                submitLabel="Set Génépis"
                title="Set Génépis?"
                describe={(d) => `${user.username}'s Génépis go from ${genepis?.balanceFormatted} to ${fmt(Number(d.get('genepis')))}. No Génépis history row is written, only the audit log.`}
                confirmLabel="Set it"
              >
                <input type="hidden" name="user_id" value={user.id} />
                <Field label="New Génépis" type="number" name="genepis" defaultValue={genepis?.balance ?? 0} min={0} step={1} required />
                <Field label="Reason" type="text" name="reason" maxLength={180} required />
              </ConfirmForm>
            </div>
          </details>
        </Panel>

        <Panel title="Resort">
          <Facts
            rows={[
              ['Name', finance?.resortName || 'Not named yet'],
              ['Map', finance?.resortMap || 'None'],
              ['Difficulty', <span className="capitalize">{finance?.difficulty || 'standard'}</span>],
              ['Reputation', `${finance?.reputation ?? 0}%`],
              ['Status', finance?.resortOpen ? 'Open' : 'Closed'],
              ['Visitors per day', finance?.dailyVisitors ?? '0'],
              ['Last active', finance?.lastActive ?? 'never'],
              [
                'Built',
                `${stats.itemCount} lifts and slopes, ${stats.buildingCount} buildings, ${stats.facilityCount} facilities`,
              ],
              ['Staff and loans', `${stats.staffCount} staff, ${stats.loanCount} active loans`],
            ]}
          />
        </Panel>

        <Panel title="Account">
          <Facts
            rows={[
              ['Player ID', `#${user.id}`],
              ['Email', user.email],
              ['Joined', user.createdFormatted],
              ['Status', stateLabel(user)],
            ]}
          />
        </Panel>

        <Panel title={`Last ${logins.length} logins`}>
          <Table head={['Date', 'Result', 'IP address']} rows={logins.length} empty="No logins recorded.">
            {logins.map((l) => (
              <tr key={l.id}>
                <td className={`${td} whitespace-nowrap`}>{l.when}</td>
                <td className={td}>
                  <Icon name={l.success ? 'fa-check' : 'fa-xmark'} className={`mr-1 ${l.success ? 'text-success' : 'text-danger'}`} />
                  {l.success ? 'Succeeded' : 'Failed'}
                </td>
                <td className={`${td} tabular-nums`}>{l.ip}</td>
              </tr>
            ))}
          </Table>
        </Panel>
      </div>

      <Panel title="Admin notes">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <NoteForm userId={user.id} />
          {notes.length === 0 ? (
            <p className="text-sm text-muted">No notes on this player yet.</p>
          ) : (
            <ul className="flex max-h-80 flex-col gap-3 overflow-y-auto">
              {notes.map((nt) => (
                <li key={nt.id} className="rounded-lg border border-border p-3 text-sm">
                  <p className="whitespace-pre-wrap break-words">{nt.note}</p>
                  <p className="mt-1 text-muted">
                    {nt.admin}, {nt.when}
                  </p>
                </li>
              ))}
            </ul>
          )}
        </div>
      </Panel>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Panel title={`Last ${transactions.length} cash transactions`} className="min-w-0">
          <Table head={['When', 'What', 'Amount']} rows={transactions.length} empty="No cash transactions yet.">
            {transactions.map((t) => (
              <tr key={t.id}>
                <td className={`${td} whitespace-nowrap text-muted`}>
                  {t.when}
                  <br />
                  Day {t.gameDay}
                </td>
                <td className={`${td} break-words`}>
                  <span className="font-medium">{t.category}</span>
                  <br />
                  {t.description}
                </td>
                <td className={`${td} whitespace-nowrap text-right tabular-nums`}>{t.amountFormatted}</td>
              </tr>
            ))}
          </Table>
        </Panel>

        <Panel title={`Last ${genepisLog.length} Génépis changes`} className="min-w-0">
          <Table head={['When', 'Reason', 'Amount']} rows={genepisLog.length} empty="No Génépis history yet.">
            {genepisLog.map((g) => (
              <tr key={g.id}>
                <td className={`${td} whitespace-nowrap text-muted`}>{g.when}</td>
                <td className={`${td} break-words`}>{g.reason}</td>
                <td className={`${td} whitespace-nowrap text-right tabular-nums`}>{g.amountFormatted}</td>
              </tr>
            ))}
          </Table>
        </Panel>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Panel title={`Achievements completed (${achievements.length})`}>
          {achievements.length === 0 ? (
            <p className="text-sm text-muted">No achievements completed yet.</p>
          ) : (
            <ul className="flex flex-wrap gap-2">
              {achievements.map((a) => (
                <li key={a.key} className="rounded-lg border border-border px-2.5 py-1 text-sm">
                  {a.name}
                  <span className="ml-1.5 text-muted">{a.claimed ? '(reward claimed)' : '(reward not claimed)'}</span>
                </li>
              ))}
            </ul>
          )}
        </Panel>

        <Panel
          title="Tutorial"
          actions={
            tutorial && (
              <ConfirmForm
                action={`/admin/reset-tutorial/${user.id}`}
                submitLabel="Restart tutorial"
                title="Restart the tutorial?"
                describe={() => `${user.username} goes back to step 1 of the tutorial.`}
                confirmLabel="Restart"
              />
            )
          }
        >
          <p className="text-sm">{tutorial ? `On step ${tutorial.step}.` : 'Finished or never started.'}</p>
        </Panel>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Panel title={`Lifts and slopes (${items.length})`} className="min-w-0">
          <Table head={['Name', 'Type', 'Length', 'Status']} rows={items.length} empty="Nothing built yet.">
            {items.map((it) => (
              <tr key={it.id}>
                <td className={`${td} font-medium`}>{it.name}</td>
                <td className={`${td} capitalize text-muted`}>{it.type}</td>
                <td className={`${td} tabular-nums`}>{it.lengthFormatted}</td>
                <td className={`${td} capitalize`}>{it.status}</td>
              </tr>
            ))}
          </Table>
        </Panel>

        <Panel title={`Staff (${staff.length})`} className="min-w-0">
          <Table head={['Name', 'Role', 'Salary', 'Morale']} rows={staff.length} empty="No staff hired.">
            {staff.map((s) => (
              <tr key={s.id}>
                <td className={`${td} font-medium`}>{s.name}</td>
                <td className={`${td} capitalize text-muted`}>{s.role}</td>
                <td className={`${td} whitespace-nowrap tabular-nums`}>{s.salaryFormatted} a day</td>
                <td className={td}>
                  <Meter
                    value={Math.min(100, Math.max(0, s.morale))}
                    size="sm"
                    color={s.morale > 70 ? 'success' : s.morale > 40 ? 'warning' : 'danger'}
                    aria-label={`${s.name} morale`}
                    className="flex min-w-24 items-center gap-2"
                  >
                    <Meter.Track className="flex-1">
                      <Meter.Fill />
                    </Meter.Track>
                    <Meter.Output className="tabular-nums" />
                  </Meter>
                </td>
              </tr>
            ))}
          </Table>
        </Panel>

        <Panel title={`Buildings (${buildings.length})`} className="min-w-0">
          <Table head={['Name', 'Type', 'Level']} rows={buildings.length} empty="No buildings yet.">
            {buildings.map((b) => (
              <tr key={b.id}>
                <td className={`${td} font-medium`}>{b.name}</td>
                <td className={`${td} capitalize text-muted`}>{b.type}</td>
                <td className={td}>{b.level}</td>
              </tr>
            ))}
          </Table>
        </Panel>

        <Panel title={`Active loans (${loans.length})`} className="min-w-0">
          <Table head={['Loan', 'Left to pay', 'Days left']} rows={loans.length} empty="No active loans.">
            {loans.map((l) => (
              <tr key={l.id}>
                <td className={td}>{l.type}</td>
                <td className={`${td} tabular-nums`}>{l.remainingFormatted}</td>
                <td className={`${td} tabular-nums`}>{l.daysRemaining}</td>
              </tr>
            ))}
          </Table>
        </Panel>
      </div>

      <Panel title={`Activity (last ${logs.length})`}>
        {logs.length === 0 ? (
          <p className="text-sm text-muted">No activity recorded for this player.</p>
        ) : (
          <ul className="max-h-80 divide-y divide-border overflow-y-auto">
            {logs.map((log, i) => (
              <li key={i} className="flex items-start gap-3 py-2 text-sm">
                <Icon name={`fa-${log.icon || 'circle-dot'}`} className="w-5 pt-0.5 text-center text-muted" />
                <span className="min-w-0 flex-1 break-words">{log.message}</span>
                <span className="shrink-0 text-muted">Day {log.game_day}</span>
              </li>
            ))}
          </ul>
        )}
      </Panel>
    </Page>
  );
}
