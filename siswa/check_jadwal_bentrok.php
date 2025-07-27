<?php
// FILE: check_jadwal_bentrok.php
session_start();
include '../db/koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['bentrok' => false, 'detail' => []]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_ekstrakulikuler']) && isset($_POST['id_user'])) {
    $id_user = $_POST['id_user'];
    $id_ekstrakulikuler_baru = $_POST['id_ekstrakulikuler'];

    // Fungsi yang diperbaiki untuk mengecek jadwal bentrok
    function cekJadwalBentrok($conn, $id_user, $id_ekstrakulikuler_baru)
    {
        // Query untuk mendapatkan jadwal ekstrakurikuler yang akan didaftar
        $query_jadwal_baru = "SELECT hari, duty_start, duty_end FROM tb_jadwal WHERE id_ekstrakulikuler = ?";
        $stmt_jadwal_baru = $conn->prepare($query_jadwal_baru);
        $stmt_jadwal_baru->bind_param("i", $id_ekstrakulikuler_baru);
        $stmt_jadwal_baru->execute();
        $result_jadwal_baru = $stmt_jadwal_baru->get_result();

        $jadwal_baru = [];
        while ($row = $result_jadwal_baru->fetch_assoc()) {
            $jadwal_baru[] = $row;
        }
        $stmt_jadwal_baru->close();

        if (empty($jadwal_baru)) {
            return ['bentrok' => false, 'detail' => []];
        }

        // Query gabungan untuk mendapatkan jadwal ekstrakurikuler yang sudah diikuti (tb_peserta) 
        // DAN yang sudah didaftar tapi belum divalidasi (tb_validasi)
        $query_jadwal_existing = "
            SELECT te.nama_ekstrakulikuler, tj.hari, tj.duty_start, tj.duty_end, 'peserta' as status
            FROM tb_peserta tp
            JOIN tb_ekstrakulikuler te ON tp.id_ekstrakulikuler = te.id_ekstrakulikuler
            JOIN tb_jadwal tj ON te.id_ekstrakulikuler = tj.id_ekstrakulikuler
            WHERE tp.id_user = ?
            
            UNION
            
            SELECT te.nama_ekstrakulikuler, tj.hari, tj.duty_start, tj.duty_end, 'validasi' as status
            FROM tb_validasi tv
            JOIN tb_ekstrakulikuler te ON tv.id_ekstrakulikuler = te.id_ekstrakulikuler
            JOIN tb_jadwal tj ON te.id_ekstrakulikuler = tj.id_ekstrakulikuler
            WHERE tv.id_user = ?
        ";
        $stmt_jadwal_existing = $conn->prepare($query_jadwal_existing);
        $stmt_jadwal_existing->bind_param("ii", $id_user, $id_user);
        $stmt_jadwal_existing->execute();
        $result_jadwal_existing = $stmt_jadwal_existing->get_result();

        $jadwal_bentrok = [];

        while ($existing = $result_jadwal_existing->fetch_assoc()) {
            foreach ($jadwal_baru as $baru) {
                // Cek apakah hari sama
                if (strtolower($existing['hari']) === strtolower($baru['hari'])) {
                    // Cek apakah waktu bentrok
                    $start_existing = strtotime($existing['duty_start']);
                    $end_existing = strtotime($existing['duty_end']);
                    $start_baru = strtotime($baru['duty_start']);
                    $end_baru = strtotime($baru['duty_end']);

                    // Handle jika waktu end lebih kecil dari start (melewati tengah malam)
                    if ($end_existing < $start_existing) {
                        $end_existing += 24 * 3600;
                    }
                    if ($end_baru < $start_baru) {
                        $end_baru += 24 * 3600;
                    }

                    // Cek bentrok waktu
                    if (($start_baru < $end_existing && $end_baru > $start_existing)) {
                        // Tambahkan informasi status untuk membedakan apakah sudah peserta atau masih validasi
                        $status_text = ($existing['status'] === 'peserta') ? '(Sudah Terdaftar)' : '(Menunggu Validasi)';

                        $jadwal_bentrok[] = [
                            'ekstrakurikuler' => $existing['nama_ekstrakulikuler'] . ' ' . $status_text,
                            'hari' => $existing['hari'],
                            'waktu_existing' => $existing['duty_start'] . ' - ' . $existing['duty_end'],
                            'waktu_baru' => $baru['duty_start'] . ' - ' . $baru['duty_end']
                        ];
                    }
                }
            }
        }

        $stmt_jadwal_existing->close();

        return [
            'bentrok' => !empty($jadwal_bentrok),
            'detail' => $jadwal_bentrok
        ];
    }

    $result = cekJadwalBentrok($conn, $id_user, $id_ekstrakulikuler_baru);
    echo json_encode($result);
} else {
    echo json_encode(['bentrok' => false, 'detail' => []]);
}

$conn->close();
