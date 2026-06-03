<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Cookie Policy<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Cookie Policy</h1>
    <p class="text-sm text-base-content/50 mb-6">Last updated: June 3, 2026</p>

    <div class="prose prose-sm max-w-none">
        <h2>1. What Are Cookies?</h2>
        <p>Cookies are small text files stored on your device when you visit a website. They help websites remember your preferences, keep you logged in, and understand how you use the site. We also use similar technologies such as localStorage and browser storage. References to "cookies" in this policy include these similar technologies where applicable.</p>

        <h2>2. Your Cookie Choices</h2>
        <p>When you first visit Ski Manager, a consent banner gives you three options:</p>
        <ul>
            <li><strong>Accept All</strong> &mdash; Enables analytics and advertising cookies</li>
            <li><strong>Analytics Only</strong> &mdash; Enables analytics cookies, blocks advertising cookies</li>
            <li><strong>Reject All</strong> &mdash; Blocks all non-essential cookies</li>
        </ul>
        <p>You can change your preference at any time through the cookie consent banner or, when logged in, from the <a href="/settings" class="link link-primary">Settings</a> page under "Cookie Preferences." We use Google Consent Mode v2 to enforce your choice across all Google services on our site.</p>

        <h2>3. Cookies We Use</h2>

        <p>The cookies listed below are examples currently in use and may change as we update our services and third-party integrations.</p>

        <h3>3.1 Essential Cookies</h3>
        <p>Required for the Service to function. These cookies are necessary for the operation of the Service and do not require consent under applicable law.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie/Storage</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>ci_session</code></td><td>User session management and authentication</td><td>2 hours</td></tr>
                    <tr><td><code>csrf_cookie_name</code></td><td>Security token to prevent cross-site request forgery</td><td>2 hours</td></tr>
                    <tr><td><code>remember</code></td><td>Persistent login ("Remember me")</td><td>30 days</td></tr>
                </tbody>
            </table>
        </div>

        <h3>3.2 Preference Storage</h3>
        <p>These store your settings and preferences using browser localStorage.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Key</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>cookie_consent</code></td><td>Your cookie consent preference (all, analytics, or none)</td><td>Persistent</td></tr>
                    <tr><td><code>theme</code></td><td>Your selected theme (light/dark)</td><td>Persistent</td></tr>
                    <tr><td><code>dashboard_edit_hint</code></td><td>Whether the dashboard edit hint has been dismissed</td><td>Persistent</td></tr>
                </tbody>
            </table>
        </div>

        <h3>3.3 Analytics Cookies</h3>
        <p>Help us understand how visitors use the Service. Only set if you choose "Accept All" or "Analytics Only."</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>_ga</code></td><td>Google Analytics</td><td>Distinguishes unique visitors</td><td>2 years</td></tr>
                    <tr><td><code>_ga_EXZWEK41WB</code></td><td>Google Analytics</td><td>Maintains session state</td><td>2 years</td></tr>
                </tbody>
            </table>
        </div>

        <h3>3.4 Advertising Cookies</h3>
        <p>Used by advertising partners to serve and measure ads. Only set if you choose "Accept All."</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>__gads</code></td><td>Google AdSense</td><td>Serves advertisements</td><td>13 months</td></tr>
                    <tr><td><code>__gpi</code></td><td>Google AdSense</td><td>Measures ad performance</td><td>13 months</td></tr>
                    <tr><td><code>__eoi</code></td><td>Google AdSense</td><td>Ad interaction tracking</td><td>6 months</td></tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-base-content/50 mt-2">These providers may process information according to their own privacy policies.</p>

        <h3>3.5 Security Cookies</h3>
        <p>Set by our CDN/security provider to protect against threats.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>cf_clearance</code></td><td>Cloudflare</td><td>Bot detection and security challenge clearance</td><td>30 minutes</td></tr>
                    <tr><td><code>__cf_bm</code></td><td>Cloudflare</td><td>Bot management</td><td>30 minutes</td></tr>
                </tbody>
            </table>
        </div>

        <p>Where required by law, non-essential cookies are only placed after obtaining your consent.</p>

        <h2>4. Google Consent Mode v2</h2>
        <p>We implement Google Consent Mode v2, which controls how Google tags behave based on your consent choice:</p>
        <ul>
            <li><strong>Accept All:</strong> <code>analytics_storage</code>, <code>ad_storage</code>, <code>ad_user_data</code>, and <code>ad_personalization</code> are all granted.</li>
            <li><strong>Analytics Only:</strong> <code>analytics_storage</code> is granted. All ad-related signals are denied.</li>
            <li><strong>Reject All:</strong> All signals are denied. Google may still use cookieless pings for basic measurement where legally permitted.</li>
        </ul>

        <h2>5. Managing Cookies</h2>
        <p>Besides our consent banner and Settings page, you can manage cookies through your browser:</p>
        <ul>
            <li><strong>Chrome:</strong> Settings &gt; Privacy and Security &gt; Cookies and other site data</li>
            <li><strong>Firefox:</strong> Settings &gt; Privacy &amp; Security &gt; Cookies and Site Data</li>
            <li><strong>Safari:</strong> Preferences &gt; Privacy &gt; Manage Website Data</li>
            <li><strong>Edge:</strong> Settings &gt; Cookies and Site Permissions</li>
        </ul>
        <p>Disabling essential cookies will prevent login and core game functionality.</p>

        <h2>6. Changes to This Policy</h2>
        <p>We may update this Cookie Policy as we add or change services. Updates will be posted on this page with a revised date.</p>

        <h2>7. Contact</h2>
        <p>Privacy and cookie-related requests may be sent to <a href="mailto:contact@ski-manager.net">contact@ski-manager.net</a>.</p>
    </div>
</div>
<?= $this->endSection() ?>
