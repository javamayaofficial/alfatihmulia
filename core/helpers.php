<?php
/**
 * core/helpers.php - Kumpulan fungsi bantu (query-string routing aman shared hosting)
 */
if (!defined('APP_NAME')) { http_response_code(403); exit('Akses ditolak.'); }

/** URL halaman publik via query-string (andal tanpa mod_rewrite) */
function url($page = 'home', $extra = []) {
    $params = array_merge(['page' => $page], $extra);
    return BASE_URL . '/index.php?' . http_build_query($params);
}
/** URL admin */
function admin_url($r = 'dashboard', $extra = []) {
    $params = array_merge(['r' => $r], $extra);
    return BASE_URL . '/admin/index.php?' . http_build_query($params);
}
/** URL aset */
function asset($path) { return BASE_URL . '/assets/' . ltrim($path, '/'); }

/** Escape output HTML (anti-XSS) */
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

/** Format Rupiah */
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

/** Ubah key teknis menjadi label yang lebih ramah dibaca */
function human_label($str) {
    $str = str_replace(['_', '-'], ' ', (string)$str);
    $str = preg_replace('/\s+/', ' ', trim($str));
    return ucwords($str);
}

/** Potong teks aman (guard mbstring) */
function snippet($str, $len = 120) {
    $str = strip_tags((string)$str);
    if (function_exists('mb_substr')) {
        return mb_strlen($str) > $len ? mb_substr($str, 0, $len) . '…' : $str;
    }
    return strlen($str) > $len ? substr($str, 0, $len) . '…' : $str;
}

/** Slugify */
function slugify($str) {
    $str = strtolower(trim(strip_tags($str)));
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-') ?: 'item-' . time();
}

/** Validasi nomor WhatsApp Indonesia -> format 62xxxx */
function normalize_wa($phone) {
    $p = preg_replace('/[^0-9]/', '', (string)$phone);
    if ($p === '') return '';
    if (substr($p, 0, 1) === '0') $p = '62' . substr($p, 1);
    if (substr($p, 0, 2) !== '62') $p = '62' . $p;
    return $p;
}
/** Buat link wa.me dengan pesan prefilled */
function wa_link($phone, $message = '') {
    $p = normalize_wa($phone);
    return 'https://wa.me/' . $p . ($message ? '?text=' . rawurlencode($message) : '');
}

/** ---- CSRF ---- */
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) { http_response_code(419); exit('Sesi kedaluwarsa. Silakan muat ulang halaman.'); }
    return true;
}

/** ---- Settings (key-value dari DB) ---- */
function setting($key, $default = '') {
    static $cache = null;
    static $localFallbacks = null;
    if ($localFallbacks === null) {
        $localFallbacks = [];
        if (defined('DEFAULT_YAYASAN_EMAIL')) {
            $localFallbacks['yayasan_email'] = (string) DEFAULT_YAYASAN_EMAIL;
        }
        if (defined('DEFAULT_MAILKETING_API_TOKEN')) {
            $localFallbacks['mailketing_api_token'] = (string) DEFAULT_MAILKETING_API_TOKEN;
        }
        if (defined('DEFAULT_MAILKETING_LIST_ID')) {
            $localFallbacks['mailketing_list_id'] = (string) DEFAULT_MAILKETING_LIST_ID;
        }
        if (defined('DEFAULT_FONNTE_TOKEN')) {
            $localFallbacks['fonnte_token'] = (string) DEFAULT_FONNTE_TOKEN;
        }
        if (defined('DEFAULT_DUITKU_MERCHANT_CODE')) {
            $localFallbacks['duitku_merchant_code'] = (string) DEFAULT_DUITKU_MERCHANT_CODE;
        }
        if (defined('DEFAULT_DUITKU_API_KEY')) {
            $localFallbacks['duitku_api_key'] = (string) DEFAULT_DUITKU_API_KEY;
        }
        if (defined('DEFAULT_DUITKU_IS_PRODUCTION')) {
            $localFallbacks['duitku_is_production'] = (string) DEFAULT_DUITKU_IS_PRODUCTION;
        }
    }
    if ($cache === null) {
        $cache = [];
        foreach (DB::all("SELECT skey, svalue FROM settings") as $r) $cache[$r['skey']] = $r['svalue'];
    }
    if (array_key_exists($key, $cache) && trim((string) $cache[$key]) !== '') {
        return $cache[$key];
    }
    if (array_key_exists($key, $localFallbacks) && trim((string) $localFallbacks[$key]) !== '') {
        return $localFallbacks[$key];
    }
    return $default;
}
function set_setting($key, $value) {
    DB::run("INSERT INTO settings (skey, svalue) VALUES (?,?)
             ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)", 'ss', [$key, $value]);
}

