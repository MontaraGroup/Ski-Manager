import { Drawer } from '@heroui/react';
import { Icon } from './Icon';
import { isActive, type NavSection } from '@/lib/shell';

export type NavDrawerProps = {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  nav: NavSection[];
  path: string;
  isAdmin: boolean;
  placement: 'left' | 'bottom';
};

/**
 * Every game page, by section: the phone menu (dock "More") and the tablet menu (navbar
 * button). Loaded on first open (MenuButton), so pages don't download it up front.
 */
export default function NavDrawer({ isOpen, onOpenChange, nav, path, isAdmin, placement }: NavDrawerProps) {
  return (
      <Drawer.Backdrop isOpen={isOpen} onOpenChange={onOpenChange}>
        <Drawer.Content placement={placement}>
          <Drawer.Dialog aria-label="Resort menu" className="max-h-[85vh]">
            <Drawer.Header>
              <Drawer.Heading>Resort menu</Drawer.Heading>
              <Drawer.CloseTrigger aria-label="Close menu" />
            </Drawer.Header>
            <Drawer.Body className="flex flex-col gap-5 overflow-y-auto pb-6">
              <a href="/dashboard" className={itemClass(isActive(path, '/dashboard'), false)}>
                <Icon name="fa-gauge-high" className="text-accent" />
                Dashboard
              </a>
              {nav.map((section) => (
                <section key={section.name} aria-labelledby={`nav-${section.name}`}>
                  <h3 id={`nav-${section.name}`} className="mb-2 flex items-center gap-2 text-sm font-semibold text-muted">
                    <Icon name={section.icon} />
                    {section.name}
                  </h3>
                  <ul className="grid grid-cols-2 gap-2">
                    {section.items.map((item) => (
                      <li key={item.url}>
                        <a
                          href={item.href}
                          className={itemClass(isActive(path, item.url), item.locked || item.flagOff)}
                          {...(item.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                          title={item.locked ? 'Unlock it through achievements' : item.flagOff ? 'Switched off for players' : undefined}
                        >
                          <Icon name={item.icon} className="text-accent" />
                          <span className="truncate">{item.label}</span>
                          {item.beta && <BetaChip />}
                          {item.locked && <Icon name="fa-lock" className="ml-auto text-muted" />}
                          {item.bonusDue && <span className="ml-auto rounded-full bg-warning px-1.5 text-xs font-semibold text-warning-foreground">Due</span>}
                        </a>
                      </li>
                    ))}
                  </ul>
                </section>
              ))}
              {isAdmin && (
                <a href="/admin" className={itemClass(isActive(path, '/admin'), false)}>
                  <Icon name="fa-shield-halved" className="text-danger" />
                  Admin panel
                </a>
              )}
            </Drawer.Body>
          </Drawer.Dialog>
        </Drawer.Content>
      </Drawer.Backdrop>
  );
}

function itemClass(active: boolean, muted: boolean): string {
  return [
    'flex min-h-11 items-center gap-2 rounded-lg border px-3 text-sm',
    active ? 'border-accent bg-accent-soft font-semibold text-accent-soft-foreground' : 'border-border hover:bg-default',
    muted ? 'text-muted' : '',
  ].join(' ');
}

/** Marks a nav item the player sees because it's in beta */
function BetaChip() {
  return <span className="shrink-0 rounded-full bg-warning/15 px-2 text-xs font-semibold text-warning">Beta</span>;
}
