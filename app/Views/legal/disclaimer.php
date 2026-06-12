<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Disclaimer
</div>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-1"><i class="fa-solid fa-triangle-exclamation text-2xl text-primary"></i><h1 class="text-3xl font-bold">Disclaimer</h1></div>
    <div class="badge badge-ghost badge-sm mb-6"><i class="fa-solid fa-clock mr-1"></i>Last updated: June 3, 2026</div>

    <div class="card bg-base-100 shadow-sm"><div class="card-body prose prose-sm max-w-none">
        <h2>General Disclaimer</h2>
        <p>Ski Manager is an online entertainment game and simulation. All in-game financial figures, statistics, resort data, and scenarios are entirely fictional and intended for entertainment purposes only. Nothing in this game constitutes real financial, business, investment, or operational advice.</p>

        <h2>Virtual Currency</h2>
        <p>All currencies and monetary values within Ski Manager (including Euros and G&eacute;n&eacute;pis) are entirely virtual and have no real-world monetary value. They cannot be exchanged, refunded, or converted into real currency. In-game transactions do not involve real money.</p>

        <h2>Simulation Accuracy</h2>
        <p>While we strive to make Ski Manager realistic, all game mechanics are simplified for entertainment. Real ski resort operations involve significantly more complexity, regulation, and expertise. The game should not be used as a reference for actual resort management, construction, engineering, environmental planning, or financial decision-making.</p>

        <h2>Brand References</h2>
        <p>References to real-world companies, products, and brands (including PistenBully, Prinoth, TechnoAlpin, Demaclenko, HKD, and SMI) are used solely for identification, descriptive, and simulation purposes. All trademarks, brand names, and logos are property of their respective owners. Ski Manager is not affiliated with, endorsed by, or sponsored by any of these companies.</p>

        <h2>Trail Map</h2>
        <p>Certain trail map imagery and related materials are used under license from Mapsynergy (<a href="https://skimap.com" class="link link-primary" target="_blank" rel="noopener">skimap.com</a>) and remain the property of their respective owners. The map is a stylized artistic representation and does not depict real-time conditions, actual terrain, or any specific real-world ski resort.</p>

        <h2>Weather and Environmental Data</h2>
        <p>All weather conditions, snowfall amounts, temperatures, and environmental data within the game are procedurally generated for gameplay purposes. They do not reflect actual weather conditions at any real location.</p>

        <h2>Third-Party Advertisements</h2>
        <p>This website displays advertisements provided by third-party ad networks, including Google AdSense. We are not responsible for the content, accuracy, or claims made in third-party advertisements. The presence of an advertisement does not constitute an endorsement by Ski Manager of the advertised product, service, or company.</p>

        <h2>External Links</h2>
        <p>Ski Manager may contain links to external websites. We have no control over and are not responsible for the content, privacy practices, or availability of external sites. Visiting external links is at your own risk.</p>

        <h2>Leaderboards and User Content</h2>
        <p>Leaderboards, rankings, statistics, and player-generated resort names are provided for entertainment purposes and may contain inaccuracies, outdated information, or user-generated content.</p>

        <h2>Service Availability</h2>
        <p>Features, gameplay mechanics, virtual economies, and content may be modified, suspended, or removed at any time without notice.</p>

        <h2>Legal</h2>
        <p>This disclaimer supplements, and does not replace, the <a href="/terms" class="link link-primary">Terms of Service</a>. Additional warranty disclaimers and limitations of liability are contained in the Terms of Service.</p>

        
        <h2>Contact</h2>
        <p>Questions about this disclaimer? Contact us at <a href="mailto:contact@ski-manager.net">contact@ski-manager.net</a>.</p>
    </div>
</div>
    <div class="text-center mt-6"><a href="#" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;" class="btn btn-ghost btn-sm gap-1"><i class="fa-solid fa-arrow-up"></i> Back to top</a></div>
    <div class="divider mt-8 mb-4"></div>
    <div class="flex flex-wrap gap-2 justify-center text-sm">
        <a href="/terms" class="link link-hover">Terms</a><span class="text-base-content/30">·</span>
        <a href="/privacy" class="link link-hover">Privacy</a><span class="text-base-content/30">·</span>
        <a href="/cookies" class="link link-hover">Cookies</a><span class="text-base-content/30">·</span>
        <a href="/disclaimer" class="link link-hover">Disclaimer</a>
    </div>
</div>
<?= $this->endSection() ?>