function setting_enabled($key, $default = false) {
    $fallback = $default ? '1' : '0';
    return setting($key, $fallback) === '1';
}

function setting_lines($key, $default = '') {
    $value = trim((string) setting($key, $default));
    if ($value === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
}

function upload_asset_image($field, $prefix = 'media') {
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['ok' => false, 'file' => null, 'message' => 'File upload tidak ditemukan.'];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'file' => null, 'message' => 'Tidak ada file baru diunggah.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'file' => null, 'message' => 'Upload file gagal diproses.'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    if (!in_array($ext, $allowed, true)) {
        return ['ok' => false, 'file' => null, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, WEBP, GIF, atau SVG.'];
    }

    $dir = __DIR__ . '/../assets/img';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'file' => null, 'message' => 'Folder upload tidak tersedia.'];
    }

    $safePrefix = preg_replace('/[^a-z0-9]+/i', '-', strtolower($prefix)) ?: 'media';
    $filename = $safePrefix . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $target = $dir . '/' . $filename;

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'file' => null, 'message' => 'File gagal dipindahkan ke folder aset.'];
    }

    return ['ok' => true, 'file' => $filename, 'message' => 'File berhasil diunggah.'];
}

function asset_image_real_path($filename) {
    $filename = trim((string) $filename);
    if ($filename === '') {
        return null;
    }
    $baseDir = realpath(__DIR__ . '/../assets/img');
    if ($baseDir === false) {
        return null;
    }
    $sanitized = str_replace(['\\', '..'], ['/', ''], $filename);
    $target = realpath($baseDir . '/' . $sanitized);
    if ($target === false || strpos($target, $baseDir) !== 0 || !is_file($target)) {
        return null;
    }
    return $target;
}

function asset_image_usage_count($filename, array $ignore = []) {
    $filename = trim((string) $filename);
    if ($filename === '') {
        return 0;
    }
    $refs = [
        ['table' => 'media_gallery', 'column' => 'media_path'],
        ['table' => 'partners', 'column' => 'logo'],
        ['table' => 'organization_members', 'column' => 'photo'],
        ['table' => 'articles', 'column' => 'image'],
        ['table' => 'programs', 'column' => 'image'],
    ];
    $total = 0;
    foreach ($refs as $ref) {
        $sql = "SELECT COUNT(*) FROM {$ref['table']} WHERE {$ref['column']} = ?";
        $types = 's';
        $params = [$filename];
        if (($ignore['table'] ?? '') === $ref['table'] && !empty($ignore['id'])) {
            $sql .= " AND id <> ?";
            $types .= 'i';
            $params[] = (int) $ignore['id'];
        }
        $total += (int) DB::val($sql, $types, $params);
    }
    return $total;
}

function delete_asset_image_if_unused($filename, array $ignore = []) {
    $filename = trim((string) $filename);
    if ($filename === '') {
        return false;
    }
    if (asset_image_usage_count($filename, $ignore) > 0) {
        return false;
    }
    $path = asset_image_real_path($filename);
    if ($path === null) {
        return false;
    }
    return @unlink($path);
}

function upload_url($path) {
    return BASE_URL . '/uploads/' . ltrim((string) $path, '/');
}

function upload_public_file($field, $prefix = 'document', array $allowedExts = null) {
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['ok' => false, 'file' => null, 'url' => null, 'message' => 'File upload tidak ditemukan.'];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'file' => null, 'url' => null, 'message' => 'Tidak ada file baru diunggah.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'file' => null, 'url' => null, 'message' => 'Upload file gagal diproses.'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = $allowedExts ?: ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        $labels = array_map('strtoupper', $allowed);
        return ['ok' => false, 'file' => null, 'url' => null, 'message' => 'Format file tidak didukung. Gunakan ' . implode(', ', $labels) . '.'];
    }

    $subdir = 'docs';
    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'file' => null, 'url' => null, 'message' => 'Folder upload dokumen tidak tersedia.'];
    }

    $safePrefix = preg_replace('/[^a-z0-9]+/i', '-', strtolower($prefix)) ?: 'document';
    $filename = $safePrefix . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $target = $dir . '/' . $filename;

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'file' => null, 'url' => null, 'message' => 'File gagal dipindahkan ke folder uploads.'];
    }

    $relativePath = $subdir . '/' . $filename;
    return ['ok' => true, 'file' => $relativePath, 'url' => upload_url($relativePath), 'message' => 'Dokumen berhasil diunggah.'];
}

