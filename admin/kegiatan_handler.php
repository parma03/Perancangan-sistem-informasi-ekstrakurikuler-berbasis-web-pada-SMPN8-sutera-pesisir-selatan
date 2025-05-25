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

// Get Kegiatan
if ($request == 'get_kegiatan') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;

    if (!$id_ekstrakulikuler) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ekstrakulikuler tidak valid']);
        exit;
    }

    // Fetch kegiatan data
    $query = "SELECT * FROM tb_kegiatan WHERE id_ekstrakulikuler = ? ORDER BY jadwal DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_ekstrakulikuler);
    $stmt->execute();
    $result = $stmt->get_result();

    $kegiatan = [];
    while ($row = $result->fetch_assoc()) {
        $kegiatan[] = $row;
    }

    // Format the output HTML
    ob_start();

    if (count($kegiatan) > 0) {
        ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 5%;">#</th>
                        <th scope="col" style="width: 25%;">Nama Kegiatan</th>
                        <th scope="col" style="width: 25%;">Deskripsi Kegiatan</th>
                        <th scope="col" style="width: 20%;">Jadwal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($kegiatan as $k) {
                        // Format tanggal to Indonesian format
                        $jadwal = date("d F Y", strtotime($k['jadwal']));
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($k['nama_kegiatan']); ?></td>
                            <td><?php echo htmlspecialchars($k['kegiatan']); ?></td>
                            <td><?php echo $jadwal; ?></td>
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
            <i class="fas fa-info-circle me-2"></i>Belum ada kegiatan yang tersimpan untuk ekstrakulikuler ini.
        </div>
        <?php
    }

    $html = ob_get_clean();
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// Create Kegiatan
if ($request == 'create_kegiatan') {
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $nama_kegiatan = $_POST['nama_kegiatan'] ?? '';
    $kegiatan = $_POST['kegiatan'] ?? '';  // Changed from deskripsi_kegiatan to match the form field
    $jadwal = $_POST['jadwal'] ?? '';

    if (!$id_ekstrakulikuler || !$nama_kegiatan || !$jadwal) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Insert kegiatan
    $query = "INSERT INTO tb_kegiatan (id_ekstrakulikuler, nama_kegiatan, kegiatan, jadwal) 
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $id_ekstrakulikuler, $nama_kegiatan, $kegiatan, $jadwal);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Kegiatan berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan kegiatan: ' . $conn->error]);
    }
    exit;
}

// Update Kegiatan
if ($request == 'update_kegiatan') {
    $id_kegiatan = $_POST['id_kegiatan'] ?? 0;
    $nama_kegiatan = $_POST['nama_kegiatan'] ?? '';
    $kegiatan = $_POST['kegiatan'] ?? '';
    $jadwal = $_POST['jadwal'] ?? '';

    if (!$id_kegiatan || !$nama_kegiatan || !$jadwal) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Update kegiatan
    $query = "UPDATE tb_kegiatan 
              SET nama_kegiatan = ?, kegiatan = ?, jadwal = ? WHERE id_kegiatan = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $nama_kegiatan, $kegiatan, $jadwal, $id_kegiatan);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Kegiatan berhasil diperbarui']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui kegiatan: ' . $conn->error]);
    }
    exit;
}

// Delete Kegiatan
if ($request == 'delete_kegiatan') {
    $id_kegiatan = $_POST['id_kegiatan'] ?? 0;

    if (!$id_kegiatan) {
        echo json_encode(['status' => 'error', 'message' => 'ID Kegiatan tidak valid']);
        exit;
    }

    // Delete kegiatan
    $query = "DELETE FROM tb_kegiatan WHERE id_kegiatan = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_kegiatan);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Kegiatan berhasil dihapus']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus kegiatan: ' . $conn->error]);
    }
    exit;
}

// If no valid request is found
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>