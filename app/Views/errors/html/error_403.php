<!DOCTYPE html>
<html lang="en" data-theme="carboncloud">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | Ski Manager</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .btn { transition: none !important; }
    </style>
</head>
<body class="bg-base-200 text-base-content">
    <div class="max-w-md w-full p-6 text-center">
        <div class="w-20 h-20 mx-auto rounded-2xl bg-error/10 border border-error/30 flex items-center justify-center text-4xl text-error mb-6">
            <i class="fa-solid fa-lock"></i>
        </div>
        <div class="text-xs font-mono font-bold text-error uppercase tracking-widest mb-1">Error 403</div>
        <h1 class="text-3xl font-extrabold mb-3">Restricted Slope</h1>
        <p class="text-sm text-base-content/60 leading-relaxed mb-6">
            <?php if (ENVIRONMENT !== 'production' && !empty($message)) : ?>
                <?= nl2br(esc($message)) ?>
            <?php else : ?>
                Access to this sector or feature is restricted. You do not have the required resort clearance or permissions to enter.
            <?php endif ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-2 justify-center">
            <a href="javascript:history.back()" class="btn btn-outline btn-sm gap-2">
                <i class="fa-solid fa-arrow-left"></i>Go Back
            </a>
            <a href="/dashboard" class="btn btn-primary btn-sm gap-2">
                <i class="fa-solid fa-gauge-high"></i>Back to Lodge
            </a>
            <?php if (!session()->get('logged_in')) : ?>
                <a href="/login" class="btn btn-secondary btn-sm gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i>Log In
                </a>
            <?php endif ?>
        </div>
        <div class="mt-8 pt-4 border-t border-base-300 text-xs text-base-content/40">
            Ski Manager Operations Sector · Security Code 403
        </div>
    </div>
</body>
</html>