function local_upload_relative_from_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    $base = rtrim((string) BASE_URL, '/');
    $uploadsBase = $base . '/uploads/';
    if (strpos($url, $uploadsBase) !== 0) {
        return null;
    }
    $relative = ltrim(substr($url, strlen($uploadsBase)), '/');
    return $relative !== '' ? $relative : null;
}

function delete_public_upload_by_url($url) {
    $relative = local_upload_relative_from_url($url);
    if ($relative === null) {
        return false;
    }
    $path = realpath(__DIR__ . '/../uploads');
    if ($path === false) {
        return false;
    }
    $target = realpath($path . '/' . str_replace(['\\', '..'], ['/', ''], $relative));
    if ($target === false || strpos($target, $path) !== 0 || !is_file($target)) {
        return false;
    }
    return @unlink($target);
}

function csv_download($filename, array $headers, array $rows) {
    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    $out = fopen('php://output', 'w');
    if ($out === false) {
        http_response_code(500);
        exit('Gagal membuat file CSV.');
    }

    // BOM UTF-8 agar karakter Indonesia aman saat dibuka di Excel.
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function request_query(array $keys, array $extra = [], array $remove = []) {
    $result = [];
    foreach ($keys as $key) {
        if (!array_key_exists($key, $_GET)) {
            continue;
        }
        $value = $_GET[$key];
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === '' || $value === null) {
            continue;
        }
        $result[$key] = $value;
    }
    foreach ($remove as $key) {
        unset($result[$key]);
    }
    foreach ($extra as $key => $value) {
        if ($value === '' || $value === null) {
            unset($result[$key]);
            continue;
        }
        $result[$key] = $value;
    }
    return $result;
}

function current_page($key = 'p') {
    $page = isset($_GET[$key]) ? (int) $_GET[$key] : 1;
    return max(1, $page);
}

function total_pages($totalItems, $perPage) {
    $perPage = max(1, (int) $perPage);
    return max(1, (int) ceil(((int) $totalItems) / $perPage));
}

function render_admin_pagination($route, $currentPage, $totalItems, $perPage, array $query = []) {
    $totalPages = total_pages($totalItems, $perPage);
    if ($totalPages <= 1) {
        return '';
    }

    $currentPage = min(max(1, (int) $currentPage), $totalPages);
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    if ($end - $start < 4) {
        if ($start === 1) {
            $end = min($totalPages, $start + 4);
        } elseif ($end === $totalPages) {
            $start = max(1, $end - 4);
        }
    }

    ob_start();
    ?>
    <div class="pagination-wrap">
      <div class="pagination-meta">Halaman <?= $currentPage ?> dari <?= $totalPages ?> · Total <?= number_format((int) $totalItems) ?> data</div>
      <div class="pagination-links">
        <?php if ($currentPage > 1): ?>
          <a class="page-link" href="<?= admin_url($route, array_merge($query, ['p' => $currentPage - 1])) ?>">Sebelumnya</a>
        <?php endif; ?>
        <?php if ($start > 1): ?>
          <a class="page-link" href="<?= admin_url($route, array_merge($query, ['p' => 1])) ?>">1</a>
          <?php if ($start > 2): ?><span class="page-ellipsis">...</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($page = $start; $page <= $end; $page++): ?>
          <a class="page-link <?= $page === $currentPage ? 'active' : '' ?>" href="<?= admin_url($route, array_merge($query, ['p' => $page])) ?>"><?= $page ?></a>
        <?php endfor; ?>
        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">...</span><?php endif; ?>
          <a class="page-link" href="<?= admin_url($route, array_merge($query, ['p' => $totalPages])) ?>"><?= $totalPages ?></a>
        <?php endif; ?>
        <?php if ($currentPage < $totalPages): ?>
          <a class="page-link" href="<?= admin_url($route, array_merge($query, ['p' => $currentPage + 1])) ?>">Berikutnya</a>
        <?php endif; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function http_request($url, $options = []) {
    $ch = curl_init($url);
    $curlOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $options['timeout'] ?? 15,
    ];
    if (!empty($options['headers'])) $curlOptions[CURLOPT_HTTPHEADER] = $options['headers'];
    if (!empty($options['method'])) $curlOptions[CURLOPT_CUSTOMREQUEST] = strtoupper($options['method']);
    if (array_key_exists('fields', $options)) $curlOptions[CURLOPT_POSTFIELDS] = $options['fields'];
    if (!empty($options['userpwd'])) $curlOptions[CURLOPT_USERPWD] = $options['userpwd'];
    curl_setopt_array($ch, $curlOptions);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'ok' => $body !== false,
        'status_code' => $statusCode,
        'body' => $body === false ? '' : $body,
        'json' => is_string($body) ? json_decode($body, true) : null,
        'error' => $error,
    ];
}

