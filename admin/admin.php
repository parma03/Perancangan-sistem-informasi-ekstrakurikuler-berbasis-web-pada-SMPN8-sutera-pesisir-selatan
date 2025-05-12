<?php
session_start();
include '../db/koneksi.php';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Pembina') {
        header("Location: ../pembina/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Siswa') {
        header("Location: ../siswa/index.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $role = "Administrator";
    $username = $_POST['username'];
    $password = $_POST['password'];
    $random_name = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] != UPLOAD_ERR_NO_FILE) {
        $file_name = $_FILES['file']['name'];
        $file_temp = $_FILES['file']['tmp_name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $random_name = uniqid() . '.' . $file_ext;
        $file_path = "../assets/img/profile/" . $random_name;

        if (!move_uploaded_file($file_temp, $file_path)) {
            $_SESSION['notification'] = "Gagal mengunggah foto profil.";
            $_SESSION['alert'] = "alert-danger";
            header("Location: admin.php");
            exit();
        }
    }

    $query_cek = "SELECT COUNT(*) AS count FROM tb_user WHERE username = ?";
    $stmt_cek = $conn->prepare($query_cek);
    $stmt_cek->bind_param("s", $username);
    $stmt_cek->execute();
    $result = $stmt_cek->get_result();
    $data = $result->fetch_assoc();
    $stmt_cek->close();

    if ($data['count'] > 0) {
        if ($random_name && file_exists("../assets/img/profile/" . $random_name)) {
            unlink("../assets/img/profile/" . $random_name);
        }
        $_SESSION['notification'] = "Username sudah terdaftar.";
        $_SESSION['alert'] = "alert-danger";
        header("Location: admin.php");
        exit();
    }

    $query_user = "INSERT INTO tb_user (username, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query_user);
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;

        $query_admin = "INSERT INTO tb_admin (id_user, adm_nama, adm_profile) VALUES (?, ?, ?)";
        $stmt_admin = $conn->prepare($query_admin);
        $stmt_admin->bind_param("iss", $last_id, $nama, $random_name);

        if ($stmt_admin->execute()) {
            $stmt_admin->close();
            $stmt->close();
            $_SESSION['notification'] = "Data Admin berhasil ditambah.";
            $_SESSION['alert'] = "alert-success";
            header("Location: admin.php");
            exit();
        } else {
            $conn->query("DELETE FROM tb_user WHERE id_user = $last_id");
            if ($random_name && file_exists("../assets/img/profile/" . $random_name)) {
                unlink("../assets/img/profile/" . $random_name);
            }
            $stmt_admin->close();
            $stmt->close();
            $_SESSION['notification'] = "Gagal menambah data admin.";
            $_SESSION['alert'] = "alert-danger";
            header("Location: admin.php");
            exit();
        }
    } else {
        if ($random_name && file_exists("../assets/img/profile/" . $random_name)) {
            unlink("../assets/img/profile/" . $random_name);
        }
        $stmt->close();
        $_SESSION['notification'] = "Gagal menambah user.";
        $_SESSION['alert'] = "alert-danger";
        header("Location: admin.php");
        exit();
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id_user = $_POST['id_user'];
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $random_name = null;
    $delete_old_photo = false;

    $query_existing = "SELECT adm_profile, username AS current_username FROM tb_admin JOIN tb_user ON tb_admin.id_user = tb_user.id_user WHERE tb_admin.id_user = ?";
    $stmt_existing = $conn->prepare($query_existing);
    $stmt_existing->bind_param("i", $id_user);
    $stmt_existing->execute();
    $result_existing = $stmt_existing->get_result();
    $existing_data = $result_existing->fetch_assoc();
    $existing_profile = $existing_data['adm_profile'];
    $current_username = $existing_data['current_username'];
    $stmt_existing->close();

    if (isset($_FILES['file']) && $_FILES['file']['error'] != UPLOAD_ERR_NO_FILE) {
        $file_name = $_FILES['file']['name'];
        $file_temp = $_FILES['file']['tmp_name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $random_name = uniqid() . '.' . $file_ext;
        $file_path = "../assets/img/profile/" . $random_name;

        if (!move_uploaded_file($file_temp, $file_path)) {
            $_SESSION['notification'] = "Gagal mengunggah foto profil.";
            $_SESSION['alert'] = "alert-danger";
            header("Location: admin.php");
            exit();
        }

        $delete_old_photo = true;
    }

    $query_duplicate = "SELECT COUNT(*) AS count FROM tb_user WHERE username = ? AND id_user != ?";
    $stmt_duplicate = $conn->prepare($query_duplicate);
    $stmt_duplicate->bind_param("si", $username, $id_user);
    $stmt_duplicate->execute();
    $result_duplicate = $stmt_duplicate->get_result();
    $duplicate_count = $result_duplicate->fetch_assoc()['count'];
    $stmt_duplicate->close();

    if ($duplicate_count > 0) {
        if ($random_name && file_exists("../assets/img/profile/" . $random_name)) {
            unlink("../assets/img/profile/" . $random_name);
        }
        $_SESSION['notification'] = "Username sudah terdaftar.";
        $_SESSION['alert'] = "alert-danger";
        header("Location: admin.php");
        exit();
    }

    $query_user = "UPDATE tb_user SET username = ?, password = ? WHERE id_user = ?";
    $stmt = $conn->prepare($query_user);
    $stmt->bind_param("ssi", $username, $password, $id_user);

    if ($stmt->execute()) {
        $photo_to_save = $random_name ?? $existing_profile;

        $query_admin = "UPDATE tb_admin SET adm_nama = ?, adm_profile = ? WHERE id_user = ?";
        $stmt_admin = $conn->prepare($query_admin);
        $stmt_admin->bind_param("ssi", $nama, $photo_to_save, $id_user);

        if ($stmt_admin->execute()) {
            if ($delete_old_photo && $existing_profile && file_exists("../assets/img/profile/" . $existing_profile)) {
                unlink("../assets/img/profile/" . $existing_profile);
            }

            $stmt_admin->close();
            $stmt->close();
            $_SESSION['notification'] = "Data Admin berhasil diupdate.";
            $_SESSION['alert'] = "alert-success";
            header("Location: admin.php");
            exit();
        } else {
            $stmt_user_revert = $conn->prepare("UPDATE tb_user SET username = ? WHERE id_user = ?");
            $stmt_user_revert->bind_param("si", $current_username, $id_user);
            $stmt_user_revert->execute();
            $stmt_user_revert->close();

            if ($random_name && file_exists("../assets/img/profile/" . $random_name)) {
                unlink("../assets/img/profile/" . $random_name);
            }

            $stmt_admin->close();
            $stmt->close();
            $_SESSION['notification'] = "Gagal update data admin.";
            $_SESSION['alert'] = "alert-danger";
            header("Location: admin.php");
            exit();
        }
    } else {
        if ($random_name && file_exists("../assets/img/profile/" . $random_name)) {
            unlink("../assets/img/profile/" . $random_name);
        }
        $stmt->close();
        $_SESSION['notification'] = "Gagal menambah user.";
        $_SESSION['alert'] = "alert-danger";
        header("Location: admin.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id_user = $_POST['id_user'];

    $query_select = "SELECT adm_profile FROM tb_admin WHERE id_user = ?";
    $stmt_select = $conn->prepare($query_select);
    $stmt_select->bind_param("i", $id_user);
    $stmt_select->execute();
    $result = $stmt_select->get_result()->fetch_assoc();
    $profile = @$result['adm_profile'];

    if ($profile && file_exists("../assets/img/profile/" . $profile)) {
        unlink("../assets/img/profile/" . $profile);
    }

    $query_admin = "DELETE FROM tb_user WHERE id_user = ?";
    $stmt = $conn->prepare($query_admin);
    $stmt->bind_param("i", $id_user);

    if ($stmt->execute()) {
        $_SESSION['notification'] = "Data Admin berhasil dihapus.";
        $_SESSION['alert'] = "alert-success";
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $stmt_cek->close();
    $conn->close();
}

$query = "SELECT * FROM tb_user INNER JOIN tb_admin ON tb_user.id_user = tb_admin.id_user";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sistem Informasi Ekstrakurikuler SMPN 8 Sutera Pesisir Selatan</title>
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Sans:300,400,500,600,700,800|PT+Mono:300,400,500,600,700"
        rel="stylesheet" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <link href="../assets/css/datatables.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/349ee9c857.js" crossorigin="anonymous"></script>
    <link id="pagestyle" href="../assets/css/corporate-ui-dashboard.css?v=1.0.0" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" />
</head>

<body class="g-sidenav-show bg-gray-100">
    <!-- Notification Container -->
    <div class="notification-container">
        <?php if (isset($_SESSION['notification']) && isset($_SESSION['alert'])): ?>
            <div class="alert fade alert-dismissible text-left <?php echo $_SESSION['alert']; ?> show">
                <button type="button" class="close" onclick="this.parentElement.remove()">
                    <span aria-hidden="true">
                        <?php if ($_SESSION['alert'] == 'alert-success'): ?>
                            <i class="fa fa-times greencross"></i>
                        <?php elseif ($_SESSION['alert'] == 'alert-info'): ?>
                            <i class="fa fa-times blue-cross"></i>
                        <?php elseif ($_SESSION['alert'] == 'alert-warning'): ?>
                            <i class="fa fa-times warning"></i>
                        <?php elseif ($_SESSION['alert'] == 'alert-danger'): ?>
                            <i class="fa fa-times danger"></i>
                        <?php elseif ($_SESSION['alert'] == 'alert-primary'): ?>
                            <i class="fa fa-times alertprimary"></i>
                        <?php endif; ?>
                    </span>
                    <span class="sr-only">Close</span>
                </button>
                <?php if ($_SESSION['alert'] == 'alert-success'): ?>
                    <i class="start-icon far fa-check-circle faa-tada animated"></i>
                <?php elseif ($_SESSION['alert'] == 'alert-info'): ?>
                    <i class="start-icon fa fa-info-circle faa-shake animated"></i>
                <?php elseif ($_SESSION['alert'] == 'alert-warning'): ?>
                    <i class="start-icon fa fa-exclamation-triangle faa-flash animated"></i>
                <?php elseif ($_SESSION['alert'] == 'alert-danger'): ?>
                    <i class="start-icon far fa-times-circle faa-pulse animated"></i>
                <?php elseif ($_SESSION['alert'] == 'alert-primary'): ?>
                    <i class="start-icon fa fa-thumbs-up faa-bounce animated"></i>
                <?php endif; ?>
                <strong class="font-weight-bold">
                    <?php
                    if ($_SESSION['alert'] == 'alert-success')
                        echo "Success!";
                    elseif ($_SESSION['alert'] == 'alert-info')
                        echo "Info!";
                    elseif ($_SESSION['alert'] == 'alert-warning')
                        echo "Warning!";
                    elseif ($_SESSION['alert'] == 'alert-danger')
                        echo "Error!";
                    elseif ($_SESSION['alert'] == 'alert-primary')
                        echo "Notice!";
                    ?>
                </strong>
                <?php echo $_SESSION['notification']; ?>
            </div>
            <?php
            // Clear the session variables after displaying
            unset($_SESSION['notification']);
            unset($_SESSION['alert']);
            ?>
        <?php endif; ?>
    </div>
    <?php include '_component/sidebar.php'; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <?php include '_component/navbar.php'; ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4 px-5">
            <div class="row my-4">
                <div class="col-lg col-md-6">
                    <div class="card border-0 shadow-xs mb-4">
                        <div class="card-header border-bottom-0 pb-0">
                            <div class="d-sm-flex align-items-center mb-3">
                                <div>
                                    <h6 class="font-weight-semibold text-lg mb-0">Data Admin</h6>
                                    <p class="text-sm text-muted mb-sm-0">
                                        Keseluruhan data User dengan Role Admin
                                    </p>
                                </div>
                                <div class="ms-auto d-flex">
                                    <button type="button"
                                        class="btn btn-sm btn-download btn-icon d-flex align-items-center mb-0"
                                        data-bs-toggle="modal" data-bs-target="#modalAddAdmin">
                                        <span class="btn-inner--icon">
                                            <i class="fas fa-user-plus me-2"></i>
                                        </span>
                                        <span class="btn-inner--text">Add Admin</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body px-0 py-0">
                            <div class="table-responsive p-0">
                                <table id="datatable-transaksi"
                                    class="table transaction-table align-items-center mb-0 display nowrap"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-white text-sm font-weight-semibold"
                                                style="width: 1%; white-space: nowrap;">
                                                #
                                            </th>
                                            <th class="text-white text-sm font-weight-semibold">
                                                Username
                                            </th>
                                            <th class="text-white text-sm font-weight-semibold">
                                                Nama
                                            </th>
                                            <th class="text-center text-white text-sm font-weight-semibold">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        while ($data = mysqli_fetch_array($result)) { ?>
                                            <tr>
                                                <td>
                                                    <p class="transaction-amount mb-0"><?php echo $no++ ?></p>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center px-2 py-1">
                                                        <?php if ($data["adm_profile"] === NULL) { ?>
                                                            <div class="trans-logo bg-gray-100 me-3">
                                                                <img src="../assets/img/team-2.jpg" class="w-75" alt="xd">
                                                            </div>
                                                        <?php } else { ?>
                                                            <div class="trans-logo bg-gray-100 me-3">
                                                                <img src="../assets/img/profile/<?php echo $data["adm_profile"]; ?>"
                                                                    class="w-75" alt="xd">
                                                            </div>
                                                        <?php } ?>
                                                        <div>
                                                            <h6 class="transaction-name mb-0">
                                                                <?php echo $data["username"] ?>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="transaction-amount mb-0"><?php echo $data["adm_nama"] ?></p>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <button type="button" class="btn-action" data-bs-toggle="modal"
                                                        data-bs-title="View Data"
                                                        data-bs-target="#viewModal<?php echo $data["id_user"]; ?>">
                                                        <i class="fas fa-eye"></i>
                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="Edit Data"
                                                            data-bs-target="#editModal<?php echo $data["id_user"]; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="Delete Data"
                                                            data-bs-target="#deleteModal<?php echo $data["id_user"]; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                </td>
                                            </tr>

                                            <!-- Modal View Admin - Enhanced Version -->
                                            <div class="modal fade" id="viewModal<?php echo $data["id_user"]; ?>"
                                                tabindex="-1"
                                                aria-labelledby="viewModalLabel<?php echo $data["id_user"]; ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-gradient-primary text-white">
                                                            <h5 class="modal-title"
                                                                id="viewModalLabel<?php echo $data["id_user"]; ?>">
                                                                <i class="fas fa-user-circle me-2"></i>Detail Admin
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-4 text-center mb-3">
                                                                    <div class="position-relative">
                                                                        <?php if ($data["adm_profile"] === NULL) { ?>
                                                                            <img src="../assets/img/team-2.jpg"
                                                                                class="img-fluid rounded-circle shadow-lg"
                                                                                style="max-width: 250px; height: 250px; object-fit: cover;"
                                                                                alt="Profile Picture">
                                                                        <?php } else { ?>
                                                                            <img src="../assets/img/profile/<?php echo $data["adm_profile"]; ?>"
                                                                                class="img-fluid rounded-circle shadow-lg"
                                                                                style="max-width: 250px; height: 250px; object-fit: cover;"
                                                                                alt="Profile Picture">
                                                                        <?php } ?>
                                                                        <div
                                                                            class="position-absolute bottom-0 end-0 mb-3 me-3 bg-white rounded-circle p-2 shadow">
                                                                            <i class="fas fa-user-shield text-primary"
                                                                                style="font-size: 24px;"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <div class="card border-0 shadow-sm">
                                                                        <div class="card-body">
                                                                            <h4 class="card-title mb-4 text-primary">
                                                                                <i
                                                                                    class="fas fa-id-card me-2"></i>Administrator
                                                                                Information
                                                                            </h4>
                                                                            <div class="row mb-2">
                                                                                <div class="col-4 text-muted">
                                                                                    <strong><i
                                                                                            class="fas fa-user me-2"></i>Full
                                                                                        Name</strong>
                                                                                </div>
                                                                                <div class="col-8">
                                                                                    <?php echo htmlspecialchars($data["adm_nama"]); ?>
                                                                                </div>
                                                                            </div>
                                                                            <hr class="my-2">
                                                                            <div class="row mb-2">
                                                                                <div class="col-4 text-muted">
                                                                                    <strong><i
                                                                                            class="fas fa-at me-2"></i>Username</strong>
                                                                                </div>
                                                                                <div class="col-8">
                                                                                    <?php echo htmlspecialchars($data["username"]); ?>
                                                                                </div>
                                                                            </div>
                                                                            <hr class="my-2">
                                                                            <div class="row mb-2">
                                                                                <div class="col-4 text-muted">
                                                                                    <strong><i
                                                                                            class="fas fa-unlock-alt me-2"></i>Role</strong>
                                                                                </div>
                                                                                <div class="col-8">
                                                                                    <span class="badge bg-gradient-primary">
                                                                                        <?php echo htmlspecialchars($data["role"]); ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-2"></i>Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal Edit Admin -->
                                            <div class="modal fade" id="editModal<?php echo $data["id_user"]; ?>"
                                                tabindex="-1"
                                                aria-labelledby="modalEditAdminLabel<?php echo $data["id_user"]; ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="modalEditAdminLabel<?php echo $data["id_user"]; ?>">Edit
                                                                Data Admin</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <center>
                                                            <div class="col-md-3">
                                                                <?php if ($data["adm_profile"] === NULL) { ?>
                                                                    <img class="card-img card-img-center align-items-center"
                                                                        src="../assets/img/team-2.jpg" />
                                                                <?php } else { ?>
                                                                    <img class="card-img card-img-center align-items-center"
                                                                        src="../assets/img/profile/<?php echo $data["adm_profile"]; ?>" />
                                                                <?php } ?>
                                                            </div>
                                                        </center>
                                                        <form action="admin.php" method="POST"
                                                            enctype="multipart/form-data">.
                                                            <input type="hidden" name="id_user"
                                                                value="<?php echo $data["id_user"] ?>" required>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="username"
                                                                        class="form-label">Username</label>
                                                                    <input type="text" class="form-control" id="username"
                                                                        name="username"
                                                                        value="<?php echo $data["username"] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="nama" class="form-label">Nama
                                                                        Lengkap</label>
                                                                    <input type="text" class="form-control" id="nama"
                                                                        name="nama" value="<?php echo $data["adm_nama"] ?>"
                                                                        required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="password" class="form-label">Kata
                                                                        Sandi</label>
                                                                    <div class="input-group">
                                                                        <input type="password" class="form-control"
                                                                            id="password" name="password"
                                                                            value="<?php echo $data["password"] ?>"
                                                                            required>
                                                                        <span class="input-group-text" id="togglePassword"
                                                                            style="cursor: pointer;">
                                                                            <i class="fas fa-eye"></i>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="file" class="form-label">Foto Profil
                                                                        (Opsional)</label>
                                                                    <input type="file" class="form-control" id="file"
                                                                        name="file" accept="image/*">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-primary"
                                                                    name="edit">Simpan</button>
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Batal</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete Modal-->
                                            <div class="modal fade" id="deleteModal<?php echo $data["id_user"]; ?>"
                                                tabindex="-1"
                                                aria-labelledby="deleteModalLabel<?php echo $data["id_user"]; ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="deleteModalLabel<?php echo $data["id_user"]; ?>">
                                                                Delete Data Admin
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Yakin Menghapus Data ini,<b>
                                                                    <?php echo $data['adm_nama']; ?></b> ?</p>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="admin.php" method="post">
                                                                <input type="hidden" name="id_user"
                                                                    value="<?php echo $data["id_user"]; ?>" />
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary"
                                                                    name="delete">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '_component/footer.php'; ?>
        </div>
    </main>

    <!-- Modal Tambah Admin -->
    <div class="modal fade" id="modalAddAdmin" tabindex="-1" aria-labelledby="modalAddAdminLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddAdminLabel">Tambah Data Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label">Foto Profil (Opsional)</label>
                            <input type="file" class="form-control" id="file" name="file" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" name="tambah">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="../assets/js/notification.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#datatable-transaksi').DataTable({
                responsive: true,
                lengthMenu: [10, 25, 50],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search Data Admin...",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    },
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    lengthMenu: "Show _MENU_ entries"
                },
                columnDefs: [{
                    orderable: false,
                    targets: 3
                } // Nonaktifkan sort di kolom "Action"
                ],
                initComplete: function () {
                    // Initialize tooltips after DataTables loads
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                        '[data-bs-toggle="tooltip"]'))
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                }
            });

            // Enable Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>

    <!--   Core JS Files   -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <script>
        document.getElementById("togglePassword").addEventListener("click", function () {
            const passwordInput = document.getElementById("password");
            const icon = this.querySelector("i");

            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";

            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    </script>
    <script>
        function handleProfileImagePreview(inputElement, previewImageElement) {
            if (inputElement.files && inputElement.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    previewImageElement.src = e.target.result;
                };

                reader.readAsDataURL(inputElement.files[0]);
            } else {
                const originalSrc = previewImageElement.getAttribute('data-original-src');
                if (originalSrc) {
                    previewImageElement.src = originalSrc;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const fileInputs = document.querySelectorAll('input[name="file"]');

            fileInputs.forEach(function (fileInput) {
                const modal = fileInput.closest('.modal');
                const previewImage = modal.querySelector('.card-img-center');

                if (previewImage) {
                    previewImage.setAttribute('data-original-src', previewImage.src);

                    fileInput.addEventListener('change', function () {
                        handleProfileImagePreview(this, previewImage);
                    });
                }
            });
        });
    </script>

</body>

</html>