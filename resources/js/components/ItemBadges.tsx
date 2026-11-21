/** Badges for the slope and lift tables on the resort page and a visited resort's tour page */

function difficultyTone(difficulty: string): string {
  switch (difficulty) {
    case 'green':
      return 'bg-success/15 text-success border-success/30';
    case 'red':
      return 'bg-danger/15 text-danger border-danger/30';
    case 'black':
    case 'double_black':
      return 'bg-neutral/20 text-foreground border-neutral/40';
    default:
      return 'bg-primary/15 text-primary border-primary/30';
  }
}

export function DifficultyBadge({ difficulty, label }: { difficulty: string; label: string }) {
  return (
    <span className={`px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider ${difficultyTone(difficulty)}`}>
      {label}
    </span>
  );
}

export function ConditionPct({ pct }: { pct: number }) {
  const tone = pct >= 70 ? 'text-success' : pct >= 40 ? 'text-warning' : 'text-danger';
  return <span className={`font-semibold tabular-nums ${tone}`}>{pct}%</span>;
}

/** Slopes show "broken" in the closed tone; lifts pass showBroken to show it in red */
export function ItemStatus({ status, showBroken = false }: { status: string; showBroken?: boolean }) {
  const tone =
    status === 'open'
      ? 'bg-success/20 text-success'
      : showBroken && status === 'broken'
      ? 'bg-danger/20 text-danger'
      : 'bg-muted/20 text-muted';
  return (
    <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${tone}`}>{status}</span>
  );
}
