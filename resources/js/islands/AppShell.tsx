import { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Dropdown, Popover, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { MenuButton } from '@/components/MenuButton';
import { useNotifications } from '@/lib/notifications';
import { applyTheme, currentTheme, isActive, type Flash, type ShellProps, type Theme } from '@/lib/shell';

/** The in-game and public navbar: menus, search, theme, notifications, account or auth actions */
export default function AppShell(props: ShellProps) {
  useFlashToasts(props.flash);

  const isLoggedIn = Boolean(props.user && props.user.username);

  return (
    <header className="border-b border-border bg-background site-nav">
      <nav aria-label="Main navigation" className="mx-auto flex min-h-14 max-w-7xl items-center gap-1 px-2 sm:px-4">
        <div className="lg:hidden">
          <MenuButton
            isIconOnly
            className="size-11 sm:size-9"
            label="Open the resort menu"
            placement="left"
            nav={props.nav}
            path={props.path}
            isAdmin={props.user?.isAdmin ?? false}
          >
            <Icon name="fa-bars" />
          </MenuButton>
        </div>
        <Brand />
        <DesktopMenus {...props} />
        <div className="ml-auto flex items-center gap-1">
          {isLoggedIn && <Search pages={props.search} />}
          <ThemeToggle />
          {isLoggedIn ? (
            <>
              {props.notifications && <Notifications initial={props.notifications} />}
              <AccountMenu username={props.user!.username} />
            </>
          ) : (
            <div className="flex items-center gap-1.5 ml-1">
              <a
                href="/login"
                className="flex min-h-11 sm:min-h-9 items-center justify-center rounded-lg px-3 text-xs sm:text-sm font-medium hover:bg-default transition-colors"
              >
                Log in
              </a>
              <a
                href="/register"
                className="flex min-h-11 sm:min-h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs sm:text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm"
              >
                Play free
              </a>
            </div>
          )}
        </div>
      </nav>
      {props.seasonVote?.showPrompt && (
        <Suspense fallback={null}>
          <SeasonVoteDialog state={props.seasonVote} />
        </Suspense>
      )}
    </header>
  );
}

function useFlashToasts(flash: Flash) {
  useEffect(() => {
    if (flash.success) toast.success(flash.success);
    if (flash.warning) toast.warning(flash.warning, { timeout: 0 });
    // Errors stay until dismissed, so a player can read them at their own pace
    if (flash.error) toast.danger(flash.error, { timeout: 0 });
    if (flash.errors?.length) toast.danger(flash.errors.length === 1 ? flash.errors[0] : 'Please fix these:', {
      timeout: 0,
      description: flash.errors.length > 1 ? flash.errors.join(' ') : undefined,
    });
  }, [flash]);
}

function Brand() {
  return (
    <a href="/" className="brand flex min-h-11 items-center gap-2 rounded-lg px-2 font-semibold" aria-label="Ski Manager home">
      <svg viewBox="0 0 32 32" aria-hidden="true" className="brand-mark size-7 shrink-0">
        <rect width="32" height="32" rx="6" fill="#1d232b" />
        <path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0" />
        <path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35" />
        <path d="M20 9 L19 25" stroke="#2f6fd6" strokeWidth="1.6" strokeLinecap="round" />
      </svg>
      <span className="brand-name hidden text-lg tracking-tight sm:inline">Ski Manager</span>
    </a>
  );
}

function DesktopMenus({ nav, path, user }: ShellProps) {
  const link = (href: string, label: string, extra = '') => (
    <a
      href={href}
      aria-current={isActive(path, href) ? 'page' : undefined}
      className={`flex min-h-9 items-center rounded-lg px-3 text-sm font-medium hover:bg-default ${isActive(path, href) ? 'bg-default font-semibold text-accent-soft-foreground' : ''} ${extra}`}
    >
      {label}
    </a>
  );
  return (
    <div className="hidden items-center gap-1 lg:flex">
      {link('/', 'Home')}
      {link('/dashboard', 'Dashboard')}
      {nav.map((section) => (
        <Dropdown key={section.name}>
          <Button variant="ghost" size="sm" className={section.items.some((i) => isActive(path, i.url)) ? 'bg-default font-semibold text-accent-soft-foreground' : ''}>
            {section.name}
            <Icon name="fa-chevron-down" className="text-xs text-muted" />
          </Button>
          <Dropdown.Popover placement="bottom start">
            <Dropdown.Menu aria-label={section.name}>
              {section.items.map((item) => (
                <Dropdown.Item
                  key={item.url}
                  id={item.url}
                  href={item.href}
                  textValue={item.label}
                  {...(item.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                  className={item.locked || item.flagOff ? 'text-muted' : ''}
                >
                  <Icon name={item.icon} className="text-accent" />
                  <span className="flex-1">{item.label}</span>
                  {item.locked && <Icon name="fa-lock" className="text-muted" />}
                  {item.bonusDue && <span className="rounded-full bg-warning px-1.5 text-xs font-semibold text-warning-foreground">Due</span>}
                </Dropdown.Item>
              ))}
            </Dropdown.Menu>
          </Dropdown.Popover>
        </Dropdown>
      ))}
      {user?.isAdmin && link('/admin', 'Admin', 'text-danger')}
    </div>
  );
}

const SearchDialog = lazy(() => import('@/components/SearchDialog'));
const SeasonVoteDialog = lazy(() => import('@/components/SeasonVoteDialog'));

/** The search dialog's code loads the first time it opens */
function Search({ pages }: { pages: ShellProps['search'] }) {
  const [open, setOpen] = useState(false);
  const [used, setUsed] = useState(false);
  return (
    <>
      <Button
        isIconOnly
        variant="ghost"
        className="size-11 sm:size-9"
        aria-label="Search pages"
        onPress={() => { setUsed(true); setOpen(true); }}
      >
        <Icon name="fa-magnifying-glass" />
      </Button>
      {used && (
        <Suspense fallback={null}>
          <SearchDialog pages={pages} isOpen={open} onOpenChange={setOpen} />
        </Suspense>
      )}
    </>
  );
}

function ThemeToggle() {
  const [theme, setTheme] = useState<Theme>(currentTheme);
  const next: Theme = theme === 'winter' ? 'carboncloud' : 'winter';
  return (
    <Button
      isIconOnly
      variant="ghost"
      className="size-11 sm:size-9"
      aria-label={theme === 'winter' ? 'Switch to the dark theme' : 'Switch to the light theme'}
      onPress={() => { applyTheme(next); setTheme(next); }}
    >
      <Icon name={theme === 'winter' ? 'fa-moon' : 'fa-sun'} />
    </Button>
  );
}

function Notifications({ initial }: { initial: NonNullable<ShellProps['notifications']> }) {
  const { unread, latest, markAllRead } = useNotifications(initial);
  const label = unread > 0 ? `Notifications, ${unread} unread` : 'Notifications';
  return (
    <Popover>
      <Button isIconOnly variant="ghost" aria-label={label} className="relative size-11 sm:size-9">
        <Icon name="fa-bell" />
        {unread > 0 && (
          <span aria-hidden="true" className="absolute right-0.5 top-0.5 min-w-4 rounded-full bg-danger px-1 text-center text-xs font-semibold leading-4 text-danger-foreground">
            {unread > 9 ? '9+' : unread}
          </span>
        )}
      </Button>
      <Popover.Content placement="bottom end" className="w-80">
        <Popover.Dialog aria-label="Notifications" className="flex flex-col gap-2 p-3">
          <div className="flex items-center justify-between">
            <Popover.Heading className="text-sm font-semibold">Notifications</Popover.Heading>
            {unread > 0 && (
              <Button size="sm" variant="ghost" onPress={() => markAllRead().catch((e: Error) => toast.danger(e.message))}>
                Mark all read
              </Button>
            )}
          </div>
          {latest.length === 0 ? (
            <p className="py-4 text-center text-sm text-muted">No notifications yet. Weather, events and staff news show up here.</p>
          ) : (
            <ul className="flex max-h-72 flex-col overflow-y-auto">
              {latest.map((n) => (
                <li key={n.id}>
                  <a href={`/notifications/open/${n.id}`} className="flex items-start gap-2 rounded-lg p-2 hover:bg-default">
                    <Icon name={n.icon} className={`mt-0.5 ${n.isRead ? 'text-muted' : 'text-accent'}`} />
                    <span className="min-w-0 flex-1">
                      <span className={`block truncate text-sm ${n.isRead ? '' : 'font-semibold'}`}>{n.title}</span>
                      <span className="block truncate text-xs text-muted">{n.message}</span>
                    </span>
                    {!n.isRead && <span className="sr-only">Unread</span>}
                  </a>
                </li>
              ))}
            </ul>
          )}
          <a href="/notifications" className="rounded-lg px-2 py-2 text-center text-sm text-accent-soft-foreground hover:bg-default">
            View all notifications
          </a>
        </Popover.Dialog>
      </Popover.Content>
    </Popover>
  );
}

function AccountMenu({ username }: { username: string }) {
  return (
    <Dropdown>
      <Button variant="ghost" size="sm" aria-label={`Account menu for ${username}`}>
        <Icon name="fa-circle-user" />
        <span className="hidden max-w-32 truncate sm:inline">{username}</span>
      </Button>
      <Dropdown.Popover placement="bottom end">
        <Dropdown.Menu aria-label="Account">
          <Dropdown.Item id="dashboard" href="/dashboard" textValue="Dashboard"><Icon name="fa-gauge-high" />Dashboard</Dropdown.Item>
          <Dropdown.Item id="settings" href="/settings" textValue="Settings"><Icon name="fa-gear" />Settings</Dropdown.Item>
          <Dropdown.Item id="logout" href="/logout" textValue="Log out" className="text-danger"><Icon name="fa-right-from-bracket" />Log out</Dropdown.Item>
        </Dropdown.Menu>
      </Dropdown.Popover>
    </Dropdown>
  );
}
