/**
 * Which ParkCity map sizes have a WebP copy on this server (layouts/main.php sets the list on
 * <html> from ResortMap::webpSizes). The map images live only on the servers, so a copy may be missing.
 */
export function parkCityHasWebp(size: 'low' | 'med' | 'full'): boolean {
  if (typeof document === 'undefined') return false;
  return (document.documentElement.dataset.parkcityWebp ?? '').split(' ').includes(size);
}
