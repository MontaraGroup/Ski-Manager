import { Icon } from '@/components/Icon';
import { MenuButton } from '@/components/MenuButton';
import { isActive, type NavSection } from '@/lib/shell';

type Props = { path: string; nav: NavSection[]; isAdmin: boolean; bonusDue: boolean };

const TABS = [
  { href: '/dashboard', label: 'Dashboard', icon: 'fa-gauge-high' },
  { href: '/resort', label: 'Resort', icon: 'fa-mountain-sun' },
  { href: '/map', label: 'Map', icon: 'fa-map' },
  { href: '/finances', label: 'Finances', icon: 'fa-coins' },
];

/** Bottom tab bar on phones; "More" opens every page as a sheet */
export default function MobileDock({ path, nav, isAdmin, bonusDue }: Props) {
  const tab = 'flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs';
  return (
    <nav aria-label="Phone navigation" className="mobile-bottom-dock md:hidden">
      <ul className="grid grid-cols-5">
        {TABS.map((t) => (
          <li key={t.href}>
            <a href={t.href} aria-current={isActive(path, t.href) ? 'page' : undefined} className={`${tab} ${isActive(path, t.href) ? 'font-semibold text-accent-soft-foreground' : 'text-muted'}`}>
              <Icon name={t.icon} className="text-lg" />
              {t.label}
            </a>
          </li>
        ))}
        <li>
          <MenuButton
            label={bonusDue ? 'More pages, daily bonus ready' : 'More pages'}
            className={`${tab} h-auto w-full rounded-none text-muted`}
            placement="bottom"
            nav={nav}
            path={path}
            isAdmin={isAdmin}
          >
            <span className="relative">
              <Icon name="fa-bars" className="text-lg" />
              {bonusDue && <span aria-hidden="true" className="absolute -right-1.5 -top-1 size-2 rounded-full bg-warning" />}
            </span>
            More
          </MenuButton>
        </li>
      </ul>
    </nav>
  );
}
