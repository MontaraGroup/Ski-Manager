import { Checkbox as HeroCheckbox, type CheckboxRootProps } from '@heroui/react';
import type { ReactNode } from 'react';

export interface CheckboxProps extends Omit<CheckboxRootProps, 'children'> {
  children?: ReactNode;
  label?: ReactNode;
  defaultChecked?: boolean;
}

export function Checkbox({ children, label, className, defaultChecked, ...props }: CheckboxProps) {
  const content = children ?? label;
  const isDefaultSelected = defaultChecked ?? props.defaultSelected;
  return (
    <HeroCheckbox {...props} defaultSelected={isDefaultSelected} className={className}>
      <HeroCheckbox.Content className="cursor-pointer">
        <HeroCheckbox.Control>
          <HeroCheckbox.Indicator />
        </HeroCheckbox.Control>
        {content ? <span>{content}</span> : null}
      </HeroCheckbox.Content>
    </HeroCheckbox>
  );
}

export { CheckboxGroup } from '@heroui/react';
