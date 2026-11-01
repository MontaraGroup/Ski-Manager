<?php
use App\Libraries\Economy as E;
$start = array_column(db_connect()->table('difficulty_config')->where('config_key', 'starting_cash')->get()->getResultArray(), 'config_value', 'difficulty');
?>
<p>You start with an empty mountain and a budget<?php if ($start) : ?> (<?= implode(', ', array_map(fn($d, $c) => currency((int) $c) . ' on ' . esc($d), array_keys($start), $start)) ?>)<?php endif ?>. Everything you build costs money up front and money every day, so the first winter is about building only what pays for itself.</p>

<h2 class="text-xl font-bold pt-2">What brings skiers</h2>
<p><b>Slopes.</b> Every open slope attracts skiers: about <?= (int) E::slopeDraw(0) ?> a day plus one more per 40 m of length, so a 1,000 m run draws around <?= (int) E::slopeDraw(1000) ?>. A worn slope draws fewer (down to <?= (int) (E::CONDITION_FLOOR * 100) ?>% at 0% condition), and the whole region only has room for about <?= number_format(E::MARKET_SIZE) ?> skiers a day before extra demand flattens out.</p>
<p><b>Lifts.</b> Skiers need lifts to reach the slopes. Without any lift you only get about <?= E::WALK_UP_VISITORS ?> walk-up skiers a day; with lifts, your daily ceiling is what they can carry (see the <a href="/guides/lifts" class="link link-primary">lifts guide</a>).</p>

<h2 class="text-xl font-bold pt-2">A sensible first build</h2>
<ol class="list-disc pl-6 space-y-1">
    <li>One or two slopes of moderate length near the base.</li>
    <li>A lift that serves them. A fixed chair carries up to <?= number_format(E::LIFT_CAPACITY['chair_fixed']) ?> skiers an hour.</li>
    <li>Ticket prices close to the reference prices (a full day around <?= currency(E::TICKET_REFERENCE['full_day']) ?>).</li>
    <li>A restaurant: about <?= (int) (E::BUILDING_SHARE['restaurant'] * 100) ?>% of your skiers use one.</li>
    <li>A groomer and someone to drive it, so your slopes don't wear out.</li>
</ol>

<h2 class="text-xl font-bold pt-2">How a game day works</h2>
<p>Every night the game runs a "tick" for every resort: it counts the day's skiers from your open slopes, prices, weather and lift capacity, collects ticket and building income, pays salaries and running costs, wears down slopes, lifts and machines, and runs the night grooming shift. The next morning your dashboard shows what happened. Small, steady improvements matter more than big gambles.</p>
