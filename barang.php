<?php
// --- 1. PENGATURAN & KEAMANAN ---
$active_page = 'barang';
include 'conn.php'; // Pastikan file koneksi database Anda benar
include 'header.php'; 
include 'sidebar.php';

// Pastikan session aktif untuk notifikasi SweetAlert
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$primary_key = 'id_barang'; 
$swal_msg = null;

// --- 2. LOGIKA PROSES (KHUSUS ADMIN) ---
if ($role === 'admin') {
    
    // A. LOGIKA "HAPUS" (Soft Delete)
    if (isset($_GET['hapus'])) {
        $id_hapus = (int)$_GET['hapus'];
        
        // Mengubah status menjadi 0 agar tidak tampil, tapi tetap ada di database untuk integritas data
        $query = $conn->query("UPDATE barang SET is_active = 0 WHERE id_barang = $id_hapus");
        
        if ($query) {
            $swal_msg = [
                'icon' => 'success', 
                'title' => 'Berhasil!', 
                'text' => 'Barang telah dihapus dari daftar inventaris.',
                'redirect' => 'barang.php'
            ];
        }
    }

    // B. LOGIKA EDIT: Simpan Perubahan
    if (isset($_POST['ubah_barang'])) {
        $id = (int) $_POST['id'];
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $harga = (int) $_POST['harga'];
        $s_baik  = max(0, (int)$_POST['stok_baik']);
        $s_rusak = max(0, (int)$_POST['stok_rusak']);
        $stok_total = $s_baik + $s_rusak;

        $sql = "UPDATE barang SET nama = '$nama', harga = '$harga', stok = '$stok_total', stok_baik = '$s_baik', stok_rusak = '$s_rusak' WHERE id_barang = $id";
        
        if ($conn->query($sql)) {
            $swal_msg = [
                'icon' => 'success', 
                'title' => 'Berhasil!', 
                'text' => 'Data barang telah diperbarui.',
                'redirect' => 'barang.php'
            ];
        }
    }
}

// --- 3. PENGAMBILAN DATA (Bisa diakses Admin & Petugas) ---
// Statistik hanya menghitung barang dengan is_active = 1
$stat_total_aset  = $conn->query("SELECT SUM(stok * harga) as total FROM barang WHERE is_active = 1")->fetch_assoc()['total'] ?? 0;
$stat_total_jenis = $conn->query("SELECT COUNT(*) as jml FROM barang WHERE is_active = 1")->fetch_assoc()['jml'] ?? 0;
$stat_total_baik  = $conn->query("SELECT SUM(stok_baik) as total FROM barang WHERE is_active = 1")->fetch_assoc()['total'] ?? 0; 
$stat_total_rusak = $conn->query("SELECT SUM(stok_rusak) as total FROM barang WHERE is_active = 1")->fetch_assoc()['total'] ?? 0;

// Ambil daftar barang yang aktif
$result = $conn->query("SELECT *, (stok * harga) as total_nilai FROM barang WHERE is_active = 1 ORDER BY $primary_key DESC");

// Data untuk mengisi form edit
$edit_data = null;
if (isset($_GET['edit']) && $role === 'admin') {
    $id = (int) $_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM barang WHERE $primary_key=$id")->fetch_assoc();
}
?>

<style>
    .table-responsive-scroll {
        max-height: 550px;
        overflow-y: auto;
    }
    .table-responsive-scroll thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 5;
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
</style>

