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

$query = "SELECT * FROM tb_ekstrakulikuler
            LEFT JOIN tb_pembina ON tb_ekstrakulikuler.pembina_id = tb_pembina.pembina_id
            INNER JOIN tb_peserta ON tb_peserta.id_ekstrakulikuler = tb_ekstrakulikuler.id_ekstrakulikuler
            WHERE tb_peserta.id_user  = '$id_user' AND status = 'Masih Berlangsung'";
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
                                    <h6 class="font-weight-semibold text-lg mb-0">Data Ekstrakurikuler - Diikuti
                                    </h6>
                                    <p class="text-sm text-muted mb-sm-0">
                                        Ekstrakurikuler yang Terdaftar
                                    </p>
                                </div>
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
                                                    <i class="fas fa-eye"></i></button>
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
                                                                        <i class="fas fa-calendar me-2"></i>Jadwal &
                                                                        Kegiatan
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

                                                                    <!-- Tab Jadwal & Kegiatan -->
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
        // Tab Kegiatan - Updated Script for Calendar View  
        $(document).ready(function () {
            console.log('Document ready - kegiatan calendar script loaded');

            // Global variables for tracking current ekstrakulikuler
            let currentEkstraId = null;

            // Load kegiatan data when tab is clicked
            $('a[data-bs-toggle="list"][href^="#list-kegiatan"]').on('shown.bs.tab', function (e) {
                const id_ekstrakulikuler = $(e.target).attr('href').replace('#list-kegiatan', '');
                currentEkstraId = id_ekstrakulikuler;
                console.log('Tab shown, loading kegiatan calendar for ID:', id_ekstrakulikuler);
                loadKegiatanData(id_ekstrakulikuler);
            });

            // Function to load kegiatan data with calendar view
            function loadKegiatanData(id_ekstrakulikuler) {
                const container = $(`.kegiatan-data-container-${id_ekstrakulikuler}`);

                console.log('Loading kegiatan calendar data for ID:', id_ekstrakulikuler);
                console.log('Container found:', container.length > 0);

                // Show loading indicator with calendar theme
                container.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="text-primary">
                    <i class="fas fa-calendar-alt me-2"></i>Memuat Calendar Kegiatan...
                </h6>
                <p class="text-muted mb-0">Sedang mengambil jadwal dan kegiatan ekstrakurikuler</p>
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
                    timeout: 10000, // 10 second timeout
                    success: function (response) {
                        console.log('Get kegiatan calendar response:', response);

                        if (response.status === 'success') {
                            // Update container with calendar data
                            container.html(response.html);

                            // Initialize any additional interactive elements
                            initializeCalendarInteractions(id_ekstrakulikuler);

                            // Add fade-in animation
                            container.find('.timeline-item, .card').each(function (index) {
                                $(this).css('opacity', '0').delay(index * 100).animate({
                                    opacity: 1
                                }, 500);
                            });

                        } else {
                            // Show error message with calendar theme
                            container.html(`
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-calendar-times text-danger" style="font-size: 3rem;"></i>
                            </div>
                            <div class="alert alert-danger d-inline-block" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${response.message || 'Terjadi kesalahan saat memuat calendar kegiatan.'}
                            </div>
                        </div>
                    `);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error when loading kegiatan calendar:', xhr, status, error);

                        let errorMessage = 'Terjadi kesalahan saat memuat calendar kegiatan.';
                        if (status === 'timeout') {
                            errorMessage = 'Koneksi timeout. Silakan coba lagi.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Terjadi kesalahan pada server. Silakan hubungi administrator.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Handler tidak ditemukan. Silakan periksa konfigurasi.';
                        }

                        // Show error message with retry option
                        container.html(`
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <div class="alert alert-warning d-inline-block" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${errorMessage}
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-sm retry-load-kegiatan" 
                                    data-ekstra-id="${id_ekstrakulikuler}">
                                <i class="fas fa-sync-alt me-2"></i>Coba Lagi
                            </button>
                        </div>
                    </div>
                `);
                    }
                });
            }

            // Function to initialize calendar interactions
            function initializeCalendarInteractions(id_ekstrakulikuler) {
                const container = $(`.kegiatan-data-container-${id_ekstrakulikuler}`);

                // Add hover effects to timeline items
                container.find('.timeline-item').hover(
                    function () {
                        $(this).find('.card').addClass('shadow-lg').css('transform', 'translateY(-5px)');
                    },
                    function () {
                        $(this).find('.card').removeClass('shadow-lg').css('transform', 'translateY(0)');
                    }
                );

                // Add click effects to schedule cards
                container.find('.card.bg-gradient-danger, .card.bg-gradient-warning, .card.bg-gradient-success, .card.bg-gradient-info, .card.bg-gradient-primary, .card.bg-gradient-secondary, .card.bg-gradient-dark').hover(
                    function () {
                        $(this).css('transform', 'translateY(-3px) scale(1.02)');
                    },
                    function () {
                        $(this).css('transform', 'translateY(0) scale(1)');
                    }
                );

                // Add tooltip for schedule duration
                container.find('[data-bs-toggle="tooltip"]').each(function () {
                    new bootstrap.Tooltip(this);
                });

                console.log('Calendar interactions initialized for ID:', id_ekstrakulikuler);
            }

            // Handle retry button click
            $(document).on('click', '.retry-load-kegiatan', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                loadKegiatanData(id_ekstrakulikuler);
            });

            // Add refresh functionality to calendar
            $(document).on('click', '.refresh-calendar-btn', function () {
                const id_ekstrakulikuler = $(this).data('ekstra-id');
                loadKegiatanData(id_ekstrakulikuler);

                // Show temporary feedback
                $(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...');
                setTimeout(() => {
                    $(this).html('<i class="fas fa-sync-alt me-1"></i>Refresh');
                }, 1000);
            });

            // Initialize first ekstrakulikuler kegiatan if tab is active
            const activeKegiatanTab = $('a[data-bs-toggle="list"][href^="#list-kegiatan"].active');
            if (activeKegiatanTab.length > 0) {
                const id_ekstrakulikuler = activeKegiatanTab.attr('href').replace('#list-kegiatan', '');
                console.log('Found active kegiatan tab, loading calendar for ID:', id_ekstrakulikuler);
                loadKegiatanData(id_ekstrakulikuler);
            }

            // Auto-refresh calendar every 5 minutes (optional)
            setInterval(function () {
                if (currentEkstraId && $(`a[data-bs-toggle="list"][href="#list-kegiatan${currentEkstraId}"]`).hasClass('active')) {
                    console.log('Auto-refreshing calendar for ID:', currentEkstraId);
                    loadKegiatanData(currentEkstraId);
                }
            }, 300000); // 5 minutes
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
</body>

</html>