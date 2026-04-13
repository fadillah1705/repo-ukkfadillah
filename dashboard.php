<?php
// --- 1. SETUP & HITUNG STATISTIK ---
$active_page = "dashboard";
include 'header.php';
include 'sidebar.php';

// Menghitung jumlah jenis barang yang terdaftar
$res_stok = $conn->query("SELECT COUNT(*) as jml FROM barang")->fetch_assoc();
$total_stokBarang = max(0, (int)($res_stok['jml'] ?? 0));

// Menghitung barang masuk khusus HARI INI
$res_masuk = $conn->query("SELECT SUM(ABS(jumlah)) as jml FROM transaksi WHERE status='masuk' AND DATE(tanggal_transaksi)=CURDATE()")->fetch_assoc();
$total_barangMasuk = max(0, (int)($res_masuk['jml'] ?? 0));

// Menghitung barang keluar khusus HARI INI
$res_keluar = $conn->query("SELECT SUM(ABS(jumlah)) as jml FROM transaksi WHERE status='keluar' AND DATE(tanggal_transaksi)=CURDATE()")->fetch_assoc();
$total_barangKeluar = max(0, (int)($res_keluar['jml'] ?? 0));

// Menghitung total seluruh stok fisik yang ada di gudang
$res_fisik = $conn->query("SELECT SUM(stok) as total FROM barang")->fetch_assoc();
$total_stokFisik = max(0, (int)($res_fisik['total'] ?? 0));

// --- 2. PERSIAPAN DATA GRAFIK (7 Hari Terakhir) ---
$labels = []; $data_masuk_7hari = []; $data_keluar_7hari = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($tgl)); 
    
    // Ambil data masuk per tanggal
    $qM = $conn->query("SELECT SUM(ABS(jumlah)) as total FROM transaksi WHERE status='masuk' AND DATE(tanggal_transaksi) = '$tgl'")->fetch_assoc();
    $data_masuk_7hari[] = max(0, (int)($qM['total'] ?? 0));
    
    // Ambil data keluar per tanggal
    $qK = $conn->query("SELECT SUM(ABS(jumlah)) as total FROM transaksi WHERE status='keluar' AND DATE(tanggal_transaksi) = '$tgl'")->fetch_assoc();
    $data_keluar_7hari[] = max(0, (int)($qK['total'] ?? 0));
}
?>

