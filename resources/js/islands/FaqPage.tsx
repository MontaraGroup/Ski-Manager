import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type FaqItem = {
  id: number;
  question: string;
  answer: string;
};

export type FaqPageProps = {
  faqs: FaqItem[];
};

export default function FaqPage({ faqs = [] }: FaqPageProps) {
  const [search, setSearch] = useState('');
  const [openIds, setOpenIds] = useState<Set<number>>(new Set([1]));

  const filtered = faqs.filter(
    (f) =>
      f.question.toLowerCase().includes(search.toLowerCase()) ||
      f.answer.toLowerCase().includes(search.toLowerCase())
  );

  const toggleOpen = (id: number) => {
    setOpenIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  return (
    <Page
      title="Frequently Asked Questions"
      icon="fa-circle-question"
      iconTone="text-primary"
      back="/dashboard"
      width="max-w-3xl"
      subtitle="Everything you need to know about building, economy, staff, and resort growth"
    >
      <div className="space-y-6">
        {/* Search Input */}
        <div className="relative">
          <Icon
            name="fa-magnifying-glass"
            className="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-xs"
          />
          <input
            type="text"
            placeholder="Search questions (e.g. money, staff, weather, lifts)..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full h-11 pl-9 pr-3 rounded-xl border border-border bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm"
          />
          {search && (
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onPress={() => setSearch('')}
              className="absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-foreground text-xs min-h-7 px-2"
            >
              Clear
            </Button>
          )}
        </div>

        {/* FAQs Accordion */}
        <div className="space-y-3">
          {filtered.length === 0 ? (
            <div className="text-center py-8 text-xs text-muted border border-border rounded-xl bg-base-100">
              No matching questions found for "{search}".
            </div>
          ) : (
            filtered.map((item) => {
              const isOpen = openIds.has(item.id);

              return (
                <div
                  key={item.id}
                  className="rounded-xl border border-border bg-base-100 shadow-sm overflow-hidden transition-all"
                >
                  <Button
                    type="button"
                    variant="ghost"
                    onPress={() => toggleOpen(item.id)}
                    className="w-full h-auto p-4 text-left flex items-center justify-between gap-3 hover:bg-base-200/50"
                  >
                    <span className="font-bold text-xs sm:text-sm text-foreground">
                      {item.question}
                    </span>
                    <Icon
                      name="fa-chevron-down"
                      className={`text-muted text-xs shrink-0 transition-transform ${
                        isOpen ? 'rotate-180 text-primary' : ''
                      }`}
                    />
                  </Button>

                  {isOpen && (
                    <div className="px-4 pb-4 pt-1 text-xs text-muted leading-relaxed border-t border-border/40">
                      <p>{item.answer}</p>
                    </div>
                  )}
                </div>
              );
            })
          )}
        </div>

        {/* Help Banner */}
        <Panel>
          <div className="text-center py-2 space-y-3">
            <h3 className="font-bold text-sm text-foreground">Still have questions?</h3>
            <p className="text-xs text-muted max-w-sm mx-auto">
              Can't find the answers you're looking for? Reach out to our community or dev team.
            </p>
            <div className="flex items-center justify-center gap-2 pt-1 flex-wrap">
              <a href="/contact">
                <Button variant="primary" size="sm" className="font-semibold text-xs min-h-[36px] gap-1.5">
                  <Icon name="fa-envelope" /> Contact Us
                </Button>
              </a>
              <a href="/support">
                <Button variant="outline" size="sm" className="font-semibold text-xs min-h-[36px] gap-1.5">
                  <Icon name="fa-headset" /> Support Chat
                </Button>
              </a>
            </div>
          </div>
        </Panel>
      </div>
    </Page>
  );
}
