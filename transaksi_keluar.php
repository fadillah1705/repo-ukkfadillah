<?php
// --- 1. SETUP HALAMAN ---
$active_page = "transaksi_keluar";
include 'header.php';
include 'sidebar.php';

// Mengambil ID User dari session untuk mencatat siapa petugas yang memproses transaksi
$id_user = $_SESSION['id_user'];
$swal_msg = null; 

// --- 2. LOGIKA PROSES SIMPAN TRANSAKSI KELUAR ---
// Mengecek apakah tombol simpan sudah diklik
if (isset($_POST['tambah_barang_keluar'])) {
    $status_transaksi = 'keluar';
    $id_barangs = $_POST['id_barang']; // Menangkap array ID barang dari form
    $stoks      = $_POST['stok'];      // Menangkap array jumlah barang dari form
    $inserted_ids = [];                // Penampung ID transaksi untuk ditampilkan di nota sekaligus

    // Melakukan perulangan (looping) karena input bisa lebih dari satu baris
    foreach ($id_barangs as $i => $id_barang) {
        $id_barang = mysqli_real_escape_string($conn, $id_barang);
        $minta = (int)$stoks[$i]; // Jumlah barang yang ingin dikeluarkan

        // Validasi: Jika input jumlah 0 atau negatif, maka baris ini dilewati
        if ($minta <= 0) continue;

        // Mencek ketersediaan stok di database berdasarkan ID Barang
        $cek = $conn->query("SELECT nama, stok, stok_baik, stok_rusak FROM barang WHERE id_barang = '$id_barang'");
        $data = $cek->fetch_assoc();

        // Cek apakah total stok gudang mencukupi permintaan user
        if ($data && $data['stok'] >= $minta) {
            $keluar_baik = 0;
            $keluar_rusak = 0;

            /** * LOGIKA PRIORITAS KONDISI BARANG:
             * Sistem akan otomatis memotong stok_baik terlebih dahulu.
             * Jika stok_baik tidak cukup, sisanya baru memotong stok_rusak.
             */
            if ($data['stok_baik'] >= $minta) {
                $keluar_baik = $minta; // Semuanya diambil dari stok baik
            } else {
                $keluar_baik = $data['stok_baik']; // Ambil semua stok baik yang tersisa
                $keluar_rusak = $minta - $keluar_baik; // Sisanya diambil dari stok rusak
            }

            // Menghitung sisa stok baru (Total, Baik, dan Rusak)
            $stok_total_baru = $data['stok'] - $minta;
            $stok_baik_baru  = $data['stok_baik'] - $keluar_baik;
            $stok_rusak_baru = $data['stok_rusak'] - $keluar_rusak;

            // Update data stok terbaru ke tabel barang
            $conn->query("UPDATE barang SET stok = '$stok_total_baru', stok_baik = '$stok_baik_baru', stok_rusak = '$stok_rusak_baru' WHERE id_barang = '$id_barang'");
            
            // Mencatat aktivitas ini ke tabel transaksi (Riwayat)
            $conn->query("INSERT INTO transaksi (id_user, id_barang, jumlah, status) 
                          VALUES ('$id_user', '$id_barang', '$minta', '$status_transaksi')");
            
            // Menyimpan ID transaksi yang baru saja diproses untuk pemicu Cetak Nota
            $inserted_ids[] = $conn->insert_id;
        }
    }

    // Jika ada minimal 1 data yang berhasil disimpan, siapkan notifikasi sukses
    if (count($inserted_ids) > 0) {
        $list_id = implode(',', $inserted_ids); // Menggabungkan ID transaksi (misal: 1,2,3)
        $swal_msg = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Barang keluar telah diproses.',
            'redirect' => "?view_nota=$list_id" // Mengarahkan ke parameter nota
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
                                    // Hanya memunculkan barang yang stoknya di atas 0
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
                <button type="submit" name="tambah_barang_keluar" class="btn btn-danger px-4 fw-bold shadow-sm" style="border-radius: 10px;">
                    <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
                </button>
            </div>
        </form>
    </div>

    <div class="card card-custom no-print">
        <div class="p-4 bg-white border-bottom">
            <h5 class="fw-bold m-0 text-danger"><i class="fas fa-history me-3"></i>Riwayat Barang Keluar</h5>
        </div>
        <div class="table-responsive">
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
                    // Mengambil 20 riwayat transaksi keluar terbaru
                    $riwayat = $conn->query("SELECT t.id_transaksi, t.tanggal_transaksi, b.nama, b.harga, t.jumlah as qty, (t.jumlah * b.harga) as total_nominal 
                                             FROM transaksi t JOIN barang b ON t.id_barang = b.id_barang 
                                             WHERE t.status = 'keluar' 
                                             ORDER BY t.id_transaksi DESC LIMIT 20");
                    if($riwayat->num_rows > 0):
                        while($r = $riwayat->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($r['tanggal_transaksi'])) ?> WIB</small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['nama']) ?></div>
                            <small class="text-muted">@ Rp <?= number_format($r['harga']) ?></small>
                        </td>
                        <td class="text-center"><span><?= number_format($r['qty']) ?></span></td>
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

<?php if (isset($_GET['view_nota'])): 
    $ids = mysqli_real_escape_string($conn, $_GET['view_nota']);
    // Mengambil detail transaksi untuk dicetak pada nota fisik
    $query_nota = $conn->query("SELECT t.*, b.nama, b.harga, u.nama_lengkap as nama_petugas, u.role 
                                FROM transaksi t 
                                JOIN barang b ON t.id_barang = b.id_barang 
                                JOIN users u ON t.id_user = u.id_user 
                                WHERE t.id_transaksi IN ($ids)");
    $data_nota = [];
    while($row = $query_nota->fetch_assoc()) { $data_nota[] = $row; }
    
    if(!empty($data_nota)):
?>
<div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none;">
            <div class="modal-body p-0">
                <div id="printArea" class="p-4 bg-white">
                    <div class="text-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold mb-1">GUDANG SENTRAL</h5>
                        <p class="small text-muted mb-0">Nota Pengeluaran Barang</p>
                        <small class="text-muted">Tanggal: <?= date('d/m/Y H:i', strtotime($data_nota[0]['tanggal_transaksi'])) ?></small>
                    </div>

                    <table class="table table-sm table-borderless small">
                        <thead>
                            <tr class="border-bottom">
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            foreach($data_nota as $item): 
                                $subtotal = $item['jumlah'] * $item['harga'];
                                $grand_total += $subtotal;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama']) ?></td>
                                <td class="text-center"><?= $item['jumlah'] ?></td>
                                <td class="text-end">Rp <?= number_format($subtotal) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-top fw-bold">
                                <td colspan="2">TOTAL</td>
                                <td class="text-end text-danger">Rp <?= number_format($grand_total) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-5 d-flex justify-content-between small px-3">
                        <div class="text-center"><p class="mb-5">Dikeluarkan oleh,</p></div>
                        <div class="text-center">
                            <p class="mb-5"><?= (strtolower($data_nota[0]['role']) == 'admin') ? 'Admin,' : 'Petugas Gudang,' ?></p>
                            <p class="fw-bold">( <?= htmlspecialchars($data_nota[0]['nama_petugas']) ?> )</p>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light d-flex gap-2 no-print">
                    <button type="button" class="btn btn-secondary w-100 rounded-pill" onclick="window.location.href='transaksi_keluar.php'">Tutup</button>
                    <button type="button" class="btn btn-primary w-100 rounded-pill" onclick="printNota()">
                        <i class="fas fa-print me-2"></i>Cetak Nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Memunculkan modal secara otomatis saat halaman selesai dimuat
    window.addEventListener('load', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalNota'));
        myModal.show();
    });

    // Fungsi Cetak: Hanya mencetak area nota saja (printArea)
    function printNota() {
        var printContents = document.getElementById('printArea').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
    }
</script>
<?php endif; endif; ?>

<script>
    // JS: Menambahkan baris input baru secara dinamis
    document.getElementById('addBtn').onclick = function() {
        let body = document.getElementById('formBody');
        let newRow = body.rows[0].cloneNode(true);
        newRow.querySelector('input').value = 1; 
        newRow.cells[2].innerHTML = '<button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button>';
        body.appendChild(newRow);
    };
</script>

<?php include 'footer.php'; ?>