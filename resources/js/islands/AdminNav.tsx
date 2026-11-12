import { useEffect, useId, useRef, useState } from 'react';
import { Button, Drawer } from '@heroui/react';
import { Icon } from '@/components/Icon';

type NavLink = { href: string; label: string; icon: string; download?: boolean };
type NavGroup = { name: string; links: NavLink[] };

const GROUPS: NavGroup[] = [
  { name: 'Overview', links: [{ href: '/admin', label: 'Overview', icon: 'fa-gauge-high' }] },
  {
    name: 'Players',
    links: [
      { href: '/admin/compare', label: 'Compare players', icon: 'fa-code-compare' },
      { href: '/admin/suspicious', label: 'Suspicious balances', icon: 'fa-triangle-exclamation' },
      { href: '/admin/export-players', label: 'Export players (CSV)', icon: 'fa-download', download: true },
    ],
  },
  { name: 'Economy', links: [{ href: '/admin/economy', label: 'Economy', icon: 'fa-chart-line' }] },
  {
    name: 'Content',
    links: [
      { href: '/roadmap', label: 'Roadmap', icon: 'fa-map-signs' },
      { href: '/admin/changelogs', label: 'Changelogs', icon: 'fa-newspaper' },
      { href: '/admin/broadcast', label: 'Broadcast', icon: 'fa-bullhorn' },
      { href: '/admin/season-vote', label: 'Season vote', icon: 'fa-check-to-slot' },
    ],
  },
  { name: 'Support', links: [{ href: '/admin/support', label: 'Support inbox', icon: 'fa-headset' }] },
  {
    name: 'System',
    links: [
      { href: '/admin/features', label: 'Feature flags', icon: 'fa-toggle-on' },
      { href: '/admin/settings', label: 'Game settings', icon: 'fa-gear' },
      { href: '/admin/seasons', label: 'Seasons', icon: 'fa-calendar-plus' },
      { href: '/admin/errors', label: 'Error log', icon: 'fa-bug' },
      { href: '/admin/audit', label: 'Audit log', icon: 'fa-shield-halved' },
      { href: '/admin/activity', label: 'Activity log', icon: 'fa-clock-rotate-left' },
    ],
  },
];

/** /admin only matches itself; other links also match their sub-pages (/admin/support/12) */
function isCurrent(path: string, href: string): boolean {
  if (href === '/admin') return path === '/admin' || path === '/admin/';
  return path === href || path.startsWith(href + '/');
}

type Result = { id: number; username: string; email: string };

