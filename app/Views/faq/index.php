<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>FAQ - Frequently Asked Questions<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $faqs = [
        ['What is Ski Manager?', "Ski Manager is a free-to-play browser-based game where you build and manage your own ski resort. You start with an empty mountain and a budget, then build slopes, hire staff, install lifts, manage finances, and compete with other players on the leaderboard. Think of it as a tycoon game set in the world of alpine skiing."],
        ['Is it really free to play?', "Yes, completely free. You can play the entire game without spending real money. There is an optional in-game currency called Génépis for speeding up certain actions and cosmetic extras, but nothing that gives a competitive advantage. The game is supported by ads."],
        ['How do I get started?', "Create a free account and you'll start with cash to build your first runs - between 200,000 and 1,000,000 depending on the difficulty you choose. A short tutorial guides you through hiring staff, building a slope, and setting lift ticket prices. Within a few minutes you'll have a basic resort running."],
        ['How does the game work day-to-day?', "Each real-world day the game processes a \"tick\" that calculates visitors based on weather, slopes, and marketing, collects ticket and building revenue, pays staff, processes loan payments, and updates equipment condition. Weather updates hour by hour and affects how many visitors come, so conditions shift throughout the day. Your job is to balance income against expenses and grow over time."],
        ['What can I build?', "Slopes (green, blue, red, black), ski lifts (chairlifts, gondolas, drag lifts), hotels, restaurants, rental shops, retail stores, terrain parks (halfpipes, jump lines, rail gardens), parking, energy sources (solar, wind, generators), and water systems. Each type generates different revenue and has its own maintenance needs."],
        ['What staff roles are available?', "Ski patrol (safety), instructors (guest satisfaction), lift mechanics (lift upkeep), groomer operators (slope conditions), receptionists (check-ins), chefs (restaurant revenue), medics (clinic), resort managers (efficiency bonus), snowmakers (artificial snow), and park crew (terrain park upkeep). Each role has a different salary and skill level."],
        ['How does weather affect my resort?', "Weather changes throughout the day and directly impacts visitor numbers. Sunny days with good snow bring the most visitors. Heavy snowfall builds your snow base but can hurt visibility. Blizzards and freezing rain cut visitor numbers sharply. You can offset bad weather with snowmaking, night skiing, and marketing."],
        ['What are Génépis?', "Génépis is the premium currency, named after the alpine herb used in traditional mountain liqueur. You earn small amounts through daily bonuses, achievements, and events. It can speed up construction, unlock resort analysis, and buy cosmetic features - never a competitive advantage over other players."],
        ['How do I earn more money?', "Revenue comes from lift ticket sales (your main income), buildings (hotels, restaurants, shops), parking fees, and terrain parks. To grow income: build more slopes and lifts, keep infrastructure in good condition, run marketing during slow stretches, and tune ticket prices to demand. Watch expenses too - salaries, fuel, loan payments, and maintenance add up."],
        ['Can I play on mobile?', "Yes. Ski Manager is fully responsive and works on phones and tablets through your mobile browser - no download required. Just visit ski-manager.net and log in. The interface adapts to smaller screens with a mobile-friendly menu."],
        ['How do I compete with other players?', "The leaderboard ranks players by cash, reputation, and resort rating. You earn reputation with high-quality slopes, good staff morale, and satisfied VIP guests. Tournaments run periodically with special rewards, and your resort rating (1-5 stars) reflects your infrastructure, staffing, amenities, and condition."],
        ['What happens if I run out of money?', "Take a loan from the bank for immediate cash (with interest). You can also sell equipment, demolish buildings for partial refunds, or reduce staff to cut salaries. If things get tight, temporarily close slopes or facilities to save on maintenance while you rebuild your finances."],
        ['How do I delete my account?', "Go to Settings and choose \"Delete Account.\" This permanently removes all your game data - resort, staff, finances, and achievements - and cannot be undone. If you just want a fresh start, contact an admin about resetting your account instead."],
        ['I found a bug. How do I report it?', "We appreciate bug reports. Use the contact form or our Discord server. Please include what you were doing, what you expected, and what actually happened - the more detail, the faster we can fix it."],
    ];
?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Frequently Asked Questions</h1>
    <p class="text-base-content/60 mb-6">Everything you need to know about Ski Manager. Can't find your answer? <a href="/contact" class="link link-primary">Contact us</a>.</p>

    <div class="space-y-3">
        <?php foreach ($faqs as $i => $faq) : ?>
        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="checkbox" <?= $i === 0 ? 'checked="checked"' : '' ?> />
            <div class="collapse-title font-semibold"><?= esc($faq[0]) ?></div>
            <div class="collapse-content text-sm text-base-content/70">
                <p><?= esc($faq[1]) ?></p>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <div class="card bg-base-200/50 shadow-sm mt-8"><div class="card-body p-5 text-center">
        <h2 class="font-bold mb-1">Still have questions?</h2>
        <p class="text-sm text-base-content/60 mb-3">We're happy to help.</p>
        <div class="flex gap-2 justify-center flex-wrap">
            <a href="/contact" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-envelope"></i> Contact Us</a>
            <a href="/support" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-headset"></i> Support</a>
        </div>
    </div></div>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    <?php foreach ($faqs as $i => $faq) : ?>
    {
      "@type": "Question",
      "name": <?= json_encode($faq[0]) ?>,
      "acceptedAnswer": { "@type": "Answer", "text": <?= json_encode($faq[1]) ?> }
    }<?= $i < count($faqs) - 1 ? ',' : '' ?>
    <?php endforeach ?>
  ]
}
</script>
<?= $this->endSection() ?>
