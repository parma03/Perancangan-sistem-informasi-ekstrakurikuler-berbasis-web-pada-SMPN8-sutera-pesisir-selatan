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

// Get Validasi Peserta
if ($request == 'get_validasi') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Fetch validasi data with siswa information using JOIN
    $query = "SELECT v.*, s.siswa_nama, s.id_user
              FROM tb_validasi v 
              JOIN tb_siswa s ON v.id_user = s.id_user 
              WHERE v.id_ekstrakulikuler = ? 
              ORDER BY s.siswa_nama ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_ekstrakulikuler);
    $stmt->execute();
    $result = $stmt->get_result();

    $validasi = [];
    while ($row = $result->fetch_assoc()) {
        $validasi[] = $row;
    }

    // Format the output HTML
    ob_start();

    if (count($validasi) > 0) {
        ?>
        <form id="validasi-form-<?php echo $id_ekstrakulikuler; ?>">
            <div class="mb-3">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-success accept-selected-btn"
                        data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                        <i class="fas fa-check me-1"></i> Terima Terpilih
                    </button>
                    <button type="button" class="btn btn-danger reject-selected-btn"
                        data-ekstra-id="<?php echo $id_ekstrakulikuler; ?>">
                        <i class="fas fa-times me-1"></i> Tolak Terpilih
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">
                                <div class="form-check">
                                    <input class="form-check-input select-all-validasi" type="checkbox" value=""
                                        id="select-all-<?php echo $id_ekstrakulikuler; ?>">
                                </div>
                            </th>
                            <th scope="col" style="width: 5%;">#</th>
                            <th scope="col" style="width: 45%;">Nama Siswa</th>
                            <th scope="col" style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($validasi as $v) {
                            ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input validasi-checkbox" type="checkbox"
                                            value="<?php echo $v['id_validasi']; ?>" name="validasi_ids[]">
                                    </div>
                                </td>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($v['siswa_nama']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-success accept-validasi-btn"
                                            data-id="<?php echo $v['id_validasi']; ?>" data-user-id="<?php echo $v['id_user']; ?>"
                                            data-name="<?php echo htmlspecialchars($v['siswa_nama']); ?>"
                                            data-ekstra-id="<?php echo $v['id_ekstrakulikuler']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger reject-validasi-btn"
                                            data-id="<?php echo $v['id_validasi']; ?>"
                                            data-name="<?php echo htmlspecialchars($v['siswa_nama']); ?>"
                                            data-ekstra-id="<?php echo $v['id_ekstrakulikuler']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?php
    } else {
        ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>Belum ada permintaan validasi peserta untuk ekstrakulikuler ini.
        </div>
        <?php
    }

    $html = ob_get_clean();
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// Accept Validasi (Single)
if ($request == 'accept_validasi') {
    $id_validasi = $_POST['id_validasi'] ?? 0;

    if (!$id_validasi) {
        echo json_encode(['status' => 'error', 'message' => 'ID Validasi tidak valid']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get validasi data
        $query = "SELECT id_ekstrakulikuler, id_user FROM tb_validasi WHERE id_validasi = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_validasi);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception("Data validasi tidak ditemukan");
        }

        $validasi_data = $result->fetch_assoc();
        $id_ekstrakulikuler = $validasi_data['id_ekstrakulikuler'];
        $id_user = $validasi_data['id_user'];

        // Check if already registered as peserta
        $check_query = "SELECT id_peserta FROM tb_peserta 
                       WHERE id_ekstrakulikuler = ? AND id_user = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Already registered, just delete the validation
            $delete_query = "DELETE FROM tb_validasi WHERE id_validasi = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $id_validasi);
            $delete_stmt->execute();
        } else {
            // Insert to peserta table
            $insert_query = "INSERT INTO tb_peserta (id_ekstrakulikuler, id_user) 
                           VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);
            $insert_stmt->execute();

            // Delete from validasi table
            $delete_query = "DELETE FROM tb_validasi WHERE id_validasi = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $id_validasi);
            $delete_stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Peserta berhasil diterima']);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses: ' . $e->getMessage()]);
    }

    exit;
}

// Reject Validasi (Single)
if ($request == 'reject_validasi') {
    $id_validasi = $_POST['id_validasi'] ?? 0;

    if (!$id_validasi) {
        echo json_encode(['status' => 'error', 'message' => 'ID Validasi tidak valid']);
        exit;
    }

    // Delete from validasi
    $query = "DELETE FROM tb_validasi WHERE id_validasi = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_validasi);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Permintaan peserta berhasil ditolak']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menolak permintaan: ' . $conn->error]);
    }
    exit;
}

// Accept Multiple Validasi
if ($request == 'accept_multiple_validasi') {
    // Get array of validation IDs
    $validasi_ids = $_POST['validasi_ids'] ?? [];

    if (empty($validasi_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data validasi yang dipilih']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        $success_count = 0;

        foreach ($validasi_ids as $id_validasi) {
            // Get validasi data
            $query = "SELECT id_ekstrakulikuler, id_user FROM tb_validasi WHERE id_validasi = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_validasi);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                continue; // Skip if not found
            }

            $validasi_data = $result->fetch_assoc();
            $id_ekstrakulikuler = $validasi_data['id_ekstrakulikuler'];
            $id_user = $validasi_data['id_user'];

            // Check if already registered as peserta
            $check_query = "SELECT id_peserta FROM tb_peserta 
                           WHERE id_ekstrakulikuler = ? AND id_user = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                // Insert to peserta table if not already there
                $insert_query = "INSERT INTO tb_peserta (id_ekstrakulikuler, id_user) 
                               VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);
                $insert_stmt->execute();
            }

            // Delete from validasi table
            $delete_query = "DELETE FROM tb_validasi WHERE id_validasi = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $id_validasi);
            $delete_stmt->execute();

            $success_count++;
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => "$success_count peserta berhasil diterima"
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses: ' . $e->getMessage()]);
    }

    exit;
}

// Reject Multiple Validasi
if ($request == 'reject_multiple_validasi') {
    // Get array of validation IDs
    $validasi_ids = $_POST['validasi_ids'] ?? [];

    if (empty($validasi_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data validasi yang dipilih']);
        exit;
    }

    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($validasi_ids), '?'));

    // Delete from validasi
    $query = "DELETE FROM tb_validasi WHERE id_validasi IN ($placeholders)";
    $stmt = $conn->prepare($query);

    // Bind parameters dynamically
    $types = str_repeat('i', count($validasi_ids));
    $stmt->bind_param($types, ...$validasi_ids);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => count($validasi_ids) . " permintaan peserta berhasil ditolak"
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menolak permintaan: ' . $conn->error]);
    }
    exit;
}

// If no valid request is found
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>