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
    } else if ($_SESSION['role'] === 'Wakil') {
        header("Location: ../wakil/index.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    // Get extracurricular data
    $nama_ekstrakulikuler = $_POST['nama_ekstrakulikuler'];
    $deskripsi_ekstrakulikuler = $_POST['deskripsi_ekstrakulikuler'];
    $pembina_id = $_POST['pembina_id'];
    $periode = $_POST['periode'];
    $status = "Masih Berlangsung"; // Default status

    // Insert extracurricular first
    $query = "INSERT INTO tb_ekstrakulikuler (nama_ekstrakulikuler, deskripsi_ekstrakulikuler, pembina_id, periode, status) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiss", $nama_ekstrakulikuler, $deskripsi_ekstrakulikuler, $pembina_id, $periode, $status);

    if ($stmt->execute()) {
        $id_ekstrakulikuler = $conn->insert_id; // Get the ID of newly inserted extracurricular

        // Process schedule data
        $hari = $_POST['hari'];
        $duty_start = $_POST['duty_start'];
        $duty_end = $_POST['duty_end'];

        // Insert schedules
        $query_jadwal = "INSERT INTO tb_jadwal (id_ekstrakulikuler, hari, duty_start, duty_end) 
                         VALUES (?, ?, ?, ?)";
        $stmt_jadwal = $conn->prepare($query_jadwal);

        $success = true;
        for ($i = 0; $i < count($hari); $i++) {
            $stmt_jadwal->bind_param("isss", $id_ekstrakulikuler, $hari[$i], $duty_start[$i], $duty_end[$i]);
            if (!$stmt_jadwal->execute()) {
                $success = false;
                break;
            }
        }

        if ($success) {
            $_SESSION['notification'] = "Data Ekstrakulikuler berhasil ditambahkan.";
            $_SESSION['alert'] = "alert-success";
        } else {
            $_SESSION['notification'] = "Gagal menambahkan jadwal ekstrakulikuler.";
            $_SESSION['alert'] = "alert-danger";
        }

        $stmt_jadwal->close();
    } else {
        $_SESSION['notification'] = "Gagal menambahkan data ekstrakulikuler.";
        $_SESSION['alert'] = "alert-danger";
    }

    $stmt->close();
    header("Location: ekstrakulikuler.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    file_put_contents("debug_post.log", print_r($_POST, return: true));
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'];
    $nama_ekstrakulikuler = $_POST['nama_ekstrakulikuler'];
    $deskripsi_ekstrakulikuler = $_POST['deskripsi_ekstrakulikuler'];
    $pembina_id = $_POST['pembina_id'];
    $periode = $_POST['periode'];
    $status = $_POST['status'];

    $debug_data = [
        'form_data' => $_POST,
        'has_hari_new' => isset($_POST['hari_new']),
        'hari_new_count' => isset($_POST['hari_new']) ? count($_POST['hari_new']) : 0,
        'all_keys' => array_keys($_POST)
    ];
    file_put_contents("debug_jadwal.log", print_r($debug_data, return: true));

    // Update extracurricular data
    $query = "UPDATE tb_ekstrakulikuler SET 
              nama_ekstrakulikuler = ?, 
              deskripsi_ekstrakulikuler = ?, 
              pembina_id = ?, 
              periode = ?, 
              status = ? 
              WHERE id_ekstrakulikuler = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssissi", $nama_ekstrakulikuler, $deskripsi_ekstrakulikuler, $pembina_id, $periode, $status, $id_ekstrakulikuler);
    $success = $stmt->execute();

    // Update success flag to track all database operations
    $all_operations_success = $success;

    if ($success) {
        // Handle existing jadwal updates
        if (isset($_POST['jadwal_id']) && is_array($_POST['jadwal_id'])) {
            $jadwal_ids = $_POST['jadwal_id'];
            $hari_edit = $_POST['hari_edit'];
            $duty_start_edit = $_POST['duty_start_edit'];
            $duty_end_edit = $_POST['duty_end_edit'];

            $query_update_jadwal = "UPDATE tb_jadwal SET 
                                   hari = ?, duty_start = ?, duty_end = ? 
                                   WHERE id_jadwal = ?";
            $stmt_update_jadwal = $conn->prepare($query_update_jadwal);

            for ($i = 0; $i < count($jadwal_ids); $i++) {
                $stmt_update_jadwal->bind_param("sssi", $hari_edit[$i], $duty_start_edit[$i], $duty_end_edit[$i], $jadwal_ids[$i]);
                $result = $stmt_update_jadwal->execute();
                $all_operations_success = $all_operations_success && $result;
            }

            $stmt_update_jadwal->close();
        }

        // Handle new jadwal insertions
        if (isset($_POST['hari_new']) && is_array($_POST['hari_new'])) {
            $hari_new = $_POST['hari_new'];
            $duty_start_new = $_POST['duty_start_new'];
            $duty_end_new = $_POST['duty_end_new'];

            // Debug for new jadwal data
            file_put_contents("debug_new_jadwal.log", print_r([
                'hari_new' => $hari_new,
                'duty_start_new' => $duty_start_new,
                'duty_end_new' => $duty_end_new
            ], return: true));

            $query_insert_jadwal = "INSERT INTO tb_jadwal (id_ekstrakulikuler, hari, duty_start, duty_end) 
                                   VALUES (?, ?, ?, ?)";
            $stmt_insert_jadwal = $conn->prepare($query_insert_jadwal);

            for ($i = 0; $i < count($hari_new); $i++) {
                if (!empty($hari_new[$i]) && $hari_new[$i] !== 'Pilih Hari') {
                    $stmt_insert_jadwal->bind_param("isss", $id_ekstrakulikuler, $hari_new[$i], $duty_start_new[$i], $duty_end_new[$i]);
                    $result = $stmt_insert_jadwal->execute();
                    $all_operations_success = $all_operations_success && $result;

                    // Debug each insertion
                    file_put_contents(
                        "debug_insert_jadwal.log",
                        "Inserting jadwal: " .
                        "ekstra_id=$id_ekstrakulikuler, " .
                        "hari={$hari_new[$i]}, " .
                        "start={$duty_start_new[$i]}, " .
                        "end={$duty_end_new[$i]}, " .
                        "result=" . ($result ? "SUCCESS" : "FAIL") . "\n",
                        FILE_APPEND
                    );
                }
            }

            $stmt_insert_jadwal->close();
        } else {
            // Debug if hari_new array is not present
            file_put_contents("debug_missing_hari_new.log", "hari_new not found in POST data or not an array\n");
        }

        // Handle jadwal deletions
        if (isset($_POST['delete_jadwal_ids']) && !empty($_POST['delete_jadwal_ids'])) {
            $delete_jadwal_ids = explode(',', $_POST['delete_jadwal_ids']);

            $query_delete_jadwal = "DELETE FROM tb_jadwal WHERE id_jadwal = ?";
            $stmt_delete_jadwal = $conn->prepare($query_delete_jadwal);

            foreach ($delete_jadwal_ids as $delete_id) {
                if (!empty($delete_id)) {
                    $stmt_delete_jadwal->bind_param("i", $delete_id);
                    $result = $stmt_delete_jadwal->execute();
                    $all_operations_success = $all_operations_success && $result;
                }
            }

            $stmt_delete_jadwal->close();
        }

        if ($all_operations_success) {
            $_SESSION['notification'] = "Data Ekstrakulikuler berhasil diperbarui.";
            $_SESSION['alert'] = "alert-success";
        } else {
            $_SESSION['notification'] = "Data Ekstrakulikuler diperbarui tetapi ada masalah dengan data jadwal.";
            $_SESSION['alert'] = "alert-warning";
        }
    } else {
        $_SESSION['notification'] = "Gagal memperbarui data ekstrakulikuler.";
        $_SESSION['alert'] = "alert-danger";
    }

    $stmt->close();
    header("Location: ekstrakulikuler.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'];

    $query = "DELETE FROM tb_ekstrakulikuler WHERE id_ekstrakulikuler = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_ekstrakulikuler);

    if ($stmt->execute()) {
        $_SESSION['notification'] = "Data Ekstrakulikuler berhasil dihapus.";
        $_SESSION['alert'] = "alert-success";
        header("Location: ekstrakulikuler.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $stmt_cek->close();
    $conn->close();
}

$query = "SELECT * FROM tb_ekstrakulikuler
            LEFT JOIN tb_pembina ON tb_ekstrakulikuler.pembina_id = tb_pembina.pembina_id";
$result = $conn->query($query);

$query_pembina = "SELECT pembina_id, pembina_nama FROM tb_pembina";
$result_pembina_tambah = $conn->query($query_pembina);

$result_edit_pembina = $conn->query($query_pembina);
$pembina = [];
while ($pembina = mysqli_fetch_array($result_edit_pembina)) {
    $pembinas[] = $pembina;
}

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
                                    <h6 class="font-weight-semibold text-lg mb-0">Data Ekstrakurikuler</h6>
                                    <p class="text-sm text-muted mb-sm-0">
                                        Keseluruhan data Ekstrakurikuler
                                    </p>
                                </div>
                                <div class="ms-auto d-flex">
                                    <button type="button"
                                        class="btn btn-sm btn-download btn-icon d-flex align-items-center mb-0"
                                        data-bs-toggle="modal" data-bs-target="#modalAddAdmin">
                                        <span class="btn-inner--icon">
                                            <i class="fas fa-user-plus me-2"></i>
                                        </span>
                                        <span class="btn-inner--text">Add Ekstrakurikuler</span>
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
                                                Nama Ekstrakulikuler
                                            </th>
                                            <th class="text-white text-sm font-weight-semibold">
                                                Deskripsi Ekstrakulikuler
                                            </th>
                                            <th class="text-white text-sm font-weight-semibold">
                                                Pembina
                                            </th>
                                            <th class="text-white text-sm font-weight-semibold">
                                                Periode
                                            </th>
                                            <th class="text-white text-sm font-weight-semibold">
                                                Status
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
                                                        <div>
                                                            <h6 class="transaction-name mb-0">
                                                                <?php echo $data["nama_ekstrakulikuler"] ?>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="transaction-amount mb-0">
                                                        <?php echo $data["deskripsi_ekstrakulikuler"] ?>
                                                    </p>
                                                </td>
                                                <td>
                                                    <?php if ($data["pembina_nama"] != NULL) { ?>
                                                        <p class="transaction-amount mb-0">
                                                            <?php echo $data["pembina_nama"] ?>
                                                        </p>
                                                    <?php } else { ?>
                                                        <span class="badge bg-danger text-dark">
                                                            Tidak Ada Pembina
                                                        </span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <p class="transaction-amount mb-0">
                                                        <?php echo $data["periode"] ?>
                                                    </p>
                                                </td>
                                                <td>
                                                    <?php if ($data["status"] === "Masih Berlangsung") { ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <?php echo $data["status"] ?>
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-success text-dark">
                                                            <?php echo $data["status"] ?>
                                                        </span>
                                                    <?php } ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <button type="button" class="btn-action" data-bs-toggle="modal"
                                                        data-bs-title="View Data"
                                                        data-bs-target="#viewModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                        <i class="fas fa-eye"></i>
                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="Edit Data"
                                                            data-bs-target="#editModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="Delete Data"
                                                            data-bs-target="#deleteModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                </td>
                                            </tr>

                                            <!-- Modal View -->
                                            <div class="modal fade" id="viewModal<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                tabindex="-1"
                                                aria-labelledby="viewModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-gradient-primary text-white">
                                                            <h5 class="modal-title"
                                                                id="viewModalLabel<?php echo $data["id_user"]; ?>">
                                                                <i class="fas fa-user-circle me-2"></i>Detail
                                                                Ekstrakulikuler <?php echo $data["nama_ekstrakulikuler"] ?>
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="list-group" id="list-tab" role="tablist">
                                                                        <a class="list-group-item list-group-item-action active"
                                                                            id="list-home-list" data-bs-toggle="list"
                                                                            href="#list-home" role="tab"
                                                                            aria-controls="list-home">Home</a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-profile-list" data-bs-toggle="list"
                                                                            href="#list-profile" role="tab"
                                                                            aria-controls="list-profile">Profile</a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-messages-list" data-bs-toggle="list"
                                                                            href="#list-messages" role="tab"
                                                                            aria-controls="list-messages">Messages</a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-settings-list" data-bs-toggle="list"
                                                                            href="#list-settings" role="tab"
                                                                            aria-controls="list-settings">Settings</a>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="tab-content" id="nav-tabContent">
                                                                        <div class="tab-pane fade show active"
                                                                            id="list-home" role="tabpanel"
                                                                            aria-labelledby="list-home-list">...</div>
                                                                        <div class="tab-pane fade" id="list-profile"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-profile-list">...</div>
                                                                        <div class="tab-pane fade" id="list-messages"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-messages-list">...</div>
                                                                        <div class="tab-pane fade" id="list-settings"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-settings-list">...</div>
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

                                            <!-- Modal Edit -->
                                            <div class="modal fade" id="editModal<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                tabindex="-1"
                                                aria-labelledby="modalEditAdminLabel<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="modalEditAdminLabel<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                Edit Data Ekstrakulikuler</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <form action="ekstrakulikuler.php" method="POST"
                                                            enctype="multipart/form-data" class="form-edit">
                                                            <div class="modal-body">
                                                                <!-- Data Ekstrakurikuler -->
                                                                <input type="hidden" name="id_ekstrakulikuler"
                                                                    value="<?php echo $data["id_ekstrakulikuler"]; ?>">

                                                                <div class="row">
                                                                    <div class="col-md-8 mb-3">
                                                                        <label
                                                                            for="nama_ekstrakulikuler_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            class="form-label">Nama Ekstrakurikuler</label>
                                                                        <input type="text" class="form-control"
                                                                            id="nama_ekstrakulikuler_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            name="nama_ekstrakulikuler"
                                                                            value="<?php echo $data["nama_ekstrakulikuler"]; ?>"
                                                                            required>
                                                                    </div>
                                                                    <div class="col-md-4 mb-3">
                                                                        <label
                                                                            for="periode_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            class="form-label">Periode</label>
                                                                        <input type="number" min="2000" max="2099" step="1"
                                                                            class="form-control"
                                                                            id="periode_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            name="periode"
                                                                            value="<?php echo $data["periode"]; ?>"
                                                                            required>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label
                                                                        for="deskripsi_ekstrakulikuler_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                        class="form-label">Deskripsi Ekstrakurikuler</label>
                                                                    <input type="text" class="form-control"
                                                                        id="deskripsi_ekstrakulikuler_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                        name="deskripsi_ekstrakulikuler"
                                                                        value="<?php echo $data["deskripsi_ekstrakulikuler"]; ?>"
                                                                        required>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label
                                                                        for="pembina_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                        class="form-label">Pembina</label>
                                                                    <select class="form-select"
                                                                        id="pembina_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                        name="pembina_id" required>
                                                                        <option value="" disabled>Pilih Pembina</option>
                                                                        <?php foreach ($pembinas as $pembina) { ?>
                                                                            <option
                                                                                value="<?php echo $pembina['pembina_id']; ?>"
                                                                                <?php echo ($data["pembina_id"] == $pembina['pembina_id']) ? 'selected' : ''; ?>>
                                                                                <?php echo $pembina['pembina_nama']; ?>
                                                                            </option>
                                                                        <?php } ?>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label
                                                                        for="status_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                        class="form-label">Status</label>
                                                                    <select class="form-select"
                                                                        id="status_edit<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                        name="status" required>
                                                                        <option value="Masih Berlangsung" <?php echo ($data["status"] == "Masih Berlangsung") ? 'selected' : ''; ?>>Masih Berlangsung</option>
                                                                        <option value="Selesai" <?php echo ($data["status"] == "Selesai") ? 'selected' : ''; ?>>Selesai</option>
                                                                    </select>
                                                                </div>

                                                                <!-- Jadwal Ekstrakurikuler -->
                                                                <hr>
                                                                <h6>Jadwal Ekstrakurikuler</h6>

                                                                <div
                                                                    id="jadwal-edit-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                    <?php
                                                                    // Fetch existing schedules
                                                                    $query_jadwal = "SELECT * FROM tb_jadwal WHERE id_ekstrakulikuler = ?";
                                                                    $stmt_jadwal = $conn->prepare($query_jadwal);
                                                                    $stmt_jadwal->bind_param("i", $data["id_ekstrakulikuler"]);
                                                                    $stmt_jadwal->execute();
                                                                    $result_jadwal = $stmt_jadwal->get_result();

                                                                    $jadwal_count = 0;
                                                                    while ($jadwal = $result_jadwal->fetch_assoc()) {
                                                                        $jadwal_count++;
                                                                        ?>
                                                                        <div class="jadwal-edit-item mb-3 p-2 border rounded">
                                                                            <div class="row">
                                                                                <input type="hidden" name="jadwal_id[]"
                                                                                    value="<?php echo $jadwal['id_jadwal']; ?>">
                                                                                <div class="col-md-4 mb-2">
                                                                                    <label class="form-label">Hari</label>
                                                                                    <select class="form-select"
                                                                                        name="hari_edit[]" required>
                                                                                        <option disabled>Pilih Hari</option>
                                                                                        <option value="Senin" <?php echo ($jadwal['hari'] == 'Senin') ? 'selected' : ''; ?>>Senin</option>
                                                                                        <option value="Selasa" <?php echo ($jadwal['hari'] == 'Selasa') ? 'selected' : ''; ?>>Selasa</option>
                                                                                        <option value="Rabu" <?php echo ($jadwal['hari'] == 'Rabu') ? 'selected' : ''; ?>>Rabu</option>
                                                                                        <option value="Kamis" <?php echo ($jadwal['hari'] == 'Kamis') ? 'selected' : ''; ?>>Kamis</option>
                                                                                        <option value="Jumat" <?php echo ($jadwal['hari'] == 'Jumat') ? 'selected' : ''; ?>>Jumat</option>
                                                                                        <option value="Sabtu" <?php echo ($jadwal['hari'] == 'Sabtu') ? 'selected' : ''; ?>>Sabtu</option>
                                                                                        <option value="Minggu" <?php echo ($jadwal['hari'] == 'Minggu') ? 'selected' : ''; ?>>Minggu</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Waktu
                                                                                        Mulai</label>
                                                                                    <input type="time" class="form-control"
                                                                                        name="duty_start_edit[]"
                                                                                        value="<?php echo $jadwal['duty_start']; ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Waktu
                                                                                        Selesai</label>
                                                                                    <input type="time" class="form-control"
                                                                                        name="duty_end_edit[]"
                                                                                        value="<?php echo $jadwal['duty_end']; ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div
                                                                                    class="col-md-2 d-flex align-items-end mb-2">
                                                                                    <button type="button"
                                                                                        class="btn btn-danger btn-sm delete-jadwal"
                                                                                        data-jadwal-id="<?php echo $jadwal['id_jadwal']; ?>">
                                                                                        <i class="fas fa-trash"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    $stmt_jadwal->close();

                                                                    // If no schedules exist yet
                                                                    if ($jadwal_count == 0) {
                                                                        echo '<div class="alert alert-info">Belum ada jadwal tersimpan untuk ekstrakulikuler ini.</div>';
                                                                    }
                                                                    ?>
                                                                </div>

                                                                <!-- Container for new schedules -->
                                                                <div
                                                                    id="jadwal-new-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                    <!-- New schedules will be added here via JavaScript -->
                                                                </div>

                                                                <input type="hidden" name="delete_jadwal_ids"
                                                                    id="delete-jadwal-ids-<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                    value="">

                                                                <button type="button"
                                                                    class="btn btn-success btn-sm tambah-jadwal-baru"
                                                                    data-ekstra-id="<?php echo $data['id_ekstrakulikuler']; ?>">
                                                                    <i class="fas fa-plus-circle"></i> Tambah Jadwal Baru
                                                                </button>
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
                                            <div class="modal fade"
                                                id="deleteModal<?php echo $data["id_ekstrakulikuler"]; ?>" tabindex="-1"
                                                aria-labelledby="deleteModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="deleteModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                Delete Data Ekstrakulikuler
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Yakin Menghapus Data ini,<b>
                                                                    <?php echo $data['nama_ekstrakulikuler']; ?></b> ?</p>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="ekstrakulikuler.php" method="post">
                                                                <input type="hidden" name="id_ekstrakulikuler"
                                                                    value="<?php echo $data["id_ekstrakulikuler"]; ?>" />
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


    <!-- Modal Tambah -->
    <div class="modal fade" id="modalAddAdmin" tabindex="-1" aria-labelledby="modalAddAdminLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddAdminLabel">Tambah Data Ekstrakurikuler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="ekstrakulikuler.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Data Ekstrakurikuler -->
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nama_ekstrakulikuler" class="form-label">Nama Ekstrakurikuler</label>
                                <input type="text" class="form-control" id="nama_ekstrakulikuler"
                                    name="nama_ekstrakulikuler" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="periode" class="form-label">Periode</label>
                                <input type="number" min="2000" max="2099" step="1" class="form-control" id="periode"
                                    name="periode" value="2025" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi_ekstrakulikuler" class="form-label">Deskripsi Ekstrakurikuler</label>
                            <input type="text" class="form-control" id="deskripsi_ekstrakulikuler"
                                name="deskripsi_ekstrakulikuler" required>
                        </div>
                        <div class="mb-3">
                            <label for="pembina" class="form-label">Pembina</label>
                            <select class="form-select" id="pembina" name="pembina_id" required>
                                <option value="" selected disabled>Pilih Pembina</option>
                                <?php
                                while ($row = $result_pembina_tambah->fetch_assoc()) {
                                    echo '<option value="' . $row['pembina_id'] . '">' . $row['pembina_nama'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Jadwal Ekstrakurikuler -->
                        <hr>
                        <h6>Jadwal Ekstrakurikuler</h6>
                        <div id="jadwal-container">
                            <div class="jadwal-item mb-3 p-2 border rounded">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Hari</label>
                                        <select class="form-select" aria-label="Pilih Hari" id="hari" name="hari[]"
                                            required>
                                            <option selected disabled>Pilih Hari</option>
                                            <option value="Senin">Senin</option>
                                            <option value="Selasa">Selasa</option>
                                            <option value="Rabu">Rabu</option>
                                            <option value="Kamis">Kamis</option>
                                            <option value="Jumat">Jumat</option>
                                            <option value="Sabtu">Sabtu</option>
                                            <option value="Mingu">Mingu</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Waktu Mulai</label>
                                        <input type="time" class="form-control" name="duty_start[]" required>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Waktu Selesai</label>
                                        <input type="time" class="form-control" name="duty_end[]" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="addJadwal">
                            <i class="bi bi-plus-circle"></i> Tambah Jadwal
                        </button>
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
                    searchPlaceholder: "Search Data Ekstrakurikuler...",
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
        $(document).ready(function () {
            // ===== MENANGANI JADWAL BARU DENGAN AJAX =====

            // 1. Fungsi untuk menambahkan form jadwal baru (di modal TAMBAH)
            $("#addJadwal").click(function () {
                const newJadwal = `
        <div class="jadwal-item mb-3 p-2 border rounded">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Hari</label>
                    <select class="form-select" aria-label="Pilih Hari" name="hari[]" required>
                        <option selected disabled>Pilih Hari</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                        <option value="Sabtu">Sabtu</option>
                        <option value="Minggu">Minggu</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="time" class="form-control" name="duty_start[]" required>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="time" class="form-control" name="duty_end[]" required>
                </div>
                <div class="col-md-2 d-flex align-items-end mb-2">
                    <button type="button" class="btn btn-danger btn-sm remove-jadwal">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        `;
                $("#jadwal-container").append(newJadwal);
            });

            // Hapus jadwal di form tambah
            $(document).on("click", ".remove-jadwal", function () {
                $(this).closest(".jadwal-item").remove();
            });

            // 2. PENDEKATAN AJAX UNTUK MODAL EDIT
            // Tambah jadwal langsung dengan Ajax
            $(document).on("click", ".tambah-jadwal-baru", function () {
                const ekstraId = $(this).data("ekstra-id");

                // Buat form jadwal baru
                const newJadwalForm = `
        <div class="jadwal-new-form mb-4 p-3 border rounded border-success">
            <h6 class="text-success">Tambah Jadwal Baru</h6>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Hari</label>
                    <select class="form-select jadwal-hari" required>
                        <option selected disabled>Pilih Hari</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                        <option value="Sabtu">Sabtu</option>
                        <option value="Minggu">Minggu</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="time" class="form-control jadwal-start" required>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="time" class="form-control jadwal-end" required>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-success btn-sm save-jadwal" data-ekstra-id="${ekstraId}">
                    <i class="fas fa-save"></i> Simpan Jadwal
                </button>
                <button type="button" class="btn btn-secondary btn-sm cancel-add-jadwal">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
        `;

                // Hapus form tambah yang mungkin sudah ada sebelumnya
                $(this).closest(".modal-body").find(".jadwal-new-form").remove();

                // Tambahkan form di atas tombol tambah jadwal
                $(this).before(newJadwalForm);

                // Sembunyikan tombol tambah jadwal
                $(this).hide();
            });

            // Batalkan penambahan jadwal
            $(document).on("click", ".cancel-add-jadwal", function () {
                // Hapus form
                $(this).closest(".jadwal-new-form").remove();

                // Tampilkan kembali tombol tambah jadwal
                $(this).closest(".modal-body").find(".tambah-jadwal-baru").show();
            });

            // Simpan jadwal baru dengan AJAX
            $(document).on("click", ".save-jadwal", function () {
                const btn = $(this);
                const form = $(this).closest(".jadwal-new-form");
                const ekstraId = $(this).data("ekstra-id");

                // Ambil nilai
                const hari = form.find(".jadwal-hari").val();
                const start = form.find(".jadwal-start").val();
                const end = form.find(".jadwal-end").val();

                // Validasi simpel
                if (hari === null || hari === "Pilih Hari" || !start || !end) {
                    alert("Silakan lengkapi semua field jadwal!");
                    return;
                }

                // Simpan dengan Ajax
                $.ajax({
                    url: "save_jadwal.php",
                    type: "POST",
                    data: {
                        id_ekstrakulikuler: ekstraId,
                        hari: hari,
                        duty_start: start,
                        duty_end: end
                    },
                    dataType: "json",
                    beforeSend: function () {
                        // Disable tombol & tampilkan loading
                        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                    },
                    success: function (response) {
                        if (response.success) {
                            // Tambahkan jadwal ke daftar jadwal
                            const newItem = `
                    <div class="jadwal-edit-item mb-3 p-2 border rounded">
                        <div class="row">
                            <input type="hidden" name="jadwal_id[]" value="${response.jadwal_id}">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Hari</label>
                                <select class="form-select" name="hari_edit[]" required>
                                    <option disabled>Pilih Hari</option>
                                    <option value="Senin" ${hari === 'Senin' ? 'selected' : ''}>Senin</option>
                                    <option value="Selasa" ${hari === 'Selasa' ? 'selected' : ''}>Selasa</option>
                                    <option value="Rabu" ${hari === 'Rabu' ? 'selected' : ''}>Rabu</option>
                                    <option value="Kamis" ${hari === 'Kamis' ? 'selected' : ''}>Kamis</option>
                                    <option value="Jumat" ${hari === 'Jumat' ? 'selected' : ''}>Jumat</option>
                                    <option value="Sabtu" ${hari === 'Sabtu' ? 'selected' : ''}>Sabtu</option>
                                    <option value="Minggu" ${hari === 'Minggu' ? 'selected' : ''}>Minggu</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Waktu Mulai</label>
                                <input type="time" class="form-control" name="duty_start_edit[]" value="${start}" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Waktu Selesai</label>
                                <input type="time" class="form-control" name="duty_end_edit[]" value="${end}" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end mb-2">
                                <button type="button" class="btn btn-danger btn-sm delete-jadwal" data-jadwal-id="${response.jadwal_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    `;

                            $(`#jadwal-edit-container-${ekstraId}`).append(newItem);

                            // Hapus form tambah
                            form.remove();

                            // Tampilkan kembali tombol tambah jadwal
                            $(".tambah-jadwal-baru[data-ekstra-id='" + ekstraId + "']").show();

                            // Tampilkan notifikasi sukses
                            const alert = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Jadwal baru berhasil ditambahkan!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;

                            // Tambahkan alert di bagian atas modal
                            $(`#editModal${ekstraId} .modal-body`).prepend(alert);

                            // Hilangkan alert setelah 3 detik
                            setTimeout(function () {
                                $(`#editModal${ekstraId} .alert`).alert('close');
                            }, 3000);

                        } else {
                            // Tampilkan error
                            alert("Gagal menyimpan jadwal: " + response.message);
                            btn.prop("disabled", false).html('<i class="fas fa-save"></i> Simpan Jadwal');
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("Terjadi kesalahan saat menyimpan jadwal!");
                        console.error(xhr.responseText);
                        btn.prop("disabled", false).html('<i class="fas fa-save"></i> Simpan Jadwal');
                    }
                });
            });

            // 3. Fungsi untuk menghapus jadwal
            const deleteJadwalIds = {};

            $(document).on("click", ".delete-jadwal", function () {
                if (confirm("Apakah Anda yakin ingin menghapus jadwal ini?")) {
                    const jadwalId = $(this).data("jadwal-id");
                    const ekstraId = $(this).closest(".modal").find("input[name='id_ekstrakulikuler']").val();

                    if (jadwalId) {
                        if (!deleteJadwalIds[ekstraId]) {
                            deleteJadwalIds[ekstraId] = [];
                        }
                        deleteJadwalIds[ekstraId].push(jadwalId);

                        // Update hidden input
                        $(`#delete-jadwal-ids-${ekstraId}`).val(deleteJadwalIds[ekstraId].join(','));
                    }

                    $(this).closest(".jadwal-edit-item").remove();
                }
            });
        });
    </script>
</body>

</html>