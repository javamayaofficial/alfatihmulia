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
    if ($cache === null) {
        $cache = [];
        foreach (DB::all("SELECT skey, svalue FROM settings") as $r) $cache[$r['skey']] = $r['svalue'];
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}
function set_setting($key, $value) {
    DB::run("INSERT INTO settings (skey, svalue) VALUES (?,?)
             ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)", 'ss', [$key, $value]);
}

function setting_enabled($key, $default = false) {
    $fallback = $default ? '1' : '0';
    return setting($key, $fallback) === '1';
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

function test_midtrans_connection($serverKey, $isProduction = false) {
    $serverKey = trim((string)$serverKey);
    if ($serverKey === '') {
        return ['ok' => false, 'message' => 'Server Key Midtrans wajib diisi.'];
    }

    $baseUrl = $isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    $orderId = 'AIP-TEST-' . date('YmdHis');
    $result = http_request($baseUrl . '/v2/' . rawurlencode($orderId) . '/status', [
        'headers' => ['Accept: application/json'],
        'userpwd' => $serverKey . ':',
    ]);

    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Gagal terhubung ke Midtrans: ' . ($result['error'] ?: 'koneksi tidak tersedia')];
    }
    if ($result['status_code'] === 401) {
        return ['ok' => false, 'message' => 'Autentikasi Midtrans gagal. Periksa Server Key Anda.'];
    }
    if (in_array($result['status_code'], [200, 404], true)) {
        $env = $isProduction ? 'production' : 'sandbox';
        return ['ok' => true, 'message' => 'Koneksi Midtrans ' . $env . ' berhasil. Server Key valid dan endpoint dapat diakses.'];
    }

    $json = is_array($result['json']) ? $result['json'] : [];
    $detail = $json['status_message'] ?? $json['error_messages'][0] ?? ('HTTP ' . $result['status_code']);
    return ['ok' => false, 'message' => 'Midtrans merespons error: ' . $detail];
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
