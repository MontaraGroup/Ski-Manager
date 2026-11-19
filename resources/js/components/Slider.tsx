import { Slider as HeroSlider, type SliderRootProps } from '@heroui/react';
import type { ReactNode } from 'react';

export interface SliderProps extends Omit<SliderRootProps, 'children'> {
  label?: ReactNode;
  showOutput?: boolean;
  name?: string;
}

export function Slider({ label, showOutput = true, className, name, ...props }: SliderProps) {
  const hiddenValue = Array.isArray(props.value)
    ? props.value[0]
    : props.value ?? (Array.isArray(props.defaultValue) ? props.defaultValue[0] : props.defaultValue ?? '');

  return (
    <HeroSlider {...props} className={className}>
      {name ? <input type="hidden" name={name} value={hiddenValue} /> : null}
      {showOutput ? <HeroSlider.Output /> : null}
      <HeroSlider.Track>
        <HeroSlider.Fill />
        <HeroSlider.Thumb />
      </HeroSlider.Track>
    </HeroSlider>
  );
}

export { Slider as HeroSlider } from '@heroui/react';
