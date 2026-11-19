<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
ob_start();
$isLoggedIn = !empty($page['isLoggedIn']);
$season = $page['season'] ?? ['current' => 1, 'day' => 1, 'length' => 120, 'daysRemaining' => 120];
$weather = $page['weather'] ?? null;
?>
<div class="flex flex-col min-h-screen">
  <section class="relative isolate overflow-hidden bg-[#141b24] text-white py-14 sm:py-20 lg:py-24 border-b border-border min-h-[560px]">
    <div class="absolute inset-0 -z-20 bg-parkcity-map bg-cover bg-center opacity-75 transform scale-105 transition-transform duration-1000"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-t lg:bg-gradient-to-r from-[#0a0e14]/95 via-[#0a0e14]/80 to-[#0a0e14]/40"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
      <div class="lg:col-span-7 space-y-6">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs text-white/90 backdrop-blur-md border border-white/20">
          <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
          <span>Season <?= (int)$season['current'] ?> · Day <?= (int)$season['day'] ?> of <?= (int)$season['length'] ?> (<?= (int)$season['daysRemaining'] ?> days left)</span>
        </div>

        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight max-w-xl text-white">
          Run a ski resort on a <span class="text-[#7fb4ff]">real mountain.</span>
        </h1>

        <p class="text-base sm:text-lg text-white/85 leading-relaxed max-w-lg">
          Ski Manager is a free, browser-native simulation game. You start with the authentic Park City trail map, starting capital for your first lift lines, and a full season to make your resort thrive.
        </p>

        <div class="flex flex-wrap items-center gap-3 pt-2">
          <?php if (!$isLoggedIn) : ?>
            <a href="/register" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary/90 transition-all shadow-lg shadow-primary/25">
              <i class="fa-solid fa-play text-xs"></i>
              <span>Play Free</span>
            </a>
            <form action="/demo" method="post" class="contents">
              <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition-all backdrop-blur-sm cursor-pointer">Try Demo Mountain</button>
            </form>
            <a href="/login" class="inline-flex min-h-11 items-center justify-center text-sm font-medium text-white/80 hover:text-white underline underline-offset-4 px-3">Sign in →</a>
          <?php else : ?>
            <a href="/dashboard" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary/90 transition-all shadow-lg">
              <i class="fa-solid fa-mountain text-xs"></i>
              <span>Go to Dashboard</span>
            </a>
            <a href="/map" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition-all backdrop-blur-sm">
              <i class="fa-solid fa-map text-xs"></i>
              <span>Open Trail Map</span>
            </a>
          <?php endif; ?>
        </div>

        <p class="text-xs text-white/60">
          <strong class="text-white font-semibold"><?= esc($page['playerCountFormatted'] ?? '0') ?></strong> managers registered ·
          <strong class="text-white font-semibold"><?= esc($page['totalSlopesFormatted'] ?? '0') ?></strong> runs built across mountains
        </p>
      </div>

      <div class="lg:col-span-5">
        <div class="rounded-2xl border border-white/20 bg-black/40 backdrop-blur-xl p-5 sm:p-6 text-white shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-white/15 pb-3">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-snowflake text-[#7fb4ff]"></i>
              <h2 class="text-sm font-bold uppercase tracking-wider text-white">Live Snow Report</h2>
            </div>
            <span class="text-xs text-white/60">Park City · Today</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="p-3 rounded-xl bg-white/5 border border-white/10">
              <span class="text-[11px] font-medium uppercase text-white/60">Summit Temp</span>
              <div class="text-2xl font-bold mt-1 text-white tabular-nums"><?= esc($weather['temp'] ?? '–') ?></div>
              <div class="text-xs text-[#7fb4ff] mt-0.5 font-medium"><?= esc($weather['conditionName'] ?? 'Clear') ?></div>
            </div>

            <div class="p-3 rounded-xl bg-white/5 border border-white/10">
              <span class="text-[11px] font-medium uppercase text-white/60">Base Depth</span>
              <div class="text-2xl font-bold mt-1 text-white tabular-nums"><?= esc($weather['snowBase'] ?? '–') ?></div>
              <div class="text-xs text-white/70 mt-0.5"><?= esc($weather['snowfallText'] ?? 'No new snow') ?></div>
            </div>
          </div>

          <div class="pt-2 border-t border-white/15 flex items-center justify-between text-xs text-white/70">
            <span class="flex items-center gap-1.5">
              <i class="fa-solid fa-wind text-white/50"></i>
              <span>Wind: <strong><?= esc($weather['windText'] ?? '–') ?></strong></span>
            </span>
            <span class="flex items-center gap-1 text-[#7fb4ff] font-medium">
              <i class="fa-regular fa-clock"></i>
              <span>Updates hourly</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php
$fallback = ob_get_clean();
?>
<?= island('HomePage', $page, $fallback) ?>
<?= $this->endSection() ?>
