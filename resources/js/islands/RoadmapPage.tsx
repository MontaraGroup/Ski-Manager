import { useState } from 'react';
import { AlertDialog, Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf, CsrfInput, Figure, Page, Panel } from '@/components/Page';
import { Select } from '@/components/Select';

export type RoadmapItem = {
  id: number;
  title: string;
  description: string;
  status: 'planned' | 'in_progress' | 'completed' | string;
  category: string;
  upvotes: number;
  author_name: string;
  author_id: number | null;
  voted: boolean;
  created_at: string;
};

export type RoadmapColumn = {
  status: string;
  title: string;
  description: string;
  icon: string;
  items: RoadmapItem[];
};

export type RoadmapPageProps = {
  isAdmin: boolean;
  stats: {
    totalItems: number;
    totalVotes: number;
  };
  columns: RoadmapColumn[];
};

export default function RoadmapPage({ isAdmin, stats, columns }: RoadmapPageProps) {
  const [cols, setCols] = useState(columns);
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [suggestOpen, setSuggestOpen] = useState(false);
  const [votingId, setVotingId] = useState<number | null>(null);

  // Optimistic upvote handler
  const handleVote = async (itemId: number) => {
    if (votingId !== null) return;
    setVotingId(itemId);

    // Optimistically toggle
    setCols((prev) =>
      prev.map((c) => ({
        ...c,
        items: c.items.map((it) => {
          if (it.id === itemId) {
            const nextVoted = !it.voted;
            return {
              ...it,
              voted: nextVoted,
              upvotes: it.upvotes + (nextVoted ? 1 : -1),
            };
          }
          return it;
        }),
      }))
    );

    try {
      const { field, token } = csrf();
      const fd = new FormData();
      fd.append(field, token);

      const res = await fetch(`/roadmap/vote/${itemId}`, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      });

      if (res.ok) {
        const data = await res.json();
        if (data && typeof data.upvotes === 'number') {
          setCols((prev) =>
            prev.map((c) => ({
              ...c,
              items: c.items.map((it) => {
                if (it.id === itemId) {
                  return {
                    ...it,
                    voted: Boolean(data.voted),
                    upvotes: data.upvotes,
                  };
                }
                return it;
              }),
            }))
          );
        }
      }
    } catch (err) {
      console.error('Vote failed', err);
    } finally {
      setVotingId(null);
    }
  };

  const completedCount =
    cols.find((c) => c.status === 'completed')?.items.length || 0;

  const categories = [
    { key: 'all', label: 'All features' },
    { key: 'gameplay', label: 'Gameplay' },
    { key: 'economy', label: 'Economy' },
    { key: 'quality_of_life', label: 'Quality of life' },
    { key: 'mobile', label: 'Mobile' },
  ];

  return (
    <Page
      title="Development Roadmap"
      icon="fa-compass"
      iconTone="text-primary"
      subtitle="Community-driven feature queue, live development status, and upcoming releases"
      width="max-w-6xl"
      actions={
        <Button
          variant="primary"
          size="sm"
          onPress={() => setSuggestOpen(true)}
        >
          <Icon name="fa-plus" className="mr-1.5" />
          Suggest feature
        </Button>
      }
    >
      {/* 3 Summary figures */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Figure
          label="Tracked features"
          value={stats.totalItems}
          note="Active roadmap items"
        />
        <Figure
          label="Community upvotes"
          value={stats.totalVotes.toLocaleString()}
          tone="text-accent"
          note="Cast across all suggestions"
        />
        <Figure
          label="Shipped & completed"
          value={completedCount}
          tone="text-success"
          note="Live in the game"
        />
      </div>

      {/* Category filter pills */}
      <div className="flex flex-wrap items-center gap-1.5">
        {categories.map((cat) => (
          <Button
            key={cat.key}
            type="button"
            size="sm"
            variant={selectedCategory === cat.key ? 'primary' : 'ghost'}
            onPress={() => setSelectedCategory(cat.key)}
            className="rounded-full px-3 py-1 text-xs"
          >
            {cat.label}
          </Button>
        ))}
      </div>

      {/* 3 Columns Kanban grid */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {cols.map((col) => {
          const filteredItems = col.items.filter(
            (it) => selectedCategory === 'all' || it.category === selectedCategory
          );

          return (
            <div key={col.status} className="flex flex-col gap-3">
              {/* Column header */}
              <div className="flex items-center justify-between border-b border-border pb-2">
                <div className="flex items-center gap-2">
                  <Icon
                    name={col.icon || 'fa-compass'}
                    className={
                      col.status === 'completed'
                        ? 'text-success'
                        : col.status === 'in_progress'
                        ? 'text-primary'
                        : 'text-muted'
                    }
                  />
                  <h2 className="font-bold text-sm">{col.title}</h2>
                  <span className="rounded-full bg-default px-2 py-0.5 text-[11px] font-semibold tabular-nums text-muted">
                    {filteredItems.length}
                  </span>
                </div>
              </div>

              {/* Items in column */}
              <div className="space-y-3">
                {filteredItems.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted">
                    No features in this column for selected filter.
                  </div>
                ) : (
                  filteredItems.map((item) => (
                    <Panel key={item.id} className="space-y-3">
                      <div className="flex items-start justify-between gap-2">
                        <h3 className="font-semibold text-sm leading-snug">{item.title}</h3>
                        <Chip size="sm" variant="secondary" className="shrink-0 text-[10px]">
                          {item.category.replace('_', ' ')}
                        </Chip>
                      </div>

                      {item.description && (
                        <p className="text-xs text-muted leading-relaxed">
                          {item.description}
                        </p>
                      )}

                      <div className="flex items-center justify-between border-t border-border pt-2 text-xs">
                        <div className="text-[11px] text-muted">
                          By <span className="font-medium text-foreground">{item.author_name}</span>
                        </div>

                        <div className="flex items-center gap-1.5">
                          <Button
                            type="button"
                            size="sm"
                            variant={item.voted ? 'primary' : 'ghost'}
                            onPress={() => handleVote(item.id)}
                            className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs tabular-nums"
                            aria-label={`Upvote ${item.title}`}
                          >
                            <Icon
                              name="fa-chevron-up"
                              className={`text-[10px] ${item.voted ? 'text-primary' : ''}`}
                            />
                            <span>{item.upvotes}</span>
                          </Button>

                          {isAdmin && (
                            <form action={`/roadmap/delete/${item.id}`} method="post" className="inline">
                              <CsrfInput />
                              <Button
                                type="submit"
                                size="sm"
                                variant="ghost"
                                isIconOnly
                                className="size-7 rounded text-muted hover:text-danger"
                                aria-label="Delete item"
                              >
                                <Icon name="fa-trash-can" className="text-xs" />
                              </Button>
                            </form>
                          )}
                        </div>
                      </div>
                    </Panel>
                  ))
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Suggest Feature Dialog */}
      <AlertDialog.Backdrop isOpen={suggestOpen} onOpenChange={setSuggestOpen} isKeyboardDismissDisabled={false}>
        <AlertDialog.Container>
          <AlertDialog.Dialog className="max-w-lg">
            <form action="/roadmap/suggest" method="post" className="space-y-4">
              <CsrfInput />
              <AlertDialog.Header>
                <AlertDialog.Heading>Suggest a new feature</AlertDialog.Heading>
              </AlertDialog.Header>
              <AlertDialog.Body className="space-y-3">
                <p className="text-xs text-muted">
                  Share your idea with the community. Other resort managers can upvote your proposal to prioritize development.
                </p>

                <div>
                  <label htmlFor="title" className="block text-xs font-medium text-foreground mb-1">
                    Feature title *
                  </label>
                  <input
                    type="text"
                    name="title"
                    id="title"
                    required
                    minLength={5}
                    maxLength={100}
                    placeholder="e.g. Helicopter Skiing Tours"
                    className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  />
                </div>

                <div>
                  <label htmlFor="category" className="block text-xs font-medium text-foreground mb-1">
                    Category
                  </label>
                  <Select
                    name="category"
                    id="category"
                    aria-label="Category"
                    defaultValue="gameplay"
                    options={[
                      { id: 'gameplay', label: 'Gameplay & Mountain' },
                      { id: 'economy', label: 'Economy & Commerce' },
                      { id: 'quality_of_life', label: 'Quality of Life' },
                      { id: 'mobile', label: 'Mobile Experience' },
                    ]}
                  />
                </div>

                <div>
                  <label htmlFor="description" className="block text-xs font-medium text-foreground mb-1">
                    Description *
                  </label>
                  <textarea
                    name="description"
                    id="description"
                    required
                    minLength={10}
                    maxLength={1000}
                    rows={4}
                    placeholder="Explain how this feature works and why it would benefit resort managers..."
                    className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  />
                </div>
              </AlertDialog.Body>
              <AlertDialog.Footer>
                <Button variant="tertiary" onPress={() => setSuggestOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" variant="primary">
                  Submit suggestion
                </Button>
              </AlertDialog.Footer>
            </form>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </Page>
  );
}
