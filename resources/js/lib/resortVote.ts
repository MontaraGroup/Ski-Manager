/** Shapes from App\Libraries\ResortVote (area(), summary(), tally(), page()) */
export type SkiArea = {
  id: number;
  name: string;
  place: string;
  locality: string;
  runs: number;
  lifts: number;
  verticalM: number | null;
  website: string;
};

export type TallyRow = SkiArea & { votes: number; pct: number };

export type VoteRound = {
  id: number;
  month: string;
  label: string;
  open: boolean;
  closesLabel: string;
  closedLabel: string | null;
  votes: number;
  winner: SkiArea | null;
  winnerNote: string | null;
  roadmapItemId: number | null;
  votersNotified: boolean;
};

export type ResortVoteState = {
  round: VoteRound;
  canVote: boolean;
  myPick: SkiArea | null;
  top: TallyRow[];
  past: VoteRound[];
  candidateCount: number;
  attribution: string;
};

/** "48 runs, 12 lifts, 920 m vertical", leaving out what the map data doesn't have */
export function areaFacts(a: SkiArea): string {
  const parts: string[] = [];
  if (a.runs) parts.push(`${a.runs} ${a.runs === 1 ? 'run' : 'runs'}`);
  if (a.lifts) parts.push(`${a.lifts} ${a.lifts === 1 ? 'lift' : 'lifts'}`);
  if (a.verticalM) parts.push(`${a.verticalM.toLocaleString()} m vertical`);
  return parts.join(', ');
}
