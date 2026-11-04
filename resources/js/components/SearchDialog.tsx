import { useMemo, useState } from 'react';
import { Modal, SearchField } from '@heroui/react';
import { Icon } from './Icon';
import type { ShellProps } from '@/lib/shell';

/** Find a game page by name. Enter opens the first match */
export default function SearchDialog({ pages, isOpen, onOpenChange }: { pages: ShellProps['search']; isOpen: boolean; onOpenChange: (open: boolean) => void }) {
  const [query, setQuery] = useState('');
  const results = useMemo(() => {
    const q = query.trim().toLowerCase();
    return q ? pages.filter((p) => p.name.toLowerCase().includes(q)).slice(0, 12) : [];
  }, [pages, query]);

  return (
      <Modal.Backdrop isOpen={isOpen} onOpenChange={onOpenChange}>
        <Modal.Container placement="top" size="md">
          <Modal.Dialog aria-label="Search pages">
            <Modal.Body className="flex flex-col gap-3 p-4">
              <SearchField
                aria-label="Search pages and features"
                value={query}
                onChange={setQuery}
                autoFocus
                onSubmit={() => { if (results[0]) window.location.assign(results[0].url); }}
              >
                <SearchField.Group>
                  <SearchField.SearchIcon />
                  <SearchField.Input placeholder="Search pages, features, settings" />
                  <SearchField.ClearButton />
                </SearchField.Group>
              </SearchField>
              {query.trim() === '' ? (
                <p className="py-4 text-center text-sm text-muted">Type a page name, for example "staff" or "bank".</p>
              ) : results.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted">No page matches "{query.trim()}".</p>
              ) : (
                <ul className="flex max-h-80 flex-col overflow-y-auto">
                  {results.map((p) => (
                    <li key={p.url}>
                      <a href={p.url} className="flex min-h-11 items-center gap-3 rounded-lg px-2 text-sm hover:bg-default">
                        <Icon name={p.icon} className="text-muted" />
                        {p.name}
                      </a>
                    </li>
                  ))}
                </ul>
              )}
            </Modal.Body>
          </Modal.Dialog>
        </Modal.Container>
      </Modal.Backdrop>
  );
}
