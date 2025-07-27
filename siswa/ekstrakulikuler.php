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
                    $end_existing += 24 * 3600; // Tambah 24 jam
                }
                if ($end_baru < $start_baru) {
                    $end_baru += 24 * 3600; // Tambah 24 jam
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

function cekPramuka($conn, $id_user)
{
    $query_pramuka = "SELECT COUNT(*) as count FROM tb_peserta tp 
                      JOIN tb_ekstrakulikuler te ON tp.id_ekstrakulikuler = te.id_ekstrakulikuler 
                      WHERE tp.id_user = ? AND LOWER(te.nama_ekstrakulikuler) LIKE '%pramuka%'";
    $stmt_pramuka = $conn->prepare($query_pramuka);
    $stmt_pramuka->bind_param("i", $id_user);
    $stmt_pramuka->execute();
    $result_pramuka = $stmt_pramuka->get_result();
    $pramuka_data = $result_pramuka->fetch_assoc();
    $stmt_pramuka->close();

    return $pramuka_data['count'] > 0;
}

// Fungsi untuk validasi input
function validateInput($data)
{
    $errors = [];

    // Validasi NIS
    if (empty($data['nis']) || !is_numeric($data['nis'])) {
        $errors[] = "NIS harus berupa angka dan tidak boleh kosong.";
    }

    // Validasi Nama Lengkap
    if (empty($data['nama_lengkap']) || strlen($data['nama_lengkap']) < 3) {
        $errors[] = "Nama lengkap minimal 3 karakter.";
    }

    // Validasi Kelas
    if (empty($data['kelas'])) {
        $errors[] = "Kelas tidak boleh kosong.";
    }

    // Validasi Jenis Kelamin
    if (empty($data['jenis_kelamin']) || !in_array($data['jenis_kelamin'], ['Laki-Laki', 'Perempuan'])) {
        $errors[] = "Jenis kelamin harus dipilih.";
    }

    // Validasi Tanggal Lahir
    if (empty($data['tanggal_lahir'])) {
        $errors[] = "Tanggal lahir tidak boleh kosong.";
    } else {
        $birth_date = new DateTime($data['tanggal_lahir']);
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;
        if ($age < 10 || $age > 20) {
            $errors[] = "Usia harus antara 10-20 tahun.";
        }
    }

    // Validasi Alamat
    if (empty($data['alamat']) || strlen($data['alamat']) < 10) {
        $errors[] = "Alamat minimal 10 karakter.";
    }

    // Validasi Nomor HP Wali
    if (empty($data['no_hp_wali']) || !preg_match('/^[0-9]{10,15}$/', $data['no_hp_wali'])) {
        $errors[] = "Nomor HP orang tua harus 10-15 digit angka.";
    }

    // Validasi Nomor HP Siswa (opsional)
    if (!empty($data['no_hp_siswa']) && !preg_match('/^[0-9]{10,15}$/', $data['no_hp_siswa'])) {
        $errors[] = "Nomor HP siswa harus 10-15 digit angka.";
    }

    // Validasi Alasan
    if (empty($data['alasan']) || strlen($data['alasan']) < 20) {
        $errors[] = "Alasan memilih ekstrakurikuler minimal 20 karakter.";
    }

    // Validasi Ketersediaan dan Persetujuan
    if (empty($data['ketersediaan']) || !in_array($data['ketersediaan'], ['Ya', 'Tidak'])) {
        $errors[] = "Ketersediaan waktu harus dipilih.";
    }

    if (empty($data['persetujuan']) || !in_array($data['persetujuan'], ['Ya', 'Tidak'])) {
        $errors[] = "Persetujuan orang tua harus dipilih.";
    }

    // Validasi bahwa ketersediaan dan persetujuan harus "Ya"
    if ($data['ketersediaan'] === 'Tidak') {
        $errors[] = "Anda harus tersedia mengikuti jadwal ekstrakurikuler.";
    }

    if ($data['persetujuan'] === 'Tidak') {
        $errors[] = "Persetujuan orang tua diperlukan untuk mendaftar.";
    }

    return $errors;
}

// Cek apakah user sudah terdaftar Pramuka
$sudah_pramuka = cekPramuka($conn, $id_user);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar'])) {
    // Ambil data dari form
    $form_data = [
        'id_ekstrakulikuler' => $_POST['id_ekstrakulikuler'],
        'nis' => trim($_POST['nis']),
        'nama_lengkap' => trim($_POST['nama_lengkap']),
        'kelas' => trim($_POST['kelas']),
        'jenis_kelamin' => $_POST['jenis_kelamin'],
        'tanggal_lahir' => $_POST['tanggal_lahir'],
        'alamat' => trim($_POST['alamat']),
        'no_hp_siswa' => trim($_POST['no_hp_siswa']),
        'no_hp_wali' => trim($_POST['no_hp_wali']),
        'alasan' => trim($_POST['alasan']),
        'pengalaman' => trim($_POST['pengalaman']),
        'ketersediaan' => $_POST['ketersediaan'],
        'persetujuan' => $_POST['persetujuan'],
        'created_date' => date('Y-m-d')
    ];

    // Validasi input
    $validation_errors = validateInput($form_data);

    if (!empty($validation_errors)) {
        $_SESSION['notification'] = "Terdapat kesalahan dalam pengisian form:<br>• " . implode("<br>• ", $validation_errors);
        $_SESSION['alert'] = "alert-danger";
        header("Location: ekstrakulikuler.php");
        exit();
    }

    $id_ekstrakulikuler = $form_data['id_ekstrakulikuler'];
    $id_user = $_SESSION['id_user'];

    // *** TAMBAHKAN PENGECEKAN JADWAL BENTROK DI SINI ***
    $cek_bentrok = cekJadwalBentrok($conn, $id_user, $id_ekstrakulikuler);

    if ($cek_bentrok['bentrok']) {
        $detail_bentrok = $cek_bentrok['detail'];
        $pesan_bentrok = "Jadwal ekstrakurikuler bentrok dengan ekstrakurikuler yang sudah Anda ikuti:<br><br>";

        foreach ($detail_bentrok as $bentrok) {
            $pesan_bentrok .= "• <strong>" . $bentrok['ekstrakurikuler'] . "</strong><br>";
            $pesan_bentrok .= "&nbsp;&nbsp;Hari: " . $bentrok['hari'] . "<br>";
            $pesan_bentrok .= "&nbsp;&nbsp;Waktu yang bentrok: " . $bentrok['waktu_existing'] . " vs " . $bentrok['waktu_baru'] . "<br><br>";
        }

        $pesan_bentrok .= "Silakan pilih ekstrakurikuler lain atau hubungi admin untuk konsultasi jadwal.";

        $_SESSION['notification'] = $pesan_bentrok;
        $_SESSION['alert'] = "alert-warning";
        header("Location: ekstrakulikuler.php");
        exit();
    }

    // Cek nama ekstrakulikuler yang akan didaftar
    $query_nama_ekstrak = "SELECT nama_ekstrakulikuler FROM tb_ekstrakulikuler WHERE id_ekstrakulikuler = ?";
    $stmt_nama_ekstrak = $conn->prepare($query_nama_ekstrak);
    $stmt_nama_ekstrak->bind_param("i", $id_ekstrakulikuler);
    $stmt_nama_ekstrak->execute();
    $result_nama_ekstrak = $stmt_nama_ekstrak->get_result();
    $nama_ekstrak_data = $result_nama_ekstrak->fetch_assoc();
    $stmt_nama_ekstrak->close();

    $nama_ekstrakulikuler = strtolower($nama_ekstrak_data['nama_ekstrakulikuler']);
    $is_pramuka = strpos($nama_ekstrakulikuler, 'pramuka') !== false;

    // Jika bukan Pramuka dan belum terdaftar Pramuka, tolak pendaftaran
    if (!$is_pramuka && !$sudah_pramuka) {
        $_SESSION['notification'] = "Anda harus mendaftar dan menjadi peserta Pramuka terlebih dahulu sebelum dapat mendaftar ekstrakulikuler lain.";
        $_SESSION['alert'] = "alert-warning";
        header("Location: ekstrakulikuler.php");
        exit();
    }

    // Cek apakah user sudah terdaftar sebagai peserta
    $check_peserta = "SELECT id_peserta FROM tb_peserta WHERE id_user = ? AND id_ekstrakulikuler = ?";
    $stmt_check_peserta = $conn->prepare($check_peserta);
    $stmt_check_peserta->bind_param("ii", $id_user, $id_ekstrakulikuler);
    $stmt_check_peserta->execute();
    $result_peserta = $stmt_check_peserta->get_result();

    // Cek apakah user sudah mengajukan validasi
    $check_validasi = "SELECT id_validasi FROM tb_validasi WHERE id_user = ? AND id_ekstrakulikuler = ?";
    $stmt_check_validasi = $conn->prepare($check_validasi);
    $stmt_check_validasi->bind_param("ii", $id_user, $id_ekstrakulikuler);
    $stmt_check_validasi->execute();
    $result_validasi = $stmt_check_validasi->get_result();

    // Cek apakah NIS sudah terdaftar untuk ekstrakurikuler yang sama
    $check_nis = "SELECT f.id_form FROM tb_form f 
                  JOIN tb_validasi v ON f.id_validasi = v.id_validasi 
                  WHERE f.nis = ? AND v.id_ekstrakulikuler = ?";
    $stmt_check_nis = $conn->prepare($check_nis);
    $stmt_check_nis->bind_param("ii", $form_data['nis'], $id_ekstrakulikuler);
    $stmt_check_nis->execute();
    $result_nis = $stmt_check_nis->get_result();

    if ($result_peserta->num_rows > 0) {
        $_SESSION['notification'] = "Anda sudah terdaftar sebagai peserta ekstrakulikuler ini.";
        $_SESSION['alert'] = "alert-warning";
    } elseif ($result_validasi->num_rows > 0) {
        $_SESSION['notification'] = "Anda sudah mengajukan pendaftaran untuk ekstrakulikuler ini. Tunggu konfirmasi.";
        $_SESSION['alert'] = "alert-info";
    } elseif ($result_nis->num_rows > 0) {
        $_SESSION['notification'] = "NIS tersebut sudah terdaftar untuk ekstrakulikuler ini.";
        $_SESSION['alert'] = "alert-warning";
    } else {
        // Mulai transaksi
        $conn->begin_transaction();

        try {
            // Insert ke tb_validasi
            $query_validasi = "INSERT INTO tb_validasi (id_ekstrakulikuler, id_user) VALUES (?, ?)";
            $stmt_validasi = $conn->prepare($query_validasi);
            $stmt_validasi->bind_param("ii", $id_ekstrakulikuler, $id_user);
            $stmt_validasi->execute();

            // Ambil ID validasi yang baru dibuat
            $id_validasi = mysqli_insert_id($conn);

            // Siapkan variabel untuk bind_param (mengatasi masalah referensi)
            $no_hp_siswa = !empty($form_data['no_hp_siswa']) ? $form_data['no_hp_siswa'] : null;
            $pengalaman = !empty($form_data['pengalaman']) ? $form_data['pengalaman'] : null;

            // Insert ke tb_form dengan data lengkap
            $query_form = "INSERT INTO tb_form (
                id_validasi, nis, nama_lengkap, kelas, jenis_kelamin, 
                tanggal_lahir, alamat, no_hp_siswa, no_hp_wali, 
                alasan, pengalaman, ketersediaan, persetujuan, created_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_form = $conn->prepare($query_form);
            $stmt_form->bind_param(
                "iissssssssssss",
                $id_validasi,
                $form_data['nis'],
                $form_data['nama_lengkap'],
                $form_data['kelas'],
                $form_data['jenis_kelamin'],
                $form_data['tanggal_lahir'],
                $form_data['alamat'],
                $no_hp_siswa,
                $form_data['no_hp_wali'],
                $form_data['alasan'],
                $pengalaman,
                $form_data['ketersediaan'],
                $form_data['persetujuan'],
                $form_data['created_date']
            );

            $stmt_form->execute();

            // Commit transaksi
            $conn->commit();

            $_SESSION['notification'] = "Berhasil mendaftar ekstrakurikuler! Data pendaftaran Anda telah disimpan dan menunggu persetujuan admin.";
            $_SESSION['alert'] = "alert-success";

            $stmt_validasi->close();
            $stmt_form->close();
        } catch (Exception $e) {
            // Rollback jika terjadi error
            $conn->rollback();
            $_SESSION['notification'] = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
            $_SESSION['alert'] = "alert-danger";
        }
    }

    $stmt_check_peserta->close();
    $stmt_check_validasi->close();
    $stmt_check_nis->close();
    header("Location: ekstrakulikuler.php");
    exit();
}

