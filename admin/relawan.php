<?php
if (!defined('APP_NAME')) { exit; }
$leadStatuses = ['new' => 'Baru', 'contacted' => 'Dihubungi', 'qualified' => 'Lolos', 'rejected' => 'Ditolak'];
$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterKeyword = trim((string) ($_GET['q'] ?? ''));
$filterFrom = trim((string) ($_GET['from'] ?? ''));
$filterTo = trim((string) ($_GET['to'] ?? ''));
$currentLeadPage = current_page('p');
$perPage = 20;
$leadFilterQuery = request_query(['status', 'q', 'from', 'to', 'p']);

$leadSql = "SELECT * FROM volunteer_leads WHERE 1=1";
$leadTypes = '';
$leadParams = [];
if (isset($leadStatuses[$filterStatus])) {
    $leadSql .= " AND status=?";
    $leadTypes .= 's';
    $leadParams[] = $filterStatus;
}
if ($filterFrom !== '') {
    $leadSql .= " AND DATE(created_at) >= ?";
    $leadTypes .= 's';
    $leadParams[] = $filterFrom;
}
if ($filterTo !== '') {
    $leadSql .= " AND DATE(created_at) <= ?";
    $leadTypes .= 's';
    $leadParams[] = $filterTo;
}
if ($filterKeyword !== '') {
    $leadSql .= " AND (name LIKE ? OR city LIKE ? OR phone LIKE ? OR email LIKE ? OR profession LIKE ? OR division LIKE ? OR note LIKE ?)";
    $kw = '%' . $filterKeyword . '%';
    $leadTypes .= 'sssssss';
    array_push($leadParams, $kw, $kw, $kw, $kw, $kw, $kw, $kw);
}

if (($_GET['export'] ?? '') === 'leads') {
    $exportRows = DB::all($leadSql . " ORDER BY created_at DESC", $leadTypes, $leadParams);
    $csvRows = [];
    foreach ($exportRows as $lead) {
        $csvRows[] = [
            $lead['id'],
            $lead['name'],
            $lead['city'],
            $lead['phone'],
            $lead['email'],
            $lead['profession'],
            $lead['division'],
            $lead['note'],
            $lead['status'],
            $lead['created_at'],
        ];
    }
    audit('export_volunteer_leads', 'Ekspor CSV lead relawan terfilter');
    csv_download('lead-relawan-' . date('Ymd-His') . '.csv', ['ID', 'Nama', 'Kota', 'WhatsApp', 'Email', 'Profesi', 'Divisi', 'Catatan', 'Status', 'Tanggal Masuk'], $csvRows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'lead_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'new';
        $existing = DB::one("SELECT * FROM volunteer_leads WHERE id=?", 'i', [$id]);
        if (isset($leadStatuses[$status])) {
            DB::run("UPDATE volunteer_leads SET status=? WHERE id=?", 'si', [$status, $id]);
            audit('update_volunteer_lead', '#' . $id . ' -> ' . $status);
            if ($existing && ($existing['status'] ?? '') !== $status) {
                $notified = notify_lead_status_update('volunteer', [
                    'name' => $existing['name'] ?? '',
                    'phone' => $existing['phone'] ?? '',
                    'email' => $existing['email'] ?? '',
                    'division' => $existing['division'] ?? '',
                    'status' => $status,
                ]);
                audit('volunteer_status_notification', $notified ? ('Lead #' . $id . ' notifikasi status terkirim') : ('Lead #' . $id . ' notifikasi status tidak aktif / gagal'));
            }
            flash_set('Status lead relawan diperbarui.');
        }
    }
    header('Location: ' . admin_url('relawan', $leadFilterQuery));
    exit;
}

