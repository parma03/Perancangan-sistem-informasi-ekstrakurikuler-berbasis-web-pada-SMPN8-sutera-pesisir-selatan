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
    } else if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../admin/index.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}

// Ambil daftar periode yang tersedia
$query_periode = "SELECT DISTINCT periode FROM tb_ekstrakulikuler ORDER BY periode DESC";
$result_periode = mysqli_query($conn, $query_periode);

// Filter berdasarkan periode yang dipilih
$selected_periode = isset($_GET['periode']) ? $_GET['periode'] : date('Y');
$show_report = isset($_GET['periode']) || isset($_GET['show_all']);

// Query untuk mengambil data laporan
$laporan_data = [];
if ($show_report) {
    $where_clause = $show_report && !isset($_GET['show_all']) ? "WHERE e.periode = '$selected_periode'" : "";
    
    $query_laporan = "
        SELECT 
            e.id_ekstrakulikuler,
            e.nama_ekstrakulikuler,
            e.deskripsi_ekstrakulikuler,
            e.periode,
            e.status,
            p.pembina_nama,
            COUNT(DISTINCT pe.id_peserta) as total_peserta
        FROM tb_ekstrakulikuler e
        LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id
        LEFT JOIN tb_peserta pe ON e.id_ekstrakulikuler = pe.id_ekstrakulikuler
        $where_clause
        GROUP BY e.id_ekstrakulikuler
        ORDER BY e.periode DESC, e.nama_ekstrakulikuler ASC
    ";
    
    $result_laporan = mysqli_query($conn, $query_laporan);
    
    while ($row = mysqli_fetch_assoc($result_laporan)) {
        // Ambil jadwal untuk setiap ekstrakurikuler
        $query_jadwal = "
            SELECT hari, duty_start, duty_end 
            FROM tb_jadwal 
            WHERE id_ekstrakulikuler = " . $row['id_ekstrakulikuler'] . "
            ORDER BY 
                CASE hari 
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                    WHEN 'Minggu' THEN 7
                END
        ";
        $result_jadwal = mysqli_query($conn, $query_jadwal);
        $jadwal = [];
        while ($jadwal_row = mysqli_fetch_assoc($result_jadwal)) {
            $jadwal[] = $jadwal_row;
        }
        
        // Ambil kegiatan untuk setiap ekstrakurikuler
        $query_kegiatan = "
            SELECT nama_kegiatan, kegiatan, jadwal 
            FROM tb_kegiatan 
            WHERE id_ekstrakulikuler = " . $row['id_ekstrakulikuler'] . "
            ORDER BY jadwal DESC
        ";
        $result_kegiatan = mysqli_query($conn, $query_kegiatan);
        $kegiatan = [];
        while ($kegiatan_row = mysqli_fetch_assoc($result_kegiatan)) {
            $kegiatan[] = $kegiatan_row;
        }
        
        $row['jadwal'] = $jadwal;
        $row['kegiatan'] = $kegiatan;
        $laporan_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>Laporan Ekstrakurikuler - SMPN 8 Sutera Pesisir Selatan</title>
    
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Sans:300,400,500,600,700,800|PT+Mono:300,400,500,600,700" rel="stylesheet" />
    <!-- Nucleo Icons -->
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/349ee9c857.js" crossorigin="anonymous"></script>
    <!-- CSS Files -->
    <link id="pagestyle" href="../assets/css/corporate-ui-dashboard.css?v=1.0.0" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/notification.css">
    
    <!-- jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .report-table {
            font-size: 12px;
        }
        
        .report-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }
        
        .report-table td {
            vertical-align: top;
        }
        
        .jadwal-item, .kegiatan-item {
            margin-bottom: 5px;
            padding: 3px 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .signature-section {
            margin-top: 50px;
            text-align: right;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            margin-left: 50px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 80px auto 10px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .report-container {
                box-shadow: none !important;
                border: none !important;
            }
        }
        
        .export-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">
    <!-- Notification Container -->
    <div class="notification-container">
        <?php if (isset($_SESSION['notification']) && isset($_SESSION['alert'])): ?>
            <div class="alert fade alert-dismissible text-left <?php echo $_SESSION['alert']; ?> show">
                <button type="button" class="close" onclick="this.parentElement.remove()">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
                <strong class="font-weight-bold">
                    <?php
                    if ($_SESSION['alert'] == 'alert-success') echo "Success!";
                    elseif ($_SESSION['alert'] == 'alert-info') echo "Info!";
                    elseif ($_SESSION['alert'] == 'alert-warning') echo "Warning!";
                    elseif ($_SESSION['alert'] == 'alert-danger') echo "Error!";
                    ?>
                </strong>
                <?php echo $_SESSION['notification']; ?>
            </div>
            <?php
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
            <!-- Filter Section -->
            <div class="row no-print">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header pb-0">
                            <h5>Filter Laporan Ekstrakurikuler</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Pilih Periode</label>
                                    <select name="periode" class="form-select">
                                        <option value="">-- Pilih Periode --</option>
                                        <?php while ($periode = mysqli_fetch_assoc($result_periode)): ?>
                                            <option value="<?php echo $periode['periode']; ?>" 
                                                <?php echo $selected_periode == $periode['periode'] ? 'selected' : ''; ?>>
                                                <?php echo $periode['periode']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fa fa-search me-1"></i>Tampilkan Laporan
                                    </button>
                                    <button type="submit" name="show_all" value="1" class="btn btn-info me-2">
                                        <i class="fa fa-list me-1"></i>Tampilkan Semua
                                    </button>
                                    <?php if ($show_report): ?>
                                        <button type="button" class="btn btn-success" onclick="exportToPDF()">
                                            <i class="fa fa-file-pdf me-1"></i>Export PDF
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Section -->
            <?php if ($show_report): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card report-container" id="reportContent">
                            <div class="card-body">
                                <!-- Report Header -->
                                <div class="report-header">
                                    <h4 class="mb-1">LAPORAN EKSTRAKURIKULER</h4>
                                    <h5 class="mb-1">SMPN 8 SUTERA PESISIR SELATAN</h5>
                                    <p class="mb-0">
                                        <?php if (isset($_GET['show_all'])): ?>
                                            Periode: Semua Periode
                                        <?php else: ?>
                                            Periode: <?php echo $selected_periode; ?>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></small>
                                </div>

                                <!-- Report Table -->
                                <?php if (empty($laporan_data)): ?>
                                    <div class="alert alert-info text-center">
                                        <i class="fa fa-info-circle fa-2x mb-3"></i>
                                        <h5>Tidak ada data ekstrakurikuler</h5>
                                        <p>Tidak ditemukan data ekstrakurikuler untuk periode yang dipilih.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered report-table">
                                            <thead>
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th width="15%">Nama Ekstrakurikuler</th>
                                                    <th width="20%">Deskripsi</th>
                                                    <th width="12%">Pembina</th>
                                                    <th width="8%">Periode</th>
                                                    <th width="8%">Status</th>
                                                    <th width="8%">Total Peserta</th>
                                                    <th width="12%">Jadwal</th>
                                                    <th width="12%">Kegiatan Terbaru</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                $total_peserta_keseluruhan = 0;
                                                foreach ($laporan_data as $data): 
                                                    $total_peserta_keseluruhan += $data['total_peserta'];
                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $no++; ?></td>
                                                        <td><strong><?php echo htmlspecialchars($data['nama_ekstrakulikuler']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($data['deskripsi_ekstrakulikuler']); ?></td>
                                                        <td><?php echo htmlspecialchars($data['pembina_nama'] ?: 'Belum ditentukan'); ?></td>
                                                        <td class="text-center"><?php echo $data['periode']; ?></td>
                                                        <td class="text-center">
                                                            <span class="badge badge-sm text-dark <?php echo $data['status'] == 'Masih Berlangsung' ? 'bg-success' : 'bg-secondary'; ?>">
                                                                <?php echo $data['status']; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center"><strong><?php echo $data['total_peserta']; ?></strong></td>
                                                        <td>
                                                            <?php if (empty($data['jadwal'])): ?>
                                                                <small class="text-muted">Belum ada jadwal</small>
                                                            <?php else: ?>
                                                                <?php foreach ($data['jadwal'] as $jadwal): ?>
                                                                    <div class="jadwal-item">
                                                                        <strong><?php echo $jadwal['hari']; ?></strong><br>
                                                                        <?php echo date('H:i', strtotime($jadwal['duty_start'])); ?> - 
                                                                        <?php echo date('H:i', strtotime($jadwal['duty_end'])); ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (empty($data['kegiatan'])): ?>
                                                                <small class="text-muted">Belum ada kegiatan</small>
                                                            <?php else: ?>
                                                                <?php foreach ($data['kegiatan'] as $kegiatan): ?>
                                                                    <div class="kegiatan-item">
                                                                        <strong><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></strong><br>
                                                                        <small><?php echo date('d/m/Y', strtotime($kegiatan['jadwal'])); ?></small>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="6" class="text-end"><strong>Total Keseluruhan:</strong></td>
                                                    <td class="text-center"><strong><?php echo $total_peserta_keseluruhan; ?></strong></td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Summary Section -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-title">Ringkasan Laporan</h6>
                                                    <ul class="list-unstyled mb-0">
                                                        <li><strong>Total Ekstrakurikuler:</strong> <?php echo count($laporan_data); ?></li>
                                                        <li><strong>Total Peserta:</strong> <?php echo $total_peserta_keseluruhan; ?></li>
                                                        <li><strong>Ekstrakurikuler Aktif:</strong> 
                                                            <?php echo count(array_filter($laporan_data, function($item) { 
                                                                return $item['status'] == 'Masih Berlangsung'; 
                                                            })); ?>
                                                        </li>
                                                        <li><strong>Ekstrakurikuler Selesai:</strong> 
                                                            <?php echo count(array_filter($laporan_data, function($item) { 
                                                                return $item['status'] == 'Selesai'; 
                                                            })); ?>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Signature Section -->
                                    <div class="signature-section">
                                        <p>Pesisir Selatan, <?php echo date('d F Y'); ?></p>
                                        <div class="signature-box">
                                            <p>Mengetahui,<br>Wakil Kepala Sekolah</p>
                                            <div class="signature-line"></div>
                                            <p><strong>(...........................)</strong><br>
                                            <small>NIP. ...........................</small></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php include '_component/footer.php'; ?>
    </main>

    <!-- Export to PDF Button (Floating) -->
    <?php if ($show_report && !empty($laporan_data)): ?>
        <div class="export-btn no-print">
            <button class="btn btn-success btn-lg rounded-circle" onclick="exportToPDF()" title="Export to PDF">
                <i class="fa fa-file-pdf fa-lg"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Core JS Files -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/corporate-ui-dashboard.min.js?v=1.0.0"></script>
    <script src="../assets/js/notification.js"></script>

    <script>
        // Function to export to PDF
        async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            
            // Show loading
            const loadingAlert = document.createElement('div');
            loadingAlert.className = 'alert alert-info position-fixed';
            loadingAlert.style.top = '20px';
            loadingAlert.style.right = '20px';
            loadingAlert.style.zIndex = '9999';
            loadingAlert.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Generating PDF...';
            document.body.appendChild(loadingAlert);
            
            try {
                const element = document.getElementById('reportContent');
                
                // Use html2canvas to capture the content
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    height: element.scrollHeight,
                    width: element.scrollWidth
                });
                
                const imgData = canvas.toDataURL('image/png');
                
                // Create PDF
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;
                
                // Add first page
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                // Add additional pages if needed
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                // Generate filename
                const periode = '<?php echo isset($_GET['show_all']) ? 'Semua_Periode' : $selected_periode; ?>';
                const filename = `Laporan_Ekstrakurikuler_${periode}_${new Date().toISOString().slice(0,10)}.pdf`;
                
                // Save PDF
                pdf.save(filename);
                
                // Show success message
                loadingAlert.className = 'alert alert-success position-fixed';
                loadingAlert.innerHTML = '<i class="fa fa-check me-2"></i>PDF berhasil diunduh!';
                
                setTimeout(() => {
                    document.body.removeChild(loadingAlert);
                }, 3000);
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                loadingAlert.className = 'alert alert-danger position-fixed';
                loadingAlert.innerHTML = '<i class="fa fa-exclamation-triangle me-2"></i>Gagal generate PDF!';
                
                setTimeout(() => {
                    document.body.removeChild(loadingAlert);
                }, 3000);
            }
        }

        // Smooth scrollbar initialization
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