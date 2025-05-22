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

// Query untuk menghitung statistik
try {
    // Query untuk mengambil jadwal ekstrakurikuler yang diikuti siswa
    $query_jadwal = "
        SELECT 
            e.nama_ekstrakulikuler,
            j.hari,
            j.duty_start,
            j.duty_end,
            p.pembina_nama
        FROM tb_peserta pe
        JOIN tb_ekstrakulikuler e ON pe.id_ekstrakulikuler = e.id_ekstrakulikuler
        JOIN tb_jadwal j ON e.id_ekstrakulikuler = j.id_ekstrakulikuler
        LEFT JOIN tb_pembina p ON e.pembina_id = p.pembina_id
        WHERE pe.id_user = ? AND e.status = 'Masih Berlangsung'
        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), j.duty_start
    ";

    $stmt = mysqli_prepare($conn, $query_jadwal);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result_jadwal = mysqli_stmt_get_result($stmt);

    $jadwal_ekskul = [];
    while ($row = mysqli_fetch_assoc($result_jadwal)) {
        $jadwal_ekskul[] = $row;
    }
} catch (Exception $e) {
    $jadwal_ekskul = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>
        Sistem Informasi Ekstrakurikuler
        SMPN 8 Sutera Pesisir Selatan
    </title>
    <!--     Fonts and icons     -->
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Sans:300,400,500,600,700,800|PT+Mono:300,400,500,600,700"
        rel="stylesheet" />
    <!-- Nucleo Icons -->
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/349ee9c857.js" crossorigin="anonymous"></script>
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- CSS Files -->
    <link id="pagestyle" href="../assets/css/corporate-ui-dashboard.css?v=1.0.0" rel="stylesheet" />
    <link id="pagestyle" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css"
        rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/notification.css">

    <style>
        .calendar-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .calendar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e5e7eb;
        }

        .calendar-day-header {
            background: #f8fafc;
            padding: 15px 5px;
            text-align: center;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: background 0.2s;
        }

        .calendar-day:hover {
            background: #f9fafb;
        }

        .calendar-day.other-month {
            background: #f8fafc;
            color: #9ca3af;
        }

        .calendar-day.today {
            background: #eff6ff;
            border: 2px solid #3b82f6;
        }

        .day-number {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.875rem;
        }

        .schedule-item {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .schedule-item:nth-child(even) {
            background: linear-gradient(135deg, #ffecd2, #fcb69f);
            color: #8b4513;
        }

        .schedule-item:nth-child(3n) {
            background: linear-gradient(135deg, #a8edea, #fed6e3);
            color: #2d3748;
        }

        .stats-row {
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .today-schedule {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .schedule-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #1f2937;
        }

        .schedule-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .schedule-list-item:last-child {
            border-bottom: none;
        }

        .schedule-name {
            font-weight: 500;
            color: #374151;
        }

        .schedule-time {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="g-sidenav-show  bg-gray-100">
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
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <?php include '_component/navbar.php'; ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4 px-5">
            <div class="row">
                <div class="col-md-12">
                    <div class="d-md-flex align-items-center mb-3 mx-2">
                        <div class="mb-md-0 mb-3">
                            <h3 class="font-weight-bold mb-0">Hello, Noah</h3>
                            <p class="mb-0">Sistem Informasi Ekstrakurikuler
                                SMPN 8 Sutera Pesisir Selatan</p>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-0">

            <!-- Calendar and Today's Schedule -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <div class="calendar-nav">
                                <button class="nav-btn" onclick="previousMonth()">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <h4 id="monthYear" class="mb-0"></h4>
                                <button class="nav-btn" onclick="nextMonth()">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <p class="mb-0">Jadwal Ekstrakurikuler Anda</p>
                        </div>
                        <div class="calendar-grid" id="calendar">
                            <!-- Calendar will be generated by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="today-schedule">
                        <h5 class="schedule-title">
                            <i class="fas fa-clock text-primary me-2"></i>
                            Jadwal Hari Ini
                        </h5>
                        <div id="todaySchedule">
                            <?php
                            $today = date('l'); // Get current day name in English
                            $day_translation = [
                                'Monday' => 'Senin',
                                'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu',
                                'Thursday' => 'Kamis',
                                'Friday' => 'Jumat',
                                'Saturday' => 'Sabtu',
                                'Sunday' => 'Minggu'
                            ];
                            $today_indonesian = $day_translation[$today];

                            $found_schedule = false;
                            foreach ($jadwal_ekskul as $jadwal) {
                                if ($jadwal['hari'] == $today_indonesian) {
                                    $found_schedule = true;
                                    echo '<div class="schedule-list-item">';
                                    echo '<div>';
                                    echo '<div class="schedule-name">' . htmlspecialchars($jadwal['nama_ekstrakulikuler']) . '</div>';
                                    echo '<small class="text-muted">Pembina: ' . htmlspecialchars($jadwal['pembina_nama']) . '</small>';
                                    echo '</div>';
                                    echo '<div class="schedule-time">' . date('H:i', strtotime($jadwal['duty_start'])) . ' - ' . date('H:i', strtotime($jadwal['duty_end'])) . '</div>';
                                    echo '</div>';
                                }
                            }

                            if (!$found_schedule) {
                                echo '<div class="text-center text-muted py-4">';
                                echo '<i class="fas fa-calendar-times fa-3x mb-3"></i>';
                                echo '<p>Tidak ada jadwal ekstrakurikuler hari ini</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="today-schedule mt-4">
                        <h5 class="schedule-title">
                            <i class="fas fa-list text-success me-2"></i>
                            Semua Jadwal Mingguan
                        </h5>
                        <div>
                            <?php if (empty($jadwal_ekskul)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                                    <p>Anda belum terdaftar di ekstrakurikuler manapun</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($jadwal_ekskul as $jadwal): ?>
                                    <div class="schedule-list-item">
                                        <div>
                                            <div class="schedule-name">
                                                <?php echo htmlspecialchars($jadwal['nama_ekstrakulikuler']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($jadwal['hari']); ?> •
                                                <?php echo htmlspecialchars($jadwal['pembina_nama']); ?></small>
                                        </div>
                                        <div class="schedule-time"><?php echo date('H:i', strtotime($jadwal['duty_start'])); ?>
                                            - <?php echo date('H:i', strtotime($jadwal['duty_end'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include '_component/footer.php'; ?>
    </main>

    <script>
        // Schedule data from PHP
        const scheduleData = <?php echo json_encode($jadwal_ekskul); ?>;

        // Indonesian day names
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        let currentDate = new Date();

        function generateCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();

            // Update month/year display
            document.getElementById('monthYear').textContent = `${monthNames[month]} ${year}`;

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            // Get previous month's last days
            const prevMonth = new Date(year, month, 0);
            const daysInPrevMonth = prevMonth.getDate();

            let calendarHTML = '';

            // Day headers
            dayNames.forEach(day => {
                calendarHTML += `<div class="calendar-day-header">${day}</div>`;
            });

            // Previous month's trailing days
            for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                calendarHTML += `<div class="calendar-day other-month">
                    <div class="day-number">${day}</div>
                </div>`;
            }

            // Current month's days
            for (let day = 1; day <= daysInMonth; day++) {
                const currentDay = new Date(year, month, day);
                const dayOfWeek = currentDay.getDay();
                const indonesianDay = dayNames[dayOfWeek];
                const isToday = currentDay.toDateString() === new Date().toDateString();

                // Get schedules for this day
                const daySchedules = scheduleData.filter(schedule => schedule.hari === indonesianDay);

                let scheduleHTML = '';
                daySchedules.forEach(schedule => {
                    const time = schedule.duty_start.substring(0, 5);
                    scheduleHTML += `<div class="schedule-item" title="${schedule.nama_ekstrakulikuler} - ${time}">${schedule.nama_ekstrakulikuler}</div>`;
                });

                calendarHTML += `<div class="calendar-day ${isToday ? 'today' : ''}">
                    <div class="day-number">${day}</div>
                    ${scheduleHTML}
                </div>`;
            }

            // Next month's leading days
            const totalCells = Math.ceil((startingDayOfWeek + daysInMonth) / 7) * 7;
            const remainingCells = totalCells - (startingDayOfWeek + daysInMonth);

            for (let day = 1; day <= remainingCells; day++) {
                calendarHTML += `<div class="calendar-day other-month">
                    <div class="day-number">${day}</div>
                </div>`;
            }

            document.getElementById('calendar').innerHTML = calendarHTML;
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar(currentDate);
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar(currentDate);
        }

        // Initialize calendar
        generateCalendar(currentDate);
    </script>

    <!--   Core JS Files   -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/chartjs.min.js"></script>
    <script src="../assets/js/plugins/swiper-bundle.min.js" type="text/javascript"></script>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Corporate UI Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="../assets/js/corporate-ui-dashboard.min.js?v=1.0.0"></script>
    <script src="../assets/js/notification.js"></script>
</body>

</html>