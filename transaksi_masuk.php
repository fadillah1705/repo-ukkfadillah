<?php
// --- 1. SETUP HALAMAN ---
$active_page = "transaksi_masuk";
include 'header.php'; // Menggunakan font Plus Jakarta Sans agar estetika tetap terjaga

// --- 2. LOGIKA PROSES BARANG MASUK ---
if (isset($_POST['tambah_barang_masuk'])) {
    $namas    = $_POST['nama'];
    $stoks    = $_POST['stok'];
    $hargas   = $_POST['harga'];
    $id_user  = $_SESSION['id_user']; // Mengambil identitas petugas dari session
    $inserted_ids = [];               // Penampung ID untuk cetak nota kolektif
    $count = 0;

    // Looping karena user bisa menambah banyak baris barang sekaligus
    foreach ($namas as $i => $val) {
        $nama   = mysqli_real_escape_string($conn, $namas[$i]);
        $stok   = (int)$stoks[$i];
        $harga  = (int)$hargas[$i];

        // Validasi: Abaikan jika data tidak lengkap atau angka tidak valid
        if ($stok <= 0 || $harga <= 0 || empty($nama)) continue; 

        // LOGIKA CEK DUPLIKASI: 
        // Mencari apakah barang dengan nama & harga yang sama sudah terdaftar
        $cek_barang = $conn->query("SELECT id_barang FROM barang WHERE nama = '$nama' AND harga = '$harga' LIMIT 1");

        if ($cek_barang->num_rows > 0) {
            /** * JIKA BARANG SUDAH ADA:
             * Tambahkan jumlah stok yang masuk ke stok total dan stok_baik di database.
             */
            $data_lama = $cek_barang->fetch_assoc();
            $id_barang_final = $data_lama['id_barang'];
            $conn->query("UPDATE barang SET stok = stok + $stok, stok_baik = stok_baik + $stok WHERE id_barang = '$id_barang_final'");
        } else {
            /** * JIKA BARANG BARU:
             * Masukkan data barang baru ke tabel barang. 
             * Secara otomatis stok_baik diisi sebesar jumlah masuk, dan stok_rusak mulai dari 0.
             */
            $conn->query("INSERT INTO barang (nama, stok, harga, stok_baik, stok_rusak) VALUES ('$nama', '$stok', '$harga', '$stok', 0)");
            $id_barang_final = $conn->insert_id;
        }

        if ($id_barang_final) {
            // CATAT RIWAYAT: Masukkan aktivitas masuk ini ke tabel transaksi sebagai laporan.
            $conn->query("INSERT INTO transaksi (id_user, id_barang, jumlah, status) VALUES ('$id_user', '$id_barang_final', '$stok', 'masuk')");
            $inserted_ids[] = $conn->insert_id;
            $count++;
        }
    }
    
    // REDIRECT: Jika berhasil, arahkan kembali ke halaman ini sambil memicu modal nota
    if ($count > 0) {
        $ids_string = implode(',', $inserted_ids);
        echo "<script>window.location.href='transaksi_masuk.php?view_nota=$ids_string';</script>";
    }
}
?>

<?php include 'sidebar.php'; ?>

