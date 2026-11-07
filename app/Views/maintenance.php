<!DOCTYPE html>
<html lang="en" data-theme="carboncloud">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Scheduled Maintenance: Season 2 In Development | Ski Manager</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        :root {
            color-scheme: dark;
        }
        body {
            background-color: #0c1524;
            background-image:
                radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.35) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 118, 110, 0.25) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(17, 24, 39, 0.8) 0px, transparent 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        @media (prefers-reduced-motion: reduce) {
            .pulse-indicator, .glow-badge {
                animation: none !important;
            }
        }
        .pulse-indicator {
            animation: pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.95); }
        }
    </style>
</head>
<body class="text-slate-100 flex flex-col justify-between p-4 sm:p-6 lg:p-8 font-sans antialiased selection:bg-amber-500 selection:text-neutral-900">

    <!-- Top Navigation / Status Header -->
    <header class="max-w-5xl mx-auto w-full flex items-center justify-between py-4 border-b border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-bold text-xl shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-person-skiing" aria-hidden="true"></i>
            </div>
            <div>
                <span class="text-lg font-black tracking-tight text-white block leading-tight">Ski Manager</span>
                <span class="text-xs text-slate-400 font-mono tracking-wider uppercase">Alpine Ops Sector</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/30">
                <span class="w-2 h-2 rounded-full bg-amber-400 pulse-indicator" aria-hidden="true"></span>
                <span>Season 2 Maintenance</span>
            </span>
        </div>
    </header>

    <!-- Main Content Stage -->
    <main class="max-w-4xl mx-auto w-full my-auto py-10 sm:py-16 text-center">

        <!-- Stage Hero Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-mono font-medium tracking-wide bg-slate-800/80 border border-slate-700/80 text-amber-300 mb-6 shadow-inner">
            <i class="fa-solid fa-clock-rotate-left text-amber-400" aria-hidden="true"></i>
            <span>TEMPORARY SITE PAUSE &bull; 4-DAY MAINTENANCE WINDOW</span>
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white mb-6 leading-tight">
            Preparing Park City for <br class="hidden sm:inline">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400">Season 2 Launch</span>
        </h1>

        <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto mb-10 leading-relaxed font-normal">
            <?= esc($maintenance['message'] ?? 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for the launch of Season 2.') ?>
        </p>

        <!-- Live Countdown Timer -->
        <section aria-labelledby="countdown-heading" class="mb-14">
            <h2 id="countdown-heading" class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-400 mb-4">
                Estimated Reopening Countdown (October 25, 2026 &bull; 18:00 UTC)
            </h2>

            <div class="grid grid-cols-4 gap-2 sm:gap-4 max-w-xl mx-auto text-center" id="countdown-timer" aria-live="polite">
                <!-- Days -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3 sm:p-5 shadow-xl backdrop-blur-md">
                    <div class="text-3xl sm:text-5xl font-black font-mono text-white tracking-tight" id="count-days">--</div>
                    <div class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Days</div>
                </div>

                <!-- Hours -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3 sm:p-5 shadow-xl backdrop-blur-md">
                    <div class="text-3xl sm:text-5xl font-black font-mono text-white tracking-tight" id="count-hours">--</div>
                    <div class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Hours</div>
                </div>

                <!-- Minutes -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3 sm:p-5 shadow-xl backdrop-blur-md">
                    <div class="text-3xl sm:text-5xl font-black font-mono text-white tracking-tight" id="count-minutes">--</div>
                    <div class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Minutes</div>
                </div>

                <!-- Seconds -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3 sm:p-5 shadow-xl backdrop-blur-md">
                    <div class="text-3xl sm:text-5xl font-black font-mono text-amber-400 tracking-tight" id="count-seconds">--</div>
                    <div class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Seconds</div>
                </div>
            </div>
        </section>

        <!-- Informational Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 text-left max-w-3xl mx-auto mb-10">

            <!-- Protection Guarantee Card -->
            <div class="bg-slate-900/70 border border-emerald-500/20 rounded-2xl p-5 sm:p-6 backdrop-blur-md">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Progress Safely Frozen</h3>
                        <span class="text-xs text-emerald-400 font-medium">Zero Expenses or Decay</span>
                    </div>
                </div>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    All resort assets, bank balances, slopes, lifts, and staff are completely preserved. The game tick is paused while offline, so you will not incur any wages, loan interest, or maintenance costs.
                </p>
            </div>

            <!-- Season 2 Preview Card -->
            <div class="bg-slate-900/70 border border-amber-500/20 rounded-2xl p-5 sm:p-6 backdrop-blur-md">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-mountain-sun" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">What's In Season 2</h3>
                        <span class="text-xs text-amber-400 font-medium">Major Expansion Preview</span>
                    </div>
                </div>
                <ul class="text-xs sm:text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong class="text-white">Sector 2 Unlocked:</strong> Advanced Peaks & Powder Bowls</li>
                    <li><strong class="text-white">Segment Builder:</strong> Intuitive lift and slope connections</li>
                    <li><strong class="text-white">Pioneer Badges:</strong> Season 1 leaderboard podium rewards</li>
                </ul>
            </div>
        </div>

        <!-- Community Call-to-Action -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
            <a href="https://discord.gg/u3GzGt8K6a" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium min-h-11 py-2.5 px-6 gap-2 shadow-lg shadow-indigo-600/20 transition-colors w-full sm:w-auto">
                <i class="fa-brands fa-discord text-lg" aria-hidden="true"></i>
                <span>Join Official Discord</span>
            </a>
            <button onclick="window.location.reload()" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-transparent text-slate-300 hover:bg-slate-800 hover:text-white font-medium min-h-11 py-2.5 px-6 gap-2 transition-colors w-full sm:w-auto">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                <span>Check Status</span>
            </button>
        </div>

    </main>

    <!-- Footer -->
    <footer class="max-w-5xl mx-auto w-full pt-6 pb-2 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-3">
        <div>
            &copy; <?= date('Y') ?> Ski Manager &bull; HTTP 503 Service Temporarily Paused
        </div>
        <div class="flex items-center gap-4">
            <a href="https://discord.gg/u3GzGt8K6a" target="_blank" rel="noopener noreferrer" class="hover:text-slate-300 transition-colors">
                <i class="fa-brands fa-discord mr-1" aria-hidden="true"></i> Community
            </a>
            <span class="text-slate-700">&bull;</span>
            <a href="/login" class="hover:text-amber-400 transition-colors flex items-center gap-1" title="Administrator and Developer sign in">
                <i class="fa-solid fa-lock text-[10px]" aria-hidden="true"></i> Staff / Admin Portal
            </a>
        </div>
    </footer>

    <!-- Countdown Engine -->
    <script>
    (function() {
        // Target: October 25, 2026, 18:00:00 UTC
        var targetTimeStr = <?= json_encode($maintenance['until'] ?? '2026-10-25 18:00:00 UTC') ?>;
        var targetDate = new Date(targetTimeStr);
        if (isNaN(targetDate.getTime())) {
            targetDate = new Date(Date.UTC(2026, 9, 25, 18, 0, 0)); // Month is 0-indexed: 9 = Oct
        }

        var elDays = document.getElementById('count-days');
        var elHours = document.getElementById('count-hours');
        var elMins = document.getElementById('count-minutes');
        var elSecs = document.getElementById('count-seconds');

        function updateCountdown() {
            var now = new Date().getTime();
            var distance = targetDate.getTime() - now;

            if (distance <= 0) {
                if (elDays) elDays.textContent = '00';
                if (elHours) elHours.textContent = '00';
                if (elMins) elMins.textContent = '00';
                if (elSecs) elSecs.textContent = '00';
                return;
            }

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            if (elDays) elDays.textContent = String(days).padStart(2, '0');
            if (elHours) elHours.textContent = String(hours).padStart(2, '0');
            if (elMins) elMins.textContent = String(minutes).padStart(2, '0');
            if (elSecs) elSecs.textContent = String(seconds).padStart(2, '0');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    })();
    </script>
</body>
</html>
