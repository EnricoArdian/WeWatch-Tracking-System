<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$orderId = trim((string) ($_GET['order_id'] ?? ''));
if ($orderId === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT
        t.*,
        COALESCE(s.name, "Official Store") AS seller_name
    FROM transactions t
    LEFT JOIN sellers s ON s.id = t.seller_id
    WHERE t.transaction_code = :transaction_code
    LIMIT 1
');
$stmt->execute(['transaction_code' => $orderId]);
$transaction = $stmt->fetch();

if (!$transaction) {
    $_SESSION['flash'] = 'Transaction not found.';
    header('Location: index.php');
    exit;
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$showBuyerSuccessModal = (string) ($_SESSION['buyer_success_modal'] ?? '') === '1';
unset($_SESSION['buyer_success_modal']);

$status = (string) ($transaction['status'] ?? 'pending');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>We Watch Asia Tracking System</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="assets/ui.css">
<style>
.progress-container {
    margin-top: 20px;
    position: relative;
    padding-top: 16px;
}
.progress-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    border-radius: 10px;
    background: #1e293b;
}
.progress-bar {
    position: absolute;
    top: 0;
    left: 0;
    height: 6px;
    width: 0%;
    background: linear-gradient(to right, #6366f1, #22c55e);
    border-radius: 10px;
    transition: width 0.4s ease-in-out, background 0.3s ease;
    z-index: 1;
}
.steps {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}
.step {
    font-size: 12px;
    color: #64748b;
}
.step.active {
    color: #818cf8;
    font-weight: bold;
}
</style></head>
<body class="wwa-page"><main class="mx-auto max-w-4xl p-4 py-8"><div class="rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md">
<div class="flex items-center justify-between gap-2"><h1 class="text-2xl font-bold text-indigo-700">We Watch Asia Tracking System</h1></div>
<?php if ($flash !== ''): ?><div class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700"><?= e($flash) ?></div><?php endif; ?>
<div id="result"></div>

<form class="mt-4 flex flex-col gap-3 sm:flex-row" action="upload_handler.php" method="POST" enctype="multipart/form-data" data-submit-spinner>
<input type="hidden" name="order_id" value="<?= e((string) $transaction['transaction_code']) ?>">
<input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
<button class="wwa-btn rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition duration-200" type="submit" <?= in_array($status, ['paid', 'rejected', 'failed'], true) ? 'disabled' : '' ?>><span data-btn-label><?= in_array($status, ['paid', 'rejected', 'failed'], true) ? 'Upload Locked' : 'Upload Transfer Receipt' ?></span></button>
</form>
<?php if (!empty($transaction['payment_proof'])): ?><a class="mt-3 inline-block text-sm text-sky-600 underline" data-image-preview target="_blank" href="<?= e((string) $transaction['payment_proof']) ?>">View Uploaded Proof</a><?php endif; ?>

<div class="mt-6 flex gap-3"><a href="index.php" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Back</a></div>
</div></main>
<?php if ($showBuyerSuccessModal): ?>
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="w-full max-w-md rounded-xl border border-slate-700 bg-slate-800 p-6 text-center shadow-xl">
        <h2 class="mb-3 text-2xl font-semibold text-white">Terima Kasih 🙌</h2>
        <p class="mb-2 text-slate-300">Terima Kasih Sudah Mengkonfirmasi pembayaran Anda, Admin akan segera verifikasi pembayaranmu</p>
        <p class="mb-4 text-sm text-slate-400">Silakan tunggu konfirmasi dari admin</p>
        <button id="successModalOk" class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">OK</button>
    </div>
</div>
<?php endif; ?>
<script>
(() => {
    const orderId = <?= json_encode((string) $transaction['transaction_code']) ?>;
    const initialData = <?= json_encode([
        'invoice_code' => (string) $transaction['transaction_code'],
        'status' => (string) $transaction['status'],
        'price' => formatRupiah((int) $transaction['price']),
        'seller_name' => (string) ($transaction['seller_name'] ?? '-'),
        'buyer_name' => (string) $transaction['buyer_name'],
        'product' => (string) $transaction['product'],
        'payment_proof' => (string) ($transaction['payment_proof'] ?? ''),
    ], JSON_UNESCAPED_SLASHES) ?>;

    let lastStatus = String(initialData.status || '').toLowerCase();

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /** Maps DB row to progress step: pending | verified | done | rejected */
    function progressStatusFromData(data) {
        const s = String(data.status || '').toLowerCase();
        if (s === 'paid') {
            return 'done';
        }
        if (s === 'rejected' || s === 'failed') {
            return 'rejected';
        }
        if (s === 'pending' && data.payment_proof) {
            return 'verified';
        }
        return 'pending';
    }

    function updateProgress(status) {
        const bar = document.getElementById('progressBar');
        const pending = document.getElementById('step-pending');
        const verified = document.getElementById('step-verified');
        const done = document.getElementById('step-done');
        if (!bar || !pending || !verified || !done) {
            return;
        }

        pending.classList.remove('active');
        verified.classList.remove('active');
        done.classList.remove('active');

        bar.style.background = 'linear-gradient(to right, #6366f1, #22c55e)';

        if (status === 'pending') {
            bar.style.width = '33%';
            pending.classList.add('active');
        }
        if (status === 'verified') {
            bar.style.width = '66%';
            pending.classList.add('active');
            verified.classList.add('active');
        }
        if (status === 'done') {
            bar.style.width = '100%';
            pending.classList.add('active');
            verified.classList.add('active');
            done.classList.add('active');
        }
        if (status === 'rejected') {
            bar.style.width = '100%';
            bar.style.background = '#ef4444';
        }
    }

    function renderResult(data) {
        const dbStatus = String(data.status || '').toLowerCase();
        const rejected = dbStatus === 'rejected' || dbStatus === 'failed';
        const invoice = escapeHtml(data.invoice_code);
        const st = escapeHtml(data.status);
        const price = escapeHtml(data.price);
        const seller = escapeHtml(data.seller_name ?? '-');
        const rejectedLine = rejected
            ? '<p class="mt-3 text-sm font-semibold text-red-400">Transaction Rejected</p>'
            : '';

        document.getElementById('result').innerHTML = `
            <div class="bg-slate-800 p-5 rounded-xl mt-3 text-slate-200">
                <p class="text-sm"><span class="text-slate-400">Invoice:</span> ${invoice}</p>
                <p class="text-sm"><span class="text-slate-400">Status:</span> ${st}</p>
                <p class="text-sm"><span class="text-slate-400">Amount:</span> ${price}</p>
                <p class="text-sm"><span class="text-slate-400">Seller:</span> ${seller}</p>
                ${rejectedLine}
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar"></div>
                    <div class="steps">
                        <div class="step" id="step-pending">Pending</div>
                        <div class="step" id="step-verified">Verified</div>
                        <div class="step" id="step-done">Done</div>
                    </div>
                </div>
            </div>
        `;
        updateProgress(progressStatusFromData(data));

        const uploadBtn = document.querySelector('button[type="submit"]');
        const uploadLabel = document.querySelector('[data-btn-label]');
        if (uploadBtn && uploadLabel) {
            const lockUpload = ['paid', 'rejected', 'failed'].includes(dbStatus);
            uploadBtn.disabled = lockUpload;
            uploadLabel.textContent = lockUpload ? 'Upload Locked' : 'Upload Transfer Receipt';
        }
    }

    async function refreshTracking() {
        try {
            const res = await fetch('track_status.php?order_id=' + encodeURIComponent(orderId) + '&nocache=' + Date.now(), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status !== 'success' || !data.transaction) return;
            const tx = data.transaction;
            renderResult(tx);
            const nextStatus = String(tx.status || '').toLowerCase();
            if ((nextStatus === 'rejected' || nextStatus === 'failed') && lastStatus !== nextStatus && window.showToast) {
                window.showToast('Transaksi ditolak', 'error');
            }
            lastStatus = nextStatus;
        } catch (_error) {
            // Silent fail on polling; next interval will retry.
        }
    }

    renderResult(initialData);
    setInterval(refreshTracking, 4000);

    const modal = document.getElementById('successModal');
    if (modal) {
        const redirectTarget = <?= json_encode(appUrl('landing.php'), JSON_UNESCAPED_SLASHES) ?>;
        const goToLanding = () => {
            window.location.href = redirectTarget;
        };
        const btn = document.getElementById('successModalOk');
        if (btn) btn.addEventListener('click', goToLanding);
        setTimeout(goToLanding, 3000);
    }
})();
</script>
<script src="assets/ui.js"></script>
</body></html>
