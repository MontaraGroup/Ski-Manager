export type Difficulty = 'green' | 'blue' | 'black' | 'double_black';

export const DIFFICULTY_LABEL: Record<Difficulty, string> = {
  green: 'Easy',
  blue: 'Intermediate',
  black: 'Advanced',
  double_black: 'Expert',
};

/** Trail-sign shapes (DESIGN.md): green circle, blue square, black diamond, double black diamond */
export function TrailSign({ difficulty }: { difficulty: Difficulty }) {
  const label = `${DIFFICULTY_LABEL[difficulty]} run`;
  if (difficulty === 'green') {
    return <span role="img" aria-label={label} className="inline-block size-3 shrink-0 rounded-full bg-[var(--sign-green)]" />;
  }
  if (difficulty === 'blue') {
    return <span role="img" aria-label={label} className="inline-block size-3 shrink-0 bg-[var(--sign-blue)]" />;
  }
  return (
    <span role="img" aria-label={label} className="inline-flex shrink-0 items-center gap-1 px-0.5">
      <span className="inline-block size-2.5 rotate-45 bg-foreground" />
      {difficulty === 'double_black' && <span className="inline-block size-2.5 rotate-45 bg-foreground" />}
    </span>
  );
}
