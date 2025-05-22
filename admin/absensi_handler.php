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

// Check request
$request = $_POST['request'] ?? '';

// Get Absensi Data
if ($request == 'get_absensi') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    $today = date('Y-m-d');

    // Get all peserta for this ekstrakulikuler with their attendance status for today
    $query = "SELECT p.id_peserta, p.id_user, s.siswa_nama,
                     a.id_absensi, a.keterangan, a.tanggal
              FROM tb_peserta p 
              JOIN tb_siswa s ON p.id_user = s.id_user 
              LEFT JOIN tb_absensi a ON p.id_peserta = a.id_peserta AND DATE(a.tanggal) = ?
              WHERE p.id_ekstrakulikuler = ? 
              ORDER BY s.siswa_nama ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $today, $id_ekstrakulikuler);
    $stmt->execute();
    $result = $stmt->get_result();

    $peserta = [];
    while ($row = $result->fetch_assoc()) {
        $peserta[] = $row;
    }

    // Format the output HTML
    ob_start();

    if (count($peserta) > 0) {
        ?>
        <form id="absensi-form-<?php echo $id_ekstrakulikuler; ?>">
            <div class="mb-3">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-success hadir-selected-btn"
                        data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                        <i class="fas fa-check me-1"></i> Tandai Hadir Terpilih
                    </button>
                    <button type="button" class="btn btn-danger tidak-hadir-selected-btn"
                        data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                        <i class="fas fa-times me-1"></i> Tandai Tidak Hadir Terpilih
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">
                                <div class="form-check">
                                    <input class="form-check-input select-all-absensi" type="checkbox" value=""
                                        id="select-all-absensi-<?php echo $id_ekstrakulikuler; ?>">
                                </div>
                            </th>
                            <th scope="col" style="width: 5%;">#</th>
                            <th scope="col" style="width: 35%;">Nama Siswa</th>
                            <th scope="col" style="width: 15%;">Status Absensi</th>
                            <th scope="col" style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($peserta as $p) {
                            $status_class = '';
                            $status_text = 'Belum Absensi';
                            $status_icon = 'fas fa-question-circle';

                            if ($p['id_absensi']) {
                                if ($p['keterangan'] == 'Hadir') {
                                    $status_class = 'text-success';
                                    $status_text = 'Hadir';
                                    $status_icon = 'fas fa-check-circle';
                                } else {
                                    $status_class = 'text-danger';
                                    $status_text = 'Tidak Hadir';
                                    $status_icon = 'fas fa-times-circle';
                                }
                            } else {
                                $status_class = 'text-warning';
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php if (!$p['id_absensi']) { ?>
                                        <div class="form-check">
                                            <input class="form-check-input absensi-checkbox" type="checkbox"
                                                value="<?php echo $p['id_peserta']; ?>" name="peserta_ids[]">
                                        </div>
                                    <?php } ?>
                                </td>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($p['siswa_nama']); ?></td>
                                <td>
                                    <span class="<?php echo $status_class; ?>">
                                        <i class="<?php echo $status_icon; ?> me-1"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$p['id_absensi']) { ?>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-success hadir-absensi-btn"
                                                data-id="<?php echo $p['id_peserta']; ?>"
                                                data-name="<?php echo htmlspecialchars($p['siswa_nama']); ?>"
                                                data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                                                <i class="fas fa-check"></i> Hadir
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger tidak-hadir-absensi-btn"
                                                data-id="<?php echo $p['id_peserta']; ?>"
                                                data-name="<?php echo htmlspecialchars($p['siswa_nama']); ?>"
                                                data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                                                <i class="fas fa-times"></i> Tidak Hadir
                                            </button>
                                        </div>
                                    <?php } else { ?>
                                        <span class="text-muted">
                                            <i class="fas fa-lock me-1"></i>Sudah Tercatat
                                        </span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Ringkasan Absensi Hari Ini</h6>
                        <?php
                        $total_peserta = count($peserta);
                        $hadir = 0;
                        $tidak_hadir = 0;
                        $belum_absensi = 0;

                        foreach ($peserta as $p) {
                            if ($p['id_absensi']) {
                                if ($p['keterangan'] == 'Hadir') {
                                    $hadir++;
                                } else {
                                    $tidak_hadir++;
                                }
                            } else {
                                $belum_absensi++;
                            }
                        }
                        ?>
                        <div class="row text-center">
                            <div class="col-3">
                                <strong class="text-primary"><?php echo $total_peserta; ?></strong><br>
                                <small>Total Peserta</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-success"><?php echo $hadir; ?></strong><br>
                                <small>Hadir</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-danger"><?php echo $tidak_hadir; ?></strong><br>
                                <small>Tidak Hadir</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-warning"><?php echo $belum_absensi; ?></strong><br>
                                <small>Belum Absensi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    } else {
        ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>Belum ada peserta terdaftar untuk ekstrakulikuler ini.
        </div>
        <?php
    }

    $html = ob_get_clean();
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// Mark Single Attendance as Present
if ($request == 'mark_hadir') {
    $id_peserta = $_POST['id_peserta'] ?? 0;
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_peserta || !$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
        exit;
    }

    $today = date('Y-m-d H:i:s');

    // Check if already marked today
    $check_query = "SELECT id_absensi FROM tb_absensi 
                   WHERE id_peserta = ? AND DATE(tanggal) = DATE(?)";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $id_peserta, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Peserta sudah melakukan absensi hari ini']);
        exit;
    }

    // Insert attendance record
    $insert_query = "INSERT INTO tb_absensi (id_peserta, tanggal, keterangan) 
                    VALUES (?, ?, 'Hadir')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("is", $id_peserta, $today);

    if ($insert_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil menandai peserta hadir']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menandai kehadiran: ' . $conn->error]);
    }
    exit;
}

