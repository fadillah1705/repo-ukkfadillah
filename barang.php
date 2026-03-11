<?php
$active_page = 'barang';
include 'header.php'; // Di dalam header sudah ada session_start dan include conn.php
include 'sidebar.php';

$primary_key = 'id_barang'; 
$swal_msg = null;

// --- PROSES HAPUS BARANG ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $cek_transaksi = $conn->query("SELECT id_transaksi FROM transaksi WHERE id_barang = $id_hapus LIMIT 1");
    
    if ($cek_transaksi->num_rows > 0) {
        $swal_msg = [
            'icon' => 'error', 
            'title' => 'Gagal Menghapus!', 
            'text' => 'Barang ini sudah memiliki riwayat transaksi. Silakan hapus transaksinya terlebih dahulu.'
        ];
    } else {
        $hapus = $conn->query("DELETE FROM barang WHERE id_barang = $id_hapus");
        if ($hapus) {
            $swal_msg = [
                'icon' => 'success', 
                'title' => 'Dihapus!', 
                'text' => 'Data barang telah berhasil dihapus.', 
                'redirect' => 'barang.php'
            ];
        }
    }
}

// --- PROSES EDIT BARANG ---
if (isset($_POST['ubah_barang'])) {
    $id = (int) $_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $harga = (int) $_POST['harga'];
    $cek = $conn->query("SELECT stok FROM barang WHERE id_barang = $id")->fetch_assoc();
    $stok_maksimal = (int)$cek['stok'];
    $s_baik  = max(0, (int)$_POST['stok_baik']);
    $s_rusak = max(0, (int)$_POST['stok_rusak']);

    if (($s_baik + $s_rusak) > $stok_maksimal) {
        $swal_msg = ['icon' => 'error', 'title' => 'Gagal!', 'text' => "Total fisik melebihi stok sistem ($stok_maksimal)."];
    } else {
        $sql = "UPDATE barang SET nama = '$nama', harga = '$harga', stok_baik = '$s_baik', stok_rusak = '$s_rusak' WHERE id_barang = $id";
        if ($conn->query($sql)) {
            $swal_msg = ['icon' => 'success', 'title' => 'Berhasil!', 'text' => 'Data barang telah diperbarui.', 'redirect' => 'barang.php'];
        }
    }
}

// --- DATA STATISTIK ---
$stat_total_aset  = $conn->query("SELECT SUM(stok * harga) as total FROM barang")->fetch_assoc()['total'] ?? 0;
$stat_total_jenis = $conn->query("SELECT COUNT(*) as jml FROM barang")->fetch_assoc()['jml'] ?? 0;
$stat_total_baik  = $conn->query("SELECT SUM(stok_baik) as total FROM barang")->fetch_assoc()['total'] ?? 0; 
$stat_total_rusak = $conn->query("SELECT SUM(stok_rusak) as total FROM barang")->fetch_assoc()['total'] ?? 0;

// Cek jika sedang mode edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $res_edit = $conn->query("SELECT * FROM barang WHERE $primary_key=$id");
    if ($res_edit && $res_edit->num_rows > 0) {
        $edit_data = $res_edit->fetch_assoc();
    }
}

$result = $conn->query("SELECT *, (stok * harga) as total_nilai FROM barang ORDER BY $primary_key DESC");
?>

