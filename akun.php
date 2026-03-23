<?php
// --- 1. PENGATURAN AWAL ---
$active_page = "akun"; // Menandai menu 'Akun' yang aktif di sidebar
include 'header.php';
include 'sidebar.php';

// Mengambil ID user dari session (siapa yang sedang login)
$id_user = $_SESSION['id_user'];

// --- 2. AMBIL DATA USER DARI DATABASE ---
// Mencari data lengkap user berdasarkan ID-nya
$user_now = $conn->query("SELECT * FROM users WHERE id_user = '$id_user'")->fetch_assoc();

// Proteksi: Jika data tidak ditemukan, buat data sementara agar tidak error
if (!$user_now) {
    $user_now = [
        'nama_lengkap' => 'User',
        'gmail' => '-',
        'username' => 'user'
    ];
}

// Variabel penanda apakah update berhasil (untuk memunculkan notifikasi nanti)
$update_success = false;

// --- 3. LOGIKA UPDATE PROFIL (Ketik Tombol Simpan Diklik) ---
if (isset($_POST['update_profil'])) {
    // Menangkap data dari form dan membersihkannya (mencegah SQL Injection)
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $gmail = mysqli_real_escape_string($conn, $_POST['gmail']);
    
    // Perintah SQL untuk update Nama, Username, dan Email
    $conn->query("UPDATE users SET nama_lengkap = '$nama', username = '$username', gmail = '$gmail' WHERE id_user = '$id_user'");
    
    // Update data Session agar nama di pojok layar/sidebar langsung berubah tanpa logout
    $_SESSION['username'] = $username;
    $_SESSION['nama_lengkap'] = $nama;

    // Logika Ganti Password: Jika kotak password diisi, baru kita update passwordnya
    if (!empty($_POST['password'])) {
        $pass = md5($_POST['password']); // Enkripsi password baru
        $conn->query("UPDATE users SET password = '$pass' WHERE id_user = '$id_user'");
    }
    
    // Set jadi true agar muncul SweetAlert sukses
    $update_success = true;
    
    // Ambil ulang data terbaru dari database agar tampilan form langsung terupdate
    $user_now = $conn->query("SELECT * FROM users WHERE id_user = '$id_user'")->fetch_assoc();
}
?>

<style>
    /* CSS Khusus: Mengatur agar card profil berada di tengah dan terlihat modern */
    .content-wrapper { display: flex; justify-content: center; align-items: flex-start; padding-top: 50px; background: #f8fafc; }
    .card-profile { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.04); width: 100%; max-width: 700px; overflow: hidden; margin-bottom: 50px; }
    
    /* Header profil dengan inisial nama */
    .profile-header { background: linear-gradient(to bottom, #f8fafc, #ffffff); padding: 40px; text-align: center; border-bottom: 1px solid #f1f5f9; }
    .avatar-circle { 
        width: 90px; height: 90px; 
        background: linear-gradient(135deg, #0ea5e9, #2563eb); 
        color: white; border-radius: 24px; 
        display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 20px; 
        font-size: 2.2rem; font-weight: 800; 
        transform: rotate(-5deg); /* Memberi kesan estetik/miring sedikit */
        box-shadow: 0 12px 20px rgba(14, 165, 233, 0.25); 
    }
    
    /* Tombol simpan perubahan */
    .btn-update { background: #2563eb; border: none; padding: 16px; border-radius: 14px; font-weight: 700; color: white; width: 100%; margin-top: 15px; transition: all 0.3s; }
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
            <span class="badge-role" style="background: #e0f2fe; color: #0369a1; padding: 5px 15px; border-radius: 50px; font-size: 11px;">
                <?= strtoupper((string)($role ?? 'petugas')) ?>
            </span>
        </div>
        
        <div class="form-section" style="padding: 40px;">
            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" style="border-radius: 10px;" value="<?= htmlspecialchars($user_now['nama_lengkap'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">Email Gmail</label>
                        <input type="email" name="gmail" class="form-control" style="border-radius: 10px;" value="<?= htmlspecialchars($user_now['gmail'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">Username</label>
                        <input type="text" name="username" class="form-control" style="border-radius: 10px;" value="<?= htmlspecialchars($user_now['username'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">Password Baru</label>
                        <input type="password" name="password" class="form-control" style="border-radius: 10px;" placeholder="Isi jika ingin ganti">
                    </div>
                </div>
                <div class="mt-5">
                    <button type="submit" name="update_profil" class="btn btn-update">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php if ($update_success): ?>
<script>
    // Munculkan pesan sukses jika data berhasil disimpan
    window.addEventListener('load', function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data profil kamu sudah diperbarui.',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>