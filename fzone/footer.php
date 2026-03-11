<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    
    // 1. GLOBAL: Logout Handler
    $(document).on('click', '#btnLogout', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Keluar dari Sistem?',
            text: "Sesi Anda akan berakhir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0ea5e9',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Logout!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    });

    // 2. GLOBAL: SweetAlert Flash Message
    <?php if (isset($swal_msg) && $swal_msg != null): ?>
        Swal.fire({
            icon: '<?= $swal_msg['icon'] ?>',
            title: '<?= $swal_msg['title'] ?>',
            text: '<?= $swal_msg['text'] ?>',
            confirmButtonColor: '#0ea5e9'
        }).then(() => {
            <?php if (isset($swal_msg['redirect'])): ?>
                window.location.href = '<?= $swal_msg['redirect'] ?>';
            <?php endif; ?>
        });
    <?php endif; ?>

    // 3. DASHBOARD ONLY: Chart & Activity Log
    <?php if ($active_page == 'dashboard'): ?>
    const chartCtx = document.getElementById('dailyStatsChart');
    if (chartCtx) {
        new Chart(chartCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels ?? []) ?>,
                datasets: [
                    { 
                        label: 'Masuk', 
                        data: <?= json_encode($data_masuk_7hari ?? []) ?>, 
                        borderColor: '#10b981', 
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4, fill: true, borderWidth: 3, pointRadius: 4
                    },
                    { 
                        label: 'Keluar', 
                        data: <?= json_encode($data_keluar_7hari ?? []) ?>, 
                        borderColor: '#f43f5e', 
                        backgroundColor: 'rgba(244, 63, 94, 0.1)',
                        tension: 0.4, fill: true, borderWidth: 3, pointRadius: 4
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { font: { family: "'Plus Jakarta Sans'", weight: '600' } } }
                },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, 
                    x: { grid: { display: false }, ticks: { font: { weight: '600' } } } 
                }
            }
        });
    }
    <?php endif; ?>

    // 4. DATA BARANG ONLY: Delete & Update Logic
    <?php if ($active_page == 'barang'): ?>
    $('.btn-hapus').click(function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        Swal.fire({
            title: 'Hapus Barang?',
            text: "Anda akan menghapus '" + nama + "'. Data tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'barang.php?hapus=' + id;
            }
        });
    });
    <?php endif; ?>

    // 5. TRANSAKSI KELUAR ONLY: Add Row & Save
    <?php if ($active_page == 'transaksi_keluar'): ?>
    // Fungsi Tambah Baris
    $(document).on('click', '#addBtn', function() {
        var row = `<tr>
            <td>
                <select name="id_barang[]" class="form-select" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php 
                    $brg2 = $conn->query("SELECT * FROM barang WHERE stok > 0 ORDER BY nama ASC"); 
                    while($b2 = $brg2->fetch_assoc()){ 
                        echo "<option value='".$b2['id_barang']."'>".$b2['nama']." (Tersedia: ".$b2['stok'].")</option>"; 
                    } 
                    ?>
                </select>
            </td>
            <td><input type="number" name="stok[]" class="form-control text-center" required min="1" value="1"></td>
            <td class="text-center">
                <i class="fas fa-times-circle text-danger remove-row-btn" style="cursor:pointer; font-size:1.2rem;"></i>
            </td>
        </tr>`;
        $("#formBody").append(row);
    });

    // Fungsi Hapus Baris
    $(document).on('click', '.remove-row-btn', function() {
        $(this).closest('tr').remove();
    });

    // Validasi Simpan Transaksi Keluar
    $(document).on('click', '#btnSimpan', function() {
        Swal.fire({
            title: 'Proses Transaksi?',
            text: "Pastikan jumlah barang keluar sudah benar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f43f5e',
            confirmButtonText: 'Ya, Simpan!'
        }).then((result) => { 
            if (result.isConfirmed) $("#submitReal").click(); 
        });
    });

    // Jika ada trigger View Nota (Modal)
    <?php if(isset($_GET['view_nota'])): ?>
        var myModal = new bootstrap.Modal(document.getElementById('modalNota'));
        myModal.show();
    <?php endif; ?>
    <?php endif; ?>

});
</script>
</body>
</html>