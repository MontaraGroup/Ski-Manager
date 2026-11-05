import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export default function AdminBroadcastPage() {
  const [type, setType] = useState<'info' | 'warning' | 'success' | 'error'>('info');
  const [icon, setIcon] = useState('fa-bullhorn');
  const [message, setMessage] = useState('');

  const typeStyles = {
    info: 'border-accent/40 bg-accent/10 text-accent-foreground',
    warning: 'border-warning/40 bg-warning/10 text-warning',
    success: 'border-emerald-600/40 bg-emerald-950/20 text-emerald-400',
    error: 'border-red-600/40 bg-red-950/20 text-red-400',
  };

  const iconOptions = [
    { value: 'fa-bullhorn', label: 'Announcement' },
    { value: 'fa-snowflake', label: 'Weather / Snow' },
    { value: 'fa-trophy', label: 'Event / Tournament' },
    { value: 'fa-triangle-exclamation', label: 'Hazard / Notice' },
    { value: 'fa-wrench', label: 'System Maintenance' },
  ];

  return (
    <Page
      title="Broadcast Announcement"
      icon="fa-bullhorn"
      iconTone="text-warning"
      back="/admin"
      width="max-w-2xl"
      subtitle="Dispatch a global system alert to all registered resort managers."
    >
      <Panel>
        <p className="text-xs text-muted mb-4">
          Broadcasts post a high-priority notification to every player's notifications inbox and logs an entry in their mountain activity journal.
        </p>

        <form
          action="/admin/sendBroadcast"
          method="post"
          onSubmit={(e) => {
            if (!confirm('Dispatch this flash broadcast to all active resort profiles?')) {
              e.preventDefault();
            }
          }}
          className="space-y-4"
        >
          <CsrfInput />

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div className="space-y-1">
              <label className="text-xs font-semibold text-muted">Alert Style</label>
              <select
                name="type"
                value={type}
                onChange={(e) => setType(e.target.value as any)}
                className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
              >
                <option value="info">Info (Blue)</option>
                <option value="warning">Warning (Yellow)</option>
                <option value="success">Success (Green)</option>
                <option value="error">Emergency (Red)</option>
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold text-muted">Display Icon</label>
              <select
                name="icon"
                value={icon}
                onChange={(e) => setIcon(e.target.value)}
                className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
              >
                {iconOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="space-y-1">
            <div className="flex items-center justify-between text-xs text-muted">
              <label className="font-semibold">Announcement Content</label>
              <span className="font-mono text-[11px]">{message.length} / 255</span>
            </div>
            <textarea
              name="message"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              maxLength={255}
              rows={3}
              placeholder="Type the message you want all active resort managers to see live..."
              className="w-full rounded-xl border border-border bg-surface-raised p-3 text-xs text-foreground placeholder:text-muted focus:border-accent focus:outline-hidden"
              required
            />
          </div>

          {/* Live Preview */}
          <div className="space-y-1.5 pt-2">
            <span className="text-[11px] font-semibold text-muted uppercase tracking-wider">
              Live Player Preview:
            </span>
            <div
              className={`flex items-start gap-3 rounded-xl border p-3 text-xs transition-colors ${typeStyles[type]}`}
            >
              <Icon name={icon} className="mt-0.5 text-sm shrink-0" />
              <div className="space-y-0.5 min-w-0">
                <div className="font-bold">Server Announcement</div>
                <div className="text-muted leading-relaxed">
                  {message || 'Your announcement text will appear here...'}
                </div>
              </div>
            </div>
          </div>

          <Button
            type="submit"
            variant="primary"
            className="w-full text-xs font-bold min-h-11"
          >
            <Icon name="fa-paper-plane" className="mr-1.5" />
            Dispatch Global Broadcast
          </Button>
        </form>
      </Panel>
    </Page>
  );
}