/** Player search: type a name, email or ID, arrows to move, Enter to open, Escape to close */
function PlayerSearch() {
  const id = useId();
  const listId = `${id}-list`;
  const [q, setQ] = useState('');
  const [results, setResults] = useState<Result[]>([]);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error'>('idle');
  const req = useRef(0);

  useEffect(() => {
    const term = q.trim();
    if (!term) {
      setResults([]);
      setStatus('idle');
      return;
    }
    const n = ++req.current;
    setStatus('loading');
    const t = setTimeout(async () => {
      try {
        const res = await fetch(`/admin/search?q=${encodeURIComponent(term)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        if (!res.ok) throw new Error(String(res.status));
        const data = (await res.json()) as { results: Result[] };
        if (n !== req.current) return;
        setResults(data.results ?? []);
        setActive(data.results?.length ? 0 : -1);
        setStatus('idle');
      } catch {
        if (n === req.current) setStatus('error');
      }
    }, 200);
    return () => clearTimeout(t);
  }, [q]);

  const go = (r: Result) => {
    window.location.href = `/admin/user/${r.id}`;
  };

  const showList = open && q.trim() !== '';

  return (
    <div className="relative">
      <label htmlFor={`${id}-input`} className="mb-1 block text-sm font-medium">
        Find a player
      </label>
      <input
        id={`${id}-input`}
        type="search"
        role="combobox"
        aria-expanded={showList}
        aria-controls={listId}
        aria-autocomplete="list"
        aria-activedescendant={showList && active >= 0 ? `${listId}-${active}` : undefined}
        autoComplete="off"
        placeholder="Name, email or ID"
        value={q}
        onChange={(e) => {
          setQ(e.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        onKeyDown={(e) => {
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            setOpen(true);
            setActive((a) => (results.length ? (a + 1) % results.length : -1));
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive((a) => (results.length ? (a - 1 + results.length) % results.length : -1));
          } else if (e.key === 'Enter') {
            if (showList && active >= 0 && results[active]) {
              e.preventDefault();
              go(results[active]);
            }
          } else if (e.key === 'Escape') {
            if (showList) {
              e.preventDefault();
              setOpen(false);
            }
          }
        }}
        className="min-h-11 w-full rounded-lg border border-border bg-field px-3 text-base text-field-foreground placeholder:text-field-placeholder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus sm:min-h-9 sm:text-sm"
      />
      <ul
        id={listId}
        role="listbox"
        aria-label="Matching players"
        hidden={!showList}
        className="absolute inset-x-0 top-full z-30 mt-1 max-h-72 overflow-y-auto rounded-lg border border-border bg-overlay p-1 shadow-lg"
      >
        {status === 'loading' && results.length === 0 && <li className="px-3 py-2 text-sm text-muted">Searching…</li>}
        {status === 'error' && <li className="px-3 py-2 text-sm">Search failed. Check your connection and type again.</li>}
        {status === 'idle' && results.length === 0 && <li className="px-3 py-2 text-sm text-muted">No player matches “{q.trim()}”.</li>}
        {results.map((r, i) => (
          <li
            key={r.id}
            id={`${listId}-${i}`}
            role="option"
            aria-selected={i === active}
            onMouseDown={(e) => e.preventDefault()}
            onClick={() => go(r)}
            onMouseEnter={() => setActive(i)}
            className={`flex min-h-11 cursor-pointer flex-col justify-center rounded-md px-3 py-1 text-sm sm:min-h-9 ${
              i === active ? 'bg-default ring-2 ring-focus ring-inset' : ''
            }`}
          >
            <span className="font-medium">
              {r.username || '(no username)'} <span className="font-normal text-muted">#{r.id}</span>
            </span>
            {r.email && <span className="truncate text-muted">{r.email}</span>}
          </li>
        ))}
      </ul>
    </div>
  );
}

function NavLinks({ path }: { path: string }) {
  const id = useId();
  return (
    <nav aria-label="Admin sections" className="flex flex-col gap-4">
      <PlayerSearch />
      {GROUPS.map((g) => (
        <section key={g.name} aria-labelledby={`${id}-${g.name}`}>
          <h2 id={`${id}-${g.name}`} className="mb-1 px-3 text-xs font-semibold uppercase tracking-wide text-muted">
            {g.name}
          </h2>
          <ul className="flex flex-col gap-0.5">
            {g.links.map((l) => {
              const current = isCurrent(path, l.href);
              return (
                <li key={l.href}>
                  <a
                    href={l.href}
                    aria-current={current ? 'page' : undefined}
                    {...(l.download ? { download: '' } : {})}
                    className={`flex min-h-11 items-center gap-2 rounded-lg border-l-4 px-3 text-sm sm:min-h-9 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${
                      current ? 'border-accent bg-accent-soft font-semibold text-accent-soft-foreground' : 'border-transparent hover:bg-default'
                    }`}
                  >
                    <Icon name={l.icon} className="w-4 text-center" />
                    {l.label}
                  </a>
                </li>
              );
            })}
          </ul>
        </section>
      ))}
    </nav>
  );
}

/** Admin navigation: a sidebar from lg up, a drawer behind a menu button on smaller screens */
export default function AdminNav() {
  const path = typeof window !== 'undefined' ? window.location.pathname.replace(/\/+$/, '') || '/' : '/admin';
  const [open, setOpen] = useState(false);
  const here = GROUPS.flatMap((g) => g.links).find((l) => isCurrent(path, l.href));

  return (
    <>
      <div className="flex items-center gap-2 px-4 pt-4 lg:hidden">
        <Button variant="secondary" className="min-h-11" onPress={() => setOpen(true)}>
          <Icon name="fa-bars" />
          Admin menu
        </Button>
        {here && <span className="truncate text-sm text-muted">{here.label}</span>}
      </div>
      <Drawer.Backdrop isOpen={open} onOpenChange={setOpen}>
        <Drawer.Content placement="left">
          <Drawer.Dialog aria-label="Admin menu" className="w-80 max-w-[85vw]">
            <Drawer.Header>
              <Drawer.Heading>Admin</Drawer.Heading>
              <Drawer.CloseTrigger aria-label="Close admin menu" />
            </Drawer.Header>
            <Drawer.Body className="overflow-y-auto pb-6">
              <NavLinks path={path} />
            </Drawer.Body>
          </Drawer.Dialog>
        </Drawer.Content>
      </Drawer.Backdrop>
      <aside className="hidden w-64 shrink-0 border-r border-border p-4 lg:block lg:sticky lg:top-0 lg:max-h-screen lg:overflow-y-auto">
        <NavLinks path={path} />
      </aside>
    </>
  );
}
