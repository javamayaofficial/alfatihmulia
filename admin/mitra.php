<?php
if (!defined('APP_NAME')) { exit; }

$categoryOptions = ['Corporate Partnership', 'Masjid Partnership', 'Komunitas', 'Sekolah', 'Pesantren', 'Mitra Lainnya'];
$leadStatuses = ['new' => 'Baru', 'contacted' => 'Dihubungi', 'negotiation' => 'Negosiasi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterKeyword = trim((string) ($_GET['q'] ?? ''));
$filterFrom = trim((string) ($_GET['from'] ?? ''));
$filterTo = trim((string) ($_GET['to'] ?? ''));
$currentLeadPage = current_page('p');
$perPage = 20;
$leadFilterQuery = request_query(['status', 'q', 'from', 'to', 'p']);

$leadSql = "SELECT * FROM partnership_leads WHERE 1=1";
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
    $leadSql .= " AND (organization_name LIKE ? OR contact_name LIKE ? OR phone LIKE ? OR email LIKE ? OR partnership_type LIKE ? OR message LIKE ?)";
    $kw = '%' . $filterKeyword . '%';
    $leadTypes .= 'ssssss';
    array_push($leadParams, $kw, $kw, $kw, $kw, $kw, $kw);
}

if (($_GET['export'] ?? '') === 'leads') {
    $exportRows = DB::all($leadSql . " ORDER BY created_at DESC", $leadTypes, $leadParams);
    $csvRows = [];
    foreach ($exportRows as $lead) {
        $csvRows[] = [
            $lead['id'],
            $lead['organization_name'],
            $lead['contact_name'],
            $lead['phone'],
            $lead['email'],
            $lead['partnership_type'],
            $lead['message'],
            $lead['status'],
            $lead['created_at'],
        ];
    }
    audit('export_partnership_leads', 'Ekspor CSV lead kemitraan terfilter');
    csv_download('lead-kemitraan-' . date('Ymd-His') . '.csv', ['ID', 'Lembaga', 'PIC', 'WhatsApp', 'Email', 'Jenis Kemitraan', 'Catatan Pengajuan', 'Status', 'Tanggal Masuk'], $csvRows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';

    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? DB::one("SELECT * FROM partners WHERE id=?", 'i', [$id]) : null;
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'Mitra');
        $logo = trim($_POST['logo'] ?? '');
        $upload = upload_asset_image('logo_file', 'partner');
        if (!$upload['ok']) {
            flash_set($upload['message'], 'err');
            header('Location: ' . admin_url('mitra', $id > 0 ? ['edit' => $id] : []));
            exit;
        }
        if (!empty($upload['file'])) {
            $logo = $upload['file'];
        }

        if ($name !== '') {
            if ($id > 0) {
                DB::run("UPDATE partners SET name=?, category=?, logo=? WHERE id=?", 'sssi', [$name, $category, $logo ?: null, $id]);
                audit('update_partner', $name);
                flash_set('Data mitra diperbarui.');
                if (!empty($existing['logo']) && $existing['logo'] !== $logo) {
                    delete_asset_image_if_unused($existing['logo']);
                }
            } else {
                DB::run("INSERT INTO partners (name, category, logo) VALUES (?,?,?)", 'sss', [$name, $category, $logo ?: null]);
                audit('create_partner', $name);
                flash_set('Mitra baru ditambahkan.');
            }
        }
    } elseif ($act === 'remove_logo') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = DB::one("SELECT * FROM partners WHERE id=?", 'i', [$id]);
        if ($existing && !empty($existing['logo'])) {
            DB::run("UPDATE partners SET logo=NULL WHERE id=?", 'i', [$id]);
            delete_asset_image_if_unused($existing['logo']);
            audit('remove_partner_logo', $existing['name'] ?? ('#' . $id));
            flash_set('Logo mitra dihapus.');
        }
    } elseif ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = DB::one("SELECT * FROM partners WHERE id=?", 'i', [$id]);
        DB::run("DELETE FROM partners WHERE id=?", 'i', [$id]);
        if (!empty($existing['logo'])) {
            delete_asset_image_if_unused($existing['logo']);
        }
        audit('delete_partner', '#' . $id);
        flash_set('Mitra dihapus.', 'err');
    } elseif ($act === 'lead_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'new';
        $existingLead = DB::one("SELECT * FROM partnership_leads WHERE id=?", 'i', [$id]);
        if (isset($leadStatuses[$status])) {
            DB::run("UPDATE partnership_leads SET status=? WHERE id=?", 'si', [$status, $id]);
            audit('update_partnership_lead', '#' . $id . ' -> ' . $status);
            if ($existingLead && ($existingLead['status'] ?? '') !== $status) {
                $notified = notify_lead_status_update('partnership', [
                    'organization_name' => $existingLead['organization_name'] ?? '',
                    'contact_name' => $existingLead['contact_name'] ?? '',
                    'phone' => $existingLead['phone'] ?? '',
                    'email' => $existingLead['email'] ?? '',
                    'partnership_type' => $existingLead['partnership_type'] ?? '',
                    'status' => $status,
                ]);
                audit('partnership_status_notification', $notified ? ('Lead #' . $id . ' notifikasi status terkirim') : ('Lead #' . $id . ' notifikasi status tidak aktif / gagal'));
            }
            flash_set('Status pengajuan kemitraan diperbarui.');
        }
        header('Location: ' . admin_url('mitra', $leadFilterQuery));
        exit;
    }

    header('Location: ' . admin_url('mitra'));
    exit;
}

