<?php
session_start();
include 'db/koneksi.php';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Administrator') {
        header("Location: admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pembina') {
        header("Location: pembina/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Siswa') {
        header("Location: siswa/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Wakil') {
        header("Location: wakil/index.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM tb_user WHERE username=? AND password=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $id_user = $_SESSION['id_user'];

        if ($user['role'] === 'Administrator') {
            $query = "SELECT * FROM tb_user INNER JOIN tb_admin ON tb_user.id_user = tb_admin.id_user WHERE tb_user.id_user = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            if ($admin) {
                $_SESSION['id_user'] = $admin['id_user'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['adm_nama'] = $admin['nama'];
                $_SESSION['adm_profile'] = $admin['profile'];
                header("Location: admin/index.php");
                exit();
            }
        } else if ($user['role'] === 'Pembina') {
            $query = "SELECT * FROM tb_user INNER JOIN tb_pembina ON tb_user.id_user = tb_pembina.id_user WHERE tb_user.id_user = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $result = $stmt->get_result();
            $pembina = $result->fetch_assoc();
            if ($pembina) {
                $_SESSION['id_user'] = $pembina['id_user'];
                $_SESSION['username'] = $pembina['username'];
                $_SESSION['pembina_nama'] = $pembina['nama'];
                $_SESSION['pembina_profile'] = $pembina['profile'];
                header("Location: pembina/index.php");
                exit();
            }
        } else if ($user['role'] === 'Siswa') {
            $query = "SELECT * FROM tb_user INNER JOIN tb_siswa ON tb_user.id_user = tb_siswa.id_user WHERE tb_user.id_user = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $result = $stmt->get_result();
            $siswa = $result->fetch_assoc();
            if ($siswa) {
                $_SESSION['id_user'] = $siswa['id_user'];
                $_SESSION['username'] = $siswa['username'];
                $_SESSION['siswa_nama'] = $siswa['nama'];
                $_SESSION['siswa_profile'] = $siswa['profile'];
                header("Location: siswa/index.php");
                exit();
            }
        } else if ($user['role'] === 'Wakil') {
            $query = "SELECT * FROM tb_user INNER JOIN tb_wakilkepalasekolah ON tb_user.id_user = tb_wakilkepalasekolah.id_user WHERE tb_user.id_user = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $wakil = $result->fetch_assoc();
                if ($wakil) {
                    $_SESSION['id_user'] = $wakil['id_user'];
                    $_SESSION['username'] = $wakil['username'];
                    $_SESSION['wakilkepalasekolah_nama'] = $wakil['nama'];
                    $_SESSION['wakilkepalasekolah_profile'] = $wakil['profile'];
                    header("Location: wakil/index.php");
                    exit();
                }
            } else {
                // If no matching record found in tb_wakilkepalasekolah
                $_SESSION['notification'] = "Data profil tidak ditemukan. Hubungi administrator.";
                $_SESSION['alert'] = "alert-warning";
                // Cleanup session
                unset($_SESSION['id_user']);
                unset($_SESSION['username']);
                unset($_SESSION['role']);
                header("Location: index.php");
                exit();
            }
        }

        // If we get here, something went wrong with the role-specific data
        $_SESSION['notification'] = "Terjadi kesalahan saat mengambil data profil. Hubungi administrator.";
        $_SESSION['alert'] = "alert-warning";
        // Cleanup session
        unset($_SESSION['id_user']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
    } else {
        $_SESSION['notification'] = "Username atau Password Salah.";
        $_SESSION['alert'] = "alert-danger";
    }
    $stmt->close();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <title>
        Sistem Informasi Ekstrakurikuler
        SMPN 8 Sutera Pesisir Selatan
    </title>
    <!--     Fonts and icons     -->
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Sans:300,400,500,600,700,800|PT+Mono:300,400,500,600,700"
        rel="stylesheet" />
    <!-- Nucleo Icons -->
    <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/349ee9c857.js" crossorigin="anonymous"></script>
    <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- CSS Files -->
    <link id="pagestyle" href="assets/css/corporate-ui-dashboard.css?v=1.0.0" rel="stylesheet" />
    <link id="pagestyle" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/notification.css">
</head>

<body class="">
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
    <div class="container position-sticky z-index-sticky top-0">
        <div class="row">
            <div class="col-12">
                <!-- Navbar -->
                <nav
                    class="navbar navbar-expand-lg blur border-radius-sm top-0 z-index-3 shadow position-absolute my-3 py-2 start-0 end-0 mx-4">
                    <div class="container-fluid px-1">
                        <a class="navbar-brand font-weight-bolder ms-lg-0 " href="index.php">
                            Ekstrakurikuler SMPN 8
                        </a>
                        <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false"
                            aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon mt-2">
                                <span class="navbar-toggler-bar bar1"></span>
                                <span class="navbar-toggler-bar bar2"></span>
                                <span class="navbar-toggler-bar bar3"></span>
                            </span>
                        </button>
                        <div class="collapse navbar-collapse" id="navigation">
                            <ul class="navbar-nav mx-auto ms-xl-auto">
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center me-2 " href="sign-up.php">
                                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="currentColor" class="opacity-6 me-1">
                                            <path fill-rule="evenodd"
                                                d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Sign Up
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center me-2 text-dark font-weight-bold"
                                        href="index.php">
                                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="currentColor" class=" text-dark  me-1">
                                            <path fill-rule="evenodd"
                                                d="M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 19.5V18h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5zm0 3a.75.75 0 000 1.5A2.25 2.25 0 0118 8.25a.75.75 0 001.5 0 3.75 3.75 0 00-3.75-3.75z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Sign In
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>
        </div>
    </div>
    <main class="main-content  mt-0">
        <section>
            <div class="page-header min-vh-100">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-4 col-md-6 d-flex flex-column mx-auto">
                            <div class="card card-plain mt-8">
                                <div class="card-header pb-0 text-left bg-transparent">
                                    <h3 class="font-weight-black text-dark display-6">Welcome back</h3>
                                    <p class="mb-0">Welcome back! Please enter your details.</p>
                                </div>
                                <div class="card-body">
                                    <form role="form" action="index.php" method="post">
                                        <label>Username</label>
                                        <div class="mb-3">
                                            <input type="text" class="form-control" placeholder="Enter your name"
                                                aria-label="Name" name="username" aria-describedby="name-addon">
                                        </div>
                                        <label>Password</label>
                                        <div class="mb-3">
                                            <input type="password" class="form-control" placeholder="Enter password"
                                                aria-label="Password" name="password" aria-describedby="password-addon">
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" name="login" class="btn btn-dark w-100 mt-4 mb-3">Sign
                                                In</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                    <p class="mb-4 text-xs mx-auto">
                                        Don't have an account?
                                        <a href="sign-up.php" class="text-dark font-weight-bold">Sign up</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="position-absolute w-40 top-0 end-0 h-100 d-md-block d-none">
                                <div class="oblique-image position-absolute fixed-top ms-auto h-100 z-index-0 bg-cover ms-n8"
                                    style="background-image:url('assets/img/image-sign-in.jpg')">
                                    <div
                                        class="blur mt-12 p-4 text-center border border-white border-radius-md position-absolute fixed-bottom m-4">
                                        <h2 class="mt-3 text-dark font-weight-bold">Sistem Informasi Ekstrakurikuler
                                            SMPN 8 Sutera Pesisir Selatan.</h2>
                                        <h6 class="text-dark text-sm mt-5">Copyright © 2025 By Tesa.</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <!--   Core JS Files   -->
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
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
    <script src="assets/js/corporate-ui-dashboard.min.js?v=1.0.0"></script>
    <script src="assets/js/notification.js"></script>
</body>

</html>