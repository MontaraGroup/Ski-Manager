<!DOCTYPE html>
<html lang="en" data-theme="carboncloud">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Trail Not Found | Ski Manager</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="bg-base-200 text-base-content">
    <div class="max-w-md w-full p-6 text-center">
        <div class="w-20 h-20 mx-auto rounded-2xl bg-info/10 border border-info/30 flex items-center justify-center text-4xl text-info mb-6">
            <i class="fa-solid fa-person-skiing"></i>
        </div>
        <div class="text-xs font-mono font-bold text-info uppercase tracking-widest mb-1">Error 404</div>
        <h1 class="text-3xl font-extrabold mb-3">Trail Not Found</h1>
        <p class="text-sm text-base-content/70 leading-relaxed mb-6">
            <?php if (ENVIRONMENT !== 'production' && !empty($message)) : ?>
                <?= nl2br(esc($message)) ?>
            <?php else : ?>
                This slope does not exist or has been groomed out of service. Looks like you took a wrong turn down an unmarked run.
            <?php endif ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-2 justify-center">
            <a href="javascript:history.back()" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center gap-2 rounded-xl border border-border bg-transparent px-4 py-2 text-sm font-semibold hover:bg-default">
                <i class="fa-solid fa-arrow-left"></i>Go Back
            </a>
            <a href="/dashboard" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center gap-2 rounded-xl bg-primary text-primary-foreground px-4 py-2 text-sm font-semibold hover:bg-primary/90">
                <i class="fa-solid fa-gauge-high"></i>Back to Lodge
            </a>
            <a href="/map" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center gap-2 rounded-xl bg-secondary text-secondary-foreground px-4 py-2 text-sm font-semibold hover:bg-secondary/90">
                <i class="fa-solid fa-map"></i>Trail Map
            </a>
        </div>
        <div class="mt-8 pt-4 border-t border-base-300 text-xs text-base-content/70">
            Ski Manager Operations Sector · Security Code 404
        </div>
    </div>
</body>
</html>
