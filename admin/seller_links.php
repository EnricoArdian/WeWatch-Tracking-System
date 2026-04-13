<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sellerId = (int) ($_POST['seller_id'] ?? 0);
    $label = sanitizeText((string) ($_POST['link_label'] ?? ''), 100);
    if ($sellerId <= 0 || $label === '') {
        $error = 'Seller and link label are required.';
    } else {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM seller_links WHERE seller_id = :seller_id');
        $countStmt->execute(['seller_id' => $sellerId]);
        if ((int) $countStmt->fetchColumn() >= 10) {
            $error = 'Each seller can only have up to 10 links.';
        } else {
            $code = generateLinkCode($pdo, $sellerId, $label);
            $stmt = $pdo->prepare('INSERT INTO seller_links (seller_id, link_code, link_label) VALUES (:seller_id, :link_code, :link_label)');
            $stmt->execute(['seller_id' => $sellerId, 'link_code' => $code, 'link_label' => $label]);
            $success = 'Link created.';
        }
    }
}

$sellers = $pdo->query('SELECT id, name FROM sellers ORDER BY name ASC')->fetchAll();
$rows = $pdo->query('
SELECT sl.*, s.name AS seller_name
FROM seller_links sl
INNER JOIN sellers s ON s.id = sl.seller_id
ORDER BY sl.id DESC
')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>We Watch Asia Tracking System</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page"><div class="mx-auto max-w-6xl p-4 py-8">
<div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h1 class="text-2xl font-bold">We Watch Asia Tracking System</h1></div>
<?php if ($error !== ''): ?><div class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700"><?= e($success) ?></div><?php endif; ?>
<form method="POST" class="mt-4 grid gap-3 md:grid-cols-3" data-submit-spinner><select name="seller_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><?php foreach ($sellers as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e((string)$s['name']) ?></option><?php endforeach; ?></select><input name="link_label" placeholder="Instagram Ads" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"><span data-btn-label>Create Link</span></button></form>
<div class="mt-4 overflow-x-auto rounded-xl bg-white p-2"><table class="min-w-[800px] w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left">Seller</th><th class="px-3 py-2 text-left">Label</th><th class="px-3 py-2 text-left">Code</th><th class="px-3 py-2 text-left">Link</th></tr></thead><tbody><?php foreach ($rows as $r): $ln = appUrl('index.php?ref=' . rawurlencode((string)$r['link_code'])); ?><tr class="border-t border-slate-100"><td class="px-3 py-2"><?= e((string)$r['seller_name']) ?></td><td class="px-3 py-2"><?= e((string)$r['link_label']) ?></td><td class="px-3 py-2"><?= e((string)$r['link_code']) ?></td><td class="px-3 py-2"><div class="flex gap-2"><input readonly value="<?= e($ln) ?>" class="w-full rounded border border-slate-300 px-2 py-1 text-xs"><button class="copy-btn rounded border border-slate-300 px-2 py-1 text-xs" data-link="<?= e($ln) ?>" type="button">Copy</button></div></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<script src="../assets/ui.js"></script>
</body></html>
