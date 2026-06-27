<?php
/**
 * api/webhook-duitku.php - Penerima callback Duitku
 * Signature callback: MD5(merchantCode + amount + merchantOrderId + apiKey)
 */
require_once __DIR__ . '/../core/core.php';
header('Content-Type: application/json');

$merchantCode = trim((string) setting('duitku_merchant_code', ''));
$apiKey = trim((string) setting('duitku_api_key', ''));
if ($merchantCode === '' || $apiKey === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'msg' => 'Duitku belum diaktifkan']);
    exit;
}

$body = $_POST;
if (empty($body)) {
    $raw = file_get_contents('php://input');
    parse_str((string) $raw, $body);
}

$incomingMerchantCode = trim((string) ($body['merchantCode'] ?? ''));
$amount = trim((string) ($body['amount'] ?? ''));
$orderId = trim((string) ($body['merchantOrderId'] ?? ''));
$signature = trim((string) ($body['signature'] ?? ''));
$resultCode = trim((string) ($body['resultCode'] ?? ''));

if ($incomingMerchantCode === '' || $amount === '' || $orderId === '' || $signature === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Payload callback Duitku tidak lengkap']);
    exit;
}
if ($incomingMerchantCode !== $merchantCode) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Merchant code tidak valid']);
    exit;
}

$calc = md5($incomingMerchantCode . $amount . $orderId . $apiKey);
if (!hash_equals($calc, $signature)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Signature tidak valid']);
    exit;
}

$donation = DB::one("SELECT * FROM donations WHERE invoice=?", 's', [$orderId]);
if (!$donation) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Invoice tidak ditemukan']);
    exit;
}

if ($resultCode === '00') {
    verify_donation_payment($donation, 'duitku_callback');
    audit('webhook_duitku', $orderId . ' success');
} elseif ($resultCode === '01') {
    audit('webhook_duitku', $orderId . ' pending');
} else {
    reject_donation_payment($donation, 'duitku_callback');
    audit('webhook_duitku', $orderId . ' failed');
}

echo json_encode(['ok' => true]);
