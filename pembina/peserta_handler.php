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


// Check request
$request = $_POST['request'] ?? '';

// Get Peserta
if ($request == 'get_peserta') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Fetch peserta data with siswa information using JOIN
    $query = "SELECT p.*, s.siswa_nama, s.id_user
              FROM tb_peserta p 
              JOIN tb_siswa s ON p.id_user = s.id_user 
              WHERE p.id_ekstrakulikuler = ? 
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
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 5%;">#</th>
                        <th scope="col" style="width: 30%;">Nama Siswa</th>
                        <th scope="col" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($peserta as $p) {
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($p['siswa_nama']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-danger delete-peserta-btn"
                                        data-id="<?php echo $p['id_peserta']; ?>"
                                        data-name="<?php echo htmlspecialchars($p['siswa_nama']); ?>"
                                        data-ekstra-id="<?php echo $p['id_ekstrakulikuler']; ?>">
                                        <i class="fas fa-trash"></i>
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
        <?php
    } else {
        ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>Belum ada peserta yang terdaftar untuk ekstrakulikuler ini.
        </div>
        <?php
    }

    $html = ob_get_clean();
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// Get Available Students
if ($request == 'get_available_students') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Get students who are not already registered in this ekstrakulikuler
    $query = "SELECT s.siswa_nama, s.id_user
              FROM tb_siswa s 
              WHERE s.id_user NOT IN (
                  SELECT p.id_user FROM tb_peserta p WHERE p.id_ekstrakulikuler = ?
              ) 
              ORDER BY s.siswa_nama ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_ekstrakulikuler);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $students]);
    exit;
}

// Create Peserta
if ($request == 'create_peserta') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $id_user = $_POST['id_user'] ?? 0;
    $status = $_POST['status'] ?? 'Aktif';

    if (!$id_ekstrakulikuler || !$id_user) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Check if student is already registered in this ekstrakulikuler
    $check_query = "SELECT id_user FROM tb_peserta WHERE id_ekstrakulikuler = ? AND id_user = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Siswa ini sudah terdaftar sebagai peserta']);
        exit;
    }

    // Get current date for tanggal_bergabung
    $tanggal_bergabung = date("Y-m-d");

    // Insert peserta
    $query = "INSERT INTO tb_peserta (id_ekstrakulikuler, id_user) 
              VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id_ekstrakulikuler, $id_user);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Peserta berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan peserta: ' . $conn->error]);
    }
    exit;
}

// Delete Peserta
if ($request == 'delete_peserta') {
    $id_peserta = $_POST['id_peserta'] ?? 0;

    if (!$id_peserta) {
        echo json_encode(['status' => 'error', 'message' => 'ID Peserta tidak valid']);
        exit;
    }

    // Delete peserta
    $query = "DELETE FROM tb_peserta WHERE id_peserta = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_peserta);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Peserta berhasil dihapus']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus peserta: ' . $conn->error]);
    }
    exit;
}

// If no valid request is found
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>