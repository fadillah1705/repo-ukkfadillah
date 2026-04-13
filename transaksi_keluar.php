<?php
// --- 1. SETUP & KEAMANAN ---
$active_page = "transaksi_keluar";
include 'header.php'; 
include 'sidebar.php';

// Mengambil ID User dari session
$id_user = $_SESSION['id_user'];

// --- 2. LOGIKA PROSES BARANG KELUAR ---
if (isset($_POST['tambah_barang_keluar'])) {
    $id_barangs = $_POST['id_barang'];
    $stoks      = $_POST['stok'];
    $inserted_ids = []; 
    $count = 0;

    foreach ($id_barangs as $i => $id_barang) {
        $id_barang = mysqli_real_escape_string($conn, $id_barang);
        $minta = (int)$stoks[$i];

        if ($minta <= 0 || empty($id_barang)) continue;

        // Cek ketersediaan stok di database
        $cek = $conn->query("SELECT nama, stok, stok_baik, stok_rusak FROM barang WHERE id_barang = '$id_barang'");
        $data = $cek->fetch_assoc();

        if ($data && $data['stok'] >= $minta) {
            $keluar_baik = 0;
            $keluar_rusak = 0;

            // Logika potong stok: Utamakan stok_baik, sisanya ambil dari stok_rusak
            if ($data['stok_baik'] >= $minta) {
                $keluar_baik = $minta;
            } else {
                $keluar_baik = $data['stok_baik'];
                $keluar_rusak = $minta - $keluar_baik;
            }

            // Hitung nilai baru
            $stok_total_baru = $data['stok'] - $minta;
            $stok_baik_baru  = $data['stok_baik'] - $keluar_baik;
            $stok_rusak_baru = $data['stok_rusak'] - $keluar_rusak;

            // Update Tabel Barang
            $conn->query("UPDATE barang SET stok = '$stok_total_baru', stok_baik = '$stok_baik_baru', stok_rusak = '$stok_rusak_baru' WHERE id_barang = '$id_barang'");
            
            // Simpan riwayat transaksi
            $conn->query("INSERT INTO transaksi (id_user, id_barang, jumlah, status) VALUES ('$id_user', '$id_barang', '$minta', 'keluar')");
            
            $inserted_ids[] = $conn->insert_id;
            $count++;
        }
    }
    
    // Jika berhasil, arahkan kembali dengan parameter nota
    if ($count > 0) {
        $ids_string = implode(',', $inserted_ids);
        echo "<script>window.location.href='transaksi_keluar.php?view_nota=$ids_string';</script>";
    }
}
?>

