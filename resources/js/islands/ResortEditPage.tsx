import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';
import { Switch } from '@/components/Switch';

export type ResortEditProps = {
  resort: {
    name: string;
    location: string;
    description: string;
    altitude: string;
    aspect: string;
    is_open: boolean;
  };
};

export default function ResortEditPage({ resort }: ResortEditProps) {
  const [name, setName] = useState(resort?.name ?? 'My Resort');
  const [location, setLocation] = useState(resort?.location ?? '');
  const [description, setDescription] = useState(resort?.description ?? '');
  const [isOpen, setIsOpen] = useState(Boolean(resort?.is_open ?? true));

  return (
    <Page
      title="Edit Resort"
      icon="fa-pen-to-square"
      iconTone="text-primary"
      back="/resort"
      width="max-w-2xl"
      subtitle="Update public identity, geographic location, and guest operations status"
    >
      <form action="/resort/edit" method="post" className="space-y-6">
        <CsrfInput />

        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-mountain" className="text-primary" />
              Resort Identity
            </span>
          }
        >
          <div className="space-y-4">
            <div>
              <label className="text-xs font-semibold text-muted block mb-1">
                Resort Name
              </label>
              <input
                type="text"
                name="name"
                aria-label="Resort Name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                maxLength={50}
                required
                className="w-full h-10 px-3 rounded-lg border border-border bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-muted block mb-1">Location</label>
              <input
                type="text"
                name="location"
                aria-label="Location"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
                maxLength={100}
                placeholder="e.g. Park City, Utah or Zermatt, Switzerland"
                className="w-full h-10 px-3 rounded-lg border border-border bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>

            <div>
              <div className="flex items-center justify-between mb-1">
                <label className="text-xs font-semibold text-muted">Description</label>
                <span className="text-[11px] text-muted tabular-nums">
                  {description.length}/500
                </span>
              </div>
              <textarea
                name="description"
                aria-label="Description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                maxLength={500}
                rows={4}
                placeholder="Describe your mountain resort to visiting players..."
                className="w-full p-3 rounded-lg border border-border bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none"
              />
            </div>
          </div>
        </Panel>

        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-power-off" className="text-warning" />
              Resort Access
            </span>
          }
        >
          <div className="p-3 rounded-xl border border-border bg-base-200/40">
            <Switch
              name="is_open"
              isSelected={isOpen}
              onChange={setIsOpen}
              placement="end"
              className="w-full"
            >
              <div className="flex flex-col text-left">
                <span className="text-xs font-bold text-foreground">Resort Open for Guests</span>
                <span className="text-[11px] text-muted mt-0.5">
                  When closed, visitors stop arriving and ticket revenue pauses, but daily upkeep still applies.
                </span>
              </div>
            </Switch>
          </div>
        </Panel>

        <div className="flex items-center gap-3">
          <Button
            type="submit"
            variant="primary"
            className="min-h-[44px] px-6 font-semibold text-sm"
          >
            Save Changes
          </Button>

          <a href="/resort">
            <Button variant="secondary" className="min-h-[44px] px-6 font-semibold text-sm">
              Cancel
            </Button>
          </a>
        </div>
      </form>
    </Page>
  );
}
