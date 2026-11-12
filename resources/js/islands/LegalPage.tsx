import React, { useState } from 'react';
import { Button } from '@heroui/react';
import { Chip } from '@heroui/react/chip';
import { Icon } from '@/components/Icon';
import { Page } from '@/components/Page';

interface TabItem {
  key: string;
  label: string;
  href: string;
  icon: string;
}

interface SitemapLink {
  label: string;
  href: string;
  desc?: string;
  external?: boolean;
}

interface SitemapCategory {
  title: string;
  icon: string;
  links: SitemapLink[];
}

interface LegalPageProps {
  active: 'terms' | 'privacy' | 'cookies' | 'disclaimer' | 'sitemap';
  title: string;
  icon: string;
  lastUpdated: string;
  tabs: TabItem[];
  categories?: SitemapCategory[];
}

export default function LegalPage({
  active,
  title,
  icon,
  lastUpdated,
  tabs,
  categories,
}: LegalPageProps) {
  const [cookieConsentNotice, setCookieConsentNotice] = useState<string | null>(null);

  const openCookiePreferences = () => {
    const banner = document.getElementById('cookieConsentBanner');
    if (banner) {
      banner.classList.remove('hidden');
      setCookieConsentNotice('Cookie preferences opened.');
    } else {
      window.dispatchEvent(new CustomEvent('open-cookie-banner'));
      setCookieConsentNotice('Cookie preferences activated. You can also adjust settings from the Settings page.');
    }
  };

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const formatIconName = (name: string) => {
    return name.startsWith('fa-') ? name : `fa-${name}`;
  };

  return (
    <Page
      title={title}
      subtitle={`Official policy documentation for Ski Manager · Last updated: ${lastUpdated}`}
      icon={formatIconName(icon)}
      back="/dashboard"
      width="max-w-4xl"
      actions={
        <Button
          variant="secondary"
          size="sm"
          onPress={scrollToTop}
          className="min-h-11 sm:min-h-9"
        >
          <Icon name="fa-arrow-up" className="w-3.5 h-3.5" />
          <span>Top</span>
        </Button>
      }
    >
      <div className="space-y-6">
        {/* Navigation Tabs */}
        <div className="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-none border-b border-border">
          {tabs.map((tab) => {
            const isCurrent = tab.key === active;
            return (
              <a
                key={tab.key}
                href={tab.href}
                className={`inline-flex shrink-0 min-h-11 sm:min-h-9 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                  isCurrent
                    ? 'bg-primary text-primary-foreground font-semibold'
                    : 'border border-border bg-surface text-muted hover:text-foreground hover:bg-default'
                }`}
              >
                <Icon name={formatIconName(tab.icon)} className="w-3.5 h-3.5" />
                <span>{tab.label}</span>
              </a>
            );
          })}
        </div>

        {/* Header Metadata */}
        <div className="flex items-center justify-between gap-4 p-4 rounded-xl bg-surface border border-border">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
              <Icon name={formatIconName(icon)} className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-base font-bold text-foreground">{title}</h2>
              <p className="text-xs text-muted">Applicable to all players and visitors of ski-manager.net</p>
            </div>
          </div>
          <Chip size="sm" variant="soft" className="text-xs shrink-0">
            <Icon name="fa-clock" className="w-3 h-3 mr-1" />
            <span>Updated {lastUpdated}</span>
          </Chip>
        </div>

        {/* Content Section: Terms of Service */}
        {active === 'terms' && (
          <div className="space-y-4">
            <div className="rounded-xl border border-border bg-surface p-6 space-y-5 text-sm text-foreground/85 leading-relaxed">
              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">1. Acceptance of Terms</h3>
                <p>
                  By accessing or using Ski Manager at ski-manager.net (the &quot;Service&quot;), you agree to be bound by these Terms of Service (&quot;Terms&quot;). If you do not agree, do not use the Service. We may update these Terms at any time, and continued use constitutes acceptance of the updated Terms.
                </p>
                <p>
                  Your use of the Service is also governed by our <a href="/privacy" className="text-link hover:underline">Privacy Policy</a>, which details how we collect, use, and safeguard your information.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">2. Description of Service</h3>
                <p>
                  Ski Manager is a free-to-play browser-based ski resort management simulation game. Players design and administer virtual resorts, construct slopes, lifts, hotels, and infrastructure, employ staff, and compete on community leaderboards. The Service is provided &quot;as is&quot; and &quot;as available&quot; without guarantees of uninterrupted uptime.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">3. Eligibility</h3>
                <p>
                  You must be at least 13 years old (or 16 in the European Union / European Economic Area) to create an account and use the Service. By registering, you warrant that you satisfy this age requirement. If you are under 18, you represent that a parent or legal guardian has reviewed and consented to these Terms.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">4. Account Registration &amp; Security</h3>
                <p>To access persistence features, you must register through email/password, Google, or Discord. You agree to:</p>
                <ul className="list-disc pl-5 space-y-1 text-muted">
                  <li>Provide accurate and complete registration credentials</li>
                  <li>Maintain confidentiality of login sessions and credentials</li>
                  <li>Assume full responsibility for all activities conducted under your account</li>
                  <li>Immediately report any unauthorized access or security breaches</li>
                  <li>Refrain from transferring, sharing, or selling account access to third parties</li>
                </ul>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">5. Fair Play &amp; User Conduct</h3>
                <p>To preserve fair competition, you strictly agree NOT to:</p>
                <ul className="list-disc pl-5 space-y-1 text-muted">
                  <li>Exploit glitches, duplication bugs, or engine flaws (report them to our team instead)</li>
                  <li>Deploy automated scripts, bots, headless browsers, or macros to interact with the game</li>
                  <li>Create multi-accounting rings to manipulate leaderboards, markets, or tournaments</li>
                  <li>Harass, intimidate, or impersonate other players or administrators</li>
                  <li>Sell, purchase, or trade in-game currency or assets for real-world money</li>
                  <li>Reverse-engineer, decompile, or tamper with client communication protocols</li>
                </ul>
                <p className="text-xs text-danger font-medium mt-1">
                  Violations result in immediate account suspension, asset resets, or permanent bans.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">6. Virtual Currencies &amp; Items</h3>
                <p>
                  The Service features virtual currencies (Cash, Euros, and G&eacute;n&eacute;pis) and assets. These possess no real-world monetary value, cannot be redeemed or cashed out for legal tender, and do not constitute personal property. All in-game assets are licensed on a revocable basis.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">7. Season Rollovers &amp; Game Resets</h3>
                <p>
                  As part of competitive seasons, balance patches, or infrastructure migrations, seasonal standings and certain temporary assets may roll over or be rebalanced. Players retain lifetime achievements and account status.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">8. Intellectual Property</h3>
                <p>
                  All software code, graphic elements, interfaces, audio, mechanics, and branding are the property of Ski Manager and its licensors. Trail map imagery and geographical representations are licensed from Mapsynergy/Skimap. All rights reserved.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">9. Disclaimers &amp; Limitation of Liability</h3>
                <p className="text-xs text-muted uppercase tracking-wider font-mono">
                  THE SERVICE IS PROVIDED ON AN &quot;AS IS&quot; BASIS WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED. UNDER NO CIRCUMSTANCES SHALL SKI MANAGER OR ITS OPERATORS BE LIABLE FOR DIRECT, INDIRECT, INCIDENTAL, OR CONSEQUENTIAL DAMAGES ARISING FROM YOUR ACCESS OR LOSS OF DATA.
                </p>
              </section>

              <section className="space-y-2 pt-2 border-t border-border">
                <h3 className="text-base font-bold text-foreground">10. Contact Information</h3>
                <p>
                  Legal notices and terms inquiries should be directed to <a href="mailto:contact@ski-manager.net" className="text-link hover:underline">contact@ski-manager.net</a>.
                </p>
              </section>
            </div>
          </div>
        )}

        {/* Content Section: Privacy Policy */}
        {active === 'privacy' && (
          <div className="space-y-4">
            <div className="rounded-xl border border-border bg-surface p-6 space-y-5 text-sm text-foreground/85 leading-relaxed">
              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">1. Introduction</h3>
                <p>
                  Ski Manager (&quot;we&quot;, &quot;us&quot;, &quot;our&quot;) values your personal privacy. This policy outlines how information is collected, processed, and protected when you play on ski-manager.net.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">2. Information We Collect</h3>
                <div className="space-y-2 pl-3 border-l-2 border-primary/40">
                  <div>
                    <h4 className="font-semibold text-foreground">2.1 Account Credentials</h4>
                    <p className="text-xs sm:text-sm text-muted">
                      When creating an account, we record your chosen username, email address, and an argon2id-hashed password. For OAuth logins (Google or Discord), we receive identity tokens and email addresses; we never receive your external passwords.
                    </p>
                  </div>
                  <div>
                    <h4 className="font-semibold text-foreground">2.2 Game Telemetry &amp; State</h4>
                    <p className="text-xs sm:text-sm text-muted">
                      Resort configuration, slope constructions, fleet inventories, financial transactions, staff records, and achievement progress are saved to provide persistent gameplay.
                    </p>
                  </div>
                  <div>
                    <h4 className="font-semibold text-foreground">2.3 Automated Logging</h4>
                    <p className="text-xs sm:text-sm text-muted">
                      Server request logs capture IP addresses, user agent headers, referring paths, and timestamps to mitigate DDoS attacks and enforce rate limits.
                    </p>
                  </div>
                </div>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">3. How Information Is Used</h3>
                <ul className="list-disc pl-5 space-y-1 text-muted">
                  <li>Authenticating user sessions and safeguarding account ownership</li>
                  <li>Simulating game ticks, weather, visitor throughput, and financial balance</li>
                  <li>Enforcing community standards and fair play rules against exploiters</li>
                  <li>Providing anonymous performance metrics and telemetry via Sentry</li>
                </ul>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">4. Player Rights (GDPR &amp; CCPA)</h3>
                <p>Under applicable international privacy regulations, you hold the right to:</p>
                <ul className="list-disc pl-5 space-y-1 text-muted">
                  <li><strong>Access:</strong> Request a full copy of personal telemetry linked to your user ID.</li>
                  <li><strong>Rectification:</strong> Update your username, email, or credentials anytime via <a href="/settings" className="text-link hover:underline">Settings</a>.</li>
                  <li><strong>Erasure:</strong> Permanently delete your account and all associated mountain state. An account deletion option is built directly into Settings.</li>
                  <li><strong>Consent Withdrawal:</strong> Change your cookie preferences anytime via the Cookie Policy page or Settings.</li>
                </ul>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">5. Third-Party Advertising &amp; Google AdSense</h3>
                <p>
                  We partner with third-party advertising networks, primarily Google AdSense, to serve advertisements on certain pages of the Service. To deliver relevant and non-disruptive ads, these third-party vendors use cookies, web beacons, and device identifiers:
                </p>
                <ul className="list-disc pl-5 space-y-1 text-muted">
                  <li>
                    Third-party vendors, including Google, use cookies to serve ads based on a player&apos;s prior visits to ski-manager.net or other websites across the Internet.
                  </li>
                  <li>
                    Google&apos;s use of advertising cookies enables it and its partners to serve personalized or contextual advertisements to you based on your visits to our site and other destinations on the web.
                  </li>
                  <li>
                    Players may opt out of personalized advertising by visiting Google&apos;s <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener noreferrer" className="text-link hover:underline">Ads Settings</a>. Alternatively, you can opt out of third-party vendor cookies for personalized advertising by visiting <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer" className="text-link hover:underline">aboutads.info</a>.
                  </li>
                  <li>
                    For detailed information regarding how Google processes data when you interact with publisher partner sites, please review <a href="https://www.google.com/policies/privacy/partners/" target="_blank" rel="noopener noreferrer" className="text-link hover:underline">How Google uses data when you use our partners&apos; sites or apps</a>.
                  </li>
                </ul>
              </section>

              <section className="space-y-2 pt-2 border-t border-border">
                <h3 className="text-base font-bold text-foreground">6. Data Retention &amp; Security</h3>
                <p>
                  All passwords are encrypted with industry-standard hashing algorithms. Communications are secured via TLS 1.3 encryption. Inactive guest accounts or unconfirmed signups are purged after 90 days.
                </p>
              </section>
            </div>
          </div>
        )}

        {/* Content Section: Cookie Policy */}
        {active === 'cookies' && (
          <div className="space-y-5">
            <div className="rounded-xl border border-border bg-surface p-6 space-y-5 text-sm text-foreground/85 leading-relaxed">
              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">1. What Are Cookies?</h3>
                <p>
                  Cookies and local browser storage are lightweight text entries stored in your browser when loading web pages. They maintain persistent logins, store your preferred theme (Carbon Cloud or Winter), and record cookie preferences.
                </p>
              </section>

              <section className="space-y-3">
                <div className="flex items-center justify-between gap-4 flex-wrap">
                  <div>
                    <h3 className="text-base font-bold text-foreground">2. Your Cookie Choices</h3>
                    <p className="text-xs text-muted">Ski Manager honors Google Consent Mode v2 standards.</p>
                  </div>
                  <Button
                    variant="secondary"
                    size="sm"
                    onPress={openCookiePreferences}
                    className="min-h-11 sm:min-h-9"
                  >
                    <Icon name="fa-sliders" className="w-3.5 h-3.5 text-primary" />
                    <span>Manage Cookie Preferences</span>
                  </Button>
                </div>
                {cookieConsentNotice && (
                  <div className="p-3 rounded-lg bg-primary/10 border border-primary/30 text-xs text-primary font-medium">
                    {cookieConsentNotice}
                  </div>
                )}
              </section>

              {/* Cookie Tables */}
              <section className="space-y-4">
                <h3 className="text-base font-bold text-foreground">3. Active Storage &amp; Cookie Inventory</h3>

                <div className="space-y-2">
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-muted">3.1 Essential Cookies (Required for Gameplay)</h4>
                  <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full text-xs text-left">
                      <thead className="bg-card/80 border-b border-border text-muted font-medium">
                        <tr>
                          <th className="p-2.5">Key</th>
                          <th className="p-2.5">Purpose</th>
                          <th className="p-2.5">Duration</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        <tr>
                          <td className="p-2.5 font-mono text-primary font-medium">ci_session</td>
                          <td className="p-2.5 text-muted">User authentication and session security state</td>
                          <td className="p-2.5 font-mono">2 hours</td>
                        </tr>
                        <tr>
                          <td className="p-2.5 font-mono text-primary font-medium">csrf_cookie_name</td>
                          <td className="p-2.5 text-muted">Cross-site request forgery prevention token</td>
                          <td className="p-2.5 font-mono">2 hours</td>
                        </tr>
                        <tr>
                          <td className="p-2.5 font-mono text-primary font-medium">remember</td>
                          <td className="p-2.5 text-muted">Persistent player authentication token</td>
                          <td className="p-2.5 font-mono">30 days</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="space-y-2">
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-muted">3.2 Preference Storage (Browser LocalStorage)</h4>
                  <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full text-xs text-left">
                      <thead className="bg-card/80 border-b border-border text-muted font-medium">
                        <tr>
                          <th className="p-2.5">Key</th>
                          <th className="p-2.5">Purpose</th>
                          <th className="p-2.5">Duration</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">cookie_consent</td>
                          <td className="p-2.5 text-muted">Saved consent tier (all, analytics, or essential)</td>
                          <td className="p-2.5 font-mono">Persistent</td>
                        </tr>
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">theme</td>
                          <td className="p-2.5 text-muted">Selected color mode (carboncloud dark or winter light)</td>
                          <td className="p-2.5 font-mono">Persistent</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="space-y-2">
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-muted">3.3 Analytics &amp; Edge Protection</h4>
                  <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full text-xs text-left">
                      <thead className="bg-card/80 border-b border-border text-muted font-medium">
                        <tr>
                          <th className="p-2.5">Key</th>
                          <th className="p-2.5">Provider</th>
                          <th className="p-2.5">Purpose</th>
                          <th className="p-2.5">Duration</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">cf_clearance</td>
                          <td className="p-2.5 text-muted">Cloudflare</td>
                          <td className="p-2.5 text-muted">Edge bot defense and threat mitigation</td>
                          <td className="p-2.5 font-mono">30 minutes</td>
                        </tr>
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">_ga</td>
                          <td className="p-2.5 text-muted">Google Analytics</td>
                          <td className="p-2.5 text-muted">Anonymous aggregate visitor counts</td>
                          <td className="p-2.5 font-mono">2 years</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="space-y-2">
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-muted">3.4 Advertising &amp; Monetization (Google AdSense &amp; DoubleClick)</h4>
                  <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full text-xs text-left">
                      <thead className="bg-card/80 border-b border-border text-muted font-medium">
                        <tr>
                          <th className="p-2.5">Key</th>
                          <th className="p-2.5">Provider</th>
                          <th className="p-2.5">Purpose</th>
                          <th className="p-2.5">Duration</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">__gads</td>
                          <td className="p-2.5 text-muted">Google AdSense</td>
                          <td className="p-2.5 text-muted">Ad serving frequency capping and fraud prevention</td>
                          <td className="p-2.5 font-mono">13 months</td>
                        </tr>
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">__gpi</td>
                          <td className="p-2.5 text-muted">Google AdSense</td>
                          <td className="p-2.5 text-muted">Publisher ad analytics and audience performance</td>
                          <td className="p-2.5 font-mono">13 months</td>
                        </tr>
                        <tr>
                          <td className="p-2.5 font-mono text-foreground font-medium">IDE</td>
                          <td className="p-2.5 text-muted">Google DoubleClick</td>
                          <td className="p-2.5 text-muted">Ad targeting and conversion measurement across third-party websites</td>
                          <td className="p-2.5 font-mono">13 months</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </section>

              <section className="space-y-2 pt-2 border-t border-border">
                <h3 className="text-base font-bold text-foreground">4. Browser Cookie Controls</h3>
                <p className="text-xs sm:text-sm text-muted">
                  You can purge or reject cookies via your browser settings (Chrome, Firefox, Safari, Edge). Disabling essential cookies will prevent authentication and save synchronization.
                </p>
              </section>
            </div>
          </div>
        )}

        {/* Content Section: Disclaimer */}
        {active === 'disclaimer' && (
          <div className="space-y-4">
            <div className="rounded-xl border border-border bg-surface p-6 space-y-5 text-sm text-foreground/85 leading-relaxed">
              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">Simulation &amp; Entertainment Notice</h3>
                <p>
                  Ski Manager is an online management simulation designed exclusively for entertainment. All financial balances, revenue projections, guest metrics, loan interest rates, and operational calculations are procedurally computed simulations. Nothing on this website constitutes financial, architectural, civil engineering, or alpine safety counsel.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">Virtual Currency Notice</h3>
                <p>
                  All cash, balances, loans, and Génépis displayed inside Ski Manager are purely digital in-game tokens. They possess no real-world equivalent value, cannot be redeemed or transferred for legal tender, and are strictly non-refundable.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">Real-World Brand &amp; Trademark Disclaimers</h3>
                <p>
                  References to real ski equipment manufacturers (such as PistenBully, Prinoth, TechnoAlpin, Demaclenko, SMI, and HKD) are utilized for authentic historical context and immersion. All product names, registered trademarks, and logos belong to their respective corporate owners. Ski Manager is not endorsed by or affiliated with these entities.
                </p>
              </section>

              <section className="space-y-2">
                <h3 className="text-base font-bold text-foreground">Alpine Weather &amp; Trail Map Data</h3>
                <p>
                  Weather simulations, snowpack depth, avalanche hazard indices, and temperatures are generated procedurally by game algorithms. They do not reflect real-time meteorological observations in the Wasatch Mountains or any real ski resort.
                </p>
              </section>
            </div>
          </div>
        )}

        {/* Content Section: Sitemap */}
        {active === 'sitemap' && categories && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {categories.map((cat) => (
              <div key={cat.title} className="rounded-xl border border-border bg-surface p-4 flex flex-col justify-between">
                <div>
                  <div className="pb-3 flex items-center gap-2 border-b border-border">
                    <div className="w-7 h-7 rounded-md bg-primary/10 text-primary flex items-center justify-center shrink-0">
                      <Icon name={formatIconName(cat.icon)} className="w-3.5 h-3.5" />
                    </div>
                    <h3 className="text-sm font-bold text-foreground">{cat.title}</h3>
                  </div>
                  <div className="pt-3 space-y-2">
                    {cat.links.map((link) => (
                      <div key={link.href} className="group">
                        <a
                          href={link.href}
                          target={link.external ? '_blank' : undefined}
                          rel={link.external ? 'noopener noreferrer' : undefined}
                          className="text-xs font-medium text-foreground hover:text-primary transition-colors flex items-center justify-between min-h-11 sm:min-h-0"
                        >
                          <span>{link.label}</span>
                          {link.external ? (
                            <Icon name="fa-arrow-up-right-from-square" className="w-2.5 h-2.5 text-muted group-hover:text-primary" />
                          ) : (
                            <Icon name="fa-chevron-right" className="w-2.5 h-2.5 text-muted/50 group-hover:text-primary" />
                          )}
                        </a>
                        {link.desc && (
                          <p className="text-[11px] text-muted leading-tight">{link.desc}</p>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Bottom Navigation Links */}
        <div className="pt-6 border-t border-border flex flex-wrap items-center justify-center gap-3 text-xs text-muted">
          <a href="/terms" className="inline-flex min-h-11 items-center text-muted hover:text-foreground sm:min-h-0">Terms of Service</a>
          <span>·</span>
          <a href="/privacy" className="inline-flex min-h-11 items-center text-muted hover:text-foreground sm:min-h-0">Privacy Policy</a>
          <span>·</span>
          <a href="/cookies" className="inline-flex min-h-11 items-center text-muted hover:text-foreground sm:min-h-0">Cookie Policy</a>
          <span>·</span>
          <a href="/disclaimer" className="text-muted hover:text-foreground">Disclaimer</a>
          <span>·</span>
          <a href="/sitemap" className="text-muted hover:text-foreground">Sitemap</a>
          <span>·</span>
          <a href="/about" className="text-muted hover:text-foreground">About</a>
        </div>
      </div>
    </Page>
  );
}