/** ---- Audit log ---- */
function audit($action, $detail = '') {
    $uid = $_SESSION['uid'] ?? null;
    $uname = $_SESSION['uname'] ?? 'system';
    DB::run("INSERT INTO audit_log (user_id, user_name, action, detail, ip)
             VALUES (?,?,?,?,?)", 'issss',
            [$uid, $uname, $action, $detail, $_SERVER['REMOTE_ADDR'] ?? '']);
}

/** Apakah kunci API gateway/WA sudah diisi? (untuk disclosure jujur) */
function feature_active($key) {
    $v = trim((string)setting($key, ''));
    return $v !== '';
}

/** Buat kode referral unik */
function make_referral_code($name) {
    $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($name)));
    $base = substr($base, 0, 6) ?: 'DUTA';
    return 'AIP-' . $base . '-' . rand(1000, 9999);
}

/** Buat nomor invoice donasi */
function make_invoice() { return 'AIP-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); }

function duitku_is_production() {
    return setting('duitku_is_production', '0') === '1';
}

function duitku_api_base_url() {
    return duitku_is_production() ? 'https://passport.duitku.com' : 'https://sandbox.duitku.com';
}

function duitku_enabled() {
    return feature_active('duitku_merchant_code') && feature_active('duitku_api_key');
}

function duitku_create_invoice(array $payload) {
    $merchantCode = trim((string) setting('duitku_merchant_code', ''));
    $apiKey = trim((string) setting('duitku_api_key', ''));
    if ($merchantCode === '' || $apiKey === '') {
        return ['ok' => false, 'message' => 'Duitku belum dikonfigurasi.'];
    }

    $paymentAmount = (int) ($payload['paymentAmount'] ?? 0);
    $merchantOrderId = trim((string) ($payload['merchantOrderId'] ?? ''));
    if ($paymentAmount <= 0 || $merchantOrderId === '') {
        return ['ok' => false, 'message' => 'Data transaksi Duitku belum lengkap.'];
    }

    $requestPayload = [
        'merchantCode' => $merchantCode,
        'paymentAmount' => $paymentAmount,
        'merchantOrderId' => $merchantOrderId,
        'productDetails' => trim((string) ($payload['productDetails'] ?? 'Donasi Yayasan')),
        'additionalParam' => trim((string) ($payload['additionalParam'] ?? '')),
        'merchantUserInfo' => trim((string) ($payload['merchantUserInfo'] ?? '')),
        'customerVaName' => trim((string) ($payload['customerVaName'] ?? 'Donatur Yayasan')),
        'email' => trim((string) ($payload['email'] ?? '')),
        'phoneNumber' => trim((string) ($payload['phoneNumber'] ?? '')),
        'itemDetails' => is_array($payload['itemDetails'] ?? null) ? $payload['itemDetails'] : [],
        'customerDetail' => is_array($payload['customerDetail'] ?? null) ? $payload['customerDetail'] : null,
        'callbackUrl' => trim((string) ($payload['callbackUrl'] ?? '')),
        'returnUrl' => trim((string) ($payload['returnUrl'] ?? '')),
        'expiryPeriod' => max(10, (int) ($payload['expiryPeriod'] ?? (int) setting('duitku_expiry_period', '60'))),
    ];
    $requestPayload['signature'] = md5($merchantCode . $merchantOrderId . $paymentAmount . $apiKey);

    $result = http_request(duitku_api_base_url() . '/webapi/api/merchant/v2/inquiry', [
        'method' => 'POST',
        'fields' => json_encode($requestPayload),
        'headers' => ['Content-Type: application/json', 'Accept: application/json'],
    ]);
    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Gagal terhubung ke Duitku: ' . ($result['error'] ?: 'koneksi tidak tersedia')];
    }

    $json = is_array($result['json']) ? $result['json'] : [];
    if (!empty($json['paymentUrl']) && !empty($json['reference'])) {
        return [
            'ok' => true,
            'message' => 'Invoice Duitku berhasil dibuat.',
            'paymentUrl' => (string) $json['paymentUrl'],
            'reference' => (string) $json['reference'],
            'response' => $json,
        ];
    }

    $detail = $json['Message'] ?? $json['message'] ?? $json['statusMessage'] ?? ('HTTP ' . $result['status_code']);
    return ['ok' => false, 'message' => 'Duitku menolak transaksi: ' . $detail, 'response' => $json];
}