$rows = leaderboard(100);
$totalLeadItems = (int) DB::val("SELECT COUNT(*) FROM (" . $leadSql . ") AS volunteer_filtered", $leadTypes, $leadParams);
$totalLeadPages = total_pages($totalLeadItems, $perPage);
$currentLeadPage = min($currentLeadPage, $totalLeadPages);
$leadOffset = ($currentLeadPage - 1) * $perPage;
$leads = DB::all($leadSql . " ORDER BY created_at DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $leadOffset, $leadTypes, $leadParams);
$hasLeadFilter = !empty($leadFilterQuery);
admin_layout_header('relawan', 'Kelola Relawan');
flash_show();
?>
<div class="panel">
  <div class="panel-head"><h3>Relawan & Kontribusi</h3><span class="muted"><?= count($rows) ?> relawan</span></div>
  <?php if (!$rows): ?><div class="empty-state small"><p>Belum ada relawan terdaftar.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>#</th><th>Nama</th><th>Kode Referral</th><th>Donasi Dibawa</th><th class="right">Total Dana</th></tr></thead>
    <tbody><?php $i=0; foreach ($rows as $b): $i++; ?>
      <tr><td><?= $i ?></td><td><b><?= e($b['name']) ?></b></td><td><code><?= e($b['referral_code']) ?></code></td>
      <td><?= number_format($b['jml_donasi']) ?>x</td><td class="right"><b><?= rupiah($b['total']) ?></b></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Lead Relawan Baru</h3>
    <div class="head-actions">
      <span class="muted"><?= number_format($totalLeadItems) ?> pendaftaran</span>
      <a class="btn btn-ghost btn-sm" href="<?= admin_url('relawan', array_merge(request_query(['status', 'q', 'from', 'to']), ['export' => 'leads'])) ?>">Ekspor CSV</a>
    </div>
  </div>
  <form method="get" class="filter-form">
    <input type="hidden" name="r" value="relawan">
    <div class="filter-grid">
      <input type="text" name="q" value="<?= e($filterKeyword) ?>" placeholder="Cari nama, kota, WA, email, profesi, divisi">
      <select name="status">
        <option value="">Semua status</option>
        <?php foreach ($leadStatuses as $key => $label): ?>
        <option value="<?= $key ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="from" value="<?= e($filterFrom) ?>">
      <input type="date" name="to" value="<?= e($filterTo) ?>">
      <button class="btn btn-primary btn-sm">Filter</button>
      <?php if ($hasLeadFilter): ?><a class="btn btn-ghost btn-sm" href="<?= admin_url('relawan') ?>">Reset</a><?php endif; ?>
    </div>
  </form>
  <?php if (!$leads): ?><div class="empty-state small"><p>Belum ada lead relawan masuk dari form publik.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Nama</th><th>Kota</th><th>Kontak</th><th>Profesi</th><th>Divisi</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody><?php foreach ($leads as $lead): ?>
      <tr>
        <td><b><?= e($lead['name']) ?></b><br><small class="muted"><?= e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></small></td>
        <td><?= e($lead['city']) ?></td>
        <td>
          <a href="https://wa.me/<?= e($lead['phone']) ?>" target="_blank" rel="noopener"><?= e($lead['phone']) ?></a>
          <?php if (!empty($lead['email'])): ?><br><small><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></small><?php endif; ?>
        </td>
        <td><?= e($lead['profession']) ?></td>
        <td><?= e($lead['division']) ?></td>
        <td><span class="badge badge-<?= $lead['status'] === 'qualified' ? 'verified' : ($lead['status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e(human_label($lead['status'])) ?></span></td>
        <td class="actions">
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="act" value="lead_status">
            <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
            <input type="hidden" name="status_filter" value="<?= e($filterStatus) ?>">
            <input type="hidden" name="q_filter" value="<?= e($filterKeyword) ?>">
            <input type="hidden" name="from_filter" value="<?= e($filterFrom) ?>">
            <input type="hidden" name="to_filter" value="<?= e($filterTo) ?>">
            <select name="status">
              <?php foreach ($leadStatuses as $key => $label): ?>
              <option value="<?= $key ?>" <?= $lead['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-ghost btn-sm">Simpan</button>
          </form>
        </td>
      </tr>
      <?php if (!empty($lead['note'])): ?>
      <tr>
        <td colspan="7"><small class="muted"><b>Catatan:</b> <?= e($lead['note']) ?></small></td>
      </tr>
      <?php endif; ?>
    <?php endforeach; ?></tbody>
  </table></div>
  <?= render_admin_pagination('relawan', $currentLeadPage, $totalLeadItems, $perPage, request_query(['status', 'q', 'from', 'to'])) ?>
  <?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
