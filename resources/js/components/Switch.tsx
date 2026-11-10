import { Switch as HeroSwitch, type SwitchRootProps } from '@heroui/react';
import type { ReactNode } from 'react';

export interface SwitchProps extends Omit<SwitchRootProps, 'children'> {
  children?: ReactNode;
  label?: ReactNode;
  defaultChecked?: boolean;
  placement?: 'start' | 'end';
}

export function Switch({
  children,
  label,
  className,
  defaultChecked,
  placement = 'start',
  ...props
}: SwitchProps) {
  const content = children ?? label;
  const isDefaultSelected = defaultChecked ?? props.defaultSelected;
  return (
    <HeroSwitch {...props} defaultSelected={isDefaultSelected} className={className}>
      <HeroSwitch.Content className={placement === 'end' ? 'w-full flex items-center justify-between cursor-pointer' : 'cursor-pointer'}>
        {placement === 'end' && content ? <span>{content}</span> : null}
        <HeroSwitch.Control>
          <HeroSwitch.Thumb />
        </HeroSwitch.Control>
        {placement === 'start' && content ? <span>{content}</span> : null}
      </HeroSwitch.Content>
    </HeroSwitch>
  );
}

export { SwitchGroup } from '@heroui/react';
