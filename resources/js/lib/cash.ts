/**
 * Broadcasts user cash balance updates across islands (such as ResortMapPage to StatsBar).
 */

export const CASH_CHANGED_EVENT = 'cash-changed';

export type CashChangedDetail = { cash: number } | number;

export function emitCashChanged(cash: number): void {
  window.dispatchEvent(
    new CustomEvent(CASH_CHANGED_EVENT, {
      detail: { cash },
    })
  );
}

export function onCashChange(fn: (cash: number) => void): () => void {
  const listener = (event: Event) => {
    const detail = (event as CustomEvent<CashChangedDetail>).detail;
    if (typeof detail === 'number') {
      fn(detail);
    } else if (detail && typeof detail.cash === 'number') {
      fn(detail.cash);
    }
  };
  window.addEventListener(CASH_CHANGED_EVENT, listener);
  return () => window.removeEventListener(CASH_CHANGED_EVENT, listener);
}
