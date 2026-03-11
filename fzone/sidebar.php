<aside class="main-sidebar">
    <div class="d-flex flex-column align-items-center justify-content-center py-4 border-bottom border-secondary mb-3">
        <i class="fas fa-boxes-stacked fa-3x mb-4 text-primary"></i>
        <h3 class="font-weight-bold m-0 text-white" style="font-size: 1.2rem; letter-spacing: 1px;">GUDANG SENTRAL</h3>
        <p class="text-info small mt-2" style="font-weight: 600;">Halo, <?= htmlspecialchars(strtoupper($role)) ?></p>
    </div>
    <ul class="nav nav-pills flex-column">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= ($active_page == 'dashboard') ? 'active' : '' ?>">
                <i class="fas fa-th-large me-3"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="barang.php" class="nav-link <?= ($active_page == 'barang') ? 'active' : '' ?>">
                <i class="fas fa-box me-3"></i> Data Barang
            </a>
        </li>
        <li class="nav-item">
            <a href="transaksi_masuk.php" class="nav-link <?= ($active_page == 'transaksi_masuk') ? 'active' : '' ?>">
                <i class="fas fa-arrow-down me-3"></i> Barang Masuk
            </a>
        </li>
        <li class="nav-item">
            <a href="transaksi_keluar.php" class="nav-link <?= ($active_page == 'transaksi_keluar') ? 'active' : '' ?>">
                <i class="fas fa-arrow-up me-3"></i> Barang Keluar
            </a>
        </li>
        <?php if ($role == 'admin') : ?>
            <li class="nav-item">
                <a href="admin.php" class="nav-link <?= ($active_page == 'admin') ? 'active' : '' ?>">
                    <i class="fas fa-users-cog me-3"></i> Admin
                </a>
            </li>
        <?php else : ?>
            <li class="nav-item">
                <a href="akun.php" class="nav-link <?= ($active_page == 'akun') ? 'active' : '' ?>">
                    <i class="fas fa-user-circle me-3"></i> Akun
                </a>
            </li>
        <?php endif; ?>
        <li class="nav-item mt-5">
            <a href="#" id="btnLogout" class="nav-link text-danger">
                <i class="fas fa-power-off me-3"></i> Logout
            </a>
        </li>
    </ul>
</aside>