<main class="content-wrapper">
    <div class="container-fluid">
        <div class="mb-4">
            <h4 class="fw-bold m-0 text-dark">Ringkasan Sistem</h4>
            <p class="text-muted small">Data inventaris real-time dan log aktivitas petugas.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded-4 shadow-sm border-0 position-relative overflow-hidden">
                    <p class="mb-1 opacity-75">Jenis Barang</p>
                    <h3 class="fw-bold mb-0"><?= number_format($total_stokBarang) ?></h3>
                    <i class="fas fa-cubes position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded-4 shadow-sm border-0 position-relative overflow-hidden">
                    <p class="mb-1 opacity-75">Masuk Hari Ini</p>
                    <h3 class="fw-bold mb-0"><?= number_format($total_barangMasuk) ?></h3>
                    <i class="fas fa-plus-circle position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger text-white p-3 rounded-4 shadow-sm border-0 position-relative overflow-hidden">
                    <p class="mb-1 opacity-75">Keluar Hari Ini</p>
                    <h3 class="fw-bold mb-0"><?= number_format($total_barangKeluar) ?></h3>
                    <i class="fas fa-minus-circle position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-dark p-3 rounded-4 shadow-sm border-0 position-relative overflow-hidden">
                    <p class="mb-1 opacity-75">Total Stok Unit</p>
                    <h3 class="fw-bold mb-0"><?= number_format($total_stokFisik) ?></h3>
                    <i class="fas fa-warehouse position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>

        <div class="card card-custom p-4 mb-4 border-0 shadow-sm rounded-4">
            <h6 class="fw-bold mb-3 text-dark">Tren Transaksi 7 Hari Terakhir</h6>
            <div style="height: 300px;"><canvas id="dailyStatsChart"></canvas></div>
        </div>

       <div class="card card-custom border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center rounded-top-4">
        <h5 class="fw-bold m-0 text-dark">Log Aktivitas Terkini</h5>
        <span class="badge bg-light text-dark border px-3 rounded-pill small">Data Terakhir</span>
    </div>
    
    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light small text-muted text-uppercase sticky-top" style="z-index: 10; background: #f8f9fa;">
                <tr>
                    <th class="ps-4" style="width: 150px;">TANGGAL</th>
                    <th>NAMA BARANG</th>
                    <th class="text-center" style="width: 120px;">STATUS</th>
                    <th class="text-center" style="width: 100px;">QTY</th>
                    <th class="text-end pe-4" style="width: 180px;">NILAI TRANSAKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $riwayat = $conn->query("SELECT t.tanggal_transaksi, t.status, b.nama, b.harga, t.jumlah as qty 
                                         FROM transaksi t JOIN barang b ON t.id_barang = b.id_barang 
                                         ORDER BY t.id_transaksi DESC LIMIT 10");
                if ($riwayat && $riwayat->num_rows > 0):
                    while($r = $riwayat->fetch_assoc()):
                        $isKeluar = ($r['status'] == 'keluar');
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-secondary" style="font-size: 0.85rem;">
                            <?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?>
                        </div>
                    </td>
                    
                    <td>
                        <div style="max-width: 250px;">
                            <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($r['nama']) ?>" style="font-size: 0.95rem;">
                                <?= htmlspecialchars($r['nama']) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                Harga Satuan: <span class="text-primary fw-medium">Rp <?= number_format($r['harga']) ?></span>
                            </div>
                        </div>
                    </td>
                    
                    <td class="text-center">
                        <?php if($isKeluar): ?>
                            <span class="badge rounded-pill px-3 py-2" 
                                  style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fecaca; font-size: 0.7rem; width: 85px; letter-spacing: 0.5px;">
                                KELUAR
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill px-3 py-2" 
                                  style="background-color: #dcfce7; color: #10b981; border: 1px solid #bbf7d0; font-size: 0.7rem; width: 85px; letter-spacing: 0.5px;">
                                MASUK
                            </span>
                        <?php endif; ?>
                    </td>

                    <td class="text-center">
                        <div class="d-inline-block px-2 py-1 rounded-2 fw-bold" 
                             style="background-color: #f1f5f9; color: #475569; font-size: 0.85rem; min-width: 40px; border: 1px solid #e2e8f0;">
                            <?= number_format(abs($r['qty'])) ?>
                        </div>
                    </td>

                    <td class="text-end pe-4 fw-bold text-primary" style="font-size: 0.95rem;">
                        Rp <?= number_format(abs($r['qty'] * $r['harga'])) ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('dailyStatsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                // Bagian ini mengunci nama yang muncul di Legend (atas) & Tooltip (saat kursor diarahkan)
                label: 'Barang Masuk', 
                data: <?= json_encode($data_masuk_7hari) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                // Bagian ini juga dikunci agar tidak berubah menjadi "Keluar" saja
                label: 'Barang Keluar', 
                data: <?= json_encode($data_keluar_7hari) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        // Ini agar saat kursor diarahkan, tampilan grafik tidak goyang (stabil)
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: { 
            legend: { 
                position: 'top',
                labels: {
                    // Memastikan teks legend tidak terpotong
                    boxWidth: 40,
                    padding: 20,
                    font: {
                        size: 13,
                        weight: '600'
                    }
                }
            },
            tooltip: {
                // Menampilkan kotak hitam yang rapi
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                padding: 12,
                cornerRadius: 8,
                displayColors: true // Munculkan kotak warna di dalam tooltip
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: { borderDash: [5, 5], color: '#e2e8f0' } 
            },
            x: { 
                grid: { display: false } 
            }
        }
    }
});
</script>