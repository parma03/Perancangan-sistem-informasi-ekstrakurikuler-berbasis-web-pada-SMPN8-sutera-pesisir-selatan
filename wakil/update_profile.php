<?php
session_start();
include '../db/koneksi.php'; // Sesuaikan path ke file koneksi database

// Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
    $_SESSION['notification'] = "Anda harus login terlebih dahulu!";
    $_SESSION['alert'] = "alert-warning";
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_SESSION['id_user'];
    $role = $_SESSION['role'];
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($nama) || empty($username)) {
        $_SESSION['notification'] = "Nama dan username tidak boleh kosong!";
        $_SESSION['alert'] = "alert-warning";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Validasi password jika diisi
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $_SESSION['notification'] = "Password lama harus diisi!";
            $_SESSION['alert'] = "alert-warning";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['notification'] = "Password baru dan konfirmasi password tidak cocok!";
            $_SESSION['alert'] = "alert-warning";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        if (strlen($new_password) < 6) {
            $_SESSION['notification'] = "Password baru minimal 6 karakter!";
            $_SESSION['alert'] = "alert-warning";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Verifikasi password lama
        $check_password = "SELECT password FROM tb_user WHERE id_user = ?";
        $stmt = $conn->prepare($check_password);
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        if ($user_data['password'] !== $current_password) {
            $_SESSION['notification'] = "Password lama tidak sesuai!";
            $_SESSION['alert'] = "alert-danger";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }

    // Handle upload foto
    $upload_success = true;
    $new_filename = null;

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = '../assets/img/profile/';

        // Buat direktori jika belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_type = $_FILES['profile_image']['type'];

        // Validasi tipe file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['notification'] = "Tipe file tidak didukung! Hanya JPG, PNG, dan GIF yang diperbolehkan.";
            $_SESSION['alert'] = "alert-warning";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Validasi ukuran file (2MB)
        if ($file_size > 2 * 1024 * 1024) {
            $_SESSION['notification'] = "Ukuran file terlalu besar! Maksimal 2MB.";
            $_SESSION['alert'] = "alert-warning";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Generate nama file unik
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_filename = $role . '_' . $id_user . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Upload file
        if (!move_uploaded_file($file_tmp, $upload_path)) {
            $upload_success = false;
            $_SESSION['notification'] = "Gagal mengupload foto profil!";
            $_SESSION['alert'] = "alert-danger";
        } else {
            // Hapus foto lama jika ada
            $old_photo_query = "";
            $profile_column = "";

            switch ($role) {
                case 'Administrator':
                    $old_photo_query = "SELECT adm_profile FROM tb_admin WHERE id_user = ?";
                    $profile_column = "adm_profile";
                    break;
                case 'Pembina':
                    $old_photo_query = "SELECT pembina_profile FROM tb_pembina WHERE id_user = ?";
                    $profile_column = "pembina_profile";
                    break;
                case 'Siswa':
                    $old_photo_query = "SELECT siswa_profile FROM tb_siswa WHERE id_user = ?";
                    $profile_column = "siswa_profile";
                    break;
                case 'Wakil':
                    $old_photo_query = "SELECT wakilkepalasekolah_profile FROM tb_wakilkepalasekolah WHERE id_user = ?";
                    $profile_column = "wakilkepalasekolah_profile";
                    break;
            }

            if ($old_photo_query && $profile_column) {
                $stmt = $conn->prepare($old_photo_query);
                $stmt->bind_param("i", $id_user);
                $stmt->execute();
                $result = $stmt->get_result();
                $old_data = $result->fetch_assoc();

                // Perbaikan: gunakan nama kolom yang sesuai dan cek apakah file ada
                if ($old_data && !empty($old_data[$profile_column])) {
                    $old_file_path = $upload_dir . $old_data[$profile_column];
                    if (file_exists($old_file_path)) {
                        if (!unlink($old_file_path)) {
                            // Log error jika gagal menghapus file
                            error_log("Gagal menghapus file lama: " . $old_file_path);
                        }
                    }
                }
                $stmt->close();
            }
        }
    }

    if ($upload_success) {
        try {
            $conn->begin_transaction();

            // Update tb_user
            if (!empty($new_password)) {
                $update_user = "UPDATE tb_user SET username = ?, password = ? WHERE id_user = ?";
                $stmt = $conn->prepare($update_user);
                $stmt->bind_param("ssi", $username, $new_password, $id_user);
            } else {
                $update_user = "UPDATE tb_user SET username = ? WHERE id_user = ?";
                $stmt = $conn->prepare($update_user);
                $stmt->bind_param("si", $username, $id_user);
            }
            $stmt->execute();

            // Update tabel role-specific
            $update_profile_query = "";
            switch ($role) {
                case 'Administrator':
                    if ($new_filename) {
                        $update_profile_query = "UPDATE tb_admin SET adm_nama = ?, adm_profile = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("ssi", $nama, $new_filename, $id_user);
                    } else {
                        $update_profile_query = "UPDATE tb_admin SET adm_nama = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("si", $nama, $id_user);
                    }
                    break;

                case 'Pembina':
                    if ($new_filename) {
                        $update_profile_query = "UPDATE tb_pembina SET pembina_nama = ?, pembina_profile = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("ssi", $nama, $new_filename, $id_user);
                    } else {
                        $update_profile_query = "UPDATE tb_pembina SET pembina_nama = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("si", $nama, $id_user);
                    }
                    break;

                case 'Siswa':
                    if ($new_filename) {
                        $update_profile_query = "UPDATE tb_siswa SET siswa_nama = ?, siswa_profile = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("ssi", $nama, $new_filename, $id_user);
                    } else {
                        $update_profile_query = "UPDATE tb_siswa SET siswa_nama = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("si", $nama, $id_user);
                    }
                    break;

                case 'Wakil':
                    if ($new_filename) {
                        $update_profile_query = "UPDATE tb_wakilkepalasekolah SET wakilkepalasekolah_nama = ?, wakilkepalasekolah_profile = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("ssi", $nama, $new_filename, $id_user);
                    } else {
                        $update_profile_query = "UPDATE tb_wakilkepalasekolah SET wakilkepalasekolah_nama = ? WHERE id_user = ?";
                        $stmt = $conn->prepare($update_profile_query);
                        $stmt->bind_param("si", $nama, $id_user);
                    }
                    break;
            }

            if ($update_profile_query) {
                $stmt->execute();
            }

            $conn->commit();

            // Update session variables
            $_SESSION['username'] = $username;
            switch ($role) {
                case 'Administrator':
                    $_SESSION['adm_nama'] = $nama;
                    if ($new_filename)
                        $_SESSION['adm_profile'] = $new_filename;
                    break;
                case 'Pembina':
                    $_SESSION['pembina_nama'] = $nama;
                    if ($new_filename)
                        $_SESSION['pembina_profile'] = $new_filename;
                    break;
                case 'Siswa':
                    $_SESSION['siswa_nama'] = $nama;
                    if ($new_filename)
                        $_SESSION['siswa_profile'] = $new_filename;
                    break;
                case 'Wakil':
                    $_SESSION['wakilkepalasekolah_nama'] = $nama;
                    if ($new_filename)
                        $_SESSION['wakilkepalasekolah_profile'] = $new_filename;
                    break;
            }

            $_SESSION['notification'] = "Profile berhasil diperbarui!";
            $_SESSION['alert'] = "alert-success";

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['notification'] = "Terjadi kesalahan saat memperbarui profile: " . $e->getMessage();
            $_SESSION['alert'] = "alert-danger";
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Jika bukan POST request, redirect
header("Location: index.php");
exit();
?>