<?php
/**
 * api/webhook-xendit.php - Penerima callback Xendit (GATED)
 * Aktif setelah xendit_key + xendit_callback_token diisi.
 * Verifikasi header x-callback-token.
 */
require_once __DIR__ . '/../core/core.php';
header('Content-Type: application/json');

$token = setting('xendit_callback_token', '');
if (setting('xendit_key','') === '') { http_response_code(503); echo json_encode(['ok'=>false,'msg'=>'Gateway belum diaktifkan']); exit; }

$incoming = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
if ($token !== '' && !hash_equals($token, $incoming)) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Token tidak valid']); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$ext = $body['external_id'] ?? '';
$status = $body['status'] ?? '';
$d = DB::one("SELECT * FROM donations WHERE invoice=?", 's', [$ext]);
if ($d && $status === 'PAID' && $d['status'] !== 'verified') {
    DB::run("UPDATE donations SET status='verified', verified_at=NOW() WHERE id=?", 'i', [$d['id']]);
    if ($d['program_id']) DB::run("UPDATE programs SET collected_amount=collected_amount+? WHERE id=?", 'ii', [(int)$d['amount'],(int)$d['program_id']]);
    audit('webhook_xendit', $ext.' paid');
}
echo json_encode(['ok'=>true]);
