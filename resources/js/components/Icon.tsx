/**
 * Font Awesome icon. The FA kit swaps each <i> for an <svg> in the DOM, which React can't
 * reconcile, so React only owns the wrapper span and the <i> lives in its innerHTML.
 */
export function Icon({ name, className = '', style = 'fa-solid' }: { name: string; className?: string; style?: string }) {
  const safe = /^[a-z0-9 -]+$/i.test(name) ? name : 'fa-circle';
  const classes = safe.includes('fa-solid') || safe.includes('fa-regular') || safe.includes('fa-brands') ? safe : `${style} ${safe}`;
  return (
    <span
      aria-hidden="true"
      className={`inline-flex shrink-0 items-center justify-center ${className}`}
      dangerouslySetInnerHTML={{ __html: `<i class="${classes} fa-fw"></i>` }}
    />
  );
}
