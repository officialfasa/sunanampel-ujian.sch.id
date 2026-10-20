<?php
session_start();
require_once 'koneksi.php';

// Jika pengguna sudah dalam keadaan login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: siswa_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran - Portal E-Ujian</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #198754 0%, #20c997 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .card-custom { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            background: rgba(255, 255, 255, 0.95); 
        }
        .header-custom { 
            background: linear-gradient(45deg, #0d6efd, #0dcaf0); 
            color: white; 
            padding: 25px; 
            text-align: center; 
            border-radius: 20px 20px 0 0; 
        }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-custom">
                
                <!-- Header Card -->
                <div class="header-custom">
                    <i class="fa-solid fa-user-plus fa-3x mb-2"></i>
                    <h4 class="fw-bold m-0">PENDAFTARAN PESERTA</h4>
                    <p class="small m-0 text-white-50">Yayasan Sunan Ampel Mulyosari</p>
                </div>

                <div class="card-body p-4">

                    <!-- Notifikasi Pesan Duplicate -->
                    <?php if (isset($_GET['pesan']) &&$_GET['pesan'] == 'duplicate'): ?>
                        <div class="alert alert-warning alert-dismissible fade show small rounded-3" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> NISN atau Username sudah terdaftar! Silakan gunakan data lain.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- FORM PENDAFTARAN MANDIRI -->
                    <form action="proses_daftar.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Nama Lengkap Siswa</label>
                            <input type="text" name="full_name" class="form-control rounded-3" placeholder="Contoh: Ahmad Subagja" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">NISN</label>
                            <input type="text" name="nisn" class="form-control rounded-3" placeholder="Contoh: 0081234567" required>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Username Login</label>
                                <input type="text" name="username" class="form-control rounded-3" placeholder="Username" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Password</label>
                                <input type="password" name="password" class="form-control rounded-3" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Jenjang</label>
                                <select name="school_level" id="regSchool" class="form-select rounded-3" onchange="updateRegClass()">
                                    <option value="MI">MI (Ibtidaiyah)</option>
                                    <option value="MTs">MTs (Tsanawiyah)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Kelas</label>
                                <select name="grade_level" id="regGrade" class="form-select rounded-3"></select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill py-2 shadow-sm mb-3">
                            <i class="fa-solid fa-paper-plane me-1"></i> KIRIM PENDAFTARAN
                        </button>
                    </form>

                    <!-- Navigasi Kembali ke Login -->
                    <div class="text-center pt-2 border-top">
                        <p class="small text-muted mb-0">Sudah punya akun?</p>
                        <a href="index.php" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-4 mt-2">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Kembali ke Login
                        </a>
                    </div>

                </div>

                <!-- Footer Card -->
                <div class="card-footer bg-light text-center py-3 border-0 rounded-bottom-4">
                    <small class="text-muted"><i class="fa-solid fa-shield-halved text-success me-1"></i> Sistem Terintegrasi Database Yayasan</small>
                </div>

            </div>
        </div>
    </div>
</div>
<?php if (isset($_GET['pesan'])): ?>
    <?php if ($_GET['pesan'] == 'registered'): ?>
        <div class="alert alert-success alert-dismissible fade show small rounded-3" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i> <strong>Pendaftaran Berhasil!</strong><br>
            Data Anda telah terkirim. Silakan tunggu verifikasi dari Admin/Panitia sebelum melakukan login.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['pesan'] == 'duplicate'): ?>
        <div class="alert alert-warning alert-dismissible fade show small rounded-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> NISN atau Username sudah terdaftar! Gunakan data lain.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>
<!-- JS Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Penyesuaian Pilihan Kelas Otomatis Berdasarkan Jenjang
function updateRegClass() {
    const level = document.getElementById('regSchool').value;
    const gradeSelect = document.getElementById('regGrade');
    gradeSelect.innerHTML = '';
    
    // MI: Kelas 1-6, MTs: Kelas 7-9
    const min = level === 'MI' ? 1 : 7;
    const max = level === 'MI' ? 6 : 9;
    
    for (let i = min; i <= max; i++) {
        gradeSelect.innerHTML += `<option value="${i}">Kelas ${i}</option>`;
    }
}
// Jalankan fungsi saat halaman dibuka
updateRegClass();
</script>
</body>
</html>