<?php
/**
 * api/webhook-midtrans.php - Penerima notifikasi Midtrans (GATED)
 * Aktif setelah midtrans_server_key diisi di Pengaturan.
 * Verifikasi signature: SHA512(order_id + status_code + gross_amount + server_key)
 */
require_once __DIR__ . '/../core/core.php';
header('Content-Type: application/json');

$serverKey = setting('midtrans_server_key', '');
if ($serverKey === '') { http_response_code(503); echo json_encode(['ok'=>false,'msg'=>'Gateway belum diaktifkan']); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$order  = $body['order_id'] ?? '';
$status = $body['status_code'] ?? '';
$gross  = $body['gross_amount'] ?? '';
$sig    = $body['signature_key'] ?? '';
$calc   = hash('sha512', $order . $status . $gross . $serverKey);

if (!hash_equals($calc, $sig)) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Signature tidak valid']); exit; }

$trx = $body['transaction_status'] ?? '';
$d = DB::one("SELECT * FROM donations WHERE invoice=?", 's', [$order]);
if ($d && in_array($trx, ['capture','settlement'], true) && $d['status'] !== 'verified') {
    DB::run("UPDATE donations SET status='verified', verified_at=NOW() WHERE id=?", 'i', [$d['id']]);
    if ($d['program_id']) DB::run("UPDATE programs SET collected_amount=collected_amount+? WHERE id=?", 'ii', [(int)$d['amount'],(int)$d['program_id']]);
    audit('webhook_midtrans', $order.' settled');
    if ($d['donor_phone']) send_wa($d['donor_phone'], "Pembayaran donasi Anda ".rupiah($d['amount'])." (".$order.") BERHASIL & terverifikasi. Jazakallahu khairan.");
}
echo json_encode(['ok'=>true]);