<main class="content-wrapper">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">Inventaris Barang</h4>
            <p class="text-muted small mb-0">Manajemen stok fisik dan kontrol kualitas.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="window.location.href='transaksi_masuk.php'">
            <i class="fas fa-plus me-2"></i>Tambah Barang
        </button>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="mini-stat">
                <div class="icon bg-primary bg-opacity-10 text-primary p-3 rounded-4"><i class="fas fa-boxes"></i></div>
                <div><small class="text-muted d-block fw-bold" style="font-size:0.65rem; text-transform: uppercase;">JENIS PRODUK</small><strong><?= $stat_total_jenis ?> Item</strong></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mini-stat">
                <div class="icon bg-success bg-opacity-10 text-success p-3 rounded-4"><i class="fas fa-check-circle"></i></div>
                <div><small class="text-muted d-block fw-bold" style="font-size:0.65rem; text-transform: uppercase;">KONDISI BAIK</small><strong><?= number_format($stat_total_baik) ?> Unit</strong></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mini-stat">
                <div class="icon bg-danger bg-opacity-10 text-danger p-3 rounded-4"><i class="fas fa-times-circle"></i></div>
                <div><small class="text-muted d-block fw-bold" style="font-size:0.65rem; text-transform: uppercase;">KONDISI RUSAK</small><strong><?= number_format($stat_total_rusak) ?> Unit</strong></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mini-stat">
                <div class="icon bg-info bg-opacity-10 text-info p-3 rounded-4"><i class="fas fa-wallet"></i></div>
                <div><small class="text-muted d-block fw-bold" style="font-size:0.65rem; text-transform: uppercase;">NILAI ASET</small><strong class="text-primary">Rp <?= number_format($stat_total_aset) ?></strong></div>
            </div>
        </div>
    </div>

    <?php if ($edit_data): ?>
    <div class="card card-custom mb-4 border-top border-warning border-4 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="fw-bold text-warning m-0"><i class="fas fa-pen-to-square me-2"></i>Update Status: <?= $edit_data['nama'] ?></h6>
                <a href="barang.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit_data['id_barang'] ?>">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small fw-bold">Nama Barang</label><input type="text" name="nama" class="form-control" value="<?= $edit_data['nama'] ?>" required></div>
                    <div class="col-md-2"><label class="form-label small fw-bold text-success">Jumlah Baik</label><input type="number" name="stok_baik" id="input_baik" class="form-control" value="<?= $edit_data['stok_baik'] ?>" min="0" required></div>
                    <div class="col-md-2"><label class="form-label small fw-bold text-danger">Jumlah Rusak</label><input type="number" name="stok_rusak" id="input_rusak" class="form-control" value="<?= $edit_data['stok_rusak'] ?>" min="0" required></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Harga Satuan</label><input type="number" name="harga" class="form-control" value="<?= $edit_data['harga'] ?>" min="0" required></div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="btnUpdateFake" class="btn btn-warning w-100 fw-bold text-white shadow-sm" style="border-radius: 10px; padding: 10px;">Simpan</button>
                        <button type="submit" name="ubah_barang" id="btnUpdateReal" class="d-none"></button>
                    </div>
                </div>
                <div class="mt-2 small text-muted text-end">Batas Maksimal Stok Sistem: <strong id="maxStok"><?= $edit_data['stok'] ?></strong></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card card-custom">
        <div class="scroll-container">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Produk</th>
                        <th class="text-center">Total Stok</th>
                        <th>Kondisi Fisik</th>
                        <th class="text-end">Total Nilai</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="d-block fw-bold text-dark"><?= $d['nama'] ?></span>
                            <small class="text-muted">ID: #<?= $d[$primary_key] ?> • @ Rp <?= number_format($d['harga']) ?></small>
                        </td>
                        <td class="text-center"><span class="qty-pill"><?= $d['stok'] ?></span></td>
                        <td>
                            <span class="badge-kondisi bg-baik me-1">Baik: <?= $d['stok_baik'] ?></span>
                            <span class="badge-kondisi bg-rusak">Rusak: <?= $d['stok_rusak'] ?></span>
                        </td>
                        <td class="text-end fw-bold text-primary">Rp <?= number_format($d['total_nilai']) ?></td>
                        <td class="text-center">
                            <a href="?edit=<?= $d[$primary_key] ?>" class="btn-action text-warning"><i class="fas fa-pen"></i></a>
                            <a href="javascript:void(0)" class="btn-action text-danger btn-hapus" data-id="<?= $d[$primary_key] ?>" data-nama="<?= $d['nama'] ?>"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    var swal_data = <?= $swal_msg ? json_encode($swal_msg) : 'null' ?>;
</script>

<?php include 'footer.php'; ?>