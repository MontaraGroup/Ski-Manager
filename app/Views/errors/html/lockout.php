<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Locked - Ski Manager</title>
    <link rel="stylesheet" href="/css/style.css?v=7">
</head>
<body class="min-h-screen flex items-center justify-center bg-base-200">
    <div class="card bg-base-100 shadow-xl w-96">
        <div class="card-body text-center">
            <i class="fa-solid fa-lock text-error text-5xl mb-4"></i>
            <h1 class="text-2xl font-bold text-error">Access Blocked</h1>
            <p class="text-base-content/60 mt-2">Too many failed login attempts from your IP address.</p>
            <div class="bg-error/10 rounded-lg p-4 mt-4">
                <div class="text-3xl font-bold text-error"><?= $minutes ?></div>
                <div class="text-sm text-base-content/50">minutes remaining</div>
            </div>
            <p class="text-xs text-base-content/40 mt-4">If you believe this is an error, please wait or contact support.</p>
            <a href="/" class="btn btn-ghost btn-sm mt-4">Back to Home</a>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/e8108d3d5f.js" crossorigin="anonymous"></script>
</body>
</html>
