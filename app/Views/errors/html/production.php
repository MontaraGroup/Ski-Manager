<!DOCTYPE html>
<html lang="en" data-theme="carboncloud">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something went wrong - Ski Manager</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#1a1a2e;color:#a6adbb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;overflow:hidden}
        .container{text-align:center;padding:2rem;max-width:480px;position:relative;z-index:1}
        .mountain{font-size:80px;margin-bottom:1rem;filter:grayscale(0.3)}
        h1{font-size:1.5rem;color:#e8e8e8;margin-bottom:0.5rem;font-weight:700}
        p{font-size:0.95rem;color:#8b92a0;margin-bottom:1.5rem;line-height:1.6}
        .actions{display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;gap:0.5rem;padding:0.6rem 1.25rem;border-radius:8px;font-size:0.875rem;font-weight:600;text-decoration:none;transition:all 0.2s}
        .btn-primary{background:#6419e6;color:#fff}
        .btn-primary:hover{background:#5415c0;transform:translateY(-1px)}
        .btn-ghost{background:rgba(255,255,255,0.06);color:#a6adbb;border:1px solid rgba(255,255,255,0.08)}
        .btn-ghost:hover{background:rgba(255,255,255,0.1);transform:translateY(-1px)}
        .snow{position:fixed;top:-10px;width:4px;height:4px;background:rgba(255,255,255,0.4);border-radius:50%;animation:fall linear infinite}
        @keyframes fall{to{transform:translateY(105vh) rotate(360deg);opacity:0}}
        .code{font-size:0.75rem;color:#555;margin-top:2rem;font-family:monospace}
    </style>
</head>
<body>
    <div class="container">
        <div class="mountain">⛷️</div>
        <h1>Trail Closed</h1>
        <p>Looks like we hit some rough terrain. Our ski patrol is on the way, try again in a moment.</p>
        <div class="actions">
            <a href="/dashboard" class="btn btn-primary">Back to Lodge</a>
            <a href="javascript:location.reload()" class="btn btn-ghost">Try Again</a>
        </div>
        <div class="code">Error <?= $statusCode ?? 500 ?></div>
    </div>
    <script>
    for(let i=0;i<40;i++){const s=document.createElement('div');s.className='snow';s.style.left=Math.random()*100+'vw';s.style.animationDuration=(3+Math.random()*4)+'s';s.style.animationDelay=Math.random()*5+'s';s.style.width=s.style.height=(2+Math.random()*3)+'px';document.body.appendChild(s);}
    </script>
</body>
</html>
