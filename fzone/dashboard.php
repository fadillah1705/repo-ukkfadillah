<?php
$active_page = "dashboard";
include 'header.php';
include 'sidebar.php';

// Statistik Data
$res_stok = $conn->query("SELECT COUNT(*) as jml FROM barang")->fetch_assoc();
$total_stokBarang = max(0, (int)($res_stok['jml'] ?? 0));

$res_masuk = $conn->query("SELECT SUM(ABS(jumlah)) as jml FROM transaksi WHERE status='masuk' AND DATE(tanggal_transaksi)=CURDATE()")->fetch_assoc();
$total_barangMasuk = max(0, (int)($res_masuk['jml'] ?? 0));

$res_keluar = $conn->query("SELECT SUM(ABS(jumlah)) as jml FROM transaksi WHERE status='keluar' AND DATE(tanggal_transaksi)=CURDATE()")->fetch_assoc();
$total_barangKeluar = max(0, (int)($res_keluar['jml'] ?? 0));

$res_fisik = $conn->query("SELECT SUM(stok) as total FROM barang")->fetch_assoc();
$total_stokFisik = max(0, (int)($res_fisik['total'] ?? 0));

// Data untuk Chart
$labels = []; $data_masuk_7hari = []; $data_keluar_7hari = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($tgl)); 
    $qM = $conn->query("SELECT SUM(ABS(jumlah)) as total FROM transaksi WHERE status='masuk' AND DATE(tanggal_transaksi) = '$tgl'")->fetch_assoc();
    $data_masuk_7hari[] = max(0, (int)($qM['total'] ?? 0));
    $qK = $conn->query("SELECT SUM(ABS(jumlah)) as total FROM transaksi WHERE status='keluar' AND DATE(tanggal_transaksi) = '$tgl'")->fetch_assoc();
    $data_keluar_7hari[] = max(0, (int)($qK['total'] ?? 0));
}
?>

<main class="content-wrapper">
    <div class="container-fluid">
        <div class="mb-4">
            <h4 class="fw-bold m-0">Ringkasan Sistem</h4>
            <p class="text-muted small">Data inventaris real-time dan log aktivitas.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-gradient-blue">
                    <p>Jenis Barang</p>
                    <h3><?= number_format($total_stokBarang) ?></h3>
                    <div class="icon-box"><i class="fas fa-cubes"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-green">
                    <p>Masuk Hari Ini</p>
                    <h3><?= number_format($total_barangMasuk) ?></h3>
                    <div class="icon-box"><i class="fas fa-plus-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-red">
                    <p>Keluar Hari Ini</p>
                    <h3><?= number_format($total_barangKeluar) ?></h3>
                    <div class="icon-box"><i class="fas fa-minus-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-orange">
                    <p>Total Stok Unit</p>
                    <h3><?= number_format($total_stokFisik) ?></h3>
                    <div class="icon-box"><i class="fas fa-warehouse"></i></div>
                </div>
            </div>
        </div>

        <div class="card card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3">Tren Transaksi 7 Hari Terakhir</h6>
            <div style="height: 280px;"><canvas id="dailyStatsChart"></canvas></div>
        </div>

        <div class="card card-custom">
            <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0">Log Aktivitas Terkini</h5>
                <span class="badge bg-light text-dark border">20 Data Terakhir</span>
            </div>
            <div class="scroll-container">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">TANGGAL</th>
                            <th>NAMA BARANG</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center">QTY</th>
                            <th class="text-end pe-4">TOTAL HARGA</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php 
    $riwayat = $conn->query("SELECT t.tanggal_transaksi, t.status, b.nama, b.harga, t.jumlah as qty 
                             FROM transaksi t JOIN barang b ON t.id_barang = b.id_barang 
                             ORDER BY t.id_transaksi DESC LIMIT 20");
                             
    if ($riwayat && $riwayat->num_rows > 0):
        while($r = $riwayat->fetch_assoc()):
            $isKeluar = ($r['status'] == 'keluar');
            $total_nominal = abs($r['qty'] * $r['harga']);
    ?>
    <tr>
        <td class="ps-4">
            <div class="fw-bold" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?></div>
            <small class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($r['tanggal_transaksi'])) ?> WIB</small>
        </td>
        
        <td>
            <div class="fw-bold text-dark"><?= htmlspecialchars($r['nama']) ?></div>
            <small class="text-muted" style="font-size: 0.75rem;">@ Rp <?= number_format($r['harga']) ?></small>
        </td>

        <td class="text-center">
            <span class="<?= $isKeluar ? 'status-keluar' : 'status-masuk' ?>">
                <?= strtoupper($r['status']) ?>
            </span>
        </td>
        <td class="text-center">
            <span class="<?= $isKeluar ? 'qty-keluar' : 'qty-masuk' ?>">
                <?= number_format(abs($r['qty'])) ?>
            </span>
        </td>
        <td class="text-end pe-4 fw-bold text-primary">
            Rp <?= number_format($total_nominal) ?>
        </td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada aktivitas.</td></tr>
    <?php endif; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>