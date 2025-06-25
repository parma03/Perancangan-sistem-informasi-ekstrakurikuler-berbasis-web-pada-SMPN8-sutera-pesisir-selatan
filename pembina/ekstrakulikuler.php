<?php
session_start();
include '../db/koneksi.php';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../admin/index.php");
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selesai'])) {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'];
    $status = "Selesai";

    $query = "UPDATE tb_ekstrakulikuler SET status = ? WHERE id_ekstrakulikuler = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $id_ekstrakulikuler);

    if ($stmt->execute()) {
        $_SESSION['notification'] = "Data Ekstrakulikuler berhasil diselesaikan.";
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

$id_pembina = $_SESSION['pembina_id'];

$query = "SELECT * FROM tb_ekstrakulikuler
            LEFT JOIN tb_pembina ON tb_ekstrakulikuler.pembina_id = tb_pembina.pembina_id WHERE tb_pembina.pembina_id = '$id_pembina' AND status = 'Masih Berlangsung'";
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
                                    <h6 class="font-weight-semibold text-lg mb-0">Data Ekstrakurikuler</h6>
                                    <p class="text-sm text-muted mb-sm-0">
                                        Keseluruhan data Ekstrakurikuler
                                    </p>
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
                                                            data-bs-title="Selesai Data"
                                                            data-bs-target="#deleteModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-check"></i>
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
                                                                id="viewModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>">
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
                                                                            id="list-deskripsi-list<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            data-bs-toggle="list"
                                                                            href="#list-deskripsi<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tab" aria-controls="list-deskripsi">
                                                                            <i class="fas fa-info-circle me-2"></i>Deskripsi
                                                                        </a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-kegiatan-list<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            data-bs-toggle="list"
                                                                            href="#list-kegiatan<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tab" aria-controls="list-kegiatan">
                                                                            <i
                                                                                class="fas fa-calendar-check me-2"></i>Kegiatan
                                                                        </a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-peserta-list<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            data-bs-toggle="list"
                                                                            href="#list-peserta<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tab" aria-controls="list-peserta">
                                                                            <i class="fas fa-users me-2"></i>Peserta
                                                                        </a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-validasi-list<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            data-bs-toggle="list"
                                                                            href="#list-validasi<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tab" aria-controls="list-validasi">
                                                                            <i class="fas fa-check-circle me-2"></i>Validasi
                                                                            <?php
                                                                            // Hitung jumlah validasi yang pending
                                                                            $query_validasi = "SELECT COUNT(*) as count FROM tb_validasi WHERE id_ekstrakulikuler = ? ";
                                                                            $stmt_validasi = $conn->prepare($query_validasi);
                                                                            $stmt_validasi->bind_param("i", $data["id_ekstrakulikuler"]);
                                                                            $stmt_validasi->execute();
                                                                            $result_validasi = $stmt_validasi->get_result();
                                                                            $validasi_count = $result_validasi->fetch_assoc()["count"];
                                                                            if ($validasi_count > 0) {
                                                                                echo '<span class="badge bg-danger float-end">' . $validasi_count . '</span>';
                                                                            }
                                                                            $stmt_validasi->close();
                                                                            ?>
                                                                        </a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-absensi-list<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            data-bs-toggle="list"
                                                                            href="#list-absensi<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tab" aria-controls="list-absensi">
                                                                            <i
                                                                                class="fas fa-clipboard-check me-2"></i>Absensi
                                                                        </a>
                                                                        <a class="list-group-item list-group-item-action"
                                                                            id="list-nilai-list<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            data-bs-toggle="list"
                                                                            href="#list-nilai<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tab" aria-controls="list-nilai">
                                                                            <i class="fas fa-star me-2"></i>Nilai
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="tab-content"
                                                                        id="nav-tabContent<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                        <!-- Tab Deskripsi -->
                                                                        <div class="tab-pane fade show active"
                                                                            id="list-deskripsi<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-deskripsi-list<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                            <div class="card shadow-sm">
                                                                                <div class="card-body">
                                                                                    <div class="row mb-3">
                                                                                        <div class="col-12">
                                                                                            <h6 class="text-primary"><i
                                                                                                    class="fas fa-user me-2"></i>Pembina
                                                                                            </h6>
                                                                                            <?php if ($data["pembina_nama"] != NULL) { ?>
                                                                                                <p class="mt-2">
                                                                                                    <?php echo htmlspecialchars($data["pembina_nama"]); ?>
                                                                                                </p>
                                                                                            <?php } else { ?>
                                                                                                <div class="alert alert-warning mt-2 py-2"
                                                                                                    role="alert">
                                                                                                    <i
                                                                                                        class="fas fa-exclamation-triangle me-2"></i>Belum
                                                                                                    ada pembina tetap
                                                                                                </div>
                                                                                            <?php } ?>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="row mb-3">
                                                                                        <div class="col-12">
                                                                                            <h6 class="text-primary"><i
                                                                                                    class="fas fa-info-circle me-2"></i>Deskripsi
                                                                                            </h6>
                                                                                            <p class="mt-2">
                                                                                                <?php echo htmlspecialchars($data["deskripsi_ekstrakulikuler"]); ?>
                                                                                            </p>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="row mb-3">
                                                                                        <div class="col-12">
                                                                                            <h6 class="text-primary"><i
                                                                                                    class="fas fa-calendar me-2"></i>Periode
                                                                                            </h6>
                                                                                            <div
                                                                                                class="badge bg-gradient-info mt-2 p-2">
                                                                                                <i
                                                                                                    class="fas fa-calendar-alt me-1"></i>
                                                                                                <?php echo htmlspecialchars($data["periode"]); ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="row mb-3">
                                                                                        <div class="col-12">
                                                                                            <h6 class="text-primary"><i
                                                                                                    class="fas fa-users me-2"></i>Jumlah
                                                                                                Peserta</h6>
                                                                                            <?php
                                                                                            // Fetch jumlah peserta yang benar
                                                                                            $query_count_peserta = "SELECT COUNT(*) AS jumlah_peserta FROM tb_peserta WHERE id_ekstrakulikuler = ?";
                                                                                            $stmt_count_peserta = $conn->prepare($query_count_peserta);
                                                                                            $stmt_count_peserta->bind_param("i", $data["id_ekstrakulikuler"]);
                                                                                            $stmt_count_peserta->execute();
                                                                                            $result_count_peserta = $stmt_count_peserta->get_result();
                                                                                            $jumlah_peserta = $result_count_peserta->fetch_assoc()["jumlah_peserta"];
                                                                                            $stmt_count_peserta->close();
                                                                                            ?>
                                                                                            <div
                                                                                                class="d-flex align-items-center mt-2">
                                                                                                <div class="progress w-100 me-3"
                                                                                                    style="height: 10px;">
                                                                                                    <?php
                                                                                                    $max_peserta = 30; // Maksimal peserta yang diharapkan
                                                                                                    $percentage = min(($jumlah_peserta / $max_peserta) * 100, 100);
                                                                                                    $progress_color = $percentage > 75 ? 'bg-success' : ($percentage > 25 ? 'bg-info' : 'bg-warning');
                                                                                                    ?>
                                                                                                    <div class="progress-bar <?php echo $progress_color; ?>"
                                                                                                        role="progressbar"
                                                                                                        style="width: <?php echo $percentage; ?>%"
                                                                                                        aria-valuenow="<?php echo $jumlah_peserta; ?>"
                                                                                                        aria-valuemin="0"
                                                                                                        aria-valuemax="<?php echo $max_peserta; ?>">
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div
                                                                                                    class="text-primary font-weight-bold">
                                                                                                    <?php echo $jumlah_peserta; ?>
                                                                                                    <small
                                                                                                        class="text-muted">siswa</small>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="row">
                                                                                        <div class="col-12">
                                                                                            <h6 class="text-primary"><i
                                                                                                    class="fas fa-clock me-2"></i>Jadwal
                                                                                            </h6>
                                                                                            <?php
                                                                                            // Fetch existing schedules
                                                                                            $query_jadwal = "SELECT * FROM tb_jadwal WHERE id_ekstrakulikuler = ?";
                                                                                            $stmt_jadwal = $conn->prepare($query_jadwal);
                                                                                            $stmt_jadwal->bind_param("i", $data["id_ekstrakulikuler"]);
                                                                                            $stmt_jadwal->execute();
                                                                                            $result_jadwal = $stmt_jadwal->get_result();

                                                                                            $jadwal_count = $result_jadwal->num_rows;

                                                                                            if ($jadwal_count > 0) {
                                                                                                echo '<ol class="list-group list-group-numbered">';
                                                                                                while ($jadwal = $result_jadwal->fetch_assoc()) {
                                                                                                    echo '<li class="list-group-item d-flex justify-content-between align-items-start">';
                                                                                                    echo '<div class="ms-2 me-auto">';
                                                                                                    echo '<div class="fw-bold">' . $jadwal["hari"] . '</div>';
                                                                                                    echo '' . $jadwal["duty_start"] . '-' . $jadwal["duty_end"] . '';
                                                                                                    echo '</div>';
                                                                                                    echo '</li>';
                                                                                                }
                                                                                                echo '</ol>';
                                                                                            } else {
                                                                                                echo '<div class="alert alert-info mt-2 py-2" role="alert">';
                                                                                                echo '<i class="fas fa-info-circle me-2"></i>Belum ada jadwal tersimpan untuk ekstrakulikuler ini.';
                                                                                                echo '</div>';
                                                                                            }
                                                                                            $stmt_jadwal->close();
                                                                                            ?>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Tab Kegiatan -->
                                                                        <div class="tab-pane fade"
                                                                            id="list-kegiatan<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-kegiatan-list<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                            <div class="card shadow-sm">
                                                                                <div class="card-body">
                                                                                    <div
                                                                                        class="d-flex justify-content-between align-items-center mb-3">
                                                                                        <h6 class="text-primary m-0"><i
                                                                                                class="fas fa-calendar-check me-2"></i>Daftar
                                                                                            Kegiatan</h6>
                                                                                        <button type="button"
                                                                                            class="btn btn-sm btn-primary create-kegiatan-btn"
                                                                                            data-ekstra-id="<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                            <i
                                                                                                class="fas fa-plus-circle me-1"></i>
                                                                                            Tambah Kegiatan
                                                                                        </button>
                                                                                    </div>

                                                                                    <div
                                                                                        class="kegiatan-data-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                        <div class="text-center py-3">
                                                                                            <div class="spinner-border text-primary"
                                                                                                role="status">
                                                                                                <span
                                                                                                    class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                            <p class="mt-2">Memuat data
                                                                                                kegiatan...</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Template for Create Kegiatan Modal - Will be created dynamically -->
                                                                        <div class="modal fade create-kegiatan-modal-template"
                                                                            id="createKegiatanModalTemplate" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div
                                                                                        class="modal-header bg-gradient-primary text-white">
                                                                                        <h5 class="modal-title">
                                                                                            <i
                                                                                                class="fas fa-plus-circle me-2"></i>Tambah
                                                                                            Kegiatan
                                                                                        </h5>
                                                                                        <button type="button"
                                                                                            class="btn-close btn-close-white"
                                                                                            data-bs-dismiss="modal"
                                                                                            aria-label="Close"></button>
                                                                                    </div>
                                                                                    <form class="create-kegiatan-form"
                                                                                        action="javascript:void(0);"
                                                                                        method="POST">
                                                                                        <div class="modal-body">
                                                                                            <input type="hidden"
                                                                                                name="id_ekstrakulikuler"
                                                                                                class="create-id-ekstrakulikuler">

                                                                                            <div class="mb-3">
                                                                                                <label
                                                                                                    for="create_nama_kegiatan"
                                                                                                    class="form-label">Nama
                                                                                                    Kegiatan</label>
                                                                                                <input type="text"
                                                                                                    class="form-control create-nama-kegiatan"
                                                                                                    name="nama_kegiatan"
                                                                                                    required>
                                                                                            </div>

                                                                                            <div class="mb-3">
                                                                                                <label
                                                                                                    for="create_deskripsi_kegiatan"
                                                                                                    class="form-label">Deskripsi
                                                                                                    Kegiatan</label>
                                                                                                <textarea
                                                                                                    class="form-control create-deskripsi-kegiatan"
                                                                                                    name="kegiatan" rows="3"
                                                                                                    required></textarea>
                                                                                            </div>

                                                                                            <div class="row">
                                                                                                <div class="col-md-6 mb-3">
                                                                                                    <label
                                                                                                        for="create_jadwal"
                                                                                                        class="form-label">Jadwal
                                                                                                        Kegiatan</label>
                                                                                                    <input
                                                                                                        type="datetime-local"
                                                                                                        class="form-control create-jadwal-kegiatan"
                                                                                                        name="jadwal"
                                                                                                        required>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button"
                                                                                                class="btn btn-secondary"
                                                                                                data-bs-dismiss="modal">
                                                                                                <i
                                                                                                    class="fas fa-times me-1"></i>
                                                                                                Batal
                                                                                            </button>
                                                                                            <button type="submit"
                                                                                                class="btn btn-primary submit-kegiatan-btn">
                                                                                                <i
                                                                                                    class="fas fa-save me-1"></i>
                                                                                                Simpan
                                                                                            </button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Template for Edit Kegiatan Modal - Will be created dynamically -->
                                                                        <div class="modal fade edit-kegiatan-modal-template"
                                                                            id="editKegiatanModalTemplate" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div
                                                                                        class="modal-header bg-gradient-primary text-white">
                                                                                        <h5 class="modal-title">
                                                                                            <i
                                                                                                class="fas fa-edit me-2"></i>Edit
                                                                                            Kegiatan
                                                                                        </h5>
                                                                                        <button type="button"
                                                                                            class="btn-close btn-close-white"
                                                                                            data-bs-dismiss="modal"
                                                                                            aria-label="Close"></button>
                                                                                    </div>
                                                                                    <form class="edit-kegiatan-form"
                                                                                        action="javascript:void(0);"
                                                                                        method="POST">
                                                                                        <div class="modal-body">
                                                                                            <input type="hidden"
                                                                                                name="id_kegiatan"
                                                                                                class="edit-id-kegiatan">
                                                                                            <input type="hidden"
                                                                                                name="id_ekstrakulikuler"
                                                                                                class="edit-id-ekstrakulikuler">

                                                                                            <div class="mb-3">
                                                                                                <label
                                                                                                    for="edit_nama_kegiatan"
                                                                                                    class="form-label">Nama
                                                                                                    Kegiatan</label>
                                                                                                <input type="text"
                                                                                                    class="form-control edit-nama-kegiatan"
                                                                                                    name="nama_kegiatan"
                                                                                                    required>
                                                                                            </div>

                                                                                            <div class="mb-3">
                                                                                                <label
                                                                                                    for="edit_deskripsi_kegiatan"
                                                                                                    class="form-label">Deskripsi
                                                                                                    Kegiatan</label>
                                                                                                <textarea
                                                                                                    class="form-control edit-deskripsi-kegiatan"
                                                                                                    name="kegiatan" rows="3"
                                                                                                    required></textarea>
                                                                                            </div>

                                                                                            <div class="row">
                                                                                                <div class="col-md-6 mb-3">
                                                                                                    <label for="edit_jadwal"
                                                                                                        class="form-label">Jadwal
                                                                                                        Kegiatan</label>
                                                                                                    <input
                                                                                                        type="datetime-local"
                                                                                                        class="form-control edit-jadwal-kegiatan"
                                                                                                        name="jadwal"
                                                                                                        required>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button"
                                                                                                class="btn btn-secondary"
                                                                                                data-bs-dismiss="modal">
                                                                                                <i
                                                                                                    class="fas fa-times me-1"></i>
                                                                                                Batal
                                                                                            </button>
                                                                                            <button type="submit"
                                                                                                class="btn btn-primary update-kegiatan-btn">
                                                                                                <i
                                                                                                    class="fas fa-save me-1"></i>
                                                                                                Simpan Perubahan
                                                                                            </button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Template for Delete Kegiatan Modal - Will be created dynamically -->
                                                                        <div class="modal fade delete-kegiatan-modal-template"
                                                                            id="deleteKegiatanModalTemplate" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div
                                                                                        class="modal-header bg-gradient-danger text-white">
                                                                                        <h5 class="modal-title">
                                                                                            <i
                                                                                                class="fas fa-trash me-2"></i>Hapus
                                                                                            Kegiatan
                                                                                        </h5>
                                                                                        <button type="button"
                                                                                            class="btn-close btn-close-white"
                                                                                            data-bs-dismiss="modal"
                                                                                            aria-label="Close"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <p>Anda yakin ingin menghapus
                                                                                            kegiatan <strong
                                                                                                class="delete-kegiatan-name"></strong>?
                                                                                        </p>
                                                                                        <p class="text-danger"><small><i
                                                                                                    class="fas fa-exclamation-triangle me-2"></i>Tindakan
                                                                                                ini tidak dapat
                                                                                                dibatalkan.</small></p>
                                                                                    </div>
                                                                                    <div class="modal-footer">
                                                                                        <button type="button"
                                                                                            class="btn btn-secondary"
                                                                                            data-bs-dismiss="modal">
                                                                                            <i
                                                                                                class="fas fa-times me-1"></i>
                                                                                            Batal
                                                                                        </button>
                                                                                        <button type="button"
                                                                                            class="btn btn-danger delete-kegiatan-confirm-btn">
                                                                                            <i
                                                                                                class="fas fa-trash me-1"></i>
                                                                                            Hapus
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Tab Peserta -->
                                                                        <div class="tab-pane fade"
                                                                            id="list-peserta<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-peserta-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                            <div class="card shadow-sm">
                                                                                <div class="card-body">
                                                                                    <div
                                                                                        class="d-flex justify-content-between align-items-center mb-3">
                                                                                        <h6 class="text-primary m-0"><i
                                                                                                class="fas fa-users me-2"></i>Daftar
                                                                                            Peserta</h6>
                                                                                        <button type="button"
                                                                                            class="btn btn-sm btn-primary create-peserta-btn"
                                                                                            data-ekstra-id="<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                            <i
                                                                                                class="fas fa-plus-circle me-1"></i>
                                                                                            Tambah Peserta
                                                                                        </button>
                                                                                    </div>

                                                                                    <div
                                                                                        class="peserta-data-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                        <div class="text-center py-3">
                                                                                            <div class="spinner-border text-primary"
                                                                                                role="status">
                                                                                                <span
                                                                                                    class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                            <p class="mt-2">Memuat data
                                                                                                peserta...</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Template for Create Peserta Modal -->
                                                                        <div class="modal fade create-peserta-modal-template"
                                                                            id="createPesertaModalTemplate" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div
                                                                                        class="modal-header bg-gradient-primary text-white">
                                                                                        <h5 class="modal-title">
                                                                                            <i
                                                                                                class="fas fa-user-plus me-2"></i>Tambah
                                                                                            Peserta
                                                                                        </h5>
                                                                                        <button type="button"
                                                                                            class="btn-close btn-close-white"
                                                                                            data-bs-dismiss="modal"
                                                                                            aria-label="Close"></button>
                                                                                    </div>
                                                                                    <form class="create-peserta-form"
                                                                                        action="javascript:void(0);"
                                                                                        method="POST">
                                                                                        <div class="modal-body">
                                                                                            <input type="hidden"
                                                                                                name="id_ekstrakulikuler"
                                                                                                class="create-id-ekstrakulikuler">

                                                                                            <div class="mb-3">
                                                                                                <label for="create_id_siswa"
                                                                                                    class="form-label">Pilih
                                                                                                    Siswa</label>
                                                                                                <select
                                                                                                    class="form-select create-id-siswa"
                                                                                                    name="id_user" required>
                                                                                                    <option value="">--
                                                                                                        Pilih Siswa --
                                                                                                    </option>
                                                                                                    <!-- Options will be loaded by AJAX -->
                                                                                                </select>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button"
                                                                                                class="btn btn-secondary"
                                                                                                data-bs-dismiss="modal">
                                                                                                <i
                                                                                                    class="fas fa-times me-1"></i>
                                                                                                Batal
                                                                                            </button>
                                                                                            <button type="submit"
                                                                                                class="btn btn-primary submit-peserta-btn">
                                                                                                <i
                                                                                                    class="fas fa-save me-1"></i>
                                                                                                Simpan
                                                                                            </button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Tab Validasi -->
                                                                        <div class="tab-pane fade"
                                                                            id="list-validasi<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-validasi-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                            <div class="card shadow-sm">
                                                                                <div class="card-body">
                                                                                    <div
                                                                                        class="d-flex justify-content-between align-items-center mb-3">
                                                                                        <h6 class="text-primary m-0"><i
                                                                                                class="fas fa-users me-2"></i>Daftar
                                                                                            Validasi Peserta</h6>
                                                                                    </div>

                                                                                    <div
                                                                                        class="validasi-data-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                        <div class="text-center py-3">
                                                                                            <div class="spinner-border text-primary"
                                                                                                role="status">
                                                                                                <span
                                                                                                    class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                            <p class="mt-2">Memuat data
                                                                                                Validasi Peserta...</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Tab Absensi -->
                                                                        <div class="tab-pane fade"
                                                                            id="list-absensi<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-absensi-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                            <div class="card shadow-sm">
                                                                                <div class="card-body">
                                                                                    <div
                                                                                        class="d-flex justify-content-between align-items-center mb-3">
                                                                                        <h6 class="text-primary m-0">
                                                                                            <i
                                                                                                class="fas fa-calendar-check me-2"></i>Absensi
                                                                                            Peserta -
                                                                                            <?php echo date('d/m/Y'); ?>
                                                                                        </h6>
                                                                                        <button type="button"
                                                                                            class="btn btn-primary btn-sm refresh-absensi-btn"
                                                                                            data-ekstra-id="<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                            <i
                                                                                                class="fas fa-sync-alt me-1"></i>
                                                                                            Refresh
                                                                                        </button>
                                                                                    </div>

                                                                                    <div
                                                                                        class="absensi-data-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                        <div class="text-center py-3">
                                                                                            <div class="spinner-border text-primary"
                                                                                                role="status">
                                                                                                <span
                                                                                                    class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                            <p class="mt-2">Memuat data
                                                                                                absensi peserta...</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Tab Nilai -->
                                                                        <div class="tab-pane fade"
                                                                            id="list-nilai<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                                            role="tabpanel"
                                                                            aria-labelledby="list-nilai-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                            <div class="card shadow-sm">
                                                                                <div class="card-body">
                                                                                    <div
                                                                                        class="d-flex justify-content-between align-items-center mb-3">
                                                                                        <h6 class="text-primary m-0">
                                                                                            <i
                                                                                                class="fas fa-star me-2"></i>Penilaian
                                                                                            Peserta Ekstrakulikuler
                                                                                        </h6>
                                                                                        <button type="button"
                                                                                            class="btn btn-primary btn-sm refresh-nilai-btn"
                                                                                            data-ekstra-id="<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                            <i
                                                                                                class="fas fa-sync-alt me-1"></i>
                                                                                            Refresh
                                                                                        </button>
                                                                                    </div>

                                                                                    <div
                                                                                        class="nilai-data-container-<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                                        <div class="text-center py-3">
                                                                                            <div class="spinner-border text-primary"
                                                                                                role="status">
                                                                                                <span
                                                                                                    class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                            <p class="mt-2">Memuat data
                                                                                                penilaian peserta...</p>
                                                                                        </div>
                                                                                    </div>
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
                                                                Selesai Periode Ekstrakulikuler
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Yakin Menyelesaikan Periode Ekstrakulikuler ini,<b>
                                                                    <?php echo $data['nama_ekstrakulikuler']; ?></b>
                                                                ?</p>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="ekstrakulikuler.php" method="post">
                                                                <input type="hidden" name="id_ekstrakulikuler"
                                                                    value="<?php echo $data["id_ekstrakulikuler"]; ?>" />
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary"
                                                                    name="selesai">Selesai</button>
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="../assets/js/notification.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <!-- Tab Kegiatan -->
    <script>
        $(document).ready(function () {
            console.log('Document ready - kegiatan script loaded');

            // Global variables for tracking current ekstrakulikuler
            let currentEkstraId = null;

            // Load kegiatan data when tab is clicked
            $('a[data-bs-toggle="list"][href^="#list-kegiatan"]').on('shown.bs.tab', function (e) {
                const id_ekstrakulikuler = $(e.target).attr('href').replace('#list-kegiatan', '');
                currentEkstraId = id_ekstrakulikuler;
                console.log('Tab shown, loading kegiatan for ID:', id_ekstrakulikuler);
                loadKegiatanData(id_ekstrakulikuler);
            });

            // Handle create kegiatan button click
            $(document).on('click', '.create-kegiatan-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                currentEkstraId = id_ekstrakulikuler;

                console.log('Create kegiatan button clicked for ekstra ID:', id_ekstrakulikuler);

                // Clear previous form data
                $('#createKegiatanModalTemplate form')[0].reset();
                $('#createKegiatanModalTemplate .create-id-ekstrakulikuler').val(id_ekstrakulikuler);

                // Show the modal directly (using the template, no cloning)
                const modalInstance = new bootstrap.Modal(document.getElementById('createKegiatanModalTemplate'));
                modalInstance.show();
            });

            // Direct form submission handler - REMOVED in favor of button click handler
            // $('.create-kegiatan-form').on('submit', function (e) { ... });

            // Button click handler with direct form submission approach
            $(document).on('click', '.submit-kegiatan-btn', function (e) {
                console.log('Submit button clicked directly');
                e.preventDefault(); // Prevent default button behavior

                // Instead of looking for the form, get the form data directly from the modal
                const modalId = 'createKegiatanModalTemplate';
                const modal = document.getElementById(modalId);

                // Get form data directly from the modal's inputs
                const id_ekstrakulikuler = $(modal).find('input[name="id_ekstrakulikuler"]').val() || currentEkstraId;
                const nama_kegiatan = $(modal).find('input[name="nama_kegiatan"]').val();
                const kegiatan = $(modal).find('textarea[name="kegiatan"]').val();
                const jadwal = $(modal).find('input[name="jadwal"]').val();

                console.log('Form data collected directly from modal:', {
                    id_ekstrakulikuler,
                    nama_kegiatan,
                    kegiatan,
                    jadwal
                });

                // Manual AJAX call
                $.ajax({
                    type: 'POST',
                    url: 'kegiatan_handler.php',
                    data: {
                        'request': 'create_kegiatan',
                        'id_ekstrakulikuler': id_ekstrakulikuler,
                        'nama_kegiatan': nama_kegiatan,
                        'kegiatan': kegiatan,
                        'jadwal': jadwal
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        console.log('Sending create kegiatan request...');
                    },
                    success: function (response) {
                        console.log('AJAX Success:', response);

                        if (response && response.status === 'success') {
                            // Success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message || 'Kegiatan berhasil ditambahkan',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Hide modal
                            bootstrap.Modal.getInstance(modal).hide();

                            // Reload data
                            loadKegiatanData(id_ekstrakulikuler);
                        } else {
                            // Error message
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: (response && response.message) || 'Gagal menambahkan kegiatan'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', xhr, status, error);

                        // Error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server'
                        });
                    }
                });
            });

            // Edit kegiatan functionality - FIXED
            $(document).on('click', '.edit-kegiatan-btn', function () {
                const id_kegiatan = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_kegiatan = $(this).data('name');
                const kegiatan = $(this).data('kegiatan');
                const jadwal = $(this).data('jadwal');

                console.log('Edit button clicked for kegiatan ID:', id_kegiatan);

                // Make sure we're using the template modal element
                const editModalElement = document.getElementById('editKegiatanModalTemplate');

                if (!editModalElement) {
                    console.error('Edit modal element not found!');
                    return;
                }

                // Fill edit form
                $(editModalElement).find('.edit-id-kegiatan').val(id_kegiatan);
                $(editModalElement).find('.edit-id-ekstrakulikuler').val(id_ekstrakulikuler);
                $(editModalElement).find('.edit-nama-kegiatan').val(nama_kegiatan);
                $(editModalElement).find('.edit-deskripsi-kegiatan').val(kegiatan);
                $(editModalElement).find('.edit-jadwal-kegiatan').val(jadwal);

                // Show edit modal
                const editModal = new bootstrap.Modal(editModalElement);
                editModal.show();
            });

            // Update kegiatan button click handler
            $(document).on('click', '.update-kegiatan-btn', function (e) {
                console.log('Update button clicked');
                e.preventDefault();

                const modalElement = document.getElementById('editKegiatanModalTemplate');

                if (!modalElement) {
                    console.error('Edit modal element not found!');
                    return;
                }

                // Get form data
                const id_kegiatan = $(modalElement).find('.edit-id-kegiatan').val();
                const id_ekstrakulikuler = $(modalElement).find('.edit-id-ekstrakulikuler').val();
                const nama_kegiatan = $(modalElement).find('.edit-nama-kegiatan').val();
                const kegiatan = $(modalElement).find('.edit-deskripsi-kegiatan').val();
                const jadwal = $(modalElement).find('.edit-jadwal-kegiatan').val();

                console.log('Update form data:', {
                    id_kegiatan,
                    id_ekstrakulikuler,
                    nama_kegiatan,
                    kegiatan,
                    jadwal
                });

                // AJAX update
                $.ajax({
                    type: 'POST',
                    url: 'kegiatan_handler.php',
                    data: {
                        request: 'update_kegiatan',
                        id_kegiatan: id_kegiatan,
                        id_ekstrakulikuler: id_ekstrakulikuler,
                        nama_kegiatan: nama_kegiatan,
                        kegiatan: kegiatan,
                        jadwal: jadwal
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message || 'Kegiatan berhasil diperbarui',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Hide modal
                            const modalInstance = bootstrap.Modal.getInstance(modalElement);
                            if (modalInstance) {
                                modalInstance.hide();
                            }

                            // Reload data
                            loadKegiatanData(id_ekstrakulikuler);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message || 'Gagal memperbarui kegiatan'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', xhr, status, error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server'
                        });
                    }
                });
            });


            // Delete kegiatan functionality - simplified
            $(document).on('click', '.delete-kegiatan-btn', function () {
                const id_kegiatan = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_kegiatan = $(this).data('name');

                // Show confirmation dialog directly with SweetAlert2
                Swal.fire({
                    title: 'Hapus Kegiatan?',
                    text: `Apakah Anda yakin ingin menghapus kegiatan "${nama_kegiatan}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Delete via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'kegiatan_handler.php',
                            data: {
                                request: 'delete_kegiatan',
                                id_kegiatan: id_kegiatan
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadKegiatanData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Function to load kegiatan data
            function loadKegiatanData(id_ekstrakulikuler) {
                // Make sure we have the correct selector with proper string template
                const container = $(`.kegiatan-data-container-${id_ekstrakulikuler}`);

                console.log('Loading kegiatan data for ID:', id_ekstrakulikuler);
                console.log('Container found:', container.length > 0);

                // Show loading indicator
                container.html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data kegiatan...</p>
            </div>
        `);

                // Fetch kegiatan data via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'kegiatan_handler.php',
                    data: {
                        request: 'get_kegiatan',
                        id_ekstrakulikuler: id_ekstrakulikuler
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Get kegiatan response:', response);

                        if (response.status === 'success') {
                            // Update container with kegiatan data
                            container.html(response.html);
                        } else {
                            // Show error message
                            container.html(`
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>${response.message || 'Terjadi kesalahan saat memuat data.'}
                        </div>
                    `);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error when loading kegiatan:', xhr, status, error);

                        // Show error message
                        container.html(`
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat memuat data kegiatan.
                    </div>
                `);
                    }
                });
            }

            // Initialize first ekstrakulikuler kegiatan if tab is active
            const activeKegiatanTab = $('a[data-bs-toggle="list"][href^="#list-kegiatan"].active');
            if (activeKegiatanTab.length > 0) {
                const id_ekstrakulikuler = activeKegiatanTab.attr('href').replace('#list-kegiatan', '');
                console.log('Found active kegiatan tab, loading for ID:', id_ekstrakulikuler);
                loadKegiatanData(id_ekstrakulikuler);
            }
        });
    </script>

    <!-- Tab Peserta -->
    <script>
        $(document).ready(function () {
            console.log('Document ready - peserta script loaded');

            // Global variables for tracking current ekstrakulikuler
            let currentEkstraId = null;

            // Load peserta data when tab is clicked
            $('a[data-bs-toggle="list"][href^="#list-peserta"]').on('shown.bs.tab', function (e) {
                const id_ekstrakulikuler = $(e.target).attr('href').replace('#list-peserta', '');
                currentEkstraId = id_ekstrakulikuler;
                console.log('Tab shown, loading peserta for ID:', id_ekstrakulikuler);
                loadPesertaData(id_ekstrakulikuler);
            });

            // Handle create peserta button click
            $(document).on('click', '.create-peserta-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                currentEkstraId = id_ekstrakulikuler;

                console.log('Create peserta button clicked for ekstra ID:', id_ekstrakulikuler);

                // Clear previous form data
                $('#createPesertaModalTemplate form')[0].reset();
                $('#createPesertaModalTemplate .create-id-ekstrakulikuler').val(id_ekstrakulikuler);

                // Load available students who aren't already participants
                loadAvailableStudents(id_ekstrakulikuler);

                // Show the modal
                const modalInstance = new bootstrap.Modal(document.getElementById('createPesertaModalTemplate'));
                modalInstance.show();
            });

            // Load available students who aren't already participants
            function loadAvailableStudents(id_ekstrakulikuler) {
                const selectElement = $('#createPesertaModalTemplate .create-id-siswa');

                // Show loading indicator in select
                selectElement.html('<option value="">Loading students...</option>');

                // AJAX to get available students
                $.ajax({
                    type: 'POST',
                    url: 'peserta_handler.php',
                    data: {
                        'request': 'get_available_students',
                        'id_ekstrakulikuler': id_ekstrakulikuler
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Available students:', response);

                        if (response.status === 'success') {
                            // Populate select with available students
                            selectElement.empty();
                            selectElement.append('<option value="">-- Pilih Siswa --</option>');

                            $.each(response.data, function (index, student) {
                                selectElement.append(
                                    $('<option></option>')
                                        .attr('value', student.id_user)
                                        .text(student.siswa_nama)
                                );
                            });
                        } else {
                            // Error message
                            selectElement.html('<option value="">Error loading students</option>');

                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to load student data'
                            });
                        }
                    },
                    error: function () {
                        selectElement.html('<option value="">Error loading students</option>');

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Server error while loading students'
                        });
                    }
                });
            }

            // Button click handler for adding peserta
            $(document).on('click', '.submit-peserta-btn', function (e) {
                console.log('Submit peserta button clicked');
                e.preventDefault(); // Prevent default button behavior

                // Get the modal
                const modalId = 'createPesertaModalTemplate';
                const modal = document.getElementById(modalId);

                // Get form data directly from the modal's inputs
                const id_ekstrakulikuler = $(modal).find('input[name="id_ekstrakulikuler"]').val() || currentEkstraId;
                const id_user = $(modal).find('select[name="id_user"]').val();
                const status = $(modal).find('select[name="status"]').val();

                console.log('Form data collected directly from modal:', {
                    id_ekstrakulikuler,
                    id_user,
                    status
                });

                // Validate required fields
                if (!id_user) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan!',
                        text: 'Silakan pilih siswa terlebih dahulu'
                    });
                    return;
                }

                // Manual AJAX call
                $.ajax({
                    type: 'POST',
                    url: 'peserta_handler.php',
                    data: {
                        'request': 'create_peserta',
                        'id_ekstrakulikuler': id_ekstrakulikuler,
                        'id_user': id_user,
                        'status': status
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        console.log('Sending create peserta request...');
                    },
                    success: function (response) {
                        console.log('AJAX Success:', response);

                        if (response && response.status === 'success') {
                            // Success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message || 'Peserta berhasil ditambahkan',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Hide modal
                            bootstrap.Modal.getInstance(modal).hide();

                            // Reload data
                            loadPesertaData(id_ekstrakulikuler);
                        } else {
                            // Error message
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: (response && response.message) || 'Gagal menambahkan peserta'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', xhr, status, error);

                        // Error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server'
                        });
                    }
                });
            });

            // Delete peserta functionality
            $(document).on('click', '.delete-peserta-btn', function () {
                const id_peserta = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_siswa = $(this).data('name');

                // Show confirmation dialog directly with SweetAlert2
                Swal.fire({
                    title: 'Hapus Peserta?',
                    text: `Apakah Anda yakin ingin menghapus peserta "${nama_siswa}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Delete via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'peserta_handler.php',
                            data: {
                                request: 'delete_peserta',
                                id_peserta: id_peserta
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadPesertaData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Function to load peserta data
            function loadPesertaData(id_ekstrakulikuler) {
                // Make sure we have the correct selector with proper string template
                const container = $(`.peserta-data-container-${id_ekstrakulikuler}`);

                console.log('Loading peserta data for ID:', id_ekstrakulikuler);
                console.log('Container found:', container.length > 0);

                // Show loading indicator
                container.html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data peserta...</p>
            </div>
        `);

                // Fetch peserta data via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'peserta_handler.php',
                    data: {
                        request: 'get_peserta',
                        id_ekstrakulikuler: id_ekstrakulikuler
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Get peserta response:', response);

                        if (response.status === 'success') {
                            // Update container with peserta data
                            container.html(response.html);
                        } else {
                            // Show error message
                            container.html(`
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>${response.message || 'Terjadi kesalahan saat memuat data.'}
                        </div>
                    `);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error when loading peserta:', xhr, status, error);

                        // Show error message
                        container.html(`
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat memuat data peserta.
                    </div>
                `);
                    }
                });
            }

            // Initialize first ekstrakulikuler peserta if tab is active
            const activePesertaTab = $('a[data-bs-toggle="list"][href^="#list-peserta"].active');
            if (activePesertaTab.length > 0) {
                const id_ekstrakulikuler = activePesertaTab.attr('href').replace('#list-peserta', '');
                console.log('Found active peserta tab, loading for ID:', id_ekstrakulikuler);
                loadPesertaData(id_ekstrakulikuler);
            }
        });
    </script>

    <!-- Tab Validasi -->
    <script>
        $(document).ready(function () {
            console.log('Document ready - validasi script loaded');

            // Global variables for tracking current ekstrakulikuler
            let currentEkstraId = null;

            // Load validasi data when tab is clicked
            $('a[data-bs-toggle="list"][href^="#list-validasi"]').on('shown.bs.tab', function (e) {
                const id_ekstrakulikuler = $(e.target).attr('href').replace('#list-validasi', '');
                currentEkstraId = id_ekstrakulikuler;
                console.log('Tab shown, loading validasi for ID:', id_ekstrakulikuler);
                loadValidasiData(id_ekstrakulikuler);
            });

            // Handle select all checkbox
            $(document).on('change', '.select-all-validasi', function () {
                const isChecked = $(this).prop('checked');
                const ekstraId = $(this).closest('form').attr('id').replace('validasi-form-', '');

                // Select or deselect all checkboxes
                $(`#validasi-form-${ekstraId} .validasi-checkbox`).prop('checked', isChecked);
            });

            // Single accept validasi functionality
            $(document).on('click', '.accept-validasi-btn', function () {
                const id_validasi = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_siswa = $(this).data('name');

                // Show confirmation dialog
                Swal.fire({
                    title: 'Terima Peserta?',
                    text: `Apakah Anda yakin ingin menerima peserta "${nama_siswa}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Terima!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Accept via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'validasi_handler.php',
                            data: {
                                request: 'accept_validasi',
                                id_validasi: id_validasi
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadValidasiData(id_ekstrakulikuler);

                                    // If there's an active peserta tab for this ekstra, reload it too
                                    const pesertaContainer = $(`.peserta-data-container-${id_ekstrakulikuler}`);
                                    if (pesertaContainer.length > 0) {
                                        if (typeof loadPesertaData === 'function') {
                                            loadPesertaData(id_ekstrakulikuler);
                                        }
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Single reject validasi functionality
            $(document).on('click', '.reject-validasi-btn', function () {
                const id_validasi = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_siswa = $(this).data('name');

                // Show confirmation dialog
                Swal.fire({
                    title: 'Tolak Peserta?',
                    text: `Apakah Anda yakin ingin menolak peserta "${nama_siswa}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Reject via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'validasi_handler.php',
                            data: {
                                request: 'reject_validasi',
                                id_validasi: id_validasi
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadValidasiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Multiple accept validasi
            $(document).on('click', '.accept-selected-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const selected = $(`#validasi-form-${id_ekstrakulikuler} .validasi-checkbox:checked`);

                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Pilih minimal satu peserta untuk diterima',
                    });
                    return;
                }

                // Get all selected IDs
                const validasi_ids = [];
                selected.each(function () {
                    validasi_ids.push($(this).val());
                });

                // Confirm action
                Swal.fire({
                    title: 'Terima Peserta Terpilih?',
                    text: `Apakah Anda yakin ingin menerima ${selected.length} peserta terpilih?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Terima Semua!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Process via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'validasi_handler.php',
                            data: {
                                request: 'accept_multiple_validasi',
                                validasi_ids: validasi_ids
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadValidasiData(id_ekstrakulikuler);

                                    // If there's an active peserta tab for this ekstra, reload it too
                                    if (typeof loadPesertaData === 'function') {
                                        loadPesertaData(id_ekstrakulikuler);
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Multiple reject validasi
            $(document).on('click', '.reject-selected-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const selected = $(`#validasi-form-${id_ekstrakulikuler} .validasi-checkbox:checked`);

                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Pilih minimal satu peserta untuk ditolak',
                    });
                    return;
                }

                // Get all selected IDs
                const validasi_ids = [];
                selected.each(function () {
                    validasi_ids.push($(this).val());
                });

                // Confirm action
                Swal.fire({
                    title: 'Tolak Peserta Terpilih?',
                    text: `Apakah Anda yakin ingin menolak ${selected.length} peserta terpilih?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak Semua!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Process via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'validasi_handler.php',
                            data: {
                                request: 'reject_multiple_validasi',
                                validasi_ids: validasi_ids
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadValidasiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Function to load validasi data
            function loadValidasiData(id_ekstrakulikuler) {
                // Make sure we have the correct selector
                const container = $(`.validasi-data-container-${id_ekstrakulikuler}`);

                console.log('Loading validasi data for ID:', id_ekstrakulikuler);
                console.log('Container found:', container.length > 0);

                // Show loading indicator
                container.html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data validasi peserta...</p>
            </div>
        `);

                // Fetch validasi data via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'validasi_handler.php',
                    data: {
                        request: 'get_validasi',
                        id_ekstrakulikuler: id_ekstrakulikuler
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Get validasi response:', response);

                        if (response.status === 'success') {
                            // Update container with validasi data
                            container.html(response.html);
                        } else {
                            // Show error message
                            container.html(`
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>${response.message || 'Terjadi kesalahan saat memuat data.'}
                        </div>
                    `);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error when loading validasi:', xhr, status, error);

                        // Show error message
                        container.html(`
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat memuat data validasi.
                    </div>
                `);
                    }
                });
            }

            // Initialize first ekstrakulikuler validasi if tab is active
            const activeValidasiTab = $('a[data-bs-toggle="list"][href^="#list-validasi"].active');
            if (activeValidasiTab.length > 0) {
                const id_ekstrakulikuler = activeValidasiTab.attr('href').replace('#list-validasi', '');
                console.log('Found active validasi tab, loading for ID:', id_ekstrakulikuler);
                loadValidasiData(id_ekstrakulikuler);
            }
        });
    </script>

    <!-- Tab Absensi -->
    <script>
        $(document).ready(function () {
            console.log('Document ready - absensi script loaded');

            // Load absensi data when tab is clicked
            $('a[data-bs-toggle="list"][href^="#list-absensi"]').on('shown.bs.tab', function (e) {
                const id_ekstrakulikuler = $(e.target).attr('href').replace('#list-absensi', '');
                console.log('Tab shown, loading absensi for ID:', id_ekstrakulikuler);
                loadAbsensiData(id_ekstrakulikuler);
            });

            // Handle select all checkbox for absensi
            $(document).on('change', '.select-all-absensi', function () {
                const isChecked = $(this).prop('checked');
                const ekstraId = $(this).closest('form').attr('id').replace('absensi-form-', '');

                // Select or deselect all checkboxes
                $(`#absensi-form-${ekstraId} .absensi-checkbox`).prop('checked', isChecked);
            });

            // Single hadir absensi functionality
            $(document).on('click', '.hadir-absensi-btn', function () {
                const id_peserta = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_siswa = $(this).data('name');

                // Show confirmation dialog
                Swal.fire({
                    title: 'Konfirmasi Kehadiran',
                    text: `Tandai "${nama_siswa}" sebagai HADIR?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hadir!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mark as present via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'absensi_handler.php',
                            data: {
                                request: 'mark_hadir',
                                id_peserta: id_peserta,
                                id_ekstrakulikuler: id_ekstrakulikuler
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadAbsensiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Single tidak hadir absensi functionality
            $(document).on('click', '.tidak-hadir-absensi-btn', function () {
                const id_peserta = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_siswa = $(this).data('name');

                // Show confirmation dialog
                Swal.fire({
                    title: 'Konfirmasi Ketidakhadiran',
                    text: `Tandai "${nama_siswa}" sebagai TIDAK HADIR?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tidak Hadir!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mark as absent via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'absensi_handler.php',
                            data: {
                                request: 'mark_tidak_hadir',
                                id_peserta: id_peserta,
                                id_ekstrakulikuler: id_ekstrakulikuler
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadAbsensiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Multiple hadir absensi
            $(document).on('click', '.hadir-selected-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const selected = $(`#absensi-form-${id_ekstrakulikuler} .absensi-checkbox:checked`);

                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Pilih minimal satu peserta untuk ditandai hadir',
                    });
                    return;
                }

                // Get all selected IDs
                const peserta_ids = [];
                selected.each(function () {
                    peserta_ids.push($(this).val());
                });

                // Confirm action
                Swal.fire({
                    title: 'Tandai Hadir Terpilih?',
                    text: `Apakah Anda yakin ingin menandai ${selected.length} peserta terpilih sebagai HADIR?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tandai Hadir!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Process via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'absensi_handler.php',
                            data: {
                                request: 'mark_multiple_hadir',
                                peserta_ids: peserta_ids,
                                id_ekstrakulikuler: id_ekstrakulikuler
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadAbsensiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Multiple tidak hadir absensi
            $(document).on('click', '.tidak-hadir-selected-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const selected = $(`#absensi-form-${id_ekstrakulikuler} .absensi-checkbox:checked`);

                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Pilih minimal satu peserta untuk ditandai tidak hadir',
                    });
                    return;
                }

                // Get all selected IDs
                const peserta_ids = [];
                selected.each(function () {
                    peserta_ids.push($(this).val());
                });

                // Confirm action
                Swal.fire({
                    title: 'Tandai Tidak Hadir Terpilih?',
                    text: `Apakah Anda yakin ingin menandai ${selected.length} peserta terpilih sebagai TIDAK HADIR?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tandai Tidak Hadir!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Process via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'absensi_handler.php',
                            data: {
                                request: 'mark_multiple_tidak_hadir',
                                peserta_ids: peserta_ids,
                                id_ekstrakulikuler: id_ekstrakulikuler
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadAbsensiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Refresh absensi data
            $(document).on('click', '.refresh-absensi-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                loadAbsensiData(id_ekstrakulikuler);
            });

            // Function to load absensi data
            function loadAbsensiData(id_ekstrakulikuler) {
                const container = $(`.absensi-data-container-${id_ekstrakulikuler}`);

                console.log('Loading absensi data for ID:', id_ekstrakulikuler);
                console.log('Container found:', container.length > 0);

                // Show loading indicator
                container.html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data absensi peserta...</p>
            </div>
        `);

                // Fetch absensi data via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'absensi_handler.php',
                    data: {
                        request: 'get_absensi',
                        id_ekstrakulikuler: id_ekstrakulikuler
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Get absensi response:', response);

                        if (response.status === 'success') {
                            // Update container with absensi data
                            container.html(response.html);
                        } else {
                            // Show error message
                            container.html(`
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>${response.message || 'Terjadi kesalahan saat memuat data.'}
                        </div>
                    `);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error when loading absensi:', xhr, status, error);

                        // Show error message
                        container.html(`
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat memuat data absensi.
                    </div>
                `);
                    }
                });
            }

            // Initialize first ekstrakulikuler absensi if tab is active
            const activeAbsensiTab = $('a[data-bs-toggle="list"][href^="#list-absensi"].active');
            if (activeAbsensiTab.length > 0) {
                const id_ekstrakulikuler = activeAbsensiTab.attr('href').replace('#list-absensi', '');
                console.log('Found active absensi tab, loading for ID:', id_ekstrakulikuler);
                loadAbsensiData(id_ekstrakulikuler);
            }
        });
    </script>

    <!-- Tab Nilai -->
    <script>
        $(document).ready(function () {
            console.log('Document ready - nilai script loaded');

            // Load nilai data when tab is clicked
            $('a[data-bs-toggle="list"][href^="#list-nilai"]').on('shown.bs.tab', function (e) {
                const id_ekstrakulikuler = $(e.target).attr('href').replace('#list-nilai', '');
                console.log('Tab shown, loading nilai for ID:', id_ekstrakulikuler);
                loadNilaiData(id_ekstrakulikuler);
            });

            // Handle individual nilai save
            $(document).on('click', '.save-nilai-btn', function () {
                const id_peserta = $(this).data('id');
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const nama_siswa = $(this).data('name');

                const nilai_keaktifan = $(`#nilai_keaktifan_${id_peserta}`).val();
                const nilai_keterampilan = $(`#nilai_keterampilan_${id_peserta}`).val();
                const nilai_sikap = $(`#nilai_sikap_${id_peserta}`).val();

                // Validation
                if (!nilai_keaktifan || !nilai_keterampilan || !nilai_sikap) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Semua komponen nilai harus diisi!'
                    });
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Simpan Nilai',
                    text: `Simpan nilai untuk "${nama_siswa}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Save nilai via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'nilai_handler.php',
                            data: {
                                request: 'save_nilai',
                                id_peserta: id_peserta,
                                id_ekstrakulikuler: id_ekstrakulikuler,
                                nilai_keaktifan: nilai_keaktifan,
                                nilai_keterampilan: nilai_keterampilan,
                                nilai_sikap: nilai_sikap
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadNilaiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Handle batch nilai save
            $(document).on('click', '.save-all-nilai-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                const form = $(`#nilai-form-${id_ekstrakulikuler}`);

                let allFilled = true;
                let nilaiData = [];

                // Collect all nilai data
                form.find('.nilai-row').each(function () {
                    const id_peserta = $(this).data('peserta-id');
                    const nilai_keaktifan = $(this).find('.nilai-keaktifan').val();
                    const nilai_keterampilan = $(this).find('.nilai-keterampilan').val();
                    const nilai_sikap = $(this).find('.nilai-sikap').val();

                    if (!nilai_keaktifan || !nilai_keterampilan || !nilai_sikap) {
                        allFilled = false;
                        return false;
                    }

                    nilaiData.push({
                        id_peserta: id_peserta,
                        nilai_keaktifan: nilai_keaktifan,
                        nilai_keterampilan: nilai_keterampilan,
                        nilai_sikap: nilai_sikap
                    });
                });

                if (!allFilled) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Semua komponen nilai harus diisi untuk semua peserta!'
                    });
                    return;
                }

                if (nilaiData.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Tidak ada data nilai untuk disimpan!'
                    });
                    return;
                }

                // Confirm batch save
                Swal.fire({
                    title: 'Simpan Semua Nilai',
                    text: `Simpan nilai untuk ${nilaiData.length} peserta?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Simpan Semua!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Save all nilai via AJAX
                        $.ajax({
                            type: 'POST',
                            url: 'nilai_handler.php',
                            data: {
                                request: 'save_all_nilai',
                                id_ekstrakulikuler: id_ekstrakulikuler,
                                nilai_data: JSON.stringify(nilaiData)
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    // Reload data
                                    loadNilaiData(id_ekstrakulikuler);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Refresh nilai data
            $(document).on('click', '.refresh-nilai-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                loadNilaiData(id_ekstrakulikuler);
            });

            // Function to load nilai data
            function loadNilaiData(id_ekstrakulikuler) {
                const container = $(`.nilai-data-container-${id_ekstrakulikuler}`);

                console.log('Loading nilai data for ID:', id_ekstrakulikuler);
                console.log('Container found:', container.length > 0);

                // Show loading indicator
                container.html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data penilaian peserta...</p>
            </div>
        `);

                // Fetch nilai data via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'nilai_handler.php',
                    data: {
                        request: 'get_nilai',
                        id_ekstrakulikuler: id_ekstrakulikuler
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Get nilai response:', response);

                        if (response.status === 'success') {
                            // Update container with nilai data
                            container.html(response.html);
                        } else {
                            // Show error message
                            container.html(`
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>${response.message || 'Terjadi kesalahan saat memuat data.'}
                        </div>
                    `);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error when loading nilai:', xhr, status, error);

                        // Show error message
                        container.html(`
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat memuat data penilaian.
                    </div>
                `);
                    }
                });
            }

            // Initialize first ekstrakulikuler nilai if tab is active
            const activeNilaiTab = $('a[data-bs-toggle="list"][href^="#list-nilai"].active');
            if (activeNilaiTab.length > 0) {
                const id_ekstrakulikuler = activeNilaiTab.attr('href').replace('#list-nilai', '');
                console.log('Found active nilai tab, loading for ID:', id_ekstrakulikuler);
                loadNilaiData(id_ekstrakulikuler);
            }
        });
    </script>

    <!-- Modal untuk menampilkan data formulir -->
    <div class="modal fade" id="formDataModal" tabindex="-1" aria-labelledby="formDataModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formDataModalLabel">
                        <i class="fas fa-file-alt me-2"></i>Data Formulir Peserta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="formDataContent">
                    <!-- Loading content -->
                    <div class="text-center" id="formDataLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data formulir...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            // Handle view form button click
            $(document).on('click', '.view-form-btn', function () {
                var validasi_id = $(this).data('id');
                var siswa_name = $(this).data('name');

                // Set modal title
                $('#formDataModalLabel').html('<i class="fas fa-file-alt me-2"></i>Data Formulir - ' + siswa_name);

                // Show loading
                $('#formDataContent').html(`
            <div class="text-center" id="formDataLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data formulir...</p>
            </div>
        `);

                // Show modal
                $('#formDataModal').modal('show');

                // Fetch form data
                $.ajax({
                    url: 'validasi_handler.php', // Current page
                    type: 'POST',
                    data: {
                        request: 'get_form_data',
                        id_validasi: validasi_id
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            displayFormData(response.data);
                        } else {
                            $('#formDataContent').html(`
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message}
                        </div>
                    `);
                        }
                    },
                    error: function () {
                        $('#formDataContent').html(`
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Terjadi kesalahan saat memuat data formulir.
                    </div>
                `);
                    }
                });
            });

            function displayFormData(data) {
                var html = `
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Informasi Pribadi
                            </h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>NIS:</strong>
                                    <p class="mb-1">${data.nis || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Nama Lengkap:</strong>
                                    <p class="mb-1">${data.nama_lengkap || '-'}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Kelas:</strong>
                                    <p class="mb-1">${data.kelas || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Jenis Kelamin:</strong>
                                    <p class="mb-1">${data.jenis_kelamin || '-'}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Tanggal Lahir:</strong>
                                    <p class="mb-1">${data.tanggal_lahir_formatted || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Alamat:</strong>
                                    <p class="mb-1">${data.alamat || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-3">
                                <i class="fas fa-phone me-2"></i>Kontak
                            </h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>No. HP Siswa:</strong>
                                    <p class="mb-1">${data.no_hp_siswa || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>No. HP Wali:</strong>
                                    <p class="mb-1">${data.no_hp_wali || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-3">
                                <i class="fas fa-clipboard-list me-2"></i>Informasi Tambahan
                            </h6>
                            <div class="mb-3">
                                <strong>Alasan Bergabung:</strong>
                                <p class="mb-1 text-justify">${data.alasan || '-'}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Pengalaman:</strong>
                                <p class="mb-1 text-justify">${data.pengalaman || '-'}</p>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Ketersediaan Waktu:</strong>
                                    <p class="mb-1">
                                        <span class="${data.ketersediaan === 'Ya' ? 'bg-success' : 'bg-danger'}">
                                            ${data.ketersediaan || '-'}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Persetujuan Orang Tua:</strong>
                                    <p class="mb-1">
                                        <span class="${data.persetujuan === 'Ya' ? 'bg-success' : 'bg-danger'}">
                                            ${data.persetujuan || '-'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-3">
                                <i class="fas fa-calendar me-2"></i>Informasi Sistem
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Tanggal Pendaftaran:</strong>
                                    <p class="mb-1">${data.created_date_formatted || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>ID Formulir:</strong>
                                    <p class="mb-1">#${data.id_form || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#formDataContent').html(html);
            }
        });
    </script>
</body>

</html>