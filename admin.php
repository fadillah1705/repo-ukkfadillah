<?php
$active_page = "admin"; 
include 'header.php';   
include 'sidebar.php';  

// Proteksi ID Login
$id_log = $_SESSION['id_user'] ?? 0; 

if ($role !== 'admin') {
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}

// --- LOGIKA HAPUS ---
if (isset($_GET['hapus'])) {
    $id_h = mysqli_real_escape_string($conn, $_GET['hapus']);
    $cek_h = $conn->query("SELECT role FROM users WHERE id_user = '$id_h'")->fetch_assoc();
    if($cek_h['role'] == 'petugas') {
        $conn->query("DELETE FROM users WHERE id_user = '$id_h'");
    }
    echo "<script>window.location.href='admin.php';</script>";
}

// --- LOGIKA UPDATE ---
if (isset($_POST['update_user'])) {
    $id_t = $_POST['id_user'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $mail = mysqli_real_escape_string($conn, $_POST['gmail']);
    
    $cek_t = $conn->query("SELECT role FROM users WHERE id_user = '$id_t'")->fetch_assoc();
    
    if ($id_t == $id_log || $cek_t['role'] == 'petugas') {
        $conn->query("UPDATE users SET nama_lengkap='$nama', username='$user', gmail='$mail' WHERE id_user='$id_t'");
        if (!empty($_POST['password'])) {
            $pass = md5($_POST['password']);
            $conn->query("UPDATE users SET password='$pass' WHERE id_user='$id_t'");
        }
    }
    echo "<script>window.location.href='admin.php';</script>";
}

// --- LOGIKA TAMBAH ---
if (isset($_POST['tambah_petugas'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $mail = mysqli_real_escape_string($conn, $_POST['gmail']);
    $pass = md5($_POST['password']);
    $conn->query("INSERT INTO users (nama_lengkap, username, gmail, password, role) VALUES ('$nama', '$user', '$mail', '$pass', 'petugas')");
    echo "<script>window.location.href='admin.php';</script>";
}
?>

<main class="content-wrapper" style="background: #f8fafc; padding: 35px 50px;">
    <div class="container-fluid p-0" style="max-width: 1150px; margin-left: 0;">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h3 class="fw-bold text-dark m-0">Manajemen Pengguna</h3>
                <p class="text-muted m-0" style="font-size: 0.9rem;">Konfigurasi hak akses administrator dan petugas gudang.</p>
            </div>
            <button class="btn btn-primary fw-bold shadow-sm d-flex align-items-center" 
                    style="border-radius: 14px; padding: 12px 25px; font-size: 0.95rem; height: 48px;" 
                    data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fas fa-user-plus me-2"></i> Tambah Petugas
            </button>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 25px; overflow: hidden; background: #ffffff;">
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="text-muted" style="font-size: 0.75rem; letter-spacing: 1.2px; background: #fafbfc;">
                            <th class="border-0 py-4 ps-4">PETUGAS</th>
                            <th class="border-0 py-4">EMAIL</th>
                            <th class="border-0 py-4 text-center">USERNAME</th>
                            <th class="border-0 py-4 text-center">ROLE</th>
                            <th class="border-0 py-4 text-end pe-5">AKSI</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid #f1f5f9;">
                        <?php
                        $query = $conn->query("SELECT * FROM users ORDER BY role ASC");
                        $modal_data = [];
                        while($u = $query->fetch_assoc()):
                            $modal_data[] = $u;
                            $is_admin = ($u['role'] == 'admin');
                            $is_me = ($u['id_user'] == $id_log);
                        ?>
                        <tr>
                            <td class="py-3 ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center fw-bold shadow-sm" 
                                         style="width: 42px; height: 42px; background: <?= $is_admin ? '#fee2e2' : '#f0f9ff' ?>; 
                                                color: <?= $is_admin ? '#ef4444' : '#38bdf8' ?>; border-radius: 12px; font-size: 1rem;">
                                        <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark m-0" style="font-size: 1rem; line-height: 1.2;"><?= $u['nama_lengkap'] ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">ID: #<?= $u['id_user'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem;"><?= $u['gmail'] ?></td>
                            <td class="text-center">
                                <span class="badge border text-primary px-3 py-2" style="background: #f8faff; border-radius: 10px; font-size: 0.8rem; font-weight: 500;">
                                    @<?= $u['username'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge px-3 py-2" 
                                      style="background: <?= $is_admin ? '#fee2e2' : '#dcfce7' ?>; 
                                             color: <?= $is_admin ? '#ef4444' : '#22c55e' ?>; 
                                             border-radius: 10px; font-size: 0.7rem; font-weight: 800;">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-5">
                                <?php if(!$is_admin || $is_me): ?>
                                    <button class="btn btn-sm btn-light text-primary border-0 p-2 px-3 me-1" style="border-radius: 10px; background: #f1f5f9;" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $u['id_user'] ?>">
                                        <i class="fas fa-pen fa-sm"></i>
                                    </button>
                                <?php else: ?>
                                    <i class="fas fa-lock text-muted opacity-25 me-3" style="font-size: 0.9rem;"></i>
                                <?php endif; ?>

                                <?php if(!$is_admin): ?>
                                    <a href="admin.php?hapus=<?= $u['id_user'] ?>" class="btn btn-sm btn-light text-danger border-0 p-2 px-3" 
                                       style="background: #fff1f2; border-radius: 10px;" onclick="return confirm('Hapus petugas ini?')">
                                        <i class="fas fa-trash fa-sm"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <form method="POST" class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-4 text-center text-primary">Tambah Petugas</h5>
                <div class="row g-3">
                    <div class="col-12"><label class="small fw-bold text-muted mb-1">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" style="border-radius: 10px;" required></div>
                    <div class="col-12"><label class="small fw-bold text-muted mb-1">Email Gmail</label><input type="email" name="gmail" class="form-control" style="border-radius: 10px;" required></div>
                    <div class="col-6"><label class="small fw-bold text-muted mb-1">Username</label><input type="text" name="username" class="form-control" style="border-radius: 10px;" required></div>
                    <div class="col-6"><label class="small fw-bold text-muted mb-1">Password</label><input type="password" name="password" class="form-control" style="border-radius: 10px;" required></div>
                </div>
                <button type="submit" name="tambah_petugas" class="btn btn-primary w-100 fw-bold mt-4 py-2" style="border-radius: 12px;">Simpan Petugas</button>
            </div>
        </form>
    </div>
</div>

<?php foreach($modal_data as $row): ?>
<div class="modal fade" id="modalEdit<?= $row['id_user'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <form method="POST" class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-4 text-center text-primary">Edit Pengguna</h5>
                <input type="hidden" name="id_user" value="<?= $row['id_user'] ?>">
                <div class="row g-3">
                    <div class="col-12"><label class="small fw-bold text-muted mb-1">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" style="border-radius: 10px;" value="<?= $row['nama_lengkap'] ?>" required></div>
                    <div class="col-12"><label class="small fw-bold text-muted mb-1">Email Gmail</label><input type="email" name="gmail" class="form-control" style="border-radius: 10px;" value="<?= $row['gmail'] ?>" required></div>
                    <div class="col-6"><label class="small fw-bold text-muted mb-1">Username</label><input type="text" name="username" class="form-control" style="border-radius: 10px;" value="<?= $row['username'] ?>" required></div>
                    <div class="col-6"><label class="small fw-bold text-muted mb-1">Pass Baru</label><input type="password" name="password" class="form-control" style="border-radius: 10px;" placeholder="Kosongkan"></div>
                </div>
                <button type="submit" name="update_user" class="btn btn-primary w-100 fw-bold mt-4 py-2" style="border-radius: 12px;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include 'footer.php'; ?>