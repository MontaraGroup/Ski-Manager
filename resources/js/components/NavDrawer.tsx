import { Suspense, lazy, useState } from 'react';
import { Button, Disclosure, Drawer } from '@heroui/react';
import { Icon } from './Icon';
import { currentSection, isActive, itemText, type NavItem, type NavSection, type PublicLink, type ShellProps } from '@/lib/shell';

const SearchDialog = lazy(() => import('./SearchDialog'));

export type NavDrawerProps = {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  /** The game menu; empty for visitors */
  nav: NavSection[];
  /** Account pages (Settings, Support…); empty for visitors */
  account: NavItem[];
  /** Visitors' public links; empty for players */
  publicNav: PublicLink[];
  /** Pages the search field finds; empty hides it */
  search: ShellProps['search'];
  path: string;
  isAdmin: boolean;
  placement: 'left' | 'bottom';
};

/**
 * The menu as a drawer: the phone menu (dock "More") and the tablet menu (navbar button) for players,
 * and the visitors' menu on small screens. Loaded on first open (MenuButton), so pages don't download it.
 */
export default function NavDrawer({ isOpen, onOpenChange, nav, account, publicNav, search, path, isAdmin, placement }: NavDrawerProps) {
  const isPlayer = nav.length > 0;
  const [searchOpen, setSearchOpen] = useState(false);
  // The section holding this page starts open; on other pages the first one does
  const openSection = currentSection(nav, path) ?? nav[0]?.name;

  return (
    <Drawer.Backdrop isOpen={isOpen} onOpenChange={onOpenChange}>
      <Drawer.Content placement={placement}>
        <Drawer.Dialog aria-label={isPlayer ? 'Resort menu' : 'Menu'} className="max-h-[85vh]">
          <Drawer.Header>
            <Drawer.Heading>{isPlayer ? 'Resort menu' : 'Menu'}</Drawer.Heading>
            <Drawer.CloseTrigger aria-label="Close menu" />
          </Drawer.Header>
          <Drawer.Body className="flex flex-col gap-4 overflow-y-auto pb-6">
            {search.length > 0 && (
              <Button variant="secondary" className="min-h-11 w-full justify-start" onPress={() => setSearchOpen(true)}>
                <Icon name="fa-magnifying-glass" />
                Search pages
              </Button>
            )}

            {isPlayer ? (
              <>
                <a href="/dashboard" className={itemClass(isActive(path, '/dashboard'), false)} aria-current={isActive(path, '/dashboard') ? 'page' : undefined}>
                  <Icon name="fa-gauge-high" className="text-accent" />
                  Dashboard
                </a>
                <div className="flex flex-col gap-2">
                  {nav.map((section) => (
                    <Disclosure key={section.name} defaultExpanded={section.name === openSection} className="rounded-xl border border-border">
                      <Disclosure.Heading>
                        <Disclosure.Trigger className="flex min-h-11 w-full items-center gap-2 px-3 text-sm font-semibold">
                          <Icon name={section.icon} className="text-muted" />
                          <span className="flex-1 text-left">{section.name}</span>
                          <Disclosure.Indicator />
                        </Disclosure.Trigger>
                      </Disclosure.Heading>
                      <Disclosure.Content>
                        <ItemGrid items={section.items} path={path} />
                      </Disclosure.Content>
                    </Disclosure>
                  ))}
                </div>
                {(account.length > 0 || isAdmin) && (
                  <section aria-labelledby="nav-account">
                    <h3 id="nav-account" className="mb-2 flex items-center gap-2 text-sm font-semibold text-muted">
                      <Icon name="fa-circle-user" />
                      Account
                    </h3>
                    <ItemGrid items={account} path={path} flush />
                    {isAdmin && (
                      <a href="/admin" className={`${itemClass(isActive(path, '/admin'), false)} mt-2`} aria-current={isActive(path, '/admin') ? 'page' : undefined}>
                        <Icon name="fa-user-shield" className="text-danger" />
                        Admin panel
                      </a>
                    )}
                  </section>
                )}
              </>
            ) : (
              <ul className="flex flex-col gap-1">
                {publicNav.map((l) => (
                  <li key={l.url}>
                    <a href={l.href} className={itemClass(isActive(path, l.url), false)} aria-current={isActive(path, l.url) ? 'page' : undefined}>
                      <Icon name={l.icon} className="text-accent" />
                      {l.label}
                    </a>
                  </li>
                ))}
              </ul>
            )}
          </Drawer.Body>
        </Drawer.Dialog>
      </Drawer.Content>
      {searchOpen && (
        <Suspense fallback={null}>
          <SearchDialog pages={search} isOpen={searchOpen} onOpenChange={setSearchOpen} />
        </Suspense>
      )}
    </Drawer.Backdrop>
  );
}

/** Pages in one column, or two once the drawer is at least 24rem wide (container query), so names are never cut off */
function ItemGrid({ items, path, flush }: { items: NavItem[]; path: string; flush?: boolean }) {
  return (
    <div className={`@container ${flush ? '' : 'px-3 pb-3'}`}>
    <ul className="grid grid-cols-1 gap-2 @sm:grid-cols-2">
      {items.map((item) => (
        <li key={item.url}>
          <a
            href={item.href}
            aria-label={itemText(item)}
            aria-current={isActive(path, item.url) ? 'page' : undefined}
            className={itemClass(isActive(path, item.url), item.locked || item.flagOff)}
            {...(item.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
          >
            <Icon name={item.icon} className="text-accent" />
            <span className="line-clamp-2 min-w-0 flex-1 leading-tight">{item.label}</span>
            {item.beta && <span aria-hidden="true" className="shrink-0 rounded-full bg-warning/15 px-1.5 text-xs font-semibold text-warning">Beta</span>}
            {item.locked && <Icon name="fa-lock" className="shrink-0 text-xs text-muted" />}
            {item.bonusDue && <span aria-hidden="true" className="shrink-0 rounded-full bg-warning px-1.5 text-xs font-semibold text-warning-foreground">Due</span>}
            {item.isNew && <span aria-hidden="true" className="shrink-0 rounded-full bg-accent px-1.5 text-xs font-semibold text-accent-foreground">New</span>}
            {item.external && <Icon name="fa-arrow-up-right-from-square" className="shrink-0 text-xs text-muted" />}
          </a>
        </li>
      ))}
    </ul>
    </div>
  );
}

function itemClass(active: boolean, muted: boolean): string {
  return [
    'flex min-h-11 items-center gap-2 rounded-lg border px-2.5 py-1.5 text-sm',
    active ? 'border-accent bg-accent-soft font-semibold text-accent-soft-foreground' : 'border-border hover:bg-default',
    muted ? 'text-muted' : '',
  ].join(' ');
}