function duitku_transaction_status($merchantOrderId) {
    $merchantCode = trim((string) setting('duitku_merchant_code', ''));
    $apiKey = trim((string) setting('duitku_api_key', ''));
    $merchantOrderId = trim((string) $merchantOrderId);
    if ($merchantCode === '' || $apiKey === '' || $merchantOrderId === '') {
        return ['ok' => false, 'message' => 'Parameter cek status Duitku belum lengkap.'];
    }

    $payload = [
        'merchantCode' => $merchantCode,
        'merchantOrderId' => $merchantOrderId,
        'signature' => md5($merchantCode . $merchantOrderId . $apiKey),
    ];
    $result = http_request(duitku_api_base_url() . '/webapi/api/merchant/transactionStatus', [
        'method' => 'POST',
        'fields' => json_encode($payload),
        'headers' => ['Content-Type: application/json', 'Accept: application/json'],
    ]);
    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Gagal mengecek status Duitku: ' . ($result['error'] ?: 'koneksi tidak tersedia')];
    }

    $json = is_array($result['json']) ? $result['json'] : [];
    if (!empty($json['statusCode'])) {
        return [
            'ok' => true,
            'statusCode' => (string) $json['statusCode'],
            'reference' => (string) ($json['reference'] ?? ''),
            'amount' => (string) ($json['amount'] ?? ''),
            'response' => $json,
        ];
    }

    $detail = $json['Message'] ?? $json['message'] ?? $json['statusMessage'] ?? ('HTTP ' . $result['status_code']);
    return ['ok' => false, 'message' => 'Status transaksi Duitku tidak dapat dibaca: ' . $detail, 'response' => $json];
}

function verify_donation_payment(array $donation, $source = 'system') {
    if (empty($donation['id']) || ($donation['status'] ?? '') === 'verified') {
        return false;
    }

    DB::run("UPDATE donations SET status='verified', verified_at=NOW() WHERE id=?", 'i', [(int) $donation['id']]);
    if (!empty($donation['program_id'])) {
        DB::run("UPDATE programs SET collected_amount = collected_amount + ? WHERE id=?", 'ii', [(int) $donation['amount'], (int) $donation['program_id']]);
    }
    audit('verify_donasi', $donation['invoice'] . ' ' . rupiah($donation['amount']) . ' via ' . $source);
    if (!empty($donation['donor_phone'])) {
        send_wa($donation['donor_phone'], "Alhamdulillah, donasi Anda " . rupiah($donation['amount']) . " (" . $donation['invoice'] . ") telah DIVERIFIKASI. Jazakallahu khairan. — " . setting('yayasan_name', 'Yayasan Al Fatih'));
    }
    return true;
}

function reject_donation_payment(array $donation, $source = 'system') {
    if (empty($donation['id']) || ($donation['status'] ?? '') === 'rejected') {
        return false;
    }

    if (($donation['status'] ?? '') === 'verified' && !empty($donation['program_id'])) {
        DB::run("UPDATE programs SET collected_amount = GREATEST(0, collected_amount - ?) WHERE id=?", 'ii', [(int) $donation['amount'], (int) $donation['program_id']]);
    }
    DB::run("UPDATE donations SET status='rejected' WHERE id=?", 'i', [(int) $donation['id']]);
    audit('reject_donasi', $donation['invoice'] . ' via ' . $source);
    return true;
}

/** Kirim notifikasi WhatsApp via Fonnte bila key aktif; jika tidak, return false (admin kirim manual) */
function send_wa($phone, $message) {
    $token = trim((string)setting('fonnte_token', ''));
    if ($token === '') return false; // gated: belum aktif
    $p = normalize_wa($phone);
    $fields = ['target' => $p, 'message' => $message];
    $sender = trim((string)setting('fonnte_sender', ''));
    if ($sender !== '') $fields['device'] = $sender;
    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: ' . $token],
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res !== false;
}

