<?php
$active_page = "akun";
include 'header.php';
include 'sidebar.php';

$id_user = $_SESSION['id_user'];

// Ambil data user terbaru
$user_now = $conn->query("SELECT * FROM users WHERE id_user = '$id_user'")->fetch_assoc();

// Proteksi jika data user tidak ditemukan di database
if (!$user_now) {
    $user_now = [
        'nama_lengkap' => 'User',
        'gmail' => '-',
        'username' => 'user'
    ];
}

// Flag untuk SweetAlert
$update_success = false;

// --- PROSES UPDATE PROFIL ---
if (isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $gmail = mysqli_real_escape_string($conn, $_POST['gmail']);
    
    // Update data dasar
    $conn->query("UPDATE users SET nama_lengkap = '$nama', username = '$username', gmail = '$gmail' WHERE id_user = '$id_user'");
    
    $_SESSION['username'] = $username;
    $_SESSION['nama_lengkap'] = $nama;

    if (!empty($_POST['password'])) {
        $pass = md5($_POST['password']);
        $conn->query("UPDATE users SET password = '$pass' WHERE id_user = '$id_user'");
    }
    
    $update_success = true;
    // Refresh data user_now agar tampilan langsung berubah
    $user_now = $conn->query("SELECT * FROM users WHERE id_user = '$id_user'")->fetch_assoc();
}
?>

<style>
    /* CSS Khusus Halaman Profil (Card Center) */
    .content-wrapper { display: flex; justify-content: center; align-items: flex-start; padding-top: 50px; background: #f8fafc; }
    .card-profile { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.04); width: 100%; max-width: 700px; overflow: hidden; margin-bottom: 50px; }
    .profile-header { background: linear-gradient(to bottom, #f8fafc, #ffffff); padding: 40px; text-align: center; border-bottom: 1px solid #f1f5f9; }
    .avatar-circle { 
        width: 90px; height: 90px; 
        background: linear-gradient(135deg, #0ea5e9, #2563eb); 
        color: white; border-radius: 24px; 
        display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 20px; 
        font-size: 2.2rem; font-weight: 800; 
        transform: rotate(-5deg); 
        box-shadow: 0 12px 20px rgba(14, 165, 233, 0.25); 
    }
    .profile-header h5 { font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .profile-header p { font-weight: 500; color: #64748b; }
    .form-section { padding: 40px; }
    .badge-role { background: #e0f2fe; color: #0369a1; font-weight: 700; padding: 6px 16px; border-radius: 100px; font-size: 0.7rem; letter-spacing: 0.5px; }
    .btn-update { background: var(--primary-blue); border: none; padding: 16px; border-radius: 14px; font-weight: 700; color: white; width: 100%; margin-top: 15px; font-size: 1rem; transition: all 0.3s; }
    .btn-update:hover { background: #0284c7; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3); }
</style>

<main class="content-wrapper">
    <div class="card-profile">
        <div class="profile-header">
            <div class="avatar-circle">
                <?= strtoupper(substr((string)($user_now['nama_lengkap'] ?? 'U'), 0, 1)) ?>
            </div>
            <h5 class="m-0 mb-1"><?= htmlspecialchars($user_now['nama_lengkap'] ?? 'User') ?></h5>
            <p class="small mb-3"><?= htmlspecialchars($user_now['gmail'] ?? '-') ?></p>
            <span class="badge-role"><?= strtoupper((string)($role ?? 'petugas')) ?></span>
        </div>
        
        <div class="form-section">
            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label-custom">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control form-control-custom" value="<?= htmlspecialchars($user_now['nama_lengkap'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Email Gmail</label>
                        <input type="email" name="gmail" class="form-control form-control-custom" value="<?= htmlspecialchars($user_now['gmail'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Username</label>
                        <input type="text" name="username" class="form-control form-control-custom" value="<?= htmlspecialchars($user_now['username'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Password Baru</label>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="Isi hanya jika ganti">
                    </div>
                </div>
                <div class="mt-5">
                    <button type="submit" name="update_profil" class="btn btn-update shadow-sm">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php if ($update_success): ?>
<script>
    // Trigger alert sukses setelah page load jika menggunakan SweetAlert dari footer
    window.addEventListener('load', function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data profil kamu sudah diperbarui.',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            customClass: { popup: 'rounded-4' }
        });
    });
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>