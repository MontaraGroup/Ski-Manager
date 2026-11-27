/**
 * PostHog "enriched flag analytics": a flag's Usage tab compares players who saw a feature with
 * players who used it, and replays can be filtered by either.
 *
 * - An element with data-ph-feature="<flag key>" sends $feature_view once it is half on screen,
 *   and $feature_interaction (plus the person property $feature_interaction/<flag>) the first
 *   time the player clicks, types in or submits something inside it.
 * - Code that has no element to mark (a popup) calls featureView() / featureInteraction().
 * Each is sent at most once per flag per page load. Without posthog nothing happens.
 */

type PostHog = { capture: (event: string, properties?: Record<string, unknown>) => void };

const viewed = new Set<string>();
const used = new Set<string>();

function ph(): PostHog | undefined {
  return (window as unknown as { posthog?: PostHog }).posthog;
}

export function featureView(flag: string): void {
  if (!flag || viewed.has(flag) || !ph()) return;
  viewed.add(flag);
  ph()!.capture('$feature_view', { feature_flag: flag });
}

export function featureInteraction(flag: string): void {
  if (!flag || used.has(flag) || !ph()) return;
  used.add(flag);
  featureView(flag);
  ph()!.capture('$feature_interaction', { feature_flag: flag, $set: { [`$feature_interaction/${flag}`]: true } });
}

let seen: IntersectionObserver | null = null;
const watched = new WeakSet<Element>();

function watch(root: ParentNode): void {
  root.querySelectorAll<HTMLElement>('[data-ph-feature]').forEach((el) => {
    if (watched.has(el)) return;
    watched.add(el);
    if (seen) seen.observe(el);
    else featureView(el.dataset.phFeature || '');
  });
}

function onUse(e: Event): void {
  const el = (e.target as Element | null)?.closest?.<HTMLElement>('[data-ph-feature]');
  if (el) featureInteraction(el.dataset.phFeature || '');
}

export function startFeatureTracking(): void {
  if (typeof document === 'undefined') return;
  if ('IntersectionObserver' in window) {
    seen = new IntersectionObserver((entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        featureView((entry.target as HTMLElement).dataset.phFeature || '');
        seen?.unobserve(entry.target);
      }
    }, { threshold: 0.5 });
  }
  watch(document);
  // Islands render after boot, so pick up marked elements as they appear
  new MutationObserver(() => watch(document)).observe(document.body, { childList: true, subtree: true });
  for (const type of ['click', 'input', 'submit']) document.addEventListener(type, onUse, true);
}
