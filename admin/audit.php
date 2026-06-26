<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireAdmin('superadmin');
$rows=DB::all("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 200");
admin_layout_header('audit','Audit Log');
?>
<div class="panel"><div class="panel-head"><h3>Riwayat Aktivitas Sistem</h3></div>
<?php if(!$rows): ?><div class="empty-state small"><p>Belum ada aktivitas tercatat.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php foreach($rows as $a): ?><tr><td class="muted"><?= e(date('d/m/y H:i',strtotime($a['created_at']))) ?></td><td><?= e($a['user_name']) ?></td><td><span class="badge"><?= e($a['action']) ?></span></td><td><?= e($a['detail']) ?></td><td class="muted"><?= e($a['ip']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
