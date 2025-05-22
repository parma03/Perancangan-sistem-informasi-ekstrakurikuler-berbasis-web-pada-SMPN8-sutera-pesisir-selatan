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

// Get Kegiatan
if ($request == 'get_kegiatan') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $id_user = $_SESSION['id_user'];

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Fetch jadwal rutin untuk ekstrakulikuler
    $query_jadwal = "SELECT 
            e.nama_ekstrakulikuler,
            j.hari,
            j.duty_start,
            j.duty_end,
            p.pembina_nama
        FROM tb_peserta pe
        JOIN tb_ekstrakulikuler e ON pe.id_ekstrakulikuler = e.id_ekstrakulikuler
        JOIN tb_jadwal j ON e.id_ekstrakulikuler = j.id_ekstrakulikuler
        LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id
        WHERE pe.id_user = ? AND pe.id_ekstrakulikuler = ?
        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), j.duty_start";

    $stmt_jadwal = $conn->prepare($query_jadwal);
    $stmt_jadwal->bind_param("ii", $id_user, $id_ekstrakulikuler);
    $stmt_jadwal->execute();
    $result_jadwal = $stmt_jadwal->get_result();

    $jadwal = [];
    $nama_ekstra = '';
    $pembina_nama = '';

    while ($row = $result_jadwal->fetch_assoc()) {
        $jadwal[] = $row;
        if (empty($nama_ekstra)) {
            $nama_ekstra = $row['nama_ekstrakulikuler'];
            $pembina_nama = $row['pembina_nama'];
        }
    }

    // Fetch kegiatan khusus
    $query_kegiatan = "SELECT * FROM tb_kegiatan WHERE id_ekstrakulikuler = ? ORDER BY jadwal DESC";
    $stmt_kegiatan = $conn->prepare($query_kegiatan);
    $stmt_kegiatan->bind_param("i", $id_ekstrakulikuler);
    $stmt_kegiatan->execute();
    $result_kegiatan = $stmt_kegiatan->get_result();

    $kegiatan = [];
    while ($row = $result_kegiatan->fetch_assoc()) {
        $kegiatan[] = $row;
    }

    // Format the output HTML
    ob_start();
    ?>

    <!-- Calendar Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 text-white">
                                <i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($nama_ekstra); ?>
                            </h5>
                            <p class="mb-0 opacity-8">
                                <i class="fas fa-user-tie me-1"></i>
                                Pembina: <?php echo $pembina_nama ? htmlspecialchars($pembina_nama) : 'Belum Ditentukan'; ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-clock fa-2x opacity-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Schedule -->
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="text-primary mb-3">
                <i class="fas fa-calendar-week me-2"></i>Jadwal Rutin Mingguan
            </h6>

            <?php if (count($jadwal) > 0): ?>
                <div class="row g-2">
                    <?php
                    $hari_colors = [
                        'Senin' => 'bg-gradient-danger',
                        'Selasa' => 'bg-gradient-warning',
                        'Rabu' => 'bg-gradient-success',
                        'Kamis' => 'bg-gradient-info',
                        'Jumat' => 'bg-gradient-primary',
                        'Sabtu' => 'bg-gradient-secondary',
                        'Minggu' => 'bg-gradient-dark'
                    ];

                    foreach ($jadwal as $j):
                        $color_class = $hari_colors[$j['hari']] ?? 'bg-gradient-secondary';
                        ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card <?php echo $color_class; ?> text-white mb-2">
                                <div class="card-body p-3 text-center">
                                    <div class="d-flex flex-column">
                                        <h6 class="text-white mb-1">
                                            <i class="fas fa-calendar-day me-1"></i><?php echo $j['hari']; ?>
                                        </h6>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="fas fa-clock me-2"></i>
                                            <span class="font-weight-bold">
                                                <?php echo date('H:i', strtotime($j['duty_start'])); ?> -
                                                <?php echo date('H:i', strtotime($j['duty_end'])); ?>
                                            </span>
                                        </div>
                                        <small class="opacity-8 mt-1">
                                            <?php
                                            $start = new DateTime($j['duty_start']);
                                            $end = new DateTime($j['duty_end']);
                                            $diff = $start->diff($end);
                                            echo $diff->h . ' jam ' . $diff->i . ' menit';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Belum ada jadwal rutin yang ditetapkan untuk ekstrakulikuler ini.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Special Activities -->
    <div class="row">
        <div class="col-12">
            <h6 class="text-primary mb-3">
                <i class="fas fa-star me-2"></i>Kegiatan Khusus
            </h6>

            <?php if (count($kegiatan) > 0): ?>
                <div class="timeline">
                    <?php foreach ($kegiatan as $index => $k):
                        $jadwal_formatted = date("d F Y, H:i", strtotime($k['jadwal']));
                        $is_past = strtotime($k['jadwal']) < time();
                        $timeline_class = $is_past ? 'timeline-item-past' : 'timeline-item-upcoming';
                        $icon_class = $is_past ? 'fas fa-check-circle text-success' : 'fas fa-clock text-warning';
                        ?>
                        <div class="timeline-item <?php echo $timeline_class; ?> mb-3">
                            <div class="timeline-marker">
                                <i class="<?php echo $icon_class; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-1 text-primary">
                                                <i class="fas fa-calendar-check me-2"></i>
                                                <?php echo htmlspecialchars($k['nama_kegiatan']); ?>
                                            </h6>
                                            <span class="badge <?php echo $is_past ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo $is_past ? 'Selesai' : 'Akan Datang'; ?>
                                            </span>
                                        </div>
                                        <p class="card-text text-muted mb-2">
                                            <?php echo htmlspecialchars($k['kegiatan']); ?>
                                        </p>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                                            <small class="text-muted"><?php echo $jadwal_formatted; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Belum ada kegiatan khusus yang dijadwalkan untuk ekstrakulikuler ini.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #007bff, #28a745);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-marker {
            position: absolute;
            left: -22px;
            top: 8px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .timeline-content {
            margin-left: 20px;
        }

        .timeline-item-past .timeline-content .card {
            opacity: 0.8;
            border-left: 4px solid #28a745;
        }

        .timeline-item-upcoming .timeline-content .card {
            border-left: 4px solid #ffc107;
        }

        .card.bg-gradient-danger {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
        }

        .card.bg-gradient-warning {
            background: linear-gradient(45deg, #ffecd2 0%, #fcb69f 100%);
        }

        .card.bg-gradient-success {
            background: linear-gradient(45deg, #a8edea 0%, #fed6e3 100%);
        }

        .card.bg-gradient-info {
            background: linear-gradient(45deg, #74b9ff 0%, #0984e3 100%);
        }

        .card.bg-gradient-primary {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
        }

        .card.bg-gradient-secondary {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
        }

        .card.bg-gradient-dark {
            background: linear-gradient(45deg, #434343 0%, #000000 100%);
        }

        .card:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .timeline {
                padding-left: 20px;
            }

            .timeline-marker {
                left: -15px;
                width: 25px;
                height: 25px;
            }

            .timeline-content {
                margin-left: 15px;
            }
        }
    </style>

    <?php
    $html = ob_get_clean();
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// If no valid request is found
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>