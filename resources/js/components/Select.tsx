import React, { type ReactNode } from 'react';
import { Select as HeroSelect, ListBox, type SelectRootProps } from '@heroui/react';

export interface SelectOption {
  id: string | number;
  label: ReactNode;
}

export interface SelectProps extends Omit<SelectRootProps<object>, 'children' | 'onChange'> {
  options?: SelectOption[];
  children?: ReactNode;
  value?: string | number;
  defaultValue?: string | number;
  onChange?: (value: string | number) => void;
  name?: string;
  placeholder?: string;
}

export function Select({
  options,
  children,
  value,
  defaultValue,
  onChange,
  className,
  ...props
}: SelectProps) {
  const items: React.ReactElement[] = [];

  if (options) {
    options.forEach((opt) => {
      items.push(
        <ListBox.Item id={String(opt.id)} key={String(opt.id)}>
          {opt.label}
        </ListBox.Item>
      );
    });
  } else if (children) {
    React.Children.forEach(children, (child) => {
      if (!React.isValidElement(child)) return;
      if (child.type === 'option') {
        const val = String((child.props as any).value ?? '');
        items.push(
          <ListBox.Item id={val} key={val}>
            {(child.props as any).children}
          </ListBox.Item>
        );
      } else {
        items.push(child as React.ReactElement);
      }
    });
  }

  const selectProps: any = { ...props };
  if (value !== undefined && value !== null) {
    selectProps.selectedKey = String(value);
  }
  if (defaultValue !== undefined && defaultValue !== null) {
    selectProps.defaultSelectedKey = String(defaultValue);
  }
  if (onChange) {
    selectProps.onSelectionChange = (key: any) => {
      onChange(key ?? '');
    };
  }

  return (
    <HeroSelect {...selectProps} className={className}>
      <HeroSelect.Trigger>
        <HeroSelect.Value />
        <HeroSelect.Indicator />
      </HeroSelect.Trigger>
      <HeroSelect.Popover>
        <ListBox>{items}</ListBox>
      </HeroSelect.Popover>
    </HeroSelect>
  );
}

export { ListBox, ListBoxItem } from '@heroui/react';
export { ComboBox } from '@heroui/react';
export { Dropdown, DropdownItem, DropdownMenu, DropdownPopover, DropdownTrigger, DropdownSection } from '@heroui/react';