<style>
    /* CSS CUSTOM: IDENTIK DENGAN TRANSAKSI MASUK */
    .card-custom { border-radius: 12px; border: none; }
    .table thead th { 
        background-color: #f8f9fa; 
        color: #64748b; 
        font-size: 11px; 
        text-transform: uppercase; 
        letter-spacing: 1px;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .status-badge-keluar { 
        padding: 4px 10px; 
        border-radius: 6px; 
        font-size: 10px; 
        font-weight: 900; 
        background-color: #fee2e2; 
        color: #b91c1c; 
    }
    .scroll-container {
        max-height: 450px; 
        overflow-y: auto;
    }
    .scroll-container::-webkit-scrollbar { width: 5px; }
    .scroll-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    /* Warna fokus input (Merah untuk Keluar) */
    select.form-select:focus, input.form-control:focus { border-color: #ef4444; box-shadow: none; }
</style>

<main class="content-wrapper p-4">
    <div class="mb-4 no-print">
        <h4 class="fw-bold m-0 text-dark">Manajemen Barang Keluar</h4>
        <p class="text-muted small">Kurangi stok gudang untuk distribusi atau pemakaian.</p>
    </div>

    <div class="card card-custom shadow-sm mb-4 no-print">
        <div class="card-body p-4">
            <form method="post" id="formTransaksiKeluar">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <thead>
                            <tr>
                                <th>Nama Barang (Tersedia)</th>
                                <th style="width: 150px;" class="text-center">Jumlah Keluar</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="formBody">
                            <tr>
                                <td>
                                    <select name="id_barang[]" class="form-select bg-light border-0" required>
                                        <option value="">-- Pilih Barang --</option>
                                        <?php
                                        $brg = $conn->query("SELECT * FROM barang WHERE stok > 0 ORDER BY nama ASC");
                                        while($b = $brg->fetch_assoc()){
                                            echo "<option value='".$b['id_barang']."'>".$b['nama']." (Stok: ".$b['stok'].")</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td><input type="number" name="stok[]" class="form-control bg-light border-0 text-center fw-bold" required min="1" value="1"></td>
                                <td class="text-center"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                    <button type="button" id="addBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold">
                        <i class="fas fa-plus me-1"></i> Tambah Baris
                    </button>
                    <button type="button" id="btnProses" class="btn btn-danger px-5 fw-bold shadow-sm" style="border-radius: 10px;">
                        SIMPAN PENGELUARAN
                    </button>
                    <input type="submit" name="tambah_barang_keluar" id="submitReal" class="d-none">
                </div>
            </form>
        </div>
    </div>

    <style>
    .status-badge-keluar {
        background-color: rgba(220, 53, 69, 0.1); /* Warna merah (danger) dengan opacity */
        color: #dc3545;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    .text-primary-custom {
        color: #0d6efd;
    }
</style>

<div class="card card-custom shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold m-0 text-danger">Riwayat Barang Keluar</h5>
    </div>
    
    <div class="scroll-container">
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
                                         WHERE t.status = 'keluar' ORDER BY t.id_transaksi DESC LIMIT 20");
                
                if($riwayat->num_rows > 0):
                    while($r = $riwayat->fetch_assoc()):
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold" style="font-size: 0.85rem;">
                            <?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?>
                        </div>
                    </td>
                    <td>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($r['nama']) ?></span><br>
                        <small class="text-muted">Rp <?= number_format($r['harga']) ?></small>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-keluar">KELUAR</span>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold"><?= abs($r['jumlah']) ?></span>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        Rp <?= number_format(abs($r['jumlah'] * $r['harga'])) ?>
                    </td>
                    <td class="text-center">
                        <a href="?view_nota=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-light border shadow-sm" style="border-radius: 8px;">
                            <i class="fas fa-print text-muted"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-info-circle me-2"></i> Belum ada riwayat barang keluar.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php 
// 4. MODAL NOTA (VERSI RINGKAS & PAS)
if (isset($_GET['view_nota'])): 
    $ids = mysqli_real_escape_string($conn, $_GET['view_nota']);
    $query_nota = $conn->query("SELECT t.*, b.nama, b.harga, u.nama_lengkap as petugas 
                                FROM transaksi t 
                                JOIN barang b ON t.id_barang = b.id_barang 
                                LEFT JOIN users u ON t.id_user = u.id_user 
                                WHERE t.id_transaksi IN ($ids)");
    $data_nota = [];
    while($row = $query_nota->fetch_assoc()) { $data_nota[] = $row; }
    if (!empty($data_nota)):
?>
<div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 400px;"> <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            <div class="modal-body p-0">
                
                <div id="printArea" class="p-4 bg-white" style="font-size: 0.85rem;">
                    <div class="text-center mb-3">
                        <h5 class="fw-bold mb-0">GUDANG SENTRAL</h5>
                        <small class="text-muted" style="font-size: 0.7rem;">Nota Pengeluaran Barang</small><br>
                        <small class="text-muted" style="font-size: 0.7rem;"><?= date('d/m/Y H:i', strtotime($data_nota[0]['tanggal_transaksi'])) ?></small>
                        <hr class="my-2">
                    </div>
                    
                    <table class="table table-sm table-borderless mb-2">
                        <thead>
                            <tr class="border-bottom" style="font-size: 0.75rem;">
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand = 0;
                            foreach($data_nota as $it): 
                                $sub = $it['jumlah'] * $it['harga']; $grand += $sub;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($it['nama']) ?></td>
                                <td class="text-center"><?= abs($it['jumlah']) ?></td>
                                <td class="text-end"><?= number_format($it['harga']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-between fw-bold p-2 mb-3" style="background: #fff5f5; border-radius: 5px; color: #dc3545;">
                        <span>TOTAL</span>
                        <span>Rp <?= number_format(abs($grand)) ?></span>
                    </div>

                    <div class="row text-center mt-4" style="font-size: 0.75rem;">
                        <div class="col-6">
                            <p class="mb-4">Penerima,</p>
                            <p class="mb-0">( ............ )</p>
                        </div>
                        <div class="col-6">
                            <p class="mb-4">Admin,</p>
                            <p class="mb-0 fw-bold">( <?= htmlspecialchars($data_nota[0]['petugas'] ?? 'Admin') ?> )</p>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light d-flex gap-2 no-print">
                    <button type="button" class="btn btn-sm btn-secondary w-100 rounded-pill" onclick="tutupNota()">Tutup</button>
                    <button type="button" class="btn btn-sm btn-danger w-100 rounded-pill" onclick="printNota()">Cetak</button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        new bootstrap.Modal(document.getElementById('modalNota')).show();
    });

    function tutupNota() {
        window.location.href = 'transaksi_keluar.php';
    }

    function printNota() {
        var p = document.getElementById('printArea').innerHTML;
        var o = document.body.innerHTML;
        document.body.innerHTML = `<html><head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="padding:20px;">${p}</body></html>`;
        window.print();
        document.body.innerHTML = o;
        window.location.reload(); 
    }
</script>
<?php endif; endif; ?>

<?php include 'footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Gunakan IIFE (Immediately Invoked Function Expression) agar variabel tidak bentrok
    (function() {
        const btn = document.getElementById('addBtn');
        if (!btn) return;

        // Hapus semua event listener yang mungkin sudah menempel sebelumnya
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Kunci proteksi ganda

            const body = document.getElementById('formBody');
            if (body.rows.length > 0) {
                // Kloning baris pertama
                const newRow = body.rows[0].cloneNode(true);

                // Reset isi baris baru
                const inputQty = newRow.querySelector('input');
                const selectBrg = newRow.querySelector('select');
                
                if(inputQty) inputQty.value = 1;
                if(selectBrg) selectBrg.selectedIndex = 0;

                // Tambahkan tombol hapus di kolom terakhir (index 2)
                newRow.cells[2].innerHTML = `
                    <button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()">
                        <i class="fas fa-times-circle"></i>
                    </button>`;

                body.appendChild(newRow);
            }
        });

        // POPUP KONFIRMASI SIMPAN
        const btnProses = document.getElementById('btnProses');
        if (btnProses) {
            btnProses.addEventListener('click', function() {
                const form = document.getElementById('formTransaksiKeluar');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Keluar',
                    text: "Apakah data barang keluar sudah sesuai? Stok akan otomatis berkurang.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => { 
                    if (result.isConfirmed) {
                        document.getElementById('submitReal').click();
                    }
                });
            });
        }
    })();
