<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We Watch Asia Tracking System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body class="bg-slate-950 text-white">
    <main class="mx-auto max-w-6xl px-6 py-12">
        <section class="py-12 text-center">
            <a href="landing.php" class="wwa-logo-wrap inline-flex items-center gap-3">
                <img src="assets/logo.png" alt="WeWatch Asia logo" class="h-10 object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                <span class="hidden rounded-xl bg-slate-800 px-3 py-1 text-sm font-bold text-white">WeWatch Asia</span>
            </a>
            <h1 class="mt-4 text-3xl font-bold md:text-4xl">We Watch Asia Tracking System</h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-400">Manage sellers, track sales, and monitor performance in one place.</p>
        </section>

        <section class="mt-6 rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <a href="admin/login.php" class="wwa-card group relative rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md transition duration-200 hover:bg-slate-700">
                <div class="h-full">
                    <div class="inline-flex rounded-xl bg-slate-700 p-3 text-indigo-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0l-7.5 6.75A.75.75 0 0 0 4.5 10.5v8.25A2.25 2.25 0 0 0 6.75 21h10.5a2.25 2.25 0 0 0 2.25-2.25V10.5a.75.75 0 0 0 .516-1.58l-7.5-6.75ZM9.75 12a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 0 1.5h-3A.75.75 0 0 1 9.75 12Zm0 3.75a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 0 1.5h-3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/></svg>
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-white">Admin Login</h2>
                    <p class="mt-2 text-sm text-slate-300">Control system settings, create sellers, and monitor all transaction performance.</p>
                    <span class="wwa-btn mt-5 inline-flex rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-indigo-500">Enter</span>
                </div>
            </a>

            <a href="seller/login.php" class="wwa-card group relative rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md transition duration-200 hover:bg-slate-700">
                <div class="h-full">
                    <div class="inline-flex rounded-xl bg-slate-700 p-3 text-emerald-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 7.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/><path fill-rule="evenodd" d="M3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.744.895H4.495a.75.75 0 0 1-.744-.895Z" clip-rule="evenodd"/></svg>
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-white">Seller Login</h2>
                    <p class="mt-2 text-sm text-slate-300">See your sales, commissions, and track conversion from your referral links.</p>
                    <span class="wwa-btn mt-5 inline-flex rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-emerald-500">Enter</span>
                </div>
            </a>

            <a href="index.php" class="wwa-card group relative rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md transition duration-200 hover:bg-slate-700">
                <div class="h-full">
                    <div class="inline-flex rounded-xl bg-slate-700 p-3 text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 6.75A2.25 2.25 0 0 1 4.5 4.5h15A2.25 2.25 0 0 1 21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75Zm3 1.5a.75.75 0 0 0 0 1.5h5.25a.75.75 0 0 0 0-1.5H5.25Zm0 3.75a.75.75 0 0 0 0 1.5h13.5a.75.75 0 0 0 0-1.5H5.25Z" clip-rule="evenodd"/></svg>
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-white">Buyer Coupon / Buy Product</h2>
                    <p class="mt-2 text-sm text-slate-300">Browse product options, apply seller attribution, and complete your checkout quickly.</p>
                    <span class="wwa-btn mt-5 inline-flex rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-blue-500">Enter</span>
                </div>
            </a>
        </div>
        </section>

        <footer class="mt-6 text-center text-sm text-slate-500">
            &copy; 2026 WeWatch Asia
        </footer>
    </main>
    <script src="assets/ui.js"></script>
</body>
</html>