<main class="content-wrapper">
    <div class="mb-4 no-print">
        <h4 class="fw-bold m-0 text-dark">Input Barang Masuk</h4>
        <p class="text-muted small mb-0">Kelola stok masuk dengan tampilan yang konsisten.</p>
    </div>

    <div class="card card-custom no-print shadow-sm">
        <div class="card-body p-4">
            <form method="post" id="formTransaksiMasuk">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <thead>
                            <tr class="small fw-bold text-muted text-uppercase" style="letter-spacing: 1px;">
                                <th>Nama Barang</th>
                                <th style="width: 200px;">Harga Satuan (Rp)</th>
                                <th style="width: 120px;" class="text-center">QTY</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="formBody">
                            <tr>
                                <td><input type="text" name="nama[]" class="form-control bg-light border-0" required placeholder="Ketik nama barang..."></td>
                                <td><input type="number" name="harga[]" class="form-control bg-light border-0" required min="1" placeholder="0"></td>
                                <td><input type="number" name="stok[]" class="form-control bg-light border-0 text-center fw-bold" required min="1" value="1"></td>
                                <td class="text-center"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                    <button type="button" id="addBtn" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                        <i class="fas fa-plus me-1"></i> Tambah Baris
                    </button>
                    <button type="button" id="btnSimpanClick" class="btn btn-success px-4 fw-bold shadow-sm" style="border-radius: 10px;">
                        <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
                    </button>
                    <input type="submit" name="tambah_barang_masuk" id="submitReal" class="d-none">
                </div>
            </form>
        </div>
    </div>

    <div class="card card-custom no-print shadow-sm">
    <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold m-0 text-primary">Riwayat Barang Masuk</h5>
    </div>
    <div class="scroll-container" style="max-height: 450px; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">TANGGAL</th>
                    <th>NAMA BARANG</th>
                    <th class="text-center">STATUS</th>
                    <th class="text-center">QTY</th>
                    <th class="text-end">TOTAL</th>
                    <th class="text-center">NOTA</th> 
                </tr>
            </thead>
            <tbody>
                <?php 
                $riwayat = $conn->query("SELECT t.id_transaksi, t.tanggal_transaksi, b.nama, b.harga, t.jumlah 
                                         FROM transaksi t JOIN barang b ON t.id_barang = b.id_barang 
                                         WHERE t.status = 'masuk' ORDER BY t.id_transaksi DESC LIMIT 20");
                while($r = $riwayat->fetch_assoc()):
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?></div>
                        <small class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($r['tanggal_transaksi'])) ?> WIB</small>
                    </td>
                    <td>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($r['nama']) ?></span><br>
                        <small class="text-muted">Rp <?= number_format($r['harga']) ?></small>
                    </td>
                    <td class="text-center"><span class="status-masuk">MASUK</span></td>
                    <td class="text-center"><span class="qty-masuk"><?= abs($r['jumlah']) ?></span></td>
                    <td class="text-end fw-bold text-primary">Rp <?= number_format(abs($r['jumlah'] * $r['harga'])) ?></td>
                    <td class="text-center">
                        <a href="?view_nota=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-light border shadow-sm" style="border-radius: 8px;">
                            <i class="fas fa-print text-muted"></i>
                        </a>
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
    // FUNGSI: Tambah Baris Input Secara Dinamis menggunakan Template Literal
    $("#addBtn").click(function() {
        var row = `<tr>
            <td><input type="text" name="nama[]" class="form-control bg-light border-0" required placeholder="Nama barang..."></td>
            <td><input type="number" name="harga[]" class="form-control bg-light border-0" required min="1" placeholder="0"></td>
            <td><input type="number" name="stok[]" class="form-control bg-light border-0 text-center fw-bold" required min="1" value="1"></td>
            <td class="text-center"><i class="fas fa-times-circle text-danger remove-row-btn" style="cursor:pointer; font-size:1.2rem;"></i></td>
        </tr>`;
        $("#formBody").append(row);
    });

    // FUNGSI: Menghapus baris tertentu pada form
    $(document).on('click', '.remove-row-btn', function() { $(this).closest('tr').remove(); });

    // FUNGSI: Konfirmasi Simpan menggunakan SweetAlert2
    $("#btnSimpanClick").click(function() {
        Swal.fire({
            title: 'Simpan Transaksi?',
            text: "Data barang akan masuk ke inventaris.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Ya, Simpan!'
        }).then((result) => { 
            if (result.isConfirmed) $("#submitReal").click(); // Klik input submit asli yang tersembunyi
        });
    });
});
</script>

<?php if (isset($_GET['view_nota'])): 
    $ids = mysqli_real_escape_string($conn, $_GET['view_nota']);
    // Mengambil data lengkap gabungan untuk keperluan cetak nota
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
                        <p class="small text-muted mb-0 text-uppercase" style="letter-spacing:1px;">Nota Penerimaan Barang</p>
                        <small class="text-muted">Tanggal: <?= date('d/m/Y H:i', strtotime($data_nota[0]['tanggal_transaksi'])) ?></small>
                    </div>

                    <table class="table table-sm table-borderless small">
                        <thead>
                            <tr class="border-bottom">
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
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
                                <td class="text-end">Rp <?= number_format($item['harga']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-top fw-bold">
                                <td colspan="2">TOTAL NILAI MASUK</td>
                                <td class="text-end text-success">Rp <?= number_format($grand_total) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-5 d-flex justify-content-between small px-3">
                        <div class="text-center"><p class="mb-5">Pengirim/Supplier,</p></div>
                        <div class="text-center">
                            <p class="mb-5"><?= (strtolower($data_nota[0]['role']) == 'admin') ? 'Admin,' : 'Petugas Gudang,' ?></p>
                            <p class="fw-bold">( <?= htmlspecialchars($data_nota[0]['nama_petugas']) ?> )</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-3 bg-light d-flex gap-2 no-print">
                    <button type="button" class="btn btn-secondary w-100 rounded-pill" onclick="window.location.href='transaksi_masuk.php'">Tutup</button>
                    <button type="button" class="btn btn-primary w-100 rounded-pill" onclick="printNota()">
                        <i class="fas fa-print me-2"></i>Cetak Nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // FUNGSI: Menampilkan modal secara otomatis ketika halaman diakses dengan parameter view_nota
    $(document).ready(function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalNota'));
        myModal.show();
    });

    // FUNGSI: Eksekusi cetak nota tanpa mencetak elemen dashboard (no-print)
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
<?php include 'footer.php'; ?>