</script>

</script>

<?php 
// 4. MODAL NOTA (IDENTIK DENGAN MASUK)
if (isset($_GET['view_nota'])): 
    $ids = mysqli_real_escape_string($conn, $_GET['view_nota']);
    $query_nota = $conn->query("SELECT t.*, b.nama, b.harga FROM transaksi t 
                                JOIN barang b ON t.id_barang = b.id_barang 
                                WHERE t.id_transaksi IN ($ids)");
    $data_nota = [];
    while($row = $query_nota->fetch_assoc()) { $data_nota[] = $row; }
?>
<div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md border-0">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-body p-0">
                <div id="printArea" class="p-4 bg-white">
                    <div class="text-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold mb-1">GUDANG SENTRAL</h5>
                        <p class="small text-muted mb-0">Nota Pengeluaran Barang</p>
                        <small class="text-muted">Tanggal: <?= date('d/m/Y', strtotime($data_nota[0]['tanggal_transaksi'])) ?></small>
                    </div>
                    <table class="table table-sm table-borderless small">
                        <thead>
                            <tr class="border-bottom text-muted">
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand = 0;
                            foreach($data_nota as $it): 
                                $sub = $it['jumlah'] * $it['harga']; $grand += $sub;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($it['nama']) ?></td>
                                <td class="text-center"><?= $it['jumlah'] ?></td>
                                <td class="text-end">Rp <?= number_format($sub) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top fw-bold">
                            <tr>
                                <td colspan="2">TOTAL NILAI</td>
                                <td class="text-end text-danger">Rp <?= number_format($grand) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="p-3 bg-light d-flex gap-2 no-print">
                    <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">TUTUP</button>
                    <button type="button" class="btn btn-danger w-100 rounded-pill fw-bold" onclick="printNota()">CETAK NOTA</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        new bootstrap.Modal(document.getElementById('modalNota')).show();
    });
    function printNota() {
        var p = document.getElementById('printArea').innerHTML;
        var o = document.body.innerHTML;
        document.body.innerHTML = p;
        window.print();
        document.body.innerHTML = o;
        window.location.reload();
    }
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>