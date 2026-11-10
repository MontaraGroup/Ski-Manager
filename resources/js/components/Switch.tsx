import { Switch as HeroSwitch, type SwitchRootProps } from '@heroui/react';
import type { ReactNode } from 'react';

export interface SwitchProps extends Omit<SwitchRootProps, 'children'> {
  children?: ReactNode;
  label?: ReactNode;
  defaultChecked?: boolean;
}

export function Switch({ children, label, className, defaultChecked, ...props }: SwitchProps) {
  const content = children ?? label;
  const isDefaultSelected = defaultChecked ?? props.defaultSelected;
  return (
    <HeroSwitch {...props} defaultSelected={isDefaultSelected} className={className}>
      <HeroSwitch.Control>
        <HeroSwitch.Thumb />
      </HeroSwitch.Control>
      {content ? <HeroSwitch.Content>{content}</HeroSwitch.Content> : null}
    </HeroSwitch>
  );
}

export { SwitchGroup } from '@heroui/react';
