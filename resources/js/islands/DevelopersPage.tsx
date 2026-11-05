import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type ApiEndpoint = {
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
  path: string;
  desc: string;
};

export type DevelopersPageProps = {
  base: string;
  endpoints: ApiEndpoint[];
  catalogUrl: string;
  openapiUrl: string;
  authMdUrl: string;
};

export default function DevelopersPage({
  base = 'https://ski-manager.net',
  endpoints = [],
  catalogUrl = '/.well-known/api-catalog',
  openapiUrl = '/openapi.json',
  authMdUrl = '/auth.md',
}: DevelopersPageProps) {
  const [copied, setCopied] = useState(false);
  const curlExample = `curl -s "${base}/api/leaderboard"`;

  const handleCopy = () => {
    navigator.clipboard.writeText(curlExample);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <Page
      title="Ski Manager API"
      icon="fa-code"
      iconTone="text-primary"
      back="/dashboard"
      width="max-w-4xl"
      subtitle={`Public JSON API and discovery catalog. All endpoints are hosted under ${base}`}
    >
      <div className="space-y-6">
        {/* Discovery Links Banner */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <a
            href={openapiUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="p-4 rounded-xl border border-border bg-base-100 hover:bg-base-200/50 hover:border-primary/50 transition-all group shadow-sm"
          >
            <div className="flex items-center gap-2 font-bold text-xs text-foreground group-hover:text-primary transition-colors">
              <Icon name="fa-file-code" className="text-primary text-sm" />
              OpenAPI 3.1 Spec
            </div>
            <div className="text-[11px] text-muted mt-1 font-mono">{openapiUrl}</div>
          </a>

          <a
            href={catalogUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="p-4 rounded-xl border border-border bg-base-100 hover:bg-base-200/50 hover:border-primary/50 transition-all group shadow-sm"
          >
            <div className="flex items-center gap-2 font-bold text-xs text-foreground group-hover:text-primary transition-colors">
              <Icon name="fa-compass" className="text-success text-sm" />
              RFC 9727 Catalog
            </div>
            <div className="text-[11px] text-muted mt-1 font-mono">{catalogUrl}</div>
          </a>

          <a
            href={authMdUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="p-4 rounded-xl border border-border bg-base-100 hover:bg-base-200/50 hover:border-primary/50 transition-all group shadow-sm"
          >
            <div className="flex items-center gap-2 font-bold text-xs text-foreground group-hover:text-primary transition-colors">
              <Icon name="fa-robot" className="text-secondary text-sm" />
              Agent Auth Spec
            </div>
            <div className="text-[11px] text-muted mt-1 font-mono">{authMdUrl}</div>
          </a>
        </div>

        {/* Endpoints Table */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-network-wired" className="text-primary" />
              Available Endpoints
            </span>
          }
        >
          <div className="divide-y divide-border/40">
            {endpoints.map((ep, i) => (
              <div key={i} className="py-3.5 first:pt-1 last:pb-1">
                <div className="flex items-center gap-2.5 flex-wrap mb-1">
                  <span
                    className={`px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider ${
                      ep.method === 'GET'
                        ? 'bg-primary/15 text-primary border border-primary/30'
                        : 'bg-success/15 text-success border border-success/30'
                    }`}
                  >
                    {ep.method}
                  </span>
                  <span className="font-mono text-xs font-semibold text-foreground">
                    {ep.path}
                  </span>
                </div>
                <p className="text-xs text-muted leading-relaxed pl-1">{ep.desc}</p>
              </div>
            ))}
          </div>
        </Panel>

        {/* cURL Example */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-terminal" className="text-warning" />
              Quickstart Example
            </span>
          }
        >
          <div className="relative">
            <pre className="p-3.5 rounded-lg bg-base-200/80 border border-border font-mono text-xs text-foreground overflow-x-auto">
              <code>{curlExample}</code>
            </pre>
            <Button
              size="sm"
              variant="outline"
              onClick={handleCopy}
              className="absolute right-2 top-2 min-h-[28px] text-[11px] font-semibold gap-1"
            >
              <Icon name={copied ? 'fa-check' : 'fa-copy'} />
              {copied ? 'Copied' : 'Copy'}
            </Button>
          </div>
        </Panel>

        {/* Limits & Usage */}
        <div className="rounded-xl border border-border bg-base-200/30 p-4 text-xs text-muted space-y-2">
          <div className="font-bold text-foreground flex items-center gap-1.5">
            <Icon name="fa-circle-info" className="text-primary" /> Usage Limits & Rate Policy
          </div>
          <p className="leading-relaxed">
            No API keys or client registration are required for public endpoints. Please throttle
            requests to at most one call every few seconds; game state updates once per day when the
            nightly tick runs. If you build an app or bot with our API,{' '}
            <a href="/contact" className="text-primary underline font-medium">
              let us know
            </a>
            !
          </p>
        </div>
      </div>
    </Page>
  );
}
