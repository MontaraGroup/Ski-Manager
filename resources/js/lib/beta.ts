// Beta program (App\Libraries\Beta): the state the server sends and the API calls
import { api } from './api';

export type BetaFeature = {
  key: string;
  name: string;
  description: string;
  since: string | null;
  on: boolean;
  feedback: { rating: number; comment: string } | null;
};

export type BetaState = {
  member: boolean;
  eligible: boolean;
  onCount: number;
  features: BetaFeature[];
};

export const COMMENT_MAX = 500;

export const joinBeta = () => api<BetaState>('beta/join', { method: 'POST', body: {} });
export const leaveBeta = () => api<BetaState>('beta/leave', { method: 'POST', body: {} });
export const setBetaFeature = (key: string, on: boolean) =>
  api<BetaState>(`beta/feature/${encodeURIComponent(key)}`, { method: 'POST', body: { on } });
export const sendBetaFeedback = (key: string, rating: number, comment: string) =>
  api<BetaState>(`beta/feedback/${encodeURIComponent(key)}`, { method: 'POST', body: { rating, comment } });
