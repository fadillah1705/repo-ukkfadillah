<?php
$active_page = "transaksi_keluar";
include 'header.php';
include 'sidebar.php';

$id_user = $_SESSION['id_user'];
$swal_msg = null; 

// --- FUNGSI PROSES SIMPAN TRANSAKSI KELUAR ---
if (isset($_POST['tambah_barang_keluar'])) {
    $status_transaksi = 'keluar';
    $id_barangs = $_POST['id_barang'];
    $stoks      = $_POST['stok'];
    $inserted_ids = [];

    foreach ($id_barangs as $i => $id_barang) {
        $id_barang = mysqli_real_escape_string($conn, $id_barang);
        $minta = (int)$stoks[$i];

        if ($minta <= 0) continue;

        $cek = $conn->query("SELECT nama, stok, stok_baik, stok_rusak FROM barang WHERE id_barang = '$id_barang'");
        $data = $cek->fetch_assoc();

        if ($data && $data['stok'] >= $minta) {
            $keluar_baik = 0;
            $keluar_rusak = 0;

            // Logika Prioritas Stok Baik
            if ($data['stok_baik'] >= $minta) {
                $keluar_baik = $minta;
            } else {
                $keluar_baik = $data['stok_baik'];
                $keluar_rusak = $minta - $keluar_baik;
            }

            $stok_total_baru = $data['stok'] - $minta;
            $stok_baik_baru  = $data['stok_baik'] - $keluar_baik;
            $stok_rusak_baru = $data['stok_rusak'] - $keluar_rusak;

            // Update Stok
            $conn->query("UPDATE barang SET stok = '$stok_total_baru', stok_baik = '$stok_baik_baru', stok_rusak = '$stok_rusak_baru' WHERE id_barang = '$id_barang'");
            
            // Simpan Transaksi (Tanggal otomatis CURRENT_TIMESTAMP di DB)
            $conn->query("INSERT INTO transaksi (id_user, id_barang, jumlah, status) 
                          VALUES ('$id_user', '$id_barang', '$minta', '$status_transaksi')");
            
            $inserted_ids[] = $conn->insert_id;
        }
    }

    if (count($inserted_ids) > 0) {
        $list_id = implode(',', $inserted_ids);
        $swal_msg = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Barang keluar telah diproses.',
            'redirect' => "?view_nota=$list_id"
        ];
    }
}
?>

<main class="content-wrapper">
    <div class="mb-4 no-print">
        <h4 class="fw-bold m-0 text-dark">Input Barang Keluar</h4>
        <p class="text-muted small">Kurangi stok barang dari gudang secara otomatis.</p>
    </div>

    <div class="card card-custom no-print p-4">
        <form method="post" id="formTransaksiKeluar">
            <div class="table-responsive">
                <table class="table table-borderless align-middle">
                    <thead>
                        <tr class="small fw-bold text-muted text-uppercase">
                            <th>Nama Barang (Tersedia)</th>
                            <th style="width: 150px;" class="text-center">Jumlah Keluar</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="formBody">
                        <tr>
                            <td>
                                <select name="id_barang[]" class="form-select" required>
                                    <option value="">-- Pilih Barang --</option>
                                    <?php
                                    $brg = $conn->query("SELECT * FROM barang WHERE stok > 0 ORDER BY nama ASC");
                                    while($b = $brg->fetch_assoc()){
                                        echo "<option value='".$b['id_barang']."'>".$b['nama']." (Tersedia: ".$b['stok'].")</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="number" name="stok[]" class="form-control text-center" required min="1" value="1"></td>
                            <td class="text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                <button type="button" id="addBtn" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="fas fa-plus me-1"></i> Tambah Baris
                </button>
                <button type="button" id="btnSimpan" class="btn btn-danger px-4 fw-bold shadow-sm" style="border-radius: 10px;">
                    <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
                </button>
                <input type="submit" name="tambah_barang_keluar" id="submitReal" class="d-none">
            </div>
        </form>
    </div>

    <div class="card card-custom no-print">
        <div class="p-4 bg-white border-bottom">
            <h5 class="fw-bold m-0 text-danger"><i class="fas fa-history me-3"></i>Riwayat Barang Keluar</h5>
        </div>
        <div class="scroll-area">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-muted text-uppercase">
                        <th class="ps-4">Tanggal</th>
                        <th>Nama Barang</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $riwayat = $conn->query("SELECT t.id_transaksi, t.tanggal_transaksi, b.nama, b.harga, t.jumlah as qty, (t.jumlah * b.harga) as total_nominal 
                                             FROM transaksi t JOIN barang b ON t.id_barang = b.id_barang 
                                             WHERE t.status = 'keluar' 
                                             ORDER BY t.id_transaksi DESC LIMIT 20");
                    if($riwayat->num_rows > 0):
                        while($r = $riwayat->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="ps-4">
    <div class="fw-bold text-dark" style="font-size: 0.85rem;">
        <?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?>
    </div>
    <small class="text-muted" style="font-size: 0.75rem;">
        <?= date('H:i', strtotime($r['tanggal_transaksi'])) ?> WIB
    </small>
</td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['nama']) ?></div>
                            <small class="text-muted">@ Rp <?= number_format($r['harga']) ?></small>
                        </td>
                        <td class="text-center">
                            <span class="qty-keluar"><?= number_format($r['qty']) ?></span>
                        </td>
                        <td class="text-end fw-bold text-danger">Rp <?= number_format($r['total_nominal']) ?></td>
                        <td class="text-center">
                            <a href="?view_nota=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-light border"><i class="fas fa-print"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted small">Belum ada riwayat transaksi keluar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>