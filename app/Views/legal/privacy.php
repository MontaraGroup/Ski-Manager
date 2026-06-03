<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Privacy Policy<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Privacy Policy</h1>
    <p class="text-sm text-base-content/50 mb-6">Last updated: June 3, 2026</p>

    <div class="prose prose-sm max-w-none">
        <h2>1. Introduction</h2>
        <p>Ski Manager ("we", "our", "us") operates skimanager.net (the "Service"). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and online game.</p>

        <h2>2. Information We Collect</h2>
        <h3>2.1 Account Information</h3>
        <p>When you create an account, we collect your username, email address, and password (stored in hashed form using industry-standard algorithms). If you sign in with Google, we receive your name, email address, and Google account identifier. We do not receive or store your Google password.</p>
        <h3>2.2 Game Data</h3>
        <p>We store all game-related data associated with your account, including resort statistics, financial transactions, staff records, equipment, achievements, notifications, and activity logs. This data is necessary to provide the game experience.</p>
        <h3>2.3 Automatically Collected Information</h3>
        <p>When you access the Service, we automatically collect certain information including your IP address, browser type and version, operating system, device type, screen resolution, referring URLs, pages visited, session duration, and timestamps. This information is collected through server logs and analytics tools.</p>
        <h3>2.4 Cookies and Tracking</h3>
        <p>We use cookies and similar tracking technologies to maintain your session, remember your preferences (such as theme and unit system), and analyze site usage. You can control cookie preferences through our consent banner, which offers three options: Accept All, Analytics Only, or Reject All. You can change your preference at any time from the Settings page. For full details, see our <a href="/cookies" class="link link-primary">Cookie Policy</a>.</p>

        <h2>3. How We Use Your Information</h2>
        <p>We use the information we collect to:</p>
        <ul>
            <li>Provide, operate, and maintain the game</li>
            <li>Create and manage your account</li>
            <li>Save your game progress, preferences, and settings</li>
            <li>Display leaderboards and public rankings (username and resort stats only)</li>
            <li>Communicate with you about your account or the Service</li>
            <li>Monitor and analyze usage patterns to improve the Service</li>
            <li>Display advertisements via Google AdSense</li>
            <li>Detect, prevent, and address technical issues, abuse, and cheating</li>
            <li>Enforce login rate limiting for security purposes</li>
            <li>Comply with legal obligations</li>
        </ul>

        <h2>4. Third-Party Services</h2>
        <p>We use the following third-party services that may collect information:</p>
        <ul>
            <li><strong>Google Analytics 4</strong> &mdash; Website usage analytics including page views, session duration, and user interactions. Subject to <a href="https://policies.google.com/privacy" class="link link-primary" target="_blank" rel="noopener">Google's Privacy Policy</a>. We use Google Consent Mode v2 to respect your cookie preferences.</li>
            <li><strong>Google Tag Manager</strong> &mdash; Tag management for analytics and advertising scripts.</li>
            <li><strong>Google AdSense</strong> &mdash; Advertising. Google may use cookies to serve ads based on your browsing history. You can opt out of personalized ads at <a href="https://adssettings.google.com" class="link link-primary" target="_blank" rel="noopener">Google Ads Settings</a>.</li>
            <li><strong>Google Sign-In</strong> &mdash; Optional authentication. We receive basic profile information (name, email) when you choose to sign in with Google.</li>
            <li><strong>Cloudflare</strong> &mdash; CDN, DNS, security, and performance optimization. Cloudflare may process connection data per their <a href="https://www.cloudflare.com/privacypolicy/" class="link link-primary" target="_blank" rel="noopener">Privacy Policy</a>.</li>
            <li><strong>Sentry</strong> &mdash; Error tracking and performance monitoring. Collects technical error data to help us fix bugs.</li>
        </ul>

        <h2>5. Google Consent Mode</h2>
        <p>We implement Google Consent Mode v2, which adjusts how Google tags behave based on your cookie preferences. When you choose "Analytics Only," advertising cookies are blocked while analytics continue. When you choose "Reject All," all non-essential Google tracking is disabled. Google may still use cookieless modeling to estimate aggregate trends where permitted.</p>

        <h2>6. Data Sharing</h2>
        <p>We do not sell your personal information. We may share information only in these circumstances:</p>
        <ul>
            <li>With your explicit consent</li>
            <li>To comply with legal obligations or valid legal processes</li>
            <li>To protect our rights, privacy, safety, or property</li>
            <li>In connection with a merger, acquisition, or sale of assets (you would be notified)</li>
            <li>Publicly visible game data: leaderboard rankings display your username and resort statistics only</li>
        </ul>

        <h2>7. Data Retention</h2>
        <p>We retain your account and game data for as long as your account is active. If you delete your account, all associated personal data will be permanently removed within 30 days. Server logs are retained for up to 90 days. Anonymized analytics data may be retained indefinitely for statistical purposes.</p>

        <h2>8. Data Security</h2>
        <p>We implement security measures including:</p>
        <ul>
            <li>HTTPS/TLS encryption for all connections</li>
            <li>Bcrypt password hashing</li>
            <li>CSRF token protection on all forms</li>
            <li>Login rate limiting (5 failed attempts triggers a 30-minute lockout)</li>
            <li>Secure session management</li>
            <li>Cloudflare DDoS protection and WAF</li>
        </ul>
        <p>However, no method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security.</p>

        <h2>9. Your Rights</h2>
        <p>Depending on your jurisdiction, you may have the right to:</p>
        <ul>
            <li>Access the personal data we hold about you</li>
            <li>Request correction of inaccurate data</li>
            <li>Request deletion of your account and data</li>
            <li>Object to or restrict processing of your data</li>
            <li>Data portability (receive your data in a structured format)</li>
            <li>Withdraw consent at any time (including cookie preferences via Settings)</li>
        </ul>
        <p><strong>For EU/EEA users (GDPR):</strong> You have the right to lodge a complaint with your local data protection authority.</p>
        <p><strong>For California users (CCPA):</strong> You have the right to know what personal information is collected, request deletion, and opt out of the sale of personal information. We do not sell personal information.</p>
        <p>To exercise any rights, contact us at <a href="mailto:contact@ski-manager.net">contact@ski-manager.net</a> or use the account deletion feature in <a href="/settings" class="link link-primary">Settings</a>.</p>

        <h2>10. Children's Privacy</h2>
        <p>Ski Manager is not intended for children under 13 years of age (or 16 in the EU/EEA). We do not knowingly collect personal information from children under these ages. If you are a parent and believe your child has provided us with personal information, please contact us and we will promptly delete such data.</p>

        <h2>11. International Data Transfers</h2>
        <p>The Service is hosted in the European Union. If you access the Service from outside the EU, your information may be transferred to and processed in the EU. By using the Service, you consent to this transfer. We rely on standard contractual clauses where applicable.</p>

        <h2>12. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. We will notify you of material changes by posting the new policy on this page and updating the "Last updated" date. Continued use of the Service after changes constitutes acceptance of the updated policy.</p>

        <h2>13. Contact Us</h2>
        <p>If you have questions about this Privacy Policy, contact us at <a href="mailto:contact@ski-manager.net">contact@ski-manager.net</a>.</p>
    </div>
</div>
<?= $this->endSection() ?>
