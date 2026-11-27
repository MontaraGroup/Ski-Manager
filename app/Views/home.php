<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
ob_start();
$isLoggedIn = !empty($page['isLoggedIn']);
$season = $page['season'] ?? ['current' => 1, 'day' => 1, 'length' => 120, 'daysRemaining' => 120];
$weather = $page['weather'] ?? null;
?>
<?php
$left = $season['daysRemaining'] > 0
    ? $season['daysRemaining'] . ' ' . ($season['daysRemaining'] === 1 ? 'day' : 'days') . ' left in Season ' . (int) $season['current']
    : 'Season ' . (int) $season['current'] . ' ends today';
$btn = 'inline-flex min-h-12 items-center justify-center gap-2 rounded-lg px-6 text-base font-semibold';
?>
<section class="relative isolate overflow-hidden bg-[#141b24] text-white">
  <div aria-hidden="true" class="absolute inset-0 -z-20 bg-parkcity-map bg-cover bg-center opacity-75"></div>
  <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t lg:bg-gradient-to-r from-[#0a0e14]/95 via-[#0a0e14]/80 to-[#0a0e14]/45"></div>
  <div class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-10 px-4 py-12 sm:px-6 sm:py-20 lg:grid-cols-12 lg:px-8 lg:py-24">
    <div class="flex flex-col gap-5 lg:col-span-7">
      <?php if ($isLoggedIn) : ?>
        <h1 class="text-[2rem] font-bold leading-tight tracking-tight text-white sm:text-5xl">Welcome back<?= !empty($page['username']) ? ', ' . esc($page['username']) : '' ?>.</h1>
        <p class="max-w-lg text-base leading-relaxed text-white/85 sm:text-lg"><?= esc($left) ?>. Check last night's numbers on the dashboard, or open the map and keep building.</p>
        <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:flex-wrap sm:items-center">
          <a href="/dashboard" class="<?= $btn ?> bg-accent text-accent-foreground">Open dashboard</a>
          <a href="/map" class="<?= $btn ?> border border-white/30 bg-white/10 text-white">Trail map</a>
        </div>
      <?php else : ?>
        <h1 class="max-w-xl text-[2rem] font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-[3.25rem]">Run a ski resort on a real mountain.</h1>
        <p class="max-w-lg text-base leading-relaxed text-white/85 sm:text-lg">Build lifts and cut runs on the Park City trail map. Hire patrol and groomers, set ticket prices, and get through the weather, one game day at a time. Free, in your browser.</p>
        <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:flex-wrap sm:items-center">
          <a href="/register" class="<?= $btn ?> w-full bg-accent text-accent-foreground sm:w-auto">Play free</a>
          <form action="/demo" method="post" class="contents">
            <?= csrf_field() ?>
            <button type="submit" class="<?= $btn ?> w-full cursor-pointer border border-white/30 bg-white/10 px-5 text-white sm:w-auto">Try a demo resort</button>
          </form>
          <a href="/login" class="inline-flex min-h-11 items-center justify-center px-2 text-sm font-medium text-white/85 underline underline-offset-4">Log in</a>
        </div>
        <p class="text-sm text-white/70">The demo needs no account: you get a resort with lifts and runs already built.</p>
      <?php endif; ?>
      <p class="flex flex-wrap gap-x-4 gap-y-1 border-t border-white/15 pt-4 text-sm text-white/75">
        <span><strong class="font-semibold tabular-nums text-white"><?= esc($page['playerCountFormatted'] ?? '0') ?></strong> players signed up</span>
        <span><strong class="font-semibold tabular-nums text-white"><?= esc($page['totalSlopesFormatted'] ?? '0') ?></strong> runs built</span>
      </p>
    </div>
    <div class="lg:col-span-5">
      <section aria-labelledby="home-snow" class="overflow-hidden rounded-xl bg-[oklch(97%_0.006_250)] text-[oklch(26%_0.04_260)] shadow-xl">
        <div class="flex items-center justify-between gap-3 bg-[oklch(24%_0.03_260)] px-4 py-3 text-white sm:px-5">
          <h2 id="home-snow" class="text-sm font-bold uppercase tracking-wider">Park City snow report</h2>
          <span class="text-xs text-white/75">In game, now</span>
        </div>
        <?php if ($weather) : ?>
          <dl class="grid grid-cols-2 gap-px bg-[oklch(88%_0.01_250)]">
            <?php foreach ([
                ['Temperature', $weather['temp'], $weather['conditionName']],
                ['Base depth', $weather['snowBase'], $weather['snowfallText']],
                ['Wind', $weather['windText'], ''],
                ['Season', 'Day ' . (int) $season['day'], 'of ' . (int) $season['length']],
            ] as [$label, $value, $note]) : ?>
              <div class="bg-[oklch(97%_0.006_250)] px-4 py-3 sm:px-5 sm:py-4">
                <dt class="text-xs font-medium text-[oklch(42%_0.03_260)]"><?= esc($label) ?></dt>
                <dd class="mt-0.5 text-2xl font-bold tabular-nums sm:text-[1.75rem]"><?= esc($value) ?></dd>
                <?php if ($note !== '') : ?><dd class="text-xs text-[oklch(42%_0.03_260)]"><?= esc($note) ?></dd><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </dl>
        <?php else : ?>
          <p class="px-5 py-6 text-sm">Today's report isn't ready yet. It's written when the game day starts.</p>
        <?php endif; ?>
        <p class="px-4 py-3 text-xs text-[oklch(42%_0.03_260)] sm:px-5"><?= esc($left) ?></p>
      </section>
    </div>
  </div>
</section>
<?php
$fallback = ob_get_clean();
?>
<?= island('HomePage', $page, $fallback) ?>
<?= $this->endSection() ?>
