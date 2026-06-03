<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>FAQ - Frequently Asked Questions<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Frequently Asked Questions</h1>
    <p class="text-base-content/60 mb-6">Everything you need to know about Ski Manager. Can't find your answer? <a href="/contact" class="link link-primary">Contact us</a>.</p>

    <div class="space-y-3">
        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" checked="checked" />
            <div class="collapse-title font-semibold">What is Ski Manager?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Ski Manager is a free-to-play browser-based game where you build and manage your own ski resort. You start with an empty mountain and a budget, then build slopes, hire staff, install lifts, manage finances, and compete with other players on the leaderboard. Think of it as a tycoon game set in the world of alpine skiing.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">Is it really free to play?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Yes, completely free. You can play the entire game without spending any real money. We offer an optional premium currency called Génépis that lets you speed up certain actions or purchase cosmetic upgrades, but nothing that gives a competitive advantage. The game is supported by ads.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">How do I get started?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Create a free account, and you'll start with 500,000€ in cash. From there, the tutorial will guide you through hiring your first staff member, building a slope on the trail map, and setting your lift ticket prices. Within a few minutes you'll have a basic resort up and running. The game advances one day per real-world day, so check back daily to manage your resort and watch it grow.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">How does the game work day-to-day?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Every real-world day at midnight, the game processes a "tick" - this calculates your visitor count based on weather, slopes, and marketing, collects ticket and building revenue, pays staff salaries, processes loan payments, and updates equipment condition. The weather changes daily and affects how many visitors come. Your job is to balance income against expenses and grow your resort over time.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">What can I build?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>You can build slopes (green, blue, red, black), ski lifts (chairlifts, gondolas, drag lifts), hotels, restaurants, rental shops, retail stores, terrain parks (halfpipes, jump lines, rail gardens), parking lots, energy sources (solar, wind, generators), and water management systems. Each building type generates different revenue and has unique maintenance requirements.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">What staff roles are available?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>You can hire ski patrol (safety), instructors (guest satisfaction), lift mechanics (lift maintenance), groomer operators (slope conditions), receptionists (check-ins), chefs (restaurant revenue), medics (clinic), resort managers (efficiency bonus), snowmakers (artificial snow), and park crew (terrain park maintenance). Each role has a different salary and skill level.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">How does weather affect my resort?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Weather changes daily and directly impacts your visitor count. Sunny days with good snow bring the most visitors. Heavy snowfall adds to your snow base but can reduce visibility. Blizzards and freezing rain significantly reduce visitor numbers. You can offset bad weather with snowmaking, night skiing, and marketing campaigns to keep visitors coming.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">What are Génépis?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Génépis is the premium currency in Ski Manager, named after the alpine herb used to make a traditional mountain liqueur. You earn small amounts through daily bonuses, achievements, and special events. Génépis can be used to purchase resort analysis reports, speed up construction, and unlock cosmetic features. They cannot be used to gain competitive advantages over other players.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">How do I earn more money?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Revenue comes from several sources: lift ticket sales (your primary income), building revenue (hotels, restaurants, shops), parking fees, and terrain park visitors. To maximize income, build more slopes and lifts to attract visitors, keep your infrastructure in good condition, run marketing campaigns during slow periods, and adjust ticket prices based on demand. Keep an eye on expenses too - staff salaries, equipment fuel, loan payments, and maintenance costs can eat into profits.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">Can I play on mobile?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Yes! Ski Manager is fully responsive and works on phones and tablets through your mobile browser. No app download required - just visit v2.ski-manager.net and log in. The interface adapts to smaller screens with a mobile-friendly navigation menu.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">How do I compete with other players?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>The leaderboard ranks all players by cash, reputation, and resort rating. You earn reputation by maintaining high-quality slopes, keeping staff morale up, and satisfying VIP guests. Tournaments run periodically with special rewards. Your resort rating (1-5 stars) is calculated based on your infrastructure, staffing, amenities, and overall resort condition.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">What happens if I run out of money?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>If cash runs low, you can take out a loan from the bank. Loans provide immediate cash but come with interest payments. You can also sell equipment, demolish buildings for partial refunds, or fire staff to reduce salary costs. If things get really tight, close some slopes or facilities temporarily to save on maintenance while you rebuild your finances.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">How do I delete my account?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>Go to Account Settings and click "Delete Account." This permanently removes all your game data, including your resort, staff, finances, and achievements. This action cannot be undone. If you just want a fresh start, contact an admin about resetting your account instead.</p>
            </div>
        </div>

        <div class="collapse collapse-arrow bg-base-100 shadow-sm">
            <input type="radio" name="faq" />
            <div class="collapse-title font-semibold">I found a bug. How do I report it?</div>
            <div class="collapse-content text-sm text-base-content/70">
                <p>We appreciate bug reports! You can report bugs through our <a href="/bugs" class="link link-primary">bug tracker on GitLab</a>, via the <a href="/contact" class="link link-primary">contact form</a>, or on our Discord server. Please include as much detail as possible - what you were doing, what you expected to happen, and what happened instead.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
