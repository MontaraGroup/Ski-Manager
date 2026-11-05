import { useState, useRef, useEffect, type FormEvent, type KeyboardEvent } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf, CsrfInput, Page, Panel } from '@/components/Page';

export type SupportChatMessage = {
  id: number;
  sender: 'player' | 'admin';
  message: string;
  is_read: boolean;
  timeFormatted: string;
  dateLabel: string | null;
};

export type PlayerMeta = {
  id: number;
  username: string;
};

export type AdminSupportChatPageProps = {
  chatUserId: number;
  player: PlayerMeta;
  messages: SupportChatMessage[];
};

export default function AdminSupportChatPage({
  chatUserId,
  player,
  messages: initialMessages = [],
}: AdminSupportChatPageProps) {
  const [messages, setMessages] = useState<SupportChatMessage[]>(initialMessages);
  const [replyText, setReplyText] = useState('');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editText, setEditText] = useState('');
  const chatBottomRef = useRef<HTMLDivElement>(null);
  const formRef = useRef<HTMLFormElement>(null);

  useEffect(() => {
    chatBottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleStartEdit = (msg: SupportChatMessage) => {
    setEditingId(msg.id);
    setEditText(msg.message);
  };

  const handleCancelEdit = () => {
    setEditingId(null);
    setEditText('');
  };

  const handleSaveEdit = async (id: number) => {
    const trimmed = editText.trim();
    if (!trimmed) return;

    const { field, token } = csrf();
    const formData = new FormData();
    formData.append('message', trimmed);
    formData.append(field, token);

    try {
      const res = await fetch(`/admin/support/message/${id}/edit`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json();
      if (data.success) {
        setMessages((prev) =>
          prev.map((m) => (m.id === id ? { ...m, message: trimmed } : m))
        );
        handleCancelEdit();
      } else {
        alert(data.error || 'Failed to edit message.');
      }
    } catch {
      alert('Network error while editing message.');
    }
  };

  const handleDeleteMessage = async (id: number) => {
    if (!window.confirm('Unsend this message?')) return;

    const { token } = csrf();
    try {
      const res = await fetch(`/admin/support/message/${id}/delete`, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token,
        },
      });
      const data = await res.json();
      if (data.success) {
        setMessages((prev) => prev.filter((m) => m.id !== id));
      } else {
        alert(data.error || 'Failed to delete message.');
      }
    } catch {
      alert('Network error while deleting message.');
    }
  };

  const handleKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (replyText.trim().length > 0) {
        formRef.current?.submit();
      }
    }
  };

  return (
    <Page
      title={player.username}
      icon="fa-headset"
      iconTone="text-primary"
      back="/admin/support"
      width="max-w-3xl"
      subtitle={`User #${chatUserId} · Direct Support Conversation`}
      actions={
        <a
          href={`/admin/user/${chatUserId}`}
          className="inline-flex h-8 items-center gap-1.5 rounded-md border border-edge bg-surface-2 px-3 text-xs font-medium text-foreground hover:bg-surface-3 transition-colors"
        >
          <Icon name="fa-user" />
          View Profile
        </a>
      }
    >
      <Panel className="p-0 overflow-hidden">
        {/* Messages Container */}
        <div className="flex flex-col gap-3 p-4 sm:p-6 min-h-[420px] max-h-[580px] overflow-y-auto">
          {messages.length === 0 ? (
            <div className="m-auto flex flex-col items-center justify-center py-12 text-center text-muted">
              <Icon name="fa-comments" className="text-3xl opacity-50 mb-2" />
              <p className="text-sm">No messages in this conversation yet.</p>
            </div>
          ) : (
            messages.map((msg) => {
              const isAdmin = msg.sender === 'admin';
              const isEditing = editingId === msg.id;

              return (
                <div key={msg.id} className="space-y-2">
                  {msg.dateLabel && (
                    <div className="flex items-center justify-center my-2">
                      <span className="rounded-full bg-surface-3 px-3 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-muted">
                        {msg.dateLabel}
                      </span>
                    </div>
                  )}

                  <div className={`flex ${isAdmin ? 'justify-end' : 'justify-start'}`}>
                    <div className={`group relative flex items-center gap-2 max-w-[85%] ${isAdmin ? 'flex-row-reverse' : ''}`}>
                      {/* Action buttons for admin messages on hover */}
                      {isAdmin && !isEditing && (
                        <div className="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                          <button
                            type="button"
                            onClick={() => handleStartEdit(msg)}
                            aria-label="Edit message"
                            className="flex h-6 w-6 items-center justify-center rounded text-muted hover:bg-surface-2 hover:text-foreground text-[11px]"
                          >
                            <Icon name="fa-pencil" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDeleteMessage(msg.id)}
                            aria-label="Delete message"
                            className="flex h-6 w-6 items-center justify-center rounded text-muted hover:bg-danger/10 hover:text-danger text-[11px]"
                          >
                            <Icon name="fa-trash" />
                          </button>
                        </div>
                      )}

                      {/* Bubble */}
                      <div
                        className={`rounded-2xl px-4 py-2.5 text-xs ${
                          isAdmin
                            ? 'bg-primary text-primary-foreground rounded-tr-sm'
                            : 'bg-surface-2 text-foreground rounded-tl-sm border border-edge'
                        }`}
                      >
                        {isEditing ? (
                          <div className="flex flex-col gap-2 min-w-[220px]">
                            <textarea
                              value={editText}
                              onChange={(e) => setEditText(e.target.value)}
                              className="w-full rounded border border-edge bg-surface-1 p-2 text-xs text-foreground focus:outline-none"
                              rows={2}
                            />
                            <div className="flex items-center justify-end gap-1.5">
                              <Button
                                size="sm"
                                variant="secondary"
                                onPress={handleCancelEdit}
                              >
                                Cancel
                              </Button>
                              <Button
                                size="sm"
                                variant="primary"
                                onPress={() => handleSaveEdit(msg.id)}
                              >
                                Save
                              </Button>
                            </div>
                          </div>
                        ) : (
                          <>
                            <div className="whitespace-pre-wrap break-words leading-relaxed">
                              {msg.message}
                            </div>
                            <div
                              className={`mt-1 flex items-center gap-1 text-[10px] ${
                                isAdmin ? 'justify-end text-primary-foreground/75' : 'text-muted'
                              }`}
                            >
                              <span>{isAdmin ? 'You' : player.username} · {msg.timeFormatted}</span>
                              <Icon
                                name={msg.is_read ? 'fa-check-double' : 'fa-check'}
                                className={msg.is_read ? 'text-accent' : 'opacity-60'}
                              />
                            </div>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })
          )}
          <div ref={chatBottomRef} />
        </div>

        {/* Reply Box */}
        <div className="border-t border-edge bg-surface-2/40 p-4">
          <form
            ref={formRef}
            action={`/admin/support/${chatUserId}/reply`}
            method="post"
            className="space-y-2"
          >
            <CsrfInput />
            <div className="flex items-end gap-2">
              <textarea
                name="message"
                aria-label="Reply message"
                placeholder="Write a reply…"
                value={replyText}
                onChange={(e) => setReplyText(e.target.value)}
                onKeyDown={handleKeyDown}
                rows={2}
                maxLength={2000}
                required
                className="flex-1 rounded-md border border-edge bg-surface-1 px-3 py-2 text-xs text-foreground placeholder:text-muted focus:border-focus focus:outline-none resize-none"
              />
              <Button
                type="submit"
                variant="primary"
                size="md"
                isDisabled={!replyText.trim()}
                className="shrink-0 gap-1.5"
              >
                <Icon name="fa-paper-plane" />
                Reply
              </Button>
            </div>
            <div className="text-[11px] text-muted">
              Press Enter to send · Shift+Enter for a new line
            </div>
          </form>
        </div>
      </Panel>
    </Page>
  );
}
