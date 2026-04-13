<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo 'STEP 1 OK<br>'; }

$products = getProducts();
$sellerList = $pdo->query('SELECT id, name FROM sellers ORDER BY name ASC')->fetchAll();

if (isset($_GET['ref'])) {
    $linkCode = sanitizeRef((string) $_GET['ref']);
    $stmt = $pdo->prepare('
        SELECT sl.link_code, sl.link_label, s.id AS seller_id, s.name AS seller_name
        FROM seller_links sl
        INNER JOIN sellers s ON s.id = sl.seller_id
        WHERE sl.link_code = :link_code
        LIMIT 1
    ');
    $stmt->execute(['link_code' => $linkCode]);
    $link = $stmt->fetch();

    if ($link) {
        setcookie('seller_link_code', (string) $link['link_code'], ['expires' => time() + 86400, 'path' => '/', 'samesite' => 'Lax']);
        $_COOKIE['seller_link_code'] = (string) $link['link_code'];
    } else {
        setcookie('seller_link_code', '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        unset($_COOKIE['seller_link_code']);
    }
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo 'STEP 2 OK<br>'; }

$activeLinkCode = sanitizeRef((string) ($_COOKIE['seller_link_code'] ?? ''));
$active = null;
if ($activeLinkCode !== '') {
    $stmt = $pdo->prepare('
        SELECT sl.link_code, sl.link_label, s.id AS seller_id, s.name AS seller_name
        FROM seller_links sl
        INNER JOIN sellers s ON s.id = sl.seller_id
        WHERE sl.link_code = :link_code
        LIMIT 1
    ');
    $stmt->execute(['link_code' => $activeLinkCode]);
    $active = $stmt->fetch() ?: null;
}
$activeSellerId = (int) ($active['seller_id'] ?? 0);
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>We Watch Asia Tracking System</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="assets/ui.css"></head>
<body class="wwa-page">
<main class="mx-auto max-w-6xl px-6 py-12 transition duration-200">
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md">
<div class="flex flex-wrap items-center justify-between gap-2"><div class="flex items-center gap-3"><img src="assets/logo.png" alt="WeWatch Asia logo" class="wwa-logo-img h-10 object-contain" onerror="this.style.display='none';"><h1 class="text-3xl font-bold text-white">We Watch Asia Tracking System</h1></div></div>
<p class="mt-2 text-lg text-slate-300">Complete your order with seller and link attribution.</p>
<?php if ($active): ?>
<div class="mt-4 rounded-xl border border-slate-700 bg-slate-800 p-3 text-sm text-slate-300">You are buying from: <b class="text-white"><?= e((string) $active['seller_name']) ?></b> via <b class="text-white"><?= e((string) $active['link_label']) ?></b>.</div>
<?php else: ?>
<div class="mt-4 rounded-xl border border-slate-700 bg-slate-800 p-3 text-sm text-slate-300">You are buying from: <b class="text-white">Official Store</b>.</div>
<?php endif; ?>

<form action="process.php" method="POST" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2" data-submit-spinner>
<input type="hidden" name="seller_link_code" value="<?= e((string) ($active['link_code'] ?? '')) ?>">
<div><label class="mb-1 block text-sm font-medium text-white">Buyer Name</label><input required name="buyer_name" class="w-full rounded-xl border border-white/30 bg-white px-3 py-2 outline-none focus:border-indigo-500"></div>
<div><label class="mb-1 block text-sm font-medium text-white">Phone</label><input required name="phone" class="w-full rounded-xl border border-white/30 bg-white px-3 py-2 outline-none focus:border-indigo-500"></div>
<div><label class="mb-1 block text-sm font-medium text-white">Email (optional)</label><input name="buyer_email" type="email" class="w-full rounded-xl border border-white/30 bg-white px-3 py-2 outline-none focus:border-indigo-500"></div>
<div>
<label class="mb-1 block text-sm font-medium text-white">Select Seller</label>
<?php if ($active): ?>
<input type="hidden" name="seller_id" value="<?= (int) $activeSellerId ?>">
<select id="seller_id" disabled class="w-full rounded-xl border border-white/30 bg-white px-3 py-2 outline-none opacity-90">
<?php foreach ($sellerList as $seller): ?><option value="<?= (int) $seller['id'] ?>" <?= $activeSellerId === (int) $seller['id'] ? 'selected' : '' ?>><?= e((string) $seller['name']) ?></option><?php endforeach; ?>
</select>
<p class="mt-1 text-xs text-yellow-200">Seller is locked by referral link.</p>
<?php else: ?>
<select id="seller_id" name="seller_id" required class="w-full rounded-xl border border-white/30 bg-white px-3 py-2 outline-none focus:border-indigo-500"><option value="">Choose Seller</option><?php foreach ($sellerList as $seller): ?><option value="<?= (int) $seller['id'] ?>"><?= e((string) $seller['name']) ?></option><?php endforeach; ?></select>
<?php endif; ?>
</div>
<div><label class="mb-1 block text-sm font-medium text-white">Product</label><select id="product" name="product" required class="w-full rounded-xl border border-white/30 bg-white px-3 py-2 outline-none focus:border-indigo-500"><option value="">Select product</option><?php foreach ($products as $n => $p): ?><option value="<?= e($n) ?>" data-price="<?= (int) $p ?>"><?= e($n) ?> - <?= e(formatRupiah((int) $p)) ?></option><?php endforeach; ?></select></div>
<div class="md:col-span-2 rounded-xl border border-slate-700 bg-slate-800 p-3"><p class="text-xs text-slate-400">Price</p><p id="priceLabel" class="wwa-number text-xl font-bold">Rp 0</p></div>
<p id="sellerDisplay" class="md:col-span-2 text-sm text-slate-300">You are buying from: <?= $active ? e((string) $active['seller_name']) : 'Official Store' ?></p>
<div class="md:col-span-2"><button class="wwa-btn w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-500 transition duration-200" type="submit"><span data-btn-label>Buy Now</span></button></div>
</form>
</div>
</main>
<?php if ($flash !== ''): ?><div data-toast data-toast-type="success" class="hidden"><?= e($flash) ?></div><?php endif; ?>
<script>
const p=document.getElementById('product'),l=document.getElementById('priceLabel');if(p){p.addEventListener('change',()=>{const o=p.options[p.selectedIndex];const v=Number(o?.dataset?.price||0);l.textContent=new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v);});}
const sellerSelect=document.getElementById('seller_id'),sellerDisplay=document.getElementById('sellerDisplay');if(sellerSelect&&sellerDisplay){sellerSelect.addEventListener('change',()=>{const s=sellerSelect.options[sellerSelect.selectedIndex];sellerDisplay.textContent='You are buying from: '+(s&&sellerSelect.value!==''?s.text:'Official Store');});}
</script>
<script src="assets/ui.js"></script>
</body></html>
