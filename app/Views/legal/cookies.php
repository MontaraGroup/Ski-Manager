<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Cookie Policy<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Cookie Policy</h1>
    <p class="text-sm text-base-content/50 mb-6">Last updated: June 1, 2026</p>

    <div class="prose prose-sm max-w-none">
        <h2>1. What Are Cookies?</h2>
        <p>Cookies are small text files stored on your device when you visit a website. They help websites remember your preferences, keep you logged in, and understand how you use the site. Some cookies are essential for the site to function, while others help us improve your experience.</p>

        <h2>2. How We Use Cookies</h2>
        <p>Ski Manager uses cookies for the following purposes:</p>

        <h3>2.1 Essential Cookies</h3>
        <p>These cookies are required for the Service to function and cannot be disabled.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>sm_session</code></td><td>User session management and authentication</td><td>Session</td></tr>
                    <tr><td><code>csrf_token</code></td><td>Security token to prevent cross-site request forgery</td><td>Session</td></tr>
                    <tr><td><code>remember</code></td><td>Persistent login ("Remember me" feature)</td><td>30 days</td></tr>
                    <tr><td><code>cookie_consent</code></td><td>Stores your cookie consent preference</td><td>1 year</td></tr>
                </tbody>
            </table>
        </div>

        <h3>2.2 Preference Cookies</h3>
        <p>These cookies remember your settings and preferences.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>theme</code></td><td>Stores your selected theme (light/dark)</td><td>1 year</td></tr>
                </tbody>
            </table>
        </div>

        <h3>2.3 Analytics Cookies</h3>
        <p>These cookies help us understand how visitors use the Service.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>_ga, _ga_*</code></td><td>Google Analytics</td><td>Distinguishes unique visitors and tracks page views</td><td>2 years</td></tr>
                    <tr><td><code>_hj*</code></td><td>Hotjar</td><td>User behavior analysis (heatmaps, recordings)</td><td>1 year</td></tr>
                </tbody>
            </table>
        </div>

        <h3>2.4 Advertising Cookies</h3>
        <p>These cookies are used by advertising partners to serve relevant ads.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
                <tbody>
                    <tr><td><code>__gads, __gpi</code></td><td>Google AdSense</td><td>Serves and measures ad performance</td><td>13 months</td></tr>
                </tbody>
            </table>
        </div>

        <h2>3. Third-Party Cookies</h2>
        <p>Some cookies are set by third-party services that appear on our pages. We do not control these cookies. The third parties include Google (Analytics and AdSense), Hotjar, Cloudflare, and Sentry. Please refer to their respective privacy policies for more information.</p>

        <h2>4. Managing Cookies</h2>
        <p>When you first visit Ski Manager, a cookie consent banner allows you to accept or decline non-essential cookies. You can also manage cookies through your browser settings:</p>
        <ul>
            <li><strong>Chrome:</strong> Settings → Privacy and Security → Cookies</li>
            <li><strong>Firefox:</strong> Settings → Privacy & Security → Cookies</li>
            <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
            <li><strong>Edge:</strong> Settings → Cookies and Site Permissions</li>
        </ul>
        <p>Note that disabling essential cookies may prevent the Service from functioning correctly.</p>

        <h2>5. Changes to This Policy</h2>
        <p>We may update this Cookie Policy from time to time. Changes will be posted on this page with an updated date.</p>

        <h2>6. Contact</h2>
        <p>For questions about our use of cookies, contact us at <a href="mailto:contact@ski-manager.net">contact@ski-manager.net</a>.</p>
    </div>
</div>
<?= $this->endSection() ?>
