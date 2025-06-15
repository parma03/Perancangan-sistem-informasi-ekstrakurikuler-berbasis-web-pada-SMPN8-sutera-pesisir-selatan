<?php
session_start();
include '../db/koneksi.php';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pembina') {
        header("Location: ../pembina/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Wakil') {
        header("Location: ../wakil/index.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}

$id_user = $_SESSION['id_user'];

function cekPramuka($conn, $id_user)
{
    $query_pramuka = "SELECT COUNT(*) as count FROM tb_peserta tp 
                      JOIN tb_ekstrakulikuler te ON tp.id_ekstrakulikuler = te.id_ekstrakulikuler 
                      WHERE tp.id_user = ? AND LOWER(te.nama_ekstrakulikuler) LIKE '%pramuka%'";
    $stmt_pramuka = $conn->prepare($query_pramuka);
    $stmt_pramuka->bind_param("i", $id_user);
    $stmt_pramuka->execute();
    $result_pramuka = $stmt_pramuka->get_result();
    $pramuka_data = $result_pramuka->fetch_assoc();
    $stmt_pramuka->close();

    return $pramuka_data['count'] > 0;
}

// Cek apakah user sudah terdaftar Pramuka
$sudah_pramuka = cekPramuka($conn, $id_user);


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar'])) {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'];
    $id_user = $_SESSION['id_user'];

    // Cek nama ekstrakulikuler yang akan didaftar
    $query_nama_ekstrak = "SELECT nama_ekstrakulikuler FROM tb_ekstrakulikuler WHERE id_ekstrakulikuler = ?";
    $stmt_nama_ekstrak = $conn->prepare($query_nama_ekstrak);
    $stmt_nama_ekstrak->bind_param("i", $id_ekstrakulikuler);
    $stmt_nama_ekstrak->execute();
    $result_nama_ekstrak = $stmt_nama_ekstrak->get_result();
    $nama_ekstrak_data = $result_nama_ekstrak->fetch_assoc();
    $stmt_nama_ekstrak->close();

    $nama_ekstrakulikuler = strtolower($nama_ekstrak_data['nama_ekstrakulikuler']);
    $is_pramuka = strpos($nama_ekstrakulikuler, 'pramuka') !== false;

    // Jika bukan Pramuka dan belum terdaftar Pramuka, tolak pendaftaran
    if (!$is_pramuka && !$sudah_pramuka) {
        $_SESSION['notification'] = "Anda harus mendaftar dan menjadi peserta Pramuka terlebih dahulu sebelum dapat mendaftar ekstrakulikuler lain.";
        $_SESSION['alert'] = "alert-warning";
        header("Location: ekstrakulikuler.php");
        exit();
    }

    // Cek apakah user sudah terdaftar sebagai peserta
    $check_peserta = "SELECT id_peserta FROM tb_peserta WHERE id_user = ? AND id_ekstrakulikuler = ?";
    $stmt_check_peserta = $conn->prepare($check_peserta);
    $stmt_check_peserta->bind_param("ii", $id_user, $id_ekstrakulikuler);
    $stmt_check_peserta->execute();
    $result_peserta = $stmt_check_peserta->get_result();

    // Cek apakah user sudah mengajukan validasi
    $check_validasi = "SELECT id_validasi FROM tb_validasi WHERE id_user = ? AND id_ekstrakulikuler = ?";
    $stmt_check_validasi = $conn->prepare($check_validasi);
    $stmt_check_validasi->bind_param("ii", $id_user, $id_ekstrakulikuler);
    $stmt_check_validasi->execute();
    $result_validasi = $stmt_check_validasi->get_result();

    if ($result_peserta->num_rows > 0) {
        $_SESSION['notification'] = "Anda sudah terdaftar sebagai peserta ekstrakulikuler ini.";
        $_SESSION['alert'] = "alert-warning";
    } elseif ($result_validasi->num_rows > 0) {
        $_SESSION['notification'] = "Anda sudah mengajukan pendaftaran untuk ekstrakulikuler ini. Tunggu konfirmasi.";
        $_SESSION['alert'] = "alert-info";
    } else {
        $query = "INSERT INTO tb_validasi (id_ekstrakulikuler, id_user) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);

        if ($stmt->execute()) {
            $_SESSION['notification'] = "Berhasil Daftar Ekstrakulikuler ini.";
            $_SESSION['alert'] = "alert-success";
        } else {
            $_SESSION['notification'] = "Gagal mendaftar ekstrakulikuler. Silakan coba lagi.";
            $_SESSION['alert'] = "alert-danger";
        }
        $stmt->close();
    }

    $stmt_check_peserta->close();
    $stmt_check_validasi->close();
    header("Location: ekstrakulikuler.php");
    exit();
}

