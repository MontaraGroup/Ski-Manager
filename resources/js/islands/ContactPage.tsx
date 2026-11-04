import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type ContactPageProps = {
  user: {
    name: string;
    email: string;
    loggedIn: boolean;
  };
  formTime: number;
  defaultSubject: string;
  defaultMessage: string;
};

export default function ContactPage({
  user,
  formTime,
  defaultSubject,
  defaultMessage,
}: ContactPageProps) {
  const [name, setName] = useState(user.name);
  const [email, setEmail] = useState(user.email);
  const [subject, setSubject] = useState(defaultSubject || 'general');
  const [message, setMessage] = useState(defaultMessage);

  return (
    <Page
      title="Contact Us"
      icon="fa-envelope"
      iconTone="text-primary"
      subtitle="Questions, bugs, or feedback: we would love to hear from you"
      width="max-w-4xl"
    >
      <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
        {/* Contact Form (2 cols) */}
        <div className="md:col-span-2">
          <Panel title="Send a message">
            <form action="/contact" method="post" className="space-y-4">
              <CsrfInput />
              <input type="hidden" name="form_time" value={formTime} />
              {/* Anti-spam honeypot */}
              <div
                aria-hidden="true"
                style={{ position: 'absolute', left: '-9999px', top: '-9999px' }}
              >
                <label>
                  Website (leave blank)
                  <input type="text" name="website" tabIndex={-1} autoComplete="off" />
                </label>
              </div>

              <div>
                <label htmlFor="name" className="block text-xs font-medium text-foreground mb-1">
                  Name *
                </label>
                <input
                  type="text"
                  name="name"
                  id="name"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  autoComplete="name"
                />
              </div>

              <div>
                <label htmlFor="email" className="block text-xs font-medium text-foreground mb-1">
                  Email *
                </label>
                <input
                  type="email"
                  name="email"
                  id="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  autoComplete="email"
                />
              </div>

              <div>
                <label htmlFor="subject" className="block text-xs font-medium text-foreground mb-1">
                  Topic
                </label>
                <select
                  name="subject"
                  id="subject"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="general">General Question</option>
                  <option value="bug">Bug Report</option>
                  <option value="feature">Feature Suggestion</option>
                  <option value="account">Account Issue</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div>
                <label htmlFor="message" className="block text-xs font-medium text-foreground mb-1">
                  Message *
                </label>
                <textarea
                  name="message"
                  id="message"
                  required
                  rows={5}
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  placeholder="Describe your question, issue, or idea in detail..."
                />
              </div>

              <Button type="submit" variant="primary" size="md">
                <Icon name="fa-paper-plane" className="mr-1.5" />
                Send message
              </Button>
            </form>
          </Panel>
        </div>

        {/* Sidebar Info (1 col) */}
        <div className="space-y-4">
          <Panel title="Other ways to reach us">
            <div className="space-y-3">
              <a
                href="https://discord.gg/u3GzGt8K6a"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 rounded-lg border border-border bg-surface-secondary/40 p-2.5 transition-colors hover:bg-surface-secondary"
              >
                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#5865F2]/10 text-[#5865F2]">
                  <Icon name="fa-brands fa-discord" className="text-base" />
                </div>
                <div>
                  <div className="text-xs font-semibold text-foreground">Discord Community</div>
                  <div className="text-[11px] text-muted">Fastest response from players & devs</div>
                </div>
              </a>

              <a
                href="/support"
                className="flex items-center gap-3 rounded-lg border border-border bg-surface-secondary/40 p-2.5 transition-colors hover:bg-surface-secondary"
              >
                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <Icon name="fa-headset" className="text-base" />
                </div>
                <div>
                  <div className="text-xs font-semibold text-foreground">In-Game Support</div>
                  <div className="text-[11px] text-muted">Direct messaging from your account</div>
                </div>
              </a>

              <a
                href="/bugs"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 rounded-lg border border-border bg-surface-secondary/40 p-2.5 transition-colors hover:bg-surface-secondary"
              >
                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-warning/10 text-warning">
                  <Icon name="fa-bug" className="text-base" />
                </div>
                <div>
                  <div className="text-xs font-semibold text-foreground">Report a Bug</div>
                  <div className="text-[11px] text-muted">Track issues on GitHub</div>
                </div>
              </a>

              <a
                href="https://github.com/MontaraGroup/Ski-Manager"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 rounded-lg border border-border bg-surface-secondary/40 p-2.5 transition-colors hover:bg-surface-secondary"
              >
                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface border border-border text-foreground">
                  <Icon name="fa-brands fa-github" className="text-base" />
                </div>
                <div>
                  <div className="text-xs font-semibold text-foreground">Open Source</div>
                  <div className="text-[11px] text-muted">Contribute code or docs</div>
                </div>
              </a>
            </div>
          </Panel>

          <Panel title="Good to know">
            <ul className="space-y-2 text-xs text-muted list-disc pl-4">
              <li>
                <strong>Response time:</strong> Messages are typically answered within 48 hours.
              </li>
              <li>
                <strong>Found a bug?</strong> GitHub issues are tracked and deployed fastest.
              </li>
              <li>
                <strong>Feature ideas:</strong> Check the public roadmap to vote on upcoming features.
              </li>
              <li>
                <strong>Quick answers:</strong> Check the <a href="/faq" className="text-accent underline">FAQ</a> for common game questions.
              </li>
            </ul>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
