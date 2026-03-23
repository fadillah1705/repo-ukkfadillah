<?php
// --- 1. PENGATURAN & KEAMANAN ---
// Menandai halaman aktif untuk navigasi di sidebar
$active_page = 'barang';
include 'header.php'; 
include 'sidebar.php';

// Nama kolom kunci utama di tabel database
$primary_key = 'id_barang'; 
// Variabel penampung pesan notifikasi (SweetAlert) agar bisa diproses di bagian bawah (JS)
$swal_msg = null; 

// --- 2. PROTEKSI AKSES (Role-Based Access Control) ---
// Mengecek apakah ada permintaan hapus (GET) atau ubah (POST)
if (isset($_GET['hapus']) || isset($_POST['ubah_barang'])) {
    // Validasi: Jika user bukan admin, akses ditolak
    if ($role !== 'admin') {
        $swal_msg = [
            'icon' => 'error', 
            'title' => 'Akses Ditolak!', 
            'text' => 'Hanya Administrator yang boleh mengubah atau menghapus data.'
        ];
    } else {
        // --- 3. LOGIKA HAPUS BARANG (Database Transaction) ---
        if (isset($_GET['hapus'])) {
            $id_hapus = (int)$_GET['hapus']; // Casting ke integer untuk keamanan
            
            // Memulai transaksi database (agar jika satu gagal, semua dibatalkan)
            $conn->begin_transaction();
            try {
                // Langkah A: Hapus riwayat transaksi terkait barang ini (menghindari error Foreign Key)
                $conn->query("DELETE FROM transaksi WHERE id_barang = $id_hapus");
                
                // Langkah B: Hapus data barang utama
                $conn->query("DELETE FROM barang WHERE id_barang = $id_hapus");
                
                // Jika semua perintah berhasil, simpan perubahan secara permanen
                $conn->commit();

                $swal_msg = [
                    'icon' => 'success', 
                    'title' => 'Terhapus!', 
                    'text' => 'Barang dan riwayatnya telah dibersihkan.', 
                    'type' => 'redirect',
                    'url' => 'barang.php'
                ];
            } catch (Exception $e) {
                // Jika terjadi error, kembalikan database ke kondisi semula (Rollback)
                $conn->rollback();
                $swal_msg = ['icon' => 'error', 'title' => 'Gagal!', 'text' => 'Sistem gagal menghapus data.'];
            }
        }

        // --- 4. LOGIKA EDIT BARANG (Update & Validasi Fisik) ---
        if (isset($_POST['ubah_barang'])) {
            $id = (int) $_POST['id'];
            $nama = mysqli_real_escape_string($conn, $_POST['nama']); // Mencegah SQL Injection
            $harga = (int) $_POST['harga'];
            
            // Mengambil stok sistem terbaru untuk divalidasi dengan stok fisik
            $cek = $conn->query("SELECT stok FROM barang WHERE id_barang = $id")->fetch_assoc();
            $stok_maksimal = (int)$cek['stok'];
            
            // Memastikan nilai tidak negatif menggunakan fungsi max(0, ...)
            $s_baik  = max(0, (int)$_POST['stok_baik']);
            $s_rusak = max(0, (int)$_POST['stok_rusak']);

            /**
             * VALIDASI: Total fisik (Baik + Rusak) tidak boleh lebih dari total stok sistem.
             * Ini penting untuk menjaga integritas data logistik.
             */
            if (($s_baik + $s_rusak) > $stok_maksimal) {
                $swal_msg = ['icon' => 'error', 'title' => 'Gagal!', 'text' => "Total fisik melebihi stok sistem ($stok_maksimal)."];
            } else {
                // Eksekusi pembaruan data ke database
                $sql = "UPDATE barang SET nama = '$nama', harga = '$harga', stok_baik = '$s_baik', stok_rusak = '$s_rusak' WHERE id_barang = $id";
                if ($conn->query($sql)) {
                    $swal_msg = [
                        'icon' => 'success', 
                        'title' => 'Berhasil!', 
                        'text' => 'Data barang telah diperbarui.',
                        'type' => 'redirect',
                        'url' => 'barang.php'
                    ];
                }
            }
        }
    }
}

// --- 5. HITUNG STATISTIK (Dashboard Mini) ---
// SUM & COUNT digunakan untuk mendapatkan angka ringkasan di bagian atas halaman
$stat_total_aset  = $conn->query("SELECT SUM(stok * harga) as total FROM barang")->fetch_assoc()['total'] ?? 0;
$stat_total_jenis = $conn->query("SELECT COUNT(*) as jml FROM barang")->fetch_assoc()['jml'] ?? 0;
$stat_total_baik  = $conn->query("SELECT SUM(stok_baik) as total FROM barang")->fetch_assoc()['total'] ?? 0; 
$stat_total_rusak = $conn->query("SELECT SUM(stok_rusak) as total FROM barang")->fetch_assoc()['total'] ?? 0;

// Logika untuk mengambil data lama saat tombol Edit diklik (Admin only)
$edit_data = null;
if (isset($_GET['edit']) && $role === 'admin') {
    $id = (int) $_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM barang WHERE $primary_key=$id")->fetch_assoc();
}

// Mengambil semua daftar barang untuk ditampilkan di tabel (Urutkan dari yang terbaru)
$result = $conn->query("SELECT *, (stok * harga) as total_nilai FROM barang ORDER BY $primary_key DESC");
?>