<main class="content-wrapper p-4">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">Inventaris Barang</h4>
            <p class="text-muted small mb-0">Manajemen stok produk dan kualitas fisik secara real-time.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="export_excel.php" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-file-excel me-2"></i> EXPORT EXCEL
            </a>

            <?php if ($role === 'admin'): ?>
            <a href="transaksi_masuk.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-plus me-2"></i> TAMBAH BARANG
            </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="row g-3 mb-4">
        <?php 
        $cards = [
            ['primary', 'fa-boxes', 'JENIS PRODUK', $stat_total_jenis . ' Item'],
            ['success', 'fa-check-circle', 'KONDISI BAIK', number_format($stat_total_baik)],
            ['danger', 'fa-times-circle', 'KONDISI RUSAK', number_format($stat_total_rusak)],
            ['info', 'fa-wallet', 'NILAI ASET', 'Rp ' . number_format($stat_total_aset)]
        ];
        foreach ($cards as $c): ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="bg-<?= $c[0] ?> bg-opacity-10 text-<?= $c[0] ?> p-3 rounded-3 me-3">
                        <i class="fas <?= $c[1] ?> fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold" style="font-size: 11px;"><?= $c[2] ?></small>
                        <span class="h5 fw-bold mb-0"><?= $c[3] ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($edit_data): ?>
    <div class="card border-0 shadow-sm mb-4 border-start border-warning border-5 rounded-3">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-warning"><i class="fas fa-edit me-2"></i> Mode Edit Produk</h6>
            <form method="POST" class="row g-3">
                <input type="hidden" name="id" value="<?= $edit_data['id_barang'] ?>">
                <div class="col-md-4">
                    <label class="small fw-bold">Nama Barang</label>
                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($edit_data['nama']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-success">Stok Baik</label>
                    <input type="number" name="stok_baik" class="form-control" value="<?= $edit_data['stok_baik'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-danger">Stok Rusak</label>
                    <input type="number" name="stok_rusak" class="form-control" value="<?= $edit_data['stok_rusak'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Harga Unit</label>
                    <input type="number" name="harga" class="form-control" value="<?= $edit_data['harga'] ?>" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="ubah_barang" class="btn btn-warning w-100 fw-bold text-white shadow-sm">SIMPAN</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-bold">INFORMASI PRODUK</th>
                        <th class="text-center text-muted small fw-bold">STOK & KONDISI</th>
                        <th class="text-end text-muted small fw-bold">TOTAL NILAI</th>
                        <th class="text-center text-muted small fw-bold">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <span class="fw-bold d-block text-dark"><?= htmlspecialchars($d['nama']) ?></span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-primary border fw-normal">ID: #<?= $d['id_barang'] ?></span>
                                <span class="small text-muted">Rp <?= number_format($d['harga']) ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold small"><?= $d['stok'] ?> Unit</div>
                            <?php 
                                $total_k = $d['stok_baik'] + $d['stok_rusak'];
                                $p_baik = ($total_k > 0) ? ($d['stok_baik'] / $total_k) * 100 : 0;
                            ?>
                            <div class="progress mx-auto" style="height: 6px; width: 100px; border-radius: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= $p_baik ?>%"></div>
                                <div class="progress-bar bg-danger" style="width: <?= 100 - $p_baik ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-center gap-2 mt-1" style="font-size: 10px;">
                                <span class="text-success fw-bold">B: <?= $d['stok_baik'] ?></span>
                                <span class="text-danger fw-bold">R: <?= $d['stok_rusak'] ?></span>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-primary pe-4">Rp <?= number_format($d['total_nilai']) ?></td>
                        <td class="text-center">
                            <?php if ($role === 'admin'): ?>
                                <div class="btn-group shadow-sm">
                                    <a href="?edit=<?= $d['id_barang'] ?>" class="btn btn-sm btn-white border shadow-sm mx-1 text-warning"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-white border shadow-sm mx-1 text-danger btn-hapus" 
                                            data-id="<?= $d['id_barang'] ?>" 
                                            data-nama="<?= htmlspecialchars($d['nama']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-light text-muted fw-normal border px-3"><i class="fas fa-lock me-1"></i> Read Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Notifikasi SweetAlert untuk pesan sistem
<?php if ($swal_msg): ?>
Swal.fire({
    icon: '<?= $swal_msg['icon'] ?>',
    title: '<?= $swal_msg['title'] ?>',
    text: '<?= $swal_msg['text'] ?>',
    timer: 2000,
    showConfirmButton: false
}).then(() => {
    <?php if (isset($swal_msg['redirect'])): ?>
    window.location.href = '<?= $swal_msg['redirect'] ?>';
    <?php endif; ?>
});
<?php endif; ?>

// Konfirmasi Hapus dengan SweetAlert
document.querySelectorAll('.btn-hapus').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const nama = this.dataset.nama;
        Swal.fire({
            title: 'Hapus Barang?',
            text: "Barang '" + nama + "' akan dinonaktifkan dari daftar inventaris.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = '?hapus=' + id;
        });
    });
});
</script>

<?php include 'footer.php'; ?>