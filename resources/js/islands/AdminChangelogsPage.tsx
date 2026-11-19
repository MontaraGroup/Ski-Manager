import { useState } from 'react';
import { Button } from '@heroui/react';
import { Checkbox } from '@/components/Checkbox';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel, PostButton } from '@/components/Page';

export type AdminChangelogEntry = {
  id: number;
  version: string;
  title: string;
  content: string;
  published: boolean;
  created_at: string;
  dateFormatted: string;
};

export type AdminChangelogsPageProps = {
  entries: AdminChangelogEntry[];
};

export default function AdminChangelogsPage({ entries }: AdminChangelogsPageProps) {
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState({
    version: '',
    title: '',
    content: '',
    published: true,
  });

  const handleEdit = (entry: AdminChangelogEntry) => {
    setEditingId(entry.id);
    setForm({
      version: entry.version,
      title: entry.title,
      content: entry.content,
      published: entry.published,
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleCancelEdit = () => {
    setEditingId(null);
    setForm({
      version: '',
      title: '',
      content: '',
      published: true,
    });
  };

  return (
    <Page
      title="Changelog Manager"
      icon="fa-newspaper"
      iconTone="text-accent"
      back="/admin"
      subtitle="Draft and publish version release notes displayed in the public updates feed."
    >
      <div className="space-y-6">
        {/* New / Edit Changelog Form */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name={editingId ? 'fa-pen-to-square' : 'fa-plus'} className="text-accent" />
              <span>{editingId ? 'Edit Changelog Entry' : 'New Changelog Entry'}</span>
            </div>
          }
          actions={
            editingId && (
              <Button size="sm" variant="outline" onPress={handleCancelEdit} className="text-xs">
                Cancel Edit
              </Button>
            )
          }
        >
          <form action="/admin/changelogs/save" method="post" className="space-y-3">
            <CsrfInput />
            {editingId && <input type="hidden" name="id" value={editingId} />}

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Version Tag</label>
                <input
                  type="text"
                  name="version"
                  value={form.version}
                  onChange={(e) => setForm({ ...form, version: e.target.value })}
                  placeholder="e.g. 2.1.0"
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-mono font-bold text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Headline Title</label>
                <input
                  type="text"
                  name="title"
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="e.g. Resort Map Overhaul & Performance Update"
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold text-muted">
                Changes & Features (one per line)
              </label>
              <textarea
                name="content"
                value={form.content}
                onChange={(e) => setForm({ ...form, content: e.target.value })}
                rows={5}
                placeholder="- Added interactive canvas resort map viewer&#10;- Improved grooming fleet dispatch timings&#10;- Fixed snowmaking pipe freezing bugs"
                className="w-full rounded-xl border border-border bg-surface-raised p-3 text-xs font-mono text-foreground focus:border-accent focus:outline-hidden"
                required
              />
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 pt-1">
              <Checkbox
                name="published"
                value="1"
                isSelected={form.published}
                onChange={(checked) => setForm({ ...form, published: checked })}
                className="text-xs font-semibold text-foreground [&_label]:flex [&_label]:min-h-11 [&_label]:items-center sm:[&_label]:min-h-0"
              >
                Publish immediately to all players
              </Checkbox>

              <Button type="submit" variant="primary" className="text-xs font-bold min-h-11 sm:min-h-10">
                <Icon name="fa-floppy-disk" className="mr-1.5" />
                {editingId ? 'Update Changelog' : 'Save Changelog'}
              </Button>
            </div>
          </form>
        </Panel>

        {/* Existing Entries List */}
        <section className="space-y-3">
          <h2 className="flex items-center gap-2 text-sm font-bold text-foreground">
            <Icon name="fa-list-check" className="text-accent text-xs" />
            Published & Draft Releases ({entries.length})
          </h2>

          {entries.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-border bg-surface p-8 text-center text-xs text-muted">
              No changelog entries yet.
            </div>
          ) : (
            <div className="space-y-3">
              {entries.map((entry) => (
                <div
                  key={entry.id}
                  className="rounded-2xl border border-border bg-surface p-4 shadow-2xs space-y-2.5 transition-colors hover:border-border/80"
                >
                  <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 pb-2.5">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="rounded-lg bg-accent/15 px-2 py-0.5 font-mono text-xs font-bold text-accent">
                        {entry.version}
                      </span>
                      <h3 className="text-sm font-bold text-foreground">{entry.title}</h3>
                      {!entry.published && (
                        <span className="rounded bg-warning/15 px-1.5 py-0.5 text-[10px] font-bold text-warning uppercase">
                          Draft
                        </span>
                      )}
                    </div>

                    <div className="flex items-center gap-2 text-xs">
                      <span className="text-muted font-mono">{entry.dateFormatted}</span>

                      <Button
                        size="sm"
                        variant="outline"
                        onPress={() => handleEdit(entry)}
                        className="h-7 text-xs font-semibold"
                      >
                        <Icon name="fa-pen-to-square" className="text-[11px]" />
                        Edit
                      </Button>

                      <PostButton
                        action={`/admin/changelogs/delete/${entry.id}`}
                        variant="danger"
                        size="sm"
                        confirm={{
                          title: `Delete Version ${entry.version}?`,
                          body: `Are you sure you want to permanently remove "${entry.title}"?`,
                          confirmLabel: 'Delete Entry',
                        }}
                        className="h-7 text-xs"
                      >
                        <Icon name="fa-trash" className="text-[11px]" />
                      </PostButton>
                    </div>
                  </div>

                  <p className="whitespace-pre-line text-xs text-muted leading-relaxed font-mono">
                    {entry.content}
                  </p>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </Page>
  );
}
