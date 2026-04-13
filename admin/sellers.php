<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? 'add_seller'));

    if ($action === 'add_seller') {
        $name = sanitizeText((string) ($_POST['name'] ?? ''), 100);
        $username = sanitizeUsername((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $commissionRate = (float) ($_POST['commission_rate'] ?? 10);
        if ($commissionRate < 0 || $commissionRate > 100) {
            $commissionRate = 10;
        }
        if ($name === '' || $username === '' || strlen($password) < 6) {
            $error = 'Name, valid username, and min 6-char password are required.';
        } else {
            try {
                $referralCode = generateSellerReferralCode($pdo, $name);
                $stmt = $pdo->prepare('INSERT INTO sellers (name, referral_code, username, password_hash, commission_rate, commission_percent) VALUES (:name, :referral_code, :username, :password_hash, :commission_rate, :commission_percent)');
                $stmt->execute([
                    'name' => $name,
                    'referral_code' => $referralCode,
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'commission_rate' => $commissionRate,
                    'commission_percent' => $commissionRate,
                ]);
                $success = 'Seller created successfully.';
            } catch (Throwable $e) {
                $error = 'Failed to create seller.';
            }
        }
    } elseif ($action === 'reset_password') {
        $sellerId = (int) ($_POST['seller_id'] ?? 0);
        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        if ($sellerId <= 0 || strlen($newPassword) < 6) {
            $error = 'Reset password failed. Minimum 6 characters.';
        } else {
            $stmt = $pdo->prepare('UPDATE sellers SET password_hash = :password_hash WHERE id = :id');
            $stmt->execute([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => $sellerId,
            ]);
            $success = 'Seller password reset successfully.';
        }
    } elseif ($action === 'delete_seller') {
        $sellerId = (int) ($_POST['seller_id'] ?? 0);
        if ($sellerId > 0) {
            $stmt = $pdo->prepare('SELECT id FROM sellers WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $sellerId]);
            $seller = $stmt->fetch();
            if ($seller) {
                $pdo->prepare('UPDATE transactions SET seller_id = NULL, seller_link_code = NULL, commission_amount = 0 WHERE seller_id = :seller_id')->execute([
                    'seller_id' => $sellerId,
                ]);
                $pdo->prepare('DELETE FROM sellers WHERE id = :id')->execute(['id' => $sellerId]);
                $success = 'Seller deleted successfully.';
            }
        }
    } elseif ($action === 'reset_sellers') {
        $pdo->exec("UPDATE transactions SET seller_id = NULL, seller_link_code = NULL, commission_amount = 0");
        $pdo->exec('DELETE FROM sellers');
        $success = 'All sellers have been reset.';
    } else {
        $error = 'Unknown action.';
    }
}

if ($success !== '') {
    $_SESSION['seller_flash_success'] = $success;
    header('Location: sellers.php');
    exit;
}

if ($error !== '') {
    $_SESSION['seller_flash_error'] = $error;
    header('Location: sellers.php');
    exit;
}

$success = (string) ($_SESSION['seller_flash_success'] ?? '');
$error = (string) ($_SESSION['seller_flash_error'] ?? '');
unset($_SESSION['seller_flash_success'], $_SESSION['seller_flash_error']);

$search = trim((string) ($_GET['search'] ?? ''));
$sellerQuery = 'SELECT id, name, referral_code, username, COALESCE(commission_percent, commission_rate, 0) AS commission_rate, created_at FROM sellers';
$params = [];
if ($search !== '') {
    $sellerQuery .= ' WHERE name LIKE :search OR referral_code LIKE :search';
    $params['search'] = '%' . $search . '%';
}
$sellerQuery .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sellerQuery);
$stmt->execute($params);
$sellers = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>We Watch Asia Tracking System</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../assets/ui.css"></head><body class="wwa-page"><div class="flex min-h-screen"><aside class="hidden w-64 border-r border-slate-800 bg-slate-900 p-6 text-slate-300 lg:block"><div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" class="h-10 w-10 object-contain" alt="WeWatch Asia logo"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div><nav class="mt-6 space-y-2 text-sm"><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="dashboard.php">Dashboard</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="transactions.php">Transactions</a><a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="sellers.php">Sellers</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="analytics.php">Analytics</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="logs.php">Activity Logs</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="logout.php">Logout</a></nav></aside><main class="flex-1 p-4 sm:p-6 transition-all duration-300 ease-in-out"><div class="wwa-sticky-header mb-4 flex items-center justify-between"><h1 class="text-2xl font-bold">We Watch Asia Tracking System</h1></div>

            <section data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-md transition duration-200 hover:bg-slate-700">
                <h2 class="text-lg font-semibold">Add New Seller</h2>
                <?php if ($error !== ''): ?>
                    <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"><?= e($error) ?></div>
                    <div data-toast data-toast-type="error" class="hidden"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div class="mt-3 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-700"><?= e($success) ?></div>
                    <div data-toast data-toast-type="success" class="hidden"><?= e($success) ?></div>
                <?php endif; ?>
                <form method="POST" class="mt-4 grid gap-3 md:grid-cols-5" data-submit-spinner>
                    <input type="hidden" name="action" value="add_seller">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Seller Name</label>
                        <input name="name" type="text" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    </div>
                    <div><label class="mb-1 block text-sm font-medium">Username</label><input name="username" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium">Password</label><input name="password" type="password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium">Commission %</label><input name="commission_rate" type="number" min="0" max="100" value="10" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div class="flex items-end">
                        <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Add Seller</button>
                    </div>
                </form>
                <form method="POST" class="mt-3" data-submit-spinner data-confirm data-confirm-message="Reset all sellers? Existing transactions will be set to Official Store.">
                    <input type="hidden" name="action" value="reset_sellers">
                    <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Reset Sellers</button>
                </form>
            </section>

            <section data-skeleton class="mt-6 rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-md transition duration-200 hover:bg-slate-700">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Seller List</h2>
                        <p class="text-sm text-slate-500">Manage links and share with your sellers.</p>
                    </div>
                    <div class="flex gap-2">
                        <input id="searchInput" data-search-input data-search-target="#sellerTableBody tr" data-search-empty-id="sellerNoResults" name="search" value="<?= e($search) ?>" placeholder="Search seller... (Press / to focus)" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Seller Name</th>
                                <th class="px-4 py-3 text-left">Username</th>
                                <th class="px-4 py-3 text-left">Referral Code</th>
                                <th class="px-4 py-3 text-left">Password</th>
                                <th class="px-4 py-3 text-left">Commission</th>
                                <th class="px-4 py-3 text-left">Referral Link</th>
                                <th class="px-4 py-3 text-left">Created At</th>
                                <th class="px-4 py-3 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody id="sellerTableBody">
                            <?php if (count($sellers) === 0): ?>
                                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($sellers as $seller): ?>
                                    <?php $link = rtrim(publicBaseUrl(), '/') . '/index.php?ref=' . rawurlencode((string) $seller['referral_code']); ?>
                                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium"><?= e((string) $seller['name']) ?></td>
                                        <td class="px-4 py-3"><?= e((string) $seller['username']) ?></td>
                                        <td class="px-4 py-3"><span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-sm font-medium"><?= e((string) $seller['referral_code']) ?></span></td>
                                        <td class="px-4 py-3 text-slate-400">Hidden (secured)</td>
                                        <td class="wwa-number px-4 py-3"><?= e((string) $seller['commission_rate']) ?>%</td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <input type="text" readonly value="<?= e($link) ?>" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs link-input">
                                                <button type="button" data-link="<?= e($link) ?>" title="Copy referral link" class="copy-btn inline-flex items-center gap-1 rounded-lg bg-slate-700 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-600"><span aria-hidden="true">📋</span><span>Copy</span></button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3"><?= e((string) $seller['created_at']) ?></td>
                                        <td class="px-4 py-3">
                                            <form method="POST" class="mb-2 flex gap-2" data-submit-spinner data-confirm data-confirm-message="Reset this seller password?">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="seller_id" value="<?= (int) $seller['id'] ?>">
                                                <input type="text" name="new_password" minlength="6" required placeholder="New password" class="w-32 rounded border border-slate-300 px-2 py-1 text-xs">
                                                <button type="submit" class="rounded-lg bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-200">Reset Password</button>
                                            </form>
                                            <form method="POST" data-submit-spinner data-confirm data-confirm-message="Delete this seller?">
                                                <input type="hidden" name="action" value="delete_seller">
                                                <input type="hidden" name="seller_id" value="<?= (int) $seller['id'] ?>">
                                                <button type="submit" class="rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="sellerNoResults" class="hidden px-4 py-8 text-center text-slate-400">No results found</div>
            </section>
        </main>
    </div>
    <footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
    <script src="../assets/ui.js"></script>
</body></html>
