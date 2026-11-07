// Season format vote (App\Libraries\SeasonVote): the shared copy and the API calls
import { api } from './api';

export type SeasonChoice = 'shared' | 'individual';

export type SeasonVoteState = {
  open: boolean;
  closesAt: string | null;
  closesLabel: string | null;
  myChoice: SeasonChoice | null;
  showPrompt: boolean;
  order: SeasonChoice[];
};

export type SeasonVoteResults = {
  votes: number;
  shared: { count: number; pct: number };
  individual: { count: number; pct: number };
  eligible: number;
  notVoted: number;
};

// Both descriptions are kept to about the same length so neither reads as the favored one
export const SEASON_OPTIONS: Record<SeasonChoice, { title: string; body: string }> = {
  shared: {
    title: 'Shared season (as now)',
    body: 'Everyone plays the same season on the same calendar: one game day per real day, the same weather, and a new season for all on the same date, when resorts restart. The leaderboard compares everyone\'s profit in that season. Tournaments and special events run on the same game days for everyone, and alliance members are always on the same day.',
  },
  individual: {
    title: 'Your own pace',
    body: 'Each resort would have its own season clock that moves as you play, so you could go faster or slower than other players. Weather and season restarts would follow your clock. Leaderboards, tournaments and alliances would have to compare players at different points, so how they work would change.',
  },
};

export const COMMENT_MAX = 500;

export function sendVote(choice: SeasonChoice, comment: string) {
  return api<SeasonVoteState>('season-vote', { method: 'POST', body: { choice, comment } });
}

export function sendSnooze() {
  return api<{ snoozedUntil: string }>('season-vote/snooze', { method: 'POST', body: {} });
}
