<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Season 1 Coming Soon | Ski Manager</title>
    <link rel="stylesheet" href="/css/style.css?v=1780692626">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body{background:#0b0e14;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,-apple-system,sans-serif;color:#c9d1d9}
        .countdown-wrap{text-center;max-width:480px;padding:2rem}
        .countdown-wrap h1{font-size:2rem;font-weight:700;margin-bottom:.5rem}
        .countdown-wrap .sub{color:#8b949e;margin-bottom:2.5rem;font-size:.95rem}
        .timer{display:flex;justify-content:center;gap:1.5rem;margin-bottom:2.5rem}
        .timer-block{text-align:center}
        .timer-block .num{font-size:3rem;font-weight:700;color:#6ee7b7;line-height:1;font-variant-numeric:tabular-nums}
        .timer-block .label{font-size:.65rem;text-transform:uppercase;letter-spacing:.1em;color:#8b949e;margin-top:.25rem}
        .sep{font-size:2.5rem;color:#2d333b;line-height:1;padding-top:.25rem}
        .tagline{color:#6b7280;font-size:.85rem;line-height:1.5;margin-bottom:2rem}
        .back-link{color:#8b949e;font-size:.85rem;text-decoration:none;transition:color .2s}
        .back-link:hover{color:#c9d1d9}
        .logo{font-size:1.1rem;font-weight:700;margin-bottom:2rem;color:#8b949e}
    </style>
</head>
<body>
    <div class="countdown-wrap" style="text-align:center">
        <p class="logo"><i class="fa-solid fa-mountain-sun"></i> Ski Manager</p>
        <h1>Season 1: Park City</h1>
        <p class="sub">The mountain opens at midnight.</p>
        <div class="timer">
            <div class="timer-block"><div class="num" id="hrs">--</div><div class="label">Hours</div></div>
            <div class="sep">:</div>
            <div class="timer-block"><div class="num" id="min">--</div><div class="label">Minutes</div></div>
            <div class="sep">:</div>
            <div class="timer-block"><div class="num" id="sec">--</div><div class="label">Seconds</div></div>
        </div>
        <p class="tagline">Start with an empty mountain and &euro;500,000. Build lifts, hire staff, manage snowmaking, and survive 135 days without going bankrupt.</p>
        <a href="/" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
    </div>
    <script data-cfasync="false">
        var target=new Date('2026-06-07T04:00:00Z').getTime();
        function tick(){
            var diff=Math.max(0,target-Date.now());
            if(diff<=0){location.href='/dashboard';return;}
            var h=Math.floor(diff/3600000),m=Math.floor((diff%3600000)/60000),s=Math.floor((diff%60000)/1000);
            document.getElementById('hrs').textContent=String(h).padStart(2,'0');
            document.getElementById('min').textContent=String(m).padStart(2,'0');
            document.getElementById('sec').textContent=String(s).padStart(2,'0');
        }
        tick();setInterval(tick,1000);
    </script>
</body>
</html>
