<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
if (!preg_match('/^(?:\[[0-9a-fA-F:]+\]|[a-zA-Z0-9.-]+)(?::[0-9]{1,5})?$/D', $host)) {
    http_response_code(400);
    exit('Could not determine a valid public host for the menu QR code.');
}

$forwarded_proto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
$request_scheme = strtolower($_SERVER['REQUEST_SCHEME'] ?? '');
$is_https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || $request_scheme === 'https'
    || $forwarded_proto === 'https';
$scheme = $is_https ? 'https' : 'http';
$admin_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/menu_qr.php'));
$app_path = rtrim(dirname($admin_path), '/.');
$menu_url = $scheme . '://' . $host . ($app_path !== '' ? $app_path : '') . '/index.php';
$brand_short_name = get_app_setting('brand_short_name', 'Menu');
$brand_logo = get_app_setting('brand_logo');

$page_title = 'Menu QR Code';
include 'header.php';
?>

<section class="mx-auto w-full max-w-4xl py-8 sm:py-12">
    <div class="glass-card overflow-hidden rounded-3xl border border-white p-6 shadow-[0_20px_60px_rgba(27,52,43,0.10)] sm:p-9">
        <div class="text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Welcome to <?= htmlspecialchars($brand_short_name) ?></p>
            <h2 class="mt-2 text-2xl font-bold text-[#20332b] sm:text-3xl">Your menu QR code</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">Scan the code to open the live menu.</p>
        </div>

        <div class="mx-auto mt-8 flex max-w-xl flex-col items-center rounded-3xl border border-gray-100 bg-white p-5 text-center shadow-sm sm:p-8">
            <?php if ($brand_logo !== ''): ?>
                <img id="menu-brand-logo" src="../<?= htmlspecialchars($brand_logo) ?>" alt="<?= htmlspecialchars($brand_short_name) ?> logo" class="mb-4 h-16 max-w-48 object-contain">
            <?php else: ?>
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-2xl font-black text-emerald-800">
                    <?= htmlspecialchars(strtoupper(mb_substr(trim($brand_short_name), 0, 1, 'UTF-8') ?: 'M')) ?>
                </div>
            <?php endif; ?>
            <h3 class="text-xl font-black uppercase tracking-wide text-[#20332b]"><?= htmlspecialchars($brand_short_name) ?></h3>

            <div class="mt-5 rounded-2xl border border-gray-100 bg-white p-3 shadow-sm">
                <div id="menu-qr" class="flex h-[240px] w-[240px] items-center justify-center" role="img" aria-label="QR code for the public menu"></div>
                <p id="qr-error" role="alert" class="hidden max-w-xs p-4 text-sm font-semibold text-red-700"></p>
            </div>

            <p class="mt-4 max-w-full break-all text-xs text-gray-500"><?= htmlspecialchars($menu_url) ?></p>
            <div class="mt-6 flex w-full flex-wrap justify-center gap-3 print:hidden">
                <button id="download-qr" type="button" disabled class="rounded-xl bg-emerald-800 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-900 disabled:cursor-wait disabled:opacity-50">
                    Download QR code
                </button>
                <button type="button" onclick="window.print()" class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
                    Print
                </button>
                <a href="../index.php" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
                    Open menu
                </a>
                <a href="settings.php" class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </div>
        <p class="mt-6 text-center text-xs leading-5 text-gray-500">Your logo and name appear beside the code, keeping the QR pattern clear for reliable scanning.</p>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    (() => {
        const qrContainer = document.getElementById('menu-qr');
        const error = document.getElementById('qr-error');
        const downloadButton = document.getElementById('download-qr');
        const menuUrl = <?= json_encode($menu_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        if (typeof window.QRCode !== 'function') {
            qrContainer.hidden = true;
            error.textContent = 'The QR-code generator could not be loaded. Check your internet connection and reload this page.';
            error.classList.remove('hidden');
            return;
        }

        try {
            new window.QRCode(qrContainer, {
                text: menuUrl,
                width: 240,
                height: 240,
                colorDark: '#111827',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.H
            });
            downloadButton.disabled = false;
        } catch (generationError) {
            qrContainer.hidden = true;
            error.textContent = 'Could not generate the menu QR code.';
            error.classList.remove('hidden');
            return;
        }

        downloadButton.addEventListener('click', () => {
            const qrCanvas = qrContainer.querySelector('canvas');
            if (!qrCanvas) {
                error.textContent = 'The QR image is not ready to download. Reload the page and try again.';
                error.classList.remove('hidden');
                return;
            }

            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = 520;
            exportCanvas.height = 700;
            const context = exportCanvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, exportCanvas.width, exportCanvas.height);

            const drawQrAndDownload = () => {
                context.textAlign = 'center';
                context.fillStyle = '#20332b';
                context.font = '700 26px sans-serif';
                context.fillText(<?= json_encode(mb_strtoupper($brand_short_name, 'UTF-8'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 260, 137, 470);

                context.fillStyle = '#317d53';
                context.fillRect(218, 157, 84, 3);
                context.font = '700 15px sans-serif';
                context.fillText('DIGITAL MENU', 260, 187);

                context.drawImage(qrCanvas, 100, 207, 320, 320);
                context.fillStyle = '#20332b';
                context.font = '600 16px sans-serif';
                context.fillText('Scan to explore our menu', 260, 560);
                context.fillStyle = '#6b7280';
                context.font = '12px sans-serif';
                context.fillText(menuUrl, 260, 625, 480);
                const link = document.createElement('a');
                link.download = '<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $brand_short_name), ENT_QUOTES) ?>-menu-qr.png';
                link.href = exportCanvas.toDataURL('image/png');
                link.click();
            };

            const logo = document.getElementById('menu-brand-logo');
            if (logo && logo.complete && logo.naturalWidth > 0) {
                const maxWidth = 150;
                const maxHeight = 78;
                const scale = Math.min(maxWidth / logo.naturalWidth, maxHeight / logo.naturalHeight, 1);
                const width = logo.naturalWidth * scale;
                const height = logo.naturalHeight * scale;
                context.drawImage(logo, (520 - width) / 2, 25 + (maxHeight - height) / 2, width, height);
            } else if (!logo) {
                context.fillStyle = '#317d53';
                context.beginPath();
                context.rect(230, 25, 60, 60);
                context.fill();
                context.fillStyle = '#ffffff';
                context.font = '700 34px sans-serif';
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillText(<?= json_encode(strtoupper(mb_substr(trim($brand_short_name), 0, 1, 'UTF-8') ?: 'M'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 260, 55);
                context.textBaseline = 'alphabetic';
            }
            drawQrAndDownload();
        });
    })();
</script>
<style>
    @media print {
        body { background: #fff !important; }
        aside, .admin-footer, header, .print\:hidden { display: none !important; }
        main { display: block !important; }
        .admin-page-content { padding: 0 !important; }
        section { padding: 0 !important; }
        .glass-card { border: 0 !important; box-shadow: none !important; }
    }
</style>

<?php include 'footer.php'; ?>