// Query yang dimodifikasi untuk menampilkan hanya ekstrakulikuler yang belum diikuti dan belum mengajukan validasi
if (!$sudah_pramuka) {
    $query = "SELECT e.*, p.pembina_nama 
              FROM tb_ekstrakulikuler e
              LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id 
              WHERE e.status = 'Masih Berlangsung'
              AND LOWER(e.nama_ekstrakulikuler) LIKE '%pramuka%'
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tp.id_ekstrakulikuler 
                  FROM tb_peserta tp 
                  WHERE tp.id_user = ?
              )
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tv.id_ekstrakulikuler 
                  FROM tb_validasi tv 
                  WHERE tv.id_user = ?
              )
              ORDER BY e.nama_ekstrakulikuler ASC";
} else {
    // Jika sudah terdaftar Pramuka, tampilkan semua kecuali yang sudah diikuti
    $query = "SELECT e.*, p.pembina_nama 
              FROM tb_ekstrakulikuler e
              LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id 
              WHERE e.status = 'Masih Berlangsung'
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tp.id_ekstrakulikuler 
                  FROM tb_peserta tp 
                  WHERE tp.id_user = ?
              )
              AND e.id_ekstrakulikuler NOT IN (
                  SELECT tv.id_ekstrakulikuler 
                  FROM tb_validasi tv 
                  WHERE tv.id_user = ?
              )
              ORDER BY e.nama_ekstrakulikuler ASC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_user, $id_user);
