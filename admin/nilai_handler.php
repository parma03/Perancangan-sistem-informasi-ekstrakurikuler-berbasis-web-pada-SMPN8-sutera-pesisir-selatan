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

// Get Nilai Data
if ($request == 'get_nilai') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Get all peserta for this ekstrakulikuler with their nilai and absensi data
    $query = "SELECT p.id_peserta, p.id_user, s.siswa_nama,
                     n.id_nilai, n.nilai_keaktifan, n.nilai_keterampilan, n.nilai_sikap, n.nilai_akhir,
                     COUNT(a.id_absensi) as total_absensi,
                     SUM(CASE WHEN a.keterangan = 'Hadir' THEN 1 ELSE 0 END) as total_hadir,
                     SUM(CASE WHEN a.keterangan = 'Tidak Hadir' THEN 1 ELSE 0 END) as total_tidak_hadir
              FROM tb_peserta p 
              JOIN tb_siswa s ON p.id_user = s.id_user 
              LEFT JOIN tb_nilai n ON p.id_peserta = n.id_peserta
              LEFT JOIN tb_absensi a ON p.id_peserta = a.id_peserta
              WHERE p.id_ekstrakulikuler = ? 
              GROUP BY p.id_peserta, p.id_user, s.siswa_nama, n.id_nilai, n.nilai_keaktifan, n.nilai_keterampilan, n.nilai_sikap, n.nilai_akhir
              ORDER BY s.siswa_nama ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_ekstrakulikuler);
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
        <form id="nilai-form-<?php echo $id_ekstrakulikuler; ?>">
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="alert alert-info mb-0 py-2">
                        <small><i class="fas fa-info-circle me-1"></i>
                        <strong>Komponen Penilaian:</strong> Keaktifan (30%), Keterampilan (40%), Sikap (30%)</small>
                    </div>
                    <button type="button" class="btn btn-success save-all-nilai-btn"
                        data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                        <i class="fas fa-save me-1"></i> Simpan Semua Nilai
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" style="width: 5%;">#</th>
                            <th scope="col" style="width: 20%;">Nama Siswa</th>
                            <th scope="col" style="width: 15%;">Data Absensi</th>
                            <th scope="col" style="width: 12%;">Keaktifan<br><small>(0-100)</small></th>
                            <th scope="col" style="width: 12%;">Keterampilan<br><small>(0-100)</small></th>
                            <th scope="col" style="width: 12%;">Sikap<br><small>(0-100)</small></th>
                            <th scope="col" style="width: 12%;">Nilai Akhir</th>
                            <th scope="col" style="width: 12%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($peserta as $p) {
                            // Calculate attendance percentage
                            $total_absensi = intval($p['total_absensi']);
                            $total_hadir = intval($p['total_hadir']);
                            $total_tidak_hadir = intval($p['total_tidak_hadir']);
                            $persentase_kehadiran = $total_absensi > 0 ? round(($total_hadir / $total_absensi) * 100, 1) : 0;
                            
                            // Determine attendance color
                            $attendance_class = 'text-success';
                            if ($persentase_kehadiran < 75) {
                                $attendance_class = 'text-danger';
                            } elseif ($persentase_kehadiran < 85) {
                                $attendance_class = 'text-warning';
                            }

                            // Get existing nilai or set defaults
                            $nilai_keaktifan = $p['nilai_keaktifan'] ?? '';
                            $nilai_keterampilan = $p['nilai_keterampilan'] ?? '';
                            $nilai_sikap = $p['nilai_sikap'] ?? '';
                            $nilai_akhir = $p['nilai_akhir'] ?? '';

                            // Determine if nilai is already saved
                            $is_saved = !empty($p['id_nilai']);
                            ?>
                            <tr class="nilai-row" data-peserta-id="<?php echo $p['id_peserta']; ?>">
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['siswa_nama']); ?></strong>
                                    <?php if ($is_saved) { ?>
                                        <br><small class="text-success"><i class="fas fa-check-circle"></i> Sudah dinilai</small>
                                    <?php } else { ?>
                                        <br><small class="text-muted"><i class="fas fa-clock"></i> Belum dinilai</small>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="<?php echo $attendance_class; ?> fw-bold">
                                            <?php echo $persentase_kehadiran; ?>%
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $total_hadir; ?>/<?php echo $total_absensi; ?> hadir<br>
                                            <?php echo $total_tidak_hadir; ?> tidak hadir
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" 
                                           class="form-control form-control-sm nilai-keaktifan" 
                                           id="nilai_keaktifan_<?php echo $p['id_peserta']; ?>"
                                           value="<?php echo $nilai_keaktifan; ?>"
                                           min="0" max="100" 
                                           placeholder="0-100"
                                           <?php echo $is_saved ? 'readonly' : ''; ?>>
                                </td>
                                <td>
                                    <input type="number" 
                                           class="form-control form-control-sm nilai-keterampilan" 
                                           id="nilai_keterampilan_<?php echo $p['id_peserta']; ?>"
                                           value="<?php echo $nilai_keterampilan; ?>"
                                           min="0" max="100" 
                                           placeholder="0-100"
                                           <?php echo $is_saved ? 'readonly' : ''; ?>>
                                </td>
                                <td>
                                    <input type="number" 
                                           class="form-control form-control-sm nilai-sikap" 
                                           id="nilai_sikap_<?php echo $p['id_peserta']; ?>"
                                           value="<?php echo $nilai_sikap; ?>"
                                           min="0" max="100" 
                                           placeholder="0-100"
                                           <?php echo $is_saved ? 'readonly' : ''; ?>>
                                </td>
                                <td class="text-center">
                                    <?php if ($is_saved) { ?>
                                        <div class="fw-bold text-primary">
                                            <?php echo number_format($nilai_akhir, 1); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php 
                                            if ($nilai_akhir >= 85) echo '<span class="text-success">Sangat Baik</span>';
                                            elseif ($nilai_akhir >= 75) echo '<span class="text-info">Baik</span>';
                                            elseif ($nilai_akhir >= 65) echo '<span class="text-warning">Cukup</span>';
                                            else echo '<span class="text-danger">Kurang</span>';
                                            ?>
                                        </small>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (!$is_saved) { ?>
                                        <button type="button" class="btn btn-sm btn-primary save-nilai-btn"
                                            data-id="<?php echo $p['id_peserta']; ?>"
                                            data-name="<?php echo htmlspecialchars($p['siswa_nama']); ?>"
                                            data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                                            <i class="fas fa-save"></i> Simpan
                                        </button>
                                    <?php } else { ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> Tersimpan
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
                    <div class="alert alert-secondary">
                        <h6 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Ringkasan Penilaian</h6>
                        <?php
                        $total_peserta = count($peserta);
                        $sudah_dinilai = 0;
                        $belum_dinilai = 0;
                        $rata_rata_nilai = 0;
                        $total_nilai = 0;
                        
                        foreach ($peserta as $p) {
                            if (!empty($p['id_nilai'])) {
                                $sudah_dinilai++;
                                $total_nilai += floatval($p['nilai_akhir']);
                            } else {
                                $belum_dinilai++;
                            }
                        }
                        
                        $rata_rata_nilai = $sudah_dinilai > 0 ? $total_nilai / $sudah_dinilai : 0;
                        ?>
                        <div class="row text-center">
                            <div class="col-3">
                                <strong class="text-primary"><?php echo $total_peserta; ?></strong><br>
                                <small>Total Peserta</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-success"><?php echo $sudah_dinilai; ?></strong><br>
                                <small>Sudah Dinilai</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-warning"><?php echo $belum_dinilai; ?></strong><br>
                                <small>Belum Dinilai</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-info"><?php echo number_format($rata_rata_nilai, 1); ?></strong><br>
                                <small>Rata-rata Nilai</small>
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

// Save Single Nilai
if ($request == 'save_nilai') {
    $id_peserta = $_POST['id_peserta'] ?? 0;
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $nilai_keaktifan = $_POST['nilai_keaktifan'] ?? 0;
    $nilai_keterampilan = $_POST['nilai_keterampilan'] ?? 0;
    $nilai_sikap = $_POST['nilai_sikap'] ?? 0;

    if (!$id_peserta || !$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
        exit;
    }

    // Validate nilai range
    if ($nilai_keaktifan < 0 || $nilai_keaktifan > 100 ||
        $nilai_keterampilan < 0 || $nilai_keterampilan > 100 ||
        $nilai_sikap < 0 || $nilai_sikap > 100) {
        echo json_encode(['status' => 'error', 'message' => 'Nilai harus antara 0-100']);
        exit;
    }

    // Calculate final score (Keaktifan 30%, Keterampilan 40%, Sikap 30%)
    $nilai_akhir = ($nilai_keaktifan * 0.3) + ($nilai_keterampilan * 0.4) + ($nilai_sikap * 0.3);

    // Check if nilai already exists
    $check_query = "SELECT id_nilai FROM tb_nilai WHERE id_peserta = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $id_peserta);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update existing nilai
        $update_query = "UPDATE tb_nilai SET 
                        nilai_keaktifan = ?, 
                        nilai_keterampilan = ?, 
                        nilai_sikap = ?, 
                        nilai_akhir = ?,
                        tanggal_input = NOW()
                        WHERE id_peserta = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ddddi", $nilai_keaktifan, $nilai_keterampilan, $nilai_sikap, $nilai_akhir, $id_peserta);
        
        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Berhasil memperbarui nilai peserta']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui nilai: ' . $conn->error]);
        }
    } else {
        // Insert new nilai
        $insert_query = "INSERT INTO tb_nilai (id_peserta, nilai_keaktifan, nilai_keterampilan, nilai_sikap, nilai_akhir, tanggal_input) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("idddd", $id_peserta, $nilai_keaktifan, $nilai_keterampilan, $nilai_sikap, $nilai_akhir);

        if ($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Berhasil menyimpan nilai peserta']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan nilai: ' . $conn->error]);
        }
    }
    exit;
}

