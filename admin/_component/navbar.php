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

// Ambil data user berdasarkan role
$user_name = '';
$user_profile = '../assets/img/team-2.jpg'; // Default image

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'Administrator':
            $user_name = $_SESSION['adm_nama'] ?? 'Administrator';
            $user_profile = !empty($_SESSION['adm_profile']) ? '../assets/img/profile/' . $_SESSION['adm_profile'] : '../assets/img/team-2.jpg';
            break;
        case 'Pembina':
            $user_name = $_SESSION['pembina_nama'] ?? 'Pembina';
            $user_profile = !empty($_SESSION['pembina_profile']) ? '../assets/img/profile/' . $_SESSION['pembina_profile'] : '../assets/img/team-2.jpg';
            break;
        case 'Siswa':
            $user_name = $_SESSION['siswa_nama'] ?? 'Siswa';
            $user_profile = !empty($_SESSION['siswa_profile']) ? '../assets/img/profile/' . $_SESSION['siswa_profile'] : '../assets/img/team-2.jpg';
            break;
        case 'Wakil':
            $user_name = $_SESSION['wakilkepalasekolah_nama'] ?? 'Wakil Kepala Sekolah';
            $user_profile = !empty($_SESSION['wakilkepalasekolah_profile']) ? '../assets/img/profile/' . $_SESSION['wakilkepalasekolah_profile'] : '../assets/img/team-2.jpg';
            break;
    }
}
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
                        <?php
                        if ($user_profile === '') { ?>
                            <img src="../assets/img/team-2.jpg" class="avatar avatar-sm" alt="avatar" />
                            <?php
                        } else { ?>
                            <img src="<?= htmlspecialchars($user_profile) ?>" class="avatar avatar-sm" alt="avatar" />
                            <?php
                        }
                        ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                data-bs-target="#profileModal">Profile</a></li>
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

<!-- Modal Edit Profile -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="profileForm" enctype="multipart/form-data" method="POST" action="update_profile.php">
                <div class="modal-body">
                    <!-- Profile Image Section -->
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <img src="<?= htmlspecialchars($user_profile) ?>" class="rounded-circle shadow-sm"
                                id="profilePreview" alt="Profile Picture"
                                style="width: 120px; height: 120px; object-fit: cover;">
                            <label for="profileImage"
                                class="position-absolute bottom-0 end-0 bg-dark text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 35px; height: 35px; cursor: pointer;">
                                <i class="fas fa-camera" style="font-size: 14px;"></i>
                            </label>
                            <input type="file" id="profileImage" name="profile_image" accept="image/*" class="d-none"
                                onchange="previewImage(this)">
                        </div>
                        <small class="text-muted d-block mt-2">Click camera icon to change photo</small>
                        <small class="text-muted">Max size: 2MB (JPG, PNG, GIF)</small>
                    </div>

                    <!-- Form Fields -->
                    <div class="mb-3">
                        <label for="profileName" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="profileName" name="nama"
                            value="<?= htmlspecialchars($user_name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="profileUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="profileUsername" name="username"
                            value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Password Lama</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="currentPassword" name="current_password"
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePassword('currentPassword')">
                                <i class="fas fa-eye" id="currentPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Password Baru</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" name="new_password"
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePassword('newPassword')">
                                <i class="fas fa-eye" id="newPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password"
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Password harus sama dengan password baru</small>
                    </div>

                    <!-- Hidden field untuk role -->
                    <input type="hidden" name="role" value="<?= htmlspecialchars($_SESSION['role'] ?? '') ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Function untuk preview image
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Validasi ukuran file (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 2MB.');
                input.value = '';
                return;
            }

            // Validasi tipe file
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Tipe file tidak didukung! Hanya JPG, PNG, dan GIF yang diperbolehkan.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('profilePreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }

    // Function untuk toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + 'Icon');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Validasi form sebelum submit
    document.getElementById('profileForm').addEventListener('submit', function (e) {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const currentPassword = document.getElementById('currentPassword').value;

        // Jika ingin mengubah password, validasi
        if (newPassword || confirmPassword || currentPassword) {
            if (!currentPassword) {
                e.preventDefault();
                alert('Password lama harus diisi jika ingin mengubah password!');
                return;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password baru dan konfirmasi password tidak cocok!');
                return;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password baru minimal 6 karakter!');
                return;
            }
        }
    });
</script>