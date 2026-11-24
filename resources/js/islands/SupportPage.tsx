import { useEffect, useRef, useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page } from '@/components/Page';

export type SupportMessage = {
  id: number;
  sender: 'player' | 'admin' | string;
  message: string;
  time: string;
  date: string;
  dateFormatted?: string;
};

export type SupportPageProps = {
  messages: SupportMessage[];
  lastId: number;
  maxLength: number;
  defaultMessage: string;
};

type Conversations = { isAvailable?: () => boolean; open?: () => void };

/** PostHog's Support chat (posthog.conversations), once its bundle has loaded */
function chatApi(): Conversations | null {
  const c = (window as unknown as { posthog?: { conversations?: Conversations } }).posthog?.conversations;
  try {
    return c?.isAvailable?.() ? c : null;
  } catch {
    return null;
  }
}

const TOPICS = [
  {
    icon: 'fa-bug',
    label: "Something's broken",
    template: 'What page were you on?\n\nWhat did you do?\n\nWhat happened instead?\n',
  },
  {
    icon: 'fa-user-gear',
    label: 'Account or sign-in',
    template: 'What do you need help with on your account?\n',
  },
  {
    icon: 'fa-lightbulb',
    label: 'Idea or feedback',
    template: '',
  },
];

export default function SupportPage({
  messages: initialMessages,
  lastId: initialLastId,
  maxLength = 1000,
  defaultMessage = '',
}: SupportPageProps) {
  const [messages, setMessages] = useState<SupportMessage[]>(initialMessages);
  const [text, setText] = useState(defaultMessage);
  const [sending, setSending] = useState(false);
  const lastIdRef = useRef(initialLastId);
  const chatEndRef = useRef<HTMLDivElement>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  // 'wait' until PostHog answers; 'chat' = new messages go through the Support chat; 'form' =
  // the chat couldn't load (blocked or switched off), so the old form below still works
  const [mode, setMode] = useState<'wait' | 'chat' | 'form'>('wait');

  useEffect(() => {
    let tries = 0;
    const timer = setInterval(() => {
      if (chatApi()) {
        setMode('chat');
        clearInterval(timer);
      } else if (++tries >= 16) {
        setMode('form');
        clearInterval(timer);
      }
    }, 250);
    return () => clearInterval(timer);
  }, []);

  const scrollToBottom = () => {
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  // Polling for live replies while tab is open
  useEffect(() => {
    lastIdRef.current = messages.length > 0 ? messages[messages.length - 1].id : 0;

    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await fetch(`/support/messages?after=${lastIdRef.current}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const newRows: SupportMessage[] = await res.json();
        if (Array.isArray(newRows) && newRows.length > 0) {
          const fresh = newRows.filter((m) => m.id > lastIdRef.current);
          if (fresh.length > 0) {
            setMessages((prev) => [...prev, ...fresh]);
            lastIdRef.current = fresh[fresh.length - 1].id;
          }
        }
      } catch (err) {
        console.error('Support poll failed', err);
      }
    };

    const interval = setInterval(poll, 15000);
    const onVisibility = () => {
      if (!document.hidden) poll();
    };

    document.addEventListener('visibilitychange', onVisibility);
    return () => {
      clearInterval(interval);
      document.removeEventListener('visibilitychange', onVisibility);
    };
  }, [messages.length]);

  const handleTopic = (tpl: string) => {
    if (!text.trim()) {
      setText(tpl);
    }
    textareaRef.current?.focus();
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    const isFineKeyboard = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (isFineKeyboard && e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (text.trim() && !sending) {
        setSending(true);
        e.currentTarget.form?.submit();
      }
    }
  };

  return (
    <Page
      title="Support"
      icon="fa-headset"
      iconTone="text-primary"
      subtitle={
        mode === 'chat'
          ? 'Chat with the Ski Manager team. Replies show up in the chat window on every page.'
          : "Message the Ski Manager team. We read everything, and you'll get a notification when we reply."
      }
      width="max-w-3xl"
      actions={
        <a
          href="/faq"
          className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-default sm:min-h-9"
        >
          <span>FAQ</span>
        </a>
      }
    >
      {mode === 'chat' && (
        <div className="mb-4 flex flex-col items-start gap-3 rounded-xl border border-border bg-surface p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="font-semibold">Start a chat</div>
            <p className="text-sm text-muted">The chat opens in the corner of the page and stays there while you play.</p>
          </div>
          <Button variant="primary" onPress={() => chatApi()?.open?.()}>
            <Icon name="fa-comments" className="mr-1.5" />
            Open chat
          </Button>
        </div>
      )}
      {mode === 'chat' && messages.length === 0 ? null : (
      <div className="rounded-xl border border-border bg-surface shadow-sm overflow-hidden flex flex-col">
        {mode === 'chat' && (
          <div className="border-b border-border px-4 py-2 text-xs font-medium text-muted">Earlier messages</div>
        )}
        {/* Chat message timeline */}
        <div
          className="flex flex-col gap-2.5 p-4 overflow-y-auto"
          style={{ height: 'min(60vh, 32rem)' }}
          aria-live="polite"
        >
          {messages.length === 0 ? (
            <div className="m-auto text-center max-w-sm py-12">
              <Icon name="fa-headset" className="text-4xl text-muted/40 mb-3" />
              <div className="font-semibold text-base">What can we help with?</div>
              <p className="text-xs text-muted mt-1 mb-4">
                Pick a topic, or write your message below.
              </p>
              <div className="flex flex-wrap justify-center gap-1.5">
                {TOPICS.map((t) => (
                  <Button
                    key={t.label}
                    type="button"
                    size="sm"
                    variant="ghost"
                    onPress={() => handleTopic(t.template)}
                    className="inline-flex min-h-11 sm:min-h-7 items-center gap-1.5 rounded-full border border-border px-3.5 py-1 text-xs font-medium"
                  >
                    <Icon name={t.icon} className="text-xs" />
                    <span>{t.label}</span>
                  </Button>
                ))}
              </div>
            </div>
          ) : (
            <>
              {messages.map((msg, index) => {
                const prev = index > 0 ? messages[index - 1] : null;
                const isNewDay = !prev || prev.date !== msg.date;
                const isTeam = msg.sender === 'admin';

                return (
                  <div key={msg.id} className="flex flex-col">
                    {isNewDay && (
                      <div className="self-center my-2 rounded-full bg-surface-secondary px-3 py-0.5 text-[11px] font-medium text-muted">
                        {msg.dateFormatted || msg.date}
                      </div>
                    )}
                    <div
                      className={`max-w-[82%] rounded-2xl p-3 text-sm leading-relaxed ${
                        isTeam
                          ? 'self-start rounded-bl-sm border border-border bg-surface-secondary text-foreground'
                          : 'self-end rounded-br-sm bg-primary text-primary-foreground'
                      }`}
                    >
                      <div className="whitespace-pre-wrap break-words">{msg.message}</div>
                      <div
                        className={`mt-1 text-[10px] tabular-nums ${
                          isTeam ? 'text-muted' : 'text-primary-foreground/75'
                        }`}
                      >
                        {isTeam ? 'Ski Manager team' : 'You'} · {msg.time}
                      </div>
                    </div>
                  </div>
                );
              })}
              <div ref={chatEndRef} />
            </>
          )}
        </div>

        {/* Compose Form: only while the Support chat isn't available */}
        {mode === 'form' && (
        <form action="/support/send" method="post" className="border-t border-border p-3 bg-surface-secondary/20">
          <CsrfInput />

          {/* Quick topic pills */}
          {messages.length > 0 && (
            <div className="flex flex-wrap gap-1.5 mb-2.5">
              {TOPICS.filter((t) => t.template !== '').map((t) => (
                <Button
                  key={t.label}
                  type="button"
                  size="sm"
                  variant="ghost"
                  onPress={() => handleTopic(t.template)}
                  className="inline-flex min-h-11 sm:min-h-7 items-center gap-1.5 rounded-full border border-border px-3 py-1 text-xs font-medium text-muted hover:text-primary"
                >
                  <Icon name={t.icon} className="text-xs" />
                  <span>{t.label}</span>
                </Button>
              ))}
            </div>
          )}

          <div className="flex items-end gap-2">
            <textarea
              ref={textareaRef}
              name="message"
              required
              rows={2}
              maxLength={maxLength}
              value={text}
              onChange={(e) => setText(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Write a message to support…"
              className="flex-1 rounded-lg border border-border bg-surface px-3 py-2 text-base sm:text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary resize-none min-h-12 max-h-36"
            />
            <Button
              type="submit"
              variant="primary"
              size="md"
              isDisabled={!text.trim() || sending}
            >
              <Icon name="fa-paper-plane" className="mr-1.5" />
              {sending ? 'Sending…' : 'Send'}
            </Button>
          </div>

          <div className="flex items-center justify-between text-[11px] text-muted mt-1.5">
            <span>Shift+Enter for a new line · Enter to send</span>
            <span className="tabular-nums">
              {text.length} / {maxLength}
            </span>
          </div>
        </form>
        )}
      </div>
      )}

      <p className="text-center text-xs text-muted mt-3">
        Prefer community chat? Find us on{' '}
        <a
          href="https://discord.gg/u3GzGt8K6a"
          target="_blank"
          rel="noopener noreferrer"
          className="text-primary underline hover:opacity-80"
        >
          Discord
        </a>
        , or use the{' '}
        <a href="/contact" className="text-primary underline hover:opacity-80">
          contact form
        </a>
        .
      </p>
    </Page>
  );
}