$stmt->execute();
$result = $stmt->get_result();

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
            <?php if (!$sudah_pramuka): ?>
                <div class="row my-4">
                    <div class="col-12">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Anda harus mendaftar dan menjadi peserta Pramuka terlebih dahulu
                            sebelum dapat mendaftar ekstrakulikuler lain.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="row my-4">
                <div class="col-lg col-md-6">
                    <div class="card border-0 shadow-xs mb-4">
                        <div class="card-header border-bottom-0 pb-0">
                            <div class="d-sm-flex align-items-center mb-3">
                                <div>
                                    <h6 class="font-weight-semibold text-lg mb-0">Data Ekstrakurikuler - Pendaftaran
                                    </h6>
                                    <p class="text-sm text-muted mb-sm-0">
                                        <?php if (!$sudah_pramuka): ?>
                                            Silakan daftar Pramuka terlebih dahulu
                                        <?php else: ?>
                                            Ekstrakurikuler yang tersedia untuk pendaftaran
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body px-0 py-0">
                            <?php if ($result->num_rows == 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Tidak ada ekstrakulikuler yang tersedia</h5>
                                    <p class="text-sm text-muted">
                                        <?php if (!$sudah_pramuka): ?>
                                            Anda belum terdaftar sebagai peserta Pramuka atau sudah mengajukan pendaftaran
                                            Pramuka.
                                        <?php else: ?>
                                            Anda sudah terdaftar atau mengajukan pendaftaran untuk semua ekstrakulikuler yang
                                            tersedia.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
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
                                            while ($data = $result->fetch_assoc()) { ?>
                                                <tr>
                                                    <td>
                                                        <p class="transaction-amount mb-0"><?php echo $no++ ?></p>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center px-2 py-1">
                                                            <div>
                                                                <h6 class="transaction-name mb-0">
                                                                    <?php echo htmlspecialchars($data["nama_ekstrakulikuler"]) ?>
                                                                </h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <p class="transaction-amount mb-0">
                                                            <?php echo htmlspecialchars($data["deskripsi_ekstrakulikuler"]) ?>
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <?php if ($data["pembina_nama"] != NULL) { ?>
                                                            <p class="transaction-amount mb-0">
                                                                <?php echo htmlspecialchars($data["pembina_nama"]) ?>
                                                            </p>
                                                        <?php } else { ?>
                                                            <span class="badge bg-danger text-white">
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
                                                            <span class="badge bg-success text-white">
                                                                <?php echo $data["status"] ?>
                                                            </span>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <button type="button" class="btn-action" data-bs-toggle="modal"
                                                            data-bs-title="View Data"
                                                            data-bs-target="#viewModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>

                                                        <button type="button" class="btn-action btn-daftar-ekstrakurikuler"
                                                            data-bs-toggle="modal"
                                                            data-bs-title="Daftar Ekstrakulikuler"
                                                            data-ekstrakurikuler-id="<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                            data-bs-target="#daftarModal<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                            <i class="fas fa-user-plus"></i>
                                                        </button>
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

                                                <!-- Daftar Modal-->
                                                <div class="modal fade"
                                                    id="daftarModal<?php echo $data["id_ekstrakulikuler"]; ?>" tabindex="-1"
                                                    aria-labelledby="daftarModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="daftarModalLabel<?php echo $data["id_ekstrakulikuler"]; ?>">
                                                                    Form Pendaftaran Ekstrakurikuler
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <form action="ekstrakulikuler.php" method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id_ekstrakulikuler"
                                                                        value="<?php echo $data["id_ekstrakulikuler"]; ?>" />

                                                                    <!-- Data Pribadi Siswa -->
                                                                    <h6 class="mb-3 text-primary"><i
                                                                            class="fas fa-user me-2"></i>Data Pribadi Siswa</h6>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="nis" class="form-label">NIS <span
                                                                                        class="text-danger">*</span></label>
                                                                                <input type="text" class="form-control"
                                                                                    name="nis" id="nis" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="nama_lengkap"
                                                                                    class="form-label">Nama Lengkap <span
                                                                                        class="text-danger">*</span></label>
                                                                                <input type="text" class="form-control"
                                                                                    name="nama_lengkap" id="nama_lengkap"
                                                                                    required>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="kelas" class="form-label">Kelas
                                                                                    <span class="text-danger">*</span></label>
                                                                                <input type="text" class="form-control"
                                                                                    name="kelas" id="kelas"
                                                                                    placeholder="contoh: 7A, 8B, 9C" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="jenis_kelamin"
                                                                                    class="form-label">Jenis Kelamin <span
                                                                                        class="text-danger">*</span></label>
                                                                                <select class="form-select" name="jenis_kelamin"
                                                                                    id="jenis_kelamin" required>
                                                                                    <option value="">Pilih Jenis Kelamin
                                                                                    </option>
                                                                                    <option value="Laki-Laki">Laki-Laki</option>
                                                                                    <option value="Perempuan">Perempuan</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="tanggal_lahir"
                                                                                    class="form-label">Tanggal Lahir <span
                                                                                        class="text-danger">*</span></label>
                                                                                <input type="date" class="form-control"
                                                                                    name="tanggal_lahir" id="tanggal_lahir"
                                                                                    required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="no_hp_siswa"
                                                                                    class="form-label">Nomor HP Siswa</label>
                                                                                <input type="tel" class="form-control"
                                                                                    name="no_hp_siswa" id="no_hp_siswa"
                                                                                    placeholder="contoh: 08123456789">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label for="alamat" class="form-label">Alamat Lengkap
                                                                            <span class="text-danger">*</span></label>
                                                                        <textarea class="form-control" name="alamat" id="alamat"
                                                                            rows="3" placeholder="Masukkan alamat lengkap"
                                                                            required></textarea>
                                                                    </div>

                                                                    <!-- Data Orang Tua/Wali -->
                                                                    <h6 class="mb-3 text-primary"><i
                                                                            class="fas fa-users me-2"></i>Data Orang Tua/Wali
                                                                    </h6>

                                                                    <div class="mb-3">
                                                                        <label for="no_hp_wali" class="form-label">Nomor HP
                                                                            Orang Tua/Wali <span
                                                                                class="text-danger">*</span></label>
                                                                        <input type="tel" class="form-control" name="no_hp_wali"
                                                                            id="no_hp_wali" placeholder="contoh: 08123456789"
                                                                            required>
                                                                    </div>

                                                                    <!-- Data Ekstrakurikuler -->
                                                                    <h6 class="mb-3 text-primary"><i
                                                                            class="fas fa-graduation-cap me-2"></i>Data
                                                                        Ekstrakurikuler</h6>

                                                                    <div class="mb-3">
                                                                        <label for="nama_ekskul" class="form-label">Nama
                                                                            Ekstrakurikuler yang Dipilih</label>
                                                                        <input type="text" class="form-control"
                                                                            name="nama_ekskul" id="nama_ekskul"
                                                                            value="<?php echo $data['nama_ekstrakulikuler']; ?>"
                                                                            readonly>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label for="alasan" class="form-label">Alasan Memilih
                                                                            Ekstrakurikuler <span
                                                                                class="text-danger">*</span></label>
                                                                        <textarea class="form-control" name="alasan" id="alasan"
                                                                            rows="3"
                                                                            placeholder="Jelaskan alasan Anda memilih ekstrakurikuler ini"
                                                                            required></textarea>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label for="pengalaman" class="form-label">Pengalaman
                                                                            Terkait (jika ada)</label>
                                                                        <textarea class="form-control" name="pengalaman"
                                                                            id="pengalaman" rows="3"
                                                                            placeholder="Ceritakan pengalaman yang relevan dengan ekstrakurikuler ini (jika tidak ada, tulis 'Tidak ada')"></textarea>
                                                                    </div>

                                                                    <!-- Konfirmasi -->
                                                                    <h6 class="mb-3 text-primary"><i
                                                                            class="fas fa-check-circle me-2"></i>Konfirmasi</h6>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="ketersediaan"
                                                                                    class="form-label">Ketersediaan Waktu <span
                                                                                        class="text-danger">*</span></label>
                                                                                <select class="form-select" name="ketersediaan"
                                                                                    id="ketersediaan" required>
                                                                                    <option value="">Pilih Ketersediaan</option>
                                                                                    <option value="Ya">Ya, saya tersedia
                                                                                        mengikuti jadwal ekstrakurikuler
                                                                                    </option>
                                                                                    <option value="Tidak">Tidak, saya memiliki
                                                                                        kendala waktu</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="mb-3">
                                                                                <label for="persetujuan"
                                                                                    class="form-label">Persetujuan Orang Tua
                                                                                    <span class="text-danger">*</span></label>
                                                                                <select class="form-select" name="persetujuan"
                                                                                    id="persetujuan" required>
                                                                                    <option value="">Pilih Persetujuan</option>
                                                                                    <option value="Ya">Ya, orang tua menyetujui
                                                                                    </option>
                                                                                    <option value="Tidak">Tidak, orang tua belum
                                                                                        menyetujui</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <input type="hidden" name="created_date"
                                                                        value="<?php echo date('Y-m-d'); ?>">

                                                                    <div class="alert alert-info">
                                                                        <i class="fas fa-info-circle me-2"></i>
                                                                        <strong>Informasi Penting:</strong><br>
                                                                        • Setelah mendaftar, pendaftaran Anda akan masuk ke
                                                                        sistem validasi dan menunggu persetujuan admin.<br>
                                                                        • Pastikan semua data yang diisi sudah benar dan
                                                                        lengkap.<br>
                                                                        • Data yang sudah dikirim tidak dapat diubah.
                                                                    </div>

                                                                    <div class="alert alert-warning">
                                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                                        <strong>Apakah Anda yakin semua data sudah benar dan
                                                                            ingin mendaftar pada ekstrakurikuler ini?</strong>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">
                                                                        <i class="fas fa-times me-2"></i>Batal
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary" name="daftar">
                                                                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
        $(document).ready(function() {
            <?php if ($result->num_rows > 0): ?>
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
                        targets: -1 // Nonaktifkan sort di kolom "Action" (kolom terakhir)
                    }],
                    initComplete: function() {
                        // Initialize tooltips after DataTables loads
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                            '[data-bs-toggle="tooltip"]'))
                        tooltipTriggerList.map(function(tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl)
                        });
                    }
                });
            <?php endif; ?>

            // Enable Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });

        $(document).on('click', '.btn-daftar-ekstrakurikuler', function(e) {
            e.preventDefault(); // Prevent default action

            const idEkstrakurikuler = $(this).data('ekstrakurikuler-id');
            console.log('ID Ekstrakurikuler yang akan dicek:', idEkstrakurikuler); // Debug log

            // Cek jadwal bentrok sebelum menampilkan modal
            cekJadwalBentrokAjax(idEkstrakurikuler, function(response) {
                console.log('Response pengecekan bentrok:', response); // Debug log

                if (response.bentrok) {
                    // Pastikan modal ditutup jika terbuka
                    $(`#daftarModal${idEkstrakurikuler}`).modal('hide');

                    // Tunggu sebentar untuk memastikan modal tertutup sebelum menampilkan alert
                    setTimeout(function() {
                        let pesanBentrok = "⚠️ JADWAL BENTROK TERDETEKSI!\n\n";
                        pesanBentrok += "Ekstrakurikuler yang akan Anda daftar memiliki jadwal yang bentrok dengan:\n\n";

                        response.detail.forEach(function(bentrok) {
                            pesanBentrok += `• ${bentrok.ekstrakurikuler}\n`;
                            pesanBentrok += `  Hari: ${bentrok.hari}\n`;
                            pesanBentrok += `  Waktu: ${bentrok.waktu_existing} vs ${bentrok.waktu_baru}\n\n`;
                        });

                        pesanBentrok += "Silakan pilih ekstrakurikuler lain atau hubungi admin untuk konsultasi jadwal.";

                        Swal.fire({
                            title: '⚠️ Jadwal Bentrok!',
                            html: pesanBentrok.replace(/\n/g, '<br>'),
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Mengerti',
                            customClass: {
                                popup: 'swal-wide'
                            },
                            backdrop: true, // Pastikan backdrop aktif
                            allowOutsideClick: true // Allow click outside to close
                        });
                    }, 300); // Delay 300ms untuk memastikan modal tertutup
                } else {
                    // Jika tidak bentrok, langsung buka modal
                    $(`#daftarModal${idEkstrakurikuler}`).modal('show');
                }
            });
        });

        function cekJadwalBentrokAjax(idEkstrakurikuler, callback) {
            console.log('Mengirim AJAX request untuk ID:', idEkstrakurikuler); // Debug log

            $.ajax({
                url: 'check_jadwal_bentrok.php',
                type: 'POST',
                data: {
                    id_ekstrakulikuler: idEkstrakurikuler,
                    id_user: <?php echo $_SESSION['id_user']; ?>
                },
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX Response:', response); // Debug log
                    callback(response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error); // Debug log
                    console.error('Response Text:', xhr.responseText); // Debug response
                    callback({
                        bentrok: false,
                        detail: []
                    });
                }
            });
        }

        $(document).on('shown.bs.modal', function(e) {
            // Jika ada SweetAlert yang aktif, tutup modal
            if (Swal.isVisible()) {
                $(e.target).modal('hide');
            }
        });


        $(document).on('show.bs.modal', function(e) {
            // Jika SweetAlert sedang aktif, prevent modal dari muncul
            if (Swal.isVisible()) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Tambahkan CSS untuk SweetAlert yang lebih lebar
        const style = document.createElement('style');
        style.textContent = `
        .swal-wide {
            width: 600px !important;
            z-index: 9999 !important; /* Pastikan SweetAlert di atas modal */
        }
        .swal2-html-container {
            text-align: left !important;
            font-family: monospace;
            font-size: 14px;
        }
        .swal2-backdrop-show {
            z-index: 9998 !important; /* Backdrop SweetAlert */
        }
        /* Pastikan modal backdrop tidak menghalangi SweetAlert */
        .modal-backdrop {
            z-index: 1040 !important;
        }
        `;
        document.head.appendChild(style);
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

</body>

</html>

<?php
// Tutup statement dan koneksi
$stmt->close();
$conn->close();
?>