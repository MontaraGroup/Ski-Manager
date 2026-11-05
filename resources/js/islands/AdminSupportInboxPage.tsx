import { useState, useMemo } from 'react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type Conversation = {
  user_id: number;
  username: string;
  unread: number;
  last_message: string;
  timeAgo: string;
};

export type AdminSupportInboxPageProps = {
  conversations: Conversation[];
  totalUnread: number;
};

export default function AdminSupportInboxPage({
  conversations = [],
  totalUnread = 0,
}: AdminSupportInboxPageProps) {
  const [filter, setFilter] = useState('');

  const filteredConversations = useMemo(() => {
    const q = filter.trim().toLowerCase();
    if (!q) return conversations;
    return conversations.filter(
      (c) =>
        c.username.toLowerCase().includes(q) ||
        c.last_message.toLowerCase().includes(q) ||
        String(c.user_id).includes(q)
    );
  }, [conversations, filter]);

  return (
    <Page
      title="Support Inbox"
      icon="fa-headset"
      iconTone="text-primary"
      back="/admin"
      width="max-w-4xl"
      subtitle={`${conversations.length} conversation${conversations.length === 1 ? '' : 's'}${
        totalUnread > 0 ? ` · ${totalUnread} unread` : ''
      }`}
    >
      <Panel
        title={`Inbox (${filteredConversations.length})`}
        actions={
          conversations.length > 0 && (
            <input
              type="search"
              aria-label="Filter conversations"
              placeholder="Filter by player or message…"
              value={filter}
              onChange={(e) => setFilter(e.target.value)}
              className="w-56 sm:w-64 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-xs text-foreground placeholder:text-muted focus:border-focus focus:outline-none"
            />
          )
        }
      >
        {conversations.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-surface-2 text-muted">
              <Icon name="fa-inbox" className="text-2xl opacity-60" />
            </div>
            <p className="text-sm font-semibold text-foreground">No support messages yet</p>
            <p className="mt-1 text-xs text-muted">Player messages will appear here when submitted.</p>
          </div>
        ) : filteredConversations.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted">No conversations match your filter.</p>
        ) : (
          <div className="divide-y divide-edge -mx-4 -my-4 sm:-mx-6 sm:-my-6">
            {filteredConversations.map((conv) => {
              const hasUnread = conv.unread > 0;
              const initials = conv.username.slice(0, 2).toUpperCase();

              return (
                <a
                  key={conv.user_id}
                  href={`/admin/support/${conv.user_id}`}
                  className={`flex items-center gap-3 px-4 py-3 sm:px-6 transition-colors hover:bg-surface-2/60 ${
                    hasUnread ? 'bg-danger/5 border-l-2 border-danger' : ''
                  }`}
                >
                  {/* Avatar */}
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-3 font-semibold text-xs text-foreground">
                    {initials}
                  </div>

                  {/* Body */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className={`text-xs font-semibold ${hasUnread ? 'text-danger' : 'text-foreground'}`}>
                        {conv.username}
                      </span>
                      <span className="font-mono text-[11px] text-muted">#{conv.user_id}</span>
                      {hasUnread && (
                        <span className="inline-flex items-center rounded-full bg-danger px-1.5 py-0.2 text-[10px] font-bold text-white">
                          {conv.unread}
                        </span>
                      )}
                    </div>
                    <div
                      className={`text-xs truncate mt-0.5 ${
                        hasUnread ? 'text-foreground font-medium' : 'text-muted'
                      }`}
                    >
                      {conv.last_message || 'No messages'}
                    </div>
                  </div>

                  {/* Metadata */}
                  <div className="shrink-0 text-right">
                    <div className="text-[11px] text-muted">{conv.timeAgo}</div>
                    {hasUnread && (
                      <div className="mt-0.5 text-[11px] font-semibold text-danger">
                        {conv.unread} unread
                      </div>
                    )}
                  </div>
                </a>
              );
            })}
          </div>
        )}
      </Panel>
    </Page>
  );
}
