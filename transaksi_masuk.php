<?php
$active_page = "transaksi_masuk";
include 'header.php'; // Memanggil header asli kamu (Plus Jakarta Sans)

// --- FUNGSI TAMBAH & PROSES BARANG MASUK ---
if (isset($_POST['tambah_barang_masuk'])) {
    $namas    = $_POST['nama'];
    $stoks    = $_POST['stok'];
    $hargas   = $_POST['harga'];
    $id_user  = $_SESSION['id_user'];
    $inserted_ids = [];
    $count = 0;

    foreach ($namas as $i => $val) {
        $nama   = mysqli_real_escape_string($conn, $namas[$i]);
        $stok   = (int)$stoks[$i];
        $harga  = (int)$hargas[$i];

        if ($stok <= 0 || $harga <= 0 || empty($nama)) continue; 

        // 1. Cek apakah barang sudah ada
        $cek_barang = $conn->query("SELECT id_barang FROM barang WHERE nama = '$nama' AND harga = '$harga' LIMIT 1");

        if ($cek_barang->num_rows > 0) {
            // Jika ada, update stok
            $data_lama = $cek_barang->fetch_assoc();
            $id_barang_final = $data_lama['id_barang'];
            $conn->query("UPDATE barang SET stok = stok + $stok, stok_baik = stok_baik + $stok WHERE id_barang = '$id_barang_final'");
        } else {
            // FUNGSI TAMBAH BARANG BARU (Jika barang belum terdaftar)
            $conn->query("INSERT INTO barang (nama, stok, harga, stok_baik, stok_rusak) VALUES ('$nama', '$stok', '$harga', '$stok', 0)");
            $id_barang_final = $conn->insert_id;
        }

        if ($id_barang_final) {
            // 2. Catat Riwayat Transaksi
            $conn->query("INSERT INTO transaksi (id_user, id_barang, jumlah, status) VALUES ('$id_user', '$id_barang_final', '$stok', 'masuk')");
            $inserted_ids[] = $conn->insert_id;
            $count++;
        }
    }
    
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
                        <th class="text-end pe-4">TOTAL</th>
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
                            <small class="text-muted" style="font-size: 0.75rem;">00:00 WIB</small>
                        </td>
                        <td>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($r['nama']) ?></span><br>
                            <small class="text-muted">Rp <?= number_format($r['harga']) ?></small>
                        </td>
                        <td class="text-center">
                            <span class="status-masuk">MASUK</span>
                        </td>
                        <td class="text-center">
                            <span class="qty-masuk"><?= abs($r['jumlah']) ?></span>
                        </td>
                        <td class="text-end pe-4 fw-bold text-primary">
                            Rp <?= number_format(abs($r['jumlah'] * $r['harga'])) ?>
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
    // Tambah Baris
    $("#addBtn").click(function() {
        var row = `<tr>
            <td><input type="text" name="nama[]" class="form-control bg-light border-0" required placeholder="Nama barang..."></td>
            <td><input type="number" name="harga[]" class="form-control bg-light border-0" required min="1" placeholder="0"></td>
            <td><input type="number" name="stok[]" class="form-control bg-light border-0 text-center fw-bold" required min="1" value="1"></td>
            <td class="text-center"><i class="fas fa-times-circle text-danger remove-row-btn" style="cursor:pointer; font-size:1.2rem;"></i></td>
        </tr>`;
        $("#formBody").append(row);
    });

    $(document).on('click', '.remove-row-btn', function() { $(this).closest('tr').remove(); });

    // Konfirmasi Simpan
    $("#btnSimpanClick").click(function() {
        Swal.fire({
            title: 'Simpan Transaksi?',
            text: "Data barang akan masuk ke inventaris.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Ya, Simpan!'
        }).then((result) => { if (result.isConfirmed) $("#submitReal").click(); });
    });
});
</script>

<?php include 'footer.php'; ?>