// Save All Nilai
if ($request == 'save_all_nilai') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $nilai_data = json_decode($_POST['nilai_data'] ?? '[]', true);

    if (!$id_ekstrakulikuler || empty($nilai_data)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid atau kosong']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();
    $success_count = 0;
    $update_count = 0;
    $insert_count = 0;

    try {
        foreach ($nilai_data as $data) {
            $id_peserta = $data['id_peserta'];
            $nilai_keaktifan = $data['nilai_keaktifan'];
            $nilai_keterampilan = $data['nilai_keterampilan'];
            $nilai_sikap = $data['nilai_sikap'];

            // Validate nilai range
            if ($nilai_keaktifan < 0 || $nilai_keaktifan > 100 ||
                $nilai_keterampilan < 0 || $nilai_keterampilan > 100 ||
                $nilai_sikap < 0 || $nilai_sikap > 100) {
                throw new Exception("Nilai harus antara 0-100 untuk peserta ID: $id_peserta");
            }

            // Calculate final score
            $nilai_akhir = ($nilai_keaktifan * 0.3) + ($nilai_keterampilan * 0.4) + ($nilai_sikap * 0.3);

            // Check if nilai already exists
            $check_query = "SELECT id_nilai FROM tb_nilai WHERE id_peserta = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $id_peserta);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // Update existing nilai
                $update_query = "UPDATE tb_nilai SET 
                                nilai_keaktifan = ?, 
                                nilai_keterampilan = ?, 
                                nilai_sikap = ?, 
                                nilai_akhir = ?,
                                tanggal_input = NOW()
                                WHERE id_peserta = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ddddi", $nilai_keaktifan, $nilai_keterampilan, $nilai_sikap, $nilai_akhir, $id_peserta);
                
                if ($update_stmt->execute()) {
                    $update_count++;
                    $success_count++;
                }
            } else {
                // Insert new nilai
                $insert_query = "INSERT INTO tb_nilai (id_peserta, nilai_keaktifan, nilai_keterampilan, nilai_sikap, nilai_akhir, tanggal_input) 
                                VALUES (?, ?, ?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("idddd", $id_peserta, $nilai_keaktifan, $nilai_keterampilan, $nilai_sikap, $nilai_akhir);

                if ($insert_stmt->execute()) {
                    $insert_count++;
                    $success_count++;
                }
            }
        }

        // Commit transaction
        $conn->commit();

        $message = "Berhasil memproses $success_count nilai";
        if ($insert_count > 0 && $update_count > 0) {
            $message .= " ($insert_count baru, $update_count diperbarui)";
        } elseif ($insert_count > 0) {
            $message .= " ($insert_count nilai baru)";
        } elseif ($update_count > 0) {
            $message .= " ($update_count nilai diperbarui)";
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