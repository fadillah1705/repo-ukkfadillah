<?php
// Memulai session agar data login bisa disimpan dan digunakan di halaman lain (seperti dashboard)
session_start();

// Menghubungkan ke file konfigurasi database
require 'conn.php'; 

// Variabel untuk menampung status login guna memicu notifikasi SweetAlert2 nantinya
$swal_status = ""; 

// Mengecek apakah tombol 'login' pada form sudah ditekan
if (isset($_POST['login'])) {
    
    /** * 1. PENGAMBILAN & PEMBERSIHAN DATA (SECURITY)
     * Menggunakan mysqli_real_escape_string untuk mencegah SQL Injection 
     * (serangan yang menyisipkan perintah database berbahaya melalui input form).
     */
    $input_user = mysqli_real_escape_string($conn, $_POST['username']);
    $email_user = mysqli_real_escape_string($conn, $_POST['email']);
    
    /**
     * 2. ENKRIPSI PASSWORD
     * Mengubah password input menjadi format MD5. 
     * Catatan: Pastikan di database kamu, password juga disimpan dalam format MD5.
     */
    $password   = md5($_POST['password']); 

    /**
     * 3. LOGIKA VALIDASI GANDA (USERNAME/EMAIL)
     * Query ini sangat fleksibel: user bisa login menggunakan Username ATAU Email.
     * Logika: (Username cocok OR Email cocok) AND Password harus benar.
     */
    $query = mysqli_query($conn, "SELECT * FROM users WHERE 
                                  (username='$input_user' OR gmail='$email_user') AND 
                                  password='$password'"); 
    
    // Mengecek apakah hasil query menemukan baris data (user ditemukan)
    if (mysqli_num_rows($query) > 0) {
        
        // Mengambil data user tersebut dari database ke dalam array $data
        $data = mysqli_fetch_assoc($query);
        
        /**
         * 4. PENYIMPANAN DATA KE SESSION
         * Data ini akan 'menempel' selama user belum logout atau menutup browser.
         * Digunakan untuk mengecek hak akses (role) dan menampilkan nama di dashboard.
         */
        $_SESSION['login']        = true;
        $_SESSION['id_user']      = $data['id_user']; 
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap']; 
        $_SESSION['role']         = $data['role']; // Contoh: 'admin' atau 'petugas'

        // Set status sukses untuk memicu SweetAlert sukses di bagian JavaScript
        $swal_status = "success";
        
    } else {
        // Jika tidak ada data yang cocok, set status error
        $swal_status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GUDANG SENTRAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root { --primary: #0ea5e9; --slate-bg: #f8fafc; }
        body { 
            background: var(--slate-bg); 
            font-family: 'Inter', sans-serif; 
            height: 100vh; display: flex; align-items: center; justify-content: center; 
            margin: 0;
        }
        /* Card Login dengan gaya modern (rounded besar & bayangan halus) */
        .login-card {
            background: white; border-radius: 24px; padding: 40px;
            width: 100%; max-width: 420px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .brand-section { text-align: center; margin-bottom: 30px; }
        .brand-logo {
            width: 60px; height: 60px; background: var(--primary); color: white;
            border-radius: 16px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 15px; box-shadow: 0 8px 16px rgba(14, 165, 233, 0.2);
        }
        .form-label { font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-group-text { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; border-radius: 12px 0 0 12px; }
        .form-control { border-color: #e2e8f0; border-radius: 0 12px 12px 0; padding: 12px; font-size: 0.9rem; }
        .btn-login { 
            background: var(--primary); border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-weight: 700; color: white; margin-top: 10px;
            transition: 0.3s;
        }
        .btn-login:hover { background: #0284c7; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-section">
        <div class="brand-logo"><i class="fas fa-boxes-stacked"></i></div>
        <h4 class="fw-bold text-dark m-0">GUDANG SENTRAL</h4>
        <p class="text-muted small">Sistem Manajemen Logistik</p>
    </div>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" class="form-control" placeholder="admin" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="admin@gmail.com" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn btn-login shadow-sm">
            LOGIN SEKARANG <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    /**
     * Menerima variabel status dari PHP.
     * Jika sukses, tampilkan popup hijau dan lempar user ke dashboard.
     * Jika error, tampilkan popup merah dengan pesan akses ditolak.
     */
    <?php if ($swal_status == "success"): ?>
        Swal.fire({
            icon: 'success',
            title: 'Login Berhasil!',
            text: 'Selamat datang, <?= $_SESSION['nama_lengkap'] ?>!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location.href = 'dashboard.php';
        });
    <?php elseif ($swal_status == "error"): ?>
        Swal.fire({
            icon: 'error',
            title: 'Akses Ditolak',
            text: 'Kombinasi Username/Email dan Password salah!',
            confirmButtonColor: '#0ea5e9'
        });
    <?php endif; ?>
});
</script>
</body>
</html>