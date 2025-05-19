<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Mapping antara nama file dan judul halaman
$page_titles = [
    'index.php' => 'Dashboard',
    'admin.php' => 'Admin',
    'siswa.php' => 'Siswa',
    'pembina.php' => 'Pembina',
    'wakil.php' => 'Wakil Kepala Sekolah',
    'ekstrakulikuler.php' => 'Ekstrakulikuler',
];

// Ambil judul dari mapping, fallback ke nama file jika tidak ditemukan
$page_title = $page_titles[$current_page] ?? ucfirst(pathinfo($current_page, PATHINFO_FILENAME));
?>

<nav class="navbar navbar-main navbar-expand-lg mx-5 px-0 shadow-none rounded" id="navbarBlur" navbar-scroll="true">
    <div class="container-fluid py-1 px-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                    <?= htmlspecialchars($page_title) ?>
                </li>
            </ol>
            <h6 class="font-weight-bold mb-0"><?= htmlspecialchars($page_title) ?></h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
            <div class="ms-md-auto pe-md-3 d-flex align-items-center">
            </div>
            <ul class="navbar-nav  justify-content-end">
                <li class="nav-item dropdown ps-2 d-flex align-items-center">
                    <a href="#" class="nav-link text-body p-0" id="userDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <img src="../assets/img/team-2.jpg" class="avatar avatar-sm" alt="avatar" />
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/profile">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>