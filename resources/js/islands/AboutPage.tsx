import React from 'react';
import { Chip } from '@heroui/react/chip';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

interface TechItem {
  category: string;
  name: string;
  url: string;
  desc: string;
}

interface BrandItem {
  name: string;
  category: string;
  url: string;
  desc: string;
}

interface AboutPageProps {
  isLoggedIn: boolean;
  developer: {
    name: string;
    title: string;
    description: string;
  };
  story: {
    origin: string;
    rewrite: string;
  };
  stack: TechItem[];
  brands: BrandItem[];
  community: {
    discord: string;
    updates: string;
    contact: string;
    roadmap: string;
  };
}

export default function AboutPage({
  isLoggedIn,
  developer,
  story,
  stack,
  brands,
  community,
}: AboutPageProps) {
  return (
    <Page
      title="About Ski Manager"
      subtitle="An independent, browser-based alpine ski resort tycoon built with love for the mountains."
      icon="fa-mountain-sun"
      back={isLoggedIn ? '/dashboard' : '/'}
      width="max-w-4xl"
      actions={
        <div className="flex items-center gap-2">
          <a
            href={community.discord}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-default transition-colors"
          >
            <Icon name="fa-discord" className="w-4 h-4 text-[#5865F2]" />
            <span>Discord</span>
          </a>
          {!isLoggedIn && (
            <a
              href="/register"
              className="inline-flex min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
            >
              <Icon name="fa-play" className="w-3.5 h-3.5" />
              <span>Play Free</span>
            </a>
          )}
        </div>
      }
    >
      <div className="space-y-6">
        {/* Core Vision & What is Ski Manager */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-circle-info" className="text-accent" />
              <span>What is Ski Manager?</span>
            </div>
          }
        >
          <div className="space-y-4 text-sm text-foreground/80 leading-relaxed">
            <p>
              Ski Manager is a free-to-play, browser-native ski resort management simulation game. You take on the role of a mountain operator, building chairlifts and gondolas, grooming scenic slopes, scheduling ski school lessons, balancing ticket pricing, managing loans, and maintaining guest safety across real weather seasons.
            </p>
            <p>
              There are no mandatory microtransactions, artificial energy timers, or pay-to-win mechanisms. Every slope and building is unlocked through strategic mountain management, smart resource allocation, and continuous resort expansion.
            </p>
          </div>
        </Panel>

        {/* Story & Evolution */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="rounded-xl border border-border bg-surface p-5 space-y-3">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <Icon name="fa-clock-rotate-left" className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-sm font-semibold text-foreground">The Origins</h3>
                <span className="text-xs text-muted">Hobby project to player community</span>
              </div>
            </div>
            <p className="text-xs sm:text-sm text-muted leading-relaxed">
              {story.origin}
            </p>
          </div>

          <div className="rounded-xl border border-border bg-surface p-5 space-y-3">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center shrink-0">
                <Icon name="fa-sparkles" className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-sm font-semibold text-foreground">Version 2 Rebuild</h3>
                <span className="text-xs text-muted">Complete rewrite and expansion</span>
              </div>
            </div>
            <p className="text-xs sm:text-sm text-muted leading-relaxed">
              {story.rewrite}
            </p>
          </div>
        </div>

        {/* Developer Note */}
        <div className="rounded-xl border border-border bg-surface p-5">
          <div className="flex flex-col sm:flex-row items-start gap-4">
            <div className="w-12 h-12 rounded-full bg-primary/15 text-primary flex items-center justify-center shrink-0 text-lg font-bold">
              <Icon name="fa-user" className="w-5 h-5" />
            </div>
            <div className="space-y-1.5 flex-1">
              <div className="flex items-center gap-2 flex-wrap">
                <h3 className="text-base font-semibold text-foreground">{developer.name}</h3>
                <Chip size="sm" variant="soft" color="accent">
                  {developer.title}
                </Chip>
              </div>
              <p className="text-xs sm:text-sm text-muted leading-relaxed">
                {developer.description}
              </p>
            </div>
          </div>
        </div>

        {/* Technology Stack */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-microchip" className="text-accent" />
              <span>Technology Stack</span>
            </div>
          }
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {stack.map((item) => (
              <div
                key={item.category}
                className="p-3.5 rounded-lg border border-border bg-card/60 flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between gap-2 mb-1.5">
                    <span className="text-[11px] font-medium uppercase tracking-wider text-muted">
                      {item.category}
                    </span>
                    <a
                      href={item.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-xs text-link inline-flex items-center gap-1 hover:underline min-h-11 sm:min-h-0"
                    >
                      <span>Visit</span>
                      <Icon name="fa-arrow-up-right-from-square" className="w-2.5 h-2.5" />
                    </a>
                  </div>
                  <h4 className="text-sm font-semibold text-foreground mb-1">{item.name}</h4>
                  <p className="text-xs text-muted leading-relaxed">{item.desc}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="mt-4 pt-3 border-t border-border flex items-center gap-2 text-xs text-muted">
            <Icon name="fa-universal-access" className="w-4 h-4 text-primary shrink-0" />
            <span>
              Engineered with <a href="https://www.w3.org/WAI/WCAG22/quickref/" target="_blank" rel="noopener noreferrer" className="text-link hover:underline">WCAG 2.2 AA</a> accessibility standards, full keyboard navigation, screen reader affordances, and prefers-reduced-motion support.
            </span>
          </div>
        </Panel>

        {/* Real Equipment Brands */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-snowflake" className="text-accent" />
              <span>Authentic Equipment &amp; Licensing</span>
            </div>
          }
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
            {brands.map((b) => (
              <div
                key={b.name}
                className="p-3 rounded-lg border border-border bg-card/60 flex items-start justify-between gap-3"
              >
                <div>
                  <div className="flex items-center gap-2 mb-0.5">
                    <span className="text-sm font-semibold text-foreground">{b.name}</span>
                    <Chip size="sm" variant="soft" className="text-[10px]">
                      {b.category}
                    </Chip>
                  </div>
                  <p className="text-xs text-muted">{b.desc}</p>
                </div>
                <a
                  href={b.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-link shrink-0 hover:underline min-h-11 sm:min-h-0 flex items-center"
                >
                  <Icon name="fa-arrow-up-right-from-square" className="w-3 h-3" />
                </a>
              </div>
            ))}
          </div>
          <p className="text-[11px] text-muted italic">
            All brand names and trademarks are property of their respective owners. Their inclusion is strictly for authentic simulation and educational purposes.
          </p>
        </Panel>

        {/* Community & Connect */}
        <div className="rounded-xl bg-gradient-to-br from-primary/15 via-surface to-surface border border-primary/30 p-6 text-center space-y-4">
          <div className="max-w-md mx-auto space-y-1.5">
            <h3 className="text-lg font-bold text-foreground">Join the Mountain Community</h3>
            <p className="text-xs sm:text-sm text-muted">
              Connect with fellow resort managers, suggest features, discuss resort strategies, and follow release updates.
            </p>
          </div>
          <div className="flex flex-wrap items-center justify-center gap-3">
            <a
              href={community.discord}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
            >
              <Icon name="fa-discord" className="w-4 h-4" />
              <span>Discord Server</span>
            </a>
            <a
              href={community.updates}
              className="inline-flex min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-default transition-colors"
            >
              <Icon name="fa-newspaper" className="w-4 h-4 text-primary" />
              <span>Game Updates</span>
            </a>
            <a
              href={community.roadmap}
              className="inline-flex min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-default transition-colors"
            >
              <Icon name="fa-compass" className="w-4 h-4 text-primary" />
              <span>Roadmap</span>
            </a>
            <a
              href={community.contact}
              className="inline-flex min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-default transition-colors"
            >
              <Icon name="fa-envelope" className="w-4 h-4 text-primary" />
              <span>Contact</span>
            </a>
          </div>
        </div>
      </div>
    </Page>
  );
}
