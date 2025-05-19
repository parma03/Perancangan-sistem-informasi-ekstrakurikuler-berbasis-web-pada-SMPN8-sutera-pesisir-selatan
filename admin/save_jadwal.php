<?php
session_start();
include '../db/koneksi.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Ambil data
    $id_ekstrakulikuler = $_POST['id_ekstrakulikuler'] ?? 0;
    $hari = $_POST['hari'] ?? '';
    $duty_start = $_POST['duty_start'] ?? '';
    $duty_end = $_POST['duty_end'] ?? '';

    // Validasi
    if (empty($id_ekstrakulikuler) || empty($hari) || empty($duty_start) || empty($duty_end)) {
        $response['message'] = 'Data tidak lengkap';
        echo json_encode($response);
        exit;
    }

    // Insert ke database
    $query = "INSERT INTO tb_jadwal (id_ekstrakulikuler, hari, duty_start, duty_end) 
              VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $id_ekstrakulikuler, $hari, $duty_start, $duty_end);

    if ($stmt->execute()) {
        $jadwal_id = $stmt->insert_id;
        $response = [
            'success' => true,
            'message' => 'Jadwal berhasil disimpan',
            'jadwal_id' => $jadwal_id
        ];
    } else {
        $response['message'] = 'Gagal menyimpan jadwal: ' . $stmt->error;
    }

    $stmt->close();

    // Kirim response
    echo json_encode($response);
    exit;
} else {
    // Method tidak diizinkan
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
?>