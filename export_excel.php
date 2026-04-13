<?php
// 1. Hubungkan ke database
include 'conn.php'; 

// 2. Pengaturan Header
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Laporan_Stok_Barang_' . date('d-m-Y') . '.csv');

// 3. Mulai menulis data
$output = fopen('php://output', 'w');

// Memberitahu Excel untuk menggunakan KOMA sebagai pemisah (Tanpa BOM agar tidak muncul karakter aneh)
fwrite($output, "sep=,\n");

// 4. Membuat JUDUL KOLOM (Header)
fputcsv($output, array(
    'ID BARANG', 
    'NAMA PRODUK', 
    'STOK BAIK', 
    'STOK RUSAK', 
    'TOTAL STOK', 
    'HARGA SATUAN', 
    'TOTAL NILAI ASET'
));

// 5. Mengambil DATA dari database
$query = "SELECT *, (stok * harga) as total_nilai FROM barang WHERE is_active = 1 ORDER BY id_barang DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array(
            $row['id_barang'],
            $row['nama'],
            $row['stok_baik'],
            $row['stok_rusak'],
            $row['stok'],
            $row['harga'],
            $row['total_nilai']
        ));
    }
}

fclose($output);
exit;
?>