// Query yang dimodifikasi untuk menampilkan hanya ekstrakulikuler yang belum diikuti dan belum mengajukan validasi
if (!$sudah_pramuka) {
    $query = "SELECT e.*, p.pembina_nama 
              FROM tb_ekstrakulikuler e
              LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id 
              WHERE e.status = 'Masih Berlangsung'
              AND LOWER(e.nama_ekstrakulikuler) LIKE '%pramuka%'
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tp.id_ekstrakulikuler 
                  FROM tb_peserta tp 
                  WHERE tp.id_user = ?
              )
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tv.id_ekstrakulikuler 
                  FROM tb_validasi tv 
                  WHERE tv.id_user = ?
              )
              ORDER BY e.nama_ekstrakulikuler ASC";
} else {
    // Jika sudah terdaftar Pramuka, tampilkan semua kecuali yang sudah diikuti
    $query = "SELECT e.*, p.pembina_nama 
              FROM tb_ekstrakulikuler e
              LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id 
              WHERE e.status = 'Masih Berlangsung'
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tp.id_ekstrakulikuler 
                  FROM tb_peserta tp 
                  WHERE tp.id_user = ?
              )
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tv.id_ekstrakulikuler 
                  FROM tb_validasi tv 
                  WHERE tv.id_user = ?
              )
              ORDER BY e.nama_ekstrakulikuler ASC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_user, $id_user);
$stmt->execute();
$result = $stmt->get_result();

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
            <?php if (!$sudah_pramuka): ?>
                <div class="row my-4">
                    <div class="col-12">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Anda harus mendaftar dan menjadi peserta Pramuka terlebih dahulu
                            sebelum dapat mendaftar ekstrakulikuler lain.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="row my-4">
                <div class="col-lg col-md-6">
                    <div class="card border-0 shadow-xs mb-4">
                        <div class="card-header border-bottom-0 pb-0">
                            <div class="d-sm-flex align-items-center mb-3">
                                <div>
                                    <h6 class="font-weight-semibold text-lg mb-0">Data Ekstrakurikuler - Pendaftaran
                                    </h6>
                                    <p class="text-sm text-muted mb-sm-0">
                                        <?php if (!$sudah_pramuka): ?>
                                            Silakan daftar Pramuka terlebih dahulu
                                        <?php else: ?>
                                            Ekstrakurikuler yang tersedia untuk pendaftaran
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body px-0 py-0">
                            <?php if ($result->num_rows == 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Tidak ada ekstrakulikuler yang tersedia</h5>
                                    <p class="text-sm text-muted">
                                        <?php if (!$sudah_pramuka): ?>
                                            Anda belum terdaftar sebagai peserta Pramuka atau sudah mengajukan pendaftaran
                                            Pramuka.
                                        <?php else: ?>
                                            Anda sudah terdaftar atau mengajukan pendaftaran untuk semua ekstrakulikuler yang
                                            tersedia.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
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
                                            while ($data = $result->fetch_assoc()) { ?>
                                                <tr>
                                                    <td>
                                                        <p class="transaction-amount mb-0"><?php echo $no++ ?></p>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center px-2 py-1">
                                                            <div>
                                                                <h6 class="transaction-name mb-0">
                                                                    <?php echo htmlspecialchars($data["nama_ekstrakulikuler"]) ?>
                                                                </h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <p class="transaction-amount mb-0">
                                                            <?php echo htmlspecialchars($data["deskripsi_ekstrakulikuler"]) ?>
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <?php if ($data["pembina_nama"] != NULL) { ?>
                                                            <p class="transaction-amount mb-0">
                                                                <?php echo htmlspecialchars($data["pembina_nama"]) ?>
                                                            </p>
                                                        <?php } else { ?>
                                                            <span class="badge bg-danger text-white">
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
                                                            <span class="badge bg-success text-white">
                                                                <?php echo $data["status"] ?>
                                                            </span>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="View Data"
                                                            data-bs-target="#viewModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>

                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="Daftar Ekstrakulikuler"
                                                            data-bs-target="#daftarModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-user-plus"></i>
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

                                                <!-- Daftar Modal-->
                                                <div class="modal fade"
                                                    id="daftarModal<?php echo $data["id_ekstrakulikuler"]; ?>" tabindex="-1"
                                                    aria-labelledby="daftarModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="daftarModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                    Daftar Ekstrakulikuler
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle me-2"></i>
                                                                    Setelah mendaftar, pendaftaran Anda akan masuk ke sistem
                                                                    validasi dan menunggu persetujuan admin.
                                                                </div>
                                                                <p class="text-center">
                                                                    <strong>Apakah Anda yakin ingin mendaftar pada
                                                                        ekstrakulikuler ini?</strong>
                                                                </p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form action="ekstrakulikuler.php" method="post">
                                                                    <input type="hidden" name="id_ekstrakulikuler"
                                                                        value="<?php echo $data["id_ekstrakulikuler"]; ?>" />
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-primary" name="daftar">
                                                                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
            <?php if ($result->num_rows > 0): ?>
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
                        targets: -1 // Nonaktifkan sort di kolom "Action" (kolom terakhir)
                    }],
                    initComplete: function () {
                        // Initialize tooltips after DataTables loads
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                            '[data-bs-toggle="tooltip"]'))
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl)
                        });
                    }
                });
            <?php endif; ?>

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

</body>

</html>

<?php
// Tutup statement dan koneksi
$stmt->close();
$conn->close();
?>