// Mark Single Attendance as Absent
if ($request == 'mark_tidak_hadir') {
    $id_peserta = $_POST['id_peserta'] ?? 0;
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_peserta || !$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
        exit;
    }

    $today = date('Y-m-d H:i:s');

    // Check if already marked today
    $check_query = "SELECT id_absensi FROM tb_absensi 
                   WHERE id_peserta = ? AND DATE(tanggal) = DATE(?)";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $id_peserta, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Peserta sudah melakukan absensi hari ini']);
        exit;
    }

    // Insert attendance record
    $insert_query = "INSERT INTO tb_absensi (id_peserta, tanggal, keterangan) 
                    VALUES (?, ?, 'Tidak Hadir')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("is", $id_peserta, $today);

    if ($insert_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil menandai peserta tidak hadir']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menandai ketidakhadiran: ' . $conn->error]);
    }
    exit;
}

// Mark Multiple Attendance as Present
if ($request == 'mark_multiple_hadir') {
    $peserta_ids = $_POST['peserta_ids'] ?? [];
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (empty($peserta_ids) || !$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid atau tidak ada peserta yang dipilih']);
        exit;
    }

    $today = date('Y-m-d H:i:s');
    $success_count = 0;
    $already_marked = 0;

    // Begin transaction
    $conn->begin_transaction();

    try {
        foreach ($peserta_ids as $id_peserta) {
            // Check if already marked today
            $check_query = "SELECT id_absensi FROM tb_absensi 
                           WHERE id_peserta = ? AND DATE(tanggal) = DATE(?)";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("is", $id_peserta, $today);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $already_marked++;
                continue;
            }

            // Insert attendance record
            $insert_query = "INSERT INTO tb_absensi (id_peserta, tanggal, keterangan) 
                            VALUES (?, ?, 'Hadir')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("is", $id_peserta, $today);

            if ($insert_stmt->execute()) {
                $success_count++;
            }
        }

        // Commit transaction
        $conn->commit();

        $message = "$success_count peserta berhasil ditandai hadir";
        if ($already_marked > 0) {
            $message .= " ($already_marked peserta sudah melakukan absensi sebelumnya)";
        }

        echo json_encode(['status' => 'success', 'message' => $message]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses: ' . $e->getMessage()]);
    }

    exit;
}

// Mark Multiple Attendance as Absent
if ($request == 'mark_multiple_tidak_hadir') {
    $peserta_ids = $_POST['peserta_ids'] ?? [];
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (empty($peserta_ids) || !$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid atau tidak ada peserta yang dipilih']);
        exit;
    }

    $today = date('Y-m-d H:i:s');
    $success_count = 0;
    $already_marked = 0;

    // Begin transaction
    $conn->begin_transaction();

    try {
        foreach ($peserta_ids as $id_peserta) {
            // Check if already marked today
            $check_query = "SELECT id_absensi FROM tb_absensi 
                           WHERE id_peserta = ? AND DATE(tanggal) = DATE(?)";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("is", $id_peserta, $today);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $already_marked++;
                continue;
            }

            // Insert attendance record
            $insert_query = "INSERT INTO tb_absensi (id_peserta, tanggal, keterangan) 
                            VALUES (?, ?, 'Tidak Hadir')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("is", $id_peserta, $today);

            if ($insert_stmt->execute()) {
                $success_count++;
            }
        }

        // Commit transaction
        $conn->commit();

        $message = "$success_count peserta berhasil ditandai tidak hadir";
        if ($already_marked > 0) {
            $message .= " ($already_marked peserta sudah melakukan absensi sebelumnya)";
        }

        echo json_encode(['status' => 'success', 'message' => $message]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses: ' . $e->getMessage()]);
    }

    exit;
}

// If no valid request is found
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>