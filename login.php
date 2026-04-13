<?php
session_start();
require 'conn.php'; 
$swal_status = ""; 

if (isset($_POST['login'])) {
    $input_user = mysqli_real_escape_string($conn, $_POST['username']);
    $password   = md5($_POST['password']); 

    $query = mysqli_query($conn, "SELECT * FROM users WHERE 
                                  (username='$input_user') AND 
                                  password='$password'"); 
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['login']        = true;
        $_SESSION['id_user']      = $data['id_user']; 
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap']; 
        $_SESSION['role']         = $data['role']; 
        $swal_status = "success";
    } else {
        $swal_status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | GUDANG SENTRAL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root { --primary: #0ea5e9; --slate-100: #f1f5f9; --slate-500: #64748b; --slate-800: #1e293b; }
        body { background: radial-gradient(circle at top right, #e0f2fe, #f8fafc); font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 28px; padding: 48px; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08); border: 1px solid rgba(255, 255, 255, 0.7); animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .brand-section { text-align: center; margin-bottom: 35px; }
        .brand-logo { width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary), #38bdf8); color: white; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 20px; box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3); }
        .brand-title { font-weight: 800; color: var(--slate-800); font-size: 1.5rem; }
        .form-label { font-size: 0.75rem; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.05em; margin-left: 4px; }
        .input-group { background: var(--slate-100); border-radius: 14px; padding: 2px 8px; border: 2px solid transparent; transition: 0.2s; }
        .input-group:focus-within { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
        .input-group-text { background: transparent; border: none; color: var(--slate-500); }
        .form-control { background: transparent; border: none; padding: 14px 10px; font-size: 0.95rem; color: var(--slate-800); }
        .form-control:focus { box-shadow: none; }
        .btn-login { background: var(--slate-800); border: none; width: 100%; padding: 16px; border-radius: 14px; font-weight: 700; color: white; margin-top: 20px; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-login:hover { background: #000; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-section">
        <div class="brand-logo"><i class="fas fa-cubes"></i></div>
        <h4 class="brand-title m-0">GUDANG SENTRAL</h4>
        <p class="text-muted small">Login Akun dan Masuk ke Sistem</p>
    </div>

    <form method="POST" autocomplete="off">
        
        <input type="text" style="display:none">
        <input type="password" style="display:none">

        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-fingerprint"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Username" required autocomplete="off">
            </div>
        </div>


        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-shield-halved"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
            </div>
        </div>

        <button type="submit" name="login" class="btn btn-login">
            MASUK KE SYSTEM <i class="fas fa-chevron-right small"></i>
        </button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    <?php if ($swal_status == "success"): ?>
        Swal.fire({
            icon: 'success',
            title: 'Authentication Success',
            text: 'Welcome back, <?= $_SESSION['nama_lengkap'] ?>!',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        }).then(() => {
            window.location.href = 'dashboard.php';
        });
    <?php elseif ($swal_status == "error"): ?>
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: 'Invalid credentials. Please check your inputs.',
            confirmButtonColor: '#1e293b'
        });
    <?php endif; ?>
});
</script>
</body>
</html>