$edit = isset($_GET['edit']) ? DB::one("SELECT * FROM partners WHERE id=?", 'i', [(int) $_GET['edit']]) : null;
$rows = DB::all("SELECT * FROM partners ORDER BY id DESC");
$totalLeadItems = (int) DB::val("SELECT COUNT(*) FROM (" . $leadSql . ") AS partnership_filtered", $leadTypes, $leadParams);
$totalLeadPages = total_pages($totalLeadItems, $perPage);
$currentLeadPage = min($currentLeadPage, $totalLeadPages);
$leadOffset = ($currentLeadPage - 1) * $perPage;
$leadRows = DB::all($leadSql . " ORDER BY created_at DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $leadOffset, $leadTypes, $leadParams);
$hasLeadFilter = !empty($leadFilterQuery);

admin_layout_header('mitra', 'Kelola Mitra');
flash_show();
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3><?= $edit ? 'Edit Mitra' : 'Tambah Mitra' ?></h3></div>
    <form method="post" class="form" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Nama Mitra</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required>
      <label>Kategori Mitra</label>
      <select name="category">
        <?php foreach ($categoryOptions as $option): ?>
        <option value="<?= e($option) ?>" <?= ($edit['category'] ?? 'Mitra') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Nama File Logo (opsional)</label><input type="text" name="logo" value="<?= e($edit['logo'] ?? '') ?>" placeholder="Contoh: logo-mitra.png">
      <label>Upload Logo</label><input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg">
      <?php if (!empty($edit['logo'])): ?>
      <div class="upload-preview">
        <img src="<?= e(asset('img/' . $edit['logo'])) ?>" alt="<?= e($edit['name'] ?? 'Preview logo mitra') ?>">
        <div class="upload-meta">
          <b>Preview Logo Saat Ini</b>
          <span class="muted"><?= e($edit['logo']) ?></span>
        </div>
      </div>
      <div class="inline-actions">
        <form method="post" onsubmit="return confirm('Hapus logo mitra ini?')">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="remove_logo">
          <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
          <button class="btn btn-ghost btn-sm">Hapus Logo Saat Ini</button>
        </form>
      </div>
      <?php endif; ?>
      <button class="btn btn-primary btn-block"><?= $edit ? 'Simpan Perubahan' : 'Tambah Mitra' ?></button>
      <?php if ($edit): ?><a class="btn btn-ghost btn-block" href="<?= admin_url('mitra') ?>">Batal</a><?php endif; ?>
    </form>
    <p class="note">Saat logo diganti atau dihapus, sistem akan mencoba membersihkan file lama jika sudah tidak dipakai konten lain.</p>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Daftar Mitra</h3><span class="muted"><?= count($rows) ?> mitra</span></div>
    <?php if (!$rows): ?><div class="empty-state small"><p>Belum ada data mitra.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Mitra</th><th>Kategori</th><th>Aksi</th></tr></thead>
      <tbody><?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <div class="table-media">
              <div class="table-thumb" style="background-image:url('<?= $row['logo'] ? e(asset('img/' . $row['logo'])) : asset('img/placeholder.svg') ?>')"></div>
              <div><b><?= e($row['name']) ?></b><br><small class="muted"><?= e($row['logo'] ?: 'Tanpa logo') ?></small></div>
            </div>
          </td>
          <td><?= e($row['category']) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= admin_url('mitra', ['edit' => $row['id']]) ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Hapus mitra ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="btn btn-ghost btn-sm">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Pengajuan Kerja Sama Masuk</h3>
    <div class="head-actions">
      <span class="muted"><?= number_format($totalLeadItems) ?> pengajuan</span>
      <a class="btn btn-ghost btn-sm" href="<?= admin_url('mitra', array_merge(request_query(['status', 'q', 'from', 'to']), ['export' => 'leads'])) ?>">Ekspor CSV</a>
    </div>
  </div>
  <form method="get" class="filter-form">
    <input type="hidden" name="r" value="mitra">
    <div class="filter-grid">
      <input type="text" name="q" value="<?= e($filterKeyword) ?>" placeholder="Cari lembaga, PIC, WA, email, jenis">
      <select name="status">
        <option value="">Semua status</option>
        <?php foreach ($leadStatuses as $key => $label): ?>
        <option value="<?= $key ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="from" value="<?= e($filterFrom) ?>">
      <input type="date" name="to" value="<?= e($filterTo) ?>">
      <button class="btn btn-primary btn-sm">Filter</button>
      <?php if ($hasLeadFilter): ?><a class="btn btn-ghost btn-sm" href="<?= admin_url('mitra') ?>">Reset</a><?php endif; ?>
    </div>
  </form>
  <?php if (!$leadRows): ?><div class="empty-state small"><p>Belum ada pengajuan kemitraan dari form publik.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Lembaga</th><th>PIC</th><th>Jenis</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody><?php foreach ($leadRows as $lead): ?>
      <tr>
        <td><b><?= e($lead['organization_name']) ?></b><br><small class="muted"><?= e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></small></td>
        <td>
          <b><?= e($lead['contact_name']) ?></b><br>
          <small class="muted"><?= e($lead['phone']) ?><?= $lead['email'] ? ' • ' . e($lead['email']) : '' ?></small>
        </td>
        <td><?= e($lead['partnership_type']) ?></td>
        <td><span class="badge badge-<?= $lead['status'] === 'approved' ? 'verified' : ($lead['status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e(human_label($lead['status'])) ?></span></td>
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
      <tr>
        <td colspan="5"><small class="muted"><b>Catatan Pengajuan:</b> <?= e($lead['message'] ?: '-') ?></small></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?= render_admin_pagination('mitra', $currentLeadPage, $totalLeadItems, $perPage, request_query(['status', 'q', 'from', 'to'])) ?>
  <?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