<main class="content-wrapper">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">Inventaris Barang</h4>
            <p class="text-muted small mb-0">Manajemen stok fisik dan kontrol kualitas.</p>
        </div>
        <?php if ($role === 'admin'): ?>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="window.location.href='transaksi_masuk.php'">
            <i class="fas fa-plus me-2"></i>Tambah Barang
        </button>
        <?php endif; ?>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="mini-stat d-flex align-items-center bg-white p-3 rounded-4 shadow-sm border-0">
                <div class="icon bg-primary bg-opacity-10 text-primary p-3 rounded-4 me-3"><i class="fas fa-boxes fa-lg"></i></div>
                <div><small class="text-muted d-block fw-bold small">JENIS PRODUK</small><strong><?= $stat_total_jenis ?> Item</strong></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mini-stat d-flex align-items-center bg-white p-3 rounded-4 shadow-sm border-0">
                <div class="icon bg-success bg-opacity-10 text-success p-3 rounded-4 me-3"><i class="fas fa-check-circle fa-lg"></i></div>
                <div><small class="text-muted d-block fw-bold small">KONDISI BAIK</small><strong><?= number_format($stat_total_baik) ?> Unit</strong></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mini-stat d-flex align-items-center bg-white p-3 rounded-4 shadow-sm border-0">
                <div class="icon bg-danger bg-opacity-10 text-danger p-3 rounded-4 me-3"><i class="fas fa-times-circle fa-lg"></i></div>
                <div><small class="text-muted d-block fw-bold small">KONDISI RUSAK</small><strong><?= number_format($stat_total_rusak) ?> Unit</strong></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mini-stat d-flex align-items-center bg-white p-3 rounded-4 shadow-sm border-0">
                <div class="icon bg-info bg-opacity-10 text-info p-3 rounded-4 me-3"><i class="fas fa-wallet fa-lg"></i></div>
                <div><small class="text-muted d-block fw-bold small">NILAI ASET</small><strong class="text-primary">Rp <?= number_format($stat_total_aset) ?></strong></div>
            </div>
        </div>
    </div>

    <?php if ($edit_data && $role === 'admin'): ?>
    <div class="card mb-4 border-0 border-top border-warning border-4 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="fw-bold text-warning m-0"><i class="fas fa-edit me-2"></i>Edit: <?= htmlspecialchars($edit_data['nama']) ?></h6>
                <a href="barang.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit_data['id_barang'] ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Nama Barang</label>
                        <input type="text" name="nama" class="form-control rounded-3" value="<?= $edit_data['nama'] ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-success">Jumlah Baik</label>
                        <input type="number" name="stok_baik" class="form-control rounded-3" value="<?= $edit_data['stok_baik'] ?>" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-danger">Jumlah Rusak</label>
                        <input type="number" name="stok_rusak" class="form-control rounded-3" value="<?= $edit_data['stok_rusak'] ?>" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Harga Satuan</label>
                        <input type="number" name="harga" class="form-control rounded-3" value="<?= $edit_data['harga'] ?>" min="0" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="ubah_barang" class="btn btn-warning w-100 fw-bold text-white shadow-sm rounded-3">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light sticky-top" style="z-index: 5; background: #f8f9fa !important;">
                    <tr>
                        <th class="ps-4 py-3">Produk</th>
                        <th class="text-center">Stok Sistem</th>
                        <th>Kondisi Fisik</th>
                        <th class="text-end">Total Nilai</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="d-block fw-bold text-dark"><?= htmlspecialchars($d['nama']) ?></span>
                            <small class="text-muted">ID: #<?= $d[$primary_key] ?> • @ Rp <?= number_format($d['harga']) ?></small>
                        </td>
                        <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $d['stok'] ?></span></td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success small px-2">Baik: <?= $d['stok_baik'] ?></span>
                            <span class="badge bg-danger bg-opacity-10 text-danger small px-2">Rusak: <?= $d['stok_rusak'] ?></span>
                        </td>
                        <td class="text-end fw-bold text-primary">Rp <?= number_format($d['total_nilai']) ?></td>
                        <td class="text-center">
                            <?php if ($role === 'admin'): ?>
                                <a href="?edit=<?= $d[$primary_key] ?>" class="btn btn-sm btn-light text-warning rounded-3 me-1"><i class="fas fa-pen"></i></a>
                                <a href="?hapus=<?= $d[$primary_key] ?>" class="btn btn-sm btn-light text-danger rounded-3 btn-hapus"><i class="fas fa-trash"></i></a>
                            <?php else: ?>
                                <i class="fas fa-lock text-muted opacity-50" title="Hanya Admin"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // 1. Menampilkan notifikasi otomatis jika variabel $swal_msg dari PHP terisi
    <?php if ($swal_msg): ?>
        Swal.fire({
            icon: '<?= $swal_msg['icon'] ?>',
            title: '<?= $swal_msg['title'] ?>',
            text: '<?= $swal_msg['text'] ?>',
            confirmButtonColor: '#0ea5e9'
        }).then((result) => {
            // Jika ada instruksi redirect, pindah halaman setelah tombol OK diklik
            <?php if (isset($swal_msg['type']) && $swal_msg['type'] == 'redirect'): ?>
                window.location.href = '<?= $swal_msg['url'] ?>';
            <?php endif; ?>
        });
    <?php endif; ?>

    // 2. Konfirmasi Hapus yang interaktif
    $('.btn-hapus').on('click', function(e) {
        e.preventDefault(); // Mencegah link langsung berjalan
        const href = $(this).attr('href');

        Swal.fire({
            title: 'Apakah anda yakin?',
            text: "Data barang dan riwayat transaksinya akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika user klik "Ya", maka pindahkan ke URL hapus yang asli
                window.location.href = href;
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>