function notify_admin_new_lead($type, array $data = []) {
    $adminPhone = trim((string) setting('yayasan_wa', ''));
    $adminEmail = trim((string) setting('yayasan_email', ''));
    $waSent = false;
    $emailSent = false;
    if ($adminPhone === '' || !feature_active('fonnte_token')) {
        $waSent = false;
    }

    $type = trim((string) $type);
    $appName = setting('yayasan_short', setting('yayasan_name', 'Yayasan Al Fatih'));
    if ($type === 'volunteer') {
        $message = "Lead relawan baru masuk di {$appName}\n"
            . "Nama: " . ($data['name'] ?? '-') . "\n"
            . "Kota: " . ($data['city'] ?? '-') . "\n"
            . "WA: " . ($data['phone'] ?? '-') . "\n"
            . "Email: " . (($data['email'] ?? '') !== '' ? $data['email'] : '-') . "\n"
            . "Profesi: " . ($data['profession'] ?? '-') . "\n"
            . "Divisi: " . ($data['division'] ?? '-') . "\n"
            . "Catatan: " . (($data['note'] ?? '') !== '' ? $data['note'] : '-') . "\n"
            . "Tindak lanjut: " . admin_url('relawan');
        if ($adminPhone !== '' && feature_active('fonnte_token')) {
            $waSent = send_wa($adminPhone, $message);
        }
        if ($adminEmail !== '') {
            $emailContent = nl2br(e($message));
            $emailSent = send_mailketing_email($adminEmail, '[' . $appName . '] Lead Relawan Baru', $emailContent);
        }
        return $waSent || $emailSent;
    }

    if ($type === 'partnership') {
        $message = "Lead kemitraan baru masuk di {$appName}\n"
            . "Lembaga: " . ($data['organization_name'] ?? '-') . "\n"
            . "PIC: " . ($data['contact_name'] ?? '-') . "\n"
            . "WA: " . ($data['phone'] ?? '-') . "\n"
            . "Email: " . (($data['email'] ?? '') !== '' ? $data['email'] : '-') . "\n"
            . "Jenis: " . ($data['partnership_type'] ?? '-') . "\n"
            . "Catatan: " . (($data['message'] ?? '') !== '' ? $data['message'] : '-') . "\n"
            . "Tindak lanjut: " . admin_url('mitra');
        if ($adminPhone !== '' && feature_active('fonnte_token')) {
            $waSent = send_wa($adminPhone, $message);
        }
        if ($adminEmail !== '') {
            $emailContent = nl2br(e($message));
            $emailSent = send_mailketing_email($adminEmail, '[' . $appName . '] Lead Kemitraan Baru', $emailContent);
        }
        return $waSent || $emailSent;
    }

    return false;
}

function notify_lead_status_update($type, array $data = []) {
    $targetPhone = trim((string) ($data['phone'] ?? ''));
    $targetEmail = trim((string) ($data['email'] ?? ''));
    $waEnabled = $targetPhone !== '' && feature_active('fonnte_token');
    $emailEnabled = $targetEmail !== '';
    if (!$waEnabled && !$emailEnabled) return false;

    $type = trim((string) $type);
    $status = trim((string) ($data['status'] ?? ''));
    $appName = setting('yayasan_short', setting('yayasan_name', 'Yayasan Al Fatih'));
    $contactWa = trim((string) setting('yayasan_wa', ''));

    if ($type === 'volunteer') {
        $statusTextMap = [
            'new' => 'pendaftaran Anda sudah kami terima dan masuk ke antrean review tim.',
            'contacted' => 'tim kami sedang menindaklanjuti pendaftaran Anda.',
            'qualified' => 'selamat, Anda lolos ke tahap berikutnya sebagai calon relawan.',
            'rejected' => 'saat ini pendaftaran Anda belum dapat kami lanjutkan ke tahap berikutnya.',
        ];
        $message = "Assalamualaikum " . ($data['name'] ?? 'Sahabat Relawan') . ",\n"
            . "Update pendaftaran relawan di {$appName}: " . ($statusTextMap[$status] ?? 'status pendaftaran Anda telah diperbarui.') . "\n"
            . "Divisi: " . (($data['division'] ?? '') !== '' ? $data['division'] : '-') . "\n";
        if ($contactWa !== '') {
            $message .= "Jika ada pertanyaan, silakan hubungi admin: " . wa_link($contactWa) . "\n";
        }
        $message .= "Jazakumullahu khairan.";
        $waSent = $waEnabled ? send_wa($targetPhone, $message) : false;
        $emailSent = $emailEnabled ? send_mailketing_email($targetEmail, '[' . $appName . '] Update Pendaftaran Relawan', nl2br(e($message))) : false;
        return $waSent || $emailSent;
    }

    if ($type === 'partnership') {
        $statusTextMap = [
            'new' => 'pengajuan kerja sama Anda sudah kami terima dan masuk proses review.',
            'contacted' => 'tim kami sedang menghubungi PIC untuk tindak lanjut awal.',
            'negotiation' => 'pengajuan kerja sama Anda sedang masuk tahap pembahasan / negosiasi.',
            'approved' => 'alhamdulillah, pengajuan kerja sama Anda disetujui untuk tahap lanjutan.',
            'rejected' => 'saat ini pengajuan kerja sama Anda belum dapat kami lanjutkan.',
        ];
        $message = "Assalamualaikum " . ($data['contact_name'] ?? 'Sahabat Mitra') . ",\n"
            . "Update pengajuan kemitraan di {$appName}: " . ($statusTextMap[$status] ?? 'status pengajuan Anda telah diperbarui.') . "\n"
            . "Lembaga: " . (($data['organization_name'] ?? '') !== '' ? $data['organization_name'] : '-') . "\n"
            . "Jenis: " . (($data['partnership_type'] ?? '') !== '' ? $data['partnership_type'] : '-') . "\n";
        if ($contactWa !== '') {
            $message .= "Jika ada pertanyaan, silakan hubungi admin: " . wa_link($contactWa) . "\n";
        }
        $message .= "Terima kasih atas niat kolaborasinya.";
        $waSent = $waEnabled ? send_wa($targetPhone, $message) : false;
        $emailSent = $emailEnabled ? send_mailketing_email($targetEmail, '[' . $appName . '] Update Pengajuan Kemitraan', nl2br(e($message))) : false;
        return $waSent || $emailSent;
    }

    return false;
}

