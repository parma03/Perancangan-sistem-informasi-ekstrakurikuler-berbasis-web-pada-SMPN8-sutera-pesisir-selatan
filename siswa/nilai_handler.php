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

// Check request
$request = $_POST['request'] ?? '';

// Get Nilai Data untuk peserta yang sedang login
if ($request == 'get_nilai') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $id_user = $_SESSION['id_user']; // Ambil ID user dari session

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Get data peserta yang sedang login untuk ekstrakulikuler ini
    $query = "SELECT p.id_peserta, p.id_user, s.siswa_nama,
                     n.id_nilai, n.nilai_keaktifan, n.nilai_keterampilan, n.nilai_sikap, n.nilai_akhir,
                     e.nama_ekstrakulikuler, e.periode,
                     COUNT(a.id_absensi) as total_absensi,
                     SUM(CASE WHEN a.keterangan = 'Hadir' THEN 1 ELSE 0 END) as total_hadir,
                     SUM(CASE WHEN a.keterangan = 'Tidak Hadir' THEN 1 ELSE 0 END) as total_tidak_hadir
              FROM tb_peserta p 
              JOIN tb_siswa s ON p.id_user = s.id_user 
              JOIN tb_ekstrakulikuler e ON p.id_ekstrakulikuler = e.id_ekstrakulikuler
              LEFT JOIN tb_nilai n ON p.id_peserta = n.id_peserta
              LEFT JOIN tb_absensi a ON p.id_peserta = a.id_peserta
              WHERE p.id_ekstrakulikuler = ? AND p.id_user = ?
              GROUP BY p.id_peserta, p.id_user, s.siswa_nama, 
                       n.id_nilai, n.nilai_keaktifan, n.nilai_keterampilan, n.nilai_sikap, n.nilai_akhir,
                       e.nama_ekstrakulikuler, e.periode";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    $peserta = $result->fetch_assoc();

    // Format the output HTML
    ob_start();

    if ($peserta) {
        // Calculate attendance data
        $total_absensi = intval($peserta['total_absensi']);
        $total_hadir = intval($peserta['total_hadir']);
        $total_tidak_hadir = intval($peserta['total_tidak_hadir']);
        $persentase_kehadiran = $total_absensi > 0 ? round(($total_hadir / $total_absensi) * 100, 1) : 0;

        // Determine attendance status
        $attendance_status = 'Sangat Baik';
        $attendance_color = 'success';
        $attendance_icon = 'fa-check-circle';

        if ($persentase_kehadiran < 75) {
            $attendance_status = 'Perlu Diperbaiki';
            $attendance_color = 'danger';
            $attendance_icon = 'fa-exclamation-circle';
        } elseif ($persentase_kehadiran < 85) {
            $attendance_status = 'Cukup Baik';
            $attendance_color = 'warning';
            $attendance_icon = 'fa-info-circle';
        }

        // Check if nilai exists
        $has_nilai = !empty($peserta['id_nilai']);
        ?>

        <!-- Header Card -->
        <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-1">
                            <i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($peserta['siswa_nama']); ?>
                        </h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="avatar avatar-xl bg-white text-primary rounded-circle">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nilai Section -->
        <?php if ($has_nilai) { ?>
            <!-- Ada Nilai -->
            <div class="row">
                <!-- Nilai Akhir Card -->
                <div class="col-md-6 mb-4">
                    <div class="card bg-gradient-success text-white h-100">
                        <div class="card-body text-center">
                            <div class="icon icon-lg icon-shape bg-white text-success rounded-circle mb-3 mx-auto">
                                <i class="fas fa-trophy fa-2x"></i>
                            </div>
                            <h2 class="card-title mb-1"><?php echo number_format($peserta['nilai_akhir'], 1); ?></h2>
                            <p class="card-text mb-2">Nilai Akhir</p>
                            <div class="badge bg-white px-3 py-2">
                                <?php
                                $nilai_akhir = floatval($peserta['nilai_akhir']);
                                if ($nilai_akhir >= 85)
                                    echo 'Sangat Baik (A)';
                                elseif ($nilai_akhir >= 75)
                                    echo 'Baik (B)';
                                elseif ($nilai_akhir >= 65)
                                    echo 'Cukup (C)';
                                else
                                    echo 'Kurang (D)';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Komponen Nilai -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-gradient-info text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Rincian Komponen Nilai</h6>
                        </div>
                        <div class="card-body">
                            <!-- Keaktifan -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1 text-primary">
                                        <i class="fas fa-hand-paper me-1"></i>Keaktifan
                                    </h6>
                                    <small class="text-muted">Bobot: 30%</small>
                                </div>
                                <div class="text-end">
                                    <h5 class="mb-0 text-primary"><?php echo $peserta['nilai_keaktifan']; ?></h5>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: <?php echo $peserta['nilai_keaktifan']; ?>%">
                                </div>
                            </div>

                            <!-- Keterampilan -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1 text-info">
                                        <i class="fas fa-tools me-1"></i>Keterampilan
                                    </h6>
                                    <small class="text-muted">Bobot: 40%</small>
                                </div>
                                <div class="text-end">
                                    <h5 class="mb-0 text-info"><?php echo $peserta['nilai_keterampilan']; ?></h5>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: <?php echo $peserta['nilai_keterampilan']; ?>%">
                                </div>
                            </div>

                            <!-- Sikap -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1 text-success">
                                        <i class="fas fa-heart me-1"></i>Sikap
                                    </h6>
                                    <small class="text-muted">Bobot: 30%</small>
                                </div>
                                <div class="text-end">
                                    <h5 class="mb-0 text-success"><?php echo $peserta['nilai_sikap']; ?></h5>
                                </div>
                            </div>
                            <div class="progress mb-0" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $peserta['nilai_sikap']; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php } else { ?>
            <!-- Belum Ada Nilai -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <div class="icon icon-xl icon-shape bg-gradient-warning text-white rounded-circle mx-auto">
                        <i class="fas fa-hourglass-half fa-3x"></i>
                    </div>
                </div>
                <h4 class="text-warning mb-3">Belum Ada Nilai</h4>
                <p class="text-muted mb-4">
                    Nilai untuk ekstrakurikuler <strong><?php echo htmlspecialchars($peserta['nama_ekstrakulikuler']); ?></strong>
                    belum tersedia. Pembina akan memberikan penilaian setelah proses pembelajaran berjalan.
                </p>
                <div class="alert alert-info d-inline-block">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Komponen Penilaian:</strong> Keaktifan (30%), Keterampilan (40%), Sikap (30%)
                </div>
            </div>
        <?php } ?>

        <!-- Data Kehadiran -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-gradient-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Data Kehadiran</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-3">
                                        <div class="icon icon-md icon-shape bg-primary text-white rounded-circle mb-2 mx-auto">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <h4 class="mb-1 text-primary"><?php echo $total_absensi; ?></h4>
                                        <p class="mb-0 text-muted small">Total Pertemuan</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-3">
                                        <div class="icon icon-md icon-shape bg-success text-white rounded-circle mb-2 mx-auto">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <h4 class="mb-1 text-success"><?php echo $total_hadir; ?></h4>
                                        <p class="mb-0 text-muted small">Hadir</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-3">
                                        <div class="icon icon-md icon-shape bg-danger text-white rounded-circle mb-2 mx-auto">
                                            <i class="fas fa-times"></i>
                                        </div>
                                        <h4 class="mb-1 text-danger"><?php echo $total_tidak_hadir; ?></h4>
                                        <p class="mb-0 text-muted small">Tidak Hadir</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-3">
                                        <div
                                            class="icon icon-md icon-shape bg-<?php echo $attendance_color; ?> text-white rounded-circle mb-2 mx-auto">
                                            <i class="fas <?php echo $attendance_icon; ?>"></i>
                                        </div>
                                        <h4 class="mb-1 text-<?php echo $attendance_color; ?>">
                                            <?php echo $persentase_kehadiran; ?>%
                                        </h4>
                                        <p class="mb-0 text-muted small">Persentase Kehadiran</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Kehadiran -->
                        <div class="text-center mt-3">
                            <div class="alert alert-<?php echo $attendance_color; ?> d-inline-block mb-0">
                                <i class="fas <?php echo $attendance_icon; ?> me-2"></i>
                                <strong>Status Kehadiran:</strong> <?php echo $attendance_status; ?>
                            </div>
                        </div>

                        <!-- Progress Bar Kehadiran -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Progress Kehadiran</span>
                                <span
                                    class="text-<?php echo $attendance_color; ?> small font-weight-bold"><?php echo $persentase_kehadiran; ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo $attendance_color; ?>"
                                    style="width: <?php echo $persentase_kehadiran; ?>%" role="progressbar"
                                    aria-valuenow="<?php echo $persentase_kehadiran; ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Motivational Message -->
        <?php if (!$has_nilai) { ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-gradient-light">
                        <div class="card-body text-center">
                            <div class="icon icon-lg icon-shape bg-gradient-primary text-white rounded-circle mb-3 mx-auto">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <h6 class="text-primary mb-2">Tetap Semangat!</h6>
                            <p class="text-muted mb-0">
                                Terus aktif mengikuti kegiatan ekstrakurikuler dan tunjukkan kemampuan terbaikmu.
                                Pembina akan memberikan penilaian berdasarkan partisipasi dan kemajuan yang kamu tunjukkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php
    } else {
        ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <div class="icon icon-xl icon-shape bg-gradient-warning text-white rounded-circle mx-auto">
                    <i class="fas fa-user-times fa-3x"></i>
                </div>
            </div>
            <h4 class="text-warning mb-3">Data Tidak Ditemukan</h4>
            <p class="text-muted mb-0">
                Anda belum terdaftar sebagai peserta untuk ekstrakurikuler ini, atau terjadi kesalahan dalam sistem.
            </p>
        </div>
        <?php
    }

    $html = ob_get_clean();
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// If no valid request is found
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>