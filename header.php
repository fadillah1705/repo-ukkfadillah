<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? 'petugas';
$username_display = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (isset($active_page) && $active_page == 'barang') ? 'Data Barang' : 'Dashboard' ?> | GUDANG SENTRAL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 240px; 
            --primary-blue: #0ea5e9; 
            --dark-sidebar: #0f172a; 
            --bg-body: #f8fafc;
        }

        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        .main-sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background: var(--dark-sidebar); padding-top: 20px; z-index: 1000; }
        .content-wrapper { margin-left: var(--sidebar-width); padding: 25px; }
        
        /* Sidebar Nav */
        .nav-pills .nav-link { color: #94a3b8; margin: 4px 15px; border-radius: 10px; font-size: 0.95rem; font-weight: 600; }
        .nav-pills .nav-link.active { background: var(--primary-blue) !important; color: white !important; }
        .nav-pills .nav-link:hover:not(.active) { background: rgba(255,255,255,0.05); color: white; }

        /* Card Umum */
        .card-custom { background: #fff; border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; }
        .scroll-container { max-height: 500px; overflow-y: auto; }
        .scroll-container::-webkit-scrollbar { width: 6px; }
        .scroll-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* --- STYLE KHUSUS DASHBOARD (KARTU STATISTIK & NOTIFIKASI) --- */
        <?php if ($active_page == 'dashboard') : ?>
        /* Gaya Kartu Utama (Stat Cards) */
        .stat-card { 
            border-radius: 15px; 
            padding: 22px; 
            color: white; 
            border: none; 
            box-shadow: 0 6px 15px rgba(0,0,0,0.08); 
            position: relative; 
            overflow: hidden; 
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover { transform: translateY(-5px); }

        /* Ikon Transparan di Pojok Kartu */
        .stat-card .icon-box { 
            position: absolute; 
            right: 15px; 
            bottom: -5px; 
            font-size: 3.5rem; 
            opacity: 0.2; 
            z-index: 0;
        }

        .stat-card h3 { 
            font-size: 2rem; 
            font-weight: 800; 
            margin: 0; 
            position: relative; 
            z-index: 1; 
        }

        .stat-card p { 
            font-size: 0.8rem; 
            margin: 0; 
            font-weight: 700; 
            text-transform: uppercase; 
            opacity: 0.9; 
            position: relative; 
            z-index: 1; 
            letter-spacing: 0.8px; 
        }

        /* Warna Gradasi Kartu */
        .bg-gradient-blue   { background: linear-gradient(135deg, #0ea5e9, #2563eb); }
        .bg-gradient-green  { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-gradient-red    { background: linear-gradient(135deg, #f43f5e, #e11d48); }
        .bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

        /* Gaya Baris Riwayat di Dashboard */
        .table-dashboard tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.02);
        }

        /* Label Status & QTY agar tetap Kotak di Dashboard */
        .status-masuk { 
            background-color: #e6f6ec !important; 
            color: #10b981 !important; 
            padding: 4px 12px !important; 
            border-radius: 6px !important; 
            font-size: 0.70rem !important; 
            font-weight: 800 !important;
            display: inline-block;
        }
           .status-keluar { 
            background-color: #f6e6e6 !important; 
            color: #b91010 !important; 
            padding: 4px 12px !important; 
            border-radius: 6px !important; 
            font-size: 0.70rem !important; 
            font-weight: 800 !important;
            display: inline-block;
        }
        
        .qty-masuk { 
            background-color: #e6f6ec !important; 
            color: #1e3b25 !important; 
            padding: 6px 14px !important; 
            border-radius: 8px !important; 
            font-weight: 760 !important;
            display: inline-block;
        }
        .qty-keluar { 
            background-color: #f6e6e6 !important; 
            color: #2e0f0f !important; 
            padding: 6px 14px !important; 
            border-radius: 8px !important; 
            font-weight: 760 !important;
            display: inline-block;
        }
        <?php endif; ?>
        /* --- STYLE KHUSUS DATA BARANG --- */
        <?php if ($active_page == 'barang') : ?>
        .mini-stat { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px; height: 100%; border: 1px solid #f1f5f9; }
        .mini-stat strong { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
        .qty-pill { display: inline-block; padding: 5px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 800; background: #f1f5f9; color: #1e293b; }
        .badge-kondisi { padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .bg-baik { background: #dcfce7; color: #15803d; }
        .bg-rusak { background: #fee2e2; color: #b91c1c; }
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #fff; border: 1.5px solid #e2e8f0; transition: 0.2s; text-decoration: none; margin: 0 3px; }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: transparent; }
        <?php endif; ?>
        
      /* --- STYLE KHUSUS TRANSAKSI MASUK --- */
        <?php if ($active_page == 'transaksi_masuk') : ?>
        /* Gaya input form agar lebih modern */
        .form-control { 
            border-radius: 10px; 
            padding: 10px 15px; 
            font-size: 0.9rem; 
        }
        
        /* Gaya baris tabel input */
        #formBody tr { 
            border-bottom: 1px solid #f1f5f9; 
        }

        /* Tombol hapus baris */
        .remove-row-btn { 
            transition: 0.2s; 
            opacity: 0.7; 
        }
        .remove-row-btn:hover { 
            opacity: 1; 
            transform: scale(1.2); 
        }

        /* Badge status dan QTY khusus di riwayat transaksi masuk (Kotak Hijau) */
        .status-masuk { 
            background-color: #e6f6ec !important; 
            color: #28a745 !important; 
            padding: 4px 10px !important; 
            border-radius: 6px !important; 
            font-size: 0.70rem !important; 
            font-weight: 670 !important;
            display: inline-block;
        }

        .qty-masuk, .qty-label { 
            background-color: #e6f6ec !important; 
            color: #1e293b !important; 
            padding: 6px 14px !important; 
            border-radius: 8px !important; 
            font-size: 0.80rem !important; 
            font-weight: 670 !important;
            display: inline-block;
            min-width: 40px;
            text-align: center;
        }
        <?php endif; ?>
/* --- STYLE KHUSUS TRANSAKSI KELUAR --- */
        <?php if ($active_page == 'transaksi_keluar') : ?>
        .form-control, .form-select { 
            border-radius: 10px; 
            padding: 10px 15px; 
            border: 1px solid #f1f5f9;
        }
        
        /* Gaya Baris Tabel Input */
        #formBody tr { border-bottom: 1px solid #f1f5f9; }

        /* Tombol hapus baris */
        .remove-row-btn { transition: 0.2s; opacity: 0.6; cursor: pointer; }
        .remove-row-btn:hover { opacity: 1; color: #e11d48 !important; transform: scale(1.2); }

        /* Kotak Merah Khusus Keluar */
        .status-keluar { 
            background-color: #fbe9e9 !important; 
            color: #dc3545 !important; 
            padding: 4px 12px !important; 
            border-radius: 6px !important; 
            font-size: 0.70rem !important; 
            font-weight: 760 !important;
            display: inline-block;
            text-transform: uppercase;
        }

        .qty-keluar { 
            background-color: #fbe9e9 !important; 
            color: #1e293b !important; 
            padding: 5px 15px !important; 
            border-radius: 8px !important; 
            font-weight: 760 !important;
            display: inline-block;
        }
        
        /* Area Riwayat dengan Scroll */
        .scroll-area { max-height: 400px; overflow-y: auto; }
        .scroll-area::-webkit-scrollbar { width: 6px; }
        .scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        <?php endif; ?>
        /* --- STYLE KHUSUS ADMIN (MANAJEMEN PENGGUNA) --- */
        <?php if ($active_page == 'admin') : ?>
        /* Table Design Admin */
        .table thead { background: #f8fafc; }
        .table thead th { border: none; padding: 18px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }
        .table tbody td { padding: 18px; border-bottom: 1px solid #f1f5f9; }

        /* Avatar Table */
        .avatar-table { width: 40px; height: 40px; background: #e0f2fe; color: var(--primary-blue); display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 800; font-size: 0.9rem; }
        
        /* Role Badges */
        .role-badge { padding: 6px 14px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .badge-admin { background: #fee2e2; color: #dc2626; }
        .badge-petugas { background: #dcfce7; color: #16a34a; }

        /* Modal & Form Custom (Identik Akun) */
        .modal-content { border-radius: 28px !important; border: none !important; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15) !important; overflow: hidden; }
        .modal-header-profile { background: linear-gradient(to bottom, #f8fafc, #ffffff); padding: 35px; text-align: center; border-bottom: 1px solid #f1f5f9; }
        
        .avatar-circle-popup { 
            width: 80px; height: 80px; 
            background: linear-gradient(135deg, #0ea5e9, #2563eb); 
            color: white; border-radius: 22px; 
            display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 15px; font-size: 2rem; font-weight: 800; 
            box-shadow: 0 12px 20px rgba(14, 165, 233, 0.25); 
        }

        .form-label-custom { font-size: 0.75rem; font-weight: 700; color: #475569; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 8px; display: block; }
        .form-control-custom { border: 1.5px solid #e2e8f0; padding: 12px 16px; border-radius: 14px; font-size: 0.95rem; font-weight: 500; transition: all 0.2s; }
        .form-control-custom:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); outline: none; }

        .btn-update-popup { background: var(--primary-blue); border: none; padding: 14px; border-radius: 14px; font-weight: 700; color: white; width: 100%; transition: all 0.3s; }
        .btn-update-popup:hover { background: #0284c7; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3); }
        <?php endif; ?>
        /* Style Table Header Sticky */
        .table thead th { position: sticky; top: 0; z-index: 10; background: #f8fafc !important; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #64748b; padding: 15px; border-bottom: 2px solid #f1f5f9; }
        .table tbody td { padding: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>