function split_name_parts($name) {
    $name = trim((string)$name);
    if ($name === '') return ['', ''];
    $parts = preg_split('/\s+/', $name);
    $first = array_shift($parts) ?: '';
    $last = trim(implode(' ', $parts));
    return [$first, $last];
}

function sync_mailketing_subscriber($email, $name = '', $phone = '') {
    $email = trim((string)$email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if (!setting_enabled('mailketing_auto_sync', true)) return false;

    $apiToken = trim((string)setting('mailketing_api_token', ''));
    $listId = trim((string)setting('mailketing_list_id', ''));
    if ($apiToken === '' || $listId === '') return false;

    [$firstName, $lastName] = split_name_parts($name);
    $mobile = normalize_wa($phone);
    $payload = [
        'api_token' => $apiToken,
        'list_id' => $listId,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
    ];
    if ($mobile !== '') $payload['mobile'] = $mobile;

    $ch = curl_init('https://api.mailketing.co.id/api/v1/addsubtolist');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) return false;

    $decoded = json_decode($response, true);
    if (is_array($decoded) && isset($decoded['status'])) {
        return strtolower((string)$decoded['status']) === 'success';
    }

    return stripos($response, 'success') !== false || stripos($response, 'added to list') !== false;
}

function send_mailketing_email($recipient, $subject, $content, $options = []) {
    $recipient = trim((string) $recipient);
    $subject = trim((string) $subject);
    $content = trim((string) $content);
    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) return false;
    if ($subject === '' || $content === '') return false;

    $apiToken = trim((string) setting('mailketing_api_token', ''));
    $fromName = trim((string) ($options['from_name'] ?? setting('yayasan_short', setting('yayasan_name', APP_NAME))));
    $fromEmail = trim((string) ($options['from_email'] ?? setting('yayasan_email', '')));
    if ($apiToken === '' || $fromName === '' || $fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $payload = [
        'api_token' => $apiToken,
        'from_name' => $fromName,
        'from_email' => $fromEmail,
        'recipient' => $recipient,
        'subject' => $subject,
        'content' => $content,
    ];
    if (!empty($options['attach1'])) $payload['attach1'] = trim((string) $options['attach1']);
    if (!empty($options['attach2'])) $payload['attach2'] = trim((string) $options['attach2']);

    $result = http_request('https://api.mailketing.co.id/api/v1/send', [
        'method' => 'POST',
        'fields' => http_build_query($payload),
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    if (!$result['ok']) return false;

    $json = is_array($result['json']) ? $result['json'] : [];
    if (($json['status'] ?? '') === 'success') {
        return true;
    }
    $response = strtolower((string) ($json['response'] ?? ''));
    return $response === 'mail sent';
}

function test_duitku_connection($merchantCode, $apiKey, $isProduction = false) {
    $merchantCode = trim((string) $merchantCode);
    $apiKey = trim((string) $apiKey);
    if ($merchantCode === '' || $apiKey === '') {
        return ['ok' => false, 'message' => 'Merchant Code dan API Key Duitku wajib diisi.'];
    }

    $amount = 10000;
    $datetime = date('Y-m-d H:i:s');
    $signature = hash('sha256', $merchantCode . $amount . $datetime . $apiKey);
    $baseUrl = $isProduction ? 'https://passport.duitku.com' : 'https://sandbox.duitku.com';
    $result = http_request($baseUrl . '/webapi/api/merchant/paymentmethod/getpaymentmethod', [
        'method' => 'POST',
        'fields' => json_encode([
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ]),
        'headers' => ['Content-Type: application/json', 'Accept: application/json'],
    ]);

    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Gagal terhubung ke Duitku: ' . ($result['error'] ?: 'koneksi tidak tersedia')];
    }

    $json = is_array($result['json']) ? $result['json'] : [];
    $paymentFees = is_array($json['paymentFee'] ?? null) ? $json['paymentFee'] : [];
    if (!empty($paymentFees)) {
        $env = $isProduction ? 'production' : 'sandbox';
        return ['ok' => true, 'message' => 'Koneksi Duitku ' . $env . ' berhasil. Metode pembayaran terdeteksi: ' . count($paymentFees) . ' channel.'];
    }

    $detail = $json['Message'] ?? $json['message'] ?? $json['statusMessage'] ?? ('HTTP ' . $result['status_code']);
    return ['ok' => false, 'message' => 'Duitku merespons error: ' . $detail];
}

function test_mailketing_connection($apiToken, $listId = '') {
    $apiToken = trim((string)$apiToken);
    $listId = trim((string)$listId);
    if ($apiToken === '') {
        return ['ok' => false, 'message' => 'API Token Mailketing wajib diisi.'];
    }

    $result = http_request('https://api.mailketing.co.id/api/v1/viewlist', [
        'method' => 'POST',
        'fields' => http_build_query(['api_token' => $apiToken]),
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Gagal terhubung ke Mailketing: ' . ($result['error'] ?: 'koneksi tidak tersedia')];
    }

    $json = is_array($result['json']) ? $result['json'] : [];
    if (($json['status'] ?? '') !== 'success') {
        $detail = $json['response'] ?? $json['message'] ?? $json['reason'] ?? 'token tidak valid atau respons tidak dikenali';
        return ['ok' => false, 'message' => 'Mailketing menolak koneksi: ' . $detail];
    }

    $lists = is_array($json['lists'] ?? null) ? $json['lists'] : [];
    if ($listId !== '') {
        foreach ($lists as $list) {
            if ((string)($list['list_id'] ?? '') === $listId) {
                $name = trim((string)($list['list_name'] ?? ''));
                return ['ok' => true, 'message' => 'Mailketing terhubung. List ID ' . $listId . ($name !== '' ? ' ditemukan (' . $name . ').' : ' ditemukan.')];
            }
        }
        return ['ok' => false, 'message' => 'API Token valid, tetapi List ID ' . $listId . ' tidak ditemukan di akun Mailketing.'];
    }

    return ['ok' => true, 'message' => 'Mailketing terhubung. Total list yang ditemukan: ' . count($lists) . '.'];
}

function test_fonnte_connection($token, $target = '') {
    $token = trim((string)$token);
    if ($token === '') {
        return ['ok' => false, 'message' => 'Token Fonnte wajib diisi.'];
    }

    $target = normalize_wa($target !== '' ? $target : setting('yayasan_wa', '08123456789'));
    if ($target === '') $target = '628123456789';

    $result = http_request('https://api.fonnte.com/validate', [
        'method' => 'POST',
        'fields' => ['target' => $target, 'countryCode' => '62'],
        'headers' => ['Authorization: ' . $token],
    ]);

    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Gagal terhubung ke Fonnte: ' . ($result['error'] ?: 'koneksi tidak tersedia')];
    }

    $json = is_array($result['json']) ? $result['json'] : [];
    if (($json['status'] ?? false) === true) {
        return ['ok' => true, 'message' => 'Fonnte terhubung dan token valid. Device dapat merespons pengecekan nomor.'];
    }

    $reason = trim((string)($json['reason'] ?? 'Token tidak valid atau device belum siap.'));
    return ['ok' => false, 'message' => 'Fonnte gagal diverifikasi: ' . $reason];
}
