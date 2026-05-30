<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Ski Manager</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="min-h-screen bg-base-200">

    <!-- Navbar -->
    <div class="navbar bg-base-100 shadow-lg">
        <div class="flex-1">
            <a href="/" class="btn btn-ghost text-xl">Ski Manager</a>
        </div>
        <div class="flex-none">
            <ul class="menu menu-horizontal px-1">
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/login">Login</a></li>
            </ul>
        </div>
    </div>

    <!-- Content -->
    <main class="container mx-auto p-4">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Footer -->
    <footer class="footer footer-center p-4 bg-base-100 text-base-content mt-auto">
        <p>&copy; <?= date('Y') ?> Ski Manager</p>
    </footer>

</body>
</html>
