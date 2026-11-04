import { Suspense, lazy, useState, type ReactNode } from 'react';
import { Button } from '@heroui/react';
import type { NavDrawerProps } from './NavDrawer';

const NavDrawer = lazy(() => import('./NavDrawer'));

type Props = Omit<NavDrawerProps, 'isOpen' | 'onOpenChange'> & { label: string; className?: string; children: ReactNode };

/** Opens the resort menu; the drawer's code loads the first time it opens */
export function MenuButton({ label, className, children, ...drawer }: Props) {
  const [open, setOpen] = useState(false);
  const [used, setUsed] = useState(false);
  return (
    <>
      <Button
        isIconOnly={!className}
        variant="ghost"
        aria-label={label}
        className={className}
        onPress={() => { setUsed(true); setOpen(true); }}
      >
        {children}
      </Button>
      {used && (
        <Suspense fallback={null}>
          <NavDrawer {...drawer} isOpen={open} onOpenChange={setOpen} />
        </Suspense>
      )}
    </>
  );
}
